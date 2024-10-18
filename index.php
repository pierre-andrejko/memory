<?php
session_start(); // Démarrer la session

// Inclure la connexion à la base de données
include 'db.php';

// Définir le nombre de paires de cartes
define('NUM_PAIRS', 3);  // Maximum 12 paires

// Définition de la classe MemoryGame
class MemoryGame {
    private $numPairs;
    private $cards = [];
    private $revealed = [];
    private $selected = [];
    public $score = 0;  // Variable pour stocker le nombre de tours

    public function __construct($numPairs = 3) {
        $this->numPairs = $numPairs;

        // Initialiser le jeu si ce n'est pas encore fait
        if (!isset($_SESSION['cards'])) {
            $this->initializeGame();
        } else {
            $this->loadGameFromSession();
        }
    }

    // Initialiser les cartes et l'état du jeu
    private function initializeGame() {
        $this->cards = range(1, $this->numPairs);
        $this->cards = array_merge($this->cards, $this->cards);  // Doubler les cartes pour les paires
        shuffle($this->cards);  // Mélanger les cartes

        $this->revealed = array_fill(0, $this->numPairs * 2, false);
        $this->selected = [];
        $this->score = 0;  // Initialiser le score à 0

        // Sauvegarder dans la session
        $_SESSION['cards'] = $this->cards;
        $_SESSION['revealed'] = $this->revealed;
        $_SESSION['selected'] = $this->selected;
        $_SESSION['score'] = $this->score;
    }

    // Charger le jeu depuis la session
    private function loadGameFromSession() {
        $this->cards = $_SESSION['cards'];
        $this->revealed = $_SESSION['revealed'] ?? array_fill(0, $this->numPairs * 2, false);
        $this->selected = $_SESSION['selected'] ?? [];
        $this->score = $_SESSION['score'] ?? 0;
    }

    // Sauvegarder l'état du jeu dans la session
    private function saveGameToSession() {
        $_SESSION['cards'] = $this->cards;
        $_SESSION['revealed'] = $this->revealed;
        $_SESSION['selected'] = $this->selected;
        $_SESSION['score'] = $this->score;
    }

    // Réinitialiser le jeu
    public function resetGame() {
        session_destroy();
        header("Location: index.php");
        exit;
    }

    // Gérer la sélection d'une carte
    public function selectCard($cardIndex) {
        if (!$this->revealed[$cardIndex] && count($this->selected) < 2) {
            $this->selected[] = $cardIndex;
        }

        // Si deux cartes sont sélectionnées
        if (count($this->selected) == 2) {
            $this->checkForMatch();
            $this->score++;  // Incrémenter le score à chaque tour (quand 2 cartes sont sélectionnées)
        }

        $this->saveGameToSession();
    }

    // Vérifier si les deux cartes sélectionnées sont identiques
    private function checkForMatch() {
        list($first, $second) = $this->selected;

        if ($this->cards[$first] === $this->cards[$second]) {
            $this->revealed[$first] = true;
            $this->revealed[$second] = true;
        }

        // Réinitialiser la sélection après un délai
        header("Refresh:1; url=index.php");
        $this->selected = [];
    }

    // Vérifier si le jeu est terminé
    public function isGameWon() {
        return !in_array(false, $this->revealed);
    }

    // Enregistrer le score dans la base de données
    public function saveScore($joueur) {
        global $pdo;  // Utiliser la connexion PDO
        $stmt = $pdo->prepare("INSERT INTO scores (joueur, nombre_tours) VALUES (?, ?)");
        $stmt->execute([$joueur, $this->score]);
    }

    // Afficher la grille de jeu
    public function displayBoard() {
        echo "<table><tr>";
        foreach ($this->cards as $index => $card) {
            if ($index % 4 == 0 && $index != 0) {
                echo "</tr><tr>";
            }
            echo "<td>";
            if ($this->revealed[$index] || in_array($index, $this->selected)) {
                echo "<span class='revealed'>$card</span>";
            } else {
                echo "<a class='hidden' href='?card=$index'></a>";
            }
            echo "</td>";
        }
        echo "</tr></table>";
    }

    // Afficher le score (nombre de tours)
    public function displayScore() {
        echo "<h2>Nombre de tours joués : " . $this->score . "</h2>";
    }
}

// Créer une instance du jeu
$memoryGame = new MemoryGame(NUM_PAIRS);

// Gérer la réinitialisation du jeu
if (isset($_GET['reset'])) {
    $memoryGame->resetGame();
}

// Gérer la sélection d'une carte
if (isset($_GET['card'])) {
    $cardIndex = (int)$_GET['card'];
    $memoryGame->selectCard($cardIndex);
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jeu de Memoryr</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        table { margin: 20px auto; }
        td { width: 80px; height: 80px; text-align: center; font-size: 24px; background-color: #ccc; }
        a { display: block; width: 100%; height: 100%; text-decoration: none; color: black; }
        .revealed { background-color: #90EE90; } /* Vert si révélées */
        .hidden { background-color: #ccc; }
    </style>
</head>
<body>

<h1>Jeu de Memory</h1>

<?php if ($memoryGame->isGameWon()): ?>
    <!-- Formulaire pour entrer le nom du joueur -->
    <form method="POST">
        <label for="joueur">Entrez votre nom :</label>
        <input type="text" id="joueur" name="joueur" required>
        <button type="submit">Enregistrer le score</button>
    </form>
<?php else: ?>
    <?php $memoryGame->displayScore(); ?>  <!-- Affichage du score -->
    <?php $memoryGame->displayBoard(); ?>
<?php endif; ?>

<?php
// Enregistrer le score si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['joueur'])) {
    $joueur = htmlspecialchars($_POST['joueur']);  // Sécuriser l'entrée
    $memoryGame->saveScore($joueur);  // Enregistrer le score
    $_SESSION['joueur'] = $joueur;  // Stocker le nom dans la session
    echo "<h2>Félicitations, $joueur ! Vous avez gagné en {$memoryGame->score} tours.</h2>";
    echo "<a href='?reset=1'>Recommencer le jeu</a><br>";
    echo "<a href='leaderboard.php'>Voir le Classement</a>";
    exit();
}
?>

<a href="?reset=1">Réinitialiser le jeu</a>

</body>
</html>
