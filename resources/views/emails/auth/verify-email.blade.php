<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email LYVA</title>
</head>
<body style="margin:0;padding:0;background:#dfe9fb;font-family:Arial,sans-serif;color:#0f172a;">
    <div style="padding:44px 16px;background:radial-gradient(circle at top left, rgba(59,130,246,0.18), transparent 34%),radial-gradient(circle at top right, rgba(56,189,248,0.16), transparent 28%),linear-gradient(180deg,#dfe9fb 0%,#eef4ff 100%);">
        <div style="max-width:680px;margin:0 auto;">
            <div style="background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 52%,#38bdf8 100%);border:1px solid rgba(191,219,254,0.34);border-radius:32px;padding:40px 38px 46px;box-shadow:0 28px 90px rgba(15,23,42,0.22);">
                <div style="display:inline-block;padding:10px 16px;border-radius:999px;background:rgba(255,255,255,0.14);font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#dbeafe;">
                    Lyva Indonesia
                </div>

                <h1 style="margin:22px 0 12px;font-size:34px;line-height:1.15;color:#ffffff;">
                    Verifikasi email akunmu
                </h1>

                <p style="margin:0;max-width:540px;font-size:16px;line-height:1.8;color:rgba(255,255,255,0.88);">
                    Halo {{ $user->name ?: 'Kak' }}, satu langkah lagi supaya akun LYVA kamu aktif penuh. Tekan tombol di bawah untuk memverifikasi email dan melanjutkan login dengan aman.
                </p>

                <div style="margin-top:30px;">
                    <a href="{{ $verificationUrl }}" style="display:inline-block;padding:16px 28px;border-radius:18px;background:#ffffff;color:#1d4ed8;text-decoration:none;font-size:15px;font-weight:700;box-shadow:0 14px 30px rgba(15,23,42,0.18);">
                        Verifikasi Email
                    </a>
                </div>
            </div>

            <div style="margin-top:18px;">
                <div style="background:linear-gradient(180deg,#ffffff 0%,#fdfefe 100%);border:1px solid #c7d2fe;border-radius:30px;padding:30px;box-shadow:0 18px 36px rgba(100,116,139,0.14), 0 0 0 8px rgba(255,255,255,0.58);">
                    <div style="font-size:13px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#64748b;">
                        Detail Keamanan
                    </div>

                    <div style="margin-top:18px;background:linear-gradient(180deg,#f8fbff 0%,#eff6ff 100%);border:1px solid #dbeafe;border-radius:24px;padding:22px 24px;">
                        <div style="font-size:15px;line-height:1.8;color:#334155;">
                            Link verifikasi ini hanya untuk akun:
                            <strong style="color:#0f172a;">{{ $user->email }}</strong>
                        </div>
                        <div style="margin-top:10px;font-size:14px;line-height:1.8;color:#475569;">
                            Kalau tombol di atas tidak bekerja, buka link berikut di browser:
                        </div>
                        <div style="margin-top:10px;padding:14px 16px;border-radius:16px;background:#ffffff;border:1px solid #dbeafe;word-break:break-all;font-size:13px;line-height:1.8;color:#2563eb;">
                            {{ $verificationUrl }}
                        </div>
                    </div>

                    <div style="margin-top:18px;padding:18px 20px;border-radius:24px;background:#f8fafc;border:1px solid #e2e8f0;font-size:14px;line-height:1.8;color:#475569;">
                        Kalau kamu tidak merasa membuat akun LYVA, email ini bisa diabaikan dengan aman.
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
