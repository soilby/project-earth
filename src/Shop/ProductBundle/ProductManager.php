<?php

namespace Shop\ProductBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class ProductManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'object.product';
    }
    
    public function getDescription()
    {
        return 'Продукция';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Магазин', $this->container->get('router')->generate('shop_product_list'), 1, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('product_list'));
        $manager->addAdminMenu('Создать продукцию', $this->container->get('router')->generate('shop_product_create'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('product_new'), 'Магазин');
        $manager->addAdminMenu('Список продукции', $this->container->get('router')->generate('shop_product_list'), 10, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('product_list'), 'Магазин');
        $manager->addAdminMenu('Настройки валюты', $this->container->get('router')->generate('shop_product_currency_list'), 20, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('product_currencyview'), 'Магазин');
        $manager->addAdminMenu('Страницы сравнения', $this->container->get('router')->generate('shop_product_compare_list'), 30, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('product_compareview'), 'Магазин');
        if ($this->container->has('object.taxonomy'))
        {
            $manager->addAdminMenu('Категории продукции', $this->container->get('router')->generate('basic_cms_taxonomy_list').'?object=object.product', 9999, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_list') | $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_listshow'), 'Магазин');
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) $this->container->get($item)->registerMenu();

    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('product_list','Просмотр списка продукции');
        $manager->addRole('product_viewown','Просмотр собственных продуктов');
        $manager->addRole('product_viewall','Просмотр всех продуктов');
        $manager->addRole('product_new','Создание новых продуктов');
        $manager->addRole('product_editown','Редактирование собственных продуктов');
        $manager->addRole('product_editall','Редактирование всех продуктов');
        $manager->addRole('product_currencyview','Просмотр валюты');
        $manager->addRole('product_currencyedit','Настройка валюты');
        $manager->addRole('product_compareview','Просмотр страниц сравнения продуктов');
        $manager->addRole('product_compareedit','Настройка страниц сравнения продуктов');
        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) $this->container->get($item)->registerRoles();
        
     }
     
     public function getContentTypes()
     {
        $contents = array('view' => 'Просмотр продукта', 'compare' => 'Сравнение продуктов');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) $this->container->get($item)->getContentTypes($contents);
        return $contents;
     }
     
     public function getTaxonomyType()
     {
         return true;
     }
     
     public function getTemplateTwig($contentType, $template)
     {
         if ($contentType == 'view') return 'ShopProductBundle:Front:productview.html.twig';
         if ($contentType == 'compare') return 'ShopProductBundle:Front:productcompare.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
         {
             $result = $this->container->get($item)->getTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         if ($contentType == 'compare') return 'ShopProductBundle:Front:moduleproductcompare.html.twig';
         if ($contentType == 'visited') return 'ShopProductBundle:Front:moduleproductvisited.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
         {
             $result = $this->container->get($item)->getModuleTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }
     
     public function getFrontContent($locale, $contentType, $contentId, $result = null)
     {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT p.id, p.article, p.createDate, p.modifyDate, p.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                       'IF(pl.title is not null, pl.title, p.title) as title, '.
                                       'IF(pl.description is not null, pl.description, p.description) as description, '.
                                       'IF(pl.metaDescription is not null, pl.metaDescription, p.metaDescription) as metaDescription, '.
                                       'IF(pl.metaKeywords is not null, pl.metaKeywords, p.metaKeywords) as metaKeywords, '.
                                       'p.price, p.discount, p.rating, p.height, p.width, p.depth, p.weight, p.manufacturer, p.onStock, '.
                                       'p.template, p.avatar FROM ShopProductBundle:Products p '.
                                       'LEFT JOIN ShopProductBundle:ProductsLocale pl WITH pl.productId = p.id AND pl.locale = :locale '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.
                                       'WHERE p.id = :id AND p.enabled != 0')->setParameter('id', $contentId)->setParameter('locale', $locale);
             $product = $query->getResult();
             if (empty($product)) return null;
             if (isset($product[0])) $params = $product[0]; else return null;
             $query = $em->createQuery('SELECT c.name, c.prefix, c.postfix, c.decimalSigns, c.decimalSep, c.thousandSep, c.main, c.minNote, c.exchangeIndex FROM ShopProductBundle:ProductCurrency c WHERE c.enabled != 0 ORDER BY c.main DESC, c.id ASC');
             $currencies = $query->getResult();
             if (!is_array($currencies)) $currencies = array();
             $params['currencies'] = $currencies;
             $params['priceWithDiscount'] = $params['price'] * (1 - $params['discount'] / 100);
             $params['formatPrice'] = array();
             $params['formatPriceWithDiscount'] = array();
             foreach ($currencies as $currency)
             {
                 $params['formatPrice'][] = $currency['prefix'].number_format(round(($params['price'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                 $params['formatPriceWithDiscount'][] = $currency['prefix'].number_format(round(($params['priceWithDiscount'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
             }
             $options = $this->container->get('object.product')->getProductOptions($contentId, $locale);
             $params['options'] = $options;
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>$params['metaKeywords'], 'description'=>$params['metaDescription'], 'robotindex'=>true);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             // Добавляем корзину просмотренных товаров
             $productIds = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_comparebasket');
             if (!is_array($productIds)) $productIds = array();
             $params['compareBasket'] = $productIds;
             // Запоминаем просмотренный товар
             $productIds = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_visitbasket');
             if (!is_array($productIds)) $productIds = array();
             if (!isset($productIds[0]) || ($productIds[0] != $contentId)) array_unshift($productIds, $contentId);
             if (count($productIds) > 49) $productIds = array_slice($productIds, 0, 49);
             $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_shop_product_visitbasket', $productIds);
             return $params;
         }
         if ($contentType == 'compare')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT c.name, c.productLimit, c.template FROM ShopProductBundle:ProductCompare c WHERE c.id = :id AND c.enabled != 0')->setParameter('id', $contentId);
             $page = $query->getResult();
             if (empty($page)) return null;
             if (isset($page[0])) $params = $page[0]; else return null;
             $params['name'] = $this->container->get('cms.cmsManager')->decodeLocalString($params['name'], $locale, true);
             $productIds = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_comparebasket');
             if (!is_array($productIds)) $productIds = array();
             if (count($productIds) > 0)
             {
                 $query = $em->createQuery('SELECT p.id, p.article, p.createDate, p.modifyDate, p.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                           'IF(pl.title is not null, pl.title, p.title) as title, '.
                                           'IF(pl.description is not null, pl.description, p.description) as description, '.
                                           'p.price, p.discount, p.rating, p.height, p.width, p.depth, p.weight, p.manufacturer, p.onStock, '.
                                           'p.template, p.avatar, sp.url FROM ShopProductBundle:Products p '.
                                           'LEFT JOIN ShopProductBundle:ProductsLocale pl WITH pl.productId = p.id AND pl.locale = :locale '.
                                           'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.
                                           'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'view\' AND sp.contentId = p.id '.
                                           'WHERE p.id IN (:id) AND p.enabled != 0')->setParameter('id', $productIds)->setParameter('locale', $locale)->setMaxResults($params['productLimit']);
                 $products = $query->getResult();
                 if (!is_array($products)) $products = array();
             } else
             {
                 $products = array();
             }
             $query = $em->createQuery('SELECT c.name, c.prefix, c.postfix, c.decimalSigns, c.decimalSep, c.thousandSep, c.main, c.minNote, c.exchangeIndex FROM ShopProductBundle:ProductCurrency c WHERE c.enabled != 0 ORDER BY c.main DESC, c.id ASC');
             $currencies = $query->getResult();
             if (!is_array($currencies)) $currencies = array();
             $params['currencies'] = $currencies;
             foreach ($products as &$product)
             {
                 if (isset($product['url']) && ($product['url'] != '')) $product['url'] = '/'.($locale != '' ? $locale.'/' : '').$product['url'];
                 $product['priceWithDiscount'] = $product['price'] * (1 - $product['discount'] / 100);
                 $product['formatPrice'] = array();
                 $product['formatPriceWithDiscount'] = array();
                 foreach ($currencies as $currency)
                 {
                     $product['formatPrice'][] = $currency['prefix'].number_format(round(($product['price'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                     $product['formatPriceWithDiscount'][] = $currency['prefix'].number_format(round(($product['priceWithDiscount'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                 }
                 $options = $this->container->get('object.product')->getProductOptions($product['id'], $locale);
                 $product['options'] = $options;
             }
             $params['items'] = $products;
             $params['itemCount'] = count($products);
             $params['meta'] = array('title'=>$params['name'], 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         $params = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
         return $params;
     }
     
     public function setFrontAction($locale, $seoPage, $action)
     {
         $result = null;
         if ($action == 'compareadd')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $query = $em->createQuery('SELECT count(p.id) as prodcount FROM ShopProductBundle:Products p WHERE p.id = :id AND p.enabled != 0')->setParameter('id', $id);
             $prodcount = $query->getResult();
             if (isset($prodcount[0]['prodcount'])) $prodcount = $prodcount[0]['prodcount']; else $prodcount = 0;
             $productIds = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_comparebasket');
             if (!is_array($productIds)) $productIds = array();
             if ($prodcount > 0)
             {
                 if (!in_array($id, $productIds)) 
                 {
                     $productIds[] = $id;
                     $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_shop_product_comparebasket', $productIds);
                     $result['status'] = 'OK';
                 } else $result['status'] = 'Already added';
             } else $result['status'] = 'Not found';
             $result['count'] = count($productIds);
             $result['action'] = 'object.product.compareadd';
             $result['actionId'] = $id;
         }
         if ($action == 'compareremove')
         {
             $productIds = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_comparebasket');
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             if (!is_array($productIds)) $productIds = array();
             if ($key = array_search($id, $productIds)) 
             {
                 unset($productIds[$key]); 
                 $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_shop_product_comparebasket', $productIds);
                 $result['status'] = 'OK';
             } else $result['status'] = 'Not found';
             $result['count'] = count($productIds);
             $result['action'] = 'object.product.compareremove';
         }
         if ($action == 'compareclear')
         {
             $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_shop_product_comparebasket', array());
             $result['count'] = 0;
             $result['status'] = 'OK';
             $result['action'] = 'object.product.compareclear';
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) $this->container->get($item)->setFrontAction($locale, $seoPage, $action, $result);
         return $result;
     }
     
     public function getModuleTypes()
     {
         $contents = array('compare' => 'Модуль сравнения товаров', 'visited' => 'Модуль просмотренных товаров');
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) $this->container->get($item)->getModuleTypes($contents);
         return $contents;
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result = null)
     {
         $params = null;
         if ($moduleType == 'compare')
         {
             if (isset($parameters['compareId']))
             {
                 $em = $this->container->get('doctrine')->getEntityManager();
                 $params = array();
                 $query = $em->createQuery('SELECT c.name, c.productLimit, sp.url FROM ShopProductBundle:ProductCompare c '.
                                           'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'compare\' AND sp.contentId = c.id '.
                                           'WHERE c.id = :id AND c.enabled != 0')->setParameter('id', $parameters['compareId']);
                 $page = $query->getResult();
                 if (empty($page)) return null;
                 if (isset($page[0])) $params = $page[0]; else return null;
                 $params['url'] = '/'.($locale != '' ? $locale.'/' : '').$params['url'];
                 $params['name'] = $this->container->get('cms.cmsManager')->decodeLocalString($params['name'], $locale, true);
                 $productIds = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_comparebasket');
                 if (!is_array($productIds)) $productIds = array();
                 if ((count($productIds) > 0) && (is_array($parameters['fields'])) && (count($parameters['fields']) > 0))
                 {
                     $pageparams['sortField'] = '';
                     $pageparams['sortDirection'] = 0;
                     $pageparams['fields'] = $parameters['fields'];
                     $query = $this->getTaxonomyQuery($locale, $pageparams, array(), null, false, $productIds);
                     $query->setMaxResults($params['productLimit']);
                     $products = $query->getResult();
                 } else
                 {
                     $products = array();
                 }
                 $params['items'] = $products;
                 $params['itemCount'] = count($productIds);
                 if (count($products) > 0) $this->getTaxonomyPostProcess($locale, $params);
             }
         }
         if ($moduleType == 'visited')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $params = array();
             $productIds = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_visitbasket');
             if (!is_array($productIds)) $productIds = array();
             if ((count($productIds) > 0) && (is_array($parameters['fields'])) && (isset($parameters['limit'])) && ($parameters['limit'] > 0))
             {
                 if (isset($parameters['unique']) && ($parameters['unique'] != 0)) $productIds = array_unique($productIds);
                 $productIds = array_slice($productIds, 0, $parameters['limit']);
                 $pageparams['sortField'] = '';
                 $pageparams['sortDirection'] = 0;
                 $pageparams['fields'] = $parameters['fields'];
                 $query = $this->getTaxonomyQuery($locale, $pageparams, array(), null, false, $productIds);
                 $productsunsort = $query->getResult();
                 $products = array();
                 foreach ($productIds as $id)
                 {
                     foreach ($productsunsort as $prod) if ($prod['id'] == $id) $products[] = $prod;
                 }
                 unset($productsunsort);
             } else
             {
                 $products = array();
             }
             $params['items'] = $products;
             $params['itemCount'] = count($products);
             if (count($products) > 0) $this->getTaxonomyPostProcess($locale, $params);
         }
         if ($params != null) return $params;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             if (strpos($item,'addone.product.') === 0) $params = $this->container->get($item)->getFrontModule($locale, $seoPage, $moduleType, $parameters, $result);
             if ($params != null) return $params;
         }
         return null;
     }
     
    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        if ($module == 'compare')
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $parameters = array();
            $parameters['compareId'] = '';
            $parameters['fields'] = array();
            if (isset($nullparameters['compareId'])) $parameters['compareId'] = $nullparameters['compareId'];
            if (isset($nullparameters['fields']) && (is_array($nullparameters['fields']))) $parameters['fields'] = $nullparameters['fields'];
            $parameterserror['compareId'] = '';
            $parameterserror['fields'] = '';
            $fieldlist = $this->getTaxonomyParameters();
            $query = $em->createQuery('SELECT c.id, c.name, c.enabled FROM ShopProductBundle:ProductCompare c ORDER BY c.id');
            $comppages = $query->getResult();
            foreach ($comppages as &$comppage) $comppage['name'] = $this->container->get('cms.cmsManager')->decodeLocalString($comppage['name'], 'default', true);
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postparameters = $request->get('parameters');
                if (isset($postparameters['compareId'])) $parameters['compareId'] = $postparameters['compareId'];
                $parameters['fields'] = array();
                if (isset($postparameters['fields']) && (is_array($postparameters['fields']))) 
                {
                    foreach ($postparameters['fields'] as $field) if (isset($fieldlist[$field])) $parameters['fields'][] = $field;
                }
                $search = false;
                foreach ($comppages as $page) if ($page['id'] == $parameters['compareId']) $search = true;
                if ($search == false) {$errors = true; $parameterserror['compareId'] = 'Выберите страницу сравнения';}
            }
            if ($actionType == 'validate')
            {
                return $errors;
            }
            if ($actionType == 'parameters')
            {
                return $parameters;
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ShopProductBundle:Default:moduleCompare.html.twig',array('parameters' => $parameters,'parameterserror' => $parameterserror,'fieldlist' => $fieldlist, 'comppages' => $comppages));
            }
        }
        if ($module == 'visited')
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $parameters = array();
            $parameters['limit'] = '';
            $parameters['unique'] = '';
            $parameters['fields'] = array();
            if (isset($nullparameters['limit'])) $parameters['limit'] = $nullparameters['limit'];
            if (isset($nullparameters['unique'])) $parameters['unique'] = $nullparameters['unique'];
            if (isset($nullparameters['fields']) && (is_array($nullparameters['fields']))) $parameters['fields'] = $nullparameters['fields'];
            $parameterserror['limit'] = '';
            $parameterserror['unique'] = '';
            $parameterserror['fields'] = '';
            $fieldlist = $this->getTaxonomyParameters();
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postparameters = $request->get('parameters');
                if (isset($postparameters['limit'])) $parameters['limit'] = $postparameters['limit'];
                if (isset($postparameters['unique'])) $parameters['unique'] = intval($postparameters['unique']); else $parameters['unique'] = 0;
                $parameters['fields'] = array();
                if (isset($postparameters['fields']) && (is_array($postparameters['fields']))) 
                {
                    foreach ($postparameters['fields'] as $field) if (isset($fieldlist[$field])) $parameters['fields'][] = $field;
                }
                if ((!preg_match('/\d+/ui', $parameters['limit'])) || ($parameters['limit'] < 1) || ($parameters['limit'] > 50)) {$errors = true; $parameterserror['limit'] = 'Количество продуктов должно быть от 1 до 50';}
            }
            if ($actionType == 'validate')
            {
                return $errors;
            }
            if ($actionType == 'parameters')
            {
                return $parameters;
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ShopProductBundle:Default:moduleVisited.html.twig',array('parameters' => $parameters,'parameterserror' => $parameterserror,'fieldlist' => $fieldlist));
            }
        }
        $answer = null;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.product.') === 0) $answer = $this->container->get($item)->getAdminModuleController($request, $module, $actionType, $nullparameters);
            if ($answer != null) return $answer;
        }
        return null;
    }

    
    public function getInfoTitle($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT p.title FROM ShopProductBundle:Products p WHERE p.id = :id')->setParameter('id', $contentId);
             $product = $query->getResult();
             if (isset($product[0]['title']))
             {
                 $title = array();
                 $title['default'] = $product[0]['title'];
                 $query = $em->createQuery('SELECT pl.title, pl.locale FROM ShopProductBundle:ProductsLocale pl WHERE pl.productId = :id')->setParameter('id', $contentId);
                 $productlocale = $query->getResult();
                 if (is_array($productlocale))
                 {
                     foreach ($productlocale as $tpitem) if ($tpitem['title'] != null) $title[$tpitem['locale']] = $tpitem['title'];
                 }
                 return $this->container->get('cms.cmsManager')->encodeLocalString($title);
             }
         }
         if ($contentType == 'compare')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT c.name FROM ShopProductBundle:ProductCompare c WHERE c.id = :id')->setParameter('id', $contentId);
             $compare = $query->getResult();
             if (isset($compare[0]['name'])) return $compare[0]['name'];
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.product.') === 0) $answer = $this->container->get($item)->getInfoTitle($contentType, $contentId);
             if ($answer != null) return $answer;
         }
         return null;
    }

    public function getInfoSitemap($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT p.avatar, p.modifyDate FROM ShopProductBundle:Products p WHERE p.id = :id AND p.enabled != 0')->setParameter('id', $contentId);
             $product = $query->getResult();
             if (isset($product[0]))
             {
                 $info = array();
                 $info['priority'] = '0.5';
                 $info['modifyDate'] = $product[0]['modifyDate'];
                 $info['avatar'] = $product[0]['avatar'];
                 return $info;
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.product.') === 0) $answer = $this->container->get($item)->getInfoSitemap($contentType, $contentId);
             if ($answer != null) return $answer;
         }
         return null;
    }
    
    public function getTaxonomyParameters()
    {
        $params = array();
        $params['createDate'] = array('type'=>'date','ext'=>0,'name'=>'Дата создания');
        $params['modifyDate'] = array('type'=>'date','ext'=>0,'name'=>'Дата изменения');
        $params['createrId'] = array('type'=>'int','ext'=>0,'name'=>'ID автора');
        $params['title'] = array('type'=>'text','ext'=>0,'name'=>'Заголовок');
        $params['article'] = array('type'=>'text','ext'=>0,'name'=>'Артикул');
        $params['description'] = array('type'=>'text','ext'=>0,'name'=>'Описание');
        $params['avatar'] = array('type'=>'text','ext'=>0,'name'=>'Аватар');
        $params['price'] = array('type'=>'int','ext'=>0,'name'=>'Цена');
        $params['discount'] = array('type'=>'int','ext'=>0,'name'=>'Скидка');
        $params['priceWithDiscount'] = array('type'=>'int','ext'=>0,'name'=>'Цена со скидкой');
        $params['rating'] = array('type'=>'int','ext'=>0,'name'=>'Рейтинг');
        $params['height'] = array('type'=>'int','ext'=>0,'name'=>'Высота');
        $params['width'] = array('type'=>'int','ext'=>0,'name'=>'Ширина');
        $params['depth'] = array('type'=>'int','ext'=>0,'name'=>'Глубина');
        $params['weight'] = array('type'=>'int','ext'=>0,'name'=>'Вес');
        $params['manufacturer'] = array('type'=>'text','ext'=>0,'name'=>'Производитель');
        $params['onStock'] = array('type'=>'bool','ext'=>0,'name'=>'На складе');
        $params['createrLogin'] = array('type'=>'text','ext'=>1,'name'=>'Логин автора');
        $params['createrFullName'] = array('type'=>'text','ext'=>1,'name'=>'Имя автора');
        $params['createrAvatar'] = array('type'=>'text','ext'=>1,'name'=>'Аватар автора');
        $params['createrEmail'] = array('type'=>'text','ext'=>1,'name'=>'E-mail автора');
        $params['url'] = array('type'=>'text','ext'=>1,'name'=>'URL страницы');
        $params['categoryId'] = array('type'=>'int','ext'=>1,'name'=>'ID категории');
        $params['categoryTitle'] = array('type'=>'text','ext'=>1,'name'=>'Заголовок категории');
        $params['categoryDescription'] = array('type'=>'text','ext'=>1,'name'=>'Описание категории');
        $params['categoryAvatar'] = array('type'=>'text','ext'=>1,'name'=>'Аватар категории');
        $params['categoryUrl'] = array('type'=>'text','ext'=>1,'name'=>'URL категории');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) $this->container->get($item)->getTaxonomyParameters($params);
        return $params;
    }
    
    public function getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $queryCount = false, $itemIds = null)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        $allparameters = array($pageParameters['sortField']);
        $queryparameters = array();
        foreach ($pageParameters['fields'] as $field) $allparameters[] = $field;
        foreach ($filterParameters as $field) $allparameters[] = $field['parameter'];
        $from = ' FROM ShopProductBundle:Products p';
        if (in_array('createrLogin', $allparameters) || 
            in_array('createrFullName', $allparameters) ||    
            in_array('createrAvatar', $allparameters) ||    
            in_array('createrEmail', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId';}
        if (in_array('title', $allparameters) || 
            in_array('description', $allparameters)) {$from .= ' LEFT JOIN ShopProductBundle:ProductsLocale pl WITH pl.productId = p.id AND pl.locale = :locale'; $queryparameters['locale'] = $locale;}
        if (in_array('url', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'view\' AND sp.contentId = p.id';}
        if (in_array('categoryId', $allparameters) || 
            in_array('categoryTitle', $allparameters) ||    
            in_array('categoryDescription', $allparameters) ||    
            in_array('categoryAvatar', $allparameters) ||    
            in_array('categoryUrl', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks catpl WITH catpl.itemId = p.id AND catpl.taxonomyId IN (SELECT catplt.id FROM BasicCmsBundle:Taxonomies catplt WHERE catplt.enabled != 0 AND catplt.object = \'object.product\') LEFT JOIN BasicCmsBundle:Taxonomies cat WITH cat.id = catpl.taxonomyId';}
        if (in_array('categoryTitle', $allparameters) ||    
            in_array('categoryDescription', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:TaxonomiesLocale catl WITH catl.taxonomyId = cat.id AND catl.locale = :locale'; $queryparameters['locale'] = $locale;}
        if (in_array('categoryUrl', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:SeoPage catsp WITH catsp.contentType = \'object.taxonomy\' AND catsp.contentAction = \'view\' AND catsp.contentId = cat.id';}
        $select = 'SELECT p.id';
        if (in_array('createDate', $pageParameters['fields'])) $select .=',p.createDate';
        if (in_array('modifyDate', $pageParameters['fields'])) $select .=',p.modifyDate';
        if (in_array('createrId', $pageParameters['fields'])) $select .=',p.createrId';
        if (in_array('title', $pageParameters['fields']) || in_array('title', $allparameters)) $select .=',IF(pl.title is not null, pl.title, p.title) as title';
        if (in_array('article', $pageParameters['fields'])) $select .=',p.article';
        if (in_array('description', $pageParameters['fields']) || in_array('description', $allparameters)) $select .=',IF(pl.description is not null, pl.description, p.description) as description';
        if (in_array('avatar', $pageParameters['fields'])) $select .=',p.avatar';
        if (in_array('price', $pageParameters['fields'])) $select .=',p.price';
        if (in_array('discount', $pageParameters['fields'])) $select .=',p.discount';
        if (in_array('priceWithDiscount', $pageParameters['fields']) || in_array('priceWithDiscount', $allparameters)) $select .=',(p.price*(1-IF(p.discount is not null, p.discount, 0)/100)) as priceWithDiscount';
        if (in_array('rating', $pageParameters['fields'])) $select .=',p.rating';
        if (in_array('height', $pageParameters['fields'])) $select .=',p.height';
        if (in_array('width', $pageParameters['fields'])) $select .=',p.width';
        if (in_array('depth', $pageParameters['fields'])) $select .=',p.depth';
        if (in_array('weight', $pageParameters['fields'])) $select .=',p.weight';
        if (in_array('manufacturer', $pageParameters['fields'])) $select .=',p.manufacturer';
        if (in_array('onStock', $pageParameters['fields'])) $select .=',p.onStock';
        if (in_array('createrLogin', $pageParameters['fields'])) $select .=',u.login as createrLogin';
        if (in_array('createrFullName', $pageParameters['fields'])) $select .=',u.fullName as createrFullName';
        if (in_array('createrAvatar', $pageParameters['fields'])) $select .=',u.avatar as createrAvatar';
        if (in_array('createrEmail', $pageParameters['fields'])) $select .=',u.email as createrEmail';
        if (in_array('url', $pageParameters['fields'])) $select .=',sp.url';
        if (in_array('categoryId', $pageParameters['fields'])) $select .=',cat.id as categoryId';
        if (in_array('categoryTitle', $pageParameters['fields']) || in_array('categoryTitle', $allparameters)) $select .=',IF(catl.title is not null, catl.title, cat.title) as categoryTitle';
        if (in_array('categoryDescription', $pageParameters['fields']) || in_array('categoryDescription', $allparameters)) $select .=',IF(catl.description is not null, catl.description, cat.description) as categoryDescription';
        if (in_array('categoryAvatar', $pageParameters['fields'])) $select .=',cat.avatar as categoryAvatar';
        if (in_array('categoryUrl', $pageParameters['fields'])) $select .=',catsp.url as categoryUrl';
        $where = ' WHERE p.enabled != 0';
        $queryparameterscount = 1;
        foreach ($filterParameters as $field) if ($field['active'] == true)
        {
            if ($field['parameter'] == 'createDate')
            {
                $date = null;
                if (preg_match("/^(\d{1,2})\.(\d{2})\.(\d{4})(( (\d{1,2}):(\d{2}))?)$/ui", $field['value'],$matches))
                {
                    $date = new \DateTime();
                    $date->setDate($matches[3], $matches[2], $matches[1]);
                    if ($matches[4] != '') $date->setTime ($matches[6], $matches[7], 0);
                }
                if (preg_match("/^\-(\d+)$/ui", $field['value']))
                {
                    $date = new \DateTime();
                    $date->setTimestamp(time() - intval($field['value']));
                }
                if ($date != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND p.createDate != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND p.createDate = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND p.createDate > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND p.createDate < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND p.createDate >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND p.createDate <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                }
                unset($date);
            }
            if ($field['parameter'] == 'modifyDate')
            {
                $date = null;
                if (preg_match("/^(\d{1,2})\.(\d{2})\.(\d{4})(( (\d{1,2}):(\d{2}))?)$/ui", $field['value'],$matches))
                {
                    $date = new \DateTime();
                    $date->setDate($matches[3], $matches[2], $matches[1]);
                    if ($matches[4] != '') $date->setTime ($matches[6], $matches[7], 0);
                }
                if (preg_match("/^\-(\d+)$/ui", $field['value']))
                {
                    $date = new \DateTime();
                    $date->setTimestamp(time() - intval($field['value']));
                }
                if ($date != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND p.modifyDate != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND p.modifyDate = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND p.modifyDate > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND p.modifyDate < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND p.modifyDate >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND p.modifyDate <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                }
                unset($date);
            }
            if ($field['parameter'] == 'createrId')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND p.createrId != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND p.createrId = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND p.createrId > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND p.createrId < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND p.createrId >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND p.createrId <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'title')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND IF(pl.title is not null, pl.title, p.title) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND IF(pl.title is not null, pl.title, p.title) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND IF(pl.title is not null, pl.title, p.title) like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'article')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND p.article != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND p.article = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND p.article like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'description')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND IF(pl.description is not null, pl.description, p.description) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND IF(pl.description is not null, pl.description, p.description) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND IF(pl.description is not null, pl.description, p.description) like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'avatar')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND p.avatar != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND p.avatar = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND p.avatar like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'price')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND p.price != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND p.price = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND p.price > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND p.price < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND p.price >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND p.price <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'discount')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND p.discount != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND p.discount = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND p.discount > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND p.discount < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND p.discount >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND p.discount <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'priceWithDiscount')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND (p.price*(1-IF(p.discount is not null, p.discount, 0)/100)) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND (p.price*(1-IF(p.discount is not null, p.discount, 0)/100)) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND (p.price*(1-IF(p.discount is not null, p.discount, 0)/100)) > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND (p.price*(1-IF(p.discount is not null, p.discount, 0)/100)) < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND (p.price*(1-IF(p.discount is not null, p.discount, 0)/100)) >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND (p.price*(1-IF(p.discount is not null, p.discount, 0)/100)) <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'rating')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND p.rating != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND p.rating = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND p.rating > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND p.rating < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND p.rating >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND p.rating <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'height')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND p.height != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND p.height = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND p.height > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND p.height < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND p.height >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND p.height <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'width')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND p.width != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND p.width = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND p.width > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND p.width < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND p.width >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND p.width <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'depth')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND p.depth != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND p.depth = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND p.depth > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND p.depth < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND p.depth >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND p.depth <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'weight')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND p.weight != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND p.weight = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND p.weight > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND p.weight < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND p.weight >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND p.weight <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'manufacturer')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND p.manufacturer != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND p.manufacturer = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND p.manufacturer like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'onStock')
            {
                if ($field['value'] != 0) {$where .= ' AND p.onStock != 0';}
                if ($field['value'] == 0) {$where .= ' AND p.onStock = 0';}
            }
            if ($field['parameter'] == 'createrLogin')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND u.login != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND u.login = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND u.login like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'createrFullName')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND u.fullName != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND u.fullName = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND u.fullName like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'createrAvatar')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND u.avatar != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND u.avatar = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND u.avatar like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'createrEmail')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND u.email != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND u.email = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND u.email like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'url')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND sp.url != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND sp.url = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND sp.url like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'categoryId')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND cat.id != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND cat.id = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND cat.id > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND cat.id < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND cat.id >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND cat.id <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'categoryTitle')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND IF(catl.title is not null, catl.title, cat.title) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND IF(catl.title is not null, catl.title, cat.title) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND IF(catl.title is not null, catl.title, cat.title) like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'categoryDescription')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND IF(catl.description is not null, catl.description, cat.description) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND IF(catl.description is not null, catl.description, cat.description) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND IF(catl.description is not null, catl.description, cat.description) like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'categoryAvatar')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND cat.avatar != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND cat.avatar = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND cat.avatar like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'categoryUrl')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND catsp.url != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND catsp.url = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND catsp.url like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
        }
        if (is_array($categoryIds) && (count($categoryIds) > 0))
        {
            $from .= ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks l WITH l.itemId = p.id AND l.taxonomyId IN (:categoryids)';
            $where .= ' AND l.id IS NOT NULL';
            $queryparameters['categoryids'] = $categoryIds;
        }
        if (($itemIds !== null) && (is_array($itemIds)))
        {
            if (count($itemIds) == 0) $itemIds[] = 0;
            $where .= ' AND p.id IN (:itemids)';
            $queryparameters['itemids'] = $itemIds;
        }
        $order = ' ORDER BY p.ordering ASC';
        if ($pageParameters['sortField'] == 'createDate') $order = ' ORDER BY p.createDate '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'modifyDate') $order = ' ORDER BY p.modifyDate '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrId') $order = ' ORDER BY p.createrId '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'title') $order = ' ORDER BY title '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'article') $order = ' ORDER BY p.article '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'description') $order = ' ORDER BY description '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'avatar') $order = ' ORDER BY p.avatar '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'price') $order = ' ORDER BY p.price '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'discount') $order = ' ORDER BY p.discount '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'priceWithDiscount') $order = ' ORDER BY priceWithDiscount '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'rating') $order = ' ORDER BY p.rating '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'height') $order = ' ORDER BY p.height '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'width') $order = ' ORDER BY p.width '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'depth') $order = ' ORDER BY p.depth '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'weight') $order = ' ORDER BY p.weight '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'manufacturer') $order = ' ORDER BY p.manufacturer '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'onStock') $order = ' ORDER BY p.onStock '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrLogin') $order = ' ORDER BY u.login '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrFullName') $order = ' ORDER BY u.fullName '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrAvatar') $order = ' ORDER BY u.avatar '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrEmail') $order = ' ORDER BY u.email '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'url') $order = ' ORDER BY sp.url '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'categoryId') $order = ' ORDER BY cat.id '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'categoryTitle') $order = ' ORDER BY categoryTitle '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'categoryDescription') $order = ' ORDER BY categoryDescription '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'categoryAvatar') $order = ' ORDER BY cat.avatar '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'categoryUrl') $order = ' ORDER BY catsp.url '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        // Обход плагинов
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) $this->container->get($item)->getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $itemIds, $allparameters, $queryparameters, $queryparameterscount, $select, $from, $where, $order);
        // Создание запроса
        if ($queryCount == true)    
        {
            $query = $em->createQuery('SELECT count(DISTINCT p.id) as itemcount'.$from.$where)->setParameters($queryparameters);
        } else
        {
            $query = $em->createQuery($select.$from.$where.' GROUP BY p.id'.$order)->setParameters($queryparameters);
        }
        return $query;
    }
    
    public function getTaxonomyPostProcess($locale, &$params)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        $query = $em->createQuery('SELECT c.name, c.prefix, c.postfix, c.decimalSigns, c.decimalSep, c.thousandSep, c.main, c.minNote, c.exchangeIndex FROM ShopProductBundle:ProductCurrency c WHERE c.enabled != 0 ORDER BY c.main DESC, c.id ASC');
        $currencies = $query->getResult();
        if (!is_array($currencies)) $currencies = array();
        $params['currencies'] = $currencies;
        foreach ($params['items'] as &$proditem)
        {
            if (isset($proditem['url']) && ($proditem['url'] != '')) $proditem['url'] = '/'.($locale != '' ? $locale.'/' : '').$proditem['url'];
            if (isset($proditem['price']))
            {
                $proditem['formatPrice'] = array();
                foreach ($currencies as $currency)
                {
                    $proditem['formatPrice'][] = $currency['prefix'].number_format(round(($proditem['price'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                }
            }
            if (isset($proditem['priceWithDiscount']))
            {
                $proditem['formatPriceWithDiscount'] = array();
                foreach ($currencies as $currency)
                {
                    $proditem['formatPriceWithDiscount'][] = $currency['prefix'].number_format(round(($proditem['priceWithDiscount'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                }
            }
            if (isset($proditem['categoryUrl']) && ($proditem['categoryUrl'] != '')) $proditem['categoryUrl'] = '/'.($locale != '' ? $locale.'/' : '').$proditem['categoryUrl'];
        }
        $productIds = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_comparebasket');
        if (!is_array($productIds)) $productIds = array();
        $params['compareBasket'] = $productIds;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) $this->container->get($item)->getTaxonomyPostProcess($locale, $params);
    }

    public function getTaxonomyExtCatInfo(&$tree)
    {
        $categoryIds = array();
        foreach ($tree as $cat) $categoryIds[] = $cat['id'];
        $em = $this->container->get('doctrine')->getEntityManager();
        if (count($categoryIds) > 0)
        {
            $query = $em->createQuery('SELECT tl.taxonomyId, count(p.id) as productCount, max(p.modifyDate) as modifyDate FROM BasicCmsBundle:TaxonomiesLinks tl '.
                                      'LEFT JOIN ShopProductBundle:Products p WITH p.id = tl.itemId '.
                                      'WHERE tl.taxonomyId IN (:taxonomyids) AND p.enabled != 0 GROUP BY tl.taxonomyId')->setParameter('taxonomyids', $categoryIds);
            $extInfo = $query->getResult();
            $nulltime = new \DateTime('now');
            $nulltime->setTimestamp(0);
            foreach ($tree as &$cat) 
            {
                $cat['productCount'] = 0;
                $cat['productMofidyDate'] = $nulltime;
                foreach ($extInfo as $info)
                    if ($info['taxonomyId'] == $cat['id'])
                    {
                        $cat['productCount'] = $info['productCount'];
                        $cat['productMofidyDate'] = $info['modifyDate'];
                    }
            }
            unset($cat);
        }
    }
       
// *********************************
// Опции товара
// *********************************
    
     public function getProductOptions($productId, $locale)
     {
         $options = array();
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) $this->container->get($item)->getProductOptions($productId, $options, $locale);
         return $options;
     }
    
//**************************************************
// Поиск по сайту
//**************************************************

    public function getSearchOptions(&$options)
    {
        $options['object.product.on'] = 'Искать информацию в продукции';
        $options['object.product.onlypage'] = 'Искать информацию в продукции только со страницей сайта';
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.product.') === 0) $this->container->get($item)->getSearchOptions($options);
        }
    }
    
    public function getSearchItemCount($options, $searchstring, $locale)
    {
        $count = 0;
        if (isset($options['object.product.on']) && ($options['object.product.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT count(p.id) as productcount FROM ShopProductBundle:Products p '.
                                      'LEFT JOIN ShopProductBundle:ProductsLocale pl WITH pl.productId = p.id AND pl.locale = :locale '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'view\' AND sp.contentId = p.id '.
                                      'WHERE (IF(pl.title is not null, pl.title, p.title) LIKE :searchstring OR IF(pl.description is not null, pl.description, p.description) LIKE :searchstring)'.
                                      ((isset($options['object.product.onlypage']) && ($options['object.product.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : ''))->setParameter('searchstring', $searchstring)->setParameter('locale', $locale);
            $productcount = $query->getResult();
            if (isset($productcount[0]['productcount'])) $count += $productcount[0]['productcount'];
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.product.') === 0) $count += $this->container->get($item)->getSearchItemCount($options, $searchstring, $locale);
        }
        return $count;
    }
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, &$items)
    {
        if (isset($options['object.product.on']) && ($options['object.product.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT \'product\' as object, p.id, IF(pl.title is not null, pl.title, p.title) as title, IF(pl.description is not null, pl.description, p.description) as description, '.
                                      'p.modifyDate, p.avatar, p.createrId, u.avatar as createrAvatar, u.login as createrLogin, u.fullName as createrFullName, sp.url FROM ShopProductBundle:Products p '.
                                      'LEFT JOIN ShopProductBundle:ProductsLocale pl WITH pl.productId = p.id AND pl.locale = :locale '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'view\' AND sp.contentId = p.id '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.
                                      'WHERE (IF(pl.title is not null, pl.title, p.title) LIKE :searchstring OR IF(pl.description is not null, pl.description, p.description) LIKE :searchstring)'.
                                      ((isset($options['object.product.onlypage']) && ($options['object.product.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : '').
                                      ($sortdir == 0 ? ' ORDER BY p.modifyDate ASC' : ' ORDER BY p.modifyDate DESC'))->setParameter('searchstring', $searchstring)->setParameter('locale', $locale)->setFirstResult($start)->setMaxResults($limit);
            $result = $query->getResult();
            if (is_array($result))
            {
                $limit = $limit - count($result);
                $start = $start - $this->getSearchItemCount($options, $searchstring, $locale);
                foreach ($result as $item) $items[] = $item;
            }
            unset($result);
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.product.') === 0) $this->container->get($item)->getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, $items);
        }
    }
    
//****************************************
// Справочная система
//****************************************

    public function getHelpChapters()
    {
        $chapters = array();
        //$chapters['d1537bd0b66a4289001629ac2e06e742'] = 'Главная страница CMS';
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.product.') === 0) $this->container->get($item)->getHelpChapters($chapters);
        }
        return $chapters;
    }
    
    public function getHelpContent($page, &$answer)
    {
        //if ($page == 'd1537bd0b66a4289001629ac2e06e742') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:index.html.twig',array());
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.product.') === 0) $answer .= $this->container->get($item)->getHelpContent($page);
        }
        return $answer;
    }
    
}
