<?php

namespace Davidvandertuijn\LaravelAfterSeeders\app\Console\Commands\AfterSeeders;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class Deploy extends Command
{
    /**
     * @var string
     */
    protected $signature = 'after-seeders:deploy';

    /**
     * @var string
     */
    protected $description = 'Deploy after seeders';

    /**
     * Handle.
     */
    public function handle(): void
    {
        $tags = $this->getTags();

        if (empty($tags)) {
            $this->components->warn('There are no tags available.');

            return;
        }

        foreach ($tags as $tag) {
            $this->components->info(sprintf(
                'Deployment tag "%s"',
                $tag
            ));

            $this->call('after-seeders:seed', [
                '--tag' => $tag,
            ]);
        }
    }

    /**
     * Get Tags.
     */
    public function getTags()
    {
        return Config::get('after_seeders.tags');
    }
}
