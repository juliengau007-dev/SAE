// ui.js
// Fonctions liées à l'interface et aux paramètres
function loadMenuSettings() {
    try {
        const pmrVal = localStorage.getItem("pmrEnabled");
        const payantVal = localStorage.getItem("payantEnabled");
        const heightVal = localStorage.getItem("heightMax");
        const elecVal = localStorage.getItem("electricOnly");
        if (document.getElementById("pmrToggle") && pmrVal !== null) {
            document.getElementById("pmrToggle").checked = pmrVal === "1";
        }
        if (document.getElementById("payantToggle") && payantVal !== null) {
            document.getElementById("payantToggle").checked = payantVal === "1";
        }
        if (document.getElementById("heightMax") && heightVal !== null) {
            document.getElementById("heightMax").value = heightVal;
        }
        if (document.getElementById("electricToggle") && elecVal !== null) {
            document.getElementById("electricToggle").checked = elecVal === "1";
        }
        pmr = document.getElementById("pmrToggle")?.checked || false;
        payant = document.getElementById("payantToggle")?.checked || false;
    } catch (e) {
        console.warn("loadMenuSettings", e);
    }
}

function saveParamSettings() {
    try {
        const pmrVal = document.getElementById("pmrToggle")?.checked
            ? "1"
            : "0";
        const payantVal = document.getElementById("payantToggle")?.checked
            ? "1"
            : "0";
        const heightVal = document.getElementById("heightMax")?.value || "";
        const elecVal = document.getElementById("electricToggle")?.checked
            ? "1"
            : "0";
        localStorage.setItem("pmrEnabled", pmrVal);
        localStorage.setItem("payantEnabled", payantVal);
        localStorage.setItem("heightMax", heightVal);
        localStorage.setItem("electricOnly", elecVal);
        pmr = pmrVal === "1";
        payant = payantVal === "1";
    } catch (e) {
        console.warn("saveParamSettings", e);
    }
}

function togglePMR() {
    pmr = document.getElementById("pmrToggle").checked;
}

function togglePayant() {
    payant = document.getElementById("payantToggle").checked;
}

function showAvailability(text, cls = "") {
    try {
        const container = document.querySelector(
            ".leaflet-routing-container .routing-actions",
        );
        if (!container) return;
        let el = container.querySelector(".availability-info");
        if (!el) {
            el = document.createElement("div");
            el.className = "availability-info";
            container.appendChild(el);
        }
        el.textContent = text;
        if (cls === "ok") el.style.background = "#d4ffd7";
        else if (cls === "warning") el.style.background = "#fff4cc";
        else if (cls === "bad") el.style.background = "#ffd6d6";
        else el.style.background = "rgba(255,255,255,0.9)";
    } catch (e) {
        console.warn("showAvailability", e);
    }
}

// Alias pour rétrocompatibilité
function showMetzAvailability(text, cls = "") {
    showAvailability(text, cls);
}

function saveCitySettings() {
    try {
        const enabled = document.getElementById("availabilityToggle")?.checked
            ? "1"
            : "0";
        const url = document.getElementById("cityApiUrl")?.value || "";
        localStorage.setItem("availabilityCheckEnabled", enabled);
        localStorage.setItem("cityApiUrl", url);
    } catch (e) {
        console.warn("saveCitySettings", e);
    }
}

// Alias pour rétrocompatibilité
function saveMetzSettings() {
    saveCitySettings();
}

function loadCitySettings() {
    try {
        const enabled = localStorage.getItem("availabilityCheckEnabled");
        const url = localStorage.getItem("cityApiUrl");
        if (document.getElementById("availabilityToggle") && enabled !== null) {
            document.getElementById("availabilityToggle").checked =
                enabled === "1";
        }
        if (document.getElementById("cityApiUrl") && url !== null) {
            document.getElementById("cityApiUrl").value = url;
        }
        // Mettre à jour le label avec le nom de la ville
        updateCityLabel();
    } catch (e) {
        console.warn("loadCitySettings", e);
    }
}

// Alias pour rétrocompatibilité
function loadMetzSettings() {
    loadCitySettings();
}

function updateCityLabel() {
    try {
        const cityConfig = getCurrentCityConfig();
        const label = document.getElementById("availabilityLabel");
        if (label) {
            label.textContent = `Vérifier disponibilité (${cityConfig.name})`;
        }
        const cityNameEl = document.getElementById("currentCityName");
        if (cityNameEl) {
            cityNameEl.textContent = cityConfig.name;
        }
    } catch (e) {}
}

