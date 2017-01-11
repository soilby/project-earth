<?php

namespace Files\FilesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Basic\CmsBundle\Entity\Users;
use Basic\CmsBundle\Entity\Roles;

class DefaultController extends Controller
{
// *******************************************
// Список файлов
// *******************************************    
    public function filesFileListAction()
    {
        if (($this->getUser()->checkAccess('file_list') == 0) && ($this->getUser()->checkAccess('file_createpage') == 0) && ($this->getUser()->checkAccess('file_editpage') == 0))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Файлы',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $tab = 0;
        $tab = $this->getRequest()->get('tab');
        if ($tab === null) $tab = $this->getRequest()->getSession()->get('files_files_file_list_tab');
                      else $this->getRequest()->getSession()->set('files_files_file_list_tab', $tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 2) $tab = 2;
        if (($tab == 2) && ($this->getUser()->checkAccess('file_editpage') == 0)) $tab = 1;
        if (($tab == 1) && ($this->getUser()->checkAccess('file_createpage') == 0)) $tab = 0;
        if (($tab == 0) && ($this->getUser()->checkAccess('file_list') == 0)) $tab = 1;
        if (($tab == 1) && ($this->getUser()->checkAccess('file_createpage') == 0)) $tab = 2;
        // Таб 1
        $page0 = $this->getRequest()->get('page0');
        $sort0 = $this->getRequest()->get('sort0');
        $search0 = $this->getRequest()->get('search0');
        $taxonomy0 = $this->getRequest()->get('taxonomy0');
        if ($page0 === null) $page0 = $this->getRequest()->getSession()->get('files_files_file_list_page0');
                        else $this->getRequest()->getSession()->set('files_files_file_list_page0', $page0);
        if ($sort0 === null) $sort0 = $this->getRequest()->getSession()->get('files_files_file_list_sort0');
                        else $this->getRequest()->getSession()->set('files_files_file_list_sort0', $sort0);
        if ($search0 === null) $search0 = $this->getRequest()->getSession()->get('files_files_file_list_search0');
                          else $this->getRequest()->getSession()->set('files_files_file_list_search0', $search0);
        if ($taxonomy0 === null) $taxonomy0 = $this->getRequest()->getSession()->get('files_files_file_list_taxonomy0');
                          else $this->getRequest()->getSession()->set('files_files_file_list_taxonomy0', $taxonomy0);
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        $taxonomy0 = intval($taxonomy0);
        // Поиск контента для 1 таба (текстовые страницы)
        $querytaxonomyleft0 = '';
        $querytaxonomywhere0 = '';
        if (($this->container->has('object.taxonomy')) && ($taxonomy0 != 0))
        {
            $querytaxonomyleft0 = ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks txl WITH txl.taxonomyId = '.$taxonomy0.' AND txl.itemId = f.id ';
            $querytaxonomywhere0 = ' AND txl.id IS NOT NULL ';
        }
        $query = $em->createQuery('SELECT count(f.id) as filecount FROM FilesFilesBundle:Files f '.$querytaxonomyleft0.
                                  'WHERE f.title like :search '.$querytaxonomywhere0)->setParameter('search', '%'.$search0.'%');
        $filecount = $query->getResult();
        if (!empty($filecount)) $filecount = $filecount[0]['filecount']; else $filecount = 0;
        $pagecount0 = ceil($filecount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY f.title ASC';
        if ($sort0 == 1) $sortsql = 'ORDER BY f.title DESC';
        if ($sort0 == 2) $sortsql = 'ORDER BY f.enabled ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY f.enabled DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY f.createDate ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY f.createDate DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        if ($sort0 == 8) $sortsql = 'ORDER BY f.ordering ASC';
        if ($sort0 == 9) $sortsql = 'ORDER BY f.ordering DESC';
        if ($sort0 == 10) $sortsql = 'ORDER BY f.fileName ASC';
        if ($sort0 == 11) $sortsql = 'ORDER BY f.fileName DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT f.id, f.title, f.createDate, f.modifyDate, u.fullName, f.enabled, f.ordering, f.fileName, f.fileSize, f.downloadCount FROM FilesFilesBundle:Files f '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.$querytaxonomyleft0.
                                  'WHERE f.title like :search '.$querytaxonomywhere0.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $files0 = $query->getResult();
        if ($this->container->has('object.taxonomy'))
        {
            $taxonomyenabled = 1;
            $actionIds = array();
            foreach ($files0 as $file) $actionIds[] = $file['id'];
            $taxonomyinfo0 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.file', $actionIds);
        } else
        {
            $taxonomyenabled = 0;
            $taxonomyinfo0 = null;
        }
        // Таб 2
        $query = $em->createQuery('SELECT c.id, c.title, c.enabled, sp.url FROM FilesFilesBundle:FileCreatePages c LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.file\' AND sp.contentAction = \'create\' AND sp.contentId = c.id ORDER BY c.id');
        $createpages1 = $query->getResult();
        foreach ($createpages1 as &$createpage) $createpage['title'] = $this->get('cms.cmsManager')->decodeLocalString($createpage['title'],'default',true);
        unset($createpage);
        // Таб 3
        $query = $em->createQuery('SELECT e.id, e.title, e.enabled, sp.url FROM FilesFilesBundle:FileEditPages e LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.file\' AND sp.contentAction = \'editpage\' AND sp.contentId = e.id ORDER BY e.id');
        $editpages2 = $query->getResult();
        foreach ($editpages2 as &$editpage) $editpage['title'] = $this->get('cms.cmsManager')->decodeLocalString($editpage['title'],'default',true);
        unset($editpage);
        return $this->render('FilesFilesBundle:Default:fileList.html.twig', array(
            'files0' => $files0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'pagecount0' => $pagecount0,
            'taxonomy0' => $taxonomy0,
            'taxonomyenabled' => $taxonomyenabled,
            'taxonomyinfo0' => $taxonomyinfo0,
            'createpages1' => $createpages1,
            'editpages2' => $editpages2,
            'tab' => $tab
        ));
        
    }
    
    
    
// *******************************************
// AJAX загрузка
// *******************************************    

    public function filesFileAjaxAvatarAction() 
    {
        $userId = $this->getUser()->getId();
        $file = $this->getRequest()->files->get('avatar');
        $tmpfile = $file->getPathName();
        if (@getimagesize($tmpfile)) 
        {
            $params = getimagesize($tmpfile);
            if ($params[0] < 10) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла меньше 10x10')));
            if ($params[1] < 10) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла меньше 10x10')));
            if ($params[0] > 6000) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла больше 6000x6000')));
            if ($params[1] > 6000) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла больше 6000x6000')));
            $imageTypeArray = array(0=>'', 1=>'gif', 2=>'jpg', 3=>'png', 4=>'', 5=>'', 6=>'', 7=>'', 8=>'', 9=>'jpg', 10=>'jpg', 11=>'jpg', 12=>'jpg', 13=>'', 14=>'', 15=>'', 16=>'', 17=>'', 18=>'');
            if (!isset($imageTypeArray[$params[2]]) || ($imageTypeArray[$params[2]] == ''))  return new Response(json_encode(array('file' => '', 'error' => 'Формат файла не поддерживается')));
            $basepath = '/images/file/';
            $name = $this->getUser()->getId().'_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
            if (move_uploaded_file($tmpfile, '..'.$basepath.$name)) 
            {
                $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $file->getClientOriginalName());
                return new Response(json_encode(array('file' => $basepath.$name, 'error' => '')));
            }
        }
        return new Response(json_encode(array('file' => '', 'error' => 'Неправильный формат файла')));
    }
    
    public function filesFileAjaxFileAction() 
    {
        $userId = $this->getUser()->getId();
        $file = $this->getRequest()->files->get('file');
        $tmpfile = $file->getPathName();
        $basepath = '/secured/file/';
        $name = $this->getUser()->getId().'_'.md5($tmpfile.time()).'.dat';
        if (move_uploaded_file($tmpfile, '..'.$basepath.$name)) 
        {
            $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $file->getClientOriginalName());
            return new Response(json_encode(array('file' => $basepath.$name, 'filename' => $file->getClientOriginalName(), 'filesize' => number_format(filesize('..'.$basepath.$name) / 1024, 0, ',', ' '), 'error' => '')));
        }
        else return new Response(json_encode(array('file' => '', 'filename' => '', 'filesize' => '', 'error' => 'Ошибка загрузки файла')));
    }
    
