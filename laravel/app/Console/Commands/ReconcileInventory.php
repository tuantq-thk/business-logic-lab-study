<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileInventory extends Command
{
    protected $signature = 'inventory:reconcile';

    public function getDescription(): string
    {
        return __('inventory_capacity.commands.reconcile_description');
    }

    public function handle(): int
    {
        $drifts = DB::table('inventory_items')
            ->leftJoin('inventory_reservations', function ($join): void {
                $join->on('inventory_reservations.inventory_item_id', '=', 'inventory_items.id')
                    ->where('inventory_reservations.status', '=', 'active');
            })
            ->select([
                'inventory_items.id',
                'inventory_items.sku',
                'inventory_items.reserved_quantity',
                DB::raw('COALESCE(SUM(inventory_reservations.quantity), 0) as active_sum'),
            ])
            ->groupBy('inventory_items.id', 'inventory_items.sku', 'inventory_items.reserved_quantity')
            ->havingRaw('inventory_items.reserved_quantity <> active_sum')
            ->get();

        if ($drifts->isEmpty()) {
            $this->info(__('inventory_capacity.commands.healthy'));

            return self::SUCCESS;
        }

        $this->table([
            __('inventory_capacity.commands.table_id'),
            __('inventory_capacity.commands.table_sku'),
            __('inventory_capacity.commands.table_counter'),
            __('inventory_capacity.commands.table_active_sum'),
        ], $drifts->map(fn (object $row): array => [
            $row->id,
            $row->sku,
            $row->reserved_quantity,
            $row->active_sum,
        ])->all());

        return self::FAILURE;
    }
}
