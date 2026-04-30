<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the KIRI Engine 3D Gaussian Splatting API.
 *
 * Docs: https://docs.kiriengine.app
 *  - POST /api/v1/open/3dgs/video           upload a video, returns { serialize }
 *  - GET  /api/v1/open/model/getStatus      poll status of a serialize
 *  - GET  /api/v1/open/model/getModelZip    fetch a 60-min signed download URL
 *
 * Status codes returned by getStatus (per KIRI docs):
 *  0 = uploaded / queued
 *  1 = processing
 *  2 = success
 *  3 = failed
 */
class KiriEngineService
{
    protected string $baseUrl = 'https://api.kiriengine.app/api/v1/open';

    public function isConfigured(): bool
    {
        return !empty(config('services.kiri.api_key'));
    }

    protected function client(): PendingRequest
    {
        return Http::withToken(config('services.kiri.api_key'))
            ->acceptJson()
            ->timeout(120)
            ->retry(2, 500, throw: false);
    }

    /**
     * Upload a video file to KIRI Engine for 3DGS reconstruction.
     *
     * @param  string  $absoluteVideoPath  local filesystem path to the video to upload
     * @return string|null KIRI serialize (task id) on success, null otherwise
     */
    public function uploadVideo(string $absoluteVideoPath, bool $generateMesh = false): ?string
    {
        if (!$this->isConfigured()) {
            Log::warning('KIRI Engine: API key not configured, skipping upload');
            return null;
        }

        if (!file_exists($absoluteVideoPath)) {
            Log::error('KIRI Engine: video file not found', ['path' => $absoluteVideoPath]);
            return null;
        }

        try {
            $response = $this->client()
                ->attach('videoFile', file_get_contents($absoluteVideoPath), basename($absoluteVideoPath))
                ->post($this->baseUrl . '/3dgs/video', [
                    'isMesh' => $generateMesh ? '1' : '0',
                    'isMask' => '0',
                ]);

            if (!$response->successful()) {
                Log::error('KIRI upload failed', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $data = $response->json();
            if (($data['code'] ?? null) !== 0) {
                Log::error('KIRI upload returned error', ['response' => $data]);
                return null;
            }

            return $data['data']['serialize'] ?? null;
        } catch (\Throwable $e) {
            Log::error('KIRI upload exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the current status of a 3D model task.
     * Returns one of: 'queued', 'processing', 'ready', 'failed', 'unknown'.
     */
    public function getStatus(string $serialize): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'unknown', 'raw' => null];
        }

        try {
            $response = $this->client()->get($this->baseUrl . '/model/getStatus', [
                'serialize' => $serialize,
            ]);

            if (!$response->successful()) {
                return ['status' => 'unknown', 'raw' => $response->body()];
            }

            $data = $response->json();
            $code = $data['data']['status'] ?? null;
            return [
                'status' => $this->mapStatus($code),
                'raw_code' => $code,
                'raw' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('KIRI getStatus exception: ' . $e->getMessage());
            return ['status' => 'unknown', 'raw' => null];
        }
    }

    /**
     * Returns a temporary (60 min) URL to the zipped 3D model.
     */
    public function getModelUrl(string $serialize): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->client()->get($this->baseUrl . '/model/getModelZip', [
                'serialize' => $serialize,
            ]);

            if (!$response->successful()) {
                Log::error('KIRI getModelZip failed', ['status' => $response->status()]);
                return null;
            }

            $data = $response->json();
            if (($data['code'] ?? null) !== 0) {
                Log::warning('KIRI getModelZip returned non-zero code', ['response' => $data]);
                return null;
            }

            return $data['data']['modelUrl'] ?? null;
        } catch (\Throwable $e) {
            Log::error('KIRI getModelUrl exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Download the KIRI ZIP, extract the first viewable Gaussian Splat file
     * (.splat preferred, .ply fallback) and upload it to a permanent S3
     * location. Returns the public URL of the extracted file or null on
     * failure.
     *
     * The browser viewer (@mkkellogg/gaussian-splats-3d) needs a direct file
     * URL, so we cannot use the temporary signed URL provided by KIRI.
     *
     * Memory note: Lambda /tmp is capped at ~512 MB. Typical KIRI splats are
     * 30-150 MB so this fits comfortably. We stream the download to disk
     * rather than loading it in memory.
     */
    public function downloadAndExtractSplat(string $serialize, int $propertyId): ?string
    {
        $modelUrl = $this->getModelUrl($serialize);
        if (!$modelUrl) {
            Log::warning('Cannot extract splat: missing KIRI model URL', ['serialize' => $serialize]);
            return null;
        }

        $tmpDir = sys_get_temp_dir() . '/kiri_' . $serialize;
        $zipPath = $tmpDir . '.zip';

        try {
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0755, true);
            }

            // 1. Stream the KIRI ZIP to /tmp so we don't OOM on big payloads.
            $context = stream_context_create([
                'http' => ['timeout' => 600],
                'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
            ]);
            $bytesWritten = file_put_contents($zipPath, fopen($modelUrl, 'rb', false, $context));
            if ($bytesWritten === false || $bytesWritten === 0) {
                Log::error('KIRI zip download failed', ['url' => $modelUrl]);
                return null;
            }

            // 2. Extract using PHP's built-in ZipArchive.
            if (!class_exists(\ZipArchive::class)) {
                Log::error('ZipArchive extension is not available in this PHP runtime');
                return null;
            }

            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                Log::error('Could not open KIRI zip archive', ['path' => $zipPath]);
                return null;
            }
            $zip->extractTo($tmpDir);
            $zip->close();

            // 3. Find the best splat file inside the extraction dir.
            //    Order of preference: .splat > .ksplat > .ply (largest size wins as tiebreaker).
            $candidates = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tmpDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (!$file->isFile()) continue;
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['splat', 'ksplat', 'ply'])) {
                    $candidates[] = ['path' => $file->getPathname(), 'ext' => $ext, 'size' => $file->getSize()];
                }
            }

