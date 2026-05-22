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
        Schema::create('diagram_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('file_path')->nullable();
            $table->string('status')->default('Analisado');
            $table->json('components')->nullable();
            $table->json('risks')->nullable();
            $table->json('recommendations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_analyses');
    }
};
