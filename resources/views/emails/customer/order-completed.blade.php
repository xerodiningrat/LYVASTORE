<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan berhasil</title>
</head>
<body style="margin:0;padding:0;background:#e8f0fb;font-family:Arial,sans-serif;color:#0f172a;">
    <div style="padding:44px 16px;background:radial-gradient(circle at top left, rgba(59,130,246,0.16), transparent 34%),radial-gradient(circle at right center, rgba(34,197,94,0.12), transparent 26%),linear-gradient(180deg,#e8f0fb 0%,#f8fbff 100%);">
        <div style="max-width:700px;margin:0 auto;">
            <div style="background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 56%,#22c55e 100%);border:1px solid rgba(191,219,254,0.34);border-radius:34px;padding:40px 38px 48px;box-shadow:0 28px 90px rgba(15,23,42,0.22);">
                <div style="display:inline-block;padding:10px 16px;border-radius:999px;background:rgba(255,255,255,0.14);font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#dcfce7;">
                    Pesanan Selesai
                </div>

                <h1 style="margin:22px 0 12px;font-size:34px;line-height:1.12;color:#ffffff;">
                    Transaksi kamu berhasil diproses
                </h1>

                <p style="margin:0;max-width:540px;font-size:16px;line-height:1.8;color:rgba(255,255,255,0.9);">
                    Halo {{ $transaction->customer_name ?: 'Kak' }}, pesanan LYVA kamu sudah selesai. Semua detail penting sudah kami rangkum biar cepat dicek.
                </p>

                <div style="margin-top:30px;display:inline-block;padding:16px 20px;border-radius:24px;background:rgba(255,255,255,0.12);">
                    <div style="font-size:12px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#bfdbfe;">ID Transaksi</div>
                    <div style="margin-top:6px;font-size:24px;font-weight:700;color:#ffffff;">#{{ $transaction->public_id }}</div>
                </div>
            </div>

            <div style="margin-top:18px;">
                <div style="background:linear-gradient(180deg,#ffffff 0%,#fcfdff 100%);border:1px solid #c7d2fe;border-radius:32px;padding:32px;box-shadow:0 18px 38px rgba(100,116,139,0.14), 0 0 0 8px rgba(255,255,255,0.58);">
                    <div style="font-size:13px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#64748b;">Ringkasan Pesanan</div>

                    <table role="presentation" style="width:100%;margin-top:20px;border-collapse:separate;border-spacing:0 14px;">
                        <tr>
                            <td style="width:34%;padding:0;font-size:14px;color:#64748b;vertical-align:top;">Produk</td>
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
                            <td style="padding:0;font-size:14px;color:#64748b;vertical-align:top;">Metode bayar</td>
                            <td style="padding:0;font-size:15px;font-weight:700;color:#0f172a;">{{ $transaction->payment_method_label ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:0;font-size:14px;color:#64748b;vertical-align:top;">Status</td>
                            <td style="padding:0;">
                                <span style="display:inline-block;padding:8px 12px;border-radius:999px;background:#dcfce7;color:#166534;font-size:13px;font-weight:700;">
                                    Berhasil
                                </span>
                            </td>
                        </tr>
                    </table>

                    @if (filled($transaction->fulfillment_note))
                        <div style="margin-top:20px;background:linear-gradient(180deg,#eff6ff 0%,#f8fbff 100%);border:1px solid #bfdbfe;border-radius:24px;padding:22px 24px;">
                            <div style="font-size:13px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#1d4ed8;">Catatan Hasil</div>
                            <p style="margin:12px 0 0;color:#1e3a8a;line-height:1.85;white-space:pre-line;">{{ $transaction->fulfillment_note }}</p>
                        </div>
                    @endif

                    <div style="margin-top:24px;padding:24px;border-radius:26px;background:#0f172a;">
                        <div style="font-size:13px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#93c5fd;">Akses Cepat</div>
                        <p style="margin:12px 0 16px;font-size:14px;line-height:1.8;color:#cbd5e1;">
                            Buka halaman detail transaksi untuk cek status, invoice, atau beri rating setelah pembelian.
                        </p>
                        <a href="{{ route('checkout.show', ['transaction' => $transaction->public_id]) }}" style="display:inline-block;padding:15px 24px;border-radius:18px;background:#ffffff;color:#1d4ed8;text-decoration:none;font-size:15px;font-weight:700;">
                            Lihat Detail Transaksi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
