<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\BanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StockCheckController;
use App\Http\Controllers\NguyenLieuController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ChiNhanhController;
use App\Http\Controllers\NhanVienController;
use App\Http\Controllers\ScanAnomalyAlertController;

Route::get('/', fn() => redirect()->route('login'));

// Cấp lại CSRF token cho client khi gặp 419 (dùng bởi giỏ hàng khách)
Route::get('/csrf-token', fn() => response()->json(['token' => csrf_token()]))->name('csrf.token');

// ── CUSTOMER ──────────────────────────────────────────────────
Route::prefix('order')->name('customer.')->group(function () {
    Route::get('/{ma_ban}',                    [QrController::class,    'scan']             )->name('scan');
    Route::get('/{ma_ban}/menu',               [MenuController::class,  'customerMenu']     )->name('menu');
    Route::post('/{ma_ban}/create',            [OrderController::class, 'createFromQr']     )->name('create');
    Route::get('/{ma_order}/cart',             [OrderController::class, 'showCart']         )->name('cart');
    Route::post('/{ma_order}/item',            [OrderController::class, 'addItem']          )->name('addItem');
    Route::delete('/{ma_order}/item/{ma_mon}', [OrderController::class, 'removeItem']       )->name('removeItem');
    Route::post('/{ma_order}/confirm',         [OrderController::class, 'confirmByCustomer'])->name('confirm');
    Route::get('/{ma_order}/status',           [OrderController::class, 'status']           )->name('status');
    Route::get('/{ma_order}/status.json',      [OrderController::class, 'statusJson']       )->name('statusJson');

    // Sơ đồ bàn 3D cho khách (public, chi nhánh suy từ bàn)
    Route::get('/{ma_ban}/tables',             [BanController::class,   'apiTablesByBan']   )->name('tables');
    Route::post('/{ma_ban}/move/{to}',         [BanController::class,   'moveByBan']        )->name('move');
});

// ── AUTH ──────────────────────────────────────────────────────
Route::get( '/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',  [AuthController::class, 'login']    )->name('login.post');
Route::post('/logout', [AuthController::class, 'logout']   )->name('logout');

// Xác thực 2 lớp (OTP qua email)
Route::get( '/otp',        [AuthController::class, 'showOtp']  )->name('otp.show');
Route::post('/otp',        [AuthController::class, 'verifyOtp'])->name('otp.verify');
Route::post('/otp/resend', [AuthController::class, 'resendOtp'])->name('otp.resend');

// Quên / đặt lại mật khẩu (public — gửi link qua email)
Route::get( '/quen-mat-khau',          [\App\Http\Controllers\PasswordResetController::class, 'showRequest'])->name('password.request');
Route::post('/quen-mat-khau',          [\App\Http\Controllers\PasswordResetController::class, 'sendLink']   )->name('password.email');
Route::get( '/dat-lai-mat-khau/{token}',[\App\Http\Controllers\PasswordResetController::class, 'showReset']  )->name('password.reset');
Route::post('/dat-lai-mat-khau',       [\App\Http\Controllers\PasswordResetController::class, 'update']     )->name('password.update');

// Kích hoạt tài khoản nhân viên (public — bấm từ link trong email)
Route::get('/kich-hoat/{token}', [\App\Http\Controllers\AccountActivationController::class, 'activate'])->name('account.activate');

// ── VNPAY CALLBACK (public — cổng/khách gọi về, không qua auth.staff) ──
Route::get('/payment/vnpay/return', [PaymentController::class, 'vnpayReturn'])->name('payment.vnpay.return');
Route::get('/payment/vnpay/ipn',    [PaymentController::class, 'vnpayIpn']   )->name('payment.vnpay.ipn');

