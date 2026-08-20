<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f5f7fb; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #121331; padding: 30px; text-align: center; color: white; }
        .content { padding: 40px; color: #333; line-height: 1.6; }
        .button { display: inline-block; padding: 12px 24px; background-color: #08a88a; color: white !important; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 20px; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 12px; border-top: 1px solid #e9ecef; }
        .invoice-box { background-color: #f8f9fa; border: 1px solid #e9ecef; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .invoice-item { display: flex; justify-content: space-between; border-bottom: 1px solid #dee2e6; padding-bottom: 10px; margin-bottom: 10px; }
        .invoice-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .total { font-weight: bold; font-size: 18px; color: #121331; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Executive Cricket Club</h2>
        </div>
        <div class="content">
            <p>Dear {{ $enquiry->contact_name ?? 'Member' }},</p>
            
            <p>Following your enquiry regarding the archive item <strong>{{ $enquiry->product->title ?? 'Item' }}</strong>, we are pleased to inform you that we have generated a payment link for your purchase.</p>
            
            <div class="invoice-box">
                <div class="invoice-item">
                    <span><strong>Item:</strong></span>
                    <span>{{ $enquiry->product->title ?? 'Archive Item' }}</span>
                </div>
                <div class="invoice-item">
                    <span><strong>Enquiry ID:</strong></span>
                    <span>#{{ str_pad($enquiry->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="invoice-item total">
                    <span><strong>Amount Due:</strong></span>
                    <span>₹{{ number_format($enquiry->payment_amount, 2) }}</span>
                </div>
            </div>
            
            <p style="text-align: center;">
                <a href="{{ $checkoutUrl }}" class="button">Proceed to Secure Payment</a>
            </p>
            
            <p style="margin-top: 30px; font-size: 14px; color: #6c757d;">
                If the button above does not work, you can copy and paste the following URL into your browser:<br>
                <a href="{{ $checkoutUrl }}" style="word-break: break-all; color: #08a88a;">{{ $checkoutUrl }}</a>
            </p>
            
            <p>Thank you for choosing Executive Cricket Club.</p>
            
            <p>Best Regards,<br><strong>The Archive Team</strong></p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Executive Cricket Club. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
