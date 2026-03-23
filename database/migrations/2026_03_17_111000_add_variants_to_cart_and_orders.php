<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('selected_size')->nullable()->after('quantity');
            $table->string('selected_color')->nullable()->after('selected_size');
            // Remove unique constraint that blocks multiple variants of the same product
            $table->dropUnique(['cart_id', 'product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('selected_size')->nullable()->after('quantity');
            $table->string('selected_color')->nullable()->after('selected_size');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn(['selected_size', 'selected_color']);
            $table->unique(['cart_id', 'product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['selected_size', 'selected_color']);
        });
    }
};
