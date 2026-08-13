<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Reset OTP</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2 style="color: #333333; margin-top: 0;">Password Reset Request</h2>
        <p style="color: #555555; font-size: 16px; line-height: 1.5;">
            You requested a password reset for your Executive Club Cricket account. Please use the following One-Time Password (OTP) to proceed:
        </p>
        <div style="background-color: #f9f9f9; padding: 20px; text-align: center; border-radius: 4px; margin: 30px 0;">
            <span style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #C7A75A;">{{ $otp }}</span>
        </div>
        <p style="color: #555555; font-size: 14px; line-height: 1.5;">
            If you did not request a password reset, please ignore this email. This code will expire in 10 minutes.
        </p>
        <hr style="border: none; border-top: 1px solid #eeeeee; margin: 30px 0;">
        <p style="color: #999999; font-size: 12px; text-align: center;">
            &copy; {{ date('Y') }} Executive Club Cricket. All rights reserved.
        </p>
    </div>
</body>
</html>