function fermerMenu() {
    document.getElementById("menuParam").style.display = "none";
    try {
        onParamChange();
    } catch (e) {}
    try {
        if (_currentTargetFid == null) {
            const mg = document.querySelector(".menuGuider");
            if (mg) mg.style.display = "flex";
        }
    } catch (e) {}
}

function onParamChange() {
    (async () => {
        try {
            saveParamSettings();
            await loadParkings();
            const nearest = await findNearestParking();
            if (
                _currentTargetFid != null && nearest && nearest.geometry &&
                nearest.geometry.coordinates
            ) {
                const fid = nearest.properties?.fid || nearest.properties?.id ||
                    (nearest.id && !isNaN(parseInt(nearest.id))
                        ? parseInt(nearest.id)
                        : null);
                goToParking(
                    nearest.geometry.coordinates[1],
                    nearest.geometry.coordinates[0],
                    fid,
                );
            }
        } catch (e) {
            console.warn("onParamChange", e);
        }
    })();
}

// startPolling / stopPolling: fonctions de polling génériques
function startPolling(fid) {
    stopPolling();
    if (!fid) return;
    _currentTargetFid = fid;
    const enabled = document.getElementById("availabilityToggle")?.checked;
    if (!enabled) {
        _currentTargetFid = null;
        return;
    }

    const cityConfig = getCurrentCityConfig();

    (async () => {
        const info = await fetchParkingAvailability(fid);
        if (info) {
            if (info.value != null) {
                showAvailability("Places: " + info.value, "ok");
            } else showAvailability("Info disponible", "warning");
            if (!isNaN(Number(info.value)) && Number(info.value) <= 0) {
                attemptAutoSwitchIfNeeded();
            }
        } else showAvailability("Pas de donnée", "bad");
    })();

    _pollInterval = setInterval(async () => {
        const info = await fetchParkingAvailability(fid);
        if (info) {
            if (info.value != null) {
                showAvailability("Places: " + info.value, "ok");
            } else showAvailability("Info disponible", "warning");
            if (!isNaN(Number(info.value)) && Number(info.value) <= 0) {
                attemptAutoSwitchIfNeeded();
            }
        } else showAvailability("Pas de donnée", "bad");
    }, _pollMs);
}

// Alias pour rétrocompatibilité
function startMetzPolling(fid) {
    startPolling(fid);
}

function stopPolling(clearTarget = false) {
    try {
        if (_pollInterval) {
            clearInterval(_pollInterval);
            _pollInterval = null;
        }
        const container = document.querySelector(
            ".leaflet-routing-container .routing-actions",
        );
        if (container) {
            const el = container.querySelector(".availability-info");
            if (el) el.remove();
        }
        if (clearTarget) _currentTargetFid = null;
    } catch (e) {
        console.warn("stopPolling", e);
    }
}

// Alias pour rétrocompatibilité
function stopMetzPolling(clearTarget = false) {
    stopPolling(clearTarget);
}

