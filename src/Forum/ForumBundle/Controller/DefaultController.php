<?php

namespace Forum\ForumBundle\Controller;

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
// Список тем форумов
// *******************************************    
    public function forumsTopicListAction()
    {
        if (($this->getUser()->checkAccess('forum_list') == 0) && ($this->getUser()->checkAccess('forum_createpage') == 0))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Форум',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $tab = $this->getRequest()->get('tab');
        if ($tab === null) $tab = $this->getRequest()->getSession()->get('forum_forum_topic_list_tab');
                      else $this->getRequest()->getSession()->set('forum_forum_topic_list_tab', $tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 3) $tab = 3;
        if (($this->getUser()->checkAccess('forum_list') == 0) && (($tab == 0) || ($tab == 2))) $tab = 1;
        if (($this->getUser()->checkAccess('forum_createpage') == 0) && ($tab == 1)) $tab = 3;
        if (($this->getUser()->checkAccess('forum_commentsview') == 0) && ($tab == 3)) $tab = 0;
        // Таб 1
        $page0 = $this->getRequest()->get('page0');
        $sort0 = $this->getRequest()->get('sort0');
        $search0 = $this->getRequest()->get('search0');
        $taxonomy0 = $this->getRequest()->get('taxonomy0');
        if ($page0 === null) $page0 = $this->getRequest()->getSession()->get('forum_forum_topic_list_page0');
                        else $this->getRequest()->getSession()->set('forum_forum_topic_list_page0', $page0);
        if ($sort0 === null) $sort0 = $this->getRequest()->getSession()->get('forum_forum_topic_list_sort0');
                        else $this->getRequest()->getSession()->set('forum_forum_topic_list_sort0', $sort0);
        if ($search0 === null) $search0 = $this->getRequest()->getSession()->get('forum_forum_topic_list_search0');
                          else $this->getRequest()->getSession()->set('forum_forum_topic_list_search0', $search0);
        if ($taxonomy0 === null) $taxonomy0 = $this->getRequest()->getSession()->get('forum_forum_topic_list_taxonomy0');
                          else $this->getRequest()->getSession()->set('forum_forum_topic_list_taxonomy0', $taxonomy0);
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
        $query = $em->createQuery('SELECT count(f.id) as forumcount FROM ForumForumBundle:Forums f '.$querytaxonomyleft0.
                                  'WHERE f.title like :search '.$querytaxonomywhere0)->setParameter('search', '%'.$search0.'%');
        $forumcount = $query->getResult();
        if (!empty($forumcount)) $forumcount = $forumcount[0]['forumcount']; else $forumcount = 0;
        $pagecount0 = ceil($forumcount / 20);
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
        if ($sort0 == 8) $sortsql = 'ORDER BY f.lastMessageDate ASC';
        if ($sort0 == 9) $sortsql = 'ORDER BY f.lastMessageDate DESC';
        if ($sort0 == 10) $sortsql = 'ORDER BY messageCount ASC';
        if ($sort0 == 11) $sortsql = 'ORDER BY messageCount DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT f.id, f.title, f.createDate, f.modifyDate, f.lastMessageDate, u.fullName, f.enabled, f.isClosed, (SELECT count(m.id) FROM ForumForumBundle:ForumMessages m WHERE m.forumId = f.id) as messageCount FROM ForumForumBundle:Forums f '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.$querytaxonomyleft0.
                                  'WHERE f.title like :search '.$querytaxonomywhere0.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $forums0 = $query->getResult();
        if ($this->container->has('object.taxonomy'))
        {
            $taxonomyenabled = 1;
            $actionIds = array();
            foreach ($forums0 as $forum) $actionIds[] = $forum['id'];
            $taxonomyinfo0 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.forum', $actionIds);
        } else
        {
            $taxonomyenabled = 0;
            $taxonomyinfo0 = null;
        }
        // Таб 2
        $query = $em->createQuery('SELECT c.id, c.title, c.enabled, sp.url FROM ForumForumBundle:ForumCreatePages c LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'create\' AND sp.contentId = c.id ORDER BY c.id');
        $createpages1 = $query->getResult();
        foreach ($createpages1 as &$createpage) $createpage['title'] = $this->get('cms.cmsManager')->decodeLocalString($createpage['title'],'default',true);
        unset($createpage);
        // Таб 3
        $categories2 = array();
        if ($this->container->has('object.taxonomy'))
        {
            $categories2 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.forum');
            foreach ($categories2 as &$cat)
            {
                $query = $em->createQuery('SELECT u.id, u.login, u.fullName FROM ForumForumBundle:ForumModerators m '.
                                          'INNER JOIN BasicCmsBundle:Users u WITH u.id = m.moderatorId '.
                                          'WHERE m.forumId = 0 AND m.categoryId = :id')->setParameter('id', $cat['id']);
                $cat['moderators'] = $query->getResult();
                if (!is_array($cat['moderators'])) $cat['moderators'] = array();
            }
            unset($cat);
        }
        // Таб 4
        $page3 = $this->getRequest()->get('page3');
        $sort3 = $this->getRequest()->get('sort3');
        $search3 = $this->getRequest()->get('search3');
        if ($page3 === null) $page3 = $this->getRequest()->getSession()->get('forum_forum_topic_list_page3');
                        else $this->getRequest()->getSession()->set('forum_forum_topic_list_page3', $page3);
        if ($sort3 === null) $sort3 = $this->getRequest()->getSession()->get('forum_forum_topic_list_sort3');
                        else $this->getRequest()->getSession()->set('forum_forum_topic_list_sort3', $sort3);
        if ($search3 === null) $search3 = $this->getRequest()->getSession()->get('forum_forum_topic_list_search3');
                          else $this->getRequest()->getSession()->set('forum_forum_topic_list_search3', $search3);
        $page3 = intval($page3);
        $sort3 = intval($sort3);
        $search3 = trim($search3);
        // Поиск контента для 1 таба (текстовые страницы)
        $query = $em->createQuery('SELECT count(c.id) as commentcount FROM ForumForumBundle:ForumComments c '.
                                  'WHERE c.content like :search')->setParameter('search', '%'.$search3.'%');
        $commentcount = $query->getResult();
        if (!empty($commentcount)) $commentcount = $commentcount[0]['commentcount']; else $commentcount = 0;
        $pagecount3 = ceil($commentcount / 20);
        if ($pagecount3 < 1) $pagecount3 = 1;
        if ($page3 < 0) $page3 = 0;
        if ($page3 >= $pagecount3) $page3 = $pagecount3 - 1;
        $sortsql = 'ORDER BY c.createDate ASC, c.id ASC';
        if ($sort3 == 1) $sortsql = 'ORDER BY c.createDate DESC, m.id DESC';
        if ($sort3 == 2) $sortsql = 'ORDER BY c.isVisible ASC';
        if ($sort3 == 3) $sortsql = 'ORDER BY c.isVisible DESC';
        if ($sort3 == 4) $sortsql = 'ORDER BY c.content ASC';
        if ($sort3 == 5) $sortsql = 'ORDER BY c.content DESC';
        if ($sort3 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort3 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        if ($sort3 == 8) $sortsql = 'ORDER BY um.fullName ASC';
        if ($sort3 == 9) $sortsql = 'ORDER BY um.fullName DESC';
        if ($sort3 == 10) $sortsql = 'ORDER BY s.description ASC';
        if ($sort3 == 11) $sortsql = 'ORDER BY s.description DESC';
        $start = $page3 * 20;
        $query = $em->createQuery('SELECT c.id, c.content, c.createDate, c.modifyDate, c.isVisible, u.fullName, um.fullName as moderatorFullName, s.description FROM ForumForumBundle:ForumComments c '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = c.createrId '.
                                  'LEFT JOIN BasicCmsBundle:Users um WITH um.id = c.moderatorId '.
                                  'LEFT JOIN BasicCmsBundle:SeoPage s WITH s.id = c.seoPageId '.
                                  'WHERE c.content like :search '.$sortsql)->setParameter('search', '%'.$search3.'%')->setFirstResult($start)->setMaxResults(20);
        $comments3 = $query->getResult();
        return $this->render('ForumForumBundle:Default:topicList.html.twig', array(
            'forums0' => $forums0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'pagecount0' => $pagecount0,
            'taxonomy0' => $taxonomy0,
            'taxonomyenabled' => $taxonomyenabled,
            'taxonomyinfo0' => $taxonomyinfo0,
            'createpages1' => $createpages1,
            'categories2' => $categories2,
            'comments3' => $comments3,
            'search3' => $search3,
            'page3' => $page3,
            'sort3' => $sort3,
            'pagecount3' => $pagecount3,
            'tab' => $tab
        ));
        
    }
    
    
    
// *******************************************
// AJAX загрузка
// *******************************************    

    public function forumsTopicAjaxAvatarAction() 
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
            $basepath = '/images/forum/';
            $name = $this->getUser()->getId().'_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
            if (move_uploaded_file($tmpfile, '..'.$basepath.$name)) 
            {
                $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $file->getClientOriginalName());
                return new Response(json_encode(array('file' => $basepath.$name, 'error' => '')));
            }
        }
        return new Response(json_encode(array('file' => '', 'error' => 'Неправильный формат файла')));
    }
    
    public function forumsTopicAjaxAttachmentAction() 
    {
        $em = $this->getDoctrine()->getEntityManager();
         $query = $em->createQuery('SELECT a.id, a.contentFile FROM ForumForumBundle:ForumAttachments a WHERE a.forumId = 0 AND a.messageId = 0 AND a.downloadDate < :date')->setParameter('date', new \DateTime('1 days ago'))->setMaxResults(10);
         $files = $query->getResult();
         if (is_array($files))
         {
             $removeids = array();
             foreach ($files as $file)
             {
                 @unlink('..'.$file['contentFile']);
                 $removeids[] = $file['id'];
             }
             if (count($removeids) > 0)
             {
                 $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $removeids);
                 $query->execute();
             }
         }
         unset($files);
        $userId = $this->getUser()->getId();
        $files = $this->getRequest()->files->get('files');
        $answer = array();
        if (is_array($files))
        {
            foreach ($files as $file)
            {
                $tmpfile = $file->getPathName();
                $basepath = '../secured/forum/';
                $name = $this->getUser()->getId().'_'.md5(time()).'.dat';
                if (move_uploaded_file($tmpfile, $basepath . $name)) 
                {
                    $fileent = new \Forum\ForumBundle\Entity\ForumAttachments();
                    $fileent->setContentFile('/secured/forum/'.$name);
                    $fileent->setDownloadDate(new \DateTime('now'));
                    $fileent->setFileName($file->getClientOriginalName());
                    $fileent->setForumId(0);
                    $fileent->setIsImage(0);
                    $fileent->setMessageId(0);
                    if (@getimagesize($basepath.$name)) $fileent->setIsImage(1);
                    $em->persist($fileent);
                    $em->flush();
                    $answer[] = array('id' => $fileent->getId(), 'filename' => $file->getClientOriginalName(), 'filesize' => number_format(filesize($basepath.$name) / 1024, 0, ',', ' '), 'error' => '');
                } else $answer[] = array('id' => '', 'filename' => $file->getClientOriginalName(), 'filesize' => '', 'error' => 'Ошибка загрузки файла');
            }
        }
        return new Response(json_encode($answer));
    }
   
    public function forumsTopicAjaxLoadUsersAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $search = trim($this->getRequest()->get('search'));
        $page = intval($this->getRequest()->get('page'));
        if ($page < 0) $page = 0;
        $query = $em->createQuery('SELECT count(u.id) as usercount FROM BasicCmsBundle:Users u WHERE u.login LIKE :search OR u.fullName LIKE :search')
                    ->setParameter('search', '%'.$search.'%');
        $pagecount = $query->getResult();
        if (isset($pagecount[0]['usercount'])) $pagecount = ceil(intval($pagecount[0]['usercount'])/20); else $pagecount = 0;
        if ($pagecount < 1) $pagecount = 1;
        if ($page >= $pagecount) $page = $pagecount - 1;
        $query = $em->createQuery('SELECT u.id, u.login, u.fullName FROM BasicCmsBundle:Users u WHERE u.login LIKE :search OR u.fullName LIKE :search')
                    ->setParameter('search', '%'.$search.'%')->setFirstResult($page * 20)->setMaxResults(20);
        $users = $query->getResult();
        if (!is_array($users)) $users = array();
        return new Response(json_encode(array('page'=>$page, 'pagecount'=>$pagecount, 'users'=>$users)));
    }    
    
