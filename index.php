<?php
session_start();
include("connection.php");

use PhpOffice\PhpSpreadsheet\IOFactory;

require_once('vendor/autoload.php');

// VARIABLE TO STORE SUCCESSFUL UPLOAD MESSAGE 
$uploadSuccessfull = null;

// VARIABLE TO HOLD ERROR MESSAGE 
$errorMsg = null;

// Store the filename in a session variable
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["exceldata"]) && $_FILES["exceldata"]["error"] == UPLOAD_ERR_OK) {
    $filename = $_FILES["exceldata"]["name"];
    $tempname = $_FILES["exceldata"]["tmp_name"];
    $uploaded_dir = 'uploads/';
    $uploaded_file = $uploaded_dir . basename($filename);

    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $extension_allowed = ['xls', 'xlsx'];

    // CHECKING IF THE FILE UPLOADED IS IN THE FORMAT ALLOWED
    if (!in_array($file_extension, $extension_allowed)) {
        $errorMsg = "File type not supported. Please upload an .xls or .xlsx file.";
        // CHECKING IF THE FILE UPLOADED IS WRITTABLE -> MY OWN PURPOSE
    } elseif (!is_writable($uploaded_dir)) {
        echo "Upload directory is not writable";
        // CHECKING IF THE FILE UPLOADED NAME ALREADY EXITS 
    } elseif (file_exists($uploaded_file)) {
        $errorMsg = "A file with the same name already exists";
    } elseif (!move_uploaded_file($tempname, $uploaded_file)) {
        echo "Failed to move uploaded file";
    } else {
        $uploadSuccessfull = "File uploaded successfully.";

        // STORING THE EXCEL FILE NAME BEACUSE STATE GETS LOST BETWEEN REQUESTS
        $_SESSION['uploaded_file_name'] = $filename;

        try {
            $spreadsheet = IOFactory::load($uploaded_file);
            $excelSheet = $spreadsheet->getActiveSheet();
            $spreadSheetArray = $excelSheet->toArray();
            $spreadSheetDataCount = count($spreadSheetArray);

            // INSERTING EXCEL DATA INTO DB
            for ($i = 1; $i < $spreadSheetDataCount; $i++) {
                $sql = "INSERT INTO demo_data (first_name, last_name, gender, country, age, entry_date, id) VALUES ('{$spreadSheetArray[$i][1]}', '{$spreadSheetArray[$i][2]}', '{$spreadSheetArray[$i][3]}', '{$spreadSheetArray[$i][4]}', '{$spreadSheetArray[$i][5]}', '{$spreadSheetArray[$i][6]}', '{$spreadSheetArray[$i][7]}')";
                mysqli_query($conn, $sql);
            }
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            $errorMsg = "Error loading file: " . $e->getMessage();
        }
    }
} else {
    $errorMsg = "You first need to select a file to upload";
}

// FETCHING THE DATA FROM THE DATABASE 
$query = "SELECT * FROM demo_data";
$result = mysqli_query($conn, $query);

// HANDLING PDF DOWNLOAD REQUEST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["download_pdf"])) {
    $original_filename = $_SESSION['uploaded_file_name'] ?? 'geekworkz';
    $timestamp = date('Y-m-d_H-i-s');
    $pdf_filename = pathinfo($original_filename, PATHINFO_FILENAME) . "_$timestamp.pdf";

    // GENERATING PDF with filename based on uploaded file name and timestamp
    generatePDF($result, $pdf_filename);

    header("Location: pdf_result.php");
    exit();
}

function generatePDF($result, $pdf_filename)
{
    // Configure mPDF to use a writable directory for PDF files
    $config = [
        'tempDir' => __DIR__ . '/uploads'
    ];
    $mpdf = new \Mpdf\Mpdf($config);

    $html = '
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
            thead tr{
            border: 2px solid blue;
}

        th, td {
            padding: 10px;
            text-align: left;
        }
    </style>
    <h2>Uploaded Data</h2>
    <table>
        <thead>
            <tr>
                <th>Index</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Gender</th>
                <th>Country</th>
                <th>Age</th>
                <th>Date</th>
                <th>ID</th>
            </tr>
        </thead>
        <tbody>';

    while ($row = mysqli_fetch_assoc($result)) {
        $html .= '<tr>
            <td>' . htmlspecialchars($row['index']) . '</td>
            <td>' . htmlspecialchars($row['first_name']) . '</td>
            <td>' . htmlspecialchars($row['last_name']) . '</td>
            <td>' . htmlspecialchars($row['gender']) . '</td>
            <td>' . htmlspecialchars($row['country']) . '</td>
            <td>' . htmlspecialchars($row['age']) . '</td>
            <td>' . htmlspecialchars($row['entry_date']) . '</td>
            <td>' . htmlspecialchars($row['id']) . '</td>
        </tr>';
    }

    $html .= '</tbody></table>';

    $mpdf->WriteHTML($html);
    $mpdf->Output("uploads/{$pdf_filename}", \Mpdf\Output\Destination::FILE);

    // SET SESION VALUES TO INDICATE THE PDF GENARATION COMPLETE 
    $_SESSION['pdf_generated'] = true;
    $_SESSION['pdf_filename'] = $pdf_filename;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Excel Upload Demo</title>
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

        @media print {
            .no-print {
                display: none;
            }

            .print-only {
                display: block;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card mt-2 mb-2 no-print">
            <h2 class="text text-center p-3">Upload Excel Data</h2>
            <div class="card-header">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="mb-3 mt-3">
                        <label for="file_upload" class="form-label">Upload Excel</label>
                        <input type="file" class="form-control" name="exceldata">
                    </div>
                    <button type="submit" name="upload_btn" value="upload_btn" class="btn btn-primary">Upload</button>
                </form>
            </div>
        </div>

        <!-- DISPLAY DB DATA IN TABLE VIEW ONLY IF UPLOAD IS SUCCESSFUL-->
        <?php if ($uploadSuccessfull) { ?>
            <div class="alert alert-success no-print">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <?= $uploadSuccessfull ?>
                    </div>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <button type="submit" name="download_pdf" value="download_pdf" class="btn btn-secondary">
                            Generate PDF
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mt-2 mb-2 print-only">
                <div class="card-header">
                    <h2 class="text-center p-3">Uploaded Data</h2>
                </div>
                <div class="no-print"></div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Index</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Gender</th>
                                <th>Country</th>
                                <th>Age</th>
                                <th>Date</th>
                                <th>ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['index']); ?></td>
                                    <td><?= htmlspecialchars($row['first_name']); ?></td>
                                    <td><?= htmlspecialchars($row['last_name']); ?></td>
                                    <td><?= htmlspecialchars($row['gender']); ?></td>
                                    <td><?= htmlspecialchars($row['country']); ?></td>
                                    <td><?= htmlspecialchars($row['age']); ?></td>
                                    <td><?= htmlspecialchars($row['entry_date']); ?></td>
                                    <td><?= htmlspecialchars($row['id']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } elseif ($errorMsg && isset($_POST["upload_btn"])) { ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
        <?php } ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>