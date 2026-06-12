<?php

namespace App\Domain\Payment;

/**
 * Catalogue des codes d'erreur MTN MoMo et leurs messages lisibles.
 *
 * Source unique de vérité pour les deux directions :
 *  - paiements entrants  → MtnMomoAdapter::buildFailureMeta()
 *  - décaissements       → DisbursementService::buildFailureMetadata()
 *
 * Clé   : code reason retourné par l'API MTN (ex: 'NOT_ENOUGH_FUNDS')
 * Valeur: ['message' => string, 'action' => string]
 */
final class MtnErrorCatalog
{
    private const ENTRIES = [
        'NOT_ENOUGH_FUNDS' => [
            'message' => 'Le solde MTN MoMo du payeur est insuffisant.',
            'action'  => 'Demandez au client d\'approvisionner son compte avant de relancer le paiement.',
        ],
        'PAYER_NOT_FOUND' => [
            'message' => 'Le numéro MTN MoMo du payeur est introuvable ou inactif.',
            'action'  => 'Vérifiez le numéro et l\'activation du compte MTN MoMo du client.',
        ],
        'TRANSFER_TYPE_UNKNOWN' => [
            'message' => 'Le type de transfert MTN n\'est pas disponible.',
            'action'  => 'Vérifiez la configuration du type de transaction côté provider.',
        ],
        'TRANSACTION_NOT_FOUND' => [
            'message' => 'La transaction MTN est introuvable.',
            'action'  => 'Contrôlez la référence provider avant de relancer la vérification.',
        ],
        'PAYEE_NOT_ALLOWED_TO_RECEIVE' => [
            'message' => 'Le bénéficiaire ne peut pas recevoir de fonds.',
            'action'  => 'Vérifiez le statut du compte marchand MTN MoMo.',
        ],
        'SENDER_ACCOUNT_NOT_ACTIVE' => [
            'message' => 'Le compte MTN émetteur n\'est pas actif.',
            'action'  => 'Faites activer le service sur le compte MTN concerné.',
        ],
        'COULD_NOT_PERFORM_TRANSACTION' => [
            'message' => 'MTN MoMo n\'a pas pu finaliser la transaction.',
            'action'  => 'Demandez au client de confirmer sur son téléphone, puis réessayez si le problème persiste.',
        ],
        'PAYER_LIMIT_REACHED' => [
            'message' => 'Le payeur a atteint sa limite MTN MoMo.',
            'action'  => 'Le client doit réduire le montant ou réessayer après réinitialisation de sa limite.',
        ],
        'PAYEE_LIMIT_REACHED' => [
            'message' => 'Le bénéficiaire a atteint sa limite MTN MoMo.',
            'action'  => 'Réduisez le montant ou augmentez la limite du compte marchand.',
        ],
        'LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED' => [
            'message' => 'Le paiement MTN a échoué à cause du solde du payeur, d\'une limite du bénéficiaire ou d\'une restriction opérateur.',
            'action'  => 'Vérifiez le solde du client, les limites du compte marchand et les autorisations MTN avant de réessayer.',
        ],
        'RESOURCE_ALREADY_EXIST' => [
            'message' => 'La référence MTN existe déjà.',
            'action'  => 'Générez une nouvelle référence de paiement unique.',
        ],
        'RESOURCE_ALREADY_EXISTS' => [
            'message' => 'La référence MTN existe déjà.',
            'action'  => 'Générez une nouvelle référence de paiement unique.',
        ],
        'PAYEE_NOT_FOUND' => [
            'message' => 'Le bénéficiaire MTN est introuvable ou inactif.',
            'action'  => 'Vérifiez le compte marchand MTN.',
        ],
        'VALIDATION_ERROR' => [
            'message' => 'La requête MTN est invalide.',
            'action'  => 'Contrôlez les champs envoyés au provider avant un nouvel essai.',
        ],
    ];

    /** Retourne l'entrée du catalogue pour un code donné, ou null si inconnu. */
    public static function lookup(string $reasonCode): ?array
    {
        return self::ENTRIES[$reasonCode] ?? null;
    }

    /** Indique si un code est référencé dans le catalogue. */
    public static function has(string $reasonCode): bool
    {
        return isset(self::ENTRIES[$reasonCode]);
    }

    /** Retourne le tableau complet (pour les appelants qui en ont besoin, ex: tests). */
    public static function all(): array
    {
        return self::ENTRIES;
    }
}
