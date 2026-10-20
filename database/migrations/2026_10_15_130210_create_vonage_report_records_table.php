<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vonage_report_records', function (Blueprint $table) {
            $table->id();
            $table->string('product');
            $table->string('external_id');
            $table->string('account_id')->nullable();
            $table->string('direction')->nullable();
            $table->string('channel')->nullable();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->string('status')->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_description')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('country_name')->nullable();
            $table->string('network_name')->nullable();
            $table->decimal('total_price', 14, 8)->nullable();
            $table->string('currency', 3)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('finalized_at')->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->unique(['product', 'external_id']);
            $table->index(['product', 'occurred_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vonage_report_records');
    }
};
