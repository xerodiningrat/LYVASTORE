<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VipaymentService;
use Inertia\Inertia;
use Inertia\Response;

class ProviderAvailabilityController extends Controller
{
    public function __invoke(VipaymentService $vipayment): Response
    {
        return Inertia::render('admin/ProviderAvailability', [
            'unavailableProducts' => $vipayment->unavailablePublicProductsReport(),
        ]);
    }
}
