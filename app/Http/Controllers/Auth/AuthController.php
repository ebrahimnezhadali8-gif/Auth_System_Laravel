<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => 'required|string',
            "phone" => 'required|string|unique:users,phone',
            "password" => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);
        return successResponse(new UserResource($user), 200, 'User registered successfully');
    }

    public function login(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            "phone"    => 'required|string',
            "password" => 'required|string',
        ]);

        if ($validator->fails()) {
            return errorResponse(422, $validator->messages());
        }

        // check if user exists and password is correct
        $user = User::where('phone', $request->phone)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return errorResponse(401, 'phone or password is incorrect');
        }

        // create deviceId 
        $deviceId = Str::uuid();

        // create Access Token 
        $accessToken = $user->createToken(
            'access_'  . $deviceId,
            ['access'],
            now()->addMinutes((int) env('ACCESS_TOKEN_EXPIRY', 15))
        )->plainTextToken;

        // create Refresh Token
        $refreshToken = $user->createToken(
            'refresh_' . $deviceId,
            ['refresh'],
            now()->addMinutes((int) env('REFRESH_TOKEN_EXPIRY', 10080))
        )->plainTextToken;

        // create HttpOnly Cookie 
        $accessCookie = cookie(
            'access_token',
            $accessToken,
            env('ACCESS_TOKEN_EXPIRY', 15),
            '/',    // path 
            null,   // domain 
            true,   // secure
            true    // httpOnly
        );

        $refreshCookie = cookie(
            'refresh_token',
            $refreshToken,
            env('REFRESH_TOKEN_EXPIRY', 10080),
            '/',
            null,
            true,
            true
        );

        $deviceCookie = cookie(
            'device_id',
            $deviceId,
            env('REFRESH_TOKEN_EXPIRY', 10080),
            '/',
            null,
            true,
            true
        );

        return successResponse(new UserResource($user), 201, 'User login successfully')
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie)
            ->withCookie($deviceCookie);
    }
    public function refresh(Request $request) {}
    public function logout(Request $request) {}
}
