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

// =============================== S E C T I O N  C H A R T  ( E L E C T R I C A L )===============================

// Query to get the total number of electrical employees
$total_electrical_employees_sql = "SELECT COUNT(*) AS total_electrical_employees_count FROM employees WHERE department = 'Electrical'";
$total_electrical_employees_result = $conn->query($total_electrical_employees_sql);

// Query to get the number of panel section employees
$panel_section_employees_sql = "SELECT COUNT(*) AS panel_section_count FROM employees WHERE department = 'Electrical' AND section='Panel'";
$panel_section_employees_result = $conn->query($panel_section_employees_sql);

// Query to get the number of roof employees
$roof_section_employees_sql = "SELECT COUNT(*) AS roof_section_count FROM employees WHERE department = 'Electrical' AND section='Roof'";
$roof_section_employees_result = $conn->query($roof_section_employees_sql);

// Initialize variables to electrical department section counts
$total_electrical_employees_count = 0;
$panel_section_count = 0;
$roof_section_count = 0;

// Fetch total number of electrical employees
if ($total_electrical_employees_result) {
    $row = $total_electrical_employees_result->fetch_assoc();
    $total_electrical_employees_count = $row['total_electrical_employees_count'];
}

// Fetch number of panel employees
if ($panel_section_employees_result) {
    $row = $panel_section_employees_result->fetch_assoc();
    $panel_section_count = $row["panel_section_count"];
}

// Fetch number of roof employees
if ($roof_section_employees_result) {
    $row = $roof_section_employees_result->fetch_assoc();
    $roof_section_count = $row["roof_section_count"];
}

// Calculate percentages for each section
$panel_percentage = ($panel_section_count / $total_electrical_employees_count) * 100;
$roof_percentage = ($roof_section_count / $total_electrical_employees_count) * 100;

// echo $total_electrical_employees_count;

// Create Electrical Section Data array with percentages for each section
$electricalSectionData = array(
    array("label" => "Panel", "symbol" => "Panel", "y" => $panel_percentage, "color" => "#5bc0de"),
    array("label" => "Roof", "symbol" => "Roof", "y" => $roof_percentage, "color" => "#2980b9"),
);

// =============================== S E C T I O N  C H A R T  ( S H E E T  M E T A L ) ===============================

// Query to get the total of sheet metal employees
$total_sheet_metal_employees_sql = "SELECT COUNT(*) total_sheet_metal_employees_count FROM employees WHERE department = 'Sheet Metal'";
$total_sheet_metal_employees_result = $conn->query($total_sheet_metal_employees_sql);

// Query to get the number of programmer section employees
$programmer_section_employees_sql = "SELECT COUNT(*) AS programmer_section_count FROM employees WHERE department = 'Sheet Metal' AND section='Programmer'";
$programmer_section_employees_result = $conn->query($programmer_section_employees_sql);

// Query to get the total of painter section employees
$painter_section_employees_sql = "SELECT COUNT(*) AS painter_section_count FROM employees WHERE department = 'Sheet Metal' AND section='Painter'";
$painter_section_employees_result = $conn->query($painter_section_employees_sql);

// Initialise variables to sheet metal department section counts
$total_sheet_metal_employees_count = 0;
$programmer_section_count = 0;
$painter_section_count = 0;

// Fetch total number of sheet metal employees
if ($total_sheet_metal_employees_result) {
    $row = $total_sheet_metal_employees_result->fetch_assoc();
    $total_sheet_metal_employees_count = $row["total_sheet_metal_employees_count"];
}

// Fetch number of programmer employees
if ($programmer_section_employees_result) {
    $row = $programmer_section_employees_result->fetch_assoc();
    $programmer_section_count = $row["programmer_section_count"];
}

// Fetch number of painter employees
if ($painter_section_employees_result) {
    $row = $painter_section_employees_result->fetch_assoc();
    $painter_section_count = $row["painter_section_count"];
}

// Calculate percentages for each section
$programmer_percentage = ($programmer_section_count / $total_sheet_metal_employees_count) * 100;
$painter_percentage = ($painter_section_count / $total_sheet_metal_employees_count) * 100;

// Create Sheet Metal Section Data array with percentages for each section
$sheetMetalSectionData = array(
    array("label" => "Programmer", "symbol" => "Programmer", "y" => $programmer_percentage, "color" => "#5bc0de"),
    array("label" => "Painter", "symbol" => "Painter", "y" => $painter_percentage, "color" => "#2980b9"),
);

// =============================== S E C T I O N  C H A R T  ( O F F I C E ) ===============================

// Query to get the total of office employees
$total_office_employees_sql = "SELECT COUNT(*) total_office_employees_count FROM employees WHERE department = 'Office'";
$total_office_employees_result = $conn->query($total_office_employees_sql);

// Query to get the number of engineer section employees
$engineer_section_employees_sql = "SELECT COUNT(*) AS engineer_section_count FROM employees WHERE department = 'Office' AND section='Engineer'";
$engineer_section_employees_result = $conn->query($engineer_section_employees_sql);

// Query to get the number of accountant section employees
$accountant_section_employees_sql = "SELECT COUNT(*) AS accountant_section_count FROM employees WHERE department = 'Office' AND section='Accountant'";
$accountant_section_employees_result = $conn->query($accountant_section_employees_sql);

// Initialise variables to office department section
$total_office_employees_count = 0;
$engineer_section_count = 0;
$accountant_section_count = 0;

