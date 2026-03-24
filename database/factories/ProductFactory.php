<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $categories = ['Electronics', 'Clothing', 'Books', 'Home', 'Sports', 'Toys', 'Food', 'Beauty'];
        $category   = $this->faker->randomElement($categories);

        return [
            'name'        => $this->faker->words(3, true) . ' - ' . $category,
            'description' => $this->faker->sentences(2, true),
            'price'       => $this->faker->randomFloat(2, 10, 9999),
            'stock'       => $this->faker->numberBetween(1, 500),
            // Use a random suffix so parallel test runs never collide on the unique constraint.
            'sku'         => strtoupper(substr($category, 0, 3))
                             . '-' . strtoupper(Str::random(10)),
            // Default TRUE so factory products are orderable unless a test overrides it.
            'is_active'   => true,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(['stock' => 0]);
    }

    public function lowStock(): static
    {
        return $this->state(['stock' => $this->faker->numberBetween(1, 5)]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}