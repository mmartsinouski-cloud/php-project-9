<?php

namespace App;

use PDO;
use Exception;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    /**
     * @throws Exception
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $databaseUrl = $_ENV['DATABASE_URL'] ?? null;

            if (!$databaseUrl) {
                throw new Exception('DATABASE_URL environment variable is not set');
            }

            $parsedUrl = parse_url($databaseUrl);

            if ($parsedUrl === false) {
                throw new Exception('Invalid DATABASE_URL format: ' . $databaseUrl);
            }

            $host = $parsedUrl['host'] ?? 'localhost';
            $port = $parsedUrl['port'] ?? '5432';
            $dbname = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '';
            $user = isset($parsedUrl['user']) ? urldecode($parsedUrl['user']) : '';
            $password = isset($parsedUrl['pass']) ? urldecode($parsedUrl['pass']) : '';

            if (empty($dbname)) {
                throw new Exception('Database name not specified in DATABASE_URL');
            }

            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

            try {
                self::$connection = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new Exception('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$connection;
    }

    /**
     * @throws Exception
     */
    public static function initializeDatabase(): void
    {
        $db = self::getConnection();

        // Проверяем существование таблицы urls
        $stmt = $db->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_name = 'urls'
        )
    ");
        $urlsTableExists = (bool) $stmt->fetchColumn();

        // Проверяем существование таблицы url_checks
        $stmt = $db->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_name = 'url_checks'
        )
    ");
        $checksTableExists = (bool) $stmt->fetchColumn();
        
        if (!$urlsTableExists || !$checksTableExists) {
            $sqlFile = __DIR__ . '/../database.sql';

            if (!file_exists($sqlFile)) {
                throw new Exception('Database schema file not found: ' . $sqlFile);
            }

            $sql = file_get_contents($sqlFile);

            if ($sql === false) {
                throw new Exception('Failed to read database schema file');
            }

            try {
                $db->exec($sql);
                error_log("Database initialized successfully with schema from database.sql");
            } catch (PDOException $e) {
                throw new Exception('Failed to initialize database: ' . $e->getMessage());
            }
        }
    }
}