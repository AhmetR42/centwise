<?php

namespace App\Http\Resources;

use App\Models\Goal;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Goal */
class GoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $left = max(0, $this->target - $this->saved);
        $monthsLeft = $this->deadline
            ? max(1, (int) ceil(now()->startOfDay()->diffInMonths($this->deadline, false)))
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'target' => Money::fromCents($this->target),
            'saved' => Money::fromCents($this->saved),
            'progress' => $this->target > 0 ? min(100, round($this->saved / $this->target * 100, 1)) : 0,
            'deadline' => $this->deadline?->toDateString(),
            'months_left' => $monthsLeft,
            // How much to put aside each month to reach the target in time
            'monthly_needed' => $monthsLeft ? Money::fromCents((int) ceil($left / $monthsLeft)) : null,
            'color' => $this->color,
        ];
    }
}
