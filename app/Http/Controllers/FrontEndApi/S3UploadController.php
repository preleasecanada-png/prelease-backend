<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Direct-to-S3 upload helper.
 *
 * API Gateway has a 10 MB payload limit which blocks large tour videos.
 * Instead of uploading files through the Lambda/API Gateway, the frontend:
 *
 *   1. Calls POST /s3/presign with file metadata (name, type, size)
 *   2. Receives a temporary presigned S3 PUT URL (valid ~15 min)
 *   3. PUTs the file bytes directly to that URL (bypassing API Gateway)
 *   4. Calls the relevant API endpoint with the resulting S3 key
 *
 * This keeps large payloads out of Lambda/API Gateway and is cheaper/faster.
 */
class S3UploadController extends Controller
{
    /**
     * Generate a presigned S3 PUT URL for direct browser upload.
     *
     * Request body:
     *   - filename: string (original filename, e.g. "tour_video.mp4")
     *   - content_type: string (MIME type, e.g. "video/mp4")
     *   - prefix: string (optional, folder prefix like "tour-videos" or "property-images")
     *
     * Response:
     *   - upload_url: string (presigned S3 PUT URL, valid 15 min)
     *   - s3_key: string (final S3 object key to store in DB later)
     *   - bucket: string
     *   - expires_in: int (seconds until URL expiry)
     */
    public function presign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string|max:255',
            'content_type' => 'required|string|max:100',
            'size' => 'required|integer|min:1|max:5368709120', // 5 GB max (S3 limit for single PUT)
            'prefix' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        $filename = $request->input('filename');
        $contentType = $request->input('content_type');
        $prefix = $request->input('prefix', 'uploads');

        // Sanitize filename: remove path traversal, keep alphanumeric + safe chars
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($filename));
        if (empty($safeName)) {
            $safeName = 'file_' . uniqid();
        }

        // Generate a unique S3 key with the requested prefix
        $extension = pathinfo($safeName, PATHINFO_EXTENSION) ?: 'bin';
        $s3Key = $prefix . '/' . date('Y/m') . '/' . uniqid() . '_' . $safeName;

        try {
            // Build S3 client from Laravel config (works with both s3 and custom endpoints)
            $diskConfig = config('filesystems.disks.s3');
            $client = new S3Client([
                'version' => 'latest',
                'region' => $diskConfig['region'] ?? env('AWS_DEFAULT_REGION', 'us-east-1'),
                'credentials' => [
                    'key' => $diskConfig['key'] ?? env('AWS_ACCESS_KEY_ID'),
                    'secret' => $diskConfig['secret'] ?? env('AWS_SECRET_ACCESS_KEY'),
                ],
                // Use path-style endpoint if configured (for MinIO, LocalStack, etc.)
                'endpoint' => $diskConfig['endpoint'] ?? null,
                'use_path_style_endpoint' => $diskConfig['use_path_style_endpoint'] ?? false,
            ]);

            $bucket = $diskConfig['bucket'] ?? env('AWS_BUCKET');

            $command = $client->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key' => $s3Key,
                'ContentType' => $contentType,
            ]);

            $presignedRequest = $client->createPresignedRequest($command, '+15 minutes');
            $uploadUrl = (string) $presignedRequest->getUri();
            $publicUrl = rtrim($diskConfig['url'] ?? "https://{$bucket}.s3.amazonaws.com", '/') . '/' . $s3Key;

            return response()->json([
                'status' => 200,
                'data' => [
                    'upload_url' => $uploadUrl,
                    's3_key' => $s3Key,
                    'bucket' => $bucket,
                    'region' => $diskConfig['region'] ?? env('AWS_DEFAULT_REGION', 'us-east-1'),
                    'content_type' => $contentType,
                    'expires_in' => 900, // 15 minutes in seconds
                    'public_url' => $publicUrl, // Final public URL after upload
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Could not generate upload URL',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm a direct S3 upload completed successfully.
     *
     * Frontend calls this after PUTting the file to S3. We verify the object
     * exists and return its metadata so the frontend can proceed with API calls
     * that reference this file (e.g., attach it to a property).
     *
     * Request body:
     *   - s3_key: string (the key returned from /s3/presign)
     *
     * Response:
     *   - exists: bool
     *   - size: int|null
     *   - url: string (public URL)
     */
    public function confirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            's3_key' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        $s3Key = $request->input('s3_key');
        $disk = Storage::disk('s3');

        try {
            $exists = $disk->exists($s3Key);
            $size = $exists ? $disk->size($s3Key) : null;

            return response()->json([
                'status' => 200,
                'data' => [
                    'exists' => $exists,
                    'size' => $size,
                    'url' => $disk->url($s3Key),
                    's3_key' => $s3Key,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Could not verify upload',
            ], 500);
        }
    }
}
