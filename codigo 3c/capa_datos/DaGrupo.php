<?php
require_once __DIR__ . '/DatabaseHelper.php';

class DaGrupo {
    public function getById(int $groupId): array {
        $dbHelper = DatabaseHelper::getInstance();
        $db = $dbHelper->connect();
        $sql = "SELECT * FROM grupo WHERE id_grupo = :id_grupo LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id_grupo' => $groupId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }
    public function getAll(): array {
        $sql = "SELECT * FROM grupo ORDER BY id_grupo DESC";
        $dbHelper = DatabaseHelper::getInstance();
        $db = $dbHelper->connect();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function update(int $groupId, array $data): bool {
        $dbHelper = DatabaseHelper::getInstance();
        return $dbHelper->update('grupo', $data, $groupId) > 0;
    }
    public function checkCapacity(int $groupId): bool {
        $grupo = $this->getById($groupId);
        if (!$grupo) return false;
        $capacidadMax = intval($grupo['capacidad_maxima'] ?? 0);
        $capacidadAct = intval($grupo['capacidad_actual'] ?? 0);
        return $capacidadMax > 0 && $capacidadAct < $capacidadMax;
    }
}
