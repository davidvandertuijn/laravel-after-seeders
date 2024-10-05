<?php

namespace Davidvandertuijn\LaravelAfterSeeders\app\Console\Commands\AfterSeeders;

use Davidvandertuijn\LaravelAfterSeeders\Exceptions\ColumnNotFoundException;
use Davidvandertuijn\LaravelAfterSeeders\Exceptions\InvalidJsonException;
use Davidvandertuijn\LaravelAfterSeeders\Exceptions\TableNotFoundException;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Seed extends Command
{
    public ConsoleService $consoleService;

    /**
     * @var string
     */
    protected $signature = 'after-seeders:seed {--tag=}';

    /**
     * @var string
     */
    protected $description = 'Seed after seeders';

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
        $this->seedPending($pendingSeeders);
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
            $file = $this->getPath().'/'.$seeder.'.json';

            // Contents

            try {
                $contents = $this->getContents($file);
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

            $records = Arr::get($contents, 'RECORDS', []);
            $columns = $this->getColumns($records);

            // Tag

            $tag = Arr::get($contents, 'TAG');
            if ($tag !== $this->option('tag')) {
                $this->components->twoColumnDetail(
                    $seeder,
                    $tag.' '.sprintf(
                        '<fg=yellow;options=bold>%s</>',
                        'SKIPPED'
                    )
                );

                continue;
            }

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
                    'DONE'
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
    protected function getColumns(array $records): array
    {
        $columns = [];

        foreach ($records as $record) {
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
     * Get Contents.
     */
    protected function getContents(string $file): array
    {
        $contents = file_get_contents($file);

        if (function_exists('json_validate')) {
            $isValid = json_validate($contents);
        } else {
            $isValid = json_decode($contents) !== null && json_last_error() === JSON_ERROR_NONE;
        }

        if (! $isValid) {
            throw new InvalidJsonException;
        }

        $contents = json_decode($contents, true);

        if (! Arr::exists($contents, 'RECORDS')) {
            throw new InvalidJsonException;
        }

        return $contents;
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
        // Remove prefix 'YYYY_MM_DD_XXXXXX_'.
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
            'tag' => $this->option('tag'),
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
     * Seed Pending.
     */
    protected function seedPending(array $seeders): void
    {
        if (count($seeders) == 0) {
            $this->components->warn('There are no after seeders available.');

            return;
        }

        if (! $this->checkPendingSeeders($seeders)) {
            return;
        }

        $this->seedPendingSeeders($seeders);
    }

    /**
     * Seed Pending Seeders.
     */
    protected function seedPendingSeeders(array $seeders): void
    {
        $batch = $this->getNextBatchNumber();

        $this->components->info(sprintf(
            'Seed batch "%s"',
            $batch
        ));

        $skipped = 0;

        foreach ($seeders as $seeder) {
            $table = $this->getTable($seeder);
            $file = $this->getPath().'/'.$seeder.'.json';

            // Contents

            try {
                $contents = $this->getContents($file);
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

            // Tag

            $tag = Arr::get($contents, 'TAG');
            if ($tag !== $this->option('tag')) {
                $skipped++;

                continue;
            }

            $records = Arr::get($contents, 'RECORDS', []);

            $start = microtime(true);
            $this->seed($table, $records);
            $stop = microtime(true);
            $time = ($stop - $start) * 1000;

            $this->log($seeder, $batch);

            $this->components->twoColumnDetail(
                $seeder,
                sprintf(
                    '%s <fg=green;options=bold>%s</>',
                    number_format($time, 2).'ms',
                    'DONE'
                )
            );
        }

        if ($skipped == count($seeders)) {
            $this->components->warn('There are no after seeders available.');
        }
    }

    /**
     * Seed.
     */
    protected function seed(string $table, array $records): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ($records as $record) {
            // Created at

            if (! array_key_exists('created_at', $record)
                && Schema::hasColumn($table, 'created_at')) {
                $record['created_at'] = now();
            }

            // Update or insert

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
