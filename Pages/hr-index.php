<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Connect to the database
require ("./db_connect.php");


// =============================== D E P A R T M E N T  C H A R T ===============================

// Query to get the total number of employees
$total_employees_sql = "SELECT COUNT(*) AS total_employees_count FROM employees";
$total_employees_result = $conn->query($total_employees_sql);

// Query to get the number of employees in the Sheet Metal department
$sheet_metal_num_sql = "SELECT COUNT(*) AS sheet_metal_count FROM employees WHERE department='Sheet Metal'";
$sheet_metal_num_result = $conn->query($sheet_metal_num_sql);

// Query to get the number of employees in the Office department
$office_num_sql = "SELECT COUNT(*) AS office_count FROM employees WHERE department='Office'";
$office_num_result = $conn->query($office_num_sql);

// Query to get the number of employees in the Electrical department
$electrical_num_sql = "SELECT COUNT(*) AS electrical_count FROM employees WHERE department='Electrical'";
$electrical_num_result = $conn->query($electrical_num_sql);

// Initialize variables to store department counts
$sheet_metal_count = 0;
$office_count = 0;
$electrical_count = 0;
$total_employees_count = 0;

// Fetch total number of employees
if ($total_employees_result) {
    $row = $total_employees_result->fetch_assoc();
    $total_employees_count = $row['total_employees_count'];
}

// Fetch number of employees in Sheet Metal department
if ($sheet_metal_num_result) {
    $row = $sheet_metal_num_result->fetch_assoc();
    $sheet_metal_count = $row['sheet_metal_count'];
}

// Fetch number of employees in Office department
if ($office_num_result) {
    $row = $office_num_result->fetch_assoc();
    $office_count = $row['office_count'];
}

// Fetch number of employees in Electrical department
if ($electrical_num_result) {
    $row = $electrical_num_result->fetch_assoc();
    $electrical_count = $row['electrical_count'];
}

// Calculate percentages for each department
$sheet_metal_percentage = ($sheet_metal_count / $total_employees_count) * 100;
$office_percentage = ($office_count / $total_employees_count) * 100;
$electrical_percentage = ($electrical_count / $total_employees_count) * 100;

// Create dataPoints array with percentages for each department
$dataPoints = array(
    array("label" => "Office", "symbol" => "Office", "y" => $office_percentage, "color" => "#5bc0de"),
    array("label" => "Sheet Metal", "symbol" => "Sheet Metal", "y" => $sheet_metal_percentage, "color" => "#3498db"),
    array("label" => "Electrical", "symbol" => "Electrical", "y" => $electrical_percentage, "color" => "#2980b9"),
);

// =============================== E M P L O Y M E N T  T Y P E  C H A R T ===============================

// Query to get the number of permanent employees
$permanent_employees_sql = "SELECT COUNT(*) AS permanent_count FROM employees WHERE employment_type='Full-Time'";
$permanent_employees_result = $conn->query($permanent_employees_sql);

// Query to get the number of part-time employees
$part_time_employees_sql = "SELECT COUNT(*) AS part_time_count FROM employees WHERE employment_type='Part-Time'";
$part_time_employees_result = $conn->query($part_time_employees_sql);

// Query to get the number of casual employees
$casual_employees_sql = "SELECT COUNT(*) AS casual_count FROM employees WHERE employment_type='Casual'";
$casual_employees_result = $conn->query($casual_employees_sql);

// Initialize variables to employment type counts
$permanent_count = 0;
$part_time_count = 0;
$casual_count = 0;

// Fetch number of permanent employees
if ($permanent_employees_result) {
    $row = $permanent_employees_result->fetch_assoc();
    $permanent_count = $row["permanent_count"];
}

// Fetch number of part-time employees
if ($part_time_employees_result) {
    $row = $part_time_employees_result->fetch_assoc();
    $part_time_count = $row["part_time_count"];
}

// Fetch number of casual employees
if ($casual_employees_result) {
    $row = $casual_employees_result->fetch_assoc();
    $casual_count = $row["casual_count"];
}


// Calculate percentages for each employment type
$permanent_percentage = ($permanent_count / $total_employees_count) * 100;
$part_time_percentage = ($part_time_count / $total_employees_count) * 100;
$casual_percentage = ($casual_count / $total_employees_count) * 100;

// Create employmentTypeData array with percentages for each employment type
$employmentTypeData = array(
    array("label" => "Full-Time", "symbol" => "Full-Time", "y" => $permanent_percentage, "color" => "#5bc0de"),
    array("label" => "Part-Time", "symbol" => "Part-Time", "y" => $part_time_percentage, "color" => "#3498db"),
    array("label" => "Casual", "symbol" => "Casual", "y" => $casual_percentage, "color" => "#2980b9"),
);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Human Resources</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
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
    </style>
</head>