// *******************************************
// Создание нового файла
// *******************************************    
    public function filesFileCreateAction()
    {
        if ($this->getUser()->checkAccess('file_new') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание нового файла',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $file = array();
        $fileerror = array();
        
        $file['title'] = '';
        $file['description'] = '';
        $file['avatar'] = '';
        $file['fileName'] = '';
        $file['fileSize'] = '';
        $file['contentFile'] = '';
        $file['onlyAutorized'] = '';
        $file['enabled'] = 1;
        $file['metadescr'] = '';
        $file['metakey'] = '';
        $fileerror['title'] = '';
        $fileerror['description'] = '';
        $fileerror['avatar'] = '';
        $fileerror['fileName'] = '';
        $fileerror['contentFile'] = '';
        $fileerror['onlyAutorized'] = '';
        $fileerror['enabled'] = '';
        $fileerror['metadescr'] = '';
        $fileerror['metakey'] = '';
        
        $page = array();
        $pageerror = array();
        
        $page['enable'] = 0;
        $page['url'] = '';
        $page['modules'] = array();
        $page['locale'] = '';
        $page['template'] = '';
        $page['layout'] = '';
        $page['accessOn'] = '';
        $page['access'] = array();
        $pageerror['enable'] = '';
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.file','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.file.view');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $fileloc = array();
        $filelocerror = array();
        foreach ($locales as $locale)
        {
            $fileloc[$locale['shortName']]['title'] = '';
            $fileloc[$locale['shortName']]['description'] = '';
            $fileloc[$locale['shortName']]['metadescr'] = '';
            $fileloc[$locale['shortName']]['metakey'] = '';
            $filelocerror[$locale['shortName']]['title'] = '';
            $filelocerror[$locale['shortName']]['description'] = '';
            $filelocerror[$locale['shortName']]['metadescr'] = '';
            $filelocerror[$locale['shortName']]['metakey'] = '';
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postfile = $this->getRequest()->get('file');
            if (isset($postfile['title'])) $file['title'] = $postfile['title'];
            if (isset($postfile['description'])) $file['description'] = $postfile['description'];
            if (isset($postfile['avatar'])) $file['avatar'] = $postfile['avatar'];
            if (isset($postfile['fileName'])) $file['fileName'] = $postfile['fileName'];
            if (isset($postfile['onlyAutorized'])) $file['onlyAutorized'] = intval($postfile['onlyAutorized']); else $file['onlyAutorized'] = 0;
            if (isset($postfile['contentFile'])) $file['contentFile'] = $postfile['contentFile'];
            if (isset($postfile['enabled'])) $file['enabled'] = intval($postfile['enabled']); else $text['enabled'] = 0;
            if (isset($postfile['metadescr'])) $file['metadescr'] = $postfile['metadescr'];
            if (isset($postfile['metakey'])) $file['metakey'] = $postfile['metakey'];
            unset($postfile);
            if (!preg_match("/^.{3,}$/ui", $file['title'])) {$errors = true; $fileerror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $file['metadescr'])) {$errors = true; $fileerror['metadescr'] = 'Использованы недопустимые символы';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $file['metakey'])) {$errors = true; $fileerror['metakey'] = 'Использованы недопустимые символы';}
            if (($file['avatar'] != '') && (!file_exists('..'.$file['avatar']))) {$errors = true; $fileerror['avatar'] = 'Файл не найден';}
            if (($file['contentFile'] == '') || (!file_exists('..'.$file['contentFile']))) {$errors = true; $fileerror['contentFile'] = 'Файл не найден'; $file['fileSize'] = '';}
            else $file['fileSize'] = filesize('..'.$file['contentFile']);
            if (!preg_match("/^[0-9A-zА-яЁё \-!#\$\%\&\*\(\)_\+\?\.]{3,}$/ui", $file['fileName'])) {$errors = true; $fileerror['fileName'] = 'Имя файла должно содержать более 3 символов';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->getRequest()->get('page');
            if (isset($postpage['enable'])) $page['enable'] = intval($postpage['enable']); else $page['enable'] = 0;
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['enable'] != 0)
            {
                if ($page['url'] != '')
                {
                    if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                    else
                    {
                        $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url')->setParameter('url', $page['url']);
                        $checkurl = $query->getResult();
                        if ($checkurl[0]['urlcount'] != 0) {$errors = true; $pageerror['url'] = 'Страница с таким адресом уже существует';}
                    }
                }
                $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $page['locale']) $localecheck = true;}
                if ($localecheck == false) $page['locale'] = '';
                if (($page['layout'] != '') && (!isset($layouts[$page['layout']]))) {$errors = true; $pageerror['layout'] = 'Шаблон не найден';}
                if (($page['template'] != '') && (!isset($templates[$page['template']]))) {$errors = true; $pageerror['template'] = 'Шаблон не найден';}
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Валидация локлизации
            $postfileloc = $this->getRequest()->get('fileloc');
            foreach ($locales as $locale)
            {
                if (isset($postfileloc[$locale['shortName']]['title'])) $fileloc[$locale['shortName']]['title'] = $postfileloc[$locale['shortName']]['title'];
                if (isset($postfileloc[$locale['shortName']]['description'])) $fileloc[$locale['shortName']]['description'] = $postfileloc[$locale['shortName']]['description'];
                if (isset($postfileloc[$locale['shortName']]['metadescr'])) $fileloc[$locale['shortName']]['metadescr'] = $postfileloc[$locale['shortName']]['metadescr'];
                if (isset($postfileloc[$locale['shortName']]['metakey'])) $fileloc[$locale['shortName']]['metakey'] = $postfileloc[$locale['shortName']]['metakey'];
                if (($fileloc[$locale['shortName']]['title'] != '') && (!preg_match("/^.{3,}$/ui", $fileloc[$locale['shortName']]['title']))) {$errors = true; $filelocerror[$locale['shortName']]['title'] = 'Заголовок должен содержать более 3 символов';}
                if (($fileloc[$locale['shortName']]['metadescr'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $fileloc[$locale['shortName']]['metadescr']))) {$errors = true; $filelocerror[$locale['shortName']]['metadescr'] = 'Использованы недопустимые символы';}
                if (($fileloc[$locale['shortName']]['metakey'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $fileloc[$locale['shortName']]['metakey']))) {$errors = true; $filelocerror[$locale['shortName']]['metakey'] = 'Использованы недопустимые символы';}
            }
            unset($postfileloc);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            if ($this->container->has('object.taxonomy')) 
            {
                $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'create', 'object.file', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }
            foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'filesFileCreate', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($file['avatar']);
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($file['contentFile']);
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($file['description'], '');
                $query = $em->createQuery('SELECT max(f.ordering) as maxorder FROM FilesFilesBundle:Files f');
                $order = $query->getResult();
                if (isset($order[0]['maxorder'])) $order = $order[0]['maxorder'] + 1; else $order = 1;
                
                $fileent = new \Files\FilesBundle\Entity\Files();
                $fileent->setContentFile($file['contentFile']);
                $fileent->setFileName($file['fileName']);
                $fileent->setOnlyAutorized($file['onlyAutorized']);
                $fileent->setAvatar($file['avatar']);
                $fileent->setCreateDate(new \DateTime('now'));
                $fileent->setCreaterId($this->getUser()->getId());
                $fileent->setDescription($file['description']);
                $fileent->setEnabled($file['enabled']);
                $fileent->setMetaDescription($file['metadescr']);
                $fileent->setMetaKeywords($file['metakey']);
                $fileent->setModifyDate(new \DateTime('now'));
                $fileent->setOrdering($order);
                $fileent->setTitle($file['title']);
                $fileent->setFileSize($file['fileSize']);
                $fileent->setDownloadCount(0);
                
                $em->persist($fileent);
                $em->flush();
                if ($page['enable'] != 0)
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $pageent->setBreadCrumbs('');
                    $pageent->setBreadCrumbsRel('');
                    $pageent->setDescription('Просмотр файла '.$fileent->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.file');
                    $pageent->setContentId($fileent->getId());
                    $pageent->setContentAction('view');
                    $fileent->setTemplate($page['template']);
                    if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                    $em->persist($pageent);
                    $em->flush();
                    if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                }
                foreach ($locales as $locale)
                {
                    if (($fileloc[$locale['shortName']]['title'] != '') ||
                        ($fileloc[$locale['shortName']]['description'] != '') ||
                        ($fileloc[$locale['shortName']]['metadescr'] != '') ||
                        ($fileloc[$locale['shortName']]['metakey'] != ''))
                    {
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($fileloc[$locale['shortName']]['description'], '');
                        $filelocent = new \Files\FilesBundle\Entity\FilesLocale();
                        $filelocent->setFileId($fileent->getId());
                        $filelocent->setLocale($locale['shortName']);
                        if ($fileloc[$locale['shortName']]['title'] == '') $filelocent->setTitle(null); else $filelocent->setTitle($fileloc[$locale['shortName']]['title']);
                        if ($fileloc[$locale['shortName']]['description'] == '') $filelocent->setDescription(null); else $filelocent->setDescription($fileloc[$locale['shortName']]['description']);
                        if ($fileloc[$locale['shortName']]['metadescr'] == '') $filelocent->setMetaDescription(null); else $filelocent->setMetaDescription($fileloc[$locale['shortName']]['metadescr']);
                        if ($fileloc[$locale['shortName']]['metakey'] == '') $filelocent->setMetaKeywords(null); else $filelocent->setMetaKeywords($fileloc[$locale['shortName']]['metakey']);
                        $em->persist($filelocent);
                        $em->flush();
                        unset($filelocent);
                    }
                }
                $i = 0;
                if ($this->container->has('object.taxonomy')) 
                {
                    $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'create', 'object.file', $fileent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }
                foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'filesFileCreate', $fileent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание нового файла',
                    'message'=>'Файл успешно создано',
                    'paths'=>array('Создать еще один файл'=>$this->get('router')->generate('files_files_file_create'),'Вернуться к списку файлов'=>$this->get('router')->generate('files_files_file_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        if ($this->container->has('object.taxonomy')) $tabs[] =  array('name'=>'Категории классификации','content'=>$this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'create', 'object.file', 0, 'tab'));
        foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'filesFileCreate', 0, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('FilesFilesBundle:Default:fileCreate.html.twig', array(
            'file' => $file,
            'fileerror' => $fileerror,
            'page' => $page,
            'pageerror' => $pageerror,
            'fileloc' => $fileloc,
            'filelocerror' => $filelocerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'roles' => $roles,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
    }

// *******************************************
// Редактирование файла
// *******************************************    
    public function filesFileEditAction()
    {
        $id = intval($this->getRequest()->get('id'));
        $fileent = $this->getDoctrine()->getRepository('FilesFilesBundle:Files')->find($id);
        if (empty($fileent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование файла',
                'message'=>'Файл не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к списку файлов'=>$this->get('router')->generate('files_files_file_list'))
            ));
        }
        if (($this->getUser()->checkAccess('file_viewall') == 0) && ($this->getUser()->getId() != $fileent->getCreaterId()))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование файла',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        if (($this->getUser()->checkAccess('file_viewown') == 0) && ($this->getUser()->getId() == $fileent->getCreaterId()))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование файла',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.file','contentId'=>$id,'contentAction'=>'view'));
        $em = $this->getDoctrine()->getEntityManager();
        
        $file = array();
        $fileerror = array();
        
        $file['title'] = $fileent->getTitle();
        $file['description'] = $fileent->getDescription();
        $file['avatar'] = $fileent->getAvatar();
        $file['fileName'] = $fileent->getFileName();
        $file['fileSize'] = @filesize('..'.$fileent->getContentFile());
        $file['contentFile'] = $fileent->getContentFile();
        $file['onlyAutorized'] = $fileent->getOnlyAutorized();
        $file['enabled'] = $fileent->getEnabled();
        $file['metadescr'] = $fileent->getMetaDescription();
        $file['metakey'] = $fileent->getMetaKeywords();
        $fileerror['title'] = '';
        $fileerror['description'] = '';
        $fileerror['avatar'] = '';
        $fileerror['fileName'] = '';
        $fileerror['contentFile'] = '';
        $fileerror['onlyAutorized'] = '';
        $fileerror['enabled'] = '';
        $fileerror['metadescr'] = '';
        $fileerror['metakey'] = '';
        
        $page = array();
        $pageerror = array();
        
        if (empty($pageent))
        {
            $page['enable'] = 0;
            $page['url'] = '';
            $page['modules'] = array();
            $page['locale'] = '';
            $page['template'] = '';
            $page['layout'] = '';
            $page['accessOn'] = '';
            $page['access'] = array();
        } else
        {
            $page['enable'] = 1;
            $page['url'] = ($pageent->getUrl() != $pageent->getId() ? $pageent->getUrl() : '');
            $page['modules'] = explode(',',$pageent->getModules());
            $page['locale'] = $pageent->getLocale();
            $page['template'] = $fileent->getTemplate();
            $page['layout'] = $pageent->getTemplate();
            if (($pageent->getAccess() == '') || (!is_array(explode(',', $pageent->getAccess()))))
            {
                $page['accessOn'] = 0;
                $page['access'] = array();
            } else
            {
                $page['accessOn'] = 1;
                $page['access'] = explode(',', $pageent->getAccess());
            }
        }
        $pageerror['enable'] = '';
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.file','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (empty($pageent))
        {
            if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
            if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        }
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.file.view');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $fileloc = array();
        $filelocerror = array();
        foreach ($locales as $locale)
        {
            $filelocent = $this->getDoctrine()->getRepository('FilesFilesBundle:FilesLocale')->findOneBy(array('locale'=>$locale['shortName'],'fileId'=>$id));
            if (!empty($filelocent))
            {
                $fileloc[$locale['shortName']]['title'] = $filelocent->getTitle();
                $fileloc[$locale['shortName']]['description'] = $filelocent->getDescription();
                $fileloc[$locale['shortName']]['metadescr'] = $filelocent->getMetaDescription();
                $fileloc[$locale['shortName']]['metakey'] = $filelocent->getMetaKeywords();
                unset($filelocent);
            } else 
            {
                $fileloc[$locale['shortName']]['title'] = '';
                $fileloc[$locale['shortName']]['description'] = '';
                $fileloc[$locale['shortName']]['metadescr'] = '';
                $fileloc[$locale['shortName']]['metakey'] = '';
            }
            $filelocerror[$locale['shortName']]['title'] = '';
            $filelocerror[$locale['shortName']]['description'] = '';
            $filelocerror[$locale['shortName']]['metadescr'] = '';
            $filelocerror[$locale['shortName']]['metakey'] = '';
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            if (($this->getUser()->checkAccess('file_editall') == 0) && ($this->getUser()->getId() != $fileent->getCreaterId()))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование файла',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            if (($this->getUser()->checkAccess('file_editown') == 0) && ($this->getUser()->getId() == $fileent->getCreaterId()))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование файла',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            // Проверка основных данных
            $postfile = $this->getRequest()->get('file');
            if (isset($postfile['title'])) $file['title'] = $postfile['title'];
            if (isset($postfile['description'])) $file['description'] = $postfile['description'];
            if (isset($postfile['avatar'])) $file['avatar'] = $postfile['avatar'];
            if (isset($postfile['fileName'])) $file['fileName'] = $postfile['fileName'];
            if (isset($postfile['onlyAutorized'])) $file['onlyAutorized'] = intval($postfile['onlyAutorized']); else $file['onlyAutorized'] = 0;
            if (isset($postfile['contentFile'])) $file['contentFile'] = $postfile['contentFile'];
            if (isset($postfile['enabled'])) $file['enabled'] = intval($postfile['enabled']); else $text['enabled'] = 0;
            if (isset($postfile['metadescr'])) $file['metadescr'] = $postfile['metadescr'];
            if (isset($postfile['metakey'])) $file['metakey'] = $postfile['metakey'];
            unset($postfile);
            if (!preg_match("/^.{3,}$/ui", $file['title'])) {$errors = true; $fileerror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $file['metadescr'])) {$errors = true; $fileerror['metadescr'] = 'Использованы недопустимые символы';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $file['metakey'])) {$errors = true; $fileerror['metakey'] = 'Использованы недопустимые символы';}
            if (($file['avatar'] != '') && (!file_exists('..'.$file['avatar']))) {$errors = true; $fileerror['avatar'] = 'Файл не найден';}
            if (($file['contentFile'] == '') || (!file_exists('..'.$file['contentFile']))) {$errors = true; $fileerror['contentFile'] = 'Файл не найден'; $file['fileSize'] = '';}
            else $file['fileSize'] = filesize('..'.$file['contentFile']);
            if (!preg_match("/^[0-9A-zА-яЁё \-!#\$\%\&\*\(\)_\+\?\.]{3,}$/ui", $file['fileName'])) {$errors = true; $fileerror['fileName'] = 'Имя файла должно содержать более 3 символов';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->getRequest()->get('page');
            if (isset($postpage['enable'])) $page['enable'] = intval($postpage['enable']); else $page['enable'] = 0;
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['enable'] != 0)
            {
                if ($page['url'] != '')
                {
                    if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                    else
                    {
                        if (empty($pageent)) $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url')->setParameter('url', $page['url']);
                                        else $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url AND s.id != :id')->setParameter('id', $pageent->getId())->setParameter('url', $page['url']);
                        $checkurl = $query->getResult();
                        if ($checkurl[0]['urlcount'] != 0) {$errors = true; $pageerror['url'] = 'Страница с таким адресом уже существует';}
                    }
                }
                $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $page['locale']) $localecheck = true;}
                if ($localecheck == false) $page['locale'] = '';
                if (($page['layout'] != '') && (!isset($layouts[$page['layout']]))) {$errors = true; $pageerror['layout'] = 'Шаблон не найден';}
                if (($page['template'] != '') && (!isset($templates[$page['template']]))) {$errors = true; $pageerror['template'] = 'Шаблон не найден';}
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Валидация локлизации
            $postfileloc = $this->getRequest()->get('fileloc');
            foreach ($locales as $locale)
            {
                if (isset($postfileloc[$locale['shortName']]['title'])) $fileloc[$locale['shortName']]['title'] = $postfileloc[$locale['shortName']]['title'];
                if (isset($postfileloc[$locale['shortName']]['description'])) $fileloc[$locale['shortName']]['description'] = $postfileloc[$locale['shortName']]['description'];
                if (isset($postfileloc[$locale['shortName']]['metadescr'])) $fileloc[$locale['shortName']]['metadescr'] = $postfileloc[$locale['shortName']]['metadescr'];
                if (isset($postfileloc[$locale['shortName']]['metakey'])) $fileloc[$locale['shortName']]['metakey'] = $postfileloc[$locale['shortName']]['metakey'];
                if (($fileloc[$locale['shortName']]['title'] != '') && (!preg_match("/^.{3,}$/ui", $fileloc[$locale['shortName']]['title']))) {$errors = true; $filelocerror[$locale['shortName']]['title'] = 'Заголовок должен содержать более 3 символов';}
                if (($fileloc[$locale['shortName']]['metadescr'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $fileloc[$locale['shortName']]['metadescr']))) {$errors = true; $filelocerror[$locale['shortName']]['metadescr'] = 'Использованы недопустимые символы';}
                if (($fileloc[$locale['shortName']]['metakey'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $fileloc[$locale['shortName']]['metakey']))) {$errors = true; $filelocerror[$locale['shortName']]['metakey'] = 'Использованы недопустимые символы';}
            }
            unset($postfileloc);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            if ($this->container->has('object.taxonomy')) 
            {
                $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'edit', 'object.file', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }
            foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'filesFileEdit', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                if (($fileent->getAvatar() != '') && ($fileent->getAvatar() != $file['avatar'])) @unlink('..'.$fileent->getAvatar());
                if (($fileent->getContentFile() != '') && ($fileent->getContentFile() != $file['contentFile'])) @unlink('..'.$fileent->getContentFile());
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($file['avatar']);
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($file['contentFile']);
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($file['description'], $fileent->getDescription());
                $fileent->setContentFile($file['contentFile']);
                $fileent->setFileName($file['fileName']);
                $fileent->setOnlyAutorized($file['onlyAutorized']);
                $fileent->setAvatar($file['avatar']);
                $fileent->setDescription($file['description']);
                $fileent->setEnabled($file['enabled']);
                $fileent->setMetaDescription($file['metadescr']);
                $fileent->setMetaKeywords($file['metakey']);
                $fileent->setModifyDate(new \DateTime('now'));
                $fileent->setTitle($file['title']);
                $fileent->setFileSize($file['fileSize']);
                $em->flush();
                if (!empty($pageent)) $this->get('cms.cmsManager')->updateBreadCrumbs($pageent->getId());
                if ($page['enable'] != 0)
                {
                    if (empty($pageent))
                    {
                        $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                        $em->persist($pageent);
                    }
                    $pageent->setBreadCrumbs('');
                    $pageent->setBreadCrumbsRel('');
                    $pageent->setDescription('Просмотр файла '.$fileent->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.file');
                    $pageent->setContentId($fileent->getId());
                    $pageent->setContentAction('view');
                    $fileent->setTemplate($page['template']);
                    if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                    $em->flush();
                    if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                } else
                {
                    if (!empty($pageent))
                    {
                        $em->remove($pageent);
                        $em->flush();
                    }
                }

                foreach ($locales as $locale)
                {
                    $filelocent = $this->getDoctrine()->getRepository('FilesFilesBundle:FilesLocale')->findOneBy(array('locale'=>$locale['shortName'],'fileId'=>$id));
                    if (($fileloc[$locale['shortName']]['title'] != '') ||
                        ($fileloc[$locale['shortName']]['description'] != '') ||
                        ($fileloc[$locale['shortName']]['metadescr'] != '') ||
                        ($fileloc[$locale['shortName']]['metakey'] != ''))
                    {
                        if (empty($filelocent))
                        {
                            $filelocent = new \Files\FilesBundle\Entity\FilesLocale();
                            $em->persist($filelocent);
                        }
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($fileloc[$locale['shortName']]['description'], $filelocent->getDescription());
                        $filelocent->setFileId($fileent->getId());
                        $filelocent->setLocale($locale['shortName']);
                        if ($fileloc[$locale['shortName']]['title'] == '') $filelocent->setTitle(null); else $filelocent->setTitle($fileloc[$locale['shortName']]['title']);
                        if ($fileloc[$locale['shortName']]['description'] == '') $filelocent->setDescription(null); else $filelocent->setDescription($fileloc[$locale['shortName']]['description']);
                        if ($fileloc[$locale['shortName']]['metadescr'] == '') $filelocent->setMetaDescription(null); else $filelocent->setMetaDescription($fileloc[$locale['shortName']]['metadescr']);
                        if ($fileloc[$locale['shortName']]['metakey'] == '') $filelocent->setMetaKeywords(null); else $filelocent->setMetaKeywords($fileloc[$locale['shortName']]['metakey']);
                        $em->flush();
                    } else
                    {
                        if (!empty($filelocent))
                        {
                            $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $filelocent->getDescription());
                            $em->remove($filelocent);
                            $em->flush();
                        }
                    }
                    unset($filelocent);
                }
                $i = 0;
                if ($this->container->has('object.taxonomy')) 
                {
                    $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'edit', 'object.file', $fileent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }
                foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'filesFileEdit', $fileent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование файла',
                    'message'=>'Данные файла успешно изменены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('files_files_file_edit').'?id='.$id,'Вернуться к списку файлов'=>$this->get('router')->generate('files_files_file_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        if ($this->container->has('object.taxonomy')) $tabs[] =  array('name'=>'Категории классификации','content'=>$this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'edit', 'object.file', $id, 'tab'));
        foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'filesFileEdit', $id, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('FilesFilesBundle:Default:fileEdit.html.twig', array(
            'id' => $id,
            'createrId' => $fileent->getCreaterId(),
            'file' => $file,
            'fileerror' => $fileerror,
            'page' => $page,
            'pageerror' => $pageerror,
            'fileloc' => $fileloc,
            'filelocerror' => $filelocerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'roles' => $roles,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// AJAX-контроллер
// *******************************************    
    
    public function filesFileAjaxAction()
    {
        $tab = intval($this->getRequest()->get('tab'));
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->getRequest()->get('action');
        $errors = array();
        $errorsorder = array();
        if ($tab == 0)
        {
            if ($this->getUser()->checkAccess('file_list') == 0) 
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Файлы',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            if ($action == 'delete')
            {
                if ($this->getUser()->checkAccess('file_editall') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Файлы',
                        'message'=>'Доступ запрещён', 
                        'error'=>true,
                        'paths'=>array()
                    ));
                }
                $cmsservices = $this->container->getServiceIds();
                $check = $this->getRequest()->get('check');
                if ($check != null)
                foreach ($check as $key=>$val)
                    if ($val == 1)
                    {
                        $fileent = $this->getDoctrine()->getRepository('FilesFilesBundle:Files')->find($key);
                        if (!empty($fileent))
                        {
                            if ($fileent->getAvatar() != '') @unlink('..'.$fileent->getAvatar());
                            if ($fileent->getContentFile() != '') @unlink('..'.$fileent->getContentFile());
                            $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $fileent->getDescription());
                            $em->remove($fileent);
                            $em->flush();
                            $query = $em->createQuery('SELECT fl.description FROM FilesFilesBundle:FilesLocale fl WHERE fl.fileId = :id')->setParameter('id', $key);
                            $delpages = $query->getResult();
                            if (is_array($delpages))
                            {
                                foreach ($delpages as $delpage)
                                {
                                    if ($delpage['description'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $delpage['description']);
                                }
                            }
                            $query = $em->createQuery('DELETE FROM FilesFilesBundle:FilesLocale fl WHERE fl.fileId = :id')->setParameter('id', $key);
                            $query->execute();
                            $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.file\' AND p.contentAction = \'view\' AND p.contentId = :id')->setParameter('id', $key);
                            $query->execute();
                            if ($this->container->has('object.taxonomy')) $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'delete', 'object.file', $key, 'save');
                            foreach ($cmsservices as $item) if (strpos($item,'addone.file.') === 0) 
                            {
                                $serv = $this->container->get($item);
                                $serv->getAdminController($this->getRequest(), 'filesFileDelete', $key, 'save');
                            }       
                            unset($fileent);    
                        }
                    }
            }
            if ($action == 'blocked')
            {
                if ($this->getUser()->checkAccess('file_editall') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Файлы',
                        'message'=>'Доступ запрещён', 
                        'error'=>true,
                        'paths'=>array()
                    ));
                }
                $check = $this->getRequest()->get('check');
                if ($check != null)
                foreach ($check as $key=>$val)
                    if ($val == 1)
                    {
                        $fileent = $this->getDoctrine()->getRepository('FilesFilesBundle:Files')->find($key);
                        if (!empty($fileent))
                        {
                            $fileent->setEnabled(0);
                            $em->flush();
                            unset($fileent);    
                        }
                    }
            }
            if ($action == 'unblocked')
            {
                if ($this->getUser()->checkAccess('file_editall') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Файлы',
                        'message'=>'Доступ запрещён', 
                        'error'=>true,
                        'paths'=>array()
                    ));
                }
                $check = $this->getRequest()->get('check');
                if ($check != null)
                foreach ($check as $key=>$val)
                    if ($val == 1)
                    {
                        $fileent = $this->getDoctrine()->getRepository('FilesFilesBundle:Files')->find($key);
                        if (!empty($fileent))
                        {
                            $fileent->setEnabled(1);
                            $em->flush();
                            unset($fileent);    
                        }
                    }
            }
            if ($action == 'ordering')
            {
                if ($this->getUser()->checkAccess('file_editall') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Файлы',
                        'message'=>'Доступ запрещён', 
                        'error'=>true,
                        'paths'=>array()
                    ));
                }
                $ordering = $this->getRequest()->get('ordering');
                $error = false;
                $ids = array();
                foreach ($ordering as $key=>$val) $ids[] = $key;
                foreach ($ordering as $key=>$val)
                {
                    if (!preg_match("/^\d{1,20}$/ui", $val)) {$error = true; $errorsorder[$key] = 'Введено не число';} else
                    {
                        foreach ($ordering as $key2=>$val2) if (($key != $key2) && ($val == $val2)) {$error = true; $errorsorder[$key] = 'Введено два одинаковых числа';} else
                        {
                            $query = $em->createQuery('SELECT count(f.id) as checkid FROM FilesFilesBundle:Files f '.
                                                      'WHERE f.ordering = :order AND f.id NOT IN (:ids)')->setParameter('ids', $ids)->setParameter('order', $val);
                            $checkid = $query->getResult();
                            if (isset($checkid[0]['checkid'])) $checkid = $checkid[0]['checkid']; else $checkid = 0;
                            if ($checkid != 0) {$error = true; $errorsorder[$key] = 'Число уже используется';}
                        }
                    }
                }
                // изменить порядок
                if ($error == false)
                {
                    foreach ($ordering as $key=>$val)
                    {
                        $fileent = $this->getDoctrine()->getRepository('FilesFilesBundle:Files')->find($key);
                        $fileent->setOrdering($val);
                    }
                    $em->flush();
                }
            }
            $page0 = $this->getRequest()->getSession()->get('files_files_file_list_page0');
            $sort0 = $this->getRequest()->getSession()->get('files_files_file_list_sort0');
            $search0 = $this->getRequest()->getSession()->get('files_files_file_list_search0');
            $taxonomy0 = $this->getRequest()->getSession()->get('files_files_file_list_taxonomy0');
            $page0 = intval($page0);
            $sort0 = intval($sort0);
            $search0 = trim($search0);
            $taxonomy0 = intval($taxonomy0);
            // Поиск контента для 1 таба (текстовые страницы)
            $querytaxonomyleft0 = '';
            $querytaxonomywhere0 = '';
            if (($this->container->has('object.taxonomy')) && ($taxonomy0 != 0))
            {
                $querytaxonomyleft0 = ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks txl WITH txl.taxonomyId = '.$taxonomy0.' AND txl.itemId = f.id ';
                $querytaxonomywhere0 = ' AND txl.id IS NOT NULL ';
            }
            $query = $em->createQuery('SELECT count(f.id) as filecount FROM FilesFilesBundle:Files f '.$querytaxonomyleft0.
                                      'WHERE f.title like :search '.$querytaxonomywhere0)->setParameter('search', '%'.$search0.'%');
            $filecount = $query->getResult();
            if (!empty($filecount)) $filecount = $filecount[0]['filecount']; else $filecount = 0;
            $pagecount0 = ceil($filecount / 20);
            if ($pagecount0 < 1) $pagecount0 = 1;
            if ($page0 < 0) $page0 = 0;
            if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
            $sortsql = 'ORDER BY f.title ASC';
            if ($sort0 == 1) $sortsql = 'ORDER BY f.title DESC';
            if ($sort0 == 2) $sortsql = 'ORDER BY f.enabled ASC';
            if ($sort0 == 3) $sortsql = 'ORDER BY f.enabled DESC';
            if ($sort0 == 4) $sortsql = 'ORDER BY f.createDate ASC';
            if ($sort0 == 5) $sortsql = 'ORDER BY f.createDate DESC';
            if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
            if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
            if ($sort0 == 8) $sortsql = 'ORDER BY f.ordering ASC';
            if ($sort0 == 9) $sortsql = 'ORDER BY f.ordering DESC';
            if ($sort0 == 10) $sortsql = 'ORDER BY f.fileName ASC';
            if ($sort0 == 11) $sortsql = 'ORDER BY f.fileName DESC';
            $start = $page0 * 20;
            $query = $em->createQuery('SELECT f.id, f.title, f.createDate, f.modifyDate, u.fullName, f.enabled, f.ordering, f.fileName, f.fileSize, f.downloadCount FROM FilesFilesBundle:Files f '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.$querytaxonomyleft0.
                                      'WHERE f.title like :search '.$querytaxonomywhere0.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
            $files0 = $query->getResult();
            if ($this->container->has('object.taxonomy'))
            {
                $taxonomyenabled = 1;
                $actionIds = array();
                foreach ($files0 as $file) $actionIds[] = $file['id'];
                $taxonomyinfo0 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.file', $actionIds);
            } else
            {
                $taxonomyenabled = 0;
                $taxonomyinfo0 = null;
            }
            foreach ($files0 as &$file)
            {
                if (($action == 'ordering') && (isset($ordering[$file['id']]))) $file['ordering'] = $ordering[$file['id']];
            }
            unset($file);
            return $this->render('FilesFilesBundle:Default:fileListTab1.html.twig', array(
                'files0' => $files0,
                'search0' => $search0,
                'page0' => $page0,
                'sort0' => $sort0,
                'pagecount0' => $pagecount0,
                'taxonomy0' => $taxonomy0,
                'taxonomyenabled' => $taxonomyenabled,
                'taxonomyinfo0' => $taxonomyinfo0,
                'tab' => $tab,
                'errors0' => $errors,
                'errorsorder0' => $errorsorder
            ));
        }
        if ($tab == 1)
        {
            if ($this->getUser()->checkAccess('file_createpage') == 0) 
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Файлы',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            if ($action == 'deletepage')
            {
                $cmsservices = $this->container->getServiceIds();
                $check = $this->getRequest()->get('check');
                if ($check != null)
                foreach ($check as $key=>$val)
                    if ($val == 1)
                    {
                        $pageent = $this->getDoctrine()->getRepository('FilesFilesBundle:FileCreatePages')->find($key);
                        if (!empty($pageent))
                        {
                            $em->remove($pageent);
                            $em->flush();
                            unset($pageent);    
                        }
                    }
            }
            if ($action == 'blockedpage')
            {
                $check = $this->getRequest()->get('check');
                if ($check != null)
                foreach ($check as $key=>$val)
                    if ($val == 1)
                    {
                        $pageent = $this->getDoctrine()->getRepository('FilesFilesBundle:FileCreatePages')->find($key);
                        if (!empty($pageent))
                        {
                            $pageent->setEnabled(0);
                            $em->flush();
                            unset($pageent);    
                        }
                    }
            }
            if ($action == 'unblockedpage')
            {
                $check = $this->getRequest()->get('check');
                if ($check != null)
                foreach ($check as $key=>$val)
                    if ($val == 1)
                    {
                        $pageent = $this->getDoctrine()->getRepository('FilesFilesBundle:FileCreatePages')->find($key);
                        if (!empty($pageent))
                        {
                            $pageent->setEnabled(1);
                            $em->flush();
                            unset($pageent);    
                        }
                    }
            }
            $query = $em->createQuery('SELECT c.id, c.title, c.enabled, sp.url FROM FilesFilesBundle:FileCreatePages c LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.file\' AND sp.contentAction = \'create\' AND sp.contentId = c.id ORDER BY c.id');
            $createpages1 = $query->getResult();
            foreach ($createpages1 as &$createpage) $createpage['title'] = $this->get('cms.cmsManager')->decodeLocalString($createpage['title'],'default',true);
            unset($createpage);
            return $this->render('FilesFilesBundle:Default:fileListTab2.html.twig', array(
                'createpages1' => $createpages1,
                'errors1' => $errors,
                'tab' => $tab
            ));
        }
        if ($tab == 2)
        {
            if ($this->getUser()->checkAccess('file_editpage') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Файлы',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            if ($action == 'deletepage')
            {
                $cmsservices = $this->container->getServiceIds();
                $check = $this->getRequest()->get('check');
                if ($check != null)
                foreach ($check as $key=>$val)
                    if ($val == 1)
                    {
                        $pageent = $this->getDoctrine()->getRepository('FilesFilesBundle:FileEditPages')->find($key);
                        if (!empty($pageent))
                        {
                            $em->remove($pageent);
                            $em->flush();
                            unset($pageent);    
                        }
                    }
            }
            if ($action == 'blockedpage')
            {
                $check = $this->getRequest()->get('check');
                if ($check != null)
                foreach ($check as $key=>$val)
                    if ($val == 1)
                    {
                        $pageent = $this->getDoctrine()->getRepository('FilesFilesBundle:FileEditPages')->find($key);
                        if (!empty($pageent))
                        {
                            $pageent->setEnabled(0);
                            $em->flush();
                            unset($pageent);    
                        }
                    }
            }
            if ($action == 'unblockedpage')
            {
                $check = $this->getRequest()->get('check');
                if ($check != null)
                foreach ($check as $key=>$val)
                    if ($val == 1)
                    {
                        $pageent = $this->getDoctrine()->getRepository('FilesFilesBundle:FileEditPages')->find($key);
                        if (!empty($pageent))
                        {
                            $pageent->setEnabled(1);
                            $em->flush();
                            unset($pageent);    
                        }
                    }
            }
            $query = $em->createQuery('SELECT e.id, e.title, e.enabled, sp.url FROM FilesFilesBundle:FileEditPages e LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.file\' AND sp.contentAction = \'editpage\' AND sp.contentId = e.id ORDER BY e.id');
            $editpages2 = $query->getResult();
            foreach ($editpages2 as &$editpage) $editpage['title'] = $this->get('cms.cmsManager')->decodeLocalString($editpage['title'],'default',true);
            unset($editpage);
            return $this->render('FilesFilesBundle:Default:fileListTab3.html.twig', array(
                'editpages2' => $editpages2,
                'errors2' => $errors,
                'tab' => $tab
            ));
        }
    }
    
