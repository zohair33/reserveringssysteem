<?php
session_start();
require 'db.php';

if (!isset($_SESSION['klant_id'])) {
    header("Location: login.php");
    exit;
}

$klant_id = $_SESSION['klant_id'];
$reservering_id = $_GET['id'] ?? null;

if (!$reservering_id) {
    header("Location: mijn_reserveringen.php");
    exit;
}

// Verwerk het wijzigen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nieuw_beschikbaarheid_id = $_POST['nieuw_beschikbaarheid_id'];

    try {
        $pdo->beginTransaction();

        // 1. Haal de oude beschikbaarheid_id op
        $stmtOld = $pdo->prepare("SELECT beschikbaarheid_id FROM reserveringen WHERE id = ? AND klant_id = ?");
        $stmtOld->execute([$reservering_id, $klant_id]);
        $oldRes = $stmtOld->fetch();

        if ($oldRes) {
            $oud_beschikbaarheid_id = $oldRes['beschikbaarheid_id'];

            // 2. Zet oude beschikbaarheid weer op 'vrij' (1)
            $stmtFree = $pdo->prepare("UPDATE beschikbaarheid SET is_beschikbaar = 1 WHERE id = ?");
            $stmtFree->execute([$oud_beschikbaarheid_id]);

            // 3. Update reservering naar nieuwe beschikbaarheid
            $stmtUpdateRes = $pdo->prepare("UPDATE reserveringen SET beschikbaarheid_id = ? WHERE id = ?");
            $stmtUpdateRes->execute([$nieuw_beschikbaarheid_id, $reservering_id]);

            // 4. Zet nieuwe beschikbaarheid op 'bezet' (0)
            $stmtOccupy = $pdo->prepare("UPDATE beschikbaarheid SET is_beschikbaar = 0 WHERE id = ?");
            $stmtOccupy->execute([$nieuw_beschikbaarheid_id]);

            $pdo->commit();
            header("Location: mijn_reserveringen.php?gewijzigd=1");
            exit;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $fout = "Fout bij wijzigen: " . $e->getMessage();
    }
}

// Beschikbare nieuwe tijdvakken ophalen
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
    <title>Reservering Wijzigen - Blazter</title>
    <style>
        body { font-family: Arial; background: #f4f4f9; padding: 40px; }
        .box { background: white; padding: 25px; border-radius: 8px; width: 450px; margin: 0 auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        select, button { width: 100%; padding: 10px; margin-top: 10px; }
        button { background: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Reservering Wijzigen</h2>
        <form method="POST">
            <label>Kies een nieuw beschikbaar tijdvak:</label>
            <select name="nieuw_beschikbaarheid_id" required>
                <option value="">-- Kies nieuw tijdvak --</option>
                <?php foreach ($tijdvakken as $slot): ?>
                    <option value="<?= $slot['id']; ?>">
                        <?= htmlspecialchars($slot['object_naam']); ?> (<?= $slot['datum']; ?> van <?= $slot['start_tijd']; ?> tot <?= $slot['eind_tijd']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Wijziging Opslaan</button>
        </form>
        <p><a href="mijn_reserveringen.php">Terug</a></p>
    </div>
</body>
</html>