<?php

return [
    'required' => 'Trường :attribute là bắt buộc.',
    'string' => 'Trường :attribute phải là chuỗi ký tự.',
    'max' => [
        'string' => 'Trường :attribute không được dài hơn :max ký tự.',
    ],
    'integer' => 'Trường :attribute phải là số nguyên.',
    'min' => [
        'numeric' => 'Trường :attribute phải có giá trị ít nhất là :min.',
    ],
    'boolean' => 'Trường :attribute phải là true hoặc false.',
    'in' => 'Giá trị của :attribute không hợp lệ.',
    'attributes' => [
        'subject' => 'tiêu đề ticket',
        'to_status' => 'trạng thái mới',
        'version' => 'phiên bản',
        'reason' => 'lý do',
    ],
];
