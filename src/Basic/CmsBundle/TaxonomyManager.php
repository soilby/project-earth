<?php

namespace Basic\CmsBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class TaxonomyManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'object.taxonomy';
    }
    
    public function getDescription()
    {
        return 'Категории';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Категории', $this->container->get('router')->generate('basic_cms_taxonomy_list').'?object=', 99, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_list') | $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_listshow'));
        $manager->addAdminMenu('Создать группу категорий', $this->container->get('router')->generate('basic_cms_taxonomy_create'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_new'), 'Категории');
        $manager->addAdminMenu('Создать представление', $this->container->get('router')->generate('basic_cms_taxonomyshow_create'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_newshow'), 'Категории');
        $manager->addAdminMenu('Список категорий', $this->container->get('router')->generate('basic_cms_taxonomy_list').'?object=', 10, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_list') | $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_listshow'), 'Категории');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) $this->container->get($item)->registerMenu();
    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('taxonomy_list','Просмотр списка категорий');
        $manager->addRole('taxonomy_view','Просмотр категорий');
        $manager->addRole('taxonomy_new','Создание новых категорий');
        $manager->addRole('taxonomy_edit','Редактирование категорий');

        $manager->addRole('taxonomy_listshow','Просмотр списка представлений');
        $manager->addRole('taxonomy_viewshow','Просмотр представлений');
        $manager->addRole('taxonomy_newshow','Создание новых представлений');
        $manager->addRole('taxonomy_editshow','Редактирование представлений');
        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) $this->container->get($item)->registerRoles();
        
     }
     
     public function getContentTypes()
     {
        $contents = array('view' => 'Просмотр категории','viewshow' => 'Просмотр представления');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) $this->container->get($item)->getContentTypes($contents);
        return $contents;
     }
     
     public function getTaxonomyType()
     {
         return false;
     }

     public function getTemplateTwig($contentType, $template)
     {
         if ($contentType == 'view') return 'BasicCmsBundle:Front:taxonomyview.html.twig';
         if ($contentType == 'viewshow') return 'BasicCmsBundle:Front:taxonomyshowview.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
         {
             $result = $this->container->get($item)->getTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         if ($contentType == 'taxonomy_show') return 'BasicCmsBundle:Front:moduletaxonomyshow.html.twig';
         if ($contentType == 'taxonomy_menu') return 'BasicCmsBundle:Front:moduletaxonomymenu.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
         {
             $result = $this->container->get($item)->getModuleTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }

     private function taxonomyGetTreeForFrontView($tree, $id, $nesting, &$cats)
    {
        foreach ($tree as $cat)
        {
            if ($cat['parent'] == $id)
            {
                $cats[] = array_merge($cat, array('nesting' => $nesting));
                $this->taxonomyGetTreeForFrontModule($tree, $cat['id'], $nesting + 1, $cats, 0);
            }
        }
    }
     
     public function getFrontContent($locale, $contentType, $contentId, $result = null)
     {
         if ($contentType == 'view')
         {
             // Забираем данные
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT t.id, t.createDate, t.modifyDate, t.avatar, t.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                       'IF(tl.title is not null, tl.title, t.title) as title, '.
                                       'IF(tl.description is not null, tl.description, t.description) as description, '.
                                       'IF(tl.metaDescription is not null, tl.metaDescription, t.metaDescription) as metaDescription, '.
                                       'IF(tl.metaKeywords is not null, tl.metaKeywords, t.metaKeywords) as metaKeywords, '.
                                       't.template, t.enableAdd, t.parent, t.firstParent, t.object, t.pageParameters, t.filterParameters FROM BasicCmsBundle:Taxonomies t '.
                                       'LEFT JOIN BasicCmsBundle:TaxonomiesLocale tl WITH tl.taxonomyId = t.id AND tl.locale = :locale '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.
                                       'WHERE t.id = :id AND t.enabled != 0')->setParameter('id', $contentId)->setParameter('locale', $locale);
             $taxcategory = $query->getResult();
             if (empty($taxcategory)) return null;
             if (isset($taxcategory[0])) $taxcategory = $taxcategory[0]; else return null;
             $pageParameters = @unserialize($taxcategory['pageParameters']);
             if (!is_array($pageParameters)) return null;
             $filterParameters = @unserialize($taxcategory['filterParameters']);
             if (!is_array($filterParameters)) return null;
             // Получение Get параметров
             $gets = array();
             if (($pageParameters['pageGetOn'] != 0) && (preg_match("/^\d+$/ui", $this->container->get('request_stack')->getCurrentRequest()->get('page'))) && ($this->container->get('request_stack')->getCurrentRequest()->get('page') >= 0)) $pageParameters['pageValue'] = intval($this->container->get('request_stack')->getCurrentRequest()->get('page'));
             if (($pageParameters['countGetOn'] != 0) && (preg_match("/^\d+$/ui", $this->container->get('request_stack')->getCurrentRequest()->get('countinpage'))) && ($this->container->get('request_stack')->getCurrentRequest()->get('countinpage') >= 1) && ($this->container->get('request_stack')->getCurrentRequest()->get('countinpage') < 1000)) $pageParameters['countValue'] = intval($this->container->get('request_stack')->getCurrentRequest()->get('countinpage'));
             foreach ($filterParameters as &$filter)
             {
                 if ($filter['getoff'] != 0) $filter['active'] = false; else $filter['active'] = true;
                 if (($filter['get'] != '') && ($this->container->get('request_stack')->getCurrentRequest()->get($filter['get']) !== null) && ($this->container->get('request_stack')->getCurrentRequest()->get($filter['get']) !== '')) {$filter['active'] = true;$filter['value'] = $this->container->get('request_stack')->getCurrentRequest()->get($filter['get']);}
                 if ($filter['get'] != '') $gets[$filter['get']] = $filter['value'];
             }
             if ($pageParameters['sortGetOn'] != 0)
             {
                 $sortget = $this->container->get('request_stack')->getCurrentRequest()->get('sort');
                 if ($sortget !== null)
                 {
                     $sortparameters = $this->container->get($taxcategory['object'])->getTaxonomyParameters();
                     foreach ($sortparameters as $key=>$val)
                     {
                         if (strtoupper($sortget) == strtoupper($key)) {$pageParameters['sortField'] = $key; $pageParameters['sortDirection'] = 0;}
                         if (strtoupper($sortget) == strtoupper($key.' asc')) {$pageParameters['sortField'] = $key; $pageParameters['sortDirection'] = 0;}
                         if (strtoupper($sortget) == strtoupper($key.' desc')) {$pageParameters['sortField'] = $key; $pageParameters['sortDirection'] = 1;}
                     }
                     unset($sortparameters);
                 }
                 unset($sortget);
             }
             // Вычисляем перечень категорий для вывода
             $categoryIds = array($contentId);
             if ($pageParameters['includeChilds'])
             {
                 $query = $em->createQuery('SELECT t.id, t.enabled, t.parent FROM BasicCmsBundle:Taxonomies t '.
                                           'WHERE t.firstParent = :id AND t.parent is not null '.
                                           'ORDER BY t.ordering')->setParameter('id', $taxcategory['firstParent']);
                 $tree = $query->getResult();
                 do
                 {
                     $count = 0;
                     foreach ($tree as $cat) if ((in_array($cat['parent'], $categoryIds)) && (!in_array($cat['id'], $categoryIds))) {$categoryIds[] = $cat['id'];$count++;}
                 } while ($count > 0);
             }
             if (($pageParameters['includeParent']) && ($taxcategory['parent'] != null)) $categoryIds[] = $taxcategory['parent'];
             // Определяем количество материалов и проверяем пагинацию
             if ($this->container->has($taxcategory['object'])) $query = $this->container->get($taxcategory['object'])->getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, true);
             $itemcount = $query->getResult();
             if (isset($itemcount[0]['itemcount'])) $itemcount = $itemcount[0]['itemcount']; else $itemcount = 0;
             $pageParameters['pageCount'] = ceil($itemcount / $pageParameters['countValue']);
             if ($pageParameters['pageCount'] < 1) $pageParameters['pageCount'] = 1;
             if ($pageParameters['pageValue'] >= $pageParameters['pageCount']) $pageParameters['pageValue'] = $pageParameters['pageCount'] - 1;
             // Пропускаем через обработчик объекта и получаем запрос
             if ($this->container->has($taxcategory['object'])) $query = $this->container->get($taxcategory['object'])->getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds);
             // Устанавливаем пагинацию
             $query->setFirstResult($pageParameters['pageValue'] * $pageParameters['countValue'])->setMaxResults($pageParameters['countValue']);
             $items = $query->getResult();
             // Поиск дочерних категорий и дополнительной информации
             $categories = array();
             if (isset($pageParameters['includeCategories']) && ($pageParameters['includeCategories'] != 0))
             {
                 $query = $em->createQuery('SELECT t.id, t.enabled, t.createDate, t.modifyDate, t.avatar, t.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, sp.url, sp.id as seoId, '.
                                           'IF(tl.title is not null, tl.title, t.title) as title, '.
                                           'IF(tl.description is not null, tl.description, t.description) as description, '.
                                           't.enableAdd, t.parent, t.object FROM BasicCmsBundle:Taxonomies t '.
                                           'LEFT JOIN BasicCmsBundle:TaxonomiesLocale tl WITH tl.taxonomyId = t.id AND tl.locale = :locale '.
                                           'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.
                                           'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.taxonomy\' AND sp.contentAction = \'view\' AND sp.contentId = t.id '.
                                           'WHERE t.firstParent = :fistparent ORDER BY t.ordering')->setParameter('fistparent', $taxcategory['firstParent'])->setParameter('locale', $locale);
                 $tree = $query->getResult();
                 if (isset($pageParameters['getExtCatInfo']) && ($pageParameters['getExtCatInfo'] != 0))
                 {
                     if ($this->container->has($taxcategory['object'])) $query = $this->container->get($taxcategory['object'])->getTaxonomyExtCatInfo($tree);
                 }
                 $categories = array();
                 $this->taxonomyGetTreeForFrontView($tree, $taxcategory['id'], 0, $categories);
                 unset($tree);
                 $parent[] = array();
                 for ($i = 0; $i < count($categories); $i++)
                 {
                     if (isset($categories[$i]['url']) && ($categories[$i]['url'] != '')) $categories[$i]['url'] = '/'.($locale != '' ? $locale.'/' : '').$categories[$i]['url'];
                     if (!isset($categories[$i]['indexChildrens'])) $categories[$i]['indexChildrens'] = array();
                     $parent[$categories[$i]['nesting']] = $i;
                     if (isset($parent[$categories[$i]['nesting'] - 1])) 
                     {
                         if (!isset($categories[$parent[$categories[$i]['nesting'] - 1]]['indexChildrens'])) $categories[$parent[$categories[$i]['nesting'] - 1]]['indexChildrens'] = array();
                         $categories[$parent[$categories[$i]['nesting'] - 1]]['indexChildrens'][] = $i;
                     }
                 }
                 unset($parent);
             }
             // Вывод параметров
             $params = array();
             $params['id'] = $taxcategory['id'];
             $params['createDate'] = $taxcategory['createDate'];
             $params['modifyDate'] = $taxcategory['modifyDate'];
             $params['avatar'] = $taxcategory['avatar'];
             $params['createrId'] = $taxcategory['createrId'];
             $params['createrLogin'] = $taxcategory['createrLogin'];
             $params['createrFullName'] = $taxcategory['createrFullName'];
             $params['createrAvatar'] = $taxcategory['createrAvatar'];
             $params['title'] = $taxcategory['title'];
             $params['description'] = $taxcategory['description'];
             $params['template'] = $taxcategory['template'];
             $params['enableAdd'] = $taxcategory['enableAdd'];
             $params['parent'] = $taxcategory['parent'];
             $params['firstParent'] = $taxcategory['firstParent'];
             $params['object'] = $taxcategory['object'];
             $params['page'] = $pageParameters['pageValue'];
             $params['countInPage'] = $pageParameters['countValue'];
             $params['pageCount'] = $pageParameters['pageCount'];
             $params['itemCount'] = $itemcount;
             $params['sortField'] = $pageParameters['sortField'];
             $params['sortDirection'] = $pageParameters['sortDirection'];
             $params['getParameters'] = $gets;
             $params['filters'] = $filterParameters;
             $params['items'] = $items;
             $params['categories'] = $categories;
             $params['meta'] = array('title'=>$taxcategory['title'], 'keywords'=>$taxcategory['metaKeywords'], 'description'=>$taxcategory['metaDescription'], 'robotindex'=>true);
             // Пропускаем через пост-обработчик объекта
             if ($this->container->has($taxcategory['object'])) $this->container->get($taxcategory['object'])->getTaxonomyPostProcess($locale, $params);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         if ($contentType == 'viewshow')
         {
             // Забираем данные
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT t.id, t.createDate, t.modifyDate, t.avatar, t.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                       'IF(tl.title is not null, tl.title, t.title) as title, '.
                                       'IF(tl.description is not null, tl.description, t.description) as description, '.
                                       'IF(tl.metaDescription is not null, tl.metaDescription, t.metaDescription) as metaDescription, '.
                                       'IF(tl.metaKeywords is not null, tl.metaKeywords, t.metaKeywords) as metaKeywords, '.
                                       't.template, t.object, t.pageParameters, t.filterParameters, t.taxonomyIds FROM BasicCmsBundle:TaxonomyShows t '.
                                       'LEFT JOIN BasicCmsBundle:TaxonomyShowsLocale tl WITH tl.showId = t.id AND tl.locale = :locale '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.
                                       'WHERE t.id = :id AND t.enabled != 0')->setParameter('id', $contentId)->setParameter('locale', $locale);
             $show = $query->getResult();
             if (empty($show)) return null;
             if (isset($show[0])) $show = $show[0]; else return null;
             $pageParameters = @unserialize($show['pageParameters']);
             if (!is_array($pageParameters)) return null;
             $filterParameters = @unserialize($show['filterParameters']);
             if (!is_array($filterParameters)) return null;
             // Получение Get параметров
             $gets = array();
             if (($pageParameters['pageGetOn'] != 0) && (preg_match("/^\d+$/ui", $this->container->get('request_stack')->getCurrentRequest()->get('page'))) && ($this->container->get('request_stack')->getCurrentRequest()->get('page') >= 0)) $pageParameters['pageValue'] = intval($this->container->get('request_stack')->getCurrentRequest()->get('page'));
             if (($pageParameters['countGetOn'] != 0) && (preg_match("/^\d+$/ui", $this->container->get('request_stack')->getCurrentRequest()->get('countinpage'))) && ($this->container->get('request_stack')->getCurrentRequest()->get('countinpage') >= 1) && ($this->container->get('request_stack')->getCurrentRequest()->get('countinpage') < 1000)) $pageParameters['countValue'] = intval($this->container->get('request_stack')->getCurrentRequest()->get('countinpage'));
             foreach ($filterParameters as &$filter)
             {
                 if ($filter['getoff'] != 0) $filter['active'] = false; else $filter['active'] = true;
                 if (($filter['get'] != '') && ($this->container->get('request_stack')->getCurrentRequest()->get($filter['get']) !== null) && ($this->container->get('request_stack')->getCurrentRequest()->get($filter['get']) !== '')) {$filter['active'] = true;$filter['value'] = $this->container->get('request_stack')->getCurrentRequest()->get($filter['get']);}
                 if ($filter['get'] != '') $gets[$filter['get']] = $filter['value'];
             }
             if ($pageParameters['sortGetOn'] != 0)
             {
                 $sortget = $this->container->get('request_stack')->getCurrentRequest()->get('sort');
                 if ($sortget !== null)
                 {
                     $sortparameters = $this->container->get($show['object'])->getTaxonomyParameters();
                     foreach ($sortparameters as $key=>$val)
                     {
                         if (strtoupper($sortget) == strtoupper($key)) {$pageParameters['sortField'] = $key; $pageParameters['sortDirection'] = 0;}
                         if (strtoupper($sortget) == strtoupper($key.' asc')) {$pageParameters['sortField'] = $key; $pageParameters['sortDirection'] = 0;}
                         if (strtoupper($sortget) == strtoupper($key.' desc')) {$pageParameters['sortField'] = $key; $pageParameters['sortDirection'] = 1;}
                     }
                     unset($sortparameters);
                 }
                 unset($sortget);
             }
             // Вычисляем перечень категорий для вывода
             if ($show['taxonomyIds'] != '') $categoryIds = explode(',', $show['taxonomyIds']); else $categoryIds = array();
             // Определяем количество материалов и проверяем пагинацию
             if ($this->container->has($show['object'])) $query = $this->container->get($show['object'])->getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, true);
             $itemcount = $query->getResult();
             if (isset($itemcount[0]['itemcount'])) $itemcount = $itemcount[0]['itemcount']; else $itemcount = 0;
             $pageParameters['pageCount'] = ceil($itemcount / $pageParameters['countValue']);
             if ($pageParameters['pageCount'] < 1) $pageParameters['pageCount'] = 1;
             if ($pageParameters['pageValue'] >= $pageParameters['pageCount']) $pageParameters['pageValue'] = $pageParameters['pageCount'] - 1;
             // Пропускаем через обработчик объекта и получаем запрос
             if ($this->container->has($show['object'])) $query = $this->container->get($show['object'])->getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds);
             // Устанавливаем пагинацию
             $query->setFirstResult($pageParameters['pageValue'] * $pageParameters['countValue'])->setMaxResults($pageParameters['countValue']);
             $items = $query->getResult();
             // Вывод параметров
             $params = array();
             $params['createDate'] = $show['createDate'];
             $params['modifyDate'] = $show['modifyDate'];
             $params['avatar'] = $show['avatar'];
             $params['createrId'] = $show['createrId'];
             $params['createrLogin'] = $show['createrLogin'];
             $params['createrFullName'] = $show['createrFullName'];
             $params['createrAvatar'] = $show['createrAvatar'];
             $params['title'] = $show['title'];
             $params['description'] = $show['description'];
             $params['template'] = $show['template'];
             $params['object'] = $show['object'];
             $params['page'] = $pageParameters['pageValue'];
             $params['countInPage'] = $pageParameters['countValue'];
             $params['pageCount'] = $pageParameters['pageCount'];
             $params['sortField'] = $pageParameters['sortField'];
             $params['sortDirection'] = $pageParameters['sortDirection'];
             $params['itemCount'] = $itemcount;
             $params['getParameters'] = $gets;
             $params['filters'] = $filterParameters;
             $params['items'] = $items;
             $params['meta'] = array('title'=>$show['title'], 'keywords'=>$show['metaKeywords'], 'description'=>$show['metaDescription'], 'robotindex'=>true);
             // Пропускаем через пост-обработчик объекта
             if ($this->container->has($show['object'])) $this->container->get($show['object'])->getTaxonomyPostProcess($locale, $params);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         $params = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
         return $params;
     }
     
     public function setFrontAction($locale, $seoPage, $action)
     {
         $result = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) $this->container->get($item)->setFrontAction($locale, $seoPage, $action, $result);
         return $result;
     }
     
     public function getModuleTypes()
     {
         $contents = array('taxonomy_show'=>'Модуль представления','taxonomy_menu'=>'Модуль меню категорий');
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) $this->container->get($item)->getModuleTypes($contents);
         return $contents;
     }

     private function taxonomyGetTreeForFrontModule($tree, $id, $nesting, &$cats, $addedFirst)
    {
        if ($addedFirst != 0) 
        {
            foreach ($tree as $cat) 
                if ($cat['id'] == $id) {$cats[] = array_merge($cat, array('nesting' => $nesting));$nesting++;}
        }
        foreach ($tree as $cat)
        {
            if ($cat['parent'] == $id)
            {
                $cats[] = array_merge($cat, array('nesting' => $nesting));
                $this->taxonomyGetTreeForFrontModule($tree, $cat['id'], $nesting + 1, $cats, 0);
            }
        }
    }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result = null)
     {
         $params = null;
         if ($moduleType == 'taxonomy_show')
         {
             // Забираем данные
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT t.createDate, t.modifyDate, t.avatar, t.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                       'IF(tl.title is not null, tl.title, t.title) as title, '.
                                       'IF(tl.description is not null, tl.description, t.description) as description, '.
                                       't.object, t.pageParameters, t.filterParameters, t.taxonomyIds FROM BasicCmsBundle:TaxonomyShows t '.
                                       'LEFT JOIN BasicCmsBundle:TaxonomyShowsLocale tl WITH tl.showId = t.id AND tl.locale = :locale '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.
                                       'WHERE t.id = :id AND t.enabled != 0')->setParameter('id', $parameters['showid'])->setParameter('locale', $locale);
             $show = $query->getResult();
             if (empty($show)) return null;
             if (isset($show[0])) $show = $show[0]; else return null;
             $pageParameters = @unserialize($show['pageParameters']);
             if (!is_array($pageParameters)) return null;
             $filterParameters = @unserialize($show['filterParameters']);
             if (!is_array($filterParameters)) return null;
             // Получение Get параметров
             $gets = array();
             if (($pageParameters['pageGetOn'] != 0) && (preg_match("/^\d+$/ui", $this->container->get('request_stack')->getCurrentRequest()->get('page'))) && ($this->container->get('request_stack')->getCurrentRequest()->get('page') >= 0)) $pageParameters['pageValue'] = intval($this->container->get('request_stack')->getCurrentRequest()->get('page'));
             if (($pageParameters['countGetOn'] != 0) && (preg_match("/^\d+$/ui", $this->container->get('request_stack')->getCurrentRequest()->get('countinpage'))) && ($this->container->get('request_stack')->getCurrentRequest()->get('countinpage') >= 1) && ($this->container->get('request_stack')->getCurrentRequest()->get('countinpage') < 1000)) $pageParameters['countValue'] = intval($this->container->get('request_stack')->getCurrentRequest()->get('countinpage'));
             foreach ($filterParameters as &$filter)
             {
                 if ($filter['getoff'] != 0) $filter['active'] = false; else $filter['active'] = true;
                 if (($filter['get'] != '') && ($this->container->get('request_stack')->getCurrentRequest()->get($filter['get']) !== null) && ($this->container->get('request_stack')->getCurrentRequest()->get($filter['get']) !== '')) {$filter['active'] = true;$filter['value'] = $this->container->get('request_stack')->getCurrentRequest()->get($filter['get']);}
                 if ($filter['get'] != '') $gets[$filter['get']] = $filter['value'];
             }
             if ($pageParameters['sortGetOn'] != 0)
             {
                 $sortget = $this->container->get('request_stack')->getCurrentRequest()->get('sort');
                 if ($sortget !== null)
                 {
                     $sortparameters = $this->container->get($show['object'])->getTaxonomyParameters();
                     foreach ($sortparameters as $key=>$val)
                     {
                         if (strtoupper($sortget) == strtoupper($key)) {$pageParameters['sortField'] = $key; $pageParameters['sortDirection'] = 0;}
                         if (strtoupper($sortget) == strtoupper($key.' asc')) {$pageParameters['sortField'] = $key; $pageParameters['sortDirection'] = 0;}
                         if (strtoupper($sortget) == strtoupper($key.' desc')) {$pageParameters['sortField'] = $key; $pageParameters['sortDirection'] = 1;}
                     }
                     unset($sortparameters);
                 }
                 unset($sortget);
             }
             // Вычисляем перечень категорий для вывода
             if ($show['taxonomyIds'] != '') $categoryIds = explode(',', $show['taxonomyIds']); else $categoryIds = array();
             if (($pageParameters['categoryGetSeo'] != 0) && ($seoPage->getContentType() == 'object.taxonomy') && (($seoPage->getContentAction() == 'view') || ($seoPage->getContentAction() == 'edit'))) $categoryIds[] = $seoPage->getContentId();
             // Определяем количество материалов и проверяем пагинацию
             if ($this->container->has($show['object'])) $query = $this->container->get($show['object'])->getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, true);
             $itemcount = $query->getResult();
             if (isset($itemcount[0]['itemcount'])) $itemcount = $itemcount[0]['itemcount']; else $itemcount = 0;
             $pageParameters['pageCount'] = ceil($itemcount / $pageParameters['countValue']);
             if ($pageParameters['pageCount'] < 1) $pageParameters['pageCount'] = 1;
             if ($pageParameters['pageValue'] >= $pageParameters['pageCount']) $pageParameters['pageValue'] = $pageParameters['pageCount'] - 1;
             // Пропускаем через обработчик объекта и получаем запрос
             if ($this->container->has($show['object'])) $query = $this->container->get($show['object'])->getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds);
             // Устанавливаем пагинацию
             $query->setFirstResult($pageParameters['pageValue'] * $pageParameters['countValue'])->setMaxResults($pageParameters['countValue']);
             $items = $query->getResult();
             // Вывод параметров
             $params = array();
             $params['createDate'] = $show['createDate'];
             $params['modifyDate'] = $show['modifyDate'];
             $params['avatar'] = $show['avatar'];
             $params['createrId'] = $show['createrId'];
             $params['createrLogin'] = $show['createrLogin'];
             $params['createrFullName'] = $show['createrFullName'];
             $params['createrAvatar'] = $show['createrAvatar'];
             $params['title'] = $show['title'];
             $params['description'] = $show['description'];
             $params['object'] = $show['object'];
             $params['page'] = $pageParameters['pageValue'];
             $params['countInPage'] = $pageParameters['countValue'];
             $params['pageCount'] = $pageParameters['pageCount'];
             $params['sortField'] = $pageParameters['sortField'];
             $params['sortDirection'] = $pageParameters['sortDirection'];
             $params['itemCount'] = $itemcount;
             $params['getParameters'] = $gets;
             $params['filters'] = $filterParameters;
             $params['items'] = $items;
             // Пропускаем через пост-обработчик объекта
             if ($this->container->has($show['object'])) $this->container->get($show['object'])->getTaxonomyPostProcess($locale, $params);
         }
         if ($moduleType == 'taxonomy_menu')
         {
             $breadcrumbs = $this->container->get('cms.cmsManager')->getBreadCrumbs($seoPage, $locale);
             // Забираем данные
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT 0 as active, t.id, t.enabled, t.createDate, t.modifyDate, t.avatar, t.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, sp.url, sp.id as seoId, '.
                                       'IF(tl.title is not null, tl.title, t.title) as title, '.
                                       'IF(tl.description is not null, tl.description, t.description) as description, '.
                                       't.enableAdd, t.parent, t.object FROM BasicCmsBundle:Taxonomies t '.
                                       'LEFT JOIN BasicCmsBundle:TaxonomiesLocale tl WITH tl.taxonomyId = t.id AND tl.locale = :locale '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.
                                       'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.taxonomy\' AND sp.contentAction = \'view\' AND sp.contentId = t.id '.
                                       'WHERE t.firstParent IN (SELECT tf.firstParent FROM BasicCmsBundle:Taxonomies tf WHERE tf.id = :id) ORDER BY t.ordering')->setParameter('id', $parameters['categoryid'])->setParameter('locale', $locale);
             $tree = $query->getResult();
             if (isset($parameters['getExtCatInfo']) && ($parameters['getExtCatInfo'] != 0) && isset($tree[0]['object']))
             {
                 if ($this->container->has($tree[0]['object'])) $query = $this->container->get($tree[0]['object'])->getTaxonomyExtCatInfo($tree);
             }
             foreach ($tree as &$treeitem)
             {
                 $treeitem['active'] = 0;
                 foreach ($breadcrumbs as $breadcrumb) 
                 {
                     if ($breadcrumb['id'] == $treeitem['seoId']) $treeitem['active'] = 1;
                 }
             }
             $categories = array();
             if (isset($parameters['getidformseo']) && ($parameters['getidformseo'] != 0) && ($seoPage->getContentType() == 'object.taxonomy') && ($seoPage->getContentAction() == 'view')) $parameters['categoryid'] = $seoPage->getContentId();
             $this->taxonomyGetTreeForFrontModule($tree, $parameters['categoryid'], 0, $categories, $parameters['showself']);
             unset($tree);
             $parent[] = array();
             for ($i = 0; $i < count($categories); $i++)
             {
                 if (isset($categories[$i]['url']) && ($categories[$i]['url'] != '')) $categories[$i]['url'] = '/'.($locale != '' ? $locale.'/' : '').$categories[$i]['url'];
                 if (!isset($categories[$i]['indexChildrens'])) $categories[$i]['indexChildrens'] = array();
                 $parent[$categories[$i]['nesting']] = $i;
                 if (isset($parent[$categories[$i]['nesting'] - 1])) 
                 {
                     if (!isset($categories[$parent[$categories[$i]['nesting'] - 1]]['indexChildrens'])) $categories[$parent[$categories[$i]['nesting'] - 1]]['indexChildrens'] = array();
                     $categories[$parent[$categories[$i]['nesting'] - 1]]['indexChildrens'][] = $i;
                 }
             }
             unset($parent);
             $params['categories'] = $categories;
         }
         if ($params != null) return $params;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             if (strpos($item,'addone.taxonomy.') === 0) $params = $this->container->get($item)->getFrontModule($locale, $seoPage, $moduleType, $parameters, $result);
             if ($params != null) return $params;
         }
         return null;
     }

    private function taxonomyGetTreeForAdminModuleController($tree, $id, $nesting, &$cats)
    {
        foreach ($tree as $cat)
        {
            if ($cat['parent'] == $id)
            {
                $cats[] = array('title' => $cat['title'], 'id' => $cat['id'], 'enabled' => $cat['enabled'], 'nesting' => $nesting);
                $this->taxonomyGetTreeForAdminModuleController($tree, $cat['id'], $nesting + 1, $cats);
            }
        }
    }
     
    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        if ($module == 'taxonomy_show')
        {
            $parameters = array();
            $parameters['showid'] = '';
            if (isset($nullparameters['showid'])) $parameters['showid'] = $nullparameters['showid'];
            $parameterserror['showid'] = '';
            $query = $em->createQuery('SELECT s.id, s.title, s.enabled FROM BasicCmsBundle:TaxonomyShows s ORDER BY s.title');
            $showlist = $query->getResult();
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postparameters = $request->get('parameters');
                if (isset($postparameters['showid'])) $parameters['showid'] = $postparameters['showid'];
                $finded = false;
                foreach ($showlist as $showitem) if ($showitem['id'] == $parameters['showid']) $finded = true;
                if ($finded == false) {$errors = true; $parameterserror['showid'] = 'Представление не найдено';}
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
                return $this->container->get('templating')->render('BasicCmsBundle:Taxonomy:moduleTaxShow.html.twig',array('parameters' => $parameters,'parameterserror' => $parameterserror,'showlist'=>$showlist));
            }
        }
        if ($module == 'taxonomy_menu')
        {
            $parameters = array();
            $parameters['categoryid'] = '';
            $parameters['showself'] = 0;
            $parameters['getidformseo'] = 0;
            $parameters['getExtCatInfo'] = 0;
            if (isset($nullparameters['categoryid'])) $parameters['categoryid'] = $nullparameters['categoryid'];
            if (isset($nullparameters['showself'])) $parameters['showself'] = $nullparameters['showself'];
            if (isset($nullparameters['getidformseo'])) $parameters['getidformseo'] = $nullparameters['getidformseo'];
            if (isset($nullparameters['getExtCatInfo'])) $parameters['getExtCatInfo'] = $nullparameters['getExtCatInfo'];
            $parameterserror['categoryid'] = '';
            $parameterserror['showself'] = '';
            $parameterserror['getidformseo'] = '';
            $parameterserror['getExtCatInfo'] = '';
            $query = $em->createQuery('SELECT t.id, t.title, t.enabled, t.parent FROM BasicCmsBundle:Taxonomies t ORDER BY t.ordering');
            $tree = $query->getResult();
            $taxonomylist = array();
            $this->taxonomyGetTreeForAdminModuleController($tree, null, 0, $taxonomylist);
            unset($tree);
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postparameters = $request->get('parameters');
                if (isset($postparameters['categoryid'])) $parameters['categoryid'] = $postparameters['categoryid'];
                if (isset($postparameters['showself'])) $parameters['showself'] = intval($postparameters['showself']); else $parameters['showself'] = 0;
                if (isset($postparameters['getidformseo'])) $parameters['getidformseo'] = intval($postparameters['getidformseo']); else $parameters['getidformseo'] = 0;
                if (isset($postparameters['getExtCatInfo'])) $parameters['getExtCatInfo'] = intval($postparameters['getExtCatInfo']); else $parameters['getExtCatInfo'] = 0;
                $finded = false;
                foreach ($taxonomylist as $item) if ($item['id'] == $parameters['categoryid']) $finded = true;
                if ($finded == false) {$errors = true; $parameterserror['categoryid'] = 'Категория не найдена';}
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
                return $this->container->get('templating')->render('BasicCmsBundle:Taxonomy:moduleTaxMenu.html.twig',array('parameters' => $parameters,'parameterserror' => $parameterserror,'taxonomylist'=>$taxonomylist));
            }
        }
        $answer = null;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.taxonomy.') === 0) $answer = $this->container->get($item)->getAdminModuleController($request, $module, $actionType, $nullparameters);
            if ($answer != null) return $answer;
        }
        return null;
    }
    
    public function getInfoTitle($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT t.title FROM BasicCmsBundle:Taxonomies t WHERE t.id = :id')->setParameter('id', $contentId);
             $taxonomy = $query->getResult();
             if (isset($taxonomy[0]['title']))
             {
                 $title = array();
                 $title['default'] = $taxonomy[0]['title'];
                 $query = $em->createQuery('SELECT tl.title, tl.locale FROM BasicCmsBundle:TaxonomiesLocale tl WHERE tl.taxonomyId = :id')->setParameter('id', $contentId);
                 $taxonomylocale = $query->getResult();
                 if (is_array($taxonomylocale))
                 {
                     foreach ($taxonomylocale as $tpitem) if ($tpitem['title'] != null) $title[$tpitem['locale']] = $tpitem['title'];
                 }
                 return $this->container->get('cms.cmsManager')->encodeLocalString($title);
             }
         }
         if ($contentType == 'viewshow')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT t.title FROM BasicCmsBundle:TaxonomyShows t WHERE t.id = :id')->setParameter('id', $contentId);
             $taxonomy = $query->getResult();
             if (isset($taxonomy[0]['title']))
             {
                 $title = array();
                 $title['default'] = $taxonomy[0]['title'];
                 $query = $em->createQuery('SELECT tl.title, tl.locale FROM BasicCmsBundle:TaxonomyShowsLocale tl WHERE tl.showId = :id')->setParameter('id', $contentId);
                 $taxonomylocale = $query->getResult();
                 if (is_array($taxonomylocale))
                 {
                     foreach ($taxonomylocale as $tpitem) if ($tpitem['title'] != null) $title[$tpitem['locale']] = $tpitem['title'];
                 }
                 return $this->container->get('cms.cmsManager')->encodeLocalString($title);
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.taxonomy.') === 0) $answer = $this->container->get($item)->getInfoTitle($contentType, $contentId);
             if ($answer != null) return $answer;
         }
         return null;
    }

    public function getInfoSitemap($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT t.avatar, t.modifyDate FROM BasicCmsBundle:Taxonomies t WHERE t.id = :id AND t.enabled != 0')->setParameter('id', $contentId);
             $taxonomy = $query->getResult();
             if (isset($taxonomy[0]))
             {
                 $info = array();
                 $info['priority'] = '0.5';
                 $info['modifyDate'] = $taxonomy[0]['modifyDate'];
                 $info['avatar'] = $taxonomy[0]['avatar'];
                 return $info;
             }
         }
         if ($contentType == 'viewshow')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT t.avatar, t.modifyDate FROM BasicCmsBundle:TaxonomyShows t WHERE t.id = :id AND t.enabled != 0')->setParameter('id', $contentId);
             $taxonomy = $query->getResult();
             if (isset($taxonomy[0]))
             {
                 $info = array();
                 $info['priority'] = '0.5';
                 $info['modifyDate'] = $taxonomy[0]['modifyDate'];
                 $info['avatar'] = $taxonomy[0]['avatar'];
                 return $info;
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.taxonomy.') === 0) $answer = $this->container->get($item)->getInfoSitemap($contentType, $contentId);
             if ($answer != null) return $answer;
         }
         return null;
    }
    
