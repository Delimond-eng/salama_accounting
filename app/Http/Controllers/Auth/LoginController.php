<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }


    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'errors' => $exception->errors(),
            ], 422);
        }

        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $user = Auth::user();

            if (Schema::hasColumn('users', 'actif') && !$user->actif) {
                Auth::logout();
                return response()->json([
                    'errors' => [
                        'email' => ['Votre compte a été bloqué ou désactivé. Veuillez contacter l\'administrateur.'],
                    ],
                ], 403);
            }

            return response()->json([
                'result' => [
                    'message' => 'Login successful',
                    'redirect' => route('dashboard'),
                ],
            ], 200);
        }

        return response()->json([
            'errors' => [
                'email' => ['Identifiants invalides'],
            ],
        ], 401);
    }
}