// Attacher écouteurs d'UI au DOMContentLoaded
document.addEventListener("DOMContentLoaded", () => {
    try {
        const paramBtn = document.getElementById("parametre");
        if (paramBtn) {
            paramBtn.addEventListener("click", () => {
                try {
                    const mg = document.querySelector(".menuGuider");
                    if (mg) mg.style.display = "none";
                } catch (e) {}
                document.getElementById("menuParam").style.display = "flex";
                loadMenuSettings();
            });
        }

        const elPmr = document.getElementById("pmrToggle");
        const elHeight = document.getElementById("heightMax");
        const elElec = document.getElementById("electricToggle");
        const elPay = document.getElementById("payantToggle");
        if (elPmr) {
            elPmr.addEventListener("change", () => {
                togglePMR();
                onParamChange();
            });
        }
        if (elPay) {
            elPay.addEventListener("change", () => {
                togglePayant();
                onParamChange();
            });
        }
        if (elHeight) {
            elHeight.addEventListener("input", () => {
                onParamChange();
            });
        }
        if (elHeight) {
            elHeight.addEventListener("change", () => {
                onParamChange();
            });
        }
        if (elElec) {
            elElec.addEventListener("change", () => {
                onParamChange();
            });
        }

        const closeBtn = document.querySelector("#menuParam .close-btn");
        if (closeBtn) closeBtn.addEventListener("click", fermerMenu);

        // ========================================
        // AUTH & VEHICLE MANAGEMENT
        // ========================================
        const btnConnexion = document.getElementById("btnConnexion");
        const btnInscription = document.getElementById("btnInscription");
        const authModal = document.getElementById("authModal");
        const authClose = document.getElementById("authClose");
        const authForm = document.getElementById("authForm");
        const authTitle = document.getElementById("authTitle");
        const authSubmit = document.getElementById("authSubmit");
        const authSwitch = document.getElementById("authSwitch");
        const authError = document.getElementById("authError");
        const registerFields = document.getElementById("registerFields");
        const authButtonsContainer = document.getElementById(
            "authButtonsContainer",
        );
        const userLoggedContainer = document.getElementById(
            "userLoggedContainer",
        );
        const loggedUserName = document.getElementById("loggedUserName");
        const btnLogout = document.getElementById("btnLogout");
        const btnMyVehicle = document.getElementById("btnMyVehicle");

        // Vehicle modal elements
        const vehicleModal = document.getElementById("vehicleModal");
        const vehicleClose = document.getElementById("vehicleClose");
        const vehicleForm = document.getElementById("vehicleForm");
        const vehicleError = document.getElementById("vehicleError");
        const vehiclePlate = document.getElementById("vehiclePlate");
        const vehicleHeight = document.getElementById("vehicleHeight");
        const vehicleElectric = document.getElementById("vehicleElectric");
        const vehicleVelo = document.getElementById("vehicleVelo");
        const vehicleDelete = document.getElementById("vehicleDelete");

        // Current user state
        let currentUser = null;
        let currentVehicle = null;

        // Check for existing session
        function loadUserSession() {
            try {
                const saved = localStorage.getItem("lokyUser");
                if (saved) {
                    currentUser = JSON.parse(saved);
                    if (
                        currentUser && currentUser.vehicles &&
                        currentUser.vehicles.length > 0
                    ) {
                        // Restaurer le véhicule sélectionné depuis localStorage
                        const savedVehicleId = localStorage.getItem(
                            "lokySelectedVehicleId",
                        );
                        if (savedVehicleId) {
                            const found = currentUser.vehicles.find((v) =>
                                v.id_vehicule == savedVehicleId
                            );
                            currentVehicle = found || currentUser.vehicles[0];
                        } else {
                            currentVehicle = currentUser.vehicles[0];
                        }
                    }
                    updateUIForLoggedUser();
                }
            } catch (e) {
                console.warn("loadUserSession", e);
            }
        }

        function saveUserSession(user) {
            currentUser = user;
            if (user && user.vehicles && user.vehicles.length > 0) {
                // Keep current selection if still valid, otherwise pick first
                const stillExists = currentVehicle &&
                    user.vehicles.some((v) =>
                        v.id_vehicule === currentVehicle.id_vehicule
                    );
                if (!stillExists) {
                    currentVehicle = user.vehicles[0];
                } else {
                    // Update vehicle data
                    currentVehicle = user.vehicles.find((v) =>
                        v.id_vehicule === currentVehicle.id_vehicule
                    );
                }
            } else {
                currentVehicle = null;
            }
            localStorage.setItem("lokyUser", JSON.stringify(user));
            localStorage.setItem(
                "lokySelectedVehicleId",
                currentVehicle ? currentVehicle.id_vehicule : "",
            );
            updateUIForLoggedUser();
        }

        function clearUserSession() {
            currentUser = null;
            currentVehicle = null;
            localStorage.removeItem("lokyUser");
            localStorage.removeItem("lokySelectedVehicleId");
            updateUIForLoggedUser();
        }

        function updateUIForLoggedUser() {
            const guestSettings = document.getElementById("guestSettingsBlock");
            const vehicleListSection = document.getElementById(
                "vehicleListSection",
            );
            const vehicleListEl = document.getElementById("vehicleList");
            const noVehicleMsg = document.getElementById("noVehicleMsg");

            if (currentUser) {
                if (authButtonsContainer) {
                    authButtonsContainer.style.display = "none";
                }
                if (userLoggedContainer) {
                    userLoggedContainer.style.display = "flex";
                }
                if (loggedUserName) {
                    loggedUserName.textContent = currentUser.nom ||
                        currentUser.email;
                }
                // Hide guest settings when logged in
                if (guestSettings) guestSettings.style.display = "none";
                // Show vehicle list section
                if (vehicleListSection) {
                    vehicleListSection.style.display = "block";
                }

                // Apply user PMR
                if (currentUser.pmr && document.getElementById("pmrToggle")) {
                    document.getElementById("pmrToggle").checked = true;
                    pmr = true;
                }

                // Render vehicle list
                renderVehicleList();

                // Apply current vehicle settings
                applyVehicleSettings();
            } else {
                if (authButtonsContainer) {
                    authButtonsContainer.style.display = "flex";
                }
                if (userLoggedContainer) {
                    userLoggedContainer.style.display = "none";
                }
                // Show guest settings when logged out
                if (guestSettings) guestSettings.style.display = "block";
                // Hide vehicle list section
                if (vehicleListSection) {
                    vehicleListSection.style.display = "none";
                }
                if (vehicleListEl) vehicleListEl.innerHTML = "";
                if (noVehicleMsg) noVehicleMsg.style.display = "none";
            }
        }

        function renderVehicleList() {
            const vehicleListEl = document.getElementById("vehicleList");
            const noVehicleMsg = document.getElementById("noVehicleMsg");
            if (!vehicleListEl) return;

            const vehicles = currentUser?.vehicles || [];
            vehicleListEl.innerHTML = "";

            if (vehicles.length === 0) {
                if (noVehicleMsg) noVehicleMsg.style.display = "block";
                return;
            }
            if (noVehicleMsg) noVehicleMsg.style.display = "none";

            vehicles.forEach((v) => {
                const isSelected = currentVehicle &&
                    currentVehicle.id_vehicule === v.id_vehicule;
                const item = document.createElement("div");
                item.className = "vehicle-item" +
                    (isSelected ? " selected" : "");
                item.innerHTML = `
                    <div class="vehicle-item-info">
                        <div class="vehicle-item-plate">${
                    v.plaque_immatriculation || t("vehicle_title")
                }</div>
                        <div class="vehicle-item-details">
                            ${v.hauteur ? `<span>${v.hauteur} cm</span>` : ""}
                            ${
                    v.electrique
                        ? `<span class="vehicle-item-badge electric">⚡</span>`
                        : ""
                }
                            ${
                    v.velo
                        ? `<span class="vehicle-item-badge velo">🚲</span>`
                        : ""
                }
                        </div>
                    </div>
                    <div class="vehicle-item-actions">
                        <button class="btn-select ${
                    isSelected ? "active" : ""
                }" data-id="${v.id_vehicule}">
                            ${isSelected ? "✓" : t("vehicle_select")}
                        </button>
                        <button class="btn-edit" data-id="${v.id_vehicule}">✏️</button>
                    </div>
                `;
                vehicleListEl.appendChild(item);
            });

            // Attach events
            vehicleListEl.querySelectorAll(".btn-select").forEach((btn) => {
                btn.addEventListener(
                    "click",
                    () => selectVehicle(parseInt(btn.dataset.id)),
                );
            });
            vehicleListEl.querySelectorAll(".btn-edit").forEach((btn) => {
                btn.addEventListener(
                    "click",
                    () => editVehicle(parseInt(btn.dataset.id)),
                );
            });
        }

        function selectVehicle(vehicleId) {
            if (!currentUser || !currentUser.vehicles) return;
            currentVehicle = currentUser.vehicles.find((v) =>
                v.id_vehicule === vehicleId
            ) || null;
            localStorage.setItem("lokySelectedVehicleId", vehicleId);
            renderVehicleList();
            applyVehicleSettings();
            onParamChange();
        }

        function applyVehicleSettings() {
            if (currentVehicle) {
                if (document.getElementById("heightMax")) {
                    document.getElementById("heightMax").value =
                        currentVehicle.hauteur || "";
                }
                if (document.getElementById("electricToggle")) {
                    document.getElementById("electricToggle").checked =
                        !!currentVehicle.electrique;
                }
                if (document.getElementById("pmrToggle") && currentUser?.pmr) {
                    document.getElementById("pmrToggle").checked = true;
                }
            }
        }

        function editVehicle(vehicleId) {
            if (!currentUser || !currentUser.vehicles) return;
            const v = currentUser.vehicles.find((veh) =>
                veh.id_vehicule === vehicleId
            );
            if (!v) return;
            currentVehicle = v;
            openVehicleModal(true);
        }

        function showAuthError(msg) {
            if (authError) {
                authError.textContent = msg;
                authError.style.display = "block";
            }
        }

        function hideAuthError() {
            if (authError) authError.style.display = "none";
        }

        function showVehicleError(msg) {
            if (vehicleError) {
                vehicleError.textContent = msg;
                vehicleError.style.display = "block";
            }
        }

        function hideVehicleError() {
            if (vehicleError) vehicleError.style.display = "none";
        }

        function openAuthModal(mode = "login") {
            try {
                if (!authModal) return;
                hideAuthError();
                authModal.style.display = "flex";
                authModal.dataset.mode = mode;
                if (mode === "login") {
                    authTitle.setAttribute("data-i18n", "login_title");
                    authSubmit.textContent = t("btn_login");
                    authSwitch.textContent = t("btn_register");
                    if (registerFields) registerFields.style.display = "none";
                } else {
                    authTitle.setAttribute("data-i18n", "register_title");
                    authSubmit.textContent = t("btn_register");
                    authSwitch.textContent = t("btn_login");
                    if (registerFields) registerFields.style.display = "block";
                }
                I18N.updateDOM();
            } catch (e) {
                console.warn("openAuthModal", e);
            }
        }

        function closeAuthModal() {
            if (!authModal) return;
            authModal.style.display = "none";
            hideAuthError();
            try {
                authForm.reset();
            } catch (e) {}
        }

        let editingVehicleId = null; // Track which vehicle we're editing

        function openVehicleModal(isEdit = false) {
            if (!vehicleModal) return;
            hideVehicleError();
            vehicleModal.style.display = "flex";

            const vehicleTitle = document.getElementById("vehicleTitle");

            if (isEdit && currentVehicle) {
                // Edit mode
                editingVehicleId = currentVehicle.id_vehicule;
                if (vehicleTitle) {
                    vehicleTitle.setAttribute(
                        "data-i18n",
                        "vehicle_edit_title",
                    );
                    vehicleTitle.textContent = t("vehicle_edit_title");
                }
                if (vehiclePlate) {
                    vehiclePlate.value =
                        currentVehicle.plaque_immatriculation || "";
                }
                if (vehicleHeight) {
                    vehicleHeight.value = currentVehicle.hauteur || "";
                }
                if (vehicleElectric) {
                    vehicleElectric.checked = !!currentVehicle.electrique;
                }
                if (vehicleVelo) vehicleVelo.checked = !!currentVehicle.velo;
                if (vehicleDelete) vehicleDelete.style.display = "block";
            } else {
                // Add mode
                editingVehicleId = null;
                if (vehicleTitle) {
                    vehicleTitle.setAttribute("data-i18n", "vehicle_new_title");
                    vehicleTitle.textContent = t("vehicle_new_title");
                }
                if (vehicleForm) vehicleForm.reset();
                if (vehicleDelete) vehicleDelete.style.display = "none";
            }
            I18N.updateDOM();
        }

        function closeVehicleModal() {
            if (!vehicleModal) return;
            vehicleModal.style.display = "none";
            hideVehicleError();
            editingVehicleId = null;
        }

        // API calls
        async function apiLogin(email, password) {
            const res = await fetch("api/Utilisateur/?action=login", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ email, mdp: password }),
            });
            return res.json();
        }

        async function apiRegister(data) {
            const res = await fetch("api/Utilisateur/?action=create", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(data),
            });
            return res.json();
        }

        async function apiSaveVehicle(vehicleData, vehicleId = null) {
            if (vehicleId) {
                // Update existing
                const res = await fetch(
                    `api/Vehicule/?action=update&id=${vehicleId}`,
                    {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify(vehicleData),
                    },
                );
                return res.json();
            } else {
                // Create new
                vehicleData.id_utilisateur = currentUser?.id_utilisateur;
                const res = await fetch("api/Vehicule/?action=create", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(vehicleData),
                });
                return res.json();
            }
        }

        async function apiDeleteVehicle(vehicleId) {
            const res = await fetch(
                `api/Vehicule/?action=delete&id=${vehicleId}`,
                {
                    method: "POST",
                },
            );
            return res.json();
        }

        async function apiGetUser(userId) {
            const res = await fetch(`api/Utilisateur/?action=get&id=${userId}`);
            return res.json();
        }

        // Event listeners
        if (btnConnexion) {
            btnConnexion.addEventListener(
                "click",
                () => openAuthModal("login"),
            );
        }
        if (btnInscription) {
            btnInscription.addEventListener(
                "click",
                () => openAuthModal("register"),
            );
        }
        if (authClose) authClose.addEventListener("click", closeAuthModal);
        if (authModal) {
            authModal.addEventListener("click", (ev) => {
                if (ev.target === authModal) closeAuthModal();
            });
        }

        if (authForm) {
            authForm.addEventListener("submit", async (ev) => {
                ev.preventDefault();
                hideAuthError();
                const email = document.getElementById("authEmail")?.value || "";
                const password =
                    document.getElementById("authPassword")?.value || "";
                const mode = authModal?.dataset.mode || "login";

                try {
                    if (mode === "login") {
                        const result = await apiLogin(email, password);
                        if (result.ok && result.user) {
                            saveUserSession(result.user);
                            closeAuthModal();
                            onParamChange();
                        } else {
                            showAuthError(t("error_invalid_credentials"));
                        }
                    } else {
                        // Register
                        const nom =
                            document.getElementById("authName")?.value || "";
                        const pmrVal =
                            document.getElementById("authPmr")?.checked ? 1 : 0;
                        const regData = {
                            email,
                            mdp: password,
                            nom,
                            pmr: pmrVal,
                        };
                        const result = await apiRegister(regData);
                        if (result.ok) {
                            // Auto-login after register
                            const loginResult = await apiLogin(email, password);
                            if (loginResult.ok && loginResult.user) {
                                saveUserSession(loginResult.user);
                                closeAuthModal();
                                onParamChange();
                            }
                        } else if (result.error === "email_exists") {
                            showAuthError(t("error_email_exists"));
                        } else {
                            showAuthError(t("error_generic"));
                        }
                    }
                } catch (e) {
                    console.warn("Auth error", e);
                    showAuthError(t("error_generic"));
                }
            });
        }

        if (authSwitch) {
            authSwitch.addEventListener("click", () => {
                const current = authModal?.dataset.mode || "login";
                openAuthModal(current === "login" ? "register" : "login");
            });
        }

        if (btnLogout) {
            btnLogout.addEventListener("click", () => {
                clearUserSession();
            });
        }

        // Add vehicle button
        const btnAddVehicle = document.getElementById("btnAddVehicle");
        if (btnAddVehicle) {
            btnAddVehicle.addEventListener(
                "click",
                () => openVehicleModal(false),
            );
        }

        if (vehicleClose) {
            vehicleClose.addEventListener("click", closeVehicleModal);
        }
        if (vehicleModal) {
            vehicleModal.addEventListener("click", (ev) => {
                if (ev.target === vehicleModal) closeVehicleModal();
            });
        }

        if (vehicleForm) {
            vehicleForm.addEventListener("submit", async (ev) => {
                ev.preventDefault();
                hideVehicleError();
                const vData = {
                    plaque: vehiclePlate?.value || "",
                    hauteur: vehicleHeight?.value
                        ? parseInt(vehicleHeight.value)
                        : null,
                    electrique: vehicleElectric?.checked || false,
                    velo: vehicleVelo?.checked || false,
                };

                try {
                    const result = await apiSaveVehicle(
                        vData,
                        editingVehicleId,
                    );
                    if (result.ok) {
                        // Reload user data
                        if (currentUser) {
                            const userData = await apiGetUser(
                                currentUser.id_utilisateur,
                            );
                            if (userData && !userData.error) {
                                saveUserSession(userData);
                            }
                        }
                        closeVehicleModal();
                        onParamChange();
                    } else {
                        showVehicleError(t("error_generic"));
                    }
                } catch (e) {
                    console.warn("Vehicle save error", e);
                    showVehicleError(t("error_generic"));
                }
            });
        }

        if (vehicleDelete) {
            vehicleDelete.addEventListener("click", async () => {
                if (!editingVehicleId) return;
                if (
                    !confirm(
                        t("vehicle_delete_confirm") ||
                            "Supprimer ce véhicule ?",
                    )
                ) return;
                try {
                    const result = await apiDeleteVehicle(editingVehicleId);
                    if (result.ok) {
                        // Si c'était le véhicule sélectionné, le désélectionner
                        if (
                            currentVehicle &&
                            currentVehicle.id_vehicule == editingVehicleId
                        ) {
                            currentVehicle = null;
                            localStorage.removeItem("lokySelectedVehicleId");
                        }
                        // Recharger l'utilisateur pour mettre à jour la liste
                        if (currentUser) {
                            const userData = await apiGetUser(
                                currentUser.id_utilisateur,
                            );
                            if (userData && !userData.error) {
                                saveUserSession(userData);
                            }
                        }
                        closeVehicleModal();
                        onParamChange();
                    }
                } catch (e) {
                    console.warn("Vehicle delete error", e);
                }
            });
        }

        // Load session on init
        loadUserSession();
    } catch (e) {
        console.warn("ui init failed", e);
    }
});
