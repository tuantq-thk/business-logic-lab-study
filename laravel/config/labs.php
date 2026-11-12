<?php

return [
    'registry' => [
        'state-transition' => [
            'key' => 'state-transition',
            'label' => 'State transition & invariant',
            'description' => 'Học cách bảo vệ trạng thái hợp lệ khi một workflow thay đổi.',
            'group' => 'Workflow',
            'question' => 'Rule nào ngăn một ticket chuyển sang trạng thái không hợp lệ?',
            'implemented' => true,
            'default_enabled' => true,
            'toggleable' => true,
            'route_names' => ['labs.show'],
        ],
        'inventory-capacity' => [
            'key' => 'inventory-capacity',
            'label' => 'Inventory, reservation & capacity',
            'description' => 'Lab kế tiếp về tồn kho, giữ hàng và xử lý đồng thời.',
            'group' => 'Capacity',
            'question' => 'Làm sao ngăn bán vượt tồn khi nhiều request cùng chạy?',
            'implemented' => false,
            'default_enabled' => false,
            'toggleable' => true,
            'route_names' => ['labs.show'],
        ],
    ],
];
