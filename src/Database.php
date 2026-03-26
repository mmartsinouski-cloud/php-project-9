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
                throw new Exception('Переменная среды DATABASE_URL не установлена.');
            }

            $parsedUrl = parse_url($databaseUrl);

            if ($parsedUrl === false) {
                throw new Exception('Неверный формат DATABASE_URL:' . $databaseUrl);
            }

            $host = $parsedUrl['host'] ?? 'localhost';
            $port = $parsedUrl['port'] ?? '5432';
            $dbname = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '';
            $user = isset($parsedUrl['user']) ? urldecode($parsedUrl['user']) : '';
            $password = isset($parsedUrl['pass']) ? urldecode($parsedUrl['pass']) : '';

            if (empty($dbname)) {
                throw new Exception('Имя базы данных не указано в DATABASE_URL');
            }

            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

            try {
                self::$connection = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new Exception('Не удалось подключиться к базе данных. ' . $e->getMessage());
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

        if ($stmt === false) {
            throw new Exception('Не удалось выполнить запрос для проверки существования таблицы urls');
        }

        $urlsTableExists = (bool) $stmt->fetchColumn();

        // Проверяем существование таблицы url_checks
        $stmt = $db->query("
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_name = 'url_checks'
            )
        ");

        if ($stmt === false) {
            throw new Exception('Не удалось выполнить запрос для проверки существования таблицы url_checks');
        }

        $checksTableExists = (bool) $stmt->fetchColumn();

        if (!$urlsTableExists || !$checksTableExists) {
            $sqlFile = __DIR__ . '/../database.sql';

            if (!file_exists($sqlFile)) {
                throw new Exception('Файл схемы базы данных не найден. ' . $sqlFile);
            }

            $sql = file_get_contents($sqlFile);

            if ($sql === false) {
                throw new Exception('Не удалось прочитать файл схемы базы данных. ');
            }

            try {
                $db->exec($sql);
                error_log("База данных успешно инициализирована со схемой из файла database.sql.");
            } catch (PDOException $e) {
                throw new Exception('Не удалось инициализировать базу данных. ' . $e->getMessage());
            }
        }
    }
}