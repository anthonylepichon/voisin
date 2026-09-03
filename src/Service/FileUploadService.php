<?php

/* Origine du code : Code créé par le développeur. */

/*
 * Description générale : Service central de gestion des fichiers téléversés par les membres.
 * Rôle : Éviter la duplication du déplacement, du nommage, de la suppression et de la résolution des fichiers.
 * Tâches : Déduire le répertoire du type, enregistrer les images, préparer leurs métadonnées Doctrine, compenser les échecs et journaliser les suppressions physiques.
 * Liens avec les autres fichiers : Utilise UploadFichier, Utilisateur, Publication et les contrôleurs de médias.
 */

namespace App\Service;

use App\Entity\Publication;
use App\Entity\UploadFichier;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    private const CHEMIN_PROFILS = 'uploads/profils';
    private const CHEMIN_PUBLICATIONS = 'uploads/publications';

    /**
     * Rôle : Initialiser les dépendances nécessaires à la gestion centralisée des fichiers.
     * Paramètres : Le slugger, Doctrine, le noyau Symfony et le journal applicatif.
     * Retour : Aucun.
     */
    public function __construct(
        private SluggerInterface $slugger,
        private EntityManagerInterface $entityManager,
        private KernelInterface $kernel,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Rôle : Enregistrer une photo de profil et ses métadonnées.
     * Paramètres : Le fichier transmis et l'utilisateur propriétaire.
     * Retour : Les métadonnées créées ou null en cas d'échec.
     */
    public function televerserPhotoProfil(UploadedFile $fichier, Utilisateur $utilisateur): ?UploadFichier
    {
        $baseNom = 'profil-'.(string) $utilisateur->getPseudonyme();
        $upload = $this->televerser($fichier, UploadFichier::TYPE_PROFIL, $baseNom);

        if (null === $upload) {
            return null;
        }

        $utilisateur->setUploadFichier($upload);
        $this->entityManager->persist($upload);

        return $upload;
    }

    /**
     * Rôle : Enregistrer une image de publication et ses métadonnées.
     * Paramètres : Le fichier transmis et la publication propriétaire.
     * Retour : Les métadonnées créées ou null en cas d'échec.
     */
    public function televerserImagePublication(UploadedFile $fichier, Publication $publication): ?UploadFichier
    {
        $baseNom = pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
        $upload = $this->televerser($fichier, UploadFichier::TYPE_PUBLICATION, $baseNom);

        if (null === $upload) {
            return null;
        }

        $publication->setUploadFichier($upload);
        $this->entityManager->persist($upload);

        return $upload;
    }

    /**
     * Rôle : Préparer la suppression Doctrine d'une ancienne photo sans toucher au fichier physique.
     * Paramètres : L'utilisateur propriétaire et les métadonnées du fichier à supprimer.
     * Retour : Aucun.
     */
    public function preparerSuppressionPhotoProfil(Utilisateur $utilisateur, UploadFichier $upload): void
    {
        if ($utilisateur->getUploadFichier() === $upload) {
            return;
        }

        $this->entityManager->remove($upload);
    }

    /**
     * Rôle : Détacher une ancienne image de publication et préparer la suppression de ses métadonnées.
     * Paramètres : La publication propriétaire et les métadonnées du fichier à supprimer.
     * Retour : Aucun.
     */
    public function preparerSuppressionImagePublication(Publication $publication, UploadFichier $upload): void
    {
        if ($publication->getUploadFichier() === $upload) {
            $publication->setUploadFichier(null);
        }

        $this->entityManager->remove($upload);
    }

    /**
     * Rôle : Supprimer le fichier créé par un téléversement dont la sauvegarde Doctrine a échoué.
     * Paramètres : Les métadonnées du nouveau fichier à compenser.
     * Retour : Vrai si le fichier est absent ou correctement supprimé.
     */
    public function compenserTeleversement(UploadFichier $upload): bool
    {
        $suppressionReussie = $this->supprimerFichierPhysique($upload);

        if (!$suppressionReussie) {
            $this->logger->critical('Le fichier d’un téléversement annulé n’a pas pu être supprimé.', [
                'type' => $upload->getType(),
                'nom' => $upload->getNom(),
            ]);
        }

        return $suppressionReussie;
    }

    /**
     * Rôle : Supprimer physiquement un fichier après la réussite de la sauvegarde Doctrine.
     * Paramètres : Les métadonnées du fichier devenu inutile.
     * Retour : Vrai si le fichier est absent ou correctement supprimé.
     */
    public function supprimerFichierPhysique(UploadFichier $upload): bool
    {
        $type = (string) $upload->getType();
        $nom = (string) $upload->getNom();
        $cheminRelatif = $this->determinerCheminParType($type);
        $chemin = $this->obtenirCheminFichier($type, $nom);

        if (!is_file($chemin)) {
            return true;
        }

        if (!@unlink($chemin)) {
            $this->logger->error('Un fichier devenu inutile n’a pas pu être supprimé.', [
                'type' => $type,
                'nom' => $nom,
                'chemin' => $cheminRelatif,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Rôle : Construire le chemin sûr d'un fichier à partir de son type métier.
     * Paramètres : Le type et le nom sécurisé du fichier.
     * Retour : Le chemin absolu attendu.
     */
    public function obtenirCheminFichier(string $type, string $nom): string
    {
        if (basename($nom) !== $nom) {
            return $this->kernel->getProjectDir().'/uploads/fichier-invalide';
        }

        $chemin = $this->determinerCheminParType($type);

        return $this->kernel->getProjectDir().'/'.$chemin.'/'.$nom;
    }

    /**
     * Rôle : Déplacer un fichier validé et créer ses métadonnées communes.
     * Paramètres : Le fichier, son type et la base de son nom.
     * Retour : Les métadonnées non rattachées ou null en cas d'échec.
     */
    private function televerser(UploadedFile $fichier, string $type, string $baseNom): ?UploadFichier
    {
        $chemin = $this->determinerCheminParType($type);
        $extension = $fichier->guessExtension();

        if (null === $extension) {
            return null;
        }

        $nomSecurise = (string) $this->slugger->slug($baseNom)->lower();

        if ('' === $nomSecurise) {
            $nomSecurise = 'image';
        }

        $nom = sprintf('%s-%s.%s', uniqid(), $nomSecurise, $extension);

        try {
            $fichier->move($this->kernel->getProjectDir().'/'.$chemin, $nom);
        } catch (FileException $exception) {
            $this->logger->error('Le déplacement d’un fichier téléversé a échoué.', [
                'type' => $type,
                'chemin' => $chemin,
                'exception' => $exception,
            ]);

            return null;
        }

        return (new UploadFichier())
            ->setType($type)
            ->setNom($nom)
            ->setChemin($chemin);
    }

    /**
     * Rôle : Déterminer l'unique répertoire autorisé pour un type de fichier.
     * Paramètres : Le type métier du fichier.
     * Retour : Le répertoire relatif correspondant au type.
     */
    private function determinerCheminParType(string $type): string
    {
        if (UploadFichier::TYPE_PROFIL === $type) {
            return self::CHEMIN_PROFILS;
        }

        if (UploadFichier::TYPE_PUBLICATION === $type) {
            return self::CHEMIN_PUBLICATIONS;
        }

        throw new \InvalidArgumentException('Le type de fichier est invalide.');
    }

}
