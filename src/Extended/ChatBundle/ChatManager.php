<?php

namespace Extended\ChatBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class ChatManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'object.chat';
    }
    
    public function getDescription()
    {
        return 'Чат';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.chat.') === 0) $this->container->get($item)->registerMenu();

    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.chat.') === 0) $this->container->get($item)->registerRoles();
        
     }
     
     public function getContentTypes()
     {
        $contents = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.chat.') === 0) $this->container->get($item)->getContentTypes($contents);
        return $contents;
     }
     
     public function getTaxonomyType()
     {
         return false;
     }
     
     public function getTemplateTwig($contentType, $template)
     {
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.chat.') === 0) 
         {
             $result = $this->container->get($item)->getTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         if ($contentType == 'modulechat') return 'ExtendedChatBundle:Front:chat.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.chat.') === 0) 
         {
             $result = $this->container->get($item)->getModuleTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }
     
     public function getFrontContent($locale, $contentType, $contentId, $result = null)
     {
         $params = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.chat.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
         return $params;
     }
     
     public function setFrontAction($locale, $seoPage, $action)
     {
         $result = null;
         if (($action == 'info') || ($action == 'editroom') || ($action == 'load') || ($action == 'post') || ($action == 'leave') || ($action == 'delete'))
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2))
             {
                 $result = array();
                 $result['action'] = 'object.chat.'.$action;
                 if ($action == 'editroom')
                 {
                     $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
                     $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
                     $result['title'] = (isset($postfields['title']) ? $postfields['title'] : '');
                     if ($result['title'] == '')
                     {
                         $result['title'] = 'Unnamed';
                     }
                     $result['users'] = array($cmsManager->getCurrentUser()->getId());
                     if (isset($postfields['users']) && is_array($postfields['users']))
                     {
                         foreach ($postfields['users'] as $uid)
                         {
                             $result['users'][] = intval($uid);
                         }
                     }
                     $rooment = $this->container->get('doctrine')->getRepository('ExtendedChatBundle:Chat')->find($id);
                     if (empty($rooment))
                     {
                         $rooment = new \Extended\ChatBundle\Entity\Chat();
                         $rooment->setCreateDate(new \DateTime('now'));
                         $rooment->setCreaterId($cmsManager->getCurrentUser()->getId());
                         $em->persist($rooment);
                     }
                     $rooment->setTitle($result['title']);
                     $em->flush();
                     foreach ($result['users'] as $uid)
                     {
                         $userent = $this->container->get('doctrine')->getRepository('ExtendedChatBundle:ChatUser')->findOneBy(array('chatId' => $rooment->getId(), 'userId' => $uid));
                         if (empty($userent))
                         {
                             $userent = new \Extended\ChatBundle\Entity\ChatUser();
                             $userent->setChatId($rooment->getId());
                             $userent->setChatType(0);
                             $userent->setUserId($uid);
                             $userent->setVisitDate(new \DateTime('now'));
                             $userent->setLastSeenMessage(0);
                             $em->persist($userent);
                             $em->flush();
                         }
                         unset($userent);
                     }
                     $em->createQuery('DELETE FROM ExtendedChatBundle:ChatUser u WHERE u.chatId = :chatid AND u.chatType = 0 AND u.userId NOT IN (:ids)')->setParameter('ids', $result['users'])->setParameter('chatid', $rooment->getId())->execute();
                     $result['roomId'] = $rooment->getId();
                 }
                 if ($action == 'load')
                 {
                     $messagesLimit = 30;
                     $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
                     $type = $this->container->get('request_stack')->getCurrentRequest()->get('actiontype');
                     $prev = $this->container->get('request_stack')->getCurrentRequest()->get('actionprev');
                     if ($prev == null)
                     {
                         $result['position'] = 'bottom';
                         $result['messages'] = $em->createQuery('SELECT m.id, m.message, m.attachment, m.attachmentMimetype, m.attachmentFilename, m.createDate, m.createrId, u.fullName, u.avatar FROM ExtendedChatBundle:ChatMessage m '.
                                                                'LEFT JOIN BasicCmsBundle:Users u WITH u.id = m.createrId '.
                                                                'WHERE m.chatId = :chatid AND m.chatType = :chattype ORDER BY m.id DESC')->setParameter('chatid', $id)->setParameter('chattype', $type)->setMaxResults($messagesLimit)->getResult();
                     } else 
                     {
                         $result['position'] = 'top';
                         $result['messages'] = $em->createQuery('SELECT m.id, m.message, m.attachment, m.attachmentMimetype, m.attachmentFilename, m.createDate, m.createrId, u.fullName, u.avatar FROM ExtendedChatBundle:ChatMessage m '.
                                                                'LEFT JOIN BasicCmsBundle:Users u WITH u.id = m.createrId '.
                                                                'WHERE m.id < :prev AND m.chatId = :chatid AND m.chatType = :chattype ORDER BY m.id DESC')->setParameter('chatid', $id)->setParameter('chattype', $type)->setParameter('prev', $prev)->setMaxResults($messagesLimit)->getResult();
                     }
                     $result['loaded'] = (count($result['messages']) < $messagesLimit ? 'end' : '');
                 }
                 if ($action == 'post')
                 {
                     $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
                     $type = $this->container->get('request_stack')->getCurrentRequest()->get('actiontype');
                     $message = $this->container->get('request_stack')->getCurrentRequest()->get('actionmessage');
                     $attach = $this->container->get('request_stack')->getCurrentRequest()->files->get('actionattachment');
                     if ($type == 0)
                     {
                        $chatEnt = $this->container->get('doctrine')->getRepository('ExtendedChatBundle:Chat')->find($id);
                     } else {
                        $chatEnt = $this->container->get('doctrine')->getRepository('ExtendedProjectBundle:Projects')->find($id);
                     }
                     if (!empty($chatEnt))
                     {
                         $messageent = new \Extended\ChatBundle\Entity\ChatMessage();
                         $messageent->setAttachment('');
                         $messageent->setAttachmentFilename('');
                         $messageent->setAttachmentMimetype('');
                         $messageent->setChatId($id);
                         $messageent->setChatType($type);
                         $messageent->setCreateDate(new \DateTime('now'));
                         $messageent->setCreaterId($cmsManager->getCurrentUser()->getId());
                         $messageent->setMessage($message);
                         if ($attach != null)
                         {
                             $messageent->setAttachmentFilename($attach->getClientOriginalName());
                             $messageent->setAttachmentMimetype($attach->getClientMimeType());
                             $fileName = $id.'_'.md5(time()).'.dat';
                             $messageent->setAttachment('/secured/chat/'.$fileName);
                             try 
                             {
                                 $attach->move('../secured/chat', $fileName);
                             } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e)
                             {
                                 $messageent->setAttachment('');
                             }
                         }
                         $em->persist($messageent);
                         $em->flush();
                         // Посылка уведомлений
                         if ($this->container->has('addone.user.alert'))
                         {
                             $filter = array();
                             if ($type == 0)
                             {
                                $chatUsers = $em->createQuery('SELECT c.userId FROM ExtendedChatBundle:ChatUser c WHERE c.visitDate < :date AND c.chatId = :chatid AND c.chatType = :chattype AND c.userId != :userid')->setParameters(array(
                                    'date' => new \DateTime('-5 minutes'),
                                    'chatid' => $chatEnt->getId(),
                                    'chattype' => $type,
                                    'userid' => $cmsManager->getCurrentUser()->getId()
                                ))->getResult();
                                foreach ($chatUsers as $user)
                                {
                                    $filter[] = $user['userId'];
                                }
                             } elseif ($type == 1)
                             {
                                $chatUsers = $em->createQuery('SELECT u.id FROM BasicCmsBundle:Users u '.
                                                              'LEFT JOIN ExtendedProjectBundle:ProjectUsers p WHERE p.userId = u.id AND p.projectId = :chatid '.
                                                              'LEFT JOIN ExtendedChatBundle:ChatUser c WHERE c.userId = u.id AND c.chatType = 1 AND c.chatId = :chatid '.
                                                              'WHERE (c.visitDate < :date OR c.id IS NULL) AND (p.id IS NOT NULL OR u.id = :creater) AND u.id != :userid')->setParameters(array(
                                    'date' => new \DateTime('-5 minutes'),
                                    'chatid' => $chatEnt->getId(),
                                    'creater' => $chatEnt->getCreaterId(),
                                    'userid' => $cmsManager->getCurrentUser()->getId()
                                ))->getResult();
                                foreach ($chatUsers as $user)
                                {
                                    $filter[] = $user['id'];
                                }
                             }
                             $result['test'] = $filter;
                             $this->container->get('addone.user.alert')->bindAlert('chat', array(
                                 'object' => 'chat', 
                                 'type' => 'post', 
                                 'id' => $messageent->getId(),
                                 'chatId' => $chatEnt->getId(),
                                 'title' => $chatEnt->getTitle(), 
                                 'message' => $messageent->getMessage()
                             ), $filter);
                         }
                     }
                 }
                 if ($action == 'leave')
                 {
                     $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
                     $userChatLinkEnt = $this->container->get('doctrine')->getRepository('ExtendedChatBundle:ChatUser')->findOneBy(array('chatId' => $id, 'chatType' => 0, 'userId' => $cmsManager->getCurrentUser()->getId()));
                     if (!empty($userChatLinkEnt))
                     {
                         $em->remove($userChatLinkEnt);
                         $em->flush();
                     }
                 }                 
                 if ($action == 'delete')
                 {
                     $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
                     $chatMessage = $this->container->get('doctrine')->getRepository('ExtendedChatBundle:ChatMessage')->find($id);
                     if (!empty($chatMessage) && ($chatMessage->getCreaterId() == $cmsManager->getCurrentUser()->getId()))
                     {
                         $chatMessage->setMessage('');
                         if ($chatMessage->getAttachment() != '')
                         {
                             @unlink('..'.$chatMessage->getAttachment());
                             $chatMessage->setAttachment('');
                             $chatMessage->setAttachmentFilename('');
                             $chatMessage->setAttachmentMimetype('');
                         }
                         $em->flush();
                         $result['deleteMessage'] = $chatMessage->getId();
                     }
                 }                 
                 $result['chats'] = $em->createQuery('SELECT c.id, c.title, 0 as chatType, (SELECT count(m.id) FROM ExtendedChatBundle:ChatMessage m WHERE m.id > cu.lastSeenMessage AND m.chatId = c.id AND m.chatType = 0 AND m.createrId != :userid) as newMessages FROM ExtendedChatBundle:Chat c '.
                                                     'LEFT JOIN ExtendedChatBundle:ChatUser cu WITH cu.chatId = c.id AND cu.chatType = 0 AND cu.userId = :userid '.
                                                     'WHERE cu.id IS NOT NULL ORDER BY c.createDate DESC')->setParameter('userid', $cmsManager->getCurrentUser()->getId())->getResult();
                 foreach ($result['chats'] as &$chat)
                 {
                     $chat['members'] = $em->createQuery('SELECT u.id, u.fullName, u.avatar FROM ExtendedChatBundle:ChatUser cu '.
                                                         'LEFT JOIN BasicCmsBundle:Users u WITH u.id = cu.userId '.
                                                         'WHERE cu.chatId = :chatid AND cu.chatType = 0 ORDER BY cu.visitDate DESC')->setParameter('chatid', $chat['id'])->getResult();
                 }
                 unset($chat);
                 // PROJECTS
