<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('title', 255);
            $table->text('content')->default('');
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['space_id', 'parent_id', 'position']);
        });

        Schema::create('page_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title', 255);
            $table->text('content');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['page_id', 'version']);
        });

        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->timestampsTz();
        });

        Schema::create('label_page', function (Blueprint $table) {
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->primary(['label_id', 'page_id']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('original_name', 255);
            $table->string('path', 255);
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->text('text_content')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        // Full-text search: language-neutral 'simple' config because pages can be
        // written in any of the five UI languages; prefix matching does the rest.
        DB::statement(<<<'SQL'
            ALTER TABLE pages ADD COLUMN search_vector tsvector GENERATED ALWAYS AS (
                setweight(to_tsvector('simple'::regconfig, coalesce(title, '')), 'A') ||
                setweight(to_tsvector('simple'::regconfig, coalesce(content, '')), 'B')
            ) STORED
        SQL);
        DB::statement('CREATE INDEX pages_search_vector_idx ON pages USING GIN (search_vector)');

        DB::statement(<<<'SQL'
            ALTER TABLE attachments ADD COLUMN search_vector tsvector GENERATED ALWAYS AS (
                setweight(to_tsvector('simple'::regconfig, coalesce(original_name, '')), 'A') ||
                setweight(to_tsvector('simple'::regconfig, coalesce(text_content, '')), 'B')
            ) STORED
        SQL);
        DB::statement('CREATE INDEX attachments_search_vector_idx ON attachments USING GIN (search_vector)');
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('label_page');
        Schema::dropIfExists('labels');
        Schema::dropIfExists('page_versions');
        Schema::dropIfExists('pages');
    }
};
