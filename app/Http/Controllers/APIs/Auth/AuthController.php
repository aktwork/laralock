<?php

namespace App\Http\Controllers\APIs\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Auth\AccessVerifyUser;
# Models
use App\Models\Auth\User;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:airlock', ['except' => ['login', 'register', 'register_verify', 'lost_password', 'lost_password_verify', 'password_renew']]);
    }

    public function login()
    {
        $validator = Validator::make(request()->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'device' => 'nullable|string|alpha_num'
        ]);
        if ($validator->fails()) {
            return response()->json(errorResponse($validator->errors()), 202);
        }
        $credentials = request(['email', 'password']);
        if (!$token = Auth::attempt($credentials)) {
            return response()->json(errorResponse('Account not found !'), 202);
        }
        if (Auth::user()->active == User_setActiveStatus('active')) {
            return $this->respondWithToken(Auth::user()->createToken(request('device') ? (request('device') . "-" . getClientIpAddress()) : ("_jwtApiToken-" . getClientIpAddress()))->plainTextToken);
        }
        return response()->json(errorResponse('Your account has been ' . User_getActiveStatus(Auth::user()->active)), 202);
    }

    public function logout()
    {
        if (Auth::user()->tokens()->delete()) {
            return response()->json(successResponse('Successfully Logout'), 201);
        }
        return response()->json(errorResponse('Failed to Logout'), 202);
    }

    protected function respondWithToken($token)
    {
        return response()->json(dataResponse([
            'account_name' => Auth::user()->userBio->name,
            'status' => User_getStatusForHuman(Auth::user()->userstat->status),
            'access_token' => $token,
            'token_type' => 'bearer'
        ], '', 'Authorization'));
    }
}
