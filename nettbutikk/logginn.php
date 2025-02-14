<?php
// Start sesjon og koble til databasen
session_start();
require_once 'db_config.php';

// Variabel for å lagre feilmeldinger
$error = "";

// Håndter innloggingsforsøk
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $epost = $_POST['epost'];
    $passord = $_POST['passord'];
    
    // Sjekk om brukeren eksisterer i databasen
    $resultat = $db->query("SELECT KundeID, Navn, etternavn, Passord FROM kunde WHERE Epost = '$epost'");
    
    if ($resultat->num_rows > 0) {
        $kunde = $resultat->fetch_assoc();
        // Verifiser at passordet er korrekt
        if (password_verify($passord, $kunde['Passord'])) {
            // Lagre brukerinfo i sesjonen
            $_SESSION['KundeID'] = $kunde['KundeID'];
            $_SESSION['Navn'] = $kunde['Navn'];
            $_SESSION['Etternavn'] = $kunde['etternavn'];
            header("Location: index.php");
            exit();
        }
    }
    $error = "Feil e-post eller passord";
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logg inn - Nettbutikk</title>
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
                        <a class="nav-link active" href="logginn.php">Logg inn</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="registrer.php">Registrer</a>
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
                        <h2 class="card-title text-center mb-4">Logg inn</h2>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="epost" class="form-label">E-post</label>
                                <input type="email" class="form-control" id="epost" name="epost" required>
                            </div>
                            <div class="mb-3">
                                <label for="passord" class="form-label">Passord</label>
                                <input type="password" class="form-control" id="passord" name="passord" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Logg inn</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>Har du ikke en konto? <a href="registrer.php">Registrer deg her</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>