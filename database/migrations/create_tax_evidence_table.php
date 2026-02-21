<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_evidence', function (Blueprint $table) {
            $table->id();
            $table->string('decision_id');
            $table->string('evidence_type');
            $table->text('evidence_value');
            $table->string('country_code', 2);
            $table->string('source');
            $table->timestamp('captured_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('decision_id')
                ->references('id')
                ->on('tax_decisions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_evidence');
    }
};
