<?php
session_start();


// TO HANDLE IF PDF GENERATION FAILED 
if (!isset($_SESSION['pdf_generated'])) {
    header("Location: index.php");
    exit();
}

// GETTING THE PDF FILE NAME
$pdf_filename = $_SESSION['pdf_filename'] ?? '';

// CLEAR SESSION VARIABLE 
unset($_SESSION['pdf_generated']);
unset($_SESSION['pdf_filename']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>PDF Download Page</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "Montserrat", sans-serif;
            font-optical-sizing: auto;
            font-style: normal;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card mt-2 mb-2">
            <h2 class="text text-center p-3">Download as PDF</h2>
            <div class="card-body">
                <p class="alert alert-success">PDF generated successfully.</p>
                <a href="uploads/<?= htmlspecialchars($pdf_filename); ?>" class="btn btn-primary" target="_blank">Download PDF</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>