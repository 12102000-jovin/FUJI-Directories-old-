<?php
$employee_id = $_SESSION['employee_id'];
// echo $employee_id;

// SQL Query to get the folders
$folders_sql = "SELECT DISTINCT f.*
FROM folders f
JOIN groups_folders gf ON f.folder_id = gf.folder_id
JOIN users_groups ug ON gf.group_id = ug.group_id
JOIN users u ON ug.user_id = u.user_id
JOIN employees e ON e.employee_id = u.employee_id
WHERE e.employee_id = $employee_id;
";
$folders_result = $conn->query($folders_sql);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />

<style>
    /* Custom CSS for hover effect */
    .list-unstyled li:hover {
        background-color: #043f9d;
        color: white;
    }

    /* Custom CSS for hover effect */
    #home-icon:hover {
        background-color: #043f9d;
        color: white !important;
    }

    /* Custom CSS for expanding the side menu */
    .expanded-menu {
        width: 12rem !important;
    }

    /* Custom CSS for expanding the side menu */
    #side-menu {
        width: 3.5rem;
        /* Initial width */
        transition: width 0.2s ease;
        /* Add transition for width change */
    }

    /* Custom CSS to ensure text alignment is centered */
    #list-menu {
        text-align: center;
    }

    /* Custom CSS for small font */
    .small-font {
        font-size: 0.8rem;
    }

    /* Custom CSS for expanding the side menu and enlarging the image */
    .expanded-menu img.logo {
        width: 3.5rem;
    }

    .close-button {
        display: none;
    }

    /* Show close button when menu is expanded */
    .expanded-menu .close-button {
        display: block;
    }

    @media (max-width: 576px) {

        /* Adjust the width of the expanded menu to 8rem on small screens */
        .expanded-menu {
            width: 8rem !important;
        }
    }
</style>

<div class="d-flex flex-column flex-shrink-0 shadow-lg min-vh-100 bg-light" style="width:4rem" id="side-menu">
    <div class="d-flex justify-content-between align-items-center mt-1" id="menu-toggle">
        <img src="Images/FE-logo.png" class="m-2" style="width:3rem" />
        <!-- Close button added dynamically when menu is expanded -->
        <i class="btn fa-solid fa-xmark me-3 close-button" id="close-menu"></i>
    </div>
    <ul class="list-unstyled mt-3 text-center" id="list-menu">
        <a href="?menu=home" class="text-decoration-none">
            <li class=" mx-2 py-2 p-1 rounded fw-bold text-dark" id="home-icon"><i class=" fa-solid fa-house"></i></li>
        </a>
        <?php
        // PHP loop to generate folder links
        if ($folders_result->num_rows > 0) {
            while ($row = $folders_result->fetch_assoc()) {
                $initials = '';
                $words = explode(' ', $row['folder_name']);
                foreach ($words as $word) {
                    $initials .= strtoupper(substr($word, 0, 1)); // Extract the first letter of each word
                }
                echo '<a href="?menu=' . strtolower($initials) . '" class="text-decoration-none text-dark">';
                echo '<li class="mx-2 py-2 p-1 rounded fw-bold larger-font">' . htmlspecialchars($row['folder_name']) . '</li>';
                echo '</a>';
            }
        } else {
            echo '<li class="mx-2 py-2 p-1 rounded fw-bold larger-font">No folders found</li>';
        }
        ?>
        <li class="mx-2 py-2 p-1 rounded fw-bold larger-font">Etc.</li>
    </ul>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const sideMenu = document.getElementById('side-menu');
        const menuToggle = document.getElementById('menu-toggle');
        const closeButton = document.getElementById('close-menu');
        const listMenu = document.getElementById('list-menu');

        // Function to update menu items based on menu state
        function updateMenuItems(isExpanded) {
            const menuItems = sideMenu.querySelectorAll('li');
            menuItems.forEach(function (item) {
                if (isExpanded) {
                    // Expand menu: show full folder names
                    switch (item.textContent.trim()) {
                        case 'HR':
                            item.innerHTML = 'Human Resources';
                            break;
                        case 'QA':
                            item.innerHTML = 'Quality Assurances';
                            break;
                        case 'R&D':
                            item.innerHTML = 'Research & Development';
                            break;
                        case 'M':
                            item.innerHTML = 'Marketing';
                            break;
                        default:
                            if (item.querySelector('.fa-house')) {
                                item.innerHTML = '<i class="fa-solid fa-house"></i> Home';
                            } else {
                                // Handle other folder names if needed
                                const folderName = item.textContent.trim();
                                const initials = folderName.split(' ').map(part => part.charAt(0)).join('');
                                item.innerHTML = initials;
                            }
                            break;
                    }
                } else {
                    // Collapse menu: show initials or icons
                    if (item.querySelector('.fa-house')) {
                        item.innerHTML = '<i class="fa-solid fa-house"></i>';
                    } else {
                        // Display initials or icons for other folders
                        const folderName = item.textContent.trim();
                        const initials = folderName.split(' ').map(part => part.charAt(0)).join('');
                        item.innerHTML = initials;
                    }
                }
            });
        }

        // Toggle menu expansion on click
        menuToggle.addEventListener('click', function () {
            const isExpanded = sideMenu.classList.contains('expanded-menu');
            sideMenu.classList.toggle('expanded-menu');
            closeButton.style.display = isExpanded ? 'none' : 'block'; // Show close button when menu is expanded

            // Update menu items based on the current state (expanded or collapsed)
            updateMenuItems(!isExpanded);

            // Toggle text-center class for list-menu based on expanded state
            listMenu.classList.toggle('text-center', !isExpanded);
        });

        // Initial call to set up menu items based on initial state
        updateMenuItems(false); // Pass false to display initials initially
    });
</script>


<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>