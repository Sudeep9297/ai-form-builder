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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('form_version');
            $table->json('payload');
            $table->string('respondent_email')->nullable();
            $table->string('respondent_name')->nullable();
            $table->string('ip_hash')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['form_id', 'created_at']);
            $table->index(['form_id', 'respondent_email']);
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['respondent_name', 'respondent_email']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
