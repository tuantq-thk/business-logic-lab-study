<?php

return [
    'page' => [
        'title' => 'BL-03 · Inventory and capacity',
        'back' => 'Back to lab library', 'loading' => 'Working…',
        'eyebrow' => 'BL-03 / inventory capacity',
        'heading' => 'Reserve the last units without overselling',
        'description' => 'This demo keeps an inventory counter and reservation lifecycle consistent while requests compete for the same SKU.',
    ],
    'flow' => [
        'request' => ['label' => '01 · request', 'title' => 'Ask for a hold', 'description' => 'The client sends only an item, reference, quantity and TTL. Price and stock remain server-owned.'],
        'lock' => ['label' => '02 · transaction', 'title' => 'Lock one SKU row', 'description' => 'The inventory row is the serialization point for reserve, consume, release and adjustment.'],
        'result' => ['label' => '03 · outcome', 'title' => 'Commit one lifecycle', 'description' => 'Counter, reservation state and movement history change atomically or not at all.'],
    ],
    'inventory' => [
        'eyebrow' => 'Inventory catalog', 'title' => 'Available capacity', 'reserve' => 'Reserve', 'sku' => 'SKU', 'on_hand' => 'On hand', 'reserved' => 'Reserved', 'available' => 'Available', 'quantity' => 'Quantity', 'order_ref' => 'Order reference', 'ttl' => 'Hold minutes', 'submit' => 'Create reservation', 'empty' => 'No inventory items have been seeded.', 'timezone' => 'UTC', 'order_ref_placeholder' => 'ORDER-001',
    ],
    'reservations' => [
        'eyebrow' => 'Lifecycle evidence', 'title' => 'Recent reservations', 'empty' => 'Your reservations will appear here.', 'reference' => 'Reference', 'status' => 'Status', 'item' => 'Item', 'quantity' => 'Qty', 'expires' => 'Expires', 'consume' => 'Consume', 'release' => 'Release', 'terminal' => 'Completed',
    ],
    'admin' => [
        'eyebrow' => 'Admin operation', 'title' => 'Adjust physical stock', 'description' => 'Adjustments require a reason and create an immutable movement entry.', 'delta' => 'Delta', 'delta_placeholder' => '+10 / -2', 'reason' => 'Reason', 'reason_placeholder' => 'Why did the physical count change?', 'reference' => 'Reference (optional)', 'reference_placeholder' => 'COUNT-001', 'submit' => 'Apply adjustment',
    ],
    'status' => ['active' => 'Active', 'consumed' => 'Consumed', 'released' => 'Released', 'expired' => 'Expired'],
    'flash' => ['reserved' => 'Reservation created or safely replayed.', 'consumed' => 'Reservation consumed and stock decremented.', 'released' => 'Reservation released.', 'adjusted' => 'Stock adjusted and movement recorded.'],
    'errors' => ['insufficient' => 'There is not enough available inventory for that quantity.', 'key_conflict' => 'This reference already exists with a different quantity.', 'expired' => 'The reservation expired before it could be consumed.', 'terminal' => 'The reservation has already completed and cannot change again.', 'adjustment' => 'The adjustment would make available inventory negative.', 'invalid_adjustment' => 'The adjustment must have a non-zero delta and a reason.', 'invalid_input' => 'The reservation input is invalid.', 'invariant' => 'Inventory data needs reconciliation before this operation can continue.'],
    'date_format' => 'M d, Y H:i', 'timezone' => 'UTC',
    'commands' => ['expire_description' => 'Release expired inventory reservations safely', 'reconcile_description' => 'Report inventory counters that differ from active reservations', 'expired' => ':count reservation(s) expired.', 'healthy' => 'Inventory counters match active reservations.', 'table_id' => 'ID', 'table_sku' => 'SKU', 'table_counter' => 'Counter', 'table_active_sum' => 'Active sum'],
    'exceptions' => ['append_only' => 'Inventory movements are append-only.'],
    'demo' => [
        'products' => ['notebook' => 'Blue lab notebook', 'pen' => 'Black pen', 'mug' => 'Research mug', 'default' => 'Inventory item'],
    ],
];
