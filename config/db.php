<?php
/**
 * Medicine-Shield (Oushodh-Shield)
 * Database Connection Handler
 * Strictly Standard SQL Database (MySQL / MariaDB via PDO)
 */

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'medicine_shield');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'admin123');

class Database {
    private static ?PDO $instance = null;
    public static bool $isDemoMode = false;

    /**
     * Get the PDO MySQL connection instance
     * Auto-tests candidate passwords (admin123, empty, root, password)
     * Auto-creates database from database/schema.sql if missing
     */
    public static function getConnection(): ?PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 2,
        ];

        // Candidate passwords to auto-detect user setup
        $passwordsToTry = [DB_PASS];
        foreach (['', 'root', 'admin123', 'password', '123456'] as $fallback) {
            if (!in_array($fallback, $passwordsToTry, true)) {
                $passwordsToTry[] = $fallback;
            }
        }

        $lastException = null;

        foreach ($passwordsToTry as $pass) {
            try {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
                self::$instance = new PDO($dsn, DB_USER, $pass, $options);
                self::ensureSchemaUpToDate(self::$instance);
                self::$isDemoMode = false;
                return self::$instance;
            } catch (PDOException $e) {
                $lastException = $e;

                // If database does not exist (1049), auto-create from schema.sql
                if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                    try {
                        $serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
                        $serverPdo = new PDO($serverDsn, DB_USER, $pass, $options);
                        $schemaFile = __DIR__ . '/../database/schema.sql';
                        if (file_exists($schemaFile)) {
                            $sql = file_get_contents($schemaFile);
                            $serverPdo->exec($sql);
                            self::$instance = new PDO($dsn, DB_USER, $pass, $options);
                            self::ensureSchemaUpToDate(self::$instance);
                            self::$isDemoMode = false;
                            return self::$instance;
                        }
                    } catch (Exception $ex) {
                        $lastException = $ex;
                    }
                }

                // If access denied (1045), try next password candidate
                if ($e->getCode() == 1045 || strpos($e->getMessage(), 'Access denied') !== false) {
                    continue;
                }

                // If connection refused (2002) or operation not permitted, MySQL server is offline
                if ($e->getCode() == 2002 || strpos($e->getMessage(), 'Connection refused') !== false || strpos($e->getMessage(), 'Operation not permitted') !== false) {
                    break;
                }
            }
        }

        // MySQL is offline: flag demo fallback
        self::$isDemoMode = true;
        return null;
    }

    /**
     * Auto-migrate schema to ensure newly introduced columns exist in live database
     */
    public static function ensureSchemaUpToDate(PDO $pdo): void {
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `medicines`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('approval_status', $cols, true)) {
                $pdo->exec("ALTER TABLE `medicines` ADD COLUMN `approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved'");
            }
            if (!in_array('approved_at', $cols, true)) {
                $pdo->exec("ALTER TABLE `medicines` ADD COLUMN `approved_at` DATETIME DEFAULT NULL");
            }
            if (!in_array('approved_by', $cols, true)) {
                $pdo->exec("ALTER TABLE `medicines` ADD COLUMN `approved_by` VARCHAR(150) DEFAULT NULL");
            }
            if (!in_array('dgda_remarks', $cols, true)) {
                $pdo->exec("ALTER TABLE `medicines` ADD COLUMN `dgda_remarks` TEXT DEFAULT NULL");
            }

            // Auto-backfill: ensure every medicine has at least one active production batch
            $medsWithoutBatches = $pdo->query("
                SELECT m.id, m.brand_name, COALESCE(mfg.code, 'MFG') AS code 
                FROM medicines m 
                LEFT JOIN batches b ON m.id = b.medicine_id 
                LEFT JOIN manufacturers mfg ON m.manufacturer_id = mfg.id 
                WHERE b.id IS NULL
            ")->fetchAll();

            foreach ($medsWithoutBatches as $m) {
                $cleanBrand = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $m['brand_name']), 0, 4));
                if (empty($cleanBrand)) $cleanBrand = 'MED';
                $mfgPrefix = strtoupper(substr($m['code'], 0, 3));
                $batchNum = $mfgPrefix . '-' . $cleanBrand . '-' . date('Y') . 'B1';

                $ins = $pdo->prepare("
                    INSERT INTO batches (medicine_id, batch_number, manufacturing_date, expiry_date, total_units, production_facility, status)
                    VALUES (:mid, :bnum, :mdate, :edate, 10000, 'Authorized Production Unit', 'Active')
                ");
                $ins->execute([
                    ':mid' => $m['id'],
                    ':bnum' => $batchNum,
                    ':mdate' => date('Y-m-d'),
                    ':edate' => date('Y-m-d', strtotime('+2 years'))
                ]);
            }
        } catch (Exception $e) {
            // Silently ignore if table does not exist yet or lacks ALTER permission
        }
    }

    public static function closeConnection(): void {
        self::$instance = null;
    }
}
