<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Services\KiriEngineService;
use Illuminate\Console\Command;

/**
 * Retry the extraction + S3 upload of the 3D model for a property whose
 * KIRI processing already finished but whose splat file failed to land on S3
 * (typically because of a one-off upload error or an old policy issue).
 *
 * Usage: php artisan tour:retry {property_id}
 */
class RetryTour3D extends Command
{
    protected $signature = 'tour:retry {property_id : ID of the property to reprocess}';

    protected $description = 'Re-run the KIRI splat download + S3 upload for a property whose 3D model is missing';

    public function handle(KiriEngineService $kiri): int
    {
        $id = (int) $this->argument('property_id');
        $property = Property::find($id);
        if (!$property) {
            $this->error("Property {$id} not found");
            return self::FAILURE;
        }
        if (!$property->tour_3d_serialize) {
            $this->error("Property {$id} has no KIRI serialize ID (no tour video uploaded yet)");
            return self::FAILURE;
        }

        $this->info("Retrying tour 3D for property {$id} (serialize: {$property->tour_3d_serialize})");

        $publicUrl = $kiri->downloadAndExtractSplat($property->tour_3d_serialize, $property->id);
        if ($publicUrl) {
            $property->update([
                'tour_3d_status' => 'ready',
                'tour_3d_model_url' => $publicUrl,
                'tour_3d_processed_at' => now(),
                'tour_3d_error' => null,
            ]);
            $this->info("Done. Model URL: {$publicUrl}");
            return self::SUCCESS;
        }

        $this->error('Extraction failed; check the application log for details.');
        return self::FAILURE;
    }
}
