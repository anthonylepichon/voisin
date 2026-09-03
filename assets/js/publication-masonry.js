/*
 * Description générale :
 * Gestion de la disposition masonry des cartes de publication.
 * Rôle :
 * Calculer la place occupée par chaque carte dans les grilles de publications.
 * Tâches :
 * Mesurer les cartes après le chargement des images et lors du redimensionnement de la fenêtre.
 * Liens avec les autres fichiers :
 * Chargé par templates/base.html.twig et utilisé par les grilles portant l’attribut data-publication-masonry.
 */

/**
 * Rôle : Répartir les publications en masonry tout en conservant leur ordre chronologique dans le HTML.
 * Paramètres : Aucun.
 * Retour : Aucun.
 */
function initialiserGrillesMasonryPublications() {
    const grilles = document.querySelectorAll('[data-publication-masonry]');

    if (grilles.length === 0) {
        return;
    }

    grilles.forEach(function (grille) {
        const cartes = grille.querySelectorAll(':scope > .publication-card');

        /**
         * Rôle : Calculer le nombre de lignes nécessaire à chaque carte selon sa hauteur réelle.
         * Paramètres : Aucun.
         * Retour : Aucun.
         */
        function ajusterHauteurCartes() {
            const stylesGrille = window.getComputedStyle(grille);
            const hauteurLigne = Number.parseFloat(stylesGrille.gridAutoRows);
            const espaceEntreLignes = Number.parseFloat(stylesGrille.rowGap);
            const espaceVisuel = Number.parseFloat(stylesGrille.getPropertyValue('--masonry-gap'));

            cartes.forEach(function (carte) {
                carte.style.gridRowEnd = 'auto';

                const hauteurCarte = carte.getBoundingClientRect().height;
                const nombreLignes = Math.ceil((hauteurCarte + espaceVisuel) / (hauteurLigne + espaceEntreLignes));

                carte.style.gridRowEnd = 'span ' + nombreLignes;
            });
        }

        grille.querySelectorAll('img').forEach(function (image) {
            if (!image.complete) {
                image.addEventListener('load', ajusterHauteurCartes);
            }
        });

        window.addEventListener('resize', ajusterHauteurCartes);
        ajusterHauteurCartes();
    });
}

initialiserGrillesMasonryPublications();
