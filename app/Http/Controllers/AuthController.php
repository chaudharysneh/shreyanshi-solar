<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');
        $user = User::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password) && !($user->is_active ?? true)) {
            Log::warning('Inactive web login attempt blocked.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return back()
                ->withErrors(['email' => 'Your account is inactive. Please contact the administrator.'])
                ->onlyInput('email');
        }

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $request->session()->put('dashboard.show_daily_overview', true);

            // Background branding/image requests can replace the intended URL
            // after a session expires. A successful login always opens the CRM.
            $request->session()->forget('url.intended');
            return redirect()->route('dashboard');
        }

        return back()
            ->withErrors(['email' => 'Invalid credentials provided.'])
            ->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        // Fully destroy the session
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Return response with cache prevention headers
        $response = redirect()->route('login');
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');

        return $response;
    }
}
