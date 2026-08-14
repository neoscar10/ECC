<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login OTP</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <!-- Card Header -->
        <div style="background-color: #f8fafc; padding: 24px; border-bottom: 1px solid #e2e8f0;">
            <h1 style="margin: 0; color: #1e293b; font-size: 20px; font-weight: 600;">Account Login</h1>
        </div>
        
        <!-- Card Body -->
        <div style="padding: 32px 24px;">
            <p style="margin-top: 0; margin-bottom: 24px; font-size: 16px; color: #475569; line-height: 1.6;">
                You requested to log in to your account. Use the verification code below to complete the process.
            </p>
            <div style="background-color: #f9f9f9; padding: 20px; text-align: center; border-radius: 4px; margin: 30px 0;">
                <span style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #C7A75A;">{{ $otp }}</span>
            </div>
            <p style="color: #555555; font-size: 14px; line-height: 1.5;">
                If you did not request a login, please ignore this email. This code will expire in 10 minutes.
            </p>
        </div>
        <hr style="border: none; border-top: 1px solid #eeeeee; margin: 30px 0;">
        <p style="color: #999999; font-size: 12px; text-align: center;">
            &copy; {{ date('Y') }} Executive Club Cricket. All rights reserved.
        </p>
    </div>
</body>
</html>
