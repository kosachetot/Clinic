<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = DB::connection('hris')->table('users')
            ->where('username', $request->username)
            ->where('status', 'active')
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid username or password'], 401);
        }

        $emp = DB::connection('hris')->table('hris_main')
            ->where('id', $user->emp_id)
            ->first();

        $fullName = $emp
            ? trim(($emp->first_name ?? '') . ' ' . ($emp->sur_name ?? ''))
            : $user->username;

        $photoUrl = ($emp && !empty($emp->picture_link))
            ? 'https://hrmis.doh12.com/storage/app/public/' . $emp->picture_link
            : null;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'emp_id' => $user->emp_id,
                'username' => $user->username,
                'level' => $user->level,
                'full_name' => $fullName ?: $user->username,
                'position' => null, // TODO: kapag alam na ang table ng position
                'photo_url' => $photoUrl,
            ],
        ]);
    }
}
