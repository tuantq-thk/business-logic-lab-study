<?php

return [
    'page' => [
        'title' => 'BL-03 · Tồn kho và sức chứa',
        'back' => 'Quay lại thư viện lab', 'loading' => 'Đang xử lý…',
        'eyebrow' => 'BL-03 / tồn kho và sức chứa',
        'heading' => 'Giữ đơn vị cuối mà không bán vượt tồn',
        'description' => 'Demo giữ bộ đếm tồn kho và vòng đời giữ hàng nhất quán khi nhiều yêu cầu cùng tranh một SKU.',
    ],
    'flow' => [
        'request' => ['label' => '01 · yêu cầu', 'title' => 'Yêu cầu giữ hàng', 'description' => 'Phía giao diện chỉ gửi mặt hàng, mã tham chiếu, số lượng và thời gian giữ. Giá và tồn kho do hệ thống kiểm soát.'],
        'lock' => ['label' => '02 · giao dịch', 'title' => 'Khóa một SKU', 'description' => 'Bản ghi tồn kho là điểm tuần tự hóa cho giữ hàng, nhận hàng, trả hàng và điều chỉnh.'],
        'result' => ['label' => '03 · kết quả', 'title' => 'Hoàn tất một vòng đời', 'description' => 'Bộ đếm, trạng thái giữ hàng và lịch sử biến động được đổi cùng nhau hoặc không đổi gì.'],
    ],
    'inventory' => [
        'eyebrow' => 'Danh mục tồn kho', 'title' => 'Sức chứa khả dụng', 'reserve' => 'Giữ hàng', 'sku' => 'SKU', 'on_hand' => 'Tồn vật lý', 'reserved' => 'Đang giữ', 'available' => 'Còn bán', 'quantity' => 'Số lượng', 'order_ref' => 'Mã đơn tham chiếu', 'ttl' => 'Số phút giữ', 'submit' => 'Tạo lượt giữ hàng', 'empty' => 'Chưa có mặt hàng tồn kho nào được tạo mẫu.', 'timezone' => 'UTC', 'order_ref_placeholder' => 'ORDER-001',
    ],
    'reservations' => [
        'eyebrow' => 'Theo dõi vòng đời', 'title' => 'Lượt giữ hàng gần đây', 'empty' => 'Các lượt giữ hàng của bạn sẽ xuất hiện ở đây.', 'reference' => 'Mã tham chiếu', 'status' => 'Trạng thái', 'item' => 'Mặt hàng', 'quantity' => 'SL', 'expires' => 'Hết hạn', 'consume' => 'Nhận hàng', 'release' => 'Trả hàng', 'terminal' => 'Đã hoàn tất',
    ],
    'admin' => [
        'eyebrow' => 'Thao tác quản trị', 'title' => 'Điều chỉnh tồn vật lý', 'description' => 'Mỗi lần điều chỉnh cần có lý do và tạo một bản ghi biến động bất biến.', 'delta' => 'Mức thay đổi', 'delta_placeholder' => '+10 / -2', 'reason' => 'Lý do', 'reason_placeholder' => 'Vì sao số đếm thực tế thay đổi?', 'reference' => 'Mã tham chiếu (tuỳ chọn)', 'reference_placeholder' => 'COUNT-001', 'submit' => 'Áp dụng điều chỉnh',
    ],
    'status' => ['active' => 'Đang giữ', 'consumed' => 'Đã nhận hàng', 'released' => 'Đã trả hàng', 'expired' => 'Đã hết hạn'],
    'flash' => ['reserved' => 'Đã tạo hoặc phát lại lượt giữ hàng an toàn.', 'consumed' => 'Đã nhận hàng và trừ tồn.', 'released' => 'Đã trả hàng.', 'adjusted' => 'Đã điều chỉnh tồn và ghi nhận biến động.'],
    'errors' => ['insufficient' => 'Không đủ tồn khả dụng cho số lượng này.', 'key_conflict' => 'Mã tham chiếu đã tồn tại với số lượng khác.', 'expired' => 'Lượt giữ hàng đã hết hạn trước khi nhận hàng.', 'terminal' => 'Lượt giữ hàng đã hoàn tất và không thể thay đổi lần nữa.', 'adjustment' => 'Điều chỉnh này sẽ làm tồn khả dụng bị âm.', 'invalid_adjustment' => 'Điều chỉnh phải có mức thay đổi khác 0 và có lý do.', 'invalid_input' => 'Dữ liệu giữ hàng không hợp lệ.', 'invariant' => 'Dữ liệu tồn kho cần được đối soát trước khi tiếp tục.'],
    'date_format' => 'd/m/Y H:i', 'timezone' => 'UTC',
    'commands' => ['expire_description' => 'Giải phóng các lượt giữ hàng đã hết hạn', 'reconcile_description' => 'Báo cáo các bộ đếm tồn kho lệch với lượt giữ hàng đang hoạt động', 'expired' => 'Đã hết hạn :count lượt giữ hàng.', 'healthy' => 'Bộ đếm tồn kho khớp với các lượt giữ hàng đang hoạt động.', 'table_id' => 'ID', 'table_sku' => 'SKU', 'table_counter' => 'Bộ đếm', 'table_active_sum' => 'Tổng đang hoạt động'],
    'exceptions' => ['append_only' => 'Lịch sử biến động tồn kho chỉ được ghi thêm, không thể sửa hoặc xoá.'],
    'demo' => [
        'products' => ['notebook' => 'Sổ lab màu xanh', 'pen' => 'Bút đen', 'mug' => 'Cốc nghiên cứu', 'default' => 'Mặt hàng tồn kho'],
    ],
];
