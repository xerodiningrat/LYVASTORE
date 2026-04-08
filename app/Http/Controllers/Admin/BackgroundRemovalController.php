<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackgroundRemovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BackgroundRemovalController extends Controller
{
    public function index(BackgroundRemovalService $backgroundRemoval): Response
    {
        return Inertia::render('admin/BackgroundRemover', [
            'status' => session('status'),
            'error' => session('error'),
            'tool' => $backgroundRemoval->diagnostics(),
            'result' => session('background_remover_result'),
        ]);
    }

    public function store(Request $request, BackgroundRemovalService $backgroundRemoval): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        try {
            $result = $backgroundRemoval->removeBackground($validated['image'], $request->user()?->id);
            $request->session()->forget('error');

            return back()
                ->with('status', 'Background gambar berhasil dihapus.')
                ->with('background_remover_result', $result);
        } catch (\Throwable $exception) {
            report($exception);
            $request->session()->forget('status');
            $request->session()->forget('background_remover_result');

            return back()->with('error', 'Gagal menghapus background. '.$exception->getMessage());
        }
    }
}
