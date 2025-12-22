<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ExpandOrdersStatusEnum extends Migration
{
    public function up()
    {
        // En production (MySQL), la colonne orders.status est un ENUM sans "prepairing"
        // Or le code (restaurant) utilise le statut "prepairing" pour la cuisine.
        // On étend donc l'ENUM.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending','assign','prepairing','completed','cancelled','scheduled') NOT NULL DEFAULT 'pending'");
    }

    public function down()
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Retour à l'ENUM initial (attention: échoue si des lignes ont status='prepairing')
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending','assign','completed','cancelled','scheduled') NOT NULL DEFAULT 'pending'");
    }
}


