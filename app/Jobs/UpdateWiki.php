<?php

namespace App\Jobs;

use App\Libraries\OsuWiki;
use App\Libraries\WikiRedirect;
use App\Models\NewsPost;
use App\Models\Wiki\Image;
use App\Models\Wiki\Page;
use App\Models\Wiki\WikiObject;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class UpdateWiki implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @var string */
    private $oldHash;

    /** @var string */
    private $newHash;

    /**
     * Create a new job instance.
     *
     * @param string $oldHash
     * @param string $newHash
     * @return void
     */
    public function __construct($oldHash, $newHash)
    {
        $this->oldHash = $oldHash;
        $this->newHash = $newHash;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $files = OsuWiki::getUpdatedFiles($this->oldHash, $this->newHash);

        foreach ($files as $file) {
            $status = $file['status'];

            $object = $this->getObject($file['filename']);

            if ($object) {
                // because otherwise we'd have newsposts being pointlessly removed and readded into the database
                if (get_class($object) !== 'NewsPost' || (get_class($object) === 'NewsPost' && $status !== 'removed')) {
                    $object->forget();
                }

                if ($status === 'renamed') {
                    $this->getObject($file['previous_filename'])->forget();
                }

                if ($status !== 'removed') {
                    $object->get();
                }
            }
        }
    }

    /**
     * @return WikiObject
     */
    private function getObject($path)
    {
        $parsed = OsuWiki::parseGithubPath($path);

        if ($parsed['type'] === 'page') {
            return new Page($parsed['path'], $parsed['locale']);
        } elseif ($parsed['type'] === 'image') {
            return new Image($parsed['path']);
        } elseif ($parsed['type'] === 'redirect') {
            return new WikiRedirect();
        } elseif ($parsed['type'] === 'news_post') {
            return NewsPost::lookupAndSync($parsed['slug']);
        }
    }
}