/*                 $result['projectChats'] = $em->createQuery('SELECT p.id, IF(pl.title is not null, pl.title, p.title) as title, 1 as chatType, (SELECT count(m.id) FROM ExtendedChatBundle:ChatMessage m WHERE m.id > cu.lastSeenMessage AND m.chatId = p.id AND m.chatType = 1 AND m.createrId != :userid) as newMessages FROM ExtendedProjectBundle:Projects p '.
                                                            'LEFT JOIN ExtendedProjectBundle:ProjectsLocale pl WITH pl.projectId = p.id AND pl.locale = :locale '.
                                                            'LEFT JOIN ExtendedChatBundle:ChatUser cu WITH cu.chatId = p.id AND cu.chatType = 1 AND cu.userId = :userid '.
                                                            'WHERE cu.id IS NOT NULL GROUP BY p.id ORDER BY p.createDate DESC')->setParameter('locale', $locale)->setParameter('userid', $cmsManager->getCurrentUser()->getId())->getResult();
                 foreach ($result['projectChats'] as &$chat)
                 {
                     $chat['members'] = $em->createQuery('SELECT u.id, u.fullName, u.avatar FROM ExtendedChatBundle:ChatUser cu '.
                                                         'LEFT JOIN BasicCmsBundle:Users u WITH u.id = cu.userId '.
                                                         'WHERE cu.chatId = :chatid AND cu.chatType = 1 ORDER BY cu.visitDate DESC')->setParameter('chatid', $chat['id'])->getResult();
                     $chat['members'] = $em->createQuery('SELECT u.id, u.fullName, u.avatar FROM ExtendedProjectBundle:ProjectUsers pu '.
                                                         'LEFT JOIN ExtendedChatBundle:ChatUser cu WITH cu.chatId = pu.projectId AND cu.chatType = 1 '.
                                                         'LEFT JOIN BasicCmsBundle:Users u WITH u.id = pu.userId '.
                                                         'WHERE pu.projectId = :chatid ORDER BY cu.visitDate DESC')->setParameter('chatid', $chat['id'])->getResult();
                 }
                 unset($chat);*/
                 $result['projectChats'] = $em->createQuery('SELECT p.createrId, p.id, IF(pl.title is not null, pl.title, p.title) as title, 1 as chatType, (SELECT count(m.id) FROM ExtendedChatBundle:ChatMessage m WHERE m.id > cu.lastSeenMessage AND m.chatId = p.id AND m.chatType = 1 AND m.createrId != :userid) as newMessages FROM ExtendedProjectBundle:Projects p '.
                                                            'LEFT JOIN ExtendedProjectBundle:ProjectsLocale pl WITH pl.projectId = p.id AND pl.locale = :locale '.
                                                            'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.projectId = p.id AND pu.userId = :userid '.
                                                            'LEFT JOIN ExtendedChatBundle:ChatUser cu WITH cu.chatId = p.id AND cu.chatType = 1 AND cu.userId = :userid '.
                                                            'WHERE p.enabled != 0 AND (pu.id IS NOT NULL OR p.createrId = :userid) GROUP BY p.id ORDER BY p.createDate DESC')->setParameter('locale', $locale)->setParameter('userid', $cmsManager->getCurrentUser()->getId())->getResult();
                 foreach ($result['projectChats'] as &$chat)
                 {
                     $chat['members'] = $em->createQuery('SELECT u.id, u.fullName, u.avatar FROM BasicCmsBundle:Users u '.
                                                         'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.userId = u.id AND pu.projectId = :chatid '.
                                                         'LEFT JOIN ExtendedChatBundle:ChatUser cu WITH cu.chatId = :chatid AND cu.chatType = 1 AND cu.userId = u.id '.
                                                         'WHERE pu.id IS NOT NULL OR u.id = :createrid ORDER BY cu.visitDate DESC')->setParameter('chatid', $chat['id'])->setParameter('createrid', $chat['createrId'])->getResult();
                 }
                 unset($chat);
                 $result['chats'] = array_merge($result['projectChats'], $result['chats']);
                 // END PROJECTS
                 $activeChat = $this->container->get('request_stack')->getCurrentRequest()->get('actionactivechat');
                 if ($activeChat != null)
                 {
                     $activeChatType = intval($this->container->get('request_stack')->getCurrentRequest()->get('actionactivechattype'));
                     $userChatLinkEnt = $this->container->get('doctrine')->getRepository('ExtendedChatBundle:ChatUser')->findOneBy(array('chatId' => $activeChat, 'chatType' => $activeChatType, 'userId' => $cmsManager->getCurrentUser()->getId()));
                     if (!empty($userChatLinkEnt))
                     {
                         if (($action == 'load') && ($result['position'] == 'bottom'))
                         {
                             $result['activemessages'] = array();
                         } else 
                         {
                             $result['activemessages'] = $em->createQuery('SELECT m.id, m.message, m.attachment, m.attachmentMimetype, m.attachmentFilename, m.createDate, m.createrId, u.fullName, u.avatar FROM ExtendedChatBundle:ChatMessage m '.
                                                                          'LEFT JOIN BasicCmsBundle:Users u WITH u.id = m.createrId '.
                                                                          'WHERE m.id > :lst AND m.chatId = :chatid AND m.chatType = :chattype ORDER BY m.id DESC')->setParameter('chattype', $activeChatType)->setParameter('chatid', $activeChat)->setParameter('lst', $userChatLinkEnt->getLastSeenMessage())->getResult();
                         }
                         $lastId = 0;
                         if (isset($result['messages']) && is_array($result['messages']))
                         {
                            foreach ($result['messages'] as $mess)
                            {
                                $lastId = max($lastId, $mess['id']);
                            }
                         }
                         foreach ($result['activemessages'] as $mess)
                         {
                             $lastId = max($lastId, $mess['id']);
                         }
                         $userChatLinkEnt->setVisitDate(new \DateTime('now'));
                         if ($lastId != 0) 
                         {
                             $userChatLinkEnt->setLastSeenMessage($lastId);
                         }
                         $em->flush();
                     } elseif (($activeChatType == 1) && ($em->createQuery('SELECT count(p.id) FROM ExtendedProjectBundle:Projects p LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.projectId = p.id AND pu.userId = :userid WHERE p.id = :id AND (p.createrId = :userid OR pu.id IS NOT NULL)')->setParameter('id', $activeChat)->setParameter('userid', $cmsManager->getCurrentUser()->getId())->getSingleScalarResult() > 0)) 
                     {
                         $lastId = 0;
                         if (isset($result['messages']) && is_array($result['messages']))
                         {
                            foreach ($result['messages'] as $mess)
                            {
                                $lastId = max($lastId, $mess['id']);
                            }
                         }
                         foreach ($result['activemessages'] as $mess)
                         {
                             $lastId = max($lastId, $mess['id']);
                         }
                         $userChatLinkEnt = new \Extended\ChatBundle\Entity\ChatUser();
                         $userChatLinkEnt->setChatId($activeChat);
                         $userChatLinkEnt->setChatType(1);
                         $userChatLinkEnt->setLastSeenMessage(0);
                         $userChatLinkEnt->setUserId($cmsManager->getCurrentUser()->getId());
                         $userChatLinkEnt->setVisitDate(new \DateTime('now'));
                         $em->persist($userChatLinkEnt);
                         $em->flush();
                     }
                 }
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.chat.') === 0) $this->container->get($item)->setFrontAction($locale, $seoPage, $action, $result);
         return $result;
     }
     
     public function getModuleTypes()
     {
         $contents = array('modulechat' => 'Модуль чата');
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.chat.') === 0) $this->container->get($item)->getModuleTypes($contents);
         return $contents;
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result = null)
     {
         $params = null;
         if ($moduleType == 'modulechat')
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2))
             {
                 // PROJECTS
                 $oldtime = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('chat_project_syncronization');
                 if (($oldtime != null) || ($oldtime + 3600 < time()))
                 {
                    $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('chat_project_syncronization', time());
                    /*$chatprojects = $em->createQuery('SELECT p.id FROM ExtendedProjectBundle:Projects p '.
                                                     'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.userId = :userid AND pu.projectId = p.id '.
                                                     'LEFT JOIN ExtendedChatBundle:ChatUser cu WITH cu.userId = :userid AND cu.chatId = p.id AND cu.chatType = 1 '.
                                                     'WHERE (pu.id IS NOT NULL OR p.createrId = :userid) AND cu.id IS NULL GROUP BY p.id')->setParameter('userid', $cmsManager->getCurrentUser()->getId())->getResult();
                    foreach ($chatprojects as $proj)
                    {
                        $chatent = new \Extended\ChatBundle\Entity\ChatUser();
                        $chatent->setChatId($proj['id']);
                        $chatent->setChatType(1);
                        $chatent->setLastSeenMessage(0);
                        $chatent->setUserId($cmsManager->getCurrentUser()->getId());
                        $chatent->setVisitDate(new \DateTime('now'));
                        $em->persist($chatent);
                        $em->flush();
                    }
                    $delchatprojects = $em->createQuery('SELECT p.id FROM ExtendedProjectBundle:Projects p '.
                                                     'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.userId = :userid AND pu.projectId = p.id '.
                                                     'LEFT JOIN ExtendedChatBundle:ChatUser cu WITH cu.userId = :userid AND cu.chatId = p.id AND cu.chatType = 1 '.
                                                     'WHERE pu.id IS NULL AND p.createrId != :userid AND cu.id IS NOT NULL GROUP BY p.id')->setParameter('userid', $cmsManager->getCurrentUser()->getId())->getResult();
                    foreach ($delchatprojects as $proj)
                    {
                        $em->createQuery('DELETE ExtendedChatBundle:ChatUser cu WHERE cu.chatType = 1 AND cu.chatId = :id AND cu.userId = :userid')->setParameter('id', $proj['id'])->setParameter('userid', $cmsManager->getCurrentUser()->getId())->execute();
                    }*/
                    // delete dublicates
                    
                    $delchatlinks = $em->createQuery('SELECT cu.id FROM ExtendedChatBundle:ChatUser cu '.
                                                     'LEFT JOIN ExtendedChatBundle:ChatUser cdu WITH cdu.userId = cu.userId AND cdu.chatId = cu.chatId AND cdu.chatType = cu.chatType AND cdu.id < cu.id '.
                                                     'WHERE cu.userId = :userid AND cdu.id IS NOT NULL GROUP BY cu.id')->setParameter('userid', $cmsManager->getCurrentUser()->getId())->getResult();
                    if (count($delchatlinks) > 0)
                    {
                        $delchatlinksids = array();
                        foreach ($delchatlinks as $delchatlink)
                        {
                            $delchatlinksids[] = $delchatlink['id'];
                        }
                        $em->createQuery('DELETE ExtendedChatBundle:ChatUser cu WHERE cu.id IN (:ids)')->setParameter('ids', $delchatlinksids)->execute();
                    }
                    
                 }                 
                 // END PROJECTS
                 $params = array();
                 $projects = $em->createQuery('SELECT p.id FROM ExtendedProjectBundle:Projects p '.
                                              'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.userId = :userid AND pu.projectId = p.id AND pu.projectRole = 3 '.
                                              'WHERE pu.id IS NOT NULL OR p.createrId = :userid')->setParameter('userid', $cmsManager->getCurrentUser()->getId())->getResult();
                 $projectids = array();
                 foreach ($projects as $proj) 
                 {
                     $projectids[] = $proj['id'];
                 }
                 if (count($projectids) > 0) {
                    $params['users'] = $em->createQuery('SELECT u.id, u.fullName FROM BasicCmsBundle:Users u '.
                                                     'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.userId = u.id AND pu.projectId IN (:ids) '.
                                                     'WHERE pu.id IS NOT NULL AND u.blocked = 2 AND u.id != :userid GROUP BY u.id')->setParameter('ids', $projectids)->setParameter('userid', $cmsManager->getCurrentUser()->getId())->getResult();
                 } else {
                    $params['users'] = array();
                 }
                 $params['isMaster'] = (count($params['users']) > 0 ? 1 : 0);
                 $params['userId'] = $cmsManager->getCurrentUser()->getId();
             }
         }
         if ($params != null) return $params;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             if (strpos($item,'addone.chat.') === 0) $params = $this->container->get($item)->getFrontModule($locale, $seoPage, $moduleType, $parameters, $result);
             if ($params != null) return $params;
         }
         return null;
     }

     
    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        if ($module == 'modulechat')
        {
            $parameters = array();
            $errors = false;
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
                return $this->container->get('templating')->render('ExtendedChatBundle:Modules:chat.html.twig',array());
            }
        }
        $answer = null;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.chat.') === 0) $answer = $this->container->get($item)->getAdminModuleController($request, $module, $actionType, $nullparameters);
            if ($answer != null) return $answer;
        }
        return null;
    }
    
    public function getInfoTitle($contentType, $contentId)
    {
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.chat.') === 0) $answer = $this->container->get($item)->getInfoTitle($contentType, $contentId);
             if ($answer != null) return $answer;
         }
         return null;
    }

    public function getInfoSitemap($contentType, $contentId)
    {
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.chat.') === 0) $answer = $this->container->get($item)->getInfoSitemap($contentType, $contentId);
             if ($answer != null) return $answer;
         }
         return null;
    }
    
//**************************************************
// Поиск по сайту
//**************************************************

    public function getSearchOptions(&$options)
    {
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.chat.') === 0) $this->container->get($item)->getSearchOptions($options);
        }
    }
    
    public function getSearchItemCount($options, $searchstring, $locale)
    {
        $count = 0;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.chat.') === 0) $count += $this->container->get($item)->getSearchItemCount($options, $searchstring, $locale);
        }
        return $count;
    }
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, &$items)
    {
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.chat.') === 0) $this->container->get($item)->getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, $items);
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
            if (strpos($item,'addone.chat.') === 0) $this->container->get($item)->getHelpChapters($chapters);
        }
        return $chapters;
    }
    
    public function getHelpContent($page, &$answer)
    {
        //if ($page == 'd1537bd0b66a4289001629ac2e06e742') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:index.html.twig',array());
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.chat.') === 0) $answer .= $this->container->get($item)->getHelpContent($page);
        }
        return $answer;
    }
    

//********************************************
// Обработка событий
//********************************************
    
    public function getAlertList($locale, $isAdmin) 
    {
        $alerts = array();
        $alerts['chat'] = ($locale != 'en' ? 'Оффлайн сообщение в чате' : 'Offline message in chat');
        return $alerts;
    }
    
}
