<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuratMasuk;
use App\Http\Resources\SuratMasukResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class SuratMasukController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Mengambil data surat, urutkan dari yang terbaru
        // with() digunakan agar data kategori & admin/petugas ikut terbawa (Eager Loading)
        $data = \App\Models\SuratMasuk::with(['kategori', 'admin', 'petugas'])->latest()->get();

        return response()->json([
            'message' => 'Daftar Surat Masuk Berhasil Diambil',
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
            'pengirim'           => 'required|string',
            'perihal'            => 'required|string',
            'klasifikasi_surat'  => 'required',
            'id_klasifikasi'     => 'required|exists:klasifikasi_surat,id_klasifikasi',
            'file_surat'         => 'required', // Bisa satu file PDF atau array Gambar
            'resolusi'           => 'nullable|in:low,medium,high'
        ]);

        $user = auth()->user();
        $now = now();
        
        // --- 2. LOGIKA NOMOR SURAT OTOMATIS (Sesuai Kode Lama Kamu) ---
        $bulan = $now->format('m');
        $tahun = $now->format('Y');
        $romawi = ['01'=>'I', '02'=>'II', '03'=>'III', '04'=>'IV', '05'=>'V', '06'=>'VI', '07'=>'VII', '08'=>'VIII', '09'=>'IX', '10'=>'X', '11'=>'XI', '12'=>'XII'];
        $bulanRomawi = $romawi[$bulan];

        $lastSurat = \App\Models\SuratMasuk::whereYear('tgl_input', $tahun)
                                        ->whereMonth('tgl_input', $bulan)
                                        ->orderBy('id_suratmasuk', 'desc')
                                        ->first();

        $noUrut = $lastSurat ? str_pad(intval(substr($lastSurat->no_surat, 0, 3)) + 1, 3, '0', STR_PAD_LEFT) : '001';
        $noSurat = "{$noUrut}/SM/{$bulanRomawi}/{$tahun}";
        
        // Nama file PDF menggunakan nomor surat (garis miring diganti strip agar aman)
        $namaFilePdf = str_replace('/', '-', $noSurat) . '.pdf';
        $path = 'surat_masuk/' . $namaFilePdf;

        // --- 3. LOGIKA PROSES FILE (Hybrid: PDF vs Scan Gambar) ---
        $fileInput = $request->file('file_surat');

        // JIKA USER UPLOAD 1 FILE PDF LANGSUNG (Pastikan bukan array dan ekstensinya pdf)
        if (!is_array($fileInput) && $fileInput->getClientOriginalExtension() == 'pdf') {
            
            // Langsung simpan PDF asli tanpa lewat Intervention Image
            \Storage::disk('public')->putFileAs('surat_masuk', $fileInput, $namaFilePdf);

        } 
        // JIKA USER SCAN GAMBAR (KAMERA HP / MESIN SCAN)
        else {
            $images = [];
            // Pastikan kita selalu berurusan dengan array file
            $files = is_array($fileInput) ? $fileInput : [$fileInput];
            $pilihanResolusi = $request->input('resolusi', 'high');

            // Gunakan FQN Driver sesuai permintaanmu
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());

            foreach ($files as $file) {
                // VALIDASI TAMBAHAN: Pastikan file ini benar-benar gambar sebelum di-read
                if (in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png'])) {
                    
                    // Membaca gambar
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

                    // Masukkan ke array dalam format Data URI
                    $images[] = (string) $img->encode()->toDataUri();
                }
            }

            // Pastikan array images tidak kosong sebelum generate PDF
            if (count($images) > 0) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.surat_masuk', compact('images'));
                \Storage::disk('public')->put($path, $pdf->output());
            } else {
                return response()->json(['message' => 'Tidak ada gambar valid yang diunggah'], 400);
            }
        }

        // --- 4. SIMPAN KE DATABASE (Sesuai Kolom Terbaru Kamu) ---
        $surat = \App\Models\SuratMasuk::create([
            'no_surat'          => $noSurat,
            'tgl_surat'         => $request->tgl_surat,
            'pengirim'          => $request->pengirim,
            'perihal'           => $request->perihal,
            'klasifikasi_surat' => $request->klasifikasi_surat,
            'id_klasifikasi'    => $request->id_klasifikasi,
            'file_surat'        => $path, // Simpan path PDF ke kolom file_surat
            'tgl_input'         => $now->toDateTimeString(),
            'id_admin'          => $user instanceof \App\Models\Admin ? $user->id_admin : null,
            'id_petugas'        => $user instanceof \App\Models\Petugas ? $user->id_petugas : null,
        ]);

        return response()->json([
            'message' => 'Surat Masuk berhasil disimpan dalam format PDF oleh ' . $user->nama,
            'data'    => $surat
        ], 201);
    }


    public function download($id)
    {
        $surat = \App\Models\SuratMasuk::findOrFail($id);

        if (!\Storage::disk('public')->exists($surat->file_surat)) {
            return response()->json(['message' => 'File fisik tidak ditemukan'], 404);
        }

        // Ganti karakter "/" pada nomor surat menjadi "-" agar aman untuk nama file
        $namaFileAman = str_replace('/', '-', $surat->no_surat);
        
        $ekstensi = pathinfo($surat->file_surat, PATHINFO_EXTENSION);

        $namaDownload = "Surat_Masuk_" . $namaFileAman . "." . $ekstensi;
        
        // TAMBAHKAN INI: Membersihkan output buffer agar file tidak corrupt
        if (ob_get_level()) {
            ob_end_clean();
        }
        return \Storage::disk('public')->download($surat->file_surat, $namaDownload);
    }

    public function preview($id)
    {
        $surat = \App\Models\SuratMasuk::findOrFail($id);

        // Cek apakah file ada di storage
        if (!\Storage::disk('public')->exists($surat->file_surat)) {
            return response()->json(['message' => 'File tidak ditemukan di server'], 404);
        }

        // Ambil path lengkap file
        $path = storage_path('app/public/' . $surat->file_surat);

        // Memberikan respon 'file' (secara default bersifat inline/preview)
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $surat->no_surat . '.pdf"'
        ]);
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
        $user = auth()->user();

        // --- PROTEKSI ROLE ---
        // Jika user adalah Petugas, maka dilarang update
        if ($user instanceof \App\Models\Petugas) {
            return response()->json([
                'message' => 'Akses ditolak! Petugas hanya diperbolehkan menginput data.'
            ], 403);
        }

        $surat = \App\Models\SuratMasuk::findOrFail($id);

        // 1. Validasi data
        $validated = $request->validate([
            'no_surat'           => 'sometimes|required|unique:surat_masuk,no_surat,'.$id.',id_suratmasuk',
            'tgl_surat'          => 'sometimes|required|date',
            'pengirim'           => 'sometimes|required|string',
            'perihal'            => 'sometimes|required|string',
            'klasifikasi_surat'  => 'sometimes|required',
            'id_kategori'        => 'sometimes|required|exists:kategori_surat,id_kategori',
            'file_surat'         => 'nullable', 
            'file_surat.*'       => 'image|mimes:jpg,png,jpeg|max:2048',
        ]);

        // 2. Cek apakah ada file baru yang diupload
        if ($request->hasFile('file_surat')) {
            
            // --- HAPUS FILE LAMA ---
            if ($surat->file_surat && \Storage::disk('public')->exists($surat->file_surat)) {
                \Storage::disk('public')->delete($surat->file_surat);
            }

            // --- PROSES KONVERSI FILE BARU KE PDF (Tetap menggunakan logic kamu) ---
            $images = [];
            foreach ($request->file('file_surat') as $file) {
                $data = base64_encode(file_get_contents($file->getRealPath()));
                $images[] = 'data:' . $file->getMimeType() . ';base64,' . $data;
            }

            $namaFilePdf = str_replace('/', '-', $surat->no_surat) . '.pdf';
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.surat_masuk', compact('images'));
            $content = $pdf->output();
            $path = 'surat_masuk/' . $namaFilePdf;

            \Storage::disk('public')->put($path, $content);
            $validated['file_surat'] = $path;
        }

        // 3. Update data lainnya di database
        $surat->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data Surat Masuk berhasil diperbarui!',
            'data'    => $surat
        ], 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = auth()->user();

        // --- PROTEKSI ROLE ---
        // Izinkan jika user adalah Admin ATAU SuperAdmin. Jika Petugas, tolak.
        if ($user instanceof \App\Models\Petugas) {
            return response()->json([
                'message' => 'Akses ditolak! Hanya Admin atau Super Admin yang boleh menghapus data.'
            ], 403);
        }
        
        $surat = \App\Models\SuratMasuk::find($id);

        if (!$surat) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // 1. Hapus file fisiknya dari storage
        if ($surat->file_surat) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($surat->file_surat);
        }

        // 2. Hapus data dari database
        $surat->delete();

        return response()->json(['message' => 'Surat berhasil dihapus!'], 200);
    }
}
