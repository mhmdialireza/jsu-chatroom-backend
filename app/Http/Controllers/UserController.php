<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\RoomResource;
use App\Http\Resources\UserResource;
use App\Http\Services\Image\ImageService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        if (auth()->user()->role_in_site != 'admin') {
            return response()->json(
                [
                    'error' => 'اجازه دسترسی وجود ندارد.',
                ],
                403
            );
        }
        $users = User::paginate(10);
        return UserResource::collection($users);
    }

    public function show($id)
    {
        if (auth()->user()->role_in_site != 'admin') {
            return response()->json(
                [
                    'error' => 'اجازه دسترسی وجود ندارد.',
                ],
                403
            );
        }
        try {
            return [
                'user' => ($user = new UserResource(
                    User::whereId($id)->firstOrFail()
                )),
                'rooms' => RoomResource::collection($user->rooms),
            ];
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'error' => 'کاربری با این مشخصات وجود ندارد.',
                ],
                404
            );
        }
    }

    public function destroy($id)
    {
        if (auth()->user()->role_in_site != 'admin') {
            return response()->json(
                ['error' => 'اجازه دسترسی وجود ندارد'],
                403
            );
        }

        try {
            $user = User::whereId($id)->firstOrFail();
            $user->delete();
            return response()->json(['success' => 'کاربر با موفقیت حذف شد.']);
        } catch (\Throwable $th) {
            return 'کاربری با این مشخصات وجود ندارد';
        }
    }

    public function profileIndex()
    {
        return response()->json(new UserResource(auth()->user()));
    }

    public function profileUpdate(Request $request, ImageService $imageService)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:16|min:3',
            'email' => [
                'required',
                'email',
                Rule::unique('users')
                    ->ignore(auth()->user()->id)
                    ->withoutTrashed(),
            ],
            'image' => 'image',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['error' => $validator->getMessageBag()],
                400
            );
        }

        if ($user->profile_path) {
            $imageService->deleteImage($user->profile_path);
        }

        $result = null;
        if ($request->hasFile('image')) {
            $imageService->setExclusiveDirectory(
                'images' . DIRECTORY_SEPARATOR . 'users'
            );
            $result = $imageService->save($request->file('image'));
        }

        if ($result === false) {
            return response()->json(
                ['error' => 'آپلود تصویر با خطا مواجه شد.'],
                500
            );
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->profile_path = $result;
        $user->save();

        return response()->json(new UserResource(auth()->user()), 202);
    }

    public function profileChangePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'new_password' => 'required|string|confirmed|min:8|max:16',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['error' => $validator->getMessageBag()],
                400
            );
        }

        if (!Hash::check($request->password, auth()->user()->password)) {
            return response()->json(['کلید تطابق ندارد.'], 403);
        }

        auth()
            ->user()
            ->update(['password' => Hash::make($request->password)]);
        return response()->json(auth()->user(), 202);
    }
}
