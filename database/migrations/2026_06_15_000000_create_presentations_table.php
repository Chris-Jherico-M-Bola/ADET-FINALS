<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presentations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('title');
            $table->string('original_name');
            $table->string('directory');
            $table->string('source_path');
            $table->string('pdf_path')->nullable();
            $table->string('status')->default('processing');
            $table->text('error_message')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presentations');
    }
};
