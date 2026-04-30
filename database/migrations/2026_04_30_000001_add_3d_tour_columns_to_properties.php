<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Original walk-through video uploaded by the host (S3 path)
            $table->string('tour_video_path', 500)->nullable()->after('description');
            // KIRI Engine task identifier returned after upload
            $table->string('tour_3d_serialize', 100)->nullable()->after('tour_video_path');
            // Lifecycle: none | uploading | processing | ready | failed
            $table->string('tour_3d_status', 30)->default('none')->after('tour_3d_serialize');
            // Final 3D model archive URL stored on S3 once KIRI finishes (.zip / .splat / .ply)
            $table->string('tour_3d_model_url', 500)->nullable()->after('tour_3d_status');
            // When the 3D model was last refreshed
            $table->timestamp('tour_3d_processed_at')->nullable()->after('tour_3d_model_url');
            // Optional human-readable error if processing failed
            $table->string('tour_3d_error', 255)->nullable()->after('tour_3d_processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'tour_video_path',
                'tour_3d_serialize',
                'tour_3d_status',
                'tour_3d_model_url',
                'tour_3d_processed_at',
                'tour_3d_error',
            ]);
        });
    }
};
