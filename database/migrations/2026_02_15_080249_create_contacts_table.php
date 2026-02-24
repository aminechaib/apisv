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
    Schema::create('contacts', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->string('company')->nullable();
        $table->string('activity')->nullable(); // This is for the job title
        $table->text('address')->nullable();
        $table->string('website')->nullable();
        $table->string('image_path')->nullable(); // Path to the original image file
        $table->decimal('confidence_score', 3, 2)->default(0.0);
        $table->boolean('needs_review')->default(false);
        $table->enum('status', ['processing', 'validated', 'failed'])->default('processing');
        $table->timestamps(); // This adds created_at and updated_at columns
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
