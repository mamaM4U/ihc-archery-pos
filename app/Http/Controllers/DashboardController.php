<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $coachesCount = User::where('role', 'coach')->count();
        $membersCount = User::where('role', 'member')->count();
        $guardiansCount = User::where('role', 'guardian')->count();

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'coaches_count' => $coachesCount,
                'members_count' => $membersCount,
                'guardians_count' => $guardiansCount,
            ],
        ]);
    }
}
