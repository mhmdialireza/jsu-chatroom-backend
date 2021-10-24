<?php

namespace App\Http\Resources;

use App\Http\Resources\RoomUserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'room';

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'owner_id' => $this->members()->first()->id,
            'description' => $this->description,
            'access' => $this->access,
            'number_of_members' => $this->number_of_members,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // 'members' =>  UserResource::collection($this->members),
        ];
    }
}
