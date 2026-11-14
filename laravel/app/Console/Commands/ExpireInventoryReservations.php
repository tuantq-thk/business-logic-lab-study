<?php

namespace App\Console\Commands;

use App\Features\InventoryCapacity\Actions\ExpireReservation;
use App\Features\InventoryCapacity\Models\InventoryReservation;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ExpireInventoryReservations extends Command
{
    protected $signature = 'inventory:expire-reservations {--limit=100}';

    public function getDescription(): string
    {
        return __('inventory_capacity.commands.expire_description');
    }

    public function handle(ExpireReservation $expire): int
    {
        $limit = max(1, min((int) $this->option('limit'), 1000));
        $now = CarbonImmutable::now();
        $expired = 0;

        InventoryReservation::query()
            ->where('status', 'active')
            ->where('expires_at', '<=', $now)
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (InventoryReservation $reservation) use ($expire, $now, &$expired): void {
                $result = $expire->handle($reservation, $now);

                if ($result?->status->value === 'expired') {
                    $expired++;
                }
            });

        $this->info(__('inventory_capacity.commands.expired', ['count' => $expired]));

        return self::SUCCESS;
    }
}
