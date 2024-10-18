<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=memory_game', 'root', ''); // Change 'root' et '' si nécessaire
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
    exit();
}
?>
