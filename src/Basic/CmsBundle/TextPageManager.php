<?php

namespace Basic\CmsBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class TextPageManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'object.textpage';
    }
    
    public function getDescription()
    {
        return 'Текстовые страницы';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Текстовые страницы', $this->container->get('router')->generate('basic_cms_textpage_list'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('textpage_list') | $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('textpage_listsite'));
        $manager->addAdminMenu('Создать новую', $this->container->get('router')->generate('basic_cms_textpage_create'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('textpage_new'), 'Текстовые страницы');
        $manager->addAdminMenu('Список страниц', $this->container->get('router')->generate('basic_cms_textpage_list'), 10, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('textpage_list') | $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('textpage_listsite'), 'Текстовые страницы');
        if ($this->container->has('object.taxonomy'))
        {
            $manager->addAdminMenu('Категории', $this->container->get('router')->generate('basic_cms_taxonomy_list').'?object=object.textpage', 9999, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_list') | $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_listshow'), 'Текстовые страницы');
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) $this->container->get($item)->registerMenu();

    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('textpage_list','Просмотр списка текстовых материалов');
        $manager->addRole('textpage_viewown','Просмотр собственных текстовых материалов');
        $manager->addRole('textpage_viewall','Просмотр всех текстовых материалов');
        $manager->addRole('textpage_new','Создание новых тектовых материалов');
        $manager->addRole('textpage_editown','Редактирование собственных текстовых материалов');
        $manager->addRole('textpage_editall','Редактирование всех текстовых материалов');
        
        $manager->addRole('textpage_listsite','Просмотр списка основных страниц сайта');
        $manager->addRole('textpage_newsite','Создание новых основных страниц сайта');
        $manager->addRole('textpage_viewsite','Просмотр основных страниц сайта');
        $manager->addRole('textpage_editsite','Редактирование основных страниц сайта');

        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) $this->container->get($item)->registerRoles();
        
     }
     
     public function getContentTypes()
     {
        $contents = array('view' => 'Просмотр текстовой страницы');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) $this->container->get($item)->getContentTypes($contents);
        return $contents;
     }
     
     public function getTaxonomyType()
     {
         return true;
     }

     public function getTemplateTwig($contentType, $template)
     {
         if ($contentType == 'view') return 'BasicCmsBundle:Front:textpageview.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) 
         {
             $result = $this->container->get($item)->getTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) 
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
             $query = $em->createQuery('SELECT t.id, t.createDate, t.modifyDate, t.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                       'IF(tl.title is not null, tl.title, t.title) as title, '.
                                       'IF(tl.description is not null, tl.description, t.description) as description, '.
                                       'IF(tl.content is not null, tl.content, t.content) as content, '.
                                       'IF(tl.metaDescription is not null, tl.metaDescription, t.metaDescription) as metaDescription, '.
                                       'IF(tl.metaKeywords is not null, tl.metaKeywords, t.metaKeywords) as metaKeywords, '.
                                       't.template, t.pageType, t.avatar FROM BasicCmsBundle:TextPages t '.
                                       'LEFT JOIN BasicCmsBundle:TextPagesLocale tl WITH tl.textPageId = t.id AND tl.locale = :locale '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.
                                       'WHERE t.id = :id AND t.enabled != 0')->setParameter('id', $contentId)->setParameter('locale', $locale);
             $textpage = $query->getResult();
             if (empty($textpage)) return null;
             if (isset($textpage[0])) $params = $textpage[0]; else return null;
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>$params['metaKeywords'], 'description'=>$params['metaDescription'], 'robotindex'=>true);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         $params = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
         return $params;
     }
     
     public function setFrontAction($locale, $seoPage, $action)
     {
         $result = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) $this->container->get($item)->setFrontAction($locale, $seoPage, $action, $result);
         return $result;
     }
     
     public function getModuleTypes()
     {
         $contents = array();
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) $this->container->get($item)->getModuleTypes($contents);
         return $contents;
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result = null)
     {
         $params = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             if (strpos($item,'addone.textpage.') === 0) $params = $this->container->get($item)->getFrontModule($locale, $seoPage, $moduleType, $parameters, $result);
             if ($params != null) return $params;
         }
         return null;
     }
     
    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        $answer = null;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.textpage.') === 0) $answer = $this->container->get($item)->getAdminModuleController($request, $module, $actionType, $nullparameters);
            if ($answer != null) return $answer;
        }
        return null;
    }

    public function getInfoTitle($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT t.title FROM BasicCmsBundle:TextPages t WHERE t.id = :id')->setParameter('id', $contentId);
             $textpage = $query->getResult();
             if (isset($textpage[0]['title']))
             {
                 $title = array();
                 $title['default'] = $textpage[0]['title'];
                 $query = $em->createQuery('SELECT tl.title, tl.locale FROM BasicCmsBundle:TextPagesLocale tl WHERE tl.textPageId = :id')->setParameter('id', $contentId);
                 $textpagelocale = $query->getResult();
                 if (is_array($textpagelocale))
                 {
                     foreach ($textpagelocale as $tpitem) if ($tpitem['title'] != null) $title[$tpitem['locale']] = $tpitem['title'];
                 }
                 return $this->container->get('cms.cmsManager')->encodeLocalString($title);
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.textpage.') === 0) $answer = $this->container->get($item)->getInfoTitle($contentType, $contentId);
             if ($answer != null) return $answer;
         }
         return null;
    }

    public function getInfoSitemap($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT t.avatar, t.modifyDate, t.pageType FROM BasicCmsBundle:TextPages t WHERE t.id = :id AND t.enabled != 0')->setParameter('id', $contentId);
             $textpage = $query->getResult();
             if (isset($textpage[0]))
             {
                 $info = array();
                 if ($textpage[0]['pageType'] != 0)
                 {
                     $info['priority'] = '1';
                     $info['modifyDate'] = null;
                     $info['avatar'] = $textpage[0]['avatar'];
                 } else
                 {
                     $info['priority'] = '0.5';
                     $info['modifyDate'] = $textpage[0]['modifyDate'];
                     $info['avatar'] = $textpage[0]['avatar'];
                 }
                 return $info;
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.textpage.') === 0) $answer = $this->container->get($item)->getInfoSitemap($contentType, $contentId);
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
        $params['description'] = array('type'=>'text','ext'=>0,'name'=>'Описание');
        $params['content'] = array('type'=>'text','ext'=>0,'name'=>'Текст');
        $params['avatar'] = array('type'=>'text','ext'=>0,'name'=>'Аватар');
        $params['pageType'] = array('type'=>'bool','ext'=>0,'name'=>'Системная');
        $params['createrLogin'] = array('type'=>'text','ext'=>1,'name'=>'Логин автора');
        $params['createrFullName'] = array('type'=>'text','ext'=>1,'name'=>'Имя автора');
        $params['createrAvatar'] = array('type'=>'text','ext'=>1,'name'=>'Аватар автора');
        $params['createrEmail'] = array('type'=>'text','ext'=>1,'name'=>'E-mail автора');
        $params['url'] = array('type'=>'text','ext'=>1,'name'=>'URL страницы');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) $this->container->get($item)->getTaxonomyParameters($params);
        return $params;
    }
    
    public function getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $queryCount = false, $itemIds = null)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        $allparameters = array($pageParameters['sortField']);
        $queryparameters = array();
        foreach ($pageParameters['fields'] as $field) $allparameters[] = $field;
        foreach ($filterParameters as $field) $allparameters[] = $field['parameter'];
        $from = ' FROM BasicCmsBundle:TextPages t';
        if (in_array('createrLogin', $allparameters) || 
            in_array('createrFullName', $allparameters) ||    
            in_array('createrAvatar', $allparameters) ||    
            in_array('createrEmail', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId';}
        if (in_array('title', $allparameters) || 
            in_array('description', $allparameters) || 
            in_array('content', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:TextPagesLocale tl WITH tl.textPageId = t.id AND tl.locale = :locale'; $queryparameters['locale'] = $locale;}
        if (in_array('url', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.textpage\' AND sp.contentAction = \'view\' AND sp.contentId = t.id';}
        $select = 'SELECT t.id';
        if (in_array('createDate', $pageParameters['fields'])) $select .=',t.createDate';
        if (in_array('modifyDate', $pageParameters['fields'])) $select .=',t.modifyDate';
        if (in_array('createrId', $pageParameters['fields'])) $select .=',t.createrId';
        if (in_array('title', $pageParameters['fields']) || in_array('title', $allparameters)) $select .=',IF(tl.title is not null, tl.title, t.title) as title';
        if (in_array('description', $pageParameters['fields']) || in_array('description', $allparameters)) $select .=',IF(tl.description is not null, tl.description, t.description) as description';
        if (in_array('content', $pageParameters['fields']) || in_array('content', $allparameters)) $select .=',IF(tl.content is not null, tl.content, t.content) as content';
        if (in_array('avatar', $pageParameters['fields'])) $select .=',t.avatar';
        if (in_array('pageType', $pageParameters['fields'])) $select .=',t.pageType';
        if (in_array('createrLogin', $pageParameters['fields'])) $select .=',u.login as createrLogin';
        if (in_array('createrFullName', $pageParameters['fields'])) $select .=',u.fullName as createrFullName';
        if (in_array('createrAvatar', $pageParameters['fields'])) $select .=',u.avatar as createrAvatar';
        if (in_array('createrEmail', $pageParameters['fields'])) $select .=',u.email as createrEmail';
        if (in_array('url', $pageParameters['fields'])) $select .=',sp.url';
        $where = ' WHERE t.enabled != 0';
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
                    if ($field['compare'] == 'noeq') {$where .= ' AND t.createDate != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND t.createDate = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND t.createDate > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND t.createDate < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND t.createDate >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND t.createDate <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
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
                    if ($field['compare'] == 'noeq') {$where .= ' AND t.modifyDate != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND t.modifyDate = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND t.modifyDate > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND t.modifyDate < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND t.modifyDate >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND t.modifyDate <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                }
                unset($date);
            }
            if ($field['parameter'] == 'createrId')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND t.createrId != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND t.createrId = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND t.createrId > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND t.createrId < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND t.createrId >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND t.createrId <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'title')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND IF(tl.title is not null, tl.title, t.title) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND IF(tl.title is not null, tl.title, t.title) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND IF(tl.title is not null, tl.title, t.title) like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'description')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND IF(tl.description is not null, tl.description, t.description) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND IF(tl.description is not null, tl.description, t.description) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND IF(tl.description is not null, tl.description, t.description) like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'content')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND IF(tl.content is not null, tl.content, t.content) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND IF(tl.content is not null, tl.content, t.content) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND IF(tl.content is not null, tl.content, t.content) like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'avatar')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND t.avatar != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND t.avatar = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND t.avatar like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'pageType')
            {
                if ($field['value'] != 0) {$where .= ' AND t.pageType != 0';}
                if ($field['value'] == 0) {$where .= ' AND t.pageType = 0';}
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
        }
        if (is_array($categoryIds) && (count($categoryIds) > 0))
        {
            $from .= ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks l WITH l.itemId = t.id AND l.taxonomyId IN (:categoryids)';
            $where .= ' AND l.id IS NOT NULL';
            $queryparameters['categoryids'] = $categoryIds;
        }
        if (($itemIds !== null) && (is_array($itemIds)))
        {
            if (count($itemIds) == 0) $itemIds[] = 0;
            $where .= ' AND t.id IN (:itemids)';
            $queryparameters['itemids'] = $itemIds;
        }
        $order = ' ORDER BY t.ordering ASC';
        if ($pageParameters['sortField'] == 'createDate') $order = ' ORDER BY t.createDate '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'modifyDate') $order = ' ORDER BY t.modifyDate '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrId') $order = ' ORDER BY t.createrId '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'title') $order = ' ORDER BY title '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'description') $order = ' ORDER BY description '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'content') $order = ' ORDER BY content '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'avatar') $order = ' ORDER BY t.avatar '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'pageType') $order = ' ORDER BY t.pageType '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrLogin') $order = ' ORDER BY u.login '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrFullName') $order = ' ORDER BY u.fullName '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrAvatar') $order = ' ORDER BY u.avatar '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrEmail') $order = ' ORDER BY u.email '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'url') $order = ' ORDER BY sp.url '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        // Обход плагинов
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) $this->container->get($item)->getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $itemIds, $allparameters, $queryparameters, $queryparameterscount, $select, $from, $where, $order);
        // Создание запроса
        if ($queryCount == true)    
        {
            $query = $em->createQuery('SELECT count(DISTINCT t.id) as itemcount'.$from.$where)->setParameters($queryparameters);
        } else
        {
            $query = $em->createQuery($select.$from.$where.' GROUP BY t.id'.$order)->setParameters($queryparameters);
        }
        return $query;
    }
    
    public function getTaxonomyPostProcess($locale, &$params)
    {
        foreach ($params['items'] as &$textpageitem) 
        {
            if (isset($textpageitem['url']) && ($textpageitem['url'] != '')) $textpageitem['url'] = '/'.($locale != '' ? $locale.'/' : '').$textpageitem['url'];
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) $this->container->get($item)->getTaxonomyPostProcess($locale, $params);
    }
    
    public function getTaxonomyExtCatInfo(&$tree)
    {
        $categoryIds = array();
        foreach ($tree as $cat) $categoryIds[] = $cat['id'];
        $em = $this->container->get('doctrine')->getEntityManager();
        if (count($categoryIds) > 0)
        {
            $query = $em->createQuery('SELECT tl.taxonomyId, count(t.id) as textPageCount, max(t.modifyDate) as modifyDate FROM BasicCmsBundle:TaxonomiesLinks tl '.
                                      'LEFT JOIN BasicCmsBundle:TextPages t WITH t.id = tl.itemId '.
                                      'WHERE tl.taxonomyId IN (:taxonomyids) AND t.enabled != 0 GROUP BY tl.taxonomyId')->setParameter('taxonomyids', $categoryIds);
            $extInfo = $query->getResult();
            $nulltime = new \DateTime('now');
            $nulltime->setTimestamp(0);
            foreach ($tree as &$cat) 
            {
                $cat['textPageCount'] = 0;
                $cat['textPageMofidyDate'] = $nulltime;
                foreach ($extInfo as $info)
                    if ($info['taxonomyId'] == $cat['id'])
                    {
                        $cat['textPageCount'] = $info['textPageCount'];
                        $cat['textPageMofidyDate'] = $info['modifyDate'];
                    }
            }
            unset($cat);
        }
    }
    
