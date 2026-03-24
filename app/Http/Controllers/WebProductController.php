<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class WebProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        // Search
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('sku', 'like', "%{$s}%");
            });
        }

        // Filter tab
        switch ($request->input('filter', '')) {
            case 'active':   $query->where('is_active', true)->where('stock', '>', 0); break;
            case 'low':      $query->where('stock', '>', 0)->where('stock', '<=', 10); break;
            case 'out':      $query->where('stock', 0); break;
            case 'inactive': $query->where('is_active', false); break;
        }

        $products = $query->orderBy('name')->paginate(20)->withQueryString();

        // Summary stats
        $stats = [
            'total'       => Product::count(),
            'active'      => Product::where('is_active', true)->count(),
            'low_stock'   => Product::where('stock', '>', 0)->where('stock', '<=', 10)->count(),
            'out_of_stock'=> Product::where('stock', 0)->count(),
        ];

        return view('products.index', compact('products', 'stats'));
    }
}