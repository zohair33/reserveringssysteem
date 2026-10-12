<?php
session_start();
require 'db.php';

// Verwerk Toevoegen van een Tijdvak / Kalender
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actie_tijdvak'])) {
    $object_id = $_POST['object_id'];
    $datum = $_POST['datum'];
    $start_tijd = $_POST['start_tijd'];
    $eind_tijd = $_POST['eind_tijd'];

    $stmt = $pdo->prepare("INSERT INTO beschikbaarheid (object_id, datum, start_tijd, eind_tijd, is_beschikbaar) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([$object_id, $datum, $start_tijd, $eind_tijd]);
    $melding = "Tijdvak succesvol toegevoegd aan de kalender!";
}

// Verwerk Toevoegen van een Object
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actie_object'])) {
    $naam = $_POST['naam'];
    $beschrijving = $_POST['beschrijving'];

    $stmt = $pdo->prepare("INSERT INTO objecten (naam, beschrijving) VALUES (?, ?)");
    $stmt->execute([$naam, $beschrijving]);
    $melding = "Object/Ruimte succesvol toegevoegd!";
}

// Ophalen gegevens voor weergave
$klanten = $pdo->query("SELECT * FROM klanten")->fetchAll();
$objecten = $pdo->query("SELECT * FROM objecten")->fetchAll();
$tijdvakken = $pdo->query("
    SELECT b.id, o.naam AS object_naam, b.datum, b.start_tijd, b.eind_tijd, b.is_beschikbaar 
    FROM beschikbaarheid b 
    JOIN objecten o ON b.object_id = o.id
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Blazter CRUD</title>
    <style>
        body { font-family: Arial; margin: 30px; background: #f4f4f9; }
        .grid { display: flex; gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; flex: 1; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #333; color: white; }
        input, select, button { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; }
        button { background: #28a745; color: white; border: none; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Beheerderspaneel (Admin CRUD)</h1>
    <?php if (isset($melding)): ?><p style="color:green; font-weight:bold;"><?= $melding; ?></p><?php endif; ?>

    <div class="grid">
        <!-- Object Toevoegen -->
        <div class="card">
            <h3>Object / Ruimte Toevoegen</h3>
            <form method="POST">
                <input type="hidden" name="actie_object" value="1">
                <input type="text" name="naam" placeholder="Ruimte Naam" required>
                <input type="text" name="beschrijving" placeholder="Beschrijving">
                <button type="submit">Object Toevoegen</button>
            </form>
        </div>

        <!-- Kalender / Tijdvak Toevoegen -->
        <div class="card">
            <h3>Kalender Tijdvak Toevoegen</h3>
            <form method="POST">
                <input type="hidden" name="actie_tijdvak" value="1">
                <select name="object_id" required>
                    <option value="">-- Kies Object --</option>
                    <?php foreach ($objecten as $o): ?>
                        <option value="<?= $o['id']; ?>"><?= htmlspecialchars($o['naam']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="datum" required>
                <input type="time" name="start_tijd" required>
                <input type="time" name="eind_tijd" required>
                <button type="submit">Tijdvak Toevoegen</button>
            </form>
        </div>
    </div>

    <!-- Overzichten -->
    <div class="card">
        <h3>Klantenbestand (CRUD)</h3>
        <table>
            <tr><th>ID</th><th>Naam</th><th>E-mail</th><th>Geregistreerd Op</th></tr>
            <?php foreach ($klanten as $k): ?>
                <tr><td><?= $k['id']; ?></td><td><?= htmlspecialchars($k['naam']); ?></td><td><?= htmlspecialchars($k['email']); ?></td><td><?= $k['aangemaakt_op']; ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>

    <br>

    <div class="card">
        <h3>Kalender & Beschikbaarheid (CRUD)</h3>
        <table>
            <tr><th>ID</th><th>Object</th><th>Datum</th><th>Tijd</th><th>Status</th></tr>
            <?php foreach ($tijdvakken as $t): ?>
                <tr>
                    <td><?= $t['id']; ?></td>
                    <td><?= htmlspecialchars($t['object_naam']); ?></td>
                    <td><?= $t['datum']; ?></td>
                    <td><?= $t['start_tijd']; ?> - <?= $t['eind_tijd']; ?></td>
                    <td><?= $t['is_beschikbaar'] ? '<b style="color:green;">Vrij</b>' : '<b style="color:red;">Bezet</b>'; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>