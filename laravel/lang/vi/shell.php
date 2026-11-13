<?php

return [
    'brand' => 'Business Logic Lab',
    'logout' => 'Đăng xuất',
    'admin_catalog' => 'Admin catalog',
    'source' => 'nguồn: :source',
    'lab_meta' => 'Lab · :key',
    'home' => [
        'title' => 'Learning shell · :brand',
        'eyebrow' => 'Your learning shell',
        'heading' => 'Chào :name, lab đang chờ bạn.',
        'description' => 'Khu vực tài khoản đã hoạt động. Trạng thái catalog được resolve từ backend, không do frontend tự đoán.',
        'enabled' => 'Catalog đang bật',
        'enabled_title' => 'Catalog đã được mở',
        'enabled_description' => 'Chỉ những lab đã implemented và enabled mới xuất hiện trong menu.',
        'empty' => 'Chưa có lab khả dụng.',
        'disabled' => 'Catalog đang tắt',
        'disabled_title' => 'Catalog chưa được mở',
        'disabled_description' => 'Admin có thể bật catalog khi registry và evidence của lab đã sẵn sàng.',
        'disabled_empty' => 'Các lab không được hiển thị và direct URL cũng bị backend chặn.',
    ],
    'lab' => [
        'title' => ':label · :brand',
        'logout' => 'Đăng xuất',
        'back' => '← Quay lại catalog',
        'question' => 'Câu hỏi trung tâm',
        'description' => 'Lab này đã được mở trong catalog. Phần scenario và bằng chứng sẽ được xây tiếp theo đúng contract của BL-01.',
    ],
];
