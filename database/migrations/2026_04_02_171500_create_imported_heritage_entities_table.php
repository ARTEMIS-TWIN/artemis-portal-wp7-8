<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imported_heritage_entities', function (Blueprint $table): void {
            $table->id();
            $table->string('graph_uri')->nullable();
            $table->string('entity_uri')->nullable();
            $table->string('record_id')->unique();
            $table->string('status')->default('linked');
            $table->string('entity_type')->nullable();
            $table->string('label')->nullable();
            $table->string('place_label')->nullable();
            $table->string('country_label')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imported_heritage_entities');
    }
};