// *******************************************
// Фронт-контроллер скачивания
// *******************************************    
    
    public function frontFileDownloadAction() 
    {
        $id = intval($this->getRequest()->get('id'));
        $fileent = $this->getDoctrine()->getRepository('FilesFilesBundle:Files')->find($id);
        if (empty($fileent)) return new Response('File not found', 404);
        // Проверить доступ к файлу
        if ($fileent->getOnlyAutorized() != 0)
        {
            $userid = $this->getRequest()->getSession()->get('front_system_autorized');
            $userEntity = null;
            if ($userid != null) $userEntity = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($userid);
            if (empty($userEntity))
            {
                $userid = $this->getRequest()->cookies->get('front_autorization_keytwo');
                $userpass = $this->getRequest()->cookies->get('front_autorization_keyone');
                $query = $this->getDoctrine()->getEntityManager()->createQuery('SELECT u FROM BasicCmsBundle:Users u WHERE MD5(CONCAT(u.id,\'Embedded.CMS\')) = :userid AND MD5(CONCAT(u.password, u.salt)) = :userpass')->setParameter('userid', $userid)->setParameter('userpass', $userpass);
                $userEntity = $query->getResult();
                if ((count($userEntity) == 1) && (isset($userEntity[0])) && (!empty($userEntity[0]))) $userEntity = $userEntity[0];
            }
            if (empty($userEntity)) return new Response('Access denied', 403);
            if ($userEntity->getBlocked() != 2) return new Response('Access denied', 403);
        }
        if ($this->getRequest()->getSession()->get('front_system_download_file') != $id) 
        {
            $fileent->setDownloadCount($fileent->getDownloadCount() + 1);
            $this->getDoctrine()->getEntityManager()->flush();
            $this->getRequest()->getSession()->set('front_system_download_file', $id);
        }
        // Выдать файл
        $filePath = '..'.$fileent->getContentFile();
        $newFileName = $fileent->getFileName();
        if (is_file($filePath)) 
        {
            header("Content-type: application/octet-stream");
            $fp = fopen($filePath, 'rb');
            $size = filesize($filePath);
            $length = $size;
            $start = 0;
            $end = $size - 1;
            if (isset($_SERVER['HTTP_RANGE']))
            {
                $c_start = $start;
                $c_end = $end;
                list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                if (strpos($range, ',') !== false) 
                {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    die;
                }
                if (substr($range, 0, 1) == '-')
                {
                    $c_start = $size - substr($range, 1);
                } else
                {
                    $range = explode('-', $range);
                    $c_start = $range[0];
                    $c_end = (isset($range[1]) && is_numeric($range[1]) ) ? $range[1] : $size;
                }
                $c_end = ($c_end > $end) ? $end : $c_end;
                if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size)
                {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    die;
                }
                $start = $c_start;
                $end = $c_end;
                $length = $end - $start + 1;
                fseek($fp, $start);
                header('HTTP/1.1 206 Partial Content');
                header('ETag: "' . md5(microtime()) . '"');
                header("Content-Range: bytes $start-$end/$size");
            }
            header("Content-Length: $length");
            header('Connection: Close');
            if (strpos($_SERVER['HTTP_USER_AGENT'],"MSIE" ) > 0 ) header('Content-Disposition: attachment; filename="'.rawurlencode(basename($newFileName)).'";');
            else header('Content-Disposition: attachment; filename*=UTF-8\'\''.rawurlencode(basename($newFileName)).';');

            $buffer = 1024 * 512;
            while (!feof($fp) && ($p = ftell($fp)) <= $end)
            {
                if ($p + $buffer > $end)
                {
                    $buffer = $end - $p + 1;
                }
                set_time_limit(600);
                echo fread($fp, $buffer);
                flush();
            }
            fclose($fp);
        } else
        {
            return new Response('File not found', 404);
        }
        die;
        /*if (($this->container->get('security.context')->isGranted('ROLE_ADMIN')) || 
            ($this->container->get('security.context')->isGranted('ROLE_RISK')) ||
            ($this->container->get('security.context')->isGranted('ROLE_OPERATOR')))
        {
            $filename = '../secured/'.$type.'/'.$file;
            $newfilename = $this->getRequest()->get('name');
            if ($newfilename == null) $newfilename = $filename;
            if (!file_exists($filename)) return new Response('');
            $resp = new Response();
            $resp->headers->set('Content-Type','application/octet-stream');
            $resp->headers->set('Last-Modified',gmdate('r', filemtime($filename)));
            $resp->headers->set('Content-Length',filesize($filename));
            $resp->headers->set('Connection','close');
            if (strpos($_SERVER['HTTP_USER_AGENT'],"MSIE" ) > 0 )
                $resp->headers->set('Content-Disposition','attachment; filename="' . rawurlencode(basename($newfilename))  . '";' );
            else
                $resp->headers->set('Content-Disposition','attachment; filename*=UTF-8\'\'' . rawurlencode(basename($newfilename)) .';');
            //$resp->headers->set('Content-Disposition','attachment; filename="'.basename($newfilename).'";');
            $resp->setContent(file_get_contents($filename));
            return $resp;
        }
        return new Response('');*/
    }

