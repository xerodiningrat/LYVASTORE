<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateWithdrawal;
use App\Models\User;
use App\Services\AffiliateService;
use Inertia\Inertia;
use Inertia\Response;

class AffiliateMonitorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Affiliates', [
            'applications' => User::query()
                ->whereIn('affiliate_status', ['pending', 'approved', 'rejected'])
                ->latest('affiliate_applied_at')
                ->get(['id', 'name', 'email', 'whatsapp_number', 'affiliate_status', 'affiliate_code', 'affiliate_applied_at', 'affiliate_approved_at'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'whatsapp' => $user->whatsapp_number,
                    'status' => $user->affiliate_status,
                    'statusLabel' => match ($user->affiliate_status) {
                        'pending' => 'Menunggu persetujuan',
                        'approved' => 'Affiliate aktif',
                        'rejected' => 'Ditolak',
                        default => 'Belum daftar',
                    },
                    'code' => $user->affiliate_code,
                    'appliedAtLabel' => $user->affiliate_applied_at?->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i'),
                    'approvedAtLabel' => $user->affiliate_approved_at?->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i'),
                ])
                ->values()
                ->all(),
            'withdrawals' => AffiliateWithdrawal::query()
                ->with('user:id,name,email')
                ->latest('id')
                ->get()
                ->map(fn (AffiliateWithdrawal $withdrawal) => [
                    'id' => $withdrawal->id,
                    'publicId' => $withdrawal->public_id,
                    'userName' => $withdrawal->user?->name,
                    'userEmail' => $withdrawal->user?->email,
                    'whatsapp' => $withdrawal->whatsapp_number,
                    'amount' => (int) $withdrawal->amount,
                    'status' => $withdrawal->status,
                    'statusLabel' => match ($withdrawal->status) {
                        AffiliateWithdrawal::STATUS_PENDING => 'Menunggu diproses',
                        AffiliateWithdrawal::STATUS_PROCESSING => 'Sedang diproses',
                        AffiliateWithdrawal::STATUS_PAID => 'Sudah dibayar',
                        AffiliateWithdrawal::STATUS_REJECTED => 'Ditolak',
                        default => $withdrawal->status,
                    },
                    'requestedAtLabel' => ($withdrawal->requested_at ?? $withdrawal->created_at)?->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i'),
                    'notes' => $withdrawal->notes,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function approve(User $user, AffiliateService $affiliate)
    {
        $affiliate->approve($user);

        return back()->with('status', 'Affiliate berhasil diaktifkan.');
    }

    public function reject(User $user, AffiliateService $affiliate)
    {
        $affiliate->reject($user);

        return back()->with('status', 'Pengajuan affiliate ditolak.');
    }

    public function processWithdrawal(AffiliateWithdrawal $withdrawal, AffiliateService $affiliate)
    {
        $affiliate->markWithdrawalProcessing($withdrawal);

        return back()->with('status', 'Withdrawal affiliate masuk tahap proses.');
    }

    public function payWithdrawal(AffiliateWithdrawal $withdrawal, AffiliateService $affiliate)
    {
        $affiliate->markWithdrawalPaid($withdrawal);

        return back()->with('status', 'Withdrawal affiliate ditandai sudah dibayar.');
    }

    public function rejectWithdrawal(AffiliateWithdrawal $withdrawal, AffiliateService $affiliate)
    {
        $affiliate->rejectWithdrawal($withdrawal);

        return back()->with('status', 'Withdrawal affiliate ditolak.');
    }
}