// *******************************************
// Создание нового форума
// *******************************************    
    public function forumsTopicCreateAction()
    {
        $this->getRequest()->getSession()->set('forum_forum_topic_list_tab', 0);
        if ($this->getUser()->checkAccess('forum_new') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание нового форума',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $forum = array();
        $forumerror = array();
        
        $forum['title'] = '';
        $forum['description'] = '';
        $forum['avatar'] = '';
        $forum['content'] = '';
        $forum['attachments'] = array();
        $forum['isClosed'] = '';
        $forum['isImportant'] = '';
        $forum['enabled'] = 1;
        $forum['isVisible'] = '';
        $forum['captchaEnabled'] = '';
        $forum['onlyAutorizedView'] = '';
        $forum['onlyAutorizedImage'] = '';
        $forum['onlyAutorizedDownload'] = '';
        $forum['onlyAutorizedPost'] = '';
        $forum['allowTags'] = '';
        $forum['allowStyleProp'] = '';
        $forum['replaceUrls'] = '';
        $forum['messageInPage'] = '';
        $forum['moderators'] = array();
        $forumerror['title'] = '';
        $forumerror['description'] = '';
        $forumerror['avatar'] = '';
        $forumerror['content'] = '';
        $forumerror['isClosed'] = '';
        $forumerror['isImportant'] = '';
        $forumerror['enabled'] = '';
        $forumerror['isVisible'] = '';
        $forumerror['captchaEnabled'] = '';
        $forumerror['onlyAutorizedView'] = '';
        $forumerror['onlyAutorizedImage'] = '';
        $forumerror['onlyAutorizedDownload'] = '';
        $forumerror['onlyAutorizedPost'] = '';
        $forumerror['allowTags'] = '';
        $forumerror['allowStyleProp'] = '';
        $forumerror['replaceUrls'] = '';
        $forumerror['messageInPage'] = '';
        
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
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.forum','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.forum.view');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postforum = $this->getRequest()->get('forum');
            if (isset($postforum['title'])) $forum['title'] = $postforum['title'];
            if (isset($postforum['description'])) $forum['description'] = $postforum['description'];
            if (isset($postforum['avatar'])) $forum['avatar'] = $postforum['avatar'];
            if (isset($postforum['content'])) $forum['content'] = $postforum['content'];
            if (isset($postforum['isClosed'])) $forum['isClosed'] = intval($postforum['isClosed']); else $forum['isClosed'] = 0;
            if (isset($postforum['isImportant'])) $forum['isImportant'] = intval($postforum['isImportant']); else $forum['isImportant'] = 0;
            if (isset($postforum['enabled'])) $forum['enabled'] = intval($postforum['enabled']); else $forum['enabled'] = 0;
            if (isset($postforum['isVisible'])) $forum['isVisible'] = intval($postforum['isVisible']); else $forum['isVisible'] = 0;
            if (isset($postforum['captchaEnabled'])) $forum['captchaEnabled'] = intval($postforum['captchaEnabled']); else $forum['captchaEnabled'] = 0;
            if (isset($postforum['onlyAutorizedView'])) $forum['onlyAutorizedView'] = intval($postforum['onlyAutorizedView']); else $forum['onlyAutorizedView'] = 0;
            if (isset($postforum['onlyAutorizedImage'])) $forum['onlyAutorizedImage'] = intval($postforum['onlyAutorizedImage']); else $forum['onlyAutorizedImage'] = 0;
            if (isset($postforum['onlyAutorizedDownload'])) $forum['onlyAutorizedDownload'] = intval($postforum['onlyAutorizedDownload']); else $forum['onlyAutorizedDownload'] = 0;
            if (isset($postforum['onlyAutorizedPost'])) $forum['onlyAutorizedPost'] = intval($postforum['onlyAutorizedPost']); else $forum['onlyAutorizedPost'] = 0;
            if (isset($postforum['allowTags'])) $forum['allowTags'] = $postforum['allowTags'];
            if (isset($postforum['allowStyleProp'])) $forum['allowStyleProp'] = intval($postforum['allowStyleProp']); else $forum['allowStyleProp'] = 0;
            if (isset($postforum['replaceUrls'])) $forum['replaceUrls'] = intval($postforum['replaceUrls']); else $forum['replaceUrls'] = 0;
            if (isset($postforum['messageInPage'])) $forum['messageInPage'] = $postforum['messageInPage'];
            unset($postforum);
            if (!preg_match("/^.{3,}$/ui", $forum['title'])) {$errors = true; $forumerror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (($forum['avatar'] != '') && (!file_exists('..'.$forum['avatar']))) {$errors = true; $forumerror['avatar'] = 'Файл не найден';}
            if (!preg_match("/^([A-z0-9]{1,10}(\s*\,\s*[A-z0-9]{1,10})*)?$/ui", $forum['allowTags'])) {$errors = true; $forumerror['allowTags'] = 'Тэги должны состоять из 1-10 латинских букв и разделяться запятыми';}
            if ((!preg_match("/^[\d]{2,3}$/ui", $forum['messageInPage'])) || (intval($forum['messageInPage']) < 10) || (intval($forum['messageInPage']) > 100)) {$errors = true; $forumerror['messageInPage'] = 'Должно быть указано число от 10 до 100';}
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
            // Проверка сообщения (текст и вложения)
            if (!preg_match("/^[\s\S]{10,}$/ui", $forum['content'])) {$errors = true; $forumerror['content'] = 'Текст сообщения должен содержать более 10 символов';}
            $postattachments = $this->getRequest()->get('attachments');
            $forum['attachments'] = array();
            if (is_array($postattachments))
            {
                foreach ($postattachments as $attachmentid)
                {
                    $fileent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumAttachments')->find($attachmentid);
                    if (!empty($fileent))
                    {
                        if (!file_exists('..'.$fileent->getContentFile())) {$errors = true; $forum['attachments'][] = array('filename' => $fileent->getFileName(), 'filesize' => 0, 'id' => $attachmentid, 'error' => 'Файл не найден');}
                        else {$forum['attachments'][] = array('filename' => $fileent->getFileName(), 'filesize' => filesize('..'.$fileent->getContentFile()), 'id' => intval($attachmentid), 'error' => '');}
                    }
                    unset($fileent);
                }
            }
            unset($postattachments);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Проверка модераторов
            $postmoderators = $this->getRequest()->get('moderators');
            $forum['moderators'] = array();
            if (is_array($postmoderators))
            {
                foreach ($postmoderators as $moderatorid) 
                {
                    $moderatorent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($moderatorid);
                    if (!empty($moderatorent))
                    {
                        $forum['moderators'][] = array('login' => $moderatorent->getLogin(), 'fullName' => $moderatorent->getFullName(), 'id' => intval($moderatorid), 'error' => '');
                    }
                    unset($moderatorent);
                }
            }
            unset($postmoderators);
            if (($errors == true) && ($activetab == 0)) $activetab = 4;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            if ($this->container->has('object.taxonomy')) 
            {
                $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'create', 'object.forum', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                $i++;
            }
            foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'forumsTopicCreate', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($forum['avatar']);
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($forum['content'], '');
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($forum['description'], '');
                $forument = new \Forum\ForumBundle\Entity\Forums();
                $forument->setAllowStyleProp($forum['allowStyleProp']);
                $forument->setAllowTags($forum['allowTags']);
                $forument->setAvatar($forum['avatar']);
                $forument->setCaptchaEnabled($forum['captchaEnabled']);
                $forument->setContent($forum['content']);
                $forument->setCreateDate(new \DateTime('now'));
                $forument->setCreaterId($this->getUser()->getId());
                $forument->setDescription($forum['description']);
                $forument->setEnabled($forum['enabled']);
                $forument->setIsClosed($forum['isClosed']);
                $forument->setIsImportant($forum['isImportant']);
                $forument->setIsVisible($forum['isVisible']);
                $forument->setLastMessageDate(new \DateTime('now'));
                $forument->setModeratorId(null);
                $forument->setModeratorMessage(null);
                $forument->setModifyDate(new \DateTime('now'));
                $forument->setOnlyAutorizedDownload($forum['onlyAutorizedDownload']);
                $forument->setOnlyAutorizedImage($forum['onlyAutorizedImage']);
                $forument->setOnlyAutorizedPost($forum['onlyAutorizedPost']);
                $forument->setOnlyAutorizedView($forum['onlyAutorizedView']);
                $forument->setReplaceUrls($forum['replaceUrls']);
                $forument->setTitle($forum['title']);
                $forument->setMessageInPage($forum['messageInPage']);
                $em->persist($forument);
                $em->flush();
                if ($page['enable'] != 0)
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $pageent->setBreadCrumbs('');
                    $pageent->setBreadCrumbsRel('');
                    $pageent->setDescription('Просмотр форума '.$forument->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.forum');
                    $pageent->setContentId($forument->getId());
                    $pageent->setContentAction('view');
                    $forument->setTemplate($page['template']);
                    if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                    $em->persist($pageent);
                    $em->flush();
                    if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                }
                // Сохранение модеров
                foreach ($forum['moderators'] as $moder)
                {
                    $moderatorent = new \Forum\ForumBundle\Entity\ForumModerators();
                    $moderatorent->setCategoryId(0);
                    $moderatorent->setForumId($forument->getId());
                    $moderatorent->setModeratorId($moder['id']);
                    $em->persist($moderatorent);
                    $em->flush();
                    unset($moderatorent);
                }
                // Сохранение вложений
                foreach ($forum['attachments'] as $attach)
                {
                    $fileent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumAttachments')->find($attach['id']);
                    if (!empty($fileent))
                    {
                        $fileent->setForumId($forument->getId());
                        $em->flush();
                    }
                    unset($fileent);
                }
                // Сохранение других табов
                $i = 0;
                if ($this->container->has('object.taxonomy')) 
                {
                    $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'create', 'object.forum', $forument->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                    $i++;
                }
                foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'forumsTopicCreate', $forument->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание нового форума',
                    'message'=>'Форум успешно создано',
                    'paths'=>array('Создать еще один форум'=>$this->get('router')->generate('forum_forum_topic_create'),'Вернуться к списку форумов'=>$this->get('router')->generate('forum_forum_topic_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        if ($this->container->has('object.taxonomy')) $tabs[] =  array('name'=>'Категории классификации','content'=>$this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'create', 'object.forum', 0, 'tab'));
        foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'forumsTopicCreate', 0, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('ForumForumBundle:Default:topicCreate.html.twig', array(
            'forum' => $forum,
            'forumerror' => $forumerror,
            'page' => $page,
            'pageerror' => $pageerror,
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
// Редактирование форума
// *******************************************    
    public function forumsTopicEditAction()
    {
        $this->getRequest()->getSession()->set('forum_forum_topic_list_tab', 0);
        $id = intval($this->getRequest()->get('id'));
        $forument = $this->getDoctrine()->getRepository('ForumForumBundle:Forums')->find($id);
        if (empty($forument))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование форума',
                'message'=>'Форум не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к списку форумов'=>$this->get('router')->generate('forum_forum_topic_list'))
            ));
        }
        if ($this->getUser()->checkAccess('forum_view') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование форума',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.forum','contentId'=>$id,'contentAction'=>'view'));
        $em = $this->getDoctrine()->getEntityManager();
        
        $forum = array();
        $forumerror = array();
        
        $forum['title'] = $forument->getTitle();
        $forum['description'] = $forument->getDescription();
        $forum['avatar'] = $forument->getAvatar();
        $forum['content'] = $forument->getContent();
        $forum['attachments'] = array();
        $forum['isClosed'] = $forument->getIsClosed();
        $forum['isImportant'] = $forument->getIsImportant();
        $forum['enabled'] = $forument->getEnabled();
        $forum['isVisible'] = $forument->getIsVisible();
        $forum['captchaEnabled'] = $forument->getCaptchaEnabled();
        $forum['onlyAutorizedView'] = $forument->getOnlyAutorizedView();
        $forum['onlyAutorizedImage'] = $forument->getOnlyAutorizedImage();
        $forum['onlyAutorizedDownload'] = $forument->getOnlyAutorizedDownload();
        $forum['onlyAutorizedPost'] = $forument->getOnlyAutorizedPost();
        $forum['allowTags'] = $forument->getAllowTags();
        $forum['allowStyleProp'] = $forument->getAllowStyleProp();
        $forum['replaceUrls'] = $forument->getReplaceUrls();
        $forum['messageInPage'] = $forument->getMessageInPage();
        $forum['moderators'] = array();
        $forumerror['title'] = '';
        $forumerror['description'] = '';
        $forumerror['avatar'] = '';
        $forumerror['content'] = '';
        $forumerror['isClosed'] = '';
        $forumerror['isImportant'] = '';
        $forumerror['enabled'] = '';
        $forumerror['isVisible'] = '';
        $forumerror['captchaEnabled'] = '';
        $forumerror['onlyAutorizedView'] = '';
        $forumerror['onlyAutorizedImage'] = '';
        $forumerror['onlyAutorizedDownload'] = '';
        $forumerror['onlyAutorizedPost'] = '';
        $forumerror['allowTags'] = '';
        $forumerror['allowStyleProp'] = '';
        $forumerror['replaceUrls'] = '';
        $forumerror['messageInPage'] = '';
        
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
            $page['template'] = $forument->getTemplate();
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
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.forum','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (empty($pageent))
        {
            if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
            if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        }
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.forum.view');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        // Модераторы и аттачи
        $query = $em->createQuery('SELECT u.id, u.login, u.fullName, \'\' as error FROM ForumForumBundle:ForumModerators m '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = m.moderatorId '.
                                  'WHERE m.forumId = :forumid')->setParameter('forumid', $id);
        $forum['moderators'] = $query->getResult();
        if (!is_array($forum['moderators'])) $forum['moderators'] = array();
        
        $query = $em->createQuery('SELECT f.id, f.fileName as filename, f.contentFile, 0 as filesize, \'\' as error FROM ForumForumBundle:ForumAttachments f '.
                                  'WHERE f.forumId = :forumid AND f.messageId = 0')->setParameter('forumid', $id);
        $forum['attachments'] = $query->getResult();
        if (!is_array($forum['attachments'])) $forum['attachments'] = array();
        foreach ($forum['attachments'] as &$attach)
        {
            $attach['filesize'] = @filesize('..'.$attach['contentFile']);
        }
        unset($attach);
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            if ($this->getUser()->checkAccess('forum_edit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование форума',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            // Проверка основных данных
            $postforum = $this->getRequest()->get('forum');
            if (isset($postforum['title'])) $forum['title'] = $postforum['title'];
            if (isset($postforum['description'])) $forum['description'] = $postforum['description'];
            if (isset($postforum['avatar'])) $forum['avatar'] = $postforum['avatar'];
            if (isset($postforum['content'])) $forum['content'] = $postforum['content'];
            if (isset($postforum['isClosed'])) $forum['isClosed'] = intval($postforum['isClosed']); else $forum['isClosed'] = 0;
            if (isset($postforum['isImportant'])) $forum['isImportant'] = intval($postforum['isImportant']); else $forum['isImportant'] = 0;
            if (isset($postforum['enabled'])) $forum['enabled'] = intval($postforum['enabled']); else $forum['enabled'] = 0;
            if (isset($postforum['isVisible'])) $forum['isVisible'] = intval($postforum['isVisible']); else $forum['isVisible'] = 0;
            if (isset($postforum['captchaEnabled'])) $forum['captchaEnabled'] = intval($postforum['captchaEnabled']); else $forum['captchaEnabled'] = 0;
            if (isset($postforum['onlyAutorizedView'])) $forum['onlyAutorizedView'] = intval($postforum['onlyAutorizedView']); else $forum['onlyAutorizedView'] = 0;
            if (isset($postforum['onlyAutorizedImage'])) $forum['onlyAutorizedImage'] = intval($postforum['onlyAutorizedImage']); else $forum['onlyAutorizedImage'] = 0;
            if (isset($postforum['onlyAutorizedDownload'])) $forum['onlyAutorizedDownload'] = intval($postforum['onlyAutorizedDownload']); else $forum['onlyAutorizedDownload'] = 0;
            if (isset($postforum['onlyAutorizedPost'])) $forum['onlyAutorizedPost'] = intval($postforum['onlyAutorizedPost']); else $forum['onlyAutorizedPost'] = 0;
            if (isset($postforum['allowTags'])) $forum['allowTags'] = $postforum['allowTags'];
            if (isset($postforum['allowStyleProp'])) $forum['allowStyleProp'] = intval($postforum['allowStyleProp']); else $forum['allowStyleProp'] = 0;
            if (isset($postforum['replaceUrls'])) $forum['replaceUrls'] = intval($postforum['replaceUrls']); else $forum['replaceUrls'] = 0;
            if (isset($postforum['messageInPage'])) $forum['messageInPage'] = $postforum['messageInPage'];
            unset($postforum);
            if (!preg_match("/^.{3,}$/ui", $forum['title'])) {$errors = true; $forumerror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (($forum['avatar'] != '') && (!file_exists('..'.$forum['avatar']))) {$errors = true; $forumerror['avatar'] = 'Файл не найден';}
            if (!preg_match("/^([A-z0-9]{1,10}(\s*\,\s*[A-z0-9]{1,10})*)?$/ui", $forum['allowTags'])) {$errors = true; $forumerror['allowTags'] = 'Тэги должны состоять из 1-10 латинских букв и разделяться запятыми';}
            if ((!preg_match("/^[\d]{2,3}$/ui", $forum['messageInPage'])) || (intval($forum['messageInPage']) < 10) || (intval($forum['messageInPage']) > 100)) {$errors = true; $forumerror['messageInPage'] = 'Должно быть указано число от 10 до 100';}
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
            // Проверка сообщения (текст и вложения)
            if (!preg_match("/^[\s\S]{10,}$/ui", $forum['content'])) {$errors = true; $forumerror['content'] = 'Текст сообщения должен содержать более 10 символов';}
            $postattachments = $this->getRequest()->get('attachments');
            $forum['attachments'] = array();
            if (is_array($postattachments))
            {
                foreach ($postattachments as $attachmentid)
                {
                    $fileent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumAttachments')->find($attachmentid);
                    if (!empty($fileent))
                    {
                        if (!file_exists('..'.$fileent->getContentFile())) {$errors = true; $forum['attachments'][] = array('filename' => $fileent->getFileName(), 'filesize' => 0, 'id' => $attachmentid, 'error' => 'Файл не найден');}
                        else {$forum['attachments'][] = array('filename' => $fileent->getFileName(), 'filesize' => filesize('..'.$fileent->getContentFile()), 'id' => intval($attachmentid), 'error' => '');}
                    }
                    unset($fileent);
                }
            }
            unset($postattachments);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Проверка модераторов
            $postmoderators = $this->getRequest()->get('moderators');
            $forum['moderators'] = array();
            if (is_array($postmoderators))
            {
                foreach ($postmoderators as $moderatorid) 
                {
                    $moderatorent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($moderatorid);
                    if (!empty($moderatorent))
                    {
                        $forum['moderators'][] = array('login' => $moderatorent->getLogin(), 'fullName' => $moderatorent->getFullName(), 'id' => intval($moderatorid), 'error' => '');
                    }
                    unset($moderatorent);
                }
            }
            unset($postmoderators);
            if (($errors == true) && ($activetab == 0)) $activetab = 4;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            if ($this->container->has('object.taxonomy')) 
            {
                $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'edit', 'object.forum', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                $i++;
            }
            foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'forumsTopicEdit', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                if (($forument->getAvatar() != '') && ($forument->getAvatar() != $forum['avatar'])) @unlink('..'.$forument->getAvatar());
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($forum['avatar']);
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($forum['content'], $forument->getContent());
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($forum['description'], $forument->getDescription());
                $forument->setAllowStyleProp($forum['allowStyleProp']);
                $forument->setAllowTags($forum['allowTags']);
                $forument->setAvatar($forum['avatar']);
                $forument->setCaptchaEnabled($forum['captchaEnabled']);
                $forument->setContent($forum['content']);
                $forument->setDescription($forum['description']);
                $forument->setEnabled($forum['enabled']);
                $forument->setIsClosed($forum['isClosed']);
                $forument->setIsImportant($forum['isImportant']);
                $forument->setIsVisible($forum['isVisible']);
                $forument->setLastMessageDate(new \DateTime('now'));
                $forument->setModeratorId(null);
                $forument->setModeratorMessage(null);
                $forument->setModifyDate(new \DateTime('now'));
                $forument->setOnlyAutorizedDownload($forum['onlyAutorizedDownload']);
                $forument->setOnlyAutorizedImage($forum['onlyAutorizedImage']);
                $forument->setOnlyAutorizedPost($forum['onlyAutorizedPost']);
                $forument->setOnlyAutorizedView($forum['onlyAutorizedView']);
                $forument->setReplaceUrls($forum['replaceUrls']);
                $forument->setTitle($forum['title']);
                $forument->setMessageInPage($forum['messageInPage']);
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
                    $pageent->setDescription('Просмотр форума '.$forument->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.forum');
                    $pageent->setContentId($forument->getId());
                    $pageent->setContentAction('view');
                    $forument->setTemplate($page['template']);
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
                // Сохранение модеров
                $moderIds = array();
                foreach ($forum['moderators'] as $moder)
                {
                    $moderIds[] = $moder['id'];
                    $moderatorent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumModerators')->findOneBy(array('forumId' => $forument->getId(), 'moderatorId' => $moder['id']));
                    if (empty($moderatorent))
                    {
                        $moderatorent = new \Forum\ForumBundle\Entity\ForumModerators();
                        $em->persist($moderatorent);
                    }
                    $moderatorent->setCategoryId(0);
                    $moderatorent->setForumId($forument->getId());
                    $moderatorent->setModeratorId($moder['id']);
                    $em->flush();
                    unset($moderatorent);
                }
                if (count($moderIds) > 0) $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumModerators m WHERE m.forumId = :forumid AND m.moderatorId NOT IN (:moderids)')->setParameter('forumid', $forument->getId())->setParameter('moderids', $moderIds);
                                     else $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumModerators m WHERE m.forumId = :forumid')->setParameter('forumid', $forument->getId());
                $query->execute();
                // Сохранение вложений
                $attachIds = array();
                foreach ($forum['attachments'] as $attach)
                {
                    $fileent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumAttachments')->find($attach['id']);
                    if (!empty($fileent))
                    {
                        $attachIds[] = $attach['id'];
                        $fileent->setForumId($forument->getId());
                        $em->flush();
                    }
                    unset($fileent);
                }
                if (count($attachIds) > 0) $query = $em->createQuery('UPDATE ForumForumBundle:ForumAttachments a SET a.forumId = 0 WHERE a.forumId = :forumid AND a.messageId = 0 AND a.id NOT IN (:attachids)')->setParameter('forumid', $forument->getId())->setParameter('attachids', $attachIds);
                                      else $query = $em->createQuery('UPDATE ForumForumBundle:ForumAttachments a SET a.forumId = 0 WHERE a.forumId = :forumid AND a.messageId = 0')->setParameter('forumid', $forument->getId());
                $query->execute();
                // Сохранение других табов
                $i = 0;
                if ($this->container->has('object.taxonomy')) 
                {
                    $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'edit', 'object.forum', $forument->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                    $i++;
                }
                foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'forumsTopicEdit', $forument->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование форума',
                    'message'=>'Данные форума успешно изменены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('forum_forum_topic_edit').'?id='.$id,'Вернуться к списку форумов'=>$this->get('router')->generate('forum_forum_topic_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        if ($this->container->has('object.taxonomy')) $tabs[] =  array('name'=>'Категории классификации','content'=>$this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'edit', 'object.forum', $id, 'tab'));
        foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'forumsTopicEdit', $id, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('ForumForumBundle:Default:topicEdit.html.twig', array(
            'id' => $id,
            'forum' => $forum,
            'forumerror' => $forumerror,
            'page' => $page,
            'pageerror' => $pageerror,
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
    
    public function forumsTopicAjaxAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->getRequest()->get('action');
        $errors = array();
        $errorsorder = array();
        $tab = intval($this->getRequest()->get('tab'));
        if ($tab == 0)
        {
            if ($this->getUser()->checkAccess('forum_list') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Форум',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            if ($action == 'deleteforum')
            {
                if ($this->getUser()->checkAccess('forum_edit') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Форум',
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
                        $forument = $this->getDoctrine()->getRepository('ForumForumBundle:Forums')->find($key);
                        if (!empty($forument))
                        {
                            if ($forument->getAvatar() != '') @unlink('..'.$forument->getAvatar());
                            $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $forument->getContent());
                            $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $forument->getDescription());
                            $em->remove($forument);
                            $em->flush();
                            $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumModerators m WHERE m.forumId = :id')->setParameter('id', $key);
                            $query->execute();
                            $query = $em->createQuery('SELECT m.content FROM ForumForumBundle:ForumMessages m WHERE m.forumId = :id')->setParameter('id', $key);
                            $delpages = $query->getResult();
                            if (is_array($delpages))
                            {
                                foreach ($delpages as $delpage)
                                {
                                    if ($delpage['content'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $delpage['content']);
                                }
                            }
                            $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumMessages m WHERE m.forumId = :id')->setParameter('id', $key);
                            $query->execute();
                            $query = $em->createQuery('SELECT a.contentFile FROM ForumForumBundle:ForumAttachments a WHERE a.forumId = :id')->setParameter('id', $key);
                            $files = $query->getResult();
                            if (is_array($files))
                            {
                                foreach ($files as $file) @unlink('..'.$file['contentFile']);
                            }
                            $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumAttachments a WHERE a.forumId = :id')->setParameter('id', $key);
                            $query->execute();
                            $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.forum\' AND p.contentAction = \'view\' AND p.contentId = :id')->setParameter('id', $key);
                            $query->execute();
                            if ($this->container->has('object.taxonomy')) $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'delete', 'object.forum', $key, 'save');
                            foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) 
                            {
                                $serv = $this->container->get($item);
                                $serv->getAdminController($this->getRequest(), 'forumsTopicDelete', $key, 'save');
                            }       
                            unset($forument);    
                        }
                    }
            }
            if ($action == 'blockedforum')
            {
                if ($this->getUser()->checkAccess('forum_edit') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Форум',
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
                        $forument = $this->getDoctrine()->getRepository('ForumForumBundle:Forums')->find($key);
                        if (!empty($forument))
                        {
                            $forument->setEnabled(0);
                            $em->flush();
                            unset($forument);    
                        }
                    }
            }
            if ($action == 'unblockedforum')
            {
                if ($this->getUser()->checkAccess('forum_edit') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Форум',
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
                        $forument = $this->getDoctrine()->getRepository('ForumForumBundle:Forums')->find($key);
                        if (!empty($forument))
                        {
                            $forument->setEnabled(1);
                            $em->flush();
                            unset($forument);    
                        }
                    }
            }

            //if ($tab == 0)
            //{
            //}
            $page0 = $this->getRequest()->getSession()->get('forum_forum_topic_list_page0');
            $sort0 = $this->getRequest()->getSession()->get('forum_forum_topic_list_sort0');
            $search0 = $this->getRequest()->getSession()->get('forum_forum_topic_list_search0');
            $taxonomy0 = $this->getRequest()->getSession()->get('forum_forum_topic_list_taxonomy0');
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
            $query = $em->createQuery('SELECT count(f.id) as forumcount FROM ForumForumBundle:Forums f '.$querytaxonomyleft0.
                                      'WHERE f.title like :search '.$querytaxonomywhere0)->setParameter('search', '%'.$search0.'%');
            $forumcount = $query->getResult();
            if (!empty($forumcount)) $forumcount = $forumcount[0]['forumcount']; else $forumcount = 0;
            $pagecount0 = ceil($forumcount / 20);
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
            if ($sort0 == 8) $sortsql = 'ORDER BY f.lastMessageDate ASC';
            if ($sort0 == 9) $sortsql = 'ORDER BY f.lastMessageDate DESC';
            if ($sort0 == 10) $sortsql = 'ORDER BY messageCount ASC';
            if ($sort0 == 11) $sortsql = 'ORDER BY messageCount DESC';
            $start = $page0 * 20;
            $query = $em->createQuery('SELECT f.id, f.title, f.createDate, f.modifyDate, f.lastMessageDate, u.fullName, f.enabled, f.isClosed, (SELECT count(m.id) FROM ForumForumBundle:ForumMessages m WHERE m.forumId = f.id) as messageCount FROM ForumForumBundle:Forums f '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.$querytaxonomyleft0.
                                      'WHERE f.title like :search '.$querytaxonomywhere0.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
            $forums0 = $query->getResult();
            if ($this->container->has('object.taxonomy'))
            {
                $taxonomyenabled = 1;
                $actionIds = array();
                foreach ($forums0 as $forum) $actionIds[] = $forum['id'];
                $taxonomyinfo0 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.forum', $actionIds);
            } else
            {
                $taxonomyenabled = 0;
                $taxonomyinfo0 = null;
            }
            return $this->render('ForumForumBundle:Default:topicListTab1.html.twig', array(
                'forums0' => $forums0,
                'search0' => $search0,
                'page0' => $page0,
                'sort0' => $sort0,
                'pagecount0' => $pagecount0,
                'taxonomy0' => $taxonomy0,
                'taxonomyenabled' => $taxonomyenabled,
                'taxonomyinfo0' => $taxonomyinfo0,
                'tab' => $tab,
                'errors0' => $errors
            ));
        }
        if ($tab == 1)
        {
            if ($this->getUser()->checkAccess('forum_createpage') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Форум',
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
                        $createpageent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumCreatePages')->find($key);
                        if (!empty($createpageent))
                        {
                            $em->remove($createpageent);
                            $em->flush();
                            $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.forum\' AND p.contentAction = \'create\' AND p.contentId = :id')->setParameter('id', $key);
                            $query->execute();
                            unset($createpageent);    
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
                        $createpageent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumCreatePages')->find($key);
                        if (!empty($createpageent))
                        {
                            $createpageent->setEnabled(0);
                            $em->flush();
                            unset($createpageent);    
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
                        $createpageent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumCreatePages')->find($key);
                        if (!empty($createpageent))
                        {
                            $createpageent->setEnabled(1);
                            $em->flush();
                            unset($createpageent);    
                        }
                    }
            }

            $query = $em->createQuery('SELECT c.id, c.title, c.enabled, sp.url FROM ForumForumBundle:ForumCreatePages c LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'create\' AND sp.contentId = c.id ORDER BY c.id');
            $createpages1 = $query->getResult();
            foreach ($createpages1 as &$createpage) $createpage['title'] = $this->get('cms.cmsManager')->decodeLocalString($createpage['title'],'default',true);
            unset($createpage);
            return $this->render('ForumForumBundle:Default:topicListTab2.html.twig', array(
                'createpages1' => $createpages1,
                'tab' => $tab,
                'errors1' => $errors
            ));
        }
        if ($tab == 2)
        {
            if ($this->getUser()->checkAccess('forum_list') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Форум',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            if ($action == 'addmoderator')
            {
                if ($this->getUser()->checkAccess('forum_edit') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Форум',
                        'message'=>'Доступ запрещён', 
                        'error'=>true,
                        'paths'=>array()
                    ));
                }
                $userid = $this->getRequest()->get('userid');
                $catid = $this->getRequest()->get('categoryid');
                $userent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($userid);
                if (empty($userent))
                {
                    $errors[$catid] = 'Пользователь не найден';
                } else
                {
                    $catent = $this->getDoctrine()->getRepository('BasicCmsBundle:Taxonomies')->findOneBy(array('id' => $catid, 'object' => 'object.forum'));
                    if (empty($catent))
                    {
                        $errors[$catid] = 'Категория не найдена';
                    } else
                    {
                        $moderent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumModerators')->findOneBy(array('categoryId' => $catid, 'moderatorId' => $userid));
                        if (empty($moderent))
                        {
                            $moderent = new \Forum\ForumBundle\Entity\ForumModerators();
                            $moderent->setCategoryId($catid);
                            $moderent->setForumId(0);
                            $moderent->setModeratorId($userid);
                            $em->persist($moderent);
                            $em->flush();
                        } else
                        {
                            $errors[$catid] = 'Пользователь уже является модератором';
                        }
                    }
                }
            }
            if ($action == 'delmoderator')
            {
                if ($this->getUser()->checkAccess('forum_edit') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Форум',
                        'message'=>'Доступ запрещён', 
                        'error'=>true,
                        'paths'=>array()
                    ));
                }
                $userid = $this->getRequest()->get('userid');
                $catid = $this->getRequest()->get('categoryid');
                $userent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($userid);
                $moderent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumModerators')->findOneBy(array('categoryId' => $catid, 'moderatorId' => $userid));
                if (empty($moderent))
                {
                    $errors[$catid] = 'Модератор не найден';
                } else
                {
                    $em->remove($moderent);
                    $em->flush();
                }
            }
            // Таб 3
            $categories2 = array();
            if ($this->container->has('object.taxonomy'))
            {
                $categories2 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.forum');
                foreach ($categories2 as &$cat)
                {
                    $query = $em->createQuery('SELECT u.id, u.login, u.fullName FROM ForumForumBundle:ForumModerators m '.
                                              'INNER JOIN BasicCmsBundle:Users u WITH u.id = m.moderatorId '.
                                              'WHERE m.forumId = 0 AND m.categoryId = :id')->setParameter('id', $cat['id']);
                    $cat['moderators'] = $query->getResult();
                    if (!is_array($cat['moderators'])) $cat['moderators'] = array();
                }
                unset($cat);
            }
            return $this->render('ForumForumBundle:Default:topicListTab3.html.twig', array(
                'categories2' => $categories2,
                'tab' => $tab,
                'errors2' => $errors
            ));
        }
        if ($tab == 3)
        {
            if ($this->getUser()->checkAccess('forum_commentsview') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Форум',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            if ($action == 'deletecomment')
            {
                if ($this->getUser()->checkAccess('forum_commentsedit') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Форум',
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
                        $commentent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumComments')->find($key);
                        if (!empty($commentent))
                        {
                            $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $commentent->getContent());
                            $em->remove($commentent);
                            $em->flush();
                            $query = $em->createQuery('SELECT a.contentFile FROM ForumForumBundle:ForumCommentAttachments a WHERE a.commentId = :id')->setParameter('id', $key);
                            $files = $query->getResult();
                            if (is_array($files))
                            {
                                foreach ($files as $file) @unlink('..'.$file['contentFile']);
                            }
                            $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumCommentAttachments a WHERE a.commentId = :id')->setParameter('id', $key);
                            $query->execute();
                            unset($commentent); 
                        }
                    }
            }
            if ($action == 'blockedcomment')
            {
                if ($this->getUser()->checkAccess('forum_commentsedit') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Форум',
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
                        $commentent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumComments')->find($key);
                        if (!empty($commentent))
                        {
                            $commentent->setIsVisible(0);
                            $em->flush();
                            unset($commentent);    
                        }
                    }
            }
            if ($action == 'unblockedcomment')
            {
                if ($this->getUser()->checkAccess('forum_commentsedit') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Форум',
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
                        $commentent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumComments')->find($key);
                        if (!empty($commentent))
                        {
                            $commentent->setIsVisible(1);
                            $em->flush();
                            unset($commentent);    
                        }
                    }
            }
            $page3 = $this->getRequest()->getSession()->get('forum_forum_topic_list_page3');
            $sort3 = $this->getRequest()->getSession()->get('forum_forum_topic_list_sort3');
            $search3 = $this->getRequest()->getSession()->get('forum_forum_topic_list_search3');
            $page3 = intval($page3);
            $sort3 = intval($sort3);
            $search3 = trim($search3);
            // Поиск контента для 1 таба (текстовые страницы)
            $query = $em->createQuery('SELECT count(c.id) as commentcount FROM ForumForumBundle:ForumComments c '.
                                      'WHERE c.content like :search')->setParameter('search', '%'.$search3.'%');
            $commentcount = $query->getResult();
            if (!empty($commentcount)) $commentcount = $commentcount[0]['commentcount']; else $commentcount = 0;
            $pagecount3 = ceil($commentcount / 20);
            if ($pagecount3 < 1) $pagecount3 = 1;
            if ($page3 < 0) $page3 = 0;
            if ($page3 >= $pagecount3) $page3 = $pagecount3 - 1;
            $sortsql = 'ORDER BY c.createDate ASC, c.id ASC';
            if ($sort3 == 1) $sortsql = 'ORDER BY c.createDate DESC, m.id DESC';
            if ($sort3 == 2) $sortsql = 'ORDER BY c.isVisible ASC';
            if ($sort3 == 3) $sortsql = 'ORDER BY c.isVisible DESC';
            if ($sort3 == 4) $sortsql = 'ORDER BY c.content ASC';
            if ($sort3 == 5) $sortsql = 'ORDER BY c.content DESC';
            if ($sort3 == 6) $sortsql = 'ORDER BY u.fullName ASC';
            if ($sort3 == 7) $sortsql = 'ORDER BY u.fullName DESC';
            if ($sort3 == 8) $sortsql = 'ORDER BY um.fullName ASC';
            if ($sort3 == 9) $sortsql = 'ORDER BY um.fullName DESC';
            if ($sort3 == 10) $sortsql = 'ORDER BY s.description ASC';
            if ($sort3 == 11) $sortsql = 'ORDER BY s.description DESC';
            $start = $page3 * 20;
            $query = $em->createQuery('SELECT c.id, c.content, c.createDate, c.modifyDate, c.isVisible, u.fullName, um.fullName as moderatorFullName, s.description FROM ForumForumBundle:ForumComments c '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = c.createrId '.
                                      'LEFT JOIN BasicCmsBundle:Users um WITH um.id = c.moderatorId '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage s WITH s.id = c.seoPageId '.
                                      'WHERE c.content like :search '.$sortsql)->setParameter('search', '%'.$search3.'%')->setFirstResult($start)->setMaxResults(20);
            $comments3 = $query->getResult();
            return $this->render('ForumForumBundle:Default:topicListTab4.html.twig', array(
                'comments3' => $comments3,
                'search3' => $search3,
                'page3' => $page3,
                'sort3' => $sort3,
                'pagecount3' => $pagecount3,
                'tab' => $tab,
                'errors3' => $errors
            ));
            
            
            
            
            
        }
    }
    
// *******************************************
// Создание страницы создания форума
// *******************************************    
    public function forumsCreatepageCreateAction()
    {
        $this->getRequest()->getSession()->set('forum_forum_topic_list_tab', 1);
        if ($this->getUser()->checkAccess('forum_createpage') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой страницы создания форумов',
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
        $createpage['forumEnabled'] = '';
        $createpage['forumIsClosed'] = '';
        $createpage['forumIsImportant'] = '';
        $createpage['forumIsVisible'] = '';
        $createpage['forumCaptchaEnabled'] = '';
        $createpage['forumOnlyAutorizedView'] = '';
        $createpage['forumOnlyAutorizedPost'] = '';
        $createpage['forumOnlyAutorizedDownload'] = '';
        $createpage['forumOnlyAutorizedImage'] = '';
        $createpage['forumAllowTags'] = '';
        $createpage['forumAllowStyleProp'] = '';
        $createpage['forumReplaceUrls'] = '';
        $createpage['forumMessageInPage'] = '';
        $createpage['forumModerators'] = array();
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
        $createpageerror['forumEnabled'] = '';
        $createpageerror['forumIsClosed'] = '';
        $createpageerror['forumIsImportant'] = '';
        $createpageerror['forumIsVisible'] = '';
        $createpageerror['forumCaptchaEnabled'] = '';
        $createpageerror['forumOnlyAutorizedView'] = '';
        $createpageerror['forumOnlyAutorizedPost'] = '';
        $createpageerror['forumOnlyAutorizedDownload'] = '';
        $createpageerror['forumOnlyAutorizedImage'] = '';
        $createpageerror['forumAllowTags'] = '';
        $createpageerror['forumAllowStyleProp'] = '';
        $createpageerror['forumReplaceUrls'] = '';
        $createpageerror['forumMessageInPage'] = '';
        $createpageerror['forumModerators'] = array();
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
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.forum','create');
        $forumtemplates = $this->get('cms.cmsManager')->getTemplateList('object.forum','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($forumtemplates) > 0) {reset($forumtemplates); $regpage['seopageTemplate'] = key($forumtemplates);}
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts); $regpage['seopageLayout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn, LOCATE(:moduleuserid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleUserDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.forum.create')->setParameter('moduleuserid','object.forum.view');
        $modules = $query->getResult();
        foreach ($modules as $module) 
        {
            if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
            if ($module['moduleUserDefOn'] != 0) $regpage['seopageModules'][] = $module['id'];
        }
        // Данные таксономии
        if ($this->container->has('object.taxonomy')) $taxonomyinfo = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.forum'); else $taxonomyinfo = array();
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
            if (isset($postcreatepage['forumEnabled'])) $createpage['forumEnabled'] = intval($postcreatepage['forumEnabled']); else $createpage['forumEnabled'] = 0;
            if (isset($postcreatepage['forumIsClosed'])) $createpage['forumIsClosed'] = intval($postcreatepage['forumIsClosed']); else $createpage['forumIsClosed'] = 0;
            if (isset($postcreatepage['forumIsImportant'])) $createpage['forumIsImportant'] = intval($postcreatepage['forumIsImportant']); else $createpage['forumIsImportant'] = 0;
            if (isset($postcreatepage['forumIsVisible'])) $createpage['forumIsVisible'] = intval($postcreatepage['forumIsVisible']); else $createpage['forumIsVisible'] = 0;
            if (isset($postcreatepage['forumCaptchaEnabled'])) $createpage['forumCaptchaEnabled'] = intval($postcreatepage['forumCaptchaEnabled']); else $createpage['forumCaptchaEnabled'] = 0;
            if (isset($postcreatepage['forumOnlyAutorizedView'])) $createpage['forumOnlyAutorizedView'] = intval($postcreatepage['forumOnlyAutorizedView']); else $createpage['forumOnlyAutorizedView'] = 0;
            if (isset($postcreatepage['forumOnlyAutorizedPost'])) $createpage['forumOnlyAutorizedPost'] = intval($postcreatepage['forumOnlyAutorizedPost']); else $createpage['forumOnlyAutorizedPost'] = 0;
            if (isset($postcreatepage['forumOnlyAutorizedDownload'])) $createpage['forumOnlyAutorizedDownload'] = intval($postcreatepage['forumOnlyAutorizedDownload']); else $createpage['forumOnlyAutorizedDownload'] = 0;
            if (isset($postcreatepage['forumOnlyAutorizedImage'])) $createpage['forumOnlyAutorizedImage'] = intval($postcreatepage['forumOnlyAutorizedImage']); else $createpage['forumOnlyAutorizedImage'] = 0;
            if (isset($postcreatepage['forumAllowStyleProp'])) $createpage['forumAllowStyleProp'] = intval($postcreatepage['forumAllowStyleProp']); else $createpage['forumAllowStyleProp'] = 0;
            if (isset($postcreatepage['forumReplaceUrls'])) $createpage['forumReplaceUrls'] = intval($postcreatepage['forumReplaceUrls']); else $createpage['forumReplaceUrls'] = 0;
            if (isset($postcreatepage['forumAllowTags'])) $createpage['forumAllowTags'] = $postcreatepage['forumAllowTags'];
            if (isset($postcreatepage['forumMessageInPage'])) $createpage['forumMessageInPage'] = $postcreatepage['forumMessageInPage'];
            
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
                $query = $em->createQuery('SELECT count(t.id) as taxcount FROM BasicCmsBundle:Taxonomies t WHERE t.id = :id AND t.enabled != 0 AND t.enableAdd != 0 AND t.object = \'object.forum\'')->setParameter('id', $createpage['categoryId']);
                $taxcount = $query->getResult();
                if (isset($taxcount[0]['taxcount'])) $taxcount = intval($taxcount[0]['taxcount']); else $taxcount = 0;
                if ($taxcount == 0) {$errors = true; $createpageerror['categoryId'] = 'Категория не найдена или добавление запрещено';}
            }
            if (!preg_match("/^([A-z0-9]{1,10}(\s*\,\s*[A-z0-9]{1,10})*)?$/ui", $createpage['forumAllowTags'])) {$errors = true; $createpageerror['forumAllowTags'] = 'Тэги должны состоять из 1-10 латинских букв и разделяться запятыми';}
            if ((!preg_match("/^[\d]{2,3}$/ui", $createpage['forumMessageInPage'])) || (intval($createpage['forumMessageInPage']) < 10) || (intval($createpage['forumMessageInPage']) > 100)) {$errors = true; $createpageerror['forumMessageInPage'] = 'Должно быть указано число от 10 до 100';}
            
            if ($createpage['seopageEnable'] != 0)
            {
                if ($createpage['seopageUrl'] != '')
                {
                    if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", str_replace(array('//id//','//title//'),'test',$createpage['seopageUrl']))) {$errors = true; $createpageerror['seopageUrl'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                }
                $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $createpage['seopageLocale']) $localecheck = true;}
                if ($localecheck == false) $createpage['seopageLocale'] = '';
                if (($createpage['seopageLayout'] != '') && (!isset($layouts[$createpage['seopageLayout']]))) {$errors = true; $createpageerror['seopageLayout'] = 'Шаблон не найден';}
                if (($createpage['seopageTemplate'] != '') && (!isset($forumtemplates[$createpage['seopageTemplate']]))) {$errors = true; $createpageerror['seopageTemplate'] = 'Шаблон не найден';}
            }
            // Проверка модераторов
            $postmoderators = $this->getRequest()->get('moderators');
            $createpage['forumModerators'] = array();
            if (is_array($postmoderators))
            {
                foreach ($postmoderators as $moderatorid) 
                {
                    $moderatorent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($moderatorid);
                    if (!empty($moderatorent))
                    {
                        $createpage['forumModerators'][] = array('login' => $moderatorent->getLogin(), 'fullName' => $moderatorent->getFullName(), 'id' => intval($moderatorid), 'error' => '');
                    }
                    unset($moderatorent);
                }
            }
            unset($postmoderators);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $createpageent = new \Forum\ForumBundle\Entity\ForumCreatePages();
                $createpageent->setCaptchaEnabled($createpage['captchaEnabled']);
                $createpageent->setCategoryId(intval($createpage['categoryId']));
                $createpageent->setCategoryMode($createpage['categoryMode']);
                $createpageent->setEnabled($createpage['enabled']);
                $createpageent->setForumAllowStyleProp($createpage['forumAllowStyleProp']);
                $createpageent->setForumAllowTags($createpage['forumAllowTags']);
                $createpageent->setForumCaptchaEnabled($createpage['forumCaptchaEnabled']);
                $createpageent->setForumEnabled($createpage['forumEnabled']);
                $createpageent->setForumIsClosed($createpage['forumIsClosed']);
                $createpageent->setForumIsImportant($createpage['forumIsImportant']);
                $createpageent->setForumIsVisible($createpage['forumIsVisible']);
                $createpageent->setForumMessageInPage($createpage['forumMessageInPage']);
                $moderIds = array();
                foreach ($createpage['forumModerators'] as $moder) $moderIds[] = $moder['id'];
                $createpageent->setForumModerators(implode(',', $moderIds));
                $createpageent->setForumOnlyAutorizedDownload($createpage['forumOnlyAutorizedDownload']);
                $createpageent->setForumOnlyAutorizedImage($createpage['forumOnlyAutorizedImage']);
                $createpageent->setForumOnlyAutorizedPost($createpage['forumOnlyAutorizedPost']);
                $createpageent->setForumOnlyAutorizedView($createpage['forumOnlyAutorizedView']);
                $createpageent->setForumReplaceUrls($createpage['forumReplaceUrls']);
                $createpageent->setForumTemplate($createpage['seopageTemplate']);
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
                $pageent->setDescription('Страница создания форума '.$this->get('cms.cmsManager')->decodeLocalString($createpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.forum');
                $pageent->setContentId($createpageent->getId());
                $pageent->setContentAction('create');
                $createpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->persist($pageent);
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой страницы создания форума',
                    'message'=>'Страница создания форума успешно создана',
                    'paths'=>array('Создать еще одну страницу создания форума'=>$this->get('router')->generate('forum_forum_createpage_create'),'Вернуться к списку тем форума'=>$this->get('router')->generate('forum_forum_topic_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('ForumForumBundle:Default:topicCreatepageCreate.html.twig', array(
            'createpage' => $createpage,
            'createpageerror' => $createpageerror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'forumtemplates' => $forumtemplates,
            'modules' => $modules,
            'layouts' => $layouts,
            'taxonomyinfo' => $taxonomyinfo,
            'activetab' => $activetab
        ));
    }

// *******************************************
// Редактирование страницы создания форума
// *******************************************    
    public function forumsCreatepageEditAction()
    {
        $this->getRequest()->getSession()->set('forum_forum_topic_list_tab', 1);
        $id = intval($this->getRequest()->get('id'));
        $createpageent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumCreatePages')->find($id);
        if (empty($createpageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы создания форумов',
                'message'=>'Страница создания форумов не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку тем форума'=>$this->get('router')->generate('forum_forum_topic_list'))
            ));
        }
        if ($this->getUser()->checkAccess('forum_createpage') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы создания форумов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.forum','contentId'=>$id,'contentAction'=>'create'));
        
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
        $createpage['forumEnabled'] = $createpageent->getForumEnabled();
        $createpage['forumIsClosed'] = $createpageent->getForumIsClosed();
        $createpage['forumIsImportant'] = $createpageent->getForumIsImportant();
        $createpage['forumIsVisible'] = $createpageent->getForumIsVisible();
        $createpage['forumCaptchaEnabled'] = $createpageent->getForumCaptchaEnabled();
        $createpage['forumOnlyAutorizedView'] = $createpageent->getForumOnlyAutorizedView();
        $createpage['forumOnlyAutorizedPost'] = $createpageent->getForumOnlyAutorizedPost();
        $createpage['forumOnlyAutorizedDownload'] = $createpageent->getForumOnlyAutorizedDownload();
        $createpage['forumOnlyAutorizedImage'] = $createpageent->getForumOnlyAutorizedImage();
        $createpage['forumAllowTags'] = $createpageent->getForumAllowTags();
        $createpage['forumAllowStyleProp'] = $createpageent->getForumAllowStyleProp();
        $createpage['forumReplaceUrls'] = $createpageent->getForumReplaceUrls();
        $createpage['forumMessageInPage'] = $createpageent->getForumMessageInPage();
        $createpage['forumModerators'] = array();
        if (is_array(explode(',', $createpageent->getForumModerators())) && (count(explode(',', $createpageent->getForumModerators())) > 0))
        {
            $query = $em->createQuery('SELECT u.id, u.login, u.fullName, \'\' as error FROM BasicCmsBundle:Users u WHERE u.id IN (:ids)')->setParameter('ids', explode(',', $createpageent->getForumModerators()));
            $createpage['forumModerators'] = $query->getResult();
            if (!is_array($createpage['forumModerators'])) $createpage['forumModerators'] = array();
        }
        $createpage['seopageEnable'] = $createpageent->getSeopageEnabled();
        $createpage['seopageLayout'] = $createpageent->getSeopageTemplate();
        $createpage['seopageTemplate'] = $createpageent->getForumTemplate();
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
        $createpageerror['forumEnabled'] = '';
        $createpageerror['forumIsClosed'] = '';
        $createpageerror['forumIsImportant'] = '';
        $createpageerror['forumIsVisible'] = '';
        $createpageerror['forumCaptchaEnabled'] = '';
        $createpageerror['forumOnlyAutorizedView'] = '';
        $createpageerror['forumOnlyAutorizedPost'] = '';
        $createpageerror['forumOnlyAutorizedDownload'] = '';
        $createpageerror['forumOnlyAutorizedImage'] = '';
        $createpageerror['forumAllowTags'] = '';
        $createpageerror['forumAllowStyleProp'] = '';
        $createpageerror['forumReplaceUrls'] = '';
        $createpageerror['forumMessageInPage'] = '';
        $createpageerror['forumModerators'] = '';
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
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.forum','create');
        $forumtemplates = $this->get('cms.cmsManager')->getTemplateList('object.forum','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($forumtemplates) > 0) {reset($forumtemplates); $regpage['seopageTemplate'] = key($forumtemplates);}
        if (empty($pageent))
        {
            if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
            if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts); $regpage['seopageLayout'] = key($layouts);}
        }
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn, LOCATE(:moduleuserid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleUserDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.forum.create')->setParameter('moduleuserid','object.forum.view');
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
        if ($this->container->has('object.taxonomy')) $taxonomyinfo = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.forum'); else $taxonomyinfo = array();
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
            if (isset($postcreatepage['forumEnabled'])) $createpage['forumEnabled'] = intval($postcreatepage['forumEnabled']); else $createpage['forumEnabled'] = 0;
            if (isset($postcreatepage['forumIsClosed'])) $createpage['forumIsClosed'] = intval($postcreatepage['forumIsClosed']); else $createpage['forumIsClosed'] = 0;
            if (isset($postcreatepage['forumIsImportant'])) $createpage['forumIsImportant'] = intval($postcreatepage['forumIsImportant']); else $createpage['forumIsImportant'] = 0;
            if (isset($postcreatepage['forumIsVisible'])) $createpage['forumIsVisible'] = intval($postcreatepage['forumIsVisible']); else $createpage['forumIsVisible'] = 0;
            if (isset($postcreatepage['forumCaptchaEnabled'])) $createpage['forumCaptchaEnabled'] = intval($postcreatepage['forumCaptchaEnabled']); else $createpage['forumCaptchaEnabled'] = 0;
            if (isset($postcreatepage['forumOnlyAutorizedView'])) $createpage['forumOnlyAutorizedView'] = intval($postcreatepage['forumOnlyAutorizedView']); else $createpage['forumOnlyAutorizedView'] = 0;
            if (isset($postcreatepage['forumOnlyAutorizedPost'])) $createpage['forumOnlyAutorizedPost'] = intval($postcreatepage['forumOnlyAutorizedPost']); else $createpage['forumOnlyAutorizedPost'] = 0;
            if (isset($postcreatepage['forumOnlyAutorizedDownload'])) $createpage['forumOnlyAutorizedDownload'] = intval($postcreatepage['forumOnlyAutorizedDownload']); else $createpage['forumOnlyAutorizedDownload'] = 0;
            if (isset($postcreatepage['forumOnlyAutorizedImage'])) $createpage['forumOnlyAutorizedImage'] = intval($postcreatepage['forumOnlyAutorizedImage']); else $createpage['forumOnlyAutorizedImage'] = 0;
            if (isset($postcreatepage['forumAllowStyleProp'])) $createpage['forumAllowStyleProp'] = intval($postcreatepage['forumAllowStyleProp']); else $createpage['forumAllowStyleProp'] = 0;
            if (isset($postcreatepage['forumReplaceUrls'])) $createpage['forumReplaceUrls'] = intval($postcreatepage['forumReplaceUrls']); else $createpage['forumReplaceUrls'] = 0;
            if (isset($postcreatepage['forumAllowTags'])) $createpage['forumAllowTags'] = $postcreatepage['forumAllowTags'];
            if (isset($postcreatepage['forumMessageInPage'])) $createpage['forumMessageInPage'] = $postcreatepage['forumMessageInPage'];
            
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
                $query = $em->createQuery('SELECT count(t.id) as taxcount FROM BasicCmsBundle:Taxonomies t WHERE t.id = :id AND t.enabled != 0 AND t.enableAdd != 0 AND t.object = \'object.forum\'')->setParameter('id', $createpage['categoryId']);
                $taxcount = $query->getResult();
                if (isset($taxcount[0]['taxcount'])) $taxcount = intval($taxcount[0]['taxcount']); else $taxcount = 0;
                if ($taxcount == 0) {$errors = true; $createpageerror['categoryId'] = 'Категория не найдена или добавление запрещено';}
            }
            if (!preg_match("/^([A-z0-9]{1,10}(\s*\,\s*[A-z0-9]{1,10})*)?$/ui", $createpage['forumAllowTags'])) {$errors = true; $createpageerror['forumAllowTags'] = 'Тэги должны состоять из 1-10 латинских букв и разделяться запятыми';}
            if ((!preg_match("/^[\d]{2,3}$/ui", $createpage['forumMessageInPage'])) || (intval($createpage['forumMessageInPage']) < 10) || (intval($createpage['forumMessageInPage']) > 100)) {$errors = true; $createpageerror['forumMessageInPage'] = 'Должно быть указано число от 10 до 100';}
            
            if ($createpage['seopageEnable'] != 0)
            {
                if ($createpage['seopageUrl'] != '')
                {
                    if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", str_replace(array('//id//','//title//'),'test',$createpage['seopageUrl']))) {$errors = true; $createpageerror['seopageUrl'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                }
                $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $createpage['seopageLocale']) $localecheck = true;}
                if ($localecheck == false) $createpage['seopageLocale'] = '';
                if (($createpage['seopageLayout'] != '') && (!isset($layouts[$createpage['seopageLayout']]))) {$errors = true; $createpageerror['seopageLayout'] = 'Шаблон не найден';}
                if (($createpage['seopageTemplate'] != '') && (!isset($forumtemplates[$createpage['seopageTemplate']]))) {$errors = true; $createpageerror['seopageTemplate'] = 'Шаблон не найден';}
            }
            // Проверка модераторов
            $postmoderators = $this->getRequest()->get('moderators');
            $createpage['forumModerators'] = array();
            if (is_array($postmoderators))
            {
                foreach ($postmoderators as $moderatorid) 
                {
                    $moderatorent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($moderatorid);
                    if (!empty($moderatorent))
                    {
                        $createpage['forumModerators'][] = array('login' => $moderatorent->getLogin(), 'fullName' => $moderatorent->getFullName(), 'id' => intval($moderatorid), 'error' => '');
                    }
                    unset($moderatorent);
                }
            }
            unset($postmoderators);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $createpageent->setCaptchaEnabled($createpage['captchaEnabled']);
                $createpageent->setCategoryId(intval($createpage['categoryId']));
                $createpageent->setCategoryMode($createpage['categoryMode']);
                $createpageent->setEnabled($createpage['enabled']);
                $createpageent->setForumAllowStyleProp($createpage['forumAllowStyleProp']);
                $createpageent->setForumAllowTags($createpage['forumAllowTags']);
                $createpageent->setForumCaptchaEnabled($createpage['forumCaptchaEnabled']);
                $createpageent->setForumEnabled($createpage['forumEnabled']);
                $createpageent->setForumIsClosed($createpage['forumIsClosed']);
                $createpageent->setForumIsImportant($createpage['forumIsImportant']);
                $createpageent->setForumIsVisible($createpage['forumIsVisible']);
                $createpageent->setForumMessageInPage($createpage['forumMessageInPage']);
                $moderIds = array();
                foreach ($createpage['forumModerators'] as $moder) $moderIds[] = $moder['id'];
                $createpageent->setForumModerators(implode(',', $moderIds));
                $createpageent->setForumOnlyAutorizedDownload($createpage['forumOnlyAutorizedDownload']);
                $createpageent->setForumOnlyAutorizedImage($createpage['forumOnlyAutorizedImage']);
                $createpageent->setForumOnlyAutorizedPost($createpage['forumOnlyAutorizedPost']);
                $createpageent->setForumOnlyAutorizedView($createpage['forumOnlyAutorizedView']);
                $createpageent->setForumReplaceUrls($createpage['forumReplaceUrls']);
                $createpageent->setForumTemplate($createpage['seopageTemplate']);
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
                $pageent->setDescription('Страница создания форума '.$this->get('cms.cmsManager')->decodeLocalString($createpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.forum');
                $pageent->setContentId($createpageent->getId());
                $pageent->setContentAction('create');
                $createpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование страницы создания форума',
                    'message'=>'Данные страницы создания форума успешно сохранены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('forum_forum_createpage_edit').'?id='.$id,'Вернуться к списку тем форума'=>$this->get('router')->generate('forum_forum_topic_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('ForumForumBundle:Default:topicCreatepageEdit.html.twig', array(
            'id' => $id,
            'createpage' => $createpage,
            'createpageerror' => $createpageerror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'forumtemplates' => $forumtemplates,
            'modules' => $modules,
            'layouts' => $layouts,
            'taxonomyinfo' => $taxonomyinfo,
            'activetab' => $activetab
        ));
    }

// *******************************************
// Список сообщений форума
// *******************************************    
    
    public function forumsMessageListAction()
    {
        $this->getRequest()->getSession()->set('forum_forum_topic_list_tab', 0);
        if ($this->getUser()->checkAccess('forum_view') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр ответов форума',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        // Таб 1
        $id = $this->getRequest()->get('id');
        $page0 = $this->getRequest()->get('page0');
        $sort0 = $this->getRequest()->get('sort0');
        $search0 = $this->getRequest()->get('search0');
        if ($id === null) $id = $this->getRequest()->getSession()->get('forum_forum_messages_list_id');
                     else $this->getRequest()->getSession()->set('forum_forum_messages_list_id', $id);
        if ($page0 === null) $page0 = $this->getRequest()->getSession()->get('forum_forum_messages_list_page0');
                        else $this->getRequest()->getSession()->set('forum_forum_messages_list_page0', $page0);
        if ($sort0 === null) $sort0 = $this->getRequest()->getSession()->get('forum_forum_messages_list_sort0');
                        else $this->getRequest()->getSession()->set('forum_forum_messages_list_sort0', $sort0);
        if ($search0 === null) $search0 = $this->getRequest()->getSession()->get('forum_forum_messages_list_search0');
                          else $this->getRequest()->getSession()->set('forum_forum_messages_list_search0', $search0);
        $forument = $this->getDoctrine()->getRepository('ForumForumBundle:Forums')->find($id);
        if (empty($forument))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр ответов форума',
                'message'=>'Форум не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к списку форумов'=>$this->get('router')->generate('forum_forum_topic_list'))
            ));
        }
        $oldid = $this->getRequest()->getSession()->get('forum_forum_messages_list_oldid');
        if ($id != $oldid)
        {
            $page0 = null;
            $sort0 = null;
            $search0 = null;
            $this->getRequest()->getSession()->set('forum_forum_messages_list_oldid', $id);
            $this->getRequest()->getSession()->set('forum_forum_messages_list_page0', $page0);
            $this->getRequest()->getSession()->set('forum_forum_messages_list_sort0', $sort0);
            $this->getRequest()->getSession()->set('forum_forum_messages_list_search0', $search0);
        }
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        // Поиск контента для 1 таба (текстовые страницы)
        $query = $em->createQuery('SELECT count(m.id) as messagecount FROM ForumForumBundle:ForumMessages m '.
                                  'WHERE m.content like :search AND m.forumId = :id')->setParameter('search', '%'.$search0.'%')->setParameter('id', $id);
        $messagecount = $query->getResult();
        if (!empty($messagecount)) $messagecount = $messagecount[0]['messagecount']; else $messagecount = 0;
        $pagecount0 = ceil($messagecount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY m.createDate ASC, m.id ASC';
        if ($sort0 == 1) $sortsql = 'ORDER BY m.createDate DESC, m.id DESC';
        if ($sort0 == 2) $sortsql = 'ORDER BY m.isVisible ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY m.isVisible DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY m.content ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY m.content DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        if ($sort0 == 8) $sortsql = 'ORDER BY um.fullName ASC';
        if ($sort0 == 9) $sortsql = 'ORDER BY um.fullName DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT m.id, m.content, m.createDate, m.modifyDate, m.isVisible, u.fullName, um.fullName as moderatorFullName FROM ForumForumBundle:ForumMessages m '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = m.createrId '.
                                  'LEFT JOIN BasicCmsBundle:Users um WITH um.id = m.moderatorId '.
                                  'WHERE m.content like :search AND m.forumId = :id '.$sortsql)->setParameter('search', '%'.$search0.'%')->setParameter('id', $id)->setFirstResult($start)->setMaxResults(20);
        $messages0 = $query->getResult();
        return $this->render('ForumForumBundle:Default:messageList.html.twig', array(
            'forum' => $forument,
            'messages0' => $messages0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'pagecount0' => $pagecount0
        ));
    }
    