// *******************************************
// Создание страницы создания файла
// *******************************************    
    public function filesCreatepageCreateAction()
    {
        $this->getRequest()->getSession()->set('files_files_file_list_tab', 1);
        if ($this->getUser()->checkAccess('file_createpage') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой страницы создания файлов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $createpage = array();
        $createpageerror = array();
        
        $createpage['title'] = array();
        $createpage['title']['default'] = '';
        foreach ($locales as $locale) $createpage['title'][$locale['shortName']] = '';
        $createpage['enabled'] = 1;
        $createpage['captchaEnabled'] = '';
        $createpage['onlyAutorized'] = '';
        $createpage['categoryMode'] = '';
        $createpage['categoryId'] = '';
        $createpage['fileEnabled'] = '';
        $createpage['fileOnlyAutorized'] = '';
        $createpage['seopageEnable'] = '';
        $createpage['seopageLayout'] = '';
        $createpage['seopageTemplate'] = '';
        $createpage['seopageUrl'] = '';
        $createpage['seopageModules'] = array();
        $createpage['seopageAccessOn'] = '';
        $createpage['seopageAccess'] = array();
        $createpage['seopageLocale'] = '';
        
        $createpageerror['title'] = array();
        $createpageerror['title']['default'] = '';
        foreach ($locales as $locale) $createpageerror['title'][$locale['shortName']] = '';
        $createpageerror['enabled'] = '';
        $createpageerror['captchaEnabled'] = '';
        $createpageerror['onlyAutorized'] = '';
        $createpageerror['categoryMode'] = '';
        $createpageerror['categoryId'] = '';
        $createpageerror['fileEnabled'] = '';
        $createpageerror['fileOnlyAutorized'] = '';
        $createpageerror['seopageEnable'] = '';
        $createpageerror['seopageLayout'] = '';
        $createpageerror['seopageTemplate'] = '';
        $createpageerror['seopageUrl'] = '';
        $createpageerror['seopageModules'] = '';
        $createpageerror['seopageAccessOn'] = '';
        $createpageerror['seopageAccess'] = '';
        $createpageerror['seopageLocale'] = '';
        
        $page = array();
        $pageerror = array();
        
        $page['url'] = '';
        $page['modules'] = array();
        $page['locale'] = '';
        $page['template'] = '';
        $page['layout'] = '';
        $page['accessOn'] = '';
        $page['access'] = array();
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.file','create');
        $filetemplates = $this->get('cms.cmsManager')->getTemplateList('object.file','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($filetemplates) > 0) {reset($filetemplates); $regpage['seopageTemplate'] = key($filetemplates);}
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts); $regpage['seopageLayout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn, LOCATE(:moduleuserid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleUserDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.file.create')->setParameter('moduleuserid','object.file.view');
        $modules = $query->getResult();
        foreach ($modules as $module) 
        {
            if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
            if ($module['moduleUserDefOn'] != 0) $regpage['seopageModules'][] = $module['id'];
        }
        // Данные таксономии
        if ($this->container->has('object.taxonomy')) $taxonomyinfo = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.file'); else $taxonomyinfo = array();
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postcreatepage = $this->getRequest()->get('createpage');
            if (isset($postcreatepage['title']['default'])) $createpage['title']['default'] = $postcreatepage['title']['default'];
            foreach ($locales as $locale) if (isset($postcreatepage['title'][$locale['shortName']])) $createpage['title'][$locale['shortName']] = $postcreatepage['title'][$locale['shortName']];
            if (isset($postcreatepage['enabled'])) $createpage['enabled'] = intval($postcreatepage['enabled']); else $createpage['enabled'] = 0;
            if (isset($postcreatepage['captchaEnabled'])) $createpage['captchaEnabled'] = intval($postcreatepage['captchaEnabled']); else $createpage['captchaEnabled'] = 0;
            if (isset($postcreatepage['onlyAutorized'])) $createpage['onlyAutorized'] = intval($postcreatepage['onlyAutorized']); else $createpage['onlyAutorized'] = 0;
            if (isset($postcreatepage['categoryMode'])) $createpage['categoryMode'] = intval($postcreatepage['categoryMode']); else $createpage['categoryMode'] = 0;
            if (isset($postcreatepage['categoryId'])) $createpage['categoryId'] = $postcreatepage['categoryId'];
            if (isset($postcreatepage['fileEnabled'])) $createpage['fileEnabled'] = intval($postcreatepage['fileEnabled']); else $createpage['fileEnabled'] = 0;
            if (isset($postcreatepage['fileOnlyAutorized'])) $createpage['fileOnlyAutorized'] = intval($postcreatepage['fileOnlyAutorized']); else $createpage['fileOnlyAutorized'] = 0;
            
            if (isset($postcreatepage['seopageEnable'])) $createpage['seopageEnable'] = intval($postcreatepage['seopageEnable']); else $createpage['seopageEnable'] = 0;
            if (isset($postcreatepage['seopageUrl'])) $createpage['seopageUrl'] = trim($postcreatepage['seopageUrl']);
            if (isset($postcreatepage['seopageModules']) && is_array($postcreatepage['seopageModules'])) $createpage['seopageModules'] = $postcreatepage['seopageModules']; else $createpage['seopageModules'] = array();
            if (isset($postcreatepage['seopageLocale'])) $createpage['seopageLocale'] = $postcreatepage['seopageLocale'];
            if (isset($postcreatepage['seopageTemplate'])) $createpage['seopageTemplate'] = $postcreatepage['seopageTemplate'];
            if (isset($postcreatepage['seopageLayout'])) $createpage['seopageLayout'] = $postcreatepage['seopageLayout'];
            if (isset($postcreatepage['seopageAccessOn'])) $createpage['seopageAccessOn'] = intval($postcreatepage['seopageAccessOn']);
            $createpage['seopageAccess'] = array();
            foreach ($roles as $onerole) if (isset($postcreatepage['seopageAccess']) && is_array($postcreatepage['seopageAccess']) && (in_array($onerole['id'], $postcreatepage['seopageAccess']))) $createpage['seopageAccess'][] = $onerole['id'];
            unset($postcreatepage);
            if (!preg_match("/^.{3,}$/ui", $createpage['title']['default'])) {$errors = true; $createpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $createpage['title'][$locale['shortName']])) {$errors = true; $createpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->getRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['url'] != '')
            {
                if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                else
                {
                    $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url')->setParameter('url', $page['url']);
                    $checkurl = $query->getResult();
                    if ($checkurl[0]['urlcount'] != 0) {$errors = true; $pageerror['url'] = 'Страница с таким адресом уже существует';}
                }
            }
            $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $page['locale']) $localecheck = true;}
            if ($localecheck == false) $page['locale'] = '';
            if (($page['layout'] != '') && (!isset($layouts[$page['layout']]))) {$errors = true; $pageerror['layout'] = 'Шаблон не найден';}
            if (($page['template'] != '') && (!isset($templates[$page['template']]))) {$errors = true; $pageerror['template'] = 'Шаблон не найден';}
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Проверка 3 таба
            if (($createpage['categoryMode'] < 0) || ($createpage['categoryMode'] > 2)) {$errors = true; $createpageerror['categoryMode'] = 'Устрановлено некорректное значение';}
            if ($createpage['categoryMode'] == 2)
            {
                $query = $em->createQuery('SELECT count(t.id) as taxcount FROM BasicCmsBundle:Taxonomies t WHERE t.id = :id AND t.enabled != 0 AND t.enableAdd != 0 AND t.object = \'object.file\'')->setParameter('id', $createpage['categoryId']);
                $taxcount = $query->getResult();
                if (isset($taxcount[0]['taxcount'])) $taxcount = intval($taxcount[0]['taxcount']); else $taxcount = 0;
                if ($taxcount == 0) {$errors = true; $createpageerror['categoryId'] = 'Категория не найдена или добавление запрещено';}
            }
            
            if ($createpage['seopageEnable'] != 0)
            {
                if ($createpage['seopageUrl'] != '')
                {
                    if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", str_replace(array('//id//','//title//'),'test',$createpage['seopageUrl']))) {$errors = true; $createpageerror['seopageUrl'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                }
                $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $createpage['seopageLocale']) $localecheck = true;}
                if ($localecheck == false) $createpage['seopageLocale'] = '';
                if (($createpage['seopageLayout'] != '') && (!isset($layouts[$createpage['seopageLayout']]))) {$errors = true; $createpageerror['seopageLayout'] = 'Шаблон не найден';}
                if (($createpage['seopageTemplate'] != '') && (!isset($filetemplates[$createpage['seopageTemplate']]))) {$errors = true; $createpageerror['seopageTemplate'] = 'Шаблон не найден';}
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $createpageent = new \Files\FilesBundle\Entity\FileCreatePages();
                $createpageent->setCaptchaEnabled($createpage['captchaEnabled']);
                $createpageent->setCategoryId(intval($createpage['categoryId']));
                $createpageent->setCategoryMode($createpage['categoryMode']);
                $createpageent->setEnabled($createpage['enabled']);
                $createpageent->setFileEnabled($createpage['fileEnabled']);
                $createpageent->setFileOnlyAutorized($createpage['fileOnlyAutorized']);
                $createpageent->setFileTemplate($createpage['seopageTemplate']);
                $createpageent->setOnlyAutorized($createpage['onlyAutorized']);
                if ($createpage['seopageAccessOn'] != 0) $createpageent->setSeopageAccess(implode(',', $createpage['seopageAccess'])); else $createpageent->setSeopageAccess('');
                $createpageent->setSeopageEnabled($createpage['seopageEnable']);
                $createpageent->setSeopageLocale($createpage['seopageLocale']);
                $createpageent->setSeopageModules(implode(',', $createpage['seopageModules']));
                $createpageent->setSeopageTemplate($createpage['seopageLayout']);
                $createpageent->setSeopageUrl($createpage['seopageUrl']);
                $createpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($createpage['title']));
                $em->persist($createpageent);
                $em->flush();
                
                $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница создания файлов '.$this->get('cms.cmsManager')->decodeLocalString($createpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.file');
                $pageent->setContentId($createpageent->getId());
                $pageent->setContentAction('create');
                $createpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->persist($pageent);
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой страницы создания файлов',
                    'message'=>'Страница создания файлов успешно создана',
                    'paths'=>array('Создать еще одну страницу создания файлов'=>$this->get('router')->generate('files_files_createpage_create'),'Вернуться к списку файлов'=>$this->get('router')->generate('files_files_file_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('FilesFilesBundle:Default:fileCreatepageCreate.html.twig', array(
            'createpage' => $createpage,
            'createpageerror' => $createpageerror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'filetemplates' => $filetemplates,
            'modules' => $modules,
            'layouts' => $layouts,
            'taxonomyinfo' => $taxonomyinfo,
            'activetab' => $activetab
        ));
    }

