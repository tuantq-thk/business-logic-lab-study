<?php

return [
    'page' => [
        'title' => 'BL-04 · Lịch làm việc và khung giờ',
        'back' => 'Quay lại thư viện lab',
        'eyebrow' => 'BL-04 / lịch nghiệp vụ',
        'heading' => 'Chuyển lịch địa phương thành các khung giờ có thể chọn',
        'description' => 'Lịch tuần và ngoại lệ theo ngày được tính theo múi giờ của phòng. Xử lý đặt trùng thuộc BL-05.',
        'loading' => 'Đang tính…',
    ],
    'form' => [
        'title' => 'Thiết lập khung giờ',
        'description' => 'Chọn phòng và khoảng thời gian địa phương để tính các khung giờ có thể chọn.',
        'room' => 'Phòng',
        'date' => 'Ngày địa phương',
        'duration' => 'Thời lượng (phút)',
        'step' => 'Bước nhảy (phút)',
        'submit' => 'Tạo khung giờ',
        'timezone' => 'Múi giờ phòng',
        'no_rooms' => 'Chưa có phòng đang hoạt động nào được cấu hình.',
        'date_hint' => 'Sử dụng ngày theo lịch địa phương của phòng.',
        'duration_hint' => 'Khoảng cho phép: từ 15 đến 480 phút.',
        'step_hint' => 'Tạo thời điểm bắt đầu mới sau mỗi 5–120 phút.',
    ],
    'slots' => [
        'eyebrow' => 'Kết quả khung giờ khả dụng',
        'title' => 'Các khung giờ có thể chọn',
        'empty' => 'Không có khung giờ phù hợp với ngày, cửa sổ, thời lượng và thời điểm hiện tại.',
        'start' => 'Bắt đầu',
        'end' => 'Kết thúc',
        'count' => ':count khung giờ',
        'local' => 'Giờ địa phương',
    ],
    'rules' => [
        'title' => 'Cách hoạt động',
        'weekly' => 'Lịch tuần cung cấp các cửa sổ mặc định.',
        'override' => 'Ngoại lệ theo ngày thay thế toàn bộ lịch tuần của phòng trong ngày đó.',
        'containment' => 'Khung giờ phải nằm trọn trong một cửa sổ đang mở.',
        'past' => 'Các khung giờ đã bắt đầu được loại bỏ dựa trên đồng hồ được truyền vào.',
    ],
    'errors' => [
        'form_heading' => 'Vui lòng kiểm tra các trường được đánh dấu.',
        'invalid_timezone' => 'Múi giờ của phòng không hợp lệ.',
        'invalid_slot_parameters' => 'Thời lượng và bước nhảy phải lớn hơn 0.',
        'invalid_window' => 'Lịch đang chứa một cửa sổ mở không hợp lệ.',
        'invalid_local_time' => 'Giờ địa phương không hợp lệ với múi giờ này.',
        'generation_failed' => 'Không thể chuyển lịch thành các khung giờ có thể chọn. Vui lòng kiểm tra lịch của phòng.',
    ],
    'validation' => [
        'room_unavailable' => 'Phòng được chọn không khả dụng.',
        'date_format' => 'Ngày phải có định dạng YYYY-MM-DD.',
        'duration_integer' => 'Thời lượng phải là số phút nguyên.',
        'duration_range' => 'Thời lượng phải nằm trong khoảng từ 15 đến 480 phút.',
        'step_integer' => 'Bước nhảy phải là số phút nguyên.',
        'step_range' => 'Bước nhảy phải nằm trong khoảng từ 5 đến 120 phút.',
    ],
    'demo' => [
        'rooms' => [
            'boardroom' => 'Phòng họp 01',
            'default' => 'Phòng demo',
        ],
    ],
];
