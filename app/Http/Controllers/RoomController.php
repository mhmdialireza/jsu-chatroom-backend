<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Http\Resources\RoomResource;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Http\Services\Image\ImageService;
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

    public function store(Request $request, ImageService $imageService)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'max:16',
                'min:3',
                Rule::unique('rooms')->withoutTrashed(),
            ],
            'access' => ['required', Rule::in(['private', 'public'])],
            'key' => 'required_if:access,private',
            'description' => 'max:512',
            'image' => 'image',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['error' => $validator->getMessageBag()],
                400
            );
        }

        $result = null;
        if ($request->hasFile('image')) {
            $imageService->setExclusiveDirectory(
                'images' . DIRECTORY_SEPARATOR . 'rooms'
            );
            $result = $imageService->save($request->file('image'));
        }

        if ($result === false) {
            return response()->json(
                ['error' => 'آپلود تصویر با خطا مواجه شد.'],
                500
            );
        }

        $room = new Room();
        $room->name = $request->name;
        $room->description = $request->description;
        $room->access = $request->access ?? null;
        $room->key = Hash::make($request->key) ?? null;
        $room->pic_path = $result ?? null;
        $room->save();

        $room->members()->attach(auth()->user(), ['role_in_room' => 'owner']);

        return response()->json(
            ['success' => 'اتاق جدید با موفقیت ایجاد شد.'],
            201
        );
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
            return response()->json(
                ['error' => 'تعداد اعضای گروه به حداکثر میزان خود رسیده است.'],
                400
            );
        }

        if (
            !count(Message::where('room_id', $room->room_id)->get()) ||
            !Carbon::instance(
                Message::where('room_id', $room->room_id)
                    ->latest()
                    ->first()->created_at
            )->isToday()
        ) {
            $x = Message::create([
                'message' => Carbon::instance(now())->toDateString(),
                'user_id' => $room->members()->first()->id,
                'room_id' => $room->id,
                'type' => 'time',
            ]);
            $x->type = 'time';
            $x->save();
        }

        if (count($room->members) == 50) {
            return response()->json(['error' => 'اعضای گروه کامل است'], 400);
        }

        if ($room->members->contains(auth()->user())) {
            return response()->json(
                [
                    'error' => 'نمی‌توانید مجدد عضو شوید.',
                ],
                400
            );
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
            !Carbon::instance(
                Message::where('room_id', $room->room_id)
                    ->latest()
                    ->first()->created_at
            )->isToday()
        ) {
            $x = Message::create([
                'message' => Carbon::instance(now())->toDateString(),
                'user_id' => $room->members()->first()->id,
                'room_id' => $room->id,
                'type' => 'time',
            ]);
            $x->type = 'time';
            $x->save();
        }

        if (!$room->members->contains(auth()->user())) {
            return response()->json(
                ['error' => 'شما جز اعضای گروه نیستید.'],
                403
            );
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
            'new_key' => 'required|min:6|max:32',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['error' => $validator->getMessageBag()],
                400
            );
        }

        if (!Hash::check($request->key, $room->key)) {
            return response()->json(['error'=>'کلید تطابق ندارد.'], 403);
        }

        $room->update(['key' => Hash::make($request->new_key)]);

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

    public function update(Request $request, ImageService $imageService, $id)
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
            'description' => 'max:512',
            'access' => ['required', Rule::in(['private', 'public'])],
            'image' => 'image',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['error' => $validator->getMessageBag()],
                400
            );
        }
        if ($room->pic_path) {
            $imageService->deleteImage($room->pic_path);
        }

        $result = null;
        if ($request->hasFile('image')) {
            $imageService->setExclusiveDirectory(
                'images' . DIRECTORY_SEPARATOR . 'rooms'
            );
            $result = $imageService->save($request->file('image'));
        }

        if ($result === false) {
            return response()->json(
                ['error' => 'آپلود تصویر با خطا مواجه شد.'],
                500
            );
        }

        if ($request->access == $room->access) {
            $room->name = $request->name;
            $room->description = $request->description ?? null;
            $room->pic_path = $result ?? $room->pic_path;
            $room->save();
            return response()->json(['room' => new RoomResource($room)], 202);
        } elseif ($request->access == 'public') {
            $validator = Validator::make($request->all(), [
                'key' => 'required|min:6|max:32',
            ]);
            if ($validator->fails()) {
                return response()->json(
                    ['error' => $validator->getMessageBag()],
                    400
                );
            }
            if (!Hash::check($request->key, $room->key)) {
                return response()->json(['error' => 'کلید اشتباه است.'], 403);
            }
            $room->name = $request->name;
            $room->description = $request->description ?? null;
            $room->access = 'public';
            $room->pic_path = $result ?? $room->pic_path;
            $room->key = null;
            $room->save();
            return response()->json(['room' => new RoomResource($room)], 202);
        } else {
            $validator = Validator::make($request->all(), [
                'key' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(
                    ['error' => $validator->getMessageBag()],
                    400
                );
            }

            $room->name = $request->name;
            $room->description = $request->description ?? null;
            $room->access = 'private';
            $room->pic_path = $result ?? $room->pic_path;
            $room->key = Hash::make($request->key);
            $room->save();

            return response()->json(['room' => new RoomResource($room)], 202);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $room = Room::whereId($id)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(
                ['error' => 'اتاقی با این مشخصات وجود ندارد'],
                404
            );
        }
        
        if ($room->access == 'private') {
            $validator = Validator::make($request->all(), [
                'key' => 'required',
            ]);
            
            if ($validator->fails()) {
                return response()->json(
                    ['error' => $validator->getMessageBag()],
                    400
                );
            }

            if (!Hash::check($request->key, $room->key)) {
                return response()->json(['error' => 'کلید اشتباه است.'], 403);
            }
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

        if (auth()->user()->id != $room->members()->first()->id) {
            return response()->json(
                ['error' => 'اجازه دسترسی وجود ندارد'],
                403
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

        if (
            !count(Message::where('room_id', $room->room_id)->get()) ||
            !Carbon::instance(
                Message::where('room_id', $room->room_id)
                    ->latest()
                    ->first()->created_at
            )->isToday()
        ) {
            $x = Message::create([
                'message' => Carbon::instance(now())->toDateString(),
                'user_id' => $room->members()->first()->id,
                'room_id' => $room->id,
                'type' => 'time',
            ]);
            $x->type = 'time';
            $x->save();
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

        return response()->json(
            ['success' => 'کاربر مورد نظر با موفقیت از گروه حذف شد.'],
            202
        );
    }
}
