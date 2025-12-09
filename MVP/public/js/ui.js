// ui.js
// Fonctions liées à l'interface et aux paramètres
function loadMenuSettings() {
    try {
        const pmrVal = localStorage.getItem("pmrEnabled");
        const heightVal = localStorage.getItem("heightMax");
        const elecVal = localStorage.getItem("electricOnly");
        if (document.getElementById("pmrToggle") && pmrVal !== null) {
            document.getElementById("pmrToggle").checked = pmrVal === "1";
        }
        if (document.getElementById("heightMax") && heightVal !== null) {
            document.getElementById("heightMax").value = heightVal;
        }
        if (document.getElementById("electricToggle") && elecVal !== null) {
            document.getElementById("electricToggle").checked = elecVal === "1";
        }
        pmr = document.getElementById("pmrToggle")?.checked || false;
    } catch (e) {
        console.warn("loadMenuSettings", e);
    }
}

function saveParamSettings() {
    try {
        const pmrVal = document.getElementById("pmrToggle")?.checked
            ? "1"
            : "0";
        const heightVal = document.getElementById("heightMax")?.value || "";
        const elecVal = document.getElementById("electricToggle")?.checked
            ? "1"
            : "0";
        localStorage.setItem("pmrEnabled", pmrVal);
        localStorage.setItem("heightMax", heightVal);
        localStorage.setItem("electricOnly", elecVal);
        pmr = pmrVal === "1";
    } catch (e) {
        console.warn("saveParamSettings", e);
    }
}

function togglePMR() {
    pmr = document.getElementById("pmrToggle").checked;
}

function showMetzAvailability(text, cls = "") {
    try {
        const container = document.querySelector(
            ".leaflet-routing-container .routing-actions",
        );
        if (!container) return;
        let el = container.querySelector(".metz-availability");
        if (!el) {
            el = document.createElement("div");
            el.className = "metz-availability";
            container.appendChild(el);
        }
        el.textContent = text;
        if (cls === "ok") el.style.background = "#d4ffd7";
        else if (cls === "warning") el.style.background = "#fff4cc";
        else if (cls === "bad") el.style.background = "#ffd6d6";
        else el.style.background = "rgba(255,255,255,0.9)";
    } catch (e) {
        console.warn("showMetzAvailability", e);
    }
}

function saveMetzSettings() {
    try {
        const enabled = document.getElementById("metzToggle")?.checked
            ? "1"
            : "0";
        const url = document.getElementById("metzApiUrl")?.value || "";
        localStorage.setItem("metzCheckEnabled", enabled);
        localStorage.setItem("metzApiUrl", url);
    } catch (e) {
        console.warn("saveMetzSettings", e);
    }
}

function loadMetzSettings() {
    try {
        const enabled = localStorage.getItem("metzCheckEnabled");
        const url = localStorage.getItem("metzApiUrl");
        if (document.getElementById("metzToggle") && enabled !== null) {
            document.getElementById("metzToggle").checked = enabled === "1";
        }
        if (document.getElementById("metzApiUrl") && url !== null) {
            document.getElementById("metzApiUrl").value = url;
        }
    } catch (e) {
        console.warn("loadMetzSettings", e);
    }
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

// startMetzPolling / stopMetzPolling: implement here since they use showMetzAvailability
function startMetzPolling(fid) {
    stopMetzPolling();
    if (!fid) return;
    _currentTargetFid = fid;
    const enabled = document.getElementById("metzToggle")?.checked;
    if (!enabled) {
        _currentTargetFid = null;
        return;
    }

    (async () => {
        const info = await fetchMetzAvailabilityOnce(fid);
        if (info) {
            if (info.value != null) {
                showMetzAvailability("Places: " + info.value, "ok");
            } else showMetzAvailability("Info disponible", "warning");
            if (!isNaN(Number(info.value)) && Number(info.value) <= 0) {
                attemptAutoSwitchIfNeeded();
            }
        } else showMetzAvailability("Pas de donnée", "bad");
    })();

    _metzPollInterval = setInterval(async () => {
        const info = await fetchMetzAvailabilityOnce(fid);
        if (info) {
            if (info.value != null) {
                showMetzAvailability("Places: " + info.value, "ok");
            } else showMetzAvailability("Info disponible", "warning");
            if (!isNaN(Number(info.value)) && Number(info.value) <= 0) {
                attemptAutoSwitchIfNeeded();
            }
        } else showMetzAvailability("Pas de donnée", "bad");
    }, _metzPollMs);
}

function stopMetzPolling(clearTarget = false) {
    try {
        if (_metzPollInterval) {
            clearInterval(_metzPollInterval);
            _metzPollInterval = null;
        }
        const container = document.querySelector(
            ".leaflet-routing-container .routing-actions",
        );
        if (container) {
            const el = container.querySelector(".metz-availability");
            if (el) el.remove();
        }
        if (clearTarget) _currentTargetFid = null;
    } catch (e) {
        console.warn("stopMetzPolling", e);
    }
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
        if (elPmr) {
            elPmr.addEventListener("change", () => {
                togglePMR();
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
