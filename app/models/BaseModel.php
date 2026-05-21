<?php
/**
 * app/models/BaseModel.php
 * Classe mère pour tous les modèles — CRUD générique via PDO
 */
abstract class BaseModel
{
    protected PDO    $db;
    protected string $table;
    protected string $pk = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(array $filters = []): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->pk} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): int|bool
    {
        $cols   = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt   = $this->db->prepare("INSERT INTO {$this->table} ({$cols}) VALUES ({$placeholders})");
        $result = $stmt->execute(array_values($data));
        return $result ? (int)$this->db->lastInsertId() : false;
    }

    public function update(int $id, array $data): bool
    {
        $set  = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$set} WHERE {$this->pk} = ?");
        return $stmt->execute([...array_values($data), $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->pk} = ?");
        return $stmt->execute([$id]);
    }

    public function getLastInsertId(): int
    {
        return (int)$this->db->lastInsertId();
    }
}
