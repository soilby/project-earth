<?php

namespace Shop\GalleryBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class GalleryManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'addone.product.gallery';
    }
    
    public function getDescription()
    {
        return 'Фотогалерея';
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
            $images = array();
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $images = array();
                $postimages = $request->get('images');
                if (is_array($postimages))
                {
                    foreach ($postimages as $item)
                    {
                        if (isset($item['url']) && isset($item['enabled'])) $images[] = array('url' => $item['url'], 'enabled' => $item['enabled']);
                    }
                }
                unset($postimages);
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    foreach ($images as $index => $item)
                    {
                        $this->container->get('cms.cmsManager')->unlockTemporaryFile($item['url']);
                        $imgent = new \Shop\GalleryBundle\Entity\ProductImage();
                        $imgent->setEnabled($item['enabled']);
                        $imgent->setImage($item['url']);
                        $imgent->setOrdering($index);
                        $imgent->setProductId($actionId);
                        $em->persist($imgent);
                        $em->flush();
                        unset($imgent);
                    }
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ShopGalleryBundle:Addone:productCreate.html.twig',array('images'=>$images));
            }
        }
        if ($action == 'productEdit')
        {
            $query = $em->createQuery('SELECT i.enabled, i.image as url FROM ShopGalleryBundle:ProductImage i WHERE i.productId = :id ORDER BY i.ordering')->setParameter('id', $actionId);
            $images = $query->getResult();
            if (!is_array($images)) $images = array();
            $oldimages = array(); foreach ($images as $image) $oldimages[] = $image['url'];
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $images = array();
                $postimages = $request->get('images');
                if (is_array($postimages))
                {
                    foreach ($postimages as $item)
                    {
                        if (isset($item['url']) && isset($item['enabled'])) $images[] = array('url' => $item['url'], 'enabled' => $item['enabled']);
                    }
                }
                unset($postimages);
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    $newimages = array(); foreach ($images as $image) $newimages[] = $image['url'];
                    foreach ($oldimages as $image) if (!in_array($image, $newimages)) @unlink('.'.$image);
                    $query = $em->createQuery('DELETE FROM ShopGalleryBundle:ProductImage i WHERE i.productId = :id')->setParameter('id', $actionId);
                    $query->execute();
                    foreach ($images as $index => $item)
                    {
                        $this->container->get('cms.cmsManager')->unlockTemporaryFile($item['url']);
                        $imgent = new \Shop\GalleryBundle\Entity\ProductImage();
                        $imgent->setEnabled($item['enabled']);
                        $imgent->setImage($item['url']);
                        $imgent->setOrdering($index);
                        $imgent->setProductId($actionId);
                        $em->persist($imgent);
                        $em->flush();
                        unset($imgent);
                    }
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ShopGalleryBundle:Addone:productEdit.html.twig',array('images'=>$images));
            }
        }
        if ($action == 'productDelete')
        {
            if ($actionType == 'validate') return false;
            if ($actionType == 'tab') return null;
            if ($actionType == 'save')
            {
                $query = $em->createQuery('SELECT i.image FROM ShopGalleryBundle:ProductImage i WHERE i.productId = :id')->setParameter('id', $actionId);
                $delpages = $query->getResult();
                if (is_array($delpages))
                {
                    foreach ($delpages as $delpage) @unlink('.'.$delpage['image']);
                }
                $query = $em->createQuery('DELETE FROM ShopGalleryBundle:ProductImage i WHERE i.productId = :id')->setParameter('id', $actionId);
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
             $query = $em->createQuery('SELECT i.image FROM ShopGalleryBundle:ProductImage i WHERE i.enabled != 0 AND i.productId = :id ORDER BY i.ordering')->setParameter('id', $contentId);
             $images = $query->getResult();
             if (!is_array($images)) $images = array();
             $params['images'] = array();
             foreach ($images as $item) 
             {
                 $params['images'][] = $item['image'];
             }
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
