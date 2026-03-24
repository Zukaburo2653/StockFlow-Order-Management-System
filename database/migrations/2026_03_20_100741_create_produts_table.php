<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->unsigned();
            $table->unsignedInteger('stock')->default(0);
            $table->string('sku', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->softDeletes(); // Prevent order creation if product deleted
            $table->timestamps();

            // Indexes for performance
            $table->index('is_active');
            $table->index('stock');
            $table->index(['is_active', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
