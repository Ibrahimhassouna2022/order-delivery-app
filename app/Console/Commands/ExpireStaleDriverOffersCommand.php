<?php

namespace App\Console\Commands;

use App\Actions\Driver\ExpireDriverOfferAction;
use App\Models\DriverAssignment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('delivery:expire-stale-offers')]
#[Description('Expire driver offers whose 30 second response window has elapsed')]
class ExpireStaleDriverOffersCommand extends Command
{
    public function handle(ExpireDriverOfferAction $expireOffer): int
    {
        $expired = 0;

        DriverAssignment::query()
            ->expired()
            ->chunkById(100, function ($assignments) use ($expireOffer, &$expired): void {
                foreach ($assignments as $assignment) {
                    $expireOffer->execute($assignment);
                    $expired++;
                }
            });

        $this->components->info("Expired {$expired} stale driver offer(s).");

        return self::SUCCESS;
    }
}
