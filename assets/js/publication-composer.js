/*
 * Origine du code : Code créé par le développeur.
 * Description générale :
 * Gestion du formulaire de création de publication intégré au fil d’actualité.
 * Rôle :
 * Piloter l’aperçu d’image et l’activation du bouton de publication.
 * Tâches :
 * Contrôler la présence du contenu, de l’image et de la visibilité, afficher l’aperçu et permettre son retrait.
 * Liens avec les autres fichiers :
 * Chargé par templates/base.html.twig et utilisé par templates/feed/index.html.twig.
 */

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
