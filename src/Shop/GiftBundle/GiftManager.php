<?php

namespace Shop\GiftBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class GiftManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'addone.product.gift';
    }
    
    public function getDescription()
    {
        return 'Подарки к продукту';
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
            $gifts = array();
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $gifts = array();
                $postgifts = $request->get('gifts');
                if (!is_array($postgifts)) $postgifts = array();
                foreach ($postgifts as $item)
                {
                    if (isset($item['id']) && isset($item['enabled']) && isset($item['avatar']) && isset($item['name'])) $gifts[] = array('id' => $item['id'], 'enabled' => $item['enabled'], 'avatar' => $item['avatar'], 'name' => $item['name']);
                }
                unset($postgifts);
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    foreach ($gifts as $index => $item)
                    {
                        $giftent = new \Shop\GiftBundle\Entity\ProductGift();
                        $giftent->setEnabled($item['enabled']);
                        $giftent->setOrdering($index);
                        $giftent->setProductId($actionId);
                        $giftent->setGiftId($item['id']);
                        $em->persist($giftent);
                        $em->flush();
                        unset($giftent);
                    }
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ShopGiftBundle:Addone:productCreate.html.twig',array('gifts'=>$gifts));
            }
        }
        if ($action == 'productEdit')
        {
            $query = $em->createQuery('SELECT g.enabled, p.id, p.avatar, p.title as name FROM ShopGiftBundle:ProductGift g '.
                                      'LEFT JOIN ShopProductBundle:Products p WITH p.id = g.giftId '.
                                      'WHERE g.productId = :id AND p.id IS NOT NULL ORDER BY g.ordering')->setParameter('id', $actionId);
            $gifts = $query->getResult();
            if (!is_array($gifts)) $gifts = array();
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $gifts = array();
                $postgifts = $request->get('gifts');
                if (!is_array($postgifts)) $postgifts = array();
                foreach ($postgifts as $item)
                {
                    if (isset($item['id']) && isset($item['enabled']) && isset($item['avatar']) && isset($item['name'])) $gifts[] = array('id' => $item['id'], 'enabled' => $item['enabled'], 'avatar' => $item['avatar'], 'name' => $item['name']);
                }
                unset($postgifts);
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    $query = $em->createQuery('DELETE FROM ShopGiftBundle:ProductGift g WHERE g.productId = :id')->setParameter('id', $actionId);
                    $query->execute();
                    foreach ($gifts as $index => $item)
                    {
                        $giftent = new \Shop\GiftBundle\Entity\ProductGift();
                        $giftent->setEnabled($item['enabled']);
                        $giftent->setOrdering($index);
                        $giftent->setProductId($actionId);
                        $giftent->setGiftId($item['id']);
                        $em->persist($giftent);
                        $em->flush();
                        unset($giftent);
                    }
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ShopGiftBundle:Addone:productEdit.html.twig',array('gifts'=>$gifts));
            }
        }
        if ($action == 'productDelete')
        {
            if ($actionType == 'validate') return false;
            if ($actionType == 'tab') return null;
            if ($actionType == 'save')
            {
                $query = $em->createQuery('DELETE FROM ShopGiftBundle:ProductGift g WHERE g.productId = :id')->setParameter('id', $actionId);
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
/*         $em = $this->container->get('doctrine')->getEntityManager();
         if ($contentType == 'view')
         {
             $query = $em->createQuery('SELECT sp.url, p.id, p.createDate, p.modifyDate, p.createrId, u.login as createrLogin, u.fullName as createrFullName, '.
                                       'IF(pl.title is not null, pl.title, p.title) as title, '.
                                       'IF(pl.description is not null, pl.description, p.description) as description, '.
                                       'p.price, p.discount, p.rating, p.height, p.width, p.depth, p.weight, p.manufacturer, p.onStock, '.
                                       'p.avatar FROM ShopGiftBundle:ProductGift g '.
                                       'LEFT JOIN ShopProductBundle:Products p WITH p.id = g.giftId '.
                                       'LEFT JOIN ShopProductBundle:ProductsLocale pl WITH pl.productId = p.id AND pl.locale = :locale '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.
                                       'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'view\' AND sp.contentId = p.id '.
                                       'WHERE g.productId = :id AND g.enabled != 0 AND p.id IS NOT NULL AND p.enabled != 0 ORDER BY g.ordering')->setParameter('id', $contentId)->setParameter('locale', $locale);
             $gifts = $query->getResult();
             $query = $em->createQuery('SELECT c.name, c.prefix, c.postfix, c.decimalSigns, c.decimalSep, c.thousandSep, c.main, c.minNote, c.exchangeIndex FROM ShopProductBundle:ProductCurrency c WHERE c.enabled != 0 ORDER BY c.main DESC, c.id ASC');
             $currencies = $query->getResult();
             if (!is_array($gifts)) $gifts = array();
             foreach ($gifts as &$gift)
             {
                 $gift['url'] = '/'.($locale != '' ? $locale.'/' : '').$gift['url'];
                 $gift['priceWithDiscount'] = $gift['price'] * (1 - $gift['discount'] / 100);
                 $gift['formatPrice'] = array();
                 $gift['formatPriceWithDiscount'] = array();
                 foreach ($currencies as $currency)
                 {
                     $gift['formatPrice'][] = $currency['prefix'].number_format(round(($gift['price'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                     $gift['formatPriceWithDiscount'][] = $currency['prefix'].number_format(round(($gift['priceWithDiscount'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                 }
             }
             unset($gift);
             $params['gifts'] = $gifts;
         }*/
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
/*         $productIds = array();
         foreach ($basket['products'] as $product)
         {
             $productIds[] = $product['id'];
         }
         $em = $this->container->get('doctrine')->getEntityManager();
         $query = $em->createQuery('SELECT g.id, g.productId, g.giftId, p.price, p.discount FROM ShopGiftBundle:ProductGift g '.
                                   'LEFT JOIN ShopProductBundle:Products p WITH p.id = g.giftId '.
                                   'WHERE g.productId IN (:ids) AND g.giftId IN (:ids) AND g.enabled != 0 AND p.id IS NOT NULL')->setParameter('ids', $productIds);
         $gifts = $query->getResult();
         $applygifts = array();
         $applyproducts = array();
         $discount = 0;
         foreach ($gifts as $giftitem)
         {
             $findcount = 0;
             foreach ($basket['products'] as $product) if ($product['id'] == $giftitem['giftId']) $findcount = $product['count'];
             if ((!in_array($giftitem['productId'], $applyproducts)) && (!in_array($giftitem['productId'], $applygifts)) && (!in_array($giftitem['giftId'], $applygifts)) && ($findcount > 0))
             {
                 $discount -= $giftitem['price'] * (1 - $giftitem['discount'] / 100);
                 $applygifts[] = $giftitem['giftId'];
                 $applyproducts[] = $giftitem['productId'];
             }
         }
         $basket['giftsId'] = $applygifts;
         $basket['summInfo'][] = array('name'=>$this->getName(), 'description'=>$this->getDescription(), 'orderPrice'=>$discount);*/
     }

     public function getBasketJsCalc(&$jsCalc, $products = null)
     {
/*         $productIds = array();
         foreach ($products as $product)
         {
             $productIds[] = $product['id'];
         }
         $em = $this->container->get('doctrine')->getEntityManager();
         $query = $em->createQuery('SELECT g.id, g.productId, g.giftId, p.price, p.discount FROM ShopGiftBundle:ProductGift g '.
                                   'LEFT JOIN ShopProductBundle:Products p WITH p.id = g.giftId '.
                                   'WHERE g.productId IN (:ids) AND g.giftId IN (:ids) AND g.enabled != 0 AND p.id IS NOT NULL')->setParameter('ids', $productIds);
         $gifts = $query->getResult();
         $jsCalc .=
'    function in_array(p_val, arr)'."\r\n".
'    {'."\r\n".
'        for(var i = 0, l = arr.length; i < l; i++)  '."\r\n".
'        {'."\r\n".
'            if(arr[i] == p_val) '."\r\n".
'            {'."\r\n".
'                return true;'."\r\n".
'            }'."\r\n".
'        }'."\r\n".
'        return false;'."\r\n".
'    }'."\r\n";
         $jsCalc .= 
'   basket[\'giftsId\'] = [];'."\r\n".
'   basket[\'giftsOutId\'] = [];'."\r\n".
'   var giftdiscount = 0;'."\r\n";
         foreach ($gifts as $giftitem)
         {
             $jsCalc .=
'   if ((!in_array('.$giftitem['productId'].', basket[\'giftsOutId\'])) && (!in_array('.$giftitem['productId'].', basket[\'giftsId\'])) && (!in_array('.$giftitem['giftId'].', basket[\'giftsId\'])) && (basket[\'products\']['.$giftitem['giftId'].'][\'count\'] > 0))'."\r\n".             
'   {'."\r\n".             
'       giftdiscount -= '.($giftitem['price'] * (1 - $giftitem['discount'] / 100)).';'."\r\n".             
'       basket[\'giftsId\'][basket[\'giftsId\'].length] = '.$giftitem['giftId'].';'."\r\n".             
'       basket[\'giftsOutId\'][basket[\'giftsOutId\'].length] = '.$giftitem['productId'].';'."\r\n".             
'   }'."\r\n";
         }
         $jsCalc .=
'   basket[\'summInfo\'][\''.$this->getName().'\'] = giftdiscount;'."\r\n";*/
     }

     public function getProductOptions($productId, &$options, $locale)
     {
         $em = $this->container->get('doctrine')->getEntityManager();
         $query = $em->createQuery('SELECT sp.url, p.id, p.createDate, p.modifyDate, p.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                   'IF(pl.title is not null, pl.title, p.title) as title, '.
                                   'IF(pl.description is not null, pl.description, p.description) as description, '.
                                   'p.price, p.discount, p.rating, p.height, p.width, p.depth, p.weight, p.manufacturer, p.onStock, '.
                                   'p.avatar FROM ShopGiftBundle:ProductGift g '.
                                   'LEFT JOIN ShopProductBundle:Products p WITH p.id = g.giftId '.
                                   'LEFT JOIN ShopProductBundle:ProductsLocale pl WITH pl.productId = p.id AND pl.locale = :locale '.
                                   'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.
                                   'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'view\' AND sp.contentId = p.id '.
                                   'WHERE g.productId = :id AND g.enabled != 0 AND p.id IS NOT NULL AND p.enabled != 0 ORDER BY g.ordering')->setParameter('id', $productId)->setParameter('locale', $locale);
         $gifts = $query->getResult();
         $query = $em->createQuery('SELECT c.name, c.prefix, c.postfix, c.decimalSigns, c.decimalSep, c.thousandSep, c.main, c.minNote, c.exchangeIndex FROM ShopProductBundle:ProductCurrency c WHERE c.enabled != 0 ORDER BY c.main DESC, c.id ASC');
         $currencies = $query->getResult();
         $optionitems = array();
         if (!is_array($gifts)) $gifts = array();
         foreach ($gifts as $gift)
         {
             $gift['url'] = '/'.($locale != '' ? $locale.'/' : '').$gift['url'];
             $gift['priceWithDiscount'] = $gift['price'] * (1 - $gift['discount'] / 100);
             $gift['formatPrice'] = array();
             $gift['formatPriceWithDiscount'] = array();
             foreach ($currencies as $currency)
             {
                 $gift['formatPrice'][] = $currency['prefix'].number_format(round(($gift['price'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                 $gift['formatPriceWithDiscount'][] = $currency['prefix'].number_format(round(($gift['priceWithDiscount'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
             }
             $optionitems[$gift['id']] = $gift;
         }
         unset($gifts);
         if (count($optionitems) > 0) $options['gift'] = array('name'=>'gift', 'title'=>'Подарок', 'type'=>'product', 'items'=>$optionitems);
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
