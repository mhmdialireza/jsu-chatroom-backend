<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Http\Resources\RoomResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\MessageResource;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($roomId)
    {
        try {
            $room = Room::find($roomId)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'اتاقی با این مشخصات وجود ندارد.',
            ]);
        }

        if (!$room->members->contains(auth()->user())) {
            return response()->json([
                'error' => 'شما در گروه عضو نیستید',
            ],403);
        }

        $message = Message::where('room_id', $roomId)->paginate(50);
        return MessageResource::collection($message);
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
            'message' => 'required|min:1|max:1024',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['error' => $validator->getMessageBag()],
                400
            );
        }

        try {
            Room::find($request->room_id)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(
                ['error', 'اتاقی با این مشخصات وجود ندارد'],
                404
            );
        }

        try {
            User::find($request->user_id)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(
                ['error', 'کاربری با این مشخصات وجود ندارد.'],
                404
            );
        }

        Message::create([
            'message' => $request->message,
            'user_id' => $request->user_id,
            'room_id' => $request->room_id,
        ]);

        return response()->json(['success' => 'پیام با موفقیت ثبت شد.'], 201);
    }
}
