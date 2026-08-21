<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | The full 2FA backend (enrollment, confirmation, recovery codes, login
    | challenge) and frontend UI are built and working, but this stays off
    | in production until we're ready to roll it out. Flip to true (per
    | environment) to test the whole flow end to end.
    |
    */

    'two_factor_auth' => env('FEATURE_2FA', false),

];