// *******************************************
// Создание нового сообщения форума
// *******************************************    
    public function forumsMessageCreateAction()
    {
        $id = intval($this->getRequest()->get('id'));
        $forument = $this->getDoctrine()->getRepository('ForumForumBundle:Forums')->find($id);
        if (empty($forument))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание нового сообщения форума',
                'message'=>'Форум не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к списку форумов'=>$this->get('router')->generate('forum_forum_topic_list'))
            ));
        }
        if ($this->getUser()->checkAccess('forum_edit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание нового сообщения форума',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $message = array();
        $messageerror = array();
        
        $message['content'] = '';
        $message['attachments'] = array();
        $message['isVisible'] = 1;
        $message['moderatorNamePrev'] = '';
        $message['moderatorMessagePrev'] = '';
        $message['moderatorMessage'] = '';
        
        $messageerror['content'] = '';
        $messageerror['attachments'] = array();
        $messageerror['isVisible'] = '';
        $messageerror['moderatorMessage'] = '';
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postmessage = $this->getRequest()->get('message');
            if (isset($postmessage['content'])) $message['content'] = $postmessage['content'];
            if (isset($postmessage['moderatorMessage'])) $message['moderatorMessage'] = $postmessage['moderatorMessage'];
            if (isset($postmessage['isVisible'])) $message['isVisible'] = intval($postmessage['isVisible']); else $message['isVisible'] = 0;
            unset($postmessage);
            if (!preg_match("/^[\s\S]{10,}$/ui", $message['content'])) {$errors = true; $messageerror['content'] = 'Текст сообщения должен содержать более 10 символов';}
            $postattachments = $this->getRequest()->get('attachments');
            $message['attachments'] = array();
            if (is_array($postattachments))
            {
                foreach ($postattachments as $attachmentid)
                {
                    $fileent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumAttachments')->find($attachmentid);
                    if (!empty($fileent))
                    {
                        if (!file_exists('..'.$fileent->getContentFile())) {$errors = true; $message['attachments'][] = array('filename' => $fileent->getFileName(), 'filesize' => 0, 'id' => $attachmentid, 'error' => 'Файл не найден');}
                        else {$message['attachments'][] = array('filename' => $fileent->getFileName(), 'filesize' => filesize('..'.$fileent->getContentFile()), 'id' => intval($attachmentid), 'error' => '');}
                    }
                    unset($fileent);
                }
            }
            unset($postattachments);
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($message['content'], '');
                $messageent = new \Forum\ForumBundle\Entity\ForumMessages();
                $messageent->setContent($message['content']);
                $messageent->setCreateDate(new \DateTime('now'));
                $messageent->setCreaterId($this->getUser()->getId());
                $messageent->setForumId($forument->getId());
                $messageent->setIsVisible($message['isVisible']);
                $messageent->setModeratorId(null);
                $messageent->setModeratorMessage(null);
                if ($message['moderatorMessage'] != '')
                {
                    $messageent->setModeratorId($this->getUser()->getId());
                    $messageent->setModeratorMessage($message['moderatorMessage']);
                }
                $messageent->setModifyDate(new \DateTime('now'));
                $em->persist($messageent);
                $em->flush();
                // Сохранение вложений
                foreach ($message['attachments'] as $attach)
                {
                    $fileent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumAttachments')->find($attach['id']);
                    if (!empty($fileent))
                    {
                        $fileent->setForumId($forument->getId());
                        $fileent->setMessageId($messageent->getId());
                        $em->flush();
                    }
                    unset($fileent);
                }
                $forument->setLastMessageId($messageent->getId());
                $forument->setLastMessageDate($messageent->getModifyDate());
                $em->flush();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание нового сообщения форума',
                    'message'=>'Сообщение форума успешно создано',
                    'paths'=>array('Создать еще одно сообщение форум'=>$this->get('router')->generate('forum_forum_messages_create').'?id='.$id,'Вернуться к списку сообщений форума'=>$this->get('router')->generate('forum_forum_messages_list').'?id='.$forument->getId())
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('ForumForumBundle:Default:messageCreate.html.twig', array(
            'id' => $id,
            'forum' => $forument,
            'message' => $message,
            'messageerror' => $messageerror,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// Редактирование сообщения форума
// *******************************************    
    public function forumsMessageEditAction()
    {
        $id = intval($this->getRequest()->get('id'));
        $messageent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumMessages')->find($id);
        if (empty($messageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование сообщения форума',
                'message'=>'Сообщение не найдено', 
                'error'=>true,
                'paths'=>array('Вернуться к списку форумов'=>$this->get('router')->generate('forum_forum_topic_list'))
            ));
        }
        $forument = $this->getDoctrine()->getRepository('ForumForumBundle:Forums')->find($messageent->getForumId());
        if (empty($forument))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование сообщения форума',
                'message'=>'Форум не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к списку форумов'=>$this->get('router')->generate('forum_forum_topic_list'))
            ));
        }
        if ($this->getUser()->checkAccess('forum_edit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование сообщения форума',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        if ($messageent->getModeratorId() != null) $moderatorent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($messageent->getModeratorId()); else $moderatorent = null;
        
        $em = $this->getDoctrine()->getEntityManager();
        
        $message = array();
        $messageerror = array();
        
        $message['content'] = $messageent->getContent();
        $message['attachments'] = array();
        $message['isVisible'] = $messageent->getIsVisible();
        $message['moderatorNamePrev'] = (!empty($moderatorent) ? $moderatorent->getFullName() : '');
        $message['moderatorMessagePrev'] = $messageent->getModeratorMessage();
        $message['moderatorMessage'] = '';
        
        $messageerror['content'] = '';
        $messageerror['attachments'] = '';
        $messageerror['isVisible'] = '';
        $messageerror['moderatorMessage'] = '';
        
        $query = $em->createQuery('SELECT f.id, f.fileName as filename, f.contentFile, 0 as filesize, \'\' as error FROM ForumForumBundle:ForumAttachments f '.
                                  'WHERE f.forumId = :forumid AND f.messageId = :id')->setParameter('id', $id)->setParameter('forumid', $forument->getId());
        $message['attachments'] = $query->getResult();
        if (!is_array($message['attachments'])) $message['attachments'] = array();
        foreach ($message['attachments'] as &$attach)
        {
            $attach['filesize'] = @filesize('..'.$attach['contentFile']);
        }
        unset($attach);
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postmessage = $this->getRequest()->get('message');
            if (isset($postmessage['content'])) $message['content'] = $postmessage['content'];
            if (isset($postmessage['moderatorMessage'])) $message['moderatorMessage'] = $postmessage['moderatorMessage'];
            if (isset($postmessage['isVisible'])) $message['isVisible'] = intval($postmessage['isVisible']); else $message['isVisible'] = 0;
            unset($postmessage);
            if (!preg_match("/^[\s\S]{10,}$/ui", $message['content'])) {$errors = true; $messageerror['content'] = 'Текст сообщения должен содержать более 10 символов';}
            $postattachments = $this->getRequest()->get('attachments');
            $message['attachments'] = array();
            if (is_array($postattachments))
            {
                foreach ($postattachments as $attachmentid)
                {
                    $fileent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumAttachments')->find($attachmentid);
                    if (!empty($fileent))
                    {
                        if (!file_exists('..'.$fileent->getContentFile())) {$errors = true; $message['attachments'][] = array('filename' => $fileent->getFileName(), 'filesize' => 0, 'id' => $attachmentid, 'error' => 'Файл не найден');}
                        else {$message['attachments'][] = array('filename' => $fileent->getFileName(), 'filesize' => filesize('..'.$fileent->getContentFile()), 'id' => intval($attachmentid), 'error' => '');}
                    }
                    unset($fileent);
                }
            }
            unset($postattachments);
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($message['content'], $messageent->getContent());
                $messageent->setContent($message['content']);
                $messageent->setIsVisible($message['isVisible']);
                if ($message['moderatorMessage'] != '')
                {
                    $messageent->setModeratorId($this->getUser()->getId());
                    $messageent->setModeratorMessage($message['moderatorMessage']);
                }
                $messageent->setModifyDate(new \DateTime('now'));
                $em->flush();
                // Сохранение вложений
                $attachIds = array();
                foreach ($message['attachments'] as $attach)
                {
                    $fileent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumAttachments')->find($attach['id']);
                    if (!empty($fileent))
                    {
                        $attachIds[] = $attach['id'];
                        $fileent->setForumId($forument->getId());
                        $fileent->setMessageId($messageent->getId());
                        $em->flush();
                    }
                    unset($fileent);
                }
                if (count($attachIds) > 0) $query = $em->createQuery('UPDATE ForumForumBundle:ForumAttachments a SET a.forumId = 0, a.messageId = 0 WHERE a.forumId = :forumid AND a.messageId = :id AND a.id NOT IN (:attachids)')->setParameter('forumid', $forument->getId())->setParameter('id', $id)->setParameter('attachids', $attachIds);
                                      else $query = $em->createQuery('UPDATE ForumForumBundle:ForumAttachments a SET a.forumId = 0, a.messageId = 0 WHERE a.forumId = :forumid AND a.messageId = :id')->setParameter('forumid', $forument->getId())->setParameter('id', $id);
                $query->execute();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование сообщения форума',
                    'message'=>'Сообщение форума успешно изменено',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('forum_forum_messages_edit').'?id='.$id,'Вернуться к списку сообщений форума'=>$this->get('router')->generate('forum_forum_messages_list').'?id='.$forument->getId())
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('ForumForumBundle:Default:messageEdit.html.twig', array(
            'id' => $id,
            'forum' => $forument,
            'message' => $message,
            'messageerror' => $messageerror,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// AJAX-контроллер
// *******************************************    
    
    public function forumsMessageAjaxAction()
    {
        //$tab = intval($this->getRequest()->get('tab'));
        $tab = 0;
        if ($this->getUser()->checkAccess('forum_edit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр ответов форума',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->getRequest()->get('action');
        $errors = array();
        if ($action == 'deletemessage')
        {
            $cmsservices = $this->container->getServiceIds();
            $check = $this->getRequest()->get('check');
            $forumids = array();
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $messageent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumMessages')->find($key);
                    if (!empty($messageent))
                    {
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $messageent->getContent());
                        if (!in_array($messageent->getForumId(), $forumids)) $forumids[] = $messageent->getForumId();
                        $em->remove($messageent);
                        $em->flush();
                        $query = $em->createQuery('SELECT a.contentFile FROM ForumForumBundle:ForumAttachments a WHERE a.messageId = :id')->setParameter('id', $key);
                        $files = $query->getResult();
                        if (is_array($files))
                        {
                            foreach ($files as $file) @unlink('..'.$file['contentFile']);
                        }
                        $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumAttachments a WHERE a.messageId = :id')->setParameter('id', $key);
                        $query->execute();
                        unset($messageent); 
                    }
                }
            if (count($forumids) > 0)
            {
                foreach ($forumids as $forumid)
                {
                    $query = $em->createQuery('SELECT m.id, m.createDate FROM ForumForumBundle:ForumMessages m WHERE m.forumId = :id ORDER BY m.createDate DESC')->setParameter('id', $forumid)->setMaxResults(1);
                    $result = $query->getResult();
                    if (isset($result[0]['id']) && ($result[0]['id'] != null)) $query = $em->createQuery('UPDATE ForumForumBundle:Forums f SET f.lastMessageId = :messageid, f.lastMessageDate = :messagedate WHERE f.id = :id')->setParameter('id', $forumid)->setParameter('messageid', $result[0]['id'])->setParameter('messagedate', $result[0]['createDate']);
                                                                          else $query = $em->createQuery('UPDATE ForumForumBundle:Forums f SET f.lastMessageId = 0, f.lastMessageDate = f.createDate WHERE f.id = :id')->setParameter('id', $forumid);
                    $query->execute();
                }
            }
        }
        if ($action == 'blockedmessage')
        {
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $messageent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumMessages')->find($key);
                    if (!empty($messageent))
                    {
                        $messageent->setIsVisible(0);
                        $em->flush();
                        unset($messageent);    
                    }
                }
        }
        if ($action == 'unblockedmessage')
        {
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $messageent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumMessages')->find($key);
                    if (!empty($messageent))
                    {
                        $messageent->setIsVisible(1);
                        $em->flush();
                        unset($messageent);    
                    }
                }
        }

        $id = $this->getRequest()->getSession()->get('forum_forum_messages_list_id');
        $page0 = $this->getRequest()->getSession()->get('forum_forum_messages_list_page0');
        $sort0 = $this->getRequest()->getSession()->get('forum_forum_messages_list_sort0');
        $search0 = $this->getRequest()->getSession()->get('forum_forum_messages_list_search0');
        $forument = $this->getDoctrine()->getRepository('ForumForumBundle:Forums')->find($id);
        if (empty($forument))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр ответов форума',
                'message'=>'Форум не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к списку форумов'=>$this->get('router')->generate('forum_forum_topic_list'))
            ));
        }
        $oldid = $this->getRequest()->getSession()->get('forum_forum_messages_list_oldid');
        if ($id != $oldid)
        {
            $page0 = null;
            $sort0 = null;
            $search0 = null;
            $this->getRequest()->getSession()->set('forum_forum_messages_list_oldid', $id);
            $this->getRequest()->getSession()->set('forum_forum_messages_list_page0', $page0);
            $this->getRequest()->getSession()->set('forum_forum_messages_list_sort0', $sort0);
            $this->getRequest()->getSession()->set('forum_forum_messages_list_search0', $search0);
        }
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        // Поиск контента для 1 таба (текстовые страницы)
        $query = $em->createQuery('SELECT count(m.id) as messagecount FROM ForumForumBundle:ForumMessages m '.
                                  'WHERE m.content like :search AND m.forumId = :id')->setParameter('search', '%'.$search0.'%')->setParameter('id', $id);
        $messagecount = $query->getResult();
        if (!empty($messagecount)) $messagecount = $messagecount[0]['messagecount']; else $messagecount = 0;
        $pagecount0 = ceil($messagecount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY m.createDate ASC';
        if ($sort0 == 1) $sortsql = 'ORDER BY m.createDate DESC';
        if ($sort0 == 2) $sortsql = 'ORDER BY m.isVisible ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY m.isVisible DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY m.content ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY m.content DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        if ($sort0 == 8) $sortsql = 'ORDER BY um.fullName ASC';
        if ($sort0 == 9) $sortsql = 'ORDER BY um.fullName DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT m.id, m.content, m.createDate, m.modifyDate, m.isVisible, u.fullName, um.fullName as moderatorFullName FROM ForumForumBundle:ForumMessages m '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = m.createrId '.
                                  'LEFT JOIN BasicCmsBundle:Users um WITH um.id = m.moderatorId '.
                                  'WHERE m.content like :search AND m.forumId = :id '.$sortsql)->setParameter('search', '%'.$search0.'%')->setParameter('id', $id)->setFirstResult($start)->setMaxResults(20);
        $messages0 = $query->getResult();
        return $this->render('ForumForumBundle:Default:messageListTab1.html.twig', array(
            'forum' => $forument,
            'messages0' => $messages0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'pagecount0' => $pagecount0,
            'errors0' => $errors
            
        ));
    }
    
