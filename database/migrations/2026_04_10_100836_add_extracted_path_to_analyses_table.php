<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->string('zip_path')->nullable()->after('error');
            $table->string('extracted_path')->nullable()->after('zip_path');
        });
    }

    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropColumn(['zip_path', 'extracted_path']);
        });
    }
};
