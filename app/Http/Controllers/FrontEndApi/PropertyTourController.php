<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\KiriEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Endpoints related to the 3D virtual tour feature.
 *
 * The pipeline:
 *  1. Host uploads a walk-through video via POST /properties/{id}/tour-video.
 *  2. The video is stored on S3 (for retention) and forwarded to KIRI Engine.
 *  3. KIRI returns a "serialize" id we persist on the property.
 *  4. The host (and renters) can poll GET /properties/{id}/tour-status to
 *     check progress. Optionally, KIRI calls our webhook when the model is ready.
 *  5. When ready we expose the temporary download URL so the frontend can
 *     load the Gaussian Splat in a 3D viewer.
 */
class PropertyTourController extends Controller
{
    public function __construct(protected KiriEngineService $kiri) {}

    /**
     * Upload (or replace) a tour video for a property the caller owns.
     */
    public function uploadVideo(Request $request, $id)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json(['status' => 401, 'message' => 'Unauthenticated'], 401);
        }

        $validator = Validator::make($request->all(), [
            // Up to 250 MB to leave headroom for typical 1080p / 3-minute clips
            'tour_video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/webm,video/x-msvideo|max:262144',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        try {
            $property = Property::where('user_id', $authUser->id)->findOrFail($id);
        } catch (\Throwable $e) {
            return response()->json(['status' => 404, 'message' => 'Property not found'], 404);
        }

        if (!$this->kiri->isConfigured()) {
            return response()->json([
                'status' => 503,
                'message' => 'The 3D tour service is not configured on this server.',
            ], 503);
        }

        $file = $request->file('tour_video');
        $extension = $file->getClientOriginalExtension() ?: 'mp4';
        $s3Path = 'tour-videos/' . $property->id . '/' . uniqid('tour_') . '.' . $extension;

        // 1. Persist the original video on S3 first so we always retain it.
        try {
            Storage::disk('s3')->put($s3Path, file_get_contents($file->getRealPath()));
        } catch (\Throwable $e) {
            Log::error('Tour video S3 upload failed: ' . $e->getMessage());
            return response()->json(['status' => 500, 'message' => 'Could not store the video.'], 500);
        }

        // 2. Forward the same physical file to KIRI Engine for 3DGS processing.
        $serialize = $this->kiri->uploadVideo($file->getRealPath(), generateMesh: false);
        if (!$serialize) {
            // Rollback: keep the video, but mark the tour as failed and surface a clear error.
            $property->update([
                'tour_video_path' => $s3Path,
                'tour_3d_status' => 'failed',
                'tour_3d_error' => 'KIRI upload failed. Please try again.',
            ]);
            return response()->json([
                'status' => 502,
                'message' => 'Video stored, but the 3D conversion service rejected the upload. You can retry later.',
                'data' => $property->fresh(),
            ], 502);
        }

        $property->update([
            'tour_video_path' => $s3Path,
            'tour_3d_serialize' => $serialize,
            'tour_3d_status' => 'queued',
            'tour_3d_model_url' => null,
            'tour_3d_processed_at' => null,
            'tour_3d_error' => null,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Video uploaded. Your 3D tour is being generated, this usually takes 5 to 30 minutes.',
            'data' => [
                'tour_3d_status' => $property->tour_3d_status,
                'tour_3d_serialize' => $property->tour_3d_serialize,
                'tour_video_path' => $property->tour_video_path,
            ],
        ]);
    }

    /**
     * Returns the latest tour status for a property. Anyone can call this
     * (renters need it to render the viewer); the data exposed is harmless.
     *
     * If the status is currently "processing", we proactively refresh it from
     * KIRI Engine (cheap GET) so the user does not have to wait for the
     * webhook. When KIRI reports "ready" we fetch and persist the model URL.
     */
    public function status($id)
    {
        $property = Property::find($id);
        if (!$property) {
            return response()->json(['status' => 404, 'message' => 'Property not found'], 404);
        }

        // Refresh from KIRI while the model is still being worked on.
        // KIRI uses status 0 (queued) -> 1 (processing) -> 2 (ready) -> 3 (failed),
        // so we poll on both 'queued' and 'processing' to catch transitions.
        if (in_array($property->tour_3d_status, ['queued', 'processing'], true)
            && $property->tour_3d_serialize) {
            $remote = $this->kiri->getStatus($property->tour_3d_serialize);
            if ($remote['status'] === 'processing' && $property->tour_3d_status !== 'processing') {
                $property->update(['tour_3d_status' => 'processing']);
            }
            if ($remote['status'] === 'ready') {
                // Download the KIRI ZIP, extract the splat, upload to S3 once.
                // After this we have a permanent, public URL the browser viewer can load directly.
                $publicUrl = $this->kiri->downloadAndExtractSplat($property->tour_3d_serialize, $property->id);
                if ($publicUrl) {
                    $property->update([
                        'tour_3d_status' => 'ready',
                        'tour_3d_model_url' => $publicUrl,
                        'tour_3d_processed_at' => now(),
                        'tour_3d_error' => null,
                    ]);
                } else {
                    // Fallback: keep the temporary KIRI URL so users can at least download the ZIP.
                    $tmp = $this->kiri->getModelUrl($property->tour_3d_serialize);
                    if ($tmp) {
                        $property->update([
                            'tour_3d_status' => 'ready',
                            'tour_3d_model_url' => $tmp,
                            'tour_3d_processed_at' => now(),
                            'tour_3d_error' => 'In-app 3D viewer unavailable, model can still be downloaded.',
                        ]);
                    }
                }
            } elseif ($remote['status'] === 'failed') {
                $property->update([
                    'tour_3d_status' => 'failed',
                    'tour_3d_error' => 'KIRI Engine reported a processing failure.',
                ]);
            }
        }

        return response()->json([
            'status' => 200,
            'data' => [
                'tour_3d_status' => $property->tour_3d_status,
                'tour_3d_model_url' => $property->tour_3d_model_url,
                'tour_3d_processed_at' => $property->tour_3d_processed_at,
                'tour_3d_error' => $property->tour_3d_error,
                'has_tour_video' => !empty($property->tour_video_path),
            ],
        ]);
    }

    /**
     * Webhook endpoint called by KIRI Engine when a model's status changes.
     *
     * Configure in KIRI dashboard:
     *   Callback URL: https://<your-api>/api/webhooks/kiri-engine
     *   Signing secret: same value as KIRI_WEBHOOK_SECRET in .env
     *
     * Expected payload (per KIRI docs): JSON body with serialize and status.
     */
    public function webhook(Request $request)
    {
        $providedSecret = $request->header('X-Kiri-Signature') ?? $request->input('signature');
        $expected = config('services.kiri.webhook_secret');
        if ($expected && $providedSecret !== $expected) {
            Log::warning('KIRI webhook rejected: bad signature');
            return response()->json(['ok' => false], 401);
        }

        $serialize = $request->input('serialize');
        $statusCode = $request->input('status');
        if (!$serialize) {
            return response()->json(['ok' => false, 'error' => 'missing serialize'], 422);
        }

        $property = Property::where('tour_3d_serialize', $serialize)->first();
        if (!$property) {
            // Acknowledge anyway so KIRI doesn't keep retrying for an unknown task.
            return response()->json(['ok' => true]);
        }

        // Map the status; the payload format may vary, so try numeric first then string.
        $mapped = match ((int) $statusCode) {
            0 => 'queued',
            1 => 'processing',
            2 => 'ready',
            3 => 'failed',
            default => is_string($statusCode) ? $statusCode : 'unknown',
        };

        if ($mapped === 'ready') {
            $publicUrl = $this->kiri->downloadAndExtractSplat($serialize, $property->id);
            $url = $publicUrl ?: $this->kiri->getModelUrl($serialize);
            $property->update([
                'tour_3d_status' => 'ready',
                'tour_3d_model_url' => $url,
                'tour_3d_processed_at' => now(),
                'tour_3d_error' => $publicUrl ? null : 'In-app 3D viewer unavailable, model can still be downloaded.',
            ]);
        } elseif ($mapped === 'failed') {
            $property->update([
                'tour_3d_status' => 'failed',
                'tour_3d_error' => 'KIRI Engine reported a processing failure.',
            ]);
        } elseif (in_array($mapped, ['queued', 'processing'])) {
            $property->update(['tour_3d_status' => 'processing']);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Remove a tour video and reset the 3D status.
     */
    public function deleteVideo($id)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json(['status' => 401, 'message' => 'Unauthenticated'], 401);
        }
        $property = Property::where('user_id', $authUser->id)->find($id);
        if (!$property) {
            return response()->json(['status' => 404, 'message' => 'Property not found'], 404);
        }

        if ($property->tour_video_path && Storage::disk('s3')->exists($property->tour_video_path)) {
            try { Storage::disk('s3')->delete($property->tour_video_path); } catch (\Throwable $e) {}
        }

        $property->update([
            'tour_video_path' => null,
            'tour_3d_serialize' => null,
            'tour_3d_status' => 'none',
            'tour_3d_model_url' => null,
            'tour_3d_processed_at' => null,
            'tour_3d_error' => null,
        ]);

        return response()->json(['status' => 200, 'message' => '3D tour removed.']);
    }

    /**
     * Generate a presigned S3 PUT URL for direct browser upload.
     * This bypasses API Gateway's 10 MB limit for large tour videos.
     */
    public function presignS3Upload(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json(['status' => 401, 'message' => 'Unauthenticated'], 401);
        }

        $validator = Validator::make($request->all(), [
            'filename' => 'required|string|max:255',
            'content_type' => 'required|string|max:100',
            'size' => 'required|integer|min:1|max:5368709120',
            'prefix' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $filename = $request->input('filename');
        $contentType = $request->input('content_type');
        $prefix = $request->input('prefix', 'uploads');

        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($filename));
        if (empty($safeName)) {
            $safeName = 'file_' . uniqid();
        }

        $s3Key = $prefix . '/' . date('Y/m') . '/' . uniqid() . '_' . $safeName;

        try {
            $diskConfig = config('filesystems.disks.s3');
            $region = $diskConfig['region'] ?? env('AWS_DEFAULT_REGION', 'us-east-1');

            // On Lambda, we use the IAM role via instance profile (no explicit key/secret in env).
            // However, for presigned URLs to work with temporary session credentials,
            // we must pass the session token along with the credentials.
            $clientConfig = [
                'version' => 'latest',
                'region' => $region,
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    'token' => env('AWS_SESSION_TOKEN'), // Required for Lambda IAM role sessions
                ],
            ];

            // Support custom S3-compatible endpoints (MinIO, LocalStack, etc.)
            if (!empty($diskConfig['endpoint'])) {
                $clientConfig['endpoint'] = $diskConfig['endpoint'];
                $clientConfig['use_path_style_endpoint'] = $diskConfig['use_path_style_endpoint'] ?? false;
            }

            $client = new \Aws\S3\S3Client($clientConfig);
            $bucket = $diskConfig['bucket'] ?? env('AWS_BUCKET');

            $command = $client->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key' => $s3Key,
                'ContentType' => $contentType,
            ]);

            $uploadUrl = (string) $client->createPresignedRequest($command, '+15 minutes')->getUri();
            $publicUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$s3Key}";
            if (!empty($diskConfig['url'])) {
                $publicUrl = rtrim($diskConfig['url'], '/') . '/' . $s3Key;
            }

            return response()->json([
                'status' => 200,
                'data' => [
                    'upload_url' => $uploadUrl,
                    's3_key' => $s3Key,
                    'bucket' => $bucket,
                    'region' => $region,
                    'content_type' => $contentType,
                    'expires_in' => 900,
                    'public_url' => $publicUrl,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 500, 'message' => 'Could not generate upload URL'], 500);
        }
    }

    /**
     * Confirm a direct S3 upload completed.
     */
    public function confirmS3Upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            's3_key' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $s3Key = $request->input('s3_key');
        $disk = Storage::disk('s3');

        try {
            $exists = $disk->exists($s3Key);
            return response()->json([
                'status' => 200,
                'data' => [
                    'exists' => $exists,
                    'size' => $exists ? $disk->size($s3Key) : null,
                    'url' => $disk->url($s3Key),
                    's3_key' => $s3Key,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 500, 'message' => 'Could not verify upload'], 500);
        }
    }
}
