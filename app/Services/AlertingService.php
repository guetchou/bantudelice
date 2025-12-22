<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\ConfigService;

/**
 * Service d'alerting (email/SMS)
 */
class AlertingService
{
    /**
     * Envoyer une alerte
     * 
     * @param string $type warning|error|info
     * @param string $message
     * @param array $context
     * @return void
     */
    public function sendAlert(string $type, string $message, array $context = []): void
    {
        // Logger l'alerte
        $this->logAlert($type, $message, $context);

        // Envoyer email si alerte critique
        if ($type === 'error' || ($type === 'warning' && ($context['severity'] ?? '') === 'high')) {
            $this->sendEmailAlert($type, $message, $context);
        }

        // TODO: Envoyer SMS si configuré
        // if (env('SMS_ALERTS_ENABLED', false)) {
        //     $this->sendSMSAlert($type, $message, $context);
        // }
    }

    /**
     * Logger l'alerte (logs structurés)
     * 
     * @param string $type
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logAlert(string $type, string $message, array $context): void
    {
        $logData = [
            'type' => 'alert',
            'alert_type' => $type,
            'message' => $message,
            'severity' => $context['severity'] ?? 'medium',
            'timestamp' => now()->toIso8601String(),
            'context' => $context,
        ];

        // Log structuré JSON
        Log::channel('daily')->info('ALERT', $logData);

        // Log standard aussi
        if ($type === 'error') {
            Log::error("ALERT [{$type}]: {$message}", $context);
        } elseif ($type === 'warning') {
            Log::warning("ALERT [{$type}]: {$message}", $context);
        } else {
            Log::info("ALERT [{$type}]: {$message}", $context);
        }
    }

    /**
     * Envoyer une alerte par email
     * 
     * @param string $type
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function sendEmailAlert(string $type, string $message, array $context): void
    {
        $adminEmail = ConfigService::getContactEmail();
        
        if (!$adminEmail) {
            Log::warning('Email admin non configuré, alerte non envoyée');
            return;
        }

        try {
            $subject = "[BantuDelice Alert] {$type}: " . substr($message, 0, 50);
            
            Mail::raw($this->formatAlertEmail($type, $message, $context), function ($mail) use ($adminEmail, $subject) {
                $mail->to($adminEmail)
                     ->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error('Erreur envoi email alerte', [
                'error' => $e->getMessage(),
                'alert_message' => $message
            ]);
        }
    }

    /**
     * Formater le contenu de l'email d'alerte
     * 
     * @param string $type
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function formatAlertEmail(string $type, string $message, array $context): string
    {
        $severity = $context['severity'] ?? 'medium';
        $timestamp = now()->format('Y-m-d H:i:s');
        
        $email = "Alerte BantuDelice\n";
        $email .= "==================\n\n";
        $email .= "Type: {$type}\n";
        $email .= "Sévérité: {$severity}\n";
        $email .= "Date: {$timestamp}\n\n";
        $email .= "Message:\n{$message}\n\n";
        
        if (!empty($context)) {
            $email .= "Contexte:\n";
            foreach ($context as $key => $value) {
                if (!in_array($key, ['severity'])) {
                    $email .= "  - {$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
                }
            }
        }
        
        $email .= "\n";
        $email .= "Dashboard: " . url('/admin/metrics') . "\n";
        
        return $email;
    }

    /**
     * Vérifier les alertes et les envoyer
     * 
     * @return array Alertes envoyées
     */
    public function checkAndSendAlerts(): array
    {
        $metricsService = new MetricsService();
        $alerts = $metricsService->getRealtimeMetrics()['alerts'] ?? [];
        
        $sentAlerts = [];
        
        foreach ($alerts as $alert) {
            $this->sendAlert(
                $alert['type'],
                $alert['message'],
                [
                    'severity' => $alert['severity'] ?? 'medium',
                    'count' => $alert['count'] ?? null,
                    'rate' => $alert['rate'] ?? null,
                ]
            );
            
            $sentAlerts[] = $alert;
        }
        
        return $sentAlerts;
    }
}

