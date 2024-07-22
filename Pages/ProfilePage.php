<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Connect to the database
require_once ("../db_connect.php");
require_once ("../status_check.php");

// Get login employee id from SESSION
$loginEmployeeId = $_SESSION["employee_id"];

// SQL to get login employee details
$login_employee_details_sql = "SELECT * FROM employees WHERE employee_id = $loginEmployeeId";
$login_employee_details_result = $conn->query($login_employee_details_sql);

if ($login_employee_details_result->num_rows > 0) {
    while ($row = $login_employee_details_result->fetch_assoc()) {
        $loginEmployeeFirstName = $row["first_name"];
        $loginEmployeeLastName = $row["last_name"];
    }
}

// Get employee_id from the URL
if (isset($_GET['employee_id'])) {
    $employeeId = $_GET['employee_id'];
}

// SQL to get the employee details
$employee_details_sql = "SELECT * FROM employees WHERE employee_id = $employeeId";
$employee_details_result = $conn->query($employee_details_sql);

$employee_start_date_sql = "SELECT start_date FROM employees WHERE employee_id = $employeeId";
$employee_start_date_result = $conn->query($employee_start_date_sql);

$employee_group_access_sql = "SELECT DISTINCT groups.group_name, folders.folder_name, groups.group_id, folders.folder_id
                        FROM groups
                        JOIN groups_folders ON groups.group_id = groups_folders.group_id
                        JOIN folders ON folders.folder_id = groups_folders.folder_id
                        JOIN users_groups ON users_groups.group_id = groups.group_id
                        JOIN users ON users.user_id = users_groups.user_id
                        JOIN employees ON employees.employee_id = users.employee_id
                        WHERE employees.employee_id = $employeeId";

$employee_group_access_result = $conn->query($employee_group_access_sql);

// SQL to get the performance review data
$performance_review_sql = "SELECT * FROM performance_review WHERE reviewee_employee_id = $employeeId";
$performance_review_result = $conn->query($performance_review_sql);
if ($performance_review_result->num_rows > 0) {
    while ($row = $performance_review_result->fetch_assoc()) {
        $reviewType = $row["review_type"];
        $reviewerEmployeeId = $row["reviewer_employee_id"];
        $revieweeEmployeeId = $row["reviewee_employee_id"];
        $reviewNotes = $row["review_notes"];
        $reviewDate = $row["review_date"];
    }
}

// Get the start date for the chart
if ($employee_start_date_result->num_rows > 0) {
    while ($row = $employee_start_date_result->fetch_assoc()) {
        $startDateChart = $row['start_date'];
    }
}

// SQL to get the wages data
$employee_wages_sql = "SELECT * FROM wages WHERE employee_id = $employeeId ORDER BY date ASC";
// Fetch wages data
$employee_wages_result = $conn->query($employee_wages_sql);

$wagesData = array(); // Array to store wages data

if ($employee_wages_result->num_rows > 0) {
    while ($row = $employee_wages_result->fetch_assoc()) {
        $wagesData[] = $row; // Store each row in the array
    }
}

// Chart data points
$dataPoints = array();

foreach ($wagesData as $row) {
    $dataPoints[] = array("y" => floatval($row['amount']), "label" => date("j F Y", strtotime($row['date'])));
}

// Get current date in the desired format
$currentDate = date("Y-m-d");

// ========================= A D D   N E W   W A G E =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['newWage'])) {
    $newWage = $_POST["newWage"];

    $add_wages_sql = "INSERT INTO wages (amount, date, employee_id) VALUES (?, ?, ?)";
    $add_wages_stmt = $conn->prepare($add_wages_sql);
    $add_wages_stmt->bind_param("sss", $newWage, $currentDate, $employeeId);

    // Execute the prepared statement
    if ($add_wages_stmt->execute()) {
        echo "New wage record inserted successfully.";
        header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
        exit();
    } else {
        echo "Error: " . $add_wages_stmt . "<br>" . $conn->error;
    }
    // Close statement 
    $add_wages_stmt->close();
}

// ========================= E D I T   W A G E  =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['editDate']) && isset($_POST['editWage'])) {
    $editDate = $_POST["editDate"];
    $editWage = $_POST["editWage"];
    $wagesId = $_POST['wages_id'];

    echo $editDate . "  " . $editWage . "  " . $wagesId;

    $edit_wages_sql = "UPDATE wages SET amount = ?,  date = ? WHERE wages_id = ?";
    $edit_wages_stmt = $conn->prepare($edit_wages_sql);
    $edit_wages_stmt->bind_param("ssi", $editWage, $editDate, $wagesId);

    // Execute the prepared statement
    if ($edit_wages_stmt->execute()) {
        echo "Wages Edited";
        header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
        exit();
    } else {
        echo "Error: " . $edit_wages_stmt . "<br>" . $conn->error;
    }

    // Close Statement
    $edit_wages_stmt->close();
}

// ========================= D E L E T E   W A G E =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['wages_id'])) {
    $wagesId = $_POST['wages_id'];

    $delete_wages_sql = "DELETE FROM wages WHERE wages_id = ?";
    $delete_wages_stmt = $conn->prepare($delete_wages_sql);
    $delete_wages_stmt->bind_param("i", $wagesId);

    // Execute the prepared statement
    if ($delete_wages_stmt->execute()) {
        echo "Wages Deleted";
        header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
        exit();
    } else {
        echo "Error: " . $delete_wages_sql . "<br>" . $conn->error;
    }

    // Close Statement
    $delete_wages_stmt->close();
}

// ========================= D E L E T E  P R O F I L E  I M A G E =========================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['deleteProfileImage'])) {
        // Handle profile image deletion
        $profileImageToDeleteEmpId = $_POST['profileImageToDeleteEmpId'];
        $delete_profile_image_sql = "UPDATE employees SET profile_image = NULL WHERE employee_id = ?";
        $delete_profile_image_stmt = $conn->prepare($delete_profile_image_sql);
        $delete_profile_image_stmt->bind_param("i", $profileImageToDeleteEmpId);

        if ($delete_profile_image_stmt->execute()) {
            echo "Profile image deleted successfully.";
            header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $profileImageToDeleteEmpId);
            exit();
        } else {
            echo "Error deleting profile image: " . $conn->error;
        }
        $delete_profile_image_stmt->close();
    } elseif (isset($_POST['changeProfileImage'])) {
        // Handle profile image change
        $profileImageToDeleteEmpId = $_POST['profileImageToDeleteEmpId'];
        $profileImage = $_FILES['profileImageToEdit'];

        // Process the uploaded file
        $imageExtension = pathinfo($profileImage["name"], PATHINFO_EXTENSION);
        $newFileName = $profileImageToDeleteEmpId . '_profiles.' . $imageExtension;
        $imagePath = "../Images/ProfilePhotos/" . $newFileName;

        if (move_uploaded_file($profileImage["tmp_name"], $imagePath)) {
            // Encode the image before insertion
            $encodedImage = base64_encode(file_get_contents($imagePath));

            // Update database with new image
            $sql = "UPDATE employees SET profile_image = ? WHERE employee_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $encodedImage, $profileImageToDeleteEmpId);

            if ($stmt->execute()) {
                echo "Profile image changed successfully.";
                header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $profileImageToDeleteEmpId);
                exit();
            } else {
                echo "Error updating profile image: " . $conn->error;
            }
            $stmt->close();
        } else {
            echo "File upload failed.";
        }
    }
}

// ========================= E D I T  P R O F I L E  =========================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if the form was submitted
    if (isset($_POST['editEmployeeProfile'])) {
        // Process the form data
        $employeeIdToEdit = $_POST['employeeIdToEdit'];
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
        $gender = $_POST['gender'];
        $dob = $_POST['dob'];
        $visaStatus = $_POST['visaStatus'];
        $visaExpiryDate = $_POST['visaExpiryDate'];
        $address = $_POST['address'];
        $email = $_POST['email'];
        $phoneNumber = $_POST['phoneNumber'];
        $emergencyContactName = $_POST['emergencyContactName'];
        $emergencyContact = $_POST['emergencyContact'];
        $emergencyContactRelationship = $_POST['emergencyContactRelationship'];
        $employeeId = $_POST['employeeId'];
        $startDate = $_POST['startDate'];
        $employmentType = $_POST['employmentType'];
        $department = $_POST['department'];
        $section = $_POST['section'];
        $position = $_POST['position'];
        $bankBuildingSociety = $_POST['bankBuildingSociety'];
        $bsb = $_POST['bsb'];
        $accountNumber = $_POST['accountNumber'];
        $uniqueSuperannuationIdentifier = $_POST['uniqueSuperannuationIdentifier'];
        $superannuationFundName = $_POST['superannuationFundName'];
        $superannuationMemberNumber = $_POST['superannuationMemberNumber'];
        $taxFileNumber = $_POST['taxFileNumber'];
        $higherEducationLoanProgramme = $_POST['higherEducationLoanProgramme'];
        $financialSupplementDebt = $_POST['financialSupplementDebt'];

        $edit_employee_detail_sql = "UPDATE employees SET first_name = ?, last_name = ?, gender = ?, dob = ?, visa = ?, visa_expiry_date = ?, address = ?, email= ?, phone_number = ?, emergency_contact_name = ?, emergency_contact_phone_number = ?, emergency_contact_relationship = ?, start_date = ?, department = ?, section = ?, employment_type = ?, position = ?, bank_building_society = ?, bsb = ?, account_number = ?, superannuation_fund_name = ?, unique_superannuation_identifier = ?, superannuation_member_number = ?, tax_file_number = ?, higher_education_loan_programme = ?, financial_supplement_debt = ? WHERE employee_id = ?";
        $edit_employee_detail_result = $conn->prepare($edit_employee_detail_sql);
        $edit_employee_detail_result->bind_param("sssssssssssssssssssssssssii", $firstName, $lastName, $gender, $dob, $visaStatus, $visaExpiryDate, $address, $email, $phoneNumber, $emergencyContactName, $emergencyContact, $emergencyContactRelationship, $startDate, $department, $section, $employmentType, $position, $bankBuildingSociety, $bsb, $accountNumber, $superannuationFundName, $uniqueSuperannuationIdentifier, $superannuationMemberNumber, $taxFileNumber, $higherEducationLoanProgramme, $financialSupplementDebt, $employeeIdToEdit);

        if ($edit_employee_detail_result->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeIdToEdit);
            exit();
        } else {
            echo "Error: " . $edit_employee_detail_result . "<br>" . $conn->error;
        }
    }
}

