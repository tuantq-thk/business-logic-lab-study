<?php

return [
    'page' => [
        'title' => 'BL-01 · Vòng đời ticket',
        'eyebrow' => 'BL-01 / state transition',
        'heading' => 'Theo dõi tiến độ phiếu hỗ trợ',
        'description' => 'Mỗi ticket đi qua một flow có kiểm soát. Backend kiểm tra actor, transition hợp lệ, version hiện tại và ghi lịch sử trong cùng một transaction.',
        'back' => '← Quay lại catalog',
    ],
    'flow' => [
        'aria_label' => 'Luồng xử lý ticket',
        'request' => [
            'label' => '01 · Request',
            'title' => 'Actor gửi yêu cầu',
            'description' => 'Admin hoặc chủ ticket chọn trạng thái mới và gửi version đang nhìn thấy.',
        ],
        'decision' => [
            'label' => '02 · Decision',
            'title' => 'Backend kiểm tra rule',
            'description' => 'Policy, transition table và stale version được kiểm tra trên state mới nhất.',
        ],
        'commit' => [
            'label' => '03 · Commit',
            'title' => 'State và history cùng commit',
            'description' => 'Ticket update và history insert nằm trong một transaction; lỗi sẽ rollback cả hai.',
        ],
    ],
    'create' => [
        'eyebrow' => 'Khởi tạo scenario',
        'title' => 'Tạo ticket mới',
        'description' => 'Ticket mới luôn bắt đầu ở Mới mở.',
        'subject_label' => 'Tiêu đề ticket',
        'subject_placeholder' => 'Ví dụ: Không nhận được email xác nhận',
        'submit' => 'Tạo ticket',
    ],
    'tickets' => [
        'eyebrow' => 'Tiến độ và lịch sử',
        'title' => 'Các ticket',
        'count' => '{0} Chưa có ticket|{1} :count ticket|[2,*] :count ticket',
        'empty' => 'Chưa có ticket. Tạo ticket đầu tiên để bắt đầu demo.',
        'meta' => 'ticket #:id · phiên bản :version',
        'created_by' => 'Tạo bởi :name',
        'assigned_to' => ' · đang phụ trách: :name',
    ],
    'progress' => [
        'label' => 'Tiến độ',
        'step' => 'Bước :current/:total',
        'aria_label' => 'Tiến độ ticket',
    ],
    'history' => [
        'title' => 'Nhật ký transition',
        'created' => 'Khởi tạo',
    ],
    'action' => [
        'eyebrow' => 'Thao tác được phép',
        'status_label' => 'Chuyển trạng thái',
        'reason_label' => 'Lý do',
        'reason_required' => '(bắt buộc khi mở lại)',
        'reason_placeholder' => 'Vì sao cần chuyển trạng thái?',
        'helper' => 'Server sẽ kiểm tra lại actor, transition hợp lệ và version trước khi ghi.',
        'submit' => 'Lưu thay đổi',
    ],
    'status' => [
        'open' => 'Mới mở',
        'in_progress' => 'Đang xử lý',
        'resolved' => 'Đã xử lý',
        'closed' => 'Đã đóng',
    ],
    'flash' => [
        'ticket_created' => 'Ticket đã được tạo ở trạng thái Mới mở.',
        'status_changed' => 'Trạng thái ticket đã được cập nhật.',
    ],
    'errors' => [
        'invalid_transition' => 'Không thể chuyển ticket từ [:from] sang [:to].',
        'stale_ticket' => 'Ticket đã được thay đổi trước khi cập nhật. Vui lòng tải lại trang và thử lại.',
        'reason_required' => 'Vui lòng nhập lý do khi mở lại ticket đã đóng.',
    ],
];
