<?php
// Start sesjon og koble til databasen
session_start();
require_once 'db_config.php';

// Sjekk om bruker er logget inn, hvis ikke redirect til login
if (!isset($_SESSION['KundeID'])) {
    header("Location: logginn.php");
    exit();
}

// Håndter bestillingsprosessen når skjemaet er sendt inn
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['handlekurv']) && !empty($_SESSION['handlekurv'])) {
    // Start en databasetransaksjon for å sikre at alle operasjoner utføres eller ingen
    $db->begin_transaction();
    
    try {
        // Beregn total pris for alle varer i handlekurven
        $totalPris = 0;
        foreach ($_SESSION['handlekurv'] as $vareId => $antall) {
            $sporring = $db->query("SELECT Pris FROM Produkt WHERE ProduktID = $vareId");
            $vare = $sporring->fetch_assoc();
            $totalPris += $vare['Pris'] * $antall;
        }
        
        // Opprett ny bestilling i databasen
        $db->query("INSERT INTO bestiling (KundeID, dato, totalpris) VALUES ({$_SESSION['KundeID']}, NOW(), $totalPris)");
        $bestillingId = $db->insert_id;
        
        // Legg til hver vare i bestillingsdetaljer og oppdater lagerbeholdning
        foreach ($_SESSION['handlekurv'] as $vareId => $antall) {
            $db->query("INSERT INTO Bestillingsdetaljer (BestillingID, ProduktID, Antall) VALUES ($bestillingId, $vareId, $antall)");
            $db->query("UPDATE Produkt SET lagerbeholdning = lagerbeholdning - $antall WHERE ProduktID = $vareId");
        }
        
        // Bekreft alle databaseendringer
        $db->commit();
        
        // Nullstill handlekurv og sett suksessmelding
        $_SESSION['handlekurv'] = [];
        $_SESSION['bestilling_vellykket'] = true;
        $_SESSION['bestilling_id'] = $bestillingId;
        
        header("Location: kasse.php");
        exit();
        
    } catch (Exception $e) {
        // Hvis noe går galt, tilbakestill alle databaseendringer
        $db->rollback();
        $feilmelding = "Det oppstod en feil under behandling av bestillingen: " . $e->getMessage();
    }
}

// Beregn totalsum og hent informasjon om varene i handlekurven
$totalSum = 0;
$varer = array();

if (!empty($_SESSION['handlekurv'])) {
    // Hent alle produkt-IDer fra handlekurven
    $vareIds = array_keys($_SESSION['handlekurv']);
    $vareIdsStr = implode(',', $vareIds);
    
    // Hent produktinformasjon fra databasen
    $sporring = $db->query("SELECT ProduktID, navn, Pris FROM Produkt WHERE ProduktID IN ($vareIdsStr)");
    
    // Beregn delsum for hver vare og total sum
    while ($vare = $sporring->fetch_assoc()) {
        $antall = $_SESSION['handlekurv'][$vare['ProduktID']];
        $delsum = $vare['Pris'] * $antall;
        $totalSum += $delsum;
        
        // Lagre vareinformasjon for visning
        $varer[] = array(
            'navn' => $vare['navn'],
            'antall' => $antall,
            'pris' => $vare['Pris'],
            'delsum' => $delsum
        );
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasse - Nettbutikk</title>
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
                        <a class="nav-link" href="profil.php">Min Profil</a>
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
        <?php if (isset($_SESSION['bestilling_vellykket']) && $_SESSION['bestilling_vellykket']): ?>
            <div class="alert alert-success text-center">
                <h4 class="alert-heading">Takk for din bestilling!</h4>
                <p>Din bestilling med ordrenummer <?= $_SESSION['bestilling_id'] ?> er bekreftet.</p>
                <hr>
                <p class="mb-0">Du vil motta en bekreftelse på e-post snart.</p>
            </div>
            <?php 
            unset($_SESSION['bestilling_vellykket']);
            unset($_SESSION['bestilling_id']);
            ?>
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-primary">Fortsett å handle</a>
            </div>
        <?php elseif (!empty($varer)): ?>
            <h2 class="mb-4">Bekreft din bestilling</h2>
            
            <?php if (isset($feilmelding)): ?>
                <div class="alert alert-danger">
                    <?php echo $feilmelding; ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Bestillingsoversikt</h5>
                        </div>
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
                                        <?php foreach ($varer as $vare): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($vare['navn']); ?></td>
                                                <td><?php echo $vare['antall']; ?></td>
                                                <td><?php echo number_format($vare['pris'], 2, ',', ' '); ?> kr</td>
                                                <td><?php echo number_format($vare['delsum'], 2, ',', ' '); ?> kr</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td><strong><?php echo number_format($totalSum, 2, ',', ' '); ?> kr</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Fullfør bestilling</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <p>Ved å klikke på "Bekreft bestilling" godtar du våre handelsbetingelser.</p>
                                <button type="submit" class="btn btn-success w-100">Bekreft bestilling</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                Din handlekurv er tom. <a href="index.php">Gå til butikken</a> for å legge til varer.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