// ========================= A D D  P O L I C I E S =========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['policiesToSubmit'])) {
    $policiesToSubmit = $_POST['policiesToSubmit'];

    $folderName = $employeeId . "_policies";

    $directory = "../Policies/";

    // Check if the directory exists or create it if not
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true); // Create directory recursively if it doesn't exist
    }

    // Check if the folder already exists
    if (is_dir($directory . $folderName)) {
        echo "Folder already exists.";
    } else {
        // Create new folder
        if (mkdir($directory . $folderName)) {
            echo "Folder created successfully.";
        } else {
            echo "Failed to create folder.";
        }
    }
}

// =========================  A C T I V A T E  E M P L O Y E E ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['employeeIdToActivate'])) {
    $employeeIdToActivate = $_POST['employeeIdToActivate'];

    $activate_employee_sql = "UPDATE employees SET is_active = 1 WHERE employee_id = ?";
    $activate_employee_result = $conn->prepare($activate_employee_sql);
    $activate_employee_result->bind_param("i", $employeeIdToActivate);

    // Execute the prepared statement
    if ($activate_employee_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '?employee_id= ' . $employeeId . '");</script>';
        exit();
    } else {
        $error_message = "Error: " . $activate_employee_result . "<br>" . $conn->error;
    }
}

//  =========================  D E A C T I V A T E  E M P L O Y E E ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["employeeIdToDeactivate"])) {
    $employeeIdToDeactivate = $_POST['employeeIdToDeactivate'];

    $deactivate_employee_sql = "UPDATE employees SET is_active = 0 WHERE employee_id = ?";
    $deactivate_employee_result = $conn->prepare($deactivate_employee_sql);
    $deactivate_employee_result->bind_param("i", $employeeIdToDeactivate);

    // Execute the prepared statement
    if ($deactivate_employee_result->execute()) {
        echo '<script>window.location.replace("' . $_SERVER['PHP_SELF'] . '?employee_id= ' . $employeeId . '");</script>';
        exit();
    } else {
        $error_message = "Error: " . $deactivate_employee_result . "<br>" . $conn->error;
    }
}

//  ========================= P E R F O R M A N C E  R E V I E W (1st Month Review) ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["revieweeEmployeeIdFirstMonthReview"])) {
    $revieweeEmployeeId = $_POST["revieweeEmployeeIdFirstMonthReview"];
    $reviewerEmployeeId = $_POST["reviewerEmployeeId"];
    $reviewType = $_POST["reviewType"];
    $reviewNotes = $_POST["reviewNotes"];
    $reviewDate = $_POST["reviewDate"];

    $submit_performance_review_sql = "INSERT INTO performance_review (review_type, reviewer_employee_id, reviewee_employee_id, review_notes, review_date) VALUES (?, ?, ? ,? ,?)";
    $submit_performance_review_result = $conn->prepare($submit_performance_review_sql);

    if (!$submit_performance_review_result) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
    } else {
        $submit_performance_review_result->bind_param("siiss", $reviewType, $reviewerEmployeeId, $revieweeEmployeeId, $reviewNotes, $reviewDate);

        // Execute the prepared statement
        if ($submit_performance_review_result->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
            exit();
        } else {
            echo "Error: " . $submit_performance_review_result->error;
        }
        // Close statement 
        $submit_performance_review_result->close();
    }
}

//  ========================= P E R F O R M A N C E  R E V I E W (3rd Month Review) ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["revieweeEmployeeIdThirdMonthReview"])) {
    $revieweeEmployeeId = $_POST["revieweeEmployeeIdThirdMonthReview"];
    $reviewerEmployeeId = $_POST["reviewerEmployeeId"];
    $reviewType = $_POST["reviewType"];
    $reviewNotes = $_POST["reviewNotes"];
    $reviewDate = $_POST["reviewDate"];

    $submit_performance_review_sql = "INSERT INTO performance_review (review_type, reviewer_employee_id, reviewee_employee_id, review_notes, review_date) VALUES (?, ?, ? ,? ,?)";
    $submit_performance_review_result = $conn->prepare($submit_performance_review_sql);

    if (!$submit_performance_review_result) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
    } else {
        $submit_performance_review_result->bind_param("siiss", $reviewType, $reviewerEmployeeId, $revieweeEmployeeId, $reviewNotes, $reviewDate);

        // Execute the prepared statement
        if ($submit_performance_review_result->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
            exit();
        } else {
            echo "Error: " . $submit_performance_review_result->error;
        }
        // Close statement 
        $submit_performance_review_result->close();
    }
}

//  ========================= P E R F O R M A N C E  R E V I E W (6th Month Review) ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["revieweeEmployeeIdSixthMonthReview"])) {
    $revieweeEmployeeId = $_POST["revieweeEmployeeIdSixthMonthReview"];
    $reviewerEmployeeId = $_POST["reviewerEmployeeId"];
    $reviewType = $_POST["reviewType"];
    $reviewNotes = $_POST["reviewNotes"];
    $reviewDate = $_POST["reviewDate"];

    $submit_performance_review_sql = "INSERT INTO performance_review (review_type, reviewer_employee_id, reviewee_employee_id, review_notes, review_date) VALUES (?, ?, ? ,? ,?)";
    $submit_performance_review_result = $conn->prepare($submit_performance_review_sql);

    if (!$submit_performance_review_result) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
    } else {
        $submit_performance_review_result->bind_param("siiss", $reviewType, $reviewerEmployeeId, $revieweeEmployeeId, $reviewNotes, $reviewDate);

        // Execute the prepared statement
        if ($submit_performance_review_result->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
            exit();
        } else {
            echo "Error: " . $submit_performance_review_result->error;
        }
        // Close statement 
        $submit_performance_review_result->close();
    }
}

//  ========================= P E R F O R M A N C E  R E V I E W (9th Month Review) ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["revieweeEmployeeIdNinthMonthReview"])) {
    $revieweeEmployeeId = $_POST["revieweeEmployeeIdNinthMonthReview"];
    $reviewerEmployeeId = $_POST["reviewerEmployeeId"];
    $reviewType = $_POST["reviewType"];
    $reviewNotes = $_POST["reviewNotes"];
    $reviewDate = $_POST["reviewDate"];

    $submit_performance_review_sql = "INSERT INTO performance_review (review_type, reviewer_employee_id, reviewee_employee_id, review_notes, review_date) VALUES (?, ?, ? ,? ,?)";
    $submit_performance_review_result = $conn->prepare($submit_performance_review_sql);

    if (!$submit_performance_review_result) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
    } else {
        $submit_performance_review_result->bind_param("siiss", $reviewType, $reviewerEmployeeId, $revieweeEmployeeId, $reviewNotes, $reviewDate);

        // Execute the prepared statement
        if ($submit_performance_review_result->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
            exit();
        } else {
            echo "Error: " . $submit_performance_review_result->error;
        }
        // Close statement 
        $submit_performance_review_result->close();
    }
}

//  ========================= O P E N  F O L D E R  (P A Y  R E V I E W) ========================= 
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["payReviewFolder"])) {
        $directory = "../../../../../../Applications/Employees/$employeeId/Pay Review";
    } else if (isset($_POST["annualLeaveFolder"])) {
        $directory = "../../../../../../Applications/Employees/$employeeId/Annual Leave";
    } else if (isset($_POST["policiesFolder"])) {
        $directory = "../../../../../../Applications/Employees/$employeeId/Policies";
    }

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Employee Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="shortcut icon" type="image/x-icon" href="../Images/FE-logo-icon.ico" />

    <style>
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

        /* Remove watermark */
        .canvasjs-chart-credit {
            display: none !important;
        }

        .form-check-input:checked+.form-check-label {
            background-color: #27a745;
            color: white;
        }

        /* Folder icon change when hover */
        .folder-icon {
            position: relative;
            cursor: pointer;
        }

        .folder-icon:hover .fa-folder {
            display: none !important;
        }

        .folder-icon:hover .fa-folder-open {
            display: inline-block !important;
        }
    </style>

    <script>
        // Capture scroll position before page refresh or redirection
        window.addEventListener('beforeunload', function () {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        });

    </script>
</head>

