<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable();
        });

        Schema::table('columns', function (Blueprint $table): void {
            $table->integer('wip_limit')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cards', fn (Blueprint $table) => $table->dropColumn('archived_at'));
        Schema::table('columns', fn (Blueprint $table) => $table->dropColumn('wip_limit'));
    }
};
