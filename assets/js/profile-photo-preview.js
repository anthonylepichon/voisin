/*
 * Description générale :
 * Gestion de l’aperçu local de la photo de profil.
 * Rôle :
 * Afficher la photo choisie avant l’envoi du formulaire d’inscription ou de profil.
 * Tâches :
 * Lire le fichier sélectionné, afficher son aperçu et son nom, puis réinitialiser l’aperçu en l’absence de fichier.
 * Liens avec les autres fichiers :
 * Chargé par templates/base.html.twig et utilisé par les formulaires contenant les attributs data-photo-profil-*.
 */

/**
 * Rôle : Afficher localement la photo choisie pendant l'inscription ou la modification du profil.
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