<body class="background-color">
    <?php require_once ("../Menu/HRStaticTopMenu.php") ?>
    <div class="container-fluid px-md-5 mb-5">
        <div class="row">
            <div class="col-lg-8">
                <?php
                if ($employee_details_result->num_rows > 0) {
                    while ($row = $employee_details_result->fetch_assoc()) {
                        $profileImage = $row['profile_image'];
                        $firstName = $row['first_name'];
                        $lastName = $row['last_name'];
                        $visaStatus = $row['visa'];
                        $visaExpiryDate = $row['visa_expiry_date'];
                        $employeeId = $row['employee_id'];
                        $address = $row['address'];
                        $phoneNumber = $row['phone_number'];
                        $emergencyContactName = $row['emergency_contact_name'];
                        $emergencyContactRelationship = $row['emergency_contact_relationship'];
                        $emergencyContact = $row['emergency_contact_phone_number'];
                        $plateNumber = $row['plate_number'];
                        $gender = $row['gender'];
                        $dob = $row['dob'];
                        $startDate = $row['start_date'];
                        $employmentType = $row['employment_type'];
                        $department = $row['department'];
                        $section = $row['section'];
                        $position = $row['position'];
                        $email = $row['email'];
                        $isActive = $row['is_active'];
                        $bankBuildingSociety = $row['bank_building_society'];
                        $bsb = $row['bsb'];
                        $accountNumber = $row['account_number'];
                        $superannuationFundName = $row['superannuation_fund_name'];
                        $uniqueSuperannuationIdentifier = $row['unique_superannuation_identifier'];
                        $superannuationMemberNumber = $row['superannuation_member_number'];
                        $taxFileNumber = $row['tax_file_number'];
                        $higherEducationLoanProgramme = $row['higher_education_loan_programme'];
                        $financialSupplementDebt = $row['financial_supplement_debt'];
                    }
                    ?>
                    <div class="row g-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <?php if (!empty($profileImage)) { ?>
                                    <!-- Profile image -->
                                    <div class="bg-gradient shadow-lg rounded-circle"
                                        style="width: 100px; height: 100px; overflow: hidden;">
                                        <img src="data:image/jpeg;base64,<?php echo $profileImage; ?>" alt="Profile Image"
                                            class="profile-pic img-fluid rounded-circle"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                <?php } else { ?>
                                    <!-- Initials -->
                                    <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center"
                                        style="width: 100px; height: 100px;">
                                        <h3 class="p-0 m-0">
                                            <?php echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); ?>
                                        </h3>
                                    </div>
                                <?php } ?>

                                <div class="d-flex flex-column ms-3">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <h5 class="card-title fw-bold text-start">
                                            <?php echo (isset($firstName) && isset($lastName)) ? $firstName . " " . $lastName : "N/A"; ?>
                                            <?php if ($isActive == 0) {
                                                echo '<small> <span class="badge rounded-pill bg-danger mb-1">Inactive</span> </small>';
                                            } else if ($isActive == 1) {
                                                echo '<small> <span class="badge rounded-pill bg-success mb-1">Active</span> </small>';
                                            }
                                            ;
                                            ?>
                                        </h5>
                                    </div>
                                    <small
                                        class="text-start"><?php echo (isset($position)) ? $position : "N/A" . " - " . ((isset($employeeId)) ? $employeeId : "N/A"); ?></small>
                                </div>
                            </div>
                            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#editProfileModal">Edit
                                Profile <i class="fa-regular fa-pen-to-square"></i></button>
                        </div>
                    </div>

                    <div class="p-3 mt-4 bg-white rounded shadow-lg">
                        <div class="p-3">
                            <p class="fw-bold signature-color">Personal Information</p>
                            <div class="row">
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>First Name</small>
                                    <h5 class="fw-bold"><?php echo (isset($firstName) ? $firstName : "N/A") ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Last Name</small>
                                    <h5 class="fw-bold"><?php echo (isset($lastName) ? $lastName : "N/A") ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Gender</small>
                                    <h5 class="fw-bold"><?php echo (isset($gender) ? $gender : "N/A") ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Date of Birth</small>
                                    <h5 class="fw-bold"><?php echo isset($dob) ? date("j F Y", strtotime($dob)) : "N/A"; ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Visa Status</small>
                                    <h5 class="fw-bold"><?php echo isset($visaStatus) ? $visaStatus : "N/A"; ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Visa Expiry Date</small>
                                    <?php $today = new DateTime();
                                    $expiryDate = new DateTime($visaExpiryDate);
                                    $interval = $today->diff($expiryDate);
                                    $daysDifference = $interval->format('%r%a');

                                    $visaExpiryDate = isset($visaExpiryDate) ? $visaExpiryDate : "N/A";

                                    // Check if the expiry date is less than 30 days from today
                                    if ($daysDifference < 30 && $daysDifference >= 0) {
                                        echo '<h5 class="fw-bold text-danger">' . $visaExpiryDate . '<i class="fa-solid fa-circle-exclamation fa-shake ms-1 tooltips" data-bs-toggle="tooltip" 
                                        data-bs-placement="top" title="Visa expired in ' . $daysDifference . ' days "></i> </h5>';
                                    } else if ($daysDifference < 0) {
                                        echo '<h5 class="fw-bold text-danger">' . $visaExpiryDate . '<i class="fa-solid fa-circle-exclamation fa-shake ms-1 tooltips" data-bs-toggle="tooltip" 
                                        data-bs-placement="top" title="Visa expired ' . abs($daysDifference) . ' days ago"></i> </h5>';
                                    } else {
                                        echo '<h5 class="fw-bold">' . $visaExpiryDate . '</h5>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 mt-4 bg-white rounded shadow-lg">
                        <div class="p-3">
                            <p class="fw-bold signature-color">Contacts</p>
                            <div class="row">
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Address</small>
                                    <h5 class="fw-bold">
                                        <?php echo (isset($address) && $address !== "" ? $address : "N/A"); ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Email</small>
                                    <h5 class="fw-bold text-break"><?php echo isset($email) ? $email : "N/A"; ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Phone Number</small>
                                    <h5 class="fw-bold"><?php echo isset($phoneNumber) ? $phoneNumber : "N/A"; ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Plate Number</small>
                                    <h5 class="fw-bold"><?php echo isset($plateNumber) ? $plateNumber : "N/A"; ?>
                                    </h5>
                                </div>
                            </div>

                            <p class="fw-bold signature-color mt-4">Emergency Contact</p>
                            <div class="row">
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Emergency Contact Name</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($emergencyContactName) ? $emergencyContactName : "N/A"; ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Emergency Contact Relationship</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($emergencyContactRelationship) ? $emergencyContactRelationship : "N/A"; ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Emergency Contact</small>
                                    <h5 class="fw-bold"><?php echo isset($emergencyContact) ? $emergencyContact : "N/A"; ?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 mt-4 bg-white rounded shadow-lg">
                        <div class="p-3">
                            <p class="fw-bold signature-color">Employment Details</p>
                            <div class="row">
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small> Employee Id </small>
                                    <h5 class="fw-bold"><?php echo isset($employeeId) ? $employeeId : "N/A"; ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Date Hired</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($startDate) ? date("j F Y", strtotime($startDate)) : "N/A"; ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Time with Company</small>
                                    <?php
                                    if (isset($startDate)) {
                                        $startDateObj = new DateTime($startDate);
                                        $currentDateObj = new DateTime();
                                        $interval = $startDateObj->diff($currentDateObj);
                                        echo "<h5 class='fw-bold'> $interval->y  years, $interval->m  months, $interval->d  days </h5>";
                                    } else {
                                        echo "<h5 class='fw-bold'>N/A</h5>";
                                    }
                                    ?>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Department</small>
                                    <h5 class="fw-bold"><?php echo isset($department) ? $department : "N/A"; ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Section</small>
                                    <h5 class="fw-bold"><?php echo isset($section) ? $section : "N/A"; ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Employment Type</small>
                                    <h5 class="fw-bold"><?php echo isset($employmentType) ? $employmentType : "N/A"; ?></h5>
                                </div>

                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Position</small>
                                    <h5 class="fw-bold"><?php echo isset($position) ? $position : "N/A"; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 mt-4 bg-white rounded shadow-lg">
                        <div class="p-3">
                            <p class="fw-bold signature-color">Banking, Super and Tax Details</p>
                            <div class="row">
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small> Banking/Building Society </small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($bankBuildingSociety) ? $bankBuildingSociety : "N/A" ?></h>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>BSB</small>
                                    <h5 class="fw-bold"><?php echo isset($bsb) ? $bsb : "N/A" ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Account Number</small>
                                    <h5 class="fw-bold"><?php echo isset($accountNumber) ? $accountNumber : "N/A" ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Unique Superannuation Identifier</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($uniqueSuperannuationIdentifier) ? $uniqueSuperannuationIdentifier : "N/A" ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Superannuation Fund Name</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($superannuationFundName) ? $superannuationFundName : "N/A" ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Superannuation Member Number</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($superannuationMemberNumber) ? $superannuationMemberNumber : "N/A" ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Tax File Number</small>
                                    <h5 class="fw-bold"><?php echo isset($taxFileNumber) ? $taxFileNumber : "N/A" ?></h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Higher Education Loan Programme</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($higherEducationLoanProgramme) ? ($higherEducationLoanProgramme == 1 ? "Yes" : "No") : "N/A"; ?>
                                    </h5>
                                </div>
                                <div class="col-lg-6 col-xl-3 d-flex flex-column">
                                    <small>Financial Supplement Debt</small>
                                    <h5 class="fw-bold">
                                        <?php echo isset($financialSupplementDebt) ? ($financialSupplementDebt == 1 ? "Yes" : "No") : "N/A" ?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-lg-4">
                    <div class="card bg-white border-0 rounded shadow-lg mt-4 mt-lg-0">
                        <div class="p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="fw-bold signature-color mb-0">Pay Raise History</p>
                                <i id="payRaiseEditIcon" role="button" class="fa-regular fa-pen-to-square signature-color"
                                    data-bs-toggle="modal" data-bs-target="#payRaiseHistoryModal"></i>
                            </div>
                            <div class="px-4 py-2">
                                <div id="chartContainer" style="height: 300px; width: 100%;"></div>
                            </div>
                        </div>
                    </div>
                    <?php if (isset($employmentType) && $employmentType == "Casual") {
                        $firstMonthDueDate = date('Y-m-d', strtotime($startDate . ' +1 month'));
                        $thirdMonthDueDate = date('Y-m-d', strtotime($startDate . ' +3 month'));
                        $sixthMonthDueDate = date('Y-m-d', strtotime($startDate . ' +6 month'));
                        $ninthMonthDueDate = date('Y-m-d', strtotime($startDate . ' +9 month'));

                        // $reviewType = isset($reviewType) ? $reviewType : null;
                        $revieweeEmployeeId = isset($revieweeEmployeeId) ? $revieweeEmployeeId : null;
                        $reviewerEmployeeId = isset($reviewerEmployeeId) ? $reviewerEmployeeId : null;
                        // $reviewDate = isset($reviewDate) ? $reviewDate : null;
                        // $reviewNotes = isset($reviewNotes) ? $reviewNotes : null;
                        ?>
                        <div class="card bg-white border-0 rounded shadow-lg mt-4">
                            <div class="p-3">
                                <p class="fw-bold signature-color">Performance Review</p>
                                <!-- First Month Review -->
                                <div class="d-flex align-items-center">
                                    <?php $hasFirstMonthReview = false;
                                    foreach ($performance_review_result as $row) {
                                        if ($row['review_type'] === "First Month Review") {
                                            $hasFirstMonthReview = true;
                                            break;
                                        }
                                    } ?>
                                    <?php
                                    $firstMonthDueDateFormat = new DateTime($firstMonthDueDate);
                                    $firstMonthInterval = $today->diff($firstMonthDueDateFormat);
                                    $firstMonthDaysDifference = $firstMonthInterval->format('%r%a');
                                    ?>
                                    <?php if ($hasFirstMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                        <i class="fa-solid fa-star fa-lg text-warning"></i>
                                    <?php } else { ?>
                                        <i class="fa-solid fa-star fa-lg text-secondary"></i>
                                    <?php } ?>
                                    <div class="ms-3">
                                        <div class="d-flex flex-column">
                                            <div class="fw-bold">1<sup>st</sup> Month Review
                                                <?php if ($hasFirstMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                                    <span class="badge rounded-pill bg-success">Done</span>
                                                <?php } else if (!$hasFirstMonthReview && $revieweeEmployeeId != $employeeId && $firstMonthDaysDifference < 7 && $firstMonthDaysDifference >= 0) { ?>
                                                        <span class="badge rounded-pill bg-warning">Due Soon</span>
                                                <?php } else if (!$hasFirstMonthReview && $firstMonthDaysDifference < 0) { ?>
                                                            <span class="badge rounded-pill bg-danger">Past Due</span>
                                                <?php } else {
                                                    } ?>
                                            </div>
                                            <div>
                                                <small class="text-secondary">Due: <?php echo $firstMonthDueDate ?></small>
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn ms-auto" data-bs-toggle="modal"
                                        data-bs-target="#firstMonthPerformanceReviewModal">
                                        <i class="fa-solid fa-arrow-up-right-from-square signature-color"></i>
                                    </button>
                                </div>

                                <!-- Third Month Review -->
                                <hr />
                                <div class="d-flex align-items-center">
                                    <?php $hasThirdMonthReview = false;
                                    foreach ($performance_review_result as $row) {
                                        if ($row['review_type'] === "Third Month Review") {
                                            $hasThirdMonthReview = true;
                                            break;
                                        }
                                    } ?>
                                    <?php
                                    $thirdMonthDueDateFormat = new DateTime($thirdMonthDueDate);
                                    $thirdMonthInterval = $today->diff($thirdMonthDueDateFormat);
                                    $thirdMonthDaysDifference = $thirdMonthInterval->format('%r%a');
                                    ?>
                                    <?php if ($hasThirdMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                        <i class="fa-solid fa-star fa-lg text-warning"></i>
                                    <?php } else { ?>
                                        <i class="fa-solid fa-star fa-lg text-secondary"></i>
                                    <?php } ?>
                                    <div class="ms-3">
                                        <div class="d-flex flex-column">
                                            <div class="fw-bold">3<sup>rd</sup> Month Review
                                                <?php if ($hasThirdMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                                    <span class="badge rounded-pill bg-success">Done</span>
                                                <?php } else if (!$hasThirdMonthReview && $revieweeEmployeeId != $employeeId && $thirdMonthDaysDifference < 7 && $thirdMonthDaysDifference >= 0) { ?>
                                                        <span class="badge rounded-pill bg-warning">Due Soon</span>
                                                <?php } else if (!$hasThirdMonthReview && $thirdMonthDaysDifference < 0) { ?>
                                                            <span class="badge rounded-pill bg-danger">Past Due</span>
                                                <?php } else {
                                                    } ?>
                                            </div>
                                            <div>
                                                <small class="text-secondary">Due: <?php echo $thirdMonthDueDate ?></small>
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn ms-auto" data-bs-toggle="modal"
                                        data-bs-target="#thirdMonthPerformanceReviewModal">
                                        <i class="fa-solid fa-arrow-up-right-from-square signature-color"></i>
                                    </button>
                                </div>

                                <!-- Sixth Month Review -->
                                <hr />
                                <div class="d-flex align-items-center">
                                    <?php $hasSixthMonthReview = false;
                                    foreach ($performance_review_result as $row) {
                                        if ($row['review_type'] === "Sixth Month Review") {
                                            $hasSixthMonthReview = true;
                                            break;
                                        }
                                    } ?>
                                    <?php
                                    $sixthMonthDueDateFormat = new DateTime($sixthMonthDueDate);
                                    $sixthMonthInterval = $today->diff($sixthMonthDueDateFormat);
                                    $sixthMonthDaysDifference = $sixthMonthInterval->format('%r%a');
                                    ?>
                                    <?php if ($hasSixthMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                        <i class="fa-solid fa-star fa-lg text-warning"></i>
                                    <?php } else { ?>
                                        <i class="fa-solid fa-star fa-lg text-secondary"></i>
                                    <?php } ?>
                                    <div class="ms-3">
                                        <div class="d-flex flex-column">
                                            <div class="fw-bold">6<sup>th</sup> Month Review
                                                <?php if ($hasSixthMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                                    <span class="badge rounded-pill bg-success">Done</span>
                                                <?php } else if (!$hasSixthMonthReview && $revieweeEmployeeId != $employeeId && $sixthMonthDaysDifference < 7 && $sixthMonthDaysDifference >= 0) { ?>
                                                        <span class="badge rounded-pill bg-warning">Due Soon</span>
                                                <?php } else if (!$hasSixthMonthReview && $sixthMonthDaysDifference < 0) { ?>
                                                            <span class="badge rounded-pill bg-danger">Past Due</span>
                                                <?php } else {
                                                    } ?>
                                            </div>
                                            <div>
                                                <small class="text-secondary">Due: <?php echo $sixthMonthDueDate ?></small>
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn ms-auto" data-bs-toggle="modal"
                                        data-bs-target="#sixthMonthPerformanceReviewModal">
                                        <i class="fa-solid fa-arrow-up-right-from-square signature-color"></i>
                                    </button>
                                </div>

                                <!-- Ninth Month Review -->
                                <hr />
                                <div class="d-flex align-items-center">
                                    <?php $hasNinthMonthReview = false;
                                    foreach ($performance_review_result as $row) {
                                        if ($row['review_type'] === "Ninth Month Review") {
                                            $hasNinthMonthReview = true;
                                            break;
                                        }
                                    } ?>
                                    <?php
                                    $ninthMonthDueDateFormat = new DateTime($ninthMonthDueDate);
                                    $ninthMonthInterval = $today->diff($ninthMonthDueDateFormat);
                                    $ninthMonthDaysDifference = $ninthMonthInterval->format('%r%a');

                                    ?>
                                    <?php if ($hasNinthMonthReview) { ?>
                                        <i class="fa-solid fa-star fa-lg text-warning"></i>
                                    <?php } else { ?>
                                        <i class="fa-solid fa-star fa-lg text-secondary"></i>
                                    <?php } ?>
                                    <div class="ms-3">
                                        <div class="d-flex flex-column">
                                            <div class="fw-bold">9<sup>th</sup> Month Review
                                                <?php if ($hasNinthMonthReview) { ?>
                                                    <span class="badge rounded-pill bg-success">Done</span>
                                                <?php } else if (!$hasNinthMonthReview && $revieweeEmployeeId != $employeeId && $reviewerEmployeeId != $loginEmployeeId && $ninthMonthDaysDifference < 7 && $ninthMonthDaysDifference >= 0) { ?>
                                                        <span class="badge rounded-pill bg-warning">Due Soon</span>
                                                <?php } else if (!$hasNinthMonthReview && $ninthMonthDaysDifference < 0) { ?>
                                                            <span class="badge rounded-pill bg-danger">Past Due</span>
                                                <?php } else {
                                                    } ?>
                                            </div>
                                            <div>
                                                <small class="text-secondary">Due: <?php echo $ninthMonthDueDate ?></small>
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn ms-auto" data-bs-toggle="modal"
                                        data-bs-target="#ninthMonthPerformanceReviewModal">
                                        <i class="fa-solid fa-arrow-up-right-from-square signature-color"></i>
                                    </button>
                                </div>

                                <!-- <?php echo $firstMonthDueDate . " " . $thirdMonthDueDate . " " . $sixthMonthDueDate . " " . $ninthMonthDueDate ?> -->

                            </div>
                        </div>


                    <?php } ?>

                    <div class="card bg-white border-0 rounded shadow-lg mt-4">
                        <div class="p-3">
                            <p class="fw-bold signature-color">Files</p>
                            <div class="d-flex justify-content-center">
                                <div class="row col-12 p-2 background-color rounded shadow-sm">
                                    <div class="col-auto d-flex align-items-center">
                                        <div class="col-auto d-flex align-items-center">
                                            <span class="folder-icon tooltips" data-bs-toggle="tooltip"
                                                data-bs-placement="top" title="Open Folder">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                                    <form method="POST">
                                                        <input type="hidden" name="payReviewFolder">
                                                        <button type="submit"
                                                            class="btn btn-link p-0 m-0 text-decoration-underline fw-bold"><i
                                                                class="fa-regular fa-folder-open text-warning fa-xl d-none"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex flex-column">
                                                <form method="POST">
                                                    <input type="hidden" name="payReviewFolder">
                                                    <button type="submit"
                                                        class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">Pay
                                                        Review</button>
                                                </form>
                                                <span>
                                                    <div class="d-flex align-items-center">
                                                        <small id="pay-review-directory-path" class="me-1 text-break"
                                                            style="color:#b1b1b1"><?php echo "/Users/jovinhampton/Documents/Employees/$employeeId/Pay%20Review" ?></small>
                                                        <button id="copy-button" class="btn rounded btn-sm"
                                                            onclick="copyDirectoryPath(this)"><i
                                                                class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                            <small class="text-primary">Copy</small>
                                                        </button>
                                                    </div>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center mt-3">
                                <div class="row col-12 p-2 background-color rounded shadow-sm">
                                    <div class="col-auto d-flex align-items-center">
                                        <span class="folder-icon tooltips" data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="Open Folder">
                                            <div class="d-flex align-items-center">
                                                <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                                <form method="POST">
                                                    <input type="hidden" name="annualLeaveFolder">
                                                    <button type="submit"
                                                        class="btn btn-link p-0 m-0 text-decoration-underline fw-bold"><i
                                                            class="fa-regular fa-folder-open text-warning fa-xl d-none"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex flex-column">
                                                <form method="POST">
                                                    <input type="hidden" name="annualLeaveFolder">
                                                    <button type="submit"
                                                        class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">Annual
                                                        Leave</button>
                                                </form>
                                                <span>
                                                    <div class="d-flex align-items-center">
                                                        <small id="annual-leaves-directory-path" class="me-1 text-break"
                                                            style="color:#b1b1b1"><?php echo "/Users/jovinhampton/Documents/Employees/$employeeId/Annual%20Leaves" ?></small>
                                                        <button id="copy-button-annual" class="btn rounded btn-sm"
                                                            onclick="copyDirectoryPath(this)"><i
                                                                class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                            <small class="text-primary">Copy</small>
                                                        </button>
                                                    </div>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center mt-3">
                                <div class="row col-12 p-2 background-color rounded shadow-sm">
                                    <div class="col-auto d-flex align-items-center">
                                        <span class="folder-icon tooltips" data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="Open Folder">
                                            <div class="d-flex align-items-center">
                                                <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                                <form method="POST">
                                                    <input type="hidden" name="policiesFolder">
                                                    <button type="submit"
                                                        class="btn btn-link p-0 m-0 text-decoration-underline fw-bold"><i
                                                            class="fa-regular fa-folder-open text-warning fa-xl d-none"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex flex-column">
                                            <form method="POST">
                                                    <input type="hidden" name="policiesFolder">
                                                    <button type="submit"
                                                        class="btn btn-link p-0 m-0 text-decoration-underline fw-bold">Policies</button>
                                                </form>
                                                <span>
                                                    <div class="d-flex align-items-center">
                                                        <small id="directory-path" class="me-1 text-break"
                                                            style="color:#b1b1b1"><?php echo "/Users/jovinhampton/Documents/Employees/$employeeId/Policies" ?></small>
                                                        <button id="copy-button-policies" class="btn rounded btn-sm"
                                                            onclick="copyDirectoryPath(this)"><i
                                                                class="fa-regular fa-copy text-primary fa-xs p-0 m-0"></i>
                                                            <small class="text-primary">Copy</small>
                                                        </button>
                                                    </div>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card bg-white border-0 rounded shadow-lg mt-4">
                        <div class="p-3">
                            <div class="d-flex justify-content-between">
                                <p class="fw-bold signature-color">Policies</p>
                                <p type="button"><i class="fa-solid fa-plus signature-color" data-bs-toggle="modal"
                                        data-bs-target="#addPoliciesModal"></i></p>
                            </div>
                            <a href="http://localhost/FUJI-Directories/CheckDirectory/Sample_Smoking_Policy.jpeg">
                                Smoking and Vaping Policy </a>
                        </div>
                    </div>
                    <div class="card bg-white border-0 rounded shadow-lg mt-4">
                        <div class="p-3">
                            <p class="fw-bold signature-color">Access</p>

                            <?php
                            // Check if there are any results
                            if ($employee_group_access_result->num_rows > 0) {
                                $current_group_id = null;

                                // Initialize arrays to store unique group names and folder names
                                $unique_group_names = [];
                                $unique_folders = [];

                                // Fetch all rows from the result set
                                while ($row = $employee_group_access_result->fetch_assoc()) {
                                    $group_id = $row['group_id'];
                                    $group_name = htmlspecialchars($row['group_name']);
                                    $folder_id = htmlspecialchars($row['folder_id']);
                                    $folder_name = htmlspecialchars($row['folder_name']);

                                    // Collect unique group names
                                    if (!isset($unique_group_names[$group_id])) {
                                        $unique_group_names[$group_id] = $group_name;
                                    }

                                    // Collect unique folder names
                                    $unique_folders[$folder_id] = $folder_name;
                                }

                                // Output unique group names
                                if (!empty($unique_group_names)) {
                                    echo "<strong>Groups:</strong><br>";
                                    foreach ($unique_group_names as $group_id => $group_name) {
                                        echo "<p>$group_name</p>";
                                    }
                                    echo "<hr>";
                                }

                                // Output unique folder names
                                if (!empty($unique_folders)) {
                                    echo "<strong>Folders:</strong><br>";
                                    foreach ($unique_folders as $folder_id => $folder_name) {
                                        echo "<p>$folder_name</p>";
                                    }
                                }
                            } else {
                                echo '<p>No group or folder access found.</p>';
                            }
                            ?>
                        </div>
                    </div>
                    <!-- ================== Pay History Modal ================== -->
                    <div class="modal fade" id="payRaiseHistoryModal" tabindex="-2"
                        aria-labelledby="payRaiseHistoryModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold" id="payRaiseHistoryModalLabel">Pay Raise History
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-5">
                                    <div class="table-responsive border rounded-3">
                                        <table class="table table-hover mb-0 pb-0">
                                            <thead class="table-primary">
                                                <tr class="text-center">
                                                    <th class="py-3">Date</th>
                                                    <th class="py-3">Amount</th>
                                                    <th class="py-3">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($wagesData)) { ?>
                                                    <?php foreach ($wagesData as $row) { ?>
                                                        <tr class="text-center align-middle">
                                                            <form method="POST">
                                                                <!-- Hidden input for wages_id -->
                                                                <input type="hidden" name="wages_id"
                                                                    value="<?php echo $row['wages_id']; ?>">
                                                                <td class="align-middle col-md-6">
                                                                    <span
                                                                        class="view-mode"><?php echo date("j F Y", strtotime($row['date'])); ?></span>
                                                                    <input type="date" class="form-control edit-mode d-none mx-auto"
                                                                        name="editDate"
                                                                        value="<?php echo date("Y-m-d", strtotime($row['date'])); ?>"
                                                                        style="width: 80%">
                                                                </td>
                                                                <td class="align-middle col-md-3">
                                                                    <span class="view-mode">$<?php echo $row['amount']; ?></span>
                                                                    <input type="text" class="form-control edit-mode d-none mx-auto"
                                                                        name="editWage" value="<?php echo $row['amount']; ?>">
                                                                </td>
                                                                <td class="align-middle">
                                                                    <!-- Edit form -->
                                                                    <div class="view-mode">
                                                                        <button type="button" class="btn btn-sm edit-btn p-0"><i
                                                                                class="fa-regular fa-pen-to-square signature-color m-1"></i></button>
                                                                        <div class="btn" id="#openDeleteConfirmation"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#deleteConfirmationModal"
                                                                            data-wageamount="<?php echo $row['amount']; ?>"
                                                                            data-wagedate="<?php echo date("j F Y", strtotime($row['date'])); ?>">
                                                                            <i class="fa-solid fa-trash-can text-danger m-1"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div class="edit-mode d-none d-flex justify-content-center">
                                                                        <button type="submit"
                                                                            class="btn btn-sm px-2 btn-success mx-1">
                                                                            <div class="d-flex justify-content-center"><i
                                                                                    role="button"
                                                                                    class="fa-solid fa-check text-white m-1"></i>
                                                                                Edit </div>
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-sm px-2 btn-danger mx-1 edit-btn">
                                                                            <div class="d-flex justify-content-center"> <i
                                                                                    role="button"
                                                                                    class="fa-solid fa-xmark  text-white m-1"></i>Cancel
                                                                            </div>
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </form>

                                                        </tr>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <tr class=" text-center align-middle">
                                                        <td colspan="3">No records found</td>
                                                    </tr>
                                                <?php } ?>

                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-start mt-4">
                                        <form method="POST" class="col-md-6">
                                            <div class="row">
                                                <label for="newWage" class="fw-bold">New Wage</label>
                                                <div class="d-flex justify-content-center align-items-center">
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" min="0" class="form-control rounded-end "
                                                            id="newWage" name="newWage">
                                                        <button class="btn btn-dark ms-2 rounded">Modify
                                                            Wage</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Delete Confirmation Modal ================== -->
                    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1"
                        aria-labelledby="deleteConfirmationLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteConfirmationLabel">Confirm Deletion</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <!-- Delete form -->
                                    Are you sure you want to delete the wage record for <b> <span id="wageDate"></span>
                                    </b>
                                    with an amount of <b> $<span id="wageAmount"></b></span>?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <!-- Add form submission for deletion here -->
                                    <form method="POST">
                                        <input type="hidden" name="wages_id" value="<?php echo $row['wages_id']; ?>">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Edit Profile Modal ================== -->
                    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editProfileLabel">Edit Profile</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="px-5">
                                    <!-- ================== Edit Profile Form ================== -->
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="signature-color fw-bold mt-5"> Personal Details </p>
                                            <?php if ($isActive == 0) {
                                                echo '<form method="POST"> <input type="hidden" name="employeeIdToActivate" value="' . $employeeId . '"/> <button class="btn btn-sm btn-success mt-4">Activate Employee</button></form>';
                                            } else if ($isActive == 1) {
                                                echo '<form method="POST"> <input type="hidden" name="employeeIdToDeactivate" value="' . $employeeId . '"/><button class="btn btn-sm btn-danger mt-4">Deactivate Employee</button></form>';
                                            }
                                            ?>
                                        </div>
                                        <div class="row">
                                            <div class="form-group col-md-12 d-flex align-items-center mb-4">
                                                <div class="col-md-2 d-flex justify-content-center align-items-center">
                                                    <?php if (!empty($profileImage)) { ?>
                                                        <!-- Profile image -->
                                                        <div class="bg-gradient shadow-lg rounded-circle"
                                                            style="width: 100px; height: 100px; overflow: hidden;">
                                                            <img src="data:image/jpeg;base64,<?php echo $profileImage; ?>"
                                                                alt="Profile Image" class="profile-pic img-fluid rounded-circle"
                                                                style="width: 100%; height: 100%; object-fit: cover;">
                                                        </div>
                                                    <?php } else { ?>
                                                        <!-- Initials -->
                                                        <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center"
                                                            style="width: 100px; height: 100px;">
                                                            <h3 class="p-0 m-0">
                                                                <?php echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); ?>
                                                            </h3>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                                <div class="col-md-10 ps-3">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <div class="d-flex">
                                                            <form method="POST" class="col-md-12"
                                                                enctype="multipart/form-data">
                                                                <input type="hidden" name="profileImageAction"
                                                                    value="delete">
                                                                <input type="hidden" name="profileImageToDeleteEmpId"
                                                                    value="<?php echo $employeeId; ?>">
                                                                <label for="profileImageToEdit" class="fw-bold">Profile
                                                                    Image</label>
                                                                <input type="file" id="profileImageToEdit"
                                                                    name="profileImageToEdit" class="form-control required">
                                                                <button type="submit" class="btn btn-sm btn-danger mt-2"
                                                                    name="deleteProfileImage">Delete Profile
                                                                    Image</button>
                                                                <button type="submit" class="btn btn-sm btn-dark mt-2"
                                                                    name="changeProfileImage">Change Profile
                                                                    Image</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-center">
                                                <hr style="width:100%" />
                                            </div>

                                            <form method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="employeeIdToEdit"
                                                    value="<?php echo $employeeId ?>">
                                                <div class="row">
                                                    <div class="form-group col-md-6">
                                                        <label for="firstName" class="fw-bold">First Name</label>
                                                        <input type="text" name="firstName" class="form-control"
                                                            id="firstName"
                                                            value="<?php echo (isset($firstName) ? $firstName : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-6 mt-3 mt-md-0">
                                                        <label for="lastName" class="fw-bold">Last Name</label>
                                                        <input type="text" name="lastName" class="form-control"
                                                            id="lastName"
                                                            value="<?php echo (isset($lastName) ? $lastName : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-2 mt-3">
                                                        <label for="gender" class="fw-bold">Gender</label>
                                                        <select class="form-select" aria-label="gender" name="gender">
                                                            <option disabled selected hidden></option>
                                                            <option value="Male" <?php if (isset($gender) && $gender == "Male")
                                                                echo "selected"; ?>>Male</option>
                                                            <option value="Female" <?php if (isset($gender) && $gender == "Female")
                                                                echo "selected"; ?>>Female
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-md-3 mt-3">
                                                        <label for="dob" class="fw-bold">Date of Birth</label>
                                                        <input type="date" name="dob" class="form-control" id="dob"
                                                            value="<?php echo (isset($dob) ? $dob : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-4 mt-3">
                                                        <label for="visaStatus" class="fw-bold">Visa Status</label>
                                                        <select class="form-select" aria-label="Visa Status"
                                                            name="visaStatus">
                                                            <option disabled selected hidden></option>
                                                            <option value="Citizen" <?php if (isset($visaStatus) && $visaStatus === "Citizen")
                                                                echo "selected"; ?>>Citizen
                                                            </option>
                                                            <option value="Permanent Resident" <?php if (isset($visaStatus) && $visaStatus == "Permanent Resident")
                                                                echo "selected"; ?>>
                                                                Permanent Resident</option>
                                                            <option value="Student" <?php if (isset($visaStatus) && $visaStatus === "Student")
                                                                echo "selected"; ?>>Student
                                                            </option>
                                                            <option value="Working Holiday" <?php if (isset($visaStatus) && $visaStatus === "Working Holiday")
                                                                echo "selected"; ?>>Working
                                                                Holiday
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-md-3 mt-3">
                                                        <label for="visaExpiryDate" class="fw-bold">Visa Expiry
                                                            Date</label>
                                                        <input type="date" name="visaExpiryDate" class="form-control"
                                                            id="visaExpiryDate"
                                                            value="<?php echo (isset($visaExpiryDate) ? $visaExpiryDate : "") ?>">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <p class="signature-color fw-bold mt-5"> Contacts</p>
                                                    <div class="form-group col-md-12">
                                                        <label for="address" class="fw-bold">Address</label>
                                                        <input type="text" class="form-control" id="address" name="address"
                                                            value="<?php echo (isset($address) && $address !== "" ? $address : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-6 mt-3">
                                                        <label for="email" class="fw-bold">Email</label>
                                                        <input type="text" class="form-control" id="email" name="email"
                                                            value="<?php echo (isset($email) && $email !== "" ? $email : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-6 mt-3">
                                                        <label for="phoneNumber" class="fw-bold">Phone
                                                            Number</label>
                                                        <input type="text" class="form-control" id="phoneNumber"
                                                            name="phoneNumber"
                                                            value="<?php echo (isset($phoneNumber) && $phoneNumber !== "" ? $phoneNumber : "") ?>">
                                                    </div>

                                                    <p class="signature-color fw-bold mt-5"> Emergency Contacts</p>
                                                    <div class="form-group col-md-6">
                                                        <label for="emergencyContactName" class="fw-bold">Emergency
                                                            Contact Name</label>
                                                        <input type="text" class="form-control" id="emergencyContactName"
                                                            name="emergencyContactName"
                                                            value="<?php echo (isset($emergencyContactName) && $emergencyContactName !== "" ? $emergencyContactName : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-6 mt-3 mt-md-0">
                                                        <label for="emergencyContactRelationship" class="fw-bold">Emergency
                                                            Contact Relationship </label>
                                                        <input type="text" class="form-control"
                                                            id="emergencyContactRelationship"
                                                            name="emergencyContactRelationship"
                                                            value="<?php echo (isset($emergencyContactRelationship) && $emergencyContactRelationship !== "" ? $emergencyContactRelationship : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-6 mt-3">
                                                        <label for="emergencyContact" class="fw-bold">Emergency
                                                            Contact</label>
                                                        <input type="text" class="form-control" id="emergencyContact"
                                                            name="emergencyContact"
                                                            value="<?php echo (isset($emergencyContact) && $emergencyContact !== "" ? $emergencyContact : "") ?>">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <p class="signature-color fw-bold mt-5"> Employment Details</p>
                                                    <div class="form-group col-md-3">
                                                        <label for="employeeId" class="fw-bold">Employee Id</label>
                                                        <input type="number" class="form-control" id="employeeId"
                                                            name="employeeId"
                                                            value="<?php echo (isset($employeeId) && $employeeId !== "" ? $employeeId : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <label for="startDate" class="fw-bold mt-3 mt-md-0">Date
                                                            Hired</label>
                                                        <input type="date" class="form-control" id="startDate"
                                                            name="startDate"
                                                            value="<?php echo (isset($startDate) && $startDate !== "" ? $startDate : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-5">
                                                        <label for="employmentStatus"
                                                            class="fw-bold mt-3 mt-md-0">Employment Type</label>
                                                        <select class="form-select" aria-label="Employment Status"
                                                            name="employmentType">
                                                            <option disabled selected hidden></option>
                                                            <option value="Full-Time" <?php if (isset($employmentType) && $employmentType == "Full-Time")
                                                                echo "selected"; ?>>
                                                                Full-Time
                                                            </option>
                                                            <option value="Part-Time" <?php if (isset($employmentType) && $employmentType == "Part-Time")
                                                                echo "selected"; ?>>
                                                                Part-Time
                                                            </option>
                                                            <option value="Casual" <?php if (isset($employmentType) && $employmentType == "Casual")
                                                                echo "selected"; ?>>
                                                                Casual
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <div class="form-group col-md-4 mt-3">
                                                        <label for="department" class="fw-bold">Department</label>
                                                        <select class="form-select" aria-label="deparment" name="department"
                                                            id="department">
                                                            <option disabled selected hidden></option>
                                                            <option value="Electrical" <?php if (isset($department) && $department == "Electrical")
                                                                echo "selected"; ?>>
                                                                Electrical
                                                            </option>
                                                            <option value="Sheet Metal" <?php if (isset($department) && $department == "Sheet Metal")
                                                                echo "selected"; ?>>
                                                                Sheet Metal
                                                            </option>
                                                            <option value="Office" <?php if (isset($department) && $department == "Office")
                                                                echo "selected"; ?>>Office
                                                            </option>
                                                        </select>
                                                    </div>

                                                    <div class="form-group col-md-4 mt-3" id="sectionField"
                                                        style="display:none">
                                                        <label for="section" class="fw-bold">Section</label>
                                                        <select class="form-select" aria-label="Section" name="section"
                                                            id="section">
                                                            <!-- Options will be dynamically populated based on department selection -->
                                                            <?php
                                                            // PHP to generate options based on $department value
                                                            if (isset($department)) {
                                                                switch ($department) {
                                                                    case "Electrical":
                                                                        echo '<option value="Panel" id="panelSection" ' . (isset($section) && $section == "Panel" ? "selected" : "") . '>Panel</option>';
                                                                        echo '<option value="Roof" id="roofSection" ' . (isset($section) && $section == "Roof" ? "selected" : "") . '>Roof</option>';
                                                                        break;
                                                                    case "Sheet Metal":
                                                                        echo '<option value="Programmer" id="ProgrammerSection" ' . (isset($section) && $section == "Programmer" ? "selected" : "") . '>Programmer</option>';
                                                                        echo '<option value="Painter" id="painterSection" ' . (isset($section) && $section == "Painter" ? "selected" : "") . '>Painter</option>';
                                                                        break;
                                                                    case "Office":
                                                                        echo '<option value="Engineer" id="engineerSection" ' . (isset($section) && $section == "Engineer" ? "selected" : "") . '>Engineer</option>';
                                                                        echo '<option value="Accountant" id="accountantSection" ' . (isset($section) && $section == "Accountant" ? "selected" : "") . '>Accountant</option>';
                                                                        break;
                                                                    default:
                                                                        break;
                                                                }
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>

                                                    <div class="form-group col-md-5 mt-3">
                                                        <label for="position" class="fw-bold">Position</label>
                                                        <input type="text" class="form-control" id="position"
                                                            name="position"
                                                            value="<?php echo (isset($position) && $position !== "" ? $position : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-12 mt-3">
                                                        <label for="policy" class="fw-bold">Upload Policy
                                                            Files</label>
                                                        <div class="input-group">
                                                            <input type="file" id="policy" name="policy_files[]"
                                                                class="form-control" multiple>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <p class="signature-color fw-bold mt-5"> Banking, Super and Tax
                                                        Details
                                                    </p>
                                                    <div class="form-group col-md-5">
                                                        <label for="bankBuildingSociety" class="fw-bold">Bank/Building
                                                            Society</label>
                                                        <input type="text" class="form-control" id="bankBuildingSociety"
                                                            name="bankBuildingSociety"
                                                            value="<?php echo (isset($bankBuildingSociety) && $bankBuildingSociety !== "" ? $bankBuildingSociety : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-3 mt-3 mt-md-0">
                                                        <label for="bsb" class="fw-bold">BSB</label>
                                                        <input type="text" class="form-control" id="bsb" name="bsb"
                                                            value="<?php echo (isset($bsb) && $bsb !== "" ? $bsb : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-4 mt-3 mt-md-0">
                                                        <label for="accountNumber" class="fw-bold">Account
                                                            Number</label>
                                                        <input type="text" class="form-control" id="accountNumber"
                                                            name="accountNumber"
                                                            value="<?php echo (isset($accountNumber) && $accountNumber !== "" ? $accountNumber : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-6 mt-3">
                                                        <label for="uniqueSuperannuationIdentifier" class="fw-bold">Unique
                                                            Superannuation
                                                            Identifier</label>
                                                        <input type="text" class="form-control"
                                                            id="uniqueSuperannuationIdentifier"
                                                            name="uniqueSuperannuationIdentifier"
                                                            value="<?php echo (isset($uniqueSuperannuationIdentifier) && $uniqueSuperannuationIdentifier !== "" ? $uniqueSuperannuationIdentifier : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-6 mt-3">
                                                        <label for="superannuationFundName" class="fw-bold">Superannuation
                                                            Fund Name</label>
                                                        <input type="text" class="form-control" id="superannuationFundName"
                                                            name="superannuationFundName"
                                                            value="<?php echo (isset($superannuationFundName) && $superannuationFundName !== "" ? $superannuationFundName : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-6 mt-3">
                                                        <label for="superannuationMemberNumber"
                                                            class="fw-bold">Superannuation Member Number</label>
                                                        <input type="text" class="form-control"
                                                            id="superannuationMemberNumber"
                                                            name="superannuationMemberNumber"
                                                            value="<?php echo (isset($superannuationMemberNumber) && $superannuationMemberNumber !== "" ? $superannuationMemberNumber : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-6 mt-3">
                                                        <label for="taxFileNumber" class="fw-bold">Tax File
                                                            Number</label>
                                                        <input type="text" class="form-control" id="taxFileNumber"
                                                            name="taxFileNumber"
                                                            value="<?php echo (isset($taxFileNumber) && $taxFileNumber !== "" ? $taxFileNumber : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-6 mt-3">
                                                        <div class="d-flex flex-column">
                                                            <label for="higherEducationLoanProgramme"
                                                                class="fw-bold"><small>Higher Education Loan
                                                                    Programme?</small></label>
                                                            <div class="btn-group col-3 col-md-2" role="group">
                                                                <input type="radio" class="btn-check"
                                                                    name="higherEducationLoanProgramme"
                                                                    id="higherEducationLoanProgrammeYes" value="1"
                                                                    autocomplete="off" <?php echo ($higherEducationLoanProgramme == 1) ? 'checked' : ''; ?>>
                                                                <label class="btn btn-sm btn-custom"
                                                                    for="higherEducationLoanProgrammeYes"
                                                                    style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                                                                <input type="radio" class="btn-check"
                                                                    name="higherEducationLoanProgramme"
                                                                    id="higherEducationLoanProgrammeNo" value="0"
                                                                    autocomplete="off" <?php echo ($higherEducationLoanProgramme == 0) ? 'checked' : ''; ?>>
                                                                <label class="btn btn-sm btn-custom"
                                                                    for="higherEducationLoanProgrammeNo"
                                                                    style="color:#043f9d; border: 1px solid #043f9d">No</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group col-md-6 mt-3">
                                                        <div class="d-flex flex-column">
                                                            <label for="financialSupplementDebt"
                                                                class="fw-bold"><small>Financial Supplement
                                                                    Debt?</small></label>
                                                            <div class="btn-group col-3 col-md-2" role="group">
                                                                <input type="radio" class="btn-check"
                                                                    name="financialSupplementDebt"
                                                                    id="financialSupplementDebtYes" value="1"
                                                                    autocomplete="off" <?php echo ($financialSupplementDebt == 1) ? 'checked' : ''; ?> />
                                                                <label class="btn btn-sm btn-custom"
                                                                    for="financialSupplementDebtYes"
                                                                    style="color:#043f9d; border: 1px solid #043f9d">
                                                                    Yes</label>

                                                                <input type="radio" class="btn-check"
                                                                    name="financialSupplementDebt"
                                                                    id="financialSupplementDebtNo" value="0"
                                                                    autocomplete="off" <?php echo ($financialSupplementDebt == 0) ? 'checked' : ''; ?> />
                                                                <label class="btn btn-sm btn-custom"
                                                                    for="financialSupplementDebtNo"
                                                                    style="color:#043f9d; border: 1px solid #043f9d">No</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-center mt-5 mb-4">
                                                    <button class="btn btn-dark" name="editEmployeeProfile"
                                                        type="submit">Edit Employee</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Add Policies Modal ================== -->
                    <div class="modal fade" id="addPoliciesModal" tabindex="-1" aria-labelledby="addPoliciesModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addPoliciesModalLabel">Add Policies Document</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Drag and Drop area -->
                                    <div class="border border-dashed p-4 text-center" id="dropZone">
                                        <p class="mb-0">Drag & Drop your documents here or <br>
                                            <button class="btn btn-primary btn-sm mt-2"
                                                onclick="document.getElementById('fileInput').click()">Browse
                                                Files</button>
                                        </p>
                                    </div>
                                    <form method="POST">
                                        <input type="file" id="fileInput" name="policiesToSubmit" class="d-none" multiple />
                                        <!-- Display uploaded file names -->
                                        <div id="fileList" class="mt-3"></div>
                                        <div class="d-flex justify-content-center">
                                            <button type="submit" class="btn btn-dark btn-sm"> Add
                                                Documents</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Performance Review Modal (First Month)================== -->
                    <div class="modal fade" id="firstMonthPerformanceReviewModal" tabindex="-1"
                        aria-labelledby="firstMonthPerformanceReviewModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="firstMonthPerformanceReviewModalLabel">1st Month Review</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <?php
                                    $hasFirstMonthReview = false;
                                    $firstMonthReviewDate = '';
                                    $firstMonthReviewerEmployeeId = '';
                                    foreach ($performance_review_result as $row) {
                                        if ($row['review_type'] === "First Month Review") {
                                            $hasFirstMonthReview = true;
                                            $firstMonthReviewDate = $row['review_date'];
                                            $firstMonthReviewerEmployeeId = $row['reviewer_employee_id'];
                                            break;
                                        }
                                    } ?>

                                    <?php
                                    $query = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("s", $firstMonthReviewerEmployeeId);
                                    $stmt->execute();
                                    $stmt->bind_result($firstMonthFirstName, $firstMonthLastName);
                                    $stmt->fetch();
                                    $stmt->close();
                                    ?>
                                    <div class="modal-body">
                                        <p><strong>Reviewer: </strong><span
                                                class="signature-color fw-bold"><?php echo $firstMonthFirstName . " " . $firstMonthLastName ?>
                                            </span></p>
                                        <p><strong>Reviewee: </strong><span class="signature-color fw-bold">
                                                <?php echo $firstName . " " . $lastName ?> </span></p>
                                        <?php if ($hasFirstMonthReview && $revieweeEmployeeId == $employeeId) { ?>
                                            <div class="review-details">
                                                <p><strong>Review Date:</strong>
                                                    <?php echo htmlspecialchars($firstMonthReviewDate); ?>
                                                </p>
                                                <p><strong>Reviewee Employee ID:</strong>
                                                    <?php echo htmlspecialchars($revieweeEmployeeId); ?></p>
                                                <p><strong>Review Notes:</strong>
                                                    <?php echo htmlspecialchars($reviewNotes); ?></p>
                                            </div>
                                        <?php } else { ?>
                                            <input type="hidden" name="revieweeEmployeeIdFirstMonthReview"
                                                value="<?php echo htmlspecialchars($employeeId); ?>" />
                                            <input type="hidden" name="reviewerEmployeeId"
                                                value="<?php echo htmlspecialchars($loginEmployeeId); ?>" />
                                            <input type="hidden" name="reviewType" value="First Month Review" />
                                            <div class="mb-3">
                                                <label for="reviewDate" class="form-label"><strong>Review Date:</strong></label>
                                                <input type="date" class="form-control" id="reviewDate" name="reviewDate">
                                            </div>
                                            <div class="mb-3">
                                                <label for="reviewNotes" class="form-label"><strong>Review
                                                        Notes:</strong></label>
                                                <textarea class="form-control" id="reviewNotes" name="reviewNotes"
                                                    rows="4"></textarea>
                                            </div>

                                        <?php } ?>
                                    </div>
                                    <!-- Additional Modal Actions -->
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-dark">Submit Review</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Performance Review Modal (Third Month)================== -->
                    <div class="modal fade" id="thirdMonthPerformanceReviewModal" tabindex="-1"
                        aria-labelledby="thirdMonthPerformanceReviewModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="thirdMonthPerformanceReviewModalLabel">1st Month Review</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <?php $hasThirdMonthReview = false;
                                    foreach ($performance_review_result as $row) {
                                        if ($row['review_type'] === "Third Month Review") {
                                            $hasThirdMonthReview = true;
                                            break;
                                        }
                                    } ?>
                                    <div class="modal-body">
                                        <p><strong>Reviewer: </strong><span
                                                class="signature-color fw-bold"><?php echo $loginEmployeeFirstName . " " . $loginEmployeeLastName ?>
                                            </span></p>
                                        <p><strong>Reviewee: </strong><span class="signature-color fw-bold">
                                                <?php echo $firstName . " " . $lastName ?> </span></p>
                                        <?php if ($hasThirdMonthReview && $revieweeEmployeeId == $employeeId && $reviewerEmployeeId == $loginEmployeeId) { ?>
                                            <div class="review-details">
                                                <p><strong>Review Date:</strong> <?php echo htmlspecialchars($reviewDate); ?>
                                                </p>
                                                <p><strong>Reviewee Employee ID:</strong>
                                                    <?php echo htmlspecialchars($revieweeEmployeeId); ?></p>
                                                <p><strong>Review Notes:</strong>
                                                    <?php echo htmlspecialchars($reviewNotes); ?></p>
                                            </div>
                                        <?php } else { ?>
                                            <input type="hidden" name="revieweeEmployeeIdThirdMonthReview"
                                                value="<?php echo htmlspecialchars($employeeId); ?>" />
                                            <input type="hidden" name="reviewerEmployeeId"
                                                value="<?php echo htmlspecialchars($loginEmployeeId); ?>" />
                                            <input type="hidden" name="reviewType" value="Third Month Review" />
                                            <div class="mb-3">
                                                <label for="reviewDate" class="form-label"><strong>Review Date:</strong></label>
                                                <input type="date" class="form-control" id="reviewDate" name="reviewDate">
                                            </div>
                                            <div class="mb-3">
                                                <label for="reviewNotes" class="form-label"><strong>Review
                                                        Notes:</strong></label>
                                                <textarea class="form-control" id="reviewNotes" name="reviewNotes"
                                                    rows="4"></textarea>
                                            </div>

                                        <?php } ?>
                                    </div>
                                    <!-- Additional Modal Actions -->
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-dark">Submit Review</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Performance Review Modal (Sixth Month)================== -->
                    <div class="modal fade" id="sixthMonthPerformanceReviewModal" tabindex="-1"
                        aria-labelledby="sixthMonthPerformanceReviewModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="sixthMonthPerformanceReviewModalLabel">6th Month Review</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <?php $hasSixthMonthReview = false;
                                    foreach ($performance_review_result as $row) {
                                        if ($row['review_type'] === "Sixth Month Review") {
                                            $hasSixthMonthReview = true;
                                            break;
                                        }
                                    } ?>
                                    <div class="modal-body">
                                        <p><strong>Reviewer: </strong><span
                                                class="signature-color fw-bold"><?php echo $loginEmployeeFirstName . " " . $loginEmployeeLastName ?>
                                            </span></p>
                                        <p><strong>Reviewee: </strong><span class="signature-color fw-bold">
                                                <?php echo $firstName . " " . $lastName ?> </span></p>
                                        <?php if ($hasSixthMonthReview && $revieweeEmployeeId == $employeeId && $reviewerEmployeeId == $loginEmployeeId) { ?>
                                            <div class="review-details">
                                                <p><strong>Review Date:</strong> <?php echo htmlspecialchars($reviewDate); ?>
                                                </p>
                                                <p><strong>Reviewee Employee ID:</strong>
                                                    <?php echo htmlspecialchars($revieweeEmployeeId); ?></p>
                                                <p><strong>Review Notes:</strong>
                                                    <?php echo htmlspecialchars($reviewNotes); ?></p>
                                            </div>
                                        <?php } else { ?>
                                            <input type="hidden" name="revieweeEmployeeIdSixthMonthReview"
                                                value="<?php echo htmlspecialchars($employeeId); ?>" />
                                            <input type="hidden" name="reviewerEmployeeId"
                                                value="<?php echo htmlspecialchars($loginEmployeeId); ?>" />
                                            <input type="hidden" name="reviewType" value="Sixth Month Review" />
                                            <div class="mb-3">
                                                <label for="reviewDate" class="form-label"><strong>Review Date:</strong></label>
                                                <input type="date" class="form-control" id="reviewDate" name="reviewDate">
                                            </div>
                                            <div class="mb-3">
                                                <label for="reviewNotes" class="form-label"><strong>Review
                                                        Notes:</strong></label>
                                                <textarea class="form-control" id="reviewNotes" name="reviewNotes"
                                                    rows="4"></textarea>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <!-- Additional Modal Actions -->
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-dark">Submit Review</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- ================== Performance Review Modal (Ninth Month)================== -->
                    <div class="modal fade" id="ninthMonthPerformanceReviewModal" tabindex="-1"
                        aria-labelledby="ninthMonthPerformanceReviewModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="ninthMonthPerformanceReviewModalLabel">6th Month Review</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <?php $hasNinthMonthReview = false;
                                    foreach ($performance_review_result as $row) {
                                        if ($row['review_type'] === "Ninth Month Review") {
                                            $hasNinthMonthReview = true;
                                            break;
                                        }
                                    } ?>
                                    <div class="modal-body">
                                        <p><strong>Reviewer: </strong><span
                                                class="signature-color fw-bold"><?php echo $loginEmployeeFirstName . " " . $loginEmployeeLastName ?>
                                            </span></p>
                                        <p><strong>Reviewee: </strong><span class="signature-color fw-bold">
                                                <?php echo $firstName . " " . $lastName ?> </span></p>
                                        <?php if ($hasNinthMonthReview && $revieweeEmployeeId == $employeeId && $reviewerEmployeeId == $loginEmployeeId) { ?>
                                            <div class="review-details">
                                                <p><strong>Review Date:</strong> <?php echo htmlspecialchars($reviewDate); ?>
                                                </p>
                                                <p><strong>Reviewee Employee ID:</strong>
                                                    <?php echo htmlspecialchars($revieweeEmployeeId); ?></p>
                                                <p><strong>Review Notes:</strong>
                                                    <?php echo htmlspecialchars($reviewNotes); ?></p>
                                            </div>
                                        <?php } else { ?>
                                            <input type="hidden" name="revieweeEmployeeIdNinthMonthReview"
                                                value="<?php echo htmlspecialchars($employeeId); ?>" />
                                            <input type="hidden" name="reviewerEmployeeId"
                                                value="<?php echo htmlspecialchars($loginEmployeeId); ?>" />
                                            <input type="hidden" name="reviewType" value="Ninth Month Review" />
                                            <div class="mb-3">
                                                <label for="reviewDate" class="form-label"><strong>Review Date:</strong></label>
                                                <input type="date" class="form-control" id="reviewDate" name="reviewDate">
                                            </div>
                                            <div class="mb-3">
                                                <label for="reviewNotes" class="form-label"><strong>Review
                                                        Notes:</strong></label>
                                                <textarea class="form-control" id="reviewNotes" name="reviewNotes"
                                                    rows="4"></textarea>
                                            </div>

                                        <?php } ?>
                                    </div>
                                    <!-- Additional Modal Actions -->
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-dark">Submit Review</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
                }
                ?>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var myModalEl = document.getElementById('deleteConfirmationModal');
                myModalEl.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget; // Button that triggered the modal
                    var wageDate = button.getAttribute('data-wagedate'); // Extract info from data-* attributes
                    var wageAmount = button.getAttribute('data-wageamount');

                    // Update the modal's content with the extracted info
                    var modalWageDate = myModalEl.querySelector('#wageDate');
                    var modalWageAmount = myModalEl.querySelector('#wageAmount');
                    modalWageDate.textContent = wageDate;
                    modalWageAmount.textContent = wageAmount;
                });
            });
        </script>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                // Initialize Bootstrap modal
                var payRaiseHistoryModal = new bootstrap.Modal(document.getElementById('payRaiseHistoryModal'));

                // Pay Raise History edit icon click event
                document.querySelector('#payRaiseEditIcon').addEventListener('click', function () {
                    // Show the pay raise history modal
                    payRaiseHistoryModal.show();
                });
            });
        </script>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                // Edit button click event handler
                document.querySelectorAll('.edit-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        // Get the parent row
                        var row = this.closest('tr');

                        // Toggle edit mode
                        row.classList.toggle('editing');

                        // Toggle visibility of view and edit elements
                        row.querySelectorAll('.view-mode, .edit-mode').forEach(function (elem) {
                            elem.classList.toggle('d-none');
                        });
                    });
                });
            });
        </script>

        <script>
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('fileInput');
            const fileList = document.getElementById('fileList');
            let selectedFiles = [];

            dropZone.addEventListener('dragover', function (event) {
                event.preventDefault();
                dropZone.classList.add('bg-light');
            });

            dropZone.addEventListener('dragleave', function () {
                dropZone.classList.remove('bg-light');
            });

            dropZone.addEventListener('drop', function (event) {
                event.preventDefault();
                dropZone.classList.remove('bg-light');
                const files = event.dataTransfer.files;
                handleFiles(files);
            });

            fileInput.addEventListener('change', function (event) {
                const files = event.target.files;
                handleFiles(files);
            });

            function handleFiles(files) {
                for (let i = 0; i < files.length; i++) {
                    addFile(files[i]);
                }
                renderFileList();
            }

            function addFile(file) {
                selectedFiles.push(file);
            }

            function removeFile(index) {
                selectedFiles.splice(index, 1);
                renderFileList();
            }

            function renderFileList() {
                fileList.innerHTML = '';
                selectedFiles.forEach((file, index) => {
                    const listItem = document.createElement('div');
                    listItem.className = 'd-flex justify-content-between align-items-center border p-2 mb-2';
                    listItem.innerHTML = `
                    <span>${file.name}</span>
                    <button class="btn btn-danger btn-sm" onclick="removeFile(${index})">Remove</button>
                `;
                    fileList.appendChild(listItem);
                });
            }

            // Reset file input and selected files when the modal is closed
            document.getElementById('addPoliciesModal').addEventListener('hidden.bs.modal', function () {
                resetFileInput();
            });

            function resetFileInput() {
                fileInput.value = ''; // Clear the file input
                selectedFiles = []; // Clear the array of selected files
                renderFileList(); // Clear the file list display
            }
        </script>

        <script>
            $(document).ready(function () {
                // Event listener for modal close event
                $('#payRaiseHistoryModal').on('hidden.bs.modal', function () {
                    // For each row in the table
                    $('#payRaiseHistoryModal tbody tr').each(function () {
                        // Show view mode and hide edit mode
                        $(this).find('.view-mode').removeClass('d-none');
                        $(this).find('.edit-mode').addClass('d-none');
                    });
                });
            });
        </script>

        <script>
            function copyDirectoryPath(button) {
                var directoryPathElement = button.parentElement.querySelector('small.text-break');
                var textArea = document.createElement("textarea");

                // Place the directory path text inside the textarea
                textArea.textContent = directoryPathElement.textContent;

                // Ensure textarea is non-visible
                textArea.style.position = "fixed";
                textArea.style.opacity = 0;

                // Append the textarea to the document
                document.body.appendChild(textArea);

                // Select the text inside the textarea
                textArea.select();

                try {
                    // Execute the copy command
                    document.execCommand('copy');
                    console.log('Text copied successfully');

                    // Change button text to "Copied"
                    button.innerHTML = '<i class="fa-regular fa-check-circle text-success fa-xs"></i> <small class="text-success">Copied</small>';

                    // Reset button text after 2 seconds
                    setTimeout(function () {
                        button.innerHTML = '<i class="fa-regular fa-copy text-primary fa-xs"></i> <small class="text-primary">Copy</small>';
                    }, 2000); // 2000 milliseconds = 2 seconds

                } catch (err) {
                    console.error('Unable to copy text', err);
                }

                // Remove the textarea from the document
                document.body.removeChild(textArea);
            }

        </script>

        <script>
            // Enabling the tooltip
            const tooltips = document.querySelectorAll('.tooltips');
            tooltips.forEach(t => {
                new bootstrap.Tooltip(t);
            })
        </script>

        <script>
            $(document).ready(function () {
                // Department and section options mapping
                var departmentSections = {
                    'Electrical': ['Panel', 'Roof'],
                    'Sheet Metal': ['Programmer', 'Painter'],
                    'Office': ['Engineer', 'Accountant']
                };

                // Function to update section options based on department selection
                $('#department').change(function () {
                    var selectedDepartment = $(this).val();
                    var sections = departmentSections[selectedDepartment] || [];
                    var sectionSelect = $('#section');

                    // Clear previous options
                    sectionSelect.empty();

                    // Populate section options
                    sections.forEach(function (section) {
                        sectionSelect.append($('<option></option>').text(section));
                    });

                    // Show the section field if a department is selected
                    $('#sectionField').show();
                });

                // Trigger change event on page load if department is pre-selected
                $('#department').trigger('change');
            });
        </script>

        <script>
            window.onload = function () {

                var chart = new CanvasJS.Chart("chartContainer", {
                    animationEnabled: true,
                    axisX: {
                        titleFontFamily: "Avenir", // Set the font family for X axis title
                        titleFontSize: 14, // Set the font size for X axis title
                        titleFontWeight: "bold", // Set the font weight for X axis title
                        titleFontColor: "#555", // Set the font color for X axis title
                        labelFontFamily: "Avenir", // Set the font family for X axis labels
                        labelFontSize: 12, // Set the font size for X axis labels
                        labelFontColor: "#555" // Set the font color for X axis labels
                    },
                    data: [{
                        type: "line",
                        color: "#043f9d", // Set line color
                        markerColor: "#043f9d", // Set marker color
                        markerSize: 8, // Set marker size
                        dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
                    }]
                });
                chart.render();
            }
        </script>
        <script>
            // Restore scroll position after page reload
            window.addEventListener('load', function () {
                const scrollPosition = sessionStorage.getItem('scrollPosition');
                if (scrollPosition) {
                    window.scrollTo(0, scrollPosition);
                    sessionStorage.removeItem('scrollPosition'); // Remove after restoring
                }
            });

        </script>
</body>

</html>