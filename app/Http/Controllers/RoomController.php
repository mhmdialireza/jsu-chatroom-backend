<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\RoomResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rooms = Room::paginate(10);
        return RoomResource::collection($rooms);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:16|min:3|unique:rooms,name',
            'is_private' => 'boolean',
            'description' => 'max:512|min:3',
        ]);

        if ($validator->fails()) {
            return $validator->getMessageBag();
        }

        if ($request->is_private == 1) {
            $room = Room::create([
                'name' => $request->name,
                'description' => $request->description,
                'is_private' => 1,
                'key' => Hash::make(Str::random(16)),
                'creator_id' => auth()->user()->id,
            ]);
        } else {
            $room = Room::create([
                'name' => $request->name,
                'description' => $request->description,
                'creator_id' => auth()->user()->id,
            ]);
        }

        $room->users()->save(auth()->user());

        return $room;
    }

    public function join(Request $request, $roomId)
    {
        $room = null;

        try {
            $room = Room::find($roomId)->firstOrFail();
        } catch (\Throwable $th) {
            return 'اتاقی با این مشخصات وجود ندارد';
        }

        if ($room->users->contains(auth()->user())) {
            return response()->json([
                'error' =>
                    'شما جز اعضای گروه هستید ، نمی‌توانید مجدد عضو شوید.',
            ]);
        }

        if ($room->is_private) {
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

        $room->users()->save(auth()->user());
        $room->increment('number_of_members');
        return 'ok';
    }

    public function resetKey(Request $request, $roomId)
    {
        $room = null;

        try {
            $room = Room::find($roomId)->firstOrFail();
        } catch (\Throwable $th) {
            return 'اتاقی با این مشخصات وجود ندارد';
        }

        if (!$room->is_private) {
            return 'این گروه عمومی است و کلید ندارد';
        }

        if (
            auth()->user()->is_admin != 1 &&
            auth()->user()->id != $room->creator_id
        ) {
            return response()->json(
                ['error' => 'اجازه دسترسی وجود ندارد'],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required',
            'newKey' => 'required|confirmed|min:16|max:16',
        ]);

        if ($validator->fails()) {
            return $validator->getMessageBag();
        }

        if (!Hash::check($request->key, $room->key)) {
            return 'کلید اصلی تطابق ندارد.';
        }

        $room->update(['key' => Hash::make($request->newKey)]);

        return 'ok';
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $name)
    {
        try {
            return new RoomResource(Room::where('name', $name)->firstOrFail());
        } catch (\Throwable $th) {
            return 'اتاقی با این مشخصات وجود ندارد';
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $room = null;
        try {
            $room = Room::find($id)->firstOrFail();
        } catch (\Throwable $th) {
            return 'اتاقی با این مشخصات وجود ندارد';
        }

        if (
            auth()->user()->is_admin != 1 &&
            auth()->user()->id != $room->creator_id
        ) {
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
            'is_private' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $validator->getMessageBag();
        }

        $newKey = Str::random(16);

        if ($request->is_private == $room->is_private) {
            $room->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);
        } elseif ($request->is_private == 0) {
            $room->update([
                'name' => $request->name,
                'description' => $request->description,
                'is_private' => 0,
                'key' => null,
            ]);
        } else {
            $room->update([
                'name' => $request->name,
                'description' => $request->description,
                'is_private' => 1,
                'key' => Hash::make($newKey),
            ]);
            return response()->json(['room' => $room, 'key' => $newKey]);
        }

        return response()->json(['room' => $room]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $room = null;
        try {
            $room = Room::find($id)->firstOrFail();
        } catch (\Throwable $th) {
            return 'اتاقی با این مشخصات وجود ندارد';
        }

        if (
            auth()->user()->is_admin != 1 &&
            auth()->user()->id != $room->creator_id
        ) {
            return response()->json(
                ['error' => 'اجازه دسترسی وجود ندارد'],
                403
            );
        }

        $room->delete();

        return response()->json(['ok', 'اتاق مورد نظر حذف شد.']);
    }
}
