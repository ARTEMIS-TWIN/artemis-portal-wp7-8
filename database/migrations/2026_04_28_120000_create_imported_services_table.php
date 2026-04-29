<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imported_services', function (Blueprint $table): void {
            $table->id();
            $table->string('source_path')->nullable();
            $table->string('service_uri')->nullable();
            $table->string('record_id')->unique();
            $table->string('status')->default('linked');
            $table->string('service_type')->nullable();
            $table->string('title')->nullable();
            $table->string('provider_name')->nullable();
            $table->text('url')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imported_services');
    }
};

