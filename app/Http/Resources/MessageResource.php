<?php

namespace App\Http\Resources;

use App\Models\User;
use Hekmatinasser\Verta\Verta;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public static $wrap = 'message';

    public function toArray($request)
    {
        $date = explode('-',explode(' ',$this->created_at)[0]);
        $dateArray = Verta::getJalali(...$date);
        $time = explode(':',explode(' ',$this->created_at)[1]);

        $date =[ 'year'=> $dateArray[0] ,'month' => $dateArray[1], 'day' => $dateArray[2], 'hour' =>(int) $time[0] ,'minute' => (int)$time[1], 'seconde' => (int)$time[2]];
        
        return [
            'id' => $this->id,
            'text' => $this->message,
            'user_name' => (new UserResource(User::find($this->user_id)))->name,
            'user_id' => $this->user_id,
            'room_id' => $this->room_id,
            'created_at' => $date,
        ];
    }
}
