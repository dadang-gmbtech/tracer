<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * "Login sebagai" — lets a Super Admin act as another user to check what
 * that user sees and can do. The Super Admin's id is kept in the session so
 * they can switch back without logging in again.
 */
class ImpersonationController extends Controller
{
    public const SESSION_KEY = 'impersonator_id';

    public function start(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();

        abort_unless(self::canImpersonate($admin, $user), 403);

        Log::info('Impersonation started', ['admin_id' => $admin->id, 'user_id' => $user->id]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $admin->id);

        return redirect()->to($user->postLoginUrl())
            ->with('status', "Anda sekarang login sebagai {$user->name}.");
    }

    public function stop(Request $request): RedirectResponse
    {
        $adminId = $request->session()->pull(self::SESSION_KEY);
        $admin = $adminId ? User::find($adminId) : null;

        abort_unless($admin && $admin->hasRole('Super Admin') && $admin->isActive(), 403);

        Log::info('Impersonation stopped', ['admin_id' => $admin->id, 'user_id' => $request->user()->id]);

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.users.index')
            ->with('status', 'Anda kembali login sebagai '.$admin->name.'.');
    }

    /**
     * Checked against the real Super Admin role rather than a policy: the
     * Gate::before bypass would otherwise let a Super Admin "impersonate"
     * another Super Admin, and nothing here should be grantable by role
     * permissions alone.
     */
    public static function canImpersonate(User $admin, User $target): bool
    {
        return $admin->hasRole('Super Admin')
            && ! $admin->is($target)
            && ! $target->hasRole('Super Admin')
            && $target->isActive();
    }
}
