<?php
session_start();
require 'db.php';

// Voor de test zijn we ingelogd als 'Test Klant' (id 1)
$_SESSION['klant_id'] = 1;
$_SESSION['klant_naam'] = 'Test Klant';
$klant_id = $_SESSION['klant_id'];

// Actie: Annuleren van een reservering
if (isset($_GET['annuleer_id'])) {
    $reservering_id = $_GET['annuleer_id'];

    try {
        $pdo->beginTransaction();

        // 1. Zoek de gekoppelde beschikbaarheid_id van deze reservering op
        $stmtCheck = $pdo->prepare("SELECT beschikbaarheid_id FROM reserveringen WHERE id = ? AND klant_id = ?");
        $stmtCheck->execute([$reservering_id, $klant_id]);
        $res = $stmtCheck->fetch();

        if ($res) {
            $beschikbaarheid_id = $res['beschikbaarheid_id'];

            // 2. Verwijder de reservering (of zet status op 'Geannuleerd')
            $stmtDel = $pdo->prepare("DELETE FROM reserveringen WHERE id = ?");
            $stmtDel->execute([$reservering_id]);

            // 3. Zet het tijdvak in 'beschikbaarheid' weer op 1 (vrij / beschikbaar)
            $stmtUpdate = $pdo->prepare("UPDATE beschikbaarheid SET is_beschikbaar = 1 WHERE id = ?");
            $stmtUpdate->execute([$beschikbaarheid_id]);

            $pdo->commit();
            $melding = "<p style='color: green;'>Reservering succesvol geannuleerd! Het tijdvak is weer vrijgegeven.</p>";
        } else {
            $pdo->rollBack();
            $melding = "<p style='color: red;'>Kon reservering niet vinden.</p>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $melding = "<p style='color: red;'>Fout bij annuleren: " . $e->getMessage();
    }
}

// Haal alle actieve reserveringen van deze klant op
$stmtRes = $pdo->prepare("
    SELECT r.id AS reservering_id, o.naam AS object_naam, b.datum, b.start_tijd, b.eind_tijd 
    FROM reserveringen r
    JOIN beschikbaarheid b ON r.beschikbaarheid_id = b.id
    JOIN objecten o ON b.object_id = o.id
    WHERE r.klant_id = ?
");
$stmtRes->execute([$klant_id]);
$reserveringen = $stmtRes->fetchAll();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Mijn Reserveringen - Blazter</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f4f4f9; }
        .box { background: white; padding: 25px; border-radius: 8px; width: 600px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #0066cc; color: white; }
        a.btn-annuleer { background: #d9534f; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; }
        a.btn-annuleer:hover { background: #c9302c; }
        .nav { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="nav">
            <a href="reserveren.php">← Nieuwe Reservering Maken</a>
        </div>
        
        <h2>Mijn Reserveringen</h2>
        <p>Ingelogd als: <b><?php echo $_SESSION['klant_naam']; ?></b></p>

        <?php if (isset($melding)) echo $melding; ?>

        <?php if (count($reserveringen) > 0): ?>
            <table>
                <tr>
                    <th>Ruimte / Object</th>
                    <th>Datum & Tijd</th>
                    <th>Actie</th>
                </tr>
                <?php foreach ($reserveringen as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['object_naam']); ?></td>
                        <td><?= $item['datum']; ?> (<?= $item['start_tijd']; ?> - <?= $item['eind_tijd']; ?>)</td>
<td>
    <a href="download_pdf.php?id=<?= $item['reservering_id']; ?>" 
       style="background: #0066cc; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px;" target="_blank">
       PDF
    </a>
    <a href="wijzigen.php?id=<?= $item['reservering_id']; ?>" 
       style="background: #f0ad4e; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px;">
       Wijzigen
    </a>
    <a href="mijn_reserveringen.php?annuleer_id=<?= $item['reservering_id']; ?>" 
       style="background: #d9534f; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px;" 
       onclick="return confirm('Weet je zeker dat je wilt annuleren?');">
       Annuleren
    </a>
</td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>Je hebt momenteel geen actieve reserveringen.</p>
        <?php endif; ?>
    </div>
</body>
</html>