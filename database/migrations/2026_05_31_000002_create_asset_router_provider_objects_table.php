<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('asset_router_provider_objects', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->uuid('asset_id');
            $table->string('provider', 64);
            $table->string('provider_key', 1000);
            $table->string('url', 1000)->nullable();
            $table->string('status', 32)->default('present');
            $table->string('etag')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('asset_router_assets')->cascadeOnDelete();
            $table->unique(['asset_id', 'provider']);
            $table->index(['provider', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('asset_router_provider_objects');
    }
};
