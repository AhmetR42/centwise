<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    private const SORTS = [
        'newest' => ['occurred_on', 'desc'],
        'oldest' => ['occurred_on', 'asc'],
        'highest' => ['amount', 'desc'],
        'lowest' => ['amount', 'asc'],
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $this->validateFilters($request);
        [$column, $direction] = self::SORTS[$filters['sort'] ?? 'newest'];

        $transactions = $this->filtered($request, $filters)
            ->orderBy($column, $direction)
            ->orderBy('id', 'desc')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();

        return TransactionResource::collection($transactions);
    }

    public function store(TransactionRequest $request): JsonResponse
    {
        $transaction = $request->user()->transactions()->create($this->attributes($request));

        return (new TransactionResource($transaction->load('category')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Transaction $transaction): TransactionResource
    {
        $this->ensureOwner($request, $transaction);

        return new TransactionResource($transaction->load('category'));
    }

    public function update(TransactionRequest $request, Transaction $transaction): TransactionResource
    {
        $this->ensureOwner($request, $transaction);

        $transaction->update($this->attributes($request));

        return new TransactionResource($transaction->load('category'));
    }

    public function destroy(Request $request, Transaction $transaction): Response
    {
        $this->ensureOwner($request, $transaction);

        $transaction->delete();

        return response()->noContent();
    }

    /**
     * Download the (filtered) transactions as a CSV file that opens in Excel.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validateFilters($request);
        $query = $this->filtered($request, $filters)->orderBy('occurred_on')->orderBy('id');
        $name = 'centwise-'.($filters['month'] ?? 'all').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Type', 'Category', 'Description', 'Amount'], escape: '');

            $query->chunk(500, function ($transactions) use ($out) {
                foreach ($transactions as $transaction) {
                    $income = $transaction->category->type->value === 'income';
                    fputcsv($out, [
                        $transaction->occurred_on->toDateString(),
                        $transaction->category->type->value,
                        $transaction->category->name,
                        $transaction->description,
                        number_format(($income ? 1 : -1) * $transaction->amount / 100, 2, '.', ''),
                    ], escape: '');
                }
            });

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'type' => ['nullable', Rule::in(['income', 'expense'])],
            'category_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);
    }

    /**
     * @return HasMany<Transaction, User>
     */
    private function filtered(Request $request, array $filters): HasMany
    {
        return $request->user()->transactions()
            ->with('category')
            ->when($filters['month'] ?? null, fn (Builder $query, string $month) => $query->inMonth($month))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $id) => $query->where('category_id', $id))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->whereHas(
                'category', fn (Builder $category) => $category->where('type', $type)
            ))
            ->when($filters['q'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $inner) => $inner->where('description', 'like', "%{$search}%")
                    ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$search}%"))
            ));
    }

    private function attributes(TransactionRequest $request): array
    {
        $data = $request->validated();

        if (isset($data['amount'])) {
            $data['amount'] = Money::toCents($data['amount']);
        }

        return $data;
    }
}
