<?php

namespace App\Services;

use App\CmsContent;
use App\CmsContentType;

class CmsStaticPageService
{
    public function getPage(string $slug): ?CmsContent
    {
        $this->ensureDefaultPagesExist();

        $type = $this->pageType();
        if (!$type) {
            return null;
        }

        return CmsContent::query()
            ->with(['values.field'])
            ->where('content_type_id', $type->id)
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->first();
    }

    public function ensureDefaultPagesExist(): void
    {
        $type = $this->pageType();
        if (!$type) {
            return;
        }

        foreach ($this->defaultPages() as $pageDefinition) {
            $content = CmsContent::query()
                ->where('content_type_id', $type->id)
                ->where('slug', $pageDefinition['slug'])
                ->first();

            if ($content) {
                continue;
            }

            $this->cmsContentService()->create(
                $type,
                [
                    'title' => $pageDefinition['title'],
                    'slug' => $pageDefinition['slug'],
                    'status' => 'published',
                    'excerpt' => $pageDefinition['excerpt'],
                    'layout' => 'cms_page',
                    'seo_title' => $pageDefinition['title'] . ' | Plateforme',
                    'seo_description' => $pageDefinition['excerpt'],
                ],
                $this->fieldValues($type, $pageDefinition),
                null
            );
        }
    }

    public function body(?CmsContent $content): ?string
    {
        return $this->fieldValue($content, 'page_body');
    }

    public function featuredImage(?CmsContent $content): ?string
    {
        return $this->fieldValue($content, 'page_featured_image');
    }

    public function primaryCtaLabel(?CmsContent $content): ?string
    {
        return $this->fieldValue($content, 'page_primary_cta_label');
    }

    public function primaryCtaUrl(?CmsContent $content): ?string
    {
        return $this->fieldValue($content, 'page_primary_cta_url');
    }

    private function fieldValues(CmsContentType $type, array $pageDefinition): array
    {
        $fields = $type->fields->keyBy('key');
        $map = [];

        foreach ([
            'page_body' => $pageDefinition['body'] ?? null,
            'page_primary_cta_label' => $pageDefinition['cta_label'] ?? null,
            'page_primary_cta_url' => $pageDefinition['cta_url'] ?? null,
            'page_template_key' => 'cms_page',
            'page_is_featured' => 0,
        ] as $key => $value) {
            $field = $fields->get($key);
            if ($field) {
                $map[$field->id] = $value;
            }
        }

        return $map;
    }

    private function fieldValue(?CmsContent $content, string $fieldKey): ?string
    {
        if (!$content) {
            return null;
        }

        $content->loadMissing('values.field');
        $value = $content->values->first(function ($item) use ($fieldKey) {
            return optional($item->field)->key === $fieldKey;
        });

        return $value?->value;
    }

    private function pageType(): ?CmsContentType
    {
        return CmsContentType::query()
            ->with('fields')
            ->where('slug', 'page')
            ->where('is_active', true)
            ->first();
    }

    private function cmsContentService(): CmsContentService
    {
        return app(CmsContentService::class);
    }

