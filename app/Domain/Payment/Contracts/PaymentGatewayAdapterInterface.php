<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\ValueObjects\GatewayResult;
use App\Domain\Payment\ValueObjects\GatewayStatus;
use App\Payment;

/**
 * Seam unique entre PaymentService et un PSP.
 *
 * Chaque adapter implémente ce contrat. PaymentService n'appelle
 * jamais les APIs PSP directement — il passe
 * toujours par la factory puis par cet contrat.
 */
interface PaymentGatewayAdapterInterface
{
    /**
     * Identifiant du provider tel que stocké dans Payment::provider.
     * Exemples : 'momo', 'paypal', 'airtel', 'cash'.
     */
    public function provider(): string;

    /**
     * Initier un paiement auprès du PSP.
     *
     * @param Payment $payment  Enregistrement Payment déjà persisté (PENDING).
     * @param array   $context  Données de checkout : phone, fulfillment_mode, etc.
     */
    public function initiate(Payment $payment, array $context): GatewayResult;

    /**
     * Interroger le PSP pour connaître le statut actuel d'un paiement.
     *
     * Utilisé pour la réconciliation et le polling.
     */
    public function checkStatus(string $providerReference): GatewayStatus;

    /**
     * Interpréter un payload de callback/webhook reçu du PSP.
     *
     * Ne doit PAS écrire en base — renvoie uniquement le statut interprété.
     * C'est PaymentService qui décide de la persistence.
     */
    public function handleCallback(array $payload): GatewayStatus;

    /**
     * Vérifier la signature d'un webhook entrant.
     *
     * Retourne true si la signature est valide ou si la vérification
     * n'est pas implémentée (cas transitoire documenté dans l'adapter).
     */
    public function verifySignature(array $payload): bool;
}
