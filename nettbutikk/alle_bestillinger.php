<?php
// Start økt og koble til database
session_start();
require_once 'db_config.php';

// Sjekk om bruker er logget inn
if (!isset($_SESSION['KundeID'])) {
    header("Location: logginn.php");
    exit();
}

// SQL-spørring for å hente alle bestillinger med kundeinformasjon
$sporring = "SELECT 
            b.BestilingID,
            b.dato,
            b.totalpris,
            CONCAT(k.Navn, ' ', k.etternavn) as kundenavn,
            k.Epost as epost,
            p.navn as produktnavn,
            bd.Antall as antall,
            p.Pris as pris
        FROM bestiling b 
        JOIN kunde k ON b.KundeID = k.KundeID
        JOIN Bestillingsdetaljer bd ON b.BestilingID = bd.BestillingID 
        JOIN Produkt p ON bd.ProduktID = p.ProduktID 
        ORDER BY b.dato DESC";

// Utfør spørringen og sjekk for feil
$resultat = $db->query($sporring);
if (!$resultat) {
    die("Kunne ikke hente bestillinger: " . $db->error);
}

// Organiser bestillingene i en oversiktlig struktur
$bestillinger = array();
while ($rad = $resultat->fetch_assoc()) {
    $bestillingId = $rad['BestilingID'];
    
    // Hvis dette er en ny bestilling, legg til hovedinformasjonen
    if (!isset($bestillinger[$bestillingId])) {
        $bestillinger[$bestillingId] = array(
            'dato' => $rad['dato'],
            'totalpris' => $rad['totalpris'],
            'kunde' => $rad['kundenavn'],
            'epost' => $rad['epost'],
            'varer' => array()
        );
    }
    
    // Legg til vareinformasjon
    $bestillinger[$bestillingId]['varer'][] = array(
        'navn' => $rad['produktnavn'],
        'antall' => $rad['antall'],
        'pris' => $rad['pris']
    );
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alle Bestillinger - Nettbutikk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigasjonsmeny -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid px-2">
            <a class="navbar-brand" href="index.php">Nettbutikk</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Hjem</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="handlekurv.php">Handlekurv</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="alle_bestillinger.php">Bestillinger</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="loggut.php">Logg ut</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hovedinnhold -->
    <div class="container my-5">
        <h2 class="mb-4">Alle Bestillinger</h2>
        
        <?php if (empty($bestillinger)): ?>
            <div class="alert alert-info">
                Ingen bestillinger er registrert.
            </div>
        <?php else: ?>
            <?php foreach ($bestillinger as $bestillingId => $bestilling): ?>
                <div class="card mb-4">
                    <!-- Bestillingsoverskrift -->
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <strong>Bestilling #<?php echo $bestillingId; ?></strong>
                            </div>
                            <div class="col-md-3">
                                <strong>Kunde:</strong> <?php echo htmlspecialchars($bestilling['kunde']); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>E-post:</strong> <?php echo htmlspecialchars($bestilling['epost']); ?>
                            </div>
                            <div class="col-md-3 text-end">
                                <?php echo date('d.m.Y', strtotime($bestilling['dato'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bestillingsdetaljer -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Vare</th>
                                        <th>Antall</th>
                                        <th>Pris</th>
                                        <th>Sum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bestilling['varer'] as $vare): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($vare['navn']); ?></td>
                                            <td><?php echo $vare['antall']; ?></td>
                                            <td><?php echo number_format($vare['pris'], 2, ',', ' '); ?> kr</td>
                                            <td><?php echo number_format($vare['pris'] * $vare['antall'], 2, ',', ' '); ?> kr</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td><strong><?php echo number_format($bestilling['totalpris'], 2, ',', ' '); ?> kr</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
