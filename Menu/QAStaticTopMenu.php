<?php
// Connect to the database
require ("../db_connect.php");

$employee_id = $_SESSION['employee_id'];
$role = $_SESSION['role'];

// Retrieve the username from the session
$username = $_SESSION['username'] ?? '';

// Prepare the SQL query to avoid SQL injection
$user_details_query = "SELECT e.*
FROM employees e
JOIN users u ON e.employee_id = u.employee_id
WHERE u.username = ?
";
$stmt = $conn->prepare($user_details_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_details_result = $stmt->get_result();

if ($user_details_result && $user_details_result->num_rows > 0) {
    $row = $user_details_result->fetch_assoc();

    // Assigning each detail to a variable
    $firstName = $row['first_name'];
    $lastName = $row['last_name'];
    $employeeId = $row['employee_id'];
    $profileImage = $row['profile_image'];
} else { // Set default values if the user is not found
    $firstName = 'N/A';
    $lastName = 'N/A';
    $employeeId = 'N/A';
}

// Free up the memory used by the database query result
$user_details_result->free();

// Close the prepared statement and the database connection
$stmt->close();


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="./style.css">
</head>

<body>
    <div class="col-auto">
        <div class="bg-light p-3">
            <div>
                <div class="dropdown d-flex justify-content-between align-items-center me-2">
                    <div class="d-flex align-items-center">
                        <img src="../Images/FE-logo.png" class="logo" style="width:3rem; height:auto;" />
                        <div class="vr mx-2"></div>
                        <h5 class="signature-color fw-bold mb-0">Quality Assurance</h5>
                    </div>
            
                    <a class="d-flex align-items-center justify-content-center text-decoration-none text-dark" href="#"
                        role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="me-2 fw-bold"><?php echo $firstName . " " . $lastName ?></span>
                        <?php if (!empty($profileImage)) { ?>
                            <img src="data:image/jpeg;base64,<?php echo $profileImage; ?>" alt="Profile Image"
                                class="profile-pic img-fluid rounded-circle me-2"
                                style="width: 40px; height: 40px; object-fit: cover;">
                        <?php } else { ?>
                            <div class="signature-bg-color shadow-lg rounded-circle text-white d-flex justify-content-center align-items-center me-2"
                                style="width: 40px; height: 40px;">
                                <h6 class="p-0 m-0">
                                    <?php echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); ?>
                                </h6>
                            </div>
                        <?php } ?>
                        <i class="fa-solid fa-caret-down fs-6"></i>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                        <li><a class="dropdown-item"
                                href="ProfilePage.php?employee_id=<?php echo $employee_id ?>">Profile</a></li>
                        <?php if ($role === "admin") { ?>
                            <li><a class="dropdown-item" href="http://localhost/FUJI-Directories/index.php?page=manageUsers">Manage User Access</a></li>
                        <?php } ?>
                        <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                                data-bs-target="#logoutModal">Logout</a></li>
                    </ul>
                </div>
                <?php require_once ("../logout.php"); ?>
            </div>
        </div>
    </div>
</body>

</html>