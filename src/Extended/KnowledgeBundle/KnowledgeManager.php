<?php

namespace Extended\KnowledgeBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Facebook\Facebook;

class KnowledgeManager
{
    
    public function postToFacebook($message, $url = null)
    {
        try {
            $facebookcfg = $this->container->get('cms.cmsManager')->getConfigValue('alerts.facebookcfg');
            if (!is_array($facebookcfg)) {
                $facebookcfg = array();
            }
            if (isset($facebookcfg['appid']) && isset($facebookcfg['secret']) && isset($facebookcfg['token']) && isset($facebookcfg['group']) && ($facebookcfg['appid'] != '') && ($facebookcfg['secret'] != '') && ($facebookcfg['token'] != '') && ($facebookcfg['group'] != ''))
            {
                $facebook = new Facebook(array(
                    'app_id'  => $facebookcfg['appid'],
                    'app_secret' => $facebookcfg['secret'],
                    'default_access_token' => $facebookcfg['token'],
                ));
                $data = array('message' => $message);
                if ($url !== null) {
                    $data['link'] = $url;
                }
                $response = $facebook->post('/'.$facebookcfg['group'].'/feed', $data);
                /*$graphObject = $response->getGraphObject();

                if ($graphObject->getProperty('id') != '') 
                {
                }*/
            }    
            $vkcfg = $this->container->get('cms.cmsManager')->getConfigValue('alerts.vkontaktecfg');
            if (!is_array($vkcfg)) {
                $vkcfg = array();
            }
            if (isset($vkcfg['appid']) && isset($vkcfg['secret']) && isset($vkcfg['token']) && isset($vkcfg['group']) && ($vkcfg['appid'] != '') && ($vkcfg['secret'] != '') && ($vkcfg['token'] != '') && ($vkcfg['group'] != '')) {
                $vkurl = "https://api.vk.com/method/wall.post?access_token={$vkcfg['token']}";
                $data = array(
                    'owner_id' => 0 - intval($vkcfg['group']),
                    'friends_only' => 0,
                    'from_group' => 1,
                    'message' => $message,
                    'attachments' => $url,
                    'signed' => 1,
                );
                $ch = curl_init($vkurl);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
            }        
            return true;
        } catch (\Exception $e) {
        }
        return false;
    }
    
    
    
    public function checkKnowledgeObject($objectType, $objectId, $objectTextId)
    {
        $objectEnt = $this->container->get('doctrine')->getRepository('ExtendedKnowledgeBundle:KnowledgeObject')->findOneBy(array(
            'objectType' => $objectType,
            'objectId' => $objectId,
            'objectTextId' => $objectTextId,
        ));
        if (empty($objectEnt)) {
            return false;
        }
        return $objectEnt;
    }
    
    
    public function addKnowledgeChange($objectType, $objectId, $objectTextId, $changeType, $changeValue, $description = '')
    {
        if ($description == null) {
            $description = '';
        }
        $em = $this->container->get('doctrine')->getEntityManager();
        $objectEnt = $this->container->get('doctrine')->getRepository('ExtendedKnowledgeBundle:KnowledgeObject')->findOneBy(array(
            'objectType' => $objectType,
            'objectId' => $objectId,
            'objectTextId' => $objectTextId,
        ));
        if (empty($objectEnt)) {
            return false;
        }
        $changeEnt = new \Extended\KnowledgeBundle\Entity\KnowledgeChanges();
        $changeEnt->setChangeType($changeType);
        $changeEnt->setChangeValue($changeValue);
        $changeEnt->setCreateDate(new \DateTime('now'));
        $changeEnt->setCreaterId($this->container->get('cms.cmsManager')->getCurrentUser()->getId());
        $changeEnt->setDescription($description);
        $changeEnt->setObjectId($objectEnt->getId());
        $em->persist($changeEnt);
        $em->flush();
    }
    
    public function addKnowledgeObject($objectType, $objectId, $objectTextId, $title, $types)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        if (!is_array($title)) {
            $title = array('default' => $title);
        }
        if (!isset($title['default'])) {
            $title['default'] = '';
        }
        
