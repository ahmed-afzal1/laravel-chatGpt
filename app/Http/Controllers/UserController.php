<?php

namespace App\Http\Controllers;

use App\Models\User;
use Validator;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Auth;
use Laravel\Passport\Client as OClient;

class UserController extends Controller
{
    public $successStatus = 200;

    // User Login
    public function login()
    {
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
            return $this->getTokenAndRefreshToken(request('email'), request('password'));
        }
        else {
            return response()->json(['error'=>'Unauthorised'], 401);
        }
    }

    // User Register
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 422);
        }

        $password = $request->password;
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        return $this->getTokenAndRefreshToken($user->email, $password);
    }

    // Generate Bearer Token and Refresh Token
    public function getTokenAndRefreshToken($email, $password) {
        $oClient = OClient::where('password_client', 1)->first();
        $http = new Client;
        $response = $http->request('POST', env('APP_URL').'/oauth/token', [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => $oClient->id,
                'client_secret' => $oClient->secret,
                'username' => $email,
                'password' => $password,
                'scope' => '*',
            ],
        ]);

        $result = json_decode((string) $response->getBody(), true);
        return response()->json($result, $this->successStatus);
    }
}
