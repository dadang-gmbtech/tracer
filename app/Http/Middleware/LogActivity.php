<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Admin\ImpersonationController;
use App\Models\ActivityLog;
use App\Models\Alumni;
use App\Models\User;
use App\Support\ActivityDescriptions;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records who did what (see ActivityDescriptions for which requests count),
 * split into the admin log (everyone in Manajemen User) and the alumni log
 * (alumni and Pengguna Alumni). A logging failure never breaks the request.
 */
class LogActivity
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Read before the request runs: logout and "kembali ke akun Super
        // Admin" change who is logged in, and the latter clears the
        // impersonation marker from the session.
        $userBefore = $request->user();
        $impersonatorId = $request->hasSession() ? $request->session()->get(ImpersonationController::SESSION_KEY) : null;

        $response = $next($request);

        if (ActivityDescriptions::shouldLog($request)) {
            try {
                $this->record($request, $response, $userBefore ?? $request->user(), $impersonatorId);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $response;
    }

    private function record(Request $request, Response $response, ?User $user, ?int $impersonatorId): void
    {
        $action = ActivityDescriptions::action($request);
        [$category, $name, $role] = $this->actor($request, $action, $user);

        ActivityLog::create([
            'category' => $category,
            // Gone if the request deleted the account itself (profile.destroy).
            'user_id' => $user?->exists ? $user->id : null,
            'actor_name' => Str::limit((string) $name, 250),
            'actor_role' => $role,
            'impersonator_id' => $impersonatorId,
            'alumni_id' => $this->alumniId($request, $user),
            'action' => $action,
            'description' => ActivityDescriptions::describe($action, $request),
            'succeeded' => $this->succeeded($request, $response),
            'properties' => $this->properties($request) ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250),
        ]);
    }

    /**
     * @return array{0: string, 1: ?string, 2: ?string}
     */
    private function actor(Request $request, string $action, ?User $user): array
    {
        if ($user) {
            $roles = $user->getRoleNames()->join(', ') ?: null;
            $category = $user->hasRole('Alumni') ? ActivityLog::CATEGORY_ALUMNI : ActivityLog::CATEGORY_ADMIN;

            return [$category, $user->name, $roles];
        }

        // Nobody logged in: a Pengguna Alumni on their public form, or a
        // failed login (named by what was typed — never the password).
        return match (true) {
            str_starts_with($action, 'employer.') => [
                ActivityLog::CATEGORY_ALUMNI, $request->input('nama_pengisi') ?: 'Pengguna Alumni', 'Pengguna Alumni',
            ],
            $action === 'login.nim' => [ActivityLog::CATEGORY_ALUMNI, 'NIM '.$request->input('nim'), null],
            default => [ActivityLog::CATEGORY_ADMIN, $request->input('email') ?: 'Tamu', null],
        };
    }

    private function alumniId(Request $request, ?User $user): ?int
    {
        $routeAlumni = $request->route('alumni');

        if ($routeAlumni instanceof Alumni) {
            return $routeAlumni->id;
        }

        return $user?->hasRole('Alumni') ? $user->alumni?->id : null;
    }

    private function succeeded(Request $request, Response $response): bool
    {
        if ($response->getStatusCode() >= 400) {
            return false;
        }

        // A failed validation redirects back (302) with errors flashed for
        // the next page, so the status code alone reads as a success.
        return ! ($request->hasSession() && in_array('errors', (array) $request->session()->get('_flash.new', []), true));
    }

    /**
     * What the action was done to and with — record names/ids, an uploaded
     * file's name, export filters. Never form input in general: it can hold
     * passwords and personal data.
     *
     * @return array<string, mixed>
     */
    private function properties(Request $request): array
    {
        $properties = [];

        foreach ($request->route()?->parameters() ?? [] as $key => $value) {
            if ($value instanceof Model) {
                $label = $value->getAttribute('name') ?? $value->getAttribute('nama') ?? $value->getAttribute('title')
                    ?? $value->getAttribute('code');
                $properties['target'][$key] = trim('#'.$value->getKey().' '.$label);
            }
        }

        if ($request->hasFile('file')) {
            $properties['file'] = $request->file('file')->getClientOriginalName();
        }

        if ($request->filled('ids')) {
            $properties['jumlah'] = count((array) $request->input('ids'));
        }

        if ($request->isMethod('GET')) {
            $filters = array_filter($request->only(['year', 'faculty_id', 'program_study_id', 'jenjang', 'graduation_year']));
            if ($filters) {
                $properties['filter'] = $filters;
            }
        }

        return $properties;
    }
}
