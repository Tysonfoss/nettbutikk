<?php
// Start en ny økt for å holde styr på innloggingsstatus
session_start();
require_once 'db_config.php';

// Sjekk om bruker er logget inn, hvis ikke send til login-siden
if (!isset($_SESSION['KundeID'])) {
    header("Location: logginn.php");
    exit();
}

// Variabler for å vise meldinger til brukeren
$suksessmelding = "";
$feilmelding = "";

// Hvis skjemaet er sendt inn (POST-metode)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hent verdier fra skjema og gjør dem klare for database
    $produktnavn = trim($_POST['navn']);                                  // Fjern mellomrom før og etter
    $produktpris = floatval(str_replace(',', '.', $_POST['pris']));      // Gjør om til tall og bytt , med .
    $produktinfo = trim($_POST['info']);                                  // Fjern mellomrom før og etter
    $antallPåLager = intval($_POST['lagerbeholdning']);                  // Gjør om til heltall
    
    // Liste over feil som oppstår under validering
    $valideringsfeil = [];
    
    // Sjekk at produktnavn er fylt ut og ikke for langt
    if (empty($produktnavn)) {
        $valideringsfeil[] = "Produktnavn kan ikke være tomt";
    } elseif (strlen($produktnavn) > 100) {
        $valideringsfeil[] = "Produktnavn kan ikke være lengre enn 100 tegn";
    }
    
    // Sjekk at prisen er fornuftig
    if ($produktpris <= 0) {
        $valideringsfeil[] = "Pris må være større enn 0";
    } elseif ($produktpris > 1000000) {
        $valideringsfeil[] = "Pris kan ikke være høyere enn 1 000 000 kr";
    }
    
    // Sjekk at lagerbeholdning er fornuftig
    if ($antallPåLager < 0) {
        $valideringsfeil[] = "Lagerbeholdning kan ikke være negativ";
    } elseif ($antallPåLager > 10000) {
        $valideringsfeil[] = "Lagerbeholdning kan ikke være større enn 10 000";
    }

    // Last opp bilde hvis det er valgt
    $bildeSti = '';
    if (!empty($_FILES['produktbilde']['name'])) {
        // Hent filendelsen fra originalfilen (jpg, png, etc.)
        $filendelse = pathinfo($_FILES['produktbilde']['name'], PATHINFO_EXTENSION);
        
        // Lag et unikt filnavn basert på tidspunkt
        $nyttFilnavn = time() . '.' . $filendelse;
        
        // Sett sammen fullstendig filsti
        $målSti = 'bilder/' . $nyttFilnavn;
        
        // Flytt filen fra midlertidig mappe til bilder-mappen
        if (move_uploaded_file($_FILES['produktbilde']['tmp_name'], $målSti)) {
            $bildeSti = $nyttFilnavn;  // Lagre filnavnet for database
        }
    }
    
    // Hvis ingen valideringsfeil, legg til i database
    if (empty($valideringsfeil)) {
        try {
            // Lag SQL-spørring med prepared statement for sikkerhet
            $sql = "INSERT INTO Produkt (navn, Pris, info, lagerbeholdning, bilde) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            
            if ($stmt) {
                // Bind verdiene til spørringen
                $stmt->bind_param("sdsss", $produktnavn, $produktpris, $produktinfo, $antallPåLager, $bildeSti);
                
                // Utfør spørringen
                if ($stmt->execute()) {
                    $suksessmelding = "Produktet ble lagt til i databasen!";
                    // Nullstill skjemaverdier etter vellykket innsetting
                    $produktnavn = $produktpris = $produktinfo = $antallPåLager = "";
                } else {
                    $feilmelding = "Kunne ikke legge til produktet: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $feilmelding = "Databasefeil ved forberedelse av spørring: " . $db->error;
            }
        } catch (Exception $e) {
            $feilmelding = "En uventet feil oppstod: " . $e->getMessage();
        }
    } else {
        // Hvis det er valideringsfeil, vis dem til brukeren
        $feilmelding = implode("<br>", $valideringsfeil);
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legg til produkt - Nettbutikk</title>
    <!-- Bootstrap og andre stilark -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Stil for bildeforhåndsvisning */
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            display: none;
        }
    </style>
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
                    <?php if (isset($_SESSION['KundeID'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profil.php">Min Profil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bestillinger.php">Bestillinger</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="legg_til_produkt.php">Legg til produkt</a>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Legg til nytt produkt</h2>

                        <!-- Vis suksessmelding hvis det er noen -->
                        <?php if ($suksessmelding): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($suksessmelding); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Vis feilmelding hvis det er noen -->
                        <?php if ($feilmelding): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($feilmelding); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Skjema for å legge til produkt -->
                        <form method="post" class="needs-validation" novalidate enctype="multipart/form-data">
                            <!-- Produktnavn -->
                            <div class="mb-3">
                                <label for="navn" class="form-label">Produktnavn</label>
                                <input type="text" class="form-control" id="navn" name="navn" 
                                       value="<?php echo isset($produktnavn) ? htmlspecialchars($produktnavn) : ''; ?>" 
                                       maxlength="100" required>
                                <div class="invalid-feedback">Vennligst skriv inn et gyldig produktnavn (maks 100 tegn).</div>
                            </div>

                            <!-- Pris -->
                            <div class="mb-3">
                                <label for="pris" class="form-label">Pris (NOK)</label>
                                <div class="input-group">
                                    <span class="input-group-text">kr</span>
                                    <input type="number" class="form-control" id="pris" name="pris" 
                                           step="0.01" min="0.01" max="1000000"
                                           value="<?php echo isset($produktpris) ? htmlspecialchars($produktpris) : ''; ?>" 
                                           required>
                                    <div class="invalid-feedback">Vennligst skriv inn en gyldig pris (0.01 - 1 000 000 kr).</div>
                                </div>
                            </div>

                            <!-- Produktbeskrivelse -->
                            <div class="mb-3">
                                <label for="info" class="form-label">Produktbeskrivelse</label>
                                <textarea class="form-control" id="info" name="info" 
                                          rows="4"><?php echo isset($produktinfo) ? htmlspecialchars($produktinfo) : ''; ?></textarea>
                            </div>

                            <!-- Bildeopplasting -->
                            <div class="mb-3">
                                <label for="produktbilde" class="form-label">Produktbilde</label>
                                <input type="file" class="form-control" id="produktbilde" name="produktbilde" accept="image/*">
                                <small class="text-muted">Velg et bilde for produktet (valgfritt)</small>
                                <img id="bildeForhåndsvisning" class="image-preview" alt="Forhåndsvisning av bilde">
                            </div>

                            <!-- Lagerbeholdning -->
                            <div class="mb-4">
                                <label for="lagerbeholdning" class="form-label">Lagerbeholdning</label>
                                <input type="number" class="form-control" id="lagerbeholdning" 
                                       name="lagerbeholdning" min="0" max="10000"
                                       value="<?php echo isset($antallPåLager) ? htmlspecialchars($antallPåLager) : ''; ?>" 
                                       required>
                                <div class="invalid-feedback">Vennligst skriv inn et gyldig antall (0 - 10 000).</div>
                            </div>

                            <!-- Knapper -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Legg til produkt</button>
                                <a href="index.php" class="btn btn-outline-secondary">Avbryt</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Aktiver Bootstrap skjemavalidering
    (function () {
        'use strict'
        var skjemaer = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(skjemaer).forEach(function (skjema) {
            skjema.addEventListener('submit', function (hendelse) {
                if (!skjema.checkValidity()) {
                    hendelse.preventDefault()
                    hendelse.stopPropagation()
                }
                skjema.classList.add('was-validated')
            }, false)
        })
    })()

    // Vis forhåndsvisning av bilde når det velges
    document.getElementById('produktbilde').addEventListener('change', function(e) {
        const forhåndsvisning = document.getElementById('bildeForhåndsvisning');
        const fil = e.target.files[0];
        
        if (fil) {
            const leser = new FileReader();
            
            leser.onload = function(e) {
                forhåndsvisning.src = e.target.result;
                forhåndsvisning.style.display = 'block';
            }
            
            leser.readAsDataURL(fil);
        } else {
            forhåndsvisning.style.display = 'none';
        }
    });
    </script>
</body>
</html>