// *******************************************
// Фронт-контроллер загрузки
// *******************************************    
    
    public function forumsFrontAjaxAttachmentAction() 
    {
        $em = $this->getDoctrine()->getEntityManager();
         $query = $em->createQuery('SELECT a.id, a.contentFile FROM ForumForumBundle:ForumAttachments a WHERE a.forumId = 0 AND a.messageId = 0 AND a.downloadDate < :date')->setParameter('date', new \DateTime('1 days ago'))->setMaxResults(10);
         $files = $query->getResult();
         if (is_array($files))
         {
             $removeids = array();
             foreach ($files as $file)
             {
                 @unlink('..'.$file['contentFile']);
                 $removeids[] = $file['id'];
             }
             if (count($removeids) > 0)
             {
                 $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $removeids);
                 $query->execute();
             }
         }
         unset($files);
        $files = $this->getRequest()->files->get('files');
        $answer = array();
        if (is_array($files))
        {
            foreach ($files as $file)
            {
                $tmpfile = $file->getPathName();
                $basepath = '../secured/forum/';
                $name = 'f_'.md5($tmpfile.time()).'.dat';
                if (move_uploaded_file($tmpfile, $basepath . $name)) 
                {
                    $fileent = new \Forum\ForumBundle\Entity\ForumAttachments();
                    $fileent->setContentFile('/secured/forum/'.$name);
                    $fileent->setDownloadDate(new \DateTime('now'));
                    $fileent->setFileName($file->getClientOriginalName());
                    $fileent->setForumId(0);
                    $fileent->setIsImage(0);
                    $fileent->setMessageId(0);
                    if (@getimagesize($basepath.$name)) $fileent->setIsImage(1);
                    $em->persist($fileent);
                    $em->flush();
                    $answer[] = array('id' => $fileent->getId(), 'filename' => $file->getClientOriginalName(), 'filesize' => filesize($basepath.$name), 'filesizeformat' => number_format(filesize($basepath.$name) / 1024, 0, ',', ' '), 'error' => '');
                } else $answer[] = array('id' => '', 'filename' => $file->getClientOriginalName(), 'filesize' => '', 'filesizeformat' => '', 'error' => 'Ошибка загрузки файла');
            }
        }
        return new Response(json_encode($answer));
    }

    public function forumsFrontAjaxAvatarAction() 
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
            $basepath = '/images/forum/';
            $name = 'f_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
            if (move_uploaded_file($tmpfile, '..'.$basepath.$name)) 
            {
                $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $file->getClientOriginalName());
                return new Response(json_encode(array('file' => $basepath.$name, 'error' => '')));
            }
        }
        return new Response(json_encode(array('file' => '', 'error' => 'format')));
    }
    
