<?php

class DatabaseHelper {
    private static $instance = null;
    private $connection;

    private $host = 'localhost';
    private $port = 5432;
    private $dbname = 'asistenciau';
    private $username = 'postgres';
    private $password = 'aguavita';

    private function __construct() {
        try {
            $connStr = "pgsql:host=$this->host;port=$this->port;dbname=$this->dbname";
            $this->connection = new PDO($connStr, $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    public static function getInstance(): DatabaseHelper {
        if (self::$instance === null) {
            self::$instance = new DatabaseHelper();
        }
        return self::$instance;
    }

    public function connect(): PDO {
        return $this->connection;
    }

    public function query(string $sql): PDOStatement {
        return $this->connection->query($sql);
    }

    public function insert(string $table, array $data): int {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($data);
        // Si la tabla no tiene autoincrement, solo retorna true si fue exitoso
        return $stmt->rowCount();
    }

    public function update(string $table, array $data, $id): int {
        $set = "";
        foreach ($data as $key => $value) {
            $set .= "$key = :$key, ";
        }
        $set = rtrim($set, ", ");
        
        // Determinar columna ID según tabla
        $idCol = 'id';
        if ($table === 'grupo') $idCol = 'id_grupo';
        if ($table === 'clase') $idCol = 'id_clase';
        if ($table === 'asistencia') $idCol = 'id_asistencia';
        if ($table === 'estudiante' || $table === 'profesor') $idCol = 'usuario_id';
        
        $sql = "UPDATE $table SET $set WHERE $idCol = :id_val";
        $data['id_val'] = $id;
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($data);
        return $stmt->rowCount();
    }

    public function delete(string $table, $id): int {
        if (is_array($id)) {
            $key = array_key_first($id);
            $val = $id[$key];
            $sql = "DELETE FROM $table WHERE $key = :val";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute(['val' => $val]);
        } else {
            // Determinar columna ID según tabla
            $idCol = 'id';
            if ($table === 'grupo') $idCol = 'id_grupo';
            if ($table === 'clase') $idCol = 'id_clase';
            if ($table === 'asistencia') $idCol = 'id_asistencia';
            if ($table === 'estudiante' || $table === 'profesor') $idCol = 'usuario_id';
            if ($table === 'materia') $idCol = 'id';

            $sql = "DELETE FROM $table WHERE $idCol = :id";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute(['id' => $id]);
        }
        return $stmt->rowCount();
    }
}
