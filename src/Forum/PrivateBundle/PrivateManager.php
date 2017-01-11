<?php

namespace Forum\PrivateBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class PrivateManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'addone.forume.private';
    }
    
    public function getDescription()
    {
        return 'Личные сообщения';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Личные сообщения', $this->container->get('router')->generate('forum_private_private_list'), 100, $this->container->get('security.context')->getToken()->getUser()->checkAccess('forum_private'), 'Форум');
    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('forum_private','Настройка личных сообщений');
    }
// ************************************
// Обработка административной части
// avtionType (validate, tab, save)
// ************************************
    public function getAdminController($request, $action, $actionId, $actionType)
    {
        return null;
    }

    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        if ($module == 'private')
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $parameters = array();
            $parameters['pageId'] = '';
            if (isset($nullparameters['pageId'])) $parameters['pageId'] = $nullparameters['pageId'];
            $parameterserror['pageId'] = '';
            $query = $em->createQuery('SELECT p.id, p.title, p.enabled FROM ForumPrivateBundle:ForumPrivatePages p ORDER BY p.id');
            $privatepages = $query->getResult();
            foreach ($privatepages as &$privatepage) 
            {
                $privatepage['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($privatepage['title'], 'default', true);
            }
            unset($privatepage);
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postparameters = $request->get('parameters');
                if (isset($postparameters['pageId'])) $parameters['pageId'] = $postparameters['pageId'];
                $search = false;
                foreach ($privatepages as $privatepage) if ($privatepage['id'] == $parameters['pageId']) $search = true;
                if ($search == false) {$errors = true; $parameterserror['pageId'] = 'Выберите страницу личных сообщений';}
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
                return $this->container->get('templating')->render('ForumPrivateBundle:Default:module.html.twig',array('parameters' => $parameters,'parameterserror' => $parameterserror, 'privatepages' => $privatepages));
            }
        }
        return null;
    }
