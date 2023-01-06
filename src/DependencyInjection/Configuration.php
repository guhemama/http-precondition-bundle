<?php

declare(strict_types=1);

namespace Guhemama\HttpPreconditionBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('guhemama_http_precondition');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('expression_language')
                    ->defaultValue('guhemama.http_precondition.default_expression_language')
                    ->info('Instance of \Symfony\Component\ExpressionLanguage\ExpressionLanguage.')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
