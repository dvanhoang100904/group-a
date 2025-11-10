document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const mainContent = document.getElementById("mainContent");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const mobileSidebarToggle = document.getElementById("mobileSidebarToggle");
    const overlay = document.getElementById("overlay");

    // Kiểm tra các element tồn tại để tránh lỗi
    if (
        !sidebar ||
        !mainContent ||
        !sidebarToggle ||
        !mobileSidebarToggle ||
        !overlay
    ) {
        console.error("One or more required elements not found");
        return;
    }

    // Desktop sidebar toggle
    sidebarToggle.addEventListener("click", function () {
        toggleSidebar();
    });

    // Mobile sidebar toggle
    mobileSidebarToggle.addEventListener("click", function () {
        sidebar.classList.add("mobile-show");
        overlay.classList.add("show");
        document.body.style.overflow = "hidden"; // Ngăn scroll khi sidebar mở
    });

    // Close mobile sidebar when clicking overlay
    overlay.addEventListener("click", function () {
        closeMobileSidebar();
    });

    // Close sidebar when pressing Escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            closeMobileSidebar();
        }
    });

    // Close mobile sidebar when window is resized to desktop
    window.addEventListener("resize", function () {
        if (window.innerWidth >= 992) {
            closeMobileSidebar();
        }
    });

    // Load saved sidebar state
    loadSidebarState();

    // Set active nav link based on current URL
    setActiveNavLink();

    function toggleSidebar() {
        sidebar.classList.toggle("collapsed");
        mainContent.classList.toggle("expanded");

        // Save state to localStorage
        const isCollapsed = sidebar.classList.contains("collapsed");
        localStorage.setItem("sidebarCollapsed", isCollapsed);

        // Dispatch custom event for other components
        window.dispatchEvent(
            new CustomEvent("sidebarToggle", {
                detail: { isCollapsed },
            }),
        );
    }

    function closeMobileSidebar() {
        sidebar.classList.remove("mobile-show");
        overlay.classList.remove("show");
        document.body.style.overflow = "";
    }

    function loadSidebarState() {
        const savedState = localStorage.getItem("sidebarCollapsed");
        if (savedState === "true") {
            sidebar.classList.add("collapsed");
            mainContent.classList.add("expanded");
        }
    }

    function setActiveNavLink() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll(".nav-link");

        navLinks.forEach((link) => {
            const href = link.getAttribute("href");
            if (href && currentPath.startsWith(href) && href !== "/") {
                link.classList.add("active");
            }
        });
    }

    // alert message
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach((alert) => {
        setTimeout(() => {
            if (typeof bootstrap !== "undefined") {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            } else {
                alert.style.display = "none";
            }
        }, 3000);
    });
});