        $count = $em->createQuery('SELECT count(o.id) FROM ExtendedKnowledgeBundle:KnowledgeObject o WHERE o.objectType = :type AND o.objectId = :id AND o.objectTextId = :textid')
                    ->setParameter('type', $objectType)
                    ->setParameter('id', $objectId)
                    ->setParameter('textid', $objectTextId)
                    ->getSingleScalarResult();
        if ($count != 0) {
            return false;
        }
        $objectEnt = new \Extended\KnowledgeBundle\Entity\KnowledgeObject();
        $objectEnt->setCreateDate(new \DateTime("now"));
        $objectEnt->setCreaterId($this->container->get('cms.cmsManager')->getCurrentUser()->getId());
        $objectEnt->setObjectId($objectId);
        $objectEnt->setObjectTextId($objectTextId);
        $objectEnt->setObjectType($objectType);
        $objectEnt->setRatingNegative(0);
        $objectEnt->setRatingPositive(0);
        $objectEnt->setTitle($this->container->get('cms.cmsManager')->encodeLocalString($title));
        $objectEnt->setDefaultTitle($title['default']);
        $em->persist($objectEnt);
        $em->flush();
        $existsTypes = $this->getKnowledgeTypes();
        if (is_array($types)) {
            foreach ($types as $typeId) {
                if (!isset($existsTypes[$typeId])) {
                    continue;
                }
                $linkEnt = new \Extended\KnowledgeBundle\Entity\KnowledgeTypeLink();
                $linkEnt->setItemId($objectEnt->getId());
                $linkEnt->setTypeId($typeId);
                $em->persist($linkEnt);
                $em->flush();
            }
        }
        return true;
    }
    
    public function changeKnowledgeObject($objectType, $objectId, $objectTextId, $title, $types, $description = '')
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        $objectEnt = $this->container->get('doctrine')->getRepository('ExtendedKnowledgeBundle:KnowledgeObject')->findOneBy(array(
            'objectType' => $objectType,
            'objectId' => $objectId,
            'objectTextId' => $objectTextId,
        ));
        if (empty($objectEnt)) {
            return false;
        }
        $objectEnt->setTitle($this->container->get('cms.cmsManager')->encodeLocalString($title));
        $objectEnt->setDefaultTitle($title['default']);
        $em->flush();
        $existsTypes = $this->getKnowledgeTypes();
        if (!is_array($types)) {
            $types = array();
        }
        $checkedTypes = array();
        foreach ($types as $typeId) {
            if (isset($existsTypes[$typeId])) {
                $checkedTypes[] = $typeId;
            }
        }
        $links = $this->container->get('doctrine')->getRepository('ExtendedKnowledgeBundle:KnowledgeTypeLink')->findBy(array('itemId' => $objectEnt->getId()));
        $registredIds = array();
        foreach ($links as $link) {
            if (!in_array($link->getTypeId(), $checkedTypes)) {
                $em->remove($link);
                $em->flush();
            } else {
                $registredIds[] = $link->getTypeId();
            }
        }
        $newLinks = array_diff($checkedTypes, $registredIds);
        foreach ($newLinks as $newId) {
            $linkEnt = new \Extended\KnowledgeBundle\Entity\KnowledgeTypeLink();
            $linkEnt->setItemId($objectEnt->getId());
            $linkEnt->setTypeId($newId);
            $em->persist($linkEnt);
            $em->flush();
        }
        $this->addKnowledgeChange($objectType, $objectId, $objectTextId, Entity\KnowledgeChanges::KC_CT_CONTENT, 0, $description);
    }
    
    
    public function getKnowledgeParameters($locale, $objectType = null, $objectId = null, $objectTextId = null)
    {
        $result = array(
            'locales' => array(),
            'types' => $this->getKnowledgeTypes(true, $locale),
            'objectTitle' => array(),
            'objectTypes' => array(),
            'registred' => 0,
        );
        $em = $this->container->get('doctrine')->getEntityManager();
        $locales = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l')->getResult();
        foreach ($locales as $locale2) {
            $result['locales'][$locale2['shortName']] = $locale2['fullName'];
        }
        if (($objectType === null) || ($objectId === null)) {
            return $result;
        }
        $objectEnt = $this->container->get('doctrine')->getRepository('ExtendedKnowledgeBundle:KnowledgeObject')->findOneBy(array(
            'objectType' => $objectType,
            'objectId' => $objectId,
            'objectTextId' => $objectTextId,
        ));
        if (empty($objectEnt)) {
            return $result;
        }
        $result['registred'] = 1;
        $result['objectTitle']['default'] = $this->container->get('cms.cmsManager')->decodeLocalString($objectEnt->getTitle(), 'default', false);
        foreach ($locales as $localitem) {
            $result['objectTitle'][$localitem['shortName']] = $this->container->get('cms.cmsManager')->decodeLocalString($objectEnt->getTitle(), $localitem['shortName'], false);
        }
        $links = $this->container->get('doctrine')->getRepository('ExtendedKnowledgeBundle:KnowledgeTypeLink')->findBy(array('itemId' => $objectEnt->getId()));
        foreach ($links as $link) {
            $result['objectTypes'][] = $link->getTypeId();
        }
        $result['objectTitleForLocale'] = $this->container->get('cms.cmsManager')->decodeLocalString($objectEnt->getTitle(), $locale, true);
        return $result;
    }
    
    public function getKnowledgeInfo()
    {
        return array(
            'types' => array(
                'document' => Entity\KnowledgeObject::KO_TYPE_DOCUMENT,
                'file' => Entity\KnowledgeObject::KO_TYPE_FILE,
            ),
            
            
        );
    }
    
    
    private function typesGetTree($tree, $id, $nesting, &$cats)
    {
        foreach ($tree as $cat)
        {
            if ($cat['parent'] == $id)
            {
                $cats[] = array('name' => $cat['title'], 'id' => $cat['id'], 'enabled' => $cat['enabled'], 'nesting' => $nesting, 'error' => '');
                $this->typesGetTree($tree, $cat['id'], $nesting + 1, $cats);
            }
        }
    }
    
    public function getKnowledgeTypes($expanded = false, $locale = 'default')
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        $tree = $em->createQuery('SELECT t.id, IF(tl.title is not null, tl.title, t.title) as title, t.enabled, t.parent FROM ExtendedKnowledgeBundle:KnowledgeType t '.
                                  'LEFT JOIN ExtendedKnowledgeBundle:KnowledgeTypeLocale tl WITH tl.typeId = t.id AND tl.locale = :locale '.
                                  'ORDER BY t.ordering')->setParameter('locale', $locale)->getResult();
        $result = array();
        if ($expanded) {
            $this->typesGetTree($tree, null, 0, $result);
        } else {
            foreach ($tree as $treeItem) {
                $result[$treeItem['id']] = $treeItem['title'];
            }
        }
        return $result;
    }
    
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'object.knowledge';
    }
    
    public function getDescription()
    {
        return 'База знаний';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Реестр объектов', $this->container->get('router')->generate('extended_knowledge_list'), 1, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('knowledge_list'));
        $manager->addAdminMenu('Браузер объектов', $this->container->get('router')->generate('extended_knowledge_list'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('knowledge_list'), 'Реестр объектов');
        /*$manager->addAdminMenu('Создать представление', $this->container->get('router')->generate('basic_cms_taxonomyshow_create'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_newshow'), 'Реестр объектов');*/
        $manager->addAdminMenu('Дерево объектов', $this->container->get('router')->generate('extended_knowledge_typelist'), 10, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('knowledge_typelist'), 'Реестр объектов');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) $this->container->get($item)->registerMenu();
    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('knowledge_list','Просмотр реестра объектов');
        $manager->addRole('knowledge_typelist','Настройка дерева объектов');
        $manager->addRole('knowledge_addobject','Добавление в реестр объектов');
        $manager->addRole('knowledge_editobject','Изменение объектов в реестре');
/*        $manager->addRole('taxonomy_new','Создание новых категорий');
        $manager->addRole('taxonomy_edit','Редактирование категорий');

        $manager->addRole('taxonomy_listshow','Просмотр списка представлений');
        $manager->addRole('taxonomy_viewshow','Просмотр представлений');
        $manager->addRole('taxonomy_newshow','Создание новых представлений');
        $manager->addRole('taxonomy_editshow','Редактирование представлений');*/
        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) $this->container->get($item)->registerRoles();
        
     }
     
     public function getContentTypes()
     {
        $contents = array(/*'view' => 'Просмотр категории','viewshow' => 'Просмотр представления'*/);
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) $this->container->get($item)->getContentTypes($contents);
        return $contents;
     }
     
     public function getTaxonomyType()
     {
         return false;
     }

     public function getTemplateTwig($contentType, $template)
     {
         /*if ($contentType == 'view') return 'BasicCmsBundle:Front:taxonomyview.html.twig';
         if ($contentType == 'viewshow') return 'BasicCmsBundle:Front:taxonomyshowview.html.twig';*/
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) 
         {
             $result = $this->container->get($item)->getTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         /*if ($contentType == 'taxonomy_show') return 'BasicCmsBundle:Front:moduletaxonomyshow.html.twig';
         if ($contentType == 'taxonomy_menu') return 'BasicCmsBundle:Front:moduletaxonomymenu.html.twig';*/
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) 
         {
             $result = $this->container->get($item)->getModuleTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }

    /*private function taxonomyGetTreeForFrontView($tree, $id, $nesting, &$cats)
    {
        foreach ($tree as $cat)
        {
            if ($cat['parent'] == $id)
            {
                $cats[] = array_merge($cat, array('nesting' => $nesting));
                $this->taxonomyGetTreeForFrontModule($tree, $cat['id'], $nesting + 1, $cats, 0);
            }
        }
    }*/
     
     private function transliterate($text) 
     {
        $converter = array(
        'а' => 'a',   'б' => 'b',   'в' => 'v',
        'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
        'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',
        'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',
        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
        'ь' => '',  'ы' => 'y',   'ъ' => '',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
        'А' => 'A',   'Б' => 'B',   'В' => 'V',
        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
        'И' => 'I',   'Й' => 'Y',   'К' => 'K',
        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
        'О' => 'O',   'П' => 'P',   'Р' => 'R',
        'С' => 'S',   'Т' => 'T',   'У' => 'U',
        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
        'Ь' => '',  'Ы' => 'Y',   'Ъ' => '',
        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya');
        return strtr($text, $converter);
     }
     
     public function getFrontContent($locale, $contentType, $contentId, $result = null)
     {
         if ($contentType == 'viewblogsprofile') {
             $em = $this->container->get('doctrine')->getEntityManager();
             $user = $this->container->get('cms.cmsManager')->getCurrentUser();
             if ($user == null) {
                 return null;
             }
             $locales = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l')->getResult();
             $request = $this->container->get('request_stack')->getCurrentRequest();
             $textEntity = $em->getRepository('BasicCmsBundle:TextPages')->find(intval($request->get('editpost')));             
             if (!empty($textEntity) && ($textEntity->getCreaterId() == $user->getId())) {
                 // Найти локали
                 $textEntityLocales = array();
                 foreach ($locales as $localeItem) {
                     $textEntityLocales[$localeItem['shortName']] = $this->container->get('doctrine')->getRepository('BasicCmsBundle:TextPagesLocale')->findOneBy(array('textPageId' => $textEntity->getId(), 'locale' => $localeItem['shortName']));
                     if ($textEntityLocales[$localeItem['shortName']] == null) {
                         $textEntityLocales[$localeItem['shortName']] = new \Basic\CmsBundle\Entity\TextPagesLocale();
                         $em->persist($textEntityLocales[$localeItem['shortName']]);
                         $textEntityLocales[$localeItem['shortName']]->setDescription('');
                         $textEntityLocales[$localeItem['shortName']]->setLocale($localeItem['shortName']);
                         $textEntityLocales[$localeItem['shortName']]->setMetaDescription('');
                         $textEntityLocales[$localeItem['shortName']]->setMetaKeywords('');
                         $textEntityLocales[$localeItem['shortName']]->setTextPageId(0);
                         $textEntityLocales[$localeItem['shortName']]->setTitle('');
                         $textEntityLocales[$localeItem['shortName']]->setContent('');
                     }
                 }
                 // приём данных
                 $errors = array();
                 $result = false;
                 // принять данные
                 if ($request->getMethod() == 'POST') {
                     $titlePost = $request->get('title');
                     $descriptionPost = $request->get('description');
                     $contentPost = $request->get('content');
                     if (isset($titlePost['default'])) {
                         $textEntity->setTitle($titlePost['default']);
                     }
                     if (!preg_match('/^.{1,}$/ui', $textEntity->getTitle())) {
                         $errors['title_default'] = ($locale == 'en' ? 'The title should contain more than 3 symbols' : 'Заголовок должен содержать более 3 символов');
                     }
                     if (isset($descriptionPost['default'])) {
                         $textEntity->setDescription($descriptionPost['default']);
                     }
                     if (isset($contentPost['default'])) {
                         $textEntity->setContent($contentPost['default']);
                     }
                     foreach ($locales as $localeItem) {
                        if (isset($titlePost[$localeItem['shortName']])) {
                            $textEntityLocales[$localeItem['shortName']]->setTitle(($titlePost[$localeItem['shortName']] != '' ? $titlePost[$localeItem['shortName']] : null));
                        }
                        if (isset($descriptionPost[$localeItem['shortName']])) {
                            $textEntityLocales[$localeItem['shortName']]->setDescription(($descriptionPost[$localeItem['shortName']] != '' ? $descriptionPost[$localeItem['shortName']] : null));
                        }
                        if (isset($contentPost[$localeItem['shortName']])) {
                            $textEntityLocales[$localeItem['shortName']]->setContent(($contentPost[$localeItem['shortName']] != '' ? $contentPost[$localeItem['shortName']] : null));
                        }
                        if (!preg_match('/^.{1,}$/ui', $textEntityLocales[$localeItem['shortName']]->getTitle())) {
                            $errors['title_'.$localeItem['shortName']] = ($locale == 'en' ? 'The title should contain more than 3 symbols' : 'Заголовок должен содержать более 3 символов');
                        }
                     }
                     // сохранить
                     if (count($errors) == 0) {
                         foreach ($textEntityLocales as $textEntityLocale) {
                             $textEntityLocale->setTextPageId($textEntity->getId());
                         }
                         $em->flush();
                         $result = true;
                     }
                 }
                 // вывод
                 $params = array(
                     'editMode' => 'editpost='.$textEntity->getId(),
                     'locales' => $locales,
                     'textPage' => $textEntity,
                     'textPageLocales' => $textEntityLocales,
                     'errors' => $errors,
                     'result' => $result,
                 );
                 $params['meta'] = array('title'=>'Edit blog post', 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
                 $params['template'] = '';
                 return $params;
             }
             $blogType = $request->get('blogtype');
             $blog = $request->get('blog');
             if ($blogType == 'village') {
                 $blogContainer = $em->getRepository('BasicCmsBundle:Taxonomies')->find($blog);
                 if (empty($blogContainer) || ($blogContainer->getCreaterId() != $user->getId())) {
                     return null;
                 }
             } elseif ($blogType == 'house') {
                 $blogContainer = $em->getRepository('BasicCmsBundle:Taxonomies')->find($blog);
                 if (empty($blogContainer) || ($blogContainer->getCreaterId() != $user->getId())) {
                     return null;
                 }
             } elseif ($blogType == 'project') {
                 $blogContainer = $em->getRepository('ExtendedProjectBundle:Projects')->find($blog);
                 $moderatorcheck = $em
                         ->createQuery('SELECT count(pu.id) FROM ExtendedProjectBundle:ProjectUsers pu WHERE pu.projectRole = 3 AND pu.userId = :userid AND pu.projectId = :id')
                         ->setParameters(array('id' => $blogContainer->getId(), 'userid' => $user->getId()))
                         ->getSingleScalarResult();
                 if (empty($blogContainer) || ($blogContainer->getEnabled() == 0) || (($blogContainer->getCreaterId() != $user->getId()) && ($moderatorcheck == 0))) {
                     return null;
                 }
             }
             if (isset($blogContainer)) {
                 // добавить новое сообщение блога
                 $result = false;
                 if ($request->getMethod() == 'POST') {
                     $textPage = new \Basic\CmsBundle\Entity\TextPages();
                     $textPage->setAvatar('');
                     $textPage->setCreateDate(new \DateTime('now'));
                     $textPage->setCreaterId($user->getId());
                     $textPage->setEnabled(1);
                     $textPage->setMetaDescription('');
                     $textPage->setMetaKeywords('');
                     $textPage->setModifyDate(new \DateTime('now'));
                     $textPage->setOrdering(0);
                     $textPage->setPageType(0);
                     $textPage->setTemplate('');
                     $textPage->setTitle('Undefined');
                     $textPage->setDescription('');
                     $textPage->setContent('');
                     foreach ($locales as $localeItem) {
                         $textPageLocales[$localeItem['shortName']] = new \Basic\CmsBundle\Entity\TextPagesLocale();
                         $textPageLocales[$localeItem['shortName']]->setLocale($localeItem['shortName']);
                         $textPageLocales[$localeItem['shortName']]->setMetaDescription(null);
                         $textPageLocales[$localeItem['shortName']]->setMetaKeywords(null);
                     }
                     $titlePost = $request->get('title');
                     $descriptionPost = $request->get('description');
                     $contentPost = $request->get('content');
                     if (isset($titlePost['default'])) {
                         $textPage->setTitle($titlePost['default']);
                     }
                     if (isset($descriptionPost['default'])) {
                         $textPage->setDescription($descriptionPost['default']);
                     }
                     if (isset($contentPost['default'])) {
                         $textPage->setContent($contentPost['default']);
                     }
                     $em->persist($textPage);
                     $em->flush();
                     foreach ($locales as $localeItem) {
                        $textPageLocales[$localeItem['shortName']]->setTextPageId($textPage->getId());
                        if (isset($titlePost[$localeItem['shortName']])) {
                            $textPageLocales[$localeItem['shortName']]->setTitle(($titlePost[$localeItem['shortName']] != '' ? $titlePost[$localeItem['shortName']] : null));
                        }
                        if (isset($descriptionPost[$localeItem['shortName']])) {
                            $textPageLocales[$localeItem['shortName']]->setDescription(($descriptionPost[$localeItem['shortName']] != '' ? $descriptionPost[$localeItem['shortName']] : null));
                        }
                        if (isset($contentPost[$localeItem['shortName']])) {
                            $textPageLocales[$localeItem['shortName']]->setContent(($contentPost[$localeItem['shortName']] != '' ? $contentPost[$localeItem['shortName']] : null));
                        }
                        $em->persist($textPageLocales[$localeItem['shortName']]);
                     }
                     $em->flush();
                     if ($blogContainer instanceof \Basic\CmsBundle\Entity\Taxonomies) {
                        $link = new \Basic\CmsBundle\Entity\TaxonomiesLinks();
                        $link->setItemId($textPage->getId());
                        $link->setTaxonomyId($blogContainer->getId());
                     } elseif ($blogContainer instanceof \Extended\ProjectBundle\Entity\Projects) {
                        $link = new \Extended\ProjectBundle\Entity\ProjectBlogLink();
                        $link->setItemId($textPage->getId());
                        $link->setProjectId($blogContainer->getId());
                     }
                     $em->persist($link);
                     $em->flush();
                     $result = true;
                         if ($blogContainer instanceof \Basic\CmsBundle\Entity\Taxonomies) {
                             $seoPage = $this->container->get('doctrine')->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.taxonomy', 'contentId'=>$blogContainer->getId(), 'contentAction'=>'view'));
                         } elseif ($blogContainer instanceof \Extended\ProjectBundle\Entity\Projects) {
                             $seoPage = $this->container->get('doctrine')->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.project', 'contentId'=>$blogContainer->getId(), 'contentAction'=>'view'));
                         }
                         $textPageEn = (isset($textPageLocales['en']) ? $textPageLocales['en'] : $textPage);
                         $message = 'News from "Project Earth" site project-earth.community'."\n".'Category: ';
                         $breadcrumbs = $this->container->get('cms.cmsManager')->getBreadCrumbs($seoPage, 'en');
                         for ($i = 0; $i < count($breadcrumbs); $i++) {
                             if ($i == 0) {
                                 continue;
                             }
                             if ($i > 1) {
                                 $message .= ' > ';
                             }
                             if ($i == 1) {
                                 $message .= 'Settlement "'.$breadcrumbs[$i]['title'].'"';
                             } elseif ($i == 2) {
                                 $message .= 'estate "'.$breadcrumbs[$i]['title'].'"';
                             } else {
                                 $message .= $breadcrumbs[$i]['title'];
                             }
                         }
                         $message .= "\nBlog:\n";
                         $message .= 'Posted '.$textPage->getCreateDate()->format('d.m.Y H:i').' by '.$this->transliterate($user->getFullName())."\n".($textPageEn->getTitle() != null ? $textPageEn->getTitle() : $textPage->getTitle())."\n";
                         $message .= strip_tags(htmlspecialchars_decode(nl2br(($textPageEn->getDescription() != null ? $textPageEn->getDescription() : $textPage->getDescription())."\n".($textPageEn->getContent() != null ? $textPageEn->getContent() : $textPage->getContent()))))."\n--------------------\n";
                         $message .= 'Новость с сайта "Проект Земля" project-earth.community'."\n".'Раздел: ';
                         $breadcrumbs = $this->container->get('cms.cmsManager')->getBreadCrumbs($seoPage, '');
                         for ($i = 0; $i < count($breadcrumbs); $i++) {
                             if ($i == 0) {
                                 continue;
                             }
                             if ($i > 1) {
                                 $message .= ' > ';
                             }
                             if ($i == 1) {
                                 $message .= 'Поселение "'.$breadcrumbs[$i]['title'].'"';
                             } elseif ($i == 2) {
                                 $message .= 'поместье "'.$breadcrumbs[$i]['title'].'"';
                             } else {
                                 $message .= $breadcrumbs[$i]['title'];
                             }
                         }
                         $message .= "\nБлог:\n";
                         $message .= 'Опубликован '.$textPage->getCreateDate()->format('d.m.Y H:i').' от '.$user->getFullName()."\n".$textPage->getTitle()."\n";
                         $message .= strip_tags(htmlspecialchars_decode(nl2br($textPage->getDescription()."\n".$textPage->getContent())));
                         $this->postToFacebook($message);
                 }
                 // найти сообщения блога
                 if ($blogContainer instanceof \Basic\CmsBundle\Entity\Taxonomies) {
                    $query = $em->createQuery('SELECT t.id, t.createDate, t.modifyDate, t.createrId, '.
                                              'IF(tl.title is not null, tl.title, t.title) as title, '.
                                              'IF(tl.description is not null, tl.description, t.description) as description, '.
                                              'IF(tl.content is not null, tl.content, t.content) as content '.
                                              'FROM BasicCmsBundle:TextPages t '.
                                              'LEFT JOIN BasicCmsBundle:TextPagesLocale tl WITH tl.textPageId = t.id AND tl.locale = :locale '.
                                              'LEFT JOIN BasicCmsBundle:TaxonomiesLinks l WITH l.itemId = t.id AND l.taxonomyId = :categoryid '.
                                              'WHERE l.id IS NOT NULL AND t.enabled != 0 ORDER BY t.createDate ASC')->setParameter('categoryid', $blogContainer->getId())->setParameter('locale', $locale);
                 } elseif ($blogContainer instanceof \Extended\ProjectBundle\Entity\Projects) {
                    $query = $em->createQuery('SELECT t.id, t.createDate, t.modifyDate, t.createrId, '.
                                              'IF(tl.title is not null, tl.title, t.title) as title, '.
                                              'IF(tl.description is not null, tl.description, t.description) as description, '.
                                              'IF(tl.content is not null, tl.content, t.content) as content '.
                                              'FROM BasicCmsBundle:TextPages t '.
                                              'LEFT JOIN BasicCmsBundle:TextPagesLocale tl WITH tl.textPageId = t.id AND tl.locale = :locale '.
                                              'LEFT JOIN ExtendedProjectBundle:ProjectBlogLink l WITH l.itemId = t.id AND l.projectId = :categoryid '.
                                              'WHERE l.id IS NOT NULL AND t.enabled != 0 ORDER BY t.createDate ASC')->setParameter('categoryid', $blogContainer->getId())->setParameter('locale', $locale);
                 }
                 $posts = $query->getResult();
                 // вывод
                 $params = array(
                     'blogType' => $blogType,
                     'blog' => $blogContainer,
                     'posts' => $posts,
                     'result' => $result,
                     'locales' => $locales,
                 );
                 $params['meta'] = array('title'=>'My blog '.$blogContainer->getTitle(), 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
                 $params['template'] = '';
                 return $params;
             }
             // найти блоги
             $vilages = $em->createQuery('SELECT t.id, IF(tl.title is not null, tl.title, t.title) as title, \'village\' as blogType '.
                                         'FROM BasicCmsBundle:Taxonomies t '.
                                         'LEFT JOIN BasicCmsBundle:TaxonomiesLocale tl WITH tl.taxonomyId = t.id AND tl.locale = :locale '.
                                         'WHERE t.template = \'mir_taxonomy_viewvillage\' AND t.enabled != 0 AND t.createrId = :id and t.parent = 19')->setParameter('id', $user->getId())->setParameter('locale', $locale)->getResult();
             $houses = $em->createQuery('SELECT t.id, IF(tl.title is not null, tl.title, t.title) as title, \'house\' as blogType '.
                                        'FROM BasicCmsBundle:Taxonomies t '.
                                        'LEFT JOIN BasicCmsBundle:TaxonomiesLocale tl WITH tl.taxonomyId = t.id AND tl.locale = :locale '.
                                        'WHERE t.template = \'mir_taxonomy_viewvillagehouse\' AND t.enabled != 0 AND t.createrId = :id')->setParameter('id', $user->getId())->setParameter('locale', $locale)->getResult();
             $projects = $em->createQuery('SELECT p.id, IF(pl.title is not null, pl.title, p.title) as title, \'project\' as blogType '.
                                        'FROM ExtendedProjectBundle:Projects p '.
                                        'LEFT JOIN ExtendedProjectBundle:ProjectsLocale pl WITH pl.projectId = p.id AND pl.locale = :locale '.
                                        'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.userId = :id AND pu.projectId = p.id AND pu.projectRole = 3 '.
                                        'WHERE p.enabled != 0 AND (p.createrId = :id OR pu IS NOT NULL)')->setParameter('id', $user->getId())->setParameter('locale', $locale)->getResult();
             $blogs = array_merge($vilages, $houses, $projects);
             // вывод
             $params = array(
                 'blogs' => $blogs,
             );
             $params['meta'] = array('title'=>'My blogs', 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
             $params['template'] = '';
             return $params;
         }
         if ($contentType == 'viewvilagesprofile') {
             $em = $this->container->get('doctrine')->getEntityManager();
             $user = $this->container->get('cms.cmsManager')->getCurrentUser();
             if ($user == null) {
                 return null;
             }
             // создание или редактирование поместья
             $request = $this->container->get('request_stack')->getCurrentRequest();
             $editMode = $request->get('edit');
             $parent = $request->get('parent');
             if ($editMode !== null) {
                 // найти локали
                 $locales = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l')->getResult();
                 // найти старую категорию
                 if ($editMode === 'newvilage') {
                     $ordering = $em->createQuery('SELECT MAX(t.ordering) as maxordering FROM BasicCmsBundle:Taxonomies t WHERE t.parent = :id')->setParameter('id', 19)->getSingleScalarResult();
                     $taxonomy = new \Basic\CmsBundle\Entity\Taxonomies();
                     $em->persist($taxonomy);
                     $taxonomy->setAvatar('');
                     $taxonomy->setCreateDate(new \DateTime('now'));
                     $taxonomy->setCreaterId($user->getId());
                     $taxonomy->setDescription('');
                     $taxonomy->setEnabled(1);
                     $taxonomy->setEnableAdd(1);
                     $taxonomy->setMetaDescription('');
                     $taxonomy->setMetaKeywords('');
                     $taxonomy->setModifyDate(new \DateTime('now'));
                     $taxonomy->setObject('object.textpage');
                     $taxonomy->setOrdering($ordering + 1);
                     $taxonomy->setTemplate('mir_taxonomy_viewvillage');
                     $taxonomy->setTitle('');
                     $taxonomy->setFirstParent(19);
                     $taxonomy->setParent(19);
                     $taxonomy->setPageParameters('a:12:{s:17:"includeCategories";i:1;s:13:"getExtCatInfo";i:0;s:13:"includeChilds";i:0;s:13:"includeParent";i:0;s:9:"sortField";s:10:"createDate";s:13:"sortDirection";i:0;s:9:"sortGetOn";i:0;s:10:"countValue";s:4:"1000";s:10:"countGetOn";i:0;s:9:"pageValue";s:1:"0";s:9:"pageGetOn";i:0;s:6:"fields";a:6:{i:0;s:10:"createDate";i:1;s:5:"title";i:2;s:11:"description";i:3;s:7:"content";i:4;s:12:"createrLogin";i:5;s:15:"createrFullName";}}');
                     $taxonomy->setFilterParameters('a:0:{}');
                 } elseif ($editMode === 'newhouse') {
                     $parentEnt = $this->container->get('doctrine')->getRepository('BasicCmsBundle:Taxonomies')->find($parent);
                     if ($parentEnt->getCreaterId() != $user->getId()) {
                         return null;
                     }
                     $ordering = $em->createQuery('SELECT MAX(t.ordering) as maxordering FROM BasicCmsBundle:Taxonomies t WHERE t.parent = :id')->setParameter('id', $parent)->getSingleScalarResult();
                     $taxonomy = new \Basic\CmsBundle\Entity\Taxonomies();
                     $em->persist($taxonomy);
                     $taxonomy->setAvatar('');
                     $taxonomy->setCreateDate(new \DateTime('now'));
                     $taxonomy->setCreaterId($user->getId());
                     $taxonomy->setDescription('');
                     $taxonomy->setEnabled(1);
                     $taxonomy->setEnableAdd(1);
                     $taxonomy->setMetaDescription('');
                     $taxonomy->setMetaKeywords('');
                     $taxonomy->setModifyDate(new \DateTime('now'));
                     $taxonomy->setObject('object.textpage');
                     $taxonomy->setOrdering($ordering + 1);
                     $taxonomy->setTemplate('mir_taxonomy_viewvillagehouse');
                     $taxonomy->setTitle('');
                     $taxonomy->setFirstParent(19);
                     $taxonomy->setParent($parent);
                     $taxonomy->setPageParameters('a:12:{s:17:"includeCategories";i:1;s:13:"getExtCatInfo";i:0;s:13:"includeChilds";i:0;s:13:"includeParent";i:0;s:9:"sortField";s:10:"createDate";s:13:"sortDirection";i:0;s:9:"sortGetOn";i:0;s:10:"countValue";s:4:"1000";s:10:"countGetOn";i:0;s:9:"pageValue";s:1:"0";s:9:"pageGetOn";i:0;s:6:"fields";a:6:{i:0;s:10:"createDate";i:1;s:5:"title";i:2;s:11:"description";i:3;s:7:"content";i:4;s:12:"createrLogin";i:5;s:15:"createrFullName";}}');
                     $taxonomy->setFilterParameters('a:0:{}');
                 } else {
                     $taxonomy = $this->container->get('doctrine')->getRepository('BasicCmsBundle:Taxonomies')->find($editMode);
                 }
                 $seoPage = $this->container->get('doctrine')->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.taxonomy', 'contentId'=>$editMode, 'contentAction'=>'view'));
                 if ($seoPage == null) {
                     $seoPage = new \Basic\CmsBundle\Entity\SeoPage();
                     $seoPage->setBreadCrumbs('');
                     $seoPage->setBreadCrumbsRel('');
                     $seoPage->setUrl('');
                     $seoPage->setTemplate('main');
                     $seoPage->setModules('18,19,20,21,23,25,26,28');
                     $seoPage->setLocale('');
                     $seoPage->setContentType('object.taxonomy');
                     $seoPage->setContentAction('view');
                     $seoPage->setAccess('');
                     $em->persist($seoPage);
                 }
                 $taxonomyLocales = array();
                 foreach ($locales as $localeItem) {
                     $taxonomyLocales[$localeItem['shortName']] = $this->container->get('doctrine')->getRepository('BasicCmsBundle:TaxonomiesLocale')->findOneBy(array('taxonomyId' => $editMode, 'locale' => $localeItem['shortName']));
                     if ($taxonomyLocales[$localeItem['shortName']] == null) {
                         $taxonomyLocales[$localeItem['shortName']] = new \Basic\CmsBundle\Entity\TaxonomiesLocale();
                         $em->persist($taxonomyLocales[$localeItem['shortName']]);
                         $taxonomyLocales[$localeItem['shortName']]->setDescription('');
                         $taxonomyLocales[$localeItem['shortName']]->setLocale($localeItem['shortName']);
                         $taxonomyLocales[$localeItem['shortName']]->setMetaDescription('');
                         $taxonomyLocales[$localeItem['shortName']]->setMetaKeywords('');
                         $taxonomyLocales[$localeItem['shortName']]->setTaxonomyId(0);
                         $taxonomyLocales[$localeItem['shortName']]->setTitle('');
                     }
                 }
                 // проверить права доступа создателя
                 if ($taxonomy->getCreaterId() != $user->getId()) {
                     return null;
                 }
                 $errors = array();
                 $result = false;
                 // принять данные
                 if ($request->getMethod() == 'POST') {
                     $titlePost = $request->get('title');
                     $descriptionPost = $request->get('description');
                     if (isset($titlePost['default'])) {
                         $taxonomy->setTitle($titlePost['default']);
                     }
                     if (!preg_match('/^.{1,}$/ui', $taxonomy->getTitle())) {
                         $errors['title_default'] = ($locale == 'en' ? 'The title should contain more than 3 symbols' : 'Заголовок должен содержать более 3 символов');
                     }
                     if (isset($descriptionPost['default'])) {
                         $taxonomy->setDescription($descriptionPost['default']);
                     }
                     foreach ($locales as $localeItem) {
                        if (isset($titlePost[$localeItem['shortName']])) {
                            $taxonomyLocales[$localeItem['shortName']]->setTitle(($titlePost[$localeItem['shortName']] != '' ? $titlePost[$localeItem['shortName']] : null));
                        }
                        if (isset($descriptionPost[$localeItem['shortName']])) {
                            $taxonomyLocales[$localeItem['shortName']]->setDescription(($descriptionPost[$localeItem['shortName']] != '' ? $descriptionPost[$localeItem['shortName']] : null));
                        }
                        if (!preg_match('/^.{1,}$/ui', $taxonomyLocales[$localeItem['shortName']]->getTitle())) {
                            $errors['title_'.$localeItem['shortName']] = ($locale == 'en' ? 'The title should contain more than 3 symbols' : 'Заголовок должен содержать более 3 символов');
                        }
                     }
                     // сохранить
                     if (count($errors) == 0) {
                         $seoPage->setDescription('');
                         $seoPage->setContentId(0);
                         $em->flush();
                         foreach ($taxonomyLocales as $taxonomyLocale) {
                             $taxonomyLocale->setTaxonomyId($taxonomy->getId());
                         }
                         $seoPage->setDescription('Просмотр категории '.$taxonomy->getTitle());
                         $seoPage->setContentId($taxonomy->getId());
                         $em->flush();
                         $seoPage->setUrl($seoPage->getId());
                         $em->flush();
                         $result = true;
                     }
                 }
                 // вывод
                 $params = array(
                     'editMode' => 'edit='.$editMode.'&parent='.$parent,
                     'locales' => $locales,
                     'taxonomy' => $taxonomy,
                     'taxonomyLocales' => $taxonomyLocales,
                     'errors' => $errors,
                     'result' => $result,
                 );
                 $params['meta'] = array('title'=>'Edit settlements', 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
                 $params['template'] = '';
                 return $params;
             }
             // найти свои поселения и поместья
             $vilages = $em->createQuery('SELECT t.id, t.createDate, t.modifyDate, t.avatar, '.
                                         'IF(tl.title is not null, tl.title, t.title) as title, '.
                                         'IF(tl.description is not null, tl.description, t.description) as description, '.
                                         'IF(tl.metaDescription is not null, tl.metaDescription, t.metaDescription) as metaDescription, '.
                                         'IF(tl.metaKeywords is not null, tl.metaKeywords, t.metaKeywords) as metaKeywords, '.
                                         't.parent, t.firstParent FROM BasicCmsBundle:Taxonomies t '.
                                         'LEFT JOIN BasicCmsBundle:TaxonomiesLocale tl WITH tl.taxonomyId = t.id AND tl.locale = :locale '.
                                         'WHERE t.template = \'mir_taxonomy_viewvillage\' AND t.enabled != 0 AND t.createrId = :id and t.parent = 19')->setParameter('id', $user->getId())->setParameter('locale', $locale)->getResult();
             $houses = $em->createQuery('SELECT t.id, t.createDate, t.modifyDate, t.avatar, '.
                                        'IF(tl.title is not null, tl.title, t.title) as title, '.
                                        'IF(tl.description is not null, tl.description, t.description) as description, '.
                                        'IF(tl.metaDescription is not null, tl.metaDescription, t.metaDescription) as metaDescription, '.
                                        'IF(tl.metaKeywords is not null, tl.metaKeywords, t.metaKeywords) as metaKeywords, '.
                                        't.parent, t.firstParent FROM BasicCmsBundle:Taxonomies t '.
                                        'LEFT JOIN BasicCmsBundle:TaxonomiesLocale tl WITH tl.taxonomyId = t.id AND tl.locale = :locale '.
                                        'WHERE t.template = \'mir_taxonomy_viewvillagehouse\' AND t.enabled != 0 AND t.createrId = :id')->setParameter('id', $user->getId())->setParameter('locale', $locale)->getResult();
             // вывод
             $params = array(
                 'vilages' => $vilages,
                 'houses' => $houses,
             );
             $params['meta'] = array('title'=>'My settlements', 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
             $params['template'] = '';
             return $params;
         }
         
         
         /*if ($contentType == 'view')
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
         }*/
         $params = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
         return $params;
     }
     
     public function setFrontAction($locale, $seoPage, $action)
     {
         $result = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) $this->container->get($item)->setFrontAction($locale, $seoPage, $action, $result);
         return $result;
     }
     
     public function getModuleTypes()
     {
         $contents = array(/*'taxonomy_show'=>'Модуль представления','taxonomy_menu'=>'Модуль меню категорий'*/);
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) $this->container->get($item)->getModuleTypes($contents);
         return $contents;
     }

    /* private function taxonomyGetTreeForFrontModule($tree, $id, $nesting, &$cats, $addedFirst)
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
    }*/
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result = null)
     {
         $params = null;
         /*if ($moduleType == 'taxonomy_show')
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
         }*/
         if ($params != null) return $params;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             if (strpos($item,'addone.knowledge.') === 0) $params = $this->container->get($item)->getFrontModule($locale, $seoPage, $moduleType, $parameters, $result);
             if ($params != null) return $params;
         }
         return null;
     }

    /*private function taxonomyGetTreeForAdminModuleController($tree, $id, $nesting, &$cats)
    {
        foreach ($tree as $cat)
        {
            if ($cat['parent'] == $id)
            {
                $cats[] = array('title' => $cat['title'], 'id' => $cat['id'], 'enabled' => $cat['enabled'], 'nesting' => $nesting);
                $this->taxonomyGetTreeForAdminModuleController($tree, $cat['id'], $nesting + 1, $cats);
            }
        }
    }*/
     
    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        /*if ($module == 'taxonomy_show')
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
        }*/
        $answer = null;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.knowledge.') === 0) $answer = $this->container->get($item)->getAdminModuleController($request, $module, $actionType, $nullparameters);
            if ($answer != null) return $answer;
        }
        return null;
    }
    
    public function getInfoTitle($contentType, $contentId)
    {
         /*if ($contentType == 'view')
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
         }*/
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.knowledge.') === 0) $answer = $this->container->get($item)->getInfoTitle($contentType, $contentId);
             if ($answer != null) return $answer;
         }
         return null;
    }

    public function getInfoSitemap($contentType, $contentId)
    {
         /*if ($contentType == 'view')
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
         }*/
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.knowledge.') === 0) $answer = $this->container->get($item)->getInfoSitemap($contentType, $contentId);
             if ($answer != null) return $answer;
         }
         return null;
    }
    
