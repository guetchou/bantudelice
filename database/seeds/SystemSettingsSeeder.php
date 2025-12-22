<?php

namespace Database\Seeds;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!Schema::hasTable('system_config')) {
            $this->command->warn('La table system_config n\'existe pas. Exécutez d\'abord la migration.');
            return;
        }
        
        $settings = [
            // Informations de l'entreprise
            [
                'key' => 'company_name',
                'value' => 'BantuDelice',
                'type' => 'string',
                'description' => 'Nom officiel de l\'entreprise',
            ],
            [
                'key' => 'noreply_email',
                'value' => 'noreply@bantudelice.cg',
                'type' => 'string',
                'description' => 'Email noreply pour tous les emails système',
            ],
            [
                'key' => 'admin_email',
                'value' => 'admin@bantudelice.cg',
                'type' => 'string',
                'description' => 'Email de l\'administrateur légal et officiel',
            ],
            [
                'key' => 'contact_email',
                'value' => 'contact@bantudelice.cg',
                'type' => 'string',
                'description' => 'Email de contact général',
            ],
            
            // Messages d'inscription
            [
                'key' => 'message_user_registration',
                'value' => 'Cher utilisateur, Votre inscription a été reçue avec succès. Nous vous remercions de votre confiance et vous souhaitons la bienvenue sur BantuDelice. Notre objectif est de traiter vos commandes avec le plus grand soin et de vous offrir un service client exceptionnel. N\'hésitez pas à nous faire part de vos retours et suggestions d\'amélioration. Merci de faire partie de la famille BantuDelice. Cordialement, L\'équipe BantuDelice',
                'type' => 'string',
                'description' => 'Message d\'inscription pour les utilisateurs',
            ],
            [
                'key' => 'message_driver_registration',
                'value' => 'Cher livreur, Votre demande d\'inscription a été reçue avec succès. Nous vous remercions de votre intérêt pour rejoindre l\'équipe BantuDelice. Notre équipe examinera votre candidature et vous contactera dans les plus brefs délais. Merci de vouloir faire partie de la famille BantuDelice. Cordialement, L\'équipe BantuDelice',
                'type' => 'string',
                'description' => 'Message d\'inscription pour les livreurs',
            ],
            [
                'key' => 'message_restaurant_registration',
                'value' => 'Cher partenaire, Votre demande d\'inscription a été reçue avec succès. Nous vous remercions de votre confiance et vous souhaitons la bienvenue sur BantuDelice. Notre équipe examinera votre demande et vous contactera dans les plus brefs délais. Merci de faire partie de la famille BantuDelice. Cordialement, L\'équipe BantuDelice',
                'type' => 'string',
                'description' => 'Message d\'inscription pour les restaurants',
            ],
            
            // Sujets d'emails
            [
                'key' => 'email_subject_registration',
                'value' => 'BantuDelice | Confirmation d\'inscription',
                'type' => 'string',
                'description' => 'Sujet de l\'email de confirmation d\'inscription',
            ],
            
            // Configuration HTTP
            [
                'key' => 'http_user_agent',
                'value' => 'BantuDelice/1.0 (contact@bantudelice.cg)',
                'type' => 'string',
                'description' => 'User-Agent pour les requêtes HTTP externes',
            ],
        ];
        
        foreach ($settings as $setting) {
            // Vérifier si la clé existe déjà
            $existing = DB::table('system_config')->where('key', $setting['key'])->first();
            
            if ($existing) {
                // Mettre à jour si elle existe
                DB::table('system_config')
                    ->where('key', $setting['key'])
                    ->update([
                        'value' => $setting['value'],
                        'type' => $setting['type'],
                        'description' => $setting['description'],
                        'updated_at' => now(),
                    ]);
                $this->command->info("✓ Mis à jour: {$setting['key']}");
            } else {
                // Insérer si elle n'existe pas
                DB::table('system_config')->insert([
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'description' => $setting['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("✓ Créé: {$setting['key']}");
            }
        }
        
        $this->command->info('Configuration système initialisée avec succès!');
    }
}
