<?php
// 1. Memuat autoloader dari Composer
require 'vendor/autoload.php';

// 2. Fungsi sederhana untuk membaca file .env (jika belum pakai library Dotenv)
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

loadEnv(__DIR__ . '/.env');

// 3. Import Namespace PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Pengaturan Server Mailtrap (ambil data dari gambar kredensial kamu)
    $mail->isSMTP();
    $mail->Host       = 'sandbox.smtp.mailtrap.io';
    $mail->SMTPAuth   = true;
    $mail->Username   = '713100785abfb8';
    $mail->Password   = '2960cada8f41d8'; // Ganti dengan password asli dari Mailtrap
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 2525;

    // Penerima & Pengirim
    $mail->setFrom('admin-arsip@poldadiy.test', 'Sistem Arsip Polda DIY');
    $mail->addAddress('tujuan-test@arsip-polda.test'); // Email tujuan testing

    // Konten Email OTP
    $otp_code = rand(100000, 999999); 
    $mail->isHTML(true);
    $mail->Subject = 'Kode OTP Login Sistem Arsip';
    $mail->Body    = "Halo, <br> Kode OTP Anda adalah: <b>$otp_code</b>.";

    $mail->send();
    echo 'OTP berhasil dikirim ke Mailtrap!';
} catch (Exception $e) {
    echo "Gagal mengirim email. Error: {$mail->ErrorInfo}";
}