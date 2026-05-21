<?php
/**
 * config/database.php
 * Connexion PDO — Singleton
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private string $host;
    private string $dbname;
    private string $username;
    private string $password;
    private string $charset;

    private function __construct()
    {
        // Optional environment variables keep credentials out of code later.
        // No dotenv dependency is required; WAMP defaults still work as before.
        $this->host     = $this->env('DB_HOST', 'localhost');
        $this->dbname   = $this->env('DB_NAME', 'intellihire');
        $this->username = $this->env('DB_USER', 'root');
        $this->password = $this->env('DB_PASS', '');
        $this->charset  = $this->env('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:monospace;background:#0d1520;color:#f43f5e;padding:2rem;border-radius:8px;margin:2rem auto;max-width:600px">
                <h2>❌ Erreur de connexion à la base de données</h2>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p style="color:#94a3b8;font-size:.85rem">Vérifiez <code>config/database.php</code> et assurez-vous que MySQL est démarré.</p>
            </div>');
        }
    }

    public static function getInstance(): static
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    private function env(string $key, string $default): string
    {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}

