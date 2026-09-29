<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Sign-in and sign-out happen through Jepflow SSO (see SsoController);
 * this only reports who the current session belongs to.
 */
class AuthController extends Controller
{
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
