<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:16|min:3',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->withoutTrashed(),
            ],
            'password' => 'required|string|confirmed|min:6|max:16',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['error' => $validator->getMessageBag()],
                400
            );
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_in_site' => 'user',
        ]);

        return response()->json(
            [
                'user' => new UserResource($user),
                'token' => $user->createToken('myApp')->plainTextToken,
            ],
            201
        );
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->getMessageBag(), 400);
        }

        $user = User::whereEmail($request->email)->first();

        if (!$user) {
            return response()->json(
                [
                    'error' => 'کاربری با این مشخصات وجود ندارد.',
                ],
                404
            );
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'رمزعبور اشتباه است.'], 401);
        }

        $token = $user->createToken('myApp')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout()
    {
        $user = auth()->user();
        $user->tokens()->delete();
        return response()->json([
            'success' => 'کاربر با موفقیت از سایت خارج شد.',
        ]);
    }
}
