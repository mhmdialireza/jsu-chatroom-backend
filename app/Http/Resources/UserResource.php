<?php

namespace App\Http\Resources;

use Hekmatinasser\Verta\Verta;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'user';

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
            'name' => $this->name,
            'email' => $this->email,
            'role_in_site' => $this->role_in_site,
            'created_at' => $date,
            'updated_at' => $this->updated_at,
            'role_in_room' => $this->pivot->role_in_room ?? null,
        ];
    }
}
