<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\ZohoCrmApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller as BaseController;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends BaseController
{
    /**
     * Create a new user if it doesn't exist and init auth on Zoho.
     *
     * @param  Request  $request
     * @todo add check if user already exists
     */
    public function auth(Request $request)
    {
        $data = $request->toArray();

        $user = DB::table('users')->where('email', '=', $data['email'])->first();

        if ($user) {
            return response(json_encode([
                'code' => 'ERROR',
                'message' => "User with {$data['email']} already exists",
            ]), 409);
        }

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
        ]);
        /** @var newAccessToken $accessToken */
        $accessToken = $user->createToken('regular');

        return response(array(
            'status' => 'success',
            'message' => 'A new user was registered on the service. Go to the zohoAuthLink to pass the authorization process on Zoho.',
            'zohoAuthLink' => route('auth-zoho'),
            'data' => [
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'accessToken' => $accessToken->plainTextToken,
                ]
            ]
        ));
    }

    public function refreshAccessToken(Request $request)
    {
        $data = $request->toArray();
        $user = DB::table('users')->where('email', '=', $data['email'])->first();

        if (Hash::check($data['password'], $user->password)) {
            dd($user);
            return response(array(
                'status' => 'success',
                'message' => 'Access token successfully obtained.',
                'data' => [
                    'user' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'accessToken' => "",
                    ]
                ]
            ));
        }
    }

    public function authZoho(Request $request)
    {
        $user = DB::table('users')->first();
        $request->session()->put('user_id', $user->id);

        return redirect(ZohoCrmApi::generateAuthURL());
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function authZohoCallback(Request $request): JsonResponse
    {
        $user = User::find($request->session()->get('user_id'));
        ZohoCrmApi::generateOauth($user, $request->get('code'));

        return response()->json([
            'status'  => true,
            'message' => 'User was created on Zoho.',
            'user' => $user
        ]);
    }
}
