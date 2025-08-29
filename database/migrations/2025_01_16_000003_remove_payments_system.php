
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // حذف العلاقة مع جدول payments من جدول subscriptions
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'payment_id')) {
                $table->dropForeign(['payment_id']);
                $table->dropColumn('payment_id');
            }
        });

        // حذف جدول payments
        Schema::dropIfExists('payments');
    }

    public function down()
    {
        // إعادة إنشاء جدول payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 8, 2);
            $table->string('currency', 3)->default('EGP');
            $table->enum('payment_method', ['vodafone_cash'])->default('vodafone_cash');
            $table->string('vodafone_number')->nullable();
            $table->string('sender_number')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->json('payment_data')->nullable();
            $table->timestamps();

            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
        });

        // إعادة إضافة العلاقة مع جدول payments في جدول subscriptions
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_id')->nullable()->after('course_id');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
        });
    }
};
