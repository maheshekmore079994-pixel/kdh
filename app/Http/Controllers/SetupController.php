<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Services\AuditService;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * One-time first-run setup: creates the first administrator when the database has no users.
 * Used by fresh installs (desktop app, new server). Once any user exists every route here returns 404.
 */
class SetupController extends Controller
{
    public function show(): View
    {
        abort_if(User::query()->exists(), 404);

        return view('setup');
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        abort_if(User::query()->exists(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = DB::transaction(function () use ($validated) {
            // Lock-free double check inside the transaction to avoid two admins racing.
            abort_if(User::query()->exists(), 404);

            (new ConfigurationSeeder)->run();

            return User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => Role::Admin,
                'permissions' => null,
                'is_active' => true,
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();
        $audit->log('setup.completed', $user, [], ['email' => $user->email]);

        return redirect()->route('config.business')->with('status', __('Setup complete. Fill in your business details next.'));
    }
}
