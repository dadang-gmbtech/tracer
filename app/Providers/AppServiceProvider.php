<?php

namespace App\Providers;

use App\Contracts\AcademicInfoServiceContract;
use App\Contracts\SsoLoginServiceContract;
use App\Models\City;
use App\Models\Faculty;
use App\Models\Province;
use App\Models\StudyProgram;
use App\Models\UmpSalary;
use App\Models\User;
use App\Policies\MasterDataPolicy;
use App\Services\MockAcademicInfoService;
use App\Services\MockSsoLoginService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SsoLoginServiceContract::class, MockSsoLoginService::class);
        $this->app->bind(AcademicInfoServiceContract::class, MockAcademicInfoService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn (User $user, string $ability) => $user->hasRole('Super Admin') ? true : null);

        foreach ([Faculty::class, StudyProgram::class, Province::class, City::class, UmpSalary::class] as $model) {
            Gate::policy($model, MasterDataPolicy::class);
        }
    }
}
