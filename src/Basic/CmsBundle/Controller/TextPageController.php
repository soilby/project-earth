<?php

namespace Basic\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Basic\CmsBundle\Entity\Users;
use Basic\CmsBundle\Entity\Roles;

class TextPageController extends Controller
{
// *******************************************
// Список текстовых страниц
// *******************************************    
    public function textPageListAction()
    {
        if (($this->getUser()->checkAccess('textpage_list') == 0) && ($this->getUser()->checkAccess('textpage_listsite') == 0))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Текстовые страницы',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $tab = $this->getRequest()->get('tab');
        if ($tab === null) $tab = $this->getRequest()->getSession()->get('basic_cms_textpage_list_tab');
                      else $this->getRequest()->getSession()->set('basic_cms_textpage_list_tab', $tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 1) $tab = 1;
        if ($this->getUser()->checkAccess('textpage_list') == 0) $tab = 1;
        if ($this->getUser()->checkAccess('textpage_listsite') == 0) $tab = 0;
        // Таб 1
        $page0 = $this->getRequest()->get('page0');
        $sort0 = $this->getRequest()->get('sort0');
        $search0 = $this->getRequest()->get('search0');
        $taxonomy0 = $this->getRequest()->get('taxonomy0');
        if ($page0 === null) $page0 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_page0');
                        else $this->getRequest()->getSession()->set('basic_cms_textpage_list_page0', $page0);
        if ($sort0 === null) $sort0 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_sort0');
                        else $this->getRequest()->getSession()->set('basic_cms_textpage_list_sort0', $sort0);
        if ($search0 === null) $search0 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_search0');
                          else $this->getRequest()->getSession()->set('basic_cms_textpage_list_search0', $search0);
        if ($taxonomy0 === null) $taxonomy0 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_taxonomy0');
                          else $this->getRequest()->getSession()->set('basic_cms_textpage_list_taxonomy0', $taxonomy0);
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        $taxonomy0 = intval($taxonomy0);
        // Поиск контента для 1 таба (текстовые страницы)
        $querytaxonomyleft0 = '';
        $querytaxonomywhere0 = '';
        if (($this->container->has('object.taxonomy')) && ($taxonomy0 != 0))
        {
            $querytaxonomyleft0 = ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks txl WITH txl.taxonomyId = '.$taxonomy0.' AND txl.itemId = t.id ';
            $querytaxonomywhere0 = ' AND txl.id IS NOT NULL ';
        }
        $query = $em->createQuery('SELECT count(t.id) as textcount FROM BasicCmsBundle:TextPages t '.$querytaxonomyleft0.
                                  'WHERE t.pageType = 0 AND t.title like :search '.$querytaxonomywhere0)->setParameter('search', '%'.$search0.'%');
        $textcount = $query->getResult();
        if (!empty($textcount)) $textcount = $textcount[0]['textcount']; else $textcount = 0;
        $pagecount0 = ceil($textcount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY t.title ASC';
        if ($sort0 == 1) $sortsql = 'ORDER BY t.title DESC';
        if ($sort0 == 2) $sortsql = 'ORDER BY t.enabled ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY t.enabled DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY t.createDate ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY t.createDate DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        if ($sort0 == 8) $sortsql = 'ORDER BY t.ordering ASC';
        if ($sort0 == 9) $sortsql = 'ORDER BY t.ordering DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT t.id, t.title, t.createDate, t.modifyDate, u.fullName, t.enabled, t.ordering FROM BasicCmsBundle:TextPages t '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.$querytaxonomyleft0.
                                  'WHERE t.pageType = 0 AND t.title like :search '.$querytaxonomywhere0.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $textpages0 = $query->getResult();
        if ($this->container->has('object.taxonomy'))
        {
            $taxonomyenabled = 1;
            $actionIds = array();
            foreach ($textpages0 as $textpage) $actionIds[] = $textpage['id'];
            $taxonomyinfo0 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.textpage', $actionIds);
        } else
        {
            $taxonomyenabled = 0;
            $taxonomyinfo0 = null;
        }
        // Таб 2
        $page1 = $this->getRequest()->get('page1');
        $sort1 = $this->getRequest()->get('sort1');
        $search1 = $this->getRequest()->get('search1');
        $taxonomy1 = $this->getRequest()->get('taxonomy1');
        if ($page1 === null) $page1 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_page1');
                        else $this->getRequest()->getSession()->set('basic_cms_textpage_list_page1', $page1);
        if ($sort1 === null) $sort1 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_sort1');
                        else $this->getRequest()->getSession()->set('basic_cms_textpage_list_sort1', $sort1);
        if ($search1 === null) $search1 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_search1');
                          else $this->getRequest()->getSession()->set('basic_cms_textpage_list_search1', $search1);
        if ($taxonomy1 === null) $taxonomy1 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_taxonomy1');
                          else $this->getRequest()->getSession()->set('basic_cms_textpage_list_taxonomy1', $taxonomy1);
        $page1 = intval($page1);
        $sort1 = intval($sort1);
        $search1 = trim($search1);
        $taxonomy1 = intval($taxonomy1);
        // Поиск контента для 2 таба (текстовые страницы)
        $querytaxonomyleft1 = '';
        $querytaxonomywhere1 = '';
        if (($this->container->has('object.taxonomy')) && ($taxonomy1 != 0))
        {
            $querytaxonomyleft1 = ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks txl WITH txl.taxonomyId = '.$taxonomy1.' AND txl.itemId = t.id ';
            $querytaxonomywhere1 = ' AND txl.id IS NOT NULL ';
        }
        $query = $em->createQuery('SELECT count(t.id) as textcount FROM BasicCmsBundle:TextPages t '.$querytaxonomyleft1.
                                  'WHERE t.pageType != 0 AND t.title like :search '.$querytaxonomywhere1)->setParameter('search', '%'.$search1.'%');
        $textcount = $query->getResult();
        if (!empty($textcount)) $textcount = $textcount[0]['textcount']; else $textcount = 0;
        $pagecount1 = ceil($textcount / 20);
        if ($pagecount1 < 1) $pagecount1 = 1;
        if ($page1 < 0) $page1 = 0;
        if ($page1 >= $pagecount1) $page1 = $pagecount1 - 1;
        $sortsql = 'ORDER BY t.title ASC';
        if ($sort1 == 1) $sortsql = 'ORDER BY t.title DESC';
        if ($sort1 == 2) $sortsql = 'ORDER BY t.enabled ASC';
        if ($sort1 == 3) $sortsql = 'ORDER BY t.enabled DESC';
        if ($sort1 == 4) $sortsql = 'ORDER BY t.createDate ASC';
        if ($sort1 == 5) $sortsql = 'ORDER BY t.createDate DESC';
        if ($sort1 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort1 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        if ($sort1 == 8) $sortsql = 'ORDER BY t.ordering ASC';
        if ($sort1 == 9) $sortsql = 'ORDER BY t.ordering DESC';
        $start = $page1 * 20;
        $query = $em->createQuery('SELECT t.id, t.title, t.createDate, t.modifyDate, u.fullName, t.enabled, t.ordering FROM BasicCmsBundle:TextPages t '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.$querytaxonomyleft1.
                                  'WHERE t.pageType != 0 AND t.title like :search '.$querytaxonomywhere1.$sortsql)->setParameter('search', '%'.$search1.'%')->setFirstResult($start)->setMaxResults(20);
        $textpages1 = $query->getResult();
        if ($this->container->has('object.taxonomy'))
        {
            $taxonomyenabled = 1;
            $actionIds = array();
            foreach ($textpages1 as $textpage) $actionIds[] = $textpage['id'];
            $taxonomyinfo1 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.textpage', $actionIds);
        } else
        {
            $taxonomyenabled = 0;
            $taxonomyinfo1 = null;
        }
        return $this->render('BasicCmsBundle:TextPages:textList.html.twig', array(
            'textpages0' => $textpages0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'pagecount0' => $pagecount0,
            'taxonomy0' => $taxonomy0,
            'taxonomyenabled' => $taxonomyenabled,
            'taxonomyinfo0' => $taxonomyinfo0,
            'textpages1' => $textpages1,
            'search1' => $search1,
            'page1' => $page1,
            'sort1' => $sort1,
            'pagecount1' => $pagecount1,
            'taxonomy1' => $taxonomy1,
            'taxonomyinfo1' => $taxonomyinfo1,
            'tab' => $tab,
        ));
        
    }
    
    
    
// *******************************************
// AJAX загрузка аватара
// *******************************************    

    public function textPageAjaxAvatarAction() 
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
            $basepath = '/images/textpage/';
            $name = $this->getUser()->getId().'_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
            if (move_uploaded_file($tmpfile, '..'.$basepath.$name)) 
            {
                $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $file->getClientOriginalName());
                return new Response(json_encode(array('file' => $basepath.$name, 'error' => '')));
            }
        }
        return new Response(json_encode(array('file' => '', 'error' => 'Неправильный формат файла')));
    }
    
    
