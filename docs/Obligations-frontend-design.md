Construis une interface front-end professionnelle, compacte et alignée.

Contraintes obligatoires :
- Ne pas créer une interface marketing sauf si je demande une landing page.
- Utiliser une hiérarchie claire : titre, résumé court, action principale, contenu.
- Limiter la largeur des contenus avec un container.
- Ne jamais étirer inutilement les boutons, inputs ou cartes.
- Les boutons sont en largeur automatique par défaut.
- Les formulaires simples doivent être limités à 640 px maximum.
- Les KPI doivent être alignés dans une grille régulière avec même hauteur.
- Les graphiques doivent avoir une hauteur homogène.
- Les tableaux doivent gérer overflow, pagination, recherche, filtre, tri.
- Prévoir les états : loading, empty, error, success, disabled.
- Prévoir mobile, tablette, desktop.
- Prévoir les textes longs avec truncate ou line-clamp.
- Utiliser un design system : couleurs, spacing, radius, typographie, variantes.
- Éviter les textes marketing longs.
- Prioriser l’usage métier, la lisibilité et la densité.
- Chaque section doit avoir un rôle clair.
- Les actions secondaires doivent aller dans dropdown, drawer ou menu contextuel.
- Les détails doivent s’ouvrir dans drawer/modal/page dédiée selon complexité.

Crée un dashboard métier compact.

Structure attendue :
- Header compact avec titre, période, action principale.
- Sidebar fixe ou navigation latérale.
- Grille KPI alignée : 4 colonnes desktop, 2 tablette, 1 mobile.
- Cartes KPI de même hauteur.
- Zone graphiques : deux cartes de même hauteur.
- Table principale avec recherche, filtres, tri, pagination.
- Drawer latéral pour détails d’une ligne.
- Toast pour succès.
- Alert pour erreur critique.
- Empty state si aucune donnée.
- Skeleton pendant chargement.
- Pas de texte marketing.
- Pas de cartes trop grandes.
- Pas de boutons pleine largeur sur desktop.
- Pas de débordement horizontal sauf table contrôlée.

Crée un formulaire professionnel.

Contraintes :
- Largeur maximale 640 px pour formulaire simple.
- Largeur maximale 900 px pour formulaire complexe.
- Labels visibles.
- Champs alignés sur une grille.
- Champs courts sur la même ligne quand logique : prénom/nom, ville/pays, date/statut.
- Messages d’erreur sous chaque champ.
- Boutons en bas : Annuler à gauche ou secondaire, Enregistrer à droite ou principal.
- Bouton principal non pleine largeur sur desktop.
- Pleine largeur seulement sur mobile si nécessaire.
- Prévoir required, disabled, loading, success, error, dirty state.
- Ne pas remplir la page avec du texte marketing.

Checklist finale avant de valider une interface
Question	Oui / Non
Les cartes sont-elles alignées ?	
Les boutons sont-ils trop larges ?	
Les champs sont-ils trop longs ?	
Le container limite-t-il bien la largeur ?	
Les KPI ont-ils la même hauteur ?	
Les graphiques ont-ils une hauteur cohérente ?	
La table gère-t-elle le débordement ?	
Le mobile est-il prévu ?	
Les textes longs sont-ils contrôlés ?	
Les états loading/error/empty existent-ils ?	
Les actions secondaires sont-elles cachées ?	
L’interface est-elle métier ou trop marketing ?	
La page a-t-elle une action principale claire ?	
Les espacements sont-ils réguliers ?	
La sidebar/header/layout tiennent-ils ensemble ?	
15. La règle mère

Pour tes applications métiers, sites, dashboards et assistants IA, impose toujours cette logique :

1. Structure stable
2. Largeurs contrôlées
3. Grille régulière
4. Composants réutilisables
5. États réels prévus
6. Texte limité
7. Actions hiérarchisées
8. Responsive sérieux
9. Pas de marketing inutile
10. Pas de débordement

La phrase à retenir :

L’IA ne doit pas seulement “dessiner une belle page”.
Elle doit respecter un système de contraintes.

C’est ce système de contraintes qui évite les boutons énormes, les cartes désalignées, les formulaires trop larges, les dashboards désorganisés et les interfaces qui débordent.

Aujourd’hui, le concept utile à apprendre est celui-ci :

Le front-end moderne ne doit plus être seulement “responsive par écran”, mais “responsive par composant”.

Avant, on faisait souvent :