// *******************************************
// Редактирование страницы создания файлов
// *******************************************    
    public function filesCreatepageEditAction()
    {
        $this->getRequest()->getSession()->set('files_files_file_list_tab', 1);
        $id = intval($this->getRequest()->get('id'));
        $createpageent = $this->getDoctrine()->getRepository('FilesFilesBundle:FileCreatePages')->find($id);
        if (empty($createpageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы создания файлов',
                'message'=>'Страница создания файлов не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку файлов'=>$this->get('router')->generate('files_files_file_list'))
            ));
        }
        if ($this->getUser()->checkAccess('file_createpage') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы создания файлов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.file','contentId'=>$id,'contentAction'=>'create'));
        
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $createpage = array();
        $createpageerror = array();
        
        $createpage['title'] = array();
        $createpage['title']['default'] = $this->get('cms.cmsManager')->decodeLocalString($createpageent->getTitle(),'default',false);
        foreach ($locales as $locale) $createpage['title'][$locale['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($createpageent->getTitle(),$locale['shortName'],false);
        $createpage['enabled'] = $createpageent->getEnabled();
        $createpage['captchaEnabled'] = $createpageent->getCaptchaEnabled();
        $createpage['onlyAutorized'] = $createpageent->getOnlyAutorized();
        $createpage['categoryMode'] = $createpageent->getCategoryMode();
        $createpage['categoryId'] = $createpageent->getCategoryId();
        $createpage['fileEnabled'] = $createpageent->getFileEnabled();
        $createpage['fileOnlyAutorized'] = $createpageent->getFileOnlyAutorized();
        $createpage['seopageEnable'] = $createpageent->getSeopageEnabled();
        $createpage['seopageLayout'] = $createpageent->getSeopageTemplate();
        $createpage['seopageTemplate'] = $createpageent->getFileTemplate();
        $createpage['seopageUrl'] = $createpageent->getSeopageUrl();
        $createpage['seopageModules'] = explode(',', $createpageent->getSeopageModules());
        if (($createpageent->getSeopageAccess() == '') || (!is_array(explode(',', $createpageent->getSeopageAccess()))))
        {
            $createpage['seopageAccessOn'] = 0;
            $createpage['seopageAccess'] = array();
        } else
        {
            $createpage['seopageAccessOn'] = 1;
            $createpage['seopageAccess'] = explode(',', $createpageent->getSeopageAccess());
        }
        $createpage['seopageLocale'] = $createpageent->getSeopageLocale();
        
        $createpageerror['title'] = array();
        $createpageerror['title']['default'] = '';
        foreach ($locales as $locale) $createpageerror['title'][$locale['shortName']] = '';
        $createpageerror['enabled'] = '';
        $createpageerror['captchaEnabled'] = '';
        $createpageerror['onlyAutorized'] = '';
        $createpageerror['categoryMode'] = '';
        $createpageerror['categoryId'] = '';
        $createpageerror['fileEnabled'] = '';
        $createpageerror['fileOnlyAutorized'] = '';
        $createpageerror['seopageEnable'] = '';
        $createpageerror['seopageLayout'] = '';
        $createpageerror['seopageTemplate'] = '';
        $createpageerror['seopageUrl'] = '';
        $createpageerror['seopageModules'] = '';
        $createpageerror['seopageAccessOn'] = '';
        $createpageerror['seopageAccess'] = '';
        $createpageerror['seopageLocale'] = '';
        
        $page = array();
        $pageerror = array();
        
        if (empty($pageent))
        {
            $page['url'] = '';
            $page['modules'] = array();
            $page['locale'] = '';
            $page['template'] = '';
            $page['layout'] = '';
            $page['accessOn'] = '';
            $page['access'] = array();
        } else
        {
            $page['url'] = ($pageent->getUrl() != $pageent->getId() ? $pageent->getUrl() : '');
            $page['modules'] = explode(',',$pageent->getModules());
            $page['locale'] = $pageent->getLocale();
            $page['template'] = $createpageent->getTemplate();
            $page['layout'] = $pageent->getTemplate();
            if (($pageent->getAccess() == '') || (!is_array(explode(',', $pageent->getAccess()))))
            {
                $page['accessOn'] = 0;
                $page['access'] = array();
            } else
            {
                $page['accessOn'] = 1;
                $page['access'] = explode(',', $pageent->getAccess());
            }
        }
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.file','create');
        $filetemplates = $this->get('cms.cmsManager')->getTemplateList('object.file','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($filetemplates) > 0) {reset($filetemplates); $regpage['seopageTemplate'] = key($filetemplates);}
        if (empty($pageent))
        {
            if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
            if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts); $regpage['seopageLayout'] = key($layouts);}
        }
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn, LOCATE(:moduleuserid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleUserDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.file.create')->setParameter('moduleuserid','object.file.view');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        if (($createpage['seopageEnable'] != 0) && (count($createpage['seopageModules']) == 0))
        {
            foreach ($modules as $module) if ($module['moduleUserDefOn'] != 0) $createpage['seopageModules'][] = $module['id'];
        }
        // Данные таксономии
        if ($this->container->has('object.taxonomy')) $taxonomyinfo = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.file'); else $taxonomyinfo = array();
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postcreatepage = $this->getRequest()->get('createpage');
            if (isset($postcreatepage['title']['default'])) $createpage['title']['default'] = $postcreatepage['title']['default'];
            foreach ($locales as $locale) if (isset($postcreatepage['title'][$locale['shortName']])) $createpage['title'][$locale['shortName']] = $postcreatepage['title'][$locale['shortName']];
            if (isset($postcreatepage['enabled'])) $createpage['enabled'] = intval($postcreatepage['enabled']); else $createpage['enabled'] = 0;
            if (isset($postcreatepage['captchaEnabled'])) $createpage['captchaEnabled'] = intval($postcreatepage['captchaEnabled']); else $createpage['captchaEnabled'] = 0;
            if (isset($postcreatepage['onlyAutorized'])) $createpage['onlyAutorized'] = intval($postcreatepage['onlyAutorized']); else $createpage['onlyAutorized'] = 0;
            if (isset($postcreatepage['categoryMode'])) $createpage['categoryMode'] = intval($postcreatepage['categoryMode']); else $createpage['categoryMode'] = 0;
            if (isset($postcreatepage['categoryId'])) $createpage['categoryId'] = $postcreatepage['categoryId'];
            if (isset($postcreatepage['fileEnabled'])) $createpage['fileEnabled'] = intval($postcreatepage['fileEnabled']); else $createpage['fileEnabled'] = 0;
            if (isset($postcreatepage['fileOnlyAutorized'])) $createpage['fileOnlyAutorized'] = intval($postcreatepage['fileOnlyAutorized']); else $createpage['fileOnlyAutorized'] = 0;
            if (isset($postcreatepage['seopageEnable'])) $createpage['seopageEnable'] = intval($postcreatepage['seopageEnable']); else $createpage['seopageEnable'] = 0;
            if (isset($postcreatepage['seopageUrl'])) $createpage['seopageUrl'] = trim($postcreatepage['seopageUrl']);
            if (isset($postcreatepage['seopageModules']) && is_array($postcreatepage['seopageModules'])) $createpage['seopageModules'] = $postcreatepage['seopageModules']; else $createpage['seopageModules'] = array();
            if (isset($postcreatepage['seopageLocale'])) $createpage['seopageLocale'] = $postcreatepage['seopageLocale'];
            if (isset($postcreatepage['seopageTemplate'])) $createpage['seopageTemplate'] = $postcreatepage['seopageTemplate'];
            if (isset($postcreatepage['seopageLayout'])) $createpage['seopageLayout'] = $postcreatepage['seopageLayout'];
            if (isset($postcreatepage['seopageAccessOn'])) $createpage['seopageAccessOn'] = intval($postcreatepage['seopageAccessOn']);
            $createpage['seopageAccess'] = array();
            foreach ($roles as $onerole) if (isset($postcreatepage['seopageAccess']) && is_array($postcreatepage['seopageAccess']) && (in_array($onerole['id'], $postcreatepage['seopageAccess']))) $createpage['seopageAccess'][] = $onerole['id'];
            unset($postcreatepage);
            if (!preg_match("/^.{3,}$/ui", $createpage['title']['default'])) {$errors = true; $createpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $createpage['title'][$locale['shortName']])) {$errors = true; $createpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->getRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['url'] != '')
            {
                if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                else
                {
                    if (empty($pageent)) $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url')->setParameter('url', $page['url']);
                                    else $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url AND s.id != :id')->setParameter('id', $pageent->getId())->setParameter('url', $page['url']);
                    $checkurl = $query->getResult();
                    if ($checkurl[0]['urlcount'] != 0) {$errors = true; $pageerror['url'] = 'Страница с таким адресом уже существует';}
                }
            }
            $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $page['locale']) $localecheck = true;}
            if ($localecheck == false) $page['locale'] = '';
            if (($page['layout'] != '') && (!isset($layouts[$page['layout']]))) {$errors = true; $pageerror['layout'] = 'Шаблон не найден';}
            if (($page['template'] != '') && (!isset($templates[$page['template']]))) {$errors = true; $pageerror['template'] = 'Шаблон не найден';}
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Проверка 3 таба
            if (($createpage['categoryMode'] < 0) || ($createpage['categoryMode'] > 2)) {$errors = true; $createpageerror['categoryMode'] = 'Устрановлено некорректное значение';}
            if ($createpage['categoryMode'] == 2)
            {
                $query = $em->createQuery('SELECT count(t.id) as taxcount FROM BasicCmsBundle:Taxonomies t WHERE t.id = :id AND t.enabled != 0 AND t.enableAdd != 0 AND t.object = \'object.file\'')->setParameter('id', $createpage['categoryId']);
                $taxcount = $query->getResult();
                if (isset($taxcount[0]['taxcount'])) $taxcount = intval($taxcount[0]['taxcount']); else $taxcount = 0;
                if ($taxcount == 0) {$errors = true; $createpageerror['categoryId'] = 'Категория не найдена или добавление запрещено';}
            }
            if ($createpage['seopageEnable'] != 0)
            {
                if ($createpage['seopageUrl'] != '')
                {
                    if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", str_replace(array('//id//','//title//'),'test',$createpage['seopageUrl']))) {$errors = true; $createpageerror['seopageUrl'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                }
                $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $createpage['seopageLocale']) $localecheck = true;}
                if ($localecheck == false) $createpage['seopageLocale'] = '';
                if (($createpage['seopageLayout'] != '') && (!isset($layouts[$createpage['seopageLayout']]))) {$errors = true; $createpageerror['seopageLayout'] = 'Шаблон не найден';}
                if (($createpage['seopageTemplate'] != '') && (!isset($filetemplates[$createpage['seopageTemplate']]))) {$errors = true; $createpageerror['seopageTemplate'] = 'Шаблон не найден';}
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $createpageent->setCaptchaEnabled($createpage['captchaEnabled']);
                $createpageent->setCategoryId(intval($createpage['categoryId']));
                $createpageent->setCategoryMode($createpage['categoryMode']);
                $createpageent->setEnabled($createpage['enabled']);
                $createpageent->setFileEnabled($createpage['fileEnabled']);
                $createpageent->setFileOnlyAutorized($createpage['fileOnlyAutorized']);
                $createpageent->setFileTemplate($createpage['seopageTemplate']);
                $createpageent->setOnlyAutorized($createpage['onlyAutorized']);
                if ($createpage['seopageAccessOn'] != 0) $createpageent->setSeopageAccess(implode(',', $createpage['seopageAccess'])); else $createpageent->setSeopageAccess('');
                $createpageent->setSeopageEnabled($createpage['seopageEnable']);
                $createpageent->setSeopageLocale($createpage['seopageLocale']);
                $createpageent->setSeopageModules(implode(',', $createpage['seopageModules']));
                $createpageent->setSeopageTemplate($createpage['seopageLayout']);
                $createpageent->setSeopageUrl($createpage['seopageUrl']);
                $createpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($createpage['title']));
                $em->flush();
                
                if (empty($pageent))
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $em->persist($pageent);
                }
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница создания файлов '.$this->get('cms.cmsManager')->decodeLocalString($createpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.file');
                $pageent->setContentId($createpageent->getId());
                $pageent->setContentAction('create');
                $createpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование страницы создания файлов',
                    'message'=>'Данные страницы создания файлов успешно сохранены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('files_files_createpage_edit').'?id='.$id,'Вернуться к списку файлов'=>$this->get('router')->generate('files_files_file_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('FilesFilesBundle:Default:fileCreatepageEdit.html.twig', array(
            'id' => $id,
            'createpage' => $createpage,
            'createpageerror' => $createpageerror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'filetemplates' => $filetemplates,
            'modules' => $modules,
            'layouts' => $layouts,
            'taxonomyinfo' => $taxonomyinfo,
            'activetab' => $activetab
        ));
    }
    
    public function filesFrontAjaxAvatarAction() 
    {
        $file = $this->getRequest()->files->get('avatar');
        $tmpfile = $file->getPathName();
        if (@getimagesize($tmpfile)) 
        {
            $params = getimagesize($tmpfile);
            if ($params[0] < 10) return new Response(json_encode(array('file' => '', 'error' => 'size')));
            if ($params[1] < 10) return new Response(json_encode(array('file' => '', 'error' => 'size')));
            if ($params[0] > 6000) return new Response(json_encode(array('file' => '', 'error' => 'size')));
            if ($params[1] > 6000) return new Response(json_encode(array('file' => '', 'error' => 'size')));
            $imageTypeArray = array(0=>'', 1=>'gif', 2=>'jpg', 3=>'png', 4=>'', 5=>'', 6=>'', 7=>'', 8=>'', 9=>'jpg', 10=>'jpg', 11=>'jpg', 12=>'jpg', 13=>'', 14=>'', 15=>'', 16=>'', 17=>'', 18=>'');
            if (!isset($imageTypeArray[$params[2]]) || ($imageTypeArray[$params[2]] == ''))  return new Response(json_encode(array('file' => '', 'error' => 'format')));
            $basepath = '/images/file/';
            $name = 'f_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
            if (move_uploaded_file($tmpfile, '..'.$basepath.$name)) 
            {
                $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $file->getClientOriginalName());
                return new Response(json_encode(array('file' => $basepath.$name, 'error' => '')));
            }
        }
        return new Response(json_encode(array('file' => '', 'error' => 'format')));
    }
    
    public function filesFrontAjaxContentFileAction() 
    {
        $file = $this->getRequest()->files->get('file');
        $tmpfile = $file->getPathName();
        $basepath = '/secured/file/';
        $name = 'f_'.md5($tmpfile.time()).'.dat';
        if (move_uploaded_file($tmpfile, '..'.$basepath.$name)) 
        {
            $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $file->getClientOriginalName());
            return new Response(json_encode(array('file' => $basepath.$name, 'error' => '', 'filename' => $file->getClientOriginalName(), 'filesize' => filesize('..'.$basepath.$name))));
        }
        return new Response(json_encode(array('file' => '', 'error' => 'format', 'filename' => '', 'filesize' => 0)));
    }
    
