<?php
// Start sesjon og koble til databasen
session_start();
require_once 'db_config.php';

// Sjekk om brukeren er logget inn, hvis ikke redirect til login
if (!isset($_SESSION['KundeID'])) {
    header("Location: logginn.php");
    exit();
}

// Hent brukerens personlige informasjon fra databasen
$resultat = $db->query("SELECT Navn, etternavn, Epost, adressa, postnummer, poststad 
                        FROM kunde 
                        WHERE KundeID = {$_SESSION['KundeID']}");
$bruker = $resultat->fetch_assoc();

// Hent alle bestillinger for brukeren med produktdetaljer
// Bruker GROUP_CONCAT for å samle alle produkter i én rad per bestilling
$bestillinger = $db->query("SELECT b.BestilingID, b.dato, b.totalpris, 
                           GROUP_CONCAT(CONCAT(p.navn, ' (', bd.Antall, ' stk)') SEPARATOR ', ') as produkter
                           FROM bestiling b
                           JOIN Bestillingsdetaljer bd ON b.BestilingID = bd.BestillingID
                           JOIN Produkt p ON bd.ProduktID = p.ProduktID
                           WHERE b.KundeID = {$_SESSION['KundeID']}
                           GROUP BY b.BestilingID
                           ORDER BY b.dato DESC");
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Min Profil - Nettbutikk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
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
                        <a class="nav-link active" href="profil.php">Min Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="loggut.php">Logg ut</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Profilinformasjon</h2>
                        <hr>
                        <p><strong>Navn:</strong> <?= htmlspecialchars($bruker['Navn'] . ' ' . $bruker['etternavn']) ?></p>
                        <p><strong>E-post:</strong> <?= htmlspecialchars($bruker['Epost']) ?></p>
                        <p><strong>Adresse:</strong> <?= htmlspecialchars($bruker['adressa']) ?></p>
                        <p><strong>Postnummer:</strong> <?= htmlspecialchars($bruker['postnummer']) ?></p>
                        <p><strong>Poststed:</strong> <?= htmlspecialchars($bruker['poststad']) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Mine bestillinger</h2>
                        <hr>
                        <?php if ($bestillinger->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Ordrenr.</th>
                                            <th>Dato</th>
                                            <th>Produkter</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($bestilling = $bestillinger->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?= $bestilling['BestilingID'] ?></td>
                                                <td><?= date('d.m.Y', strtotime($bestilling['dato'])) ?></td>
                                                <td><?= htmlspecialchars($bestilling['produkter']) ?></td>
                                                <td class="text-end">kr <?= number_format($bestilling['totalpris'], 2, ',', ' ') ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">Du har ingen bestillinger ennå.</p>
                            <div class="text-center">
                                <a href="index.php" class="btn btn-primary">Gå til butikken</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