// **********************************    
// Обработчик таба таксономии
// **********************************    
    private function taxonomyGetTree($tree, $id, $nesting, &$cats)
    {
        foreach ($tree as $cat)
        {
            if ($cat['parent'] == $id)
            {
                $cats[] = array('title' => $cat['title'], 'id' => $cat['id'], 'enableAdd' => $cat['enableAdd'], 'enabled' => $cat['enabled'], 'nesting' => $nesting, 'error' => '', 'selected' => $cat['selected']);
                $this->taxonomyGetTree($tree, $cat['id'], $nesting + 1, $cats);
            }
        }
    }

    private function taxonomyGetMiniTree($tree, $id, $nesting, &$cats)
    {
        foreach ($tree as $cat)
        {
            if ($cat['parent'] == $id)
            {
                $cats[] = array('title' => $cat['title'], 'id' => $cat['id'], 'enabled' => $cat['enabled'], 'enableAdd' => $cat['enableAdd'], 'nesting' => $nesting);
                $this->taxonomyGetMiniTree($tree, $cat['id'], $nesting + 1, $cats);
            }
        }
    }
    
    
    
    public function getTaxonomyListInfo($object, $actionIds = null)
    {
        $result = array();
        $em = $this->container->get('doctrine')->getEntityManager();
        $query = $em->createQuery('SELECT t.id, t.title, t.parent, t.enabled, t.enableAdd FROM BasicCmsBundle:Taxonomies t WHERE t.object = :object ORDER BY t.ordering')->setParameter('object', $object);
        $tree = $query->getResult();
        $taxonomyIds = array();
        foreach ($tree as $treeitem) $taxonomyIds[] = $treeitem['id'];
        $categories = array();
        $this->taxonomyGetMiniTree($tree, null, 0, $categories);
        if ($actionIds === null) return $categories;
        $result['categories'] = $categories;
        $result['links'] = array();
        if (count($actionIds) > 0)
        {
            if (count($taxonomyIds) > 0)
            {
                $query = $em->createQuery('SELECT l.taxonomyId, l.itemId FROM BasicCmsBundle:TaxonomiesLinks l WHERE l.taxonomyId in (:taxonomyIds) AND l.itemId in (:itemIds)')->setParameter('taxonomyIds', $taxonomyIds)->setParameter('itemIds', $actionIds);
                $links = $query->getResult();
            } else $links = array();
            foreach ($actionIds as $actionId)
            {
                $result['links'][$actionId] = array();
                foreach ($links as $link)
                {
                    if ($link['itemId'] == $actionId)
                    {
                        foreach ($categories as $category) if ($category['id'] == $link['taxonomyId']) {$result['links'][$actionId][$link['taxonomyId']] = $category['title'];break;}
                    }
                }
            }
        }
        return $result;
    }
    
    
    
  
    public function getTaxonomyController($request, $action, $object, $actionId, $actionType)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        if ($action == 'create')
        {
            $query = $em->createQuery('SELECT t.id, t.title, t.parent, t.enabled, t.enableAdd, 0 as selected FROM BasicCmsBundle:Taxonomies t WHERE t.object = :object ORDER BY t.ordering')->setParameter('object', $object);
            $tree = $query->getResult();
            $categories = array();
            $this->taxonomyGetTree($tree, null, 0, $categories);
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postcats = $request->get('taxonomycategories');
                if (!is_array($postcats)) $postcats = array();
                foreach ($categories as &$catm)
                {
                    if (in_array($catm['id'], $postcats)) $catm['selected'] = 1; else $catm['selected'] = 0;
                }
                unset($postcats);
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    foreach ($categories as $cat)
                    {
                        if ($cat['selected'] != 0)
                        {
                            $linkent = new \Basic\CmsBundle\Entity\TaxonomiesLinks();
                            $linkent->setItemId($actionId);
                            $linkent->setTaxonomyId($cat['id']);
                            $em->persist($linkent);
                            $em->flush();
                            unset($linkent);
                        }
                    }
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('BasicCmsBundle:Taxonomy:objectTab.html.twig',array('categories'=>$categories));
            }
        }
        if ($action == 'edit')
        {
            $query = $em->createQuery('SELECT t.id, t.title, t.parent, t.enabled, t.enableAdd, IF(tl.id is null, \'0\', \'1\') as selected FROM BasicCmsBundle:Taxonomies t '.
                                      'LEFT JOIN BasicCmsBundle:TaxonomiesLinks tl WITH tl.taxonomyId = t.id AND tl.itemId = :id '.
                                      'WHERE t.object = :object')->setParameter('object', $object)->setParameter('id', $actionId);
            $tree = $query->getResult();
            $categories = array();
            $this->taxonomyGetTree($tree, null, 0, $categories);
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postcats = $request->get('taxonomycategories');
                if (!is_array($postcats)) $postcats = array();
                foreach ($categories as &$catm)
                {
                    if (in_array($catm['id'], $postcats)) $catm['selected'] = 1; else $catm['selected'] = 0;
                }
                unset($postcats);
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    foreach ($categories as $cat)
                    {
                        $linkent = $em->getRepository('BasicCmsBundle:TaxonomiesLinks')->findOneBy(array('taxonomyId'=>$cat['id'],'itemId'=>$actionId));
                        if (($cat['selected'] != 0) && empty($linkent))
                        {
                            $linkent = new \Basic\CmsBundle\Entity\TaxonomiesLinks();
                            $linkent->setItemId($actionId);
                            $linkent->setTaxonomyId($cat['id']);
                            $em->persist($linkent);
                            $em->flush();
                        }
                        if (($cat['selected'] == 0) && (!empty($linkent)))
                        {
                            $em->remove($linkent);
                            $em->flush();
                        }
                        unset($linkent);
                    }
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('BasicCmsBundle:Taxonomy:objectTab.html.twig',array('categories'=>$categories));
            }
        }
        if ($action == 'delete')
        {
            $query = $em->createQuery('SELECT t.id FROM BasicCmsBundle:Taxonomies t '.
                                      'WHERE t.object = :object')->setParameter('object', $object);
            $tree = $query->getResult();
            if ($actionType == 'save')
            {
                foreach ($tree as $cat)
                {
                    $linkent = $em->getRepository('BasicCmsBundle:TaxonomiesLinks')->findOneBy(array('taxonomyId'=>$cat['id'],'itemId'=>$actionId));
                    if (!empty($linkent))
                    {
                        $em->remove($linkent);
                        $em->flush();
                    }
                    unset($linkent);
                }
            }
        }
        return true;
    }

