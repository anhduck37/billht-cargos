<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterFormRequest;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new user as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    // use RegistersUsers;

    /**
     * Where to redirect user after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;
    public function showRegistrationForm() {
        return view('auth.register');
    }
    public function create(RegisterFormRequest $request)
    {
        $formData = $request->all();
        $formData['password'] = Hash::make($request->password);
        $user = new User();
        $user->fill($formData);
        $user->save();
        return  $user;
    }
}
