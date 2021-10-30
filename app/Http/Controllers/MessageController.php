<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Resources\RoomResource;
use App\Http\Resources\UserResource;
use Hekmatinasser\Verta\Facades\Verta;
use App\Http\Resources\MessageResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public function index($roomId)
    {
        try {
            $room = Room::whereId($roomId)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'اتاقی با این مشخصات وجود ندارد.',
            ]);
        }

        if (!$room->members->contains(auth()->user())) {
            return response()->json(
                [
                    'error' => 'شما در گروه عضو نیستید',
                ],
                403
            );
        }

        $message = Message::where('room_id', $roomId)->paginate(50);
        return MessageResource::collection($message);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|min:1|max:1024',
            'room_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['error' => $validator->getMessageBag()],
                400
            );
        }

        try {
            $room = Room::whereId($request->room_id)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(
                ['error' => 'اتاقی با این مشخصات وجود ندارد'],
                404
            );
        }

        if (!$room->members->contains(auth()->user())) {
            return response()->json(
                ['error' => 'کاربری با این مشخصات وجود ندارد.'],
                404
            );
        }

        Message::create([
            'message' => $request->message,
            'user_id' => auth()->user()->id,
            'room_id' => $request->room_id,
        ]);

        return response()->json(['success' => 'پیام با موفقیت ثبت شد.'], 201);
    }

    public function getAllMessageInPeriodOfTime($start, $end)
    {
        $startArray = explode('-', $start);
        $endArray = explode('-', $end);

        if (!Verta::isValidDate($startArray[0], $startArray[1], $startArray[2])) {
            return response()->json(['error' => 'تاریخ وارد شده معتبر نیست.'], 400);
        }
        if (!Verta::isValidTime($startArray[3], $startArray[4], $startArray[5])) {
            return response()->json(['error' => 'زمان وارد شده معتبر نیست.'], 400);
        }

        if (!Verta::isValidDate($endArray[0], $endArray[1], $endArray[2])) {
            return response()->json(['error' => 'تاریخ وارد شده معتبر نیست.'], 400);
        }
        if (!Verta::isValidTime($endArray[3], $endArray[4], $endArray[5])) {
            return response()->json(['error' => 'زمان وارد شده معتبر نیست.'], 400);
        }
        [$startArray[0], $startArray[1], $startArray[2]] = Verta::getGregorian(
            $startArray[0],
            $startArray[1],
            $startArray[2]
        );
        [$endArray[0], $endArray[1], $endArray[2]] = Verta::getGregorian(
            $endArray[0],
            $endArray[1],
            $endArray[2]
        );

        $startDateString = "$startArray[0]-$startArray[1]-$startArray[2] $startArray[3]:$startArray[4]:$startArray[5]";
        $endDateString = "$endArray[0]-$endArray[1]-$endArray[2] $endArray[3]:$endArray[4]:$endArray[5]";
        $startDateStringEnd = "$startArray[0]-$startArray[1]-$startArray[2] 23:59:59";

        try {
            $diffDays = Carbon::parse($startDateString)->diffInDays(
                Carbon::parse($endDateString)
            );
        } catch (\Throwable $th) {
            return response()->json(['error' => 'تاریخ وارد شده معتبر نیست.'], 400);
        }
        $customDates = new Collection([
            [$startDateString, $startDateStringEnd],
        ]);

        for ($i = 0; $i < $diffDays; $i++) {
            if ($i == $diffDays - 1) {
                $customDates->add([
                   Carbon::Parse($startDateStringEnd)
                            ->addDays($i)
                            ->addSeconds(1)
                   ->toDateTimeString(),
                    $endDateString,
                ]);
                break;
            }
            $customDates->add([
                 Carbon::parse($startDateStringEnd)
                        ->addSecond()
                        ->addDays($i)
                ->toDateTimeString(),
                Carbon::parse($startDateStringEnd)->addDays($i + 1)
                ->toDateTimeString(),
            ]);
        }

        $massageBag = new Collection();

        foreach ($customDates as $customDate) {
            $arrTime = explode(' ', $customDate[0]);

            $arrTimeD = explode('-', $arrTime[0]);
            $arrTimeT = explode(':', $arrTime[1]);

            $finalTime1 = Carbon::instance(Verta::create(...$arrTimeD, ...$arrTimeT))->toDateTimeString();

            $arrTime = explode(' ', $customDate[1]);

            $arrTimeD = explode('-', $arrTime[0]);
            $arrTimeT = explode(':', $arrTime[1]);

            $finalTime2 = Carbon::instance(Verta::create(...$arrTimeD, ...$arrTimeT))->toDateTimeString();

            $query = Message::whereBetween('created_at', [
                ...$customDate,
            ])->get();

            $massageBag->add(['messages' => $query, 'time' => [$finalTime1, $finalTime2], 'count' => $query->count()]);
        }

        return $massageBag;
    }

    public function cakeChart($start, $end)
    {
        $startArray = explode('-', $start);
        $endArray = explode('-', $end);


        if (!Verta::isValidDate($startArray[0], $startArray[1], $startArray[2])) {
            return response()->json(['error' => 'تاریخ وارد شده معتبر نیست.'], 400);
        }
        if (!Verta::isValidTime($startArray[3], $startArray[4], $startArray[5])) {
            return response()->json(['error' => 'زمان وارد شده معتبر نیست.'], 400);
        }

        if (!Verta::isValidDate($endArray[0], $endArray[1], $endArray[2])) {
            return response()->json(['error' => 'تاریخ وارد شده معتبر نیست.'], 400);
        }
        if (!Verta::isValidTime($endArray[3], $endArray[4], $endArray[5])) {
            return response()->json(['error' => 'زمان وارد شده معتبر نیست.'], 400);
        }

        [$startArray[0], $startArray[1], $startArray[2]] = Verta::getGregorian(
            $startArray[0],
            $startArray[1],
            $startArray[2]
        );
        [$endArray[0], $endArray[1], $endArray[2]] = Verta::getGregorian(
            $endArray[0],
            $endArray[1],
            $endArray[2]
        );

        $startDateString = "$startArray[0]-$startArray[1]-$startArray[2] $startArray[3]:$startArray[4]:$startArray[5]";
        $endDateString = "$endArray[0]-$endArray[1]-$endArray[2] $endArray[3]:$endArray[4]:$endArray[5]";
        
        $roomNames = Room::pluck('name')->toArray();

        $messages = Message::whereBetween('created_at', [$startDateString, $endDateString])->get();
        // return $messages;
        $allRoomNames = null;
        foreach ($messages as $m) {
            $allRoomNames[] = Room::find($m->room_id)->name;
        }
        // dd($allRoomNames);
        $ratings = null;
        
        for ($i = 0; $i < count($roomNames ?? []); $i++) {
            $counter = 0;
            for ($j = 0; $j < count($allRoomNames); $j++) {
                if ($roomNames[$i] == $allRoomNames[$j]) {
                    $counter++;
                }
            }
            $ratings[$roomNames[$i]] = ($counter / count($messages)) * 100;
        }
        
        return $ratings;
    }
}
