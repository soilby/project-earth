<?php

namespace Files\FilesBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class FileManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'object.file';
    }
    
    public function getDescription()
    {
        return 'Файлы';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('База знаний наследие', $this->container->get('router')->generate('files_files_file_list'), 20, $this->container->get('security.context')->getToken()->getUser()->checkAccess('file_list') || $this->container->get('security.context')->getToken()->getUser()->checkAccess('file_createpage') || $this->container->get('security.context')->getToken()->getUser()->checkAccess('file_editpage'));
        $manager->addAdminMenu('Создать новый файл', $this->container->get('router')->generate('files_files_file_create'), 0, $this->container->get('security.context')->getToken()->getUser()->checkAccess('file_new'), 'База знаний наследие');
        $manager->addAdminMenu('Список файлов', $this->container->get('router')->generate('files_files_file_list'), 10, $this->container->get('security.context')->getToken()->getUser()->checkAccess('file_list') || $this->container->get('security.context')->getToken()->getUser()->checkAccess('file_createpage') || $this->container->get('security.context')->getToken()->getUser()->checkAccess('file_editpage'), 'База знаний наследие');
        if ($this->container->has('object.taxonomy'))
        {
            $manager->addAdminMenu('Категории файлов', $this->container->get('router')->generate('basic_cms_taxonomy_list').'?object=object.file', 9999, $this->container->get('security.context')->getToken()->getUser()->checkAccess('taxonomy_list') | $this->container->get('security.context')->getToken()->getUser()->checkAccess('taxonomy_listshow'), 'База знаний наследие');
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) $this->container->get($item)->registerMenu();

    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('file_list','Просмотр списка файлов');
        $manager->addRole('file_viewown','Просмотр собственных файлов');
        $manager->addRole('file_viewall','Просмотр всех файлов');
        $manager->addRole('file_new','Создание новых файлов');
        $manager->addRole('file_editown','Редактирование собственных файлов');
        $manager->addRole('file_editall','Редактирование всех файлов');

        $manager->addRole('file_createpage','Настройка страниц создания файлов');
        $manager->addRole('file_editpage','Настройка страниц редактирования файлов');
        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) $this->container->get($item)->registerRoles();
        
     }
     
     public function getContentTypes()
     {
        $contents = array('view' => 'Просмотр файла', 'create' => 'Создание файла', 'editpage' => 'Редактирование файла');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) $this->container->get($item)->getContentTypes($contents);
        return $contents;
     }
     
     public function getTaxonomyType()
     {
         return true;
     }
        
     public function getTemplateTwig($contentType, $template)
     {
         if ($contentType == 'view') return 'FilesFilesBundle:Front:fileview.html.twig';
         if ($contentType == 'create') return 'FilesFilesBundle:Front:filecreate.html.twig';
         if ($contentType == 'editpage') return 'FilesFilesBundle:Front:fileeditpage.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) 
         {
             $result = $this->container->get($item)->getTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) 
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
             $query = $em->createQuery('SELECT f.id, f.createDate, f.modifyDate, f.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                       'IF(fl.title is not null, fl.title, f.title) as title, '.
                                       'IF(fl.description is not null, fl.description, f.description) as description, '.
                                       'IF(fl.metaDescription is not null, fl.metaDescription, f.metaDescription) as metaDescription, '.
                                       'IF(fl.metaKeywords is not null, fl.metaKeywords, f.metaKeywords) as metaKeywords, '.
                                       'f.template, f.avatar, f.fileName, f.onlyAutorized, f.fileSize, f.downloadCount FROM FilesFilesBundle:Files f '.
                                       'LEFT JOIN FilesFilesBundle:FilesLocale fl WITH fl.fileId = f.id AND fl.locale = :locale '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.
                                       'WHERE f.id = :id AND f.enabled != 0')->setParameter('id', $contentId)->setParameter('locale', $locale);
             $file = $query->getResult();
             if (empty($file)) return null;
             if (isset($file[0])) $params = $file[0]; else return null;
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>$params['metaKeywords'], 'description'=>$params['metaDescription'], 'robotindex'=>true);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         if ($contentType == 'create')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $createpage = $this->container->get('doctrine')->getRepository('FilesFilesBundle:FileCreatePages')->find($contentId);
             if (empty($createpage)) return null;
             if ($createpage->getEnabled() == 0) return null;
             if (($createpage->getOnlyAutorized() != 0) && (($this->container->get('cms.cmsManager')->getCurrentUser() == null) || ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() != 2)))
             {
                 $this->container->get('cms.cmsManager')->setErrorPageNumber(1); return null;
             }
             $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
             $locales = $query->getResult();
             if (!is_array($locales)) $locales = array();
             $params = array();
             $params['template'] = $createpage->getTemplate();
             $params['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($createpage->getTitle(),$locale,true);
             $params['status'] = 'OK';
             $params['saveStatus'] = '';
             // Получить информацию о категориях таксономии
             $params['categories'] = array();
             if ($this->container->has('object.taxonomy')) $params['categories'] = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.file');
             //title, description, avatar, content, categoryId, attachments
             $params['id'] = $createpage->getId();
             $params['fileTitle'] = array();
             $params['fileTitleError'] = array();
             $params['fileDescription'] = array();
             $params['fileDescriptionError'] = array();
             $params['fileTitle']['default'] = '';
             $params['fileTitleError']['default'] = '';
             $params['fileDescription']['default'] = '';
             $params['fileDescriptionError']['default'] = '';
             foreach ($locales as $loc)
             {
                 $params['fileTitle'][$loc['shortName']] = '';
                 $params['fileTitleError'][$loc['shortName']] = '';
                 $params['fileDescription'][$loc['shortName']] = '';
                 $params['fileDescriptionError'][$loc['shortName']] = '';
             }
             $params['fileAvatar'] = '';
             $params['fileAvatarError'] = '';
             $params['fileContentFile'] = '';
             $params['fileContentFileError'] = '';
             $params['fileCategoryId'] = '';
             if ($createpage->getCategoryMode() == 1) $params['fileCategoryId'] = intval($this->container->get('request_stack')->getCurrentRequest()->get('categoryid'));
             if ($createpage->getCategoryMode() == 2) $params['fileCategoryId'] = $createpage->getCategoryId();
             $params['fileCategoryIdError'] = '';
             $params['fileCategoryMode'] = $createpage->getCategoryMode();
             $params['fileFileName'] = '';
             $params['fileFileNameError'] = '';
             $params['locales'] = $locales;
             $params['captchaEnabled'] = 0;
             $params['captchaPath'] = '';
             $params['captchaError'] = '';
             if ($createpage->getCaptchaEnabled() != 0)
             {
                 $params['captchaEnabled'] = 1;
                 $params['captchaPath'] = $this->container->get('router')->generate('basic_cms_captcha').'?target=filecreate'.$createpage->getId();
                 $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_filecreate'.$createpage->getId(), '');
             }
             if (isset($result['action']) && ($result['action'] == 'object.file.create') && ($result['actionId'] == $contentId))
             {
                 $em = $this->container->get('doctrine')->getEntityManager();
                 if (isset($result['title']['default'])) $params['fileTitle']['default'] = $result['title']['default'];
                 if (isset($result['titleError']['default'])) $params['fileTitleError']['default'] = $result['titleError']['default'];
                 if (isset($result['description']['default'])) $params['fileDescription']['default'] = $result['description']['default'];
                 if (isset($result['descriptionError']['default'])) $params['fileDescriptionError']['default'] = $result['descriptionError']['default'];
                 foreach ($locales as $loc)
                 {
                     if (isset($result['title'][$loc['shortName']])) $params['fileTitle'][$loc['shortName']] = $result['title'][$loc['shortName']];
                     if (isset($result['titleError'][$loc['shortName']])) $params['fileTitleError'][$loc['shortName']] = $result['titleError'][$loc['shortName']];
                     if (isset($result['description'][$loc['shortName']])) $params['fileDescription'][$loc['shortName']] = $result['description'][$loc['shortName']];
                     if (isset($result['descriptionError'][$loc['shortName']])) $params['fileDescriptionError'][$loc['shortName']] = $result['descriptionError'][$loc['shortName']];
                 }
                 if (isset($result['avatar'])) $params['fileAvatar'] = $result['avatar'];
                 if (isset($result['avatarError'])) $params['fileAvatarError'] = $result['avatarError'];
                 if (isset($result['contentFile'])) $params['fileContentFile'] = $result['contentFile'];
                 if (isset($result['contentFileError'])) $params['fileContentFileError'] = $result['contentFileError'];
                 if (isset($result['fileName'])) $params['fileFileName'] = $result['fileName'];
                 if (isset($result['fileNameError'])) $params['fileFileNameError'] = $result['fileNameError'];
                 if (isset($result['categoryId'])) $params['fileCategoryId'] = $result['categoryId'];
                 if (isset($result['categoryIdError'])) $params['fileCategoryIdError'] = $result['categoryIdError'];
                 if (isset($result['captchaError'])) $params['captchaError'] = $result['captchaError'];
                 if ($result['status'] == 'OK')
                 {
                     $params['saveStatus'] = 'OK';
                     $params['createdFile'] = $result['createdFile'];
                     $params['createdUrl'] = $result['createdUrl'];
                 }
             }
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
             
         }
         if ($contentType == 'editpage')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $editpage = $this->container->get('doctrine')->getRepository('FilesFilesBundle:FileEditPages')->find($contentId);
             if (empty($editpage)) return null;
             if ($editpage->getEnabled() == 0) return null;
             if (($editpage->getOnlyAutorized() != 0) && (($this->container->get('cms.cmsManager')->getCurrentUser() == null) || ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() != 2)))
             {
                 $this->container->get('cms.cmsManager')->setErrorPageNumber(1); return null;
             }
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('fileid');
             $fileent = $this->container->get('doctrine')->getRepository('FilesFilesBundle:Files')->find(intval($id));
             if (empty($fileent)) return null;
             if (($editpage->getOnlyOwn() != 0) && (($this->container->get('cms.cmsManager')->getCurrentUser() == null) || ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() != 2) || ($this->container->get('cms.cmsManager')->getCurrentUser()->getId() != $fileent->getCreaterId())))
             {
                 $this->container->get('cms.cmsManager')->setErrorPageNumber(1); return null;
             }
             $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
             $locales = $query->getResult();
             if (!is_array($locales)) $locales = array();
             $params = array();
             $params['template'] = $editpage->getTemplate();
             $params['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($editpage->getTitle(),$locale,true);
             $params['status'] = 'OK';
             $params['saveStatus'] = '';
             //title, description, avatar, content, categoryId, attachments
             $params['pageid'] = $editpage->getId();
             $params['id'] = $fileent->getId();
             $params['fileTitle'] = array();
             $params['fileTitleError'] = array();
             $params['fileDescription'] = array();
             $params['fileDescriptionError'] = array();
             $params['fileTitle']['default'] = $fileent->getTitle();
             $params['fileTitleError']['default'] = '';
             $params['fileDescription']['default'] = $fileent->getDescription();
             $params['fileDescriptionError']['default'] = '';
             foreach ($locales as $loc)
             {
                 $filelocent = $this->container->get('doctrine')->getRepository('FilesFilesBundle:FilesLocale')->findOneBy(array('locale'=>$loc['shortName'],'fileId'=>$id));
                 if (!empty($filelocent))
                 {
                     $params['fileTitle'][$loc['shortName']] = $filelocent->getTitle();
                     $params['fileDescription'][$loc['shortName']] = $filelocent->getDescription();
                 } else
                 {
                     $params['fileTitle'][$loc['shortName']] = '';
                     $params['fileDescription'][$loc['shortName']] = '';
                 }
                 $params['fileTitleError'][$loc['shortName']] = '';
                 $params['fileDescriptionError'][$loc['shortName']] = '';
             }
             $params['fileAvatar'] = $fileent->getAvatar();
             $params['fileAvatarError'] = '';
             $params['fileContentFile'] = $fileent->getContentFile();
             $params['fileContentFileError'] = '';
             $params['fileFileName'] = $fileent->getFileName();
             $params['fileFileNameError'] = '';
             $params['locales'] = $locales;
             $params['captchaEnabled'] = 0;
             $params['captchaPath'] = '';
             $params['captchaError'] = '';
             if ($editpage->getCaptchaEnabled() != 0)
             {
                 $params['captchaEnabled'] = 1;
                 $params['captchaPath'] = $this->container->get('router')->generate('basic_cms_captcha').'?target=fileeditpage'.$editpage->getId();
                 $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_fileeditpage'.$editpage->getId(), '');
             }
             if (isset($result['action']) && ($result['action'] == 'object.file.editpage') && ($result['actionId'] == $contentId) && ($result['fileId'] == $fileent->getId()))
             {
                 $em = $this->container->get('doctrine')->getEntityManager();
                 if (isset($result['title']['default'])) $params['fileTitle']['default'] = $result['title']['default'];
                 if (isset($result['titleError']['default'])) $params['fileTitleError']['default'] = $result['titleError']['default'];
                 if (isset($result['description']['default'])) $params['fileDescription']['default'] = $result['description']['default'];
                 if (isset($result['descriptionError']['default'])) $params['fileDescriptionError']['default'] = $result['descriptionError']['default'];
                 foreach ($locales as $loc)
                 {
                     if (isset($result['title'][$loc['shortName']])) $params['fileTitle'][$loc['shortName']] = $result['title'][$loc['shortName']];
                     if (isset($result['titleError'][$loc['shortName']])) $params['fileTitleError'][$loc['shortName']] = $result['titleError'][$loc['shortName']];
                     if (isset($result['description'][$loc['shortName']])) $params['fileDescription'][$loc['shortName']] = $result['description'][$loc['shortName']];
                     if (isset($result['descriptionError'][$loc['shortName']])) $params['fileDescriptionError'][$loc['shortName']] = $result['descriptionError'][$loc['shortName']];
                 }
                 if (isset($result['avatar'])) $params['fileAvatar'] = $result['avatar'];
                 if (isset($result['avatarError'])) $params['fileAvatarError'] = $result['avatarError'];
                 if (isset($result['contentFile'])) $params['fileContentFile'] = $result['contentFile'];
                 if (isset($result['contentFileError'])) $params['fileContentFileError'] = $result['contentFileError'];
                 if (isset($result['fileName'])) $params['fileFileName'] = $result['fileName'];
                 if (isset($result['fileNameError'])) $params['fileFileNameError'] = $result['fileNameError'];
                 if (isset($result['captchaError'])) $params['captchaError'] = $result['captchaError'];
                 if ($result['status'] == 'OK')
                 {
                     $params['saveStatus'] = 'OK';
                     $params['editedFile'] = $result['editedFile'];
                     $params['editedUrl'] = $result['editedUrl'];
                 }
             }
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
             
         }
         $params = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
         return $params;
     }
     
     public function setFrontAction($locale, $seoPage, $action)
     {
         $result = null;
         if ($action == 'create')
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             $postfiles = $this->container->get('request_stack')->getCurrentRequest()->files->get('actionfiles');
             $createpage = $this->container->get('doctrine')->getRepository('FilesFilesBundle:FileCreatePages')->find($id);
             if ((!empty($createpage)) && ($createpage->getEnabled() != 0))
             {
                 $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
                 $locales = $query->getResult();
                 if (!is_array($locales)) $locales = array();
                 $errors = false;
                 $result['title'] = array();
                 $result['titleError'] = array();
                 $result['description'] = array();
                 $result['descriptionError'] = array();
                 $result['title']['default'] = '';
                 $result['titleError']['default'] = '';
                 $result['description']['default'] = '';
                 $result['descriptionError']['default'] = '';
                 foreach ($locales as $loc)
                 {
                     $result['title'][$loc['shortName']] = '';
                     $result['titleError'][$loc['shortName']] = '';
                     $result['description'][$loc['shortName']] = '';
                     $result['descriptionError'][$loc['shortName']] = '';
                 }
                 $result['avatar'] = '';
                 $result['avatarError'] = '';
                 $result['contentFile'] = '';
                 $result['contentFileError'] = '';
                 $result['fileName'] = '';
                 $result['fileNameError'] = '';
                 $result['categoryId'] = '';
                 $result['categoryIdError'] = '';
                 $result['captchaError'] = '';
                 $result['status'] = '';
                 $result['action'] = 'object.file.create';
                 $result['actionId'] = $id;
                 if (isset($postfields['title']['default'])) $result['title']['default'] = $postfields['title']['default'];
                 if (isset($postfields['description']['default'])) $result['description']['default'] = $postfields['description']['default'];
                 foreach ($locales as $loc)
                 {
                     if (isset($postfields['title'][$loc['shortName']])) $result['title'][$loc['shortName']] = $postfields['title'][$loc['shortName']];
                     if (isset($postfields['description'][$loc['shortName']])) $result['description'][$loc['shortName']] = $postfields['description'][$loc['shortName']];
                 }
                 if (isset($postfields['avatar'])) $result['avatar'] = $postfields['avatar'];
                 if (isset($postfields['contentFile'])) $result['contentFile'] = $postfields['contentFile'];
                 if (isset($postfields['fileName'])) $result['fileName'] = $postfields['fileName'];
                 if (isset($postfields['categoryId'])) $result['categoryId'] = $postfields['categoryId'];
                 if ((($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2)) && ($createpage->getOnlyAutorized() != 0)) 
                 {
                     $result['status'] = 'Access error';
                 } else
                 {
                     // Приём файлов
                     if (isset($postfiles['avatar']))
                     {
                         $tmpfile = $postfiles['avatar']->getPathName();
                         if (@getimagesize($tmpfile)) 
                         {
                             $params = getimagesize($tmpfile);
                             if ($params[0] < 10) {$errors = true; $result['avatarError'] = 'size';}
                             if ($params[1] < 10) {$errors = true; $result['avatarError'] = 'size';} 
                             if ($params[0] > 6000) {$errors = true; $result['avatarError'] = 'size';}
                             if ($params[1] > 6000) {$errors = true; $result['avatarError'] = 'size';}
                             $imageTypeArray = array(0=>'', 1=>'gif', 2=>'jpg', 3=>'png', 4=>'', 5=>'', 6=>'', 7=>'', 8=>'', 9=>'jpg', 10=>'jpg', 11=>'jpg', 12=>'jpg', 13=>'', 14=>'', 15=>'', 16=>'', 17=>'', 18=>'');
                             if (!isset($imageTypeArray[$params[2]]) || ($imageTypeArray[$params[2]] == '')) {$errors = true; $result['avatarError'] = 'format';}
                             $basepath = '/images/file/';
                             $name = 'f_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
                             if (move_uploaded_file($tmpfile, '..'.$basepath.$name)) 
                             {
                                 $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $postfiles['avatar']->getClientOriginalName());
                                 $result['avatar'] = $basepath.$name;
                             }
                         } else {$errors = true; $result['avatarError'] = 'format';}
                     }
                     if (isset($postfiles['contentFile']))
                     {
                         $tmpfile = $postfiles['contentFile']->getPathName();
                         $basepath = '/secured/file/';
                         $name = 'f_'.md5($tmpfile.time()).'.dat';
                         if (move_uploaded_file($tmpfile, '..'.$basepath.$name)) 
                         {
                             $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $postfiles['contentFile']->getClientOriginalName());
                             $result['contentFile'] = $basepath.$name;
                         }
                     }
                     // Валидация
                     if (!preg_match("/^.{3,}$/ui", $result['title']['default'])) {$errors = true; $result['titleError']['default'] = 'Preg error';}
                     foreach ($locales as $loc)
                     {
                         if (!preg_match("/^(.{3,})?$/ui", $result['title'][$loc['shortName']])) {$errors = true; $result['titleError'][$loc['shortName']] = 'Preg error';}
                     }
                     if (($result['avatar'] != '') && (!file_exists('..'.$result['avatar']))) {$errors = true; $result['avatarError'] = 'Not found';}
                     if (($result['contentFile'] == '') || (!file_exists('..'.$result['contentFile']))) {$errors = true; $result['contentFileError'] = 'Not found';}
                     if (!preg_match("/^[0-9A-zА-яЁё \-!#\$\%\&\*\(\)_\+\?\.]{3,}$/ui", $result['fileName'])) {$errors = true; $result['fileNameError'] = 'Preg error';}
                     if ($createpage->getCategoryMode() == 1)
                     {
                         $query = $em->createQuery('SELECT count(t.id) as taxcount FROM BasicCmsBundle:Taxonomies t WHERE t.enabled != 0 AND t.enableAdd != 0 AND t.object = \'object.file\' AND t.id = :id')->setParameter('id', $result['categoryId']);
                         $taxcount = $query->getResult();
                         if (isset($taxcount[0]['taxcount'])) $taxcount = intval($taxcount[0]['taxcount']); else $taxcount = 0;
                         if ($taxcount != 1) {$errors = true; $result['categoryIdError'] = 'Not found';}
                     }
                     if ($createpage->getCaptchaEnabled() != 0)
                     {
                         $needle = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_captcha_filecreate'.$createpage->getId());
                         $value = (isset($postfields['captcha']) ? $postfields['captcha'] : null);
                         if (($needle == '') || (strtoupper($value) != strtoupper($needle)) || ($value == null))
                         {
                             $result['captchaError'] = 'Value error';
                             $errors = true;
                         }
                         $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_filecreate'.$createpage->getId(), '');
                     }
                     // Сохранение если нет ошибок
                     if ($errors == false)
                     {
                         $this->container->get('cms.cmsManager')->unlockTemporaryFile($result['avatar']);
                         $this->container->get('cms.cmsManager')->unlockTemporaryFile($result['contentFile']);
                         $query = $em->createQuery('SELECT max(f.ordering) as maxorder FROM FilesFilesBundle:Files f');
                         $order = $query->getResult();
                         if (isset($order[0]['maxorder'])) $order = $order[0]['maxorder'] + 1; else $order = 1;
                         $fileent = new \Files\FilesBundle\Entity\Files();
                         $fileent->setContentFile($result['contentFile']);
                         $fileent->setFileName($result['fileName']);
                         $fileent->setOnlyAutorized($createpage->getFileOnlyAutorized());
                         $fileent->setAvatar($result['avatar']);
                         $fileent->setCreateDate(new \DateTime('now'));
                         $fileent->setCreaterId(($cmsManager->getCurrentUser() != null ? $cmsManager->getCurrentUser()->getId() : 0));
                         $fileent->setDescription($result['description']['default']);
                         $fileent->setEnabled($createpage->getFileEnabled());
                         $fileent->setMetaDescription('');
                         $fileent->setMetaKeywords('');
                         $fileent->setModifyDate(new \DateTime('now'));
                         $fileent->setOrdering($order);
                         $fileent->setTitle($result['title']['default']);
                         $fileent->setFileSize(filesize('..'.$result['contentFile']));
                         $fileent->setDownloadCount(0);
                         $fileent->setTemplate($createpage->getFileTemplate());
                         $em->persist($fileent);
                         $em->flush();
                         $result['createdFile'] = $fileent;
                         $result['createdUrl'] = '';
                         
                         if ($createpage->getSeopageEnabled() != 0)
                         {
                             $pageurl = $this->container->get('cms.cmsManager')->getUniqueUrl($createpage->getSeopageUrl(), array('id'=>$fileent->getId(), 'title'=>$fileent->getTitle()));
                             $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                             $pageent->setBreadCrumbs('');
                             $pageent->setBreadCrumbsRel('');
                             $pageent->setDescription('Просмотр файла '.$fileent->getTitle());
                             $pageent->setUrl($pageurl);
                             $pageent->setTemplate($createpage->getSeopageTemplate());
                             $pageent->setModules($createpage->getSeopageModules());
                             $pageent->setLocale($createpage->getSeopageLocale());
                             $pageent->setContentType('object.file');
                             $pageent->setContentId($fileent->getId());
                             $pageent->setContentAction('view');
                             $pageent->setAccess($createpage->getSeopageAccess());
                             $em->persist($pageent);
                             $em->flush();
                             if ($pageurl == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                             $result['createdUrl'] = $pageent->getUrl();
                         }
                         foreach ($locales as $loc)
                         {
                             if (($result['description'][$loc['shortName']] != '') ||
                                 ($result['title'][$loc['shortName']] != ''))
                             {
                                 $filelocent = new \Files\FilesBundle\Entity\FilesLocale();
                                 $filelocent->setFileId($fileent->getId());
                                 $filelocent->setLocale($loc['shortName']);
                                 if ($result['title'][$loc['shortName']] == '') $filelocent->setTitle(null); else $filelocent->setTitle($result['title'][$loc['shortName']]);
                                 if ($result['description'][$loc['shortName']] == '') $filelocent->setDescription(null); else $filelocent->setDescription($result['description'][$loc['shortName']]);
                                 $filelocent->setMetaDescription(null);
                                 $filelocent->setMetaKeywords(null);
                                 $em->persist($filelocent);
                                 $em->flush();
                                 unset($filelocent);
                             }
                         }
                         // taxonomy
                         if ($createpage->getCategoryMode() == 1)
                         {
                             $taxlink = new \Basic\CmsBundle\Entity\TaxonomiesLinks();
                             $taxlink->setItemId($fileent->getId());
                             $taxlink->setTaxonomyId($result['categoryId']);
                             $em->persist($taxlink);
                             $em->flush();
                         }
                         if ($createpage->getCategoryMode() == 2)
                         {
                             $taxlink = new \Basic\CmsBundle\Entity\TaxonomiesLinks();
                             $taxlink->setItemId($fileent->getId());
                             $taxlink->setTaxonomyId($createpage->getCategoryId());
                             $em->persist($taxlink);
                             $em->flush();
                             $result['categoryId'] = $createpage->getCategoryId();
                         }
                         $result['status'] = 'OK';
                         // Запись в лог
                         $this->container->get('cms.cmsManager')->logPushFront(0, $this->getName().'.create', 'Создание файла "'.$fileent->getTitle().'"', $this->container->get('router')->generate('files_files_file_edit').'?id='.$fileent->getId());
                     } else $result['status'] = 'Check error';
                 }
             }
         }
         if ($action == 'editpage')
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $fileid = $this->container->get('request_stack')->getCurrentRequest()->get('actionfileid');
             $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             $postfiles = $this->container->get('request_stack')->getCurrentRequest()->files->get('actionfiles');
             $editpage = $this->container->get('doctrine')->getRepository('FilesFilesBundle:FileEditPages')->find($id);
             $fileent = $this->container->get('doctrine')->getRepository('FilesFilesBundle:Files')->find($fileid);
             if ((!empty($editpage)) && ($editpage->getEnabled() != 0) && (!empty($fileent)))
             {
                 $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
                 $locales = $query->getResult();
                 if (!is_array($locales)) $locales = array();
                 $errors = false;
                 $result['title'] = array();
                 $result['titleError'] = array();
                 $result['description'] = array();
                 $result['descriptionError'] = array();
                 $result['title']['default'] = '';
                 $result['titleError']['default'] = '';
                 $result['description']['default'] = '';
                 $result['descriptionError']['default'] = '';
                 foreach ($locales as $loc)
                 {
                     $result['title'][$loc['shortName']] = '';
                     $result['titleError'][$loc['shortName']] = '';
                     $result['description'][$loc['shortName']] = '';
                     $result['descriptionError'][$loc['shortName']] = '';
                 }
                 $result['avatar'] = '';
                 $result['avatarError'] = '';
                 $result['contentFile'] = '';
                 $result['contentFileError'] = '';
                 $result['fileName'] = '';
                 $result['fileNameError'] = '';
                 $result['captchaError'] = '';
                 $result['status'] = '';
                 $result['action'] = 'object.file.editpage';
                 $result['actionId'] = $id;
                 $result['fileId'] = $fileid;
                 if (isset($postfields['title']['default'])) $result['title']['default'] = $postfields['title']['default'];
                 if (isset($postfields['description']['default'])) $result['description']['default'] = $postfields['description']['default'];
                 foreach ($locales as $loc)
                 {
                     if (isset($postfields['title'][$loc['shortName']])) $result['title'][$loc['shortName']] = $postfields['title'][$loc['shortName']];
                     if (isset($postfields['description'][$loc['shortName']])) $result['description'][$loc['shortName']] = $postfields['description'][$loc['shortName']];
                 }
                 if (isset($postfields['avatar'])) $result['avatar'] = $postfields['avatar'];
                 if (isset($postfields['contentFile'])) $result['contentFile'] = $postfields['contentFile'];
                 if (isset($postfields['fileName'])) $result['fileName'] = $postfields['fileName'];
                 if ((($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2)) && ($editpage->getOnlyAutorized() != 0)) 
                 {
                     $result['status'] = 'Access error';
                 } elseif ((($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2) || ($cmsManager->getCurrentUser()->getId() != $fileent->getCreaterId())) && ($editpage->getOnlyOwn() != 0)) 
                 {
                     $result['status'] = 'Access error';
                 } else
                 {
                     // Приём файлов
                     if (isset($postfiles['avatar']))
                     {
                         $tmpfile = $postfiles['avatar']->getPathName();
                         if (@getimagesize($tmpfile)) 
                         {
                             $params = getimagesize($tmpfile);
                             if ($params[0] < 10) {$errors = true; $result['avatarError'] = 'size';}
                             if ($params[1] < 10) {$errors = true; $result['avatarError'] = 'size';} 
                             if ($params[0] > 6000) {$errors = true; $result['avatarError'] = 'size';}
                             if ($params[1] > 6000) {$errors = true; $result['avatarError'] = 'size';}
                             $imageTypeArray = array(0=>'', 1=>'gif', 2=>'jpg', 3=>'png', 4=>'', 5=>'', 6=>'', 7=>'', 8=>'', 9=>'jpg', 10=>'jpg', 11=>'jpg', 12=>'jpg', 13=>'', 14=>'', 15=>'', 16=>'', 17=>'', 18=>'');
                             if (!isset($imageTypeArray[$params[2]]) || ($imageTypeArray[$params[2]] == '')) {$errors = true; $result['avatarError'] = 'format';}
                             $basepath = '/images/file/';
                             $name = 'f_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
                             if (move_uploaded_file($tmpfile, '..'.$basepath.$name)) 
                             {
                                 $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $postfiles['avatar']->getClientOriginalName());
                                 $result['avatar'] = $basepath.$name;
                             }
                         } else {$errors = true; $result['avatarError'] = 'format';}
                     }
                     if (isset($postfiles['contentFile']))
                     {
                         $tmpfile = $postfiles['contentFile']->getPathName();
                         $basepath = '/secured/file/';
                         $name = 'f_'.md5($tmpfile.time()).'.dat';
                         if (move_uploaded_file($tmpfile, '..'.$basepath.$name)) 
                         {
                             $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $postfiles['contentFile']->getClientOriginalName());
                             $result['contentFile'] = $basepath.$name;
                         }
                     }
                     // Валидация
                     if (!preg_match("/^.{3,}$/ui", $result['title']['default'])) {$errors = true; $result['titleError']['default'] = 'Preg error';}
                     foreach ($locales as $loc)
                     {
                         if (!preg_match("/^(.{3,})?$/ui", $result['title'][$loc['shortName']])) {$errors = true; $result['titleError'][$loc['shortName']] = 'Preg error';}
                     }
                     if (($result['avatar'] != '') && (!file_exists('..'.$result['avatar']))) {$errors = true; $result['avatarError'] = 'Not found';}
                     if (($result['contentFile'] == '') || (!file_exists('..'.$result['contentFile']))) {$errors = true; $result['contentFileError'] = 'Not found';}
                     if (!preg_match("/^[0-9A-zА-яЁё \-!#\$\%\&\*\(\)_\+\?\.]{3,}$/ui", $result['fileName'])) {$errors = true; $result['fileNameError'] = 'Preg error';}
                     if ($editpage->getCaptchaEnabled() != 0)
                     {
                         $needle = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_captcha_fileeditpage'.$editpage->getId());
                         $value = (isset($postfields['captcha']) ? $postfields['captcha'] : null);
                         if (($needle == '') || (strtoupper($value) != strtoupper($needle)) || ($value == null))
                         {
                             $result['captchaError'] = 'Value error';
                             $errors = true;
                         }
                         $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_fileeditpage'.$editpage->getId(), '');
                     }
                     // Сохранение если нет ошибок
                     if ($errors == false)
                     {
                         $this->container->get('cms.cmsManager')->unlockTemporaryFile($result['avatar']);
                         $this->container->get('cms.cmsManager')->unlockTemporaryFile($result['contentFile']);
                         $fileent->setContentFile($result['contentFile']);
                         $fileent->setFileName($result['fileName']);
                         $fileent->setAvatar($result['avatar']);
                         $fileent->setDescription($result['description']['default']);
                         if ($editpage->getResetFileEnabled() != 0) $fileent->setEnabled(0);
                         $fileent->setModifyDate(new \DateTime('now'));
                         $fileent->setTitle($result['title']['default']);
                         $fileent->setFileSize(filesize('..'.$result['contentFile']));
                         $em->flush();
                         $result['editedFile'] = $fileent;
                         $result['editedUrl'] = '';
                         $pageent = $this->container->get('doctrine')->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.file','contentId'=>$fileent->getId(),'contentAction'=>'view'));
                         if (!empty($pageent)) $result['editedUrl'] = $pageent->getUrl();
                         foreach ($locales as $loc)
                         {
                             $filelocent = $this->container->get('doctrine')->getRepository('FilesFilesBundle:FilesLocale')->findOneBy(array('locale'=>$loc['shortName'],'fileId'=>$fileent->getId()));
                             if (($result['description'][$loc['shortName']] != '') ||
                                 ($result['title'][$loc['shortName']] != ''))
                             {
                                 if (empty($filelocent))
                                 {
                                     $filelocent = new \Files\FilesBundle\Entity\FilesLocale();
                                     $em->persist($filelocent);
                                     $filelocent->setMetaDescription(null);
                                     $filelocent->setMetaKeywords(null);
                                     $filelocent->setFileId($fileent->getId());
                                     $filelocent->setLocale($loc['shortName']);
                                 }
                                 if ($result['title'][$loc['shortName']] == '') $filelocent->setTitle(null); else $filelocent->setTitle($result['title'][$loc['shortName']]);
                                 if ($result['description'][$loc['shortName']] == '') $filelocent->setDescription(null); else $filelocent->setDescription($result['description'][$loc['shortName']]);
                                 $em->flush();
                             } else
                             {
                                 if (!empty($filelocent) && ($filelocent->getMetaKeywords() == null) && ($filelocent->getMetaDescription() == null))
                                 {
                                     $em->remove($filelocent);
                                 } else
                                 {
                                     $filelocent->setTitle(null);
                                     $filelocent->setDescription(null);
                                 }
                                 $em->flush();
                             }
                             unset($filelocent);
                         }
                         $result['status'] = 'OK';
                         // Запись в лог
                         $this->container->get('cms.cmsManager')->logPushFront(0, $this->getName().'.edit', 'Редактирование файла "'.$fileent->getTitle().'"', $this->container->get('router')->generate('files_files_file_edit').'?id='.$fileent->getId());
                     } else $result['status'] = 'Check error';
                 }
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) $this->container->get($item)->setFrontAction($locale, $seoPage, $action, $result);
         return $result;
     }
     
     public function getModuleTypes()
     {
         $contents = array();
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) $this->container->get($item)->getModuleTypes($contents);
         return $contents;
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result = null)
     {
         $params = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             if (strpos($item,'addone.file.') === 0) $params = $this->container->get($item)->getFrontModule($locale, $seoPage, $moduleType, $parameters, $result);
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
            if (strpos($item,'addone.file.') === 0) $answer = $this->container->get($item)->getAdminModuleController($request, $module, $actionType, $nullparameters);
            if ($answer != null) return $answer;
        }
        return null;
    }

    public function getInfoTitle($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT f.title FROM FilesFilesBundle:Files f WHERE f.id = :id')->setParameter('id', $contentId);
             $file = $query->getResult();
             if (isset($file[0]['title']))
             {
                 $title = array();
                 $title['default'] = $file[0]['title'];
                 $query = $em->createQuery('SELECT fl.title, fl.locale FROM FilesFilesBundle:FilesLocale fl WHERE fl.fileId = :id')->setParameter('id', $contentId);
                 $filelocale = $query->getResult();
                 if (is_array($filelocale))
                 {
                     foreach ($filelocale as $fpitem) if ($fpitem['title'] != null) $title[$fpitem['locale']] = $fpitem['title'];
                 }
                 return $this->container->get('cms.cmsManager')->encodeLocalString($title);
             }
         }
         if ($contentType == 'create')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT p.title FROM FilesFilesBundle:FileCreatePages p WHERE p.id = :id')->setParameter('id', $contentId);
             $page = $query->getResult();
             if (isset($page[0]['title'])) return $page[0]['title'];
         }
         if ($contentType == 'editpage')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT p.title FROM FilesFilesBundle:FileEditPages p WHERE p.id = :id')->setParameter('id', $contentId);
             $page = $query->getResult();
             if (isset($page[0]['title'])) return $page[0]['title'];
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.file.') === 0) $answer = $this->container->get($item)->getInfoTitle($contentType, $contentId);
             if ($answer != null) return $answer;
         }
         return null;
    }

    public function getInfoSitemap($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT f.avatar, f.modifyDate FROM FilesFilesBundle:Files f WHERE f.id = :id AND f.enabled != 0')->setParameter('id', $contentId);
             $file = $query->getResult();
             if (isset($file[0]))
             {
                 $info = array();
                 $info['priority'] = '0.5';
                 $info['modifyDate'] = $file[0]['modifyDate'];
                 $info['avatar'] = $file[0]['avatar'];
                 return $info;
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.file.') === 0) $answer = $this->container->get($item)->getInfoSitemap($contentType, $contentId);
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
        $params['onlyAutorized'] = array('type'=>'bool','ext'=>0,'name'=>'Доступ только авторизованным');
        $params['fileName'] = array('type'=>'text','ext'=>0,'name'=>'Имя файла');
        $params['fileSize'] = array('type'=>'int','ext'=>0,'name'=>'Размер файла');
        $params['downloadCount'] = array('type'=>'int','ext'=>0,'name'=>'Количество скачиваний');
        $params['avatar'] = array('type'=>'text','ext'=>0,'name'=>'Аватар');
        $params['createrLogin'] = array('type'=>'text','ext'=>1,'name'=>'Логин автора');
        $params['createrFullName'] = array('type'=>'text','ext'=>1,'name'=>'Имя автора');
        $params['createrAvatar'] = array('type'=>'text','ext'=>1,'name'=>'Аватар автора');
        $params['createrEmail'] = array('type'=>'text','ext'=>1,'name'=>'E-mail автора');
        $params['url'] = array('type'=>'text','ext'=>1,'name'=>'URL страницы');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) $this->container->get($item)->getTaxonomyParameters($params);
        return $params;
    }
    
    public function getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $queryCount = false, $itemIds = null)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        $allparameters = array($pageParameters['sortField']);
        $queryparameters = array();
        foreach ($pageParameters['fields'] as $field) $allparameters[] = $field;
        foreach ($filterParameters as $field) $allparameters[] = $field['parameter'];
        $from = ' FROM FilesFilesBundle:Files f';
        if (in_array('createrLogin', $allparameters) || 
            in_array('createrFullName', $allparameters) ||    
            in_array('createrAvatar', $allparameters) ||    
            in_array('createrEmail', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId';}
        if (in_array('title', $allparameters) || 
            in_array('description', $allparameters)) {$from .= ' LEFT JOIN FilesFilesBundle:FilesLocale fl WITH fl.fileId = f.id AND fl.locale = :locale'; $queryparameters['locale'] = $locale;}
        if (in_array('url', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.file\' AND sp.contentAction = \'view\' AND sp.contentId = f.id';}
        $select = 'SELECT f.id';
        if (in_array('createDate', $pageParameters['fields'])) $select .=',f.createDate';
        if (in_array('modifyDate', $pageParameters['fields'])) $select .=',f.modifyDate';
        if (in_array('createrId', $pageParameters['fields'])) $select .=',f.createrId';
        if (in_array('title', $pageParameters['fields']) || in_array('title', $allparameters)) $select .=',IF(fl.title is not null, fl.title, f.title) as title';
        if (in_array('description', $pageParameters['fields']) || in_array('description', $allparameters)) $select .=',IF(fl.description is not null, fl.description, f.description) as description';
        if (in_array('avatar', $pageParameters['fields'])) $select .=',f.avatar';
        if (in_array('onlyAutorized', $pageParameters['fields'])) $select .=',f.onlyAutorized';
        if (in_array('fileName', $pageParameters['fields'])) $select .=',f.fileName';
        if (in_array('fileSize', $pageParameters['fields'])) $select .=',f.fileSize';
        if (in_array('downloadCount', $pageParameters['fields'])) $select .=',f.downloadCount';
        if (in_array('createrLogin', $pageParameters['fields'])) $select .=',u.login as createrLogin';
        if (in_array('createrFullName', $pageParameters['fields'])) $select .=',u.fullName as createrFullName';
        if (in_array('createrAvatar', $pageParameters['fields'])) $select .=',u.avatar as createrAvatar';
        if (in_array('createrEmail', $pageParameters['fields'])) $select .=',u.email as createrEmail';
        if (in_array('url', $pageParameters['fields'])) $select .=',sp.url';
        $where = ' WHERE f.enabled != 0';
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
                    if ($field['compare'] == 'noeq') {$where .= ' AND f.createDate != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND f.createDate = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND f.createDate > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND f.createDate < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND f.createDate >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND f.createDate <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
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
                    if ($field['compare'] == 'noeq') {$where .= ' AND f.modifyDate != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND f.modifyDate = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND f.modifyDate > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND f.modifyDate < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND f.modifyDate >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND f.modifyDate <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                }
                unset($date);
            }
            if ($field['parameter'] == 'createrId')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND f.createrId != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND f.createrId = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND f.createrId > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND f.createrId < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND f.createrId >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND f.createrId <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'title')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND IF(fl.title is not null, fl.title, f.title) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND IF(fl.title is not null, fl.title, f.title) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND IF(fl.title is not null, fl.title, f.title) like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'description')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND IF(fl.description is not null, fl.description, f.description) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND IF(fl.description is not null, fl.description, f.description) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND IF(fl.description is not null, fl.description, f.description) like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'onlyAutorized')
            {
                if ($field['value'] != 0) {$where .= ' AND f.onlyAutorized != 0';}
                if ($field['value'] == 0) {$where .= ' AND f.onlyAutorized = 0';}
            }
            if ($field['parameter'] == 'fileName')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND f.fileName != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND f.fileName = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND f.fileName like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'fileSize')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND f.fileSize != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND f.fileSize = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND f.fileSize > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND f.fileSize < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND f.fileSize >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND f.fileSize <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'downloadCount')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND f.downloadCount != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND f.downloadCount = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND f.downloadCount > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND f.downloadCount < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND f.downloadCount >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND f.downloadCount <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'avatar')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND f.avatar != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND f.avatar = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND f.avatar like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
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
            $from .= ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks l WITH l.itemId = f.id AND l.taxonomyId IN (:categoryids)';
            $where .= ' AND l.id IS NOT NULL';
            $queryparameters['categoryids'] = $categoryIds;
        }
        if (($itemIds !== null) && (is_array($itemIds)))
        {
            if (count($itemIds) == 0) $itemIds[] = 0;
            $where .= ' AND f.id IN (:itemids)';
            $queryparameters['itemids'] = $itemIds;
        }
        $order = ' ORDER BY f.ordering ASC';
        if ($pageParameters['sortField'] == 'createDate') $order = ' ORDER BY f.createDate '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'modifyDate') $order = ' ORDER BY f.modifyDate '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrId') $order = ' ORDER BY f.createrId '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'title') $order = ' ORDER BY title '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'description') $order = ' ORDER BY description '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'onlyAutorized') $order = ' ORDER BY f.onlyAutorized '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'fileName') $order = ' ORDER BY f.fileName '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'fileSize') $order = ' ORDER BY f.fileSize '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'downloadCount') $order = ' ORDER BY f.downloadCount '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'avatar') $order = ' ORDER BY f.avatar '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrLogin') $order = ' ORDER BY u.login '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrFullName') $order = ' ORDER BY u.fullName '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrAvatar') $order = ' ORDER BY u.avatar '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrEmail') $order = ' ORDER BY u.email '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'url') $order = ' ORDER BY sp.url '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        // Обход плагинов
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) $this->container->get($item)->getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $itemIds, $allparameters, $queryparameters, $queryparameterscount, $select, $from, $where, $order);
        // Создание запроса
        if ($queryCount == true)    
        {
            $query = $em->createQuery('SELECT count(DISTINCT f.id) as itemcount'.$from.$where)->setParameters($queryparameters);
        } else
        {
            $query = $em->createQuery($select.$from.$where.' GROUP BY f.id'.$order)->setParameters($queryparameters);
        }
        return $query;
    }
    
    public function getTaxonomyPostProcess($locale, &$params)
    {
        foreach ($params['items'] as &$fileitem) 
        {
            if (isset($fileitem['url']) && ($fileitem['url'] != '')) $fileitem['url'] = '/'.($locale != '' ? $locale.'/' : '').$fileitem['url'];
        }
        unset($fileitem);
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) $this->container->get($item)->getTaxonomyPostProcess($locale, $params);
    }

    
    public function getTaxonomyExtCatInfo(&$tree)
    {
        $categoryIds = array();
        foreach ($tree as $cat) $categoryIds[] = $cat['id'];
        $em = $this->container->get('doctrine')->getEntityManager();
        if (count($categoryIds) > 0)
        {
            $query = $em->createQuery('SELECT tl.taxonomyId, count(f.id) as fileCount, max(f.modifyDate) as modifyDate FROM BasicCmsBundle:TaxonomiesLinks tl '.
                                      'LEFT JOIN FilesFilesBundle:Files f WITH f.id = tl.itemId '.
                                      'WHERE tl.taxonomyId IN (:taxonomyids) AND f.enabled != 0 GROUP BY tl.taxonomyId')->setParameter('taxonomyids', $categoryIds);
            $extInfo = $query->getResult();
            $nulltime = new \DateTime('now');
            $nulltime->setTimestamp(0);
            foreach ($tree as &$cat) 
            {
                $cat['fileCount'] = 0;
                $cat['fileMofidyDate'] = $nulltime;
                foreach ($extInfo as $info)
                    if ($info['taxonomyId'] == $cat['id'])
                    {
                        $cat['fileCount'] = $info['fileCount'];
                        $cat['fileMofidyDate'] = $info['modifyDate'];
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
        $options['object.file.on'] = 'Искать информацию в файлах';
        $options['object.file.onlypage'] = 'Искать информацию в файлах только со страницей сайта';
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.file.') === 0) $this->container->get($item)->getSearchOptions($options);
        }
    }
    
    public function getSearchItemCount($options, $searchstring, $locale)
    {
        $count = 0;
        if (isset($options['object.file.on']) && ($options['object.file.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT count(f.id) as filecount FROM FilesFilesBundle:Files f '.
                                      'LEFT JOIN FilesFilesBundle:FilesLocale fl WITH fl.fileId = f.id AND fl.locale = :locale '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.file\' AND sp.contentAction = \'view\' AND sp.contentId = f.id '.
                                      'WHERE (IF(fl.title is not null, fl.title, f.title) LIKE :searchstring OR IF(fl.description is not null, fl.description, f.description) LIKE :searchstring)'.
                                      ((isset($options['object.file.onlypage']) && ($options['object.file.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : ''))->setParameter('searchstring', $searchstring)->setParameter('locale', $locale);
            $filecount = $query->getResult();
            if (isset($filecount[0]['filecount'])) $count += $filecount[0]['filecount'];
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.file.') === 0) $count += $this->container->get($item)->getSearchItemCount($options, $searchstring, $locale);
        }
        return $count;
    }
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, &$items)
    {
        if (isset($options['object.file.on']) && ($options['object.file.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT \'file\' as object, f.id, IF(fl.title is not null, fl.title, f.title) as title, IF(fl.description is not null, fl.description, f.description) as description, '.
                                      'f.modifyDate, f.avatar, f.createrId, u.avatar as createrAvatar, u.login as createrLogin, u.fullName as createrFullName, sp.url FROM FilesFilesBundle:Files f '.
                                      'LEFT JOIN FilesFilesBundle:FilesLocale fl WITH fl.fileId = f.id AND fl.locale = :locale '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.file\' AND sp.contentAction = \'view\' AND sp.contentId = f.id '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.
                                      'WHERE (IF(fl.title is not null, fl.title, f.title) LIKE :searchstring OR IF(fl.description is not null, fl.description, f.description) LIKE :searchstring)'.
                                      ((isset($options['object.file.onlypage']) && ($options['object.file.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : '').
                                      ($sortdir == 0 ? ' ORDER BY f.modifyDate ASC' : ' ORDER BY f.modifyDate DESC'))->setParameter('searchstring', $searchstring)->setParameter('locale', $locale)->setFirstResult($start)->setMaxResults($limit);
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
            if (strpos($item,'addone.file.') === 0) $this->container->get($item)->getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, $items);
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
            if (strpos($item,'addone.file.') === 0) $this->container->get($item)->getHelpChapters($chapters);
        }
        return $chapters;
    }
    
    public function getHelpContent($page, &$answer)
    {
        //if ($page == 'd1537bd0b66a4289001629ac2e06e742') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:index.html.twig',array());
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.file.') === 0) $answer .= $this->container->get($item)->getHelpContent($page);
        }
        return $answer;
    }
    
}
