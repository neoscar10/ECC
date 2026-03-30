<!DOCTYPE html>
<html>
<head>
    <title>Your ECC Account Has Been Created</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="color: #405189; margin-bottom: 5px;">Executive Club Cricket</h2>
            <p style="color: #666; margin-top: 0;">Premier Cricket Memorabilia & Experience</p>
        </div>

        <h3 style="color: #0ab39c;">Hello {{ $user->name }},</h3>
        <p>An administrator has created your ECC account. You now have access to the exclusive ECC Pavilion.</p>
        
        <div style="background-color: #f3f6f9; padding: 20px; border-radius: 5px; margin: 25px 0; border: 1px solid #e2e8f0;">
            <h4 style="margin-top: 0; color: #405189; border-bottom: 1px solid #cbd5e1; padding-bottom: 10px;">Login Credentials</h4>
            <p style="margin: 10px 0;"><strong>Login URL:</strong> <a href="{{ route('login') }}" style="color: #405189;">{{ route('login') }}</a></p>
            <p style="margin: 10px 0;"><strong>Email:</strong> {{ $user->email }}</p>
            @if($user->phone)
            <p style="margin: 10px 0;"><strong>Phone:</strong> {{ $user->phone }}</p>
            @endif
            <p style="margin: 10px 0;"><strong>Temporary Password:</strong> <span style="background-color: #fff; padding: 2px 5px; border: 1px dashed #405189; font-family: monospace;">{{ $password }}</span></p>
        </div>

        <div style="background-color: #fff3f3; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #fecaca;">
            <p style="margin: 0; color: #991b1b;"><strong>IMPORTANT:</strong> Please change your password immediately after logging in for the first time.</p>
        </div>

        <div style="margin: 25px 0;">
            <h4 style="color: #405189;">Membership Details</h4>
            <p style="margin: 5px 0;"><strong>Assigned Tier:</strong> {{ $tier->name }}</p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="{{ route('login') }}" style="background-color: #405189; color: white; padding: 12px 30px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">Login to ECC Pavilion</a>
        </div>
        
        <p style="margin-top: 30px; font-size: 13px; color: #666;">
            If you did not expect this email, please ignore it or contact our support team.
        </p>

        <p style="border-top: 1px solid #e0e0e0; padding-top: 20px; font-size: 12px; color: #999; text-align: center;">
            &copy; {{ date('Y') }} Executive Club Cricket. All rights reserved.
        </p>
    </div>
</body>
</html>
