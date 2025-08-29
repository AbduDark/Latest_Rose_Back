<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, delete all failed jobs with high attempts
        DB::table('jobs')->where('attempts', '>', 10)->delete();
        
        // Update the attempts column to allow higher values
        Schema::table('jobs', function (Blueprint $table) {
            $table->integer('attempts')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->tinyInteger('attempts')->change();
        });
    }
};
