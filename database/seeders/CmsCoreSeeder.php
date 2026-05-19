<?php

namespace Database\Seeders;

use App\CmsContentField;
use App\CmsContentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CmsCoreSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('cms_content_types') || !Schema::hasTable('cms_content_fields')) {
            $this->command?->warn('Les tables CMS n\'existent pas encore. Exécutez d\'abord les migrations.');
            return;
        }

        foreach ($this->definitions() as $typeDefinition) {
            $type = CmsContentType::updateOrCreate(
                ['slug' => $typeDefinition['slug']],
                [
                    'name' => $typeDefinition['name'],
                    'description' => $typeDefinition['description'],
                    'is_active' => true,
                    'supports_revisions' => true,
                ]
            );

            foreach ($typeDefinition['fields'] as $index => $fieldDefinition) {
                CmsContentField::updateOrCreate(
                    ['key' => $fieldDefinition['key']],
                    [
                        'content_type_id' => $type->id,
                        'name' => $fieldDefinition['name'],
                        'field_type' => $fieldDefinition['field_type'],
                        'is_required' => $fieldDefinition['is_required'] ?? false,
                        'sort_order' => $fieldDefinition['sort_order'] ?? (($index + 1) * 10),
                        'default_value' => $fieldDefinition['default_value'] ?? null,
                        'help_text' => $fieldDefinition['help_text'] ?? null,
                        'options' => $fieldDefinition['options'] ?? null,
                    ]
                );
            }
        }

        $this->command?->info('CMS core: types de contenus et champs de base initialisés.');
    }

    private function definitions(): array
    {
        return [
            [
                'name' => 'Page',
                'slug' => 'page',
                'description' => 'Pages éditoriales statiques du site.',
                'fields' => [
                    [
                        'name' => 'Contenu principal',
                        'key' => 'page_body',
                        'field_type' => 'richtext',
                        'is_required' => true,
                        'help_text' => 'Contenu long de la page.',
                    ],
                    [
                        'name' => 'Image principale',
                        'key' => 'page_featured_image',
                        'field_type' => 'image',
                        'help_text' => 'Image d’illustration de la page.',
                    ],
                    [
                        'name' => 'Template',
                        'key' => 'page_template_key',
                        'field_type' => 'text',
                        'default_value' => 'default',
                        'help_text' => 'Identifiant de template ou variation d’affichage.',
                    ],
                    [
                        'name' => 'CTA principal libellé',
                        'key' => 'page_primary_cta_label',
                        'field_type' => 'text',
                    ],
                    [
                        'name' => 'CTA principal URL',
                        'key' => 'page_primary_cta_url',
                        'field_type' => 'url',
                    ],
                    [
                        'name' => 'Mettre en avant',
                        'key' => 'page_is_featured',
                        'field_type' => 'boolean',
                        'default_value' => '0',
                    ],
                ],
            ],
            [
                'name' => 'Actualité',
                'slug' => 'news',
                'description' => 'Actualités, annonces et publications éditoriales.',
                'fields' => [
                    [
                        'name' => 'Contenu principal',
                        'key' => 'news_body',
                        'field_type' => 'richtext',
                        'is_required' => true,
                    ],
                    [
                        'name' => 'Image principale',
                        'key' => 'news_featured_image',
                        'field_type' => 'image',
                    ],
                    [
                        'name' => 'Catégorie éditoriale',
                        'key' => 'news_category',
                        'field_type' => 'text',
                    ],
                    [
                        'name' => 'Date événementielle',
                        'key' => 'news_event_date',
                        'field_type' => 'date',
                    ],
                    [
                        'name' => 'Source externe',
                        'key' => 'news_source_url',
                        'field_type' => 'url',
                    ],
                    [
                        'name' => 'Mettre en avant',
                        'key' => 'news_is_featured',
                        'field_type' => 'boolean',
                        'default_value' => '0',
                    ],
                ],
            ],
            [
                'name' => 'Section d’accueil',
                'slug' => 'home_section',
                'description' => 'Sections éditoriales modulaires pour la page d’accueil.',
                'fields' => [
                    [
                        'name' => 'Clé de section',
                        'key' => 'home_section_key',
                        'field_type' => 'text',
                        'is_required' => true,
                        'help_text' => 'Ex: hero, services, popular_products, support.',
                    ],
                    [
                        'name' => 'Eyebrow / badge',
                        'key' => 'home_section_eyebrow',
                        'field_type' => 'text',
                    ],
                    [
                        'name' => 'Corps de section',
                        'key' => 'home_section_body',
                        'field_type' => 'textarea',
                    ],
                    [
                        'name' => 'CTA principal libellé',
                        'key' => 'home_section_primary_cta_label',
                        'field_type' => 'text',
                    ],
                    [
                        'name' => 'CTA principal URL',
                        'key' => 'home_section_primary_cta_url',
                        'field_type' => 'url',
                    ],
                    [
                        'name' => 'CTA secondaire libellé',
                        'key' => 'home_section_secondary_cta_label',
                        'field_type' => 'text',
                    ],
                    [
                        'name' => 'CTA secondaire URL',
                        'key' => 'home_section_secondary_cta_url',
                        'field_type' => 'url',
                    ],
                    [
                        'name' => 'Image principale',
                        'key' => 'home_section_image',
                        'field_type' => 'image',
                    ],
                    [
                        'name' => 'Image secondaire',
                        'key' => 'home_section_image_secondary',
                        'field_type' => 'image',
                    ],
                    [
                        'name' => 'Image tertiaire',
                        'key' => 'home_section_image_tertiary',
                        'field_type' => 'image',
                    ],
                    [
                        'name' => 'Ordre d’affichage',
                        'key' => 'home_section_sort_order',
                        'field_type' => 'number',
                        'default_value' => '0',
                    ],
                    [
                        'name' => 'Section active',
                        'key' => 'home_section_is_active',
                        'field_type' => 'boolean',
                        'default_value' => '1',
                    ],
                ],
            ],
            [
                'name' => 'FAQ',
                'slug' => 'faq',
                'description' => 'Questions fréquentes structurées.',
                'fields' => [
                    [
                        'name' => 'Question',
                        'key' => 'faq_question',
                        'field_type' => 'text',
                        'is_required' => true,
                    ],
                    [
                        'name' => 'Réponse',
                        'key' => 'faq_answer',
                        'field_type' => 'richtext',
                        'is_required' => true,
                    ],
                    [
                        'name' => 'Catégorie',
                        'key' => 'faq_category',
                        'field_type' => 'text',
                    ],
                    [
                        'name' => 'Ordre d’affichage',
                        'key' => 'faq_sort_order',
                        'field_type' => 'number',
                        'default_value' => '0',
                    ],
                    [
                        'name' => 'Mettre en avant',
                        'key' => 'faq_is_featured',
                        'field_type' => 'boolean',
                        'default_value' => '0',
                    ],
                ],
            ],
            [
                'name' => 'Bannière',
                'slug' => 'banner',
                'description' => 'Bannières marketing et CTA réutilisables.',
                'fields' => [
                    [
                        'name' => 'Eyebrow / badge',
                        'key' => 'banner_eyebrow',
                        'field_type' => 'text',
                    ],
                    [
                        'name' => 'Texte secondaire',
                        'key' => 'banner_body',
                        'field_type' => 'textarea',
                    ],
                    [
                        'name' => 'Image',
                        'key' => 'banner_image',
                        'field_type' => 'image',
                    ],
                    [
                        'name' => 'CTA libellé',
                        'key' => 'banner_cta_label',
                        'field_type' => 'text',
                    ],
                    [
                        'name' => 'CTA URL',
                        'key' => 'banner_cta_url',
                        'field_type' => 'url',
                    ],
                    [
                        'name' => 'Thème visuel',
                        'key' => 'banner_theme',
                        'field_type' => 'text',
                        'default_value' => 'primary',
                    ],
                    [
                        'name' => 'Bannière active',
                        'key' => 'banner_is_active',
                        'field_type' => 'boolean',
                        'default_value' => '1',
                    ],
                ],
            ],
            [
                'name' => 'Témoignage',
                'slug' => 'testimonial',
                'description' => 'Avis et témoignages éditoriaux.',
                'fields' => [
                    [
                        'name' => 'Citation',
                        'key' => 'testimonial_quote',
                        'field_type' => 'richtext',
                        'is_required' => true,
                    ],
                    [
                        'name' => 'Nom',
                        'key' => 'testimonial_person_name',
                        'field_type' => 'text',
                        'is_required' => true,
                    ],
                    [
                        'name' => 'Fonction',
                        'key' => 'testimonial_person_role',
                        'field_type' => 'text',
                    ],
                    [
                        'name' => 'Organisation',
                        'key' => 'testimonial_company',
                        'field_type' => 'text',
                    ],
                    [
                        'name' => 'Avatar',
                        'key' => 'testimonial_avatar',
                        'field_type' => 'image',
                    ],
                    [
                        'name' => 'Note',
                        'key' => 'testimonial_rating',
                        'field_type' => 'number',
                        'default_value' => '5',
                    ],
                ],
            ],
        ];
    }
}