<body>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="?menu=home-index">Home</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#043f9d" aria-current="page">HR</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-center rounded-3 bg-white py-2 shadow-sm">
        <ul class="nav nav-underline nav-fill col-6 col-md-8" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active signature-color" id="active-tab" data-bs-toggle="tab" href="#active"
                    role="tab" aria-controls="active" aria-selected="true">HR Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link signature-color" id="link1-tab" data-bs-toggle="tab" href="#link1" role="tab"
                    aria-controls="link1" aria-selected="false">Employees</a>
            </li>
            <li class="nav-item">
                <a class="nav-link signature-color" id="link2-tab" data-bs-toggle="tab" href="#link2" role="tab"
                    aria-controls="link2" aria-selected="false">New Employees</a>
            </li>
            <li class="nav-item">
                <a class="nav-link signature-color" id="link3-tab" data-bs-toggle="tab" href="#link3" role="tab"
                    aria-controls="link3" aria-selected="false">Server Folder</a>
            </li>
        </ul>
    </div>
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
            <div class="row">
                <div class="col-md-4">
                    <div class="bg-white p-2 mt-5 rounded-3">
                        <h4 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-toggle="collapse"
                            data-target="#departmentCollapse" aria-expanded="false" aria-controls="departmentCollapse"
                            style="cursor: pointer;">
                            Departments
                        </h4>
                        <div class="collapse" id="departmentCollapse">
                            <div class="card card-body border-0 pb-0 pt-2">
                                <table class="table">
                                    <tbody class="pe-none">
                                        <tr>
                                            <td>Electrical</td>
                                            <td><?php echo $electrical_count ?></td>
                                        </tr>
                                        <tr>
                                            <td>Sheet Metal</td>
                                            <td><?php echo $sheet_metal_count ?></td>
                                        </tr>
                                        <tr>
                                            <td>Office</td>
                                            <td><?php echo $office_count ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold" style="color:#043f9d">Total Employees</td>
                                            <td class="fw-bold" style="color:#043f9d">
                                                <?php echo $total_employees_count ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="" id="chartContainer" style="height: 370px;"></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-white p-2 mt-5 rounded-3">
                        <h4 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-toggle="collapse"
                            data-target="#employmentTypeCollapse" aria-expanded="false"
                            aria-controls="employmentTypeCollapse" style="cursor: pointer;">
                            Employment Type
                        </h4>
                        <div class="collapse" id="employmentTypeCollapse">
                            <div class="card card-body border-0 pb-0 pt-2">
                                <table class="table">
                                    <tbody class="pe-none">
                                        <tr>
                                            <td>Full-Time</td>
                                            <td><?php echo $permanent_count ?></td>
                                        </tr>
                                        <tr>
                                            <td>Part-Time</td>
                                            <td><?php echo $part_time_count ?></td>
                                        </tr>
                                        <tr>
                                            <td>Casual</td>
                                            <td><?php echo $casual_count ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold" style="color:#043f9d">Total Employees</td>
                                            <td class="fw-bold" style="color:#043f9d">
                                                <?php echo $total_employees_count ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div id="chartContainer2" style="height: 370px;"></div>
                    </div>

                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="link1" role="tabpanel" aria-labelledby="link1-tab">
            <?php require_once ("./List/EmployeeList.php") ?>
        </div>
        <div class="tab-pane fade " id="link2" role="tabpanel" aria-labelledby="link2-tab">
            <div class="col-md-12"> <?php require_once ("./Form/EmployeeDetailsForm.php") ?></div>
        </div>
        <div class="tab-pane fade " id="link3" role="tabpanel" aria-labelledby="link3-tab">
            <div class="col-md-12"> <?php require_once ("directory.php") ?></div>
        </div>
    </div>
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.onload = function () {
            var chart = new CanvasJS.Chart("chartContainer", {
                theme: "light2",
                animationEnabled: true,
                title: {
                    // text: "Total Employees: <?php echo $total_employees_count ?>",
                    fontSize: 18,
                },
                data: [{
                    type: "doughnut",
                    indexLabel: "{symbol} - {y}",
                    yValueFormatString: "#,##0.0\"%\"",
                    showInLegend: true,
                    legendText: "{label} : {y}",
                    dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>,
                    cornerRadius: 10,
                }]
            });

            var chart2 = new CanvasJS.Chart("chartContainer2", {
                theme: "light2",
                animationEnabled: true,
                title: {
                    // text: "Total Employees: <?php echo $total_employees_count ?>",
                    fontSize: 18,
                },
                data: [{
                    type: "pie",
                    indexLabel: "{symbol} - {y}",
                    yValueFormatString: "#,##0.0\"%\"",
                    showInLegend: true,
                    legendText: "{label} : {y}",
                    dataPoints: <?php echo json_encode($employmentTypeData, JSON_NUMERIC_CHECK); ?>,
                }]
            });

            chart.render();
            chart2.render();
        }
    </script>
</body>

</html>