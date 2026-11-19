<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('default_locale', 5)->default('de');
            $table->boolean('enforce_mfa')->default(false);
            $table->timestampsTz();
        });

        DB::table('workspaces')->insert([
            'name' => 'Knowledgebase',
            'default_locale' => 'de',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            // 'everyone' marks the built-in group every user belongs to implicitly.
            $table->string('system_key', 50)->nullable()->unique();
            $table->timestampsTz();
        });

        DB::table('groups')->insert([
            'name' => 'Everyone',
            'description' => null,
            'system_key' => 'everyone',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('group_user', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['group_id', 'user_id']);
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('subject_type', 50)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->jsonb('properties')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('workspaces');
    }
};
