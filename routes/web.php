<?php

use App\Http\Controllers\MessageController;
use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use Hekmatinasser\Verta\Facades\Verta;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
// 1635352926
Route::get('/{x}', function ($x) {
    $x = Message::create([
        'message' => Carbon::instance(now())->toDateString(),
        'user_id' => 1,
        'room_id' => 1,
        'type' => "jlk"
    ]);
$x->type = "time";
$x->save();
   return Carbon::instance(Message::latest()->first()->created_at)->isToday();
});


Route::get('/{start}/{end}',[MessageController::class,'getAllMessageInPeriodOfTime']);
Route::get('/{start}/{end}',[MessageController::class,'cakeChart']);
// 2021-10-27 20:12:06