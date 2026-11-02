<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Scopes\TenantScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * API User / Tenant Registration (Postman / Sanctum ready)
     *
     * Supports:
     * 1. Admin/Merchant: creates company, admin user, optional subscription, and returns Sanctum Bearer token.
     * 2. User/Customer: creates customer account under merchant, user record, and returns Sanctum Bearer token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'string', 'in:admin,user,super_admin'],
            // Company fields for Merchant/Admin
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            // Tenant key for Customer/User
            'merchant_id' => ['nullable', 'exists:merchants,id'],
        ]);

        $role = $validated['role'] ?? ($request->filled('company_name') ? User::ROLE_ADMIN : User::ROLE_USER);
        $merchantId = $validated['merchant_id'] ?? null;
        $merchant = null;

        // 1. If registering as Tenant Admin, create or link Merchant
        if ($role === User::ROLE_ADMIN || $request->filled('company_name')) {
            $companyName = $validated['company_name'] ?: "{$validated['name']}'s Company";
            $merchant = Merchant::create([
                'name' => $companyName,
                'company_name' => $companyName,
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'api_key' => 'mk_' . Str::random(32),
                'status' => 'active',
            ]);
            $merchantId = $merchant->id;
            $role = User::ROLE_ADMIN;

            // Attach plan if provided
            if (!empty($validated['plan_id'])) {
                $plan = TenantScope::withoutTenancy(fn () => Plan::find($validated['plan_id']));
                if ($plan) {
                    $customer = Customer::create([
                        'merchant_id' => $merchantId,
                        'name' => $validated['name'],
                        'email' => $validated['email'],
                    ]);

                    Subscription::create([
                        'merchant_id' => $merchantId,
                        'customer_id' => $customer->id,
                        'plan_id' => $plan->id,
                        'started_at' => Carbon::now()->startOfMonth(),
                        'ends_at' => Carbon::now()->addDays($plan->billing_cycle_days ?: 30),
                        'status' => 'active',
                    ]);
                }
            }
        } elseif ($role === User::ROLE_USER && $merchantId) {
            // Registering as a customer under a merchant
            $merchant = Merchant::find($merchantId);
            Customer::firstOrCreate(
                ['merchant_id' => $merchantId, 'email' => $validated['email']],
                ['name' => $validated['name']]
            );
        }

        // 2. Create User account
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'merchant_id' => $merchantId,
        ]);

        // 3. Issue Sanctum Bearer Token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'merchant_id' => $user->merchant_id,
            ],
            'merchant' => $merchant ? [
                'id' => $merchant->id,
                'company_name' => $merchant->company_name ?? $merchant->name,
                'api_key' => $merchant->api_key,
            ] : null,
        ], 201);
    }

    /**
     * API Login (Postman / Sanctum ready)
     *
     * Validates credentials and returns Sanctum Bearer token with role metadata.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // Issue Sanctum Bearer Token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'merchant_id' => $user->merchant_id,
            ],
            'role' => $user->role,
            'merchant_id' => $user->merchant_id,
        ], 200);
    }

    /**
     * API Logout
     *
     * Revokes user's current Sanctum Bearer token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Successfully logged out and token revoked.',
        ], 200);
    }

    /**
     * Get Current Authenticated Profile (GET /api/auth/me)
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $merchant = $user->merchant;
        $plan = $user->getActivePlan();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'merchant_id' => $user->merchant_id,
            ],
            'merchant' => $merchant ? [
                'id' => $merchant->id,
                'company_name' => $merchant->company_name ?? $merchant->name,
                'email' => $merchant->email,
                'status' => $merchant->status ?? 'active',
            ] : null,
            'active_plan' => $plan ? [
                'id' => $plan->id,
                'name' => $plan->name,
                'base_price' => (float) $plan->base_price,
                'included_units' => (int) ($plan->included_units ?? $plan->included_usage_units),
                'overage_rate_per_unit' => (float) $plan->overage_rate_per_unit,
            ] : null,
        ]);
    }
}

