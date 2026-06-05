<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background:#f6f3f2; padding:24px; color:#1a1a1a;">
    <div style="max-width:480px;margin:0 auto;background:#fff;border-radius:16px;padding:28px;">
        <h2 style="color:#8B5A2B;margin-top:0;">8AM Coffee</h2>
        <p>Xin chào <strong>{{ $tenNv }}</strong>,</p>
        <p>Tài khoản đăng nhập hệ thống của bạn đã được tạo. Thông tin đăng nhập:</p>
        <div style="background:#FFF7E8;border-radius:12px;padding:16px;margin:16px 0;">
            <p style="margin:4px 0;">Tên đăng nhập: <strong>{{ $tenTk }}</strong></p>
            <p style="margin:4px 0;">Mật khẩu: <strong>{{ $matKhau }}</strong></p>
        </div>
        @if($activationUrl)
        <p>Để bảo mật, tài khoản cần được <strong>kích hoạt</strong> trước khi đăng nhập. Vui lòng bấm nút bên dưới (link có hiệu lực {{ $activationHours }} giờ):</p>
        <p style="text-align:center;margin:20px 0;">
            <a href="{{ $activationUrl }}" style="display:inline-block;background:#E82C2A;color:#fff;text-decoration:none;padding:12px 28px;border-radius:999px;font-weight:600;">Kích hoạt tài khoản</a>
        </p>
        <p style="font-size:12px;color:#777;">Nếu nút không bấm được, dán link sau vào trình duyệt:<br>{{ $activationUrl }}</p>
        @endif
        <p>Vui lòng đăng nhập và đổi mật khẩu (liên hệ quản lý nếu cần). Không chia sẻ thông tin này cho người khác.</p>
        <p style="color:#522C25;font-size:13px;">— Đội ngũ 8AM Coffee</p>
    </div>
</body>
</html>
