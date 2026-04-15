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
        Schema::table('analyses', function (Blueprint $table) {
            $table->text('features_content')->nullable();
            $table->text('ui_content')->nullable();
            $table->text('flow_content')->nullable();
            $table->text('mermaid_content')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropColumn(['features_content', 'ui_content', 'flow_content', 'mermaid_content']);
        });
    }
};
