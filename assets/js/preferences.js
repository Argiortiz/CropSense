(function () {
    const ready = (callback) => {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", callback, { once: true });
            return;
        }

        callback();
    };

    ready(() => {
        const root = document.documentElement;
        const body = document.body;
        const themeToggle = document.getElementById("themeToggle");
        const fontSizeRange = document.getElementById("fontSizeRange");
        const fontSizeValue = document.getElementById("fontSizeValue");
        const sidebarToggles = document.querySelectorAll("[data-sidebar-toggle]");
        const sidebar = document.querySelector(".dashboard-sidebar");
        const sidebarBrand = document.querySelector("[data-sidebar-brand]");
        const sidebarBackdrop = document.querySelector("[data-sidebar-dismiss]");
        const sidebarNavLinks = document.querySelectorAll(".sidebar-nav a");
        const passwordToggles = document.querySelectorAll("[data-password-toggle]");
        const dataFilterLinks = document.querySelectorAll(".data-filter-more a");
        const confirmButtons = document.querySelectorAll("[data-confirm-message]");
        const logoImages = document.querySelectorAll("[data-logo-image]");

        const readPreference = (key, fallback) => {
            try {
                return localStorage.getItem(key) || fallback;
            } catch (error) {
                return fallback;
            }
        };

        const savePreference = (key, value) => {
            try {
                localStorage.setItem(key, value);
            } catch (error) {
                // Storage can be blocked in private modes; the UI should still work.
            }
        };

        const applyTheme = (theme) => {
            body.classList.toggle("theme-dark", theme === "dark");
            savePreference("cropsenseTheme", theme);
        };

        const applyFontSize = (fontSize) => {
            const normalizedSize = Math.min(20, Math.max(14, Number(fontSize) || 16));
            root.style.setProperty("--dashboard-font-size", `${normalizedSize}px`);
            savePreference("cropsenseFontSize", String(normalizedSize));

            if (fontSizeRange) {
                fontSizeRange.value = String(normalizedSize);
            }

            if (fontSizeValue) {
                fontSizeValue.textContent = `${normalizedSize}px`;
            }
        };

        const mobileSidebarQuery = window.matchMedia("(max-width: 860px)");
        let desktopSidebarState = readPreference("cropsenseSidebar", "open") === "closed" ? "closed" : "open";

        const applySidebar = (state, persist = true) => {
            const isCollapsed = state === "closed";
            const isMobile = mobileSidebarQuery.matches;
            body.classList.toggle("sidebar-collapsed", isCollapsed);
            body.classList.add("sidebar-ready");
            body.classList.remove("sidebar-initializing");

            if (persist && !isMobile) {
                desktopSidebarState = isCollapsed ? "closed" : "open";
                savePreference("cropsenseSidebar", desktopSidebarState);
            }

            sidebarToggles.forEach((button) => {
                button.setAttribute("aria-expanded", String(!isCollapsed));
                button.setAttribute("aria-label", isCollapsed ? "Open sidebar" : "Close sidebar");
                button.setAttribute("title", isCollapsed ? "Open sidebar" : "Close sidebar");
            });

            if (sidebar) {
                sidebar.setAttribute("aria-hidden", String(isMobile && isCollapsed));
                sidebar.inert = isMobile && isCollapsed;
            }

            if (sidebarBackdrop) {
                sidebarBackdrop.setAttribute("aria-hidden", String(!isMobile || isCollapsed));
            }

            if (sidebarBrand) {
                sidebarBrand.setAttribute("aria-label", isCollapsed ? "Open sidebar" : "CropSense Live Monitoring");
                sidebarBrand.setAttribute("title", isCollapsed ? "Open sidebar" : "CropSense Live Monitoring");
            }
        };

        const savedTheme = readPreference("cropsenseTheme", "light");
        const savedFontSize = readPreference("cropsenseFontSize", "16");

        applyTheme(savedTheme);
        applyFontSize(savedFontSize);
        applySidebar(mobileSidebarQuery.matches ? "closed" : desktopSidebarState, false);

        logoImages.forEach((image) => {
            const markMissing = () => image.classList.add("is-missing");

            if (image.complete && image.naturalWidth === 0) {
                markMissing();
            }

            image.addEventListener("error", markMissing);
        });

        if (themeToggle) {
            themeToggle.checked = savedTheme === "dark";
            themeToggle.addEventListener("change", () => {
                applyTheme(themeToggle.checked ? "dark" : "light");
            });
        }

        if (fontSizeRange) {
            fontSizeRange.addEventListener("input", () => {
                applyFontSize(fontSizeRange.value);
            });
        }

        document.addEventListener("click", (event) => {
            const toggleButton = event.target.closest("[data-sidebar-toggle]");

            if (toggleButton) {
                event.preventDefault();
                applySidebar(
                    body.classList.contains("sidebar-collapsed") ? "open" : "closed",
                    !mobileSidebarQuery.matches
                );
                return;
            }

            const brand = event.target.closest("[data-sidebar-brand]");

            if (brand && body.classList.contains("sidebar-collapsed")) {
                event.preventDefault();
                applySidebar("open", !mobileSidebarQuery.matches);
                return;
            }

            if (event.target.closest("[data-sidebar-dismiss]")) {
                applySidebar("closed", false);
            }
        });

        sidebarNavLinks.forEach((link) => {
            link.addEventListener("click", () => {
                if (mobileSidebarQuery.matches) {
                    applySidebar("closed", false);
                }
            });
        });

        document.addEventListener("keydown", (event) => {
            if (
                event.key === "Escape" &&
                mobileSidebarQuery.matches &&
                !body.classList.contains("sidebar-collapsed")
            ) {
                applySidebar("closed", false);
            }
        });

        const syncSidebarToViewport = (event) => {
            applySidebar(event.matches ? "closed" : desktopSidebarState, false);
        };

        if (typeof mobileSidebarQuery.addEventListener === "function") {
            mobileSidebarQuery.addEventListener("change", syncSidebarToViewport);
        } else if (typeof mobileSidebarQuery.addListener === "function") {
            mobileSidebarQuery.addListener(syncSidebarToViewport);
        }

        passwordToggles.forEach((button) => {
            button.addEventListener("click", () => {
                const input = document.getElementById(button.dataset.passwordToggle);
                const icon = button.querySelector("i");

                if (!input || !icon) {
                    return;
                }

                const isHidden = input.type === "password";
                input.type = isHidden ? "text" : "password";
                button.setAttribute("aria-label", isHidden ? "Hide temporary password" : "Show temporary password");
                button.setAttribute("aria-pressed", String(isHidden));
                icon.classList.toggle("bi-eye", !isHidden);
                icon.classList.toggle("bi-eye-slash", isHidden);
            });
        });

        dataFilterLinks.forEach((link) => {
            link.addEventListener("click", () => {
                const menu = link.closest("details");

                if (menu) {
                    menu.open = false;
                }
            });
        });

        confirmButtons.forEach((button) => {
            button.addEventListener("click", (event) => {
                if (button.disabled) {
                    return;
                }

                if (!window.confirm(button.dataset.confirmMessage)) {
                    event.preventDefault();
                }
            });
        });
    });
})();
