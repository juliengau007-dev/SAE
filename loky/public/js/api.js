// api.js

/**
 * Construire l'URL de requête externe pour la ville courante
 */
function _getRequestUrlForId(parkingId) {
    const cityConfig = getCurrentCityConfig();
    const inputId = `${_currentCity}ApiUrl`;
    const input = document.getElementById(inputId);
    if (!input) return null;
    let url = input.value.trim();
    if (!url) return null;

    // Remplacer les placeholders
    if (url.includes("{fid}")) {
        return url.replace("{fid}", encodeURIComponent(parkingId));
    }
    if (url.includes("{id}")) {
        return url.replace("{id}", encodeURIComponent(parkingId));
    }
    if (url.includes("?")) return url + "&id=" + encodeURIComponent(parkingId);
    return url + "?id=" + encodeURIComponent(parkingId);
}

/**
 * Trouver un parking dans le GeoJSON par son ID
 */
function _findParkingInGeoJson(geoJson, parkingId) {
    if (!geoJson?.features) return null;
    const cityConfig = getCurrentCityConfig();
    const idFields = cityConfig.idFields || ["fid", "id"];

    return geoJson.features.find((f) => {
        const props = f.properties || {};
        for (const field of idFields) {
            const val = props[field] ?? f[field];
            if (val != null && String(val) === String(parkingId)) {
                return true;
            }
        }
        // Fallback sur l'id de la feature
        if (f.id && !isNaN(parseInt(f.id))) {
            return Number(parseInt(f.id)) === Number(parkingId);
        }
        return false;
    });
}

/**
 * Extraire la disponibilité depuis les propriétés selon la config de la ville
 */
function _extractAvailability(props) {
    const cityConfig = getCurrentCityConfig();
    const keys = cityConfig.availabilityKeys || [
        "available",
        "free",
        "places",
        "disponible",
        "nb_places",
    ];

    // Recherche directe
    for (const k of keys) {
        if (props[k] !== undefined && props[k] !== null) {
            return { raw: props, value: props[k], key: k };
        }
    }
    // Recherche dans les objets imbriqués
    for (const k of Object.keys(props)) {
        const v = props[k];
        if (v && typeof v === "object") {
            for (const kk of keys) {
                if (v[kk] !== undefined && v[kk] !== null) {
                    return { raw: props, value: v[kk], key: kk };
                }
            }
        }
    }
    return null;
}

/**
 * Récupérer la disponibilité d'un parking (générique pour toute ville)
 * Retourne { raw, value, key } ou null
 */
async function fetchParkingAvailability(parkingId) {
    if (!parkingId) return null;

    const cityConfig = getCurrentCityConfig();
    const cacheKey = `${_currentCity}:${parkingId}`;
    const now = Date.now();

    // Vérifier le cache
    try {
        const c = _clientCache[cacheKey];
        if (c && (now - c.ts) < _clientCacheTtl) return c.valueObj;
    } catch (e) {}

    // 1) Tentative depuis lastGeoJson (cache local)
    try {
        let geo = lastGeoJson;
        if (!geo) {
            try {
                const r = await fetch(cityConfig.geojsonEndpoint);
                if (r.ok) {
                    geo = await r.json();
                    lastGeoJson = geo;
                }
            } catch (e) {}
        }

        if (geo && Array.isArray(geo.features)) {
            const feat = _findParkingInGeoJson(geo, parkingId);
            if (feat) {
                const props = feat.properties || {};
                const result = _extractAvailability(props);
                if (result) {
                    _clientCache[cacheKey] = { ts: now, valueObj: result };
                    return result;
                }
            }
        }
    } catch (e) {
        console.warn(
            `[${cityConfig.name}] fetchParkingAvailability: local lookup failed`,
            e,
        );
    }

    // 2) Fallback vers URL externe configurée
    const reqUrl = _getRequestUrlForId(parkingId);
    if (!reqUrl) return null;

    try {
        const res = await fetch(reqUrl);
        if (!res.ok) return null;
        const json = await res.json();

        const result = _extractAvailability(json);
        if (result) {
            _clientCache[cacheKey] = { ts: now, valueObj: result };
            return result;
        }

        // Retourner les données brutes même sans disponibilité trouvée
        const out = { raw: json, value: null, key: null };
        _clientCache[cacheKey] = { ts: now, valueObj: out };
        return out;
    } catch (e) {
        console.warn(
            `[${cityConfig.name}] fetchParkingAvailability external fetch failed`,
            e,
        );
        return null;
    }
}

// Alias pour rétrocompatibilité
async function fetchMetzAvailabilityOnce(fid) {
    return await fetchParkingAvailability(fid);
}
