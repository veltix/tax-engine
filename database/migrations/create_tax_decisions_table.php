<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_decisions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('order_reference')->index();
            $table->string('seller_country', 2);
            $table->string('buyer_country', 2);
            $table->string('resolved_country', 2);
            $table->string('tax_scheme');
            $table->string('rule_version')->nullable();
            $table->string('rate_version')->nullable();
            $table->json('snapshot_data');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_decisions');
    }
};
