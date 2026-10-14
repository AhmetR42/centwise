<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoalRequest;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GoalController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return GoalResource::collection($request->user()->goals()->orderBy('created_at')->get());
    }

    public function store(GoalRequest $request): JsonResponse
    {
        $goal = $request->user()->goals()->create($this->attributes($request));

        return (new GoalResource($goal))->response()->setStatusCode(201);
    }

    public function show(Request $request, Goal $goal): GoalResource
    {
        $this->ensureOwner($request, $goal);

        return new GoalResource($goal);
    }

    public function update(GoalRequest $request, Goal $goal): GoalResource
    {
        $this->ensureOwner($request, $goal);

        $goal->update($this->attributes($request));

        return new GoalResource($goal);
    }

    /**
     * Add money to a goal, or take it out with a negative amount.
     */
    public function deposit(Request $request, Goal $goal): GoalResource
    {
        $this->ensureOwner($request, $goal);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'not_in:0', 'min:-1000000', 'max:1000000'],
        ]);

        $goal->update(['saved' => max(0, $goal->saved + Money::toCents($data['amount']))]);

        return new GoalResource($goal);
    }

    public function destroy(Request $request, Goal $goal): Response
    {
        $this->ensureOwner($request, $goal);

        $goal->delete();

        return response()->noContent();
    }

    private function attributes(GoalRequest $request): array
    {
        $data = $request->validated();

        foreach (['target', 'saved'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = Money::toCents($data[$field]);
            }
        }

        return $data;
    }
}
