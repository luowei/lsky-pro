<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('asset_router_assets', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->uuid('id')->primary();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('key')->unique();
            $table->string('display_name')->default('');
            $table->string('original_name')->default('');
            $table->string('mime_type', 128);
            $table->string('extension', 32)->default('');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('sha256', 64)->nullable()->index();
            $table->string('md5', 32)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('visibility', 32)->default('public')->index();
            $table->string('asset_type', 32)->default('image')->index();
            $table->string('status', 32)->default('active')->index();
            $table->string('canonical_url', 1000);
            $table->string('members_url', 1000)->nullable();
            $table->string('primary_provider', 64)->default('r2');
            $table->json('metadata')->nullable();
            $table->string('created_by')->nullable();
            $table->string('uploaded_ip')->default('');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_user_id', 'created_at']);
            $table->index(['visibility', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('asset_router_assets');
    }
};
