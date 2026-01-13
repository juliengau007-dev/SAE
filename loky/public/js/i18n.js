// i18n.js - Système de traduction multilingue
const I18N = {
    currentLang: "fr",
    translations: {},
    supportedLangs: ["fr", "en"],

    /**
     * Initialise le système i18n : charge la langue sauvegardée ou détecte la langue du navigateur
     */
    async init() {
        // Charger la langue depuis localStorage ou détecter depuis le navigateur
        let savedLang = localStorage.getItem("appLanguage");
        if (!savedLang || !this.supportedLangs.includes(savedLang)) {
            // Détecter la langue du navigateur
            const browserLang = navigator.language?.slice(0, 2) || "fr";
            savedLang = this.supportedLangs.includes(browserLang)
                ? browserLang
                : "fr";
        }
        await this.setLanguage(savedLang);
    },

    /**
     * Charge un fichier de traduction
     */
    async loadTranslations(lang) {
        try {
            const res = await fetch(`i18n/${lang}.json?v=${Date.now()}`);
            if (!res.ok) throw new Error(`Failed to load ${lang}.json`);
            return await res.json();
        } catch (e) {
            console.warn(`i18n: Could not load ${lang}.json`, e);
            return {};
        }
    },

    /**
     * Change la langue courante et met à jour le DOM
     */
    async setLanguage(lang) {
        if (!this.supportedLangs.includes(lang)) {
            console.warn(`i18n: Language ${lang} not supported`);
            return;
        }

        this.translations = await this.loadTranslations(lang);
        this.currentLang = lang;
        localStorage.setItem("appLanguage", lang);

        // Mettre à jour l'attribut lang du document
        document.documentElement.lang = lang;

        // Mettre à jour tous les éléments avec data-i18n
        this.updateDOM();

        // Notifier les listeners externes qu'une nouvelle langue a été définie
        try {
            window.dispatchEvent(
                new CustomEvent("i18n:languageChanged", { detail: { lang } }),
            );
        } catch (e) {}

        // Mettre à jour le sélecteur de langue s'il existe
        const langSelect = document.getElementById("languageSelect");
        if (langSelect) langSelect.value = lang;

        console.log(`i18n: Language set to ${lang}`);
    },

    /**
     * Récupère une traduction par sa clé
     */
    t(key, fallback = null) {
        return this.translations[key] || fallback || key;
    },

    /**
     * Met à jour tous les éléments du DOM avec l'attribut data-i18n
     */
    updateDOM() {
        // Éléments avec data-i18n (contenu texte)
        document.querySelectorAll("[data-i18n]").forEach((el) => {
            const key = el.getAttribute("data-i18n");
            if (key && this.translations[key]) {
                el.textContent = this.translations[key];
            }
        });

        // Éléments avec data-i18n-title (attribut title)
        document.querySelectorAll("[data-i18n-title]").forEach((el) => {
            const key = el.getAttribute("data-i18n-title");
            if (key && this.translations[key]) {
                el.title = this.translations[key];
            }
        });

        // Éléments avec data-i18n-placeholder (attribut placeholder)
        document.querySelectorAll("[data-i18n-placeholder]").forEach((el) => {
            const key = el.getAttribute("data-i18n-placeholder");
            if (key && this.translations[key]) {
                el.placeholder = this.translations[key];
            }
        });

        // Mettre à jour le titre de la page
        if (this.translations["app_title"]) {
            document.title = this.translations["app_title"];
        }
    },
};

// Fonction globale pour changer de langue (utilisée par le select)
function changeLanguage(lang) {
    I18N.setLanguage(lang);
}

// Fonction globale pour récupérer une traduction (utilisée dans le code JS)
function t(key, fallback = null) {
    return I18N.t(key, fallback);
}

// Initialiser i18n au chargement du DOM
document.addEventListener("DOMContentLoaded", () => {
    I18N.init();
});
