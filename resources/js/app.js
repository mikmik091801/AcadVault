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

// ---------------------------------------------------------------------------
// Show/hide password toggle
// ---------------------------------------------------------------------------
// Added from script so every password field gets one — the auth pages wrap
// theirs in an .input-group, while the profile and user forms use a bare
// .form-control, and neither has to know about this.
const addPasswordToggle = (input) => {
    if (input.dataset.avPasswordToggle) {
        return;
    }

    input.dataset.avPasswordToggle = "1";

    if (!input.closest(".input-group")) {
        const group = document.createElement("div");
        group.className = "input-group";
        input.parentNode.insertBefore(group, input);
        group.appendChild(input);

        // Bootstrap only renders validation feedback when it sits inside the
        // group, so it has to come along with the field.
        const feedback = group.nextElementSibling;
        if (feedback && feedback.classList.contains("invalid-feedback")) {
            group.appendChild(feedback);
        }
    }

    const button = document.createElement("button");
    button.type = "button";
    button.className = "btn btn-outline-secondary av-password-toggle";
    button.setAttribute("aria-label", "Show password");
    button.setAttribute("aria-pressed", "false");
    button.innerHTML = '<i class="bi bi-eye" aria-hidden="true"></i>';

    // Flattening the field's trailing corners is done with a class rather than
    // :last-child, because the feedback element only exists on error.
    input.classList.add("av-has-toggle");
    input.insertAdjacentElement("afterend", button);
};

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('input[type="password"]').forEach(addPasswordToggle);
});

document.addEventListener("click", (event) => {
    const button = event.target.closest(".av-password-toggle");

    if (!button) {
        return;
    }

    const input = button.parentNode.querySelector("input");

    if (!input) {
        return;
    }

    const reveal = input.type === "password";
    input.type = reveal ? "text" : "password";

    button.setAttribute("aria-pressed", reveal ? "true" : "false");
    button.setAttribute("aria-label", reveal ? "Hide password" : "Show password");
    button.querySelector("i").className = reveal ? "bi bi-eye-slash" : "bi bi-eye";

    // Keep the caret where the user left it rather than at the start.
    const caret = input.value.length;
    input.focus();
    input.setSelectionRange(caret, caret);
});
