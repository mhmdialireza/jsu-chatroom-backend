<?php

namespace App\Observers;

use App\Models\Room;

class RoomObserver
{
    /**
     * Handle the Room "created" event.
     *
     * @param  \App\Models\Room  $room
     * @return void
     */
    public function created(Room $room)
    {
        $room->members()->attach(auth()->user(), ['role_in_room' => 'owner']);
    }

    /**
     * Handle the Room "updated" event.
     *
     * @param  \App\Models\Room  $room
     * @return void
     */
    public function updated(Room $room)
    {
        //
    }

    /**
     * Handle the Room "deleted" event.
     *
     * @param  \App\Models\Room  $room
     * @return void
     */
    public function deleted(Room $room)
    {
        $messages = $room->messages;

        foreach ($messages as &$message) {
            $message->delete();
        }
    }

    /**
     * Handle the Room "restored" event.
     *
     * @param  \App\Models\Room  $room
     * @return void
     */
    public function restored(Room $room)
    {
        //
    }

    /**
     * Handle the Room "force deleted" event.
     *
     * @param  \App\Models\Room  $room
     * @return void
     */
    public function forceDeleted(Room $room)
    {
        //
    }
}
