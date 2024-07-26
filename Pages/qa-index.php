<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Connect to the database
require_once ("../db_connect.php");
require_once ("../status_check.php");

// Get role from session
$role = $_SESSION["role"];

// Sorting Variables
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'qa_document';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Pagination
$records_per_page = isset($_GET["recordsPerPage"]) ? intval($_GET["recordsPerPage"]) : 10; // Number of records per page
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1; //Current Page
$offset = ($page - 1) * $records_per_page; // Offset for SQL query

// Get search term
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Get status filter
$statusFilter = isset($_GET['statusFilter']) ? $conn->real_escape_string($_GET['statusFilter']) : '';

// Get revision status filter
$revisionStatusFilter = isset($_GET['revisionStatusFilter']) ? $conn->real_escape_string($_GET['revisionStatusFilter']) : '';

// SQL Query to retrieve QA details with LIMIT for pagination
$qa_sql = "SELECT * FROM quality_assurance WHERE
    (qa_document LIKE '%$searchTerm%' OR
    document_name LIKE '%$searchTerm%' OR
    document_description LIKE '%$searchTerm%' OR
    department LIKE '%$searchTerm%')
    AND (status = '$statusFilter' OR '$statusFilter' = '')
    AND (revision_status = '$revisionStatusFilter' OR '$revisionStatusFilter' = '')
    ORDER BY $sort $order 
    LIMIT $offset, $records_per_page";
$qa_result = $conn->query($qa_sql);

// Get total number of records
$total_records_sql = "SELECT COUNT(*) AS total FROM quality_assurance WHERE
    (qa_document LIKE '%$searchTerm%' OR
    document_name LIKE '%$searchTerm%' OR
    document_description LIKE '%$searchTerm%' OR
    department LIKE '%$searchTerm%')
    AND (status = '$statusFilter' OR '$statusFilter' = '')
    AND (revision_status = '$revisionStatusFilter' OR '$revisionStatusFilter' = '')";

$total_records_result = $conn->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);


//  ========================= O P E N  (Q A)  D O C U M E N T [PDF] ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["qa_document"])) {
    $qaDocument = $_POST["qa_document"];

    $directory = "../../../../../QA/$qaDocument.pdf";

    // Escape the directory path for security
    $escaped_directory = escapeshellarg($directory);

    // Determine the operating system
    $os = strtoupper(substr(PHP_OS, 0, 3));

    if ($os === 'WIN') {
        // Windows Command Shell
        $command = "start \"\" " . $escaped_directory;
    } else if ($os === "DAR") {
        // macOS Shell Command
        $command = "open {$escaped_directory}";
    } else {
        // Unix-based command (Linux)
        $command = "xdg-open " . $escaped_directory;
    }

    // Execute the shell command and capture output and return status
    $output = [];
    $return_var = 0;
    exec($command . ' 2>&1', $output, $return_var);

    // Check if the command was executed successfully
    if ($return_var !== 0) {
        // Build the current URL with query parameters
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }

        // Output JavaScript to show alert and reload the page with parameters
        echo "<script>
                alert('Failed to open the document.');
                window.location.href = '" . $current_url . "';
              </script>";
        exit();
    }
}

// =========================  O P E N  (Q A)  D O C U M E N T [DOC] =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["wip_document"])) {
    $wipDocument = $_POST["wip_document"];
    $directory = "../../../../../QA/$wipDocument.docx";

    // Escape the directory path for security
    $escaped_directory = escapeshellarg($directory);

    // Determine the operating system
    $os = strtoupper(substr(PHP_OS, 0, 3));

    // Construct the command based on the OS
    if ($os === "WIN") {
        // Windows Command Shell
        $command = "start \"\" " . $escaped_directory;
    } elseif ($os === "DAR") {
        // macOS Shell Command
        $command = "open " . $escaped_directory;
    } else {
        // Unix-based command (Linux)
        $command = "xdg-open " . $escaped_directory;
    }

    // Execute the shell command and capture output and return status
    $output = [];
    $return_var = 0;
    exec($command . ' 2>&1', $output, $return_var);

    // Check if the command was executed successfully
    if ($return_var !== 0) {
        // Build the current URL with query parameters
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }

        // Output JavaScript to show alert and reload the page with parameters
        echo "<script>
                alert('Failed to open the document.');
                window.location.href = '" . $current_url . "';
              </script>";
        exit();
    }
}

// ========================= A D D   D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["addDocument"])) {
    $qaDocument = $_POST["qaDocument"];
    $documentName = $_POST["documentName"];
    $documentDescription = $_POST["documentDescription"];
    $revNo = $_POST["revNo"];
    $wipDocLink = $_POST["qaDocument"];
    $department = $_POST["department"];
    $type = $_POST["type"];
    $owner = $_POST["owner"];
    $status = $_POST["status"];
    $approvedBy = $_POST["approvedBy"];
    $lastUpdated = $_POST["lastUpdated"];
    $revisionStatus = $_POST["revisionStatus"];
    $ISO9001 = $_POST["iso9001"];

    $add_document_sql = "INSERT INTO quality_assurance (qa_document, document_name, document_description, rev_no, wip_doc_link, department, type, owner, status, approved_by, last_updated, revision_status, iso_9001) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $add_document_result = $conn->prepare($add_document_sql);
    $add_document_result->bind_param("ssssssssssssi", $qaDocument, $documentName, $documentDescription, $revNo, $wipDocLink, $department, $type, $owner, $status, $approvedBy, $lastUpdated, $revisionStatus, $ISO9001);

    // Execute the prepared statement
    if ($add_document_result->execute()) {
        // Build the current URL with query parameters
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        // Redirect to the same URL with parameters
        header("Location: " . $current_url);
        exit();
    } else {
        // Improved error reporting
        echo "Error updating record: " . $conn->error;
    }
    $add_document_result->close();
}

// ========================= D E L E T E  D O C U M E N T =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["qaIdToDelete"])) {
    $qaIdToDelete = $_POST["qaIdToDelete"];

    $delete_document_sql = "DELETE FROM quality_assurance WHERE qa_id = ?";
    $delete_document_result = $conn->prepare($delete_document_sql);
    $delete_document_result->bind_param("i", $qaIdToDelete);

    if ($delete_document_result->execute()) {
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: " . $current_url);
        exit();

    } else {
        echo "Error: " . $delete_document_result . "<br>" . $conn->error;
    }
    $delete_document_result->close();
}

