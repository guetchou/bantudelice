<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur BantuDelice</title>
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
            padding: 40px 30px;
            text-align: center;
        }
        .header img {
            max-width: 180px;
            height: auto;
        }
        .header h1 {
            color: #ffffff;
            font-size: 24px;
            margin-top: 20px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 20px;
            color: #1A1A2E;
            margin-bottom: 20px;
        }
        .message {
            color: #666;
            font-size: 16px;
            margin-bottom: 20px;
            line-height: 1.8;
        }
        .highlight-box {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%);
            border-left: 4px solid #FF6B35;
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .highlight-box img {
            width: 48px;
            height: 48px;
        }
        .highlight-box p {
            color: #1A1A2E;
            margin: 0;
            flex: 1;
        }
        .features {
            margin: 30px 0;
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
        }
        .feature-icon {
            width: 48px;
            height: 48px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .feature-icon img {
            width: 100%;
            height: 100%;
        }
        .feature-text h4 {
            color: #1A1A2E;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .feature-text p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #FF6B35 0%, #E55A2B 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
        }
        .cta-section {
            text-align: center;
            margin: 30px 0;
        }
        .footer {
            background: #1A1A2E;
            padding: 30px;
            text-align: center;
        }
        .footer p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-bottom: 15px;
        }
        .social-links {
            margin: 20px 0;
        }
        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            margin: 0 5px;
            line-height: 40px;
            color: #ffffff;
            text-decoration: none;
        }
        .footer-links {
            margin-top: 20px;
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
                padding: 30px 20px;
            }
            .feature-item {
                flex-direction: column;
                text-align: center;
            }
            .feature-icon {
                margin: 0 auto 10px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <img src="{{ url('frontend/images/BuntuDelice.png') }}" alt="BantuDelice">
            <h1>Bienvenue dans la famille BantuDelice !</h1>
        </div>
        
        <!-- Content -->
        <div class="content">
            <p class="greeting">Bonjour {{ $data['name'] }},</p>
            
            <p class="message">
                Nous sommes ravis de vous accueillir sur BantuDelice ! Votre inscription a été effectuée avec succès.
            </p>
            
            <div class="highlight-box">
                <img src="{{ url('images/icons/celebration.svg') }}" alt="Célébration">
                <p>
                    Votre compte est maintenant actif ! Vous pouvez dès maintenant commander vos plats préférés et profiter de nos services de livraison.
                </p>
            </div>
            
            <p class="message">
                Chez BantuDelice, nous nous engageons à vous offrir la meilleure expérience de livraison. Voici ce que vous pouvez faire :
            </p>
            
            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">
                        <img src="{{ url('images/icons/restaurant.svg') }}" alt="Restaurant">
                    </div>
                    <div class="feature-text">
                        <h4>Commander des repas</h4>
                        <p>Découvrez les meilleurs restaurants de votre ville</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <img src="{{ url('images/icons/shopping-cart.svg') }}" alt="Courses">
                    </div>
                    <div class="feature-text">
                        <h4>Faire vos courses</h4>
                        <p>Épicerie, produits frais et bien plus encore</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <img src="{{ url('images/icons/flower-bouquet.svg') }}" alt="Fleurs">
                    </div>
                    <div class="feature-text">
                        <h4>Envoyer des fleurs</h4>
                        <p>Surprenez vos proches avec de belles attentions</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <img src="{{ url('images/icons/package-box.svg') }}" alt="Colis">
                    </div>
                    <div class="feature-text">
                        <h4>Livraison de colis</h4>
                        <p>Service de coursier rapide et fiable</p>
                    </div>
                </div>
            </div>
            
            <div class="cta-section">
                <a href="{{ url('/') }}" class="cta-button">Commencer à commander</a>
            </div>
            
            <p class="message">
                Si vous avez des questions ou besoin d'aide, n'hésitez pas à nous contacter. Notre équipe est là pour vous accompagner.
            </p>
            
            <p class="message">
                À très bientôt sur BantuDelice !<br>
                <strong>L'équipe BantuDelice</strong>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Suivez-nous sur les réseaux sociaux</p>
            <div class="social-links">
                <a href="https://www.facebook.com/bantudelice" title="Facebook">f</a>
                <a href="https://www.instagram.com/bantudelice" title="Instagram">in</a>
                <a href="https://twitter.com/bantudelice" title="Twitter">X</a>
            </div>
            <p>
                © {{ date('Y') }} BantuDelice. Tous droits réservés.<br>
                Brazzaville, République du Congo
            </p>
            <div class="footer-links">
                <a href="{{ url('/terms-and-conditions') }}">Conditions générales</a>
                <a href="{{ url('/return-policy') }}">Politique de remboursement</a>
                <a href="{{ url('/contact-us') }}">Contact</a>
            </div>
        </div>
    </div>
</body>
</html>
