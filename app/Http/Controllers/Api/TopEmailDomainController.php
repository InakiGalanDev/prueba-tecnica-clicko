<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TopEmailDomainController extends Controller
{
    /**
     * Return the three most used email domains among users, in descending order.
     */
    public function __invoke(): JsonResponse
    {
        $topDomains = User::query()
            ->selectRaw("lower(substr(email, instr(email, '@') + 1)) as domain, count(*) as total")
            ->groupBy('domain')
            ->orderByDesc('total')
            ->orderBy('domain')
            ->limit(3)
            ->pluck('total', 'domain');

        return response()->json($topDomains);
    }
}
