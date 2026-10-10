<?php

declare(strict_types=1);

namespace SilverShop\Search\Backend;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;

/**
 * Registry of search backends. Backends are declared in config (name => Injector service); `active_backend` picks the
 * one in use. The DB backend is registered and active by default, so search works out of the box; installing an engine
 * adapter module adds its backend here and a shop switches to it by setting `active_backend`.
 *
 *   SilverShop\Search\Backend\BackendRegistry:
 *     active_backend: 'db'
 *     backends:
 *       db: '%$SilverShop\Search\Backend\DbSearchBackend'
 */
class BackendRegistry
{
    use Configurable;

    /**
     * @var array<string, string> backend name => Injector service name / class
     */
    private static array $backends = [];

    private static string $active_backend = 'db';

    /**
     * @return array<string, SearchBackend> keyed by backend name
     */
    public function all(): array
    {
        $backends = [];
        foreach ((array) self::config()->get('backends') as $service) {
            if (!$service) {
                continue;
            }
            /** @var SearchBackend $backend */
            $backend = Injector::inst()->get($service);
            $backends[$backend->getName()] = $backend;
        }

        return $backends;
    }

    /**
     * The active backend: the one named by `active_backend` if configured, else the first configured one, else the
     * DB default.
     */
    public function getActive(): SearchBackend
    {
        $all = $this->all();
        $name = (string) self::config()->get('active_backend');

        if ($name && isset($all[$name]) && $all[$name]->isConfigured()) {
            return $all[$name];
        }

        foreach ($all as $backend) {
            if ($backend->isConfigured()) {
                return $backend;
            }
        }

        return Injector::inst()->get(DbSearchBackend::class);
    }
}
