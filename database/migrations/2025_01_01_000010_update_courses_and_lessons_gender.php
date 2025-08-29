
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove target_gender from courses
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('target_gender');
        });

        // Add target_gender to lessons

        // Update subscriptions to include expiry and admin approval
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->text('admin_notes')->nullable();
        });

        // Add profile image to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_image')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->enum('target_gender', ['male', 'female', 'both'])->default('both');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('target_gender');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['is_approved', 'approved_at', 'admin_notes']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_image');
        });
    }
};
