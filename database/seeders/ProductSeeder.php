<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    private const TOTAL_PRODUCTS = 5000;
    private const CHUNK_SIZE     = 500;

    private array $categories = [
        'Electronics', 'Clothing', 'Books', 'Home', 'Sports',
        'Toys', 'Food', 'Beauty', 'Auto', 'Garden',
    ];

    private array $adjectives = [
        'Premium', 'Ultra', 'Pro', 'Smart', 'Eco', 'Deluxe',
        'Advanced', 'Classic', 'Modern', 'Compact', 'Portable',
        'Wireless', 'Organic', 'Heavy-Duty', 'Lightweight',
    ];

    private array $nouns = [
        'Widget', 'Device', 'Kit', 'Set', 'Pack', 'System',
        'Tool', 'Unit', 'Bundle', 'Module', 'Gadget', 'Accessory',
        'Solution', 'Gear', 'Equipment',
    ];

    public function run(): void
    {
        $this->command->info('Seeding products...');

        $now    = Carbon::now();
        $chunks = ceil(self::TOTAL_PRODUCTS / self::CHUNK_SIZE);

        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $records = [];
            $size    = min(self::CHUNK_SIZE, self::TOTAL_PRODUCTS - ($chunk * self::CHUNK_SIZE));

            for ($i = 0; $i < $size; $i++) {
                $globalIndex = $chunk * self::CHUNK_SIZE + $i + 1;
                $category    = $this->categories[array_rand($this->categories)];
                $adjective   = $this->adjectives[array_rand($this->adjectives)];
                $noun        = $this->nouns[array_rand($this->nouns)];
                $stock       = $this->generateStock();

                $records[] = [
                    'name'        => "{$adjective} {$noun} - {$category} #{$globalIndex}",
                    'description' => "High-quality {$noun} for all your {$category} needs. "
                                   . "Designed for durability and performance. Item #PRD-{$globalIndex}.",
                    'price'       => round(mt_rand(999, 999999) / 100, 2), // $9.99 – $9999.99
                    'stock'       => $stock,
                    'sku'         => strtoupper(substr($category, 0, 3))
                                   . '-' . str_pad($globalIndex, 8, '0', STR_PAD_LEFT),
                    'is_active'   => mt_rand(1, 10) <= 9, // 90% active
                    'deleted_at'  => null,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            DB::table('products')->insert($records);

            $this->command->info("  Inserted chunk " . ($chunk + 1) . "/{$chunks}");
        }

        $this->command->info('✓ ' . number_format(self::TOTAL_PRODUCTS) . ' products created.');
    }

    private function generateStock(): int
    {
        $rand = mt_rand(1, 100);
        return match (true) {
            $rand <= 5  => 0,                        // 5%  out of stock
            $rand <= 15 => mt_rand(1, 10),           // 10% low stock (1-10)
            $rand <= 85 => mt_rand(11, 200),         // 70% normal stock
            default     => mt_rand(201, 1000),       // 15% high stock
        };
    }
}
