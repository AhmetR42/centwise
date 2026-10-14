<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatsController extends Controller
{
    /**
     * Totals, spending per category against its budget, and spending per day for one month.
     */
    public function summary(Request $request): JsonResponse
    {
        $month = $request->validate(['month' => ['nullable', 'date_format:Y-m']])['month'] ?? now()->format('Y-m');
        $user = $request->user();
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();

        $current = $this->totals($user, $month);
        $previous = $this->totals($user, $start->copy()->subMonthNoOverflow()->format('Y-m'));

        $perCategory = $user->transactions()->inMonth($month)
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $categories = $user->categories()->orderBy('name')->get()->map(fn ($category) => [
            'id' => $category->id,
            'name' => $category->name,
            'type' => $category->type->value,
            'color' => $category->color,
            'icon' => $category->icon,
            'total' => Money::fromCents((int) ($perCategory[$category->id] ?? 0)),
            'budget' => Money::fromCents($category->budget),
            'budget_used' => $category->budget ? round(($perCategory[$category->id] ?? 0) / $category->budget * 100, 1) : null,
        ])->values();

        $daily = $user->transactions()->inMonth($month)
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->selectRaw('occurred_on, categories.type as type, SUM(amount) as total')
            ->groupBy('occurred_on', 'categories.type')
            ->get();

        $days = [];
        for ($day = $start->copy(); $day->month === $start->month; $day->addDay()) {
            $date = $day->toDateString();
            $days[] = [
                'date' => $date,
                'income' => Money::fromCents((int) $daily->first(fn ($row) => substr($row->occurred_on, 0, 10) === $date && $row->type === 'income')?->total),
                'expense' => Money::fromCents((int) $daily->first(fn ($row) => substr($row->occurred_on, 0, 10) === $date && $row->type === 'expense')?->total),
            ];
        }

        $budgetTotal = $user->categories()->where('type', 'expense')->sum('budget');

        return response()->json([
            'month' => $month,
            'income' => Money::fromCents($current['income']),
            'expense' => Money::fromCents($current['expense']),
            'balance' => Money::fromCents($current['income'] - $current['expense']),
            'savings_rate' => $current['income'] > 0
                ? round(($current['income'] - $current['expense']) / $current['income'] * 100, 1)
                : null,
            'budget_total' => $budgetTotal ? Money::fromCents((int) $budgetTotal) : null,
            'transactions_count' => $current['count'],
            'previous' => [
                'income' => Money::fromCents($previous['income']),
                'expense' => Money::fromCents($previous['expense']),
            ],
            'categories' => $categories,
            'daily' => $days,
        ]);
    }

    /**
     * Income and expenses per month, oldest first.
     */
    public function trend(Request $request): JsonResponse
    {
        $months = (int) ($request->validate(['months' => ['nullable', 'integer', 'min:2', 'max:24']])['months'] ?? 6);
        $first = now()->startOfMonth()->subMonthsNoOverflow($months - 1);

        $result = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $first->copy()->addMonthsNoOverflow($i)->format('Y-m');
            $totals = $this->totals($request->user(), $month);
            $result[] = [
                'month' => $month,
                'income' => Money::fromCents($totals['income']),
                'expense' => Money::fromCents($totals['expense']),
            ];
        }

        return response()->json(['data' => $result]);
    }

    /**
     * @return array{income: int, expense: int, count: int}
     */
    private function totals(User $user, string $month): array
    {
        $rows = $user->transactions()->inMonth($month)
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->selectRaw('categories.type as type, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('categories.type')
            ->get()
            ->keyBy('type');

        return [
            'income' => (int) ($rows['income']->total ?? 0),
            'expense' => (int) ($rows['expense']->total ?? 0),
            'count' => (int) $rows->sum('count'),
        ];
    }
}