// *******************************************
// Фронт-контроллер скачивания
// *******************************************    
    
    public function forumsFrontDownloadAttachmentAction() 
    {
        $id = intval($this->getRequest()->get('id'));
        $fileent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumAttachments')->find($id);
        if (empty($fileent)) return new Response('File not found', 404);
        $forument = $this->getDoctrine()->getRepository('ForumForumBundle:Forums')->find($fileent->getForumId());
        // Проверить доступ к файлу
        if ((($fileent->getIsImage() == 0) && ($forument->getOnlyAutorizedDownload() != 0)) || (($fileent->getIsImage() != 0) && ($forument->getOnlyAutorizedImage() != 0)))
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
// Фронт-контроллер загрузки к комментарию
// *******************************************    
    
    public function forumsFrontAjaxCommentAttachmentAction() 
    {
        $em = $this->getDoctrine()->getEntityManager();
         $query = $em->createQuery('SELECT a.id, a.contentFile FROM ForumForumBundle:ForumCommentAttachments a WHERE a.commentId = 0 AND a.downloadDate < :date')->setParameter('date', new \DateTime('1 days ago'))->setMaxResults(10);
         $files = $query->getResult();
         if (is_array($files))
         {
             $removeids = array();
             foreach ($files as $file)
             {
                 @unlink('..'.$file['contentFile']);
                 $removeids[] = $file['id'];
             }
             if (count($removeids) > 0)
             {
                 $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumCommentAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $removeids);
                 $query->execute();
             }
         }
         unset($files);
        $files = $this->getRequest()->files->get('files');
        $answer = array();
        if (is_array($files))
        {
            foreach ($files as $file)
            {
                $tmpfile = $file->getPathName();
                $basepath = '../secured/forum/';
                $name = 'f_'.md5($tmpfile.time()).'.dat';
                if (move_uploaded_file($tmpfile, $basepath . $name)) 
                {
                    $fileent = new \Forum\ForumBundle\Entity\ForumCommentAttachments();
                    $fileent->setContentFile('/secured/forum/'.$name);
                    $fileent->setDownloadDate(new \DateTime('now'));
                    $fileent->setFileName($file->getClientOriginalName());
                    $fileent->setIsImage(0);
                    $fileent->setCommentId(0);
                    if (@getimagesize($basepath.$name)) $fileent->setIsImage(1);
                    $em->persist($fileent);
                    $em->flush();
                    $answer[] = array('id' => $fileent->getId(), 'filename' => $file->getClientOriginalName(), 'filesize' => filesize($basepath.$name), 'filesizeformat' => number_format(filesize($basepath.$name) / 1024, 0, ',', ' '), 'error' => '');
                } else $answer[] = array('id' => '', 'filename' => $file->getClientOriginalName(), 'filesize' => '', 'filesizeformat' => '', 'error' => 'Ошибка загрузки файла');
            }
        }
        return new Response(json_encode($answer));
    }
    
    
    
