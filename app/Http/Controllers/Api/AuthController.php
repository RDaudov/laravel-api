<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
//        $validatedData = $request->validate([
//            'name' => 'required|max:55',
//            'email' => 'email|required|unique:users',
//            'password' => 'required|confirmed'
//        ]);
//
//        $validatedData['password'] = Hash::make($request->password);
//
//        $user = User::create($validatedData);
//
//        $accessToken = $user->createToken('authToken')->accessToken;
//
//        return response(['user' => $user, 'access_token' => $accessToken]);
        return response('user' );
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json([
                'user' => $user,
                'access_token' => $token
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }
}
