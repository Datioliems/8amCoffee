<?php

/**
 * Danh mục QUYỀN + quyền mặc định theo VAI TRÒ.
 *
 *  - 'catalog': mọi quyền trong hệ thống (key => nhãn hiển thị), nhóm theo chức năng.
 *  - 'roles'  : quyền mặc định mỗi vai trò ('*' = tất cả). Tài khoản có thể được
 *               override riêng (cột TAI_KHOAN.quyen) — nếu NULL thì dùng mặc định này.
 *
 * superadmin luôn có TẤT CẢ quyền (bỏ qua kiểm tra).
 */
return [
    'catalog' => [
        // Vận hành
        'dashboard.view'   => 'Xem tổng quan',
        'orders.manage'    => 'Quản lý đơn hàng',
        'payment.process'  => 'Thu ngân / thanh toán',
        'floorplan.view'   => 'Xem sơ đồ bàn 3D',
        'ban.manage'       => 'Quản lý bàn & QR',
        // Kho & thực đơn
        'inventory.manage'   => 'Xem tồn kho & nguyên liệu',
        'import.create'      => 'Lập phiếu nhập kho',
        'import.approve'     => 'Duyệt / hủy phiếu nhập kho',
        'stockcheck.create'  => 'Lập phiếu kiểm kê',
        'stockcheck.approve' => 'Xác nhận / hủy phiếu kiểm kê',
        'menu.manage'        => 'Quản lý thực đơn',
        // Khách hàng & loyalty
        'customer.view'    => 'Xem khách hàng',
        'loyalty.manage'   => 'Quản lý thẻ thành viên & điểm',
        // Phân tích & báo cáo
        'analytics.view'   => 'Xem phân tích AI',
        // Quản trị
        'staff.manage'     => 'Quản lý nhân viên & phân quyền',
        'scanlog.view'     => 'Xem log quét QR',
        'anomaly.view'     => 'Xem cảnh báo QR bất thường',
        'auditlog.view'    => 'Xem nhật ký đăng nhập',
        'emaillog.view'    => 'Xem nhật ký email',
        'branch.manage'    => 'Quản lý chi nhánh (chủ chuỗi)',
    ],

    'roles' => [
        'superadmin' => ['*'],

        // Quản lý chi nhánh: gần như toàn quyền vận hành + quản trị nhân viên chi nhánh.
        'admin' => [
            'dashboard.view', 'orders.manage', 'payment.process', 'floorplan.view', 'ban.manage',
            'inventory.manage', 'import.create', 'import.approve',
            'stockcheck.create', 'stockcheck.approve',
            'menu.manage', 'customer.view', 'loyalty.manage', 'analytics.view',
            'staff.manage', 'scanlog.view', 'anomaly.view',
        ],

        // Nhân viên: xem kho được, nhưng KHÔNG lập / duyệt phiếu.
        // Quản lý chi nhánh có thể cấp thêm import.create / stockcheck.create / *.approve qua trang phân quyền.
        'nhan_vien' => [
            'dashboard.view', 'orders.manage', 'payment.process', 'floorplan.view', 'ban.manage',
            'inventory.manage',
        ],
    ],
];
