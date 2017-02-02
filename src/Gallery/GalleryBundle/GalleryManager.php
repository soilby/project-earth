<?php

namespace Gallery\GalleryBundle;

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
        return 'object.image';
    }
    
    public function getDescription()
    {
        return 'Галерея';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Галерея', $this->container->get('router')->generate('gallery_gallery_images_list'), 10, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('image_list'));
        $manager->addAdminMenu('Создать новое изображение', $this->container->get('router')->generate('gallery_gallery_images_create'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('image_new'), 'Галерея');
        $manager->addAdminMenu('Список изображений', $this->container->get('router')->generate('gallery_gallery_images_list'), 10, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('image_list'), 'Галерея');
        if ($this->container->has('object.taxonomy'))
        {
            $manager->addAdminMenu('Категории', $this->container->get('router')->generate('basic_cms_taxonomy_list').'?object=object.image', 9999, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_list') | $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_listshow'), 'Галерея');
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) $this->container->get($item)->registerMenu();

    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('image_list','Просмотр списка изображений галереи');
        $manager->addRole('image_viewown','Просмотр собственных изображений галереи');
        $manager->addRole('image_viewall','Просмотр всех изображений галереи');
        $manager->addRole('image_new','Создание новых изображений галереи');
        $manager->addRole('image_editown','Редактирование собственных изображений галереи');
        $manager->addRole('image_editall','Редактирование всех изображений галереи');
        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) $this->container->get($item)->registerRoles();
        
     }
     
     public function getContentTypes()
     {
        $contents = array('view' => 'Просмотр изображения галереи');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) $this->container->get($item)->getContentTypes($contents);
        return $contents;
     }
     
     public function getTaxonomyType()
     {
         return true;
     }
        
     public function getTemplateTwig($contentType, $template)
     {
         if ($contentType == 'view') return 'GalleryGalleryBundle:Front:imageview.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) 
         {
             $result = $this->container->get($item)->getTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) 
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
             $query = $em->createQuery('SELECT i.id, i.createDate, i.modifyDate, i.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                       'IF(il.title is not null, il.title, i.title) as title, '.
                                       'IF(il.description is not null, il.description, i.description) as description, '.
                                       'IF(il.metaDescription is not null, il.metaDescription, i.metaDescription) as metaDescription, '.
                                       'IF(il.metaKeywords is not null, il.metaKeywords, i.metaKeywords) as metaKeywords, '.
                                       'i.template, i.avatar, i.isVideo, i.content FROM GalleryGalleryBundle:Images i '.
                                       'LEFT JOIN GalleryGalleryBundle:ImagesLocale il WITH il.imageId = i.id AND il.locale = :locale '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = i.createrId '.
                                       'WHERE i.id = :id AND i.enabled != 0')->setParameter('id', $contentId)->setParameter('locale', $locale);
             $image = $query->getResult();
             if (empty($image)) return null;
             if (isset($image[0])) $params = $image[0]; else return null;
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>$params['metaKeywords'], 'description'=>$params['metaDescription'], 'robotindex'=>true);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         $params = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
         return $params;
     }
     
     public function setFrontAction($locale, $seoPage, $action)
     {
         $result = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) $this->container->get($item)->setFrontAction($locale, $seoPage, $action, $result);
         return $result;
     }
     
     public function getModuleTypes()
     {
         $contents = array();
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) $this->container->get($item)->getModuleTypes($contents);
         return $contents;
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result = null)
     {
         $params = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             if (strpos($item,'addone.image.') === 0) $params = $this->container->get($item)->getFrontModule($locale, $seoPage, $moduleType, $parameters, $result);
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
            if (strpos($item,'addone.image.') === 0) $answer = $this->container->get($item)->getAdminModuleController($request, $module, $actionType, $nullparameters);
            if ($answer != null) return $answer;
        }
        return null;
    }

    public function getInfoTitle($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT i.title FROM GalleryGalleryBundle:Images i WHERE i.id = :id')->setParameter('id', $contentId);
             $image = $query->getResult();
             if (isset($image[0]['title']))
             {
                 $title = array();
                 $title['default'] = $image[0]['title'];
                 $query = $em->createQuery('SELECT il.title, il.locale FROM GalleryGalleryBundle:ImagesLocale il WHERE il.imageId = :id')->setParameter('id', $contentId);
                 $imagelocale = $query->getResult();
                 if (is_array($imagelocale))
                 {
                     foreach ($imagelocale as $ipitem) if ($ipitem['title'] != null) $title[$ipitem['locale']] = $ipitem['title'];
                 }
                 return $this->container->get('cms.cmsManager')->encodeLocalString($title);
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.image.') === 0) $answer = $this->container->get($item)->getInfoTitle($contentType, $contentId);
             if ($answer != null) return $answer;
         }
         return null;
    }

    public function getInfoSitemap($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT i.avatar, i.modifyDate FROM GalleryGalleryBundle:Images i WHERE i.id = :id AND i.enabled != 0')->setParameter('id', $contentId);
             $image = $query->getResult();
             if (isset($image[0]))
             {
                 $info = array();
                 $info['priority'] = '0.5';
                 $info['modifyDate'] = $image[0]['modifyDate'];
                 $info['avatar'] = $image[0]['avatar'];
                 return $info;
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.image.') === 0) $answer = $this->container->get($item)->getInfoSitemap($contentType, $contentId);
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
        $params['isVideo'] = array('type'=>'bool','ext'=>0,'name'=>'Видеоизображение');
        $params['content'] = array('type'=>'text','ext'=>0,'name'=>'Ссылка на изображение');
        $params['avatar'] = array('type'=>'text','ext'=>0,'name'=>'Аватар');
        $params['createrLogin'] = array('type'=>'text','ext'=>1,'name'=>'Логин автора');
        $params['createrFullName'] = array('type'=>'text','ext'=>1,'name'=>'Имя автора');
        $params['createrAvatar'] = array('type'=>'text','ext'=>1,'name'=>'Аватар автора');
        $params['createrEmail'] = array('type'=>'text','ext'=>1,'name'=>'E-mail автора');
        $params['url'] = array('type'=>'text','ext'=>1,'name'=>'URL страницы');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) $this->container->get($item)->getTaxonomyParameters($params);
        return $params;
    }
    
    public function getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $queryCount = false, $itemIds = null)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        $allparameters = array($pageParameters['sortField']);
        $queryparameters = array();
        foreach ($pageParameters['fields'] as $field) $allparameters[] = $field;
        foreach ($filterParameters as $field) $allparameters[] = $field['parameter'];
        $from = ' FROM GalleryGalleryBundle:Images i';
        if (in_array('createrLogin', $allparameters) || 
            in_array('createrFullName', $allparameters) ||    
            in_array('createrAvatar', $allparameters) ||    
            in_array('createrEmail', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:Users u WITH u.id = i.createrId';}
        if (in_array('title', $allparameters) || 
            in_array('description', $allparameters)) {$from .= ' LEFT JOIN GalleryGalleryBundle:ImagesLocale il WITH il.imageId = i.id AND il.locale = :locale'; $queryparameters['locale'] = $locale;}
        if (in_array('url', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.image\' AND sp.contentAction = \'view\' AND sp.contentId = i.id';}
        $select = 'SELECT i.id';
        if (in_array('createDate', $pageParameters['fields'])) $select .=',i.createDate';
        if (in_array('modifyDate', $pageParameters['fields'])) $select .=',i.modifyDate';
        if (in_array('createrId', $pageParameters['fields'])) $select .=',i.createrId';
        if (in_array('title', $pageParameters['fields']) || in_array('title', $allparameters)) $select .=',IF(il.title is not null, il.title, i.title) as title';
        if (in_array('description', $pageParameters['fields']) || in_array('description', $allparameters)) $select .=',IF(il.description is not null, il.description, i.description) as description';
        if (in_array('content', $pageParameters['fields'])) $select .=',i.content';
        if (in_array('avatar', $pageParameters['fields'])) $select .=',i.avatar';
        if (in_array('isVideo', $pageParameters['fields'])) $select .=',i.isVideo';
        if (in_array('createrLogin', $pageParameters['fields'])) $select .=',u.login as createrLogin';
        if (in_array('createrFullName', $pageParameters['fields'])) $select .=',u.fullName as createrFullName';
        if (in_array('createrAvatar', $pageParameters['fields'])) $select .=',u.avatar as createrAvatar';
        if (in_array('createrEmail', $pageParameters['fields'])) $select .=',u.email as createrEmail';
        if (in_array('url', $pageParameters['fields'])) $select .=',sp.url';
        $where = ' WHERE i.enabled != 0';
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
                    if ($field['compare'] == 'noeq') {$where .= ' AND i.createDate != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND i.createDate = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND i.createDate > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND i.createDate < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND i.createDate >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND i.createDate <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
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
                    if ($field['compare'] == 'noeq') {$where .= ' AND i.modifyDate != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND i.modifyDate = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND i.modifyDate > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND i.modifyDate < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND i.modifyDate >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND i.modifyDate <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                }
                unset($date);
            }
            if ($field['parameter'] == 'createrId')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND i.createrId != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND i.createrId = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND i.createrId > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND i.createrId < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND i.createrId >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND i.createrId <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'title')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND IF(il.title is not null, il.title, i.title) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND IF(il.title is not null, il.title, i.title) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND IF(il.title is not null, il.title, i.title) like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'description')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND IF(il.description is not null, il.description, i.description) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND IF(il.description is not null, il.description, i.description) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND IF(il.description is not null, il.description, i.description) like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'content')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND i.content != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND i.content = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND i.content like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'avatar')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND i.avatar != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND i.avatar = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND i.avatar like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'isVideo')
            {
                if ($field['value'] != 0) {$where .= ' AND i.isVideo != 0';}
                if ($field['value'] == 0) {$where .= ' AND i.isVideo = 0';}
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
            $from .= ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks l WITH l.itemId = i.id AND l.taxonomyId IN (:categoryids)';
            $where .= ' AND l.id IS NOT NULL';
            $queryparameters['categoryids'] = $categoryIds;
        }
        if (($itemIds !== null) && (is_array($itemIds)))
        {
            if (count($itemIds) == 0) $itemIds[] = 0;
            $where .= ' AND i.id IN (:itemids)';
            $queryparameters['itemids'] = $itemIds;
        }
        $order = ' ORDER BY i.ordering ASC';
        if ($pageParameters['sortField'] == 'createDate') $order = ' ORDER BY i.createDate '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'modifyDate') $order = ' ORDER BY i.modifyDate '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrId') $order = ' ORDER BY i.createrId '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'title') $order = ' ORDER BY title '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'description') $order = ' ORDER BY description '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'content') $order = ' ORDER BY i.content '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'avatar') $order = ' ORDER BY i.avatar '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'isVideo') $order = ' ORDER BY i.isVideo '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrLogin') $order = ' ORDER BY u.login '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrFullName') $order = ' ORDER BY u.fullName '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrAvatar') $order = ' ORDER BY u.avatar '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrEmail') $order = ' ORDER BY u.email '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'url') $order = ' ORDER BY sp.url '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        // Обход плагинов
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) $this->container->get($item)->getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $itemIds, $allparameters, $queryparameters, $queryparameterscount, $select, $from, $where, $order);
        // Создание запроса
        if ($queryCount == true)    
        {
            $query = $em->createQuery('SELECT count(DISTINCT i.id) as itemcount'.$from.$where)->setParameters($queryparameters);
        } else
        {
            $query = $em->createQuery($select.$from.$where.' GROUP BY i.id'.$order)->setParameters($queryparameters);
        }
        return $query;
    }
    
    public function getTaxonomyPostProcess($locale, &$params)
    {
        foreach ($params['items'] as &$imageitem) 
        {
            if (isset($imageitem['url']) && ($imageitem['url'] != '')) $imageitem['url'] = '/'.($locale != '' ? $locale.'/' : '').$imageitem['url'];
        }
        unset($imageitem);
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) $this->container->get($item)->getTaxonomyPostProcess($locale, $params);
    }
    
    public function getTaxonomyExtCatInfo(&$tree)
    {
        $categoryIds = array();
        foreach ($tree as $cat) $categoryIds[] = $cat['id'];
        $em = $this->container->get('doctrine')->getEntityManager();
        if (count($categoryIds) > 0)
        {
            $query = $em->createQuery('SELECT tl.taxonomyId, count(i.id) as imageCount, max(i.modifyDate) as modifyDate FROM BasicCmsBundle:TaxonomiesLinks tl '.
                                      'LEFT JOIN GalleryGalleryBundle:Images i WITH i.id = tl.itemId '.
                                      'WHERE tl.taxonomyId IN (:taxonomyids) AND i.enabled != 0 GROUP BY tl.taxonomyId')->setParameter('taxonomyids', $categoryIds);
            $extInfo = $query->getResult();
            $nulltime = new \DateTime('now');
            $nulltime->setTimestamp(0);
            foreach ($tree as &$cat) 
            {
                $cat['imageCount'] = 0;
                $cat['imageMofidyDate'] = $nulltime;
                foreach ($extInfo as $info)
                    if ($info['taxonomyId'] == $cat['id'])
                    {
                        $cat['imageCount'] = $info['imageCount'];
                        $cat['imageMofidyDate'] = $info['modifyDate'];
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
        $options['object.image.on'] = 'Искать информацию в изображениях';
        $options['object.image.onlypage'] = 'Искать информацию в изображениях только со страницей сайта';
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.image.') === 0) $this->container->get($item)->getSearchOptions($options);
        }
    }
    
    public function getSearchItemCount($options, $searchstring, $locale)
    {
        $count = 0;
        if (isset($options['object.image.on']) && ($options['object.image.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT count(i.id) as imagecount FROM GalleryGalleryBundle:Images i '.
                                      'LEFT JOIN GalleryGalleryBundle:ImagesLocale il WITH il.imageId = i.id AND il.locale = :locale '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.image\' AND sp.contentAction = \'view\' AND sp.contentId = i.id '.
                                      'WHERE (IF(il.title is not null, il.title, i.title) LIKE :searchstring OR IF(il.description is not null, il.description, i.description) LIKE :searchstring)'.
                                      ((isset($options['object.image.onlypage']) && ($options['object.image.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : ''))->setParameter('searchstring', $searchstring)->setParameter('locale', $locale);
            $imagecount = $query->getResult();
            if (isset($imagecount[0]['imagecount'])) $count += $imagecount[0]['imagecount'];
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.image.') === 0) $count += $this->container->get($item)->getSearchItemCount($options, $searchstring, $locale);
        }
        return $count;
    }
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, &$items)
    {
        if (isset($options['object.image.on']) && ($options['object.image.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT \'image\' as object, i.id, IF(il.title is not null, il.title, i.title) as title, IF(il.description is not null, il.description, i.description) as description, '.
                                      'i.modifyDate, i.avatar, i.createrId, u.avatar as createrAvatar, u.login as createrLogin, u.fullName as createrFullName, sp.url FROM GalleryGalleryBundle:Images i '.
                                      'LEFT JOIN GalleryGalleryBundle:ImagesLocale il WITH il.imageId = i.id AND il.locale = :locale '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.image\' AND sp.contentAction = \'view\' AND sp.contentId = i.id '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = i.createrId '.
                                      'WHERE (IF(il.title is not null, il.title, i.title) LIKE :searchstring OR IF(il.description is not null, il.description, i.description) LIKE :searchstring)'.
                                      ((isset($options['object.image.onlypage']) && ($options['object.image.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : '').
                                      ($sortdir == 0 ? ' ORDER BY i.modifyDate ASC' : ' ORDER BY i.modifyDate DESC'))->setParameter('searchstring', $searchstring)->setParameter('locale', $locale)->setFirstResult($start)->setMaxResults($limit);
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
            if (strpos($item,'addone.image.') === 0) $this->container->get($item)->getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, $items);
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
            if (strpos($item,'addone.image.') === 0) $this->container->get($item)->getHelpChapters($chapters);
        }
        return $chapters;
    }
    
    public function getHelpContent($page, &$answer)
    {
        //if ($page == 'd1537bd0b66a4289001629ac2e06e742') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:index.html.twig',array());
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.image.') === 0) $answer .= $this->container->get($item)->getHelpContent($page);
        }
        return $answer;
    }
    
}
