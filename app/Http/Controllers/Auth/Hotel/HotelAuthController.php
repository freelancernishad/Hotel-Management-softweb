<?php

namespace App\Http\Controllers\Auth\Hotel;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\HotelManagement\Hotel;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

class HotelAuthController extends Controller
{
    /**
     * Hotel login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string', // এখানে username বা email আসবে
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $login = $request->input('email'); // এখানে email বা username আসবে
        $password = $request->input('password');

        // Determine whether input is email or username
        $credentials = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? ['email' => $login, 'password' => $password]
            : ['username' => $login, 'password' => $password];

        if (! $token = Auth::guard('hotel')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $hotel = Auth::guard('hotel')->user();

        $payload = [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'description' => $hotel->description,
            'location' => $hotel->location,
            'contact_number' => $hotel->contact_number,
            'email' => $hotel->email,
            'image' => $hotel->image,
            'is_active' => $hotel->is_active,
            'username' => $hotel->username,
            'role' => "hotel",
        ];

        return response()->json([
            'token' => $token,
            'hotel' => $payload,
        ], 200);
    }


    /**
     * Hotel logout
     */
    public function logout()
    {
        try {
            $token = JWTAuth::getToken();
            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Token not provided.'], 401);
            }

            JWTAuth::invalidate($token);

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully.'
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Current hotel info
     */
    public function me()
    {
        $hotel = Auth::guard('hotel')->user();

        $payload = [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'description' => $hotel->description,
            'location' => $hotel->location,
            'contact_number' => $hotel->contact_number,
            'email' => $hotel->email,
            'image' => $hotel->image,
            // 'manager_id' => $hotel->manager_id,
            'is_active' => $hotel->is_active,
            'username' => $hotel->username,
            'role' => "hotel",
        ];

        return response()->json([
            'hotel' => $payload,
        ], 200);
    }

    /**
     * Change hotel password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hotelUser = Auth::guard('hotel')->user();

        if (!Hash::check($request->current_password, $hotelUser->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 400);
        }

        // Retrieve the Eloquent model instance
        $hotel = \App\Models\HotelManagement\Hotel::find($hotelUser->id);
        $hotel->password = Hash::make($request->new_password);
        $hotel->save();

        $payload = [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'email' => $hotel->email,
            'location' => $hotel->location,
            'is_active' => $hotel->is_active,
            'username' => $hotel->username,
            'role' => "hotel",
        ];

        return response()->json([
            'hotel' => $payload,
            'message' => 'Password updated successfully.'
        ], 200);
    }

    /**
     * Check JWT token validity
     */
    public function checkToken(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token not provided.'], 400);
        }

        try {
            $hotel = JWTAuth::setToken($token)->authenticate();

            if (!$hotel) {
                return response()->json(['message' => 'Token is invalid.'], 401);
            }

            return response()->json(['message' => 'Token is valid'], 200);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token has expired.'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token is invalid.'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token is missing or invalid.'], 401);
        }
    }



    /**
     * Update hotel profile
     */
    public function updateProfile(Request $request)
    {
        $hotelUser = Auth::guard('hotel')->user();

        $validator = Validator::make($request->all(), [
            'name'           => 'sometimes|required|string|max:255',
            'description'    => 'sometimes|nullable|string',
            'location'       => 'sometimes|required|string',
            'contact_number' => 'sometimes|required|string',
            'email'          => 'sometimes|required|email|unique:hotels,email,' . $hotelUser->id,
            'image'          => 'sometimes|nullable|string',
            'username'       => 'sometimes|required|string|unique:hotels,username,' . $hotelUser->id,
            'is_active'      => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hotel = \App\Models\HotelManagement\Hotel::find($hotelUser->id);

        // Update only provided fields
        $fields = ['name', 'description', 'location', 'contact_number', 'email', 'image', 'username', 'is_active'];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $hotel->$field = $request->$field;
            }
        }

        $hotel->save();

        $payload = [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'description' => $hotel->description,
            'location' => $hotel->location,
            'contact_number' => $hotel->contact_number,
            'email' => $hotel->email,
            'image' => $hotel->image,
            'username' => $hotel->username,
            'is_active' => $hotel->is_active,
            'role' => "hotel",
        ];

        return response()->json([
            'hotel' => $payload,
            'message' => 'Profile updated successfully.'
        ], 200);
    }




}
