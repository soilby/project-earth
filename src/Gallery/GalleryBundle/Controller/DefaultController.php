<?php

namespace Gallery\GalleryBundle\Controller;

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
// Получение информации о видео
// *******************************************    

    private function checkVideoUrl($video)
    {
        if (empty($video)) {return array('error' => 'Пустая ссылка');}
        if (!preg_match('/^(http|https)\:\/\//i', $video)) 
        {
            $video = 'http://' . $video;
        }
        // YouTube
        if (preg_match('/[http|https]+:\/\/(?:www\.|)youtube\.com\/watch\?(?:.*)?v=([a-zA-Z0-9_\-]+)/i', $video, $matches) || preg_match('/[http|https]+:\/\/(?:www\.|)youtube\.com\/embed\/([a-zA-Z0-9_\-]+)/i', $video, $matches) || preg_match('/[http|https]+:\/\/(?:www\.|)youtu\.be\/([a-zA-Z0-9_\-]+)/i', $video, $matches)) 
        {
            $video = 'http://www.youtube.com/embed/'.$matches[1];
            $image = 'http://img.youtube.com/vi/'.$matches[1].'/0.jpg';
            $array = array('video' => $video, 'image' => $image, 'error' => null);
        }
        // Vimeo
        else if (preg_match('/[http|https]+:\/\/(?:www\.|)vimeo\.com\/([a-zA-Z0-9_\-]+)(&.+)?/i', $video, $matches) || preg_match('/[http|https]+:\/\/player\.vimeo\.com\/video\/([a-zA-Z0-9_\-]+)(&.+)?/i', $video, $matches)) 
        {
            $video = 'http://player.vimeo.com/video/'.$matches[1];
            $image = '';
            if ($xml = simplexml_load_file('http://vimeo.com/api/v2/video/'.$matches[1].'.xml')) 
            {
                $image = $xml->video->thumbnail_large ? (string) $xml->video->thumbnail_large: (string) $xml->video->thumbnail_medium;
            }
            $array = array('video' => $video, 'image' => $image, 'error' => null);
        }
        // ruTube
        else if (preg_match('/[http|https]+:\/\/(?:www\.|)rutube\.ru\/video\/embed\/([a-zA-Z0-9_\-]+)/i', $video, $matches) || preg_match('/[http|https]+:\/\/(?:www\.|)rutube\.ru\/tracks\/([a-zA-Z0-9_\-]+)(&.+)?/i', $video, $matches)) 
        {
            $video = 'http://rutube.ru/video/embed/'.$matches[1];
            $image = '';
            if ($xml = simplexml_load_file("http://rutube.ru/cgi-bin/xmlapi.cgi?rt_mode=movie&rt_movie_id=".$matches[1]."&utf=1")) 
            {
                $image = (string) $xml->movie->thumbnailLink;
            }
            $array = array('video' => $video, 'image' => $image, 'error' => null);
        }
        else if (preg_match('/[http|https]+:\/\/(?:www\.|)rutube\.ru\/video\/([a-zA-Z0-9_\-]+)\//i', $video, $matches)) 
        {
            if (empty($matches[0])) return array('error' => 'Возможно неправильная ссылка');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $matches[0]);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $html = curl_exec($ch);
            return $this->checkVideoUrl($html);
        }
        // No matches
        else 
        {
            $array = array('error' => 'Возможно неправильная ссылка');
        }
        return $array;
    }
    
