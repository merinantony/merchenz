<?php

namespace App\Services;

use App\Models\Merchant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * TenantDatabaseManager
 *
 * Manages dynamic client-database provisioning, connection switching,
 * and tenant schema migrations in a database-per-tenant multi-tenant SaaS architecture.
 */
class TenantDatabaseManager
{
    /**
     * Get directory where client sqlite databases are stored.
     */
    public function getStorageDirectory(): string
    {
        $dir = database_path('tenants');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Get absolute path to client database.
     */
    public function getDatabasePath(Merchant|int $merchant): string
    {
        $id = $merchant instanceof Merchant ? $merchant->id : $merchant;
        return $this->getStorageDirectory() . DIRECTORY_SEPARATOR . "tenant_{$id}.sqlite";
    }

    /**
     * Provision and migrate a dedicated client database.
     *
     * @return string The absolute database path
     */
    public function createTenantDatabase(Merchant $merchant): string
    {
        $dbPath = $this->getDatabasePath($merchant);

        // Create empty SQLite file if it doesn't already exist
        if (!File::exists($dbPath)) {
            touch($dbPath);
        }

        // Configure tenant connection to point to this new client database
        $this->connectToTenant($merchant);

        // Run isolated tenant schema migrations
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        $dbFileName = "tenant_{$merchant->id}.sqlite";
        $merchant->update(['database_name' => $dbFileName]);

        return $dbPath;
    }

    /**
     * Dynamically point the 'tenant' connection to a merchant's database.
     */
    public function connectToTenant(Merchant $merchant): void
    {
        $dbPath = $this->getDatabasePath($merchant);

        if (!File::exists($dbPath)) {
            touch($dbPath);
        }

        Config::set('database.connections.tenant.database', $dbPath);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    /**
     * Disconnect from current tenant.
     */
    public function disconnectTenant(): void
    {
        DB::purge('tenant');
    }

    /**
     * Run a callback inside the isolated client's database context.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function runInTenantContext(Merchant $merchant, callable $callback)
    {
        $this->connectToTenant($merchant);
        try {
            return $callback();
        } finally {
            $this->disconnectTenant();
        }
    }

    /**
     * Inspect database health, size, and table metrics for Super Admin monitoring.
     *
     * @return array<string, mixed>
     */
    public function getTenantDatabaseStatus(Merchant $merchant): array
    {
        $dbPath = $this->getDatabasePath($merchant);
        $exists = File::exists($dbPath);
        $sizeBytes = $exists ? File::size($dbPath) : 0;

        $tablesCount = 0;
        $isHealthy = false;

        if ($exists) {
            try {
                $this->connectToTenant($merchant);
                $tables = DB::connection('tenant')->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tablesCount = count($tables);
                $isHealthy = $tablesCount > 0;
            } catch (\Throwable $e) {
                $isHealthy = false;
            } finally {
                $this->disconnectTenant();
            }
        }

        return [
            'merchant_id' => $merchant->id,
            'merchant_name' => $merchant->name,
            'company_name' => $merchant->company_name ?? $merchant->name,
            'database_name' => $merchant->database_name ?? "tenant_{$merchant->id}.sqlite",
            'file_path' => $dbPath,
            'exists' => $exists,
            'is_healthy' => $isHealthy,
            'tables_count' => $tablesCount,
            'size_bytes' => $sizeBytes,
            'size_formatted' => $this->formatBytes($sizeBytes),
            'last_modified' => $exists ? date('Y-m-d H:i:s', File::lastModified($dbPath)) : null,
        ];
    }

    /**
     * Format bytes into human-readable string.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $floor = floor($base);

        return round(pow(1024, $base - $floor), $precision) . ' ' . ($units[$floor] ?? 'B');
    }
}

