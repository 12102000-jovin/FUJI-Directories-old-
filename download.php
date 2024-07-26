<?php
$file = $_GET['file'];
$baseDir = '../../../../../Applications/QA'; // Base directory where files are located

// Sanitize the file name to prevent directory traversal attacks
$fileName = basename($file);
$filePath = $baseDir . '/' . $fileName;

if (file_exists($filePath)) {
    // Determine the file's MIME type
    $mimeType = mime_content_type($filePath);

    // Serve the file with appropriate headers
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: inline; filename="' . $fileName . '"'); // Change to "attachment" for download
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit();
} else {
    http_response_code(404);
    echo 'File not found.';
}
?>
