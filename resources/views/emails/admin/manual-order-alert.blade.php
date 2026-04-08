<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan manual baru</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
    <div style="padding:32px 16px;background:radial-gradient(circle at top left, rgba(249,115,22,0.18), transparent 30%),radial-gradient(circle at top right, rgba(15,23,42,0.16), transparent 28%),#f8fafc;">
        <div style="max-width:700px;margin:0 auto;">
            <div style="background:linear-gradient(135deg,#111827 0%,#1f2937 48%,#f97316 100%);border-radius:32px;padding:34px 34px 44px;box-shadow:0 24px 80px rgba(15,23,42,0.16);">
                <div style="display:inline-block;padding:10px 16px;border-radius:999px;background:rgba(255,255,255,0.12);font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#fed7aa;">
                    Manual Fulfillment
                </div>

                <h1 style="margin:22px 0 12px;font-size:34px;line-height:1.12;color:#ffffff;">
                    Pesanan manual baru masuk
                </h1>

                <p style="margin:0;max-width:560px;font-size:16px;line-height:1.8;color:rgba(255,255,255,0.9);">
                    Ada order yang butuh tindak lanjut admin. Setelah akun, kode, atau invite dikirim ke customer, tinggal selesaikan dari panel admin.
                </p>
            </div>

            <div style="margin-top:-24px;padding:0 16px;">
                <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:30px;padding:28px;box-shadow:0 24px 60px rgba(148,163,184,0.14);">
                    <div style="font-size:13px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#64748b;">Ringkasan Order</div>

                    <table role="presentation" style="width:100%;margin-top:18px;border-collapse:separate;border-spacing:0 12px;">
                        <tr>
                            <td style="width:34%;padding:0;font-size:14px;color:#64748b;vertical-align:top;">ID transaksi</td>
                            <td style="padding:0;font-size:15px;font-weight:700;color:#0f172a;">#{{ $transaction->public_id }}</td>
                        </tr>
                        <tr>
                            <td style="padding:0;font-size:14px;color:#64748b;vertical-align:top;">Produk</td>
                            <td style="padding:0;font-size:15px;font-weight:700;color:#0f172a;">{{ $transaction->product_name }}</td>
                        </tr>
                        <tr>
                            <td style="padding:0;font-size:14px;color:#64748b;vertical-align:top;">Paket</td>
                            <td style="padding:0;font-size:15px;font-weight:700;color:#0f172a;">{{ $transaction->package_label }}</td>
                        </tr>
                        <tr>
                            <td style="padding:0;font-size:14px;color:#64748b;vertical-align:top;">Total</td>
                            <td style="padding:0;font-size:15px;font-weight:700;color:#0f172a;">Rp{{ number_format((int) $transaction->total, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="padding:0;font-size:14px;color:#64748b;vertical-align:top;">Customer</td>
                            <td style="padding:0;font-size:15px;font-weight:700;color:#0f172a;">{{ $transaction->customer_name ?: 'Guest Customer' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:0;font-size:14px;color:#64748b;vertical-align:top;">WhatsApp</td>
                            <td style="padding:0;font-size:15px;font-weight:700;color:#0f172a;">{{ $transaction->customer_whatsapp ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:0;font-size:14px;color:#64748b;vertical-align:top;">Email</td>
                            <td style="padding:0;font-size:15px;font-weight:700;color:#0f172a;">{{ $transaction->customer_email ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:0;font-size:14px;color:#64748b;vertical-align:top;">Status stok</td>
                            <td style="padding:0;">
                                <span style="display:inline-block;padding:8px 12px;border-radius:999px;background:#fff7ed;color:#c2410c;font-size:13px;font-weight:700;">
                                    {{ $stockStatusLabel ?? 'Menunggu pengecekan admin' }}
                                </span>
                            </td>
                        </tr>
                    </table>

                    <div style="margin-top:22px;padding:22px;border-radius:24px;background:#111827;">
                        <div style="font-size:13px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#fdba74;">Aksi Admin</div>
                        <p style="margin:12px 0 16px;font-size:14px;line-height:1.8;color:#d1d5db;">
                            Buka panel transaksi untuk cek stok, kirim data ke customer, lalu selesaikan order dari dashboard admin.
                        </p>
                        <a href="{{ route('admin.transactions.index') }}" style="display:inline-block;padding:15px 24px;border-radius:18px;background:#ffffff;color:#ea580c;text-decoration:none;font-size:15px;font-weight:700;">
                            Buka Panel Admin
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
