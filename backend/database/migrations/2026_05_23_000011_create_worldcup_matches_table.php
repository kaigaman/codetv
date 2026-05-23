<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worldcup_matches', function (Blueprint $table) {
            $table->id();
            $table->string('match_name');
            $table->string('stream_url')->nullable();
            $table->string('iframe_url')->nullable();
            $table->string('source')->nullable();
            $table->string('country')->nullable();
            $table->string('sport')->default('football');
            $table->boolean('is_live')->default(false);
            $table->timestamp('match_time')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['is_live', 'match_time']);
            $table->index(['country']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worldcup_matches');
    }
};
