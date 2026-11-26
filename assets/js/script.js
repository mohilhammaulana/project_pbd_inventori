document.addEventListener("DOMContentLoaded", function () {

    const menuToggle = document.getElementById("menuToggle");

    const sidebar = document.getElementById("sidebar");

    if (!menuToggle || !sidebar) {
        return;
    }

    menuToggle.addEventListener("click", function () {

        if (window.innerWidth <= 768) {

            document.body.classList.toggle("sidebar-open");

        } else {

            document.body.classList.toggle(
                "sidebar-collapsed"
            );

        }

    });

    document.addEventListener("click", function (event) {

        if (window.innerWidth <= 768) {

            const klikSidebar = sidebar.contains(
                event.target
            );

            const klikTombol = menuToggle.contains(
                event.target
            );

            if (!klikSidebar && !klikTombol) {

                document.body.classList.remove(
                    "sidebar-open"
                );

            }

        }

    });

    window.addEventListener("resize", function () {

        if (window.innerWidth > 768) {

            document.body.classList.remove(
                "sidebar-open"
            );

        } else {

            document.body.classList.remove(
                "sidebar-collapsed"
            );

        }

    });

});