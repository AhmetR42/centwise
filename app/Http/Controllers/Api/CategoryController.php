<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = $request->user()->categories()
            ->withCount('transactions')
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $category = $request->user()->categories()->create($this->attributes($request));

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(Request $request, Category $category): CategoryResource
    {
        $this->ensureOwner($request, $category);

        return new CategoryResource($category->loadCount('transactions'));
    }

    public function update(CategoryRequest $request, Category $category): CategoryResource
    {
        $this->ensureOwner($request, $category);

        $category->update($this->attributes($request));

        return new CategoryResource($category);
    }

    public function destroy(Request $request, Category $category): Response|JsonResponse
    {
        $this->ensureOwner($request, $category);

        $count = $category->transactions()->count();
        if ($count > 0 && ! $request->boolean('with_transactions')) {
            return response()->json([
                'message' => "This category still has {$count} transactions.",
                'transactions_count' => $count,
            ], 409);
        }

        $category->delete();

        return response()->noContent();
    }

    private function attributes(CategoryRequest $request): array
    {
        $data = $request->validated();

        if (array_key_exists('budget', $data)) {
            $data['budget'] = $data['budget'] === null ? null : Money::toCents($data['budget']);
        }

        return $data;
    }
}
