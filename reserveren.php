<?php
session_start();
require 'db.php';

// Voor de test loggen we de 'Test Klant' (id 1) automatisch in
$_SESSION['klant_id'] = 1;
$_SESSION['klant_naam'] = 'Test Klant';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $beschikbaarheid_id = $_POST['beschikbaarheid_id'];
    $klant_id = $_SESSION['klant_id'];

    try {
        $pdo->beginTransaction();

        // 1. Check of het tijdvak beschikbaar is
        $stmt = $pdo->prepare("SELECT is_beschikbaar FROM beschikbaarheid WHERE id = ? FOR UPDATE");
        $stmt->execute([$beschikbaarheid_id]);
        $slot = $stmt->fetch();

        if ($slot && $slot['is_beschikbaar'] == 1) {
            // 2. Sla de reservering op
            $stmtRes = $pdo->prepare("INSERT INTO reserveringen (klant_id, beschikbaarheid_id) VALUES (?, ?)");
            $stmtRes->execute([$klant_id, $beschikbaarheid_id]);

            // 3. Zet de beschikbaarheid direct op 0 (bezet) zodat niemand anders het kan kiezen
            $stmtUpdate = $pdo->prepare("UPDATE beschikbaarheid SET is_beschikbaar = 0 WHERE id = ?");
            $stmtUpdate->execute([$beschikbaarheid_id]);

            $pdo->commit();
            
            echo "<h3 style='color: green;'>Reservering gelukt! Het tijdvak is direct afgeschermd voor anderen.</h3>";
        } else {
            echo "<h3 style='color: red;'>Fout: Dit tijdvak is net door iemand anders gereserveerd!</h3>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Fout: " . $e->getMessage();
    }
}

// Haal alle vrije tijdvakken op
$tijdvakken = $pdo->query("
    SELECT b.id, o.naam AS object_naam, b.datum, b.start_tijd, b.eind_tijd 
    FROM beschikbaarheid b 
    JOIN objecten o ON b.object_id = o.id 
    WHERE b.is_beschikbaar = 1
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Reserveringssysteem - Blazter</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f4f4f9; }
        .box { background: white; padding: 25px; border-radius: 8px; width: 450px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        select, button { width: 100%; padding: 10px; margin-top: 10px; }
        button { background: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #00509e; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Reservering Maken</h2>
        <p>Ingelogd als: <b><?php echo $_SESSION['klant_naam']; ?></b></p>
        
        <form method="POST">
            <label for="beschikbaarheid_id">Kies een beschikbaar tijdvak:</label>
            <select name="beschikbaarheid_id" required>
                <option value="">-- Selecteer een optie --</option>
                <?php foreach ($tijdvakken as $slot): ?>
                    <option value="<?= $slot['id']; ?>">
                        <?= htmlspecialchars($slot['object_naam']); ?> (<?= $slot['datum']; ?> van <?= $slot['start_tijd']; ?> tot <?= $slot['eind_tijd']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Bevestig Reservering</button>
        </form>
    </div>
</body>
</html>