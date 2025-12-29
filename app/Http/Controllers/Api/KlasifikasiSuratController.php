<?php

namespace App\Http\Controllers\Api; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KlasifikasiSurat;

class KlasifikasiSuratController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {
        return response()->json(KlasifikasiSurat::all());
    }

    public function store(Request $request) {
        $user = auth()->user();
        // Hanya Super Admin (Admin dengan role superadmin) yang boleh input master data
        if (!($user instanceof \App\Models\Admin && $user->role === 'superadmin')) {
            return response()->json([
                'message' => 'Hanya Super Admin yang boleh menambah klasifikasi'
            ], 403);
        }

        $request->validate(['nama_klasifikasi' => 'required|unique:klasifikasi_surat']);
        $data = KlasifikasiSurat::create($request->all());
        return response()->json(['message' => 'Berhasil', 'data' => $data], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
