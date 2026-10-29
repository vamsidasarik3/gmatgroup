<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportDatabaseCommand extends Command
{
    protected $signature = 'db:export-for-deploy {--output= : Custom output file path}';
    protected $description = 'Export the database for deployment';

    public function handle(): int
    {
        $this->info('Starting database export for deployment...');

        $dbConfig = config('database.connections.mysql');
        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = $dbConfig['port'] ?? 3306;
        $database = $dbConfig['database'] ?? 'gmatgroup';
        $username = $dbConfig['username'] ?? 'root';
        $password = $dbConfig['password'] ?? '';

        $mysqldump = $this->findMysqldumpBinary();
        if (!$mysqldump) {
            $this->error('mysqldump binary not found. Please ensure MySQL bin directory is in PATH or XAMPP is installed.');
            return 1;
        }

        $exportDir = storage_path('app/deployments/exports');
        if (!File::isDirectory($exportDir)) {
            File::makeDirectory($exportDir, 0755, true);
        }

        $outputPath = $this->option('output');
        if (!$outputPath) {
            $timestamp = date('Ymd_His');
            $outputPath = $exportDir . DIRECTORY_SEPARATOR . "gmatgroup_db_{$timestamp}.sql";
        }

        $this->info("Exporting database '{$database}' to: {$outputPath}");

        $cmdParts = [];
        $cmdParts[] = escapeshellcmd($mysqldump);
        $cmdParts[] = '-h ' . escapeshellarg($host);
        $cmdParts[] = '-P ' . escapeshellarg($port);
        $cmdParts[] = '-u ' . escapeshellarg($username);
        if (!empty($password)) {
            $cmdParts[] = '-p' . escapeshellarg($password);
        }
        $cmdParts[] = '--routines';
        $cmdParts[] = '--triggers';
        $cmdParts[] = '--single-transaction';
        $cmdParts[] = escapeshellarg($database);
        $cmdParts[] = '--result-file=' . escapeshellarg($outputPath);

        $command = implode(' ', $cmdParts);
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($outputPath) || filesize($outputPath) === 0) {
            $this->error('mysqldump failed with return code ' . $returnCode . ': ' . implode("\n", $output));
            return 1;
        }

        // Optionally compress to .gz to speed up SCP transfer
        $gzPath = $outputPath . '.gz';
        if (function_exists('gzopen')) {
            $this->info("Compressing export to {$gzPath}...");
            $fpOut = gzopen($gzPath, 'wb9');
            $fpIn = fopen($outputPath, 'rb');
            if ($fpOut && $fpIn) {
                while (!feof($fpIn)) {
                    gzwrite($fpOut, fread($fpIn, 1024 * 512));
                }
                fclose($fpIn);
                gzclose($fpOut);
                unlink($outputPath);
                $outputPath = $gzPath;
            }
        }

        $size = round(filesize($outputPath) / 1024, 2);
        $this->info("Database exported successfully ({$size} KB).");
        $this->line("BUNDLE_PATH={$outputPath}");

        return 0;
    }

    private function findMysqldumpBinary(): ?string
    {
        $candidates = [
            'mysqldump',
            'G:/xampp/mysql/bin/mysqldump.exe',
            'C:/xampp/mysql/bin/mysqldump.exe',
            'D:/xampp/mysql/bin/mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/bin/mariadb-dump',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // Test if mysqldump is in PATH
        $testCmd = (DIRECTORY_SEPARATOR === '\\') ? 'where mysqldump 2>nul' : 'which mysqldump 2>/dev/null';
        $found = trim((string) shell_exec($testCmd));
        if (!empty($found) && file_exists(strtok($found, "\r\n"))) {
            return strtok($found, "\r\n");
        }

        return null;
    }
}

