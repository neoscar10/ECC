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
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 12px; border-top: 1px solid #e9ecef; }
        .info-box { background-color: #f8f9fa; border: 1px solid #e9ecef; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .info-item { display: flex; justify-content: space-between; border-bottom: 1px solid #dee2e6; padding-bottom: 10px; margin-bottom: 10px; }
        .info-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Executive Cricket Club</h2>
        </div>
        <div class="content">
            <p>Dear {{ $enquiry->contact_name ?? 'Member' }},</p>
            
            <p>Regarding your enquiry for the archive item <strong>{{ $enquiry->product->title ?? 'Item' }}</strong>, we require your delivery details to prepare the shipment and calculate the final invoice details.</p>
            
            <p>We have sent you a delivery details form on <strong>WhatsApp</strong> to your registered ECC WhatsApp number: <strong>{{ $enquiry->contact_phone ?? 'your registered number' }}</strong>.</p>
            
            <p>Please open WhatsApp and complete the form. Once submitted, we will process your shipping details and send you the secure payment link.</p>
            
            <div class="info-box">
                <div class="info-item">
                    <span><strong>Item:</strong></span>
                    <span>{{ $enquiry->product->title ?? 'Archive Item' }}</span>
                </div>
                <div class="info-item">
                    <span><strong>Enquiry ID:</strong></span>
                    <span>#{{ str_pad($enquiry->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="info-item">
                    <span><strong>WhatsApp Number:</strong></span>
                    <span>{{ $enquiry->contact_phone ?? 'N/A' }}</span>
                </div>
            </div>
            
            <p>Thank you for choosing Executive Cricket Club.</p>
            
            <p>Best Regards,<br><strong>The Archive Team</strong></p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Executive Cricket Club. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
