<?php
namespace Omeka\Thumbnail;

use Omeka\Thumbnail\Exception;
use Omeka\Thumbnail\ThumbnailerInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Manager implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var ThumbnailerInterface
     */
    protected $thumbnailer;

    /**
     * @param StrategyInterface $strategy
     */
    public function __construct(ThumbnailerInterface $thumbnailer, array $config)
    {
        $this->thumbnailer = $thumbnailer;
    }

    /**
     * Create thumbnail derivatives.
     *
     * @param string $path
     * @param string $storedName
     */
    public function create($path, $storedName)
    {}
}
