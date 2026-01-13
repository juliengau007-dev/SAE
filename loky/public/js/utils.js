// utils.js
/**
 * Calcule la distance en kilomètres entre deux points (formule haversine).
 */
function getDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // rayon Terre en km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 +
        Math.cos(lat1 * Math.PI / 180) *
            Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) ** 2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

/**
 * Centre la carte en appliquant un offset en pixels.
 */
function centerWithOffset(latlng, offsetX = 0, offsetY = -3) {
    try {
        const p = map.latLngToContainerPoint(latlng);
        const pOffset = L.point(p.x + offsetX, p.y + offsetY);
        const latlngOffset = map.containerPointToLatLng(pOffset);
        map.setView(latlngOffset, defaultZoom, { animate: false });
    } catch (e) { /* map pas prête ou erreur */ }
}

/**
 * Met à jour / restaure la traduction des instructions de guidage.
 * - si `lang === 'fr'` : traduit l'anglais -> français en utilisant DICT
 * - sinon : restaure le texte original (stocké en `data-orig`)
 */
function translateRoutingInstructions(lang) {
    const DICT = [
        [/roundabout/ig, "Rond-point"],
        [/enter roundabout/ig, "Entrez au rond-point"],
        [/exit at roundabout/ig, "Prenez la sortie"],
        [/take the exit/ig, "Prenez la sortie"],
        [/turn right/ig, "Tournez à droite"],
        [/turn left/ig, "Tournez à gauche"],
        [/continue/ig, "Continuez"],
        [/slight right/ig, "Légèrement à droite"],
        [/slight left/ig, "Légèrement à gauche"],
        [/make a U-turn/ig, "Faites demi-tour"],
        [/you have arrived/ig, "Vous êtes arrivé(e)"],
        [/arrive at your destination/ig, "Vous êtes arrivé(e) à destination"],
        [/merge/ig, "Rapprochez-vous et fusionnez"],
        [/keep left/ig, "Restez à gauche"],
        [/keep right/ig, "Restez à droite"],
    ];

    document.querySelectorAll(".leaflet-routing-instruction-text").forEach(
        (el) => {
            try {
                // sauvegarder l'original la première fois
                if (!el.dataset.orig) {
                    el.dataset.orig = el.innerText || el.textContent || "";
                }
                const original = el.dataset.orig || "";
                if (lang === "fr") {
                    let s = original;
                    DICT.forEach(([re, fr]) => {
                        s = s.replace(re, fr);
                    });
                    if (s && s !== (el.innerText || "")) el.innerText = s;
                } else {
                    // restaurer
                    if (original && original !== (el.innerText || "")) {
                        el.innerText = original;
                    }
                }
            } catch (e) { /* ignorer erreurs élément par élément */ }
        },
    );
}
