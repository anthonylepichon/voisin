<?php

/*
 * Description générale : Service central de gestion des fichiers téléversés par les membres.
 * Rôle : Éviter la duplication du déplacement, du nommage, de la suppression et de la résolution des fichiers.
 * Tâches : Enregistrer les photos et images, créer leurs métadonnées Doctrine, retirer les anciens fichiers et fournir un chemin sûr.
 * Liens avec les autres fichiers : Utilise UploadFichier, Utilisateur, Publication, UploadFichierRepository et les contrôleurs de médias.
 */

namespace App\Service;

use App\Entity\Publication;
use App\Entity\UploadFichier;
use App\Entity\Utilisateur;
use App\Repository\UploadFichierRepository;
use Doctrine\ORM\EntityManagerInterface;
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
     * Paramètres : Le slugger, Doctrine, le dépôt des fichiers et le noyau Symfony.
     * Retour : Aucun.
     */
    public function __construct(
        private SluggerInterface $slugger,
        private EntityManagerInterface $entityManager,
        private UploadFichierRepository $uploadFichierRepository,
        private KernelInterface $kernel
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
        $upload = $this->televerser($fichier, UploadFichier::TYPE_PROFIL, self::CHEMIN_PROFILS, $baseNom);

        if (null === $upload) {
            return null;
        }

        $upload->setUtilisateur($utilisateur);
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
        $upload = $this->televerser($fichier, UploadFichier::TYPE_PUBLICATION, self::CHEMIN_PUBLICATIONS, $baseNom);

        if (null === $upload) {
            return null;
        }

        $upload->setPublication($publication);
        $this->entityManager->persist($upload);

        return $upload;
    }

    /**
     * Rôle : Supprimer une ancienne photo de profil et ses métadonnées éventuelles.
     * Paramètres : L'utilisateur propriétaire et le nom du fichier.
     * Retour : Aucun.
     */
    public function supprimerPhotoProfil(Utilisateur $utilisateur, string $nom): void
    {
        $upload = $this->uploadFichierRepository->findOneBy([
            'type' => UploadFichier::TYPE_PROFIL,
            'nom' => $nom,
            'utilisateur' => $utilisateur,
        ]);

        $this->supprimer($upload, UploadFichier::TYPE_PROFIL, $nom);
    }

    /**
     * Rôle : Supprimer une ancienne image de publication et ses métadonnées éventuelles.
     * Paramètres : La publication propriétaire et le nom du fichier.
     * Retour : Aucun.
     */
    public function supprimerImagePublication(Publication $publication, string $nom): void
    {
        $upload = $this->uploadFichierRepository->findOneBy([
            'type' => UploadFichier::TYPE_PUBLICATION,
            'nom' => $nom,
            'publication' => $publication,
        ]);

        $this->supprimer($upload, UploadFichier::TYPE_PUBLICATION, $nom);
    }

    /**
     * Rôle : Construire le chemin sûr d'un fichier centralisé ou historique.
     * Paramètres : Le type, le nom sécurisé et le chemin enregistré lorsqu'il existe.
     * Retour : Le chemin absolu attendu.
     */
    public function obtenirCheminFichier(string $type, string $nom, ?string $cheminEnregistre = null): string
    {
        if (basename($nom) !== $nom) {
            return $this->kernel->getProjectDir().'/uploads/fichier-invalide';
        }

        $chemin = self::CHEMIN_PUBLICATIONS;

        if (UploadFichier::TYPE_PROFIL === $type) {
            $chemin = self::CHEMIN_PROFILS;
        }

        if (null !== $cheminEnregistre && in_array($cheminEnregistre, [self::CHEMIN_PROFILS, self::CHEMIN_PUBLICATIONS], true)) {
            $chemin = $cheminEnregistre;
        }

        return $this->kernel->getProjectDir().'/'.$chemin.'/'.$nom;
    }

    /**
     * Rôle : Déplacer un fichier validé et créer ses métadonnées communes.
     * Paramètres : Le fichier, son type, son répertoire relatif et la base de son nom.
     * Retour : Les métadonnées non rattachées ou null en cas d'échec.
     */
    private function televerser(UploadedFile $fichier, string $type, string $chemin, string $baseNom): ?UploadFichier
    {
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
        } catch (FileException) {
            return null;
        }

        return (new UploadFichier())
            ->setType($type)
            ->setNom($nom)
            ->setChemin($chemin);
    }

    /**
     * Rôle : Supprimer un fichier physique et retirer ses métadonnées Doctrine.
     * Paramètres : Les métadonnées éventuelles, le type et le nom du fichier.
     * Retour : Aucun.
     */
    private function supprimer(?UploadFichier $upload, string $type, string $nom): void
    {
        $cheminEnregistre = null;

        if (null !== $upload) {
            $cheminEnregistre = $upload->getChemin();
        }

        $chemin = $this->obtenirCheminFichier($type, $nom, $cheminEnregistre);

        if (is_file($chemin)) {
            unlink($chemin);
        }

        if (null !== $upload) {
            $this->entityManager->remove($upload);
        }
    }

}
