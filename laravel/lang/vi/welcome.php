<?php

return [
    'brand' => 'Business Logic Lab',
    'nav' => [
        'greeting' => 'Xin chào, :name',
        'dashboard' => 'Vào learning shell',
        'continue' => 'Tiếp tục học',
        'logout' => 'Đăng xuất',
        'login' => 'Đăng nhập',
        'register' => 'Tạo tài khoản',
    ],
    'hero' => [
        'eyebrow' => 'technical notebook / learning shell',
        'title' => 'Đừng chỉ học code. Hãy học cách quyết định.',
        'description' => 'Business Logic Lab giúp bạn nhìn thấy những rule làm nên một hệ thống đáng tin cậy — từ câu hỏi nghiệp vụ đến bằng chứng trong code và test.',
        'principles' => 'Xem nguyên tắc',
    ],
    'lab_index' => [
        'label' => 'lab index',
        'status' => 'đang xây dựng',
        'question_label' => '01 / câu hỏi',
        'scenario_label' => '02 / scenario',
        'evidence_label' => '03 / evidence',
        'question' => 'Rule nào thực sự bảo vệ invariant?',
        'scenario' => 'Điều gì xảy ra ở boundary và conflict?',
        'evidence' => 'Test, migration và source link — không có evidence giả.',
    ],
    'principles' => [
        'question' => ['title' => 'Bắt đầu từ câu hỏi', 'text' => 'Bắt đầu từ vấn đề, không bắt đầu từ menu domain.'],
        'state' => ['title' => 'Trạng thái rõ ràng', 'text' => 'Planned, implemented và evidence-backed không bị đánh đồng.'],
        'server' => ['title' => 'Server là nguồn sự thật', 'text' => 'UI giải thích logic; backend vẫn kiểm tra mọi mutation.'],
    ],
];
