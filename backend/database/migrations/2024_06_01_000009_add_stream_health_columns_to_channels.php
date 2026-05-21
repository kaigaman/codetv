<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->float('health_score')->nullable()->after('latency_ms');
            $table->integer('uptime_count')->default(0)->after('health_score');
            $table->integer('downtime_count')->default(0)->after('uptime_count');
            $table->float('avg_latency_ms')->nullable()->after('downtime_count');
            $table->string('last_error', 500)->nullable()->after('avg_latency_ms');
            $table->index('health_score');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropIndex(['health_score']);
            $table->dropColumn(['health_score', 'uptime_count', 'downtime_count', 'avg_latency_ms', 'last_error']);
        });
    }
};
