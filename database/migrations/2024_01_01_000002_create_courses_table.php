
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('image')->nullable();
            $table->decimal('price', 8, 2);
            $table->enum('level', ['beginner', 'intermediate', 'advanced']);
            $table->enum('target_gender', ['male', 'female', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
            $table->integer('duration_hours')->default(0);
            $table->text('requirements')->nullable();
            $table->string('instructor_name')->nullable();
            $table->string('language', 10)->default('ar');
            $table->enum('grade', ['الاول', 'الثاني', 'الثالث'])->default('الاول');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
