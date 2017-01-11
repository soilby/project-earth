<?php

namespace Forum\PrivateBundle\Controller;

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
// Список сообщений и страниц
// *******************************************    
    public function privateListAction()
    {
        if ($this->getUser()->checkAccess('forum_private') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Личные сообщения',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $tab = $this->getRequest()->get('tab');
        if ($tab === null) $tab = $this->getRequest()->getSession()->get('forum_private_private_list_tab');
                      else $this->getRequest()->getSession()->set('forum_private_private_list_tab', $tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 1) $tab = 1;
        // Таб 1
        $page0 = $this->getRequest()->get('page0');
        $sort0 = $this->getRequest()->get('sort0');
        $search0 = $this->getRequest()->get('search0');
        if ($page0 === null) $page0 = $this->getRequest()->getSession()->get('forum_private_private_list_page0');
                        else $this->getRequest()->getSession()->set('forum_private_private_list_page0', $page0);
        if ($sort0 === null) $sort0 = $this->getRequest()->getSession()->get('forum_private_private_list_sort0');
                        else $this->getRequest()->getSession()->set('forum_private_private_list_sort0', $sort0);
        if ($search0 === null) $search0 = $this->getRequest()->getSession()->get('forum_private_private_list_search0');
                          else $this->getRequest()->getSession()->set('forum_private_private_list_search0', $search0);
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        // Поиск контента для 1 таба (текстовые страницы)
        $query = $em->createQuery('SELECT count(m.id) as privatecount FROM ForumPrivateBundle:ForumPrivates m '.
                                  'WHERE m.title like :search ')->setParameter('search', '%'.$search0.'%');
        $privatecount = $query->getResult();
        if (!empty($privatecount)) $privatecount = $privatecount[0]['privatecount']; else $privatecount = 0;
        $pagecount0 = ceil($privatecount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY m.createDate DESC';
        if ($sort0 == 1) $sortsql = 'ORDER BY m.createDate ASC';
        if ($sort0 == 2) $sortsql = 'ORDER BY m.title ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY m.title DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY u.fullName DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY ut.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY ut.fullName DESC';
        if ($sort0 == 8) $sortsql = 'ORDER BY m.viewed ASC';
        if ($sort0 == 9) $sortsql = 'ORDER BY m.viewed DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT m.id, m.title, m.createDate, m.modifyDate, m.viewed, u.fullName as createrName, ut.fullName as targetName FROM ForumPrivateBundle:ForumPrivates m '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = m.createrId '.
                                  'LEFT JOIN BasicCmsBundle:Users ut WITH ut.id = m.targetId '.
                                  'WHERE m.title like :search '.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $privates0 = $query->getResult();
        // Таб 2
        $query = $em->createQuery('SELECT p.id, p.title, p.enabled, sp.url FROM ForumPrivateBundle:ForumPrivatePages p LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'private\' AND sp.contentId = p.id ORDER BY p.id');
        $privatepages1 = $query->getResult();
        foreach ($privatepages1 as &$privatepage) $privatepage['title'] = $this->get('cms.cmsManager')->decodeLocalString($privatepage['title'],'default',true);
        unset($privatepage);
        return $this->render('ForumPrivateBundle:Default:privateList.html.twig', array(
            'privates0' => $privates0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'pagecount0' => $pagecount0,
            'privatepages1' => $privatepages1,
            'tab' => $tab
        ));
        
    }
    
// *******************************************
// Просмотр сообщения
// *******************************************    
    public function privateViewAction()
    {
        $this->getRequest()->getSession()->set('forum_private_private_list_tab', 0);
        $id = intval($this->getRequest()->get('id'));
        $privateent = $this->getDoctrine()->getRepository('ForumPrivateBundle:ForumPrivates')->find($id);
        if (empty($privateent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр личного сообщения',
                'message'=>'Сообщение не найдено', 
                'error'=>true,
                'paths'=>array('Вернуться к списку личных сообщений'=>$this->get('router')->generate('forum_private_private_list'))
            ));
        }
        if ($this->getUser()->checkAccess('forum_private') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр личного сообщения',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        if (($privateent->getTargetId() == $this->getUser()->getId()) && ($privateent->getViewed() == 0))
        {
            $privateent->setViewed(1);
            $privateent->setViewDate(new \DateTime('now'));
            $em->flush();
        }
        $createrent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($privateent->getCreaterId());
        $targetent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($privateent->getTargetId());
        $query = $em->createQuery('SELECT f.id, f.fileName as filename, f.contentFile, 0 as filesize, \'\' as error FROM ForumPrivateBundle:ForumPrivateAttachments f '.
                                  'WHERE f.messageId = :id')->setParameter('id', $id);
        $attachments = $query->getResult();
        if (!is_array($attachments)) $attachments = array();
        foreach ($attachments as &$attach)
        {
            $attach['filesize'] = @filesize('..'.$attach['contentFile']);
        }
        unset($attach);
        return $this->render('ForumPrivateBundle:Default:privateView.html.twig', array(
            'id' => $id,
            'private' => $privateent,
            'creater' => $createrent,
            'target' => $targetent,
            'attachments' => $attachments,
            'activetab' => 1
        ));
        
    }

// *******************************************
// Создание страницы личных сообщений
// *******************************************    
    public function privatePageCreateAction()
    {
        $this->getRequest()->getSession()->set('forum_private_private_list_tab', 1);
        if ($this->getUser()->checkAccess('forum_private') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой страницы личных сообщений',
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
        $createpage['messageAllowTags'] = '';
        $createpage['messageAllowStyleProp'] = '';
        $createpage['messageReplaceUrls'] = '';
        $createpage['messagesInPage'] = '';
        $createpage['messageMode'] = '';
        
        $createpageerror['title'] = array();
        $createpageerror['title']['default'] = '';
        foreach ($locales as $locale) $createpageerror['title'][$locale['shortName']] = '';
        $createpageerror['enabled'] = '';
        $createpageerror['captchaEnabled'] = '';
        $createpageerror['messageAllowTags'] = '';
        $createpageerror['messageAllowStyleProp'] = '';
        $createpageerror['messageReplaceUrls'] = '';
        $createpageerror['messagesInPage'] = '';
        $createpageerror['messageMode'] = '';
        
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
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.forum','private');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts); $regpage['seopageLayout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.forum.private');
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
            $postcreatepage = $this->getRequest()->get('createpage');
            if (isset($postcreatepage['title']['default'])) $createpage['title']['default'] = $postcreatepage['title']['default'];
            foreach ($locales as $locale) if (isset($postcreatepage['title'][$locale['shortName']])) $createpage['title'][$locale['shortName']] = $postcreatepage['title'][$locale['shortName']];
            if (isset($postcreatepage['enabled'])) $createpage['enabled'] = intval($postcreatepage['enabled']); else $createpage['enabled'] = 0;
            if (isset($postcreatepage['captchaEnabled'])) $createpage['captchaEnabled'] = intval($postcreatepage['captchaEnabled']); else $createpage['captchaEnabled'] = 0;
            if (isset($postcreatepage['messageAllowStyleProp'])) $createpage['messageAllowStyleProp'] = intval($postcreatepage['messageAllowStyleProp']); else $createpage['messageAllowStyleProp'] = 0;
            if (isset($postcreatepage['messageReplaceUrls'])) $createpage['messageReplaceUrls'] = intval($postcreatepage['messageReplaceUrls']); else $createpage['messageReplaceUrls'] = 0;
            if (isset($postcreatepage['messageAllowTags'])) $createpage['messageAllowTags'] = $postcreatepage['messageAllowTags'];
            if (isset($postcreatepage['messagesInPage'])) $createpage['messagesInPage'] = $postcreatepage['messagesInPage'];
            if (isset($postcreatepage['messageMode'])) $createpage['messageMode'] = intval($postcreatepage['messageMode']); else $createpage['messageMode'] = 0;
            unset($postcreatepage);
            if (!preg_match("/^.{3,}$/ui", $createpage['title']['default'])) {$errors = true; $createpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $createpage['title'][$locale['shortName']])) {$errors = true; $createpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if (!preg_match("/^([A-z0-9]{1,10}(\s*\,\s*[A-z0-9]{1,10})*)?$/ui", $createpage['messageAllowTags'])) {$errors = true; $createpageerror['messageAllowTags'] = 'Тэги должны состоять из 1-10 латинских букв и разделяться запятыми';}
            if ((!preg_match("/^[\d]{2,3}$/ui", $createpage['messagesInPage'])) || (intval($createpage['messagesInPage']) < 10) || (intval($createpage['messagesInPage']) > 100)) {$errors = true; $createpageerror['messagesInPage'] = 'Должно быть указано число от 10 до 100';}
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
                $createpageent = new \Forum\PrivateBundle\Entity\ForumPrivatePages();
                $createpageent->setCaptchaEnabled($createpage['captchaEnabled']);
                $createpageent->setEnabled($createpage['enabled']);
                $createpageent->setMessageAllowStyleProp($createpage['messageAllowStyleProp']);
                $createpageent->setMessageAllowTags($createpage['messageAllowTags']);
                $createpageent->setMessagesInPage($createpage['messagesInPage']);
                $createpageent->setMessageReplaceUrls($createpage['messageReplaceUrls']);
                $createpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($createpage['title']));
                $createpageent->setMessageMode($createpage['messageMode']);
                $em->persist($createpageent);
                $em->flush();
                
                $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница личных сообщений '.$this->get('cms.cmsManager')->decodeLocalString($createpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.forum');
                $pageent->setContentId($createpageent->getId());
                $pageent->setContentAction('private');
                $createpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->persist($pageent);
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой страницы личных сообщений',
                    'message'=>'Страница личных сообщений успешно создана',
                    'paths'=>array('Создать еще одну страницу личных сообщений'=>$this->get('router')->generate('forum_private_private_pagecreate'),'Вернуться к списку личных сообщений'=>$this->get('router')->generate('forum_private_private_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('ForumPrivateBundle:Default:privatePageCreate.html.twig', array(
            'createpage' => $createpage,
            'createpageerror' => $createpageerror,
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
// Редактирование страницы личных сообщений
// *******************************************    
    public function privatePageEditAction()
    {
        $this->getRequest()->getSession()->set('forum_private_private_list_tab', 1);
        $id = intval($this->getRequest()->get('id'));
        $createpageent = $this->getDoctrine()->getRepository('ForumPrivateBundle:ForumPrivatePages')->find($id);
        if (empty($createpageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы личных сообщений',
                'message'=>'Страница личных сообщений не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку личных сообщений'=>$this->get('router')->generate('forum_private_private_list'))
            ));
        }
        if ($this->getUser()->checkAccess('forum_private') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы личных сообщений',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.forum','contentId'=>$id,'contentAction'=>'private'));
        
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
        $createpage['messageAllowTags'] = $createpageent->getMessageAllowTags();
        $createpage['messageAllowStyleProp'] = $createpageent->getMessageAllowStyleProp();
        $createpage['messageReplaceUrls'] = $createpageent->getMessageReplaceUrls();
        $createpage['messagesInPage'] = $createpageent->getMessagesInPage();
        $createpage['messageMode'] = $createpageent->getMessageMode();
        
        $createpageerror['title'] = array();
        $createpageerror['title']['default'] = '';
        foreach ($locales as $locale) $createpageerror['title'][$locale['shortName']] = '';
        $createpageerror['enabled'] = '';
        $createpageerror['captchaEnabled'] = '';
        $createpageerror['messageAllowTags'] = '';
        $createpageerror['messageAllowStyleProp'] = '';
        $createpageerror['messageReplaceUrls'] = '';
        $createpageerror['messagesInPage'] = '';
        $createpageerror['messageMode'] = '';
        
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
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.forum','private');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (empty($pageent))
        {
            if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
            if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts); $regpage['seopageLayout'] = key($layouts);}
        }
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.forum.private');
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
            $postcreatepage = $this->getRequest()->get('createpage');
            if (isset($postcreatepage['title']['default'])) $createpage['title']['default'] = $postcreatepage['title']['default'];
            foreach ($locales as $locale) if (isset($postcreatepage['title'][$locale['shortName']])) $createpage['title'][$locale['shortName']] = $postcreatepage['title'][$locale['shortName']];
            if (isset($postcreatepage['enabled'])) $createpage['enabled'] = intval($postcreatepage['enabled']); else $createpage['enabled'] = 0;
            if (isset($postcreatepage['captchaEnabled'])) $createpage['captchaEnabled'] = intval($postcreatepage['captchaEnabled']); else $createpage['captchaEnabled'] = 0;
            if (isset($postcreatepage['messageAllowStyleProp'])) $createpage['messageAllowStyleProp'] = intval($postcreatepage['messageAllowStyleProp']); else $createpage['messageAllowStyleProp'] = 0;
            if (isset($postcreatepage['messageReplaceUrls'])) $createpage['messageReplaceUrls'] = intval($postcreatepage['messageReplaceUrls']); else $createpage['messageReplaceUrls'] = 0;
            if (isset($postcreatepage['messageAllowTags'])) $createpage['messageAllowTags'] = $postcreatepage['messageAllowTags'];
            if (isset($postcreatepage['messagesInPage'])) $createpage['messagesInPage'] = $postcreatepage['messagesInPage'];
            if (isset($postcreatepage['messageMode'])) $createpage['messageMode'] = intval($postcreatepage['messageMode']); else $createpage['messageMode'] = 0;
            unset($postcreatepage);
            if (!preg_match("/^.{3,}$/ui", $createpage['title']['default'])) {$errors = true; $createpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $createpage['title'][$locale['shortName']])) {$errors = true; $createpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if (!preg_match("/^([A-z0-9]{1,10}(\s*\,\s*[A-z0-9]{1,10})*)?$/ui", $createpage['messageAllowTags'])) {$errors = true; $createpageerror['messageAllowTags'] = 'Тэги должны состоять из 1-10 латинских букв и разделяться запятыми';}
            if ((!preg_match("/^[\d]{2,3}$/ui", $createpage['messagesInPage'])) || (intval($createpage['messagesInPage']) < 10) || (intval($createpage['messagesInPage']) > 100)) {$errors = true; $createpageerror['messagesInPage'] = 'Должно быть указано число от 10 до 100';}
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
                $createpageent->setCaptchaEnabled($createpage['captchaEnabled']);
                $createpageent->setEnabled($createpage['enabled']);
                $createpageent->setMessageAllowStyleProp($createpage['messageAllowStyleProp']);
                $createpageent->setMessageAllowTags($createpage['messageAllowTags']);
                $createpageent->setMessagesInPage($createpage['messagesInPage']);
                $createpageent->setMessageReplaceUrls($createpage['messageReplaceUrls']);
                $createpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($createpage['title']));
                $createpageent->setMessageMode($createpage['messageMode']);
                $em->flush();
                if (empty($pageent))
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $em->persist($pageent);
                }
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница личных сообщений '.$this->get('cms.cmsManager')->decodeLocalString($createpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.forum');
                $pageent->setContentId($createpageent->getId());
                $pageent->setContentAction('private');
                $createpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование страницы личных сообщений',
                    'message'=>'Данные страницы личных сообщений успешно сохранены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('forum_private_private_pageedit').'?id='.$id,'Вернуться к списку личных сообщений'=>$this->get('router')->generate('forum_private_private_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('ForumPrivateBundle:Default:privatePageEdit.html.twig', array(
            'id' => $id,
            'createpage' => $createpage,
            'createpageerror' => $createpageerror,
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
// AJAX-контроллер
// *******************************************    
    
    public function privateAjaxAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->getRequest()->get('action');
        $errors = array();
        $tab = intval($this->getRequest()->get('tab'));
        if ($tab == 0)
        {
            if ($this->getUser()->checkAccess('forum_private') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Личные сообщения',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            if ($action == 'deleteprivate')
            {
                $cmsservices = $this->container->getServiceIds();
                $check = $this->getRequest()->get('check');
                if ($check != null)
                foreach ($check as $key=>$val)
                    if ($val == 1)
                    {
                        $messageent = $this->getDoctrine()->getRepository('ForumPrivateBundle:ForumPrivates')->find($key);
                        if (!empty($messageent))
                        {
                            $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $messageent->getContent());
                            $em->remove($messageent);
                            $em->flush();
                            $query = $em->createQuery('SELECT a.contentFile FROM ForumPrivateBundle:ForumPrivateAttachments a WHERE a.messageId = :id')->setParameter('id', $key);
                            $files = $query->getResult();
                            if (is_array($files))
                            {
                                foreach ($files as $file) @unlink('..'.$file['contentFile']);
                            }
                            $query = $em->createQuery('DELETE FROM ForumPrivateBundle:ForumPrivateAttachments a WHERE a.messageId = :id')->setParameter('id', $key);
                            $query->execute();
                            unset($messageent); 
                        }
                    }
            }
            $page0 = $this->getRequest()->getSession()->get('forum_private_private_list_page0');
            $sort0 = $this->getRequest()->getSession()->get('forum_private_private_list_sort0');
            $search0 = $this->getRequest()->getSession()->get('forum_private_private_list_search0');
            $page0 = intval($page0);
            $sort0 = intval($sort0);
            $search0 = trim($search0);
            // Поиск контента для 1 таба (текстовые страницы)
            $query = $em->createQuery('SELECT count(m.id) as privatecount FROM ForumPrivateBundle:ForumPrivates m '.
                                      'WHERE m.title like :search ')->setParameter('search', '%'.$search0.'%');
            $privatecount = $query->getResult();
            if (!empty($privatecount)) $privatecount = $privatecount[0]['privatecount']; else $privatecount = 0;
            $pagecount0 = ceil($privatecount / 20);
            if ($pagecount0 < 1) $pagecount0 = 1;
            if ($page0 < 0) $page0 = 0;
            if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
            $sortsql = 'ORDER BY m.createDate DESC';
            if ($sort0 == 1) $sortsql = 'ORDER BY m.createDate ASC';
            if ($sort0 == 2) $sortsql = 'ORDER BY m.title ASC';
            if ($sort0 == 3) $sortsql = 'ORDER BY m.title DESC';
            if ($sort0 == 4) $sortsql = 'ORDER BY u.fullName ASC';
            if ($sort0 == 5) $sortsql = 'ORDER BY u.fullName DESC';
            if ($sort0 == 6) $sortsql = 'ORDER BY ut.fullName ASC';
            if ($sort0 == 7) $sortsql = 'ORDER BY ut.fullName DESC';
            if ($sort0 == 8) $sortsql = 'ORDER BY m.viewed ASC';
            if ($sort0 == 9) $sortsql = 'ORDER BY m.viewed DESC';
            $start = $page0 * 20;
            $query = $em->createQuery('SELECT m.id, m.title, m.createDate, m.modifyDate, m.viewed, u.fullName as createrName, ut.fullName as targetName FROM ForumPrivateBundle:ForumPrivates m '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = m.createrId '.
                                      'LEFT JOIN BasicCmsBundle:Users ut WITH ut.id = m.targetId '.
                                      'WHERE m.title like :search '.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
            $privates0 = $query->getResult();
            return $this->render('ForumPrivateBundle:Default:privateListTab1.html.twig', array(
                'privates0' => $privates0,
                'search0' => $search0,
                'page0' => $page0,
                'sort0' => $sort0,
                'pagecount0' => $pagecount0,
                'tab' => $tab,
                'errors0' => $errors
            ));
        }
        if ($tab == 1)
        {
            if ($this->getUser()->checkAccess('forum_private') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Личные сообщения',
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
                        $createpageent = $this->getDoctrine()->getRepository('ForumPrivateBundle:ForumPrivatePages')->find($key);
                        if (!empty($createpageent))
                        {
                            $em->remove($createpageent);
                            $em->flush();
                            $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.forum\' AND p.contentAction = \'private\' AND p.contentId = :id')->setParameter('id', $key);
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
                        $createpageent = $this->getDoctrine()->getRepository('ForumPrivateBundle:ForumPrivatePages')->find($key);
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
                        $createpageent = $this->getDoctrine()->getRepository('ForumPrivateBundle:ForumPrivatePages')->find($key);
                        if (!empty($createpageent))
                        {
                            $createpageent->setEnabled(1);
                            $em->flush();
                            unset($createpageent);    
                        }
                    }
            }
            // Таб 2
            $query = $em->createQuery('SELECT p.id, p.title, p.enabled, sp.url FROM ForumPrivateBundle:ForumPrivatePages p LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'private\' AND sp.contentId = p.id ORDER BY p.id');
            $privatepages1 = $query->getResult();
            foreach ($privatepages1 as &$privatepage) $privatepage['title'] = $this->get('cms.cmsManager')->decodeLocalString($privatepage['title'],'default',true);
            unset($privatepage);
            return $this->render('ForumPrivateBundle:Default:privateListTab2.html.twig', array(
                'privatepages1' => $privatepages1,
                'tab' => $tab,
                'errors1' => $errors
            ));
        }
    }
    
// *******************************************
// Фронт-контроллер скачивания
// *******************************************    
    
    public function privateFrontDownloadAttachmentAction() 
    {
        $id = intval($this->getRequest()->get('id'));
        $fileent = $this->getDoctrine()->getRepository('ForumPrivateBundle:ForumPrivateAttachments')->find($id);
        if (empty($fileent)) return new Response('File not found', 404);
        $privateent = $this->getDoctrine()->getRepository('ForumPrivateBundle:ForumPrivates')->find($fileent->getMessageId());
        // Проверить доступ к файлу
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
        if (($privateent->getCreaterId() != $userEntity->getId()) && ($privateent->getTargetId() != $userEntity->getId())) return new Response('Access denied', 403);
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
    }
    
// *******************************************
// Фронт-контроллер загрузки
// *******************************************    
    
    public function privateFrontAjaxAttachmentAction() 
    {
        $em = $this->getDoctrine()->getEntityManager();
         $query = $em->createQuery('SELECT a.id, a.contentFile FROM ForumPrivateBundle:ForumPrivateAttachments a WHERE a.messageId = 0 AND a.downloadDate < :date')->setParameter('date', new \DateTime('1 days ago'))->setMaxResults(10);
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
                 $query = $em->createQuery('DELETE FROM ForumPrivateBundle:ForumPrivateAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $removeids);
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
                $basepath = '../secured/forumprivate/';
                $name = 'f_'.md5($tmpfile.time()).'.dat';
                if (move_uploaded_file($tmpfile, $basepath . $name)) 
                {
                    $fileent = new \Forum\PrivateBundle\Entity\ForumPrivateAttachments();
                    $fileent->setContentFile('/secured/forumprivate/'.$name);
                    $fileent->setDownloadDate(new \DateTime('now'));
                    $fileent->setFileName($file->getClientOriginalName());
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
    
    
}
