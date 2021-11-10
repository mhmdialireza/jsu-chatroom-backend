<?php

namespace App\Http\Resources;

use Hekmatinasser\Verta\Verta;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public static $wrap = 'user';

    public function toArray($request)
    {
        $date = explode('-', explode(' ', $this->created_at)[0]);
        $dateArray = Verta::getJalali(...$date);
        $time = explode(':', explode(' ', $this->created_at)[1]);

        $date = [
            'year' => $dateArray[0], 'month' => $dateArray[1], 'day' => $dateArray[2],
            'hour' => (int)$time[0], 'minute' => (int)$time[1], 'seconde' => (int)$time[2]
        ];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'profile_path' => $this->profile_path,
            'role_in_site' => $this->role_in_site ?? 'user',
            'created_at' => $date,
            'role_in_room' => $this->pivot->role_in_room ?? null,
        ];
    }
}
