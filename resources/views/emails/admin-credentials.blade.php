<!DOCTYPE html>
<html>
<head>
    <title>Your ECC Admin Login Details</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
        <h2 style="color: #0ab39c;">Welcome to Executive Cricket Club</h2>
        <p>Hello {{ $user->name }},</p>
        <p>An administrator account has been created for you. Please find your login details below:</p>
        
        <div style="background-color: #f3f6f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Login URL:</strong> <a href="{{ route('login') }}" style="color: #405189;">{{ route('login') }}</a></p>
            <p style="margin: 5px 0;"><strong>Email:</strong> {{ $user->email }}</p>
            <p style="margin: 5px 0;"><strong>Temporary Password:</strong> {{ $password }}</p>
        </div>

        <p><strong>Security Note:</strong> Please change your password immediately after your first login.</p>
        
        <p>Best regards,<br>ECC Admin Team</p>
    </div>
</body>
</html>
