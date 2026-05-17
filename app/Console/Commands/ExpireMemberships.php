<?php

namespace App\Console\Commands;

use App\Services\MembershipService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('memberships:expire')]
#[Description('Expire overdue memberships that have passed their end date')]
class ExpireMemberships extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(MembershipService $membershipService): int
    {
        $count = $membershipService->expireOverdueMemberships();

        $this->info("Expired {$count} overdue membership(s).");

        return self::SUCCESS;
    }
}
