<?php

namespace Shop\AttachmentBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class AttachmentManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'addone.product.attachment';
    }
    
    public function getDescription()
    {
        return 'Файловые вложения';
    }

    public function registerMenu()
    {
        //$manager = $this->container->get('cms.cmsManager');
        
        //$manager->addAdminMenu('Профиль пользователя', $this->container->get('router')->generate('addone_profile_parameter_list'), 1, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('user_profile'), 'Администрирование');
    }
    
    public function registerRoles()
    {
        //$manager = $this->container->get('cms.cmsManager');
        
        //$manager->addRole('user_profile','Изменение полей профиля');
    }
// ************************************
// Обработка административной части
// avtionType (validate, tab, save)
// ************************************
    public function getAdminController($request, $action, $actionId, $actionType)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        if ($action == 'productCreate')
        {
            $attachments = array();
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $attachments = array();
                $postattachments = $request->get('attachments');
                if (is_array($postattachments))
                {
                    foreach ($postattachments as $item)
                    {
                        if (isset($item['fileid']) && isset($item['enabled']) && isset($item['filename'])) $attachments[] = array('fileid' => $item['fileid'], 'filename' => $item['filename'], 'enabled' => $item['enabled']);
                    }
                }
                unset($postattachments);
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    foreach ($attachments as $index => $item)
                    {
                        $attent = $this->container->get('doctrine')->getRepository('ShopAttachmentBundle:ProductAttachment')->find($item['fileid']);
                        if (!empty($attent))
                        {
                            $attent->setEnabled($item['enabled']);
                            $attent->setOrdering($index);
                            $attent->setProductId($actionId);
                            $em->flush();
                        }
                        unset($attent);
                    }
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ShopAttachmentBundle:Addone:productCreate.html.twig',array('attachments'=>$attachments));
            }
        }
        if ($action == 'productEdit')
        {
            $query = $em->createQuery('SELECT a.enabled, a.fileName as filename, a.id as fileid FROM ShopAttachmentBundle:ProductAttachment a WHERE a.productId = :id ORDER BY a.ordering')->setParameter('id', $actionId);
            $attachments = $query->getResult();
            if (!is_array($attachments)) $attachments = array();
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $attachments = array();
                $postattachments = $request->get('attachments');
                if (is_array($postattachments))
                {
                    foreach ($postattachments as $item)
                    {
                        if (isset($item['fileid']) && isset($item['enabled']) && isset($item['filename'])) $attachments[] = array('fileid' => $item['fileid'], 'filename' => $item['filename'], 'enabled' => $item['enabled']);
                    }
                }
                unset($postattachments);
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    foreach ($attachments as $index => $item)
                    {
                        $attent = $this->container->get('doctrine')->getRepository('ShopAttachmentBundle:ProductAttachment')->find($item['fileid']);
                        if (!empty($attent))
                        {
                            $attent->setEnabled($item['enabled']);
                            $attent->setOrdering($index);
                            $attent->setProductId($actionId);
                            $em->flush();
                        }
                        unset($attent);
                    }
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ShopAttachmentBundle:Addone:productEdit.html.twig',array('attachments'=>$attachments));
            }
        }
        if ($action == 'productDelete')
        {
            if ($actionType == 'validate') return false;
            if ($actionType == 'tab') return null;
            if ($actionType == 'save')
            {
                $query = $em->createQuery('SELECT a.attachment FROM ShopAttachmentBundle:ProductAttachment a WHERE a.productId = :id')->setParameter('id', $actionId);
                $attachs = $query->getResult();
                if (is_array($attachs)) 
                {
                    foreach ($attachs as $attach) @unlink('.'.$attach['attachment']);
                }
                $query = $em->createQuery('DELETE FROM ShopAttachmentBundle:ProductAttachment a WHERE a.productId = :id')->setParameter('id', $actionId);
                $query->execute();
            }
        }
        return null;
    }

    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        return null;
    }
// ************************************
// Обработка фронт части
// ************************************
     public function getTemplateTwig($contentType, $template)
     {
         return null;
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         return null;
     }
     
     public function getContentTypes(&$contents)
     {
     }

     public function getFrontContent($locale, $contentType, $contentId, $result, &$params)
     {
         $em = $this->container->get('doctrine')->getEntityManager();
         if ($contentType == 'view')
         {
             $query = $em->createQuery('SELECT a.id, a.fileName FROM ShopAttachmentBundle:ProductAttachment a WHERE a.enabled != 0 AND a.productId = :id ORDER BY a.ordering')->setParameter('id', $contentId);
             $attachments = $query->getResult();
             if (!is_array($attachments)) $attachments = array();
             $params['attachments'] = $attachments;
         }
     }
     
     public function setFrontActionPrepare($locale, $seoPage, $action, &$result)
     {
     }
     
     public function setFrontAction($locale, $seoPage, $action, &$result)
     {
     }
     
     public function getModuleTypes(&$contents)
     {
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result)
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
     
// ************************************
// Обработка таксономии
// ************************************
     public function getTaxonomyParameters(&$params)
     {

     }
     
     public function getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $itemIds, $allparameters, &$queryparameters, &$queryparameterscount, &$select, &$from, &$where, &$order)
     {
         
     }
     
     public function getTaxonomyPostProcess($locale, &$params)
     {
         
     }
     
// ****************************************
// Обработка корзины
// ****************************************
     public function calcBasketInfo (&$basket)
     {
         
     }
     
     public function getBasketJsCalc(&$jsCalc, $products = null)
     {
         
     }
     
     public function getProductOptions($productId, &$options, $locale)
     {
     
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
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, &$start, &$limit, &$items)
    {        
    }
    
//****************************************
// Справочная система
//****************************************

    public function getHelpChapters(&$chapters)
    {
        //$chapters['d1537bd0b66a4289001629ac2e06e742'] = 'Главная страница CMS';
    }
    
    public function getHelpContent($page)
    {
        //if ($page == 'd1537bd0b66a4289001629ac2e06e742') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:index.html.twig',array());
    }
     
}
