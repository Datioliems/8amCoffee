<?php

/**
 * Cấu hình chương trình thẻ thành viên RFID + tích điểm (loyalty).
 * Có thể chỉnh qua biến môi trường (.env) mà không sửa code.
 */
return [
    // Mốc tổng chi tiêu tích lũy (đ) để KHÁCH ĐỦ ĐIỀU KIỆN được phát thẻ.
    'issue_threshold' => (int) env('LOYALTY_ISSUE_THRESHOLD', 500_000),

    // Tỉ lệ TÍCH điểm: cứ mỗi `earn_per_amount` đồng chi tiêu được 1 điểm.
    // Mặc định 1.000đ = 1 điểm.
    'earn_per_amount' => (int) env('LOYALTY_EARN_PER_AMOUNT', 1_000),

    // Giá trị ĐỔI điểm: 1 điểm = `point_value` đồng giảm giá.
    // Mặc định 1 điểm = 50đ  →  giá trị hoàn lại thực tế ≈ 5% chi tiêu.
    'point_value' => (int) env('LOYALTY_POINT_VALUE', 50),

    // Số điểm tối thiểu cho mỗi lần đổi.
    'min_redeem' => (int) env('LOYALTY_MIN_REDEEM', 100),

    // Mức giảm tối đa cho mỗi hóa đơn khi đổi điểm (% giá trị hóa đơn).
    'max_redeem_pct' => (int) env('LOYALTY_MAX_REDEEM_PCT', 50),

    // Xếp hạng thẻ theo TỔNG điểm đã tích lũy (tong_diem_tich_luy).
    // key = mã hạng, value = ngưỡng điểm tối thiểu để đạt hạng.
    'tiers' => [
        'thuong'    => 0,
        'bac'       => 1_000,
        'vang'      => 5_000,
        'kim_cuong' => 20_000,
    ],
];