@media (max-width: 768px) {
  .card { ... }
}

Problème : on adapte la carte à la taille de l’écran, pas à la taille réelle de son espace. Une carte peut être dans une sidebar étroite sur un grand écran, ou dans une grande zone sur mobile. Le vrai contexte, c’est le conteneur, pas toujours le viewport.

Les CSS Container Queries permettent justement de styliser un composant selon la taille de son parent, et non selon la largeur globale de l’écran. C’est une approche officiellement documentée par MDN.

Principe design graphique

Un bon design n’est pas d’abord une question de beauté. C’est une question de hiérarchie visuelle.

L’utilisateur doit comprendre en moins de 3 secondes :

Quel est le titre ?
Quelle est l’information importante ?
Quelle action il doit faire ?
Quelle information est secondaire ?

Nielsen Norman Group rappelle qu’un bon design visuel repose notamment sur une grille claire, une hiérarchie visible, une couleur utilisée avec intention et une cohérence générale.
une carte propre dépend rarement seulement de width. Elle dépend aussi de padding, gap, border, shadow, radius, max-width, etc.

Frontend 

Grande famille	Élément / notion	Cas d’utilisation	Exemple concret
Structure de page	layout	Organiser toute l’interface	Page avec header, sidebar, contenu et footer
Structure de page	header	Afficher l’identité et les accès principaux	Logo, recherche, bouton connexion
Structure de page	navbar	Naviguer entre les grandes rubriques	Accueil, Services, Contact
Structure de page	sidebar / barre latérale	Naviguer dans une application métier	Dashboard, Clients, Commandes, Factures
Structure de page	main	Contenir le contenu principal	Tableau de bord ou liste des clients
Structure de page	section	Découper une page en blocs logiques	Section hero, services, témoignages
Structure de page	footer	Afficher les informations secondaires	Adresse, mentions légales, réseaux sociaux
Structure de page	container	Limiter la largeur du contenu	Contenu centré à 1200 px
Structure de page	wrapper	Envelopper un bloc pour gérer le style	Bloc autour d’une grille de cartes
Dimensions	width	Gérer la largeur	Carte à 320 px ou width: 100%
Dimensions	height	Gérer la hauteur	Header de 72 px
Dimensions	min-width	Empêcher un élément d’être trop petit	Bouton minimum 120 px
Dimensions	max-width	Empêcher un élément d’être trop large	Texte limité à 700 px
Espacement	padding	Espace intérieur	24 px dans une carte
Espacement	margin	Espace extérieur	32 px entre deux sections
Espacement	gap	Espace entre éléments	16 px entre cartes
Forme	border-radius	Arrondir les coins	Carte avec coins arrondis
Forme	border	Délimiter un élément	Bordure légère sur un input
Profondeur	box-shadow	Créer un effet de relief	Carte légèrement surélevée
Débordement	overflow	Gérer ce qui dépasse	Scroll dans un tableau
Superposition	z-index	Contrôler l’ordre visuel	Modal au-dessus de la page
Positionnement	sticky	Garder visible au scroll	Header fixe en haut
Positionnement	fixed	Fixer à l’écran	Bouton WhatsApp flottant
Layout	flex	Aligner simplement des éléments	Logo à gauche, menu à droite
Layout	grid	Organiser des blocs complexes	Dashboard avec cartes KPI
Layout	columns	Organiser en colonnes	Texte institutionnel en 2 colonnes
Layout	stack	Empiler verticalement	Titre, texte, bouton
Layout	Alignement	Rendre l’interface propre	Titres alignés sur la même grille
Layout	Grille visuelle	Donner une structure régulière	3 cartes par ligne sur desktop
Responsive	Breakpoint	Changer l’affichage selon l’écran	Mobile, tablette, desktop
Responsive	Viewport	Taille visible de l’écran	Largeur réelle du navigateur
Responsive	Mobile first	Concevoir d’abord pour mobile	Une colonne par défaut
Responsive	Desktop first	Concevoir d’abord pour grand écran	Dashboard dense
Responsive	Container query	Adapter selon la taille du composant	Carte différente selon son conteneur
Responsive	Fluid typography	Texte qui s’adapte	Titre avec taille dynamique
Mobile	Safe area	Éviter les zones système mobile	Encoche iPhone / Android
Mobile	Thumb zone	Zone atteignable par le pouce	Boutons importants en bas
Mobile	Touch target	Taille confortable au toucher	Bouton de 44 px minimum
Mobile	Keyboard overlay	Gérer le clavier mobile	Input non caché par le clavier
Mobile	Bottom navigation	Menu mobile en bas	Accueil, Commandes, Profil
Mobile	Bottom sheet	Panneau qui monte depuis le bas	Options rapides sur mobile
Mobile	PWA	Application web installable	Icône sur écran d’accueil
Composant UI	card / carte	Présenter une information isolée	Carte produit, service ou KPI
Composant UI	Bouton	Déclencher une action	Enregistrer, Valider, Supprimer
Composant UI	Input	Saisir une donnée	Nom, téléphone, email
Composant UI	Select	Choisir une option	Ville, statut, catégorie
Composant UI	Checkbox	Choix multiple	Sélectionner plusieurs options
Composant UI	Radio	Choix unique	Paiement cash ou mobile money
Composant UI	Table	Afficher des données structurées	Liste des clients
Composant UI	Badge	Afficher un statut court	Payé, En attente, Annulé
Composant UI	Avatar	Identifier un utilisateur	Photo ou initiales
Composant UI	Tooltip	Aide courte	Explication au survol d’une icône
Composant UI	Toast	Message temporaire	“Commande enregistrée”
Composant UI	Breadcrumb	Fil d’Ariane	Accueil > Clients > Détail
Composant UI	Pagination	Naviguer entre pages	Page 1, 2, 3
Composant UI	Search bar	Rechercher	Recherche client par nom
Composant UI	Filter	Filtrer des données	Commandes livrées uniquement
Composant UI	Sort	Trier les données	Trier par date ou montant
Formulaire	Label	Nom clair du champ	“Téléphone”
Formulaire	Placeholder	Exemple de saisie	“Ex : 06 000 00 00”
Formulaire	Helper text	Aide à la saisie	“Format accepté : PDF”
Formulaire	Validation client	Contrôler avant envoi	Email invalide
Formulaire	Validation serveur	Contrôle réel côté backend	Téléphone déjà utilisé
Formulaire	Message d’erreur	Expliquer le problème	“Ce champ est obligatoire”
Formulaire	Required field	Champ obligatoire	Astérisque ou mention obligatoire
Formulaire	Input mask	Forcer un format	Téléphone, montant, date
Formulaire	Auto-complete	Accélérer la saisie	Suggestion d’adresse
Formulaire	Multi-step form	Diviser un long formulaire	Étape identité, documents, validation
Formulaire	Conditional fields	Afficher selon choix	Champ société si “professionnel”
Formulaire	File upload	Envoyer un fichier	Facture, pièce jointe, image
Formulaire	Autosave	Sauvegarde automatique	Brouillon de formulaire
Formulaire	Dirty state	Détecter les changements	“Modifications non enregistrées”
États UI	Normal	État standard	Bouton disponible
États UI	Hover	Réaction au survol	Carte qui monte légèrement
États UI	Active	Réaction au clic	Bouton qui s’enfonce
États UI	Focus	Navigation clavier	Contour visible sur input
États UI	Disabled	Action indisponible	Bouton grisé
États UI	Loading	Chargement	Spinner sur bouton
États UI	Skeleton	Chargement visuel	Faux blocs gris avant données
États UI	Success	Action réussie	“Enregistrement effectué”
États UI	Error	Problème	“Impossible de charger”
États UI	Empty state	Aucune donnée	“Aucune commande trouvée”
États UI	Offline	Pas de connexion	“Vous êtes hors connexion”
États UI	Unauthorized	Accès refusé	Page 403
Feedback	Loader	Montrer que le système travaille	Chargement d’une liste
Feedback	Spinner	Chargement court	Connexion en cours
Feedback	Progress bar	Progression mesurable	Upload à 70 %
Feedback	Toast animé	Confirmation temporaire	“Facture envoyée”
Feedback	Alert	Information importante	“Votre session expire bientôt”
Feedback	Confirm dialog	Sécuriser une action sensible	Confirmation suppression
Feedback	Retry	Relancer une action échouée	Bouton “Réessayer”
Animations	Blink / clignotement	Attirer l’attention, à limiter	Badge “Nouveau” qui clignote
Animations	Pulse	Montrer un état vivant	Point vert “en ligne”
Animations	Shimmer	Effet de chargement	Reflet sur skeleton loader
Animations	Transition	Changement doux	Bouton qui change au hover
Animations	Fade	Apparition/disparition douce	Modal qui apparaît
Animations	Slide	Glissement	Drawer qui sort de la droite
Animations	Scale	Agrandissement léger	Modal qui s’ouvre avec zoom discret
Animations	Ripple	Onde au clic/tap	Bouton mobile façon Material Design
Animations	Error shake	Signaler une erreur	Champ mot de passe qui tremble
Animations	Success checkmark	Confirmer visuellement	Coche animée après paiement
Animations	Count animation	Animer un chiffre	KPI qui passe de 0 à 125
Animations	Chart animation	Apparition graphique	Barres qui montent au chargement
Animations	Scroll reveal	Apparition au scroll	Section qui apparaît en descendant
Animations	Parallax	Effet de profondeur	Image de fond qui bouge lentement
Animations	Typing indicator	Montrer qu’on écrit	Trois points dans un chat
Animations	Live indicator	Montrer une activité temps réel	“Appel en cours” avec point animé
Animations	Auto-refresh indicator	Montrer la synchronisation	Icône sync qui tourne
Animations	Confetti	Célébration rare	Paiement réussi
Interaction	Click	Action souris	Cliquer sur “Valider”
Interaction	Tap	Action tactile	Appuyer sur mobile
Interaction	Long press	Pression longue	Options rapides mobile
Interaction	Swipe	Glissement tactile	Supprimer une carte
Interaction	Drag and drop	Déplacer	Kanban ou upload fichier
Interaction	Scroll	Défilement	Page longue
Interaction	Inner scroll	Scroll dans un bloc	Tableau dans dashboard
Interaction	Infinite scroll	Chargement continu	Fil d’actualité
Interaction	Virtual scroll	Optimiser longues listes	10 000 lignes affichées proprement
Interaction	Scroll lock	Bloquer le fond	Modal ouverte
Interaction	Keyboard navigation	Naviguer au clavier	Tabulation dans formulaire
Interaction	Focus trap	Garder le focus dans un overlay	Modal accessible
Affichage conditionnel	Progressive disclosure	Ne pas tout afficher au départ	Détails visibles seulement au clic
Affichage conditionnel	Conditional rendering	Afficher selon l’état	Bouton visible seulement si autorisé
Affichage conditionnel	Modal	Formulaire court sans quitter la page	“Ajouter client” ouvre une fenêtre
Affichage conditionnel	Drawer	Détail latéral	Clic sur commande ouvre fiche à droite
Affichage conditionnel	Popover	Petit bloc contextuel	Infos rapides sur un statut
Affichage conditionnel	Dropdown	Actions secondaires	Menu “Plus d’actions”
Affichage conditionnel	Accordion	Contenu long masqué	FAQ, historique, détails techniques
Affichage conditionnel	Tabs	Séparer sans changer de page	Profil, Sécurité, Facturation
Affichage conditionnel	Expand row	Déplier une ligne	Détail d’une facture dans un tableau
Affichage conditionnel	Inline form	Modifier directement	Modifier un nom dans une ligne
Affichage conditionnel	Command palette	Chercher une action	Ctrl+K pour ouvrir une commande
Affichage conditionnel	Floating action button	Action rapide principale	Bouton “+” pour créer
Affichage conditionnel	Context menu	Menu contextuel	Clic droit ou long press
Navigation	Route	URL d’une page	/dashboard/clients
Navigation	Protected route	Page protégée	Dashboard réservé aux admins
Navigation	Redirect	Redirection automatique	Non connecté vers login
Navigation	Active link	Montrer la page active	Menu “Commandes” sélectionné
Navigation	404	Page introuvable	Mauvaise URL
Navigation	403	Accès interdit	Agent sur page admin
Navigation	500	Erreur serveur	Page d’erreur propre
Navigation	Stepper	Guider un processus	Panier > Paiement > Confirmation
Navigation	Mega menu	Grand menu structuré	Site institutionnel ou e-commerce
Design graphique	Hiérarchie visuelle	Guider l’œil	Titre plus fort que description
Design graphique	Typographie	Lisibilité et style	H1, H2, paragraphe, label
Design graphique	Contraste	Lecture confortable	Texte sombre sur fond clair
Design graphique	Couleur	Identité et priorité	Bleu pour confiance, orange pour action
Design graphique	Alignement	Propreté visuelle	Blocs alignés sur une grille
Design graphique	Espace blanc	Respiration	Marges généreuses entre sections
Design graphique	Icônes	Repères rapides	Icône téléphone pour contact
Design graphique	Images	Contexte et crédibilité	Hero image d’un service
Design graphique	Ombres	Profondeur	Carte légèrement surélevée
Design graphique	Cohérence	Uniformité	Même style de boutons partout
Design graphique	Charte graphique	Identité de marque	Couleurs, logo, typographie
Design system	Design system	Règles globales d’interface	Boutons, cartes, formulaires normalisés
Design system	UI kit	Composants réutilisables	Button, Card, Modal, Table
Design system	Design tokens	Valeurs centralisées	Couleurs, espacements, radius
Design system	Variables CSS	Éviter les valeurs dispersées	--color-primary
Design system	Thème clair/sombre	Adapter l’apparence	Light mode / dark mode
Design system	Variantes	Plusieurs formes d’un composant	Primary, secondary, danger
Données	KPI card	Indicateur clé	Commandes du jour
Données	Chart	Visualiser une tendance	Courbe des ventes
Données	Table	Données détaillées	Liste des paiements
Données	Filter bar	Filtrer une liste	Date, statut, ville
Données	Date range picker	Choisir une période	Du 1er au 31 mai
Données	Search debounce	Limiter les appels API	Recherche après 300 ms
Données	Drill-down	Voir le détail	Clic sur KPI pour analyse
Données	Drawer detail	Voir une fiche sans quitter la page	Fiche client latérale
Données	Activity log	Historique	Qui a modifié quoi
Données	Export	Sortir les données	PDF, Excel, CSV
API	Query	Lire les données	Charger les clients
API	Mutation	Modifier les données	Créer une commande
API	Cache	Éviter les rechargements inutiles	Garder les clients en mémoire
API	Optimistic UI	Afficher avant confirmation serveur	Marquer payé immédiatement
API	Timeout	Gérer serveur lent	Message après délai dépassé
API	Retry	Réessayer	Relancer une requête échouée
API	Sync pending	Synchronisation en attente	Données non encore envoyées
API	Conflict	Conflit de modification	Deux utilisateurs modifient la même fiche
API	Error boundary	Éviter écran blanc	Page d’erreur propre
Permissions	Auth state	Connecté / non connecté	Afficher login ou dashboard
Permissions	Role-based UI	Interface selon rôle	Admin, agent, client
Permissions	Permission check	Contrôler les actions	Peut supprimer ou non
Permissions	Hidden action	Masquer action interdite	Bouton suppression absent
Permissions	Disabled action	Bloquer avec explication	Bouton grisé avec tooltip
Sécurité	XSS	Éviter injection script	Nettoyer les entrées utilisateur
Sécurité	CSRF	Protéger les formulaires	Token CSRF
Sécurité	Secure cookies	Protéger la session	Cookie HTTPOnly/Secure
Sécurité	Session timeout	Expiration de session	Demande de reconnexion
Sécurité	Données sensibles	Ne pas exposer les secrets	Ne jamais afficher token ou mot de passe
Accessibilité	HTML sémantique	Structure lisible par machines	button, nav, main, section
Accessibilité	ARIA	Aider lecteurs d’écran	aria-label sur icône
Accessibilité	Focus visible	Navigation clavier	Contour sur bouton/input
Accessibilité	Skip link	Aller au contenu principal	“Passer au contenu”
Accessibilité	Alt image	Décrire les images	Description d’une image
Accessibilité	Reduced motion	Réduire les animations	Respect des préférences utilisateur
Accessibilité	Contraste suffisant	Lisibilité	Texte lisible sur fond
Accessibilité	Screen reader text	Texte pour lecteur d’écran	Libellé invisible mais accessible
Performance	Lazy loading	Charger seulement quand nécessaire	Images chargées au scroll
Performance	Image optimization	Réduire le poids	WebP, compression
Performance	Code splitting	Charger par morceaux	Dashboard chargé séparément
Performance	Cache	Accélérer l’interface	Données déjà disponibles
Performance	Debounce	Limiter les actions répétées	Recherche utilisateur
Performance	Throttle	Limiter les événements fréquents	Scroll ou resize
Performance	Font loading	Charger les polices proprement	Éviter texte invisible
Performance	Animation légère	Garder la fluidité	Transition courte
Performance	INP	Mesurer la réactivité	Clic qui répond vite
SEO	Title tag	Titre navigateur/Google	“Entreprise - Services”
SEO	Meta description	Résumé de la page	Description visible dans Google
SEO	Open Graph	Aperçu réseaux sociaux	Image sur WhatsApp/LinkedIn
SEO	Canonical URL	Éviter les doublons	URL officielle
SEO	Sitemap	Aider l’indexation	Liste des pages
SEO	Robots.txt	Contrôler les robots	Autoriser ou bloquer
SEO	H1/H2/H3	Structure du contenu	Un H1 par page
SEO	Structured data	Données enrichies	Organisation, adresse, service
Internationalisation	Langue	Interface multilingue	Français / Anglais
Internationalisation	Locale	Format local	Congo-Brazzaville
Internationalisation	Devise	Affichage monétaire	FCFA, €, USD
Internationalisation	Date	Format de date	28/05/2026
Internationalisation	Heure	Fuseau horaire	Africa/Brazzaville
Internationalisation	Pluralisation	Singulier/pluriel	1 commande / 2 commandes
Internationalisation	Fallback language	Langue de secours	Français si anglais absent
Qualité	Unit test	Tester une fonction	Calcul total commande
Qualité	Component test	Tester un composant	Bouton, formulaire
Qualité	Integration test	Tester plusieurs blocs	Formulaire + API
Qualité	E2E test	Tester un parcours complet	Connexion > commande > paiement
Qualité	Visual regression	Détecter casse visuelle	Comparaison captures
Qualité	Responsive test	Vérifier les écrans	Mobile, tablette, desktop
Qualité	Browser test	Vérifier navigateurs	Chrome, Firefox, Safari
Qualité	Accessibility test	Vérifier accessibilité	Contraste, clavier, ARIA
Production	Build	Compiler l’application	Vite, React, Next
Production	Déploiement	Mettre en ligne	VPS, Vercel, Docker
Production	Monitoring	Surveiller les erreurs	Logs, alertes
Production	Analytics	Comprendre l’usage	Clics, pages vues, conversions
Handoff	Maquette Figma	Source design	Écran dashboard
Handoff	Specs	Mesures précises	Padding 24 px, gap 16 px
Handoff	Assets export	Fichiers graphiques	Logo SVG, images WebP
Handoff	Composants nommés	Faciliter le développement	ButtonPrimary, CardKPI
Handoff	États documentés	Prévoir les cas réels	Hover, loading, error
Handoff	Responsive documenté	Clarifier les versions	Mobile, tablette, desktop
Handoff	Checklist QA	Vérifier avant livraison	Mobile, erreurs, accessibilité
Règle de décision rapide
Besoin	Élément recommandé
Petite action courte	Modal
Détail sans quitter la page	Drawer
Formulaire long ou important	Page dédiée
Actions secondaires	Dropdown
Information courte	Tooltip ou popover
Contenu long secondaire	Accordion
Confirmation d’action	Modal de confirmation
Message temporaire	Toast
Chargement de données	Skeleton loader
Chargement court	Spinner
Progression mesurable	Progress bar
Action principale mobile	Floating action button
Options rapides mobile	Bottom sheet
Réduire surcharge	Divulgation progressive
Interface selon rôle	Affichage conditionnel
Très longue liste	Virtual scroll
Données tabulaires	Table + filtres + pagination
Interface professionnelle	Design system + composants réutilisables
Formule complète à retenir
Couche	Question à poser
Structure	Où sont les grandes zones de la page ?
Layout	Comment les blocs sont-ils organisés ?
Design graphique	Qu’est-ce que l’œil voit en premier ?
Composants	Quels éléments sont réutilisables ?
États	Que se passe-t-il si ça charge, échoue ou reste vide ?
Interaction	Comment l’utilisateur clique, touche, scrolle ou saisit ?
Affichage conditionnel	Qu’est-ce qu’on montre maintenant, plus tard ou jamais ?
Animation	Le mouvement aide-t-il à comprendre ?
Responsive	Est-ce propre sur mobile, tablette et desktop ?
Données	Comment les informations arrivent, se filtrent et s’affichent ?
Permissions	Qui peut voir ou faire quoi ?
Accessibilité	Tout le monde peut-il utiliser l’interface ?
Performance	Est-ce rapide, fluide et léger ?
Sécurité	Les données sensibles sont-elles protégées ?
Production	Est-ce testable, maintenable et surveillé ?
La phrase clé :
Une bonne interface ne montre pas tout.
Elle montre l’essentiel, puis révèle le détail au bon moment.

