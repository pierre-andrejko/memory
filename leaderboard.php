<?php
session_start(); // Démarrer la session
include 'db.php'; // Connexion à la base de données

// Vérifier si le joueur est connecté
if (isset($_SESSION['joueur'])) {
    $joueurConnecte = htmlspecialchars($_SESSION['joueur']);

    // Récupérer tous les scores de ce joueur, triés par nombre de tours (croissant)
    $stmt = $pdo->prepare("SELECT nombre_tours, date_partie FROM scores WHERE joueur = ? ORDER BY nombre_tours ASC");
    $stmt->execute([$joueurConnecte]);

    echo "<h2>Scores de $joueurConnecte</h2>";
    echo "<table border='1' style='margin: auto; border-collapse: collapse;'>";
    echo "<tr><th>Score (Tours)</th><th>Date</th></tr>";

    while ($row = $stmt->fetch()) {
        $date = date('d-m-Y H:i', strtotime($row['date_partie'])); // Formater la date
        echo "<tr>
                <td>{$row['nombre_tours']}</td>
                <td>$date</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<h2>Aucun joueur connecté. Veuillez revenir au jeu.</h2>";
}

// Récupérer tous les scores de tous les joueurs, triés par nombre de tours (croissant)
echo "<h2>Tous les Scores des Joueurs</h2>";
$stmt = $pdo->query("SELECT joueur, nombre_tours, date_partie FROM scores ORDER BY nombre_tours ASC, date_partie DESC");
echo "<table border='1' style='margin: auto; border-collapse: collapse;'>";
echo "<tr><th>Joueur</th><th>Score (Tours)</th><th>Date</th></tr>";

while ($row = $stmt->fetch()) {
    $date = date('d-m-Y H:i', strtotime($row['date_partie'])); // Formater la date
    echo "<tr>
            <td>{$row['joueur']}</td>
            <td>{$row['nombre_tours']}</td>
            <td>$date</td>
          </tr>";
}
echo "</table>";

echo "<br><a href='index.php'>Retour au jeu</a>";
?>