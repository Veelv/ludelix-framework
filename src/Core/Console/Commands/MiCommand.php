<?php

namespace Ludelix\Core\Console\Commands;

use Ludelix\Core\Console\Engine\MiEngine;
use Ludelix\Interface\DI\ContainerInterface;

/**
 * Mi Command
 * 
 * Main CLI command handler for Ludelix Framework
 */
class MiCommand
{
    protected MiEngine $engine;
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container ?? new \Ludelix\Core\Container();
        $this->engine = new MiEngine($this->container);
    }

    /**
     * Execute mi command
     */
    public function execute(array $argv): int
    {
        return $this->engine->run($argv);
    }

}
 