//**************************************************
// Поиск по сайту
//**************************************************

    public function getSearchOptions(&$options)
    {
        $options['object.taxonomy.on'] = 'Искать информацию в категориях';
        $options['object.taxonomy.onlypage'] = 'Искать информацию в категориях только со страницей сайта';
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.taxonomy.') === 0) $this->container->get($item)->getSearchOptions($options);
        }
    }
    
    public function getSearchItemCount($options, $searchstring, $locale)
    {
        $count = 0;
        if (isset($options['object.taxonomy.on']) && ($options['object.taxonomy.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT count(t.id) as taxcount FROM BasicCmsBundle:Taxonomies t '.
                                      'LEFT JOIN BasicCmsBundle:TaxonomiesLocale tl WITH tl.taxonomyId = t.id AND tl.locale = :locale '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.taxonomy\' AND sp.contentAction = \'view\' AND sp.contentId = t.id '.
                                      'WHERE (IF(tl.title is not null, tl.title, t.title) LIKE :searchstring OR IF(tl.description is not null, tl.description, t.description) LIKE :searchstring)'.
                                      ((isset($options['object.taxonomy.onlypage']) && ($options['object.taxonomy.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : ''))->setParameter('searchstring', $searchstring)->setParameter('locale', $locale);
            $taxcount = $query->getResult();
            if (isset($taxcount[0]['taxcount'])) $count += $taxcount[0]['taxcount'];
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.taxonomy.') === 0) $count += $this->container->get($item)->getSearchItemCount($options, $searchstring, $locale);
        }
        return $count;
    }
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, &$items)
    {
        if (isset($options['object.taxonomy.on']) && ($options['object.taxonomy.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT \'taxonomy\' as object, t.id, IF(tl.title is not null, tl.title, t.title) as title, IF(tl.description is not null, tl.description, t.description) as description, '.
                                      't.modifyDate, t.avatar, t.createrId, u.avatar as createrAvatar, u.login as createrLogin, u.fullName as createrFullName, sp.url FROM BasicCmsBundle:Taxonomies t '.
                                      'LEFT JOIN BasicCmsBundle:TaxonomiesLocale tl WITH tl.taxonomyId = t.id AND tl.locale = :locale '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.taxonomy\' AND sp.contentAction = \'view\' AND sp.contentId = t.id '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.
                                      'WHERE (IF(tl.title is not null, tl.title, t.title) LIKE :searchstring OR IF(tl.description is not null, tl.description, t.description) LIKE :searchstring)'.
                                      ((isset($options['object.taxonomy.onlypage']) && ($options['object.taxonomy.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : '').
                                      ($sortdir == 0 ? ' ORDER BY t.modifyDate ASC' : ' ORDER BY t.modifyDate DESC'))->setParameter('searchstring', $searchstring)->setParameter('locale', $locale)->setFirstResult($start)->setMaxResults($limit);
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
            if (strpos($item,'addone.taxonomy.') === 0) $this->container->get($item)->getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, $items);
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
            if (strpos($item,'addone.taxonomy.') === 0) $this->container->get($item)->getHelpChapters($chapters);
        }
        return $chapters;
    }
    
    public function getHelpContent($page, &$answer)
    {
        //if ($page == 'd1537bd0b66a4289001629ac2e06e742') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:index.html.twig',array());
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.taxonomy.') === 0) $answer .= $this->container->get($item)->getHelpContent($page);
        }
        return $answer;
    }
    
    
}
