<?php
session_start();
require_once 'db_config.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $navn = $_POST['navn'];
    $etternavn = $_POST['etternavn'];
    $epost = $_POST['epost'];
    $passord = password_hash($_POST['passord'], PASSWORD_BCRYPT);
    $adressa = $_POST['adressa'];
    $postnummer = $_POST['postnummer'];
    $poststad = $_POST['poststad'];
    
    // Sjekk om e-post allerede eksisterer
    $resultat = $db->query("SELECT KundeID FROM kunde WHERE Epost = '$epost'");
    
    if ($resultat->num_rows > 0) {
        $error = "E-postadressen er allerede registrert";
    } else {
        $sql = "INSERT INTO kunde (Navn, etternavn, Epost, Passord, adressa, postnummer, poststad) 
                VALUES ('$navn', '$etternavn', '$epost', '$passord', '$adressa', '$postnummer', '$poststad')";
        
        if ($db->query($sql)) {
            $success = "Registrering vellykket! Du kan nå logge inn.";
        } else {
            $error = "Det oppstod en feil under registrering: " . $db->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrer ny bruker - Nettbutikk</title>
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
                        <a class="nav-link" href="logginn.php">Logg inn</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="registrer.php">Registrer</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Registrer ny bruker</h2>
                        
                        <?php if (isset($error) && $error != ""): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($success) && $success != ""): ?>
                            <div class="alert alert-success">
                                <?= $success ?>
                                <br>
                                <a href="logginn.php" class="alert-link">Klikk her for å logge inn</a>
                            </div>
                        <?php else: ?>
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="navn" class="form-label">Fornavn</label>
                                    <input type="text" class="form-control" id="navn" name="navn" required>
                                </div>
                                <div class="mb-3">
                                    <label for="etternavn" class="form-label">Etternavn</label>
                                    <input type="text" class="form-control" id="etternavn" name="etternavn" required>
                                </div>
                                <div class="mb-3">
                                    <label for="epost" class="form-label">E-post</label>
                                    <input type="email" class="form-control" id="epost" name="epost" required>
                                </div>
                                <div class="mb-3">
                                    <label for="passord" class="form-label">Passord</label>
                                    <input type="password" class="form-control" id="passord" name="passord" required>
                                </div>
                                <div class="mb-3">
                                    <label for="adressa" class="form-label">Adresse</label>
                                    <input type="text" class="form-control" id="adressa" name="adressa" required>
                                </div>
                                <div class="mb-3">
                                    <label for="postnummer" class="form-label">Postnummer</label>
                                    <input type="text" class="form-control" id="postnummer" name="postnummer" required>
                                </div>
                                <div class="mb-3">
                                    <label for="poststad" class="form-label">Poststed</label>
                                    <input type="text" class="form-control" id="poststad" name="poststad" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Registrer</button>
                                </div>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center mt-3">
                            <p>Har du allerede en konto? <a href="logginn.php">Logg inn her</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>