// *******************************************
// Создание страницы редактирования файла
// *******************************************    
    public function filesEditpageCreateAction()
    {
        $this->getRequest()->getSession()->set('files_files_file_list_tab', 2);
        if ($this->getUser()->checkAccess('file_editpage') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой страницы редактирования файлов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $editpage = array();
        $editpageerror = array();
        
        $editpage['title'] = array();
        $editpage['title']['default'] = '';
        foreach ($locales as $locale) $editpage['title'][$locale['shortName']] = '';
        $editpage['enabled'] = 1;
        $editpage['captchaEnabled'] = '';
        $editpage['onlyAutorized'] = '';
        $editpage['onlyOwn'] = '';
        $editpage['resetFileEnabled'] = '';
        
        $editpageerror['title'] = array();
        $editpageerror['title']['default'] = '';
        foreach ($locales as $locale) $editpageerror['title'][$locale['shortName']] = '';
        $editpageerror['enabled'] = '';
        $editpageerror['captchaEnabled'] = '';
        $editpageerror['onlyAutorized'] = '';
        $editpageerror['onlyOwn'] = '';
        $editpageerror['resetFileEnabled'] = '';
        
        $page = array();
        $pageerror = array();
        
        $page['url'] = '';
        $page['modules'] = array();
        $page['locale'] = '';
        $page['template'] = '';
        $page['layout'] = '';
        $page['accessOn'] = '';
        $page['access'] = array();
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.file','editpage');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts); $regpage['seopageLayout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.file.editpage');
        $modules = $query->getResult();
        foreach ($modules as $module) 
        {
            if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $posteditpage = $this->getRequest()->get('editpage');
            if (isset($posteditpage['title']['default'])) $editpage['title']['default'] = $posteditpage['title']['default'];
            foreach ($locales as $locale) if (isset($posteditpage['title'][$locale['shortName']])) $editpage['title'][$locale['shortName']] = $posteditpage['title'][$locale['shortName']];
            if (isset($posteditpage['enabled'])) $editpage['enabled'] = intval($posteditpage['enabled']); else $editpage['enabled'] = 0;
            if (isset($posteditpage['captchaEnabled'])) $editpage['captchaEnabled'] = intval($posteditpage['captchaEnabled']); else $editpage['captchaEnabled'] = 0;
            if (isset($posteditpage['onlyAutorized'])) $editpage['onlyAutorized'] = intval($posteditpage['onlyAutorized']); else $editpage['onlyAutorized'] = 0;
            if (isset($posteditpage['onlyOwn'])) $editpage['onlyOwn'] = intval($posteditpage['onlyOwn']); else $editpage['onlyOwn'] = 0;
            if (isset($posteditpage['resetFileEnabled'])) $editpage['resetFileEnabled'] = intval($posteditpage['resetFileEnabled']); else $editpage['resetFileEnabled'] = 0;
            unset($posteditpage);
            if (!preg_match("/^.{3,}$/ui", $editpage['title']['default'])) {$errors = true; $editpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $editpage['title'][$locale['shortName']])) {$errors = true; $editpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->getRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['url'] != '')
            {
                if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                else
                {
                    $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url')->setParameter('url', $page['url']);
                    $checkurl = $query->getResult();
                    if ($checkurl[0]['urlcount'] != 0) {$errors = true; $pageerror['url'] = 'Страница с таким адресом уже существует';}
                }
            }
            $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $page['locale']) $localecheck = true;}
            if ($localecheck == false) $page['locale'] = '';
            if (($page['layout'] != '') && (!isset($layouts[$page['layout']]))) {$errors = true; $pageerror['layout'] = 'Шаблон не найден';}
            if (($page['template'] != '') && (!isset($templates[$page['template']]))) {$errors = true; $pageerror['template'] = 'Шаблон не найден';}
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $editpageent = new \Files\FilesBundle\Entity\FileEditPages();
                $editpageent->setCaptchaEnabled($editpage['captchaEnabled']);
                $editpageent->setEnabled($editpage['enabled']);
                $editpageent->setOnlyAutorized($editpage['onlyAutorized']);
                $editpageent->setOnlyOwn($editpage['onlyOwn']);
                $editpageent->setResetFileEnabled($editpage['resetFileEnabled']);
                $editpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($editpage['title']));
                $em->persist($editpageent);
                $em->flush();
                
                $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница редактирования файлов '.$this->get('cms.cmsManager')->decodeLocalString($editpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.file');
                $pageent->setContentId($editpageent->getId());
                $pageent->setContentAction('editpage');
                $editpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->persist($pageent);
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой страницы редактирования файлов',
                    'message'=>'Страница редактирования файлов успешно создана',
                    'paths'=>array('Создать еще одну страницу редактирования файлов'=>$this->get('router')->generate('files_files_editpage_create'),'Вернуться к списку файлов'=>$this->get('router')->generate('files_files_file_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('FilesFilesBundle:Default:fileEditpageCreate.html.twig', array(
            'editpage' => $editpage,
            'editpageerror' => $editpageerror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'activetab' => $activetab
        ));
    }

