<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once ("db_connect.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ================ P E R S O N A L   D E T A I L S ================ 
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $profileImage = $_FILES['profileImage'];
    $visaStatus = $_POST['visaStatus'];
    $visaExpiryDate = $_POST['visaExpiryDate'];

    // ================ C O N T A C T S ================ 
    $address = $_POST["address"];
    $email = $_POST["email"];
    $phoneNumber = $_POST["phoneNumber"];
    $emergencyContact = $_POST["emergencyContact"];
    $emergencyContactName = $_POST["emergencyContactName"];
    $emergencyContactRelationship = $_POST["emergencyContactRelationship"];

    // ================ E M P L O Y M E N T   D E T A I L S ================ 
    $employeeId = $_POST["employeeId"];
    $startDate = $_POST["startDate"];
    $employmentType = $_POST["employmentType"];
    $department = $_POST["department"];
    $position = $_POST["position"];
    $payRate = $_POST["payRate"];


    // ================ B A N K I N G,  S U P E R,  &  T A X  D E T A I L S ================
    $bankBuildingSociety = $_POST["bankBuildingSociety"];
    $bsb = $_POST["bsb"];
    $accountNumber = $_POST["accountNumber"];
    $superannuationFundName = $_POST["superannuationFundName"];
    $uniqueSuperannuatioIdentifier = $_POST["uniqueSuperannuationIdentifier"];
    $superannuationMemberNumber = $_POST["superannuationMemberNumber"];
    $taxFileNumber = $_POST["taxFileNumber"];
    $higherEducationLoanProgramme = $_POST["higherEducationLoanProgramme"];
    $financialSupplementDebt = $_POST["financialSupplementDebt"];

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
    $sql = "INSERT INTO employees (first_name, last_name, gender, dob, visa, visa_expiry_date , address, email, phone_number, emergency_contact_phone_number, emergency_contact_name, emergency_contact_relationship, employee_id, start_date, employment_type, department, position, pay_rate, bank_building_society, bsb, account_number, superannuation_fund_name, unique_superannuation_identifier, superannuation_member_number, tax_file_number, higher_education_loan_programme, financial_supplement_debt, profile_image) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssssssisssssssiis", $firstName, $lastName, $gender, $dob, $visaStatus, $visaExpiryDate, $address, $email, $phoneNumber, $emergencyContact, $emergencyContactName, $emergencyContactRelationship, $employeeId, $startDate, $employmentType, $department, $position, $payRate, $bankBuildingSociety, $bsb, $accountNumber, $superannuationFundName, $uniqueSuperannuatioIdentifier, $superannuationMemberNumber, $taxFileNumber, $higherEducationLoanProgramme, $financialSupplementDebt, $encodedImage);

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

<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />

    <style>
        /* Style for checked state */
        .btn-check:checked+.btn-custom {
            background-color: #043f9d !important;
            border-color: #043f9d !important;
            color: white !important;
            /* Text color when selected */
        }

        /* Optional: Adjust hover state if needed */
        .btn-custom:hover {
            background-color: #032b6b;
            border-color: #032b6b;
            color: white;
        }

        /* Optional: Adjust focus state if needed
        .btn-check:focus+.btn-custom {
            box-shadow: 0 0 0 0.25rem rgba(4, 63, 157, 0.5);
        } */
    </style>
</head>

