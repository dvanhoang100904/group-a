document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const mainContent = document.getElementById("mainContent");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const mobileSidebarToggle = document.getElementById("mobileSidebarToggle");
    const overlay = document.getElementById("overlay");

    // Desktop sidebar toggle
    sidebarToggle.addEventListener("click", function () {
        sidebar.classList.toggle("collapsed");
        mainContent.classList.toggle("expanded");

        // Save state to localStorage
        const isCollapsed = sidebar.classList.contains("collapsed");
        localStorage.setItem("sidebarCollapsed", isCollapsed);
    });

    // Mobile sidebar toggle
    mobileSidebarToggle.addEventListener("click", function () {
        sidebar.classList.toggle("mobile-show");
        overlay.classList.toggle("show");
    });

    // Close mobile sidebar when clicking overlay
    overlay.addEventListener("click", function () {
        sidebar.classList.remove("mobile-show");
        overlay.classList.remove("show");
    });

    // Load saved sidebar state
    const savedState = localStorage.getItem("sidebarCollapsed");
    if (savedState === "true") {
        sidebar.classList.add("collapsed");
        mainContent.classList.add("expanded");
    }

    // Set active nav link
    const navLinks = document.querySelectorAll(".nav-link");
    navLinks.forEach((link) => {
        link.addEventListener("click", function () {
            navLinks.forEach((l) => l.classList.remove("active"));
            this.classList.add("active");

            // Close mobile sidebar after clicking a link
            if (window.innerWidth < 992) {
                sidebar.classList.remove("mobile-show");
                overlay.classList.remove("show");
            }
        });
    });
});
