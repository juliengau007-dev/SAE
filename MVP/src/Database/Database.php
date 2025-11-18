<?php
namespace V3\Database;

use \PDO;
use \PDOException;

class Database
{
    /** @var PDO */
    protected $pdo;

    /**
     * Create a new Database connection using PDO.
     * Default credentials: host=127.0.0.1, db=ParkingMetz, user=root, pass=''
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
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Return the raw PDO instance
     * @return PDO
     */
    public function pdo(): PDO
    {
        return $this->pdo;
    }
}

// EOF
