<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SecurityLogService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Inertia\Inertia;
use Inertia\Response;

class SecurityMonitorController extends Controller
{
    public function index(Request $request, SecurityLogService $securityLogs): Response
    {
        $filters = $request->only(['level', 'event', 'ip', 'search']);

        return Inertia::render('admin/Security', [
            'security' => $securityLogs->dashboardPayload(120, $filters),
        ]);
    }

    public function download(SecurityLogService $securityLogs): BinaryFileResponse
    {
        $file = $securityLogs->latestLogFile();

        abort_unless($file !== null, 404, 'Security log file not found.');

        return response()->download($file, basename($file), [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
