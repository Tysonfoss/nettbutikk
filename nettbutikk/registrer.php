<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "nynettbutikk";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Tilkobling mislyktes: " . $conn->connect_error);
}
$conn->set_charset("utf8");

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $navn = $_POST['navn'];
    $etternavn = $_POST['etternavn'];
    $epost = $_POST['epost'];
    $passord = $_POST['passord'];
    $adressa = $_POST['adressa'];
    $postnummer = $_POST['postnummer'];
    $poststad = $_POST['poststad'];
    
    // Sjekk om e-post allerede eksisterer
    $sql = "SELECT KundeID FROM kunde WHERE Epost = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $epost);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error = "E-postadressen er allerede registrert";
    } else {
        // Legg til ny kunde
        $sql = "INSERT INTO kunde (Navn, etternavn, Epost, Passord, adressa, postnummer, poststad) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $navn, $etternavn, $epost, $passord, $adressa, $postnummer, $poststad);
        
        if ($stmt->execute()) {
            $success = "Registrering vellykket! Du kan nå logge inn.";
        } else {
            $error = "Det oppstod en feil under registrering: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrer ny bruker</title>
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
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                                <br>
                                <a href="logginn.php" class="alert-link">Gå til innlogging</a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="navn" class="form-label">Navn</label>
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
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Registrer</button>
                                    <a href="logginn.php" class="btn btn-link">Har du allerede en konto? Logg inn</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>