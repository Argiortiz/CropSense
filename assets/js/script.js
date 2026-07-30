// Show / Hide Password
const togglePassword = () => {
    const password = document.getElementById("password");
    const icon = document.getElementById("togglePassword");
    const button = document.getElementById("passwordToggle");

    if (!password || !icon) {
        return;
    }

    if (password.type === "password") {
        password.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
        if (button) {
            button.setAttribute("aria-label", "Hide password");
            button.setAttribute("aria-pressed", "true");
        }
    } else {
        password.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
        if (button) {
            button.setAttribute("aria-label", "Show password");
            button.setAttribute("aria-pressed", "false");
        }
    }
};

const setupLoginForm = () => {
    const form = document.getElementById("loginForm");
    const username = document.getElementById("username");
    const remember = document.getElementById("remember");
    const passwordToggle = document.getElementById("passwordToggle");
    const rememberedUsername = localStorage.getItem("cropsenseRememberedUsername");

    if (passwordToggle) {
        passwordToggle.addEventListener("click", togglePassword);
    }

    if (username && remember && rememberedUsername) {
        username.value = rememberedUsername;
        remember.checked = true;
    }

    if (!form || !username || !remember) {
        return;
    }

    form.addEventListener("submit", () => {
        if (remember.checked) {
            localStorage.setItem("cropsenseRememberedUsername", username.value.trim());
        } else {
            localStorage.removeItem("cropsenseRememberedUsername");
        }
    });
};

const translations = {
    en: {
        brandKicker: "AgriTech Intelligence",
        brandTagline: "Smart Agriculture, Better Tomorrow",
        heroTitle: "Smart Crop Recommendation and Monitoring System",
        heroText: "Turn live soil readings into clear crop decisions with a secure, farmer-friendly monitoring dashboard.",
        fieldStatus: "Live Field Status",
        optimal: "Optimal",
        soilMoisture: "Soil Moisture",
        nitrogen: "Nitrogen",
        high: "High",
        cropMatch: "Crop Match",
        featureSoilTitle: "Live Soil Monitoring",
        featureSoilText: "Real-time data from connected farm sensors",
        featureAiTitle: "AI Crop Recommendation",
        featureAiText: "Smarter crop choices for better yield planning",
        featureCloudTitle: "Cloud Dashboard",
        featureCloudText: "Secure access to farm insights anywhere",
        welcomePill: "Welcome to CropSense",
        welcomeTitle: "WELCOME MABUHAY!",
        welcomeText: "Sign in to unlock your farm intelligence dashboard",
        username: "Username or Email",
        usernamePlaceholder: "Enter your username or email",
        password: "Password",
        passwordPlaceholder: "Enter your password",
        remember: "Remember Me",
        forgot: "Forgot Password?",
        signIn: "Sign In",
        security: "Protected access for your farm data",
        secure: "Secure",
        reliable: "Reliable",
        smartAgri: "Smart Agriculture",
        copyright: "© 2026 CropSense. All rights reserved.",
        version: "Version 2.0"
    },
    tl: {
        brandKicker: "AgriTech Intelligence",
        brandTagline: "Matalinong Agrikultura, Mas Magandang Bukas",
        heroTitle: "Matalinong Rekomendasyon at Monitoring ng Pananim",
        heroText: "Gawing malinaw na desisyon sa pananim ang live soil readings gamit ang ligtas at madaling dashboard.",
        fieldStatus: "Live Status ng Bukid",
        optimal: "Maayos",
        soilMoisture: "Halumigmig ng Lupa",
        nitrogen: "Nitrogen",
        high: "Mataas",
        cropMatch: "Tugma sa Pananim",
        featureSoilTitle: "Live Soil Monitoring",
        featureSoilText: "Real-time data mula sa connected farm sensors",
        featureAiTitle: "AI Rekomendasyon ng Pananim",
        featureAiText: "Mas matalinong pagpili ng pananim para sa ani",
        featureCloudTitle: "Cloud Dashboard",
        featureCloudText: "Ligtas na access sa farm insights kahit saan",
        welcomePill: "Maligayang pagdating sa CropSense",
        welcomeTitle: "WELCOME MABUHAY!",
        welcomeText: "Mag-sign in para buksan ang iyong farm intelligence dashboard",
        username: "Username o Email",
        usernamePlaceholder: "Ilagay ang iyong username o email",
        password: "Password",
        passwordPlaceholder: "Ilagay ang iyong password",
        remember: "Tandaan Ako",
        forgot: "Nakalimutan ang Password?",
        signIn: "Mag Sign In",
        security: "Protektado ang access sa iyong farm data",
        secure: "Secure",
        reliable: "Reliable",
        smartAgri: "Matalinong Agrikultura",
        copyright: "© 2026 CropSense. All rights reserved.",
        version: "Version 2.0"
    }
};

const applyLanguage = (language) => {
    const dictionary = translations[language] || translations.en;

    document.documentElement.lang = language;

    document.querySelectorAll("[data-i18n]").forEach((element) => {
        const key = element.dataset.i18n;
        if (dictionary[key]) {
            element.textContent = dictionary[key];
        }
    });

    document.querySelectorAll("[data-i18n-placeholder]").forEach((element) => {
        const key = element.dataset.i18nPlaceholder;
        if (dictionary[key]) {
            element.placeholder = dictionary[key];
        }
    });

    localStorage.setItem("cropsenseLanguage", language);
};

document.addEventListener("DOMContentLoaded", () => {
    setupLoginForm();

    const languageSelect = document.getElementById("languageSelect");
    if (!languageSelect) {
        return;
    }

    const savedLanguage = localStorage.getItem("cropsenseLanguage") || "en";
    languageSelect.value = savedLanguage;
    applyLanguage(savedLanguage);

    languageSelect.addEventListener("change", (event) => {
        applyLanguage(event.target.value);
    });
});