            if (empty($candidates)) {
                Log::warning('KIRI zip contained no splat/ply file', ['contents_dir' => $tmpDir]);
                return null;
            }

            usort($candidates, function ($a, $b) {
                $rank = ['splat' => 3, 'ksplat' => 2, 'ply' => 1];
                $diff = ($rank[$b['ext']] ?? 0) - ($rank[$a['ext']] ?? 0);
                return $diff !== 0 ? $diff : ($b['size'] - $a['size']);
            });
            $picked = $candidates[0];

            // 4. Upload to S3 at a stable, public-readable path.
            $extension = $picked['ext'];
            $s3Path = 'tour-models/' . $propertyId . '/model_' . $serialize . '.' . $extension;
            \Illuminate\Support\Facades\Storage::disk('s3')->put(
                $s3Path,
                fopen($picked['path'], 'rb'),
                'public'
            );

            // 5. Build the public URL. The S3 disk should already be configured
            //    with a public visibility default, but we expose the URL via the
            //    Storage facade for portability.
            $publicUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url($s3Path);

            return $publicUrl;
        } catch (\Throwable $e) {
            Log::error('KIRI splat extraction failed: ' . $e->getMessage());
            return null;
        } finally {
            // Clean up temp files so we don't fill up /tmp on warm Lambda invocations.
            try {
                if (is_file($zipPath)) @unlink($zipPath);
                if (is_dir($tmpDir)) {
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($tmpDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($iterator as $file) {
                        $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
                    }
                    @rmdir($tmpDir);
                }
            } catch (\Throwable $cleanup) {
                // best-effort cleanup
            }
        }
    }

    protected function mapStatus($code): string
    {
        return match ((int) $code) {
            0 => 'queued',
            1 => 'processing',
            2 => 'ready',
            3 => 'failed',
            default => 'unknown',
        };
    }
}
