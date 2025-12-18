<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function kirimDataLogin(Request $request)
    {
        if (! $request->filled('username') || ! $request->filled('password')) {
            return response()->json([
                'message' => 'Username dan password harus diisi.',
            ], 422);
        }

        $pengguna = $this->mengidentifikasiUser(
            $request->input('username'),
            $request->input('password')
        );

        if (! $pengguna) {
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

    private function mengidentifikasiUser(string $username, string $password): ?Pengguna
    {
        $pengguna = Pengguna::where('username', $username)->first();

        if (! $pengguna || ! Hash::check($password, $pengguna->password)) {
            return null;
        }

        return $pengguna;
    }
}