// *******************************************
// Редактирование страницы редактирования файлов
// *******************************************    
    public function filesEditpageEditAction()
    {
        $this->getRequest()->getSession()->set('files_files_file_list_tab', 2);
        $id = intval($this->getRequest()->get('id'));
        $editpageent = $this->getDoctrine()->getRepository('FilesFilesBundle:FileEditPages')->find($id);
        if (empty($editpageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы редактирования файлов',
                'message'=>'Страница редактирования файлов не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку файлов'=>$this->get('router')->generate('files_files_file_list'))
            ));
        }
        if ($this->getUser()->checkAccess('file_editpage') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы редактирования файлов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.file','contentId'=>$id,'contentAction'=>'editpage'));
        
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $editpage = array();
        $editpageerror = array();
        
        $editpage['title'] = array();
        $editpage['title']['default'] = $this->get('cms.cmsManager')->decodeLocalString($editpageent->getTitle(),'default',false);
        foreach ($locales as $locale) $editpage['title'][$locale['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($editpageent->getTitle(),$locale['shortName'],false);
        $editpage['enabled'] = $editpageent->getEnabled();
        $editpage['captchaEnabled'] = $editpageent->getCaptchaEnabled();
        $editpage['onlyAutorized'] = $editpageent->getOnlyAutorized();
        $editpage['onlyOwn'] = $editpageent->getOnlyOwn();
        $editpage['resetFileEnabled'] = $editpageent->getResetFileEnabled();
        
        $editpageerror['title'] = array();
        $editpageerror['title']['default'] = '';
        foreach ($locales as $locale) $editpageerror['title'][$locale['shortName']] = '';
        $editpageerror['enabled'] = '';
        $editpageerror['captchaEnabled'] = '';
        $editpageerror['onlyAutorized'] = '';
        $editpageerror['onlyOwn'] = '';
        $editpageerror['resetFileEnabled'] = '';
        
        $page = array();
        $pageerror = array();
        
        if (empty($pageent))
        {
            $page['url'] = '';
            $page['modules'] = array();
            $page['locale'] = '';
            $page['template'] = '';
            $page['layout'] = '';
            $page['accessOn'] = '';
            $page['access'] = array();
        } else
        {
            $page['url'] = ($pageent->getUrl() != $pageent->getId() ? $pageent->getUrl() : '');
            $page['modules'] = explode(',',$pageent->getModules());
            $page['locale'] = $pageent->getLocale();
            $page['template'] = $editpageent->getTemplate();
            $page['layout'] = $pageent->getTemplate();
            if (($pageent->getAccess() == '') || (!is_array(explode(',', $pageent->getAccess()))))
            {
                $page['accessOn'] = 0;
                $page['access'] = array();
            } else
            {
                $page['accessOn'] = 1;
                $page['access'] = explode(',', $pageent->getAccess());
            }
        }
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.file','editpage');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (empty($pageent))
        {
            if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
            if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts); $regpage['seopageLayout'] = key($layouts);}
        }
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.file.editpage');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $posteditpage = $this->getRequest()->get('editpage');
            if (isset($posteditpage['title']['default'])) $editpage['title']['default'] = $posteditpage['title']['default'];
            foreach ($locales as $locale) if (isset($posteditpage['title'][$locale['shortName']])) $editpage['title'][$locale['shortName']] = $posteditpage['title'][$locale['shortName']];
            if (isset($posteditpage['enabled'])) $editpage['enabled'] = intval($posteditpage['enabled']); else $editpage['enabled'] = 0;
            if (isset($posteditpage['captchaEnabled'])) $editpage['captchaEnabled'] = intval($posteditpage['captchaEnabled']); else $editpage['captchaEnabled'] = 0;
            if (isset($posteditpage['onlyAutorized'])) $editpage['onlyAutorized'] = intval($posteditpage['onlyAutorized']); else $editpage['onlyAutorized'] = 0;
            if (isset($posteditpage['onlyOwn'])) $editpage['onlyOwn'] = intval($posteditpage['onlyOwn']); else $editpage['onlyOwn'] = 0;
            if (isset($posteditpage['resetFileEnabled'])) $editpage['resetFileEnabled'] = intval($posteditpage['resetFileEnabled']); else $editpage['resetFileEnabled'] = 0;
            unset($posteditpage);
            if (!preg_match("/^.{3,}$/ui", $editpage['title']['default'])) {$errors = true; $editpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $editpage['title'][$locale['shortName']])) {$errors = true; $editpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->getRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['url'] != '')
            {
                if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                else
                {
                    if (empty($pageent)) $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url')->setParameter('url', $page['url']);
                                    else $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url AND s.id != :id')->setParameter('id', $pageent->getId())->setParameter('url', $page['url']);
                    $checkurl = $query->getResult();
                    if ($checkurl[0]['urlcount'] != 0) {$errors = true; $pageerror['url'] = 'Страница с таким адресом уже существует';}
                }
            }
            $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $page['locale']) $localecheck = true;}
            if ($localecheck == false) $page['locale'] = '';
            if (($page['layout'] != '') && (!isset($layouts[$page['layout']]))) {$errors = true; $pageerror['layout'] = 'Шаблон не найден';}
            if (($page['template'] != '') && (!isset($templates[$page['template']]))) {$errors = true; $pageerror['template'] = 'Шаблон не найден';}
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $editpageent->setCaptchaEnabled($editpage['captchaEnabled']);
                $editpageent->setEnabled($editpage['enabled']);
                $editpageent->setOnlyAutorized($editpage['onlyAutorized']);
                $editpageent->setOnlyOwn($editpage['onlyOwn']);
                $editpageent->setResetFileEnabled($editpage['resetFileEnabled']);
                $editpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($editpage['title']));
                $em->flush();
                if (empty($pageent))
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $em->persist($pageent);
                }
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница редактирования файлов '.$this->get('cms.cmsManager')->decodeLocalString($editpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.file');
                $pageent->setContentId($editpageent->getId());
                $pageent->setContentAction('editpage');
                $editpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование страницы редактирования файлов',
                    'message'=>'Данные страницы редактирования файлов успешно сохранены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('files_files_editpage_edit').'?id='.$id,'Вернуться к списку файлов'=>$this->get('router')->generate('files_files_file_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('FilesFilesBundle:Default:fileEditpageEdit.html.twig', array(
            'id' => $id,
            'editpage' => $editpage,
            'editpageerror' => $editpageerror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'activetab' => $activetab
        ));
    }

    
    
    
    
}
