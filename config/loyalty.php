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

    // Hạng hội viên — xếp theo TỔNG điểm tích lũy (tong_diem_tich_luy).
    //  nguong       : điểm tối thiểu để đạt hạng
    //  he_so        : HỆ SỐ TÍCH ĐIỂM của hạng (vd 1.5 = tích nhanh gấp 1.5 lần)
    //  giam_loai    : 'phan_tram' (giảm %) hoặc 'tien' (giảm số tiền cố định)
    //  giam_gia_tri : giá trị giảm (số % nếu phan_tram, số đồng nếu tien) — ƯU ĐÃI khi quẹt thẻ ở POS
    //  nhan         : nhãn hiển thị
    'tiers' => [
        'thuong'    => ['nhan' => 'Thường',    'nguong' => 0,       'he_so' => 1.0,  'giam_loai' => 'phan_tram', 'giam_gia_tri' => 0],
        'bac'       => ['nhan' => 'Bạc',       'nguong' => 1_000,   'he_so' => 1.25, 'giam_loai' => 'phan_tram', 'giam_gia_tri' => 3],
        'vang'      => ['nhan' => 'Vàng',      'nguong' => 5_000,   'he_so' => 1.5,  'giam_loai' => 'phan_tram', 'giam_gia_tri' => 5],
        'kim_cuong' => ['nhan' => 'Kim cương', 'nguong' => 20_000,  'he_so' => 2.0,  'giam_loai' => 'tien',      'giam_gia_tri' => 20_000],
    ],
];
