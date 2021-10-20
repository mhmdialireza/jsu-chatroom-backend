<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::paginate(10);
        return UserResource::collection($users);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            return new UserResource(User::find($id)->firstOrFail());
        } catch (\Throwable $th) {
            return 'کاریری با این مشخصات وجود ندارد';
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        if(auth()->user()->is_admin != 1){
            return response()->json(['error','اجازه دسترسی وجود ندارد'], 403);
        }

        try {
            $user = User::find($id)->firstOrFail();
            $user->delete();
            return response()->json(['success', 'کاربر با موفقیت حذف شد.']);
        } catch (\Throwable $th) {
            return 'کاربری با این مشخصات وجود ندارد';
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $email
     * @return \Illuminate\Http\Response
     */
    public function destroyByEmail(string $email)
    {
        if(auth()->user()->is_admin != 1){
            return response()->json(['error','اجازه دسترسی وجود ندارد'], 403);
        }

        try {
            $user = User::where('email', $email)->firstOrFail();
            $user->delete();
            return response()->json(['success', 'کاربر با موفقیت حذف شد.']);
        } catch (\Throwable $th) {
            return 'کاربری با این مشخصات وجود ندارد';
        }
    }
}
