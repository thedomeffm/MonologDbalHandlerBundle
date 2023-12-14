<?php

declare(strict_types=1);

namespace TheDomeFfm\MonologDbalHandlerBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class MonologDbalHandlerBundle extends AbstractBundle
{
    protected string $extensionAlias = 'thedomeffm_monolog_dbal_handler';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('dbal')
                    ->children()
                        ->scalarNode('log_table_name')->end()
                    ->end()
                ->end() // dbal
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('Resources/config/services.xml');

        if (empty($config)) {
            return;
        }

        $container->services()
            ->get('thedomeffm_monolog_dbal_handler')
            ->arg('$logTableName', $config['dbal']['log_table_name'])
        ;
    }
}
