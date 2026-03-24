<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ── Stat Cards ────────────────────────────────────────────────────────
        $stats = [
            'total_orders'   => Order::count(),
            'total_products' => Product::where('is_active', true)->count(),
            'total_revenue'  => Order::where('status', 'completed')->sum('total_amount'),
            'pending_orders' => Order::where('status', 'pending')->count(),
        ];

        // ── Status Distribution ───────────────────────────────────────────────
        $total = max($stats['total_orders'], 1);
        $statusColors = [
            'pending'    => 'var(--amber)',
            'processing' => 'var(--accent)',
            'completed'  => 'var(--green)',
            'cancelled'  => 'var(--red)',
        ];

        $statusDist = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(fn($r) => [
                'status'  => $r->status,
                'count'   => $r->count,
                'percent' => round(($r->count / $total) * 100),
                'color'   => $statusColors[$r->status] ?? 'var(--slate)',
            ])->toArray();

        // ── Weekly Orders (last 7 days) ────────────────────────────────────────
        $weeklyData = [];
        $weeklyTotal = 0;
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $count = Order::whereDate('created_at', $date)->count();
            $weeklyData[] = $count;
            $weeklyTotal += $count;
        }

        $prevWeekTotal = Order::whereBetween('created_at', [
            Carbon::today()->subDays(14),
            Carbon::today()->subDays(7),
        ])->count();

        $weeklyOrders = [
            'data'   => $weeklyData,
            'total'  => $weeklyTotal,
            'growth' => $prevWeekTotal > 0 ? round((($weeklyTotal - $prevWeekTotal) / $prevWeekTotal) * 100) : 0,
        ];

        // ── Weekly Revenue ────────────────────────────────────────────────────
        $revenueData = [];
        $revTotal = 0;
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $rev  = Order::whereDate('created_at', $date)
                         ->where('status', 'completed')
                         ->sum('total_amount');
            $revenueData[] = (float) $rev;
            $revTotal += $rev;
        }

        $prevRevTotal = Order::whereBetween('created_at', [
            Carbon::today()->subDays(14),
            Carbon::today()->subDays(7),
        ])->where('status', 'completed')->sum('total_amount');

        $weeklyRevenue = [
            'data'   => $revenueData,
            'total'  => $revTotal,
            'growth' => $prevRevTotal > 0 ? round((($revTotal - $prevRevTotal) / $prevRevTotal) * 100) : 0,
        ];

        // ── Recent Orders (eager loaded — no N+1) ─────────────────────────────
        $recentOrders = Order::with(['orderItems'])
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'stats', 'statusDist', 'weeklyOrders', 'weeklyRevenue', 'recentOrders'
        ));
    }
}