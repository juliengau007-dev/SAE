// savedParkings.js - Système de parkings favoris avec API

let savedParkings = [];

// Charger les favoris depuis l'API
async function loadSavedParkings() {
    try {
        // Récupérer l'utilisateur actuel depuis localStorage
        const userStr = localStorage.getItem('lokyUser');
        const user = userStr ? JSON.parse(userStr) : null;
        
        // Si pas d'utilisateur, vider la liste
        if (!user || !user.id_utilisateur) {
            savedParkings = [];
            updateSavedParkingsList();
            return;
        }
        
        const response = await fetch('api/SavedParkings/index.php?action=list&user_id=' + user.id_utilisateur);
        const result = await response.json();
        
        if (result.success) {
            savedParkings = result.data.map(p => ({
                id: p.parking_id,
                name: p.parking_name,
                customName: p.custom_name,
                note: p.note,
                lat: parseFloat(p.latitude),
                lon: parseFloat(p.longitude)
            }));
        } else {
            savedParkings = [];
        }
    } catch (e) {
        console.error('Error loading saved parkings:', e);
        savedParkings = [];
    }
    updateSavedParkingsList();
}

// Vérifier si un parking est enregistré
function isParkingSaved(parkingId) {
    return savedParkings.some(p => String(p.id) === String(parkingId));
}

// Obtenir un parking enregistré
function getSavedParking(parkingId) {
    return savedParkings.find(p => String(p.id) === String(parkingId));
}

// Ajouter/Modifier un parking
async function addSavedParking(parkingData, customName, note) {
    try {
        // Vérifier la connexion
        const userStr = localStorage.getItem('lokyUser');
        const user = userStr ? JSON.parse(userStr) : null;
        
        if (!user || !user.id_utilisateur) {
            alert('Vous devez être connecté pour enregistrer un parking');
            return false;
        }
        
        const response = await fetch('api/SavedParkings/index.php?action=save&user_id=' + user.id_utilisateur, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                parking_id: parkingData.id,
                parking_name: parkingData.name,
                custom_name: customName,
                note: note,
                latitude: parkingData.lat,
                longitude: parkingData.lon
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            await loadSavedParkings();
            
            // Recharger les parkings pour mettre à jour les étoiles
            if (typeof loadParkings === 'function') {
                await loadParkings();
            }
            
            return true;
        } else {
            throw new Error(result.error || 'Save failed');
        }
    } catch (e) {
        console.error('Error saving parking:', e);
        alert('Erreur lors de l\'enregistrement du parking');
        return false;
    }
}

// Supprimer un parking
async function deleteSavedParking(parkingId) {
    const confirm_msg = typeof t === 'function' ? t('save_parking_delete_confirm') : 'Êtes-vous sûr de vouloir supprimer ce parking ?';
    if (!confirm(confirm_msg)) return false;
    
    try {
        // Vérifier la connexion
        const userStr = localStorage.getItem('lokyUser');
        const user = userStr ? JSON.parse(userStr) : null;
        
        if (!user || !user.id_utilisateur) {
            alert('Vous devez être connecté');
            return false;
        }
        
        const response = await fetch('api/SavedParkings/index.php?action=delete&user_id=' + user.id_utilisateur, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ parking_id: parkingId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            await loadSavedParkings();
            
            // Recharger les parkings pour mettre à jour les étoiles
            if (typeof loadParkings === 'function') {
                await loadParkings();
            }
            
            return true;
        } else {
            throw new Error(result.error || 'Delete failed');
        }
    } catch (e) {
        console.error('Error deleting parking:', e);
        alert('Erreur lors de la suppression');
        return false;
    }
}

// Mettre à jour l'affichage de la liste
function updateSavedParkingsList() {
    const container = document.getElementById('savedParkingsList');
    const noMsg = document.getElementById('noSavedParkingsMsg');
    
    if (!container) return;
    
    container.innerHTML = '';
    
    if (savedParkings.length === 0) {
        if (noMsg) noMsg.style.display = 'block';
        return;
    }
    
    if (noMsg) noMsg.style.display = 'none';
    
    savedParkings.forEach(parking => {
        const item = document.createElement('div');
        item.className = 'vehicle-item saved-parking-item';
        
        item.innerHTML = `
            <div class="vehicle-item-info" onclick="goToSavedParking('${parking.id}')">
                <div class="vehicle-item-plate" style="color: #28a745; cursor: pointer;">
                    ⭐ ${parking.customName}
                </div>
                <div class="vehicle-item-details">
                    ${parking.note ? `<div style="font-size: 12px; color: #666;">${parking.note}</div>` : ''}
                </div>
            </div>
        `;
        
        container.appendChild(item);
    });
}

