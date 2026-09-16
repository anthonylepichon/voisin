<?php

/* Origine du code : Code créé par le développeur. */

/*
 * Description générale : Tests unitaires des autorisations appliquées aux publications.
 * Rôle : Protéger la visibilité, la modification par le propriétaire et la suppression administrative.
 * Tâches : Simuler Symfony Security et comparer les droits d'un visiteur, d'un membre, d'un ami et d'un administrateur.
 * Liens avec les autres fichiers : Utilise PublicationAccessService, Publication, Utilisateur et AuthorizationCheckerInterface.
 */

use App\Entity\Publication;
use App\Entity\Utilisateur;
use App\Service\PublicationAccessService;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ControleurAutorisationPourTest implements AuthorizationCheckerInterface
{
    /**
     * Rôle : Initialiser le faux contrôleur avec l'état administrateur attendu.
     * Paramètres : Vrai pour simuler ROLE_ADMIN.
     * Retour : Aucun.
     */
    public function __construct(private bool $administrateur)
    {
    }

    /**
     * Rôle : Répondre aux contrôles de rôle effectués par le service testé.
     * Paramètres : L'attribut de sécurité et l'éventuel sujet.
     * Retour : L'état administrateur simulé pour ROLE_ADMIN.
     */
    public function isGranted(mixed $attribute, mixed $subject = null): bool
    {
        if ('ROLE_ADMIN' === $attribute) {
            return $this->administrateur;
        }

        return false;
    }
}

/**
 * Rôle : Attribuer un identifiant fictif à une entité sans base de données.
 * Paramètres : L'utilisateur et l'identifiant à simuler.
 * Retour : Aucun.
 */
function definirIdentifiantUtilisateurPourTest(Utilisateur $utilisateur, int $identifiant): void
{
    $propriete = new ReflectionProperty(Utilisateur::class, 'id');
    $propriete->setValue($utilisateur, $identifiant);
}

$serviceMembre = new PublicationAccessService(new ControleurAutorisationPourTest(false));
$serviceAdministrateur = new PublicationAccessService(new ControleurAutorisationPourTest(true));

$proprietaireAcces = new Utilisateur();
$amiAcces = new Utilisateur();
$autreMembreAcces = new Utilisateur();
definirIdentifiantUtilisateurPourTest($proprietaireAcces, 1);
definirIdentifiantUtilisateurPourTest($amiAcces, 2);
definirIdentifiantUtilisateurPourTest($autreMembreAcces, 3);
$proprietaireAcces->ajouterAmi($amiAcces);

$publicationPublique = new Publication();
$publicationPublique->setContenu('Publication publique');
$publicationPublique->setUtilisateur($proprietaireAcces);
$publicationPublique->setVisibilite(Publication::VISIBILITE_PUBLIQUE);

$lanceurTests->verifierVrai(
    $serviceMembre->peutVoir($publicationPublique, null),
    'Un visiteur peut voir une publication publique'
);

$publicationAmis = new Publication();
$publicationAmis->setContenu('Publication réservée aux amis');
$publicationAmis->setUtilisateur($proprietaireAcces);
$publicationAmis->setVisibilite(Publication::VISIBILITE_AMIS);

$lanceurTests->verifierFaux(
    $serviceMembre->peutVoir($publicationAmis, null),
    'Un visiteur ne peut pas voir une publication réservée aux amis'
);
$lanceurTests->verifierVrai(
    $serviceMembre->peutVoir($publicationAmis, $amiAcces),
    'Un ami peut voir une publication réservée aux amis'
);
$lanceurTests->verifierVrai(
    $serviceMembre->peutModifier($publicationAmis, $proprietaireAcces),
    'Le propriétaire peut modifier sa publication'
);
$lanceurTests->verifierFaux(
    $serviceMembre->peutModifier($publicationAmis, $autreMembreAcces),
    'Un autre membre ne peut pas modifier la publication'
);
$lanceurTests->verifierVrai(
    $serviceAdministrateur->peutSupprimer($publicationAmis, $autreMembreAcces),
    'Un administrateur peut supprimer une publication à modérer'
);
