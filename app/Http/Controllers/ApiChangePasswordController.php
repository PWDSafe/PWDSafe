<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class ApiChangePasswordController extends Controller
{
    public function store(Request $request): Response|Application|ResponseFactory
    {
        $params = $this->validate($request, [
            'username' => 'required',
            'old_password' => 'required',
            'new_password' => 'required',
        ]);

        $user = User::where('email', $params['username'])->first();

        abort_if(is_null($user), \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN);
        abort_unless(Hash::check($params['old_password'], $user->password), \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN);

        $user->password = Hash::make($params['new_password']);
        $user->save();

        return response(['status' => 'OK']);
    }
}
