<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class EnvConfigService
{
    /**
     * Mettre à jour plusieurs variables d'environnement dans .env
     *
     * @param array $variables ['KEY' => 'value', ...]
     * @return array
     */
    public static function updateEnvVariables(array $variables): array
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            return [
                'success' => false,
                'message' => 'Fichier .env introuvable',
            ];
        }
        
        try {
            // Créer une sauvegarde
            $backupPath = storage_path('app/env_backups/.env.backup.' . date('Y-m-d_H-i-s') . '.txt');
            $backupDir = dirname($backupPath);
            
            if (!File::isDirectory($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }
            
            File::copy($envPath, $backupPath);
            
            // Lire le contenu
            $envContent = File::get($envPath);
            $updated = [];
            
            foreach ($variables as $key => $value) {
                // Normaliser la valeur (supprimer les guillemets si présents)
                $cleanValue = trim($value ?? '', ' "\'');
                
                // Échapper les caractères spéciaux pour la regex
                $escapedKey = preg_quote($key, '/');
                
                // Pattern pour trouver la ligne (peut être commentée ou non)
                $pattern = "/^(\s*#?\s*){$escapedKey}\s*=\s*.*$/m";
                
                if (preg_match($pattern, $envContent)) {
                    // Remplacer la ligne existante
                    $envContent = preg_replace(
                        $pattern,
                        "{$key}={$cleanValue}",
                        $envContent
                    );
                    $updated[] = $key;
                } else {
                    // Ajouter à la fin
                    $envContent .= "\n{$key}={$cleanValue}";
                    $updated[] = $key;
                }
            }
            
            // Sauvegarder
            File::put($envPath, $envContent);
            
            Log::info('Variables .env mises à jour', [
                'updated' => $updated,
                'backup' => $backupPath,
            ]);
            
            return [
                'success' => true,
                'message' => count($updated) . ' variable(s) mise(s) à jour',
                'updated' => $updated,
                'backup' => $backupPath,
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du .env', [
                'error' => $e->getMessage(),
                'variables' => array_keys($variables),
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Obtenir une variable d'environnement depuis .env
     *
     * @param string $key
     * @return string|null
     */
    public static function getEnvValue(string $key): ?string
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            return null;
        }
        
        $envContent = File::get($envPath);
        
        // Chercher la ligne (peut être commentée ou non)
        if (preg_match("/^(?:#\s*)?{$key}\s*=\s*(.+)$/m", $envContent, $matches)) {
            $value = trim($matches[1]);
            // Supprimer les guillemets
            $value = trim($value, ' "\'');
            return $value === '' ? null : $value;
        }
        
        return null;
    }
    
    /**
     * Obtenir plusieurs variables d'environnement
     *
     * @param array $keys
     * @return array
     */
    public static function getEnvValues(array $keys): array
    {
        $values = [];
        
        foreach ($keys as $key) {
            $values[$key] = self::getEnvValue($key);
        }
        
        return $values;
    }
    
    /**
     * Masquer partiellement une valeur sensible
     *
     * @param string|null $value
     * @return string
     */
    public static function maskValue(?string $value): string
    {
        if (empty($value)) {
            return '';
        }
        
        $length = strlen($value);
        
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        
        // Afficher les 4 premiers et 4 derniers caractères
        $visible = substr($value, 0, 4) . str_repeat('*', max(0, $length - 8)) . substr($value, -4);
        
        return $visible;
    }
}

