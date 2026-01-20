<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        App::singleton('chargetable', function () {
            return array(
                'DRUMA',
                'DRUMB',
                'DRUMC',
                'DRUME',
                'DRUMK',
                'DRUMAA',
                'DRUMAB',
                'DRUMR',
                'DRUMS',
                'DRUMAD',
                'DRUMAE',
                'DRUMAF',
                'DRUMAG',
                'DRUMAH',
                'DRUMAI',
                'DRUMAJ',
                'DRUMAK',
                'DRUMAL',
                'DRUMAM',
                'DRUMAN',
            );
        });

        Gate::after(function ($user, $ability) {
            return $user->hasRole('Super Admin'); // note this returns boolean
        });
    }
}
