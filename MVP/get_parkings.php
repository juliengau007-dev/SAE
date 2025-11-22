<?php
// Wrapper de compatibilité : redirige vers le nouvel endpoint
// public/api/get_parkings.php. Conserve l'ancien chemin pour
// les scripts qui l'appelaient encore.
require_once __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'get_parkings.php';

// fin
?>