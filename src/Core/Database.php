<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $pdo = null;

    public static function init(): void {
        if (self::$pdo !== null) return;
        if (!defined('DB_HOST')) {
            $cfgPath = BASE_PATH . '/config/database.php';
            if (file_exists($cfgPath)) require_once $cfgPath;
        }
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT
             . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            // PHP 8.5+ uses Pdo\Mysql::ATTR_INIT_COMMAND; older uses PDO::MYSQL_ATTR_INIT_COMMAND
            $initCmd = defined('Pdo\Mysql::ATTR_INIT_COMMAND')
                ? \Pdo\Mysql::ATTR_INIT_COMMAND
                : PDO::MYSQL_ATTR_INIT_COMMAND;
            $options[$initCmd] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Don't expose credentials in error messages
            throw new \RuntimeException('Database connection failed. Check your config/database.php.');
        }
    }

    public static function pdo(): PDO {
        if (self::$pdo === null) self::init();
        return self::$pdo;
    }

    /**
     * Execute a query and return the PDOStatement.
     */
    public static function query(string $sql, array $params = []): \PDOStatement {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row.
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $row = self::query($sql, $params)->fetch();
        return $row ?: null;
    }

    /**
     * Fetch all rows.
     */
    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Execute an INSERT/UPDATE/DELETE and return affected rows.
     */
    public static function execute(string $sql, array $params = []): int {
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Return the last inserted ID.
     */
    public static function lastInsertId(): int {
        return (int)self::pdo()->lastInsertId();
    }

    /**
     * Fetch a single scalar value.
     */
    public static function fetchScalar(string $sql, array $params = []) {
        $row = self::query($sql, $params)->fetch(PDO::FETCH_NUM);
        return $row ? $row[0] : null;
    }

    /**
     * Begin a transaction.
     */
    public static function beginTransaction(): void {
        self::pdo()->beginTransaction();
    }

    /**
     * Commit a transaction.
     */
    public static function commit(): void {
        self::pdo()->commit();
    }

    /**
     * Roll back a transaction.
     */
    public static function rollback(): void {
        if (self::pdo()->inTransaction()) {
            self::pdo()->rollBack();
        }
    }

    /**
     * Paginated query helper. Returns ['data' => [], 'total' => int, 'pages' => int].
     */
    public static function paginate(string $sql, array $params, int $page, int $perPage): array {
        // Count total
        $countSql = 'SELECT COUNT(*) FROM (' . $sql . ') AS _ct';
        $total = (int)self::fetchScalar($countSql, $params);

        // Add limit/offset
        $offset = ($page - 1) * $perPage;
        $data = self::fetchAll($sql . ' LIMIT ' . $perPage . ' OFFSET ' . $offset, $params);

        return [
            'data'    => $data,
            'total'   => $total,
            'pages'   => (int)ceil($total / $perPage),
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }
}
