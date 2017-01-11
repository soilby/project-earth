<?php

namespace Shop\SizeBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class SizeManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'addone.product.size';
    }
    
    public function getDescription()
    {
        return 'Размеры продукта';
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
            $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
            $locales = $query->getResult();
            if (!is_array($locales)) $locales = array();
            $sizes = array();
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $sizes = array();
                $postsizes = $request->get('sizes');
                if (!is_array($postsizes)) $postsizes = array();
                ksort($postsizes, SORT_NUMERIC);
                foreach ($postsizes as $item)
                {
                    if (isset($item['enabled']) && isset($item['name']['default'])) 
                    {
                        $error = '';
                        $names = array();
                        $names['default'] = $item['name']['default'];
                        if (!preg_match("/^[-\s_A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\/\\\\,]{1,999}$/ui", $names['default'])) {$errors = true; $error = 'Имя должно содержать от 1 до 999 букв';}
                        foreach ($locales as $locale)
                        {
                            $names[$locale['shortName']] = (isset($item['name'][$locale['shortName']]) ? $item['name'][$locale['shortName']] : '');
                            if (!preg_match("/^([-\s_A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\/\\\\,]{1,999})?$/ui", $names[$locale['shortName']])) {$errors = true; if ($error == '') $error = 'Имя должно содержать от 1 до 999 букв';}
                        }
                        $sizes[] = array('enabled' => $item['enabled'], 'name' => $names, 'error' => $error);
                    }
                }
                unset($postsizes);
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    foreach ($sizes as &$tmpitem)
                    {
                        $tmpitem['name'] = $this->container->get('cms.cmsManager')->encodeLocalString($tmpitem['name']);
                    }
                    unset($tmpitem);
                    $sizeent = new \Shop\SizeBundle\Entity\ProductSizes();
                    $sizeent->setProductId($actionId);
                    $sizeent->setSizes(serialize($sizes));
                    $em->persist($sizeent);
                    $em->flush();
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ShopSizeBundle:Addone:productCreate.html.twig',array('sizes'=>$sizes, 'locales'=>$locales));
            }
        }
        if ($action == 'productEdit')
        {
            $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
            $locales = $query->getResult();
            if (!is_array($locales)) $locales = array();
            $sizes = array();
            $sizeent = $this->container->get('doctrine')->getRepository('ShopSizeBundle:ProductSizes')->findOneBy(array('productId'=>$actionId));
            if (!empty($sizeent))
            {
                $postsizes = @unserialize($sizeent->getSizes());
                if (!is_array($postsizes)) $postsizes = array();
                ksort($postsizes, SORT_NUMERIC);
                foreach ($postsizes as $item)
                {
                    if (isset($item['enabled']) && isset($item['name'])) 
                    {
                        $names = array();
                        $names['default'] = $this->container->get('cms.cmsManager')->decodeLocalString($item['name'],'default',false);
                        foreach ($locales as $locale)
                        {
                            $names[$locale['shortName']] = $this->container->get('cms.cmsManager')->decodeLocalString($item['name'],$locale['shortName'],false);
                        }
                        $sizes[] = array('enabled' => $item['enabled'], 'name' => $names, 'error' => '');
                    }
                }
                unset($postsizes);
            }
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $sizes = array();
                $postsizes = $request->get('sizes');
                if (!is_array($postsizes)) $postsizes = array();
                ksort($postsizes, SORT_NUMERIC);
                foreach ($postsizes as $item)
                {
                    if (isset($item['enabled']) && isset($item['name']['default'])) 
                    {
                        $error = '';
                        $names = array();
                        $names['default'] = $item['name']['default'];
                        if (!preg_match("/^[-\s_A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\/\\\\,]{1,999}$/ui", $names['default'])) {$errors = true; $error = 'Имя должно содержать от 1 до 999 букв';}
                        foreach ($locales as $locale)
                        {
                            $names[$locale['shortName']] = (isset($item['name'][$locale['shortName']]) ? $item['name'][$locale['shortName']] : '');
                            if (!preg_match("/^([-\s_A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\/\\\\,]{1,999})?$/ui", $names[$locale['shortName']])) {$errors = true; if ($error == '') $error = 'Имя должно содержать от 1 до 999 букв';}
                        }
                        $sizes[] = array('enabled' => $item['enabled'], 'name' => $names, 'error' => $error);
                    }
                }
                unset($postsizes);
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    foreach ($sizes as &$tmpitem)
                    {
                        $tmpitem['name'] = $this->container->get('cms.cmsManager')->encodeLocalString($tmpitem['name']);
                    }
                    unset($tmpitem);
                    if (empty($sizeent))
                    {
                        $sizeent = new \Shop\SizeBundle\Entity\ProductSizes();
                        $em->persist($sizeent);
                    }
                    $sizeent->setProductId($actionId);
                    $sizeent->setSizes(serialize($sizes));
                    $em->flush();
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ShopSizeBundle:Addone:productEdit.html.twig',array('sizes'=>$sizes, 'locales'=>$locales));
            }
        }
        if ($action == 'productDelete')
        {
            if ($actionType == 'validate') return false;
            if ($actionType == 'tab') return null;
            if ($actionType == 'save')
            {
                $query = $em->createQuery('DELETE FROM ShopSizeBundle:ProductSizes s WHERE s.productId = :id')->setParameter('id', $actionId);
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
         $em = $this->container->get('doctrine')->getEntityManager();
         $query = $em->createQuery('SELECT s.sizes FROM ShopSizeBundle:ProductSizes s WHERE s.productId = :id')->setParameter('id', $productId);
         $sizes = $query->getResult();
         if (isset($sizes[0]['sizes'])) $sizes = @unserialize($sizes[0]['sizes']);
         if (!is_array($sizes)) $sizes = array();
         $optionitems = array();
         foreach ($sizes as $size)
         {
             if (($size['enabled'] != 0) && ($this->container->get('cms.cmsManager')->decodeLocalString($size['name'],'default',true) != ''))
             {
                 $optionitems[$this->container->get('cms.cmsManager')->decodeLocalString($size['name'],'default',true)] = $this->container->get('cms.cmsManager')->decodeLocalString($size['name'],$locale,true);
             }
         }
         unset($sizes);
         if (count($optionitems) > 0) $options['size'] = array('name'=>'size', 'title'=>'Размер', 'type'=>'string', 'items'=>$optionitems);
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
