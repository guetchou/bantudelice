<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$source = static function (string $relativePath) use ($root, &$failures): string {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $contents = @file_get_contents($path);

    if ($contents === false) {
        $failures[] = "Fichier introuvable : {$relativePath}";
        return '';
    }

    return $contents;
};

$contains = static function (string $haystack, string $needle, string $message) use (&$failures): void {
    if (! str_contains($haystack, $needle)) {
        $failures[] = $message;
    }
};

$notContains = static function (string $haystack, string $needle, string $message) use (&$failures): void {
    if (str_contains($haystack, $needle)) {
        $failures[] = $message;
    }
};

$routes = $source('routes/api.php');
$contains($routes, "Route::middleware('auth:driver_api')->group", 'Les routes sensibles livreur doivent être authentifiées.');
$contains($routes, 'driver/offers/{delivery}/accept', 'La route mobile d’acceptation des offres est absente.');

$apiController = $source('app/Http/Controllers/Api/DriverDeliveriesController.php');
$webController = $source('app/Http/Controllers/DriverDeliveriesController.php');
$contains($apiController, 'required|in:ARRIVED_AT_RESTAURANT,PICKED_UP,ON_THE_WAY,DELIVERED', 'L’API livreur autorise un statut interdit.');
$contains($webController, 'required|in:ARRIVED_AT_RESTAURANT,PICKED_UP,ON_THE_WAY,DELIVERED', 'Le portail livreur autorise un statut interdit.');
$notContains($apiController, "'customer_confirmed' => \$request", 'Le livreur peut encore fabriquer une confirmation client via API.');
$notContains($webController, "'customer_confirmed' => \$request", 'Le livreur peut encore fabriquer une confirmation client via le portail.');
$notContains($webController, "'delivery_otp_code' =>", 'Le code OTP est encore exposé au livreur.');

$proof = $source('app/Services/DeliveryProofService.php');
$contains($proof, 'Hash::make($code)', 'L’OTP de livraison n’est pas haché.');
$contains($proof, 'OTP_MAX_ATTEMPTS = 5', 'La limite de tentatives OTP est absente.');
$contains($proof, 'OTP_TTL_MINUTES = 30', 'La durée OTP attendue est absente.');
$contains($proof, "['ASSIGNED', 'PICKED_UP', 'ON_THE_WAY']", 'L’OTP peut être généré avant l’assignation du livreur.');

$deliveryService = $source('app/Services/SecureDeliveryService.php');
$dashboard = $source('app/Services/PartnerFinancialDashboardService.php');
$contains($deliveryService, 'OrderPaymentStatus::CASH_DUE->value', 'Une collecte cash échouée ne reste pas à encaisser.');
$contains($deliveryService, "\$collected ? 'PAID' : 'PENDING'", 'Le paiement cash n’est pas conservé en attente après échec.');
$contains($dashboard, "cash_collection_status', 'collected'", 'Le revenu livreur inclut du cash non confirmé.');
$notContains($dashboard, "orWhere('orders.payment_method', 'cash')", 'Le simple mode cash libère encore le revenu livreur.');

$dispatch = $source('app/Services/SecureDispatchService.php');
$geo = $source('app/Services/DeliveryDispatchService.php');
$offerJob = $source('app/Jobs/BroadcastDeliveryOfferJob.php');
$cashAcceptance = $source('app/Domain/Food/Services/WorkflowOrderAcceptanceService.php');
$onlinePayment = $source('app/Domain/Food/Listeners/FoodOrderPaymentConfirmed.php');

$contains($dispatch, "where('business_status', 'ready_for_pickup')", 'Le scheduler peut proposer une commande avant qu’elle soit prête.');
$contains($offerJob, "business_status !== 'ready_for_pickup'", 'Le job d’offre ne vérifie pas que la commande est prête.');
$contains($deliveryService, "business_status !== 'ready_for_pickup'", 'L’acceptation ne vérifie pas que la commande est prête.');
$contains($geo, "where('approved', true)", 'Le dispatch n’exclut pas les livreurs non approuvés.');
$contains($geo, 'locationFreshnessSeconds()', 'Le dispatch n’exige pas un GPS récent.');
$notContains($cashAcceptance, "enqueue_job('food', 'auto_assign_delivery'", 'Le flux cash dispatche encore dès l’entrée en cuisine.');
$notContains($onlinePayment, "enqueue_job('food', 'auto_assign_delivery'", 'Le flux en ligne dispatche encore dès l’entrée en cuisine.');

$auth = $source('app/Http/Controllers/api/DriverAuthController.php');
$contains($auth, "where('provider', 'drivers')", 'La révocation OAuth n’est pas limitée au provider drivers.');
$notContains($auth, "where('user_id', (string) \$driver->id)\n            ->update", 'La révocation OAuth globale par identifiant est encore présente.');

if ($failures !== []) {
    fwrite(STDERR, "Échec du contrôle workflow livreur :\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "Contrôle workflow livreur réussi.\n");