// *******************************************
// Список изображений галереи
// *******************************************    
    public function galleryImagesListAction()
    {
        if ($this->getUser()->checkAccess('image_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Изображения галереи',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $tab = 0;
        //$tab = $this->get('request_stack')->getMasterRequest()->get('tab');
        //if ($tab === null) $tab = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_textpage_list_tab');
        //              else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_textpage_list_tab', $tab);
        //if ($tab < 0) $tab = 0;
        //if ($tab > 1) $tab = 1;
        // Таб 1
        $page0 = $this->get('request_stack')->getMasterRequest()->get('page0');
        $sort0 = $this->get('request_stack')->getMasterRequest()->get('sort0');
        $search0 = $this->get('request_stack')->getMasterRequest()->get('search0');
        $taxonomy0 = $this->get('request_stack')->getMasterRequest()->get('taxonomy0');
        if ($page0 === null) $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('gallery_gallery_images_list_page0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('gallery_gallery_images_list_page0', $page0);
        if ($sort0 === null) $sort0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('gallery_gallery_images_list_sort0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('gallery_gallery_images_list_sort0', $sort0);
        if ($search0 === null) $search0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('gallery_gallery_images_list_search0');
                          else $this->get('request_stack')->getMasterRequest()->getSession()->set('gallery_gallery_images_list_search0', $search0);
        if ($taxonomy0 === null) $taxonomy0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('gallery_gallery_images_list_taxonomy0');
                          else $this->get('request_stack')->getMasterRequest()->getSession()->set('gallery_gallery_images_list_taxonomy0', $taxonomy0);
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        $taxonomy0 = intval($taxonomy0);
        // Поиск контента для 1 таба (текстовые страницы)
        $querytaxonomyleft0 = '';
        $querytaxonomywhere0 = '';
        if (($this->container->has('object.taxonomy')) && ($taxonomy0 != 0))
        {
            $querytaxonomyleft0 = ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks txl WITH txl.taxonomyId = '.$taxonomy0.' AND txl.itemId = i.id ';
            $querytaxonomywhere0 = ' AND txl.id IS NOT NULL ';
        }
        $query = $em->createQuery('SELECT count(i.id) as imgcount FROM GalleryGalleryBundle:Images i '.$querytaxonomyleft0.
                                  'WHERE i.title like :search '.$querytaxonomywhere0)->setParameter('search', '%'.$search0.'%');
        $imgcount = $query->getResult();
        if (!empty($imgcount)) $imgcount = $imgcount[0]['imgcount']; else $imgcount = 0;
        $pagecount0 = ceil($imgcount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY i.title ASC';
        if ($sort0 == 1) $sortsql = 'ORDER BY i.title DESC';
        if ($sort0 == 2) $sortsql = 'ORDER BY i.enabled ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY i.enabled DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY i.createDate ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY i.createDate DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        if ($sort0 == 8) $sortsql = 'ORDER BY i.ordering ASC';
        if ($sort0 == 9) $sortsql = 'ORDER BY i.ordering DESC';
        if ($sort0 == 10) $sortsql = 'ORDER BY i.isVideo ASC';
        if ($sort0 == 11) $sortsql = 'ORDER BY i.isVideo DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT i.id, i.title, i.createDate, i.modifyDate, u.fullName, i.enabled, i.ordering, i.avatar, i.isVideo FROM GalleryGalleryBundle:Images i '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = i.createrId '.$querytaxonomyleft0.
                                  'WHERE i.title like :search '.$querytaxonomywhere0.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $images0 = $query->getResult();
        if ($this->container->has('object.taxonomy'))
        {
            $taxonomyenabled = 1;
            $actionIds = array();
            foreach ($images0 as $image) $actionIds[] = $image['id'];
            $taxonomyinfo0 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.image', $actionIds);
        } else
        {
            $taxonomyenabled = 0;
            $taxonomyinfo0 = null;
        }
        return $this->render('GalleryGalleryBundle:Default:imagesList.html.twig', array(
            'images0' => $images0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'pagecount0' => $pagecount0,
            'taxonomy0' => $taxonomy0,
            'taxonomyenabled' => $taxonomyenabled,
            'taxonomyinfo0' => $taxonomyinfo0,
            'tab' => $tab
        ));
        
    }
    
    
    
// *******************************************
// AJAX загрузка изображения
// *******************************************    

    public function galleryImagesAjaxImageAction() 
    {
        $userId = $this->getUser()->getId();
        $file = $this->get('request_stack')->getMasterRequest()->files->get('foto');
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
            $basepath = '/images/gallery/';
            $name = $this->getUser()->getId().'_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
            if (move_uploaded_file($tmpfile, '.'.$basepath.$name)) 
            {
                $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $file->getClientOriginalName());
                return new Response(json_encode(array('file' => $basepath.$name, 'error' => '')));
            }
        }
        return new Response(json_encode(array('file' => '', 'error' => 'Неправильный формат файла')));
    }
    
    
// *******************************************
// Создание нового изображения
// *******************************************    
    public function galleryImagesCreateAction()
    {
        if ($this->getUser()->checkAccess('image_new') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание нового изображения',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $image = array();
        $imageerror = array();
        
        $image['title'] = '';
        $image['description'] = '';
        $image['isVideo'] = '';
        $image['contentFoto'] = '';
        $image['contentVideo'] = '';
        $image['enabled'] = 1;
        $image['metadescr'] = '';
        $image['metakey'] = '';
        $imageerror['title'] = '';
        $imageerror['description'] = '';
        $imageerror['isVideo'] = '';
        $imageerror['contentFoto'] = '';
        $imageerror['contentVideo'] = '';
        $imageerror['enabled'] = '';
        $imageerror['metadescr'] = '';
        $imageerror['metakey'] = '';
        
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
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.image','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.image.view');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $imageloc = array();
        $imagelocerror = array();
        foreach ($locales as $locale)
        {
            $imageloc[$locale['shortName']]['title'] = '';
            $imageloc[$locale['shortName']]['description'] = '';
            $imageloc[$locale['shortName']]['metadescr'] = '';
            $imageloc[$locale['shortName']]['metakey'] = '';
            $imagelocerror[$locale['shortName']]['title'] = '';
            $imagelocerror[$locale['shortName']]['description'] = '';
            $imagelocerror[$locale['shortName']]['metadescr'] = '';
            $imagelocerror[$locale['shortName']]['metakey'] = '';
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postimage = $this->get('request_stack')->getMasterRequest()->get('image');
            if (isset($postimage['title'])) $image['title'] = $postimage['title'];
            if (isset($postimage['description'])) $image['description'] = $postimage['description'];
            if (isset($postimage['isVideo'])) $image['isVideo'] = intval($postimage['isVideo']); else $image['isVideo'] = 0;
            if (isset($postimage['contentFoto'])) $image['contentFoto'] = $postimage['contentFoto'];
            if (isset($postimage['contentVideo'])) $image['contentVideo'] = $postimage['contentVideo'];
            if (isset($postimage['enabled'])) $image['enabled'] = intval($postimage['enabled']); else $text['enabled'] = 0;
            if (isset($postimage['metadescr'])) $image['metadescr'] = $postimage['metadescr'];
            if (isset($postimage['metakey'])) $image['metakey'] = $postimage['metakey'];
            unset($postimage);
            if (!preg_match("/^.{3,}$/ui", $image['title'])) {$errors = true; $imageerror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $image['metadescr'])) {$errors = true; $imageerror['metadescr'] = 'Использованы недопустимые символы';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $image['metakey'])) {$errors = true; $imageerror['metakey'] = 'Использованы недопустимые символы';}
            if ($image['isVideo'] == 0)
            {
                if (($image['contentFoto'] == '') || (!file_exists('.'.$image['contentFoto']))) {$errors = true; $imageerror['contentFoto'] = 'Файл не найден';}
            } else
            {
                $videoinfo = $this->checkVideoUrl($image['contentVideo']);
                if ($videoinfo['error'] != null) {$errors = true; $imageerror['contentFoto'] = $videoinfo['error'];}
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
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
            $postimageloc = $this->get('request_stack')->getMasterRequest()->get('imageloc');
            foreach ($locales as $locale)
            {
                if (isset($postimageloc[$locale['shortName']]['title'])) $imageloc[$locale['shortName']]['title'] = $postimageloc[$locale['shortName']]['title'];
                if (isset($postimageloc[$locale['shortName']]['description'])) $imageloc[$locale['shortName']]['description'] = $postimageloc[$locale['shortName']]['description'];
                if (isset($postimageloc[$locale['shortName']]['metadescr'])) $imageloc[$locale['shortName']]['metadescr'] = $postimageloc[$locale['shortName']]['metadescr'];
                if (isset($postimageloc[$locale['shortName']]['metakey'])) $imageloc[$locale['shortName']]['metakey'] = $postimageloc[$locale['shortName']]['metakey'];
                if (($imageloc[$locale['shortName']]['title'] != '') && (!preg_match("/^.{3,}$/ui", $imageloc[$locale['shortName']]['title']))) {$errors = true; $imagelocerror[$locale['shortName']]['title'] = 'Заголовок должен содержать более 3 символов';}
                if (($imageloc[$locale['shortName']]['metadescr'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $imageloc[$locale['shortName']]['metadescr']))) {$errors = true; $imagelocerror[$locale['shortName']]['metadescr'] = 'Использованы недопустимые символы';}
                if (($imageloc[$locale['shortName']]['metakey'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $imageloc[$locale['shortName']]['metakey']))) {$errors = true; $imagelocerror[$locale['shortName']]['metakey'] = 'Использованы недопустимые символы';}
            }
            unset($postimageloc);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            if ($this->container->has('object.taxonomy')) 
            {
                $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->get('request_stack')->getMasterRequest(), 'create', 'object.image', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }
            foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'galleryImagesCreate', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                if ($image['isVideo'] == 0) $this->container->get('cms.cmsManager')->unlockTemporaryFile($image['contentFoto']);
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($image['description'], '');
                $query = $em->createQuery('SELECT max(i.ordering) as maxorder FROM GalleryGalleryBundle:Images i');
                $order = $query->getResult();
                if (isset($order[0]['maxorder'])) $order = $order[0]['maxorder'] + 1; else $order = 1;
                
                $imageent = new \Gallery\GalleryBundle\Entity\Images();
                if ($image['isVideo'] == 0)
                {
                    $imageent->setAvatar($image['contentFoto']);
                    $imageent->setContent($image['contentFoto']);
                    $imageent->setIsVideo(0);
                } else 
                {
                    $imageent->setAvatar((isset($videoinfo['image']) ? $videoinfo['image'] : ''));
                    $imageent->setContent((isset($videoinfo['video']) ? $videoinfo['video'] : $image['contentVideo']));
                    $imageent->setIsVideo(1);
                }
                $imageent->setCreateDate(new \DateTime('now'));
                $imageent->setCreaterId($this->getUser()->getId());
                $imageent->setDescription($image['description']);
                $imageent->setEnabled($image['enabled']);
                $imageent->setMetaDescription($image['metadescr']);
                $imageent->setMetaKeywords($image['metakey']);
                $imageent->setModifyDate(new \DateTime('now'));
                $imageent->setOrdering($order);
                $imageent->setTitle($image['title']);
                $em->persist($imageent);
                $em->flush();
                if ($page['enable'] != 0)
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $pageent->setBreadCrumbs('');
                    $pageent->setBreadCrumbsRel('');
                    $pageent->setDescription('Просмотр изображения галереи '.$imageent->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.image');
                    $pageent->setContentId($imageent->getId());
                    $pageent->setContentAction('view');
                    $imageent->setTemplate($page['template']);
                    if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                    $em->persist($pageent);
                    $em->flush();
                    if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                }
                foreach ($locales as $locale)
                {
                    if (($imageloc[$locale['shortName']]['title'] != '') ||
                        ($imageloc[$locale['shortName']]['description'] != '') ||
                        ($imageloc[$locale['shortName']]['metadescr'] != '') ||
                        ($imageloc[$locale['shortName']]['metakey'] != ''))
                    {
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($imageloc[$locale['shortName']]['description'], '');
                        $imagelocent = new \Gallery\GalleryBundle\Entity\ImagesLocale();
                        $imagelocent->setImageId($imageent->getId());
                        $imagelocent->setLocale($locale['shortName']);
                        if ($imageloc[$locale['shortName']]['title'] == '') $imagelocent->setTitle(null); else $imagelocent->setTitle($imageloc[$locale['shortName']]['title']);
                        if ($imageloc[$locale['shortName']]['description'] == '') $imagelocent->setDescription(null); else $imagelocent->setDescription($imageloc[$locale['shortName']]['description']);
                        if ($imageloc[$locale['shortName']]['metadescr'] == '') $imagelocent->setMetaDescription(null); else $imagelocent->setMetaDescription($imageloc[$locale['shortName']]['metadescr']);
                        if ($imageloc[$locale['shortName']]['metakey'] == '') $imagelocent->setMetaKeywords(null); else $imagelocent->setMetaKeywords($imageloc[$locale['shortName']]['metakey']);
                        $em->persist($imagelocent);
                        $em->flush();
                        unset($imagelocent);
                    }
                }
                $i = 0;
                if ($this->container->has('object.taxonomy')) 
                {
                    $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->get('request_stack')->getMasterRequest(), 'create', 'object.image', $imageent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }
                foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'galleryImagesCreate', $imageent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание нового изображения',
                    'message'=>'Новое изображение успешно создано',
                    'paths'=>array('Создать еще одно изображение'=>$this->get('router')->generate('gallery_gallery_images_create'),'Вернуться к списку изображений'=>$this->get('router')->generate('gallery_gallery_images_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        if ($this->container->has('object.taxonomy')) $tabs[] =  array('name'=>'Категории классификации','content'=>$this->container->get('object.taxonomy')->getTaxonomyController($this->get('request_stack')->getMasterRequest(), 'create', 'object.image', 0, 'tab'));
        foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'galleryImagesCreate', 0, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('GalleryGalleryBundle:Default:imagesCreate.html.twig', array(
            'image' => $image,
            'imageerror' => $imageerror,
            'page' => $page,
            'pageerror' => $pageerror,
            'imageloc' => $imageloc,
            'imagelocerror' => $imagelocerror,
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
// Редактирование изображения
// *******************************************    
    public function galleryImagesEditAction()
    {
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $imageent = $this->getDoctrine()->getRepository('GalleryGalleryBundle:Images')->find($id);
        if (empty($imageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование изображения',
                'message'=>'Изображение не найдено', 
                'error'=>true,
                'paths'=>array('Вернуться к списку изображений'=>$this->get('router')->generate('gallery_gallery_images_list'))
            ));
        }
        if (($this->getUser()->checkAccess('image_viewall') == 0) && ($this->getUser()->getId() != $imageent->getCreaterId()))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование изображения',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        if (($this->getUser()->checkAccess('image_viewown') == 0) && ($this->getUser()->getId() == $imageent->getCreaterId()))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование изображения',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.image','contentId'=>$id,'contentAction'=>'view'));
        $em = $this->getDoctrine()->getEntityManager();
        
        $image = array();
        $imageerror = array();
        
        $image['title'] = $imageent->getTitle();
        $image['description'] = $imageent->getDescription();
        $image['isVideo'] = $imageent->getIsVideo();
        $image['contentFoto'] = ($imageent->getIsVideo() == 0 ? $imageent->getContent() : '');
        $image['contentVideo'] = ($imageent->getIsVideo() != 0 ? $imageent->getContent() : '');
        $image['enabled'] = $imageent->getEnabled();
        $image['metadescr'] = $imageent->getMetaDescription();
        $image['metakey'] = $imageent->getMetaKeywords();
        $imageerror['title'] = '';
        $imageerror['description'] = '';
        $imageerror['isVideo'] = '';
        $imageerror['contentFoto'] = '';
        $imageerror['contentVideo'] = '';
        $imageerror['enabled'] = '';
        $imageerror['metadescr'] = '';
        $imageerror['metakey'] = '';
        
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
            $page['template'] = $imageent->getTemplate();
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
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.image','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (empty($pageent))
        {
            if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
            if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        }
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.image.view');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $imageloc = array();
        $imagelocerror = array();
        foreach ($locales as $locale)
        {
            $imagelocent = $this->getDoctrine()->getRepository('GalleryGalleryBundle:ImagesLocale')->findOneBy(array('locale'=>$locale['shortName'],'imageId'=>$id));
            if (!empty($imagelocent))
            {
                $imageloc[$locale['shortName']]['title'] = $imagelocent->getTitle();
                $imageloc[$locale['shortName']]['description'] = $imagelocent->getDescription();
                $imageloc[$locale['shortName']]['metadescr'] = $imagelocent->getMetaDescription();
                $imageloc[$locale['shortName']]['metakey'] = $imagelocent->getMetaKeywords();
                unset($imagelocent);
            } else 
            {
                $imageloc[$locale['shortName']]['title'] = '';
                $imageloc[$locale['shortName']]['description'] = '';
                $imageloc[$locale['shortName']]['metadescr'] = '';
                $imageloc[$locale['shortName']]['metakey'] = '';
            }
            $imagelocerror[$locale['shortName']]['title'] = '';
            $imagelocerror[$locale['shortName']]['description'] = '';
            $imagelocerror[$locale['shortName']]['metadescr'] = '';
            $imagelocerror[$locale['shortName']]['metakey'] = '';
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            if (($this->getUser()->checkAccess('image_editall') == 0) && ($this->getUser()->getId() != $imageent->getCreaterId()))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование изображения',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            if (($this->getUser()->checkAccess('image_editown') == 0) && ($this->getUser()->getId() == $imageent->getCreaterId()))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование изображения',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            // Проверка основных данных
            $postimage = $this->get('request_stack')->getMasterRequest()->get('image');
            if (isset($postimage['title'])) $image['title'] = $postimage['title'];
            if (isset($postimage['description'])) $image['description'] = $postimage['description'];
            if (isset($postimage['isVideo'])) $image['isVideo'] = intval($postimage['isVideo']); else $image['isVideo'] = 0;
            if (isset($postimage['contentFoto'])) $image['contentFoto'] = $postimage['contentFoto'];
            if (isset($postimage['contentVideo'])) $image['contentVideo'] = $postimage['contentVideo'];
            if (isset($postimage['enabled'])) $image['enabled'] = intval($postimage['enabled']); else $text['enabled'] = 0;
            if (isset($postimage['metadescr'])) $image['metadescr'] = $postimage['metadescr'];
            if (isset($postimage['metakey'])) $image['metakey'] = $postimage['metakey'];
            unset($postimage);
            if (!preg_match("/^.{3,}$/ui", $image['title'])) {$errors = true; $imageerror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $image['metadescr'])) {$errors = true; $imageerror['metadescr'] = 'Использованы недопустимые символы';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $image['metakey'])) {$errors = true; $imageerror['metakey'] = 'Использованы недопустимые символы';}
            if ($image['isVideo'] == 0)
            {
                if (($image['contentFoto'] == '') || (!file_exists('.'.$image['contentFoto']))) {$errors = true; $imageerror['contentFoto'] = 'Файл не найден';}
            } else
            {
                $videoinfo = $this->checkVideoUrl($image['contentVideo']);
                if ($videoinfo['error'] != null) {$errors = true; $imageerror['contentFoto'] = $videoinfo['error'];}
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
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
            $postimageloc = $this->get('request_stack')->getMasterRequest()->get('imageloc');
            foreach ($locales as $locale)
            {
                if (isset($postimageloc[$locale['shortName']]['title'])) $imageloc[$locale['shortName']]['title'] = $postimageloc[$locale['shortName']]['title'];
                if (isset($postimageloc[$locale['shortName']]['description'])) $imageloc[$locale['shortName']]['description'] = $postimageloc[$locale['shortName']]['description'];
                if (isset($postimageloc[$locale['shortName']]['metadescr'])) $imageloc[$locale['shortName']]['metadescr'] = $postimageloc[$locale['shortName']]['metadescr'];
                if (isset($postimageloc[$locale['shortName']]['metakey'])) $imageloc[$locale['shortName']]['metakey'] = $postimageloc[$locale['shortName']]['metakey'];
                if (($imageloc[$locale['shortName']]['title'] != '') && (!preg_match("/^.{3,}$/ui", $imageloc[$locale['shortName']]['title']))) {$errors = true; $imagelocerror[$locale['shortName']]['title'] = 'Заголовок должен содержать более 3 символов';}
                if (($imageloc[$locale['shortName']]['metadescr'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $imageloc[$locale['shortName']]['metadescr']))) {$errors = true; $imagelocerror[$locale['shortName']]['metadescr'] = 'Использованы недопустимые символы';}
                if (($imageloc[$locale['shortName']]['metakey'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $imageloc[$locale['shortName']]['metakey']))) {$errors = true; $imagelocerror[$locale['shortName']]['metakey'] = 'Использованы недопустимые символы';}
            }
            unset($postimageloc);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            if ($this->container->has('object.taxonomy')) 
            {
                $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->get('request_stack')->getMasterRequest(), 'edit', 'object.image', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }
            foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'galleryImagesEdit', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($image['contentFoto']);
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($image['description'], $imageent->getDescription());
                if ($image['isVideo'] == 0)
                {
                    if (($imageent->getAvatar() != '') && ($imageent->getAvatar() != $image['contentFoto']) && (strpos($imageent->getAvatar(), '/images/') === 0)) @unlink('.'.$imageent->getAvatar());
                    $imageent->setAvatar($image['contentFoto']);
                    $imageent->setContent($image['contentFoto']);
                    $imageent->setIsVideo(0);
                } else 
                {
                    if (($imageent->getAvatar() != '') && (strpos($imageent->getAvatar(), '/images/') === 0)) @unlink('.'.$imageent->getAvatar());
                    $imageent->setAvatar((isset($videoinfo['image']) ? $videoinfo['image'] : ''));
                    $imageent->setContent((isset($videoinfo['video']) ? $videoinfo['video'] : $image['contentVideo']));
                    $imageent->setIsVideo(1);
                }
                $imageent->setDescription($image['description']);
                $imageent->setEnabled($image['enabled']);
                $imageent->setMetaDescription($image['metadescr']);
                $imageent->setMetaKeywords($image['metakey']);
                $imageent->setModifyDate(new \DateTime('now'));
                $imageent->setTitle($image['title']);
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
                    $pageent->setDescription('Просмотр изображения галереи '.$imageent->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.image');
                    $pageent->setContentId($imageent->getId());
                    $pageent->setContentAction('view');
                    $imageent->setTemplate($page['template']);
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
                    $imagelocent = $this->getDoctrine()->getRepository('GalleryGalleryBundle:ImagesLocale')->findOneBy(array('locale'=>$locale['shortName'],'imageId'=>$id));
                    if (($imageloc[$locale['shortName']]['title'] != '') ||
                        ($imageloc[$locale['shortName']]['description'] != '') ||
                        ($imageloc[$locale['shortName']]['metadescr'] != '') ||
                        ($imageloc[$locale['shortName']]['metakey'] != ''))
                    {
                        if (empty($imagelocent))
                        {
                            $imagelocent = new \Gallery\GalleryBundle\Entity\ImagesLocale();
                            $em->persist($imagelocent);
                        }
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($imageloc[$locale['shortName']]['description'], $imagelocent->getDescription());
                        $imagelocent->setImageId($imageent->getId());
                        $imagelocent->setLocale($locale['shortName']);
                        if ($imageloc[$locale['shortName']]['title'] == '') $imagelocent->setTitle(null); else $imagelocent->setTitle($imageloc[$locale['shortName']]['title']);
                        if ($imageloc[$locale['shortName']]['description'] == '') $imagelocent->setDescription(null); else $imagelocent->setDescription($imageloc[$locale['shortName']]['description']);
                        if ($imageloc[$locale['shortName']]['metadescr'] == '') $imagelocent->setMetaDescription(null); else $imagelocent->setMetaDescription($imageloc[$locale['shortName']]['metadescr']);
                        if ($imageloc[$locale['shortName']]['metakey'] == '') $imagelocent->setMetaKeywords(null); else $imagelocent->setMetaKeywords($imageloc[$locale['shortName']]['metakey']);
                        $em->flush();
                    } else
                    {
                        if (!empty($imagelocent))
                        {
                            $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $imagelocent->getDescription());
                            $em->remove($imagelocent);
                            $em->flush();
                        }
                    }
                    unset($imagelocent);
                }
                $i = 0;
                if ($this->container->has('object.taxonomy')) 
                {
                    $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->get('request_stack')->getMasterRequest(), 'edit', 'object.image', $imageent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }
                foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'galleryImagesEdit', $imageent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование изображения',
                    'message'=>'Данные изображения успешно изменены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('gallery_gallery_images_edit').'?id='.$id,'Вернуться к списку изображений'=>$this->get('router')->generate('gallery_gallery_images_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        if ($this->container->has('object.taxonomy')) $tabs[] =  array('name'=>'Категории классификации','content'=>$this->container->get('object.taxonomy')->getTaxonomyController($this->get('request_stack')->getMasterRequest(), 'edit', 'object.image', $id, 'tab'));
        foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'galleryImagesEdit', $id, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('GalleryGalleryBundle:Default:imagesEdit.html.twig', array(
            'id' => $id,
            'createrId' => $imageent->getCreaterId(),
            'image' => $image,
            'imageerror' => $imageerror,
            'page' => $page,
            'pageerror' => $pageerror,
            'imageloc' => $imageloc,
            'imagelocerror' => $imagelocerror,
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
    
    public function galleryImagesAjaxAction()
    {
        //$tab = intval($this->get('request_stack')->getMasterRequest()->get('tab'));
        $tab = 0;
        if ($this->getUser()->checkAccess('image_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Изображения галереи',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->get('request_stack')->getMasterRequest()->get('action');
        $errors = array();
        $errorsorder = array();
        if ($action == 'delete')
        {
            if ($this->getUser()->checkAccess('image_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Изображения галереи',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $cmsservices = $this->container->getServiceIds();
            $check = $this->get('request_stack')->getMasterRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $imageent = $this->getDoctrine()->getRepository('GalleryGalleryBundle:Images')->find($key);
                    if (!empty($imageent))
                    {
                        if (strpos($imageent->getAvatar(), '/images/') === 0) @unlink('.'.$imageent->getAvatar());
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $imageent->getDescription());
                        $em->remove($imageent);
                        $em->flush();
                        $query = $em->createQuery('SELECT il.description FROM GalleryGalleryBundle:ImagesLocale il WHERE il.imageId = :id')->setParameter('id', $key);
                        $delpages = $query->getResult();
                        if (is_array($delpages))
                        {
                            foreach ($delpages as $delpage)
                            {
                                if ($delpage['description'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $delpage['description']);
                            }
                        }
                        $query = $em->createQuery('DELETE FROM GalleryGalleryBundle:ImagesLocale il WHERE il.imageId = :id')->setParameter('id', $key);
                        $query->execute();
                        $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.image\' AND p.contentAction = \'view\' AND p.contentId = :id')->setParameter('id', $key);
                        $query->execute();
                        if ($this->container->has('object.taxonomy')) $this->container->get('object.taxonomy')->getTaxonomyController($this->get('request_stack')->getMasterRequest(), 'delete', 'object.image', $key, 'save');
                        foreach ($cmsservices as $item) if (strpos($item,'addone.image.') === 0) 
                        {
                            $serv = $this->container->get($item);
                            $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'galleryImagesDelete', $key, 'save');
                        }       
                        unset($imageent);    
                    }
                }
        }
        if ($action == 'blocked')
        {
            if ($this->getUser()->checkAccess('image_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Изображения галереи',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $check = $this->get('request_stack')->getMasterRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $imageent = $this->getDoctrine()->getRepository('GalleryGalleryBundle:Images')->find($key);
                    if (!empty($imageent))
                    {
                        $imageent->setEnabled(0);
                        $em->flush();
                        unset($imageent);    
                    }
                }
        }
        if ($action == 'unblocked')
        {
            if ($this->getUser()->checkAccess('image_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Изображения галереи',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $check = $this->get('request_stack')->getMasterRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $imageent = $this->getDoctrine()->getRepository('GalleryGalleryBundle:Images')->find($key);
                    if (!empty($imageent))
                    {
                        $imageent->setEnabled(1);
                        $em->flush();
                        unset($imageent);    
                    }
                }
        }
        if ($action == 'ordering')
        {
            if ($this->getUser()->checkAccess('image_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Изображения галереи',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $ordering = $this->get('request_stack')->getMasterRequest()->get('ordering');
            $error = false;
            $ids = array();
            foreach ($ordering as $key=>$val) $ids[] = $key;
            foreach ($ordering as $key=>$val)
            {
                if (!preg_match("/^\d{1,20}$/ui", $val)) {$error = true; $errorsorder[$key] = 'Введено не число';} else
                {
                    foreach ($ordering as $key2=>$val2) if (($key != $key2) && ($val == $val2)) {$error = true; $errorsorder[$key] = 'Введено два одинаковых числа';} else
                    {
                        $query = $em->createQuery('SELECT count(i.id) as checkid FROM GalleryGalleryBundle:Images i '.
                                                  'WHERE i.ordering = :order AND i.id NOT IN (:ids)')->setParameter('ids', $ids)->setParameter('order', $val);
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
                    $imageent = $this->getDoctrine()->getRepository('GalleryGalleryBundle:Images')->find($key);
                    $imageent->setOrdering($val);
                }
                $em->flush();
            }
        }

        //if ($tab == 0)
        //{
        //}
            
        $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('gallery_gallery_images_list_page0');
        $sort0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('gallery_gallery_images_list_sort0');
        $search0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('gallery_gallery_images_list_search0');
        $taxonomy0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('gallery_gallery_images_list_taxonomy0');
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        $taxonomy0 = intval($taxonomy0);
        // Поиск контента для 1 таба (текстовые страницы)
        $querytaxonomyleft0 = '';
        $querytaxonomywhere0 = '';
        if (($this->container->has('object.taxonomy')) && ($taxonomy0 != 0))
        {
            $querytaxonomyleft0 = ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks txl WITH txl.taxonomyId = '.$taxonomy0.' AND txl.itemId = i.id ';
            $querytaxonomywhere0 = ' AND txl.id IS NOT NULL ';
        }
        $query = $em->createQuery('SELECT count(i.id) as imgcount FROM GalleryGalleryBundle:Images i '.$querytaxonomyleft0.
                                  'WHERE i.title like :search '.$querytaxonomywhere0)->setParameter('search', '%'.$search0.'%');
        $imgcount = $query->getResult();
        if (!empty($imgcount)) $imgcount = $imgcount[0]['imgcount']; else $imgcount = 0;
        $pagecount0 = ceil($imgcount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY i.title ASC';
        if ($sort0 == 1) $sortsql = 'ORDER BY i.title DESC';
        if ($sort0 == 2) $sortsql = 'ORDER BY i.enabled ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY i.enabled DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY i.createDate ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY i.createDate DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        if ($sort0 == 8) $sortsql = 'ORDER BY i.ordering ASC';
        if ($sort0 == 9) $sortsql = 'ORDER BY i.ordering DESC';
        if ($sort0 == 10) $sortsql = 'ORDER BY i.isVideo ASC';
        if ($sort0 == 11) $sortsql = 'ORDER BY i.isVideo DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT i.id, i.title, i.createDate, i.modifyDate, u.fullName, i.enabled, i.ordering, i.avatar, i.isVideo FROM GalleryGalleryBundle:Images i '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = i.createrId '.$querytaxonomyleft0.
                                  'WHERE i.title like :search '.$querytaxonomywhere0.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $images0 = $query->getResult();
        if ($this->container->has('object.taxonomy'))
        {
            $taxonomyenabled = 1;
            $actionIds = array();
            foreach ($images0 as $image) $actionIds[] = $image['id'];
            $taxonomyinfo0 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.image', $actionIds);
        } else
        {
            $taxonomyenabled = 0;
            $taxonomyinfo0 = null;
        }
        foreach ($images0 as &$image)
        {
            if (($action == 'ordering') && (isset($ordering[$image['id']]))) $image['ordering'] = $ordering[$image['id']];
        }
        unset($image);
        return $this->render('GalleryGalleryBundle:Default:imagesListTab1.html.twig', array(
            'images0' => $images0,
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
    
}