// Naviguer vers un parking
function goToSavedParking(parkingId) {
    const parking = getSavedParking(parkingId);
    if (!parking || !map) return;
    
    // Fermer le menu
    const menu = document.getElementById('menuParam');
    if (menu) menu.style.display = 'none';
    
    // Centrer sur le parking
    map.setView([parking.lat, parking.lon], 17);
    
    // Ouvrir le popup après un court délai
    setTimeout(() => {
        if (parkingsLayer) {
            parkingsLayer.eachLayer(layer => {
                if (layer.feature) {
                    const props = layer.feature.properties || {};
                    const fid = props.fid || props.id || layer.feature.id;
                    if (String(fid) === String(parkingId)) {
                        layer.openPopup();
                    }
                }
            });
        }
    }, 300);
}

// Ouvrir le modal d'enregistrement
function openSaveParkingModal(parkingData, isEdit = false) {
    const modal = document.getElementById('saveParkingModal');
    const nameInput = document.getElementById('parkingCustomName');
    const noteInput = document.getElementById('parkingNote');
    const deleteBtn = document.getElementById('btnDeleteSavedParking');
    
    if (!modal) return;
    
    window.currentEditingParking = parkingData;
    
    // Réinitialiser
    document.getElementById('saveParkingForm').reset();
    document.getElementById('saveParkingError').style.display = 'none';
    
    if (isEdit) {
        const saved = getSavedParking(parkingData.id);
        if (saved) {
            nameInput.value = saved.customName || '';
            noteInput.value = saved.note || '';
        }
        deleteBtn.style.display = 'block';
    } else {
        nameInput.value = parkingData.name || '';
        deleteBtn.style.display = 'none';
    }
    
    modal.style.display = 'flex';
}

// Fermer le modal
function closeSaveParkingModal() {
    const modal = document.getElementById('saveParkingModal');
    if (modal) modal.style.display = 'none';
    window.currentEditingParking = null;
}

// Initialiser les événements
function initSaveParkingModal() {
    const modal = document.getElementById('saveParkingModal');
    const form = document.getElementById('saveParkingForm');
    const closeBtn = document.getElementById('saveParkingClose');
    const cancelBtn = document.getElementById('btnCancelSaveParking');
    const deleteBtn = document.getElementById('btnDeleteSavedParking');
    
    if (!modal) return;
    
    if (closeBtn) closeBtn.onclick = closeSaveParkingModal;
    if (cancelBtn) cancelBtn.onclick = closeSaveParkingModal;
    
    modal.onclick = (e) => {
        if (e.target === modal) closeSaveParkingModal();
    };
    
    if (deleteBtn) {
        deleteBtn.onclick = async () => {
            if (window.currentEditingParking) {
                const success = await deleteSavedParking(window.currentEditingParking.id);
                if (success) {
                    closeSaveParkingModal();
                    const msg = typeof t === 'function' ? t('save_parking_success') : 'Parking supprimé !';
                    if (typeof showTemporaryMessage === 'function') {
                        showTemporaryMessage(msg);
                    }
                }
            }
        };
    }
    
    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            if (!window.currentEditingParking) return;
            
            const name = document.getElementById('parkingCustomName').value.trim();
            const note = document.getElementById('parkingNote').value.trim();
            
            if (!name) {
                document.getElementById('saveParkingError').textContent = 'Le nom est requis';
                document.getElementById('saveParkingError').style.display = 'block';
                return;
            }
            
            const success = await addSavedParking(window.currentEditingParking, name, note);
            if (success) {
                closeSaveParkingModal();
                const msg = typeof t === 'function' ? t('save_parking_success') : 'Parking enregistré !';
                if (typeof showTemporaryMessage === 'function') {
                    showTemporaryMessage(msg);
                }
            }
        };
    }
}

// Charger au démarrage
document.addEventListener('DOMContentLoaded', () => {
    loadSavedParkings();
    initSaveParkingModal();
});
