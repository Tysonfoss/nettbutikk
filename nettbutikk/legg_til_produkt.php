<?php
session_start();

// Sjekk om bruker er logget inn
if (!isset($_SESSION['KundeID'])) {
    header("Location: logginn.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "nynettbutikk";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Tilkobling mislyktes: " . $conn->connect_error);
}
$conn->set_charset("utf8");

$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validering av input
    $navn = trim($_POST['navn']);
    $pris = floatval(str_replace(',', '.', $_POST['pris']));
    $info = trim($_POST['info']);
    $lagerbeholdning = intval($_POST['lagerbeholdning']);
    
    if (empty($navn)) {
        $error_message = "Produktnavn kan ikke være tomt";
    } elseif ($pris <= 0) {
        $error_message = "Pris må være større enn 0";
    } elseif ($lagerbeholdning < 0) {
        $error_message = "Lagerbeholdning kan ikke være negativ";
    } else {
        // Legg til produkt i databasen
        $sql = "INSERT INTO Produkt (navn, Pris, info, lagerbeholdning) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $navn, $pris, $info, $lagerbeholdning);
        
        if ($stmt->execute()) {
            $success_message = "Produktet ble lagt til!";
            // Tøm skjemaet ved å nullstille verdiene
            $navn = $pris = $info = $lagerbeholdning = "";
        } else {
            $error_message = "Det oppstod en feil: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legg til produkt</title>
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
                    <?php if (isset($_SESSION['KundeID'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profil.php">Min Profil</a>
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

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="mb-4">Legg til nytt produkt</h1>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="navn" class="form-label">Produktnavn</label>
                                <input type="text" class="form-control" id="navn" name="navn" 
                                       value="<?php echo isset($navn) ? htmlspecialchars($navn) : ''; ?>" required>
                                <div class="invalid-feedback">Vennligst skriv inn et produktnavn.</div>
                            </div>

                            <div class="mb-3">
                                <label for="pris" class="form-label">Pris</label>
                                <div class="input-group">
                                    <span class="input-group-text">kr</span>
                                    <input type="number" class="form-control" id="pris" name="pris" step="0.01" 
                                           value="<?php echo isset($pris) ? htmlspecialchars($pris) : ''; ?>" required>
                                    <div class="invalid-feedback">Vennligst skriv inn en gyldig pris.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="info" class="form-label">Produktbeskrivelse</label>
                                <textarea class="form-control" id="info" name="info" rows="4" required><?php echo isset($info) ? htmlspecialchars($info) : ''; ?></textarea>
                                <div class="invalid-feedback">Vennligst skriv inn en produktbeskrivelse.</div>
                            </div>

                            <div class="mb-4">
                                <label for="lagerbeholdning" class="form-label">Lagerbeholdning</label>
                                <input type="number" class="form-control" id="lagerbeholdning" name="lagerbeholdning" 
                                       value="<?php echo isset($lagerbeholdning) ? htmlspecialchars($lagerbeholdning) : ''; ?>" required>
                                <div class="invalid-feedback">Vennligst skriv inn et gyldig antall.</div>
                            </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Aktiver Bootstrap validering
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
</body>
</html>
