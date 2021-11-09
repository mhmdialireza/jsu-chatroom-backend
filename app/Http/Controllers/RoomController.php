<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Carbon;
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
        $rooms = auth()
            ->user()
            ->rooms()
            ->paginate(20);
        return RoomResource::collection($rooms);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'max:16', 'min:3', Rule::unique('rooms')->withoutTrashed()],
            'access' => ['required', Rule::in(['private', 'public'])],
            'description' => 'max:512',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->getMessageBag()], 400);
        }

        if ($request->access == 'private') {
            $validator = Validator::make($request->all(), [
                'key' => 'required|min:6|max:32',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->getMessageBag()], 400);
            }
        }

        $room = Room::create([
            'name' => $request->name,
            'description' => $request->description,
            'access' => $request->access ?? null,
            'key' => Hash::make($request->key) ?? null,
        ]);
        $room->members()->attach(auth()->user(), ['role_in_room' => 'owner']);

        return response()->json(['success' => 'اتاق جدید با موفقیت ایجاد شد.'], 201);
    }

    public function join(Request $request)
    {
        try {
            $room = Room::where('name', $request->name)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(['error' => 'اتاقی با این مشخصات وجود ندارد'], 404);
        }

        if ($room->members->count() == 50) {
            return response()->json(['error' => 'تعداد اعضای گروه به حداکثر میزان خود رسیده است.',], 400);
        }

        if (
            !count(Message::where('room_id', $room->room_id)->get()) ||
            !Carbon::instance(
                Message::where('room_id', $room->room_id)->latest()->first()->created_at
            )->isToday()
        ) {
            $x = Message::create([
                'message' => Carbon::instance(now())->toDateString(),
                'user_id' => $room->members()->first()->id,
                'room_id' => $room->id,
                'type' => 'time'
            ]);
            $x->type = "time";
            $x->save();
        }

        if (count($room->members) == 50) {
            return response()->json(['error' => 'اعضای گروه کامل است.', 400]);
        }

        if ($room->members->contains(auth()->user())) {
            return response()->json(['error' => 'نمی‌توانید مجدد عضو شوید.', 400]);
        }

        if ($room->access == 'private') {
            if (!isset($request->key)) {
                return response()->json(['error' => 'کلید الزامی است.'], 400);
            }
            if (!Hash::check($request->key, $room->key)) {
                return response()->json(
                    [
                        'error' => 'کلید تطابق ندارد.',
                    ],
                    401
                );
            }
        }
        $x = Message::create([
            'message' => 'وارد گروه شد ' . auth()->user()->name,
            'user_id' => auth()->user()->id,
            'room_id' => $room->id,
            'type' => 'jlk',
        ]);
        $x->type = 'jlk';
        $x->save();

        $room->members()->attach(auth()->user(), ['role_in_room' => 'member']);
        $room->increment('number_of_members');
        return response()->json(['error' => 'با موفقیت در گروه عضو شدید.']);
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

        if (
            !count(Message::where('room_id', $room->room_id)->get()) ||
            !Carbon::instance(Message::where('room_id', $room->room_id)
                ->latest()->first()->created_at)->isToday()
        ) {
            $x = Message::create([
                'message' => Carbon::instance(now())->toDateString(),
                'user_id' => $room->members()->first()->id,
                'room_id' => $room->id,
                'type' => 'time'
            ]);
            $x->type = "time";
            $x->save();
        }

        if (!$room->members->contains(auth()->user())) {
            return response()->json(['error' => 'شما جز اعضای گروه نیستید.',], 403);
        }

        $x = Message::create([
            'message' => 'از گروه خارج شد ' . auth()->user()->name,
            'user_id' => auth()->user()->id,
            'room_id' => $room->id,
            'type' => 'jlk',
        ]);
        $x->type = 'jlk';
        $x->save();

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
            return response()->json(['error' => 'این گروه عمومی است.'], 400);
        }

        if (auth()->user()->id != $room->members()->first()->id) {
            return response()->json(
                ['error' => 'اجازه دسترسی وجود ندارد.'],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required',
            'new-key' => 'required|min:6|max:32',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['error' => $validator->getMessageBag()],
                400
            );
        }

        if (!Hash::check($request->key, $room->key)) {
            return response()->json(['کلید تطابق ندارد.'], 403);
        }

        $room->update(['key' => Hash::make($request->newKey)]);

        return response()->json(
            [
                'success' => 'کلید با موفقیت ریست شد.',
            ],
            201
        );
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

    public function search(string $name)
    {
        $rooms = Room::where('name', 'LIKE', $name . '%')->paginate(20);

        foreach ($rooms as &$room) {
            if ($room->members->contains(auth()->user())) {
                $room->is_join = 1;
            } else {
                $room->is_join = 0;
            }
        }
        return RoomResource::collection($rooms);
    }

    public function update(Request $request, $id)
    {
        try {
            $room = Room::whereId($id)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(['error' => 'اتاقی با این مشخصات وجود ندارد'], 404);
        }

        if (auth()->user()->id != $room->members()->first()->id) {
            return response()->json(
                ['error' => 'اجازه دسترسی وجود ندارد.'],
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
            return response()->json(['room' => $room], 202);
        } elseif ($request->access == 'public') {
            $validator = Validator::make($request->all(), [
                'key' => 'required|min:6|max:32',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->getMessageBag()],400);
            }

            $room->update([
                'name' => $request->name,
                'description' => $request->description,
                'access' => 'public',
                'key' => null,
            ]);
            return response()->json(['room' => $room], 202);
        } else {
            $validator = Validator::make($request->all(), [
                'key' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->getMessageBag()],400);
            }

            if (!Hash::check($request->key, $room->key)) {
                return response()->json(['error' => 'کلید اشتباه است.'], 403);
            }

            $room->update([
                'name' => $request->name,
                'description' => $request->description,
                'access' => 'private',
                'key' => Hash::make($request->key),
            ]);
            return response()->json(['room' => $room], 202);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $room = Room::whereId($id)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(['error' => 'اتاقی با این مشخصات وجود ندارد'], 404);
        }

        if ($room->access == 'private') {
            $validator = Validator::make($request->all(), [
                'key' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->getMessageBag()], 400);
            }

            if (!Hash::check($request->key, $room->key)) {
                return response()->json(['error' => 'کلید اشتباه است.'], 403);
            }
        }

        if (auth()->user()->id != $room->members()->first()->id) {
            return response()->json(['error' => 'اجازه دسترسی وجود ندارد'], 403);
        }

        $room->delete();

        return response()->json(['success' => 'اتاق مورد نظر با موفقیت حذف شد.'], 202);
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
            return response()->json(['error' => 'کاربری با این مشخصات وجود ندارد.'], 404);
        }

        if (auth()->user()->id == $userId) {
            return response()->json(['error' => 'شما نمیتوانید خودتان را حذف کنید.'], 400);
        }

        if (
            !count(Message::where('room_id', $room->room_id)->get()) ||
            !Carbon::instance(Message::where('room_id', $room->room_id)
                ->latest()->first()->created_at)->isToday()
        ) {
            $x = Message::create([
                'message' => Carbon::instance(now())->toDateString(),
                'user_id' => $room->members()->first()->id,
                'room_id' => $room->id,
                'type' => 'time'
            ]);
            $x->type = "time";
            $x->save();
        }

        if (auth()->user()->id != $room->members()->first()->id) {
            return response()->json(['error' => 'اجازه دسترسی وجود ندارد'], 403);
        }
        $x = Message::create([
            'message' => 'از گروه اخراج شد ' . auth()->user()->name,
            'user_id' => auth()->user()->id,
            'room_id' => $roomId,
            'type' => 'jlk',
        ]);
        $x->type = 'jlk';
        $x->save();

        $room->members()->detach($user);
        $room->decrement('number_of_members');

        return response()->json(['success' => 'کاربر مورد نظر با موفقیت از گروه حذف شد.'], 202);
    }
}