// **********************************    
// Обработчик таба таксономии
// **********************************    
    /*private function taxonomyGetTree($tree, $id, $nesting, &$cats)
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
*/
//**************************************************
// Поиск по сайту
//**************************************************

    public function getSearchOptions(&$options)
    {
        /*$options['object.taxonomy.on'] = 'Искать информацию в категориях';
        $options['object.taxonomy.onlypage'] = 'Искать информацию в категориях только со страницей сайта';*/
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.knowledge.') === 0) $this->container->get($item)->getSearchOptions($options);
        }
    }
    
    public function getSearchItemCount($options, $searchstring, $locale)
    {
        $count = 0;
        /*if (isset($options['object.taxonomy.on']) && ($options['object.taxonomy.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT count(t.id) as taxcount FROM BasicCmsBundle:Taxonomies t '.
                                      'LEFT JOIN BasicCmsBundle:TaxonomiesLocale tl WITH tl.taxonomyId = t.id AND tl.locale = :locale '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.taxonomy\' AND sp.contentAction = \'view\' AND sp.contentId = t.id '.
                                      'WHERE (IF(tl.title is not null, tl.title, t.title) LIKE :searchstring OR IF(tl.description is not null, tl.description, t.description) LIKE :searchstring)'.
                                      ((isset($options['object.taxonomy.onlypage']) && ($options['object.taxonomy.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : ''))->setParameter('searchstring', $searchstring)->setParameter('locale', $locale);
            $taxcount = $query->getResult();
            if (isset($taxcount[0]['taxcount'])) $count += $taxcount[0]['taxcount'];
        }*/
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.knowledge.') === 0) $count += $this->container->get($item)->getSearchItemCount($options, $searchstring, $locale);
        }
        return $count;
    }
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, &$items)
    {
        /*if (isset($options['object.taxonomy.on']) && ($options['object.taxonomy.on'] != 0))
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
        }*/
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.knowledge.') === 0) $this->container->get($item)->getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, $items);
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
            if (strpos($item,'addone.knowledge.') === 0) $this->container->get($item)->getHelpChapters($chapters);
        }
        return $chapters;
    }
    
    public function getHelpContent($page, &$answer)
    {
        //if ($page == 'd1537bd0b66a4289001629ac2e06e742') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:index.html.twig',array());
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.knowledge.') === 0) $answer .= $this->container->get($item)->getHelpContent($page);
        }
        return $answer;
    }
    
    
}
