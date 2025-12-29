<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Petugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // 1. LIHAT SEMUA AKUN (Index)
    public function index()
    {
        $user = auth()->user();
        if (!($user instanceof \App\Models\Admin && $user->role === 'superadmin')) {
            return response()->json(['message' => 'Akses ditolak! Khusus Super Admin.'], 403);
        }

        return response()->json([
            'admins' => \App\Models\Admin::all(),
            'petugas' => \App\Models\Petugas::all()
        ]);

        return response()->json([
            'message' => 'Daftar semua user berhasil diambil',
            'data' => [
                'admins' => Admin::all(),
                'petugas' => Petugas::all()
            ]
        ], 200);
    }

    // 2. TAMBAH USER (Store)
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!($user instanceof \App\Models\Admin && $user->role === 'superadmin')) {
            return response()->json(['message' => 'Akses ditolak! Khusus Super Admin.'], 403);
        }

        return response()->json([
            'admins' => \App\Models\Admin::all(),
            'petugas' => \App\Models\Petugas::all()
        ]);

        $request->validate([
            'nama'      => 'required|string',
            'username'  => 'required|string|unique:admin,username|unique:petugas,username',
            'password'  => 'required|min:6',
            'email'     => 'required|email|unique:admin,email|unique:petugas,email',
            'tipe_user' => 'required|in:admin,petugas', // Pembeda tabel
            'role'      => 'nullable|in:admin,superadmin' // Hanya jika tipe_user adalah admin
        ]);

        if ($request->tipe_user === 'admin') {
            $user = \App\Models\Admin::create([
                'nama'     => $request->nama,
                'username' => $request->username,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => $request->role ?? 'admin',
            ]);
        } else {
            $user = \App\Models\Petugas::create([
                'nama'     => $request->nama,
                'username' => $request->username,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);
        }
        
        // DEBUG: Paksa aplikasi untuk menampilkan data yang baru saja disimpan
        return response()->json([
            'message' => 'User berhasil dibuat',
            'id_baru' => $user->id_admin ?? $user->id_petugas, // Cek apakah ID-nya muncul (misal: 1, 2, dst)
            'data_asli_db' => $user->fresh() // .fresh() akan mengambil data terbaru langsung dari tabel
        ], 201);

        return response()->json(['message' => 'User berhasil dibuat', 'data' => $user], 201);
    }

    // 3. UPDATE USER
    // Format URL: /api/manage-users/{id}?tipe={admin/petugas}
    public function update(Request $request, $id)
    {

        $user = auth()->user();
        if (!($user instanceof \App\Models\Admin && $user->role === 'superadmin')) {
            return response()->json(['message' => 'Akses ditolak! Khusus Super Admin.'], 403);
        }

        return response()->json([
            'admins' => \App\Models\Admin::all(),
            'petugas' => \App\Models\Petugas::all()
        ]);

        $tipe = $request->query('tipe'); // Ambil parameter tipe dari URL
        
        if ($tipe === 'admin') {
            $user = Admin::findOrFail($id);
        } else {
            $user = Petugas::findOrFail($id);
        }

        $request->validate([
            'nama'     => 'sometimes|required|string',
            'username' => "sometimes|required|unique:admin,username,{$id},id_admin|unique:petugas,username,{$id},id_petugas",
            'email' => "sometimes|required|email|unique:admin,email,{$id},id_admin|unique:petugas,email,{$id},id_petugas",
            'password' => 'nullable|min:6',
            'role'     => 'nullable|in:admin,superadmin'
        ]);

        $user->nama = $request->nama ?? $user->nama;
        $user->username = $request->username ?? $user->username;
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($tipe === 'admin' && $request->has('role')) {
            $user->role = $request->role;
        }

        $user->save();

        return response()->json(['message' => 'User berhasil diperbarui', 'data' => $user], 200);
    }

    // 4. HAPUS USER
    public function destroy(Request $request, $id)
    {

        $user = auth()->user();
        if (!($user instanceof \App\Models\Admin && $user->role === 'superadmin')) {
            return response()->json(['message' => 'Akses ditolak! Khusus Super Admin.'], 403);
        }

        return response()->json([
            'admins' => \App\Models\Admin::all(),
            'petugas' => \App\Models\Petugas::all()
        ]);

        $tipe = $request->query('tipe');

        if ($tipe === 'admin') {
            // Cegah Super Admin menghapus dirinya sendiri
            if (auth()->user()->id_admin == $id) {
                return response()->json(['message' => 'Anda tidak bisa menghapus akun sendiri!'], 400);
            }
            $user = Admin::findOrFail($id);
        } else {
            $user = Petugas::findOrFail($id);
        }

        $user->delete();
        return response()->json(['message' => 'User berhasil dihapus'], 200);
    }
}