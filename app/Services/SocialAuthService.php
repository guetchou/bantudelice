<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Service d'authentification sociale (Google, Facebook, Apple)
 */
class SocialAuthService
{
    /**
     * Obtenir l'URL d'autorisation Google
     * 
     * @param string $redirectUri URI de callback (optionnel)
     * @return string URL d'autorisation
     */
    public static function getGoogleAuthUrl(?string $redirectUri = null): string
    {
        $config = config('external-services.social_auth.google');
        
        if (!$config['enabled']) {
            throw new \RuntimeException('Authentification Google non activée');
        }
        
        $redirectUri = $redirectUri ?? url($config['redirect']);
        
        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'offline',
            'prompt' => 'select_account',
            'state' => csrf_token(),
        ];
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * Traiter le callback Google et authentifier l'utilisateur
     * 
     * @param string $code Code d'autorisation
     * @param string $redirectUri URI de callback utilisé
     * @return array ['user' => User, 'token' => string, 'is_new' => bool]
     */
    public static function handleGoogleCallback(string $code, ?string $redirectUri = null): array
    {
        $config = config('external-services.social_auth.google');
        $redirectUri = $redirectUri ?? url($config['redirect']);
        
        // Échanger le code contre un token
        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);
        
        if (!$tokenResponse->successful()) {
            Log::error('Google token exchange failed', ['response' => $tokenResponse->json()]);
            throw new \RuntimeException('Erreur lors de l\'authentification Google');
        }
        
        $tokens = $tokenResponse->json();
        $accessToken = $tokens['access_token'];
        
        // Récupérer les infos utilisateur
        $userResponse = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v2/userinfo');
        
        if (!$userResponse->successful()) {
            throw new \RuntimeException('Impossible de récupérer les informations utilisateur');
        }
        
        $googleUser = $userResponse->json();
        