// ========================= E D I T  D O C U M E N T  =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["qaIdToEdit"])) {
    $qaIdToEdit = $_POST["qaIdToEdit"];
    $qaDocumentToEdit = $_POST["qaDocumentToEdit"];
    $documentNameToEdit = $_POST["documentNameToEdit"];
    $documentDescriptionToEdit = $_POST["documentDescriptionToEdit"];
    $revNoToEdit = $_POST["revNoToEdit"];
    $wipDocLinkToEdit = $_POST["qaDocumentToEdit"];
    $departmentToEdit = $_POST["departmentToEdit"];
    $typeToEdit = $_POST["typeToEdit"];
    $ownerToEdit = $_POST["ownerToEdit"];
    $statusToEdit = $_POST["statusToEdit"];
    $approvedByToEdit = $_POST["approvedByToEdit"];
    $lastUpdatedToEdit = $_POST["lastUpdatedToEdit"];
    $revisionStatusToEdit = $_POST["revisionStatusToEdit"];
    $ISO9001ToEdit = $_POST["iso9001ToEdit"];

    // echo $qaIdToEdit . $qaDocumentToEdit . $documentNameToEdit . $documentDescriptionToEdit . $revNoToEdit 
    // . $wipDocLinkToEdit . $departmentToEdit . $typeToEdit . $ownerToEdit . $statusToEdit . $approvedByToEdit 
    // .$lastUpdatedToEdit . $revisionStatusToEdit . $ISO9001ToEdit;

    $edit_document_sql = "UPDATE quality_assurance SET qa_document = ?, document_name = ?, document_description = ?, rev_no = ?,  wip_doc_link = ?, 
    department = ?, type = ?, owner = ?, status = ?, approved_by = ?, last_updated = ?, revision_status = ?, iso_9001 = ? WHERE qa_id = ? ";
    $edit_document_result = $conn->prepare($edit_document_sql);
    $edit_document_result->bind_param(
        "sssssssssssssi",
        $qaDocumentToEdit,
        $documentNameToEdit,
        $documentDescriptionToEdit,
        $revNoToEdit,
        $wipDocLinkToEdit,
        $departmentToEdit,
        $typeToEdit,
        $ownerToEdit,
        $statusToEdit,
        $approvedByToEdit,
        $lastUpdatedToEdit,
        $revisionStatusToEdit,
        $ISO9001ToEdit,
        $qaIdToEdit
    );

    if ($edit_document_result->execute()) {
        // Build the current URL with query parameters
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }

        // Redirect to the same URL with parameters
        header("Location: " . $current_url);
        exit();
    } else {
        echo "Error updating record: " . $edit_document_result->error;
    }

}

// ========================= E D I T  S T A T U S ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["statusCellToEdit"])) {
    $qaIdToEdit = $_POST["qaIdToEditStatusCell"];
    $statusToEdit = $_POST["statusCellToEdit"];

    $update_status_sql = "UPDATE quality_assurance SET status = ? WHERE qa_id = ?";
    $update_status_result = $conn->prepare($update_status_sql);
    $update_status_result->bind_param("si", $statusToEdit, $qaIdToEdit);

    if ($update_status_result->execute()) {
        // Redirect to the same URL to refresh data
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: " . $current_url);
        exit();
    } else {
        echo "Error updating status: " . $update_status_result->error;
    }
}

