<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Hash; // Penting untuk cek password
use App\Models\Admin;
use App\Models\Petugas;

class OTPController extends Controller
{
    // STEP 1: Verifikasi Username & Password, lalu Kirim OTP
    public function step1_verify_credentials(Request $request)
    {
        // 1. Validasi input login
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // 2. Cari user di tabel admin atau petugas
        $user = Admin::where('username', $request->username)->first() 
                ?? Petugas::where('username', $request->username)->first();

        // 3. Cek apakah user ada dan password benar
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Username atau password salah'], 401);
        }

        // 4. Generate OTP
        $otp = rand(100000, 999999);

        // 5. SIMPAN OTP KE DATABASE (Ini yang sebelumnya terlewat)
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(5); // Berlaku 5 menit
        $user->save();

        // 6. Kirim Email via PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'sandbox.smtp.mailtrap.io';
            $mail->SMTPAuth   = true;
            $mail->Username   = '713100785abfb8'; // Kredensial kamu
            $mail->Password   = '2960cada8f41d8'; 
            $mail->Port       = 2525;

            $mail->setFrom('admin@arsip-polda.test', 'Sistem Arsip Polda');
            $mail->addAddress($user->email); // Kirim ke email user yang ditemukan

            $mail->isHTML(true);
            $mail->Subject = 'Kode OTP Login';
            $mail->Body    = "Halo <b>{$user->nama}</b>, <br> Kode OTP Anda adalah: <b>$otp</b>";

            $mail->send();
            
            return response()->json([
                'message' => 'Kredensial valid, OTP telah dikirim ke email!',
                'username' => $user->username
            ], 200);

        } catch (Exception $e) {
            return response()->json(['message' => "Gagal mengirim email: {$mail->ErrorInfo}"], 500);
        }
    }

    // STEP 2: Verifikasi OTP & Berikan Token
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'otp_code' => 'required|digits:6',
        ]);

        $user = Admin::where('username', $request->username)->first() 
                ?? Petugas::where('username', $request->username)->first();

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        // Cek kecocokan OTP
        if ($user->otp_code !== $request->otp_code) {
            return response()->json(['message' => 'Kode OTP salah!'], 401);
        }

        // Cek apakah sudah expired
        if (now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'Kode OTP sudah kadaluwarsa!'], 401);
        }

        // Bersihkan OTP agar tidak bisa dipakai ulang
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        // Buat Token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login Berhasil!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }
}