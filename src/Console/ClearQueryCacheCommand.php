<?php

namespace LaravelDoctrine\ORM\Console;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use LaravelDoctrine\ORM\Configuration\Cache\IlluminateCacheProvider;
use LogicException;

class ClearQueryCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'doctrine:clear:query:cache
    {--flush : If defined, cache entries will be flushed instead of deleted/invalidated.}
    {--em= : Clear cache for a specific entity manager }';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Clear all query cache of the various cache drivers.';

    /**
     * Execute the console command.
     *
     * @param ManagerRegistry $registry
     */
    public function handle(ManagerRegistry $registry)
    {
        $names = $this->option('em') ? [$this->option('em')] : $registry->getManagerNames();

        foreach ($names as $name) {
            $em    = $registry->getManager($name);
            $cache = $em->getConfiguration()->getQueryCacheImpl();

            if (!$cache) {
                throw new InvalidArgumentException('No Result cache driver is configured on given EntityManager.');
            }

            if ($cache instanceof IlluminateCacheProvider && $cache->getStore() === "apc") {
                throw new LogicException("Cannot clear APC Cache from Console, its shared in the Webserver memory and not accessible from the CLI.");
            }

            $this->message('Clearing result cache entries for <info>' . $name . '</info> entity manager');

            $result  = $cache->deleteAll();
            $message = ($result) ? 'Successfully deleted cache entries.' : 'No cache entries were deleted.';

            if ($this->option('flush')) {
                $result  = $cache->flushAll();
                $message = ($result) ? 'Successfully flushed cache entries.' : $message;
            }

            $this->info($message);
        }
    }
}
