<?php

namespace Addone\BackupBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class BackupManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'object.backup';
    }
    
    public function getDescription()
    {
        return 'Создание резервных копий';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        $manager->addAdminMenu('Резервные копии', $this->container->get('router')->generate('addone_backup_homepage'), 70, $this->container->get('security.context')->getToken()->getUser()->checkAccess('backup_create'), 'Администрирование');
    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        $manager->addRole('backup_create','Создание резервных копий');
     }
     
     public function getContentTypes()
     {
        return array();
     }
     
     public function getTaxonomyType()
     {
         return false;
     }

     public function getTemplateTwig($contentType, $template)
     {
         return 'BasicCmsBundle:Front:null.html.twig';
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         return 'BasicCmsBundle:Front:null.html.twig';
     }
     
     public function getFrontContent($locale, $contentType, $contentId, $result = null)
     {
         return null;
     }
     
     public function setFrontAction($locale, $seoPage, $action)
     {
         return null;
     }
     
     public function getModuleTypes()
     {
         return array();
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result = null)
     {
         return null;
     }
     
    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        return null;
    }

    public function getInfoTitle($contentType, $contentId)
    {
         return null;
    }

    public function getInfoSitemap($contentType, $contentId)
    {
         return null;
    }

//**************************************************
// Поиск по сайту
//**************************************************

    public function getSearchOptions(&$options)
    {
    }
    
    public function getSearchItemCount($options, $searchstring, $locale)
    {
        return 0;
    }
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, &$items)
    {
    }

//****************************************
// Справочная система
//****************************************

    public function getHelpChapters()
    {
        return $array();
    }
    
    public function getHelpContent($page, &$answer)
    {
        return $answer;
    }
    
}
