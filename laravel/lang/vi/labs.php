<?php

return [
    'state_transition' => [
        'label' => 'State transition & invariant',
        'description' => 'Học cách bảo vệ trạng thái hợp lệ khi một workflow thay đổi.',
        'group' => 'Workflow',
        'question' => 'Rule nào ngăn một ticket chuyển sang trạng thái không hợp lệ?',
    ],
    'inventory_capacity' => [
        'label' => 'Inventory, reservation & capacity',
        'description' => 'Giữ counter tồn kho và lifecycle reservation đúng khi request chạy đồng thời.',
        'group' => 'Capacity',
        'question' => 'Làm sao ngăn bán vượt tồn khi nhiều request cùng chạy?',
    ],
    'pricing_discounts' => [
        'label' => 'Giá, giảm giá và thứ tự áp dụng',
        'description' => 'Học cách tính tiền xác định, làm tròn và chọn các rule giảm giá.',
        'group' => 'Giá và khuyến mãi',
        'question' => 'Làm sao để quote luôn đúng khi có nhiều rule giảm giá cùng đủ điều kiện?',
    ],
    'business_calendar_slots' => [
        'label' => 'Lịch làm việc và slot',
        'description' => 'Chuyển lịch tuần và ngoại lệ ngày thành các slot có thể chọn đúng timezone.',
        'group' => 'Lịch',
        'question' => 'Phòng còn slot nào trên một ngày địa phương cụ thể?',
    ],
];
