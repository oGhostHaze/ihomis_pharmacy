<?php

use App\Events\IoTransEvent;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

// Broadcast::channel('io-trans.{location_id}', function ($user, $location_id) {
//     return (int) $user->pharm_location_id === (int) $location_id;
// });

Broadcast::channel('user.{user_id}', function ($user, $user_id) {
    return (int) $user->id === (int) $user_id;
});

Broadcast::channel('encounter-view.{pharm_location_id}', function ($user, $pharm_location_id) {
    // return (int) $user->pharm_location_id === (int) $pharm_location_id;
    return true;
});

Broadcast::channel('ioTrans.{pharm_location_id}', function ($user, $pharm_location_id) {
    // return (int) $user->pharm_location_id === (int) $pharm_location_id;
    return true;
});

Broadcast::channel('App.Models.Pharmacy.PharmLocation.{pharm_location_id}', function ($user, $pharm_location_id) {
    return true;
});
