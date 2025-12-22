<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Clé FCM pour les notifications utilisateurs
     */
    const FCM_USER_KEY = 'AAAAGem_t_Q:APA91bHTc5fzslTffQuZgnw2bq9Wk8bG1eFZxbAvSvdC_64s5oJz245iRZolu56XgtHbh8KvrzOkFW_Gw0ZT3PIcSyLZL-nl9UGuw_MNBOJdzcwlt7k-Rvd8RPW8G7cNHeyyyYE8--1I';
    
    /**
     * Clé FCM pour les notifications restaurants
     */
    const FCM_RESTAURANT_KEY = 'AAAAU6vnK2I:APA91bH9FiIKziwh9o2eyWAb9sMmERkNpZWMqC1jMSD3dXQOdS45Fu7_x74N3ryYmv0U3fvOnlnXYYdLncGautnTziZFAbWB79rDHbdZVkHNOdkequvbPiey8u27b99-3NUtE_7LTzSu';
    
    /**
     * URL de l'API FCM
     */
    const FCM_URL = 'https://fcm.googleapis.com/fcm/send';
    
    /**
     * Envoyer une notification à un seul appareil
     * 
     * @param string $deviceToken
     * @param string $title
     * @param string $body
     * @param string $key
     * @param mixed $userId
     * @param string $type Type de notification (user|restaurant)
     * @return array
     */
    public static function sendToDevice($deviceToken, $title, $body, $key, $userId = null, $type = 'user')
    {
        $fcmKey = $type === 'restaurant' ? self::FCM_RESTAURANT_KEY : self::FCM_USER_KEY;
        
        $notification = [
            'title' => $title,
            'body' => $body,
            'sound' => true,
        ];
        
        $extraData = ['key' => $key];
        if ($userId) {
            $extraData['user_id'] = $userId;
        }
        
        $fcmNotification = [
            'to' => $deviceToken,
            'notification' => $notification,
            'data' => $extraData
        ];
        
        return self::sendFCMRequest($fcmNotification, $fcmKey);
    }
    
    /**
     * Envoyer une notification à plusieurs appareils
     * 
     * @param array $deviceTokens
     * @param string $title
     * @param string $body
     * @param string $key
     * @param mixed $userId
     * @param string $type Type de notification (user|restaurant)
     * @return array
     */
    public static function sendToMultipleDevices($deviceTokens, $title, $body, $key, $userId = null, $type = 'user')
    {
        $fcmKey = $type === 'restaurant' ? self::FCM_RESTAURANT_KEY : self::FCM_USER_KEY;
        
        $notification = [
            'title' => $title,
            'body' => $body,
            'sound' => true,
        ];
        
        $extraData = ['key' => $key];
        if ($userId) {
            $extraData['user_id'] = $userId;
        }
        
        $fcmNotification = [
            'registration_ids' => $deviceTokens,
            'notification' => $notification,
            'data' => $extraData
        ];
        
        return self::sendFCMRequest($fcmNotification, $fcmKey);
    }
    
    /**
     * Envoyer une notification avec action personnalisée
     * 
     * @param string $deviceToken
     * @param string $title
     * @param string $body
     * @param string $key
     * @param string $clickAction
     * @return array
     */
    public static function sendWithAction($deviceToken, $title, $body, $key, $clickAction)
    {
        $notification = [
            'title' => $title,
            'body' => $body,
            'sound' => true,
        ];
        
        $fcmNotification = [
            'to' => $deviceToken,
            'notification' => $notification,
            'data' => [
                'key' => $key,
                'click_action' => $clickAction
            ]
        ];
        
        return self::sendFCMRequest($fcmNotification, self::FCM_USER_KEY);
    }
    
    /**
     * Envoyer une notification à un utilisateur (via tous ses tokens)
     */
    public static function sendToUser($userId, $title, $body, array $extra = [])
    {
        $tokens = \App\UserToken::where('user_id', $userId)->pluck('device_tokens')->toArray();
        
        if (empty($tokens)) {
            Log::info("Aucun token trouvé pour l'utilisateur {$userId}");
            return ['success' => false, 'error' => 'no_tokens'];
        }

        return self::sendToMultipleDevices($tokens, $title, $body, $extra['type'] ?? 'general', $userId);
    }

    /**
     * Envoyer la requête FCM
     * 
     * @param array $payload
     * @param string $apiKey
     * @return array
     */
    private static function sendFCMRequest($payload, $apiKey)
    {
        $headers = [
            'Authorization: key=' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::FCM_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            Log::error('FCM Notification Error', [
                'error' => $error,
                'payload' => $payload
            ]);
            return [
                'success' => false,
                'error' => $error,
                'data' => null
            ];
        }
        
        $response = json_decode($result, true);
        
        Log::info('FCM Notification Sent', [
            'http_code' => $httpCode,
            'response' => $response,
            'payload' => $payload
        ]);
        
        return [
            'success' => $httpCode === 200,
            'data' => $response,
            'action' => $result
        ];
    }
}

