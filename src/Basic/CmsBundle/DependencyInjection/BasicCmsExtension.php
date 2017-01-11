<?php

namespace Basic\CmsBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class BasicCmsExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('captcha_type', $config['captcha_type']);
        $container->setParameter('captcha_sizex', $config['captcha_sizex']);
        $container->setParameter('captcha_sizey', $config['captcha_sizey']);
        $container->setParameter('captcha_backcolor', $config['captcha_backcolor']);
        $container->setParameter('captcha_color', $config['captcha_color']);
        $container->setParameter('captcha_noise', $config['captcha_noise']);
        $container->setParameter('user_unique_email', $config['user_unique_email']);
        $container->setParameter('user_unique_fullname', $config['user_unique_fullname']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
