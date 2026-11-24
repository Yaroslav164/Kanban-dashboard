<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('completed')->default(false);
            $table->jsonb('labels')->default('[]');
            $table->jsonb('checklists')->default('[]');
        });

        Schema::create('card_assignees', function (Blueprint $table) {
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['card_id', 'user_id']);
            $table->index(['user_id', 'card_id']);
        });

        Schema::create('card_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestampTz('created_at');
            $table->index(['card_id', 'id']);
        });

        Schema::create('card_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('mime_type');
            $table->unsignedInteger('size');
            $table->binary('content');
            $table->timestampTz('created_at');
            $table->index(['card_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_attachments');
        Schema::dropIfExists('card_comments');
        Schema::dropIfExists('card_assignees');
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'due_date', 'completed', 'labels', 'checklists']);
        });
    }
};
