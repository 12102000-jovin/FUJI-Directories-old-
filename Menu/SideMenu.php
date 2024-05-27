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
        <a href="?menu=home" class="text-decoration-none text-dark">
            <li class=" mx-2 py-2 p-1 rounded fw-bold"><i class=" fa-solid fa-house"></i></li>
        </a>
        <a href="?menu=hr" class="text-decoration-none text-dark">
            <li class=" mx-2 py-2 p-1 rounded fw-bold larger-font" id="hr-menu-item">HR</li>

            <div class="collapse" id="hrCollapse">
                <li class="ms-3 me-2 p-1 rounded fw-bold larger-font small-font">HR Sub-Item 1</li>
                <li class="ms-3 me-2 p-1 rounded fw-bold larger-font small-font">HR Sub-Item 2</li>
            </div>
        </a>
        <a href="?menu=qa" class="text-decoration-none text-dark">
            <li class="mx-2 py-2 p-1 rounded fw-bold larger-font">QA</li>
        </a>
        <li class="mx-2 py-2 p-1 rounded fw-bold larger-font">Etc.</li>
    </ul>
</div>

<script>
    // Javascript to toggle the expansion of the side menu when clicking the image
    document.addEventListener("DOMContentLoaded", function() {
        const sideMenu = document.getElementById('side-menu');
        const menuToggle = document.getElementById('menu-toggle');
        const hrMenuItem = document.getElementById('hr-menu-item');
        const hrCollapse = document.getElementById('hrCollapse');
        const closeButton = document.getElementById('close-menu');
        const listMenu = document.getElementById('list-menu')

        menuToggle.addEventListener('click', function() {
            const isExpanded = sideMenu.classList.contains('expanded-menu');
            sideMenu.classList.toggle('expanded-menu');
            closeButton.style.display = isExpanded ? 'none' : 'block'; // Show close button when menu is expanded

            // Remove or add "text-center" class based on menu expansion
            if (sideMenu.classList.contains('expanded-menu')) {
                listMenu.classList.remove('text-center');
                // Change house icon to "Home" text when expanding
                const houseIcon = document.querySelector('.fa-house');
                if (houseIcon) {
                    houseIcon.parentElement.innerHTML = 'Home';
                }
            } else {
                listMenu.classList.add('text-center');
                // Change "Home" text back to house icon when collapsing
                const homeText = document.querySelector('#list-menu li:first-child');
                if (homeText) {
                    homeText.innerHTML = '<i class="fa-solid fa-house"></i>';
                }
            }

            // Change the text and icon when expanding
            const menuItems = sideMenu.querySelectorAll('li');
            menuItems.forEach(function(item) {
                if (sideMenu.classList.contains('expanded-menu')) {
                    switch (item.textContent.trim()) {
                        case 'HR':
                            item.innerHTML = 'Human Resource <i class="fa-solid fa-caret-down"></i>';
                            break;
                        case 'QA':
                            item.textContent = 'Quality Assurance';
                            break;
                    }
                } else {
                    // Reset text and icon when collapsing
                    switch (item.textContent.trim()) {
                        case 'Human Resource':
                            item.innerHTML = 'HR';
                            break;
                        case 'Quality Assurance':
                            item.textContent = 'QA';
                            break;
                    }
                }
            });
            // Close dropdown if menu is collapsed
            if (isExpanded && !sideMenu.classList.contains('expanded-menu')) {
                hrCollapse.classList.remove('show');
            }
        });

        // Prevent dropdown when menu is not expanded
        hrMenuItem.addEventListener('click', function() {
            if (sideMenu.classList.contains('expanded-menu')) {
                hrCollapse.classList.toggle('show');
            }
        });
    });
</script>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>