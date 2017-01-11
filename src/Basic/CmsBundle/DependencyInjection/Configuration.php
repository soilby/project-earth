<?php

namespace Basic\CmsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('basic_cms')->children()
            ->variableNode('captcha_type')->defaultValue("default")->end()
            ->variableNode('captcha_sizex')->defaultValue("200")->end()
            ->variableNode('captcha_sizey')->defaultValue("100")->end()
            ->variableNode('captcha_backcolor')->defaultValue("fff")->end()
            ->variableNode('captcha_color')->defaultValue("000")->end()
            ->booleanNode('captcha_noise')->defaultFalse()->end()
            ->booleanNode('user_unique_email')->defaultTrue()->end()
            ->booleanNode('user_unique_fullname')->defaultTrue()->end();
        return $treeBuilder;
    }
}