// *******************************************
// Загрузка к комментарию
// *******************************************    
    
    public function forumsCommentAjaxAttachmentAction() 
    {
        $em = $this->getDoctrine()->getEntityManager();
         $query = $em->createQuery('SELECT a.id, a.contentFile FROM ForumForumBundle:ForumCommentAttachments a WHERE a.commentId = 0 AND a.downloadDate < :date')->setParameter('date', new \DateTime('1 days ago'))->setMaxResults(10);
         $files = $query->getResult();
         if (is_array($files))
         {
             $removeids = array();
             foreach ($files as $file)
             {
                 @unlink('..'.$file['contentFile']);
                 $removeids[] = $file['id'];
             }
             if (count($removeids) > 0)
             {
                 $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumCommentAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $removeids);
                 $query->execute();
             }
         }
         unset($files);
        $userId = $this->getUser()->getId();
        $files = $this->getRequest()->files->get('files');
        $answer = array();
        if (is_array($files))
        {
            foreach ($files as $file)
            {
                $tmpfile = $file->getPathName();
                $basepath = '../secured/forum/';
                $name = $this->getUser()->getId().'_'.md5(time()).'.dat';
                if (move_uploaded_file($tmpfile, $basepath . $name)) 
                {
                    $fileent = new \Forum\ForumBundle\Entity\ForumCommentAttachments();
                    $fileent->setContentFile('/secured/forum/'.$name);
                    $fileent->setDownloadDate(new \DateTime('now'));
                    $fileent->setFileName($file->getClientOriginalName());
                    $fileent->setIsImage(0);
                    $fileent->setCommentId(0);
                    if (@getimagesize($basepath.$name)) $fileent->setIsImage(1);
                    $em->persist($fileent);
                    $em->flush();
                    $answer[] = array('id' => $fileent->getId(), 'filename' => $file->getClientOriginalName(), 'filesize' => number_format(filesize($basepath.$name) / 1024, 0, ',', ' '), 'error' => '');
                } else $answer[] = array('id' => '', 'filename' => $file->getClientOriginalName(), 'filesize' => '', 'error' => 'Ошибка загрузки файла');
            }
        }
        return new Response(json_encode($answer));
    }
    
    
