<!DOCTYPE html>
<html>
<head>
    <title>Membership Approved</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Congratulations!</h2>
    <p>Dear {{ $application->user->name }},</p>
    <p>We are pleased to inform you that your application for membership at the <strong>Executive Cricket Club</strong> has been <strong>APPROVED</strong>.</p>
    <p>
        <strong>Membership Tier:</strong> {{ $application->membershipTier->name ?? 'Member' }}<br>
        <strong>Status:</strong> Active
    </p>
    <p>You can now access the full range of benefits associated with your membership tier.</p>
    
    <p>If you have any questions, please contact our support team.</p>
    <p>Best Regards,<br>Executive Cricket Club Team</p>
</body>
</html>
