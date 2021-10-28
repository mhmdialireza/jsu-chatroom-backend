<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\RoomResource;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $allRooms = Room::all();

        $customRooms = $allRooms->filter(function ($value) {
            $userRooms = auth()->user()->rooms;
            return !$userRooms->contains($value);
        });

        return response()->json([
            'rooms' => RoomResource::collection($customRooms),
        ]);
    }

    public function userRooms()
    {
        // return 1;
        $rooms = auth()->user()->rooms()->paginate(10);
        return RoomResource::collection($rooms);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'max:16',
                'min:3',
                Rule::unique('rooms')->withoutTrashed(),
            ],
            'access' => ['required', Rule::in(['private', 'public'])],
            'description' => 'max:512|min:3',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['error' => $validator->getMessageBag()],
                400
            );
        }

        if ($request->access == 'private') {
            $key = Str::random(16);
            $room = Room::create([
                'name' => $request->name,
                'description' => $request->description,
                'access' => 'private',
                'key' => Hash::make($key),
            ]);
            $room
                ->members()
                ->attach(auth()->user(), ['role_in_room' => 'owner']);

            return response()->json(
                ['success' => 'اتاق جدید با موفقیت ایجاد شد.', 'key' => $key],
                201
            );
        } else {
            $room = Room::create([
                'name' => $request->name,
                'description' => $request->description,
                'access' => 'public',
            ]);

            $room
                ->members()
                ->attach(auth()->user(), ['role_in_room' => 'owner']);

            return response()->json(
                ['success' => 'اتاق جدید با موفقیت ایجاد شد.'],
                201
            );
        }
    }

    public function join(Request $request)
    {
        try {
            $room = Room::where('name', $request->name)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(
                ['error' => 'اتاقی با این مشخصات وجود ندارد'],
                404
            );
        }

        if ($room->members->count() == 50) {
            return response()->json([
                'error' => 'تعداد اعضای گروه به حداکثر میزان خود رسیده است.',
            ]);
        }

        if ($room->members->contains(auth()->user())) {
            return response()->json([
                'error' =>
                    'شما جز اعضای گروه هستید ، نمی‌توانید مجدد عضو شوید.',
            ]);
        }

        if ($room->access == 'private') {
            if (!isset($request->key)) {
                return response()->json([
                    'error' => 'این گروه خصوصی است و نیاز به کلید دارد.',
                ]);
            }
            if (!Hash::check($request->key, $room->key)) {
                return response()->json([
                    'error' =>
                        'کلید وارد شده با کلید ثبت شده برای گروه تطابق ندارد.',
                ]);
            }
        }

        $room
            ->members()
            ->attach(auth()->user(), ['role_in_room' => 'member']);
        $room->increment('number_of_members');
        return response()->json([
            'error' => 'با موفقیت در گروه عضو شدید.',
        ]);
    }

    public function left(Request $request)
    {
        try {
            $room = Room::where('name', $request->name)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(
                ['error' => 'اتاقی با این مشخصات وجود ندارد'],
                404
            );
        }

        if (!$room->members->contains(auth()->user())) {
            return response()->json([
                'error' => 'شما جز اعضای گروه نیستید.',
            ]);
        }

        $room->members()->detach(auth()->user());
        $room->decrement('number_of_members');

        return response()->json(['success' => 'با موفقیت از گروه خارج شدید.']);
    }

    public function resetKey(Request $request)
    {
        try {
            $room = Room::where('name', $request->name)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(
                ['error' => 'اتاقی با این مشخصات وجود ندارد'],
                404
            );
        }

        if ($room->access != 'private') {
            return response()->json(['error' => 'این گروه عمومی است.']);
        }

        if (auth()->user()->id != $room->members()->first()->id) {
            return response()->json(
                ['error' => 'اجازه دسترسی وجود ندارد'],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required',
        ]);

        if (!Hash::check($request->key, $room->key)) {
            return response()->json(
                ['کلید وارده شده با کلید اصلی تطابق ندارد.'],
                403
            );
        }

        if ($request->auto_generate && $request->auto_generate == 1) {
            $room->update(['key' => Hash::make($key = Str::random(16))]);
            return response()->json([
                'success' => 'کلید با موفقیت ریست شد.',
                'key' => $key,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'newKey' => 'required|confirmed|min:6|max:32',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['error' => $validator->getMessageBag()],
                400
            );
        }

        $room->update(['key' => Hash::make($request->newKey)]);

        return response()->json([
            'success' => 'کلید با موفقیت ریست شد.',
        ]);
    }

    public function show(string $name)
    {
        try {
            return [
                'room' => ($room = new RoomResource(
                    Room::where('name', $name)->firstOrFail()
                )),
                'members' => UserResource::collection($room->members),
            ];
        } catch (\Throwable $th) {
            return response()->json(
                ['error' => 'اتاقی با این مشخصات وجود ندارد'],
                404
            );
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $room = Room::whereId($id)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(
                ['error' => 'اتاقی با این مشخصات وجود ندارد'],
                404
            );
        }

        if (auth()->user()->id != $room->members()->first()->id) {
            return response()->json(
                ['error' => 'اجازه دسترسی وجود ندارد'],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'max:16',
                'min:3',
                Rule::unique('rooms')->ignore($id),
            ],
            'description' => 'required|max:512|min:3',
            'access' => ['required', Rule::in(['private', 'public'])],
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['error' => $validator->getMessageBag()],
                400
            );
        }

        if ($request->access == $room->access) {
            $room->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);
        } elseif ($request->access == 'public') {
            $room->update([
                'name' => $request->name,
                'description' => $request->description,
                'access' => 'public',
                'key' => null,
            ]);
        } else {
            $room->update([
                'name' => $request->name,
                'description' => $request->description,
                'access' => 'private',
                'key' => Hash::make($newKey = Str::random(16)),
            ]);
            return response()->json(['room' => $room, 'key' => $newKey]);
        }

        return response()->json(['room' => $room]);
    }

    public function destroy($id)
    {
        try {
            $room = Room::whereId($id)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(
                ['error' => 'اتاقی با این مشخصات وجود ندارد'],
                404
            );
        }

        if (auth()->user()->id != $room->members()->first()->id) {
            return response()->json(
                ['error' => 'اجازه دسترسی وجود ندارد'],
                403
            );
        }

        $room->delete();

        return response()->json(
            ['success' => 'اتاق مورد نظر با موفقیت حذف شد.'],
            202
        );
    }

    public function deleteMember($roomId, $userId)
    {
        try {
            $room = Room::whereId($roomId)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(
                ['error' => 'اتاقی با این مشخصات وجود ندارد'],
                404
            );
        }

        try {
            $user = User::whereId($userId)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(
                ['error' => 'کاربری با این مشخصات وجود ندارد.'],
                404
            );
        }

        if (auth()->user()->id == $userId) {
            return response()->json(
                ['error' => 'شما نمیتوانید خودتان را حذف کنید.'],
                400
            );
        }

        if (auth()->user()->id != $room->members()->first()->id) {
            return response()->json(
                ['error' => 'اجازه دسترسی وجود ندارد'],
                403
            );
        }
        $room->members()->detach($user);

        return response()->json(
            ['success' => 'کاربر مورد نظر با موفقیت از گروه حذف شد.'],
            202
        );
    }
}
