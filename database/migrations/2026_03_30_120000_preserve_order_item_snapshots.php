<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildOrderItemsTableForSqlite();
            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_name')->nullable()->after('product_id');
            $table->string('product_image')->nullable()->after('product_name');
        });

        DB::table('order_items')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->update([
                'product_name' => DB::raw('products.name'),
                'product_image' => DB::raw('products.image_url'),
            ]);

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->restoreOrderItemsTableForSqlite();
            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        DB::table('order_items')
            ->whereNull('product_id')
            ->delete();

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->dropColumn(['product_name', 'product_image']);
        });
    }

    private function rebuildOrderItemsTableForSqlite(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::statement('
            CREATE TABLE order_items_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NULL,
                product_name VARCHAR NOT NULL,
                product_image VARCHAR NULL,
                quantity INTEGER NOT NULL,
                selected_size VARCHAR NULL,
                selected_color VARCHAR NULL,
                unit_price NUMERIC NOT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
            )
        ');

        DB::statement('
            INSERT INTO order_items_new (
                id, order_id, product_id, product_name, product_image, quantity, selected_size, selected_color, unit_price, created_at, updated_at
            )
            SELECT
                oi.id,
                oi.order_id,
                oi.product_id,
                COALESCE(p.name, "Produit indisponible"),
                p.image_url,
                oi.quantity,
                oi.selected_size,
                oi.selected_color,
                oi.unit_price,
                oi.created_at,
                oi.updated_at
            FROM order_items oi
            LEFT JOIN products p ON p.id = oi.product_id
        ');

        DB::statement('DROP TABLE order_items');
        DB::statement('ALTER TABLE order_items_new RENAME TO order_items');

        Schema::enableForeignKeyConstraints();
    }

    private function restoreOrderItemsTableForSqlite(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::statement('
            CREATE TABLE order_items_old (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                selected_size VARCHAR NULL,
                selected_color VARCHAR NULL,
                unit_price NUMERIC NOT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
            )
        ');

        DB::statement('
            INSERT INTO order_items_old (
                id, order_id, product_id, quantity, selected_size, selected_color, unit_price, created_at, updated_at
            )
            SELECT
                id, order_id, product_id, quantity, selected_size, selected_color, unit_price, created_at, updated_at
            FROM order_items
            WHERE product_id IS NOT NULL
        ');

        DB::statement('DROP TABLE order_items');
        DB::statement('ALTER TABLE order_items_old RENAME TO order_items');

        Schema::enableForeignKeyConstraints();
    }
};