// ========================= E D I T  R E V I S I O N  S T A T U S =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["revisionStatusCellToEdit"])) {
    $qaIdToEdit = $_POST["qaIdToEditRevisionStatusCell"];
    $revisionStatusToEdit = $_POST["revisionStatusCellToEdit"];

    $update_revision_status_sql = "UPDATE quality_assurance SET revision_status = ? WHERE qa_id = ?";
    $update_revision_status_result = $conn->prepare($update_revision_status_sql);
    $update_revision_status_result->bind_param("si", $revisionStatusToEdit, $qaIdToEdit);

    if ($update_revision_status_result->execute()) {
        // Redirect to the same URL to refresh data
        $current_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $current_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: " . $current_url);
        exit();
    } else {
        echo "Error updating status: " . $update_revision_status_result->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Quality Assurances</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="../Images/FE-logo-icon.ico" />
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

        /* Style for checked state */
        .btn-check:checked+.btn-custom {
            background-color: #043f9d !important;
            border-color: #043f9d !important;
            color: white !important;
        }

        /* Optional: Adjust hover state if needed */
        .btn-custom:hover {
            background-color: #032b6b;
            border-color: #032b6b;
            color: white;
        }

        .pagination .page-item.active .page-link {
            background-color: #043f9d;
            border-color: #043f9d;
        }

        .pagination .page-link {
            color: black
        }

        .table-container {
            width: 100%;
            height: 100%;
        }

        .modal-backdrop.show {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1040;
        }
    </style>

    <style>
        #loading-indicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 9999;
        }

        .spinner {
            border: 16px solid #f3f3f3;
            border-top: 16px solid #3498db;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="background-color">
    <?php require_once ("../Menu/QAStaticTopMenu.php"); ?>
    <div class="container-fluid px-md-5 mb-5 mt-4">
        <div class="d-flex justify-content-between align-items-center">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="http://localhost/FUJI-Directories/index.php">Home</a></li>
                    <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">QA</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-end mb-3">
                <div class="d-flex align-items-start me-2 mt-0 pt-0">
                    <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#filterColumnModal">
                        <i class="fa-solid fa-sliders me-1"></i>Filter Column</button>
                </div>
                <div class="btn-group shadow-lg" role="group" aria-label="Zoom Controls">
                    <button class="btn btn-sm btn-light" style="cursor:pointer" onclick="zoom(0.8)"><i
                            class="fa-solid fa-magnifying-glass-minus"></i></button>
                    <button class="btn btn-sm btn-light" style="cursor:pointer" onclick="zoom(1.2)"><i
                            class="fa-solid fa-magnifying-glass-plus"></i></button>
                    <button class="btn btn-sm btn-danger" style="cursor:pointer" onclick="resetZoom()"><small
                            class="fw-bold">Reset</small></button>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="col-8 col-lg-5">
                    <form method="GET">
                        <div class="d-flex align-items-center">
                            <div class="input-group me-2">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="search" class="form-control" id="searchDocuments" name="search"
                                    placeholder="Search Documents" value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                            <button class="btn" type="submit"
                                style="background-color:#043f9d; color: white; transition: 0.3s ease !important;">Search
                            </button>
                            <button class="btn btn-danger ms-2"><a class="dropdown-item" href="#"
                                    onclick="clearURLParameters()">Clear</a></button>
                            <div class="dropdown">
                                <button class="btn btn-outline-dark dropdown-toggle ms-2" type="button"
                                    id="departmentDropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                    Department
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="departmentDropdownMenuButton">
                                    <li><a class="dropdown-item" href="#" onclick="updateSearchQuery('')">All
                                            Departments</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="updateSearchQuery('Accounts')">Accounts</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="updateSearchQuery('Engineering')">Engineering</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="updateSearchQuery('Estimating')">Estimating</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="updateSearchQuery('Electrical')">Electrical</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="updateSearchQuery('Human Resources')">Human Resources</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="updateSearchQuery('Management')">Management</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="updateSearchQuery('Operations Support')">Operations Support</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="updateSearchQuery('Quality Assurance')">Quality Assurance</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="updateSearchQuery('Quality Control')">Quality Control</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="updateSearchQuery('Research & Development')">Research &
                                            Development</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="updateSearchQuery('Sheet Metal')">Sheet Metal</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="updateSearchQuery('Special Projects')">Special Projects</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"
                                            onclick="updateSearchQuery('Work, Health and Safety')">Work, Health and
                                            Safety</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if ($role === "admin") { ?>
                    <div class="d-flex justify-content-end align-items-center col-4 col-lg-7">
                        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addDocumentModal"> <i
                                class="fa-solid fa-plus"></i> Add Document</button>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="table-responsive rounded-3 shadow-lg bg-light m-0">
            <table class="table table-hover mb-0 pb-0">
                <thead>
                    <tr class="text-center">
                        <?php if ($role === "admin") { ?>
                            <th></th>
                        <?php } ?>
                        <th class="py-4 align-middle qaDocument" style="min-width:200px">
                            <a onclick="updateSort('qa_document', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor: pointer;">
                                QA Document <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle documentName" style="min-width:200px">
                            <a onclick="updateSort('document_name', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor: pointer;">
                                Document Name<i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <th class="py-4 align-middle documentDescription" style="min-width:400px">
                            <a onclick="updateSort('document_description', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                class="text-decoration-none text-white" style="cursor: pointer;">
                                Document Description <i class="fa-solid fa-sort fa-md ms-1"></i>
                            </a>
                        </th>
                        <?php if ($role === "admin") { ?>
                            <th class="py-4 align-middle revNo" style="min-width:100px">
                                <a onclick="updateSort('rev_no','<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor: pointer;">
                                    Rev No. <i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>
                            <th class="py-4 align-middle revisionStatus">
                                <div class="dropdown">
                                    <a class="text-decoration-none text-white" href="#" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Revision Status<i class="fa-solid fa-sort fa-md ms-2"></i>
                                    </a>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item"
                                            onclick="updateSort('revision_status','<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                            class="text-decoration-none text-white" style="cursor:pointer">
                                            <?php if ($order === 'asc') { ?>
                                                <i class="fa-solid fa-arrow-down-z-a me-2 signature-color"></i> Sort Z-A
                                            <?php } else if ($order === 'desc') { ?>
                                                    <i class="fa-solid fa-arrow-down-z-a me-2 signature-color"></i> Sort A-Z
                                            <?php } ?>
                                        </a>
                                        <a class="dropdown-item" data-bs-toggle="modal"
                                            data-bs-target="#filterRevisionStatusModal">
                                            <i class="fa-solid fa-filter me-2 signature-color"></i>Sort By Revision Status
                                        </a>
                                    </div>
                                </div>
                                <!-- <a onclick="updateSort('revision_status', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor:pointer">
                                    Revision Status <i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a> -->
                            </th>
                            <th class="py-4 align-middle wipDocLink" style="min-width:200px">
                                <a onclick="updateSort('wip_doc_link', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor: pointer;">
                                    WIP Doc Link <i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>
                            <th class="py-4 align-middle department" style="min-width:200px">
                                <a onclick="updateSort('department', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor: pointer;">
                                    Department<i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>
                            <th class="py-4 align-middle type">
                                <a onclick="updateSort('Type', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor: pointer;">
                                    Type<i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>
                            <th class="py-4 align-middle owner">
                                <a onclick="updateSort('Owner', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor:pointer">
                                    Owner<i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>
                            <th class="py-4 align-middle status">
                                <div class="dropdown">
                                    <a class="text-decoration-none text-white" href="#" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Status<i class="fa-solid fa-sort fa-md ms-2"></i>
                                    </a>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item"
                                            onclick="updateSort('Status','<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                            class="text-decoration-none text-white" style="cursor:pointer">
                                            <?php if ($order === 'asc') { ?>
                                                <i class="fa-solid fa-arrow-down-z-a me-2 signature-color"></i> Sort Z-A
                                            <?php } else if ($order === 'desc') { ?>
                                                    <i class="fa-solid fa-arrow-down-z-a me-2 signature-color"></i> Sort A-Z
                                            <?php } ?>
                                        </a>
                                        <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#filterStatusModal">
                                            <i class="fa-solid fa-filter me-2 signature-color"></i>Sort By Status
                                        </a>
                                    </div>
                                </div>
                            </th>
                            <th class="py-4 align-middle approvedBy" style="min-width:200px">
                                <a onclick="updateSort('approved_by', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor:pointer">
                                    Approved By<i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>
                            <th class="py-4 align-middle lastUpdated" style="min-width:200px">
                                <a onclick="updateSort('last_updated', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor:pointer">
                                    Last Updated <i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>

                            <th class="py-4 align-middle iso9001" style="min-width:120px">
                                <a onclick="updateSort('iso_9001', '<?= $order == 'asc' ? 'desc' : 'asc' ?>')"
                                    class="text-decoration-none text-white" style="cursor:pointer">
                                    ISO 9001 <i class="fa-solid fa-sort fa-md ms-1"></i>
                                </a>
                            </th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($qa_result->num_rows > 0) { ?>
                        <?php while ($row = $qa_result->fetch_assoc()) { ?>
                            <tr class="document-row">
                                <?php if ($role === "admin") { ?>
                                    <td class="align-middle">
                                        <div class="d-flex">
                                            <button class="btn" data-bs-toggle="modal" data-bs-target="#editDocumentModal"
                                                data-qa-id="<?= $row["qa_id"] ?>" data-qa-document="<?= $row["qa_document"] ?>"
                                                data-document-name="<?= $row["document_name"] ?>"
                                                data-document-description="<?= $row["document_description"] ?>"
                                                data-rev-no="<?= $row["rev_no"] ?>" data-department="<?= $row["department"] ?>"
                                                data-type="<?= $row["type"] ?>" data-owner="<?= $row["owner"] ?>"
                                                data-status="<?= $row["status"] ?>" data-approved-by="<?= $row["approved_by"] ?>"
                                                data-last-updated="<?= $row["last_updated"] ?>"
                                                data-revision-status="<?= $row["revision_status"] ?>"
                                                data-iso-9001="<?= $row["iso_9001"] ?>">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>

                                            <button class="btn" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                                data-qa-id="<?= $row["qa_id"] ?>" data-qa-document="<?= $row["qa_document"] ?>"><i
                                                    class="fa-regular fa-trash-can text-danger"></i></button>
                                        </div>
                                    </td>
                                <?php } ?>
                                <td class="py-2 align-middle text-center document-title qaDocument">
                                    <form class="document-form" method="POST">
                                        <input type="hidden" value=<?= $row['qa_document'] ?> name="qa_document">
                                        <a href="download.php?file=<?= urlencode($row['qa_document']) ?>.pdf" target="_blank"
                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">
                                            <?= htmlspecialchars($row["qa_document"], ENT_QUOTES, 'UTF-8') ?>
                                        </a>

                                    </form>
                                </td>
                                <td class="py-2 align-middle document-name documentName">
                                    <?= $row["document_name"] ?>
                                </td>
                                <td class="py-2 align-middle documentDescription">
                                    <?= $row["document_description"] ?>
                                </td>
                                <?php if ($role === "admin") { ?>
                                    <td class="py-2 align-middle text-center revNo">
                                        <?= $row["rev_no"] ?>
                                    </td>
                                    <td class="py-2 align-middle text-center revisionStatus" ondblclick="editRevisionStatus(this)">
                                        <form method="POST" class="edit-revision-status-form" style="display:none">
                                            <div class="d-flex align-items-center">
                                                <input type="hidden" name="qaIdToEditRevisionStatusCell"
                                                    value="<?= $row['qa_id'] ?>">
                                                <select name="revisionStatusCellToEdit" class="form-select"
                                                    onchange="this.form.submit()">
                                                    <option value="Normal" <?= $row['revision_status'] === 'Normal' ? 'selected' : '' ?>>Normal</option>
                                                    <option value="Revision Required" <?= $row['revision_status'] === 'Revision Required' ? 'selected' : '' ?>>Revision Required</option>
                                                    <option value="Urgent Revision Required" <?= $row['revision_status'] === 'Urgent Revision Required' ? 'selected' : '' ?>>Urgent Revision Required</option>
                                                </select>
                                                <a class="text-danger mx-2 text-decoration-none" style="cursor: pointer"
                                                    onclick="cancelEditRevisionStatus(this)">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </div>
                                                </a>
                                            </div>
                                        </form>
                                        <span class="badge 
                                    <?php if ($row["revision_status"] === "Normal") {
                                        echo "bg-success";
                                    } else if ($row["revision_status"] === "Revision Required") {
                                        echo "bg-warning";
                                    } else if ($row["revision_status"] === "Urgent Revision Required") {
                                        echo "bg-danger";
                                    }
                                    ?> rounded-pill"> <?= $row["revision_status"] ?></span>
                                    </td>
                                    <td class="py-2 align-middle text-center wipDocLink">
                                        <form class="wip_document" method="POST">
                                            <input type="hidden" value="<?= $row['wip_doc_link'] ?>" name="wip_document">
                                            <button type="submit"
                                                class="btn btn-link p-0 m-0 text-decoration-underline fw-bold"><?= $row["wip_doc_link"] ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="py-2 align-middle text-center department">
                                        <?= $row["department"] ?>
                                    </td>
                                    <td class="py-2 align-middle text-center type">
                                        <?= $row["type"] ?>
                                    </td>
                                    <td class="py-2 align-middle text-center owner">
                                        <?= $row["owner"] ?>
                                    </td>
                                    <td class="py-2 align-middle text-center status" ondblclick="editStatus(this)">
                                        <form method="POST" class="edit-status-form" style="display:none">
                                            <div class="d-flex align-items-center">
                                                <input type="hidden" name="qaIdToEditStatusCell"
                                                    value="<?= htmlspecialchars($row['qa_id']) ?>">
                                                <select name="statusCellToEdit" class="form-select" onchange="this.form.submit()">
                                                    <option value="Approved" <?= $row['status'] === 'Approved' ? 'selected' : '' ?>>
                                                        Approved</option>
                                                    <option value="Need to review" <?= $row['status'] === 'Need to review' ? 'selected' : '' ?>>Need to review</option>
                                                    <option value="In progress" <?= $row['status'] === 'In progress' ? 'selected' : '' ?>>In progress</option>
                                                    <option value="To be created" <?= $row['status'] === 'To be created' ? 'selected' : '' ?>>To be created</option>
                                                    <option value="Pending approval" <?= $row['status'] === 'Pending approval' ? 'selected' : '' ?>>Pending approval</option>
                                                    <option value="Not approved yet" <?= $row['status'] === 'Not approved yet' ? 'selected' : '' ?>>Not approved yet</option>
                                                    <option value="Revision/Creation requested"
                                                        <?= $row['status'] === 'Revision/Creation requested' ? 'selected' : '' ?>>
                                                        Revision/Creation requested</option>
                                                </select>
                                                <a class="text-danger mx-2 text-decoration-none" style="cursor:pointer"
                                                    onclick="cancelEditStatus(this)">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </div>
                                                </a>
                                            </div>
                                        </form>
                                        <span class="badge
        <?php
                if ($row["status"] === "Approved") {
                    echo "bg-success";
                } else if ($row["status"] === "Need to review") {
                    echo "bg-light text-dark border";
                } else if ($row["status"] === "In progress") {
                    echo "bg-warning";
                } else if ($row["status"] === "To be created") {
                    echo "bg-secondary";
                } else if ($row["status"] === "Pending approval") {
                    echo "bg-primary";
                } else if ($row["status"] === "Not approved yet") {
                    echo "bg-info";
                } else if ($row["status"] === "Revision/Creation requested") {
                    echo "bg-danger";
                }
                ?> rounded-pill"> <?= htmlspecialchars($row["status"]) ?></span>
                                    </td>
                                    <td class="py-2 align-middle text-center approvedBy">
                                        <?= $row["approved_by"] ?>
                                    </td>
                                    <td class="py-2 align-middle text-center lastUpdated">
                                        <?= date("j F Y", strtotime($row["last_updated"])) ?>
                                    </td>
                                    <td class="py-2 align-middle text-center iso9001">
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
                    <?php } else { ?>
                        <tr>
                            <td colspan="14" class="text-center">No records found</td>
                        </tr>
                    <?php } ?>
            </table>
            <div class="d-flex justify-content-end mt-3 pe-2">
                <div class="d-flex align-items-center  me-2">
                    <p>Rows Per Page: </p>
                </div>

                <form method="GET" class="me-2">
                    <select class="form-select" id="recordsPerPage" name="recordsPerPage"
                        onchange="updateURLWithRecordsPerPage()">
                        <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="20" <?php echo $records_per_page == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="30" <?php echo $records_per_page == 30 ? 'selected' : ''; ?>>30</option>
                    </select>
                </form>

                <!-- Pagination controls -->
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" onclick="updatePage(<?php echo $page - 1; ?>); return false;"
                                    aria-label="Previous" style="cursor: pointer">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;</span>
                            </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        if ($page === 1 || ($page === $total_pages)) {
                            $page_range = 2;
                        } else {
                            $page_range = 3;
                        }

                        // $page_range = 3; // Number of pages to display at a time
                        $start_page = max(1, $page - floor($page_range / 2)); // Calculate start page
                        $end_page = min($total_pages, $start_page + $page_range - 1); // Calculate end page
                        
                        // Adjust start page if it goes below 1
                        if ($end_page - $start_page < $page_range - 1) {
                            $start_page = max(1, $end_page - $page_range + 1);
                        }

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>" style="cursor: pointer">
                                <a class="page-link"
                                    onclick="updatePage(<?php echo $i ?>); return false"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="#" onclick="updatePage(<?php echo $page + 1; ?>); return false;"
                                    aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
    <!-- ================== Add Document Modal ================== -->
    <div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ninthMonthPerformanceReviewModalLabel">Add QA Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="qaDocument" class="fw-bold">QA Document</label>
                                <input type="text" name="qaDocument" class="form-control" id="qaDocument">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="documentName" class="fw-bold">Document Name</label>
                                <input type="text" name="documentName" class="form-control" id="documentName">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12 mt-3">
                                <label for="documentDescription" class="fw-bold">Document Description</label>
                                <textarea type="text" name="documentDescription" class="form-control"
                                    id="documentDescription"> </textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6 mt-3">
                                <label for="revNo" class="fw-bold">Rev No.</label>
                                <input type="text" name="revNo" class="form-control" id="revNo">
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="department" class="fw-bold">Department</label>
                                <select class="form-select" aria-label="department" name="department">
                                    <option disabled selected hidden></option>
                                    <option value="Accounts">Accounts</option>
                                    <option value="Electrical">Electrical</option>
                                    <option value="Engineering">Engineering</option>
                                    <option value="Estimating">Estimating</option>
                                    <option value="Human Resources">Human Resources</option>
                                    <option value="Management">Management</option>
                                    <option value="Operations Support">Operations Support</option>
                                    <option value="Projects">Projects</option>
                                    <option value="Quality Assurance">Quality Assurance</option>
                                    <option value="Quality Control">Quality Control</option>
                                    <option value="Research & Development">Research & Development</option>
                                    <option value="Special Projects">Special Projects</option>
                                    <option value="Work, Health and Safety">Work, Health and Safety</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="type" class="fw-bold">Type</label>
                                <select class="form-select" aria-label="type" name="type">
                                    <option disabled selected hidden></option>
                                    <option value="Additional Duties">Additional Duties</option>
                                    <option value="CAPA">CAPA</option>
                                    <option value="Employee Record">Employee Record</option>
                                    <option value="External Documents">External Documents</option>
                                    <option value="Form">Form</option>
                                    <option value="Internal Documents">Internal Documents</option>
                                    <option value="Job Description">Job Description</option>
                                    <option value="Manuals">Manuals</option>
                                    <option value="Policy">Policy</option>
                                    <option value="Process/Procedure">Process/Procedure</option>
                                    <option value="Quiz">Quiz</option>
                                    <option value="Risk Assessment">Risk Assessment</option>
                                    <option value="Work Instruction">Work Instruction</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="owner" class="fw-bold">Owner</label>
                                <select class="form-select" aria-label="owner" name="owner">
                                    <option disabled selected hidden></option>
                                    <option value="General Manager">General Manager</option>
                                    <option value="Engineering Manager">Engineering Manager</option>
                                    <option value="Electrical Department Manager">Electrical Department Manager</option>
                                    <option value="Sheet Metal Department Manager">Sheet Metal Department Manager
                                    </option>
                                    <option value="Operations Support Manager">Operations Support Manager</option>
                                    <option value="QA Officer">QA Officer</option>
                                    <option value="QA Officer">HR Officer</option>
                                    <option value="WHS Committee">WHS Committee</option>
                                    <option value="Risk Assessment Committee">Risk Assessment Committee</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="status" class="fw-bold">Status</label>
                                <select class="form-select" aria-label="status" name="status">
                                    <option disabled selected hidden></option>
                                    <option value="Approved">Approved</option>
                                    <option value="Need to review">Need to review</option>
                                    <option value="Not approved yet">Not approved yet</option>
                                    <option value="In progress">In progress</option>
                                    <option value="Pending approval">Pending approval</option>
                                    <option value="To be created">To be created</option>
                                    <option value="Revision/Creation requested">Revision/Creation requested</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="approvedBy" class="fw-bold">Approved By</label>
                                <select class="form-select" aria-label="approvedBy" name="approvedBy">
                                    <option disabled selected hidden></option>
                                    <option value="General Manager">General Manager</option>
                                    <option value="Engineering Manager">Engineering Manager</option>
                                    <option value="Electrical Department Manager">Electrical Department Manager</option>
                                    <option value="Sheet Metal Department Manager">Sheet Metal Department Manager
                                    </option>
                                    <option value="Operations Support Manager">Operations Support Manager</option>
                                    <option value="QA Officer">QA Officer</option>
                                    <option value="QA Officer">HR Officer</option>
                                    <option value="WHS Committee">WHS Committee</option>
                                    <option value="Risk Assessment Committee">Risk Assessment Committee</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="lastUpdated" class="fw-bold">Last Updated</label>
                                <input type="date" name="lastUpdated" class="form-control" id="lastUpdated">
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="revisionStatus" class="fw-bold">Revision Status</label>
                                <select class="form-select" aria-label="revisionStatus" name="revisionStatus">
                                    <option disabled selected hidden></option>
                                    <option value="Normal">Normal</option>
                                    <option value="Revision Required">Revision Required</option>
                                    <option value="Urgent Revision Required">Urgent Revision Required</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <div class="d-flex flex-column">
                                    <label for="iso9001" class="fw-bold">ISO 9001</label>
                                    <div class="btn-group col-3 col-md-2" role="group">
                                        <input type="radio" class="btn-check" name="iso9001" id="iso9001Yes" value="1"
                                            autocomplete="off">
                                        <label class="btn btn-custom" for="iso9001Yes"
                                            style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                                        <input type="radio" class="btn-check" name="iso9001" id="iso9001No" value="0"
                                            autocomplete="off">
                                        <label class="btn btn-custom" for="iso9001No"
                                            style="color:#043f9d; border: 1px solid #043f9d">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center mt-5 mb-4">
                                <button class="btn btn-dark" name="addDocument" type="submit">Add Document</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- ================== Delete Confirmation Modal ================== -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmationLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <span class="fw-bold" id="qaDocumentToDelete"></span> document?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <!-- Add form submission for deletion here -->
                    <form method="POST">
                        <input type="hidden" name="qaIdToDelete" id="qaIdToDelete">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- ================== Edit Document Modal ================== -->
    <div class="modal fade" id="editDocumentModal" tabindex="-1" aria-labelledby="editDocumentModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit QA Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="row">
                            <input type="hidden" name="qaIdToEdit" id="qaIdToEdit">
                            <div class="form-group col-md-6">
                                <label for="qaDocumentToEdit" class="fw-bold">QA Document</label>
                                <input type="text" name="qaDocumentToEdit" class="form-control" id="qaDocumentToEdit">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="documentNameToEdit" class="fw-bold">Document Name</label>
                                <input type="text" name="documentNameToEdit" class="form-control"
                                    id="documentNameToEdit">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12 mt-3">
                                <label for="documentDescriptionToEdit" class="fw-bold">Document Description</label>
                                <textarea type="text" name="documentDescriptionToEdit" class="form-control"
                                    id="documentDescriptionToEdit"> </textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6 mt-3">
                                <label for="revNoToEdit" class="fw-bold">Rev No.</label>
                                <input type="text" name="revNoToEdit" class="form-control" id="revNoToEdit">
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="departmentToEdit" class="fw-bold">Department</label>
                                <select class="form-select" aria-label="departmentToEdit" name="departmentToEdit"
                                    id="departmentToEdit">
                                    <option disabled selected hidden></option>
                                    <option value="Accounts">Accounts</option>
                                    <option value="Electrical">Electrical</option>
                                    <option value="Engineering">Engineering</option>
                                    <option value="Estimating">Estimating</option>
                                    <option value="Human Resources">Human Resources</option>
                                    <option value="Management">Management</option>
                                    <option value="Operations Support">Operations Support</option>
                                    <option value="Projects">Projects</option>
                                    <option value="Quality Assurance">Quality Assurance</option>
                                    <option value="Quality Control">Quality Control</option>
                                    <option value="Research & Development">Research & Development</option>
                                    <option value="Sheet Metal">Sheet Metal</option>
                                    <option value="Special Projects">Special Projects</option>
                                    <option value="Work, Health and Safety">Work, Health and Safety</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="typeToEdit" class="fw-bold">Type</label>
                                <select class="form-select" aria-label="type" name="typeToEdit" id="typeToEdit">
                                    <option disabled selected hidden></option>
                                    <option value="Additional Duties">Additional Duties</option>
                                    <option value="CAPA">CAPA</option>
                                    <option value="Employee Record">Employee Record</option>
                                    <option value="External Documents">External Documents</option>
                                    <option value="Form">Form</option>
                                    <option value="Internal Documents">Internal Documents</option>
                                    <option value="Job Description">Job Description</option>
                                    <option value="Manuals">Manuals</option>
                                    <option value="Policy">Policy</option>
                                    <option value="Process/Procedure">Process/Procedure</option>
                                    <option value="Quiz">Quiz</option>
                                    <option value="Risk Assessment">Risk Assessment</option>
                                    <option value="Work Instruction">Work Instruction</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="ownerToEdit" class="fw-bold">Owner</label>
                                <select class="form-select" aria-label="ownerToEdit" name="ownerToEdit"
                                    id="ownerToEdit">
                                    <option disabled selected hidden></option>
                                    <option value="General Manager">General Manager</option>
                                    <option value="Engineering Manager">Engineering Manager</option>
                                    <option value="Electrical Department Manager">Electrical Department Manager</option>
                                    <option value="Sheet Metal Department Manager">Sheet Metal Department Manager
                                    </option>
                                    <option value="Operations Support Manager">Operations Support Manager</option>
                                    <option value="QA Officer">QA Officer</option>
                                    <option value="QA Officer">HR Officer</option>
                                    <option value="WHS Committee">WHS Committee</option>
                                    <option value="Risk Assessment Committee">Risk Assessment Committee</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="statusToEdit" class="fw-bold">Status</label>
                                <select class="form-select" aria-label="statusToEdit" name="statusToEdit"
                                    id="statusToEdit">
                                    <option disabled selected hidden></option>
                                    <option value="Approved">Approved</option>
                                    <option value="Need to review">Need to review</option>
                                    <option value="Not approved yet">Not approved yet</option>
                                    <option value="In progress">In progress</option>
                                    <option value="Pending approval">Pending approval</option>
                                    <option value="To be created">To be created</option>
                                    <option value="Revision/Creation requested">Revision/Creation requested</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="approvedByToEdit" class="fw-bold">Approved By</label>
                                <select class="form-select" aria-label="approvedByToEdit" name="approvedByToEdit"
                                    id="approvedByToEdit">
                                    <option disabled selected hidden></option>
                                    <option value="General Manager">General Manager</option>
                                    <option value="Engineering Manager">Engineering Manager</option>
                                    <option value="Electrical Department Manager">Electrical Department Manager</option>
                                    <option value="Sheet Metal Department Manager">Sheet Metal Department Manager
                                    </option>
                                    <option value="Operations Support Manager">Operations Support Manager</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="lastUpdatedToEdit" class="fw-bold">Last Updated</label>
                                <input type="date" name="lastUpdatedToEdit" class="form-control" id="lastUpdatedToEdit">
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <label for="revisionStatusToEdit" class="fw-bold">Revision Status</label>
                                <select class="form-select" aria-label="revisionStatusToEdit"
                                    name="revisionStatusToEdit" id="revisionStatusToEdit">
                                    <option disabled selected hidden></option>
                                    <option value="Normal">Normal</option>
                                    <option value="Revision Required">Revision Required</option>
                                    <option value="Urgent Revision Required">Urgent Revision Required</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 mt-3">
                                <div class="d-flex flex-column">
                                    <label for="iso9001ToEdit" class="fw-bold">ISO 9001</label>
                                    <div class="btn-group col-3 col-md-2" role="group">
                                        <input type="radio" class="btn-check" name="iso9001ToEdit" id="iso9001YesToEdit"
                                            value="1" autocomplete="off">
                                        <label class="btn btn-custom" for="iso9001YesToEdit"
                                            style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                                        <input type="radio" class="btn-check" name="iso9001ToEdit" id="iso9001NoToEdit"
                                            value="0" autocomplete="off">
                                        <label class="btn btn-custom" for="iso9001NoToEdit"
                                            style="color:#043f9d; border: 1px solid #043f9d">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center mt-5 mb-4">
                                <button class="btn btn-dark" name="editDocument" type="submit">Edit Document</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- ================== Filter Column Modal ================== -->
    <div class="modal fade" id="filterColumnModal" tabindex="-1" aria-labelledby="filterColumnModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ninthMonthPerformanceReviewModalLabel">Filter Column</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="qaDocumentCheckBox"
                                data-column="qaDocument">
                            <label class="form-check-label" for="qaDocumentCheckBox">
                                QA Document
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="documentNameCheckBox"
                                data-column="documentName">
                            <label class="form-check-label" for="documentNameCheckBox">
                                Document Name
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="documentDescriptionCheckBox"
                                data-column="documentDescription">
                            <label class="form-check-label" for="documentDescriptionCheckBox">
                                Document Description
                            </label>
                        </div>
                        <?php if ($role === "admin") { ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="revNoCheckBox"
                                    data-column="revNo">
                                <label class="form-check-label" for="revNoCheckBox">
                                    Rev No.
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="wipDocumentCheckBox"
                                    data-column="wipDocLink">
                                <label class="form-check-label" for="wipDocumentCheckBox">
                                    WIP Doc Link
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="departmentCheckBox"
                                    data-column="department">
                                <label class="form-check-label" for="departmentCheckBox">
                                    Department
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="typeCheckBox"
                                    data-column="type">
                                <label class="form-check-label" for="typeCheckBox">
                                    Type
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="ownerCheckBox"
                                    data-column="owner">
                                <label class="form-check-label" for="ownerCheckBox">
                                    Owner
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="statusCheckBox"
                                    data-column="status">
                                <label class="form-check-label" for="statusCheckBox">
                                    Status
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="approvedByCheckBox"
                                    data-column="approvedBy">
                                <label class="form-check-label" for="approvedByCheckBox">
                                    Approved By
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="lastUpdatedCheckBox"
                                    data-column="lastUpdated">
                                <label class="form-check-label" for="lastUpdatedCheckBox">
                                    Last Updated
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="revisionStatusCheckBox"
                                    data-column="revisionStatus">
                                <label class="form-check-label" for="revisionStatusCheckBox">
                                    Revision Status
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="iso9001CheckBox"
                                    data-column="iso9001">
                                <label class="form-check-label" for="iso9001CheckBox">
                                    ISO 9001
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="d-flex justify-content-end" style="cursor:pointer">
                        <button onclick="resetColumnFilter()" class="btn btn-sm btn-danger me-1"> Reset Filter</button>
                        <button type="button" class="btn btn-sm btn-dark" data-bs-dismiss="modal">Done</button>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- ================== Filter by Status Modal ================== -->
    <div class="modal fade" id="filterStatusModal" tabindex="-1" aria-labelledby="filterStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterStatusModalLabel">Filter by Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET" action="">
                        <!-- Preserve URL parameters -->
                        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                        <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
                        <input type="hidden" name="recordsPerPage" value="<?= htmlspecialchars($records_per_page) ?>">
                        <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>">

                        <div class="mb-3">
                            <label for="statusFilter" class="form-label">Select Status</label>
                            <select id="statusFilter" name="statusFilter" class="form-select">
                                <option value="">All</option>
                                <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved
                                </option>
                                <option value="Need to review" <?= $statusFilter === 'Need to review' ? 'selected' : '' ?>>
                                    Need to review</option>
                                <option value="In progress" <?= $statusFilter === 'In progress' ? 'selected' : '' ?>>In
                                    progress</option>
                                <option value="To be created" <?= $statusFilter === 'To be created' ? 'selected' : '' ?>>To
                                    be created</option>
                                <option value="Pending approval" <?= $statusFilter === 'Pending approval' ? 'selected' : '' ?>>Pending approval</option>
                                <option value="Not approved yet" <?= $statusFilter === 'Not approved yet' ? 'selected' : '' ?>>Not approved yet</option>
                                <option value="Revision/Creation requested" <?= $statusFilter === 'Revision/Creation requested' ? 'selected' : '' ?>>Revision/Creation requested</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- ==================  Filter by Revision Status Modal ==================  -->
    <div class="modal fade" id="filterRevisionStatusModal" tabindex="-1"
        aria-labelledby="filterRevisionStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterStatusModalLabel">Filter by Revision Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET">
                        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                        <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
                        <input type="hidden" name="recordsPerPage" value="<?= htmlspecialchars($records_per_page) ?>">
                        <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>">

                        <div class="mb-3">
                            <label for="revisionStatusFilter" class="form-label">Select Revision Status</label>
                            <select id="revisionStatusFilter" name="revisionStatusFilter" class="form-select">
                                <option value="">All</option>
                                <option value="Normal" <?= $revisionStatusFilter === 'Normal' ? 'selected' : '' ?>>Normal
                                </option>
                                <option value="Revision Required" <?= $revisionStatusFilter === 'Revision Required' ? 'selected' : '' ?>>Revision Required</option>
                                <option value="Urgent Revision Required" <?= $revisionStatusFilter === 'Urgent Revision Required' ? 'selected' : '' ?>>Urgent Revision Required</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <div id="loading-indicator" style="display: none;">
        <div class="spinner"></div>
        <p>Opening document...</p>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('deleteConfirmationModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var qaId = button.getAttribute('data-qa-id'); // Extract info from data-* attributes
                var qaDocument = button.getAttribute('data-qa-document');

                // Update the modal's content with the extracted info
                var modalQaIdToDelete = myModalEl.querySelector('#qaIdToDelete');
                var modalQaDocument = myModalEl.querySelector('#qaDocumentToDelete');
                modalQaIdToDelete.value = qaId;
                modalQaDocument.textContent = qaDocument;
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModalEl = document.getElementById('editDocumentModal');
            myModalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var qaId = button.getAttribute('data-qa-id');
                var qaDocument = button.getAttribute('data-qa-document');
                var documentName = button.getAttribute('data-document-name');
                var documentDescription = button.getAttribute('data-document-description');
                var revNo = button.getAttribute('data-rev-no');
                var department = button.getAttribute('data-department');
                var type = button.getAttribute('data-type');
                var owner = button.getAttribute('data-owner');
                var status = button.getAttribute('data-status');
                var approvedBy = button.getAttribute('data-approved-by');
                var lastUpdated = button.getAttribute('data-last-updated');
                var revisionStatus = button.getAttribute('data-revision-status');
                var iso9001 = button.getAttribute('data-iso-9001');

                // Update the modal's content with the extracted info
                var modalQaId = myModalEl.querySelector('#qaIdToEdit')
                var modalQaDocument = myModalEl.querySelector('#qaDocumentToEdit');
                var modalDocumentName = myModalEl.querySelector('#documentNameToEdit');
                var modalDocumentDescription = myModalEl.querySelector('#documentDescriptionToEdit');
                var modalRevNo = myModalEl.querySelector('#revNoToEdit');
                var modalDepartment = myModalEl.querySelector('#departmentToEdit');
                var modalType = myModalEl.querySelector('#typeToEdit');
                var modalOwner = myModalEl.querySelector('#ownerToEdit');
                var modalStatus = myModalEl.querySelector('#statusToEdit');
                var modalApprovedBy = myModalEl.querySelector('#approvedByToEdit');
                var modalLastUpdated = myModalEl.querySelector('#lastUpdatedToEdit');
                var modalRevisionStatus = myModalEl.querySelector('#revisionStatusToEdit');
                var iso9001YesEdit = document.getElementById('iso9001YesToEdit');
                var iso9001NoEdit = document.getElementById('iso9001NoToEdit');

                modalQaId.value = qaId;
                modalQaDocument.value = qaDocument;
                modalDocumentName.value = documentName;
                modalDocumentDescription.value = documentDescription;
                modalRevNo.value = revNo;
                modalDepartment.value = department;
                modalType.value = type;
                modalOwner.value = owner;
                modalStatus.value = status;
                modalApprovedBy.value = approvedBy;
                modalLastUpdated.value = lastUpdated;
                modalRevisionStatus.value = revisionStatus;
                if (iso9001 === "1") {
                    iso9001YesToEdit.checked = true;
                } else {
                    iso9001NoToEdit.checked = true;
                }
            });
        });
    </script>
    <script>
        function updateURLWithRecordsPerPage() {
            const selectElement = document.getElementById('recordsPerPage');
            const recordsPerPage = selectElement.value;
            const url = new URL(window.location.href);
            url.searchParams.set('recordsPerPage', recordsPerPage);
            url.searchParams.set('page', 1);
            window.location.href = url.toString();
        }
    </script>
    <script>
        function updateSearchQuery(department) {
            const url = new URL(window.location.href);
            url.searchParams.set('search', department);
            window.location.href = url.toString();
        }
    </script>
    <script>
        function updatePage(page) {
            // Check if page number is valid
            if (page < 1) return;

            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }
    </script>
    <script>
        function updateSort(sort, order) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sort);
            url.searchParams.set('order', order);
            window.location.href = url.toString();
        }   
    </script>
    <script>
        // Load saved zoom level from localStorage or use default
        let currentZoom = parseFloat(localStorage.getItem('zoomLevel')) || 1;

        // Apply the saved zoom level
        document.body.style.zoom = currentZoom;

        function zoom(factor) {
            currentZoom *= factor;
            document.body.style.zoom = currentZoom;

            // Save the new zoom level to localStorage
            localStorage.setItem('zoomLevel', currentZoom);
        }

        function resetZoom() {
            currentZoom = 1;
            document.body.style.zoom = currentZoom;

            // Remove the zoom level from localStorage
            localStorage.removeItem('zoomLevel');
        }

        // Optional: Reset zoom level on page load
        window.addEventListener('load', () => {
            document.body.style.zoom = currentZoom;
        });

    </script>
    <script>
        // Enabling the tooltip
        const tooltips = document.querySelectorAll('.tooltips');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t);
        })
    </script>
    <script>
        // Add event listeners to all forms with the class 'document-form'
        document.querySelectorAll('.document-form').forEach(form => {
            form.addEventListener('submit', function () {
                // Show the loading indicator
                document.getElementById('loading-indicator').style.display = 'flex';
            });
        });

        // Add event listeners to all forms with the class 'wip_document'
        document.querySelectorAll('.wip_document').forEach(form => {
            form.addEventListener('submit', function () {
                // Show the loading indicator
                document.getElementById('loading-indicator').style.display = 'flex';
            });
        });


    </script>
    <script>
        const STORAGE_EXPIRATION_TIME = 8 * 60 * 60 * 1000; // 8 hours in milliseconds

        // Save checkbox state to localStorage with a timestamp
        document.querySelectorAll('.form-check-input').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const columnClass = this.getAttribute('data-column');
                const columns = document.querySelectorAll(`.${columnClass}`);
                columns.forEach(column => {
                    if (this.checked) {
                        column.style.display = '';
                        localStorage.setItem(columnClass, 'visible');
                    } else {
                        column.style.display = 'none';
                        localStorage.setItem(columnClass, 'hidden');
                    }
                });
                localStorage.setItem(columnClass + '_timestamp', Date.now()); // Save current timestamp
            });
        });

        // Initialize checkboxes based on current column visibility
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.form-check-input').forEach(checkbox => {
                const columnClass = checkbox.getAttribute('data-column');
                const columns = document.querySelectorAll(`.${columnClass}`);

                // Retrieve stored visibility state and timestamp
                const storedVisibility = localStorage.getItem(columnClass);
                const storedTimestamp = localStorage.getItem(columnClass + '_timestamp');
                const currentTime = Date.now();

                // Check if stored timestamp is within the expiration time
                if (storedTimestamp && (currentTime - storedTimestamp <= STORAGE_EXPIRATION_TIME)) {
                    if (storedVisibility === 'hidden') {
                        columns.forEach(column => column.style.display = 'none');
                        checkbox.checked = false;
                    } else {
                        columns.forEach(column => column.style.display = '');
                        checkbox.checked = true;
                    }
                } else {
                    // Clear the localStorage if timestamp is expired
                    localStorage.removeItem(columnClass);
                    localStorage.removeItem(columnClass + '_timestamp');
                    columns.forEach(column => column.style.display = '');
                    checkbox.checked = true;
                }
            });
        });
        function resetColumnFilter() {
            // Get all checkboxes
            document.querySelectorAll('.form-check-input').forEach(checkbox => {
                // Check each checkbox
                checkbox.checked = true;

                // Get the column class associated with the checkbox
                const columnClass = checkbox.getAttribute('data-column');

                // Get all columns with that class
                const columns = document.querySelectorAll(`.${columnClass}`);

                // Show all columns
                columns.forEach(column => {
                    column.style.display = '';
                });

                // Also update localStorage to reflect the reset state
                localStorage.setItem(columnClass, 'visible');
                localStorage.removeItem(columnClass + '_timestamp'); // Clear the timestamp
            });
        }

    </script>
    <script>
        function editStatus(cell) {
            // Get the form and span elements
            const form = cell.querySelector('.edit-status-form');
            const span = cell.querySelector('span');

            // Toggle the form visibility
            if (form.style.display === 'none') {
                form.style.display = 'block';
                span.style.display = 'none';
                form.querySelector('select').focus();
            } else {
                form.style.display = 'none';
                span.style.display = 'block';
            }
        }

        function cancelEditStatus(link) {
            // Find the closest <td> element
            const cell = link.closest('td');
            if (!cell) return;

            // Find the form and span within the <td>
            const form = cell.querySelector('.edit-status-form');
            const span = cell.querySelector('span');

            if (form && span) {
                // Toggle visibility of form and span
                form.style.display = 'none';
                span.style.display = 'inline-block';
            }
        }

        function editRevisionStatus(cell) {
            // Get the form and span elements
            const form = cell.querySelector('.edit-revision-status-form');
            const span = cell.querySelector('span');

            // Toggle the form visibility
            if (form.style.display === 'none') {
                form.style.display = 'block';
                span.style.display = 'none'
                form.querySelector('select').focus();
            } else {
                form.style.display = 'none';
                form.style.display = 'block';
            }
        }

        function cancelEditRevisionStatus(link) {
            //Find the closest <td> element 
            const cell = link.closest('td');
            if (!cell) return;

            // Find the form and span within the <td>
            const form = cell.querySelector('.edit-revision-status-form');
            const span = cell.querySelector('span');

            if (form && span) {
                //Toggle visibility of form and span
                form.style.display = 'none';
                span.style.display = 'inline-block';
            }
        }
    </script>
    <script>
        function clearURLParameters() {
            // Use the URL API to manipulate the URL
            const url = new URL(window.location.href);
            url.search = ''; // Clear the query string

            // Update the URL without reloading the page
            window.history.pushState({}, '', url);

            // Reload the page with the updated URL
            window.location.href = url.href; // This will reload the page with the cleared URL parameters
        }
    </script>
</body>

</html>