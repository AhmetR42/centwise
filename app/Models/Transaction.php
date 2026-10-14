<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['category_id', 'amount', 'description', 'occurred_on'])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'occurred_on' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Limit the query to one calendar month, given as "YYYY-MM".
     *
     * @param  Builder<Transaction>  $query
     */
    public function scopeInMonth(Builder $query, string $month): void
    {
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();

        $query->whereBetween('occurred_on', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()]);
    }
}
