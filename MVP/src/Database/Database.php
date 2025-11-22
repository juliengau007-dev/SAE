<?php
namespace V3\Database;

use \PDO;
use \PDOException;

/**
 * Petit wrapper de connexion PDO réutilisable par les services.
 *
 * Usage : instancier `new Database()` (ou fournir host/db/user/pass)
 * puis récupérer la connexion via `$db->pdo()` pour exécuter des requêtes.
 */
class Database
{
    /** @var PDO */
    protected $pdo;

    /**
     * Constructeur : ouvre la connexion PDO.
     * Les paramètres par défaut conviennent pour un serveur local de dev.
     *
     * @param string $host hôte MySQL
     * @param string $db   nom de la base
     * @param string $user utilisateur
     * @param string $pass mot de passe
     * @param string $charset jeu de caractères
     */
    public function __construct(string $host = '127.0.0.1', string $db = 'ParkingMetz', string $user = 'root', string $pass = '', string $charset = 'utf8mb4')
    {
        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Remonter une exception claire en cas d'échec de connexion
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Retourne l'instance PDO brute pour exécuter des requêtes.
     *
     * @return PDO
     */
    public function pdo(): PDO
    {
        return $this->pdo;
    }
}

// EOF
