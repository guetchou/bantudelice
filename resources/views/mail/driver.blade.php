<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue Livreur BantuDelice</title>
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
        .steps {
            margin: 30px 0;
        }
        .step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
        }
        .step-icon {
            width: 48px;
            height: 48px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .step-icon img {
            width: 100%;
            height: 100%;
        }
        .step-text h4 {
            color: #1A1A2E;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .step-text p {
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
            .step-item {
                flex-direction: column;
                text-align: center;
            }
            .step-icon {
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
            <h1>Bienvenue dans l'équipe de livreurs !</h1>
        </div>
        
        <!-- Content -->
        <div class="content">
            <p class="greeting">Bonjour {{ $data['name'] ?? 'Nouveau livreur' }},</p>
            
            <p class="message">
                Félicitations ! Votre inscription en tant que livreur BantuDelice a été reçue avec succès.
            </p>
            
            <div class="highlight-box">
                <img src="{{ url('images/icons/celebration.svg') }}" alt="Félicitations">
                <p>
                    Votre candidature est en cours de validation. Notre équipe l'examinera dans les plus brefs délais et vous recevrez une confirmation par email.
                </p>
            </div>
            
            <p class="message">
                En attendant, voici ce que vous devez savoir :
            </p>
            
            <div class="steps">
                <div class="step-item">
                    <div class="step-icon">
                        <img src="{{ url('images/icons/happy-customer.svg') }}" alt="Validation">
                    </div>
                    <div class="step-text">
                        <h4>Validation de votre profil</h4>
                        <p>Notre équipe vérifie vos informations et documents</p>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-icon">
                        <img src="{{ url('images/icons/food-delivery.svg') }}" alt="Activation">
                    </div>
                    <div class="step-text">
                        <h4>Activation du compte</h4>
                        <p>Vous recevrez un email de confirmation une fois validé</p>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-icon">
                        <img src="{{ url('images/icons/delivery-scooter.svg') }}" alt="Livraison">
                    </div>
                    <div class="step-text">
                        <h4>Commencez à livrer</h4>
                        <p>Connectez-vous à l'application et acceptez des commandes</p>
                    </div>
                </div>
            </div>
            
            <p class="message">
                Si vous avez des questions, n'hésitez pas à nous contacter. Notre équipe est là pour vous accompagner dans cette nouvelle aventure.
            </p>
            
            <p class="message">
                À très bientôt sur les routes !<br>
                <strong>L'équipe BantuDelice</strong>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>
                © {{ date('Y') }} BantuDelice. Tous droits réservés.<br>
                Brazzaville, République du Congo
            </p>
            <div class="footer-links">
                <a href="{{ url('/terms-and-conditions') }}">Conditions générales</a>
                <a href="{{ url('/contact-us') }}">Contact</a>
            </div>
        </div>
    </div>
</body>
</html>
