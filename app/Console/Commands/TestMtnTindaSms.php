<?php

namespace App\Console\Commands;

use App\Services\MtnSmsService;
use Illuminate\Console\Command;

class TestMtnTindaSms extends Command
{
    protected $signature = 'sms:mtn-test
        {phone? : Numéro congolais au format 06..., 24206... ou +24206...}
        {--message=BantuDelice : test technique SMS MTN Tinda}
        {--status= : Interroger uniquement le statut d’un identifiant serveur}
        {--force : Confirmer explicitement l’envoi réel facturable}';

    protected $description = 'Teste l’envoi ou le statut d’un SMS réel via MTN Congo Tinda';

    public function handle(MtnSmsService $sms): int
    {
        if ($serverId = $this->option('status')) {
            $result = $sms->getStatus((string) $serverId);
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return ($result['success'] ?? false) ? self::SUCCESS : self::FAILURE;
        }

        $phone = (string) ($this->argument('phone') ?? '');
        if ($phone === '') {
            $this->error('Le numéro est obligatoire pour un envoi.');
            return self::INVALID;
        }

        if (!$this->option('force')) {
            $this->warn('Aucun SMS envoyé. Cette commande déclenche un envoi réel potentiellement facturable.');
            $this->line('Relancez avec --force après vérification du numéro, du sender et du token.');
            return self::INVALID;
        }

        if (!config('external-services.notifications.mtn_sms.enabled')) {
            $this->error('MTN_TINDA_ENABLED doit être défini à true.');
            return self::FAILURE;
        }

        $result = $sms->sendSms($phone, (string) $this->option('message'));
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return ($result['success'] ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