// ── STAFF ─────────────────────────────────────────────────────
Route::middleware(['auth.staff'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('perm:dashboard.view')->name('dashboard');

    // ── PHÂN TÍCH AI (dự báo doanh thu + gợi ý món) ──────────
    Route::middleware('perm:analytics.view')->get('/phan-tich', [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');

    // ── SƠ ĐỒ BÀN 3D (Three.js) ──────────────────────────────
    Route::middleware('perm:floorplan.view')->group(function () {
        Route::get('/floorplan',                    [BanController::class, 'floorplan'] )->name('floorplan');
        Route::get('/floorplan/tables',             [BanController::class, 'apiTables'] )->name('floorplan.tables');
        Route::post('/floorplan/move/{from}/{to}',  [BanController::class, 'moveTable'] )->name('floorplan.move');
    });

    // Super-admin đổi chi nhánh đang xem
    Route::post('/chi-nhanh/doi', [ChiNhanhController::class, 'switch'])->middleware('role:superadmin')->name('chinhanh.switch');
    // Super-admin sửa thông tin chi nhánh (tên, địa chỉ, model 3D)
    Route::put('/chi-nhanh/{ma_chi_nhanh}', [ChiNhanhController::class, 'update'])->middleware('role:superadmin')->name('chinhanh.update');

    // ── QUẢN LÝ TÀI KHOẢN NHÂN VIÊN + PHÂN QUYỀN ─────────────
    Route::middleware('perm:staff.manage')->prefix('nhan-vien')->name('nhanvien.')->group(function () {
        Route::get('/',                 [NhanVienController::class, 'index'] )->name('index');
        Route::post('/',                [NhanVienController::class, 'store'] )->name('store');
        Route::get('/{ma_tai_khoan}/phan-quyen',  [NhanVienController::class, 'permissions']      )->name('permissions');
        Route::post('/{ma_tai_khoan}/phan-quyen', [NhanVienController::class, 'updatePermissions'])->name('permissions.update');
        Route::put('/{ma_tai_khoan}',   [NhanVienController::class, 'update'])->name('update');
        Route::post('/{ma_tai_khoan}/resend',       [NhanVienController::class, 'resend']    )->name('resend');
        Route::post('/{ma_tai_khoan}/vo-hieu-hoa', [NhanVienController::class, 'deactivate'])->name('deactivate');
        Route::delete('/{ma_tai_khoan}',           [NhanVienController::class, 'destroy']   )->name('destroy');
    });

    // ── DANH SÁCH KHÁCH HÀNG ─────────────────────────────────
    Route::middleware('perm:customer.view')->prefix('khach-hang')->name('khachhang.')->group(function () {
        Route::get('/', [\App\Http\Controllers\KhachHangController::class, 'index'])->name('index');
    });

    // ── THẺ THÀNH VIÊN RFID + ĐIỂM ───────────────────────────
    Route::middleware('perm:loyalty.manage')->prefix('the-thanh-vien')->name('loyalty.')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\LoyaltyController::class, 'index']       )->name('index');
        Route::post('/phat-the',            [\App\Http\Controllers\LoyaltyController::class, 'issue']       )->name('issue');
        Route::get('/cau-hinh-hang',              [\App\Http\Controllers\LoyaltyController::class, 'tiersConfig'] )->name('tiers');
        Route::post('/cau-hinh-hang',             [\App\Http\Controllers\LoyaltyController::class, 'tiersUpdate'] )->name('tiers.update');
        Route::delete('/cau-hinh-hang/{ma_hang}', [\App\Http\Controllers\LoyaltyController::class, 'tiersDelete'] )->name('tiers.delete');
        Route::get('/{ma_the}',             [\App\Http\Controllers\LoyaltyController::class, 'show']        )->name('show');
        Route::post('/{ma_the}/trang-thai', [\App\Http\Controllers\LoyaltyController::class, 'updateStatus'])->name('status');
        Route::post('/{ma_the}/dieu-chinh', [\App\Http\Controllers\LoyaltyController::class, 'adjust']      )->name('adjust');
    });

    // ── LOG QUÉT QR ──────────────────────────────────────────
    Route::middleware('perm:scanlog.view')->get('/scan-log', [QrController::class, 'scanLog'])->name('scanlog.index');

    // ── CẢNH BÁO QR BẤT THƯỜNG (ML anomaly detection) ────────
    Route::middleware('perm:anomaly.view')->group(function () {
        Route::get('/scan-anomaly-alerts',                [ScanAnomalyAlertController::class, 'index']   )->name('scan-anomaly.index');
        Route::post('/scan-anomaly-alerts/{id}/feedback', [ScanAnomalyAlertController::class, 'feedback'])->name('scan-anomaly.feedback');
    });

    // ── NHẬT KÝ ĐĂNG NHẬP / AN TOÀN ──────────────────────────
    Route::middleware('perm:auditlog.view')->get('/nhat-ky-dang-nhap', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('auditlog.index');
    Route::middleware('perm:auditlog.view')->get('/nhat-ky-hanh-dong', [\App\Http\Controllers\NhatKyHanhDongController::class, 'index'])->name('hanhdonlog.index');
    // ── NHẬT KÝ EMAIL ────────────────────────────────────────
    Route::middleware('perm:emaillog.view')->group(function () {
        Route::get('/nhat-ky-email',            [\App\Http\Controllers\EmailLogController::class, 'index']      )->name('emaillog.index');
        Route::delete('/nhat-ky-email/that-bai',[\App\Http\Controllers\EmailLogController::class, 'clearFailed'])->name('emaillog.clearFailed');
        Route::delete('/nhat-ky-email/{id}',    [\App\Http\Controllers\EmailLogController::class, 'destroy']    )->name('emaillog.destroy');
    });

    Route::middleware('perm:orders.manage')->prefix('orders')->name('orders.')->group(function () {
        Route::get('/',                      [OrderController::class, 'index']       )->name('index');
        Route::get('/api/list',              [OrderController::class, 'apiList']     )->name('api.list');
        Route::get('/takeaway/create',       [OrderController::class, 'createTakeaway'])->name('takeaway.create');
        Route::post('/takeaway',             [OrderController::class, 'storeTakeaway'])->name('takeaway.store');
        Route::get('/table/{ma_ban}',        [OrderController::class, 'tablePanel']  )->name('table');
        Route::post('/table/{ma_ban}',       [OrderController::class, 'storeTable']  )->name('table.store');
        Route::get('/{ma_order}',            [OrderController::class, 'show']        )->name('show');
        Route::put('/{ma_order}/confirm',    [OrderController::class, 'confirm']     )->name('confirm');
        Route::put('/{ma_order}/status',     [OrderController::class, 'updateStatus'])->name('status');
        Route::post('/{ma_order}/merge',     [OrderController::class, 'merge']       )->name('merge');
        Route::post('/{ma_order}/split',     [OrderController::class, 'split']       )->name('split');
    });

    // ── YÊU CẦU ĐỔI BÀN (khách gửi, nhân viên duyệt) ─────────
    Route::middleware('perm:orders.manage')->group(function () {
        Route::get( '/yeu-cau-doi-ban',            [\App\Http\Controllers\TableMoveController::class, 'index']  )->name('movereq.index');
        Route::post('/yeu-cau-doi-ban/{id}/duyet', [\App\Http\Controllers\TableMoveController::class, 'approve'])->name('movereq.approve');
        Route::post('/yeu-cau-doi-ban/{id}/tu-choi',[\App\Http\Controllers\TableMoveController::class, 'reject'] )->name('movereq.reject');
    });

    Route::middleware('perm:payment.process')->prefix('payment')->name('payment.')->group(function () {
        Route::get( '/{ma_order}',       [PaymentController::class, 'show']    )->name('show');
        Route::post('/{ma_order}',       [PaymentController::class, 'process'] )->name('process');
        Route::post('/{ma_order}/vnpay', [PaymentController::class, 'payVnpay'])->name('vnpay.create');
        Route::get( '/{ma_order}/card-lookup', [PaymentController::class, 'cardLookup'])->name('card-lookup');
    });

    // ── HÓA ĐƠN IN (bán hàng / nhập kho) ─────────────────────
    Route::get('/hoa-don/{ma_order}/in', [\App\Http\Controllers\InvoiceController::class, 'sale'])->name('invoice.sale');
    Route::get('/phieu-nhap/{id}/in',    [\App\Http\Controllers\InvoiceController::class, 'import'])->name('invoice.import');

    Route::middleware(['perm:menu.manage'])->prefix('menu')->name('menu.')->group(function () {
        Route::get('/',              [MenuController::class, 'index']  )->name('index');
        Route::get('/out-of-stock',  [MenuController::class, 'outOfStock'])->name('out-of-stock');
        Route::get('/create',        [MenuController::class, 'create'] )->name('create');
        Route::post('/',             [MenuController::class, 'store']  )->name('store');

        // Quản lý topping (đặt trước route wildcard {ma_mon})
        Route::get('/toppings',                  [\App\Http\Controllers\ToppingController::class, 'index']  )->name('toppings.index');
        Route::post('/toppings',                 [\App\Http\Controllers\ToppingController::class, 'store']  )->name('toppings.store');
        Route::put('/toppings/{ma_topping}',     [\App\Http\Controllers\ToppingController::class, 'update'] )->name('toppings.update');
        Route::delete('/toppings/{ma_topping}',  [\App\Http\Controllers\ToppingController::class, 'destroy'])->name('toppings.destroy');

        Route::get('/{ma_mon}/edit', [MenuController::class, 'edit']   )->name('edit');
        Route::put('/{ma_mon}',      [MenuController::class, 'update'] )->name('update');
        Route::put('/{ma_mon}/restore', [MenuController::class, 'restore'])->name('restore');
        Route::delete('/{ma_mon}',   [MenuController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('perm:ban.manage')->group(function () {
        Route::get('/ban',             [BanController::class, 'index']   )->name('ban.index');
        Route::put('/ban/{ma_ban}',    [BanController::class, 'update']  )->name('ban.update');
        Route::get('/ban/{ma_ban}/qr',        [QrController::class, 'generate'])->name('ban.qr');
        Route::get('/ban/{ma_ban}/qr/poster', [QrController::class, 'poster']  )->name('ban.qr.poster');
        Route::post('/ban/{ma_ban}/photo', [BanController::class, 'uploadPhoto'])->name('ban.photo');
    });

    Route::middleware('perm:inventory.manage')->prefix('inventory')->name('inventory.')->group(function () {
        // Tổng quan & cảnh báo tồn kho
        Route::get('/',      [InventoryController::class, 'index']   )->name('index');
        Route::get('/alert', [InventoryController::class, 'lowStock'])->name('alert');

        // Nguyên liệu & nhà cung cấp (vẫn dùng inventory.manage)
        Route::resource('materials', NguyenLieuController::class)->except(['show']);
        Route::post('/supplier/quick', [SupplierController::class, 'quickStore'])->name('supplier.quick');
        Route::resource('supplier',   SupplierController::class)->except(['show']);

        // ── Phiếu nhập kho ──────────────────────────────────────────────────
        Route::get('/import', [ImportController::class, 'index'])->name('import.index');
        // QUAN TRỌNG: route cố định (create) phải khai báo TRƯỚC route động ({id})
        Route::middleware('perm:import.create')->group(function () {
            Route::get('/import/create', [ImportController::class, 'create'])->name('import.create');
            Route::post('/import',       [ImportController::class, 'store'] )->name('import.store');
        });
        Route::get('/import/{id}', [ImportController::class, 'show'])->name('import.show');
        Route::middleware('perm:import.approve')->group(function () {
            Route::put('/import/{id}/approve', [ImportController::class, 'approve'])->name('import.approve');
            Route::put('/import/{id}/cancel',  [ImportController::class, 'cancel'] )->name('import.cancel');
        });

        // ── Phiếu kiểm kê ───────────────────────────────────────────────────
        Route::get('/stockcheck', [StockCheckController::class, 'index'])->name('stockcheck.index');
        // QUAN TRỌNG: route cố định (create) phải khai báo TRƯỚC route động ({id})
        Route::middleware('perm:stockcheck.create')->group(function () {
            Route::get('/stockcheck/create', [StockCheckController::class, 'create'])->name('stockcheck.create');
            Route::post('/stockcheck',       [StockCheckController::class, 'store'] )->name('stockcheck.store');
        });
        Route::get('/stockcheck/{id}', [StockCheckController::class, 'show'])->name('stockcheck.show');
        Route::middleware('perm:stockcheck.approve')->group(function () {
            Route::put('/stockcheck/{id}/confirm', [StockCheckController::class, 'confirm'])->name('stockcheck.confirm');
            Route::put('/stockcheck/{id}/cancel',  [StockCheckController::class, 'cancel'] )->name('stockcheck.cancel');
        });
    });

    // ── BÁO CÁO DOANH THU (tách riêng, KHÔNG nằm trong inventory) ──
    Route::middleware('perm:inventory.manage')->prefix('report')->name('report.')->group(function () {
        Route::get('/',       [ReportController::class, 'index'] )->name('index');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
        Route::get('/print',  [ReportController::class, 'print'] )->name('print');
    });
});
