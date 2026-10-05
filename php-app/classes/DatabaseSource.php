<?php
abstract class DatabaseSource
{
    protected PDO $pdo;

    public function __construct(array $dbConfig)
    {
        if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
            throw new RuntimeException('Ative pdo_mysql na instalacao PHP utilizada pelo MAMP.', 503);
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['name'],
            $dbConfig['charset']
        );

        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    abstract public function getAll(): array;
}