<div class="row-cols-1 row-cols-md-3">
    <div class="mt-5 bg-light rounded p-md-5 p-2 col-12 col-md-8 shadow-lg">
        <h3 class="fw-bold text-center">Add New Employee</h3>
        <form method="POST" enctype="multipart/form-data">

            <!-- ================ P E R S O N A L   D E T A I L S ================  -->
            <div class="row">
                <p class="signature-color fw-bold mt-5">Personal Details</p>
                <div class="form-group col-md-6">
                    <label for="firstName" class="fw-bold"><small>First Name</small></label>
                    <input type="text" name="firstName" class="form-control" id="firstName">
                </div>
                <div class="form-group col-md-6 mt-3 mt-md-0">
                    <label for="lastName" class="fw-bold"><small>Last Name</small></label>
                    <input type="text" name="lastName" class="form-control" id="lastName">
                </div>

                <div class="form-group col-md-6 mt-3">
                    <label for="gender" class="fw-bold"><small>Gender</small></label>
                    <select class="form-select" aria-label="gender" name="gender">
                        <option disabled selected hidden> </option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>

                <div class="form-group col-md-6 mt-3">
                    <label for="dob" class="fw-bold"><small>Date of Birth</small></label>
                    <input type="date" name="dob" class="form-control" id="dob">
                </div>

                <div class="form-group col-md-6 mt-3">
                    <label for="visaStatus" class="fw-bold"><small>Visa Status</small></label>
                    <select class="form-select" aria-label="Visa Status" name="visaStatus">
                        <option disabled selected hidden> </option>
                        <option value="Permanent Resident">Permanent Resident</option>
                        <option value="Student">Student</option>
                        <option value="Working Holiday">Working Holiday</option>
                    </select>
                </div>

                <div class="form-group col-md-6 mt-3">
                    <label for="visaExpiryDate" class="fw-bold"><small>Visa Expiry Date</small></label>
                    <input type="date" name="visaExpiryDate" class="form-control" id="visaExpiryDate">
                </div>

                <div class="form-group col-md-12 mt-3">
                    <label for="profileImage" class="fw-bold"><small>Upload Profile Image</small></label>
                    <div class="input-group">
                        <input type="file" id="profileImage" name="profileImage" class="form-control">
                    </div>
                </div>
            </div>

            <!-- ================ C O N T A C T S ================  -->
            <div class="row">
                <p class="signature-color fw-bold mt-5">Contacts</p>
                <div class="form-group col-md-12">
                    <label for="address" class="fw-bold"><small>Address</small></label>
                    <input type="text" class="form-control" id="address" name="address">
                </div>

                <div class="form-group col-md-12 mt-3">
                    <label for="email" class="fw-bold"><small>Email</small></label>
                    <input type="text" class="form-control" id="email" name="email">
                </div>

                <div class="form-group col-md-6 mt-3">
                    <label for="phoneNumber" class="fw-bold"><small>Mobile</small></label>
                    <input type="text" class="form-control" id="phoneNumber" name="phoneNumber">
                </div>

                <div class="form-group col-md-6 mt-3">
                    <label for="vehicleNumberPlate" class="fw-bold"><small>Vehicle Number Plate</small></label>
                    <input type="text" class="form-control" id="vehicleNumberPlate" name="vehicleNumberPlate">
                </div>
            </div>

            <!-- ================ E M E R G E N C Y  C O N T A C T S ================  -->
            <div class="row">
                <p class="signature-color fw-bold mt-5">Emergency Contact</p>

                <div class="form-group col-md-6">
                    <label for="emergencyContactName" class="fw-bold"><small>Emergency Contact Name</small></label>
                    <input type="text" class="form-control" id="emergencyContactName" name="emergencyContactName">
                </div>

                <div class="form-group col-md-6">
                    <label for="emergencyContact" class="fw-bold"><small>Emergency Contact Mobile</small></label>
                    <input type="text" class="form-control" id="emergencyContact" name="emergencyContact">
                </div>

                <div class="form-group col-md-6 mt-3">
                    <label for="emergencyContactRelationship" class="fw-bold"><small>Relationship</small></label>
                    <input type="text" class="form-control" id="emergencyContactRelationship"
                        name="emergencyContactRelationship">
                </div>
            </div>

            <!-- ================ E M P L O Y M E N T   D E T A I L S ================  -->
            <div class="row">
                <p class="signature-color fw-bold mt-5">Employment Details</p>
                <div class="form-group col-md-3">
                    <label for="employeeId" class="fw-bold"><small>Employee Id</small></label>
                    <input type="number" class="form-control" id="employeeId" name="employeeId">
                </div>

                <div class="form-group col-md-4">
                    <label for="startDate" class="fw-bold mt-3 mt-md-0"><small>Date Hired</small></label>
                    <input type="date" class="form-control" id="startDate" name="startDate">
                </div>

                <div class="form-group col-md-5">
                    <label for="employmentType" class="fw-bold mt-3 mt-md-0"><small>Employment Type</small></label>
                    <select class="form-select" aria-label="Employment Type" name="employmentType">
                        <option disabled selected hidden> </option>
                        <option value="Full-Time">Full-Time</option>
                        <option value="Part-Time">Part-Time</option>
                        <option value="Casual">Casual</option>
                    </select>
                </div>

                <div class="form-group col-md-4 mt-3">
                    <label for="department" class="fw-bold"><small>Department</small></label>
                    <select class="form-select" aria-label="Department" name="department">
                        <option disabled selected hidden> </option>
                        <option value="Electrical">Electrical</option>
                        <option value="Sheet Metal">Sheet Metal</option>
                        <option value="Office">Office</option>
                    </select>
                </div>

                <div class="form-group col-md-5 mt-3">
                    <label for="position" class="fw-bold"><small>Position</small></label>
                    <input type="text" class="form-control" id="position" name="position">
                </div>

                <div class="form-group col-md-3 mt-3">
                    <label for="payRate" class="fw-bold"><small>Pay Rate</small></label>
                    <div class="input-group">
                        <span class="input-group-text rounded-start">$</span>
                        <input type="number" min="0" class="form-control" id="payRate" name="payRate"
                            aria-describedby="payRate">
                    </div>
                </div>

                <div class="form-group col-md-12 mt-3">
                    <label for="policy" class="fw-bold"><small>Upload Policy Files</small></label>
                    <div class="input-group">
                        <input type="file" id="policy" name="policy_files[]" class="form-control" multiple>
                    </div>
                </div>
            </div>

            <!-- ================ B A N K,  S U P E R,  &  T A X ================  -->
            <div class="row">
                <p class="signature-color fw-bold mt-5">Banking, Super and Tax Details</p>
                <div class="form-group col-md-12">
                    <label for="bankBuildingSociety" class="fw-bold"><small>Bank/Building Society</small></label>
                    <input type="text" name="bankBuildingSociety" class="form-control" id="bankBuildingSociety">
                </div>

                <div class="form-group col-md-4 mt-3">
                    <label for="bsb" class="fw-bold"><small>BSB</small></label>
                    <input type="number" name="bsb" class="form-control" id="bsb">
                </div>

                <div class="form-group col-md-8 mt-3">
                    <label for="accountNumber" class="fw-bold"><small>Account Number</small></label>
                    <input type="number" name="accountNumber" class="form-control" id="accountNumber">
                </div>

                <div class="form-group col-md-12 mt-3">
                    <label for="superannuationFundName" class="fw-bold"><small>Superannuation Fund Name</small></label>
                    <input type="text" name="superannuationFundName" class="form-control" id="superannuationFundName">
                </div>

                <div class="form-group col-md-6 mt-3">
                    <label for="uniqueSuperannuationIdentifier" class="fw-bold"><small>Unique Superannuation
                            Identifier</small></label>
                    <input type="text" name="uniqueSuperannuationIdentifier" class="form-control"
                        id="uniqueSuperannuationIdentifier">
                </div>

                <div class="form-group col-md-6 mt-3">
                    <label for="superannuationMemberNumber" class="fw-bold"><small>Superannuation Member
                            Number</small></label>
                    <input type="text" name="superannuationMemberNumber" class="form-control"
                        id="superannuationMemberNumber">
                </div>

                <div class="form-group col-md-6 mt-3">
                    <label for="taxFileNumber" class="fw-bold"><small>Tax File Number</small></label>
                    <input type="number" name="taxFileNumber" class="form-control" id="taxFileNumber">
                </div>

                <div class="form-group col-md-12 mt-3">
                    <div class="d-flex flex-column">
                        <label for="higherEducationLoanProgramme" class="fw-bold"><small>Higher Education Loan
                                Programme?</small></label>
                        <div class="btn-group col-3 col-md-2" role="group">
                            <input type="radio" class="btn-check" name="higherEducationLoanProgramme"
                                id="higherEducationLoanProgrammeYes" value="1" autocomplete="off">
                            <label class="btn btn-sm btn-custom" for="higherEducationLoanProgrammeYes"
                                style="color:#043f9d; border: 1px solid #043f9d">Yes</label>

                            <input type="radio" class="btn-check" name="higherEducationLoanProgramme"
                                id="higherEducationLoanProgrammeNo" value="0" autocomplete="off">
                            <label class="btn btn-sm btn-custom" for="higherEducationLoanProgrammeNo"
                                style="color:#043f9d; border: 1px solid #043f9d">No</label>
                        </div>
                    </div>
                </div>

                <div class="form-group col-md-12 mt-3">
                    <div class="d-flex flex-column">
                        <label for="financialSupplementDebt" class="fw-bold"><small>Financial Supplement
                                Debt?</small></label>
                        <div class="btn-group col-3 col-md-2" role="group">
                            <input type="radio" class="btn-check" name="financialSupplementDebt"
                                id="financialSupplementDebtYes" value="1" autocomplete="off" />
                            <label class="btn btn-sm btn-custom" for="financialSupplementDebtYes"
                                style="color:#043f9d; border: 1px solid #043f9d"> Yes</label>

                            <input type="radio" class="btn-check" name="financialSupplementDebt"
                                id="financialSupplementDebtNo" value="0" autocomplete="off" />
                            <label class="btn btn-sm btn-custom" for="financialSupplementDebtNo"
                                style="color:#043f9d; border: 1px solid #043f9d">No</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-center mt-5">
                <button class="btn btn-dark">Add Employee</button>
            </div>
        </form>
    </div>

</div>