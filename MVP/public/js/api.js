// api.js
/**
 * Construire l'URL de requête externe pour Metz si configurée
 */
function _getMetzRequestUrlForFid(fid) {
    const input = document.getElementById("metzApiUrl");
    if (!input) return null;
    let url = input.value.trim();
    if (!url) return null;
    if (url.includes("{fid}")) {
        return url.replace("{fid}", encodeURIComponent(fid));
    }
    if (url.includes("?")) return url + "&fid=" + encodeURIComponent(fid);
    return url + "?fid=" + encodeURIComponent(fid);
}

/**
 * Tente de retrouver la disponibilité d'un parking (local -> externe)
 * Retourne { raw, value, key } ou null
 */
async function fetchMetzAvailabilityOnce(fid) {
    if (!fid) return null;
    const keys = [
        "available",
        "free",
        "places",
        "available_places",
        "disponible",
        "nb_places",
        "nombre",
        "places_libres",
        "place_libre",
    ];
    const now = Date.now();

    try {
        const c = _metzClientCache[fid];
        if (c && (now - c.ts) < _metzClientCacheTtl) return c.valueObj;
    } catch (e) {}

    // 1) tentative depuis lastGeoJson
    try {
        let geo = lastGeoJson;
        if (!geo) {
            try {
                const r = await fetch("api/parkings_geojson.php");
                if (r.ok) {
                    geo = await r.json();
                    lastGeoJson = geo;
                }
            } catch (e) {}
        }

        if (geo && Array.isArray(geo.features)) {
            const feat = geo.features.find((f) => {
                const p = f.properties || {};
                const fidProp = p.fid ?? p.id ?? null;
                if (fidProp != null) return Number(fidProp) === Number(fid);
                if (f.id && !isNaN(parseInt(f.id))) {
                    return Number(parseInt(f.id)) === Number(fid);
                }
                return false;
            });
            if (feat) {
                const props = feat.properties || {};
                for (const k of keys) {
                    if (props[k] !== undefined && props[k] !== null) {
                        const out = { raw: props, value: props[k], key: k };
                        _metzClientCache[fid] = { ts: now, valueObj: out };
                        return out;
                    }
                }
                for (const k of Object.keys(props)) {
                    const v = props[k];
                    if (v && typeof v === "object") {
                        for (const kk of keys) {
                            if (v[kk] !== undefined && v[kk] !== null) {
                                const out = {
                                    raw: props,
                                    value: v[kk],
                                    key: kk,
                                };
                                _metzClientCache[fid] = {
                                    ts: now,
                                    valueObj: out,
                                };
                                return out;
                            }
                        }
                    }
                }
            }
        }
    } catch (e) {
        console.warn("fetchMetzAvailabilityOnce: local lookup failed", e);
    }

    // 2) fallback vers URL utilisateur
    const reqUrl = _getMetzRequestUrlForFid(fid);
    if (!reqUrl) return null;
    try {
        const res = await fetch(reqUrl);
        if (!res.ok) return null;
        const json = await res.json();
        for (const k of keys) {
            if (json[k] !== undefined && json[k] !== null) {
                const out = { raw: json, value: json[k], key: k };
                _metzClientCache[fid] = { ts: now, valueObj: out };
                return out;
            }
        }
        for (const k of Object.keys(json)) {
            const v = json[k];
            if (typeof v === "object" && v !== null) {
                for (const kk of keys) {
                    if (v && v[kk] !== undefined && v[kk] !== null) {
                        const out = { raw: json, value: v[kk], key: kk };
                        _metzClientCache[fid] = { ts: now, valueObj: out };
                        return out;
                    }
                }
            }
        }
        const out = { raw: json, value: null, key: null };
        _metzClientCache[fid] = { ts: now, valueObj: out };
        return out;
    } catch (e) {
        console.warn("fetchMetzAvailabilityOnce", e);
        return null;
    }
}
