<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('v2_subweb')) {
            Schema::create('v2_subweb', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('domain')->unique();
                $table->string('owner_email');
                $table->string('status')->default('active');
                $table->string('plan')->nullable();
                $table->text('permission')->nullable();
                $table->integer('user_id')->nullable();
                $table->integer('discount')->default(0);
                $table->integer('commission')->default(0);
                $table->integer('income')->default(0);
                $table->integer('order_id')->nullable();
                $table->string('theme')->nullable();
                $table->json('payment')->nullable();
                $table->integer('created_at');
                $table->integer('updated_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('v2_subweb');
    }
};
