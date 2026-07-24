<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Contact Inquiry</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f4f4f7;
            color: #333333;
            margin: 0;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .header {
            background-color: #0b0904;
            color: #C7A75A;
            padding: 24px;
            text-align: center;
            border-bottom: 2px solid #C7A75A;
        }
        .header h2 {
            margin: 0;
            font-size: 20px;
            letter-spacing: 1px;
        }
        .content {
            padding: 30px 24px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .meta-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #edf2f7;
            font-size: 14px;
        }
        .meta-label {
            font-weight: bold;
            color: #4a5568;
            width: 120px;
        }
        .message-box {
            background-color: #f8fafc;
            border-left: 4px solid #C7A75A;
            padding: 16px;
            border-radius: 4px;
            font-size: 15px;
            line-height: 1.6;
            color: #2d3748;
            white-space: pre-wrap;
        }
        .footer {
            background-color: #f8fafc;
            padding: 16px 24px;
            text-align: center;
            font-size: 12px;
            color: #718096;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h2>EXECUTIVE CRICKET CLUB</h2>
            <p style="margin: 4px 0 0; font-size: 13px; opacity: 0.85;">Website Contact Form Inquiry</p>
        </div>

        <div class="content">
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Sender Name:</td>
                    <td><strong>{{ $contactMessage->name }}</strong></td>
                </tr>
                <tr>
                    <td class="meta-label">Email Address:</td>
                    <td><a href="mailto:{{ $contactMessage->email }}" style="color: #9C7D35; text-decoration: none;">{{ $contactMessage->email }}</a></td>
                </tr>
                @if($contactMessage->subject)
                    <tr>
                        <td class="meta-label">Subject:</td>
                        <td>{{ $contactMessage->subject }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="meta-label">Submitted At:</td>
                    <td>{{ $contactMessage->created_at->format('M d, Y @ h:i A') }}</td>
                </tr>
            </table>

            <h4 style="margin-bottom: 12px; color: #1a202c; font-size: 15px;">Inquiry Message:</h4>
            <div class="message-box">
                {{ $contactMessage->message }}
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0;">This email was sent automatically from the Executive Cricket Club contact form.</p>
            <p style="margin: 4px 0 0;">You can reply directly to this email to respond to {{ $contactMessage->name }}.</p>
        </div>
    </div>
</body>
</html>
