<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Page Out of Play | Executive Cricket Club</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    
    <!-- Google Fonts & RemixIcons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark: #090c10;
            --bg-card: rgba(18, 24, 38, 0.75);
            --gold-primary: #d4af37;
            --gold-light: #f3e5ab;
            --gold-glow: rgba(212, 175, 55, 0.25);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-glow: rgba(212, 175, 55, 0.3);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Public Sans', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }

        /* Dynamic Animated Background Components */
        .ambient-light {
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            filter: blur(140px);
            opacity: 0.18;
            pointer-events: none;
            z-index: 0;
        }

        .ambient-gold {
            background: radial-gradient(circle, var(--gold-primary) 0%, transparent 70%);
            top: -150px;
            left: -150px;
            animation: pulse-slow 8s infinite alternate ease-in-out;
        }

        .ambient-emerald {
            background: radial-gradient(circle, #059669 0%, transparent 70%);
            bottom: -150px;
            right: -150px;
            animation: pulse-slow 10s infinite alternate ease-in-out 2s;
        }

        @keyframes pulse-slow {
            0% { transform: scale(1) translate(0, 0); opacity: 0.15; }
            100% { transform: scale(1.25) translate(40px, -30px); opacity: 0.28; }
        }

        /* Cricket Pitch Grid Lines Subtlety */
        .pitch-lines {
            position: absolute;
            inset: 0;
            background-image: 
                radial-gradient(circle at 50% 50%, rgba(212, 175, 55, 0.04) 1px, transparent 1px),
                linear-gradient(to right, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
            background-size: 40px 40px, 80px 80px;
            pointer-events: none;
            z-index: 0;
        }

        /* Card Container */
        .error-card {
            position: relative;
            z-index: 1;
            width: 90%;
            max-width: 620px;
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-glow);
            border-radius: 24px;
            padding: 50px 36px;
            text-align: center;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.7),
                0 0 40px var(--gold-glow);
            animation: fadeUp 0.8s ease-out forwards;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Branding Top Emblem */
        .brand-emblem {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background: radial-gradient(circle at 30% 30%, #2a2d3d, #121622);
            border: 2px solid var(--gold-primary);
            border-radius: 50%;
            margin-bottom: 24px;
            box-shadow: 0 0 25px var(--gold-glow);
            position: relative;
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .brand-emblem img {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        /* Huge 404 Display */
        .error-code {
            font-family: 'Cinzel', serif;
            font-size: 110px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -2px;
            background: linear-gradient(135deg, #ffffff 0%, var(--gold-light) 40%, var(--gold-primary) 70%, #997a15 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 10px 30px rgba(0,0,0,0.5);
            margin-bottom: 10px;
        }

        .error-title {
            font-family: 'Cinzel', serif;
            font-size: 26px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 14px;
            letter-spacing: 0.5px;
        }

        .error-description {
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.6;
            max-width: 480px;
            margin: 0 auto 36px;
        }

        /* Action Buttons */
        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            justify-content: center;
            align-items: center;
        }

        .btn-gold {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold-primary) 100%);
            color: #0b0e14;
            font-weight: 700;
            font-size: 14px;
            padding: 14px 28px;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 20px rgba(212, 175, 55, 0.35);
            border: none;
            cursor: pointer;
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(212, 175, 55, 0.5);
            background: linear-gradient(135deg, #ffffff 0%, var(--gold-primary) 100%);
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            font-weight: 600;
            font-size: 14px;
            padding: 14px 24px;
            border-radius: 50px;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: var(--gold-primary);
            color: var(--gold-light);
            transform: translateY(-2px);
        }

        /* Quick Links Nav */
        .quick-nav {
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .quick-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }

        .quick-link:hover {
            color: var(--gold-primary);
        }

        @media (max-width: 576px) {
            .error-card {
                padding: 36px 20px;
            }

            .error-code {
                font-size: 80px;
            }

            .error-title {
                font-size: 22px;
            }

            .action-group {
                flex-direction: column;
                width: 100%;
            }

            .btn-gold, .btn-outline {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- Ambient Glowing Orbs -->
    <div class="ambient-light ambient-gold"></div>
    <div class="ambient-light ambient-emerald"></div>
    
    <!-- Background Grid Lines -->
    <div class="pitch-lines"></div>

    <!-- Main Card Content -->
    <div class="error-card">
        <!-- Logo Emblem -->
        <div class="brand-emblem">
            <img src="{{ asset('ecc_logo_dark.png') }}" alt="Executive Cricket Club">
        </div>

        <!-- 404 Number -->
        <div class="error-code">404</div>

        <!-- Title -->
        <h1 class="error-title">Page Out of Play</h1>

        <!-- Message -->
        <p class="error-description">
            The page or resource you are searching for has been caught outside the boundary or moved to a different pavilion.
        </p>

        <!-- Main Action Buttons -->
        <div class="action-group">
            <a href="{{ route('home') }}" class="btn-gold">
                <i class="ri-home-5-line"></i> Return to Pavilion
            </a>
            <button onclick="window.history.back();" class="btn-outline">
                <i class="ri-arrow-left-line"></i> Go Back
            </button>
        </div>

        <!-- Useful Quick Links -->
        <div class="quick-nav">
            <a href="{{ route('archive.index') }}" class="quick-link">
                <i class="ri-archive-line"></i> The Archive
            </a>
            <a href="{{ route('shop.index') }}" class="quick-link">
                <i class="ri-store-2-line"></i> Cricket Store
            </a>
            <a href="{{ route('auctions.index') }}" class="quick-link">
                <i class="ri-auction-line"></i> Live Auctions
            </a>
            <a href="{{ route('contact') }}" class="quick-link">
                <i class="ri-customer-service-2-line"></i> Contact Support
            </a>
        </div>
    </div>

</body>
</html>
