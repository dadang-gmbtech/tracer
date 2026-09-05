<?php

namespace App\Policies;

use App\Models\TracerResponse;
use App\Models\User;

class TracerResponsePolicy
{
    public function view(User $user, TracerResponse $response): bool
    {
        return app(AlumniPolicy::class)->view($user, $response->alumni);
    }

    public function update(User $user, TracerResponse $response): bool
    {
        return app(AlumniPolicy::class)->fillTracer($user, $response->alumni);
    }
}
