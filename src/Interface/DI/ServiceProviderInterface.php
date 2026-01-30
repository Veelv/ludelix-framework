<?php

namespace Ludelix\Interface\DI;

interface ServiceProviderInterface
{
    public function register(ContainerInterface $container): void;

    public function boot(ContainerInterface $container): void;
}
