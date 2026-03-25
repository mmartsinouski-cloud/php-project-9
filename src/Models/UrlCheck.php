<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use Exception;
use PDO;
use Carbon\Carbon;

class UrlCheck
{
    private PDO $db;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * @param int $urlId
     * @return array<int, array<string, mixed>>
     */
    public function findByUrlId(int $urlId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY created_at DESC"
        );
        $stmt->execute(['url_id' => $urlId]);

        /** @var array<int, array<string, mixed>> $result */
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * @param int $urlId
     * @return array<string, mixed>|null
     */
    public function getLastCheck(int $urlId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute(['url_id' => $urlId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * @param int $urlId
     * @param array<string, mixed> $data
     * @return int|null
     */
    public function create(int $urlId, array $data): ?int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO url_checks (url_id, status_code, h1, title, description, created_at) 
             VALUES (:url_id, :status_code, :h1, :title, :description, :created_at)"
        );

        $result = $stmt->execute([
            'url_id' => $urlId,
            'status_code' => $data['status_code'] ?? null,
            'h1' => $data['h1'] ?? null,
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'created_at' => Carbon::now()->toDateTimeString()
        ]);

        if ($result) {
            return (int) $this->db->lastInsertId();
        }

        return null;
    }
}