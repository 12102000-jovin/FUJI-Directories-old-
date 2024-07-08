<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Connect to the database
require_once ("../db_connect.php");

if (isset($_GET['employee_id'])) {
    $employeeId = $_GET['employee_id'];
}

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

// Get the start date for the chart
if ($employee_start_date_result->num_rows > 0) {
    while ($row = $employee_start_date_result->fetch_assoc()) {
        $startDateChart = $row['start_date'];
    }
}

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
        $position = $_POST['position'];

        $edit_employee_detail_sql = "UPDATE employees SET first_name = ?, last_name = ?, gender = ?, dob = ?, visa = ?, address = ?, email= ?, phone_number = ?, emergency_contact_name = ?, emergency_contact_phone_number = ?, emergency_contact_relationship = ?, start_date = ?, department = ?, employment_type = ?, position = ? WHERE employee_id = ?";
        $edit_employee_detail_result = $conn->prepare($edit_employee_detail_sql);
        $edit_employee_detail_result->bind_param("sssssssssssssssi", $firstName, $lastName, $gender, $dob, $visaStatus, $address, $email, $phoneNumber, $emergencyContactName, $emergencyContact, $emergencyContactRelationship, $startDate, $department, $employmentType, $position, $employeeIdToEdit);

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
    </style>

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

    <style>
        .canvasjs-chart-credit {
            display: none !important;
        }
    </style>
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
                        $position = $row['position'];
                        $email = $row['email'];
                        $isActive = $row['is_active'];
                        $bankBuildingSociety = $row['bank_building_society'];
                        $bsb = $row['bsb'];
                        $accountNumber = $row['account_number'];
                        $superannuationFundName = $row['superannuation_fund_name'];
                        $uniqueSuperannuatioIdentifier = $row['unique_superannuation_identifier'];
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
                                        <?php echo isset($uniqueSuperannuatioIdentifier) ? $uniqueSuperannuatioIdentifier : "N/A" ?>
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
                    <div class="card bg-white border-0 rounded shadow-lg mt-4">
                        <div class="p-3">
                            <p class="fw-bold signature-color">Files</p>
                            <div class="d-flex justify-content-center">
                                <div class="row col-12 p-2 background-color rounded shadow-sm">
                                    <div class="col-auto d-flex align-items-center">
                                        <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="text-start fw-bold">Pay Review</span>
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
                                    <div class="col-auto d-flex align-items-center justify-content-end">
                                        <div class="dropdown">
                                            <button class="btn " type="button" id="dropdownMenuButton"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-solid fa-ellipsis" role="button"></i>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <li><a class="dropdown-item" href="../">Quick Access From Browser</a>
                                                </li>
                                                <li><a class="dropdown-item" onclick="copyDirectoryPath(this)">Copy
                                                        Folder</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center mt-3">
                                <div class="row col-12 p-2 background-color rounded shadow-sm">
                                    <div class="col-auto d-flex align-items-center">
                                        <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="text-start fw-bold">Annual Leave</span>
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
                                    <div class="col-auto d-flex align-items-center justify-content-end">
                                        <div class="dropdown">
                                            <button class="btn " type="button" id="dropdownMenuButton"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-solid fa-ellipsis" role="button"></i>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <li><a class="dropdown-item" href="../../">Quick Access From Browser</a>
                                                </li>
                                                <li><a class="dropdown-item" onclick="copyDirectoryPath(this)">Copy
                                                        Folder</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center mt-3">
                                <div class="row col-12 p-2 background-color rounded shadow-sm">
                                    <div class="col-auto d-flex align-items-center">
                                        <i class="fa-solid fa-folder text-warning fa-xl"></i>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex flex-column">
                                                <span class="text-start fw-bold">Policies</span>
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
                                    <div class="col-auto d-flex align-items-center justify-content-end">
                                        <div class="dropdown">
                                            <button class="btn " type="button" id="dropdownMenuButton"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-solid fa-ellipsis" role="button"></i>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <li><a class="dropdown-item" href="../../">Quick Access From Browser</a>
                                                </li>
                                                <li><a class="dropdown-item" onclick="copyDirectoryPath(this)">Copy
                                                        Folder</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="card bg-white border-0 rounded shadow-lg mt-4">
                        <div class="p-3">
                            <div class="d-flex justify-content-between">
                                <p class="fw-bold signature-color">Policies</p>
                                <p type="button"><i class="fa-solid fa-plus signature-color" data-bs-toggle="modal"
                                        data-bs-target="#addPoliciesModal"></i></p>
                            </div>
                            <a href="http://localhost/FUJI-Directories/CheckDirectory/Sample_Smoking_Policy.jpeg">
                                Smoking and Vaping Policy </a>
                        </div>
                    </div> -->
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
                                                        <button class="btn btn-dark ms-2 rounded">Modify Wage</button>
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
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
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
                                                    <div class="form-group col-md-3 mt-3">
                                                        <label for="gender" class="fw-bold">Gender</label>
                                                        <select class="form-select" aria-label="gender" name="gender">
                                                            <option disabled selected hidden></option>
                                                            <option value="Male" <?php if (isset($gender) && $gender == "Male")
                                                                echo "selected"; ?>>Male</option>
                                                            <option value="Female" <?php if (isset($gender) && $gender == "Female")
                                                                echo "selected"; ?>>Female</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-md-4 mt-3">
                                                        <label for="dob" class="fw-bold">Date of Birth</label>
                                                        <input type="date" name="dob" class="form-control" id="dob"
                                                            value="<?php echo (isset($dob) ? $dob : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-5 mt-3">
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
                                                                echo "selected"; ?>>Working Holiday
                                                            </option>
                                                        </select>
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
                                                        <label for="phoneNumber" class="fw-bold">Phone Number</label>
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
                                                    <div class="form-group col-md-6">
                                                        <label for="emergencyContactRelationship" class="fw-bold">Emergency
                                                            Contact Relationship</label>
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
                                                                echo "selected"; ?>>Full-Time
                                                            </option>
                                                            <option value="Part-Time" <?php if (isset($employmentType) && $employmentType == "Part-Time")
                                                                echo "selected"; ?>>Part-Time
                                                            </option>
                                                            <option value="Casual" <?php if (isset($employmentType) && $employmentType == "Casual")
                                                                echo "selected"; ?>>Casual
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-md-4 mt-3">
                                                        <label for="department" class="fw-bold">Department</label>
                                                        <select class="form-select" aria-label="deparment"
                                                            name="department">
                                                            <option disabled selected hidden></option>
                                                            <option value="Electrical" <?php if (isset($department) && $department == "Electrical")
                                                                echo "selected"; ?>>Electrical
                                                            </option>
                                                            <option value="Sheet Metal" <?php if (isset($department) && $department == "Sheet Metal")
                                                                echo "selected"; ?>>Sheet Metal
                                                            </option>
                                                            <option value="Office" <?php if (isset($department) && $department == "Office")
                                                                echo "selected"; ?>>Office
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-md-5 mt-3">
                                                        <label for="position" class="fw-bold">Position</label>
                                                        <input type="text" class="form-control" id="position"
                                                            name="position"
                                                            value="<?php echo (isset($position) && $position !== "" ? $position : "") ?>">
                                                    </div>
                                                    <div class="form-group col-md-12 mt-3">
                                                        <label for="policy" class="fw-bold">Upload Policy Files</label>
                                                        <div class="input-group">
                                                            <input type="file" id="policy" name="policy_files[]"
                                                                class="form-control" multiple>
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
                                            <button type="submit" class="btn btn-dark btn-sm"> Add Documents</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
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
</body>

</html>