<?php
// serve_file.php

// Specify the path to the external directory
$externalDirectory = '/Users/jovinhampton/Documents/QA/';

if (isset($_GET['file'])) {
    $fileName = basename($_GET['file']); // Validate the file name to prevent directory traversal
    $filePath = $externalDirectory . $fileName;

    // Debugging output
    echo "File path: " . $filePath . "<br>";

    if (file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        echo "File not found: " . $filePath;
    }
} else {
    echo "No file specified.";
}
?>
