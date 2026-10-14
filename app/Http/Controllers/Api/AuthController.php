<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\DefaultCategories;
use App\Support\DemoData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public const CURRENCIES = ['EUR', 'USD', 'GBP', 'TRY'];

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:120', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'locale' => ['nullable', Rule::in(['en', 'nl'])],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create($data);
            DefaultCategories::create($user, $data['locale'] ?? 'en');

            return $user;
        });

        return $this->tokenResponse($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'These details do not match an account.',
            ]);
        }

        return $this->tokenResponse($user);
    }

    /**
     * Create a throwaway account filled with example data.
     */
    public function demo(Request $request): JsonResponse
    {
        $locale = $request->input('locale') === 'nl' ? 'nl' : 'en';

        // There is no scheduler on the free hosting, so clean up old demos here as well.
        User::pruneDemos();

        $user = DB::transaction(function () use ($locale) {
            $user = User::create([
                'name' => $locale === 'nl' ? 'Demo gebruiker' : 'Demo user',
                'email' => 'demo-'.Str::lower(Str::random(12)).'@centwise.test',
                'password' => Str::random(32),
                'is_demo' => true,
            ]);
            DefaultCategories::create($user, $locale);
            DemoData::fill($user);

            return $user;
        });

        return $this->tokenResponse($user, 201);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(Request $request): UserResource
    {
        $user = $request->user();

        $user->update($request->validate([
            'name' => ['sometimes', 'string', 'max:60'],
            'currency' => ['sometimes', Rule::in(self::CURRENCIES)],
        ]));

        return new UserResource($user);
    }

    public function destroy(Request $request): Response
    {
        $request->user()->tokens()->delete();
        $request->user()->delete();

        return response()->noContent();
    }

    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'token' => $user->createToken('dashboard')->plainTextToken,
            'user' => new UserResource($user),
        ], $status);
    }
}
