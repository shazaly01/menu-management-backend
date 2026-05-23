<?php

namespace App\Providers;

use App\Models\User;
use App\Models\RemoteOrder;
use App\Models\Category;
use App\Models\Product;
use App\Models\Table;
use App\Policies\UserPolicy;
use App\Policies\RemoteOrderPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\TablePolicy;
use Spatie\Permission\Models\Role;
use App\Policies\RolePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // التخطي المركزي للـ Super Admin لمنحه صلاحية كاملة على كل شيء تلقائياً
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
