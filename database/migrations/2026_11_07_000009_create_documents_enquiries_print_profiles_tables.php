<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('documentable_type', 120);
            $table->unsignedBigInteger('documentable_id');
            $table->string('disk', 30)->default('private');
            $table->string('path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['documentable_type', 'documentable_id']);
            $table->index('expires_at');
        });

        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 20);
            $table->string('email', 150)->nullable();
            $table->string('service_interest', 150)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new');
            $table->string('ip', 45)->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('print_cost_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('paper_cost_per_sheet', 12, 4)->nullable();
            $table->decimal('ink_cost_per_page', 12, 4)->nullable();
            $table->decimal('maintenance_per_page', 12, 4)->nullable();
            $table->decimal('other_cost_per_page', 12, 4)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_cost_profiles');
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('documents');
    }
};