// Fetch total number of office employees
if ($total_office_employees_result) {
    $row = $total_office_employees_result->fetch_assoc();
    $total_office_employees_count = $row["total_office_employees_count"];
}

// Fetch number of engineer employees
if ($engineer_section_employees_result) {
    $row = $engineer_section_employees_result->fetch_assoc();
    $engineer_section_count = $row["engineer_section_count"];
}

// Fetch number of accountant employees
if ($accountant_section_employees_result) {
    $row = $accountant_section_employees_result->fetch_assoc();
    $accountant_section_count = $row["accountant_section_count"];
}

// Calculate percentages for each section
$engineer_percentage = ($engineer_section_count / $total_office_employees_count) * 100;
$accountant_percentage = ($accountant_section_count / $total_office_employees_count) * 100;

// Create Office section data array with percentages for each section
$officeSectionData = array(
    array("label" => "Engineer", "symbol" => "Engineer", "y" => $engineer_percentage, "color" => "#5bc0de"),
    array("label" => "Accountant", "symbol" => "Accountant", "y" => $accountant_percentage, "color" => "#2980b9"),
)
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
                <div class="col-lg-4">
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
                <div class="col-lg-4">
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
            <hr class="mt-5"/>
            <h3 class="fw-bold">Section</h3>
            <div class="row">
                <div class="col-lg-4">
                    <div class="bg-white p-2 mt-3 rounded-3">
                        <h4 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-toggle="collapse"
                            data-target="#electricalDepartmentCollapse" aria-expanded="false"
                            aria-controls="electricalDepartmentCollapse" style="cursor: pointer;">
                            Electrical Department
                        </h4>
                        <div class="collapse" id="electricalDepartmentCollapse">
                            <div class="card card-body border-0 pb-0 pt-2">
                                <table class="table">
                                    <tbody class="pe-none">
                                        <tr>
                                            <td>Panel</td>
                                            <td><?php echo $panel_section_count ?></td>
                                        </tr>
                                        <tr>
                                            <td>Roof</td>
                                            <td><?php echo $roof_section_count ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold" style="color:#043f9d">Total Employees</td>
                                            <td class="fw-bold" style="color:#043f9d">
                                                <?php echo $total_electrical_employees_count ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div id="chartContainer3" style="height: 370px;"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="bg-white p-2 mt-3 rounded-3">
                        <h4 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-toggle="collapse"
                            data-target="#sheetMetalDepartmentCollapse" aria-expanded="false"
                            aria-controls="sheetMetalDepartmentCollapse" style="cursor: pointer;">
                            Sheet Metal Department
                        </h4>
                        <div class="collapse" id="sheetMetalDepartmentCollapse">
                            <div class="card card-body border-0 pb-0 pt-2">
                                <table class="table">
                                    <tbody class="pe-none">
                                        <tr>
                                            <td>Painter</td>
                                            <td><?php echo $painter_section_count ?> </td>
                                        </tr>
                                        <tr>
                                            <td>Programmer</td>
                                            <td><?php echo $programmer_section_count ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold" style="color:#043f9d">Total Employees</td>
                                            <td class="fw-bold" style="color:#043f9d">
                                                <?php echo $total_sheet_metal_employees_count ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div id="chartContainer4" style="height: 370px;"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="bg-white p-2 mt-3 rounded-3">
                        <h4 class="p-2 pb-0 fw-bold mb-0 signature-color dropdown-toggle" data-toggle="collapse"
                            data-target="#officeDepartmentCollapse" aria-expanded="false"
                            aria-controls="officeDepartmentCollapse" style="cursor: pointer;">
                            Office Department
                        </h4>
                        <div class="collapse" id="officeDepartmentCollapse">
                            <div class="card card-body border-0 pb-0 pt-2">
                                <table class="table">
                                    <tbody class="pe-none">
                                        <tr>
                                            <td>Engineer</td>
                                            <td><?php echo $engineer_section_count ?></td>
                                        </tr>
                                        <tr>
                                            <td>Accountant</td>
                                            <td><?php echo $accountant_section_count ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold" style="color:#043f9d">Total Employees</td>
                                            <td class="fw-bold" style="color:#043f9d">
                                                <?php echo $total_office_employees_count ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div id="chartContainer5" style="height: 370px;"></div>
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

            var chart3 = new CanvasJS.Chart("chartContainer3", {
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
                    dataPoints: <?php echo json_encode($electricalSectionData, JSON_NUMERIC_CHECK); ?>,
                }]
            });

            var chart4 = new CanvasJS.Chart("chartContainer4", {
                theme: "light2",
                animationEnabled: true,
                data: [{
                    type: "pie",
                    indexLabel: "{symbol} - {y}",
                    yValueFormatString: "#,##0.0\"%\"",
                    showInLegend: true,
                    legendText: "{label} : {y}",
                    dataPoints: <?php echo json_encode($sheetMetalSectionData, JSON_NUMERIC_CHECK); ?>
                }]
            })

            var chart5 = new CanvasJS.Chart("chartContainer5", {
                theme: "light2",
                animationEnabled: true,
                data: [{
                    type: "pie",
                    indexLabel: "{symbol} = {y}",
                    yValueFormatString: "#,##0.0\"%\"",
                    showInLegend: true,
                    legendText: "{label} : {y}",
                    dataPoints: <?php echo json_encode($officeSectionData, JSON_NUMERIC_CHECK); ?>
                }]
            })

            chart.render();
            chart2.render();
            chart3.render();
            chart4.render();
            chart5.render();
        }
    </script>
</body>

</html>