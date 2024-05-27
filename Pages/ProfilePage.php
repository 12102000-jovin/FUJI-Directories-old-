<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Connect to the database
require_once("../db_connect.php");

if (isset($_GET['employee_id'])) {
    $employeeId = $_GET['employee_id'];
}

$employee_details_sql = "SELECT * FROM employees WHERE employee_id = $employeeId";
$employee_details_result = $conn->query($employee_details_sql);

$employee_start_date_sql = "SELECT start_date FROM employees WHERE employee_id = $employeeId";
$employee_start_date_result = $conn->query($employee_start_date_sql);

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

if ($_SERVER["REQUEST_METHOD"] === "POST" &&  isset($_POST['editDate']) && isset($_POST['editWage'])) {
    $editDate = $_POST["editDate"];
    $editWage = $_POST["editWage"];
    $wagesId = $_POST['wages_id'];

    echo $editDate . "  " .  $editWage . "  " . $wagesId;

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['deleteProfileImage'])) {
        // Handle profile image deletion
        if (isset($_POST['profileImageToDeleteEmpId'])) {
            $profileImageToDeleteEmpId = $_POST['profileImageToDeleteEmpId'];

            echo $profileImageToDeleteEmpId;

            $delete_profile_image_sql = "UPDATE employees SET profile_image = NULL where employee_id = ?";
            $delete_profile_image_stmt = $conn->prepare($delete_profile_image_sql);
            $delete_profile_image_stmt->bind_param("i", $profileImageToDeleteEmpId);

            if ($delete_profile_image_stmt->execute()) {
                echo "Profile image deleted successfully.";
                header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
                exit();
            } else {
                echo "Error deleting profile image: " . $conn->error;
            }
            $delete_profile_image_stmt->close();
        }
    } else // Handle profile image change
        if (isset($_FILES['profileImageToEdit'])) {
            $profileImage = $_FILES['profileImageToEdit'];

            // Extract file extension
            $imageExtension = pathinfo($profileImage["name"], PATHINFO_EXTENSION);

            // Generate the new filename based on employee ID
            $newFileName = $employeeId . '_profiles.' . $imageExtension;

            $imagePath = "../Images/ProfilePhotos/" . $newFileName;
            move_uploaded_file($profileImage["tmp_name"], $imagePath);

            // Encode the image before insertion
            $encodedImage = base64_encode(file_get_contents($imagePath));

            // Update $profileImage variable with the new encoded image data
            $profileImage = $encodedImage;
        } else {
            $encodedImage = 'test';
        }

    // Construct SQL query to update profile image
    $sql = "UPDATE employees SET profile_image = ? WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $encodedImage, $employeeId);

    if ($stmt->execute()) {
        echo "Profile image changed successfully.";
        header("Location: " . $_SERVER['PHP_SELF'] . '?employee_id=' . $employeeId);
        exit();
    } else {
        echo "Error updating profile image: " . $conn->error;
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
        window.onload = function() {

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
    <?php require_once("../Menu/HRStaticTopMenu.php") ?>
    <div class="container-fluid px-md-5 mb-5">
        <div class="row">
            <div class="col-lg-8">
                <?php
                if ($employee_details_result->num_rows > 0) {
                    while ($row = $employee_details_result->fetch_assoc()) {
                        $profileImage = $row['profile_image'];
                        $firstName = $row['first_name'];
                        $lastName = $row['last_name'];
                        $employeeId = $row['employee_id'];
                        $address = $row['address'];
                        $phoneNumber = $row['phone_number'];
                        $emergencyContact = $row['emergency_contact_phone_number'];
                        $gender = $row['gender'];
                        $dob = $row['dob'];
                        $startDate = $row['start_date'];
                        $employmentType = $row['employment_type'];
                        $department = $row['department'];
                        $position = $row['position'];
                        $email = $row['email'];
                    }
                ?>
                    <div class="row g-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <?php if (!empty($profileImage)) { ?>
                                    <!-- Profile image -->
                                    <div class="bg-gradient shadow-lg rounded-circle" style="width: 100px; height: 100px; overflow: hidden;">
                                        <img src="data:image/jpeg;base64,<?php echo $profileImage; ?>" alt="Profile Image" class="profile-pic img-fluid rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                <?php } else { ?>
                                    <!-- Initials -->
                                    <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center" style="width: 100px; height: 100px;">
                                        <h3 class="p-0 m-0"><?php echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); ?></h3>
                                    </div>
                                <?php } ?>


                                <div class="d-flex flex-column ms-3">
                                    <h5 class="card-title fw-bold text-start"><?php echo (isset($firstName) && isset($lastName)) ? $firstName . " " . $lastName : "N/A"; ?></h5>
                                    <small class="text-start"><?php echo (isset($position)) ? $position : "N/A" . " - " . ((isset($employeeId)) ? $employeeId : "N/A"); ?></small>
                                </div>
                            </div>
                            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#editProfileModal">Edit Profile <i class="fa-regular fa-pen-to-square"></i></button>
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
                                    <h5 class="fw-bold"><?php echo isset($dob) ? date("j F Y", strtotime($dob)) : "N/A"; ?></h5>
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
                                    <h5 class="fw-bold"><?php echo (isset($address) && $address !== "" ? $address : "N/A"); ?></h5>
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
                                    <small>Emergency Contact</small>
                                    <h5 class="fw-bold"><?php echo isset($emergencyContact) ? $emergencyContact : "N/A"; ?></h5>
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
                                    <h5 class="fw-bold"><?php echo isset($startDate) ? date("j F Y", strtotime($startDate)) : "N/A"; ?></h5>
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
            </div>
            <div class="col-lg-4">
                <div class="card bg-white border-0 rounded shadow-lg mt-4 mt-lg-0">
                    <div class="p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="fw-bold signature-color mb-0">Pay Raise History</p>
                            <i id="payRaiseEditIcon" role="button" class="fa-regular fa-pen-to-square signature-color" data-bs-toggle="modal" data-bs-target="#payRaiseHistoryModal"></i>
                        </div>
                        <div class="px-4 py-2">
                            <div id="chartContainer" style="height: 300px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
                <div class="card bg-white border-0 rounded shadow-lg mt-4">
                    <div class="p-3">
                        <p class="fw-bold signature-color">Leave Allowance</p>
                        <div class="px-4">
                            <p></p>
                        </div>
                    </div>
                </div>
                <div class="card bg-white border-0 rounded shadow-lg mt-4">
                    <div class="p-3">
                        <p class="fw-bold signature-color">Policies</p>
                        <a href="#"> Smoking and Vaping Policy </a>
                    </div>
                </div>

                <!-- ================== Pay History Modal ================== -->
                <div class="modal fade" id="payRaiseHistoryModal" tabindex="-2" aria-labelledby="payRaiseHistoryModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold" id="payRaiseHistoryModalLabel">Pay Raise History</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                                            <input type="hidden" name="wages_id" value="<?php echo $row['wages_id']; ?>">
                                                            <td class="align-middle col-md-6">
                                                                <span class="view-mode"><?php echo date("j F Y", strtotime($row['date'])); ?></span>
                                                                <input type="date" class="form-control edit-mode d-none mx-auto" name="editDate" value="<?php echo date("Y-m-d", strtotime($row['date'])); ?>" style="width: 80%">
                                                            </td>
                                                            <td class="align-middle col-md-3">
                                                                <span class="view-mode">$<?php echo $row['amount']; ?></span>
                                                                <input type="text" class="form-control edit-mode d-none mx-auto" name="editWage" value="<?php echo $row['amount']; ?>">
                                                            </td>
                                                            <td class="align-middle">
                                                                <!-- Edit form -->
                                                                <div class="view-mode">
                                                                    <button type="button" class="btn btn-sm edit-btn p-0"><i class="fa-regular fa-pen-to-square signature-color m-1"></i></button>
                                                                    <div class="btn" id="#openDeleteConfirmation" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-wageamount="<?php echo $row['amount']; ?>" data-wagedate="<?php echo date("j F Y", strtotime($row['date'])); ?>"><i class="fa-solid fa-trash-can text-danger m-1"></i></div>
                                                                </div>
                                                                <div class="edit-mode d-none d-flex justify-content-center">
                                                                    <button type="submit" class="btn btn-sm px-2 btn-success mx-1">
                                                                        <div class="d-flex justify-content-center"><i role="button" class="fa-solid fa-check text-white m-1"></i> Edit </div>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm px-2 btn-danger mx-1 edit-btn">
                                                                        <div class="d-flex justify-content-center"> <i role="button" class="fa-solid fa-xmark  text-white m-1"></i>Cancel </div>
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
                                                    <input type="number" min="0" class="form-control rounded-end " id="newWage" name="newWage">
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
                <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteConfirmationLabel">Confirm Deletion</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Delete form -->
                                Are you sure you want to delete the wage record for <b> <span id="wageDate"></span> </b> with an amount of <b> $<span id="wageAmount"></b></span>?
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
                <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editProfileLabel">Edit Profile</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="px-5">
                                <!-- ================== Edit Profile Form ================== -->

                                <div>
                                    <p class=" signature-color fw-bold mt-5"> Personal Details</p>
                                    <div class="row">
                                        <div class="form-group col-md-12 d-flex align-items-center mb-4">
                                            <div class="col-md-2 d-flex justify-content-center align-items-center">
                                                <?php if (!empty($profileImage)) { ?>
                                                    <!-- Profile image -->
                                                    <div class="bg-gradient shadow-lg rounded-circle" style="width: 100px; height: 100px; overflow: hidden;">
                                                        <img src="data:image/jpeg;base64,<?php echo $profileImage; ?>" alt="Profile Image" class="profile-pic img-fluid rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                                                    </div>
                                                <?php } else { ?>
                                                    <!-- Initials -->
                                                    <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center" style="width: 100px; height: 100px;">
                                                        <h3 class="p-0 m-0"><?php echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); ?></h3>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <div class="col-md-10 ps-3">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <div class="d-flex">
                                                        <form method="POST" class="col-md-12" enctype="multipart/form-data">
                                                            <input type="hidden" name="profileImageAction" value="delete">
                                                            <input type="hidden" name="profileImageToDeleteEmpId" value="<?php echo $employeeId; ?>">
                                                            <label for="profileImageToEdit" class="fw-bold">Profile Image</label>
                                                            <input type="file" id="profileImageToEdit" name="profileImageToEdit" class="form-control">
                                                            <button type="submit" class="btn btn-sm btn-danger mt-2" name="deleteProfileImage">Delete Profile Image</button>
                                                            <button type="submit" class="btn btn-sm btn-dark mt-2" name="changeProfileImage">Change Profile Image</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="employeeIdToEdit" value="<?php echo $employeeId ?>">
                                            <div class="row">
                                                <div class="form-group col-md-6">
                                                    <label for="firstName" class="fw-bold">First Name</label>
                                                    <input type="text" name="firstName" class="form-control" id="firstName" value="<?php echo (isset($firstName) ? $firstName : "") ?>">
                                                </div>
                                                <div class="form-group col-md-6 mt-3 mt-md-0">
                                                    <label for="lastName" class="fw-bold">Last Name</label>
                                                    <input type="text" name="lastName" class="form-control" id="lastName" value="<?php echo (isset($lastName) ? $lastName : "") ?>">
                                                </div>
                                                <div class="form-group col-md-3 mt-3">
                                                    <label for="gender" class="fw-bold">Gender</label>
                                                    <select class="form-select" aria-label="gender" name="gender">
                                                        <option disabled selected hidden> </option>
                                                        <option value="male" <?php if (isset($gender) && $gender == "Male") echo "selected"; ?>>Male</option>
                                                        <option value="female" <?php if (isset($gender) && $gender == "Female") echo "selected"; ?>>Female</option>
                                                    </select>
                                                </div>


                                                <div class="form-group col-md-4 mt-3">
                                                    <label for="dob" class="fw-bold">Date of Birth</label>
                                                    <input type="date" name="dob" class="form-control" id="dob" value="<?php echo (isset($dob) ? $dob : "") ?>">
                                                </div>

                                                <div class="form-group col-md-5 mt-3">
                                                    <label for="visaStatus" class="fw-bold">Visa Status</label>
                                                    <select class="form-select" aria-label="Visa Status" name="visaStatus">
                                                        <option disabled selected hidden> </option>
                                                        <option value="PR">Permanent Resident</option>
                                                        <option value="student">Student</option>
                                                        <option value="WHV">Working Holiday</option>
                                                    </select>
                                                </div>
                                            </div>
                                    </div>

                                    <div class="row">
                                        <p class=" signature-color fw-bold mt-5"> Contacts</p>

                                        <div class="form-group col-md-12">
                                            <label for="address" class="fw-bold">Address</label>
                                            <input type="text" class="form-control" id="address" name="address" value="<?php echo (isset($address) && $address !== "" ? $address : "") ?>" ;>
                                        </div>

                                        <div class=" form-group col-md-12 mt-3">
                                            <label for="email" class="fw-bold">Email</label>
                                            <input type="text" class="form-control" id="email" name="email" value="<?php echo (isset($email) && $email !== "" ? $email : "") ?>">
                                        </div>

                                        <div class="form-group col-md-6 mt-3">
                                            <label for="phoneNumber" class="fw-bold">Phone Number</label>
                                            <input type="text" class="form-control" id="phoneNumber" name="phoneNumber" value="<?php echo (isset($phoneNumber) && $phoneNumber !== "" ? $phoneNumber : "") ?>">
                                        </div>

                                        <div class="form-group col-md-6 mt-3">
                                            <label for="emergencyContact" class="fw-bold">Emergency Contact</label>
                                            <input type="text" class="form-control" id="emergencyContact" name="emergencyContact" value="<?php echo (isset($emergencyContact) && $emergencyContact !== "" ? $emergencyContact : "") ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <p class=" signature-color fw-bold mt-5"> Employment Details</p>
                                        <div class="form-group col-md-3 ">
                                            <label for="employeeId" class="fw-bold">Employee Id</label>
                                            <input type="number" class="form-control" id="employeeId" name="employeeId" value="<?php echo (isset($employeeId) && $employeeId !== "" ? $employeeId : "") ?>">
                                        </div>

                                        <div class="form-group col-md-4">
                                            <label for="startDate" class="fw-bold mt-3 mt-md-0">Date Hired</label>
                                            <input type="date" class="form-control" id="startDate" name="startDate" value="<?php echo (isset($startDate) && $startDate !== "" ? $startDate : "") ?>">
                                        </div>
                                        <div class=" form-group col-md-5">
                                            <label for="employmentStatus" class="fw-bold mt-3 mt-md-0">Employment Type</label>
                                            <select class="form-select" aria-label="Employment Status" name="employmentType">
                                                <option disabled selected hidden> </option>
                                                <option value="permanent" <?php if (isset($employmentType) && $employmentType == "Permanent") echo "selected"; ?>>Permanent</option>
                                                <option value="partTime" <?php if (isset($employmentType) && $employmentType == "Part-Time") echo "selected"; ?>>Part-Time</option>
                                                <option value="casual" <?php if (isset($employmentType) && $employmentType == "Casual") echo "selected"; ?>>Casual</option>
                                            </select>
                                        </div>

                                        <div class="form-group col-md-4 mt-3">
                                            <label for="department" class="fw-bold">Department</label>
                                            <select class="form-select" aria-label="deparment" name="department">
                                                <option disabled selected hidden> </option>
                                                <option value="electrical" <?php if (isset($department) && $department == "Electrical") echo "selected"; ?>>Electrical</option>
                                                <option value="sheetMetal" <?php if (isset($department) && $department == "Sheet Metal") echo "selected"; ?>>Sheet Metal</option>
                                                <option value="office" <?php if (isset($department) && $department == "Office") echo "selected"; ?>>Office</option>
                                            </select>
                                        </div>

                                        <div class="form-group col-md-5 mt-3">
                                            <label for="position" class="fw-bold">Position</label>
                                            <input type="text" class="form-control" id="position" name="position" value="<?php echo (isset($position) && $position !== "" ? $position : "") ?>">
                                        </div>

                                        <div class="form-group col-md-3 mt-3">
                                            <label for="wage" class="fw-bold">Wage</label>
                                            <div class="input-group">
                                                <span class="input-group-text rounded-start" id="wage">$</span>
                                                <input type="number" min="0" class="form-control" aria-describedby="wage">
                                            </div>
                                        </div>

                                        <div class="form-group col-md-12 mt-3">
                                            <label for="policy" class="fw-bold">Upload Policy Files</label>
                                            <div class="input-group">
                                                <input type="file" id="policy" name="policy_files[]" class="form-control" multiple>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-center mt-5 mb-4">
                                        <button class="btn btn-dark" role="submit">Edit Employee</button>
                                    </div>
                                    </form>
                                </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        var myModalEl = document.getElementById('deleteConfirmationModal');
        myModalEl.addEventListener('show.bs.modal', function(event) {
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
    document.addEventListener("DOMContentLoaded", function() {
        // Initialize Bootstrap modal
        var payRaiseHistoryModal = new bootstrap.Modal(document.getElementById('payRaiseHistoryModal'));

        // Pay Raise History edit icon click event
        document.querySelector('#payRaiseEditIcon').addEventListener('click', function() {
            // Show the pay raise history modal
            payRaiseHistoryModal.show();
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Edit button click event handler
        document.querySelectorAll('.edit-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                // Get the parent row
                var row = this.closest('tr');

                // Toggle edit mode
                row.classList.toggle('editing');

                // Toggle visibility of view and edit elements
                row.querySelectorAll('.view-mode, .edit-mode').forEach(function(elem) {
                    elem.classList.toggle('d-none');
                });
            });
        });
    });
</script>

<script>
    $(document).ready(function() {
        // Event listener for modal close event
        $('#payRaiseHistoryModal').on('hidden.bs.modal', function() {
            // For each row in the table
            $('#payRaiseHistoryModal tbody tr').each(function() {
                // Show view mode and hide edit mode
                $(this).find('.view-mode').removeClass('d-none');
                $(this).find('.edit-mode').addClass('d-none');
            });
        });
    });
</script>
</body>

</html>