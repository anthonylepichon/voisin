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