    private function defaultPages(): array
    {
        return [
            [
                'slug' => 'about-us',
                'title' => 'À propos de BantuDelice',
                'excerpt' => 'BantuDelice connecte les Congolais aux meilleurs restaurants de leur quartier pour une livraison rapide à domicile.',
                'cta_label' => 'Nous contacter',
                'cta_url' => route('contact.us'),
                'body' => <<<'HTML'
<h2>Notre histoire</h2>
<p>Dans un monde où chaque minute compte, BantuDelice a été créé pour connecter les habitants de Brazzaville et Pointe-Noire aux meilleurs restaurants de leur quartier. Commander un repas savoureux ne devrait pas être compliqué.</p>
<h3>Notre mission</h3>
<p>Livrer des repas frais et de qualité, rapidement et de façon fiable, directement à votre porte. Nous travaillons chaque jour pour soutenir les restaurants locaux et offrir à chaque client une expérience simple et satisfaisante.</p>
<h3>Notre vision</h3>
<p>Devenir la référence de la livraison de repas au Congo, en mettant la technologie au service des commerces locaux et des consommateurs congolais.</p>
<h3>Ce que nous proposons</h3>
<ul>
  <li>Commande de repas en ligne auprès des meilleurs restaurants.</li>
  <li>Livraison rapide à domicile ou au bureau.</li>
  <li>Retrait sur place avec code de confirmation.</li>
  <li>Suivi en temps réel de votre commande.</li>
</ul>
HTML,
            ],
            [
                'slug' => 'faq',
                'title' => 'Questions fréquentes',
                'excerpt' => 'Retrouvez les réponses essentielles sur les commandes, la livraison, le paiement et votre compte.',
                'cta_label' => 'Contacter le support',
                'cta_url' => route('contact.us'),
                'body' => <<<'HTML'
<h2>Commandes</h2>
<p>Découvrez comment commander, modifier ou annuler une commande selon son état d’avancement.</p>
<ul>
  <li>Passer une commande depuis un restaurant ou un plat.</li>
  <li>Suivre l’avancement en temps réel.</li>
  <li>Contacter le support en cas de besoin.</li>
</ul>
<h2>Livraison</h2>
<p>Consultez les délais, les zones couvertes et les options de remise ou de retrait disponibles.</p>
<h2>Paiement</h2>
<p>Consultez les moyens de paiement disponibles, les remboursements et les confirmations de paiement.</p>
<h2>Compte</h2>
<p>Retrouvez les informations sur votre profil, vos adresses, la sécurité de votre compte et vos préférences.</p>
HTML,
            ],
            [
                'slug' => 'help',
                'title' => "Centre d'aide",
                'excerpt' => 'Un point d’entrée unique pour trouver de l’aide sur les commandes, la livraison, le paiement et le compte.',
                'cta_label' => 'Voir la FAQ',
                'cta_url' => route('faq'),
                'body' => <<<'HTML'
<h2>Besoin d’aide rapidement ?</h2>
<p>Le centre d’aide regroupe les réponses utiles pour commander, payer, suivre une livraison et gérer votre compte.</p>
<h3>Commandes</h3>
<p>Apprenez à passer une commande, la modifier, l’annuler ou obtenir une assistance après achat.</p>
<h3>Livraison</h3>
<p>Consultez les délais, le suivi en direct et les solutions en cas de retard ou de difficulté de remise.</p>
<h3>Paiement</h3>
<p>Retrouvez les moyens de paiement, les confirmations et les cas de remboursement.</p>
<h3>Compte</h3>
<p>Gérez votre profil, vos informations de contact et vos préférences de communication.</p>
HTML,
            ],
            [
                'slug' => 'offers',
                'title' => 'Promotions et offres',
                'excerpt' => 'Consultez les avantages actuellement proposés et les conditions pour en bénéficier.',
                'cta_label' => 'Commander maintenant',
                'cta_url' => route('home'),
                'body' => <<<'HTML'
<h2>Offres du moment</h2>
<p>Retrouvez les promotions en cours sur les commandes, les livraisons et les avantages fidélité.</p>
<ul>
  <li>Réductions de bienvenue.</li>
  <li>Livraison offerte selon seuil.</li>
  <li>Offres week-end et fidélité.</li>
</ul>
<h2>Conditions d’application</h2>
<p>Chaque offre peut avoir des conditions spécifiques de montant, de date, de zone ou de moyen de paiement. Vérifiez les détails avant validation.</p>
<h2>Besoin d’une précision ?</h2>
<p>Contactez l’équipe si vous avez un doute sur l’éligibilité d’une promotion ou sur un code promo.</p>
HTML,
            ],
            [
                'slug' => 'terms-and-conditions',
                'title' => 'Conditions générales',
                'excerpt' => 'Consultez les conditions générales applicables aux services proposés par la plateforme.',
                'cta_label' => 'Politique de confidentialité',
                'cta_url' => route('privacy.policy'),
                'body' => <<<'HTML'
<h2>Objet</h2>
<p>Les présentes conditions encadrent l’utilisation des services proposés par la plateforme, notamment la commande de repas, la livraison, le colis et le transport.</p>
<h2>Utilisation des services</h2>
<p>L’utilisateur s’engage à fournir des informations exactes, à respecter les règles de la plateforme et à utiliser les services conformément aux lois applicables.</p>
<h2>Commandes et paiements</h2>
<p>Les commandes sont soumises à disponibilité, validation partenaire et confirmation du paiement selon le mode choisi.</p>
<h2>Responsabilités</h2>
<p>La plateforme agit comme espace d’orchestration et de mise en relation, avec des obligations variables selon le service utilisé.</p>
HTML,
            ],
            [
                'slug' => 'return-policy',
                'title' => 'Politique de remboursement',
                'excerpt' => 'Consultez les règles de remboursement, d’annulation et de traitement des incidents.',
                'cta_label' => 'Centre d’aide',
                'cta_url' => route('help'),
                'body' => <<<'HTML'
<h2>Annulation et remboursement</h2>
<p>Les remboursements dépendent du type de service, du statut de la commande et de la cause de l’incident.</p>
<h2>Commandes repas</h2>
<p>Une annulation avant préparation peut ouvrir droit à remboursement. Après préparation, une analyse support peut être nécessaire.</p>
<h2>Colis et transport</h2>
<p>Les remboursements peuvent dépendre de l’état de prise en charge, du déplacement engagé ou de la preuve de remise.</p>
<h2>Support</h2>
<p>En cas de litige, contactez le support avec votre numéro de commande, d’envoi ou de réservation.</p>
HTML,
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Politique de confidentialité',
                'excerpt' => 'Informations sur la collecte, l’usage et la protection des données personnelles sur la plateforme.',
                'cta_label' => 'Mentions légales',
                'cta_url' => route('legal.notices'),
                'body' => <<<'HTML'
<h2>Données collectées</h2>
<p>La plateforme collecte les informations nécessaires à l’exécution des services, à la sécurité des comptes et à l’assistance utilisateur.</p>
<h2>Utilisation des données</h2>
<p>Les données servent au traitement des commandes, à la livraison, au support, à la facturation et à l’amélioration du service.</p>
<h2>Sécurité</h2>
<p>La plateforme met en œuvre des mesures techniques et organisationnelles pour protéger les données sensibles et limiter les accès non autorisés.</p>
<h2>Contact</h2>
<p>Pour toute demande liée à la confidentialité ou à l’exercice de vos droits, utilisez les canaux officiels de contact.</p>
HTML,
            ],
            [
                'slug' => 'mentions-legales',
                'title' => 'Mentions légales',
                'excerpt' => 'Informations légales relatives à l’éditeur, à l’hébergement et à l’exploitation de la plateforme.',
                'cta_label' => 'Conditions générales',
                'cta_url' => route('terms.conditions'),
                'body' => <<<'HTML'
<h2>Éditeur</h2>
<p>La plateforme assure la coordination des services présentés sur ce site.</p>
<h2>Hébergement</h2>
<p>Le service est hébergé sur une infrastructure sécurisée adaptée aux besoins de disponibilité, de sauvegarde et de supervision.</p>
<h2>Propriété intellectuelle</h2>
<p>Les contenus, éléments visuels, logos et composants du site restent protégés selon les règles applicables.</p>
<h2>Contact</h2>
<p>Pour toute demande administrative ou légale, utilisez les coordonnées publiées sur la plateforme.</p>
HTML,
            ],
            [
                'slug' => 'politique-cookies',
                'title' => 'Politique de cookies',
                'excerpt' => 'Informations sur l’utilisation des cookies et technologies similaires sur la plateforme.',
                'cta_label' => 'Politique de confidentialité',
                'cta_url' => route('privacy.policy'),
                'body' => <<<'HTML'
<h2>Pourquoi des cookies ?</h2>
<p>Les cookies permettent de maintenir les sessions, mémoriser certaines préférences, améliorer l’expérience et mesurer l’usage du service.</p>
<h2>Types de cookies</h2>
<p>Des cookies techniques, fonctionnels et éventuellement analytiques peuvent être utilisés selon la configuration active du site.</p>
<h2>Gestion des préférences</h2>
<p>Vous pouvez gérer les cookies via les paramètres de votre navigateur ou les outils de consentement proposés lorsque disponibles.</p>
HTML,
            ],
        ];
    }
}
