<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function mengidentifikasiUser(Request $request)
    {
        if (! $request->filled('username') || ! $request->filled('password')) {
            return response()->json([
                'message' => 'Username dan password harus diisi.',
            ], 422);
        }

        $pengguna = Pengguna::where('username', $request->input('username'))->first();

        if (! $pengguna || ! Hash::check($request->input('password'), $pengguna->password)) {
            return response()->json([
                'message' => 'Username atau password salah.',
            ], 401);
        }

        $token = $pengguna->createToken('admin-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'pengguna' => $pengguna,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
