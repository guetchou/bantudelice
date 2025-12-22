@component('mail::message')
# Suivi de votre envoi

Bonjour {{ $shipment->customer->name }},

Le statut de votre colis **{{ $shipment->tracking_number }}** a été mis à jour.

**Nouveau statut :** {{ $shipment->status->label() }}

@component('mail::button', ['url' => route('colis.show', $shipment->id)])
Voir les détails
@endcomponent

Vous pouvez suivre votre colis en temps réel sur notre plateforme.

Merci de faire confiance à **BantuDelice**,  
L'équipe de livraison.
@endcomponent