//**************************************************
// Поиск по сайту
//**************************************************

    public function getSearchOptions(&$options)
    {
        $options['object.textpage.on'] = 'Искать информацию в текстовых материалах';
        $options['object.textpage.onsite'] = 'Искать информацию в основных страницах сайта';
        $options['object.textpage.onlypage'] = 'Искать информацию в текстовых материалах только со страницей сайта';
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.textpage.') === 0) $this->container->get($item)->getSearchOptions($options);
        }
    }
    
    public function getSearchItemCount($options, $searchstring, $locale)
    {
        $count = 0;
        if ((isset($options['object.textpage.on']) && ($options['object.textpage.on'] != 0)) || (isset($options['object.textpage.onsite']) && ($options['object.textpage.onsite'] != 0)))
        {
            $typefilter = '';
            if (isset($options['object.textpage.on']) && ($options['object.textpage.on'] != 0) && (!isset($options['object.textpage.onsite']) || ($options['object.textpage.onsite'] == 0))) $typefilter = ' AND t.pageType = 0';
            if (isset($options['object.textpage.onsite']) && ($options['object.textpage.onsite'] != 0) && (!isset($options['object.textpage.on']) || ($options['object.textpage.on'] == 0))) $typefilter = ' AND t.pageType != 0';
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT count(t.id) as textcount FROM BasicCmsBundle:TextPages t '.
                                      'LEFT JOIN BasicCmsBundle:TextPagesLocale tl WITH tl.textPageId = t.id AND tl.locale = :locale '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.textpage\' AND sp.contentAction = \'view\' AND sp.contentId = t.id '.
                                      'WHERE (IF(tl.title is not null, tl.title, t.title) LIKE :searchstring OR IF(tl.description is not null, tl.description, t.description) LIKE :searchstring OR IF(tl.content is not null, tl.content, t.content) LIKE :searchstring)'.$typefilter.
                                      ((isset($options['object.textpage.onlypage']) && ($options['object.textpage.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : ''))->setParameter('searchstring', $searchstring)->setParameter('locale', $locale);
            $textcount = $query->getResult();
            if (isset($textcount[0]['textcount'])) $count += $textcount[0]['textcount'];
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.textpage.') === 0) $count += $this->container->get($item)->getSearchItemCount($options, $searchstring, $locale);
        }
        return $count;
    }
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, &$items)
    {
        if ((isset($options['object.textpage.on']) && ($options['object.textpage.on'] != 0)) || (isset($options['object.textpage.onsite']) && ($options['object.textpage.onsite'] != 0)))
        {
            $typefilter = '';
            if (isset($options['object.textpage.on']) && ($options['object.textpage.on'] != 0) && (!isset($options['object.textpage.onsite']) || ($options['object.textpage.onsite'] == 0))) $typefilter = ' AND t.pageType = 0';
            if (isset($options['object.textpage.onsite']) && ($options['object.textpage.onsite'] != 0) && (!isset($options['object.textpage.on']) || ($options['object.textpage.on'] == 0))) $typefilter = ' AND t.pageType != 0';
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT \'textpage\' as object, t.id, IF(tl.title is not null, tl.title, t.title) as title, IF(tl.description is not null, tl.description, t.description) as description, '.
                                      't.modifyDate, t.avatar, t.createrId, u.avatar as createrAvatar, u.login as createrLogin, u.fullName as createrFullName, sp.url FROM BasicCmsBundle:TextPages t '.
                                      'LEFT JOIN BasicCmsBundle:TextPagesLocale tl WITH tl.textPageId = t.id AND tl.locale = :locale '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.textpage\' AND sp.contentAction = \'view\' AND sp.contentId = t.id '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.
                                      'WHERE (IF(tl.title is not null, tl.title, t.title) LIKE :searchstring OR IF(tl.description is not null, tl.description, t.description) LIKE :searchstring OR IF(tl.content is not null, tl.content, t.content) LIKE :searchstring)'.$typefilter.
                                      ((isset($options['object.textpage.onlypage']) && ($options['object.textpage.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : '').
                                      ($sortdir == 0 ? ' ORDER BY t.pageType DESC, t.modifyDate ASC' : ' ORDER BY t.pageType DESC, t.modifyDate DESC'))->setParameter('searchstring', $searchstring)->setParameter('locale', $locale)->setFirstResult($start)->setMaxResults($limit);
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
            if (strpos($item,'addone.textpage.') === 0) $this->container->get($item)->getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, $items);
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
            if (strpos($item,'addone.textpage.') === 0) $this->container->get($item)->getHelpChapters($chapters);
        }
        return $chapters;
    }
    
    public function getHelpContent($page, &$answer)
    {
        //if ($page == 'd1537bd0b66a4289001629ac2e06e742') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:index.html.twig',array());
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.textpage.') === 0) $answer .= $this->container->get($item)->getHelpContent($page);
        }
        return $answer;
    }
    
}
