<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 191)->unique();
            $table->string('email', 191)->nullable()->index();
            $table->string('name', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('remote_created_at')->nullable()->index();
            $table->timestamps();

            $table->index(['created_at', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};