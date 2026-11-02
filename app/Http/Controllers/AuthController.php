<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the unified login page for Super Admin, Merchant Admin, and Regular User.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle login authentication and role-based redirect.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            /** @var User $user */
            $user = Auth::user();

            // 1. Super Admin -> Global Platform Monitoring Hub (all users/all tenants)
            if ($user->isSuperAdmin()) {
                return redirect()->intended(route('superadmin.dashboard'))
                    ->with('success', 'Logged in as Super Admin. Viewing all platform data.');
            }

            // 2. Company Admin -> Company Metering & Billing Dashboard
            if ($user->isAdmin()) {
                $merchantId = $user->merchant_id ?: 1;
                return redirect()->intended(route('merchant.dashboard', ['id' => $merchantId]))
                    ->with('success', "Welcome back, {$user->name}. Viewing {$user->merchant?->company_name} dashboard.");
            }

            // 3. Regular Tenant User -> Assigned Plan & Usage Quota View only
            return redirect()->intended(route('user.plan'))
                ->with('success', "Welcome back, {$user->name}. Here are your plan details.");
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have been safely logged out.');
    }
}

