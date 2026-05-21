<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('stream_url');
            $table->string('stream_type')->default('hls');
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('language_id')->nullable()->constrained()->nullOnDelete();
            $table->string('logo_url')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->string('resolution')->nullable();
            $table->boolean('is_hd')->default(false);
            $table->boolean('is_geoblocked')->default(false);
            $table->boolean('is_online')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('source')->default('iptv-org');
            $table->string('tvg_id')->nullable();
            $table->string('tvg_name')->nullable();
            $table->string('tvg_url')->nullable();
            $table->float('latency_ms')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_online_at')->nullable();
            $table->timestamps();

            $table->index(['country_id', 'is_online']);
            $table->index(['category_id']);
            $table->index(['language_id']);
            $table->index(['is_active', 'is_online']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
