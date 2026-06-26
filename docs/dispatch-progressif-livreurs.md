# Dispatch progressif des livreurs

Le dispatch utilise désormais plusieurs rayons successifs au lieu de relancer indéfiniment la même recherche.

## Paliers par défaut

- round 1 : 5 km ;
- round 2 : 10 km ;
- round 3 : 20 km ;
- round 4 : 40 km.

Les livreurs déjà sollicités sont exclus des tours suivants. Le classement existant par score reste inchangé. Le nouveau mécanisme filtre les candidats selon le rayon du round, puis conserve le meilleur lot.

Lorsqu'aucun candidat n'est trouvé, le round suivant est planifié après le délai configuré. Au dernier palier, le mécanisme de dernier recours existant est conservé. Le même round n'est plus relancé sans fin.

Le broadcast est annulé si la livraison n'est plus en attente ou si la commande n'est plus dans un statut autorisant le dispatch. Les règles de paiement et la formule de score ne sont pas modifiées.

Les paramètres sont centralisés dans `config/food.php` : rayons, taille du lot, taille du vivier, fenêtre d'acceptation et délai avant élargissement.
