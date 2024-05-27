<?php
require 'vendor/autoload.php'; // Adjust the path as necessary
use PhpOffice\PhpSpreadsheet\IOFactory;

function displayExcelFile($filePath)
{
    if (is_dir($filePath)) {
        // If the given path is a directory, open the 'susb' subfolder first
        $filePath = rtrim($filePath, '/') . '/susb/';
    }

    if (file_exists($filePath)) {
        $spreadsheet = IOFactory::load($filePath);
        $html = '';

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $worksheet = $spreadsheet->getSheetByName($sheetName);
            $html .= "<h3 class='my-3'>Sheet: $sheetName</h3>";
            $html .= '<div class="table-responsive"><table class="table table-striped table-bordered table-hover">';

            foreach ($worksheet->getRowIterator() as $row) {
                $html .= '<tr>';
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $value = htmlspecialchars($cell->getValue());
                    $coordinate = $cell->getCoordinate();
                    $html .= "<td data-coordinate='$coordinate' data-sheet='$sheetName' data-value='$value'>$value</td>";
                }
                $html .= '</tr>';
            }
            $html .= '</table></div>';
        }

        return $html;
    } else {
        return "File not found.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['coordinate']) && isset($_POST['value'])) {
    $fileName = $_GET['file'];
    $filePath = __DIR__ . '/CheckDirectory/' . $fileName;
    $spreadsheet = IOFactory::load($filePath);

    $coordinate = $_POST['coordinate'];
    $value = $_POST['value'];
    $sheetName = $_POST['sheet'];

    $spreadsheet->getSheetByName($sheetName)->getCell($coordinate)->setValue($value);

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($filePath);

    // Redirect to prevent form resubmission
    header("Location: {$_SERVER['REQUEST_URI']}");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Excel Viewer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <style>
        .table td {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="py-4">
        <?php
        if (isset($_GET['file'])) {
            echo "<div class='container'>";
            $fileName = $_GET['file'];
            $filePath = __DIR__ . '/CheckDirectory/' . $fileName;
            echo "<h2 class='my-4'>Displaying: " . htmlspecialchars($fileName) . "</h2>";
            if (is_dir($filePath)) {
                // If it's a directory, show its content
                $directory = rtrim($filePath, '/') . '/';
                echo "<h3>Contents of Directory: $directory</h3>";
                if ($dh = opendir($directory)) {
                    echo "<ul class='list-group'>";
                    while (($file = readdir($dh)) !== false) {
                        if ($file != "." && $file != "..") {
                            $filePath = $directory . $file;
                            echo "<li class='list-group-item'><a href='directory.php?file=" . urlencode($file) . "'>" . htmlspecialchars($file) . "</a></li>";
                        }
                    }
                    echo "</ul>";
                    closedir($dh);
                } else {
                    echo "<div class='alert alert-danger'>Unable to open directory.</div>";
                }
                echo "</div>";
            } else {
                // If it's a file, display its content
                echo displayExcelFile($filePath);
            }
            echo "<p><a class='btn btn-primary mt-3' href=''>Back to Directory Listing</a></p>";
        } else {
            // Directory to scan
            $directory = __DIR__ . '/CheckDirectory/';

            // Open the directory
            if (is_dir($directory)) {
                if ($dh = opendir($directory)) {
                    echo "<h6 class='my-4'>Files in directory: $directory</h6>";
                    echo "<ul class='list-group'>";
                    // Loop through the directory and list files
                    while (($file = readdir($dh)) !== false) {
                        if ($file != "." && $file != "..") {
                            // Determine the file extension
                            $extension = pathinfo($file, PATHINFO_EXTENSION);
                            // Output file link
                            if ($extension === 'xls' || $extension === 'xlsx') {
                                echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
                                echo "<a href='directory.php?file=" . urlencode($file) . "' target='_blank' class='d-flex align-items-center text-decoration-none'>";
                                echo "<div class='d-grid'>";
                                echo "<i class='fa-solid fa-file-excel text-success me-2'></i>";
                                echo "</div>";
                                echo "<div class='col text-dark'>" . htmlspecialchars($file) . "</div>";
                                echo "</a>";
                                echo "<a href='$directory$file' class='btn btn-sm btn-outline-secondary' download>Download</a>";
                                echo "</li>";
                            } elseif (in_array($extension, ['doc', 'docx'])) {
                                echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
                                echo "<a href='CheckDirectory/$file' class='d-flex align-items-center text-decoration-none'>";
                                echo "<div class='d-grid'>";
                                echo "<i class='fa-solid fa-file-word  me-2'></i>";
                                echo "</div>";
                                echo "<div class='col text-dark'>" . htmlspecialchars($file) . "</div>";
                                echo "</a>";
                                echo "<a href='$directory$file' class='btn btn-sm btn-outline-secondary' download>Download</a>";
                                echo "</li>";
                            } elseif (in_array($extension, ['pdf'])) {
                                echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
                                echo "<a href='CheckDirectory/$file' target='_blank'  class='d-flex align-items-center text-decoration-none'>";
                                echo "<div class='d-grid'>";
                                echo "<i class='fa-solid fa-file-pdf text-danger me-2'></i>";
                                echo "</div>";
                                echo "<div class='col text-dark'>" . htmlspecialchars($file) . "</div>";
                                echo "</a>";
                                echo "<a href='$directory$file' class='btn btn-sm btn-outline-secondary' download>Download</a>";
                                echo "</li>";
                            } else {
                                echo "<li class='list-group-item'>";
                                echo "<a href='directory.php?file=" . urlencode($file) . "' class='d-flex align-items-center text-decoration-none'> ";
                                echo "<div class='d-grid'>";
                                echo "<i class='fa-solid fa-folder text-warning me-2'></i>";
                                echo "</div>";
                                echo "<div class='col text-dark'>" . htmlspecialchars($file) . "</div>";
                                echo "</a>";
                                echo "</li>";
                            }
                        }
                    }
                    echo "</ul>";
                    closedir($dh);
                } else {
                    echo "<div class='alert alert-danger'>Unable to open directory.</div>";
                }
            } else {
                echo "<div class='alert alert-danger'>Directory does not exist.</div>";
            }
        }
        ?>
    </div>

    <!-- Modal for editing cell -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Cell</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="cellValue">Cell Value</label>
                            <input type="text" class="form-control" id="cellValue" name="value" required>
                        </div>
                        <input type="hidden" id="cellCoordinate" name="coordinate">
                        <input type="hidden" id="sheetName" name="sheet">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('table').on('click', 'td', function() {
                var coordinate = $(this).data('coordinate');
                var value = $(this).data('value');
                var sheet = $(this).data('sheet');
                $('#cellCoordinate').val(coordinate);
                $('#cellValue').val(value);
                $('#sheetName').val(sheet);
                $('#editModal').modal('show');
            });
        });
    </script>

</body>

</html>