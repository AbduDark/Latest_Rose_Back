<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // إضافة حقول الفيديو الجديدة
            $table->enum('video_status', ['processing', 'ready', 'failed'])->nullable()->after('video_path');
            $table->integer('video_duration')->nullable()->comment('مدة الفيديو بالثواني')->after('video_status');
            $table->bigInteger('video_size')->nullable()->comment('حجم الفيديو بالبايت')->after('video_duration');

            // إضافة فهارس للبحث السريع
            $table->index('video_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex(['video_status']);
            $table->dropColumn(['video_status', 'video_duration', 'video_size']);
        });
    }
};
