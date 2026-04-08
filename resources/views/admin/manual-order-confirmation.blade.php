<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Konfirmasi Pesanan Manual</title>
    @if (in_array($state, ['success', 'info'], true))
        <meta http-equiv="refresh" content="1.8;url={{ $checkoutUrl }}">
    @endif
    <style>
        :root {
            color-scheme: light;
            --bg: #eff6ff;
            --panel: rgba(255, 255, 255, 0.94);
            --text: #0f172a;
            --muted: #64748b;
            --line: rgba(148, 163, 184, 0.25);
            --success: #16a34a;
            --info: #2563eb;
            --error: #dc2626;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 32%),
                linear-gradient(180deg, #f8fbff 0%, var(--bg) 100%);
            font-family: "Segoe UI", "Inter", sans-serif;
            color: var(--text);
        }

        .card {
            width: min(680px, 100%);
            border: 1px solid var(--line);
            border-radius: 30px;
            background: var(--panel);
            padding: 32px;
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.12);
            backdrop-filter: blur(10px);
        }

        .eyebrow {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            color: var(--muted);
        }

        h1 {
            margin: 14px 0 10px;
            font-size: clamp(28px, 5vw, 42px);
            line-height: 1.02;
        }

        .message {
            margin: 0;
            font-size: 16px;
            line-height: 1.8;
            color: #334155;
        }

        .hint {
            margin-top: 10px;
            font-size: 13px;
            line-height: 1.7;
            color: var(--muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 22px;
            padding: 12px 18px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 700;
        }

        .badge.success {
            color: var(--success);
            background: rgba(22, 163, 74, 0.1);
        }

        .badge.info {
            color: var(--info);
            background: rgba(37, 99, 235, 0.1);
        }

        .badge.error {
            color: var(--error);
            background: rgba(220, 38, 38, 0.1);
        }

        .summary {
            margin-top: 28px;
            padding: 22px;
            border-radius: 24px;
            border: 1px solid var(--line);
            background: rgba(248, 250, 252, 0.9);
        }

        .summary-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .summary-label {
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .summary-value {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 20px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button.primary {
            color: #fff;
            background: #0f172a;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.18);
        }

        .button.secondary {
            color: var(--text);
            background: #fff;
            border: 1px solid var(--line);
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="eyebrow">Telegram Confirmation</div>
        <h1>Konfirmasi Pesanan Manual</h1>
        <p class="message">{{ $message }}</p>
        @if (in_array($state, ['success', 'info'], true))
            <p class="hint">Halaman ini akan lanjut otomatis ke detail transaksi dalam beberapa detik.</p>
        @endif

        <div class="badge {{ $state }}">
            @if ($state === 'success')
                <span>Pesanan berhasil ditandai selesai</span>
            @elseif ($state === 'info')
                <span>Pesanan ini sebelumnya sudah selesai</span>
            @else
                <span>Pesanan belum bisa diproses</span>
            @endif
        </div>

        <section class="summary">
            <div class="summary-grid">
                <div>
                    <div class="summary-label">ID Transaksi</div>
                    <div class="summary-value">#{{ $transaction->public_id }}</div>
                </div>
                <div>
                    <div class="summary-label">Produk</div>
                    <div class="summary-value">{{ $transaction->product_name }}</div>
                </div>
                <div>
                    <div class="summary-label">Paket</div>
                    <div class="summary-value">{{ $transaction->package_label }}</div>
                </div>
                <div>
                    <div class="summary-label">Status</div>
                    <div class="summary-value">{{ strtoupper($transaction->status) }}</div>
                </div>
            </div>
        </section>

        <div class="actions">
            <a class="button primary" href="{{ $checkoutUrl }}">Lihat detail transaksi</a>
        </div>
    </main>
</body>
</html>
