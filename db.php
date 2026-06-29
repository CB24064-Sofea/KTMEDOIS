<?php
// Change 3307 to 3306 if you use 3306 port
define('DB_PORT', 3307);

if (!defined('APP_BASE_PATH')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $segments = array_values(array_filter(explode('/', trim(dirname($scriptName), '/'))));
    if (!empty($segments) && preg_match('/^m[1-4]$/', end($segments))) {
        array_pop($segments);
    }
    define('APP_BASE_PATH', empty($segments) ? '' : '/' . implode('/', $segments));
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string {
        $path = ltrim($path, '/');
        return APP_BASE_PATH . ($path !== '' ? '/' . $path : '');
    }
}

/**
 * Class Database
 * Encapsulates system connection settings and instances using OOP
 */
class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "ktm_edois";
    private $port;
    private $connection = null;

    public function __construct() {
        // Automatically fetch the DB_PORT configuration set above
        $this->port = defined('DB_PORT') ? (int)DB_PORT : 3306;

        try {
            // Instantiate the built-in PHP mysqli object class using OOP parameters
            $this->connection = new mysqli($this->host, $this->username, $this->password, $this->dbname, $this->port);
            
            if ($this->connection->connect_error) {
                throw new Exception("Database Connection Error: " . $this->connection->connect_error);
            }
        } catch (Exception $e) {
            die("<div style='padding:20px; background:#fff5f5; color:#c53030; font-family:sans-serif; border-left:5px solid #c53030; margin:20px;'>" .
                "<strong>System Connection Failure:</strong> " . htmlspecialchars($e->getMessage()) . 
                "<br><small>Tip: Double check if your XAMPP/MySQL service is actively running on port " . $this->port . ".</small></div>");
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    public function closeConnection() {
        if ($this->connection !== null) {
            $this->connection->close();
        }
    }
}

// Instantiate the global object class instance for your dashboard and report pages
$dbInstance = new Database();
$conn = $dbInstance->getConnection();
