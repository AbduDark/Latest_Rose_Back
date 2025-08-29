
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add new columns for Vodafone Cash
            $table->string('currency', 3)->default('EGP')->after('amount');
            $table->string('vodafone_number')->nullable()->after('payment_method');
            $table->string('sender_number')->nullable()->after('vodafone_number');
            $table->string('transaction_reference')->nullable()->after('sender_number');
            $table->string('rejection_reason')->nullable()->after('admin_notes');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->unsignedBigInteger('approved_by')->nullable()->after('rejected_at');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_by');
            $table->json('payment_data')->nullable()->after('rejected_by');

            // Add foreign keys
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');

            // Remove old columns if they exist
            if (Schema::hasColumn('payments', 'phone_number')) {
                $table->dropColumn('phone_number');
            }
            if (Schema::hasColumn('payments', 'transaction_id')) {
                $table->dropColumn('transaction_id');
            }
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            
            $table->dropColumn([
                'currency',
                'vodafone_number',
                'sender_number',
                'transaction_reference',
                'rejection_reason',
                'rejected_at',
                'approved_by',
                'rejected_by',
                'payment_data'
            ]);
        });
    }
};
