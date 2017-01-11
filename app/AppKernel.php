<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            new JMS\AopBundle\JMSAopBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
            new Liuggio\ExcelBundle\LiuggioExcelBundle(),

            new Basic\CmsBundle\BasicCmsBundle(),
            new Addone\ProfileBundle\AddoneProfileBundle(),
            new Basic\FrontBundle\BasicFrontBundle(),
            //new Template\TestBundle\TemplateTestBundle(),
            new Shop\ProductBundle\ShopProductBundle(),
            //new Shop\GalleryBundle\ShopGalleryBundle(),
            //new Shop\ReferenceBundle\ShopReferenceBundle(),
            //new Shop\ParameterBundle\ShopParameterBundle(),
            new Form\FormBundle\FormFormBundle(),
            new Shop\BasketBundle\ShopBasketBundle(),
            //new Extended\ShopImportBundle\ExtendedShopImportBundle(),
            //new Extended\ShopExportBundle\ExtendedShopExportBundle(),
            //new Shop\GiftBundle\ShopGiftBundle(),
            //new Shop\AttachmentBundle\ShopAttachmentBundle(),
            //new Gallery\GalleryBundle\GalleryGalleryBundle(),
            new Files\FilesBundle\FilesFilesBundle(),
            new Forum\ForumBundle\ForumForumBundle(),
            new Template\MirclubBundle\TemplateMirclubBundle(),
            new Extended\ProjectBundle\ExtendedProjectBundle(),
            new Addone\BackupBundle\AddoneBackupBundle(),
            new Extended\AlertBundle\ExtendedAlertBundle(),
            new Extended\ChatBundle\ExtendedChatBundle(),
            new Extended\KnowledgeBundle\ExtendedKnowledgeBundle(),


        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
