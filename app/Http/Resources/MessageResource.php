<?php

namespace App\Http\Resources;

use Hekmatinasser\Verta\Verta;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'message';

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $date = explode('-',explode(' ',$this->created_at)[0]);
        $dateArray = Verta::getJalali(...$date);
        $time = explode(':',explode(' ',$this->created_at)[1]);

        $date =[ 'year'=> $dateArray[0] ,'month' => $dateArray[1], 'day' => $dateArray[2], 'hour' =>(int) $time[0] ,'minute' => (int)$time[1], 'seconde' => (int)$time[2]];
        
        return [
            'id' => $this->id,
            'message' => $this->message,
            'user_id' => $this->user_id,
            'room_id' => $this->room_id,
            'created_at' => $date,
        ];
    }
}
