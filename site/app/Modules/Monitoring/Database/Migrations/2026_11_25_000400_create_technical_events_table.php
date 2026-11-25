<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technical_events', function (Blueprint $table): void {
            $table->id();
            $table->string('route_name', 120);
            $table->string('method', 10);
            $table->unsignedSmallInteger('status');
            $table->double('duration_ms');
            $table->boolean('verification')->default(false);
            $table->timestamp('created_at');
            $table->index(['verification', 'route_name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_events');
    }
};
