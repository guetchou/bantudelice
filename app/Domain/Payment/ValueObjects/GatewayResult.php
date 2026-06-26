<?php

namespace App\Domain\Payment\ValueObjects;

/**
 * Résultat d'une initiation de paiement auprès d'un PSP.
 */
final class GatewayResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerReference,
        public readonly ?string $redirectUrl,
        public readonly array $meta,
        public readonly bool $isDemo,
        public readonly ?string $error = null,
    ) {}

    public static function success(
        string $providerReference,
        array $meta = [],
        ?string $redirectUrl = null,
        bool $isDemo = false,
    ): self {
        return new self(
            success: true,
            providerReference: $providerReference,
            redirectUrl: $redirectUrl,
            meta: $meta,
            isDemo: $isDemo,
        );
    }

    public static function demo(string $providerReference, array $meta = [], ?string $redirectUrl = null): self
    {
        // Un PSP non configuré ne doit jamais simuler un succès en production,
        // préproduction ou dans tout environnement autre que local/testing.
        if (! app()->environment(['local', 'testing'])) {
            return self::failure(
                'Le mode démonstration des paiements est désactivé dans cet environnement.',
                array_merge($meta, ['demo_blocked' => true])
            );
        }

        return new self(
            success: true,
            providerReference: $providerReference,
            redirectUrl: $redirectUrl,
            meta: array_merge($meta, ['demo' => true]),
            isDemo: true,
        );
    }

    public static function failure(string $error, array $meta = []): self
    {
        return new self(
            success: false,
            providerReference: null,
            redirectUrl: null,
            meta: $meta,
            isDemo: false,
            error: $error,
        );
    }

    /** Convertit en tableau pour compatibilité avec le code PaymentService existant. */
    public function toArray(): array
    {
        return [
            'provider_reference' => $this->providerReference,
            'meta'               => $this->meta,
            'redirect_url'       => $this->redirectUrl,
            'demo'               => $this->isDemo,
        ];
    }
}
