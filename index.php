<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Connect to the database
require_once ("db_connect.php");
require_once ("status_check.php");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />

    <style>
        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar::-webkit-scrollbar {
            display: none;
        }

        .sticky-top-menu {
            position: sticky;
            top: 0;
            z-index: 1000;
            /* Ensure the menu stays above other content */
            background-color: white;
            /* Adjust as needed */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            /* Add shadow for visual separation */
        }
    </style>
</head>

<body class="background-color">
    <div class="container-fluid">
        <div class="row">
            <div class="col-auto p-0 sidebar">
                <?php require_once ("Menu/SideMenu.php"); ?>
            </div>
            <div class="col p-0">
                <div class="sticky-top-menu">
                    <?php require_once ("Menu/TopMenu.php") ?>
                </div>
                <div class="p-4">
                    <?php
                    // Check if a menu item is selected, if yes, include the corresponding file
                    if (isset($_GET['menu'])) {
                        switch ($_GET['menu']) {
                            case 'home':
                                require_once ("Pages/home-index.php");
                                break;
                            case 'hr':
                                require_once ("Pages/hr-index.php");
                                break;
                            case 'qa':
                                echo '<script type="text/javascript">';
                                echo 'window.location.href = "Pages/qa-index.php";';      // Redirects to the current page to prevent loading other content
                                echo '</script>';
                                break;
                            default:
                                require_once ("Pages/home-index.php");
                        }
                    } else if (isset($_GET['page'])) {
                        switch ($_GET['page']) {
                            case 'manageUsers':
                                require_once ("Pages/ManageUsers.php");
                                break;
                            case 'manageGroups':
                                require_once ("Pages/ManageGroups.php");
                                break;
                            case 'manageFolders':
                                require_once ("Pages/ManageFolders.php");
                                break;
                            default:
                                // Default to HR index if no or invalid menu is selected
                                require_once ("Pages/home-index.php");
                        }
                    } else {
                        // Default to HR index if no menu is selected
                        require_once ("Pages/home-index.php");
                    }

                    ?>
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                </div>
            </div>
        </div>
    </div>
    <?php require_once ("logout.php"); ?>
</body>

</html>