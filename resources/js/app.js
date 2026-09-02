import * as bootstrap from "bootstrap";

window.bootstrap = bootstrap;

// ---------------------------------------------------------------------------
// Mobile sidebar toggle
// ---------------------------------------------------------------------------
document.addEventListener("click", (event) => {
    if (event.target.closest("[data-av-sidebar-toggle]")) {
        document.body.classList.toggle("av-sidebar-open");
        return;
    }

    if (event.target.closest(".av-sidebar-backdrop")) {
        document.body.classList.remove("av-sidebar-open");
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        document.body.classList.remove("av-sidebar-open");
    }
});

// ---------------------------------------------------------------------------
// Auto-show any toasts rendered on the page
// ---------------------------------------------------------------------------
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".toast").forEach((el) => {
        bootstrap.Toast.getOrCreateInstance(el, { delay: 5000 }).show();
    });
});
