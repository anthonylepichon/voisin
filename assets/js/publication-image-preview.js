/*
 * Description générale :
 * Gestion de l’aperçu local de l’image d’une publication.
 * Rôle :
 * Afficher ou retirer l’image choisie dans le formulaire autonome d’une publication.
 * Tâches :
 * Lire le fichier sélectionné, afficher son aperçu et permettre sa suppression avant l’envoi du formulaire.
 * Liens avec les autres fichiers :
 * Chargé par templates/base.html.twig et utilisé par templates/publication/_form.html.twig.
 */

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
