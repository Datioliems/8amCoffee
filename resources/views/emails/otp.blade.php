<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background:#f6f3f2; padding:24px; color:#1a1a1a;">
    <div style="max-width:480px;margin:0 auto;background:#fff;border-radius:16px;padding:28px;">
        <h2 style="color:#8B5A2B;margin-top:0;">8AM Coffee</h2>
        <p>Xin chào <strong>{{ $tenNv }}</strong>,</p>
        <p>Mã xác thực đăng nhập (OTP) của bạn là:</p>
        <div style="background:#FFF7E8;border-radius:12px;padding:20px;margin:16px 0;text-align:center;">
            <span style="font-size:34px;font-weight:700;letter-spacing:10px;color:#522C25;">{{ $otp }}</span>
        </div>
        <p>Mã có hiệu lực trong <strong>{{ $phut }} phút</strong>. Vui lòng không chia sẻ mã này cho bất kỳ ai —
           kể cả người tự xưng là nhân viên 8AM Coffee.</p>
        <p style="color:#999;font-size:13px;">Nếu bạn không thực hiện đăng nhập, hãy đổi mật khẩu ngay và báo quản lý.</p>
        <p style="color:#522C25;font-size:13px;">— Đội ngũ 8AM Coffee</p>
    </div>
</body>
</html>
