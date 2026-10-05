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

// ---------------------------------------------------------------------------
// Motion preference
// ---------------------------------------------------------------------------
const prefersReducedMotion = () =>
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;

// ---------------------------------------------------------------------------
// Page-load progress bar
// ---------------------------------------------------------------------------
// Server-rendered pages give no feedback between the click and the next page
// painting; a thin bar at the top fills that gap.
const progressBar = () => document.querySelector(".av-progress");

let progressTimeout;

const startProgress = () => {
    const bar = progressBar();

    if (!bar) {
        return;
    }

    bar.classList.add("is-loading");

    // Safety net for anything that turns out not to navigate.
    clearTimeout(progressTimeout);
    progressTimeout = setTimeout(stopProgress, 10000);
};

const stopProgress = () => {
    progressBar()?.classList.remove("is-loading");
    clearTimeout(progressTimeout);
};

// A link that leaves the current page for another page in the app. File
// downloads, new tabs and in-page anchors never unload the page, so they are
// left alone.
const isPageNavigation = (link, event) => {
    if (
        event.defaultPrevented ||
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey
    ) {
        return false;
    }

    if (
        link.getAttribute("href").startsWith("#") ||
        link.target === "_blank" ||
        link.hasAttribute("download") ||
        link.hasAttribute("data-bs-toggle") ||
        link.dataset.avNoProgress !== undefined
    ) {
        return false;
    }

    const url = new URL(link.href, window.location.href);

    if (url.origin !== window.location.origin || url.pathname.endsWith("/download")) {
        return false;
    }

    // Same page, different #fragment.
    return !(url.pathname === window.location.pathname && url.search === window.location.search && url.hash);
};

document.addEventListener("click", (event) => {
    const link = event.target.closest("a[href]");

    if (link && isPageNavigation(link, event)) {
        startProgress();
    }
});

// ---------------------------------------------------------------------------
// Busy submit buttons
// ---------------------------------------------------------------------------
// Shows a spinner on the button that was pressed and ignores a second submit,
// so a double-click cannot file a grade or request an export twice.
document.addEventListener("submit", (event) => {
    const form = event.target;

    if (event.defaultPrevented) {
        return;
    }

    if (form.dataset.avSubmitting) {
        event.preventDefault();
        return;
    }

    form.dataset.avSubmitting = "1";
    startProgress();

    const button = event.submitter;

    if (button && button.matches(".btn") && !button.querySelector(".spinner-border")) {
        button.classList.add("av-is-busy");
        button.setAttribute("aria-busy", "true");

        const icon = button.querySelector("i.bi");
        const spinner = document.createElement("span");
        spinner.className = "spinner-border spinner-border-sm me-1";
        spinner.setAttribute("aria-hidden", "true");

        if (icon) {
            icon.classList.add("d-none");
            icon.insertAdjacentElement("afterend", spinner);
        } else {
            button.prepend(spinner);
        }
    }
});

// Returning with the back button can restore this page from the browser's
// cache exactly as it was left, busy buttons and all.
const resetBusyState = () => {
    stopProgress();

    document.querySelectorAll("form[data-av-submitting]").forEach((form) => {
        delete form.dataset.avSubmitting;
    });

    document.querySelectorAll(".btn.av-is-busy").forEach((button) => {
        button.classList.remove("av-is-busy");
        button.removeAttribute("aria-busy");
        button.querySelector(".spinner-border")?.remove();
        button.querySelector("i.bi.d-none")?.classList.remove("d-none");
    });
};

window.addEventListener("pageshow", (event) => {
    if (event.persisted) {
        resetBusyState();
    }
});

// ---------------------------------------------------------------------------
// Stat counters
// ---------------------------------------------------------------------------
const countUp = (el) => {
    const target = Number(el.dataset.avCount);

    if (!Number.isFinite(target) || target <= 0 || prefersReducedMotion()) {
        return;
    }

    const duration = Math.min(900, 400 + target * 20);
    const start = performance.now();
    const format = new Intl.NumberFormat();

    const tick = (now) => {
        const progress = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);

        el.textContent = format.format(Math.round(target * eased));

        if (progress < 1) {
            requestAnimationFrame(tick);
        }
    };

    el.textContent = "0";
    requestAnimationFrame(tick);
};

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-av-count]").forEach(countUp);
});