// ************************************
// Обработка фронт части
// ************************************
     public function getTemplateTwig($contentType, $template)
     {
         if ($contentType == 'private') return 'ForumPrivateBundle:Front:private.html.twig';
         return null;
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         if ($contentType == 'private') return 'ForumPrivateBundle:Front:module.html.twig';
         return null;
     }
     
     public function getContentTypes(&$contents)
     {
         $contents['private'] = 'Просмотр личных сообщений';
     }

     public function getFrontContent($locale, $contentType, $contentId, $result, &$params)
     {
         $em = $this->container->get('doctrine')->getEntityManager();
         if ($contentType == 'private')
         {
             $privatepage = $this->container->get('doctrine')->getRepository('ForumPrivateBundle:ForumPrivatePages')->find($contentId);
             if (empty($privatepage)) return null;
             if ($privatepage->getEnabled() == 0) return null;
             if (($this->container->get('cms.cmsManager')->getCurrentUser() == null) || ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() != 2))
             {
                 $this->container->get('cms.cmsManager')->setErrorPageNumber(1); return null;
             }
             $params = array();
             $params['template'] = $privatepage->getTemplate();
             $params['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($privatepage->getTitle(),$locale,true);
             $params['messageMode'] = $privatepage->getMessageMode();
             $params['id'] = $privatepage->getId();
             $messageId = $this->container->get('request_stack')->getCurrentRequest()->get('message');
             if ($messageId === 'new')
             {
                 // Создание нового сообщения
                 $params['postUser'] = intval($this->container->get('request_stack')->getCurrentRequest()->get('user'));
                 $params['view'] = 'new';
                 $params['messageId'] = $messageId;
                 $params['messages'] = array();
                 $params['count'] = 0;
                 $params['pageCount'] = 1;
                 $params['page'] = 0;
                 $params['postReply'] = '';
                 $params['captchaEnabled'] = $privatepage->getCaptchaEnabled();
                 $params['postTitleValue'] = '';
                 $params['postTitleError'] = '';
                 $params['postMessageValue'] = '';
                 $params['postMessageError'] = '';
                 $params['captchaPath'] = '';
                 $params['captchaError'] = '';
                 $params['postAttachments'] = array();
                 $params['postAttachmentsError'] = '';
                 $params['status'] = '';
                 if ($params['captchaEnabled'] != 0)
                 {
                     $params['captchaPath'] = $this->container->get('router')->generate('basic_cms_captcha').'?target=forumprivate'.$params['id'];
                     $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_forumprivate'.$params['id'], '');
                 }
                 if (isset($result['action']) && ($result['action'] == 'object.forum.privatepost') && isset($result['actionId']) && ($result['actionId'] == $contentId))
                 {
                     if ($result['status'] != 'OK')
                     {
                         $params['status'] = $result['status'];
                         if (isset($result['actionUserId'])) $params['postUser'] = $result['actionUserId'];
                         if (isset($result['title'])) $params['postTitleValue'] = $result['title'];
                         if (isset($result['titleError'])) $params['postTitleError'] = $result['titleError'];
                         if (isset($result['text'])) $params['postMessageValue'] = $result['text'];
                         if (isset($result['textError'])) $params['postMessageError'] = $result['textError'];
                         if (isset($result['captchaError'])) $params['captchaError'] = $result['captchaError'];
                         if (isset($result['attachmentsError'])) $params['attachmentsError'] = $result['attachmentsError'];
                         if (isset($result['attachments']) && (count($result['attachments']) > 0)) 
                         {
                             $query = $em->createQuery('SELECT a.id, a.messageId, a.downloadDate, a.fileName, a.contentFile, a.isImage FROM ForumPrivateBundle:ForumPrivateAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $result['attachments']);
                             $pattachments = $query->getResult();
                             foreach ($result['attachments'] as $attid)
                             {
                                 foreach ($pattachments as $pattach)
                                 {
                                     if ($pattach['id'] == $attid) $params['postAttachments'][] = array_merge ($pattach, array('fileSize' => @filesize('..'.$pattach['contentFile'])));
                                 }
                             }
                             unset($pattachments);
                         }
                     } else 
                     {
                         if (isset($result['actionUserId'])) $params['postUser'] = $result['actionUserId'];
                         $messageId = $result['messageId'];
                         $params['status'] = 'OK';
                     }
                 }
                 $query = $em->createQuery('SELECT u.id FROM BasicCmsBundle:Users u WHERE u.id = :id AND u.blocked = 2')->setParameter('id', $params['postUser']);
                 $replyuser = $query->getResult();
                 if (!isset($replyuser[0])) {$params = null; return null;}
             } 
             if ($messageId === 'new')
             {
             } elseif ($messageId !== null)
             {
                 // Просмотр сообщения
                 $params['view'] = 'view';
                 $params['messageId'] = $messageId;
                 if ($privatepage->getMessageMode() == 0)
                 {
                     $filtersql = '(m.removedCreater = 0 AND m.createrId = '.$this->container->get('cms.cmsManager')->getCurrentUser()->getId().') OR (m.removedTarget = 0 AND m.targetId = '.$this->container->get('cms.cmsManager')->getCurrentUser()->getId().')';
                     $query = $em->createQuery('SELECT m.id, m.chainId, m.createDate, m.modifyDate, m.createrId, m.targetId, m.viewed, m.viewDate, m.title, m.content, '.
                                               'uc.login as createrLogin, uc.fullName as createrFullName, uc.email as createrEmail, uc.avatar as createrAvatar, '.
                                               'ut.login as targetLogin, ut.fullName as targetFullName, ut.email as targetEmail, ut.avatar as targetAvatar, 0 as isInbox '.
                                               'FROM ForumPrivateBundle:ForumPrivates m '.
                                               'LEFT JOIN BasicCmsBundle:Users uc WITH uc.id = m.createrId '.
                                               'LEFT JOIN BasicCmsBundle:Users ut WITH ut.id = m.targetId '.
                                               'WHERE ('.$filtersql.') AND m.id = :id')->setParameter('id', $messageId);
                     $params['messages'] = $query->getResult();
                     if (!isset($params['messages'][0])) return null;
                     $query = $em->createQuery('SELECT a.id, a.messageId, a.downloadDate, a.fileName, a.contentFile, a.isImage FROM ForumPrivateBundle:ForumPrivateAttachments a WHERE a.messageId = :messageid')->setParameter('messageid', $messageId);
                     $params['messages'][0]['attachments'] = $query->getResult();
                     if (!is_array($params['messages'][0]['attachments'])) $params['messages'][0]['attachments'] = array();
                     foreach ($params['messages'][0]['attachments'] as &$attach)
                     {
                         $attach['fileSize'] = @filesize('..'.$attach['contentFile']);
                     }
                     unset($attach);
                     $params['count'] = 1;
                     $params['pageCount'] = 1;
                     $params['page'] = 0;
                     $params['postReply'] = $params['messages'][0]['chainId'];
                 } else
                 {
                     $filtersql = '(m.removedCreater = 0 AND m.createrId = '.$this->container->get('cms.cmsManager')->getCurrentUser()->getId().') OR (m.removedTarget = 0 AND m.targetId = '.$this->container->get('cms.cmsManager')->getCurrentUser()->getId().')';
                     $query = $em->createQuery('SELECT m.chainId FROM ForumPrivateBundle:ForumPrivates m '.
                                               'WHERE ('.$filtersql.') AND m.id = :id')->setParameter('id', $messageId);
                     $chainId = $query->getResult();
                     if (!isset($chainId[0]['chainId'])) {$params = null; return null;}
                     $chainId = $chainId[0]['chainId'];
                     $params['postReply'] = $chainId;
                     $params['page'] = intval($this->container->get('request_stack')->getCurrentRequest()->get('page'));
                     $query = $em->createQuery('SELECT COUNT(m.id) as messagecount FROM ForumPrivateBundle:ForumPrivates m '.
                                               'WHERE ('.$filtersql.') AND m.chainId = :id')->setParameter('id', $chainId);
                     $messagecount = $query->getResult();
                     if (isset($messagecount[0]['messagecount'])) $messagecount = $messagecount[0]['messagecount']; else $messagecount = 0;
                     $params['count'] = $messagecount;
                     $params['pageCount'] = ceil($messagecount / 20);
                     if ($params['pageCount'] < 1) $params['pageCount'] = 1;
                     if ($params['page'] < 0) $params['page'] = 0;
                     if ($params['page'] >= $params['pageCount']) $params['page'] = $params['pageCount'] - 1;
                     $query = $em->createQuery('SELECT m.id, m.createDate, m.modifyDate, m.createrId, m.targetId, m.viewed, m.viewDate, m.title, m.content, '.
                                               'uc.login as createrLogin, uc.fullName as createrFullName, uc.email as createrEmail, uc.avatar as createrAvatar, '.
                                               'ut.login as targetLogin, ut.fullName as targetFullName, ut.email as targetEmail, ut.avatar as targetAvatar, 0 as isInbox '.
                                               'FROM ForumPrivateBundle:ForumPrivates m '.
                                               'LEFT JOIN BasicCmsBundle:Users uc WITH uc.id = m.createrId '.
                                               'LEFT JOIN BasicCmsBundle:Users ut WITH ut.id = m.targetId '.
                                               'WHERE ('.$filtersql.') AND m.chainId = :id ORDER BY m.createDate DESC')->setParameter('id', $chainId)->setFirstResult($params['page'] * $privatepage->getMessagesInPage())->setMaxResults($privatepage->getMessagesInPage());
                     $params['messages'] = $query->getResult();
                     if (!is_array($params['messages'])) $params['messages'] = array();
                     foreach ($params['messages'] as &$message)
                     {
                         $message['attachments'] = array();
                     }
                     unset($message);
                     // Найти вложения и добавить к сообщениям
                     $messageIds = array(0);
                     foreach ($params['messages'] as $message) $messageIds[] = $message['id'];
                     $query = $em->createQuery('SELECT a.id, a.messageId, a.downloadDate, a.fileName, a.contentFile, a.isImage FROM ForumPrivateBundle:ForumPrivateAttachments a WHERE a.messageId IN (:messageids)')->setParameter('messageids', $messageIds);
                     $attachments = $query->getResult();
                     if (!is_array($attachments)) $attachments = array();
                     foreach ($attachments as &$attach)
                     {
                         $attach['fileSize'] = @filesize('..'.$attach['contentFile']);
                     }
                     unset($attach);
                     foreach ($attachments as $attach)
                     {
                         foreach ($params['messages'] as &$message) if ($message['id'] == $attach['messageId']) $message['attachments'][] = $attach;
                         unset($message);
                     }
                     unset($attachments);
                 }
                 $viewIds = array();
                 foreach ($params['messages'] as &$message)
                 {
                     if (($message['viewed'] == 0) && ($message['targetId'] == $this->container->get('cms.cmsManager')->getCurrentUser()->getId()))
                     {
                         $viewIds[] = $message['id'];
                     }
                     if ($message['targetId'] == $this->container->get('cms.cmsManager')->getCurrentUser()->getId()) $message['isInbox'] = 1;
                     $message['premissionEdit'] = 0;
                     if (($message['isInbox'] == 0) && ($message['viewed'] == 0))
                     {
                         $message['premissionEdit'] = 1;
                     }
                     $message['editStatus'] = '';
                     $message['editMessageValue'] = $this->container->get('cms.cmsManager')->unclearHtmlCode($message['content']);
                     $message['editMessageError'] = '';
                     $message['editAttachments'] = $message['attachments'];
                     $message['editAttachmentsError'] = '';
                     if (isset($result['action']) && ($result['action'] == 'object.forum.privateedit') && isset($result['actionId']) && ($result['actionId'] == $contentId) && isset($result['actionMessageId']) && ($result['actionMessageId'] == $message['id']))
                     {
                         if ($result['status'] != 'OK')
                         {
                             $message['editStatus'] = $result['status'];
                             if (isset($result['text'])) $message['editMessageValue'] = $result['text'];
                             if (isset($result['textError'])) $message['editMessageError'] = $result['textError'];
                             if (isset($result['attachmentsError'])) $message['editAttachmentsError'] = $result['attachmentsError'];
                             if (isset($result['attachments']) && (count($result['attachments']) > 0)) 
                             {
                                 $message['editAttachments'] = array();
                                 $query = $em->createQuery('SELECT a.id, a.messageId, a.downloadDate, a.fileName, a.contentFile, a.isImage FROM ForumPrivateBundle:ForumPrivateAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $result['attachments']);
                                 $pattachments = $query->getResult();
                                 foreach ($result['attachments'] as $attid)
                                 {
                                     foreach ($pattachments as $pattach)
                                     {
                                         if ($pattach['id'] == $attid) $message['editAttachments'][] = array_merge ($pattach, array('fileSize' => @filesize('..'.$pattach['contentFile'])));
                                     }
                                 }
                                 unset($pattachments);
                             }
                         } else $message['editStatus'] = 'OK';
                     }
                 }
                 unset($message);
                 if (count($viewIds) > 0)
                 {
                     $query = $em->createQuery('UPDATE ForumPrivateBundle:ForumPrivates p SET p.viewed = 1, p.viewDate = :date WHERE p.id IN (:viewids)')->setParameter('viewids', $viewIds)->setParameter('date', new \DateTime('now'));
                     $query->execute();
                 }
                 // Новое сообщение
                 $params['captchaEnabled'] = $privatepage->getCaptchaEnabled();
                 $params['postTitleValue'] = '';
                 $params['postTitleError'] = '';
                 $params['postMessageValue'] = '';
                 $params['postMessageError'] = '';
                 $params['captchaPath'] = '';
                 $params['captchaError'] = '';
                 $params['postAttachments'] = array();
                 $params['postAttachmentsError'] = '';
                 $params['postUser'] = '';
                 $params['status'] = '';
                 if ($params['captchaEnabled'] != 0)
                 {
                     $params['captchaPath'] = $this->container->get('router')->generate('basic_cms_captcha').'?target=forumprivate'.$params['id'];
                     $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_forumprivate'.$params['id'], '');
                 }
                 if (isset($result['action']) && ($result['action'] == 'object.forum.privatepost') && isset($result['actionId']) && ($result['actionId'] == $contentId))
                 {
                     if ($result['status'] != 'OK')
                     {
                         $params['status'] = $result['status'];
                         if (isset($result['title'])) $params['postTitleValue'] = $result['title'];
                         if (isset($result['titleError'])) $params['postTitleError'] = $result['titleError'];
                         if (isset($result['text'])) $params['postMessageValue'] = $result['text'];
                         if (isset($result['textError'])) $params['postMessageError'] = $result['textError'];
                         if (isset($result['captchaError'])) $params['captchaError'] = $result['captchaError'];
                         if (isset($result['attachmentsError'])) $params['attachmentsError'] = $result['attachmentsError'];
                         if (isset($result['attachments']) && (count($result['attachments']) > 0)) 
                         {
                             $query = $em->createQuery('SELECT a.id, a.messageId, a.downloadDate, a.fileName, a.contentFile, a.isImage FROM ForumPrivateBundle:ForumPrivateAttachments a WHERE a.id IN (:ids)')->setParameter('ids', $result['attachments']);
                             $pattachments = $query->getResult();
                             foreach ($result['attachments'] as $attid)
                             {
                                 foreach ($pattachments as $pattach)
                                 {
                                     if ($pattach['id'] == $attid) $params['postAttachments'][] = array_merge ($pattach, array('fileSize' => @filesize('..'.$pattach['contentFile'])));
                                 }
                             }
                             unset($pattachments);
                         }
                     } else $params['status'] = 'OK';
                 }
                 // messages, pageCount, count, page
             } else
             {
                 // Просмотр списка сообщения
                 $params['view'] = 'list';
                 $params['filter'] = 'all';
                 $params['page'] = intval($this->container->get('request_stack')->getCurrentRequest()->get('page'));
                 if ($privatepage->getMessageMode() == 0)
                 {
                     $filtersql = '(m.removedCreater = 0 AND m.createrId = '.$this->container->get('cms.cmsManager')->getCurrentUser()->getId().') OR (m.removedTarget = 0 AND m.targetId = '.$this->container->get('cms.cmsManager')->getCurrentUser()->getId().')';
                     if ($this->container->get('request_stack')->getCurrentRequest()->get('filter') == 'in') {$params['filter'] = 'in'; $filtersql = '(m.removedTarget = 0 AND m.targetId = '.$this->container->get('cms.cmsManager')->getCurrentUser()->getId().')';}
                     if ($this->container->get('request_stack')->getCurrentRequest()->get('filter') == 'out') {$params['filter'] = 'out'; $filtersql = '(m.removedCreater = 0 AND m.createrId = '.$this->container->get('cms.cmsManager')->getCurrentUser()->getId().')';}
                     $query = $em->createQuery('SELECT count(m.id) as messagecount FROM ForumPrivateBundle:ForumPrivates m '.
                                               'WHERE '.$filtersql);
                     $messagecount = $query->getResult();
                     if (isset($messagecount[0]['messagecount'])) $messagecount = $messagecount[0]['messagecount']; else $messagecount = 0;
                     $params['count'] = $messagecount;
                     $params['pageCount'] = ceil($messagecount / 20);
                     if ($params['pageCount'] < 1) $params['pageCount'] = 1;
                     if ($params['page'] < 0) $params['page'] = 0;
                     if ($params['page'] >= $params['pageCount']) $params['page'] = $params['pageCount'] - 1;
                     $query = $em->createQuery('SELECT m.id, m.createDate, m.modifyDate, m.createrId, m.targetId, m.viewed, m.viewDate, m.title, m.content, '.
                                               'uc.login as createrLogin, uc.fullName as createrFullName, uc.email as createrEmail, uc.avatar as createrAvatar, '.
                                               'ut.login as targetLogin, ut.fullName as targetFullName, ut.email as targetEmail, ut.avatar as targetAvatar, 0 as isInbox '.
                                               'FROM ForumPrivateBundle:ForumPrivates m '.
                                               'LEFT JOIN BasicCmsBundle:Users uc WITH uc.id = m.createrId '.
                                               'LEFT JOIN BasicCmsBundle:Users ut WITH ut.id = m.targetId '.
                                               'WHERE '.$filtersql.' ORDER BY m.createDate DESC')->setFirstResult($params['page'] * $privatepage->getMessagesInPage())->setMaxResults($privatepage->getMessagesInPage());
                     $params['messages'] = $query->getResult();
                     if (!is_array($params['messages'])) $params['messages'] = array();
                 } else
                 {
                     $filtersql = '(m.removedCreater = 0 AND m.createrId = '.$this->container->get('cms.cmsManager')->getCurrentUser()->getId().') OR (m.removedTarget = 0 AND m.targetId = '.$this->container->get('cms.cmsManager')->getCurrentUser()->getId().')';
                     $query = $em->createQuery('SELECT COUNT(DISTINCT m.chainId) as messagecount FROM ForumPrivateBundle:ForumPrivates m '.
                                               'WHERE '.$filtersql);
                     $messagecount = $query->getResult();
                     if (isset($messagecount[0]['messagecount'])) $messagecount = $messagecount[0]['messagecount']; else $messagecount = 0;
                     $params['count'] = $messagecount;
                     $params['pageCount'] = ceil($messagecount / 20);
                     if ($params['pageCount'] < 1) $params['pageCount'] = 1;
                     if ($params['page'] < 0) $params['page'] = 0;
                     if ($params['page'] >= $params['pageCount']) $params['page'] = $params['pageCount'] - 1;
                     $query = $em->createQuery('SELECT m.chainId, MAX(m.createDate) as maxCreateDate FROM ForumPrivateBundle:ForumPrivates m '.
                                               'WHERE '.$filtersql.' GROUP BY m.chainId ORDER BY maxCreateDate DESC')->setFirstResult($params['page'] * $privatepage->getMessagesInPage())->setMaxResults($privatepage->getMessagesInPage());
                     $chainIds = $query->getResult();
                     $params['messages'] = array();
                     if (is_array($chainIds))
                     {
                         foreach ($chainIds as $chainId)
                         {
                             $query2 = $em->createQuery('SELECT m.id, m.createDate, m.modifyDate, m.createrId, m.targetId, m.viewed, m.viewDate, m.title, m.content, '.
                                                       'uc.login as createrLogin, uc.fullName as createrFullName, uc.email as createrEmail, uc.avatar as createrAvatar, '.
                                                       'ut.login as targetLogin, ut.fullName as targetFullName, ut.email as targetEmail, ut.avatar as targetAvatar, 0 as isInbox '.
                                                       'FROM ForumPrivateBundle:ForumPrivates m '.
                                                       'LEFT JOIN BasicCmsBundle:Users uc WITH uc.id = m.createrId '.
                                                       'LEFT JOIN BasicCmsBundle:Users ut WITH ut.id = m.targetId '.
                                                       'WHERE ('.$filtersql.') AND m.chainId = :chainid ORDER BY m.createDate DESC')->setParameter('chainid', $chainId['chainId'])->setFirstResult(0)->setMaxResults(1);
                             $messageque = $query2->getResult();
                             if (isset($messageque[0]['id'])) $params['messages'][] = $messageque[0];
                         }
                     }
                 }
                 foreach ($params['messages'] as &$message)
                 {
                     if ($message['targetId'] == $this->container->get('cms.cmsManager')->getCurrentUser()->getId()) $message['isInbox'] = 1;
                 }
                 unset($message);
                 // messages, pagecount, count, page, filter
             }
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
         }
     }
     
     public function setFrontActionPrepare($locale, $seoPage, $action, &$result)
     {
     }
     
     public function setFrontAction($locale, $seoPage, $action, &$result)
     {
         if ($action == 'privateremove')
         {
             $privateids = $this->container->get('request_stack')->getCurrentRequest()->get('actionprivateid');
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $privatepage = $this->container->get('doctrine')->getRepository('ForumPrivateBundle:ForumPrivatePages')->find($id);
             if (!empty($privatepage) && ($privatepage->getEnabled() != 0))
             {
                 $result['action'] = 'object.forum.privateremove';
                 $result['id'] = $id;
                 if (($this->container->get('cms.cmsManager')->getCurrentUser() == null) || ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() != 2))
                 {
                     $result['status'] = 'Access error';
                 } else
                 {
                     $result['privateids'] = $privateids;
                     $em = $this->container->get('doctrine')->getEntityManager();
                     if (!is_array($privateids)) $privateids = array($privateids);
                     foreach ($privateids as $privateid)
                     {
                         $privateent = $this->container->get('doctrine')->getRepository('ForumPrivateBundle:ForumPrivates')->find($privateid);
                         if (!empty($privateent))
                         {
                             if ($privateent->getCreaterId() == $this->container->get('cms.cmsManager')->getCurrentUser()->getId()) 
                             {
                                 if ($privateent->getViewed() == 0) $privateent->setRemovedTarget(1);
                                 $privateent->setRemovedCreater(1);
                             }
                             if ($privateent->getTargetId() == $this->container->get('cms.cmsManager')->getCurrentUser()->getId()) $privateent->setRemovedTarget(1);
                             $em->flush();
                             if ($privatepage->getMessageMode() == 1)
                             {
                                 $query = $em->createQuery('UPDATE ForumPrivateBundle:ForumPrivates p SET p.removedCreater = 1 WHERE p.createrId = :userid AND p.chainId = :chain')
                                             ->setParameter('userid', $this->container->get('cms.cmsManager')->getCurrentUser()->getId())
                                             ->setParameter('chain', $privateent->getChainId());
                                 $query->execute();
                                 $query = $em->createQuery('UPDATE ForumPrivateBundle:ForumPrivates p SET p.removedTarget = 1 WHERE p.viewed = 0 AND p.createrId = :userid AND p.chainId = :chain')
                                             ->setParameter('userid', $this->container->get('cms.cmsManager')->getCurrentUser()->getId())
                                             ->setParameter('chain', $privateent->getChainId());
                                 $query->execute();
                                 $query = $em->createQuery('UPDATE ForumPrivateBundle:ForumPrivates p SET p.removedTarget = 1 WHERE p.targetId = :userid AND p.chainId = :chain')
                                             ->setParameter('userid', $this->container->get('cms.cmsManager')->getCurrentUser()->getId())
                                             ->setParameter('chain', $privateent->getChainId());
                                 $query->execute();
                             }
                             $query = $em->createQuery('SELECT p.id FROM ForumPrivateBundle:ForumPrivates p WHERE p.removedTarget = 1 AND p.removedCreater = 1');
                             $removedprivates = $query->getResult();
                             $query = $em->createQuery('DELETE FROM ForumPrivateBundle:ForumPrivates p WHERE p.removedTarget = 1 AND p.removedCreater = 1');
                             $query->execute();
                             if (is_array($removedprivates))
                             {
                                 $privids = array();
                                 foreach ($removedprivates as $removedprivate)
                                 {
                                     $privids[] = $removedprivate['id'];
                                 }
                                 if (count($privids) > 0)
                                 {
                                     $query = $em->createQuery('SELECT f.contentFile FROM ForumPrivateBundle:ForumPrivateAttachments f WHERE f.messageId IN (:ids)')->setParameter('ids', $privids);
                                     $removedfiles = $query->getResult();
                                     $query = $em->createQuery('DELETE FROM ForumPrivateBundle:ForumPrivateAttachments f WHERE f.messageId IN (:ids)')->setParameter('ids', $privids);
                                     $query->execute();
                                     if (is_array($removedfiles))
                                     {
                                         foreach ($removedfiles as $removedfile)
                                         {
                                             @unlink('..'.$removedfile['contentFile']);
                                         }
                                     }
                                 }
                             }
                             $result['status'] = 'OK';
                         }
                     }
                     if ($result['status'] != 'OK') $result['status'] = 'Not found';
                 }
             }
         }
         if ($action == 'privateviewed')
         {
             $privateids = $this->container->get('request_stack')->getCurrentRequest()->get('actionprivateid');
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $privatepage = $this->container->get('doctrine')->getRepository('ForumPrivateBundle:ForumPrivatePages')->find($id);
             if (!empty($privatepage) && ($privatepage->getEnabled() != 0))
             {
                 $result['action'] = 'object.forum.privateviewed';
                 $result['id'] = $id;
                 if (($this->container->get('cms.cmsManager')->getCurrentUser() == null) || ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() != 2))
                 {
                     $result['status'] = 'Access error';
                 } else
                 {
                     $result['privateids'] = $privateids;
                     $em = $this->container->get('doctrine')->getEntityManager();
                     if (!is_array($privateids)) $privateids = array($privateids);
                     foreach ($privateids as $privateid)
                     {
                         $privateent = $this->container->get('doctrine')->getRepository('ForumPrivateBundle:ForumPrivates')->find($privateid);
                         if (!empty($privateent))
                         {
                             if (($privateent->getTargetId() == $this->container->get('cms.cmsManager')->getCurrentUser()->getId()) && ($privateent->getViewed() == 0))
                             {
                                 $privateent->setViewed(1);
                                 $privateent->setViewDate(new \DateTime('now'));
                                 $em->flush();
                             }
                             if ($privatepage->getMessageMode() == 1)
                             {
                                 $query = $em->createQuery('UPDATE ForumPrivateBundle:ForumPrivates p SET p.viewed = 1, p.viewDate = :date WHERE p.targetId = :userid AND p.chainId = :chain AND p.viewed = 0')
                                             ->setParameter('userid', $this->container->get('cms.cmsManager')->getCurrentUser()->getId())
                                             ->setParameter('date', new \DateTime('now'))
                                             ->setParameter('chain', $privateent->getChainId());
                                 $query->execute();
                             }
                             $result['status'] = 'OK';
                         }
                     }
                     if ($result['status'] != 'OK') $result['status'] = 'Not found';
                 }
             }
         }
         if ($action == 'privatepost')
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $replyid = $this->container->get('request_stack')->getCurrentRequest()->get('actionreply');
             $userid = $this->container->get('request_stack')->getCurrentRequest()->get('actionuser');
             $result['status'] = '';
             $result['action'] = 'object.forum.privatepost';
             $result['actionId'] = $id;
             $result['actionReplyId'] = $replyid;
             $result['actionUserId'] = $userid;
             if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2))
             {
                 $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
                 $postfiles = $this->container->get('request_stack')->getCurrentRequest()->files->get('actionfiles');
                 $query = $em->createQuery('SELECT p.id, p.captchaEnabled, p.messageAllowTags, p.messageAllowStyleProp, p.messageReplaceUrls FROM ForumPrivateBundle:ForumPrivatePages p '.
                                           'WHERE p.id = :id AND p.enabled != 0')->setParameter('id', $id);
                 $privatepage = $query->getResult();
                 if ($replyid != null)
                 {
                     $query = $em->createQuery('SELECT m.id, m.createrId, m.targetId, m.title, m.chainId FROM ForumPrivateBundle:ForumPrivates m '.
                                               'WHERE m.id = :id AND (m.createrId = :userid OR m.targetId = :userid)')->setParameter('id', $replyid)->setParameter('userid', $cmsManager->getCurrentUser()->getId());
                     $reply = $query->getResult();
                 } elseif ($userid != null)
                 {
                     $query = $em->createQuery('SELECT u.id FROM BasicCmsBundle:Users u WHERE u.id = :id AND u.blocked = 2')->setParameter('id', $userid);
                     $replyuser = $query->getResult();
                 }
                 if (isset($privatepage[0]) && (isset($reply[0]) || isset($replyuser[0]))) 
                 {
                     $errors = false;
                     $result['captchaError'] = '';
                     $result['textError'] = '';
                     $result['text'] = '';
                     $result['titleError'] = '';
                     $result['title'] = '';
                     $result['attachments'] = array();
                     $result['attachmentsError'] = '';
                     if (isset($postfields['title'])) $result['title'] = $postfields['title'];
                     if (isset($postfields['text'])) $result['text'] = $postfields['text'];
                     if (isset($postfields['attachments']) && is_array($postfields['attachments']))
                     {
                         foreach ($postfields['attachments'] as $attid)
                         {
                            $query = $em->createQuery('SELECT count(a.id) as attachcount FROM ForumPrivateBundle:ForumPrivateAttachments a WHERE a.id = :id AND a.messageId = 0')->setParameter('id',intval($attid));
                            $attachcount = $query->getResult();
                            if (isset($attachcount[0]['attachcount'])) $attachcount = intval($attachcount[0]['attachcount']); else $attachcount = 0;
                            if ($attachcount == 1) $result['attachments'][] = intval($attid);
                         }
                     }
                     // Приём файлов
                     if (isset($postfiles['attachments']) && (is_array($postfiles['attachments'])))
                     {
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
                         foreach ($postfiles['attachments'] as $file) if ($file != null)
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
                                 $result['attachments'][] = $fileent->getId();
                             } else {$errors = true; $result['attachmentsError'] = 'Upload error';}
                         }
                     }
                     // Валидация
                     if (($result['title'] == '') && isset($reply[0])) $result['title'] = $reply[0]['title'];
                     if (!preg_match("/^[\s\S]{0,255}$/ui", $result['title'])) {$errors = true; $result['titleError'] = 'Preg error';}
                     if (!preg_match("/^[\s\S]{2,}$/ui", $result['text'])) {$errors = true; $result['textError'] = 'Preg error';}
                     if ($privatepage[0]['captchaEnabled'] != 0)
                     {
                         $needle = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_captcha_forumprivate'.$privatepage[0]['id']);
                         $value = (isset($postfields['captcha']) ? $postfields['captcha'] : null);
                         if (($needle == '') || (strtoupper($value) != strtoupper($needle)) || ($value == null))
                         {
                             $result['captchaError'] = 'Value error';
                             $errors = true;
                         }
                         $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_forumprivate'.$privatepage[0]['id'], '');
                     }
                     // Удаление неразрешенных тегов
                     $result['text'] = $cmsManager->clearHtmlCode($result['text'], $privatepage[0]['messageAllowTags'], $privatepage[0]['messageAllowStyleProp'], $privatepage[0]['messageReplaceUrls']);
                     // Сохранение если нет ошибок
                     if ($errors == false)
                     {
                         $messageent = new \Forum\PrivateBundle\Entity\ForumPrivates();
                         $messageent->setContent($result['text']);
                         $messageent->setCreateDate(new \DateTime('now'));
                         $messageent->setModifyDate(new \DateTime('now'));
                         $messageent->setCreaterId($cmsManager->getCurrentUser()->getId());
                         if (isset($reply[0]))
                         {
                             $messageent->setTargetId(($reply[0]['createrId'] != $cmsManager->getCurrentUser()->getId() ? $reply[0]['createrId'] : $reply[0]['targetId']));
                             $messageent->setChainId($reply[0]['chainId']);
                         } else
                         {
                             $messageent->setTargetId($userid);
                             $messageent->setChainId(0);
                         }
                         $messageent->setViewed(0);
                         $messageent->setViewDate(null);
                         $messageent->setTitle($result['title']);
                         $messageent->setRemovedCreater(0);
                         $messageent->setRemovedTarget(0);
                         $em->persist($messageent);
                         $em->flush();
                         if (!isset($reply[0])) {$messageent->setChainId($messageent->getId());$em->flush();}
                         if (count($result['attachments']) > 0)
                         {
                            $query = $em->createQuery('UPDATE ForumPrivateBundle:ForumPrivateAttachments a SET a.messageId = :messageid WHERE a.id IN (:ids)')->setParameter('messageid', $messageent->getId())->setParameter('ids', $result['attachments']);
                            $query->execute();
                         }
                         $result['status'] = 'OK';
                         $result['messageId'] = $messageent->getId();
                     } else $result['status'] = 'Check error';
                 } else $result['status'] = 'Not found';
             } else $result['status'] = 'Access error';
         }
         if ($action == 'privateedit')
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $messageid = $this->container->get('request_stack')->getCurrentRequest()->get('actionmessageid');
             $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             $postfiles = $this->container->get('request_stack')->getCurrentRequest()->files->get('actionfiles');
             $result['status'] = '';
             $result['action'] = 'object.forum.privateedit';
             $result['actionId'] = $id;
             $result['actionMessageId'] = $messageid;
             if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2))
             {
                 $query = $em->createQuery('SELECT p.id, p.captchaEnabled, p.messageAllowTags, p.messageAllowStyleProp, p.messageReplaceUrls FROM ForumPrivateBundle:ForumPrivatePages p '.
                                           'WHERE p.id = :id AND p.enabled != 0')->setParameter('id', $id);
                 $privatepage = $query->getResult();
                 $messageent = $this->container->get('doctrine')->getRepository('ForumPrivateBundle:ForumPrivates')->findOneBy(array('id' => $messageid, 'removedCreater' => 0, 'viewed' => 0, 'createrId' => $cmsManager->getCurrentUser()->getId()));
                 if (isset($privatepage[0]) && (!empty($messageent)))
                 {
                     $errors = false;
                     $result['textError'] = '';
                     $result['text'] = '';
                     $result['attachments'] = array();
                     $result['attachmentsError'] = '';
                     if (isset($postfields['text'])) $result['text'] = $postfields['text'];
                     if (isset($postfields['attachments']) && is_array($postfields['attachments']))
                     {
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
                         foreach ($postfields['attachments'] as $attid)
                         {
                            $query = $em->createQuery('SELECT count(a.id) as attachcount FROM ForumPrivateBundle:ForumPrivateAttachments a WHERE a.id = :id AND (a.messageId = :messageid OR a.messageId = 0)')->setParameter('messageid', $messageid)->setParameter('id',intval($attid));
                            $attachcount = $query->getResult();
                            if (isset($attachcount[0]['attachcount'])) $attachcount = intval($attachcount[0]['attachcount']); else $attachcount = 0;
                            if ($attachcount == 1) $result['attachments'][] = intval($attid);
                         }
                     }

                     // Приём файлов
                     if (isset($postfiles['attachments']) && (is_array($postfiles['attachments'])))
                     {
                         foreach ($postfiles['attachments'] as $file) if ($file != null)
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
                                 $result['attachments'][] =$fileent->getId();
                             } else {$errors = true; $result['attachmentsError'] = 'Upload error';}
                         }
                     }
                     // Валидация
                     if (!preg_match("/^[\s\S]{2,}$/ui", $result['text'])) {$errors = true; $result['textError'] = 'Preg error';}
                     // Удаление неразрешенных тегов
                     $result['text'] = $cmsManager->clearHtmlCode($result['text'], $privatepage[0]['messageAllowTags'], $privatepage[0]['messageAllowStyleProp'], $privatepage[0]['messageReplaceUrls']);
                     // Сохранение если нет ошибок
                     if ($errors == false)
                     {
                         $messageent->setContent($result['text']);
                         $messageent->setModifyDate(new \DateTime('now'));
                         $em->flush();
                         if (count($result['attachments']) > 0)
                         {
                            $query = $em->createQuery('UPDATE ForumPrivateBundle:ForumPrivateAttachments a SET a.messageId = :messageid WHERE a.id IN (:ids)')->setParameter('messageid', $messageid)->setParameter('ids', $result['attachments']);
                            $query->execute();
                            $query = $em->createQuery('UPDATE ForumPrivateBundle:ForumPrivateAttachments a SET a.messageId = 0 WHERE a.messageId = :messageid AND a.id NOT IN (:ids)')->setParameter('messageid', $messageid)->setParameter('ids', $result['attachments']);
                            $query->execute();
                         } else
                         {
                            $query = $em->createQuery('UPDATE ForumPrivateBundle:ForumPrivateAttachments a SET a.messageId = 0 WHERE a.messageId = :messageid')->setParameter('messageid', $messageid);
                            $query->execute();
                         }
                         $result['status'] = 'OK';
                     } else $result['status'] = 'Check error';
                 } else $result['status'] = 'Access error';
             } else $result['status'] = 'Access error';
         }
     }
     
     public function getModuleTypes(&$contents)
     {
         $contents['private'] = 'Модуль личных сообщений';
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result)
     {
         if ($moduleType == 'private')
         {
             if (isset($parameters['pageId']))
             {
                 $em = $this->container->get('doctrine')->getEntityManager();
                 $cmsManager = $this->container->get('cms.cmsManager');
                 $query = $em->createQuery('SELECT p.title, sp.url FROM ForumPrivateBundle:ForumPrivatePages p '.
                                           'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.forum\' AND sp.contentAction = \'private\' AND sp.contentId = p.id '.
                                           'WHERE p.id = :id AND p.enabled != 0')->setParameter('id', $parameters['pageId']);
                 $page = $query->getResult();
                 if (isset($page[0])) $params = $page[0]; else return null;
                 $params['url'] = '/'.($locale != '' ? $locale.'/' : '').$params['url'];
                 $params['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($params['title'], $locale, true);
                 $params['inbox'] = 0;
                 $params['outbox'] = 0;
                 $params['all'] = 0;
                 $params['inboxNew'] = 0;
                 $params['outboxNew'] = 0;
                 $params['allNew'] = 0;
                 $params['autorized'] = 0;
                 if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2))
                 {
                     $params['autorized'] = 1;
                     $query = $em->createQuery('SELECT count(m.id) as messagecount FROM ForumPrivateBundle:ForumPrivates m '.
                                               'WHERE m.removedTarget = 0 AND m.targetId = '.$this->container->get('cms.cmsManager')->getCurrentUser()->getId());
                     $messagecount = $query->getResult();
                     if (isset($messagecount[0]['messagecount'])) $messagecount = $messagecount[0]['messagecount']; else $messagecount = 0;
                     $params['inbox'] = $messagecount;
                     $query = $em->createQuery('SELECT count(m.id) as messagecount FROM ForumPrivateBundle:ForumPrivates m '.
                                               'WHERE m.removedTarget = 0 AND m.targetId = '.$this->container->get('cms.cmsManager')->getCurrentUser()->getId().' AND m.viewed = 0');
                     $messagecount = $query->getResult();
                     if (isset($messagecount[0]['messagecount'])) $messagecount = $messagecount[0]['messagecount']; else $messagecount = 0;
                     $params['inboxNew'] = $messagecount;
                     $query = $em->createQuery('SELECT count(m.id) as messagecount FROM ForumPrivateBundle:ForumPrivates m '.
                                               'WHERE m.removedCreater = 0 AND m.createrId = '.$this->container->get('cms.cmsManager')->getCurrentUser()->getId());
                     $messagecount = $query->getResult();
                     if (isset($messagecount[0]['messagecount'])) $messagecount = $messagecount[0]['messagecount']; else $messagecount = 0;
                     $params['outbox'] = $messagecount;
                     $query = $em->createQuery('SELECT count(m.id) as messagecount FROM ForumPrivateBundle:ForumPrivates m '.
                                               'WHERE m.removedCreater = 0 AND m.createrId = '.$this->container->get('cms.cmsManager')->getCurrentUser()->getId().' AND m.viewed = 0');
                     $messagecount = $query->getResult();
                     if (isset($messagecount[0]['messagecount'])) $messagecount = $messagecount[0]['messagecount']; else $messagecount = 0;
                     $params['outboxNew'] = $messagecount;
                     $params['all'] = $params['inbox'] + $params['outbox'];
                     $params['allNew'] = $params['inboxNew'] + $params['outboxNew'];
                 }
                 return $params;
             }
         }
         return null;
     }
     
     public function getInfoTitle($contentType, $contentId)
     {
         if ($contentType == 'private')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT c.title FROM ForumPrivateBundle:ForumPrivatePages c WHERE c.id = :id')->setParameter('id', $contentId);
             $pages = $query->getResult();
             if (isset($pages[0]['title'])) return $pages[0]['title'];
         }
         return null;
     }

     public function getInfoSitemap($contentType, $contentId)
     {
          return null;
     }
     
