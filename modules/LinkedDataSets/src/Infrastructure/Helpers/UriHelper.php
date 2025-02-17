<?php

declare(strict_types=1);

namespace LinkedDataSets\Infrastructure\Helpers;

use Laminas\View\Helper\BasePath;
use Laminas\View\Helper\ServerUrl;

final class UriHelper
{
    private $viewHelperManager;

    private $serverUrl;

    private $basePath;
    public function __construct($viewHelperManager)
    {
        $this->viewHelperManager = $viewHelperManager;
        /** @var ServerUrl serverUrl */
        $this->serverUrl = $viewHelperManager->get('ServerUrl');
        /** @var BasePath basePath */
        $this->basePath = $viewHelperManager->get('BasePath');
    }

    public function constructUri(): string
    {
        $basePath = $this->basePath;

        return $this->serverUrl->getScheme() .
            '://' . $this->serverUrl->getHost() .
            $basePath();
    }
}
