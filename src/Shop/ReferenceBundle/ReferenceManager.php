<?php

namespace Shop\ReferenceBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class ReferenceManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'addone.product.reference';
    }
    
    public function getDescription()
    {
        return 'Рекомендуемые продукты';
    }

    public function registerMenu()
    {
        //$manager = $this->container->get('cms.cmsManager');
        
        //$manager->addAdminMenu('Профиль пользователя', $this->container->get('router')->generate('addone_profile_parameter_list'), 1, $this->container->get('security.context')->getToken()->getUser()->checkAccess('user_profile'), 'Администрирование');
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
            $references = array();
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $references = array();
                $postreferences = $request->get('references');
                if (!is_array($postreferences)) $postreferences = array();
                foreach ($postreferences as $item)
                {
                    if (isset($item['id']) && isset($item['enabled']) && isset($item['avatar']) && isset($item['name'])) $references[] = array('id' => $item['id'], 'enabled' => $item['enabled'], 'avatar' => $item['avatar'], 'name' => $item['name']);
                }
                unset($postreferences);
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    foreach ($references as $index => $item)
                    {
                        $refent = new \Shop\ReferenceBundle\Entity\ProductReference();
                        $refent->setEnabled($item['enabled']);
                        $refent->setOrdering($index);
                        $refent->setProductId($actionId);
                        $refent->setReferenceId($item['id']);
                        $em->persist($refent);
                        $em->flush();
                        unset($refent);
                    }
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ShopReferenceBundle:Addone:productCreate.html.twig',array('references'=>$references));
            }
        }
        if ($action == 'productEdit')
        {
            $query = $em->createQuery('SELECT r.enabled, p.id, p.avatar, p.title as name FROM ShopReferenceBundle:ProductReference r '.
                                      'LEFT JOIN ShopProductBundle:Products p WITH p.id = r.referenceId '.
                                      'WHERE r.productId = :id AND p.id IS NOT NULL ORDER BY r.ordering')->setParameter('id', $actionId);
            $references = $query->getResult();
            if (!is_array($references)) $references = array();
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $references = array();
                $postreferences = $request->get('references');
                if (!is_array($postreferences)) $postreferences = array();
                foreach ($postreferences as $item)
                {
                    if (isset($item['id']) && isset($item['enabled']) && isset($item['avatar']) && isset($item['name'])) $references[] = array('id' => $item['id'], 'enabled' => $item['enabled'], 'avatar' => $item['avatar'], 'name' => $item['name']);
                }
                unset($postreferences);
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    $query = $em->createQuery('DELETE FROM ShopReferenceBundle:ProductReference r WHERE r.productId = :id')->setParameter('id', $actionId);
                    $query->execute();
                    foreach ($references as $index => $item)
                    {
                        $refent = new \Shop\ReferenceBundle\Entity\ProductReference();
                        $refent->setEnabled($item['enabled']);
                        $refent->setOrdering($index);
                        $refent->setProductId($actionId);
                        $refent->setReferenceId($item['id']);
                        $em->persist($refent);
                        $em->flush();
                        unset($refent);
                    }
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ShopReferenceBundle:Addone:productEdit.html.twig',array('references'=>$references));
            }
        }
        if ($action == 'productDelete')
        {
            if ($actionType == 'validate') return false;
            if ($actionType == 'tab') return null;
            if ($actionType == 'save')
            {
                $query = $em->createQuery('DELETE FROM ShopReferenceBundle:ProductReference r WHERE r.productId = :id')->setParameter('id', $actionId);
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
             $query = $em->createQuery('SELECT sp.url, p.id, p.createDate, p.modifyDate, p.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                       'IF(pl.title is not null, pl.title, p.title) as title, '.
                                       'IF(pl.description is not null, pl.description, p.description) as description, '.
                                       'p.price, p.discount, p.rating, p.height, p.width, p.depth, p.weight, p.manufacturer, p.onStock, '.
                                       'p.avatar FROM ShopReferenceBundle:ProductReference r '.
                                       'LEFT JOIN ShopProductBundle:Products p WITH p.id = r.referenceId '.
                                       'LEFT JOIN ShopProductBundle:ProductsLocale pl WITH pl.productId = p.id AND pl.locale = :locale '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.
                                       'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'view\' AND sp.contentId = p.id '.
                                       'WHERE r.productId = :id AND r.enabled != 0 AND p.id IS NOT NULL AND p.enabled != 0 ORDER BY r.ordering')->setParameter('id', $contentId)->setParameter('locale', $locale);
             $references = $query->getResult();
             $query = $em->createQuery('SELECT c.name, c.prefix, c.postfix, c.decimalSigns, c.decimalSep, c.thousandSep, c.main, c.minNote, c.exchangeIndex FROM ShopProductBundle:ProductCurrency c WHERE c.enabled != 0 ORDER BY c.main DESC, c.id ASC');
             $currencies = $query->getResult();
             if (!is_array($references)) $references = array();
             foreach ($references as &$reference)
             {
                 $reference['url'] = '/'.($locale != '' ? $locale.'/' : '').$reference['url'];
                 $reference['priceWithDiscount'] = $reference['price'] * (1 - $reference['discount'] / 100);
                 $reference['formatPrice'] = array();
                 $reference['formatPriceWithDiscount'] = array();
                 foreach ($currencies as $currency)
                 {
                     $reference['formatPrice'][] = $currency['prefix'].number_format(round(($reference['price'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                     $reference['formatPriceWithDiscount'][] = $currency['prefix'].number_format(round(($reference['priceWithDiscount'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                 }
             }
             unset($reference);
             $params['references'] = $references;
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
