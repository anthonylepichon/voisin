/*
 * Description générale :
 * Point d'entrée du JavaScript classique de l'application.
 * Rôle :
 * Regrouper les futurs scripts servis par AssetMapper.
 * Tâches :
 * Accueillir uniquement les comportements JavaScript validés pendant le codage.
 * Liens avec les autres fichiers :
 * Chargé par templates/base.html.twig au moyen de la fonction asset().
 */

/**
 * Rôle : Afficher localement la photo choisie pendant l'inscription.
 * Paramètres : Aucun.
 * Retour : Aucun.
 */
function initialiserApercuPhotoProfil() {
    const input = document.querySelector('[data-photo-profil-input]');
    const apercu = document.querySelector('[data-photo-profil-preview]');
    const image = document.querySelector('[data-photo-profil-image]');
    const nom = document.querySelector('[data-photo-profil-name]');

    if (!input || !apercu || !image || !nom) {
        return;
    }

    input.addEventListener('change', function () {
        const fichier = input.files[0];

        if (!fichier) {
            apercu.hidden = true;
            image.removeAttribute('src');
            nom.textContent = '';

            return;
        }

        image.src = URL.createObjectURL(fichier);
        nom.textContent = fichier.name;
        apercu.hidden = false;
    });
}

initialiserApercuPhotoProfil();

/**
 * Rôle : Afficher localement l'image choisie pour une publication.
 * Paramètres : Aucun.
 * Retour : Aucun.
 */
function initialiserApercuImagePublication() {
    const input = document.querySelector('[data-publication-image-input]');
    const apercu = document.querySelector('[data-publication-image-preview]');
    const image = document.querySelector('[data-publication-image-element]');
    const nom = document.querySelector('[data-publication-image-name]');
    const boutonRetrait = document.querySelector('[data-publication-image-remove]');

    if (!input || !apercu || !image || !nom || !boutonRetrait) {
        return;
    }

    input.addEventListener('change', function () {
        const fichier = input.files[0];

        if (!fichier) {
            apercu.hidden = true;
            image.removeAttribute('src');
            nom.textContent = '';

            return;
        }

        image.src = URL.createObjectURL(fichier);
        nom.textContent = fichier.name;
        apercu.hidden = false;
    });

    boutonRetrait.addEventListener('click', function () {
        input.value = '';
        apercu.hidden = true;
        image.removeAttribute('src');
        nom.textContent = '';
    });
}

initialiserApercuImagePublication();

/**
 * Rôle : Rendre interactif le formulaire de création intégré au fil d’actualité.
 * Paramètres : Aucun.
 * Retour : Aucun.
 */
function initialiserCreationPublicationDansFil() {
    const formulaire = document.querySelector('[data-publication-composer]');

    if (!formulaire) {
        return;
    }

    const contenu = formulaire.querySelector('[data-composer-text]');
    const imageInput = formulaire.querySelector('[data-composer-image]');
    const visibilite = formulaire.querySelector('[data-composer-visibility]');
    const apercu = formulaire.querySelector('[data-composer-preview]');
    const imageApercu = formulaire.querySelector('[data-composer-preview-image]');
    const boutonRetrait = formulaire.querySelector('[data-composer-remove]');
    const boutonPublication = formulaire.querySelector('[data-composer-submit]');
    let adresseApercu = null;

    if (!contenu || !imageInput || !visibilite || !apercu || !imageApercu || !boutonRetrait || !boutonPublication) {
        return;
    }

    /**
     * Rôle : Activer le bouton uniquement lorsque le contenu et la visibilité sont valides côté interface.
     * Paramètres : Aucun.
     * Retour : Aucun.
     */
    function actualiserBoutonPublication() {
        const textePresent = contenu.value.trim() !== '';
        const imagePresente = imageInput.files.length > 0;
        const visibiliteChoisie = visibilite.value !== '';

        boutonPublication.disabled = !((textePresent || imagePresente) && visibiliteChoisie);
    }

    /**
     * Rôle : Masquer l’aperçu et libérer son adresse temporaire.
     * Paramètres : Aucun.
     * Retour : Aucun.
     */
    function masquerApercu() {
        if (adresseApercu !== null) {
            URL.revokeObjectURL(adresseApercu);
            adresseApercu = null;
        }

        imageApercu.removeAttribute('src');
        apercu.hidden = true;
        boutonRetrait.hidden = true;
    }

    contenu.addEventListener('input', actualiserBoutonPublication);
    visibilite.addEventListener('change', actualiserBoutonPublication);

    imageInput.addEventListener('change', function () {
        masquerApercu();

        if (imageInput.files.length === 0) {
            actualiserBoutonPublication();

            return;
        }

        adresseApercu = URL.createObjectURL(imageInput.files[0]);
        imageApercu.src = adresseApercu;
        apercu.hidden = false;
        boutonRetrait.hidden = false;
        actualiserBoutonPublication();
    });

    boutonRetrait.addEventListener('click', function () {
        imageInput.value = '';
        masquerApercu();
        actualiserBoutonPublication();
    });

    actualiserBoutonPublication();
}

initialiserCreationPublicationDansFil();

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
