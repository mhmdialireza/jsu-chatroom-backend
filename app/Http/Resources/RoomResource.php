<?php

namespace App\Http\Resources;

use App\Http\Resources\UserResource;
use App\Http\Resources\RoomUserResource;
use Hekmatinasser\Verta\Verta;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public static $wrap = 'room';

    public function toArray($request)
    {
        $date = explode('-', explode(' ', $this->created_at)[0]);
        $dateArray = Verta::getJalali(...$date);
        $time = explode(':', explode(' ', $this->created_at)[1]);

        $date = [
            'year' => $dateArray[0],
            'month' => $dateArray[1],
            'day' => $dateArray[2],
            'hour' => (int) $time[0],
            'minute' => (int) $time[1],
            'seconde' => (int) $time[2],
        ];
        return [
            'id' => $this->id,
            'name' => $this->name,
            'owner_id' => $this->members()->first()->id,
            'description' => $this->description,
            'access' => $this->access,
            'number_of_members' => $this->number_of_members,
            'created_at' => $date,
            'last_message' => new MessageResource(
                $this->messages()
                    ->whereNotIn('type', ['time'])
                    ->latest()
                    ->first()
            ),
            'number_of_messages' => $this->messages()->count(),
        ];
    }
}
