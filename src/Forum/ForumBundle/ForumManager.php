<?php

namespace Forum\ForumBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class ForumManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'object.forum';
    }
    
    public function getDescription()
    {
        return 'Форум';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Форум', $this->container->get('router')->generate('forum_forum_topic_list'), 30, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('forum_list'));
        $manager->addAdminMenu('Создать новый форум', $this->container->get('router')->generate('forum_forum_topic_create'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('forum_new'), 'Форум');
        $manager->addAdminMenu('Список форумов', $this->container->get('router')->generate('forum_forum_topic_list'), 10, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('forum_list'), 'Форум');
        if ($this->container->has('object.taxonomy'))
        {
            $manager->addAdminMenu('Категории форума', $this->container->get('router')->generate('basic_cms_taxonomy_list').'?object=object.forum', 9999, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_list') | $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_listshow'), 'Форум');
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) $this->container->get($item)->registerMenu();

    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('forum_list','Просмотр списка форумов');
        $manager->addRole('forum_view','Просмотр настроек форумов');
        $manager->addRole('forum_new','Создание новых форумов');
        $manager->addRole('forum_edit','Редактирование настроек форумов');
        
        $manager->addRole('forum_createpage','Настройка страниц создания форумов');

        $manager->addRole('forum_commentsview','Просмотр комментариев к страницам');
        $manager->addRole('forum_commentsedit','Редактирование комментариев к страницам');
        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) $this->container->get($item)->registerRoles();
        
     }

     public function getContentTypes()
     {
        $contents = array('view' => 'Просмотр форума', 'create' => 'Создание нового форума');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) $this->container->get($item)->getContentTypes($contents);
        return $contents;
     }
     
     public function getTaxonomyType()
     {
         return true;
     }
        
     public function getTemplateTwig($contentType, $template)
     {
         if ($contentType == 'view') return 'ForumForumBundle:Front:forumview.html.twig';
         if ($contentType == 'create') return 'ForumForumBundle:Front:forumcreate.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) 
         {
             $result = $this->container->get($item)->getTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         if ($contentType == 'comments') return 'ForumForumBundle:Front:modulecomments.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) 
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
             // информация о форуме
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT f.id, f.title, f.description, f.avatar, f.content, f.template, f.createDate, f.modifyDate, f.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                       'f.lastMessageDate, f.isClosed, f.isImportant, f.isVisible, f.moderatorMessage, f.moderatorId, um.login as moderatorLogin, um.fullName as moderatorFullName, um.avatar as moderatorAvatar, '.
                                       'f.captchaEnabled, f.onlyAutorizedView, f.onlyAutorizedPost, f.onlyAutorizedDownload, f.onlyAutorizedImage, f.messageInPage FROM ForumForumBundle:Forums f '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.
                                       'LEFT JOIN BasicCmsBundle:Users um WITH um.id = f.moderatorId '.
                                       'WHERE f.id = :id AND f.enabled != 0')->setParameter('id', $contentId);
             $forum = $query->getResult();
             if (empty($forum)) return null;
             if (isset($forum[0])) $params = $forum[0]; else return null;
             if ((($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2)) && ($params['onlyAutorizedView'] != 0)) {$cmsManager->setErrorPageNumber(1); return null;}
             $params['attachments'] = array();
             $params['newFlag'] = 0;
             if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getPrevVisitDate() < $params['lastMessageDate'])) $params['newFlag'] = 1;
             // Определине прав
             $params['permissions'] = array();
             $params['permissions']['post'] = 1;
             $params['permissions']['download'] = 1;
             $params['permissions']['image'] = 1;
             $params['permissions']['moderator'] = 0;
             $params['permissions']['edit'] = 0;
             if (($params['isVisible'] != 0) && ($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2) && ($cmsManager->getCurrentUser()->getId() == $params['createrId'])) $params['permissions']['edit'] = 1;
             if ((($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2)) && ($params['onlyAutorizedPost'] != 0)) $params['permissions']['post'] = 0;
             if ((($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2)) && ($params['onlyAutorizedDownload'] != 0)) $params['permissions']['download'] = 0;
             if ((($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2)) && ($params['onlyAutorizedImage'] != 0)) $params['permissions']['image'] = 0;
             if ($params['isClosed'] != 0) $params['permissions']['post'] = 0;
             // модераторы форума
             $query = $em->createQuery('SELECT u.id, u.login, u.fullName, u.avatar FROM BasicCmsBundle:Users u '.
                                       'LEFT JOIN ForumForumBundle:ForumModerators fm WITH fm.moderatorId = u.id '.
                                       'WHERE u.blocked = 2 AND (fm.forumId = :id OR fm.categoryId IN ('.
                                       'SELECT t.id FROM BasicCmsBundle:Taxonomies t LEFT JOIN BasicCmsBundle:TaxonomiesLinks tl WITH tl.taxonomyId = t.id AND tl.itemId = :id '.
                                       'WHERE tl.id IS NOT NULL AND t.object = \'object.forum\''.
                                       ')) GROUP BY u.id')->setParameter('id', $contentId);
             $moderators = $query->getResult();
             if (!is_array($moderators)) $moderators = array();
             $params['moderators'] = $moderators;
             if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2)) 
             {
                 foreach ($moderators as $moderator) if ($cmsManager->getCurrentUser()->getId() == $moderator['id']) $params['permissions']['moderator'] = 1;
                 if ($cmsManager->getCurrentUser()->getUserType() == 'SUPERADMIN') $params['permissions']['moderator'] = 1;
                 if (($cmsManager->getCurrentUser()->getUserType() == 'ADMIN') && ($cmsManager->getCurrentUser()->checkAccess('forum_edit') != 0)) $params['permissions']['moderator'] = 1;
             }
             // Сообщения с правами на редактирование (+пагинация)
             $page = $this->container->get('request_stack')->getCurrentRequest()->get('page');
             $query = $em->createQuery('SELECT count(m.id) as messagecount FROM ForumForumBundle:ForumMessages m WHERE m.forumId = :id')->setParameter('id', $contentId);
             $messagecount = $query->getResult();
             if (isset($messagecount[0]['messagecount'])) $messagecount = $messagecount[0]['messagecount']; else $messagecount = 0;
             $pagecount = ceil($messagecount / $params['messageInPage']);
             if ($pagecount < 1) $pagecount = 1;
             if (($page === null) || ($page == 'last')) $page = $pagecount - 1;
             $page = intval($page);
             if ($page < 0) $page = 0;
             if ($page > ($pagecount - 1)) $page = $pagecount - 1;
             $query = $em->createQuery('SELECT m.id, m.createDate, m.modifyDate, m.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                       'm.isVisible, m.moderatorMessage, m.moderatorId, um.login as moderatorLogin, um.fullName as moderatorFullName, um.avatar as moderatorAvatar, '.
                                       'm.content FROM ForumForumBundle:ForumMessages m '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = m.createrId '.
                                       'LEFT JOIN BasicCmsBundle:Users um WITH um.id = m.moderatorId '.
                                       'WHERE m.forumId = :id ORDER BY m.createDate ASC, m.id ASC')->setParameter('id', $contentId)->setFirstResult($page * $params['messageInPage'])->setMaxResults($params['messageInPage']);
             $messages = $query->getResult();
             if (!is_array($messages)) $messsages = array();
             foreach ($messages as &$message)
             {
                 $message['newFlag'] = 0;
                 if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getPrevVisitDate() < $message['createDate'])) $message['newFlag'] = 1;
                 $message['attachments'] = array();
                 $message['permissions'] = array();
                 $message['permissions']['edit'] = 0;
                 if (($message['isVisible'] != 0) && ($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2) && ($cmsManager->getCurrentUser()->getId() == $message['createrId'])) $message['permissions']['edit'] = 1;
             }
             unset($message);
             $params['page'] = $page;
             $params['messageCount'] = $messagecount;
             $params['pageCount'] = $pagecount;
             $params['messages'] = $messages;
             unset($messages);
             // Найти вложения и добавить к сообщениям
             $messageIds = array(0);
             foreach ($params['messages'] as $message) $messageIds[] = $message['id'];
             $query = $em->createQuery('SELECT a.id, a.messageId, a.downloadDate, a.fileName, a.contentFile, a.isImage FROM ForumForumBundle:ForumAttachments a WHERE a.forumId = :id AND a.messageId IN (:messageids)')->setParameter('id', $contentId)->setParameter('messageids', $messageIds);
             $attachments = $query->getResult();
             if (!is_array($attachments)) $attachments = array();
             foreach ($attachments as &$attach)
             {
                 $attach['fileSize'] = @filesize('.'.$attach['contentFile']);
                 $attach['permissions'] = array();
                 $attach['permissions']['download'] = 0;
                 if (($attach['isImage'] != 0) && ($params['permissions']['image'] != 0)) $attach['permissions']['download'] = 1;
                 if (($attach['isImage'] == 0) && ($params['permissions']['download'] != 0)) $attach['permissions']['download'] = 1;
             }
             unset($attach);
             foreach ($attachments as $attach)
             {
                 if ($attach['messageId'] == 0) 
                 {
                     $params['attachments'][] = $attach; 
                 } else
                 {
                     foreach ($params['messages'] as &$message) if ($message['id'] == $attach['messageId']) $message['attachments'][] = $attach;
                     unset($message);
                 }
             }
             unset($attachments);
             // Скрыть сообщения для немодераторов
             if ($params['permissions']['moderator'] == 0)
             {
                 if ($params['isVisible'] == 0) $params['content'] = '';
                 foreach ($params['messages'] as &$message) if ($message['isVisible'] == 0) $message['content'] = '';
                 unset($message);
             }
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>'', 'description'=>'', 'robotindex'=>true);
             // Сообщение
             $params['postMessageValue'] = '';
             $params['postMessageError'] = '';
             $params['captchaPath'] = '';
             $params['captchaError'] = '';
             $params['postAttachments'] = array();
             $params['postAttachmentsError'] = '';
             $params['status'] = '';
             if ($params['captchaEnabled'] != 0)
             {
                 $params['captchaPath'] = $this->container->get('router')->generate('basic_cms_captcha').'?target=forum'.$params['id'];
                 $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_forum'.$params['id'], '');
             }
             if (isset($result['action']) && ($result['action'] == 'object.forum.post') && isset($result['actionId']) && ($result['actionId'] == $contentId))
             {
                 if ($result['status'] != 'OK')
                 {
                     $params['status'] = $result['status'];
                     if (isset($result['text'])) $params['postMessageValue'] = $result['text'];
                     if (isset($result['error'])) $params['postMessageError'] = $result['error'];
                     if (isset($result['captchaError'])) $params['captchaError'] = $result['captchaError'];
                     if (isset($result['attachmentsError'])) $params['attachmentsError'] = $result['attachmentsError'];
                     if (isset($result['attachments']) && (count($result['attachments']) > 0)) 
                     {
                         $query = $em->createQuery('SELECT a.id, a.messageId, a.downloadDate, a.fileName, a.contentFile, a.isImage FROM ForumForumBundle:ForumAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $result['attachments']);
                         $pattachments = $query->getResult();
                         foreach ($result['attachments'] as $attid)
                         {
                             foreach ($pattachments as $pattach)
                             {
                                 if ($pattach['id'] == $attid) $params['postAttachments'][] = array_merge ($pattach, array('fileSize' => @filesize('.'.$pattach['contentFile'])));
                             }
                         }
                         unset($pattachments);
                     }
                 } else $params['status'] = 'OK';
             }
             // Подготовка данных к редактированию сообщения
             $params['editStatus'] = '';
             $params['editMessageValue'] = $cmsManager->unclearHtmlCode($params['content']);
             $params['editMessageError'] = '';
             $params['editAttachments'] = $params['attachments'];
             $params['editAttachmentsError'] = '';
             if (isset($result['action']) && ($result['action'] == 'object.forum.edit') && isset($result['actionId']) && ($result['actionId'] == $contentId) && isset($result['actionMessageId']) && ($result['actionMessageId'] == 0))
             {
                 if ($result['status'] != 'OK')
                 {
                     $params['editStatus'] = $result['status'];
                     if (isset($result['text'])) $params['editMessageValue'] = $result['text'];
                     if (isset($result['error'])) $params['editMessageError'] = $result['error'];
                     if (isset($result['attachmentsError'])) $params['editAttachmentsError'] = $result['attachmentsError'];
                     if (isset($result['attachments']) && (count($result['attachments']) > 0)) 
                     {
                         $params['editAttachments'] = array();
                         $query = $em->createQuery('SELECT a.id, a.messageId, a.downloadDate, a.fileName, a.contentFile, a.isImage FROM ForumForumBundle:ForumAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $result['attachments']);
                         $pattachments = $query->getResult();
                         foreach ($result['attachments'] as $attid)
                         {
                             foreach ($pattachments as $pattach)
                             {
                                 if ($pattach['id'] == $attid) $params['editAttachments'][] = array_merge ($pattach, array('fileSize' => @filesize('.'.$pattach['contentFile'])));
                             }
                         }
                         unset($pattachments);
                     }
                 } else $params['editStatus'] = 'OK';
             }
             $params['moderateStatus'] = '';
             $params['moderateTextValue'] = '';
             $params['moderateTextError'] = '';
             $params['moderateHideValue'] = '';
             $params['moderateBanValue'] = '';
             if (isset($result['action']) && ($result['action'] == 'object.forum.moderate') && isset($result['actionId']) && ($result['actionId'] == $contentId) && isset($result['actionMessageId']) && ($result['actionMessageId'] == 0))
             {
                 if ($result['status'] != 'OK')
                 {
                     $params['moderateStatus'] = $result['status'];
                     if (isset($result['text'])) $params['moderateTextValue'] = $result['text'];
                     if (isset($result['error'])) $params['moderateTextError'] = $result['error'];
                     if (isset($result['hide'])) $params['moderateHideValue'] = $result['hide'];
                     if (isset($result['ban'])) $params['moderateBanValue'] = $result['ban'];
                 } else $params['moderateStatus'] = 'OK';
             }
             foreach ($params['messages'] as &$message)
             {
                 $message['editStatus'] = '';
                 $message['editMessageValue'] = $cmsManager->unclearHtmlCode($message['content']);
                 $message['editMessageError'] = '';
                 $message['editAttachments'] = $message['attachments'];
                 $message['editAttachmentsError'] = '';
                 if (isset($result['action']) && ($result['action'] == 'object.forum.edit') && isset($result['actionId']) && ($result['actionId'] == $contentId) && isset($result['actionMessageId']) && ($result['actionMessageId'] == $message['id']))
                 {
                     if ($result['status'] != 'OK')
                     {
                         $message['editStatus'] = $result['status'];
                         if (isset($result['text'])) $message['editMessageValue'] = $result['text'];
                         if (isset($result['error'])) $message['editMessageError'] = $result['error'];
                         if (isset($result['attachmentsError'])) $message['editAttachmentsError'] = $result['attachmentsError'];
                         if (isset($result['attachments']) && (count($result['attachments']) > 0)) 
                         {
                             $message['editAttachments'] = array();
                             $query = $em->createQuery('SELECT a.id, a.messageId, a.downloadDate, a.fileName, a.contentFile, a.isImage FROM ForumForumBundle:ForumAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $result['attachments']);
                             $pattachments = $query->getResult();
                             foreach ($result['attachments'] as $attid)
                             {
                                 foreach ($pattachments as $pattach)
                                 {
                                     if ($pattach['id'] == $attid) $message['editAttachments'][] = array_merge ($pattach, array('fileSize' => @filesize('.'.$pattach['contentFile'])));
                                 }
                             }
                             unset($pattachments);
                         }
                     } else $message['editStatus'] = 'OK';
                 }
                 $message['moderateStatus'] = '';
                 $message['moderateTextValue'] = '';
                 $message['moderateTextError'] = '';
                 $message['moderateHideValue'] = '';
                 $message['moderateBanValue'] = '';
                 if (isset($result['action']) && ($result['action'] == 'object.forum.moderate') && isset($result['actionId']) && ($result['actionId'] == $contentId) && isset($result['actionMessageId']) && ($result['actionMessageId'] == $message['id']))
                 {
                     if ($result['status'] != 'OK')
                     {
                         $message['moderateStatus'] = $result['status'];
                         if (isset($result['text'])) $message['moderateTextValue'] = $result['text'];
                         if (isset($result['error'])) $message['moderateTextError'] = $result['error'];
                         if (isset($result['hide'])) $message['moderateHideValue'] = $result['hide'];
                         if (isset($result['ban'])) $message['moderateBanValue'] = $result['ban'];
                     } else $message['moderateStatus'] = 'OK';
                 }
             }
             unset($message);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         if ($contentType == 'create')
         {
             $createpage = $this->container->get('doctrine')->getRepository('ForumForumBundle:ForumCreatePages')->find($contentId);
             if (empty($createpage)) return null;
             if ($createpage->getEnabled() == 0) return null;
             if (($createpage->getOnlyAutorized() != 0) && (($this->container->get('cms.cmsManager')->getCurrentUser() == null) || ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() != 2)))
             {
                 $this->container->get('cms.cmsManager')->setErrorPageNumber(1); return null;
             }
             $params = array();
             $params['template'] = $createpage->getTemplate();
             $params['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($createpage->getTitle(),$locale,true);
             $params['status'] = 'OK';
             $params['saveStatus'] = '';
             // Получить информацию о категориях таксономии
             $params['categories'] = array();
             if ($this->container->has('object.taxonomy')) $params['categories'] = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.forum');
             //title, description, avatar, content, categoryId, attachments
             $params['id'] = $createpage->getId();
             $params['forumTitle'] = '';
             $params['forumTitleError'] = '';
             $params['forumDescription'] = '';
             $params['forumDescriptionError'] = '';
             $params['forumAvatar'] = '';
             $params['forumAvatarError'] = '';
             $params['forumContent'] = '';
             $params['forumContentError'] = '';
             $params['forumCategoryId'] = '';
             if ($createpage->getCategoryMode() == 1) $params['forumCategoryId'] = intval($this->container->get('request_stack')->getCurrentRequest()->get('categoryid'));
             if ($createpage->getCategoryMode() == 2) $params['forumCategoryId'] = $createpage->getCategoryId();
             $params['forumCategoryIdError'] = '';
             $params['forumCategoryMode'] = $createpage->getCategoryMode();
             $params['forumAttachments'] = array();
             $params['forumAttachmentsError'] = '';
             $params['captchaEnabled'] = 0;
             $params['captchaPath'] = '';
             $params['captchaError'] = '';
             if ($createpage->getCaptchaEnabled() != 0)
             {
                 $params['captchaEnabled'] = 1;
                 $params['captchaPath'] = $this->container->get('router')->generate('basic_cms_captcha').'?target=forumcreate'.$createpage->getId();
                 $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_forumcreate'.$createpage->getId(), '');
             }
             if (isset($result['action']) && ($result['action'] == 'object.forum.create') && ($result['actionId'] == $contentId))
             {
                 $em = $this->container->get('doctrine')->getEntityManager();
                 if (isset($result['title'])) $params['forumTitle'] = $result['title'];
                 if (isset($result['titleError'])) $params['forumTitleError'] = $result['titleError'];
                 if (isset($result['description'])) $params['forumDescription'] = $result['description'];
                 if (isset($result['descriptionError'])) $params['forumDescriptionError'] = $result['descriptionError'];
                 if (isset($result['avatar'])) $params['forumAvatar'] = $result['avatar'];
                 if (isset($result['avatarError'])) $params['forumAvatarError'] = $result['avatarError'];
                 if (isset($result['content'])) $params['forumContent'] = $result['content'];
                 if (isset($result['contentError'])) $params['forumContentError'] = $result['contentError'];
                 if (isset($result['categoryId'])) $params['forumCategoryId'] = $result['categoryId'];
                 if (isset($result['categoryIdError'])) $params['forumCategoryIdError'] = $result['categoryIdError'];
                 if (isset($result['captchaError'])) $params['captchaError'] = $result['captchaError'];
                 if (isset($result['attachments']) && (count($result['attachments']) > 0)) 
                 {
                     $query = $em->createQuery('SELECT a.id, a.messageId, a.downloadDate, a.fileName, a.contentFile, a.isImage FROM ForumForumBundle:ForumAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $result['attachments']);
                     $pattachments = $query->getResult();
                     foreach ($result['attachments'] as $attid)
                     {
                         foreach ($pattachments as $pattach)
                         {
                             if ($pattach['id'] == $attid) $params['forumAttachments'][] = array_merge ($pattach, array('fileSize' => @filesize('.'.$pattach['contentFile'])));
                         }
                     }
                     unset($pattachments);
                 }
                 if (isset($result['attachmentsError'])) $params['forumAttachmentsError'] = $result['attachmentsError'];
                 if ($result['status'] == 'OK')
                 {
                     $params['saveStatus'] = 'OK';
                     $params['createdForum'] = $result['createdForum'];
                     $params['createdUrl'] = $result['createdUrl'];
                 }
             }
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         $params = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
         return $params;
     }

     private $allowStyleProp = 0;
     private $replaceUrls = 0;
     
     
     private function clearHtmlPropertiy($matches)
     {
         if (!isset($matches[1]) || !isset($matches[2])) return '';
         if ($matches[1] == 'href')
         {
             if ($this->replaceUrls != 0) return 'href="#" onclick="window.open(\''.$matches[2].'\');"';
             return 'href="'.$matches[2].'"';
         } elseif ($matches[1] == 'style')
         {
             if ($this->allowStyleProp != 0) return 'style="'.$matches[2].'"';
             return '';
         }
         return '';
     }
     
     private function clearHtmlProperties($matches)
     {
         if (!isset($matches[1]) || !isset($matches[2])) return '';
         return '<'.$matches[1].' '.preg_replace_callback('/([A-z][A-z0-9]*)[\s]*\=[\s]*"([^"]*)"/i', array($this, 'clearHtmlPropertiy') , $matches[2]).'>';
     }
     
     public function setFrontAction($locale, $seoPage, $action)
     {
         $result = null;
         if ($action == 'post')
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             $postfiles = $this->container->get('request_stack')->getCurrentRequest()->files->get('actionfiles');
             $query = $em->createQuery('SELECT f.id, f.captchaEnabled, f.onlyAutorizedPost, f.isClosed, f.allowTags, f.allowStyleProp, f.replaceUrls, f.title FROM ForumForumBundle:Forums f '.
                                       'WHERE f.id = :id AND f.enabled != 0')->setParameter('id', $id);
             $forum = $query->getResult();
             if (isset($forum[0])) 
             {
                 $errors = false;
                 $result['error'] = '';
                 $result['captchaError'] = '';
                 $result['text'] = '';
                 $result['attachments'] = array();
                 $result['attachmentsError'] = '';
                 $result['status'] = '';
                 $result['action'] = 'object.forum.post';
                 $result['actionId'] = $id;
                 if (isset($postfields['text'])) $result['text'] = $postfields['text'];
                 if (isset($postfields['attachments']) && is_array($postfields['attachments']))
                 {
                     foreach ($postfields['attachments'] as $attid)
                     {
                        $query = $em->createQuery('SELECT count(a.id) as attachcount FROM ForumForumBundle:ForumAttachments a WHERE a.id = :id AND a.forumId = 0')->setParameter('id',intval($attid));
                        $attachcount = $query->getResult();
                        if (isset($attachcount[0]['attachcount'])) $attachcount = intval($attachcount[0]['attachcount']); else $attachcount = 0;
                        if ($attachcount == 1) $result['attachments'][] = intval($attid);
                     }
                 }
                 if ($forum[0]['isClosed'] != 0)
                 {
                     $result['status'] = 'Close error';
                 } elseif ((($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2)) && ($forum[0]['onlyAutorizedPost'] != 0)) 
                 {
                     $result['status'] = 'Access error';
                 } else
                 {
                     // Приём файлов
                     if (isset($postfiles['attachments']) && (is_array($postfiles['attachments'])))
                     {
                         $query = $em->createQuery('SELECT a.id, a.contentFile FROM ForumForumBundle:ForumAttachments a WHERE a.forumId = 0 AND a.messageId = 0 AND a.downloadDate < :date')->setParameter('date', new \DateTime('1 days ago'))->setMaxResults(10);
                         $files = $query->getResult();
                         if (is_array($files))
                         {
                             $removeids = array();
                             foreach ($files as $file)
                             {
                                 @unlink('.'.$file['contentFile']);
                                 $removeids[] = $file['id'];
                             }
                             if (count($removeids) > 0)
                             {
                                 $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $removeids);
                                 $query->execute();
                             }
                         }
                         unset($files);
                         foreach ($postfiles['attachments'] as $file) if ($file != null)
                         {
                             $tmpfile = $file->getPathName();
                             $basepath = 'secured/forum/';
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
                                 $result['attachments'][] =$fileent->getId();
                             } else {$errors = true; $result['attachmentsError'] = 'Upload error';}
                         }
                     }
                     // Валидация
                     if (!preg_match("/^[\s\S]{10,}$/ui", $result['text'])) {$errors = true; $result['error'] = 'Preg error';}
                     if ($forum[0]['captchaEnabled'] != 0)
                     {
                         $needle = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_captcha_forum'.$forum[0]['id']);
                         $value = (isset($postfields['captcha']) ? $postfields['captcha'] : null);
                         if (($needle == '') || (strtoupper($value) != strtoupper($needle)) || ($value == null))
                         {
                             $result['captchaError'] = 'Value error';
                             $errors = true;
                         }
                         $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_forum'.$forum[0]['id'], '');
                     }
                     // Удаление неразрешенных тегов
                     $allowtags = explode(',', $forum[0]['allowTags']);
                     $allowtagsstr = '';
                     foreach ($allowtags as $tag) $allowtagsstr .= '<'.trim($tag).'>';
                     $result['text'] = strip_tags($result['text'], $allowtagsstr);
                     // Удаление свойств onclick и т.д. и изменение урлов на редиректы
                     $this->allowStyleProp = $forum[0]['allowStyleProp'];
                     $this->replaceUrls = $forum[0]['replaceUrls'];
                     $result['text'] = preg_replace_callback('/<([A-z][A-z0-9]*)[\s]+([^>]*)>/i', array($this, 'clearHtmlProperties') , $result['text']);
                     // Сохранение если нет ошибок
                     if ($errors == false)
                     {
                         $messageent = new \Forum\ForumBundle\Entity\ForumMessages();
                         $messageent->setContent($result['text']);
                         $messageent->setCreateDate(new \DateTime('now'));
                         $messageent->setCreaterId(($cmsManager->getCurrentUser() != null ? $cmsManager->getCurrentUser()->getId() : null));
                         $messageent->setForumId($forum[0]['id']);
                         $messageent->setIsVisible(1);
                         $messageent->setModeratorId(null);
                         $messageent->setModeratorMessage(null);
                         $messageent->setModifyDate(new \DateTime('now'));
                         $em->persist($messageent);
                         $em->flush();
                         if (count($result['attachments']) > 0)
                         {
                            $query = $em->createQuery('UPDATE ForumForumBundle:ForumAttachments a SET a.forumId = :forumid, a.messageId = :messageid WHERE a.id IN (:ids)')->setParameter('forumid', $forum[0]['id'])->setParameter('messageid', $messageent->getId())->setParameter('ids', $result['attachments']);
                            $query->execute();
                         }
                         $query = $em->createQuery('UPDATE ForumForumBundle:Forums f SET f.lastMessageDate = :now, f.lastMessageId = :messageid WHERE f.id = :id')->setParameter('now', new \DateTime('now'))->setParameter('id', $forum[0]['id'])->setParameter('messageid', $messageent->getId());
                         $query->execute();
                         $result['status'] = 'OK';
                         // Запись в лог
                         $this->container->get('cms.cmsManager')->logPushFront(0, $this->getName().'.create', 'Отправка сообщения к форуму "'.$forum[0]['title'].'"', $this->container->get('router')->generate('forum_forum_messages_edit').'?id='.$messageent->getId());
                     } else $result['status'] = 'Check error';
                 }
             }
         }
         if ($action == 'edit')
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $messageid = $this->container->get('request_stack')->getCurrentRequest()->get('actionmessageid');
             $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             $postfiles = $this->container->get('request_stack')->getCurrentRequest()->files->get('actionfiles');
             $forument = $this->container->get('doctrine')->getRepository('ForumForumBundle:Forums')->findOneBy(array('id' => $id, 'enabled' => 1));
             $messageent = $this->container->get('doctrine')->getRepository('ForumForumBundle:ForumMessages')->findOneBy(array('id' => $messageid, 'forumId' => $id));
             if ((!empty($forument)) && (($messageid == 0) || (!empty($messageent))))
             {
                 $errors = false;
                 $result['error'] = '';
                 $result['text'] = '';
                 $result['attachments'] = array();
                 $result['attachmentsError'] = '';
                 $result['status'] = '';
                 $result['action'] = 'object.forum.edit';
                 $result['actionId'] = $id;
                 $result['actionMessageId'] = $messageid;
                 if (isset($postfields['text'])) $result['text'] = $postfields['text'];
                 if (isset($postfields['attachments']) && is_array($postfields['attachments']))
                 {
                     $query = $em->createQuery('SELECT a.id, a.contentFile FROM ForumForumBundle:ForumAttachments a WHERE a.forumId = 0 AND a.messageId = 0 AND a.downloadDate < :date')->setParameter('date', new \DateTime('1 days ago'))->setMaxResults(10);
                     $files = $query->getResult();
                     if (is_array($files))
                     {
                         $removeids = array();
                         foreach ($files as $file)
                         {
                             @unlink('.'.$file['contentFile']);
                             $removeids[] = $file['id'];
                         }
                         if (count($removeids) > 0)
                         {
                             $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $removeids);
                             $query->execute();
                         }
                     }
                     unset($files);
                     foreach ($postfields['attachments'] as $attid)
                     {
                        $query = $em->createQuery('SELECT count(a.id) as attachcount FROM ForumForumBundle:ForumAttachments a WHERE a.id = :id AND ((a.forumId = :forumid AND a.messageId = :messageid) OR (a.forumId = 0 AND a.messageId = 0))')->setParameter('forumid', $id)->setParameter('messageid', $messageid)->setParameter('id',intval($attid));
                        $attachcount = $query->getResult();
                        if (isset($attachcount[0]['attachcount'])) $attachcount = intval($attachcount[0]['attachcount']); else $attachcount = 0;
                        if ($attachcount == 1) $result['attachments'][] = intval($attid);
                     }
                 }
                 
                 if (($forument->getIsClosed() != 0) || (($messageid == 0) && ($forument->getIsVisible() == 0)) || (($messageid != 0) && ($messageent->getIsVisible() == 0)))
                 {
                     $result['status'] = 'Close error';
                 } elseif (($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2) || (($messageid == 0) && ($forument->getCreaterId() != $cmsManager->getCurrentUser()->getId())) || (($messageid != 0) && ($messageent->getCreaterId() != $cmsManager->getCurrentUser()->getId())))
                 {
                     $result['status'] = 'Access error';
                 } else
                 {
                     // Приём файлов
                     if (isset($postfiles['attachments']) && (is_array($postfiles['attachments'])))
                     {
                         foreach ($postfiles['attachments'] as $file) if ($file != null)
                         {
                             $tmpfile = $file->getPathName();
                             $basepath = 'secured/forum/';
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
                                 $result['attachments'][] =$fileent->getId();
                             } else {$errors = true; $result['attachmentsError'] = 'Upload error';}
                         }
                     }
                     // Валидация
                     if (!preg_match("/^[\s\S]{10,}$/ui", $result['text'])) {$errors = true; $result['error'] = 'Preg error';}
                     // Удаление неразрешенных тегов
                     $allowtags = explode(',', $forument->getAllowTags());
                     $allowtagsstr = '';
                     foreach ($allowtags as $tag) $allowtagsstr .= '<'.trim($tag).'>';
                     $result['text'] = strip_tags($result['text'], $allowtagsstr);
                     // Удаление свойств onclick и т.д. и изменение урлов на редиректы
                     $this->allowStyleProp = $forument->getAllowStyleProp();
                     $this->replaceUrls = $forument->getReplaceUrls();
                     $result['text'] = preg_replace_callback('/<([A-z][A-z0-9]*)[\s]+([^>]*)>/i', array($this, 'clearHtmlProperties') , $result['text']);
                     // Сохранение если нет ошибок
                     if ($errors == false)
                     {
                         if ($messageid == 0)
                         {
                             $forument->setContent($result['text']);
                             $forument->setModifyDate(new \DateTime('now'));
                             $em->flush();
                         } else
                         {
                             $messageent->setContent($result['text']);
                             $messageent->setModifyDate(new \DateTime('now'));
                             $em->flush();
                         }
                         if (count($result['attachments']) > 0)
                         {
                            $query = $em->createQuery('UPDATE ForumForumBundle:ForumAttachments a SET a.forumId = :forumid, a.messageId = :messageid WHERE a.id IN (:ids)')->setParameter('forumid', $forument->getId())->setParameter('messageid', $messageid)->setParameter('ids', $result['attachments']);
                            $query->execute();
                            $query = $em->createQuery('UPDATE ForumForumBundle:ForumAttachments a SET a.forumId = 0, a.messageId = 0 WHERE a.forumId = :forumid AND a.messageId = :messageid AND a.id NOT IN (:ids)')->setParameter('forumid', $forument->getId())->setParameter('messageid', $messageid)->setParameter('ids', $result['attachments']);
                            $query->execute();
                         } else
                         {
                            $query = $em->createQuery('UPDATE ForumForumBundle:ForumAttachments a SET a.forumId = 0, a.messageId = 0 WHERE a.forumId = :forumid AND a.messageId = :messageid')->setParameter('forumid', $forument->getId())->setParameter('messageid', $messageid);
                            $query->execute();
                         }
                         $result['status'] = 'OK';
                         // Запись в лог
                         if ($messageid == 0)
                         {
                            $this->container->get('cms.cmsManager')->logPushFront(0, $this->getName().'.edit', 'Изменение сообщения к форуму "'.$forument->getTitle().'"', $this->container->get('router')->generate('forum_forum_topic_edit').'?id='.$forument->getId());
                         } else
                         {
                            $this->container->get('cms.cmsManager')->logPushFront(0, $this->getName().'.edit', 'Изменение сообщения к форуму "'.$forument->getTitle().'"', $this->container->get('router')->generate('forum_forum_messages_edit').'?id='.$messageid);
                         }
                     } else $result['status'] = 'Check error';
                 }
             }
         }
         if ($action == 'moderate')
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $messageid = $this->container->get('request_stack')->getCurrentRequest()->get('actionmessageid');
             $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             $forument = $this->container->get('doctrine')->getRepository('ForumForumBundle:Forums')->findOneBy(array('id' => $id, 'enabled' => 1));
             $messageent = $this->container->get('doctrine')->getRepository('ForumForumBundle:ForumMessages')->findOneBy(array('id' => $messageid, 'forumId' => $id));
             if ((!empty($forument)) && (($messageid == 0) || (!empty($messageent))))
             {
                 $errors = false;
                 $result['error'] = '';
                 $result['text'] = '';
                 $result['hide'] = 0;
                 $result['ban'] = 0;
                 $result['status'] = '';
                 $result['action'] = 'object.forum.moderate';
                 $result['actionId'] = $id;
                 $result['actionMessageId'] = $messageid;
                 if (isset($postfields['text'])) $result['text'] = $postfields['text'];
                 if (isset($postfields['hide'])) $result['hide'] = intval($postfields['hide']); else $result['hide'] = 0;
                 if (isset($postfields['ban'])) $result['ban'] = intval($postfields['ban']); else $result['ban'] = 0;
                 // Проверить на права модератора
                 $checkmoderator = 0;
                 if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2)) 
                 {
                     $query = $em->createQuery('SELECT count(u.id) as checkmoderator FROM BasicCmsBundle:Users u '.
                                               'LEFT JOIN ForumForumBundle:ForumModerators fm WITH fm.moderatorId = u.id '.
                                               'WHERE u.blocked = 2 AND u.id = :userid AND (fm.forumId = :id OR fm.categoryId IN ('.
                                               'SELECT t.id FROM BasicCmsBundle:Taxonomies t LEFT JOIN BasicCmsBundle:TaxonomiesLinks tl WITH tl.taxonomyId = t.id AND tl.itemId = :id '.
                                               'WHERE tl.id IS NOT NULL AND t.object = \'object.forum\''.
                                               ')) GROUP BY u.id')->setParameter('id', $id)->setParameter('userid', $cmsManager->getCurrentUser()->getId());
                     $checkmoderator = $query->getResult();
                     if (isset($checkmoderator[0]['checkmoderator']) && ($checkmoderator[0]['checkmoderator'] == 1)) $checkmoderator = 1;
                     if ($cmsManager->getCurrentUser()->getUserType() == 'SUPERADMIN') $checkmoderator = 1;
                     if (($cmsManager->getCurrentUser()->getUserType() == 'ADMIN') && ($cmsManager->getCurrentUser()->checkAccess('forum_edit') != 0)) $checkmoderator = 1;
                 }
                 if ($checkmoderator == 0)
                 {
                     $result['status'] = 'Access error';
                 } else
                 {
                     // Валидация
                     if (!preg_match("/^[\s\S]{10,}$/ui", $result['text'])) {$errors = true; $result['error'] = 'Preg error';}
                     $result['text'] = strip_tags($result['text']);
                     // Сохранение если нет ошибок
                     if ($errors == false)
                     {
                         if ($messageid == 0)
                         {
                             $forument->setModeratorId($cmsManager->getCurrentUser()->getId());
                             $forument->setModeratorMessage($result['text']);
                             $forument->setIsVisible(($result['hide'] != 0 ? 0 : 1));
                             $em->flush();
                             
                         } else
                         {
                             $messageent->setModeratorId($cmsManager->getCurrentUser()->getId());
                             $messageent->setModeratorMessage($result['text']);
                             $messageent->setIsVisible(($result['hide'] != 0 ? 0 : 1));
                             $em->flush();
                         }
                         if ($result['ban'] != 0)
                         {
                             if ($messageid == 0) $baneduser = $this->container->get('doctrine')->getRepository('BasicCmsBundle:Users')->find($forument->getCreaterId()); 
                                             else $baneduser = $this->container->get('doctrine')->getRepository('BasicCmsBundle:Users')->find($messageent->getCreaterId()); 
                             if (!empty($baneduser))
                             {
                                 if ($banneduser->getUserType() == 'USER') 
                                 {
                                     $banneduser->setBlocked(1);
                                    $em->flush();
                                 }
                             }
                         }
                         // Запись в лог
                         if ($messageid == 0)
                         {
                            $this->container->get('cms.cmsManager')->logPushFront(0, $this->getName().'.moderate', 'Модерация сообщения к форуму "'.$forument->getTitle().'"', $this->container->get('router')->generate('forum_forum_topic_edit').'?id='.$forument->getId());
                         } else
                         {
                            $this->container->get('cms.cmsManager')->logPushFront(0, $this->getName().'.moderate', 'Модерация сообщения к форуму "'.$forument->getTitle().'"', $this->container->get('router')->generate('forum_forum_messages_edit').'?id='.$messageid);
                         }
                         $result['status'] = 'OK';
                     } else $result['status'] = 'Check error';
                 }
             }
         }
         if (($action == 'closetopic') || ($action == 'importanttopic'))
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $value = intval($this->container->get('request_stack')->getCurrentRequest()->get('actionvalue'));
             $forument = $this->container->get('doctrine')->getRepository('ForumForumBundle:Forums')->findOneBy(array('id' => $id, 'enabled' => 1));
             if (!empty($forument))
             {
                 $errors = false;
                 
                 $result['status'] = '';
                 $result['action'] = 'object.forum.'.$action;
                 $result['actionId'] = $id;
                 // Проверить на права модератора
                 $checkmoderator = 0;
                 if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2)) 
                 {
                     $query = $em->createQuery('SELECT count(u.id) as checkmoderator FROM BasicCmsBundle:Users u '.
                                               'LEFT JOIN ForumForumBundle:ForumModerators fm WITH fm.moderatorId = u.id '.
                                               'WHERE u.blocked = 2 AND u.id = :userid AND (fm.forumId = :id OR fm.categoryId IN ('.
                                               'SELECT t.id FROM BasicCmsBundle:Taxonomies t LEFT JOIN BasicCmsBundle:TaxonomiesLinks tl WITH tl.taxonomyId = t.id AND tl.itemId = :id '.
                                               'WHERE tl.id IS NOT NULL AND t.object = \'object.forum\''.
                                               ')) GROUP BY u.id')->setParameter('id', $id)->setParameter('userid', $cmsManager->getCurrentUser()->getId());
                     $checkmoderator = $query->getResult();
                     if (isset($checkmoderator[0]['checkmoderator']) && ($checkmoderator[0]['checkmoderator'] == 1)) $checkmoderator = 1;
                     if ($cmsManager->getCurrentUser()->getUserType() == 'SUPERADMIN') $checkmoderator = 1;
                     if (($cmsManager->getCurrentUser()->getUserType() == 'ADMIN') && ($cmsManager->getCurrentUser()->checkAccess('forum_edit') != 0)) $checkmoderator = 1;
                 }
                 if ($checkmoderator == 0)
                 {
                     $result['status'] = 'Access error';
                 } else
                 {
                     if ($action == 'closetopic') $forument->setIsClosed($value);
                     if ($action == 'importanttopic') $forument->setIsImportant($value);
                     $em->flush();
                     $result['status'] = 'OK';
                     // Запись в лог
                     $this->container->get('cms.cmsManager')->logPushFront(0, $this->getName().'.edit', 'Изменение статуса форума "'.$forument->getTitle().'"', $this->container->get('router')->generate('forum_forum_topic_edit').'?id='.$forument->getId());
                 }
             }
         }
         if ($action == 'create')
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             $postfiles = $this->container->get('request_stack')->getCurrentRequest()->files->get('actionfiles');
             $createpage = $this->container->get('doctrine')->getRepository('ForumForumBundle:ForumCreatePages')->find($id);
             if ((!empty($createpage)) && ($createpage->getEnabled() != 0))
             {
                 $errors = false;
                 $result['title'] = '';
                 $result['titleError'] = '';
                 $result['description'] = '';
                 $result['descriptionError'] = '';
                 $result['avatar'] = '';
                 $result['avatarError'] = '';
                 $result['content'] = '';
                 $result['contentError'] = '';
                 $result['categoryId'] = '';
                 $result['categoryIdError'] = '';
                 $result['attachments'] = array();
                 $result['attachmentsError'] = '';
                 $result['captchaError'] = '';
                 $result['status'] = '';
                 $result['action'] = 'object.forum.create';
                 $result['actionId'] = $id;
                 if (isset($postfields['title'])) $result['title'] = $postfields['title'];
                 if (isset($postfields['description'])) $result['description'] = $postfields['description'];
                 if (isset($postfields['avatar'])) $result['avatar'] = $postfields['avatar'];
                 if (isset($postfields['content'])) $result['content'] = $postfields['content'];
                 if (isset($postfields['categoryId'])) $result['categoryId'] = $postfields['categoryId'];
                 if (isset($postfields['attachments']) && is_array($postfields['attachments']))
                 {
                     foreach ($postfields['attachments'] as $attid)
                     {
                        $query = $em->createQuery('SELECT count(a.id) as attachcount FROM ForumForumBundle:ForumAttachments a WHERE a.id = :id AND a.forumId = 0')->setParameter('id',intval($attid));
                        $attachcount = $query->getResult();
                        if (isset($attachcount[0]['attachcount'])) $attachcount = intval($attachcount[0]['attachcount']); else $attachcount = 0;
                        if ($attachcount == 1) $result['attachments'][] = intval($attid);
                     }
                 }
                 if ((($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2)) && ($createpage->getOnlyAutorized() != 0)) 
                 {
                     $result['status'] = 'Access error';
                 } else
                 {
                     // Приём файлов
                     if (isset($postfiles['avatar']))
                     {
                         $tmpfile = $postfiles['avatar']->getPathName();
                         /*$source = "";
                         if (@getimagesize($tmpfile)) 
                         {
                             $params = getimagesize($tmpfile);
                             if ($params[0] < 10) {$errors = true; $result['avatarError'] = 'size';}
                             if ($params[1] < 10) {$errors = true; $result['avatarError'] = 'size';} 
                             if ($params[0] > 6000) {$errors = true; $result['avatarError'] = 'size';}
                             if ($params[1] > 6000) {$errors = true; $result['avatarError'] = 'size';}
                             $basepath = 'images/forum/';
                             $name = 'f_'.md5($tmpfile.time()).'.jpg';
                             if (move_uploaded_file($tmpfile, $basepath . $name)) $result['avatar'] = '/images/forum/'.$name;
                         } else {$errors = true; $result['avatarError'] = 'format';}*/
                         if (@getimagesize($tmpfile)) 
                         {
                             $params = getimagesize($tmpfile);
                             if ($params[0] < 10) {$errors = true; $result['avatarError'] = 'size';}
                             if ($params[1] < 10) {$errors = true; $result['avatarError'] = 'size';} 
                             if ($params[0] > 6000) {$errors = true; $result['avatarError'] = 'size';}
                             if ($params[1] > 6000) {$errors = true; $result['avatarError'] = 'size';}
                             $imageTypeArray = array(0=>'', 1=>'gif', 2=>'jpg', 3=>'png', 4=>'', 5=>'', 6=>'', 7=>'', 8=>'', 9=>'jpg', 10=>'jpg', 11=>'jpg', 12=>'jpg', 13=>'', 14=>'', 15=>'', 16=>'', 17=>'', 18=>'');
                             if (!isset($imageTypeArray[$params[2]]) || ($imageTypeArray[$params[2]] == '')) {$errors = true; $result['avatarError'] = 'format';}
                             $basepath = '/images/forum/';
                             $name = 'f_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
                             if (move_uploaded_file($tmpfile, '.'.$basepath.$name)) 
                             {
                                 $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $postfiles['avatar']->getClientOriginalName());
                                 $result['avatar'] = $basepath.$name;
                             }
                         } else {$errors = true; $result['avatarError'] = 'format';}
                     }
                     if (isset($postfiles['attachments']) && (is_array($postfiles['attachments'])))
                     {
                         $query = $em->createQuery('SELECT a.id, a.contentFile FROM ForumForumBundle:ForumAttachments a WHERE a.forumId = 0 AND a.messageId = 0 AND a.downloadDate < :date')->setParameter('date', new \DateTime('1 days ago'))->setMaxResults(10);
                         $files = $query->getResult();
                         if (is_array($files))
                         {
                             $removeids = array();
                             foreach ($files as $file)
                             {
                                 @unlink('.'.$file['contentFile']);
                                 $removeids[] = $file['id'];
                             }
                             if (count($removeids) > 0)
                             {
                                 $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $removeids);
                                 $query->execute();
                             }
                         }
                         unset($files);
                         foreach ($postfiles['attachments'] as $file) if ($file != null)
                         {
                             $tmpfile = $file->getPathName();
                             $basepath = 'secured/forum/';
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
                                 $result['attachments'][] =$fileent->getId();
                             } else {$errors = true; $result['attachmentsError'] = 'Upload error';}
                         }
                     }
                     // Валидация
                     if (!preg_match("/^.{3,}$/ui", $result['title'])) {$errors = true; $result['titleError'] = 'Preg error';}
                     if (($result['avatar'] != '') && (!file_exists('.'.$result['avatar']))) {$errors = true; $result['avatarError'] = 'Not found';}
                     if (!preg_match("/^[\s\S]{10,}$/ui", $result['content'])) {$errors = true; $result['contentError'] = 'Preg error';}
                     if ($createpage->getCategoryMode() == 1)
                     {
                         $query = $em->createQuery('SELECT count(t.id) as taxcount FROM BasicCmsBundle:Taxonomies t WHERE t.enabled != 0 AND t.enableAdd != 0 AND t.object = \'object.forum\' AND t.id = :id')->setParameter('id', $result['categoryId']);
                         $taxcount = $query->getResult();
                         if (isset($taxcount[0]['taxcount'])) $taxcount = intval($taxcount[0]['taxcount']); else $taxcount = 0;
                         if ($taxcount != 1) {$errors = true; $result['categoryIdError'] = 'Not found';}
                     }
                     if ($createpage->getCaptchaEnabled() != 0)
                     {
                         $needle = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_captcha_forumcreate'.$createpage->getId());
                         $value = (isset($postfields['captcha']) ? $postfields['captcha'] : null);
                         if (($needle == '') || (strtoupper($value) != strtoupper($needle)) || ($value == null))
                         {
                             $result['captchaError'] = 'Value error';
                             $errors = true;
                         }
                         $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_forumcreate'.$createpage->getId(), '');
                     }
                     // Удаление неразрешенных тегов
                     $allowtags = explode(',', $createpage->getForumAllowTags());
                     $allowtagsstr = '';
                     foreach ($allowtags as $tag) $allowtagsstr .= '<'.trim($tag).'>';
                     $result['content'] = strip_tags($result['content'], $allowtagsstr);
                     // Удаление свойств onclick и т.д. и изменение урлов на редиректы
                     $this->allowStyleProp = $createpage->getForumAllowStyleProp();
                     $this->replaceUrls = $createpage->getForumReplaceUrls();
                     $result['content'] = preg_replace_callback('/<([A-z][A-z0-9]*)[\s]+([^>]*)>/i', array($this, 'clearHtmlProperties') , $result['content']);
                     // Сохранение если нет ошибок
                     if ($errors == false)
                     {
                         $this->container->get('cms.cmsManager')->unlockTemporaryFile($result['avatar']);
                         $forument = new \Forum\ForumBundle\Entity\Forums();
                         $forument->setAllowStyleProp($createpage->getForumAllowStyleProp());
                         $forument->setAllowTags($createpage->getForumAllowTags());
                         $forument->setAvatar($result['avatar']);
                         $forument->setCaptchaEnabled($createpage->getForumCaptchaEnabled());
                         $forument->setContent($result['content']);
                         $forument->setCreateDate(new \DateTime('now'));
                         $forument->setCreaterId(($cmsManager->getCurrentUser() != null ? $cmsManager->getCurrentUser()->getId() : 0));
                         $forument->setDescription($result['description']);
                         $forument->setEnabled($createpage->getForumEnabled());
                         $forument->setIsClosed($createpage->getForumIsClosed());
                         $forument->setIsImportant($createpage->getForumIsImportant());
                         $forument->setIsVisible($createpage->getForumIsVisible());
                         $forument->setLastMessageDate(new \DateTime('now'));
                         $forument->setLastMessageId(null);
                         $forument->setMessageInPage($createpage->getForumMessageInPage());
                         $forument->setModeratorId(null);
                         $forument->setModeratorMessage(null);
                         $forument->setModifyDate(new \DateTime('now'));
                         $forument->setOnlyAutorizedDownload($createpage->getForumOnlyAutorizedDownload());
                         $forument->setOnlyAutorizedImage($createpage->getForumOnlyAutorizedImage());
                         $forument->setOnlyAutorizedPost($createpage->getForumOnlyAutorizedPost());
                         $forument->setOnlyAutorizedView($createpage->getForumOnlyAutorizedView());
                         $forument->setReplaceUrls($createpage->getForumReplaceUrls());
                         $forument->setTemplate($createpage->getForumTemplate());
                         $forument->setTitle($result['title']);
                         $em->persist($forument);
                         $em->flush();
                         // seopage
                         $result['createdForum'] = $forument;
                         $result['createdUrl'] = '';
                         if ($createpage->getSeopageEnabled() != 0)
                         {
                             $pageurl = $this->container->get('cms.cmsManager')->getUniqueUrl($createpage->getSeopageUrl(), array('id'=>$forument->getId(), 'title'=>$forument->getTitle()));
                             $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                             $pageent->setBreadCrumbs('');
                             $pageent->setBreadCrumbsRel('');
                             $pageent->setDescription('Просмотр форума '.$forument->getTitle());
                             $pageent->setUrl($pageurl);
                             $pageent->setTemplate($createpage->getSeopageTemplate());
                             $pageent->setModules($createpage->getSeopageModules());
                             $pageent->setLocale($createpage->getSeopageLocale());
                             $pageent->setContentType('object.forum');
                             $pageent->setContentId($forument->getId());
                             $pageent->setContentAction('view');
                             $pageent->setAccess($createpage->getSeopageAccess());
                             $em->persist($pageent);
                             $em->flush();
                             if ($pageurl == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                             $result['createdUrl'] = $pageent->getUrl();
                         }
                         // moderators
                         if ($createpage->getForumModerators() != '')
                         {
                            $moderatorIds = explode(',', $createpage->getForumModerators());
                            if (is_array($moderatorIds) && (count($moderatorIds) > 0))
                            {
                                $query = $em->createQuery('SELECT u.id FROM BasicCmsBundle:Users u WHERE u.id IN (:ids)')->setParameter('ids', $moderatorIds);
                                $moders = $query->getResult();
                                if (is_array($moders) && (count($moders) > 0))                                
                                {
                                    foreach ($moders as $moder)
                                    {
                                        $moderent = new \Forum\ForumBundle\Entity\ForumModerators();
                                        $moderent->setCategoryId(0);
                                        $moderent->setForumId($forument->getId());
                                        $moderent->setModeratorId($moder['id']);
                                        $em->persist($moderent);
                                        $em->flush();
                                    }
                                }
                            }
                         }
                         // taxonomy
                         if ($createpage->getCategoryMode() == 1)
                         {
                             $taxlink = new \Basic\CmsBundle\Entity\TaxonomiesLinks();
                             $taxlink->setItemId($forument->getId());
                             $taxlink->setTaxonomyId($result['categoryId']);
                             $em->persist($taxlink);
                             $em->flush();
                         }
                         if ($createpage->getCategoryMode() == 2)
                         {
                             $taxlink = new \Basic\CmsBundle\Entity\TaxonomiesLinks();
                             $taxlink->setItemId($forument->getId());
                             $taxlink->setTaxonomyId($createpage->getCategoryId());
                             $em->persist($taxlink);
                             $em->flush();
                             $result['categoryId'] = $createpage->getCategoryId();
                         }
                         // attachments
                         if (count($result['attachments']) > 0)
                         {
                            $query = $em->createQuery('UPDATE ForumForumBundle:ForumAttachments a SET a.forumId = :forumid, a.messageId = 0 WHERE a.id IN (:ids)')->setParameter('forumid', $forument->getId())->setParameter('ids', $result['attachments']);
                            $query->execute();
                         }
                         $result['status'] = 'OK';
                        // Запись в лог
                        $this->container->get('cms.cmsManager')->logPushFront(0, $this->getName().'.create', 'Создание форума "'.$forument->getTitle().'"', $this->container->get('router')->generate('forum_forum_topic_edit').'?id='.$forument->getId());
                     } else $result['status'] = 'Check error';
                 }
             }
         }
         if ($action == 'postcomment')
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $seoPageId = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             $postfiles = $this->container->get('request_stack')->getCurrentRequest()->files->get('actionfiles');
             // Найти модуль
             $module = null;
             $seoPageEnt = $this->container->get('doctrine')->getRepository('BasicCmsBundle:SeoPage')->find($seoPageId);
             if (!empty($seoPageEnt))
             {
                $moduleids = @explode(',', $seoPageEnt->getModules());
                if (is_array($moduleids) && (count($moduleids) > 0))
                {
                    $query = $em->createQuery('SELECT m.parameters FROM BasicCmsBundle:Modules m WHERE m.enabled != 0 AND m.id in (:moduleids) AND m.objectName = \'object.forum\' AND m.moduleType = \'comments\'')->setParameter('moduleids', $moduleids);
                    $module = $query->getResult();
                    if (isset($module[0]['parameters'])) $module = @unserialize($module[0]['parameters']); else $module = null;
                }
             }
             // погнали
             if ($module != null) 
             {
                 $errors = false;
                 $result['error'] = '';
                 $result['captchaError'] = '';
                 $result['text'] = '';
                 $result['attachments'] = array();
                 $result['attachmentsError'] = '';
                 $result['status'] = '';
                 $result['action'] = 'object.forum.postcomment';
                 $result['actionId'] = $seoPageId;
                 if (isset($postfields['text'])) $result['text'] = $postfields['text'];
                 if (isset($postfields['attachments']) && is_array($postfields['attachments']))
                 {
                     foreach ($postfields['attachments'] as $attid)
                     {
                        $query = $em->createQuery('SELECT count(a.id) as attachcount FROM ForumForumBundle:ForumCommentAttachments a WHERE a.id = :id AND a.commentId = 0')->setParameter('id',intval($attid));
                        $attachcount = $query->getResult();
                        if (isset($attachcount[0]['attachcount'])) $attachcount = intval($attachcount[0]['attachcount']); else $attachcount = 0;
                        if ($attachcount == 1) $result['attachments'][] = intval($attid);
                     }
                 }
                 if ($module['isClosed'] != 0)
                 {
                     $result['status'] = 'Close error';
                 } elseif ((($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2)) && ($module['onlyAutorizedPost'] != 0)) 
                 {
                     $result['status'] = 'Access error';
                 } else
                 {
                     // Приём файлов
                     if (isset($postfiles['attachments']) && (is_array($postfiles['attachments'])))
                     {
                         $query = $em->createQuery('SELECT a.id, a.contentFile FROM ForumForumBundle:ForumCommentAttachments a WHERE a.commentId = 0 AND a.downloadDate < :date')->setParameter('date', new \DateTime('1 days ago'))->setMaxResults(10);
                         $files = $query->getResult();
                         if (is_array($files))
                         {
                             $removeids = array();
                             foreach ($files as $file)
                             {
                                 @unlink('.'.$file['contentFile']);
                                 $removeids[] = $file['id'];
                             }
                             if (count($removeids) > 0)
                             {
                                 $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumCommentAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $removeids);
                                 $query->execute();
                             }
                         }
                         unset($files);
                         foreach ($postfiles['attachments'] as $file) if ($file != null)
                         {
                             $tmpfile = $file->getPathName();
                             $basepath = 'secured/forum/';
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
                                 $result['attachments'][] =$fileent->getId();
                             } else {$errors = true; $result['attachmentsError'] = 'Upload error';}
                         }
                     }
                     // Валидация
                     if (!preg_match("/^[\s\S]{10,}$/ui", $result['text'])) {$errors = true; $result['error'] = 'Preg error';}
                     if ($module['captchaEnabled'] != 0)
                     {
                         $needle = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_captcha_forumcomments'.$seoPageId);
                         $value = (isset($postfields['captcha']) ? $postfields['captcha'] : null);
                         if (($needle == '') || (strtoupper($value) != strtoupper($needle)) || ($value == null))
                         {
                             $result['captchaError'] = 'Value error';
                             $errors = true;
                         }
                         $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_forumcomments'.$seoPageId, '');
                     }
                     // Удаление неразрешенных тегов
                     $allowtags = explode(',', $module['allowTags']);
                     $allowtagsstr = '';
                     foreach ($allowtags as $tag) $allowtagsstr .= '<'.trim($tag).'>';
                     $result['text'] = strip_tags($result['text'], $allowtagsstr);
                     // Удаление свойств onclick и т.д. и изменение урлов на редиректы
                     $this->allowStyleProp = $module['allowStyleProp'];
                     $this->replaceUrls = $module['replaceUrls'];
                     $result['text'] = preg_replace_callback('/<([A-z][A-z0-9]*)[\s]+([^>]*)>/i', array($this, 'clearHtmlProperties') , $result['text']);
                     // Сохранение если нет ошибок
                     if ($errors == false)
                     {
                         $messageent = new \Forum\ForumBundle\Entity\ForumComments();
                         $messageent->setContent($result['text']);
                         $messageent->setCreateDate(new \DateTime('now'));
                         $messageent->setCreaterId(($cmsManager->getCurrentUser() != null ? $cmsManager->getCurrentUser()->getId() : null));
                         $messageent->setSeoPageId($seoPageId);
                         $messageent->setIsVisible(1);
                         $messageent->setModeratorId(null);
                         $messageent->setModeratorMessage(null);
                         $messageent->setModifyDate(new \DateTime('now'));
                         $em->persist($messageent);
                         $em->flush();
                         if (count($result['attachments']) > 0)
                         {
                            $query = $em->createQuery('UPDATE ForumForumBundle:ForumCommentAttachments a SET a.commentId = :messageid WHERE a.id IN (:ids)')->setParameter('messageid', $messageent->getId())->setParameter('ids', $result['attachments']);
                            $query->execute();
                         }
                         $result['status'] = 'OK';
                        // Запись в лог
                        $this->container->get('cms.cmsManager')->logPushFront(0, $this->getName().'.create', 'Создание комментария к странице "'.$seoPageEnt->getDescription().'"', $this->container->get('router')->generate('forum_forum_comments_edit').'?id='.$messageent->getId());
                     } else $result['status'] = 'Check error';
                 }
             }
         }
         if ($action == 'editcomment')
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $seoPageId = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $messageid = $this->container->get('request_stack')->getCurrentRequest()->get('actionmessageid');
             $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             $postfiles = $this->container->get('request_stack')->getCurrentRequest()->files->get('actionfiles');
             $module = null;
             $seoPageEnt = $this->container->get('doctrine')->getRepository('BasicCmsBundle:SeoPage')->find($seoPageId);
             if (!empty($seoPageEnt))
             {
                $moduleids = @explode(',', $seoPageEnt->getModules());
                if (is_array($moduleids) && (count($moduleids) > 0))
                {
                    $query = $em->createQuery('SELECT m.parameters FROM BasicCmsBundle:Modules m WHERE m.enabled != 0 AND m.id in (:moduleids) AND m.objectName = \'object.forum\' AND m.moduleType = \'comments\'')->setParameter('moduleids', $moduleids);
                    $module = $query->getResult();
                    if (isset($module[0]['parameters'])) $module = @unserialize($module[0]['parameters']); else $module = null;
                }
             }
             $messageent = $this->container->get('doctrine')->getRepository('ForumForumBundle:ForumComments')->findOneBy(array('id' => $messageid, 'seoPageId' => $seoPageId));
             
             if (($module != null) && (!empty($messageent)))
             {
                 $errors = false;
                 $result['error'] = '';
                 $result['text'] = '';
                 $result['attachments'] = array();
                 $result['attachmentsError'] = '';
                 $result['status'] = '';
                 $result['action'] = 'object.forum.editcomment';
                 $result['actionId'] = $seoPageId;
                 $result['actionMessageId'] = $messageid;
                 if (isset($postfields['text'])) $result['text'] = $postfields['text'];
                 if (isset($postfields['attachments']) && is_array($postfields['attachments']))
                 {
                     $query = $em->createQuery('SELECT a.id, a.contentFile FROM ForumForumBundle:ForumCommentAttachments a WHERE a.commentId = 0 AND a.downloadDate < :date')->setParameter('date', new \DateTime('1 days ago'))->setMaxResults(10);
                     $files = $query->getResult();
                     if (is_array($files))
                     {
                         $removeids = array();
                         foreach ($files as $file)
                         {
                             @unlink('.'.$file['contentFile']);
                             $removeids[] = $file['id'];
                         }
                         if (count($removeids) > 0)
                         {
                             $query = $em->createQuery('DELETE FROM ForumForumBundle:ForumCommentAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $removeids);
                             $query->execute();
                         }
                     }
                     unset($files);
                     foreach ($postfields['attachments'] as $attid)
                     {
                        $query = $em->createQuery('SELECT count(a.id) as attachcount FROM ForumForumBundle:ForumCommentAttachments a WHERE a.id = :id AND ((a.commentId = :messageid) OR (a.commentId = 0))')->setParameter('messageid', $messageid)->setParameter('id',intval($attid));
                        $attachcount = $query->getResult();
                        if (isset($attachcount[0]['attachcount'])) $attachcount = intval($attachcount[0]['attachcount']); else $attachcount = 0;
                        if ($attachcount == 1) $result['attachments'][] = intval($attid);
                     }
                 }
                 
                 if (($module['isClosed'] != 0) || ($messageent->getIsVisible() == 0))
                 {
                     $result['status'] = 'Close error';
                 } elseif (($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2) || ($messageent->getCreaterId() != $cmsManager->getCurrentUser()->getId()))
                 {
                     $result['status'] = 'Access error';
                 } else
                 {
                     // Приём файлов
                     if (isset($postfiles['attachments']) && (is_array($postfiles['attachments'])))
                     {
                         foreach ($postfiles['attachments'] as $file) if ($file != null)
                         {
                             $tmpfile = $file->getPathName();
                             $basepath = 'secured/forum/';
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
                                 $result['attachments'][] =$fileent->getId();
                             } else {$errors = true; $result['attachmentsError'] = 'Upload error';}
                         }
                     }
                     // Валидация
                     if (!preg_match("/^[\s\S]{10,}$/ui", $result['text'])) {$errors = true; $result['error'] = 'Preg error';}
                     // Удаление неразрешенных тегов
                     $allowtags = explode(',', $module['allowTags']);
                     $allowtagsstr = '';
                     foreach ($allowtags as $tag) $allowtagsstr .= '<'.trim($tag).'>';
                     $result['text'] = strip_tags($result['text'], $allowtagsstr);
                     // Удаление свойств onclick и т.д. и изменение урлов на редиректы
                     $this->allowStyleProp = $module['allowStyleProp'];
                     $this->replaceUrls = $module['replaceUrls'];
                     $result['text'] = preg_replace_callback('/<([A-z][A-z0-9]*)[\s]+([^>]*)>/i', array($this, 'clearHtmlProperties') , $result['text']);
                     // Сохранение если нет ошибок
                     if ($errors == false)
                     {
                         $messageent->setContent($result['text']);
                         $messageent->setModifyDate(new \DateTime('now'));
                         $em->flush();
                         if (count($result['attachments']) > 0)
                         {
                            $query = $em->createQuery('UPDATE ForumForumBundle:ForumCommentAttachments a SET a.commentId = :messageid WHERE a.id IN (:ids)')->setParameter('messageid', $messageid)->setParameter('ids', $result['attachments']);
                            $query->execute();
                            $query = $em->createQuery('UPDATE ForumForumBundle:ForumCommentAttachments a SET a.commentId = 0 WHERE a.commentId = :messageid AND a.id NOT IN (:ids)')->setParameter('messageid', $messageid)->setParameter('ids', $result['attachments']);
                            $query->execute();
                         } else
                         {
                            $query = $em->createQuery('UPDATE ForumForumBundle:ForumCommentAttachments a SET a.commentId = 0 WHERE a.commentId = :messageid')->setParameter('messageid', $messageid);
                            $query->execute();
                         }
                         $result['status'] = 'OK';
                         // Запись в лог
                         $this->container->get('cms.cmsManager')->logPushFront(0, $this->getName().'.edit', 'Изменение комментария к странице "'.$seoPageEnt->getDescription().'"', $this->container->get('router')->generate('forum_forum_comments_edit').'?id='.$messageent->getId());
                     } else $result['status'] = 'Check error';
                 }
             }
         }
         if ($action == 'moderatecomment')
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $seoPageId = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $messageid = $this->container->get('request_stack')->getCurrentRequest()->get('actionmessageid');
             $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             $module = null;
             $seoPageEnt = $this->container->get('doctrine')->getRepository('BasicCmsBundle:SeoPage')->find($seoPageId);
             if (!empty($seoPageEnt))
             {
                $moduleids = @explode(',', $seoPageEnt->getModules());
                if (is_array($moduleids) && (count($moduleids) > 0))
                {
                    $query = $em->createQuery('SELECT m.parameters FROM BasicCmsBundle:Modules m WHERE m.enabled != 0 AND m.id in (:moduleids) AND m.objectName = \'object.forum\' AND m.moduleType = \'comments\'')->setParameter('moduleids', $moduleids);
                    $module = $query->getResult();
                    if (isset($module[0]['parameters'])) $module = @unserialize($module[0]['parameters']); else $module = null;
                }
             }
             $messageent = $this->container->get('doctrine')->getRepository('ForumForumBundle:ForumComments')->findOneBy(array('id' => $messageid, 'seoPageId' => $seoPageId));
             if (($module != null) && (!empty($messageent)))
             {
                 $errors = false;
                 $result['error'] = '';
                 $result['text'] = '';
                 $result['hide'] = 0;
                 $result['ban'] = 0;
                 $result['status'] = '';
                 $result['action'] = 'object.forum.moderatecomment';
                 $result['actionId'] = $seoPageId;
                 $result['actionMessageId'] = $messageid;
                 if (isset($postfields['text'])) $result['text'] = $postfields['text'];
                 if (isset($postfields['hide'])) $result['hide'] = intval($postfields['hide']); else $result['hide'] = 0;
                 if (isset($postfields['ban'])) $result['ban'] = intval($postfields['ban']); else $result['ban'] = 0;
                 // Проверить на права модератора
                 $checkmoderator = 0;
                 if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2)) 
                 {
                     if (in_array($cmsManager->getCurrentUser()->getId(), $module['moderators'])) $checkmoderator = 1;
                     if ($cmsManager->getCurrentUser()->getUserType() == 'SUPERADMIN') $checkmoderator = 1;
                     if (($cmsManager->getCurrentUser()->getUserType() == 'ADMIN') && ($cmsManager->getCurrentUser()->checkAccess('forum_commentsedit') != 0)) $checkmoderator = 1;
                 }
                 if ($checkmoderator == 0)
                 {
                     $result['status'] = 'Access error';
                 } else
                 {
                     // Валидация
                     if (!preg_match("/^[\s\S]{10,}$/ui", $result['text'])) {$errors = true; $result['error'] = 'Preg error';}
                     $result['text'] = strip_tags($result['text']);
                     // Сохранение если нет ошибок
                     if ($errors == false)
                     {
                         $messageent->setModeratorId($cmsManager->getCurrentUser()->getId());
                         $messageent->setModeratorMessage($result['text']);
                         $messageent->setIsVisible(($result['hide'] != 0 ? 0 : 1));
                         $em->flush();
                         if ($result['ban'] != 0)
                         {
                             $baneduser = $this->container->get('doctrine')->getRepository('BasicCmsBundle:Users')->find($messageent->getCreaterId()); 
                             if (!empty($baneduser))
                             {
                                 if ($banneduser->getUserType() == 'USER') 
                                 {
                                     $banneduser->setBlocked(1);
                                     $em->flush();
                                 }
                             }
                         }
                         $result['status'] = 'OK';
                         // Запись в лог
                         $this->container->get('cms.cmsManager')->logPushFront(0, $this->getName().'.moderate', 'Модерация комментария к странице "'.$seoPageEnt->getDescription().'"', $this->container->get('router')->generate('forum_forum_comments_edit').'?id='.$messageent->getId());
                     } else $result['status'] = 'Check error';
                 }
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) $this->container->get($item)->setFrontAction($locale, $seoPage, $action, $result);
         return $result;
     }
     
     public function getModuleTypes()
     {
         $contents = array('comments'=>'Модуль комментариев к странице сайта');
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) $this->container->get($item)->getModuleTypes($contents);
         return $contents;
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result = null)
     {
         $params = null;
         if ($moduleType == 'comments')
         {
             $params = array();
             // информация о форуме
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $params['seoPageId'] = $seoPage->getId();
             $params['captchaEnabled'] = $parameters['captchaEnabled'];
             $params['onlyAutorizedView'] = $parameters['onlyAutorizedView'];
             $params['onlyAutorizedPost'] = $parameters['onlyAutorizedPost'];
             $params['onlyAutorizedImage'] = $parameters['onlyAutorizedImage'];
             $params['onlyAutorizedDownload'] = $parameters['onlyAutorizedDownload'];
             $params['permissions'] = array();
             $params['permissions']['post'] = 0;
             $params['permissions']['download'] = 0;
             $params['permissions']['image'] = 0;
             $params['permissions']['moderator'] = 0;
             $params['moderators'] = array();
             $params['messages'] = array();
             $params['page'] = 0;
             $params['messageCount'] = 0;
             $params['pageCount'] = 1;
             $params['preContent'] = (!empty($parameters['preContent']) ? $this->container->get('cms.cmsManager')->decodeLocalString($parameters['preContent'], $locale, true) : '');
             $params['postContent'] = (!empty($parameters['postContent']) ? $this->container->get('cms.cmsManager')->decodeLocalString($parameters['postContent'], $locale, true) : '');
             if ((($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2)) || ($parameters['onlyAutorizedView'] == 0))
             {
                 // Определение прав
                 $params['permissions']['post'] = 1;
                 $params['permissions']['download'] = 1;
                 $params['permissions']['image'] = 1;
                 if ((($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2)) && ($parameters['onlyAutorizedPost'] != 0)) $params['permissions']['post'] = 0;
                 if ((($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2)) && ($parameters['onlyAutorizedDownload'] != 0)) $params['permissions']['download'] = 0;
                 if ((($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2)) && ($parameters['onlyAutorizedImage'] != 0)) $params['permissions']['image'] = 0;
                 if ($parameters['isClosed'] != 0) $params['permissions']['post'] = 0;
                 // Поиск модераторов
                 if (is_array($parameters['moderators']) && (count($parameters['moderators']) > 0))
                 {
                     $query = $em->createQuery('SELECT u.id, u.login, u.fullName, u.avatar FROM BasicCmsBundle:Users u '.
                                               'WHERE u.blocked = 2 AND u.id in (:ids) GROUP BY u.id')->setParameter('ids', $parameters['moderators']);
                     $moderators = $query->getResult();
                     if (!is_array($moderators)) $moderators = array();
                     $params['moderators'] = $moderators;
                 }
                 if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2)) 
                 {
                     foreach ($params['moderators'] as $moderator) if ($cmsManager->getCurrentUser()->getId() == $moderator['id']) $params['permissions']['moderator'] = 1;
                     if ($cmsManager->getCurrentUser()->getUserType() == 'SUPERADMIN') $params['permissions']['moderator'] = 1;
                     if (($cmsManager->getCurrentUser()->getUserType() == 'ADMIN') && ($cmsManager->getCurrentUser()->checkAccess('forum_commentsedit') != 0)) $params['permissions']['moderator'] = 1;
                 }
                 // Сообщения с правами на редактирование (+пагинация)
                 $page = $this->container->get('request_stack')->getCurrentRequest()->get('commentspage');
                 $query = $em->createQuery('SELECT count(m.id) as messagecount FROM ForumForumBundle:ForumComments m WHERE m.seoPageId = :id')->setParameter('id', $seoPage->getId());
                 $messagecount = $query->getResult();
                 if (isset($messagecount[0]['messagecount'])) $messagecount = $messagecount[0]['messagecount']; else $messagecount = 0;
                 $pagecount = ceil($messagecount / $parameters['messageInPage']);
                 if ($pagecount < 1) $pagecount = 1;
                 if (($page === null) || ($page == 'last')) $page = $pagecount - 1;
                 $page = intval($page);
                 if ($page < 0) $page = 0;
                 if ($page > ($pagecount - 1)) $page = $pagecount - 1;
                 $query = $em->createQuery('SELECT m.id, m.createDate, m.modifyDate, m.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                           'm.isVisible, m.moderatorMessage, m.moderatorId, um.login as moderatorLogin, um.fullName as moderatorFullName, um.avatar as moderatorAvatar, '.
                                           'm.content FROM ForumForumBundle:ForumComments m '.
                                           'LEFT JOIN BasicCmsBundle:Users u WITH u.id = m.createrId '.
                                           'LEFT JOIN BasicCmsBundle:Users um WITH um.id = m.moderatorId '.
                                           'WHERE m.seoPageId = :id ORDER BY m.createDate ASC, m.id ASC')->setParameter('id', $seoPage->getId())->setFirstResult($page * $parameters['messageInPage'])->setMaxResults($parameters['messageInPage']);
                 $messages = $query->getResult();
                 if (!is_array($messages)) $messsages = array();
                 foreach ($messages as &$message)
                 {
                     $message['newFlag'] = 0;
                     if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getPrevVisitDate() < $message['createDate'])) $message['newFlag'] = 1;
                     $message['attachments'] = array();
                     $message['permissions'] = array();
                     $message['permissions']['edit'] = 0;
                     if (($message['isVisible'] != 0) && ($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2) && ($cmsManager->getCurrentUser()->getId() == $message['createrId'])) $message['permissions']['edit'] = 1;
                 }
                 unset($message);
                 $params['page'] = $page;
                 $params['messageCount'] = $messagecount;
                 $params['pageCount'] = $pagecount;
                 $params['messages'] = $messages;
                 unset($messages);
                 // Найти вложения и добавить к сообщениям
                 $messageIds = array(0);
                 foreach ($params['messages'] as $message) $messageIds[] = $message['id'];
                 $query = $em->createQuery('SELECT a.id, a.commentId, a.downloadDate, a.fileName, a.contentFile, a.isImage FROM ForumForumBundle:ForumCommentAttachments a WHERE a.commentId IN (:messageids)')->setParameter('messageids', $messageIds);
                 $attachments = $query->getResult();
                 if (!is_array($attachments)) $attachments = array();
                 foreach ($attachments as &$attach)
                 {
                     $attach['fileSize'] = @filesize('.'.$attach['contentFile']);
                     $attach['permissions'] = array();
                     $attach['permissions']['download'] = 0;
                     if (($attach['isImage'] != 0) && ($params['permissions']['image'] != 0)) $attach['permissions']['download'] = 1;
                     if (($attach['isImage'] == 0) && ($params['permissions']['download'] != 0)) $attach['permissions']['download'] = 1;
                 }
                 unset($attach);
                 foreach ($attachments as $attach)
                 {
                     foreach ($params['messages'] as &$message) if ($message['id'] == $attach['commentId']) $message['attachments'][] = $attach;
                     unset($message);
                 }
                 unset($attachments);
                 // Скрыть сообщения для немодераторов
                 if ($params['permissions']['moderator'] == 0)
                 {
                     foreach ($params['messages'] as &$message) if ($message['isVisible'] == 0) $message['content'] = '';
                     unset($message);
                 }
                 // Публикация сообщения
                 $params['postMessageValue'] = '';
                 $params['postMessageError'] = '';
                 $params['captchaPath'] = '';
                 $params['captchaError'] = '';
                 $params['postAttachments'] = array();
                 $params['postAttachmentsError'] = '';
                 $params['status'] = '';
                 if ($params['captchaEnabled'] != 0)
                 {
                     $params['captchaPath'] = $this->container->get('router')->generate('basic_cms_captcha').'?target=forumcomments'.$seoPage->getId();
                     $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_forumcomments'.$seoPage->getId(), '');
                 }
                 if (isset($result['action']) && ($result['action'] == 'object.forum.postcomment') && isset($result['actionId']) && ($result['actionId'] == $seoPage->getId()))
                 {
                     if ($result['status'] != 'OK')
                     {
                         $params['status'] = $result['status'];
                         if (isset($result['text'])) $params['postMessageValue'] = $result['text'];
                         if (isset($result['error'])) $params['postMessageError'] = $result['error'];
                         if (isset($result['captchaError'])) $params['captchaError'] = $result['captchaError'];
                         if (isset($result['attachmentsError'])) $params['attachmentsError'] = $result['attachmentsError'];
                         if (isset($result['attachments']) && (count($result['attachments']) > 0)) 
                         {
                             $query = $em->createQuery('SELECT a.id, a.commentId, a.downloadDate, a.fileName, a.contentFile, a.isImage FROM ForumForumBundle:ForumCommentAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $result['attachments']);
                             $pattachments = $query->getResult();
                             foreach ($result['attachments'] as $attid)
                             {
                                 foreach ($pattachments as $pattach)
                                 {
                                     if ($pattach['id'] == $attid) $params['postAttachments'][] = array_merge ($pattach, array('fileSize' => @filesize('.'.$pattach['contentFile'])));
                                 }
                             }
                             unset($pattachments);
                         }
                     } else $params['status'] = 'OK';
                 }
                 // Подготовка данных к редактированию сообщения
                 foreach ($params['messages'] as &$message)
                 {
                     $message['editStatus'] = '';
                     $message['editMessageValue'] = $cmsManager->unclearHtmlCode($message['content']);
                     $message['editMessageError'] = '';
                     $message['editAttachments'] = $message['attachments'];
                     $message['editAttachmentsError'] = '';
                     if (isset($result['action']) && ($result['action'] == 'object.forum.editcomment') && isset($result['actionId']) && ($result['actionId'] == $seoPage->getId()) && isset($result['actionMessageId']) && ($result['actionMessageId'] == $message['id']))
                     {
                         if ($result['status'] != 'OK')
                         {
                             $message['editStatus'] = $result['status'];
                             if (isset($result['text'])) $message['editMessageValue'] = $result['text'];
                             if (isset($result['error'])) $message['editMessageError'] = $result['error'];
                             if (isset($result['attachmentsError'])) $message['editAttachmentsError'] = $result['attachmentsError'];
                             if (isset($result['attachments']) && (count($result['attachments']) > 0)) 
                             {
                                 $message['editAttachments'] = array();
                                 $query = $em->createQuery('SELECT a.id, a.commentId, a.downloadDate, a.fileName, a.contentFile, a.isImage FROM ForumForumBundle:ForumCommentAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $result['attachments']);
                                 $pattachments = $query->getResult();
                                 foreach ($result['attachments'] as $attid)
                                 {
                                     foreach ($pattachments as $pattach)
                                     {
                                         if ($pattach['id'] == $attid) $message['editAttachments'][] = array_merge ($pattach, array('fileSize' => @filesize('.'.$pattach['contentFile'])));
                                     }
                                 }
                                 unset($pattachments);
                             }
                         } else $message['editStatus'] = 'OK';
                     }
                     $message['moderateStatus'] = '';
                     $message['moderateTextValue'] = '';
                     $message['moderateTextError'] = '';
                     $message['moderateHideValue'] = '';
                     $message['moderateBanValue'] = '';
                     if (isset($result['action']) && ($result['action'] == 'object.forum.moderatecomment') && isset($result['actionId']) && ($result['actionId'] == $seoPage->getId()) && isset($result['actionMessageId']) && ($result['actionMessageId'] == $message['id']))
                     {
                         if ($result['status'] != 'OK')
                         {
                             $message['moderateStatus'] = $result['status'];
                             if (isset($result['text'])) $message['moderateTextValue'] = $result['text'];
                             if (isset($result['error'])) $message['moderateTextError'] = $result['error'];
                             if (isset($result['hide'])) $message['moderateHideValue'] = $result['hide'];
                             if (isset($result['ban'])) $message['moderateBanValue'] = $result['ban'];
                         } else $message['moderateStatus'] = 'OK';
                     }
                 }
                 unset($message);
             }
         }
         if ($params != null) return $params;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             if (strpos($item,'addone.forum.') === 0) $params = $this->container->get($item)->getFrontModule($locale, $seoPage, $moduleType, $parameters, $result);
             if ($params != null) return $params;
         }
         return null;
     }
     
    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        if ($module == 'comments')
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
            $locales = $query->getResult();
            if (!is_array($locales)) $locales = array();
            $parameters = array();
            $parameters['isClosed'] = '';
            $parameters['captchaEnabled'] = '';
            $parameters['onlyAutorizedView'] = '';
            $parameters['onlyAutorizedPost'] = '';
            $parameters['onlyAutorizedImage'] = '';
            $parameters['onlyAutorizedDownload'] = '';
            $parameters['allowTags'] = '';
            $parameters['allowStyleProp'] = '';
            $parameters['replaceUrls'] = '';
            $parameters['messageInPage'] = '';
            $parameters['moderators'] = array();
            $parameters['preContent'] = '';
            $parameters['postContent'] = '';
            $moderators = array();
            if (isset($nullparameters['isClosed'])) $parameters['isClosed'] = $nullparameters['isClosed'];
            if (isset($nullparameters['captchaEnabled'])) $parameters['captchaEnabled'] = $nullparameters['captchaEnabled'];
            if (isset($nullparameters['onlyAutorizedView'])) $parameters['onlyAutorizedView'] = $nullparameters['onlyAutorizedView'];
            if (isset($nullparameters['onlyAutorizedPost'])) $parameters['onlyAutorizedPost'] = $nullparameters['onlyAutorizedPost'];
            if (isset($nullparameters['onlyAutorizedImage'])) $parameters['onlyAutorizedImage'] = $nullparameters['onlyAutorizedImage'];
            if (isset($nullparameters['onlyAutorizedDownload'])) $parameters['onlyAutorizedDownload'] = $nullparameters['onlyAutorizedDownload'];
            if (isset($nullparameters['allowTags'])) $parameters['allowTags'] = $nullparameters['allowTags'];
            if (isset($nullparameters['allowStyleProp'])) $parameters['allowStyleProp'] = $nullparameters['allowStyleProp'];
            if (isset($nullparameters['replaceUrls'])) $parameters['replaceUrls'] = $nullparameters['replaceUrls'];
            if (isset($nullparameters['messageInPage'])) $parameters['messageInPage'] = $nullparameters['messageInPage'];
            if (isset($nullparameters['moderators']) && is_array($nullparameters['moderators'])) 
            {
                foreach ($nullparameters['moderators'] as $moderatorid) 
                {
                    $moderatorent = $this->container->get('doctrine')->getRepository('BasicCmsBundle:Users')->find($moderatorid);
                    if (!empty($moderatorent))
                    {
                        $parameters['moderators'][] = intval($moderatorid);
                        $moderators[] = array('login' => $moderatorent->getLogin(), 'fullName' => $moderatorent->getFullName(), 'id' => intval($moderatorid), 'error'=>'');
                    }
                    unset($moderatorent);
                }
            }
            if (isset($nullparameters['preContent'])) $parameters['preContent'] = $nullparameters['preContent'];
            if (isset($nullparameters['postContent'])) $parameters['postContent'] = $nullparameters['postContent'];
            $parameterserror['isClosed'] = '';
            $parameterserror['captchaEnabled'] = '';
            $parameterserror['onlyAutorizedView'] = '';
            $parameterserror['onlyAutorizedPost'] = '';
            $parameterserror['onlyAutorizedImage'] = '';
            $parameterserror['onlyAutorizedDownload'] = '';
            $parameterserror['allowTags'] = '';
            $parameterserror['allowStyleProp'] = '';
            $parameterserror['replaceUrls'] = '';
            $parameterserror['messageInPage'] = '';
            $parameterserror['moderators'] = '';
            $parameterserror['preContent'] = array();
            $parameterserror['postContent'] = array();
            $parameterserror['preContent']['default'] = '';
            $parameterserror['postContent']['default'] = '';
            foreach ($locales as $locale)
            {
                $parameterserror['preContent'][$locale['shortName']] = '';
                $parameterserror['postContent'][$locale['shortName']] = '';
            }
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postparameters = $request->get('parameters');
                if (isset($postparameters['isClosed'])) $parameters['isClosed'] = intval($postparameters['isClosed']); else $parameters['isClosed'] = 0;
                if (isset($postparameters['captchaEnabled'])) $parameters['captchaEnabled'] = intval($postparameters['captchaEnabled']); else $parameters['captchaEnabled'] = 0;
                if (isset($postparameters['onlyAutorizedView'])) $parameters['onlyAutorizedView'] = intval($postparameters['onlyAutorizedView']); else $parameters['onlyAutorizedView'] = 0;
                if (isset($postparameters['onlyAutorizedPost'])) $parameters['onlyAutorizedPost'] = intval($postparameters['onlyAutorizedPost']); else $parameters['onlyAutorizedPost'] = 0;
                if (isset($postparameters['onlyAutorizedImage'])) $parameters['onlyAutorizedImage'] = intval($postparameters['onlyAutorizedImage']); else $parameters['onlyAutorizedImage'] = 0;
                if (isset($postparameters['onlyAutorizedDownload'])) $parameters['onlyAutorizedDownload'] = intval($postparameters['onlyAutorizedDownload']); else $parameters['onlyAutorizedDownload'] = 0;
                if (isset($postparameters['allowTags'])) $parameters['allowTags'] = $postparameters['allowTags'];
                if (isset($postparameters['allowStyleProp'])) $parameters['allowStyleProp'] = intval($postparameters['allowStyleProp']); else $parameters['allowStyleProp'] = 0;
                if (isset($postparameters['replaceUrls'])) $parameters['replaceUrls'] = intval($postparameters['replaceUrls']); else $parameters['replaceUrls'] = 0;
                if (isset($postparameters['messageInPage'])) $parameters['messageInPage'] = $postparameters['messageInPage'];
                $parameters['moderators'] = array();
                $moderators = array();
                if (isset($postparameters['moderators']) && is_array($postparameters['moderators'])) 
                {
                    foreach ($postparameters['moderators'] as $moderatorid) 
                    {
                        $moderatorent = $this->container->get('doctrine')->getRepository('BasicCmsBundle:Users')->find($moderatorid);
                        if (!empty($moderatorent))
                        {
                            $parameters['moderators'][] = intval($moderatorid);
                            $moderators[] = array('login' => $moderatorent->getLogin(), 'fullName' => $moderatorent->getFullName(), 'id' => intval($moderatorid), 'error'=>'');
                        }
                        unset($moderatorent);
                    }
                }
                if (isset($postparameters['preContent'])) $parameters['preContent'] = $this->container->get('cms.cmsManager')->encodeLocalString($postparameters['preContent']);
                if (isset($postparameters['postContent'])) $parameters['postContent'] = $this->container->get('cms.cmsManager')->encodeLocalString($postparameters['postContent']);
                if (!preg_match("/^([A-z0-9]{1,10}(\s*\,\s*[A-z0-9]{1,10})*)?$/ui", $parameters['allowTags'])) {$errors = true; $parameterserror['allowTags'] = 'Тэги должны состоять из 1-10 латинских букв и разделяться запятыми';}
                if ((!preg_match("/^[\d]{2,3}$/ui", $parameters['messageInPage'])) || (intval($parameters['messageInPage']) < 10) || (intval($parameters['messageInPage']) > 100)) {$errors = true; $parameterserror['messageInPage'] = 'Должно быть указано число от 10 до 100';}
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
                $tmpcnt = $parameters['preContent'];
                $tmpcnt2 = $parameters['postContent'];
                $parameters['preContent'] = array();
                $parameters['preContent']['default'] = $this->container->get('cms.cmsManager')->decodeLocalString($tmpcnt, 'default', false);
                $parameters['postContent'] = array();
                $parameters['postContent']['default'] = $this->container->get('cms.cmsManager')->decodeLocalString($tmpcnt2, 'default', false);
                foreach ($locales as $locale)
                {
                    $parameters['preContent'][$locale['shortName']] = $this->container->get('cms.cmsManager')->decodeLocalString($tmpcnt, $locale['shortName'], false);
                    $parameters['postContent'][$locale['shortName']] = $this->container->get('cms.cmsManager')->decodeLocalString($tmpcnt2, $locale['shortName'], false);
                }
                return $this->container->get('templating')->render('ForumForumBundle:Modules:comments.html.twig',array('locales' => $locales, 'parameters' => $parameters,'parameterserror' => $parameterserror,'moderators'=>$moderators));
            }
        }
        $answer = null;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.forum.') === 0) $answer = $this->container->get($item)->getAdminModuleController($request, $module, $actionType, $nullparameters);
            if ($answer != null) return $answer;
        }
        return null;
    }

    public function getInfoTitle($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT f.title FROM ForumForumBundle:Forums f WHERE f.id = :id')->setParameter('id', $contentId);
             $forum = $query->getResult();
             if (isset($forum[0]['title']))
             {
                 $title = array();
                 $title['default'] = $forum[0]['title'];
                 return $this->container->get('cms.cmsManager')->encodeLocalString($title);
             }
         }
         if ($contentType == 'create')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT p.title FROM ForumForumBundle:ForumCreatePages p WHERE p.id = :id')->setParameter('id', $contentId);
             $page = $query->getResult();
             if (isset($page[0]['title'])) return $page[0]['title'];
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.forum.') === 0) $answer = $this->container->get($item)->getInfoTitle($contentType, $contentId);
             if ($answer != null) return $answer;
         }
         return null;
    }

    public function getInfoSitemap($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT f.avatar, f.lastMessageDate FROM ForumForumBundle:Forums f WHERE f.id = :id AND f.enabled != 0')->setParameter('id', $contentId);
             $forum = $query->getResult();
             if (isset($forum[0]))
             {
                 $info = array();
                 $info['priority'] = '0.2';
                 $info['modifyDate'] = $forum[0]['lastMessageDate'];
                 $info['avatar'] = $forum[0]['avatar'];
                 return $info;
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.forum.') === 0) $answer = $this->container->get($item)->getInfoSitemap($contentType, $contentId);
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
        $params['avatar'] = array('type'=>'text','ext'=>0,'name'=>'Аватар');
        $params['createrLogin'] = array('type'=>'text','ext'=>1,'name'=>'Логин автора');
        $params['createrFullName'] = array('type'=>'text','ext'=>1,'name'=>'Имя автора');
        $params['createrAvatar'] = array('type'=>'text','ext'=>1,'name'=>'Аватар автора');
        $params['createrEmail'] = array('type'=>'text','ext'=>1,'name'=>'E-mail автора');
        $params['url'] = array('type'=>'text','ext'=>1,'name'=>'URL страницы');
        $params['content'] = array('type'=>'text','ext'=>0,'name'=>'Текст первого сообщения');
        $params['isVisible'] = array('type'=>'bool','ext'=>0,'name'=>'Первое сообщение видимо');
        $params['isClosed'] = array('type'=>'bool','ext'=>0,'name'=>'Форум закрыт');
        $params['isImportant'] = array('type'=>'bool','ext'=>0,'name'=>'Форум важный');
        $params['messageCount'] = array('type'=>'int','ext'=>1,'name'=>'Количество сообщений');
        $params['lastMessageId'] = array('type'=>'int','ext'=>0,'name'=>'ID последнего сообщения');
        $params['lastMessageDate'] = array('type'=>'date','ext'=>0,'name'=>'Дата последнего сообщения');
        $params['lastMessageСontent'] = array('type'=>'text','ext'=>1,'name'=>'Текст последнего сообщения');
        $params['lastMessageIsVisible'] = array('type'=>'bool','ext'=>1,'name'=>'Последнее сообщение видимо');
        $params['lastMessageCreaterId'] = array('type'=>'int','ext'=>1,'name'=>'ID автора последнего сообщения');
        $params['lastMessageCreaterLogin'] = array('type'=>'text','ext'=>1,'name'=>'Логин автора последнего сообщения');
        $params['lastMessageCreaterFullName'] = array('type'=>'text','ext'=>1,'name'=>'Имя автора последнего сообщения');
        $params['lastMessageCreaterAvatar'] = array('type'=>'text','ext'=>1,'name'=>'Аватар автора последнего сообщения');
        $params['lastMessageCreaterEmail'] = array('type'=>'text','ext'=>1,'name'=>'E-mail автора последнего сообщения');
        $params['onlyAutorizedView'] = array('type'=>'bool','ext'=>0,'name'=>'Требует авторизации');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) $this->container->get($item)->getTaxonomyParameters($params);
        return $params;
    }
    
    public function getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $queryCount = false, $itemIds = null)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        $allparameters = array($pageParameters['sortField']);
        $queryparameters = array();
        foreach ($pageParameters['fields'] as $field) $allparameters[] = $field;
        foreach ($filterParameters as $field) $allparameters[] = $field['parameter'];
        $from = ' FROM ForumForumBundle:Forums f';
        if (in_array('createrLogin', $allparameters) || 
            in_array('createrFullName', $allparameters) ||    
            in_array('createrAvatar', $allparameters) ||    
            in_array('createrEmail', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId';}
        if (in_array('url', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'view\' AND sp.contentId = f.id';}
        if (in_array('lastMessageContent', $allparameters) || 
            in_array('lastMessageIsVisible', $allparameters) || 
            in_array('lastMessageCreaterId', $allparameters) || 
            in_array('lastMessageCreaterLogin', $allparameters) || 
            in_array('lastMessageCreaterFullName', $allparameters) || 
            in_array('lastMessageCreaterAvatar', $allparameters) || 
            in_array('lastMessageCreaterEmail', $allparameters)) {$from .= ' LEFT JOIN ForumForumBundle:ForumMessages fm WITH fm.id = f.lastMessageId';}
        if (in_array('lastMessageCreaterLogin', $allparameters) || 
            in_array('lastMessageCreaterFullName', $allparameters) || 
            in_array('lastMessageCreaterAvatar', $allparameters) || 
            in_array('lastMessageCreaterEmail', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:Users lu WITH lu.id = fm.createrId';}
        
        $select = 'SELECT f.id';
        if (in_array('createDate', $pageParameters['fields'])) $select .=',f.createDate';
        if (in_array('modifyDate', $pageParameters['fields'])) $select .=',f.modifyDate';
        if (in_array('createrId', $pageParameters['fields'])) $select .=',f.createrId';
        if (in_array('title', $pageParameters['fields'])) $select .=',f.title';
        if (in_array('description', $pageParameters['fields'])) $select .=',f.description';
        if (in_array('avatar', $pageParameters['fields'])) $select .=',f.avatar';
        if (in_array('createrLogin', $pageParameters['fields'])) $select .=',u.login as createrLogin';
        if (in_array('createrFullName', $pageParameters['fields'])) $select .=',u.fullName as createrFullName';
        if (in_array('createrAvatar', $pageParameters['fields'])) $select .=',u.avatar as createrAvatar';
        if (in_array('createrEmail', $pageParameters['fields'])) $select .=',u.email as createrEmail';
        if (in_array('url', $pageParameters['fields'])) $select .=',sp.url';
        if (in_array('content', $pageParameters['fields'])) $select .=',f.content';
        if (in_array('isVisible', $pageParameters['fields'])) $select .=',f.isVisible';
        if (in_array('isClosed', $pageParameters['fields'])) $select .=',f.isClosed';
        if (in_array('isImportant', $pageParameters['fields'])) $select .=',f.isImportant';
        if (in_array('messageCount', $pageParameters['fields'])) $select .=',(SELECT count(ma.id) FROM ForumForumBundle:ForumMessages ma WHERE ma.forumId = f.id) as messageCount ';
        if (in_array('lastMessageId', $pageParameters['fields'])) $select .=',f.lastMessageId';
        if (in_array('lastMessageDate', $pageParameters['fields'])) $select .=',f.lastMessageDate';
        if (in_array('lastMessageСontent', $pageParameters['fields'])) $select .=',fm.content as lastMessageContent';
        if (in_array('lastMessageIsVisible', $pageParameters['fields'])) $select .=',fm.isVisible as lastMessageIsVisible';
        if (in_array('lastMessageCreaterId', $pageParameters['fields'])) $select .=',fm.createrId as lastMessageCreaterId';
        if (in_array('lastMessageCreaterLogin', $pageParameters['fields'])) $select .=',lu.login as lastMessageCreaterLogin';
        if (in_array('lastMessageCreaterFullName', $pageParameters['fields'])) $select .=',lu.fullName as lastMessageCreaterFullName';
        if (in_array('lastMessageCreaterAvatar', $pageParameters['fields'])) $select .=',lu.avatar as lastMessageCreaterAvatar';
        if (in_array('lastMessageCreaterEmail', $pageParameters['fields'])) $select .=',lu.email as lastMessageCreaterEmail';
        if (in_array('onlyAutorizedView', $pageParameters['fields'])) $select .=',f.onlyAutorizedView';
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
                if ($field['compare'] == 'noeq') {$where .= ' AND f.title != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND f.title = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND f.title like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'description')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND f.description != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND f.description = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND f.description like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
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
            if ($field['parameter'] == 'content')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND f.content != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND f.content = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND f.content like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'isVisible')
            {
                if ($field['value'] != 0) {$where .= ' AND f.isVisible != 0';}
                if ($field['value'] == 0) {$where .= ' AND f.isVisible = 0';}
            }
            if ($field['parameter'] == 'isClosed')
            {
                if ($field['value'] != 0) {$where .= ' AND f.isClosed != 0';}
                if ($field['value'] == 0) {$where .= ' AND f.isClosed = 0';}
            }
            if ($field['parameter'] == 'isImportant')
            {
                if ($field['value'] != 0) {$where .= ' AND f.isImportant != 0';}
                if ($field['value'] == 0) {$where .= ' AND f.isImportant = 0';}
            }
            if ($field['parameter'] == 'messageCount')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND (SELECT count(mb.id) FROM ForumForumBundle:ForumMessages mb WHERE mb.forumId = f.id) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND (SELECT count(mb.id) FROM ForumForumBundle:ForumMessages mb WHERE mb.forumId = f.id) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND (SELECT count(mb.id) FROM ForumForumBundle:ForumMessages mb WHERE mb.forumId = f.id) > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND (SELECT count(mb.id) FROM ForumForumBundle:ForumMessages mb WHERE mb.forumId = f.id) < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND (SELECT count(mb.id) FROM ForumForumBundle:ForumMessages mb WHERE mb.forumId = f.id) >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND (SELECT count(mb.id) FROM ForumForumBundle:ForumMessages mb WHERE mb.forumId = f.id) <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'lastMessageId')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND f.lastMessageId != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND f.lastMessageId = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND f.lastMessageId > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND f.lastMessageId < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND f.lastMessageId >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND f.lastMessageId <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'lastMessageDate')
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
                    if ($field['compare'] == 'noeq') {$where .= ' AND f.lastMessageDate != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND f.lastMessageDate = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND f.lastMessageDate > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND f.lastMessageDate < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND f.lastMessageDate >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND f.lastMessageDate <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                }
                unset($date);
            }
            if ($field['parameter'] == 'lastMessageContent')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND fm.content != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND fm.content = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND fm.content like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'lastMessageIsVisible')
            {
                if ($field['value'] != 0) {$where .= ' AND fm.isVisible != 0';}
                if ($field['value'] == 0) {$where .= ' AND fm.isVisible = 0';}
            }
            if ($field['parameter'] == 'lastMessageCreaterId')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND fm.createrId != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND fm.createrId = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND fm.createrId > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND fm.createrId < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND fm.createrId >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND fm.createrId <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'lastMessageCreaterLogin')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND lu.login != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND lu.login = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND lu.login like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'lastMessageCreaterFullName')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND lu.fullName != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND lu.fullName = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND lu.fullName like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'lastMessageCreaterAvatar')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND lu.avatar != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND lu.avatar = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND lu.avatar like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'lastMessageCreaterEmail')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND lu.email != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND lu.email = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND lu.email like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'onlyAutorizedView')
            {
                if ($field['value'] != 0) {$where .= ' AND f.onlyAutorizedView != 0';}
                if ($field['value'] == 0) {$where .= ' AND f.onlyAutorizedView = 0';}
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
        $order = ' ORDER BY f.isImportant DESC, f.lastMessageDate DESC';
        if ($pageParameters['sortField'] == 'createDate') $order = ' ORDER BY f.createDate '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'modifyDate') $order = ' ORDER BY f.modifyDate '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrId') $order = ' ORDER BY f.createrId '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'title') $order = ' ORDER BY f.title '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'description') $order = ' ORDER BY f.description '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'avatar') $order = ' ORDER BY f.avatar '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrLogin') $order = ' ORDER BY u.login '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrFullName') $order = ' ORDER BY u.fullName '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrAvatar') $order = ' ORDER BY u.avatar '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrEmail') $order = ' ORDER BY u.email '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'url') $order = ' ORDER BY sp.url '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'content') $order = ' ORDER BY f.content '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'isVisible') $order = ' ORDER BY f.isVisible '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'isClosed') $order = ' ORDER BY f.isClosed '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'isImportant') $order = ' ORDER BY f.isImportant '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'messageCount') $order = ' ORDER BY messageCount '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'lastMessageId') $order = ' ORDER BY f.lastMessageId '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'lastMessageDate') $order = ' ORDER BY f.lastMessageDate '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'lastMessageСontent') $order = ' ORDER BY fm.content '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'lastMessageIsVisible') $order = ' ORDER BY fm.isVisible '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'lastMessageCreaterId') $order = ' ORDER BY fm.createrId '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'lastMessageCreaterLogin') $order = ' ORDER BY lu.login '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'lastMessageCreaterFullName') $order = ' ORDER BY lu.fullName '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'lastMessageCreaterAvatar') $order = ' ORDER BY lu.avatar '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'lastMessageCreaterEmail') $order = ' ORDER BY lu.email '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'onlyAutorizedView') $order = ' ORDER BY f.onlyAutorizedView '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        // Обход плагинов
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) $this->container->get($item)->getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $itemIds, $allparameters, $queryparameters, $queryparameterscount, $select, $from, $where, $order);
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
        foreach ($params['items'] as &$forumitem) 
        {
            if (isset($forumitem['url']) && ($forumitem['url'] != '')) $forumitem['url'] = '/'.($locale != '' ? $locale.'/' : '').$forumitem['url'];
            if (isset($forumitem['lastMessageDate']))
            {
                $forumitem['newFlag'] = 0;
                if (($this->container->get('cms.cmsManager')->getCurrentUser() != null) && ($this->container->get('cms.cmsManager')->getCurrentUser()->getPrevVisitDate() < $forumitem['lastMessageDate'])) $forumitem['newFlag'] = 1;
            }
        }
        unset($forumitem);
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.forum.') === 0) $this->container->get($item)->getTaxonomyPostProcess($locale, $params);
    }

    
    public function getTaxonomyExtCatInfo(&$tree)
    {
        $cmsManager = $this->container->get('cms.cmsManager');
        $categoryIds = array();
        foreach ($tree as $cat) $categoryIds[] = $cat['id'];
        $em = $this->container->get('doctrine')->getEntityManager();
        $query = $em->createQuery('SELECT tl.taxonomyId, count(f.id) as forumCount FROM BasicCmsBundle:TaxonomiesLinks tl '.
                                  'LEFT JOIN ForumForumBundle:Forums f WITH f.id = tl.itemId '.
                                  'WHERE tl.taxonomyId IN (:taxonomyids) AND f.enabled != 0 GROUP BY tl.taxonomyId')->setParameter('taxonomyids', $categoryIds);
        $extCountInfo = $query->getResult();
        if (!is_array($extCountInfo)) $extCountInfo = array();
        $nulltime = new \DateTime('now');
        $nulltime->setTimestamp(0);
        foreach ($tree as &$cat)
        {
            $cat['forumCount'] = 0;
            $cat['forumMofidyDate'] = $nulltime;
            /*foreach ($extInfo as $info)
                if ($info['taxonomyId'] == $cat['id'])
                {
                    $cat['forumCount'] = $info['forumCount'];
                }*/
            $categoryIds = array($cat['id']);
            do
            {
                $added = 0;
                foreach ($tree as $cat2) if ((in_array($cat2['parent'], $categoryIds)) && (!in_array($cat2['id'], $categoryIds))) {$categoryIds[] = $cat2['id']; $added++;}
            } while ($added != 0);
            foreach ($extCountInfo as $info) if (in_array($info['taxonomyId'], $categoryIds)) $cat['forumCount'] += $info['forumCount'];
            $query = $em->createQuery('SELECT f.id, f.lastMessageDate, f.lastMessageId, '.
                                      'f.content, f.isVisible, f.createrId, fu.login as createrLogin, fu.fullName as createrFullName, fu.avatar as createrAvatar, '.
                                      'fm.content as messageContent, fm.isVisible as messageIsVisible, fm.createrId as messageCreaterId, '.
                                      'fmu.login as messageCreaterLogin, fmu.fullName as messageCreaterFullName, fmu.avatar as messageCreaterAvatar, sp.url FROM ForumForumBundle:Forums f '.
                                      'LEFT JOIN BasicCmsBundle:TaxonomiesLinks tl WITH f.id = tl.itemId '.
                                      'LEFT JOIN BasicCmsBundle:Users fu WITH fu.id = f.createrId '.
                                      'LEFT JOIN ForumForumBundle:ForumMessages fm WITH fm.id = f.lastMessageId '.
                                      'LEFT JOIN BasicCmsBundle:Users fmu WITH fmu.id = fm.createrId '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'view\' AND sp.contentId = f.id '.
                                      'WHERE tl.taxonomyId IN (:taxonomyids) AND f.enabled != 0 ORDER BY f.lastMessageDate DESC')->setParameter('taxonomyids', $categoryIds)->setMaxResults(1);
            $extCatInfo = $query->getResult();
            $cat['forumModifyId'] = '';
            $cat['forumModifyContent'] = '';
            $cat['forumModifyIsVisible'] = '';
            $cat['forumModifyCreaterId'] = '';
            $cat['forumModifyCreaterLogin'] = '';
            $cat['forumModifyCreaterFullName'] = '';
            $cat['forumModifyCreaterAvatar'] = '';
            $cat['forumModifyUrl'] = '';
            $cat['forumNewFlag'] = 0;
            if (isset($extCatInfo[0]) && ($extCatInfo[0]['id'] != null))
            {
                $cat['forumModifyId'] = $extCatInfo[0]['id'];
                if ($extCatInfo[0]['lastMessageId'] != 0)
                {
                    $cat['forumModifyContent'] = $extCatInfo[0]['messageContent'];
                    $cat['forumModifyIsVisible'] = $extCatInfo[0]['messageIsVisible'];
                    $cat['forumModifyCreaterId'] = $extCatInfo[0]['messageCreaterId'];
                    $cat['forumModifyCreaterLogin'] = $extCatInfo[0]['messageCreaterLogin'];
                    $cat['forumModifyCreaterFullName'] = $extCatInfo[0]['messageCreaterFullName'];
                    $cat['forumModifyCreaterAvatar'] = $extCatInfo[0]['messageCreaterAvatar'];
                } else
                {
                    $cat['forumModifyContent'] = $extCatInfo[0]['content'];
                    $cat['forumModifyIsVisible'] = $extCatInfo[0]['isVisible'];
                    $cat['forumModifyCreaterId'] = $extCatInfo[0]['createrId'];
                    $cat['forumModifyCreaterLogin'] = $extCatInfo[0]['createrLogin'];
                    $cat['forumModifyCreaterFullName'] = $extCatInfo[0]['createrFullName'];
                    $cat['forumModifyCreaterAvatar'] = $extCatInfo[0]['createrAvatar'];
                }
                $cat['forumMofidyDate'] = $extCatInfo[0]['lastMessageDate'];
                //$cat['forumCount'] = $extCatInfo[0]['forumCount'];
                $cat['forumModifyUrl'] = $extCatInfo[0]['url'];
                if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getPrevVisitDate() < $extCatInfo[0]['lastMessageDate'])) $cat['forumNewFlag'] = 1;
            }
        }
        unset($cat);
    }

