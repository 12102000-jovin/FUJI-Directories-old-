<?php

require_once("./db_connect.php");

$employee_list_sql = "SELECT * FROM employees";
$employee_list_result = $conn->query($employee_list_sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .card {
            height: 100%;
        }
    </style>
</head>

<body>
    <div class="container-fluid p-0 mt-5">
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php
            // Check if there are any employees
            if ($employee_list_result->num_rows > 0) {
                // Loop through each row of employee data
                while ($row = $employee_list_result->fetch_assoc()) {
                    $profileImage = $row['profile_image'];
                    $firstName = $row['first_name'];
                    $lastName = $row['last_name'];
                    $employeeId = $row['employee_id'];
            ?>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="card">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center position-relative">
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

                                <!-- Employee details -->
                                <h5 class="card-title fw-bold mt-2"><?php echo $firstName . " " . $lastName; ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">Employee ID: <?php echo $employeeId; ?></h6>
                                <a href="../FUJI-Directories/Pages/ProfilePage.php?employee_id=<?php echo $employeeId ?>" target="_blank">
                                    <button class="btn btn-dark"><small>Profile <i class="fa-solid fa-up-right-from-square fa-sm"></i></small></button>
                                </a>
                            </div>
                        </div>
                    </div>
            <?php
                }
            } else {
                // If there are no employees, display a message
                echo "<p>No employees found.</p>";
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>