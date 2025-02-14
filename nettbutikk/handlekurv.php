<?php
// Start sesjon og koble til databasen
session_start();
require_once 'db_config.php';

// Håndter oppdatering av antall varer i handlekurven
// Dette skjer når brukeren endrer antall eller oppdaterer handlekurven
if (isset($_POST['oppdater']) && isset($_POST['antall'])) {
    foreach ($_POST['antall'] as $vareId => $antall) {
        if ($antall > 0) {
            // Sjekk om det er nok varer på lager før oppdatering
            $sporring = $db->query("SELECT lagerbeholdning FROM Produkt WHERE ProduktID = $vareId");
            $vare = $sporring->fetch_assoc();
            
            // Hvis varen finnes og det er nok på lager, oppdater antallet
            if ($vare && $vare['lagerbeholdning'] >= $antall) {
                $_SESSION['handlekurv'][$vareId] = $antall;
            } else {
                // Hvis ikke nok på lager, sett antall til maksimum tilgjengelig
                $_SESSION['handlekurv'][$vareId] = $vare ? $vare['lagerbeholdning'] : 0;
                $_SESSION['feilmelding'] = "Noen varer hadde ikke nok på lager. Mengden er justert.";
            }
        } else {
            // Hvis antall er 0 eller negativt, fjern varen fra handlekurven
            unset($_SESSION['handlekurv'][$vareId]);
        }
    }
    $_SESSION['suksessmelding'] = "Handlekurven er oppdatert.";
    header("Location: handlekurv.php");
    exit();
}

// Håndter fjerning av enkeltvarer fra handlekurven
// Dette skjer når brukeren klikker på søppelkasse-ikonet
if (isset($_POST['fjern_vare']) && isset($_POST['vare_id'])) {
    unset($_SESSION['handlekurv'][$_POST['vare_id']]);
    $_SESSION['suksessmelding'] = "Varen er fjernet fra handlekurven.";
    header("Location: handlekurv.php");
    exit();
}

// Initialiser arrays for varer og total sum
$varer = array();
$totalSum = 0;

// Hvis handlekurven ikke er tom, hent informasjon om alle varene
if (!empty($_SESSION['handlekurv'])) {
    // Lag en kommaseparert liste av alle vare-IDer i handlekurven
    $vareIds = array_keys($_SESSION['handlekurv']);
    $vareIdsStr = implode(',', $vareIds);
    
    // Hent detaljert informasjon om hver vare fra databasen
    $sporring = $db->query("SELECT ProduktID, navn, Pris, lagerbeholdning FROM Produkt WHERE ProduktID IN ($vareIdsStr)");
    
    // Beregn delsum for hver vare og legg til i vare-arrayet
    while ($vare = $sporring->fetch_assoc()) {
        $antall = $_SESSION['handlekurv'][$vare['ProduktID']];
        $delsum = $vare['Pris'] * $antall;
        $totalSum += $delsum;
        
        // Lagre all nødvendig informasjon om varen for visning
        $varer[] = array(
            'id' => $vare['ProduktID'],
            'navn' => $vare['navn'],
            'pris' => $vare['Pris'],
            'antall' => $antall,
            'delsum' => $delsum,
            'lagerbeholdning' => $vare['lagerbeholdning']
        );
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handlekurv - Nettbutikk</title>
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
                        <a class="nav-link active" href="handlekurv.php">Handlekurv</a>
                    </li>
                    <?php if (isset($_SESSION['KundeID'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profil.php">Min Profil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="alle_bestillinger.php">Bestillinger</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="loggut.php">Logg ut</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="logginn.php">Logg inn</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="registrer.php">Registrer</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hovedinnhold -->
    <div class="container my-5">
        <h1 class="mb-4">Din Handlekurv</h1>
        
        <?php if (isset($_SESSION['feilmelding'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['feilmelding'];
                unset($_SESSION['feilmelding']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Lukk"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['suksessmelding'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['suksessmelding'];
                unset($_SESSION['suksessmelding']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Lukk"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($varer)): ?>
            <div class="alert alert-info">
                Handlekurven din er tom.
            </div>
            <a href="index.php" class="btn btn-primary">Fortsett å handle</a>
        <?php else: ?>
            <form method="post" action="">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Vare</th>
                                <th>Pris</th>
                                <th>Antall</th>
                                <th>Sum</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($varer as $vare): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vare['navn']); ?></td>
                                    <td><?php echo number_format($vare['pris'], 2, ',', ' '); ?> kr</td>
                                    <td>
                                        <input type="number" name="antall[<?php echo $vare['id']; ?>]" 
                                               value="<?php echo $vare['antall']; ?>" 
                                               min="0" max="<?php echo $vare['lagerbeholdning']; ?>" 
                                               class="form-control" style="width: 80px;">
                                    </td>
                                    <td><?php echo number_format($vare['delsum'], 2, ',', ' '); ?> kr</td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="vare_id" value="<?php echo $vare['id']; ?>">
                                            <button type="submit" name="fjern_vare" class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td><strong><?php echo number_format($totalSum, 2, ',', ' '); ?> kr</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between mt-3">
                    <a href="index.php" class="btn btn-secondary">Fortsett å handle</a>
                    <div>
                        <button type="submit" name="oppdater" class="btn btn-primary me-2">Oppdater handlekurv</button>
                        <?php if (isset($_SESSION['KundeID'])): ?>
                            <a href="kasse.php" class="btn btn-success">Gå til kassen</a>
                        <?php else: ?>
                            <a href="logginn.php" class="btn btn-success">Logg inn for å handle</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
