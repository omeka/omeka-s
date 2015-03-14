<?php
namespace Omeka\Thumbnailer;

use Omeka\Thumbnailer\Exception;
use Omeka\Thumbnailer\StrategyInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Thumbnailer implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var StrategyInterface
     */
    protected $strategy;

    /**
     * @param StrategyInterface $strategy
     */
    public function __construct(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * Create thumbnail derivatives.
     *
     * @param string $path
     * @param string $baseName
     */
    public function create($path, $baseName)
    {
        $strategy = $this->getStrategy();
        // Check whether Omeka can generally create a thumbnail of the file.
        if (!$this->canCreate($path)) {
            return;
        }
        // Check whether the strategy can create a thumbnail of the file.
        if (!$this->strategy->canCreate()) {
            return;
        }
    }

    public function canCreate($path)
    {
        return true;
    }
}
