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

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM urls ORDER BY created_at DESC");
        /** @var array<int, array<string, mixed>> $result */
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
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
}