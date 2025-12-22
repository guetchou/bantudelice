<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class ImageUploadService
{
    /**
     * Uploader une image
     * 
     * @param UploadedFile $image
     * @param string $destination
     * @param string|null $oldImage
     * @return string Nom du fichier
     */
    public static function uploadImage(UploadedFile $image, $destination, $oldImage = null)
    {
        try {
            // Générer un nom de fichier unique
            $filename = strtolower(
                pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                . '-'
                . uniqid()
                . '.'
                . $image->getClientOriginalExtension()
            );
            
            // Nettoyer le nom du fichier
            $filename = str_replace(' ', '-', $filename);
            
            // Déplacer l'image
            $image->move($destination, $filename);
            
            // Supprimer l'ancienne image si elle existe
            if ($oldImage && file_exists($destination . '/' . $oldImage)) {
                @unlink($destination . '/' . $oldImage);
            }
            
            Log::info('Image uploaded successfully', [
                'filename' => $filename,
                'destination' => $destination
            ]);
            
            return $filename;
            
        } catch (Exception $e) {
            Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'destination' => $destination
            ]);
            throw $e;
        }
    }
    
    /**
     * Uploader une image de restaurant
     * 
     * @param UploadedFile $image
     * @param string $type Type d'image (logo|cover_image)
     * @param string|null $oldImage
     * @return string
     */
    public static function uploadRestaurantImage(UploadedFile $image, $type = 'logo', $oldImage = null)
    {
        $destination = 'images/restaurant_images';
        return self::uploadImage($image, $destination, $oldImage);
    }
    
    /**
     * Uploader une image de produit
     * 
     * @param UploadedFile $image
     * @param string|null $oldImage
     * @return string
     */
    public static function uploadProductImage(UploadedFile $image, $oldImage = null)
    {
        $destination = 'images/product_images';
        return self::uploadImage($image, $destination, $oldImage);
    }
    
    /**
     * Uploader une image de cuisine
     * 
     * @param UploadedFile $image
     * @param string|null $oldImage
     * @return string
     */
    public static function uploadCuisineImage(UploadedFile $image, $oldImage = null)
    {
        $destination = 'images/cuisine';
        return self::uploadImage($image, $destination, $oldImage);
    }
    
    /**
     * Uploader une image de profil
     * 
     * @param UploadedFile $image
     * @param string|null $oldImage
     * @return string
     */
    public static function uploadProfileImage(UploadedFile $image, $oldImage = null)
    {
        $destination = 'images/profile_images';
        return self::uploadImage($image, $destination, $oldImage);
    }
    
    /**
     * Supprimer une image
     * 
     * @param string $imagePath
     * @return bool
     */
    public static function deleteImage($imagePath)
    {
        try {
            if (file_exists($imagePath)) {
                @unlink($imagePath);
                Log::info('Image deleted', ['path' => $imagePath]);
                return true;
            }
            return false;
        } catch (Exception $e) {
            Log::error('Image deletion failed', [
                'error' => $e->getMessage(),
                'path' => $imagePath
            ]);
            return false;
        }
    }
    
    /**
     * Valider une image
     * 
     * @param UploadedFile $image
     * @param array $allowedMimes
     * @param int $maxSize En KB
     * @return array
     */
    public static function validateImage(UploadedFile $image, $allowedMimes = ['jpeg', 'png', 'jpg', 'gif'], $maxSize = 2048)
    {
        $errors = [];
        
        // Vérifier le type MIME
        if (!in_array(strtolower($image->getClientOriginalExtension()), $allowedMimes)) {
            $errors[] = 'Type de fichier non autorisé. Types autorisés: ' . implode(', ', $allowedMimes);
        }
        
        // Vérifier la taille
        if ($image->getSize() > $maxSize * 1024) {
            $errors[] = "La taille de l'image ne doit pas dépasser {$maxSize} KB";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

