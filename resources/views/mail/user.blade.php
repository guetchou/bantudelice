<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification BantuDelice</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 100%);
            padding: 30px;
            text-align: center;
        }
        .header img {
            max-width: 150px;
            height: auto;
        }
        .content {
            padding: 40px 30px;
        }
        .message {
            color: #666;
            font-size: 16px;
            margin-bottom: 20px;
            line-height: 1.8;
        }
        .footer {
            background: #1A1A2E;
            padding: 30px;
            text-align: center;
        }
        .footer p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-bottom: 10px;
        }
        .footer-links a {
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 12px;
            margin: 0 10px;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }
            .header {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <img src="{{ url('frontend/images/BuntuDelice.png') }}" alt="BantuDelice">
        </div>
        
        <!-- Content -->
        <div class="content">
            <p class="message">Bonjour,</p>
            <div class="message">{!! $data !!}</div>
            <p class="message">Cordialement,<br><strong>L'équipe BantuDelice</strong></p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>© {{ date('Y') }} BantuDelice. Tous droits réservés.</p>
            <p>Brazzaville, République du Congo</p>
            <div class="footer-links">
                <a href="{{ url('/terms-and-conditions') }}">Conditions générales</a>
                <a href="{{ url('/contact-us') }}">Contact</a>
            </div>
        </div>
    </div>
</body>
</html>
