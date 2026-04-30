<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;

/**
 * Temporary helper to reattach a known KIRI serialize to a property whose
 * tour upload was wrongly marked as failed (before the code:200 fix).
 * Delete this command once the KIRI E2E test is complete.
 */
class KiriAttachSerialize extends Command
{
    protected $signature = 'kiri:attach {property_id} {serialize}';

    protected $description = 'Attach an existing KIRI serialize to a property and mark it queued';

    public function handle(): int
    {
        $id = $this->argument('property_id');
        $serialize = $this->argument('serialize');

        $property = Property::find($id);
        if (!$property) {
            $this->error("Property #$id not found");
            return self::FAILURE;
        }

        $property->tour_3d_serialize = $serialize;
        $property->tour_3d_status = 'queued';
        $property->tour_3d_error = null;
        $property->save();

        $this->info("OK: property=$id serialize=$serialize status={$property->tour_3d_status}");
        return self::SUCCESS;
    }
}