//**************************************************
// Поиск по сайту
//**************************************************

    public function getSearchOptions(&$options)
    {
        $options['object.forum.on'] = 'Искать информацию в форуме';
        $options['object.forum.onmessage'] = 'Выводить каждое сообщение форума отдельно';
        $options['object.forum.onlypage'] = 'Искать информацию в форумах только со страницей сайта';
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.forum.') === 0) $this->container->get($item)->getSearchOptions($options);
        }
    }
    
    public function getSearchItemCount($options, $searchstring, $locale)
    {
        $count = 0;
        if (isset($options['object.forum.on']) && ($options['object.forum.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            if (isset($options['object.forum.onmessage']) && ($options['object.forum.onmessage'] != 0))
            {
                $query = $em->createQuery('SELECT count(f.id) as forumcount FROM ForumForumBundle:Forums f '.
                                          'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'view\' AND sp.contentId = f.id '.
                                          'WHERE (f.title LIKE :searchstring OR f.description LIKE :searchstring OR f.content LIKE :searchstring)'.
                                          ((isset($options['object.forum.onlypage']) && ($options['object.forum.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : ''))->setParameter('searchstring', $searchstring);
                $forumcount = $query->getResult();
                if (isset($forumcount[0]['forumcount'])) $count += $forumcount[0]['forumcount'];
                $query = $em->createQuery('SELECT count(fm.id) as forumcount FROM ForumForumBundle:ForumMessages fm '.
                                          'LEFT JOIN ForumForumBundle:Forums f WITH fm.forumId = f.id '.
                                          'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'view\' AND sp.contentId = f.id '.
                                          'WHERE (fm.content LIKE :searchstring)'.
                                          ((isset($options['object.forum.onlypage']) && ($options['object.forum.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : ''))->setParameter('searchstring', $searchstring);
                $forumcount = $query->getResult();
                if (isset($forumcount[0]['forumcount'])) $count += $forumcount[0]['forumcount'];
            } else
            {
                $query = $em->createQuery('SELECT count(DISTINCT f.id) as forumcount FROM ForumForumBundle:ForumMessages fm '.
                                          'LEFT JOIN ForumForumBundle:Forums f WITH fm.forumId = f.id '.
                                          'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'view\' AND sp.contentId = f.id '.
                                          'WHERE (fm.content LIKE :searchstring OR f.title LIKE :searchstring OR f.description LIKE :searchstring OR f.content LIKE :searchstring)'.
                                          ((isset($options['object.forum.onlypage']) && ($options['object.forum.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : ''))->setParameter('searchstring', $searchstring);
                $forumcount = $query->getResult();
                if (isset($forumcount[0]['forumcount'])) $count += $forumcount[0]['forumcount'];
            }
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.forum.') === 0) $count += $this->container->get($item)->getSearchItemCount($options, $searchstring, $locale);
        }
        return $count;
    }
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, &$items)
    {
        if (isset($options['object.forum.on']) && ($options['object.forum.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            if (isset($options['object.forum.onmessage']) && ($options['object.forum.onmessage'] != 0))
            {
                $query = $em->createQuery('SELECT count(f.id) as forumcount FROM ForumForumBundle:Forums f '.
                                          'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'view\' AND sp.contentId = f.id '.
                                          'WHERE (f.title LIKE :searchstring OR f.description LIKE :searchstring OR f.content LIKE :searchstring)'.
                                          ((isset($options['object.forum.onlypage']) && ($options['object.forum.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : ''))->setParameter('searchstring', $searchstring);
                $forumcount = $query->getResult();
                if (isset($forumcount[0]['forumcount'])) $forumcount = $forumcount[0]['forumcount']; else $forumcount = 0;
                $query = $em->createQuery('SELECT \'forum\' as object, f.id, f.title, f.content as description, f.modifyDate, \'\' as avatar, f.createrId, '.
                                          'u.avatar as createrAvatar, u.login as createrLogin, u.fullName as createrFullName, sp.url FROM ForumForumBundle:Forums f '.
                                          'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'view\' AND sp.contentId = f.id '.
                                          'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.
                                          'WHERE (f.title LIKE :searchstring OR f.description LIKE :searchstring OR f.content LIKE :searchstring)'.
                                          ((isset($options['object.forum.onlypage']) && ($options['object.forum.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : '').
                                          ($sortdir == 0 ? ' ORDER BY f.modifyDate ASC' : ' ORDER BY f.modifyDate DESC'))->setParameter('searchstring', $searchstring)->setFirstResult($start)->setMaxResults($limit);
                $result = $query->getResult();
                if (is_array($result))
                {
                    $limit = $limit - count($result);
                    $start = $start - $forumcount;
                    foreach ($result as $item) $items[] = $item;
                }
                unset($result);
                if ($limit > 0)
                {
                    $query = $em->createQuery('SELECT count(fm.id) as forumcount FROM ForumForumBundle:ForumMessages fm '.
                                              'LEFT JOIN ForumForumBundle:Forums f WITH fm.forumId = f.id '.
                                              'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'view\' AND sp.contentId = f.id '.
                                              'WHERE (fm.content LIKE :searchstring)'.
                                              ((isset($options['object.forum.onlypage']) && ($options['object.forum.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : ''))->setParameter('searchstring', $searchstring);
                    $forumcount = $query->getResult();
                    if (isset($forumcount[0]['forumcount'])) $forumcount = $forumcount[0]['forumcount']; else $forumcount = 0;
                    $query = $em->createQuery('SELECT \'forum\' as object, f.id, f.title, fm.content as description, fm.modifyDate, \'\' as avatar, f.createrId, '.
                                              'u.avatar as createrAvatar, u.login as createrLogin, u.fullName as createrFullName, sp.url FROM ForumForumBundle:ForumMessages fm '.
                                              'LEFT JOIN ForumForumBundle:Forums f WITH fm.forumId = f.id '.
                                              'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'view\' AND sp.contentId = f.id '.
                                              'LEFT JOIN BasicCmsBundle:Users u WITH u.id = fm.createrId '.
                                              'WHERE (fm.content LIKE :searchstring)'.
                                              ((isset($options['object.forum.onlypage']) && ($options['object.forum.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : '').
                                              ($sortdir == 0 ? ' ORDER BY fm.modifyDate ASC' : ' ORDER BY fm.modifyDate DESC'))->setParameter('searchstring', $searchstring)->setFirstResult(($start > 0 ? $start : 0))->setMaxResults($limit);
                    $result = $query->getResult();
                    if (is_array($result))
                    {
                        $limit = $limit - count($result);
                        $start = $start - $forumcount;
                        foreach ($result as $item) $items[] = $item;
                    }
                    unset($result);
                }
            } else
            {
                $query = $em->createQuery('SELECT \'forum\' as object, f.id, f.title, f.description, f.lastMessageDate, \'\' as avatar, f.createrId, '.
                                          'u.avatar as createrAvatar, u.login as createrLogin, u.fullName as createrFullName, sp.url FROM ForumForumBundle:ForumMessages fm '.
                                          'LEFT JOIN ForumForumBundle:Forums f WITH fm.forumId = f.id '.
                                          'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'view\' AND sp.contentId = f.id '.
                                          'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.
                                          'WHERE (fm.content LIKE :searchstring OR f.title LIKE :searchstring OR f.description LIKE :searchstring OR f.content LIKE :searchstring)'.
                                          ((isset($options['object.forum.onlypage']) && ($options['object.forum.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : '').' GROUP BY f.id'.
                                          ($sortdir == 0 ? ' ORDER BY f.lastMessageDate ASC' : ' ORDER BY f.lastMessageDate DESC'))->setParameter('searchstring', $searchstring)->setFirstResult($start)->setMaxResults($limit);
                $result = $query->getResult();
                if (is_array($result))
                {
                    $limit = $limit - count($result);
                    $start = $start - $this->getSearchItemCount($options, $searchstring, $locale);
                    foreach ($result as $item) $items[] = $item;
                }
                unset($result);
            }
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.forum.') === 0) $this->container->get($item)->getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, $items);
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
            if (strpos($item,'addone.forum.') === 0) $this->container->get($item)->getHelpChapters($chapters);
        }
        return $chapters;
    }
    
    public function getHelpContent($page, &$answer)
    {
        //if ($page == 'd1537bd0b66a4289001629ac2e06e742') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:index.html.twig',array());
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.forum.') === 0) $answer .= $this->container->get($item)->getHelpContent($page);
        }
        return $answer;
    }
    
    
    
}
