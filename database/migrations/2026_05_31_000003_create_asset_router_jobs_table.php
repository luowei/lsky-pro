<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('asset_router_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id')->nullable();
            $table->string('type', 64);
            $table->string('status', 32)->default('queued');
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('asset_router_assets')->nullOnDelete();
            $table->index(['type', 'status']);
            $table->index(['asset_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('asset_router_jobs');
    }
};
