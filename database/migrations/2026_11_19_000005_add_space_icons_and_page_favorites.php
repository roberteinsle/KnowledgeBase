<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spaces', function (Blueprint $table) {
            // Uploaded image on the private disk; takes precedence over the emoji.
            $table->string('icon_path', 255)->nullable()->after('icon');
        });

        Schema::create('page_favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->primary(['user_id', 'page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_favorites');
        Schema::table('spaces', fn (Blueprint $table) => $table->dropColumn('icon_path'));
    }
};
