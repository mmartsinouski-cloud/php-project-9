<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use Exception;
use PDO;
use Carbon\Carbon;

class Url
{
    private PDO $db;
    private UrlCheck $urlCheck;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->urlCheck = new UrlCheck();
    }
    

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM urls WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * @param string $name
     * @return array<string, mixed>|null
     */
    public function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM urls WHERE name = :name");
        $stmt->execute(['name' => $name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * @param string $name
     * @return int|null
     */
    public function create(string $name): ?int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)"
        );

        $result = $stmt->execute([
            'name' => $name,
            'created_at' => Carbon::now()->toDateTimeString()
        ]);

        if ($result) {
            return (int) $this->db->lastInsertId();
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAllWithLastCheck(): array
    {
        $stmt = $this->db->query("SELECT * FROM urls ORDER BY created_at DESC");
        $urls = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($urls as $url) {
            $lastCheck = $this->urlCheck->getLastCheck((int)$url['id']);
            $url['last_check'] = $lastCheck;
            $url['status_code'] = $lastCheck['status_code'] ?? null;
            $result[] = $url;
        }

        return $result;
    }
}