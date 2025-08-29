
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('courses', 'grade')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->enum('grade', ['الاول', 'الثاني', 'الثالث'])->default('الاول')->after('language');
            });
        }
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }
};
