<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // OAuth authorize URLs (MCP clients) are longer than 255 characters.
        Schema::table('magic_links', function (Blueprint $table) {
            $table->string('redirect_to', 2048)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('magic_links', function (Blueprint $table) {
            $table->string('redirect_to', 255)->nullable()->change();
        });
    }
};
