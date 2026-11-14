<?php

return [
    'required' => 'Trường :attribute là bắt buộc.',
    'string' => 'Trường :attribute phải là chuỗi ký tự.',
    'max' => [
        'string' => 'Trường :attribute không được dài hơn :max ký tự.',
        'array' => 'Trường :attribute không được có quá :max phần tử.',
        'numeric' => 'Trường :attribute không được lớn hơn :max.',
    ],
    'array' => 'Trường :attribute phải là một mảng.',
    'size' => [
        'array' => 'Trường :attribute phải có đúng :size phần tử.',
    ],
    'integer' => 'Trường :attribute phải là số nguyên.',
    'min' => [
        'numeric' => 'Trường :attribute phải có giá trị ít nhất là :min.',
        'string' => 'Trường :attribute phải có ít nhất :min ký tự.',
    ],
    'between' => [
        'numeric' => 'Trường :attribute phải nằm trong khoảng từ :min đến :max.',
    ],
    'not_in' => 'Giá trị của :attribute không hợp lệ.',
    'boolean' => 'Trường :attribute phải là true hoặc false.',
    'in' => 'Giá trị của :attribute không hợp lệ.',
    'attributes' => [
        'subject' => 'tiêu đề ticket',
        'to_status' => 'trạng thái mới',
        'version' => 'phiên bản',
        'reason' => 'lý do',
        'items.*.order_ref' => 'mã đơn tham chiếu',
        'items.*.quantity' => 'số lượng',
        'items.*.ttl_minutes' => 'số phút giữ',
        'delta' => 'delta tồn kho',
        'reference' => 'mã tham chiếu',
    ],
];