// ---------------------------------------------------------------------------
// Grade form: only offer students enrolled in the chosen course
// ---------------------------------------------------------------------------
const initRosterFilter = (container) => {
    const courseSelect = container.querySelector("#course_id");
    const studentSelect = container.querySelector("#student_id");
    const hint = container.querySelector("[data-av-roster-hint]");

    if (!courseSelect || !studentSelect) {
        return;
    }

    let rosters = {};

    try {
        rosters = JSON.parse(container.dataset.avRosters || "{}");
    } catch {
        return;
    }

    // On edit, the record's own student stays selectable even if they are no
    // longer on the class list, so an old grade can still be corrected.
    const keep = studentSelect.dataset.avKeep;
    const defaultHint = hint?.textContent ?? "";

    const apply = () => {
        const courseId = courseSelect.value;
        const allowed = new Set((rosters[courseId] ?? []).map(String));

        if (keep) {
            allowed.add(keep);
        }

        let visible = 0;

        [...studentSelect.options].forEach((option) => {
            if (option.value === "") {
                return;
            }

            const show = courseId === "" || allowed.has(option.value);
            option.hidden = !show;
            option.disabled = !show;
            visible += show ? 1 : 0;
        });

        if (studentSelect.selectedOptions[0]?.disabled) {
            studentSelect.value = "";
        }

        if (!hint) {
            return;
        }

        if (courseId === "") {
            hint.textContent = defaultHint;
        } else if (visible === 0) {
            hint.textContent = "Nobody is enrolled in this course yet.";
        } else {
            hint.textContent = `${visible} enrolled student${visible === 1 ? "" : "s"} in this course.`;
        }
    };

    courseSelect.addEventListener("change", apply);
    apply();
};

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-av-rosters]").forEach(initRosterFilter);
});

// ---------------------------------------------------------------------------
// Sign-in showcase: "decrypt" the sample record, then seal it as verified
// ---------------------------------------------------------------------------
// Purely illustrative. Without JavaScript, or with reduced motion, the card
// simply shows its readable values and the seal from the start.
const runDecryptDemo = (demo) => {
    // Hidden on narrow screens, where the showcase panel is not shown.
    if (prefersReducedMotion() || demo.offsetParent === null) {
        return;
    }

    const targets = [...demo.querySelectorAll("[data-av-decrypt]")];
    const finals = targets.map((el) => el.textContent.trim());
    const glyphs = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    const randomGlyph = () => glyphs[Math.floor(Math.random() * glyphs.length)];

    const delay = 900; // let the entrance animation land first
    const duration = 1200;
    const start = performance.now() + delay;
    let lastShuffle = 0;

    demo.classList.add("is-decrypting");

    const frame = (now) => {
        const progress = Math.min(1, Math.max(0, (now - start) / duration));

        // Re-roll the unrevealed glyphs about 20 times a second, not every
        // frame, so it reads as churning rather than flickering.
        if (now - lastShuffle > 50 || progress === 1) {
            lastShuffle = now;

            targets.forEach((el, index) => {
                const final = finals[index];
                const revealed = Math.floor(progress * final.length);
                let text = final.slice(0, revealed);

                for (let i = revealed; i < final.length; i++) {
                    text += randomGlyph();
                }

                el.textContent = text;
            });
        }

        if (progress < 1) {
            requestAnimationFrame(frame);
            return;
        }

        demo.classList.remove("is-decrypting");
        demo.classList.add("is-sealed");
    };

    requestAnimationFrame(frame);
};

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-av-demo]").forEach(runDecryptDemo);
});

// ---------------------------------------------------------------------------
// Caps Lock warning on password fields
// ---------------------------------------------------------------------------
// Runs after the show/hide toggle above has wrapped bare fields in an
// .input-group, so the hint lands below the whole group.
const addCapsLockHint = (input) => {
    const hint = document.createElement("div");
    hint.className = "av-capslock-hint";
    hint.hidden = true;
    hint.setAttribute("role", "status");
    hint.innerHTML = '<i class="bi bi-capslock-fill" aria-hidden="true"></i>Caps Lock is on';

    (input.closest(".input-group") ?? input).insertAdjacentElement("afterend", hint);

    const update = (event) => {
        if (typeof event.getModifierState === "function") {
            hint.hidden = !event.getModifierState("CapsLock");
        }
    };

    input.addEventListener("keydown", update);
    input.addEventListener("keyup", update);
    input.addEventListener("blur", () => {
        hint.hidden = true;
    });
};

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('input[type="password"]').forEach(addCapsLockHint);
});
