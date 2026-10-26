<!DOCTYPE html>
<html lang="vi">

<body>
    <p>Xin chào {{ $recipientName }},</p>
    <p>Mã OTP khôi phục mật khẩu MESA của bạn là:</p>
    <p style="font-size: 24px; font-weight: bold; letter-spacing: 6px;">{{ $otp }}</p>
    <p>Mã có hiệu lực trong 5 phút và chỉ sử dụng được một lần.</p>
    <p>Nếu bạn không yêu cầu khôi phục mật khẩu, hãy bỏ qua email này.</p>
</body>

</html>
