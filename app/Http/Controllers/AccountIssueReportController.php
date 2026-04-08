<?php

namespace App\Http\Controllers;

use App\Models\AccountIssueReport;
use App\Services\TelegramBotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AccountIssueReportController extends Controller
{
    public function __invoke(Request $request, TelegramBotService $telegram): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $profileWhatsapp = trim((string) $user->whatsapp_number);

        if ($profileWhatsapp === '') {
            return redirect()
                ->route('profile.edit')
                ->with('status', 'Isi nomor WhatsApp di profil dulu supaya tim support bisa mengabari hasil proses akun bermasalah.');
        }

        $validated = $request->validate([
            'product_id' => ['required', 'string', 'max:120'],
            'product_name' => ['required', 'string', 'max:160'],
            'issue_type' => ['required', 'string', 'in:login-failed,premium-not-active,wrong-account,limit-or-verification,other'],
            'transaction_reference' => ['nullable', 'string', 'max:120'],
            'account_email' => ['required', 'email:rfc,dns', 'max:255'],
            'issue_message' => ['required', 'string', 'min:12', 'max:2000'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'website' => ['nullable', 'string', 'max:255'],
            'formStartedAt' => ['required', 'integer', 'min:1'],
        ]);

        $proofPath = $request->file('proof')?->store('account-issues', 'public');
        $proofUrl = $proofPath ? Storage::disk('public')->url($proofPath) : null;
        $report = AccountIssueReport::create([
            'user_id' => $user->id,
            'public_id' => 'AIR'.strtoupper(Str::random(10)),
            'status' => AccountIssueReport::STATUS_PENDING,
            'product_id' => (string) $validated['product_id'],
            'product_name' => (string) $validated['product_name'],
            'issue_type' => (string) $validated['issue_type'],
            'transaction_reference' => filled($validated['transaction_reference'] ?? null) ? (string) $validated['transaction_reference'] : null,
            'account_email' => (string) $validated['account_email'],
            'contact_whatsapp' => $profileWhatsapp,
            'issue_message' => (string) $validated['issue_message'],
            'proof_path' => $proofPath,
            'proof_url' => $proofUrl,
        ]);

        $issueTypeLabels = [
            'login-failed' => 'Login tidak masuk',
            'premium-not-active' => 'Premium belum aktif',
            'wrong-account' => 'Email / akun salah',
            'limit-or-verification' => 'Akun kena limit / verifikasi',
            'other' => 'Masalah lain',
        ];

        $messageLines = [
            '<b>Laporan akun bermasalah baru</b>',
            '',
            '<b>ID laporan:</b> '.e($report->public_id),
            '<b>Produk:</b> '.e((string) $validated['product_name']),
            '<b>Jenis masalah:</b> '.e($issueTypeLabels[$validated['issue_type']] ?? (string) $validated['issue_type']),
            '<b>Email akun target:</b> '.e((string) $validated['account_email']),
            '<b>WhatsApp profil:</b> '.e($profileWhatsapp),
        ];

        $messageLines[] = '<b>User:</b> #'.$user->id.' - '.e((string) $user->name);

        if (filled($validated['transaction_reference'] ?? null)) {
            $messageLines[] = '<b>Referensi transaksi:</b> '.e((string) $validated['transaction_reference']);
        }

        if ($proofUrl) {
            $messageLines[] = '<b>Bukti:</b> '.e($proofUrl);
        }

        $messageLines[] = '';
        $messageLines[] = '<b>Kronologi:</b>';
        $messageLines[] = e(Str::limit((string) $validated['issue_message'], 1200));

        try {
            if ($telegram->configured()) {
                $telegram->sendMessage(implode("\n", $messageLines));
                $report->forceFill([
                    'telegram_notified_at' => now(),
                ])->save();
            } else {
                Log::info('Account issue report submitted without Telegram configuration.', [
                    'report_id' => $report->public_id,
                    'product_id' => $validated['product_id'],
                    'product_name' => $validated['product_name'],
                    'issue_type' => $validated['issue_type'],
                    'proof_url' => $proofUrl,
                    'user_id' => $user?->id,
                ]);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return back()->with('status', 'Laporan akun berhasil dikirim ke admin. Tim support akan proses akun ini, dan kalau invite ulang atau perbaikannya berhasil kamu akan dikabari lewat WhatsApp profil.');
    }
}
