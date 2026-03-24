<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    private const TOTAL_ORDERS  = 50000;
    private const CHUNK_SIZE    = 1000;
    private const MAX_ITEMS_PER_ORDER = 5;

    public function run(): void
    {
        $this->command->info('Seeding orders (50,000+)... This may take a minute.');

        // Pre-load IDs into arrays for fast random access
        $userIds    = DB::table('users')->pluck('id')->toArray();
        $products   = DB::table('products')
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->where('stock', '>', 0)
                        ->select('id', 'price')
                        ->get()
                        ->toArray();

        if (empty($products)) {
            $this->command->error('No active products with stock found. Run ProductSeeder first.');
            return;
        }

        $statuses    = ['pending', 'processing', 'completed', 'cancelled'];
        $statusWeights = [40, 20, 30, 10]; // % chance of each status
        $now         = Carbon::now();
        $chunks      = ceil(self::TOTAL_ORDERS / self::CHUNK_SIZE);

        $totalItemsInserted = 0;
        $orderId = DB::table('orders')->max('id') ?? 0;

        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $orderSize  = min(self::CHUNK_SIZE, self::TOTAL_ORDERS - ($chunk * self::CHUNK_SIZE));
            $orderRows  = [];
            $itemsQueue = [];

            for ($i = 0; $i < $orderSize; $i++) {
                $orderId++;
                $userId   = $userIds[array_rand($userIds)];
                $status   = $this->weightedRandom($statuses, $statusWeights);
                $numItems = mt_rand(1, self::MAX_ITEMS_PER_ORDER);

                // Pick unique products for this order
                $shuffled    = $products;
                shuffle($shuffled);
                $chosen      = array_slice($shuffled, 0, min($numItems, count($shuffled)));
                $totalAmount = 0;

                foreach ($chosen as $product) {
                    $qty      = mt_rand(1, 10);
                    $price    = $product->price;
                    $subtotal = round($price * $qty, 2);
                    $totalAmount += $subtotal;

                    $itemsQueue[] = [
                        'order_id'   => $orderId,
                        'product_id' => $product->id,
                        'quantity'   => $qty,
                        'unit_price' => $price,
                        'subtotal'   => $subtotal,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $orderRows[] = [
                    'id'           => $orderId,
                    'user_id'      => $userId,
                    'status'       => $status,
                    'total_amount' => round($totalAmount, 2),
                    'notes'        => null,
                    'created_at'   => $now->copy()->subDays(mt_rand(0, 365)),
                    'updated_at'   => $now,
                ];
            }

            // Bulk insert orders first, then items (respects FK)
            DB::table('orders')->insert($orderRows);
            
            // Insert items in sub-chunks to avoid packet size limits
            foreach (array_chunk($itemsQueue, 2000) as $itemChunk) {
                DB::table('order_items')->insert($itemChunk);
            }

            $totalItemsInserted += count($itemsQueue);
            $progress = round((($chunk + 1) / $chunks) * 100);
            $this->command->info("  [{$progress}%] Chunk " . ($chunk + 1) . "/{$chunks} — Orders: " 
                . number_format(($chunk + 1) * self::CHUNK_SIZE) . " | Items so far: " 
                . number_format($totalItemsInserted));
        }

        $this->command->info('✓ ' . number_format(self::TOTAL_ORDERS) . ' orders created.');
        $this->command->info('✓ ' . number_format($totalItemsInserted) . ' order items created.');
        $this->command->info('✓ Total records seeded: ' . number_format(
            DB::table('users')->count() +
            DB::table('products')->count() +
            DB::table('orders')->count() +
            DB::table('order_items')->count()
        ));
    }

    private function weightedRandom(array $items, array $weights): string
    {
        $total = array_sum($weights);
        $rand  = mt_rand(1, $total);
        $cumulative = 0;

        foreach ($items as $i => $item) {
            $cumulative += $weights[$i];
            if ($rand <= $cumulative) {
                return $item;
            }
        }

        return end($items);
    }
}
