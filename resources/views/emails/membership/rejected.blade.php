<!DOCTYPE html>
<html>
<head>
    <title>Application Status Update</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Application Status Update</h2>
    <p>Dear {{ $application->user->name }},</p>
    <p>Thank you for your interest in the Executive Cricket Club.</p>
    <p>After careful review of your application, we regret to inform you that we are unable to approve your membership request at this time.</p>
    @if($reason)
        <p><strong>Reason:</strong><br>
        {{ $reason }}</p>
    @endif
    <p>You may contact us for further clarification or submit a new application in the future.</p>
    <p>Best Regards,<br>Executive Cricket Club Team</p>
</body>
</html>
