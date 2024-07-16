<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

// Connect to the database
require_once ("../db_connect.php");

// Get role from session
$role = $_SESSION["role"];

// SQL Query to retrieve QA details
$qa_sql = "SELECT * FROM quality_assurance";
$qa_result = $conn->query($qa_sql);

//  ========================= O P E N  (Q A)  D O C U M E N T ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["qa_document"])) {
    $qaDocument = $_POST["qa_document"];

    $directory = "../../../../../QA/$qaDocument.pdf";

    // Escape the directory path for security
    $escaped_directory = escapeshellarg($directory);

    // Construct the shell command
    $command = "open {$escaped_directory}";

    // Execute the shell command
    $output = shell_exec($command);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <title>Quality Assurances</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <style>
        .canvasjs-chart-credit {
            display: none !important;
        }

        .canvasjs-chart-canvas {
            border-radius: 12px;
        }

        #chartContainer {
            border-radius: 12px;
        }

        .nav-underline .nav-item .nav-link {
            color: black;
        }

        .nav-underline .nav-item .nav-link.active {
            color: #043f9d;
        }

        .nav-underline .nav-item .nav-link:hover {
            background-color: #043f9d;
            color: white;
            border-bottom: 2px solid #54B4D3;
            /* border-radius: 10px; */

        }

        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }
    </style>
</head>

<body class="background-color">
    <?php require_once ("../Menu/QAStaticTopMenu.php"); ?>
    <div class="container-fluid px-md-5 mb-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="http://localhost/FUJI-Directories/index.php">Home</a></li>
                <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">QA</li>
            </ol>
        </nav>

        <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
            <table class="table table-hover table-bordered mb-0 pb-0">
                <thead>
                    <tr class="text-center">
                        <th class="py-4 align-middle col-md-1">QA Document</th>
                        <th class="py-4 align-middle col-md-1">Document Name</th>
                        <th class="py-4 align-middle col-md-2">Document Description</th>
                        <?php if ($role === "admin") { ?>
                            <th class="py-4 align-middle">Rev No.</th>
                            <th class="py-4 align-middle col-md-1">WIP Doc Link</th>
                            <th class="py-4 align-middle">Department</th>
                            <th class="py-4 align-middle">Type</th>
                            <th class="py-4 align-middle">Owner</th>
                            <th class="py-4 align-middle">Status</th>
                            <th class="py-4 align-middle">Approved By</th>
                            <th class="py-4 align-middle">Last Updated</th>
                            <th class="py-4 align-middle">Revision Status</th>
                            <th class="py-4 align-middle">ISO 9001</th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $qa_result->fetch_assoc()) { ?>
                        <tr>
                            <td class="py-2 align-middle">
                                <form method="POST">
                                    <input type="hidden" value=<?= $row['qa_document'] ?> name="qa_document">
                                    <button type="submit"
                                        class="btn btn-link p-0 m-0 text-decoration-underline fw-bold"><?= $row["qa_document"] ?></button>
                                </form>
                            </td>
                            <td class="py-2 align-middle">
                                <?= $row["document_name"] ?>
                            </td>
                            <td class="py-2 align-middle">
                                <?= $row["document_description"] ?>
                            </td>
                            <?php if ($role === "admin") { ?>
                                <td class="py-2 align-middle text-center">
                                    <?= $row["rev_no"] ?>
                                </td>
                                <td class="py-2 align-middle text-center">
                                    <a href="<?= $row["wip_doc_link"] ?>"><?= $row["qa_document"] ?></a>
                                </td>
                                <td class="py-2 align-middle text-center">
                                    <?= $row["department"] ?>
                                </td>
                                <td class="py-2 align-middle text-center">
                                    <?= $row["type"] ?>
                                </td>
                                <td class="py-2 align-middle text-center">
                                    <?= $row["owner"] ?>
                                </td>
                                <td class="py-2 align-middle text-center">
                                    <?= $row["status"] ?>
                                </td>
                                <td class="py-2 align-middle text-center">
                                    <?= isset($row["approved_by"]) ? $row["approved_by"] : "N/A" ?>
                                </td>
                                <td class="py-2 align-middle text-center">
                                    <?= date("j F Y", strtotime($row["last_updated"])) ?>
                                </td>
                                <td class="py-2 align-middle text-center">
                                    <?= $row["revision_status"] ?>
                                </td>
                                <td class="py-2 align-middle text-center">
                                    <?php if ($row["iso_9001"] == 1) { ?>
                                        Yes
                                    <?php } else if ($row["iso_9001"] == 0) { ?>
                                        No
                                    <?php } else { ?>
                                        N/A
                                    <?php } ?>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>