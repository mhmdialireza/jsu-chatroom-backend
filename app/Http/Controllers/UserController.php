<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\RoomResource;
use App\Http\Resources\UserResource;

class UserController extends Controller
{

    public function index()
    {
        $users = User::paginate(10);
        return UserResource::collection($users);
    }

    public function show($id)
    {
        try {
            return [
                'user' => ($user = new UserResource(
                    User::find($id)->firstOrFail()
                )),
                'rooms' => RoomResource::collection($user->rooms),
            ];
        } catch (\Throwable $th) {
            return 'کاریری با این مشخصات وجود ندارد'; //TODO
        }
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy(int $id)
    {
        if (auth()->user()->is_admin != 1) {
            return response()->json(['error', 'اجازه دسترسی وجود ندارد'], 403);
        }

        try {
            $user = User::find($id)->firstOrFail();
            $user->delete();
            return response()->json(['success', 'کاربر با موفقیت حذف شد.']);
        } catch (\Throwable $th) {
            return 'کاربری با این مشخصات وجود ندارد';
        }
    }

    public function destroyByEmail(string $email)
    {
        if (auth()->user()->is_admin != 1) {
            return response()->json(['error', 'اجازه دسترسی وجود ندارد'], 403);
        }

        try {
            $user = User::where('email', $email)->firstOrFail();
            $user->delete();
            return response()->json(['success', 'کاربر با موفقیت حذف شد.']);
        } catch (\Throwable $th) {
            return 'کاربری با این مشخصات وجود ندارد';
        }
    }
}