// *******************************************
// Редактирование комментария к странице
// *******************************************    
    public function forumsCommentEditAction()
    {
        $this->getRequest()->getSession()->set('forum_forum_topic_list_tab', 3);
        $id = intval($this->getRequest()->get('id'));
        $messageent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumComments')->find($id);
        if (empty($messageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование комментария к странице сайта',
                'message'=>'Сообщение не найдено', 
                'error'=>true,
                'paths'=>array('Вернуться к списку форумов'=>$this->get('router')->generate('forum_forum_topic_list'))
            ));
        }
        if ($this->getUser()->checkAccess('forum_commentsedit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование комментария к странице сайта',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        if ($messageent->getModeratorId() != null) $moderatorent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($messageent->getModeratorId()); else $moderatorent = null;
        
        $em = $this->getDoctrine()->getEntityManager();
        
        $message = array();
        $messageerror = array();
        
        $message['content'] = $messageent->getContent();
        $message['attachments'] = array();
        $message['isVisible'] = $messageent->getIsVisible();
        $message['moderatorNamePrev'] = (!empty($moderatorent) ? $moderatorent->getFullName() : '');
        $message['moderatorMessagePrev'] = $messageent->getModeratorMessage();
        $message['moderatorMessage'] = '';
        
        $messageerror['content'] = '';
        $messageerror['attachments'] = '';
        $messageerror['isVisible'] = '';
        $messageerror['moderatorMessage'] = '';
        
        $query = $em->createQuery('SELECT f.id, f.fileName as filename, f.contentFile, 0 as filesize, \'\' as error FROM ForumForumBundle:ForumCommentAttachments f '.
                                  'WHERE f.commentId = :id')->setParameter('id', $id);
        $message['attachments'] = $query->getResult();
        if (!is_array($message['attachments'])) $message['attachments'] = array();
        foreach ($message['attachments'] as &$attach)
        {
            $attach['filesize'] = @filesize('..'.$attach['contentFile']);
        }
        unset($attach);
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postmessage = $this->getRequest()->get('message');
            if (isset($postmessage['content'])) $message['content'] = $postmessage['content'];
            if (isset($postmessage['moderatorMessage'])) $message['moderatorMessage'] = $postmessage['moderatorMessage'];
            if (isset($postmessage['isVisible'])) $message['isVisible'] = intval($postmessage['isVisible']); else $message['isVisible'] = 0;
            unset($postmessage);
            if (!preg_match("/^[\s\S]{10,}$/ui", $message['content'])) {$errors = true; $messageerror['content'] = 'Текст сообщения должен содержать более 10 символов';}
            $postattachments = $this->getRequest()->get('attachments');
            $message['attachments'] = array();
            if (is_array($postattachments))
            {
                foreach ($postattachments as $attachmentid)
                {
                    $fileent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumCommentAttachments')->find($attachmentid);
                    if (!empty($fileent))
                    {
                        if (!file_exists('..'.$fileent->getContentFile())) {$errors = true; $message['attachments'][] = array('filename' => $fileent->getFileName(), 'filesize' => 0, 'id' => $attachmentid, 'error' => 'Файл не найден');}
                        else {$message['attachments'][] = array('filename' => $fileent->getFileName(), 'filesize' => filesize('..'.$fileent->getContentFile()), 'id' => intval($attachmentid), 'error' => '');}
                    }
                    unset($fileent);
                }
            }
            unset($postattachments);
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($message['content'], $messageent->getContent());
                $messageent->setContent($message['content']);
                $messageent->setIsVisible($message['isVisible']);
                if ($message['moderatorMessage'] != '')
                {
                    $messageent->setModeratorId($this->getUser()->getId());
                    $messageent->setModeratorMessage($message['moderatorMessage']);
                }
                $messageent->setModifyDate(new \DateTime('now'));
                $em->flush();
                // Сохранение вложений
                $attachIds = array();
                foreach ($message['attachments'] as $attach)
                {
                    $fileent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumCommentAttachments')->find($attach['id']);
                    if (!empty($fileent))
                    {
                        $attachIds[] = $attach['id'];
                        $fileent->setCommentId($messageent->getId());
                        $em->flush();
                    }
                    unset($fileent);
                }
                if (count($attachIds) > 0) $query = $em->createQuery('UPDATE ForumForumBundle:ForumCommentAttachments a SET a.commentId = 0 WHERE a.commentId = :id AND a.id NOT IN (:attachids)')->setParameter('id', $id)->setParameter('attachids', $attachIds);
                                      else $query = $em->createQuery('UPDATE ForumForumBundle:ForumCommentAttachments a SET a.commentId = 0 WHERE a.commentId = :id')->setParameter('id', $id);
                $query->execute();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование комментария к странице сайта',
                    'message'=>'Комментарий успешно изменен',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('forum_forum_comments_edit').'?id='.$id,'Вернуться к списку форумов'=>$this->get('router')->generate('forum_forum_topic_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('ForumForumBundle:Default:commentEdit.html.twig', array(
            'id' => $id,
            'message' => $message,
            'messageerror' => $messageerror,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// Фронт-контроллер скачивания
// *******************************************    
    
    public function forumsFrontDownloadCommentAttachmentAction() 
    {
        $id = intval($this->getRequest()->get('id'));
        $fileent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumCommentAttachments')->find($id);
        if (empty($fileent)) return new Response('File not found', 404);
        $messageent = $this->getDoctrine()->getRepository('ForumForumBundle:ForumComments')->find($fileent->getCommentId());
        if (empty($messageent)) return new Response('File not found', 404);
        $seopageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->find($messageent->getSeoPageId());
        if (empty($seopageent)) return new Response('File not found', 404);
        $moduleids = @explode(',', $seopageent->getModules());
        if (is_array($moduleids) && (count($moduleids) > 0))
        {
            $query = $this->getDoctrine()->getEntityManager()->createQuery('SELECT m.parameters FROM BasicCmsBundle:Modules m WHERE m.enabled != 0 AND m.id in (:moduleids) AND m.objectName = \'object.forum\' AND m.moduleType = \'comments\'')->setParameter('moduleids', $moduleids);
            $module = $query->getResult();
            if (isset($module[0]['parameters'])) $module = @unserialize($module[0]['parameters']); else return new Response('File not found', 404);
        } else return new Response('File not found', 404);
        // Проверить доступ к файлу
        if (!isset($module['onlyAutorizedDownload']) || !isset($module['onlyAutorizedImage'])) return new Response('File not found', 404);
        if ((($fileent->getIsImage() == 0) && ($module['onlyAutorizedDownload'] != 0)) || (($fileent->getIsImage() != 0) && ($module['onlyAutorizedImage'] != 0)))
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
    
    
    
    
}
