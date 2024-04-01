<?php

namespace App\Providers;

use App\Actions\Jetstream\DeleteUser;
use App\Models\Pharmacy\Drugs\ConsumptionLogDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->configurePermissions();

        Jetstream::deleteUsersUsing(DeleteUser::class);

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();
            if (
                $user &&
                Hash::check($request->password, $user->password)
            ) {
                session(['user_id' => $user->id]);
                session(['user_name' => $user->name]);
                session(['user_email' => $user->email]);
                session(['employeeid' => $user->employeeid]);
                session(['pharm_location_id' => $user->pharm_location_id]);
                session(['pharm_location_name' => $user->location->description]);

                $active_consumption = ConsumptionLogDetail::where('status', 'A')
                    ->where('loc_code', $user->pharm_location_id)
                    ->first();

                if ($active_consumption) {
                    session(['active_consumption' => $active_consumption->id]);
                } else {
                    session(['active_consumption' => null]);
                }

                return $user;
            }
        });
    }

    /**
     * Configure the permissions that are available within the application.
     *
     * @return void
     */
    protected function configurePermissions()
    {
        Jetstream::defaultApiTokenPermissions(['read']);

        Jetstream::permissions([
            'create',
            'read',
            'update',
            'delete',
        ]);
    }
}
