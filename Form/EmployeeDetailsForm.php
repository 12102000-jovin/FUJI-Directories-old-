<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once("db_connect.php");


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ================ P E R S O N A L   D E T A I L S ================ 
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $profileImage = $_FILES['profileImage'];

    // ================ C O N T A C T S ================ 
    $address = $_POST["address"];
    $email = $_POST["email"];
    $phoneNumber = $_POST["phoneNumber"];
    $emergencyContact = $_POST["emergencyContact"];

    // ================ E M P L O Y M E N T   D E T A I L S ================ 
    $employeeId = $_POST["employeeId"];
    $startDate = $_POST["startDate"];
    $employmentType = $_POST["employmentType"];
    $department = $_POST["department"];
    $position = $_POST["position"];
    $payRate = $_POST["payRate"];

    // Set $imagePath if profile image is uploaded
    if (isset($_FILES["profileImage"]) && $_FILES["profileImage"]["error"] == 0) {
        $profileImage = $_FILES["profileImage"];

        // Get the employee ID
        $employeeId = $_POST["employeeId"];

        // Extract file extension
        $imageExtension = pathinfo($profileImage["name"], PATHINFO_EXTENSION);

        // Generate the new filename based on employee ID
        $newFileName = $employeeId . '_profile.' . $imageExtension;

        $imagePath = "./Images/ProfilePhotos/" . $newFileName;
        move_uploaded_file($profileImage["tmp_name"], $imagePath);

        // Encode the image before insertion
        $encodedImage = base64_encode(file_get_contents($imagePath));
    } else {
        $encodedImage = ''; // Default empty image if no profile image is uploaded
    }


    // Prepare and execute SQL statement to insert data into 'employees' table
    $sql = "INSERT INTO employees (first_name, last_name, gender, dob, address, email, phone_number, emergency_contact_phone_number, employee_id, start_date, employment_type, department, position, pay_rate,  profile_image) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssis", $firstName, $lastName, $gender, $dob, $address, $email, $phoneNumber, $emergencyContact, $employeeId, $startDate, $employmentType, $department, $position, $payRate, $encodedImage);

    // Execute the prepared statement for inserting into the 'employees' table
    if ($stmt->execute()) {
        echo "New record inserted successfully into employees table.";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Prepare and execute SQL statement to insert data into 'wages' table
    $insert_wages_sql = "INSERT INTO wages(employee_id, amount, date)  VALUES (?, ?, ?)";
    $insert_wages_result = $conn->prepare($insert_wages_sql);
    $insert_wages_result->bind_param("ids", $employeeId, $payRate, date('Y-m-d'));

    // Execute the prepared statement for inserting into the 'wages' table
    if ($insert_wages_result->execute()) {
        echo "New wages record inserted successfully.";
    } else {
        echo "Error: " . $insert_wages_result->error;
    }

    // Redirect after successful insertion
    echo '<script>window.location.replace("index.php?menu=hr");</script>';

    // Close prepared statements and database connection
    $stmt->close();
    $insert_wages_result->close();
    $conn->close();
}

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />

<div class="row-cols-1 row-cols-md-3">
    <div class="mt-5 bg-light rounded p-md-5 p-2 col-12 col-md-8 shadow-lg">
        <h3 class="fw-bold text-center">Add New Employee</h3>
        <form method="POST" enctype="multipart/form-data">
            <div>
                <p class=" signature-color fw-bold mt-5"> Personal Details</p>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="firstName" class="fw-bold">First Name</label>
                        <input type="text" name="firstName" class="form-control" id="firstName">
                    </div>
                    <div class="form-group col-md-6 mt-3 mt-md-0">
                        <label for="lastName" class="fw-bold">Last Name</label>
                        <input type="text" name="lastName" class="form-control" id="lastName">
                    </div>

                    <div class="form-group col-md-3 mt-3">
                        <label for="gender" class="fw-bold">Gender</label>
                        <select class="form-select" aria-label="gender" name="gender">
                            <option disabled selected hidden> </option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>

                    <div class="form-group col-md-4 mt-3">
                        <label for="dob" class="fw-bold">Date of Birth</label>
                        <input type="date" name="dob" class="form-control" id="dob">
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

                    <div class="form-group col-md-12 mt-3">
                        <label for="profileImage" class="fw-bold">Upload Profile Image</label>
                        <div class="input-group">
                            <input type="file" id="profileImage" name="profileImage" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <p class=" signature-color fw-bold mt-5"> Contacts</p>

                <div class="form-group col-md-12">
                    <label for="address" class="fw-bold">Address</label>
                    <input type="text" class="form-control" id="address" name="address">
                </div>

                <div class="form-group col-md-12 mt-3">
                    <label for="email" class="fw-bold">Email</label>
                    <input type="text" class="form-control" id="email" name="email">
                </div>

                <div class="form-group col-md-6 mt-3">
                    <label for="phoneNumber" class="fw-bold">Phone Number</label>
                    <input type="text" class="form-control" id="phoneNumber" name="phoneNumber">
                </div>

                <div class="form-group col-md-6 mt-3">
                    <label for="emergencyContact" class="fw-bold">Emergency Contact</label>
                    <input type="text" class="form-control" id="emergencyContact" name="emergencyContact">
                </div>
            </div>

            <div class="row">
                <p class=" signature-color fw-bold mt-5"> Employment Details</p>
                <div class="form-group col-md-3 ">
                    <label for="employeeId" class="fw-bold">Employee Id</label>
                    <input type="number" class="form-control" id="employeeId" name="employeeId">
                </div>

                <div class="form-group col-md-4">
                    <label for="startDate" class="fw-bold mt-3 mt-md-0">Date Hired</label>
                    <input type="date" class="form-control" id="startDate" name="startDate">
                </div>
                <div class="form-group col-md-5">
                    <label for="employmentStatus" class="fw-bold mt-3 mt-md-0">Employment Type</label>
                    <select class="form-select" aria-label="Employment Status" name="employmentType">
                        <option disabled selected hidden> </option>
                        <option value="Permanent">Permanent</option>
                        <option value="Part-Time">Part-Time</option>
                        <option value="Casual">Casual</option>
                    </select>
                </div>

                <div class="form-group col-md-4 mt-3">
                    <label for="department" class="fw-bold">Department</label>
                    <select class="form-select" aria-label="deparment" name="department">
                        <option disabled selected hidden> </option>
                        <option value="Electrical">Electrical</option>
                        <option value="Sheet Metal">Sheet Metal</option>
                        <option value="Office">Office</option>
                    </select>
                </div>

                <div class="form-group col-md-5 mt-3">
                    <label for="position" class="fw-bold">Position</label>
                    <input type="text" class="form-control" id="position" name="position">
                </div>

                <div class="form-group col-md-3 mt-3">
                    <label for="payRate" class="fw-bold">Pay Rate</label>
                    <div class="input-group">
                        <span class="input-group-text rounded-start">$</span>
                        <input type="number" min="0" name="payRate" class="form-control" id="payRate" aria-describedby="payRate">
                    </div>
                </div>

                <div class="form-group col-md-12 mt-3">
                    <label for="policy" class="fw-bold">Upload Policy Files</label>
                    <div class="input-group">
                        <input type="file" id="policy" name="policy_files[]" class="form-control" multiple>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-center mt-5">
                <button class="btn signature-btn">Add Employee</button>
            </div>
        </form>
    </div>
</div>