// ************************************
// Обработка таксономии
// ************************************
     public function getTaxonomyParameters(&$params)
     {

     }
     
     public function getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $itemIds, $allparameters, &$queryparameters, &$queryparameterscount, &$select, &$from, &$where, &$order)
     {
         
     }
     
     public function getTaxonomyPostProcess($locale, &$params)
     {
         
     }
     
// ****************************************
// Обработка корзины
// ****************************************
     public function calcBasketInfo (&$basket)
     {
         
     }
     
     public function getBasketJsCalc(&$jsCalc, $products = null)
     {
         
     }
     
     public function getProductOptions($productId, &$options, $locale)
     {
     
     }

//**************************************************
// Поиск по сайту
//**************************************************

    public function getSearchOptions(&$options)
    {        
    }
    
    public function getSearchItemCount($options, $searchstring, $locale)
    {
        return 0;
    }
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, &$start, &$limit, &$items)
    {        
    }
    
//****************************************
// Справочная система
//****************************************

    public function getHelpChapters(&$chapters)
    {
        //$chapters['d1537bd0b66a4289001629ac2e06e742'] = 'Главная страница CMS';
    }
    
    public function getHelpContent($page)
    {
        //if ($page == 'd1537bd0b66a4289001629ac2e06e742') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:index.html.twig',array());
    }
     
}
