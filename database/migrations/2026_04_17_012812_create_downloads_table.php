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
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->string('file_url', 255);
            $table->string('file_size', 50)->nullable(); // '2.4 MB'
            $table->string('file_type', 50)->nullable(); // 'PDF', 'PPTX', 'XLSX'
            $table->string('icon_class', 50)->nullable(); // 'fas fa-file-pdf'
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->integer('download_count')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            
            $table->index('slug');
            $table->index('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};