// *******************************************
// Создание новой текстовой странички
// *******************************************    
    public function textPageCreateAction()
    {
        $pagetype = intval($this->getRequest()->get('pagetype'));
        $this->getRequest()->getSession()->set('basic_cms_textpage_list_tab', ($pagetype != 0 ? 1 : 0));
        if ((($this->getUser()->checkAccess('textpage_new') == 0) && ($pagetype == 0)) || (($this->getUser()->checkAccess('textpage_newsite') == 0) && ($pagetype != 0)))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой текстовой страницы',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $text = array();
        $texterror = array();
        
        $text['title'] = '';
        $text['description'] = '';
        $text['avatar'] = '';
        $text['content'] = '';
        $text['enabled'] = 1;
        $text['metadescr'] = '';
        $text['metakey'] = '';
        $texterror['title'] = '';
        $texterror['description'] = '';
        $texterror['avatar'] = '';
        $texterror['content'] = '';
        $texterror['enabled'] = '';
        $texterror['metadescr'] = '';
        $texterror['metakey'] = '';
        
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
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.textpage','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.textpage.view');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        $textloc = array();
        $textlocerror = array();
        foreach ($locales as $locale)
        {
            $textloc[$locale['shortName']]['title'] = '';
            $textloc[$locale['shortName']]['description'] = '';
            $textloc[$locale['shortName']]['content'] = '';
            $textloc[$locale['shortName']]['metadescr'] = '';
            $textloc[$locale['shortName']]['metakey'] = '';
            $textlocerror[$locale['shortName']]['title'] = '';
            $textlocerror[$locale['shortName']]['description'] = '';
            $textlocerror[$locale['shortName']]['content'] = '';
            $textlocerror[$locale['shortName']]['metadescr'] = '';
            $textlocerror[$locale['shortName']]['metakey'] = '';
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $posttext = $this->getRequest()->get('text');
            if (isset($posttext['title'])) $text['title'] = $posttext['title'];
            if (isset($posttext['description'])) $text['description'] = $posttext['description'];
            if (isset($posttext['avatar'])) $text['avatar'] = $posttext['avatar'];
            if (isset($posttext['content'])) $text['content'] = $posttext['content'];
            if (isset($posttext['enabled'])) $text['enabled'] = intval($posttext['enabled']); else $text['enabled'] = 0;
            if (isset($posttext['metadescr'])) $text['metadescr'] = $posttext['metadescr'];
            if (isset($posttext['metakey'])) $text['metakey'] = $posttext['metakey'];
            unset($posttext);
            if (!preg_match("/^.{3,}$/ui", $text['title'])) {$errors = true; $texterror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (($text['avatar'] != '') && (!file_exists('..'.$text['avatar']))) {$errors = true; $texterror['avatar'] = 'Файл не найден';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $text['metadescr'])) {$errors = true; $texterror['metadescr'] = 'Использованы недопустимые символы';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $text['metakey'])) {$errors = true; $texterror['metakey'] = 'Использованы недопустимые символы';}
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
                /*if (!preg_match("/^[a-z0-9\-]{1,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                if ($pageerror['url'] == '')
                {
                    $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url')->setParameter('url', $page['url']);
                    $checkurl = $query->getResult();
                    if ($checkurl[0]['urlcount'] != 0) $pageerror['url'] = 'Страница с таким адресом уже существует';
                }*/
                $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $page['locale']) $localecheck = true;}
                if ($localecheck == false) $page['locale'] = '';
                if (($page['layout'] != '') && (!isset($layouts[$page['layout']]))) {$errors = true; $pageerror['layout'] = 'Шаблон не найден';}
                if (($page['template'] != '') && (!isset($templates[$page['template']]))) {$errors = true; $pageerror['template'] = 'Шаблон не найден';}
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Валидация локлизации
            $posttextloc = $this->getRequest()->get('textloc');
            foreach ($locales as $locale)
            {
                if (isset($posttextloc[$locale['shortName']]['title'])) $textloc[$locale['shortName']]['title'] = $posttextloc[$locale['shortName']]['title'];
                if (isset($posttextloc[$locale['shortName']]['description'])) $textloc[$locale['shortName']]['description'] = $posttextloc[$locale['shortName']]['description'];
                if (isset($posttextloc[$locale['shortName']]['content'])) $textloc[$locale['shortName']]['content'] = $posttextloc[$locale['shortName']]['content'];
                if (isset($posttextloc[$locale['shortName']]['metadescr'])) $textloc[$locale['shortName']]['metadescr'] = $posttextloc[$locale['shortName']]['metadescr'];
                if (isset($posttextloc[$locale['shortName']]['metakey'])) $textloc[$locale['shortName']]['metakey'] = $posttextloc[$locale['shortName']]['metakey'];
                if (($textloc[$locale['shortName']]['title'] != '') && (!preg_match("/^.{3,}$/ui", $textloc[$locale['shortName']]['title']))) {$errors = true; $textlocerror[$locale['shortName']]['title'] = 'Заголовок должен содержать более 3 символов';}
                if (($textloc[$locale['shortName']]['metadescr'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $textloc[$locale['shortName']]['metadescr']))) {$errors = true; $textlocerror[$locale['shortName']]['metadescr'] = 'Использованы недопустимые символы';}
                if (($textloc[$locale['shortName']]['metakey'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $textloc[$locale['shortName']]['metakey']))) {$errors = true; $textlocerror[$locale['shortName']]['metakey'] = 'Использованы недопустимые символы';}
            }
            unset($posttextloc);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            if ($this->container->has('object.taxonomy')) 
            {
                $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'create', 'object.textpage', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }
            foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'textPageCreate', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($text['avatar']);
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($text['content'], '');
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($text['description'], '');
                $query = $em->createQuery('SELECT max(t.ordering) as maxorder FROM BasicCmsBundle:TextPages t');
                $order = $query->getResult();
                if (isset($order[0]['maxorder'])) $order = $order[0]['maxorder'] + 1; else $order = 1;
                $textpageent = new \Basic\CmsBundle\Entity\TextPages();
                $textpageent->setAvatar($text['avatar']);
                $textpageent->setContent($text['content']);
                $textpageent->setCreateDate(new \DateTime('now'));
                $textpageent->setCreaterId($this->getUser()->getId());
                $textpageent->setDescription($text['description']);
                $textpageent->setEnabled($text['enabled']);
                $textpageent->setMetaDescription($text['metadescr']);
                $textpageent->setMetaKeywords($text['metakey']);
                $textpageent->setModifyDate(new \DateTime('now'));
                $textpageent->setOrdering($order);
                $textpageent->setPageType($pagetype);
                $textpageent->setTitle($text['title']);
                $em->persist($textpageent);
                $em->flush();
                if ($page['enable'] != 0)
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $pageent->setBreadCrumbs('');
                    $pageent->setBreadCrumbsRel('');
                    $pageent->setDescription('Просмотр текстовой страницы '.$textpageent->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.textpage');
                    $pageent->setContentId($textpageent->getId());
                    $pageent->setContentAction('view');
                    $textpageent->setTemplate($page['template']);
                    if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                    $em->persist($pageent);
                    $em->flush();
                    if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                }
                foreach ($locales as $locale)
                {
                    if (($textloc[$locale['shortName']]['title'] != '') ||
                        ($textloc[$locale['shortName']]['description'] != '') ||
                        ($textloc[$locale['shortName']]['content'] != '') ||
                        ($textloc[$locale['shortName']]['metadescr'] != '') ||
                        ($textloc[$locale['shortName']]['metakey'] != ''))
                    {
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($textloc[$locale['shortName']]['content'], '');
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($textloc[$locale['shortName']]['description'], '');
                        $textlocent = new \Basic\CmsBundle\Entity\TextPagesLocale();
                        $textlocent->setTextPageId($textpageent->getId());
                        $textlocent->setLocale($locale['shortName']);
                        if ($textloc[$locale['shortName']]['title'] == '') $textlocent->setTitle(null); else $textlocent->setTitle($textloc[$locale['shortName']]['title']);
                        if ($textloc[$locale['shortName']]['description'] == '') $textlocent->setDescription(null); else $textlocent->setDescription($textloc[$locale['shortName']]['description']);
                        if ($textloc[$locale['shortName']]['content'] == '') $textlocent->setContent(null); else $textlocent->setContent($textloc[$locale['shortName']]['content']);
                        if ($textloc[$locale['shortName']]['metadescr'] == '') $textlocent->setMetaDescription(null); else $textlocent->setMetaDescription($textloc[$locale['shortName']]['metadescr']);
                        if ($textloc[$locale['shortName']]['metakey'] == '') $textlocent->setMetaKeywords(null); else $textlocent->setMetaKeywords($textloc[$locale['shortName']]['metakey']);
                        $em->persist($textlocent);
                        $em->flush();
                        unset($textlocent);
                    }
                }
                $i = 0;
                if ($this->container->has('object.taxonomy')) 
                {
                    $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'create', 'object.textpage', $textpageent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }
                foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'textPageCreate', $textpageent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой текстовой страницы',
                    'message'=>'Текстовая страница успешно создана',
                    'paths'=>array('Создать еще одну страницу'=>$this->get('router')->generate('basic_cms_textpage_create',array('pagetype'=>$pagetype)),'Вернуться к списку страниц'=>$this->get('router')->generate('basic_cms_textpage_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        if ($this->container->has('object.taxonomy')) $tabs[] =  array('name'=>'Категории классификации','content'=>$this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'create', 'object.textpage', 0, 'tab'));
        foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'textPageCreate', 0, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:TextPages:textCreate.html.twig', array(
            'pagetype' => $pagetype,
            'text' => $text,
            'texterror' => $texterror,
            'page' => $page,
            'pageerror' => $pageerror,
            'textloc' => $textloc,
            'textlocerror' => $textlocerror,
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
// Редактирование странички
// *******************************************    
    public function textPageEditAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $textpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:TextPages')->find($id);
        if (empty($textpageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование текстовой страницы',
                'message'=>'Текстовая страница не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку текстовых страниц'=>$this->get('router')->generate('basic_cms_textpage_list'))
            ));
        }
        if (($textpageent->getPageType() == 0) && ($this->getUser()->checkAccess('textpage_viewall') == 0) && ($this->getUser()->getId() != $textpageent->getCreaterId()))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование текстовой страницы',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        if (($textpageent->getPageType() == 0) && ($this->getUser()->checkAccess('textpage_viewown') == 0) && ($this->getUser()->getId() == $textpageent->getCreaterId()))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование текстовой страницы',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        if (($textpageent->getPageType() != 0) && ($this->getUser()->checkAccess('textpage_viewsite') == 0))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование текстовой страницы',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.textpage','contentId'=>$id,'contentAction'=>'view'));
        $pagetype = $textpageent->getPageType();
        $this->getRequest()->getSession()->set('basic_cms_textpage_list_tab', ($pagetype != 0 ? 1 : 0));
        
        $text = array();
        $texterror = array();
        
        $text['title'] = $textpageent->getTitle();
        $text['description'] = $textpageent->getDescription();
        $text['avatar'] = $textpageent->getAvatar();
        $text['content'] = $textpageent->getContent();
        $text['enabled'] = $textpageent->getEnabled();
        $text['metadescr'] = $textpageent->getMetaDescription();
        $text['metakey'] = $textpageent->getMetaKeywords();
        $texterror['title'] = '';
        $texterror['description'] = '';
        $texterror['avatar'] = '';
        $texterror['content'] = '';
        $texterror['enabled'] = '';
        $texterror['metadescr'] = '';
        $texterror['metakey'] = '';
        
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
            $page['template'] = $textpageent->getTemplate();
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
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.textpage','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.textpage.view');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        $textloc = array();
        $textlocerror = array();
        foreach ($locales as $locale)
        {
            $textlocent = $this->getDoctrine()->getRepository('BasicCmsBundle:TextPagesLocale')->findOneBy(array('locale'=>$locale['shortName'],'textPageId'=>$id));
            if (!empty($textlocent))
            {
                $textloc[$locale['shortName']]['title'] = $textlocent->getTitle();
                $textloc[$locale['shortName']]['description'] = $textlocent->getDescription();
                $textloc[$locale['shortName']]['content'] = $textlocent->getContent();
                $textloc[$locale['shortName']]['metadescr'] = $textlocent->getMetaDescription();
                $textloc[$locale['shortName']]['metakey'] = $textlocent->getMetaKeywords();
                unset($textlocent);
            } else 
            {
                $textloc[$locale['shortName']]['title'] = '';
                $textloc[$locale['shortName']]['description'] = '';
                $textloc[$locale['shortName']]['content'] = '';
                $textloc[$locale['shortName']]['metadescr'] = '';
                $textloc[$locale['shortName']]['metakey'] = '';
            }
            $textlocerror[$locale['shortName']]['title'] = '';
            $textlocerror[$locale['shortName']]['description'] = '';
            $textlocerror[$locale['shortName']]['content'] = '';
            $textlocerror[$locale['shortName']]['metadescr'] = '';
            $textlocerror[$locale['shortName']]['metakey'] = '';
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            if (($textpageent->getPageType() == 0) && ($this->getUser()->checkAccess('textpage_editall') == 0) && ($this->getUser()->getId() != $textpageent->getCreaterId()))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование текстовой страницы',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            if (($textpageent->getPageType() == 0) && ($this->getUser()->checkAccess('textpage_editown') == 0) && ($this->getUser()->getId() == $textpageent->getCreaterId()))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование текстовой страницы',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            if (($textpageent->getPageType() != 0) && ($this->getUser()->checkAccess('textpage_editsite') == 0))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование текстовой страницы',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            // Проверка основных данных
            $posttext = $this->getRequest()->get('text');
            if (isset($posttext['title'])) $text['title'] = $posttext['title'];
            if (isset($posttext['description'])) $text['description'] = $posttext['description'];
            if (isset($posttext['avatar'])) $text['avatar'] = $posttext['avatar'];
            if (isset($posttext['content'])) $text['content'] = $posttext['content'];
            if (isset($posttext['enabled'])) $text['enabled'] = intval($posttext['enabled']); else $text['enabled'] = 0;
            if (isset($posttext['metadescr'])) $text['metadescr'] = $posttext['metadescr'];
            if (isset($posttext['metakey'])) $text['metakey'] = $posttext['metakey'];
            unset($posttext);
            if (!preg_match("/^.{3,}$/ui", $text['title'])) {$errors = true; $texterror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (($text['avatar'] != '') && (!file_exists('..'.$text['avatar']))) {$errors = true; $texterror['avatar'] = 'Файл не найден';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $text['metadescr'])) {$errors = true; $texterror['metadescr'] = 'Использованы недопустимые символы';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $text['metakey'])) {$errors = true; $texterror['metakey'] = 'Использованы недопустимые символы';}
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
                /*if (!preg_match("/^[a-z0-9\-]{1,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                if ($pageerror['url'] == '')
                {
                    $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url')->setParameter('url', $page['url']);
                    $checkurl = $query->getResult();
                    if ($checkurl[0]['urlcount'] != 0) $pageerror['url'] = 'Страница с таким адресом уже существует';
                }*/
                $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $page['locale']) $localecheck = true;}
                if ($localecheck == false) $page['locale'] = '';
                if (($page['layout'] != '') && (!isset($layouts[$page['layout']]))) {$errors = true; $pageerror['layout'] = 'Шаблон не найден';}
                if (($page['template'] != '') && (!isset($templates[$page['template']]))) {$errors = true; $pageerror['template'] = 'Шаблон не найден';}
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Валидация локлизации
            $posttextloc = $this->getRequest()->get('textloc');
            foreach ($locales as $locale)
            {
                if (isset($posttextloc[$locale['shortName']]['title'])) $textloc[$locale['shortName']]['title'] = $posttextloc[$locale['shortName']]['title'];
                if (isset($posttextloc[$locale['shortName']]['description'])) $textloc[$locale['shortName']]['description'] = $posttextloc[$locale['shortName']]['description'];
                if (isset($posttextloc[$locale['shortName']]['content'])) $textloc[$locale['shortName']]['content'] = $posttextloc[$locale['shortName']]['content'];
                if (isset($posttextloc[$locale['shortName']]['metadescr'])) $textloc[$locale['shortName']]['metadescr'] = $posttextloc[$locale['shortName']]['metadescr'];
                if (isset($posttextloc[$locale['shortName']]['metakey'])) $textloc[$locale['shortName']]['metakey'] = $posttextloc[$locale['shortName']]['metakey'];
                if (($textloc[$locale['shortName']]['title'] != '') && (!preg_match("/^.{3,}$/ui", $textloc[$locale['shortName']]['title']))) {$errors = true; $textlocerror[$locale['shortName']]['title'] = 'Заголовок должен содержать более 3 символов';}
                if (($textloc[$locale['shortName']]['metadescr'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $textloc[$locale['shortName']]['metadescr']))) {$errors = true; $textlocerror[$locale['shortName']]['metadescr'] = 'Использованы недопустимые символы';}
                if (($textloc[$locale['shortName']]['metakey'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $textloc[$locale['shortName']]['metakey']))) {$errors = true; $textlocerror[$locale['shortName']]['metakey'] = 'Использованы недопустимые символы';}
            }
            unset($posttextloc);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            if ($this->container->has('object.taxonomy')) 
            {
                $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'edit', 'object.textpage', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }
            foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'textPageEdit', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                if (($textpageent->getAvatar() != '') && ($textpageent->getAvatar() != $text['avatar'])) @unlink('..'.$textpageent->getAvatar());
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($text['avatar']);
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($text['content'], $textpageent->getContent());
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($text['description'], $textpageent->getDescription());
                $textpageent->setAvatar($text['avatar']);
                $textpageent->setContent($text['content']);
                $textpageent->setDescription($text['description']);
                $textpageent->setEnabled($text['enabled']);
                $textpageent->setMetaDescription($text['metadescr']);
                $textpageent->setMetaKeywords($text['metakey']);
                $textpageent->setModifyDate(new \DateTime('now'));
                $textpageent->setPageType($pagetype);
                $textpageent->setTitle($text['title']);
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
                    $pageent->setDescription('Просмотр текстовой страницы '.$textpageent->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.textpage');
                    $pageent->setContentId($textpageent->getId());
                    $pageent->setContentAction('view');
                    $textpageent->setTemplate($page['template']);
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
                    $textlocent = $this->getDoctrine()->getRepository('BasicCmsBundle:TextPagesLocale')->findOneBy(array('locale'=>$locale['shortName'],'textPageId'=>$id));
                    if (($textloc[$locale['shortName']]['title'] != '') ||
                        ($textloc[$locale['shortName']]['description'] != '') ||
                        ($textloc[$locale['shortName']]['content'] != '') ||
                        ($textloc[$locale['shortName']]['metadescr'] != '') ||
                        ($textloc[$locale['shortName']]['metakey'] != ''))
                    {
                        if (empty($textlocent))
                        {
                            $textlocent = new \Basic\CmsBundle\Entity\TextPagesLocale();
                            $em->persist($textlocent);
                        }
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($textloc[$locale['shortName']]['content'], $textlocent->getContent());
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($textloc[$locale['shortName']]['description'], $textlocent->getDescription());
                        $textlocent->setTextPageId($textpageent->getId());
                        $textlocent->setLocale($locale['shortName']);
                        if ($textloc[$locale['shortName']]['title'] == '') $textlocent->setTitle(null); else $textlocent->setTitle($textloc[$locale['shortName']]['title']);
                        if ($textloc[$locale['shortName']]['description'] == '') $textlocent->setDescription(null); else $textlocent->setDescription($textloc[$locale['shortName']]['description']);
                        if ($textloc[$locale['shortName']]['content'] == '') $textlocent->setContent(null); else $textlocent->setContent($textloc[$locale['shortName']]['content']);
                        if ($textloc[$locale['shortName']]['metadescr'] == '') $textlocent->setMetaDescription(null); else $textlocent->setMetaDescription($textloc[$locale['shortName']]['metadescr']);
                        if ($textloc[$locale['shortName']]['metakey'] == '') $textlocent->setMetaKeywords(null); else $textlocent->setMetaKeywords($textloc[$locale['shortName']]['metakey']);
                        $em->flush();
                    } else
                    {
                        if (!empty($textlocent))
                        {
                            $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $textlocent->getContent());
                            $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $textlocent->getDescription());
                            $em->remove($textlocent);
                            $em->flush();
                        }
                    }
                    unset($textlocent);
                }
                $i = 0;
                if ($this->container->has('object.taxonomy')) 
                {
                    $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'edit', 'object.textpage', $textpageent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }
                foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'textPageEdit', $textpageent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование текстовой страницы',
                    'message'=>'Текстовая страница успешно изменена',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_textpage_edit').'?id='.$id, 'Вернуться к списку страниц'=>$this->get('router')->generate('basic_cms_textpage_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        if ($this->container->has('object.taxonomy')) $tabs[] =  array('name'=>'Категории классификации','content'=>$this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'edit', 'object.textpage', $id, 'tab'));
        foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'textPageEdit', $id, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:TextPages:textEdit.html.twig', array(
            'id' => $id,
            'createrId' => $textpageent->getCreaterId(),
            'pagetype' => $pagetype,
            'text' => $text,
            'texterror' => $texterror,
            'page' => $page,
            'pageerror' => $pageerror,
            'textloc' => $textloc,
            'textlocerror' => $textlocerror,
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
    
    public function textPageAjaxAction()
    {
        $tab = intval($this->getRequest()->get('tab'));
        
        if ((($tab == 0) && ($this->getUser()->checkAccess('textpage_list') == 0)) || (($tab != 0) && ($this->getUser()->checkAccess('textpage_listsite') == 0)))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Текстовые страницы',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->getRequest()->get('action');
        $errors = array();
        $errorsorder = array();
        if ($action == 'delete')
        {
            if ((($tab == 0) && ($this->getUser()->checkAccess('textpage_editall') == 0)) || (($tab != 0) && ($this->getUser()->checkAccess('textpage_editsite') == 0)))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Текстовые страницы',
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
                    $textpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:TextPages')->find($key);
                    if (!empty($textpageent))
                    {
                        if ($textpageent->getAvatar() != '') @unlink('..'.$textpageent->getAvatar());
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $textpageent->getContent());
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $textpageent->getDescription());
                        $em->remove($textpageent);
                        $em->flush();
                        $query = $em->createQuery('SELECT tl.content, tl.description FROM BasicCmsBundle:TextPagesLocale tl WHERE tl.textPageId = :id')->setParameter('id', $key);
                        $delpages = $query->getResult();
                        if (is_array($delpages))
                        {
                            foreach ($delpages as $delpage)
                            {
                                if ($delpage['description'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $delpage['description']);
                                if ($delpage['content'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $delpage['content']);
                            }
                        }
                        $query = $em->createQuery('DELETE FROM BasicCmsBundle:TextPagesLocale tl WHERE tl.textPageId = :id')->setParameter('id', $key);
                        $query->execute();
                        $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.textpage\' AND p.contentAction = \'view\' AND p.contentId = :id')->setParameter('id', $key);
                        $query->execute();
                        if ($this->container->has('object.taxonomy')) $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'delete', 'object.textpage', $key, 'save');
                        foreach ($cmsservices as $item) if (strpos($item,'addone.textpage.') === 0) 
                        {
                            $serv = $this->container->get($item);
                            $serv->getAdminController($this->getRequest(), 'textPageDelete', $key, 'save');
                        }       
                        unset($textpageent);    
                    }
                }
        }
        if ($action == 'blocked')
        {
            if ((($tab == 0) && ($this->getUser()->checkAccess('textpage_editall') == 0)) || (($tab != 0) && ($this->getUser()->checkAccess('textpage_editsite') == 0)))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Текстовые страницы',
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
                    $textpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:TextPages')->find($key);
                    if (!empty($textpageent))
                    {
                        $textpageent->setEnabled(0);
                        $em->flush();
                        unset($textpageent);    
                    }
                }
        }
        if ($action == 'unblocked')
        {
            if ((($tab == 0) && ($this->getUser()->checkAccess('textpage_editall') == 0)) || (($tab != 0) && ($this->getUser()->checkAccess('textpage_editsite') == 0)))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Текстовые страницы',
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
                    $textpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:TextPages')->find($key);
                    if (!empty($textpageent))
                    {
                        $textpageent->setEnabled(1);
                        $em->flush();
                        unset($textpageent);    
                    }
                }
        }
        if ($action == 'ordering')
        {
            if ((($tab == 0) && ($this->getUser()->checkAccess('textpage_editall') == 0)) || (($tab != 0) && ($this->getUser()->checkAccess('textpage_editsite') == 0)))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Текстовые страницы',
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
                        $query = $em->createQuery('SELECT count(t.id) as checkid FROM BasicCmsBundle:TextPages t '.
                                                  'WHERE t.ordering = :order AND t.id NOT IN (:ids)')->setParameter('ids', $ids)->setParameter('order', $val);
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
                    $textpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:TextPages')->find($key);
                    $textpageent->setOrdering($val);
                }
                $em->flush();
            }
        }

        if ($tab == 0)
        {
            // Таб 1
            $page0 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_page0');
            $sort0 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_sort0');
            $search0 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_search0');
            $taxonomy0 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_taxonomy0');
            $page0 = intval($page0);
            $sort0 = intval($sort0);
            $search0 = trim($search0);
            $taxonomy0 = intval($taxonomy0);
            // Поиск контента для 1 таба (текстовые страницы)
            $querytaxonomyleft0 = '';
            $querytaxonomywhere0 = '';
            if (($this->container->has('object.taxonomy')) && ($taxonomy0 != 0))
            {
                $querytaxonomyleft0 = ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks txl WITH txl.taxonomyId = '.$taxonomy0.' AND txl.itemId = t.id ';
                $querytaxonomywhere0 = ' AND txl.id IS NOT NULL ';
            }
            $query = $em->createQuery('SELECT count(t.id) as textcount FROM BasicCmsBundle:TextPages t '.$querytaxonomyleft0.
                                      'WHERE t.pageType = 0 AND t.title like :search '.$querytaxonomywhere0)->setParameter('search', '%'.$search0.'%');
            $textcount = $query->getResult();
            if (!empty($textcount)) $textcount = $textcount[0]['textcount']; else $textcount = 0;
            $pagecount0 = ceil($textcount / 20);
            if ($pagecount0 < 1) $pagecount0 = 1;
            if ($page0 < 0) $page0 = 0;
            if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
            $sortsql = 'ORDER BY t.title ASC';
            if ($sort0 == 1) $sortsql = 'ORDER BY t.title DESC';
            if ($sort0 == 2) $sortsql = 'ORDER BY t.enabled ASC';
            if ($sort0 == 3) $sortsql = 'ORDER BY t.enabled DESC';
            if ($sort0 == 4) $sortsql = 'ORDER BY t.createDate ASC';
            if ($sort0 == 5) $sortsql = 'ORDER BY t.createDate DESC';
            if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
            if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
            if ($sort0 == 8) $sortsql = 'ORDER BY t.ordering ASC';
            if ($sort0 == 9) $sortsql = 'ORDER BY t.ordering DESC';
            $start = $page0 * 20;
            $query = $em->createQuery('SELECT t.id, t.title, t.createDate, t.modifyDate, u.fullName, t.enabled, t.ordering FROM BasicCmsBundle:TextPages t '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.$querytaxonomyleft0.
                                      'WHERE t.pageType = 0 AND t.title like :search '.$querytaxonomywhere0.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
            $textpages0 = $query->getResult();
            if ($this->container->has('object.taxonomy'))
            {
                $taxonomyenabled = 1;
                $actionIds = array();
                foreach ($textpages0 as $textpage) $actionIds[] = $textpage['id'];
                $taxonomyinfo0 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.textpage', $actionIds);
            } else
            {
                $taxonomyenabled = 0;
                $taxonomyinfo0 = null;
            }
            foreach ($textpages0 as &$textpage)
            {
                if (($action == 'ordering') && (isset($ordering[$textpage['id']]))) $textpage['ordering'] = $ordering[$textpage['id']];
            }
            return $this->render('BasicCmsBundle:TextPages:textListTab1.html.twig', array(
                'textpages0' => $textpages0,
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
            
        } else 
        {
            // Таб 2
            $page1 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_page1');
            $sort1 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_sort1');
            $search1 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_search1');
            $taxonomy1 = $this->getRequest()->getSession()->get('basic_cms_textpage_list_taxonomy1');
            $page1 = intval($page1);
            $sort1 = intval($sort1);
            $search1 = trim($search1);
            $taxonomy1 = intval($taxonomy1);
            // Поиск контента для 2 таба (текстовые страницы)
            $querytaxonomyleft1 = '';
            $querytaxonomywhere1 = '';
            if (($this->container->has('object.taxonomy')) && ($taxonomy1 != 0))
            {
                $querytaxonomyleft1 = ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks txl WITH txl.taxonomyId = '.$taxonomy1.' AND txl.itemId = t.id ';
                $querytaxonomywhere1 = ' AND txl.id IS NOT NULL ';
            }
            $query = $em->createQuery('SELECT count(t.id) as textcount FROM BasicCmsBundle:TextPages t '.$querytaxonomyleft1.
                                      'WHERE t.pageType != 0 AND t.title like :search '.$querytaxonomywhere1)->setParameter('search', '%'.$search1.'%');
            $textcount = $query->getResult();
            if (!empty($textcount)) $textcount = $textcount[0]['textcount']; else $textcount = 0;
            $pagecount1 = ceil($textcount / 20);
            if ($pagecount1 < 1) $pagecount1 = 1;
            if ($page1 < 0) $page1 = 0;
            if ($page1 >= $pagecount1) $page1 = $pagecount1 - 1;
            $sortsql = 'ORDER BY t.title ASC';
            if ($sort1 == 1) $sortsql = 'ORDER BY t.title DESC';
            if ($sort1 == 2) $sortsql = 'ORDER BY t.enabled ASC';
            if ($sort1 == 3) $sortsql = 'ORDER BY t.enabled DESC';
            if ($sort1 == 4) $sortsql = 'ORDER BY t.createDate ASC';
            if ($sort1 == 5) $sortsql = 'ORDER BY t.createDate DESC';
            if ($sort1 == 6) $sortsql = 'ORDER BY u.fullName ASC';
            if ($sort1 == 7) $sortsql = 'ORDER BY u.fullName DESC';
            if ($sort1 == 8) $sortsql = 'ORDER BY t.ordering ASC';
            if ($sort1 == 9) $sortsql = 'ORDER BY t.ordering DESC';
            $start = $page1 * 20;
            $query = $em->createQuery('SELECT t.id, t.title, t.createDate, t.modifyDate, u.fullName, t.enabled, t.ordering FROM BasicCmsBundle:TextPages t '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.$querytaxonomyleft1.
                                      'WHERE t.pageType != 0 AND t.title like :search '.$querytaxonomywhere1.$sortsql)->setParameter('search', '%'.$search1.'%')->setFirstResult($start)->setMaxResults(20);
            $textpages1 = $query->getResult();
            if ($this->container->has('object.taxonomy'))
            {
                $taxonomyenabled = 1;
                $actionIds = array();
                foreach ($textpages1 as $textpage) $actionIds[] = $textpage['id'];
                $taxonomyinfo1 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.textpage', $actionIds);
            } else
            {
                $taxonomyenabled = 0;
                $taxonomyinfo1 = null;
            }
            foreach ($textpages1 as &$textpage)
            {
                if (($action == 'ordering') && (isset($ordering[$textpage['id']]))) $textpage['ordering'] = $ordering[$textpage['id']];
            }
            return $this->render('BasicCmsBundle:TextPages:textListTab2.html.twig', array(
                'textpages1' => $textpages1,
                'search1' => $search1,
                'page1' => $page1,
                'sort1' => $sort1,
                'pagecount1' => $pagecount1,
                'taxonomy1' => $taxonomy1,
                'taxonomyenabled' => $taxonomyenabled,
                'taxonomyinfo1' => $taxonomyinfo1,
                'tab' => $tab,
                'errors1' => $errors,
                'errorsorder1' => $errorsorder
            ));
        }
    }
    
}
