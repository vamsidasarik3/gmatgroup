<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportDatabaseCommand extends Command
{
    protected $signature = 'db:import-for-deploy {file : Path to the SQL file or SQL.gz file to import} {--force : Force import without prompt}';
    protected $description = 'Import an exported database dump into the active application database';

    public function handle(): int
    {
        $filePath = $this->argument('file');
        if (!file_exists($filePath)) {
            $this->error("Specified file not found: {$filePath}");
            return 1;
        }

        $this->info("Preparing database import from: {$filePath}");

        $dbConfig = config('database.connections.mysql');
        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = $dbConfig['port'] ?? 3306;
        $database = $dbConfig['database'] ?? '';
        $username = $dbConfig['username'] ?? '';
        $password = $dbConfig['password'] ?? '';

        if (empty($database)) {
            $this->error('Target database name not configured in config/database.php or .env.');
            return 1;
        }

        // Handle .gz decompress if needed
        $sqlPath = $filePath;
        $tempDecompressed = null;
        if (str_ends_with(strtolower($filePath), '.gz')) {
            $this->info('Decompressing gzipped SQL dump...');
            $tempDecompressed = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gmatgroup_import_' . uniqid() . '.sql';
            $fpIn = gzopen($filePath, 'rb');
            $fpOut = fopen($tempDecompressed, 'wb');
            if ($fpIn && $fpOut) {
                while (!feof($fpIn)) {
                    fwrite($fpOut, gzread($fpIn, 1024 * 512));
                }
                fclose($fpOut);
                gzclose($fpIn);
                $sqlPath = $tempDecompressed;
            } else {
                $this->error('Failed to decompress .gz archive.');
                return 1;
            }
        }

        $mysqlBinary = $this->findMysqlBinary();
        if (!$mysqlBinary) {
            $this->error('mysql/mariadb CLI client not found.');
            if ($tempDecompressed && file_exists($tempDecompressed)) {
                @unlink($tempDecompressed);
            }
            return 1;
        }

        $this->info("Importing into database '{$database}' on host '{$host}:{$port}' using user '{$username}'...");

        $cmdParts = [];
        $cmdParts[] = escapeshellcmd($mysqlBinary);
        $cmdParts[] = '-h ' . escapeshellarg($host);
        $cmdParts[] = '-P ' . escapeshellarg($port);
        $cmdParts[] = '-u ' . escapeshellarg($username);
        if (!empty($password)) {
            $cmdParts[] = '-p' . escapeshellarg($password);
        }
        $cmdParts[] = escapeshellarg($database);
        $cmdParts[] = '< ' . escapeshellarg($sqlPath);

        $command = implode(' ', $cmdParts);
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($tempDecompressed && file_exists($tempDecompressed)) {
            @unlink($tempDecompressed);
        }

        if ($returnCode !== 0) {
            $this->error('Database import failed with exit code ' . $returnCode . ': ' . implode("\n", $output));
            return 1;
        }

        $this->info('Database imported successfully!');
        return 0;
    }

    private function findMysqlBinary(): ?string
    {
        $candidates = [
            'mariadb',
            'mysql',
            '/usr/bin/mariadb',
            '/usr/bin/mysql',
            'G:/xampp/mysql/bin/mysql.exe',
            'C:/xampp/mysql/bin/mysql.exe',
            'D:/xampp/mysql/bin/mysql.exe',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        $testCmd = (DIRECTORY_SEPARATOR === '\\') ? 'where mysql 2>nul' : 'which mariadb 2>/dev/null || which mysql 2>/dev/null';
        $found = trim((string) shell_exec($testCmd));
        if (!empty($found) && file_exists(strtok($found, "\r\n"))) {
            return strtok($found, "\r\n");
        }

        return null;
    }
}

