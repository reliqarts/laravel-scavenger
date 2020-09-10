<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('line_ending', "\n");

    $parameters->set('paths', [__DIR__ . '/src', __DIR__ . '/tests']);

    $parameters->set('sets', ['clean-code', 'psr12']);
};
