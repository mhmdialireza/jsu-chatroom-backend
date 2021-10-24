<?php

namespace App\Http\Controllers;

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
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:8|max:16',
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

        $token = $user->createToken('myApp')->plainTextToken;

        return response()->json([
            'success' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->getMessageBag());
        }

        $user = User::whereEmail($request->email)->first();

        if (!$user) {
            return "this user didn't exist";
        }

        if (!Hash::check($request->password, $user->password)) {
            return "password doesn't correct";
        }

        $token = $user->createToken('myApp')->plainTextToken;

        return response()->json([
            'success' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    public function logout()
    {
        $user = auth()->user();
        auth()
            ->user()
            ->tokens()
            ->delete();
        return response()->json([
            'success' => 'کاربر با موفقیت از سایت خارج شد.',
        ]);
    }
}
