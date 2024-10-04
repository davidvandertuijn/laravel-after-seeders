<?php

namespace Davidvandertuijn\LaravelAfterSeeders\app\Console\Commands\AfterSeeders;

use Davidvandertuijn\LaravelAfterSeeders\Exceptions\ColumnNotFound as ColumnNotFoundException;
use Davidvandertuijn\LaravelAfterSeeders\Exceptions\InvalidJson as InvalidJsonException;
use Davidvandertuijn\LaravelAfterSeeders\Exceptions\TableNotFound as TableNotFoundException;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Run extends Command
{
    /**
     * @var string
     */
    protected $signature = 'after-seeders:run';

    /**
     * @var string
     */
    protected $description = 'Running after seeders';

    /**
     * Handle.
     */
    public function handle(): void
    {
        $path = $this->getPath();
        $files = $this->getSeederFiles($path);
        $seederNames = $this->getSeederNames($files);
        $ran = $this->getRan();
        $pendingSeeders = $this->pendingSeeders($seederNames, $ran);

        $this->runPending($pendingSeeders);
    }

    /**
     * Check Pending Seeders.
     */
    protected function checkPendingSeeders(array $seeders): bool
    {
        $errors = 0;

        $this->components->info(sprintf(
            'Check after seeders',
        ));

        foreach ($seeders as $seeder) {
            $table = $this->getTable($seeder);

            // Ensure table exist

            try {
                $this->ensureTableExist($table);
            } catch (TableNotFoundException) {
                $errors++;

                $this->components->error(sprintf(
                    'Table "%s" does not exists.',
                    $table
                ));

                continue;
            }

            $file = $this->getPath().'/'.$seeder.'.json';

            // Get records

            try {
                $records = $this->getRecords($file);
            } catch (InvalidJsonException) {
                $errors++;

                $this->components->twoColumnDetail(
                    $seeder,
                    sprintf(
                        '<fg=red;options=bold>%s</>',
                        'ERROR'
                    )
                );

                $this->components->error('Invalid JSON');

                continue;
            }

            $columns = $this->getColumns($records);

            // Ensure columns exist

            try {
                $this->ensureColumnsExist($table, $columns);
            } catch (ColumnNotFoundException $e) {
                $errors++;

                $this->components->twoColumnDetail(
                    $seeder,
                    sprintf(
                        '<fg=red;options=bold>%s</>',
                        'ERROR'
                    )
                );

                $this->components->error(sprintf(
                    'Column "%s" not exists.',
                    $e->getMessage()
                ));

                continue;
            }

            $this->components->twoColumnDetail(
                $seeder,
                sprintf(
                    '<fg=green;options=bold>%s</>',
                    'SUCCESS'
                )
            );
        }

        return $errors == 0;
    }

    /**
     * Ensure Columns Exist.
     */
    protected function ensureColumnsExist(string $table, array $columns): void
    {
        $exists = [];

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                throw new ColumnNotFoundException($column);
            } else {
                $exists[] = $column;
            }
        }

        sort($exists);
    }

    /**
     * Ensure Table Exist.
     */
    protected function ensureTableExist(string $table): void
    {
        if (! Schema::hasTable($table)) {
            throw new TableNotFoundException;
        }
    }

    /**
     * Get Columns.
     */
    protected function getColumns(&$records): array
    {
        $columns = [];

        foreach ($records['RECORDS'] as $record) {
            foreach (array_keys($record) as $column) {
                if (! in_array($column, $columns)) {
                    $columns[] = $column;
                }
            }
        }

        return $columns;
    }

    /**
     * Get Next Batch Number.
     */
    protected function getNextBatchNumber()
    {
        return DB::table('after_seeders')->max('batch') + 1;
    }

    /**
     * Get Path.
     */
    protected function getPath(): string
    {
        return Config::get('after_seeders.path');
    }

    /**
     * Get Ran.
     */
    protected function getRan(): array
    {
        return DB::table('after_seeders')
            ->orderBy('batch', 'asc')
            ->orderBy('seeder', 'asc')
            ->pluck('seeder')
            ->all();
    }

    /**
     * Get Records.
     */
    protected function getRecords(string $file): array
    {
        $records = file_get_contents($file);

        if (function_exists('json_validate')) {
            $isValid = json_validate($records);
        } else {
            $isValid = json_decode($records) !== null && json_last_error() === JSON_ERROR_NONE;
        }

        if (! $isValid) {
            throw new InvalidJsonException;
        }

        $records = json_decode($records, true);

        if (! Arr::exists($records, 'RECORDS')) {
            throw new InvalidJsonException;
        }

        return $records;
    }

    /**
     * Get Seeder Files.
     */
    protected function getSeederFiles(string $path): array
    {
        return glob($path.'/*.json');
    }

    /**
     * Get Seeder Name.
     */
    protected function getSeederName(string $path): string
    {
        // Remove .json
        return str_replace('.json', '', basename($path));
    }

    /**
     * Get Seeder Names.
     */
    protected function getSeederNames(array $files): array
    {
        $seederNames = [];

        foreach ($files as $path) {
            $seederNames[] = $this->getSeederName($path);
        }

        return $seederNames;
    }

    /**
     * Get Table.
     */
    protected function getTable(string $seeder): string
    {
        // Remove Prefix 'YYYY_MM_DD_XXXXXX_'
        $prefix = substr($seeder, 0, 18);

        return str_replace($prefix, '', $seeder);
    }

    /**
     * Log.
     */
    protected function log(string $seeder, int $batch)
    {
        DB::table('after_seeders')->insert([
            'seeder' => $seeder,
            'batch' => $batch,
            'created_at' => now(),
        ]);
    }

    /**
     * Pending Seeders.
     */
    protected function pendingSeeders(array $seederNames, array $ran): array
    {
        return array_diff($seederNames, $ran);
    }

    /**
     * Run Pending.
     */
    protected function runPending(array $seeders): void
    {
        if (count($seeders) == 0) {
            $this->components->warn('There are no after seeders available.');

            return;
        }

        if (! $this->checkPendingSeeders($seeders)) {
            return;
        }

        $this->runPendingSeeders($seeders);
    }

    /**
     * Run Pending Seeders.
     */
    protected function runPendingSeeders(array $seeders): void
    {
        $batch = $this->getNextBatchNumber();

        $this->components->info(sprintf(
            'Run batch "%s"',
            $batch
        ));

        foreach ($seeders as $seeder) {
            $table = $this->getTable($seeder);
            $file = $this->getPath().'/'.$seeder.'.json';

            try {
                $records = $this->getRecords($file);
            } catch (InvalidJsonException) {
                $this->components->twoColumnDetail(
                    $seeder,
                    sprintf(
                        '<fg=red;options=bold>%s</>',
                        'ERROR'
                    )
                );

                $this->components->error('Invalid JSON');

                continue;
            }

            $this->components->twoColumnDetail(
                $seeder,
                sprintf(
                    '<fg=green;options=bold>%s</>',
                    'SUCCESS'
                )
            );

            $this->seed($table, $records);
            $this->log($seeder, $batch);
        }
    }

    /**
     * Seed.
     */
    protected function seed(string $table, array &$records): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ($records['RECORDS'] as $record) {
            // Created At

            if (! array_key_exists('created_at', $record)
                && Schema::hasColumn($table, 'created_at')) {
                $record['created_at'] = now();
            }

            // Update Or Insert

            if (Arr::exists($record, 'id')) {
                DB::table($table)->updateOrInsert(
                    [
                        'id' => $record['id'],
                    ],
                    $record
                );
            } else {
                DB::table($table)->insert($record);
            }
        }

        Schema::enableForeignKeyConstraints();
    }
}
