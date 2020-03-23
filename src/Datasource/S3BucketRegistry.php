<?php
declare(strict_types=1);

namespace S3Bucket\Datasource;

use Cake\Core\App;
use Cake\Core\ObjectRegistry;

class S3BucketRegistry extends ObjectRegistry
{
    /**
     * @var \S3Bucket\Datasource\S3BucketRegistry
     */
    protected static $_instance;

    /**
     * Contains a list of locations where bucket classes should be looked for.
     *
     * @var array
     */
    protected $locations = [];

    public function __construct(?array $locations = null)
    {
        if ($locations === null) {
            $locations = [
                'Model/S3Bucket',
            ];
        }

        foreach ($locations as $location) {
            $this->addLocation($location);
        }
    }

    public static function init(?array $locations = null)
    {
        if (static::$_instance === null) {
            static::$_instance = new S3BucketRegistry($locations);
        }

        return static::$_instance;
    }

    public function addLocation(string $location)
    {
        $location = str_replace('\\', '/', $location);
        $this->locations[] = trim($location, '/');

        return $this;
    }

    /**
     * @inheritDoc
     * @return \S3Bucket\Datasource\S3Bucket
     */
    public function get(string $name): S3Bucket
    {
        try {
            if (!isset($this->_loaded[$name])) {
                $this->load($name);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Unknown object "%s"', $name));
        }

        return parent::get($name);
    }

    /**
     * @inheritDoc
     */
    protected function _resolveClassName(string $class): ?string
    {
        foreach ($this->locations as $location) {
            $className = App::className($class, $location);
            if ($className !== null) {
                return $className;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    protected function _throwMissingClassError(string $class, ?string $plugin): void
    {
        if (is_null($plugin)) {
            throw new \RuntimeException(sprintf('Unknown object "%s"', $class));
        } else {
            throw new \RuntimeException(sprintf('Unknown object "%s" in "%s"', $class, $plugin));
        }
    }

    /**
     * @inheritDoc
     */
    protected function _create($class, string $alias, array $config)
    {
        return new $class($config);
    }
}
