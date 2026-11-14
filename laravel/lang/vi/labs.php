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
    'booking_overlap' => [
        'label' => 'Đặt lịch không trùng',
        'description' => 'Đặt phòng an toàn khi nhiều request cùng tranh một khoảng thời gian.',
        'group' => 'Đặt lịch / concurrency',
        'question' => 'Các request đồng thời có thể tạo booking bị trùng không?',
    ],
    'contextual_validation' => [
        'label' => 'Validation theo ngữ cảnh',
        'description' => 'Tách input sai định dạng khỏi input đúng nhưng không đạt policy nghiệp vụ hiện tại.',
        'group' => 'Validation và điều kiện tham gia',
        'question' => 'Khi nào input hợp lệ vẫn bị từ chối bởi policy của sự kiện?',
    ],
    'cancellation_rescheduling' => ['label' => 'Hủy và đổi lịch', 'description' => 'Áp dụng cutoff, capacity, history và rule bù trừ khi đổi khóa học.', 'group' => 'Workflow và bù trừ', 'question' => 'Làm sao đổi đăng ký mà không giải phóng seat hai lần?'],
];
