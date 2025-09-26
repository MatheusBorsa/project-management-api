<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Utils\ApiResponseUtil;
use App\Utils\PasswordValidatorUtil;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'required|string|max:30|unique:users',
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    new PasswordValidatorUtil()
                ],
            ]);

            if (!User::isValidPhone($validatedData['phone'])) {
                return ApiResponseUtil::error(
                    'Validation error',
                    ['phone' => ['Invalid phone number format']],
                    422
                );
            }

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'password' => $validatedData['password']
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return ApiResponseUtil::success(
                'User Registered Successfully',
                [
                    'user' => $user,
                    'token' => $token
                ],
                201
            );

        } catch (ValidationException $e) {
            return ApiResponseUtil::error(
                'Validation Error',
                $e->errors(),
                422
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Server Error',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
    
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string'
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Credentials are invalid.']
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return ApiResponseUtil::success(
                'Login successful',
                [
                    'user' => $user,
                    'token' => $token
                ],
            );

        } catch (ValidationException $e){
            return ApiResponseUtil::error(
                'Authentication Error',
                $e->errors(),
                401
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Server Error',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return ApiResponseUtil::success(
                'Logged out succesfully',
                null,
                200
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Server Error',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function show(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return ApiResponseUtil::error(
                    'User not authenticated',
                    null,
                    401
                );
            }

            return ApiResponseUtil::success(
                'User retrieved successfully',
                [
                    'user' => $user
                ],
                200
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Server Error',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