        // Créer ou mettre à jour l'utilisateur
        return self::findOrCreateUser([
            'provider' => 'google',
            'provider_id' => $googleUser['id'],
            'email' => $googleUser['email'],
            'name' => $googleUser['name'],
            'avatar' => $googleUser['picture'] ?? null,
            'email_verified' => $googleUser['verified_email'] ?? false,
        ]);
    }
    
    /**
     * Obtenir l'URL d'autorisation Facebook
     * 
     * @param string $redirectUri
     * @return string
     */
    public static function getFacebookAuthUrl(?string $redirectUri = null): string
    {
        $config = config('external-services.social_auth.facebook');
        
        if (!$config['enabled']) {
            throw new \RuntimeException('Authentification Facebook non activée');
        }
        
        $redirectUri = $redirectUri ?? url($config['redirect']);
        
        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $redirectUri,
            'scope' => 'email,public_profile',
            'response_type' => 'code',
            'state' => csrf_token(),
        ];
        
        return 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    }
    
    /**
     * Traiter le callback Facebook
     * 
     * @param string $code
     * @param string $redirectUri
     * @return array
     */
    public static function handleFacebookCallback(string $code, ?string $redirectUri = null): array
    {
        $config = config('external-services.social_auth.facebook');
        $redirectUri = $redirectUri ?? url($config['redirect']);
        
        // Échanger le code contre un token
        $tokenResponse = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ]);
        
        if (!$tokenResponse->successful()) {
            Log::error('Facebook token exchange failed', ['response' => $tokenResponse->json()]);
            throw new \RuntimeException('Erreur lors de l\'authentification Facebook');
        }
        
        $tokens = $tokenResponse->json();
        $accessToken = $tokens['access_token'];
        
        // Récupérer les infos utilisateur
        $userResponse = Http::get('https://graph.facebook.com/me', [
            'access_token' => $accessToken,
            'fields' => 'id,name,email,picture.type(large)',
        ]);
        
        if (!$userResponse->successful()) {
            throw new \RuntimeException('Impossible de récupérer les informations utilisateur');
        }
        
        $fbUser = $userResponse->json();
        
        return self::findOrCreateUser([
            'provider' => 'facebook',
            'provider_id' => $fbUser['id'],
            'email' => $fbUser['email'] ?? null,
            'name' => $fbUser['name'],
            'avatar' => $fbUser['picture']['data']['url'] ?? null,
            'email_verified' => !empty($fbUser['email']),
        ]);
    }
    
    /**
     * Authentifier via token (pour app mobile)
     * 
     * @param string $provider google|facebook|apple
     * @param string $accessToken Token d'accès du provider
     * @return array
     */
    public static function authenticateWithToken(string $provider, string $accessToken): array
    {
        switch ($provider) {
            case 'google':
                return self::authenticateGoogleToken($accessToken);
            case 'facebook':
                return self::authenticateFacebookToken($accessToken);
            case 'apple':
                return self::authenticateAppleToken($accessToken);
            default:
                throw new \InvalidArgumentException('Provider non supporté: ' . $provider);
        }
    }
    
    /**
     * Authentifier avec un token Google
     */
    protected static function authenticateGoogleToken(string $accessToken): array
    {
        $response = Http::get('https://www.googleapis.com/oauth2/v2/userinfo', [
            'access_token' => $accessToken,
        ]);
        
        if (!$response->successful()) {
            throw new \RuntimeException('Token Google invalide');
        }
        
        $googleUser = $response->json();
        
        return self::findOrCreateUser([
            'provider' => 'google',
            'provider_id' => $googleUser['id'],
            'email' => $googleUser['email'],
            'name' => $googleUser['name'],
            'avatar' => $googleUser['picture'] ?? null,
            'email_verified' => $googleUser['verified_email'] ?? false,
        ]);
    }
    
    /**
     * Authentifier avec un token Facebook
     */
    protected static function authenticateFacebookToken(string $accessToken): array
    {
        $response = Http::get('https://graph.facebook.com/me', [
            'access_token' => $accessToken,
            'fields' => 'id,name,email,picture.type(large)',
        ]);
        
        if (!$response->successful()) {
            throw new \RuntimeException('Token Facebook invalide');
        }
        
        $fbUser = $response->json();
        
        return self::findOrCreateUser([
            'provider' => 'facebook',
            'provider_id' => $fbUser['id'],
            'email' => $fbUser['email'] ?? null,
            'name' => $fbUser['name'],
            'avatar' => $fbUser['picture']['data']['url'] ?? null,
            'email_verified' => !empty($fbUser['email']),
        ]);
    }
    
    /**
     * Authentifier avec un token Apple (Sign in with Apple)
     */
    protected static function authenticateAppleToken(string $identityToken): array
    {
        // Décoder le JWT Apple
        $parts = explode('.', $identityToken);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Token Apple invalide');
        }
        
        $payload = json_decode(base64_decode($parts[1]), true);
        
        if (!$payload || !isset($payload['sub'])) {
            throw new \RuntimeException('Payload Apple invalide');
        }
        
        return self::findOrCreateUser([
            'provider' => 'apple',
            'provider_id' => $payload['sub'],
            'email' => $payload['email'] ?? null,
            'name' => $payload['name'] ?? 'Utilisateur Apple',
            'avatar' => null,
            'email_verified' => $payload['email_verified'] ?? false,
        ]);
    }
    
    /**
     * Trouver ou créer un utilisateur à partir des données du provider
     * 
     * @param array $data
     * @return array ['user' => User, 'token' => string, 'is_new' => bool]
     */
    protected static function findOrCreateUser(array $data): array
    {
        $isNew = false;
        
        // Chercher par provider_id d'abord
        $user = User::where('social_provider', $data['provider'])
            ->where('social_id', $data['provider_id'])
            ->first();
        
        // Si non trouvé et email disponible, chercher par email
        if (!$user && !empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
            
            // Si trouvé par email, mettre à jour les infos sociales
            if ($user) {
                $user->update([
                    'social_provider' => $data['provider'],
                    'social_id' => $data['provider_id'],
                    'social_avatar' => $data['avatar'],
                ]);
            }
        }
        
        // Créer un nouvel utilisateur si non trouvé
        if (!$user) {
            $isNew = true;
            
            $user = User::create([
                'name' => $data['name'],
                // Certains providers (Facebook) peuvent ne pas retourner l'email selon permissions
                // On génère alors un email "technique" unique sur notre domaine.
                'email' => $data['email'] ?? ($data['provider'] . '_' . $data['provider_id'] . '@bantudelice.cg'),
                'password' => Hash::make(Str::random(32)), // Mot de passe aléatoire
                'phone' => null,
                'social_provider' => $data['provider'],
                'social_id' => $data['provider_id'],
                'social_avatar' => $data['avatar'],
                'email_verified_at' => $data['email_verified'] ? now() : null,
            ]);
            
            Log::info('Nouvel utilisateur créé via social auth', [
                'user_id' => $user->id,
                'provider' => $data['provider']
            ]);
        }
        
        // Générer un token d'authentification
        $token = Str::random(60);
        $user->update(['api_token' => hash('sha256', $token)]);
        
        return [
            'user' => $user,
            'token' => $token,
            'is_new' => $isNew,
        ];
    }
    
    /**
     * Lier un compte social à un utilisateur existant
     * 
     * @param User $user
     * @param string $provider
     * @param string $accessToken
     * @return User
     */
    public static function linkSocialAccount(User $user, string $provider, string $accessToken): User
    {
        $socialData = self::authenticateWithToken($provider, $accessToken);
        
        // Vérifier que le compte social n'est pas déjà lié à un autre utilisateur
        $existingUser = User::where('social_provider', $provider)
            ->where('social_id', $socialData['user']->social_id)
            ->where('id', '!=', $user->id)
            ->first();
        
        if ($existingUser) {
            throw new \RuntimeException('Ce compte ' . ucfirst($provider) . ' est déjà lié à un autre utilisateur');
        }
        
        $user->update([
            'social_provider' => $provider,
            'social_id' => $socialData['user']->social_id,
            'social_avatar' => $socialData['user']->social_avatar,
        ]);
        
        return $user->fresh();
    }
    
    /**
     * Dissocier un compte social
     * 
     * @param User $user
     * @return User
     */
    public static function unlinkSocialAccount(User $user): User
    {
        // Vérifier que l'utilisateur a un mot de passe configuré avant de dissocier
        if (empty($user->password) || $user->password === Hash::make('')) {
            throw new \RuntimeException('Veuillez configurer un mot de passe avant de dissocier votre compte social');
        }
        
        $user->update([
            'social_provider' => null,
            'social_id' => null,
            'social_avatar' => null,
        ]);
        
        return $user->fresh();
    }
}

