<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('title');
            $table->text('description')->nullable();
            $table->jsonb('labels');
            $table->jsonb('checklists');
            $table->timestampsTz();
            $table->index(['board_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_templates');
    }
};
