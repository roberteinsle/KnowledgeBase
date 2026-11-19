<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 16)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('space_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            // 'user' or 'group' (morph map), role: reader | editor | admin
            $table->string('subject_type', 20);
            $table->unsignedBigInteger('subject_id');
            $table->string('role', 20);
            $table->timestampsTz();
            $table->unique(['space_id', 'subject_type', 'subject_id']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('space_permissions');
        Schema::dropIfExists('spaces');
    }
};
