<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2); // Snapshot price at time of order
            $table->decimal('subtotal', 12, 2);   // quantity * unit_price
            $table->timestamps();

            // ─── Indexes ─────────────────────────────────────────────────────────
            $table->index('order_id');   // Required index on order_id
            $table->index('product_id'); // Required index on product_id
            $table->unique(['order_id', 'product_id']); // Prevent duplicate products in same order

            // ─── Foreign Keys (no orphan records) ────────────────────────────────
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('cascade'); // If order deleted → items deleted too

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('restrict'); // Cannot delete product if it has order_items
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
