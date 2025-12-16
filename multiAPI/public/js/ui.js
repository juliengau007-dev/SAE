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
    } catch (e) {
        console.warn("ui init failed", e);
    }
});
