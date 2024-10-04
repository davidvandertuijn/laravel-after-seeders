<?php

namespace Davidvandertuijn\LaravelAfterSeeders\app\Console\Commands\AfterSeeders;

use Davidvandertuijn\LaravelAfterSeeders\Exceptions\TableNotFound as TableNotFoundException;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
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
    protected $description = 'Make after seeder';

    /**
     * Handle.
     */
    public function handle(): void
    {
        $table = $this->argument('table');

        $this->components->info(sprintf(
            'Make after seeder for table "%s".',
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
        $json = $this->getJson();

        $this->create($path, $filename, $json);
    }

    /**
     * Create.
     */
    protected function create(string $path, string $filename, string $json): void
    {
        try {
            File::put($path.'/'.$filename, $json);
        } catch (Exception $e) {
            $this->components->error($e->getMessage());

            return;
        }

        $this->components->twoColumnDetail(
            sprintf('<fg=white;options=bold>%s/%s</>',
                $path,
                $filename
            ),
            '<fg=green>SUCCESS</>'
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
    protected function getJson(): string
    {
        $records = [
            'RECORDS' => [
                [
                    'name' => 'Example',
                ],
            ],
        ];

        return json_encode($records, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * Get Path.
     */
    protected function getPath(): string
    {
        return Config::get('after_seeders.path');
    }
}
