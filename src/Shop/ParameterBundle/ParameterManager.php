<?php

namespace Shop\ParameterBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class ParameterManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'addone.product.parameter';
    }
    
    public function getDescription()
    {
        return 'Параметры продукции';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Параметры продукции', $this->container->get('router')->generate('shop_parameter_list'), 80, $this->container->get('security.context')->getToken()->getUser()->checkAccess('product_parameter'), 'Магазин');
    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('product_parameter','Изменение параметров продукции');
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
            $profile = array();
            $query = $em->createQuery('SELECT p.id, p.type, p.name, p.description, p.regularExp, p.items, \'\' as value, \'\' as error FROM ShopParameterBundle:ProductParameters p ORDER BY p.ordering');
            $parameters = $query->getResult();
            foreach ($parameters as &$item) 
            {
                $selectitems = preg_split('/\\r\\n?|\\n/', $item['items']);
                $item['items'] = array();
                if (is_array($selectitems))
                {
                    foreach ($selectitems as $line) if (trim($line) != '') $item['items'][] = $line;
                }
                $item['description'] = $this->container->get('cms.cmsManager')->decodeLocalString($item['description'],'default');
            }
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $profile = $request->get('parameters');
                foreach ($parameters as &$item)
                {
                    if (($item['type'] == 'text') || ($item['type'] == 'textarea') || ($item['type'] == 'html'))
                    {
                        if (isset($profile[$item['name']])) $item['value'] = $profile[$item['name']];
                        if (!@preg_match("/^".$item['regularExp']."$/ui", $item['value'])) {$errors = true; $item['error'] = 'Поле заполнено неверно';}
                    }
                    if ($item['type'] == 'select')
                    {
                        if (isset($profile[$item['name']])) $item['value'] = $profile[$item['name']];
                        $finded = false;
                        foreach ($item['items'] as $line) if ($line == $item['value']) $finded = true;
                        if ($finded == false) {$errors = true; $item['error'] = 'Поле заполнено неверно';}
                    }
                    if ($item['type'] == 'checkbox')
                    {
                        if (isset($profile[$item['name']])) $item['value'] = intval($profile[$item['name']]); else $item['value'] = 0;
                    }
                }
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    foreach ($parameters as &$item)
                    {
                        if ($item['type'] == 'html') $this->container->get('cms.cmsManager')->unlockTemporaryEditor($item['value'], '');
                        $valueent = new \Shop\ParameterBundle\Entity\ProductValues();
                        $valueent->setParameterId($item['id']);
                        $valueent->setProductId($actionId);
                        $valueent->setValue($item['value']);
                        $em->persist($valueent);
                        $em->flush();
                        unset($valueent);
                    }
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ShopParameterBundle:Addone:productCreate.html.twig',array('parameters'=>$parameters));
            }
        }
        if ($action == 'productEdit')
        {
            $profile = array();
            $query = $em->createQuery('SELECT p.id, p.type, p.name, p.description, p.regularExp, p.items, v.value as value, \'\' as error FROM ShopParameterBundle:ProductParameters p '.
                                      'LEFT JOIN ShopParameterBundle:ProductValues v WITH v.productId = :prodid AND v.parameterId = p.id '.
                                      'ORDER BY p.ordering')->setParameter('prodid', $actionId);
            $parameters = $query->getResult();
            foreach ($parameters as &$item) 
            {
                $selectitems = preg_split('/\\r\\n?|\\n/', $item['items']);
                $item['items'] = array();
                if (is_array($selectitems))
                {
                    foreach ($selectitems as $line) if (trim($line) != '') $item['items'][] = $line;
                }
                $item['description'] = $this->container->get('cms.cmsManager')->decodeLocalString($item['description'],'default');
            }
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $profile = $request->get('parameters');
                foreach ($parameters as &$item)
                {
                    if (($item['type'] == 'text') || ($item['type'] == 'textarea') || ($item['type'] == 'html'))
                    {
                        if (isset($profile[$item['name']])) $item['value'] = $profile[$item['name']];
                        if (!@preg_match("/^".$item['regularExp']."$/ui", $item['value'])) {$errors = true; $item['error'] = 'Поле заполнено неверно';}
                    }
                    if ($item['type'] == 'select')
                    {
                        if (isset($profile[$item['name']])) $item['value'] = $profile[$item['name']];
                        $finded = false;
                        foreach ($item['items'] as $line) if ($line == $item['value']) $finded = true;
                        if ($finded == false) {$errors = true; $item['error'] = 'Поле заполнено неверно';}
                    }
                    if ($item['type'] == 'checkbox')
                    {
                        if (isset($profile[$item['name']])) $item['value'] = intval($profile[$item['name']]); else $item['value'] = 0;
                    }
                }
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    foreach ($parameters as &$item)
                    {
                        $valueent = $this->container->get('doctrine')->getRepository('ShopParameterBundle:ProductValues')->findOneBy(array('parameterId'=>$item['id'],'productId'=>$actionId));
                        if (empty($valueent))
                        {
                            $valueent = new \Shop\ParameterBundle\Entity\ProductValues();
                            $valueent->setParameterId($item['id']);
                            $valueent->setProductId($actionId);
                            $em->persist($valueent);
                        }
                        if ($item['type'] == 'html') $this->container->get('cms.cmsManager')->unlockTemporaryEditor($item['value'], $valueent->getValue());
                        $valueent->setValue($item['value']);
                        $em->flush();
                        unset($valueent);
                    }
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ShopParameterBundle:Addone:productEdit.html.twig',array('parameters'=>$parameters));
            }
        }
        if ($action == 'productDelete')
        {
            if ($actionType == 'validate') return false;
            if ($actionType == 'tab') return null;
            if ($actionType == 'save')
            {
                $query = $em->createQuery('SELECT v.value FROM ShopParameterBundle:ProductValues v '.
                                          'LEFT JOIN ShopParameterBundle:ProductParameters p WITH v.parameterId = p.id '.
                                          'WHERE v.productId = :prodid AND p.type = \'html\'')->setParameter('prodid', $actionId);
                $delpages = $query->getResult();
                if (is_array($delpages))
                {
                    foreach ($delpages as $delpage)
                    {
                        if ($delpage['value'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $delpage['value']);
                    }
                }
                $query = $em->createQuery('DELETE FROM ShopParameterBundle:ProductValues v '.
                                          'WHERE v.productId = :prodid')->setParameter('prodid', $actionId);
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
             $query = $em->createQuery('SELECT p.name, p.type, p.description, v.value as value FROM ShopParameterBundle:ProductParameters p '.
                                       'LEFT JOIN ShopParameterBundle:ProductValues v WITH v.productId = :prodid AND v.parameterId = p.id '.
                                       'ORDER BY p.ordering')->setParameter('prodid', $contentId);
             $parameters = $query->getResult();
             foreach ($parameters as &$item) $item['description'] = $this->container->get('cms.cmsManager')->decodeLocalString($item['description'],'default');
             foreach ($parameters as $item2) 
             {
                 $params['parameters'][$item2['name']] = $item2;
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
         $em = $this->container->get('doctrine')->getEntityManager();
         $query = $em->createQuery('SELECT p.name, p.description FROM ShopParameterBundle:ProductParameters p ORDER BY p.ordering');
         $parameters = $query->getResult();
         if (is_array($parameters))
         {
             foreach ($parameters as $item)
             {
                 $params['parameter_'.$item['name']] = array('type'=>'text','ext'=>1,'name'=>$this->container->get('cms.cmsManager')->decodeLocalString($item['description'],'default'));
             }
         }
         unset($parameters);
     }
     
     public function getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $itemIds, $allparameters, &$queryparameters, &$queryparameterscount, &$select, &$from, &$where, &$order)
     {
         $em = $this->container->get('doctrine')->getEntityManager();
         $query = $em->createQuery('SELECT p.id, p.name FROM ShopParameterBundle:ProductParameters p ORDER BY p.ordering');
         $parameters = $query->getResult();
         if (!is_array($parameters)) return null;
         foreach ($parameters as $item)
         {
             if (in_array('parameter_'.$item['name'], $allparameters)) {$from .= ' LEFT JOIN ShopParameterBundle:ProductValues pv'.$item['id'].' WITH pv'.$item['id'].'.productId = p.id AND pv'.$item['id'].'.parameterId = '.$item['id'];}
             if (in_array('parameter_'.$item['name'], $pageParameters['fields'])) $select .=',pv'.$item['id'].'.value as parameter_'.$item['name'];
             foreach ($filterParameters as $field) if ($field['active'] == true)
             {
                 if ($field['parameter'] == 'parameter_'.$item['name'])
                 {
                     if ($field['compare'] == 'noeq') {$where .= ' AND pv'.$item['id'].'.value != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                     if ($field['compare'] == 'eq') {$where .= ' AND pv'.$item['id'].'.value = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                     if ($field['compare'] == 'like') {$where .= ' AND pv'.$item['id'].'.value like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
                 }
             }
             if ($pageParameters['sortField'] == 'parameter_'.$item['name']) $order = ' ORDER BY pv'.$item['id'].'.value '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
         }
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
