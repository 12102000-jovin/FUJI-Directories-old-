<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Connect to the database
require("./db_connect.php");

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
    array("label" => "Office", "symbol" => "Office", "y" => $office_percentage, "color" => "#ffbb00"), // Yellow
    array("label" => "Sheet Metal", "symbol" => "Sheet Metal", "y" => $sheet_metal_percentage, "color" => "#ff4500"), // Orange
    array("label" => "Electrical", "symbol" => "Electrical", "y" => $electrical_percentage, "color" => "#008080"), // Teal
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
    <div class="d-flex justify-content-center rounded-3 bg-white py-2 shadow-lg">
        <ul class="nav nav-underline nav-fill col-6 col-md-8" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active signature-color" id="active-tab" data-bs-toggle="tab" href="#active" role="tab" aria-controls="active" aria-selected="true">HR Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link signature-color" id="link1-tab" data-bs-toggle="tab" href="#link1" role="tab" aria-controls="link1" aria-selected="false">Employees</a>
            </li>
            <li class="nav-item">
                <a class="nav-link signature-color" id="link2-tab" data-bs-toggle="tab" href="#link2" role="tab" aria-controls="link2" aria-selected="false">New Employees</a>
            </li>
            <li class="nav-item">
                <a class="nav-link signature-color" id="link3-tab" data-bs-toggle="tab" href="#link3" role="tab" aria-controls="link3" aria-selected="false">Server Folder</a>
            </li>
        </ul>
    </div>
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
            <div class="mt-5 col-md-8" id="chartContainer" style="height: 370px;"></div>
        </div>
        <div class="tab-pane fade" id="link1" role="tabpanel" aria-labelledby="link1-tab">
            <?php require_once("./List/EmployeeList.php") ?>
        </div>
        <div class="tab-pane fade " id="link2" role="tabpanel" aria-labelledby="link2-tab">
            <div class="col-md-12"> <?php require_once("./Form/EmployeeDetailsForm.php") ?></div>
        </div>
        <div class="tab-pane fade " id="link3" role="tabpanel" aria-labelledby="link3-tab">
            <div class="col-md-12"> <?php require_once("directory.php") ?></div>
        </div>
    </div>
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.onload = function() {
            var chart = new CanvasJS.Chart("chartContainer", {
                theme: "light2",
                animationEnabled: true,
                title: {
                    text: "Total Employees: <?php echo $total_employees_count ?>",
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
            // Add error handling
            chart.render().then(function(res) {
                console.log("Chart rendered successfully");
            }).catch(function(err) {
                console.error("Chart rendering error: ", err);
            });
        }
    </script>
</body>

</html>