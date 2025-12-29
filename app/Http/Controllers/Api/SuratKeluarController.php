<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuratKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class SuratKeluarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = \App\Models\SuratKeluar::with(['kategori', 'admin', 'petugas'])->latest()->get();

        return response()->json([
            'message' => 'Daftar Surat Keluar Berhasil Diambil',
            'data' => $data
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'tgl_surat'          => 'required|date',
            'penerima_surat'     => 'required|string', 
            'perihal'            => 'required|string',
            'klasifikasi_surat'  => 'required',
            'id_klasifikasi'     => 'required|exists:klasifikasi_surat,id_klasifikasi',
            'file_surat'         => 'required', // Bisa satu file PDF atau array Gambar
            'resolusi'           => 'nullable|in:low,medium,high'
        ]);

        $user = auth()->user();
        $now = now();
        
        // --- 2. LOGIKA NOMOR SURAT KELUAR OTOMATIS (SK) ---
        $bulan = $now->format('m');
        $tahun = $now->format('Y');
        $romawi = ['01'=>'I', '02'=>'II', '03'=>'III', '04'=>'IV', '05'=>'V', '06'=>'VI', '07'=>'VII', '08'=>'VIII', '09'=>'IX', '10'=>'X', '11'=>'XI', '12'=>'XII'];
        $bulanRomawi = $romawi[$bulan];

        $lastSurat = \App\Models\SuratKeluar::whereYear('tgl_input', $tahun)
                                        ->whereMonth('tgl_input', $bulan)
                                        ->orderBy('id_suratkeluar', 'desc')
                                        ->first();

        $noUrut = $lastSurat ? str_pad(intval(substr($lastSurat->no_surat, 0, 3)) + 1, 3, '0', STR_PAD_LEFT) : '001';
        $noSurat = "{$noUrut}/SK/{$bulanRomawi}/{$tahun}"; // Menggunakan kode SK
        
        $namaFilePdf = str_replace('/', '-', $noSurat) . '.pdf';
        $path = 'surat_keluar/' . $namaFilePdf;

        // --- 3. LOGIKA PROSES FILE (Hybrid) ---
        $fileInput = $request->file('file_surat');

        // Jika upload PDF langsung
        if (!is_array($fileInput) && $fileInput->getClientOriginalExtension() == 'pdf') {
            \Storage::disk('public')->putFileAs('surat_keluar', $fileInput, $namaFilePdf);
        } 
        // Jika scan gambar
        else {
            $images = [];
            $files = is_array($fileInput) ? $fileInput : [$fileInput];
            $pilihanResolusi = $request->input('resolusi', 'high');

            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());

            foreach ($files as $file) {
                if (in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png'])) {
                    $img = $manager->read($file->getRealPath());

                    if ($pilihanResolusi == 'low') {
                        $img->resize(800, null, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    } elseif ($pilihanResolusi == 'medium') {
                        $img->resize(1200, null, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    }

                    $images[] = (string) $img->encode()->toDataUri();
                }
            }

            if (count($images) > 0) {
                // Gunakan view pdf.surat_keluar (pastikan filenya sudah ada)
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.surat_keluar', compact('images'));
                \Storage::disk('public')->put($path, $pdf->output());
            } else {
                return response()->json(['message' => 'Tidak ada gambar valid yang diunggah'], 400);
            }
        }

        // --- 4. SIMPAN KE DATABASE ---
        $surat = \App\Models\SuratKeluar::create([
            'no_surat'          => $noSurat,
            'tgl_surat'         => $request->tgl_surat,
            'penerima_surat'    => $request->penerima_surat,
            'perihal'           => $request->perihal,
            'klasifikasi_surat' => $request->klasifikasi_surat,
            'id_klasifikasi'    => $request->id_klasifikasi,
            'file_surat'        => $path,
            'tgl_input'         => $now->toDateTimeString(),
            'id_admin'          => $user instanceof \App\Models\Admin ? $user->id_admin : null,
            'id_petugas'        => $user instanceof \App\Models\Petugas ? $user->id_petugas : null,
        ]);

        return response()->json([
            'message' => 'Surat Keluar berhasil disimpan dalam format PDF oleh ' . $user->nama,
            'data'    => $surat
        ], 201);
    }

    public function download($id)
    {
        $surat = \App\Models\SuratKeluar::findOrFail($id);

        if (!\Storage::disk('public')->exists($surat->file_surat)) {
            return response()->json(['message' => 'File fisik tidak ditemukan'], 404);
        }

        $namaFileAman = str_replace('/', '-', $surat->no_surat);
        $ekstensi = pathinfo($surat->file_surat, PATHINFO_EXTENSION);
        $namaDownload = "Surat_Keluar_" . $namaFileAman . "." . $ekstensi;
        
        if (ob_get_level()) { ob_end_clean(); }
        return \Storage::disk('public')->download($surat->file_surat, $namaDownload);
    }

    public function preview($id)
    {
        $surat = \App\Models\SuratKeluar::findOrFail($id);

        if (!\Storage::disk('public')->exists($surat->file_surat)) {
            return response()->json(['message' => 'File tidak ditemukan di server'], 404);
        }

        $path = storage_path('app/public/' . $surat->file_surat);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $surat->no_surat . '.pdf"'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = auth()->user();

        // Proteksi Role Petugas
        if ($user instanceof \App\Models\Petugas) {
            return response()->json([
                'message' => 'Akses ditolak! Petugas hanya diperbolehkan menginput data.'
            ], 403);
        }

        $surat = \App\Models\SuratKeluar::findOrFail($id);

        $validated = $request->validate([
            'no_surat'           => 'sometimes|required|unique:surat_keluar,no_surat,'.$id.',id_suratkeluar',
            'tgl_surat'          => 'sometimes|required|date',
            'penerima_surat'     => 'sometimes|required|string',
            'perihal'            => 'sometimes|required|string',
            'klasifikasi_surat'  => 'sometimes|required',
            'id_kategori'        => 'sometimes|required|exists:kategori_surat,id_kategori',
            'file_surat'         => 'nullable', 
            'file_surat.*'       => 'image|mimes:jpg,png,jpeg|max:2048',
        ]);

        if ($request->hasFile('file_surat')) {
            // Hapus file lama
            if ($surat->file_surat && \Storage::disk('public')->exists($surat->file_surat)) {
                \Storage::disk('public')->delete($surat->file_surat);
            }

            $images = [];
            foreach ($request->file('file_surat') as $file) {
                $data = base64_encode(file_get_contents($file->getRealPath()));
                $images[] = 'data:' . $file->getMimeType() . ';base64,' . $data;
            }

            $namaFilePdf = str_replace('/', '-', $surat->no_surat) . '.pdf';
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.surat_keluar', compact('images'));
            $content = $pdf->output();
            $path = 'surat_keluar/' . $namaFilePdf;

            \Storage::disk('public')->put($path, $content);
            $validated['file_surat'] = $path;
        }

        $surat->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data Surat Keluar berhasil diperbarui!',
            'data'    => $surat
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = auth()->user();

        if ($user instanceof \App\Models\Petugas) {
            return response()->json([
                'message' => 'Akses ditolak! Hanya Admin atau Super Admin yang boleh menghapus data.'
            ], 403);
        }
        
        $surat = \App\Models\SuratKeluar::find($id);

        if (!$surat) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        if ($surat->file_surat) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($surat->file_surat);
        }

        $surat->delete();

        return response()->json(['message' => 'Surat Keluar berhasil dihapus!'], 200);
    }
}