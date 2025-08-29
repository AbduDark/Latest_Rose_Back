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
        Schema::table('subscriptions', function (Blueprint $table) {
            // Check if column exists before adding to prevent duplicate column errors
            if (!Schema::hasColumn('subscriptions', 'vodafone_number')) {
                $table->string('vodafone_number')->after('course_id');
            }
            if (!Schema::hasColumn('subscriptions', 'parent_phone')) {
                $table->string('parent_phone')->after('vodafone_number');
            }
            if (!Schema::hasColumn('subscriptions', 'student_info')) {
                $table->text('student_info')->nullable()->after('parent_phone');
            }
            if (!Schema::hasColumn('subscriptions', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('student_info');
            }
            if (!Schema::hasColumn('subscriptions', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('subscriptions', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('admin_notes');
            }
            if (!Schema::hasColumn('subscriptions', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('subscriptions', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('rejected_at');
            }
            if (!Schema::hasColumn('subscriptions', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_by');
            }

            // Add foreign keys if they don't exist (implicitly handled by checking column existence first)
            if (Schema::hasColumn('subscriptions', 'approved_by')) {
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            }
            if (Schema::hasColumn('subscriptions', 'rejected_by')) {
                $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);

            // Drop columns if they exist
            if (Schema::hasColumn('subscriptions', 'rejected_by')) {
                $table->dropColumn('rejected_by');
            }
            if (Schema::hasColumn('subscriptions', 'approved_by')) {
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('subscriptions', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
            if (Schema::hasColumn('subscriptions', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('subscriptions', 'admin_notes')) {
                $table->dropColumn('admin_notes');
            }
            if (Schema::hasColumn('subscriptions', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('subscriptions', 'student_info')) {
                $table->dropColumn('student_info');
            }
            if (Schema::hasColumn('subscriptions', 'parent_phone')) {
                $table->dropColumn('parent_phone');
            }
            if (Schema::hasColumn('subscriptions', 'vodafone_number')) {
                $table->dropColumn('vodafone_number');
            }
        });
    }
};