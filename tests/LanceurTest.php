<?php

/* Origine du code : Code créé par le développeur. */

/*
 * Description générale : Petit utilitaire autonome de comptabilisation des tests PHP de Voisin.
 * Rôle : Comparer les valeurs attendues et obtenues puis produire un bilan lisible dans le terminal.
 * Tâches : Regrouper les résultats, afficher les succès et les échecs et fournir un code de sortie exploitable par la CI.
 * Liens avec les autres fichiers : Utilisé par tests/Lancer.php et tous les fichiers des dossiers tests/unitaire et tests/integration.
 */

class LanceurTest
{
    private int $testsReussis = 0;
    private int $testsEchoues = 0;
    private string $itemActuel = '';

    /** @var array<string, array{reussis: int, echoues: int}> */
    private array $resultatsParItem = [];

    /**
     * Rôle : Commencer un groupe de vérifications et initialiser ses compteurs.
     * Paramètres : Le nom du comportement ou du composant testé.
     * Retour : Aucun.
     */
    public function commencerItem(string $nomItem): void
    {
        $this->itemActuel = $nomItem;

        if (!isset($this->resultatsParItem[$nomItem])) {
            $this->resultatsParItem[$nomItem] = [
                'reussis' => 0,
                'echoues' => 0,
            ];
        }

        echo PHP_EOL . '----- ' . $nomItem . ' -----' . PHP_EOL;
    }

    /**
     * Rôle : Comparer strictement une valeur obtenue avec la valeur attendue.
     * Paramètres : La valeur attendue, la valeur obtenue et le message explicatif.
     * Retour : Aucun.
     */
    public function verifierEgalite(mixed $valeurAttendue, mixed $valeurObtenue, string $message): void
    {
        if ($valeurAttendue === $valeurObtenue) {
            $this->enregistrerSucces($message);

            return;
        }

        $details = 'attendu ' . $this->formaterValeur($valeurAttendue)
            . ', obtenu ' . $this->formaterValeur($valeurObtenue);
        $this->enregistrerEchec($message, $details);
    }

    /**
     * Rôle : Vérifier qu'une condition est vraie.
     * Paramètres : La condition obtenue et le message explicatif.
     * Retour : Aucun.
     */
    public function verifierVrai(bool $condition, string $message): void
    {
        $this->verifierEgalite(true, $condition, $message);
    }

    /**
     * Rôle : Vérifier qu'une condition est fausse.
     * Paramètres : La condition obtenue et le message explicatif.
     * Retour : Aucun.
     */
    public function verifierFaux(bool $condition, string $message): void
    {
        $this->verifierEgalite(false, $condition, $message);
    }

    /**
     * Rôle : Enregistrer une exception imprévue sans interrompre les autres fichiers de test.
     * Paramètres : L'exception interceptée pendant l'exécution d'un fichier.
     * Retour : Aucun.
     */
    public function enregistrerException(\Throwable $exception): void
    {
        $details = $exception::class . ' : ' . $exception->getMessage();
        $this->enregistrerEchec('Le fichier de test doit s\'exécuter sans exception', $details);
    }

    /**
     * Rôle : Afficher le bilan détaillé de la campagne.
     * Paramètres : Aucun.
     * Retour : Aucun.
     */
    public function afficherResume(): void
    {
        echo PHP_EOL . '========================================' . PHP_EOL;
        echo '               RÉSULTATS' . PHP_EOL;
        echo '========================================' . PHP_EOL;

        foreach ($this->resultatsParItem as $nomItem => $resultats) {
            echo PHP_EOL . $nomItem . PHP_EOL;
            echo 'Tests réussis : ' . $resultats['reussis'] . PHP_EOL;
            echo 'Tests échoués : ' . $resultats['echoues'] . PHP_EOL;
        }

        echo PHP_EOL . '----------------------------------------' . PHP_EOL;
        echo 'TOTAL' . PHP_EOL;
        echo '----------------------------------------' . PHP_EOL;
        echo 'Tests réussis : ' . $this->testsReussis . PHP_EOL;
        echo 'Tests échoués : ' . $this->testsEchoues . PHP_EOL;
    }

    /**
     * Rôle : Retourner le code de sortie attendu par le terminal et GitHub Actions.
     * Paramètres : Aucun.
     * Retour : Zéro sans échec, un lorsqu'au moins un test a échoué.
     */
    public function obtenirCodeSortie(): int
    {
        if (0 === $this->testsEchoues) {
            return 0;
        }

        return 1;
    }

    /**
     * Rôle : Comptabiliser et afficher une vérification réussie.
     * Paramètres : Le message du test.
     * Retour : Aucun.
     */
    private function enregistrerSucces(string $message): void
    {
        echo '[OK] ' . $message . PHP_EOL;
        $this->testsReussis++;

        if ('' !== $this->itemActuel) {
            $this->resultatsParItem[$this->itemActuel]['reussis']++;
        }
    }

    /**
     * Rôle : Comptabiliser et afficher une vérification échouée.
     * Paramètres : Le message du test et le diagnostic associé.
     * Retour : Aucun.
     */
    private function enregistrerEchec(string $message, string $details): void
    {
        echo '[ECHEC] ' . $message . PHP_EOL;
        echo '        ' . $details . PHP_EOL;
        $this->testsEchoues++;

        if ('' !== $this->itemActuel) {
            $this->resultatsParItem[$this->itemActuel]['echoues']++;
        }
    }

    /**
     * Rôle : Transformer une valeur en texte court pour le diagnostic d'un échec.
     * Paramètres : La valeur à représenter.
     * Retour : La représentation PHP de la valeur.
     */
    private function formaterValeur(mixed $valeur): string
    {
        return var_export($valeur, true);
    }
}
