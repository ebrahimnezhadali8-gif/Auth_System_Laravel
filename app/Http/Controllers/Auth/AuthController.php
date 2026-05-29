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

        return successResponse([
            'user' => new UserResource($user),
            'token' => $accessToken
        ], 200, 'User login successfully')
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie)
            ->withCookie($deviceCookie);
    }
    public function refresh(Request $request)
    {
        // read refresh token of cookie
        $refreshToken = $request->cookie('refresh_token');
        $deviceId = $request->cookie('device_id');

        if (!$refreshToken || !$deviceId) {
            return errorResponse(401, 'Refresh token not found');
        }

        //  find to DB
        $tokenId = explode('|', $refreshToken)[0];
        $token = \Laravel\Sanctum\PersonalAccessToken::find($tokenId);

        // check validity token
        if (!$token) {
            return errorResponse(401, 'Invalid refresh token');
        }

        // reuse detection — if the token was used before, it means it was stolen, so we delete all tokens of this user
        if ($token->last_used_at) {
            // delete all tokens of this user
            $token->tokenable->tokens()->delete();
            // expire cookies for client 
            $accessCookie  = cookie('access_token',  '', -1, '/');
            $refreshCookie = cookie('refresh_token', '', -1, '/');
            $deviceCookie  = cookie('device_id',     '', -1, '/');

            return errorResponse(401, 'Refresh token reuse detected')
                ->withCookie($accessCookie)
                ->withCookie($refreshCookie)
                ->withCookie($deviceCookie);
        }

        //  check expire
        if ($token->expires_at && $token->expires_at->isPast()) {
            return errorResponse(401, 'Refresh token expired');
        }

        //  check ability
        if (!in_array('refresh', $token->abilities)) {
            return errorResponse(401, 'Invalid token type');
        }

        // get user
        $user = $token->tokenable;

        // delete tokens device
        $user->tokens()
            ->where('name', 'like', '%' . $deviceId . '%')
            ->delete();

        //  create new tokens
        $accessToken = $user->createToken(
            'access_' . $deviceId,
            ['access'],
            now()->addMinutes((int) env('ACCESS_TOKEN_EXPIRY', 15))
        )->plainTextToken;

        $refreshToken = $user->createToken(
            'refresh_' . $deviceId,
            ['refresh'],
            now()->addMinutes((int) env('REFRESH_TOKEN_EXPIRY', 10080))
        )->plainTextToken;

        //  create new cookies
        $accessCookie = cookie(
            'access_token',
            $accessToken,
            (int) env('ACCESS_TOKEN_EXPIRY', 15),
            '/',
            null,
            true,
            true
        );

        $refreshCookie = cookie(
            'refresh_token',
            $refreshToken,
            (int) env('REFRESH_TOKEN_EXPIRY', 10080),
            '/',
            null,
            true,
            true
        );

        $deviceCookie = cookie(
            'device_id',
            $deviceId,
            (int) env('REFRESH_TOKEN_EXPIRY', 10080),
            '/',
            null,
            true,
            true
        );

        return successResponse(null, 200, 'Token refreshed successfully')
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie)
            ->withCookie($deviceCookie);
    }

    public function logout(Request $request)
    {
        $deviceId = $request->cookie('device_id');

        //  delete device from DB
        $request->user()
            ->tokens()
            ->where('name', 'like', '%' . $deviceId . '%')
            ->delete();

        // expire cookies for client
        $accessCookie  = cookie('access_token',  '', -1, '/');
        $refreshCookie = cookie('refresh_token', '', -1, '/');
        $deviceCookie  = cookie('device_id',     '', -1, '/');

        return successResponse(null, 200, 'Logged out successfully')
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie)
            ->withCookie($deviceCookie);
    }
}
