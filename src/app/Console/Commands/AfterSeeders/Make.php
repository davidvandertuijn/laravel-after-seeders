<?php

namespace Davidvandertuijn\LaravelAfterSeeders\app\Console\Commands\AfterSeeders;

use Davidvandertuijn\LaravelAfterSeeders\Exceptions\ColumnsNotAddedException;
use Davidvandertuijn\LaravelAfterSeeders\Exceptions\TableNotFoundException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class Make extends Command
{
    /**
     * @var string
     */
    protected $signature = 'after-seeders:make {table}';

    /**
     * @var string
     */
    protected $description = 'Make an after seeder';

    /**
     * Handle.
     */
    public function handle(): void
    {
        $table = $this->argument('table');

        $this->components->info(sprintf(
            'Make an after seeder for table "%s".',
            $table
        ));

        try {
            $this->ensureTableExist($table);
        } catch (TableNotFoundException) {
            $this->components->error(sprintf(
                'Table "%s" does not exists.',
                $table
            ));

            return;
        }

        $path = $this->getPath();
        $filename = $this->getFilename($table);
        $columns = $this->getColumns($table);

        try {
            $this->checkColumns($columns);
        } catch (ColumnsNotAddedException) {
            $this->components->error('Columns not added.');

            return;
        }

        $range = $this->getRange($table);
        $records = $this->getRecords($table, $columns, $range);
        $json = $this->getJson($records);

        $this->create($path, $filename, $json);
    }

    /**
     * Check Columns.
     */
    protected function checkColumns(array $columns): void
    {
        if (count($columns) == 0) {
            throw new ColumnsNotAddedException;
        }
    }

    /**
     * Create.
     */
    protected function create(string $path, string $filename, string $json): void
    {
        File::put($path.'/'.$filename, $json);

        $this->components->twoColumnDetail(
            sprintf('<fg=white;options=bold>%s/%s</>',
                $path,
                $filename
            ),
            '<fg=green>DONE</>'
        );
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
     * Get Columns
     */
    protected function getColumns(string $table): array
    {
        $columns = [];

        $this->line(sprintf(
            '<fg=white;options=bold>%s</>',
            sprintf(
                'Columns for table "%s".',
                $table
            )
        ));

        $columnListing = Schema::getColumnListing($table);

        foreach ($columnListing as $column) {
            if ($this->confirm(sprintf(
                'Would you like to add the column "%s" ?',
                $column
            ))) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * Get Date Prefix.
     */
    protected function getDatePrefix(): string
    {
        return date('Y_m_d_His');
    }

    /**
     * Get Filename.
     */
    protected function getFilename(string $table): string
    {
        return $this->getDatePrefix().'_'.$table.'.json';
    }

    /**
     * Get Json.
     */
    protected function getJson(\Illuminate\Support\Collection $records): string
    {
        $records = [];

        $tag = $this->option('tag');

        if (! empty($tag)) {
            $records['TAG'] = $tag;
        }

        $records = array_merge($records, [
            'RECORDS' => $records->toArray(),
        ]);

        return json_encode($records, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * Get Max Id.
     */
    protected function getMaxId(string $table)
    {
        return DB::table($table)->max('id');
    }

    /**
     * Get Path.
     */
    protected function getPath(): string
    {
        return Config::get('after_seeders.path');
    }

    /**
     * Get Range
     */
    protected function getRange(string $table): array
    {
        $this->line(sprintf(
            '<fg=white;options=bold>%s</>',
            sprintf(
                'Select range for table "%s".',
                $table
            )
        ));

        $from = $this->ask('Enter the starting ID', 0);
        $to = $this->ask('Enter the ending ID', $this->getMaxId($table));

        return range($from, $to);
    }

    /**
     * Get Records.
     */
    protected function getRecords(string $table, array $columns, array $range): \Illuminate\Support\Collection
    {
        return DB::table($table)
            ->select($columns)
            ->whereIn('id', $range)
            ->get();
    }
}
