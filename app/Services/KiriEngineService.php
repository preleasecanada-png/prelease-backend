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
