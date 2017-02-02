<?php

namespace Extended\ProjectBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Extended\ProjectBundle\Classes\HtmlDiff as HtmlDiff;

class ProjectManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'object.project';
    }
    
    public function getDescription()
    {
        return 'Мастерские';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Мастерские', $this->container->get('router')->generate('extended_project_list'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('project_list'));
        $manager->addAdminMenu('Создать мастерскую', $this->container->get('router')->generate('extended_project_create'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('project_new'), 'Мастерские');
        $manager->addAdminMenu('Список мастерских', $this->container->get('router')->generate('extended_project_list'), 10, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('project_list'), 'Мастерские');
        if ($this->container->has('object.taxonomy'))
        {
            $manager->addAdminMenu('Категории', $this->container->get('router')->generate('basic_cms_taxonomy_list').'?object=object.project', 9999, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_list') | $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('taxonomy_listshow'), 'Мастерские');
        }
        $manager->addAdminMenu('Прочие настройки', $this->container->get('router')->generate('extended_project_messages'), 10000, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('project_editall'), 'Мастерские');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) $this->container->get($item)->registerMenu();

    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('project_list','Просмотр списка мастерских');
        $manager->addRole('project_viewown','Просмотр собственных мастерских');
        $manager->addRole('project_viewall','Просмотр всех мастерских');
        $manager->addRole('project_new','Создание новых мастерских');
        $manager->addRole('project_editown','Редактирование собственных мастерских');
        $manager->addRole('project_editall','Редактирование всех мастерских');
        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) $this->container->get($item)->registerRoles();
        
     }
     
     public function getContentTypes()
     {
        $contents = array('view' => 'Просмотр мастерской', 'edit' => 'Редактирование мастерской');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) $this->container->get($item)->getContentTypes($contents);
        return $contents;
     }
     
     public function getTaxonomyType()
     {
         return true;
     }

     public function getTemplateTwig($contentType, $template)
     {
         if ($contentType == 'view') return 'ExtendedProjectBundle:Front:projectview.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) 
         {
             $result = $this->container->get($item)->getTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         if ($contentType == 'projectRequest') return 'ExtendedProjectBundle:Front:modulerequest.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) 
         {
             $result = $this->container->get($item)->getModuleTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }
     
     private function diff($oldText, $newText)
     {
         $imagereplaces = array();
         preg_match_all('/<[iI][mM][gG]\s+[^>]*/ui', $oldText, $matches);
         foreach ($matches[0] as $match)
         {
             $imagereplaces['image'.md5($match).'endimage'] = $match.'>';
         }
         preg_match_all('/<[iI][mM][gG]\s+[^>]*/ui', $newText, $matches);
         foreach ($matches[0] as $match)
         {
             $imagereplaces['image'.md5($match).'endimage'] = $match.'>';
         }
         $oldverdiff = str_replace(array_values($imagereplaces), array_keys($imagereplaces), $oldText);
         $newverdiff = str_replace(array_values($imagereplaces), array_keys($imagereplaces), $newText);
         $htmldiff = new HtmlDiff($oldverdiff, $newverdiff);
         $htmldiff->build();
         $result = $htmldiff->getDifference();
         unset($htmldiff);
         $result = str_replace(array_keys($imagereplaces), array_values($imagereplaces), $result);
         return $result;
     }
     
     
     public function getFrontContent($locale, $contentType, $contentId, $result = null)
     {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $cmsManager = $this->container->get('cms.cmsManager');
             // Найти проект
             $query = $em->createQuery('SELECT p.id, p.createDate, p.modifyDate, p.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                       'IF(pl.title is not null, pl.title, p.title) as title, '.
                                       'IF(pl.description is not null, pl.description, p.description) as description, '.
                                       'IF(pl.content is not null, pl.content, p.content) as content, '.
                                       'p.template, p.viewCount, p.isPublic, p.isUnvisible, p.isLocked FROM ExtendedProjectBundle:Projects p '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.
                                       'LEFT JOIN ExtendedProjectBundle:ProjectsLocale pl WITH pl.projectId = p.id AND pl.locale = :locale '.
                                       'WHERE p.id = :id AND p.enabled != 0')->setParameter('id', $contentId)->setParameter('locale', $locale);
             $project = $query->getResult();
             if (empty($project)) return null;
             if (isset($project[0])) $params = $project[0]; else return null;
             // Найти пользователей проекта
             $query = $em->createQuery('SELECT u.id, pu.readOnly, pu.projectRole, u.login, u.fullName, u.avatar FROM ExtendedProjectBundle:ProjectUsers pu '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = pu.userId '.
                                       'WHERE pu.projectId = :id')->setParameter('id', $contentId);
             $params['projectUsers'] = $query->getResult();
             if (!is_array($params['projectUsers'])) $params['projectUsers'] = array();
             // Проверить доступ к проекту
             if ($params['isUnvisible'] != 0)
             {
                 if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2)) 
                 {
                     $usersearch = false;
                     foreach ($params['projectUsers'] as $projectuser) if ($cmsManager->getCurrentUser()->getId() == $projectuser['id']) 
                     {
                         $usersearch = true;
                     }
                     if (($usersearch == false) && ($cmsManager->getCurrentUser()->getId() != $params['createrId']))
                     {
                         $this->container->get('cms.cmsManager')->setErrorPageNumber(1); return null;
                     }
                 } else 
                 {
                     $this->container->get('cms.cmsManager')->setErrorPageNumber(1); return null;
                 }
             }
             // Локали
             $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
             $params['locales'] = $query->getResult();
             if (!is_array($params['locales'])) $params['locales'] = array();
             $params['documentsLocale'] = '';
             $postlocale = $this->container->get('request_stack')->getCurrentRequest()->get('locale');
             if ($postlocale == '')
             {
                 $params['documentsLocale'] = $locale;
             } elseif ($postlocale == 'default')
             {
                 $params['documentsLocale'] = '';
             } elseif ($postlocale == 'all')
             {
                 $params['documentsLocale'] = 'all';
             } else
             {
                foreach ($params['locales'] as $tmplocale)
                {
                    if ($tmplocale['shortName'] == $postlocale)
                    {
                        $params['documentsLocale'] = $tmplocale['shortName'];
                    }
                }
             }
             // Проверить на выбранный документ
             $params['fileView'] = 0;
             $fileid = $this->container->get('request_stack')->getCurrentRequest()->get('file');
             if ($fileid) {
                 $params['fileView'] = 1;
                 $params['fileId'] = $fileid;
                 if ($this->container->get('request_stack')->getCurrentRequest()->get('view') == 'true') {
                     $params['fileView'] = 2;
                     $params['fileLocale'] = $this->container->get('request_stack')->getCurrentRequest()->get('locale');
                     $params['fileVersion'] = $this->container->get('request_stack')->getCurrentRequest()->get('version');
                 }
             }
             
             
             $params['documentView'] = 0;
             $documentid = $this->container->get('request_stack')->getCurrentRequest()->get('document');
             if ($documentid != null)
             {
                 $versionid = $this->container->get('request_stack')->getCurrentRequest()->get('version');
                 if (($versionid !== null) && ($versionid !== 'new'))
                 {
                    $query = $em->createQuery('SELECT d.id, d.createDate, d.createrId, d.parentId, '.
                                              'u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                              'd.title, d.content, d.readOnly, d.isPublic, d.isMaster FROM ExtendedProjectBundle:ProjectDocuments d '.
                                              'LEFT JOIN BasicCmsBundle:Users u WITH u.id = d.createrId '.
                                              'WHERE d.parentId = :id AND d.id = :version AND d.projectId = :projectid AND d.enabled != 0 ORDER BY d.createDate DESC')->setParameter('version', $versionid)->setParameter('id', $documentid)->setParameter('projectid', $contentId);
                 } else
                 {
                    $query = $em->createQuery('SELECT d.id, d.createDate, d.createrId, d.parentId, '.
                                              'u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                              'd.title, d.content, d.readOnly, d.isPublic, d.isMaster FROM ExtendedProjectBundle:ProjectDocuments d '.
                                              'LEFT JOIN BasicCmsBundle:Users u WITH u.id = d.createrId '.
                                              'WHERE d.parentId = :id AND d.projectId = :projectid AND d.enabled != 0 ORDER BY d.createDate DESC')->setMaxResults(1)->setParameter('id', $documentid)->setParameter('projectid', $contentId);
                 }
                 $document = $query->getResult();
                 if (isset($document[0])) 
                 {
                     $params['documentView'] = 1;
                     if ($versionid === 'new') 
                     {
                         $params['documentView'] = 2;
                     }
                     $params['document'] = $document[0];
                     // Найти переводы
                     $params['document']['title'] = array('default' => $params['document']['title']);
                     $params['document']['content'] = array('default' => $params['document']['content']);
                     foreach ($params['locales'] as $tlocale) {
                         $transEnt = $em->getRepository('ExtendedProjectBundle:ProjectDocumentsLocale')->findOneBy(array('documentId' => $params['document']['id'], 'locale' => $tlocale['shortName']));
                         if (!empty($transEnt)) {
                             $params['document']['title'][$tlocale['shortName']] = $transEnt->getTitle();
                             $params['document']['content'][$tlocale['shortName']] = $transEnt->getContent();
                         } else {
                             $params['document']['title'][$tlocale['shortName']] = $params['document']['title']['default'];
                             $params['document']['content'][$tlocale['shortName']] = '';
                         }
                     }
                     $params['document']['localeTitle'] = (isset($params['document']['title'][$locale]) ? $params['document']['title'][$locale] : $params['document']['title']['default']);
                     // end
                     $params['documentPremissions'] = array('read' => 0, 'write' => 0, 'master' => 0);
                     if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2)) 
                     {
                         foreach ($params['projectUsers'] as $projectuser) if ($cmsManager->getCurrentUser()->getId() == $projectuser['id']) 
                         {
                             if (($projectuser['readOnly'] == 0) && ($params['isLocked'] == 0)) $params['documentPremissions']['write'] = 1;
                             if ($projectuser['projectRole'] == 3) 
                             {
                                 $params['documentPremissions']['read'] = 1;
                                 $params['documentPremissions']['master'] = 1;
                                 $params['documentPremissions']['write'] = 1;
                             }
                             if ($params['document']['isPublic'] == 0)
                             {
                                 $params['documentPremissions']['read'] = 1;
                                 $params['documentPremissions']['write'] = 1;
                             }
                         }
                         if ($cmsManager->getCurrentUser()->getId() == $params['createrId'])
                         {
                             $params['documentPremissions']['read'] = 1;
                             $params['documentPremissions']['write'] = 1;
                             $params['documentPremissions']['master'] = 1;
                         }
                         /*if ($params['document']['isPublic'] != 0)
                         {
                             $params['documentPremissions']['write'] = 1;
                         }
                         if ($params['isPublic'] != 0)
                         {
                             $params['documentPremissions']['read'] = 1;
                             if ($params['isPublic'] == 2) $params['documentPremissions']['write'] = 1;
                         }*/
                         if ($params['document']['isPublic'] == 2)
                         {
                             $params['documentPremissions']['read'] = 1;
                             $params['documentPremissions']['write'] = 1;
                         }
                     }
                     if ($params['document']['isPublic'] == 1)
                     {
                         $params['documentPremissions']['read'] = 1;
                     }
                     if ($params['document']['readOnly'] != 0)
                     {
                         $params['documentPremissions']['write'] = 0;
                     }
                     if ($params['documentPremissions']['read'] == 0) $params['documentView'] = 0;
                     if ($params['document']['isMaster'] != 1)
                     {
                        $query = $em->createQuery('SELECT d.id, d.title, d.content FROM ExtendedProjectBundle:ProjectDocuments d '.
                                                  'WHERE d.parentId = :id AND d.projectId = :projectid AND d.enabled != 0 AND d.createDate < :date ORDER BY d.createDate DESC')->setMaxResults(1)->setParameter('id', $documentid)->setParameter('projectid', $contentId)->setParameter('date', $document[0]['createDate']);
                        $prevdocument = $query->getResult();
                        if (isset($prevdocument[0]))
                        {
                            $params['document']['diffContent'] = array('default' => $this->diff($prevdocument[0]['content'], $params['document']['content']['default']));
                            // Найти diff переводов
                            foreach ($params['locales'] as $tlocale) {
                                $transEnt = $em->getRepository('ExtendedProjectBundle:ProjectDocumentsLocale')->findOneBy(array('documentId' => $prevdocument[0]['id'], 'locale' => $tlocale['shortName']));
                                if (!empty($transEnt)) {
                                    $params['document']['diffContent'][$tlocale['shortName']] = $this->diff($transEnt->getContent(), $params['document']['content'][$tlocale['shortName']]);
                                } else {
                                    $params['document']['diffContent'][$tlocale['shortName']] = $params['document']['content'][$tlocale['shortName']];
                                }
                            }
                            // end
                        }
                     }
                     $params['document']['editStatus'] = '';
                     $params['document']['editTitle'] = $params['document']['title'];
                     $params['document']['editTitleError'] = array();
                     $params['document']['editContent'] = $params['document']['content'];
                     $params['document']['editContentError'] = array();
                     $params['document']['editIsMaster'] = '';
                     if (isset($result['action']) && ($result['action'] == 'object.project.edit') && isset($result['actionId']) && ($result['actionId'] == $documentid))
                     {
                         if ($result['status'] != 'OK')
                         {
                             $params['documentView'] = 2;
                             $params['document']['editStatus'] = $result['status'];
                             if (isset($result['title'])) $params['document']['editTitle'] = $result['title'];
                             if (isset($result['titleError'])) $params['document']['editTitleError'] = $result['titleError'];
                             if (isset($result['content'])) $params['document']['editContent'] = $result['content'];
                             if (isset($result['contentError'])) $params['document']['editContentError'] = $result['contentError'];
                             if (isset($result['isMaster'])) $params['document']['editIsMaster'] = $result['isMaster'];
                         } else $params['document']['editStatus'] = 'OK';
                     }
                 }
             }
             // Определить права текущего пользователя
             $params['premissions'] = array('read' => 0, 'write' => 0, 'config' => 0, 'role' => 0);
             if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2)) 
             {
                 $params['premissions']['role'] = 1;
                 foreach ($params['projectUsers'] as $projectuser) if ($cmsManager->getCurrentUser()->getId() == $projectuser['id']) 
                 {
                     $params['premissions']['role'] = 2;
                     $params['premissions']['read'] = 1;
                     if (($projectuser['readOnly'] == 0) && ($params['isLocked'] == 0)) $params['premissions']['write'] = 1;
                     if ($projectuser['projectRole'] == 3) 
                     {
                         $params['premissions']['role'] = 3;
                         $params['premissions']['write'] = 1;
                         $params['premissions']['config'] = 1;
                     }
                 }
                 if ($cmsManager->getCurrentUser()->getId() == $params['createrId'])
                 {
                     $params['premissions']['role'] = 3;
                     $params['premissions']['read'] = 1;
                     $params['premissions']['write'] = 1;
                     $params['premissions']['config'] = 1;
                 }
                 /*if ($params['isPublic'] != 0)
                 {
                     $params['premissions']['read'] = 1;
                     if ($params['isPublic'] == 2) $params['premissions']['write'] = 1;
                 }*/
             }
             // Найти документы
             $query = $em->createQuery('SELECT d.parentId, count(d.id) as documentCount FROM ExtendedProjectBundle:ProjectDocuments d WHERE d.projectId = :projectid AND d.enabled != 0 GROUP BY d.parentId')->setParameter('projectid', $contentId);
             $params['documentGroups'] = $query->getResult();
             if (!is_array($params['documentGroups'])) $params['documentGroups'] = array();
             $locSelect = 'd.title as title_default';
             $locJoin = '';
             foreach ($params['locales'] as $tlocale) {
                 $locSelect .= ', l'.$tlocale['shortName'].'.title as title_'.$tlocale['shortName'];
                 $locJoin .= 'LEFT JOIN ExtendedProjectBundle:ProjectDocumentsLocale l'.$tlocale['shortName'].' WITH l'.$tlocale['shortName'].'.locale = \''.$tlocale['shortName'].'\' AND l'.$tlocale['shortName'].'.documentId = d.id ';
             }
             $query = $em->createQuery('SELECT d.id, d.createDate, d.createrId, d.parentId, '.
                                       'u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                       $locSelect.', d.readOnly, d.isPublic, d.isMaster FROM ExtendedProjectBundle:ProjectDocuments d '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = d.createrId '.$locJoin.
                                       'WHERE d.projectId = :projectid AND d.enabled != 0 ORDER BY d.createDate ASC')->setParameter('projectid', $contentId);
             $params['documents'] = $query->getResult();
             if (!is_array($params['documents'])) $params['documents'] = array();
             foreach ($params['documents'] as $dock=>&$doc) {
                 $doc['title'] = array();
                 if (isset($doc['title_'.$locale]) && ($doc['title_'.$locale] != null)) {
                     $doc['title'][] = $doc['title_'.$locale];
                 }
                 $doc['title'][] = $doc['title_default'];
                 foreach ($params['locales'] as $tlocale) {
                     if (isset($doc['title_'.$tlocale['shortName']]) && ($doc['title_'.$tlocale['shortName']] != null) && ($tlocale['shortName'] != $locale)) {
                        $doc['title'][] = $doc['title_'.$tlocale['shortName']];
                     }
                 }
                 if ((($doc['isPublic'] == 0) && ($params['premissions']['role'] < 2)) ||
                     (($doc['isPublic'] == 2) && ($params['premissions']['role'] < 1)) ||
                     (($doc['isPublic'] == 3) && ($params['premissions']['role'] < 3))) {
                     unset($params['documents'][$dock]);
                 }
             }
             unset($doc);
             
             
             /*foreach ($params['documents'] as &$doc)
             {
                 $doc['isMaster'] = 0;
                 if ($doc['createrId'] == $params['createrId']) 
                 {
                     $doc['isMaster'] = 1;
                 } else
                 {
                    foreach ($params['projectUsers'] as $projectuser) 
                    {
                         if (($doc['createrId'] == $projectuser['id']) && ($projectuser['projectRole'] == 3))
                        {
                             $doc['isMaster'] = 1;
                         }
                     }
                 }
             }*/
             unset($doc);
             // Найти файлы
             $params['files'] = array();
             $projectLink = @unserialize(file_get_contents('secured/project/'.'.projectlink'));
             if (!is_array($projectLink)) $projectLink = array();
             foreach ($projectLink as $projectKey=>$projectVal) 
             {
                 if (($projectVal['projectId'] == $params['id']) && is_file('secured/project/'.($projectVal['path'] != '' ? $projectVal['path'].'/' : '').$projectVal['file']))
                 {
                     if ($this->container->has('object.knowledge')) {
                         $info = $this->container->get('object.knowledge')->getKnowledgeParameters($locale, 1, $contentId, $projectVal['path'].'/'.$projectVal['file']);
                         $nameFileTitle = (isset($info['objectTitleForLocale']) ? $info['objectTitleForLocale'] : '');
                     } else {
                         $nameFileTitle = '';
                     }
                     
                     $downloadurl = '/system/download/mceprojectfile?projectid='.$params['id'].'&file='.urlencode(($projectVal['path'] != '' ? $projectVal['path'].'/' : '').$projectVal['file']);
                     $pathTitle = $projectVal['path'];
                     if ($locale == 'en') {
                         $pathTitle = preg_replace('/#[^\/]+/ui', '', $pathTitle);
                     } else {
                         $pathTitle = preg_replace('/[^\/]+#/ui', '', $pathTitle);
                     }
                     $params['files'][] = array('title' => $nameFileTitle, 'file'=>$projectVal['file'], 'path'=>$projectVal['path'], 'pathTitle' => $pathTitle, 'downurl'=>$downloadurl, 'size'=>filesize('secured/project/'.($projectVal['path'] != '' ? $projectVal['path'].'/' : '').$projectVal['file']));
                 }
             }
             // Прибавить счётчик просмотров
             $cmsManager->addCookie(new \Symfony\Component\HttpFoundation\Cookie('front_project_viewed_'.$params['id'], 1, time() + 3600));
             if (!$this->container->get('request_stack')->getCurrentRequest()->cookies->has('front_project_viewed_'.$params['id']))
             {
                 $query = $em->createQuery('UPDATE ExtendedProjectBundle:Projects p SET p.viewCount = p.viewCount + 1 WHERE p.id = :id')->setParameter('id', $params['id']);
                 $query->execute();
             }
             // Форма создания нового документа
             $params['createStatus'] = '';
             $params['createTitle'] = '';
             $params['createTitleError'] = '';
             $params['createContent'] = '';
             $params['createContentError'] = '';
             if (isset($result['action']) && ($result['action'] == 'object.project.create') && isset($result['actionId']) && ($result['actionId'] == $contentId))
             {
                 if ($result['status'] != 'OK')
                 {
                     $params['createStatus'] = $result['status'];
                     if (isset($result['title'])) $params['createTitle'] = $result['title'];
                     if (isset($result['titleError'])) $params['createTitleError'] = $result['titleError'];
                     if (isset($result['content'])) $params['createContent'] = $result['content'];
                     if (isset($result['contentError'])) $params['createContentError'] = $result['contentError'];
                 } else $params['createStatus'] = 'OK';
             }
             // Запросы на добавление к проекту
             if ($params['premissions']['config'])
             {
                 $query = $em->createQuery('SELECT r.id, r.projectRole, r.message, u.login, u.fullName, u.avatar FROM ExtendedProjectBundle:ProjectsRequests r '.
                                           'LEFT JOIN BasicCmsBundle:Users u WITH u.id = r.userId '.
                                           'WHERE r.projectId = :id')->setParameter('id', $contentId);
                 $params['projectRequests'] = $query->getResult();
                 if (!is_array($params['projectRequests'])) $params['projectRequests'] = array();
             } else $params['projectRequests'] = array();
             
             // Найти информацию для базы знаний
             if ($this->container->has('object.knowledge')) {
                 $params['knowledgeEnabled'] = 1;
                 $params['knowledgeInfo'] = $this->container->get('object.knowledge')->getKnowledgeInfo();
             }
             // Найти сообщения блога
             $query = $em->createQuery('SELECT t.id, t.createDate, t.modifyDate, t.createrId, u.fullName as createrFullName, u.login as createrLogin, '.
                                       'IF(tl.title is not null, tl.title, t.title) as title, '.
                                       'IF(tl.description is not null, tl.description, t.description) as description, '.
                                       'IF(tl.content is not null, tl.content, t.content) as content '.
                                       'FROM BasicCmsBundle:TextPages t '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.
                                       'LEFT JOIN BasicCmsBundle:TextPagesLocale tl WITH tl.textPageId = t.id AND tl.locale = :locale '.
                                       'LEFT JOIN ExtendedProjectBundle:ProjectBlogLink l WITH l.itemId = t.id AND l.projectId = :categoryid '.
                                       'WHERE l.id IS NOT NULL AND t.enabled != 0 ORDER BY t.createDate ASC')->setParameter('categoryid', $params['id'])->setParameter('locale', $locale);
             $params['blogPosts'] = $query->getResult();
             // Вывод
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>'', 'description'=>'', 'robotindex'=>true);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         if ($contentType == 'edit')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $cmsManager = $this->container->get('cms.cmsManager');
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('id');
             // Найти проект
             $query = $em->createQuery('SELECT p.id, p.createDate, p.modifyDate, p.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, '.
                                       'IF(pl.title is not null, pl.title, p.title) as title, p.title as editTitle, '.
                                       'IF(pl.description is not null, pl.description, p.description) as description, p.description as editDescription, '.
                                       'IF(pl.content is not null, pl.content, p.content) as content, p.content as editContent, '.
                                       'p.template, p.viewCount, p.isPublic, p.isUnvisible, p.isLocked, sp.url FROM ExtendedProjectBundle:Projects p '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.
                                       'LEFT JOIN ExtendedProjectBundle:ProjectsLocale pl WITH pl.projectId = p.id AND pl.locale = :locale '.
                                       'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.project\' AND sp.contentAction = \'view\' AND sp.contentId = p.id '.
                                       'WHERE p.id = :id AND p.enabled != 0')->setParameter('id', $id)->setParameter('locale', $locale);
             $project = $query->getResult();
             if (empty($project)) return null;
             if (isset($project[0])) $params = $project[0]; else return null;
             $params['template'] = 'mir_project_edit';
             // Проверить права на доступ к странице
             if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2))
             {
                $query = $em->createQuery('SELECT count(pu.id) FROM ExtendedProjectBundle:ProjectUsers pu '.
                                          'WHERE pu.projectId = :id AND pu.userId = :userid AND pu.projectRole = 3')->setParameter('userid', $cmsManager->getCurrentUser()->getId())->setParameter('id', $id);
                if ((intval($query->getSingleScalarResult()) == 0) && ($cmsManager->getCurrentUser()->getId() !== $params['createrId']))
                {
                    $this->container->get('cms.cmsManager')->setErrorPageNumber(1); return null;
                }
             } else 
             {
                 $this->container->get('cms.cmsManager')->setErrorPageNumber(1); return null;
             }
             // Локали
             $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
             $params['locales'] = $query->getResult();
             if (!is_array($params['locales'])) $params['locales'] = array();
             // Найти информацию по проету
             $params['editTitle'] = array('default' => $params['editTitle']);
             $params['editDescription'] = array('default' => $params['editDescription']);
             $params['editContent'] = array('default' => $params['editContent']);
             $params['editTitleError'] = array('default' => '');
             $params['editDescriptionError'] = array('default' => '');
             $params['editContentError'] = array('default' => '');
             foreach ($params['locales'] as $localeitem)
             {
                 $projectlocent = $this->container->get('doctrine')->getRepository('ExtendedProjectBundle:ProjectsLocale')->findOneBy(array('projectId' => $id, 'locale' => $localeitem['shortName']));
                 if (!empty($projectlocent))
                 {
                    $params['editTitle'][$localeitem['shortName']] = $projectlocent->getTitle();
                    $params['editDescription'][$localeitem['shortName']] = $projectlocent->getDescription();
                    $params['editContent'][$localeitem['shortName']] = $projectlocent->getContent();
                 } else 
                 {
                    $params['editTitle'][$localeitem['shortName']] = '';
                    $params['editDescription'][$localeitem['shortName']] = '';
                    $params['editContent'][$localeitem['shortName']] = '';
                 }
                 $params['editTitleError'][$localeitem['shortName']] = '';
                 $params['editDescriptionError'][$localeitem['shortName']] = '';
                 $params['editContentError'][$localeitem['shortName']] = '';
                 unset($projectlocent);
             }
             $params['editStatus'] = '';
             // Форма редактирования проекта
             if (isset($result['action']) && ($result['action'] == 'object.project.editproject') && isset($result['actionId']) && ($result['actionId'] == $id))
             {
                 if ($result['status'] != 'OK')
                 {
                     $params['editStatus'] = $result['status'];
                     if (isset($result['editTitle'])) $params['editTitle'] = array_merge($params['editTitle'], $result['editTitle']);
                     if (isset($result['editDescription'])) $params['editDescription'] = array_merge($params['editDescription'], $result['editDescription']);
                     if (isset($result['editContent'])) $params['editContent'] = array_merge($params['editContent'], $result['editContent']);
                     if (isset($result['editTitleError'])) $params['editTitleError'] = array_merge($params['editTitleError'], $result['editTitleError']);
                     if (isset($result['editDescriptionError'])) $params['editDescriptionError'] = array_merge($params['editDescriptionError'], $result['editDescriptionError']);
                     if (isset($result['editContentError'])) $params['editContentError'] = array_merge($params['editContentError'], $result['editContentError']);
                     
                 } else $params['editStatus'] = 'OK';
             }
             // Вывод
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>'', 'description'=>'', 'robotindex'=>true);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         $params = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
         return $params;
     }
     
     public function setFrontAction($locale, $seoPage, $action)
     {
         $result = null;
         if ($action == 'edit')
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             // Найти документ
             $query = $em->createQuery('SELECT d.id, d.readOnly, d.isPublic, d.projectId FROM ExtendedProjectBundle:ProjectDocuments d '.
                                       'WHERE d.id = :id AND d.enabled != 0')->setParameter('id', $id);
             $document = $query->getResult();
             if (isset($document[0]))
             {
                $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
                $locales = $query->getResult();
                $document = $document[0];
                $errors = false;
                $result['status'] = '';
                $result['action'] = 'object.project.edit';
                $result['actionId'] = $id;
                $result['title'] = array();
                $result['content'] = array();
                $result['titleError'] = array();
                $result['contentError'] = array();
                $result['isMaster'] = null;
                $result['title']['default'] = (isset($postfields['title']['default']) ? $postfields['title']['default'] : '');
                $result['content']['default'] = (isset($postfields['content']['default']) ? $postfields['content']['default'] : '');
                foreach ($locales as $tlocale) {
                    $result['title'][$tlocale['shortName']] = (isset($postfields['title'][$tlocale['shortName']]) ? $postfields['title'][$tlocale['shortName']] : '');
                    $result['content'][$tlocale['shortName']] = (isset($postfields['content'][$tlocale['shortName']]) ? $postfields['content'][$tlocale['shortName']] : '');
                }
                if (isset($postfields['isMaster']) && ($postfields['isMaster'] == 1)) 
                {
                    $result['isMaster'] = 1;
                }
                // Найти проект
                $query = $em->createQuery('SELECT p.id, p.createrId, p.isPublic, p.title, sp.url, p.isLocked FROM ExtendedProjectBundle:Projects p '.
                                          'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.project\' AND sp.contentAction = \'view\' AND sp.contentId = p.id '.
                                          'WHERE p.id = :id AND p.enabled != 0')->setParameter('id', $document['projectId']);
                $project = $query->getResult();
                if (!isset($project[0]))
                {
                     $result['status'] = 'Project error';
                } else
                {
                    $premission = 0;
                    $premissionMaster = 0;
                    $userRole = 0;
                    // Проверить права на редактирование
                    if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2)) 
                    {
                         if ($cmsManager->getCurrentUser()->getId() == $project[0]['createrId']) 
                         {
                             $premissionMaster = 1;
                             $userRole = 3;
                         } else
                         {
                             $query = $em->createQuery('SELECT count(pu.id) as usercount FROM ExtendedProjectBundle:ProjectUsers pu '.
                                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = pu.userId '.
                                                       'WHERE pu.projectId = :projectid AND pu.userId = :userid AND u.blocked = 2 AND pu.readOnly = 0')->setParameter('projectid', $document['projectId'])->setParameter('userid', $cmsManager->getCurrentUser()->getId());
                             $users = $query->getResult();
                             if (isset($users[0]['usercount']) && ($users[0]['usercount'] > 0)) $userRole = 2;
                             $query = $em->createQuery('SELECT count(pu.id) as usercount FROM ExtendedProjectBundle:ProjectUsers pu '.
                                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = pu.userId '.
                                                       'WHERE pu.projectId = :projectid AND pu.userId = :userid AND u.blocked = 2 AND pu.readOnly = 0 AND pu.projectRole = 3')->setParameter('projectid', $document['projectId'])->setParameter('userid', $cmsManager->getCurrentUser()->getId());
                             $users = $query->getResult();
                             if (isset($users[0]['usercount']) && ($users[0]['usercount'] > 0)) {$userRole = 3;$premissionMaster = 1;}
                         }
                         $userRole = 1;
                         if (($document['isPublic'] == 2) && ($userRole >= 1)) $premission = 1;
                         if (($document['isPublic'] == 0) && ($userRole >= 2)) $premission = 1;
                         if (($document['isPublic'] == 3) && ($userRole >= 3)) $premission = 1;
                         if (($document['isPublic'] == 1) && ($userRole >= 0)) $premission = 1;
                         /*if ($project[0]['isPublic'] == 2) $premission = 1;*/
                         if ($document['readOnly'] != 0) $premission = 0;
                         if ($project[0]['isLocked'] != 0)
                         {
                             $premission = $premissionMaster;
                         }
                    }
                    if ($premission == 0)
                    {
                        $result['status'] = 'Access error';
                    } else
                    {
                        // Сохранение и результат
                        if (!preg_match("/^.{3,}$/ui", $result['title']['default'])) {$errors = true; $result['titleError']['default'] = 'Preg error';}
                        foreach ($locales as $tlocale) {
                            if (!preg_match("/^.{3,}$/ui", $result['title'][$tlocale['shortName']])) {$errors = true; $result['titleError'][$tlocale['shortName']] = 'Preg error';}
                        }
                        if ($errors == false)
                        {
                            $olddocumentent = $this->container->get('doctrine')->getRepository('ExtendedProjectBundle:ProjectDocuments')->find($id);
                            $this->container->get('cms.cmsManager')->unlockTemporaryEditor($result['content']['default'], '');
                            foreach ($locales as $tlocale) {
                                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($result['content'][$tlocale['shortName']], '');
                            }
                            $groupCount = $em->createQuery('SELECT count(d.id) FROM ExtendedProjectBundle:ProjectDocuments d WHERE d.parentId = :id')->setParameter('id', $olddocumentent->getParentId())->getSingleScalarResult();
                            $localeCount = $em->createQuery('SELECT count(d.id) FROM ExtendedProjectBundle:ProjectDocumentsLocale d WHERE d.documentId = :id')->setParameter('id', $olddocumentent->getId())->getSingleScalarResult();
                            if (($olddocumentent->getContent() == '') && ($groupCount == 1) && ($localeCount == 0) && ($olddocumentent->getCreaterId() == $cmsManager->getCurrentUser()->getId()))
                            {
                                $olddocumentent->setCreateDate(new \DateTime('now'));
                                $olddocumentent->setTitle($result['title']['default']);
                                $olddocumentent->setContent($result['content']['default']);
                                if ($premissionMaster != 0)
                                {
                                    $olddocumentent->setIsMaster($result['isMaster']);
                                } else {
                                    $olddocumentent->setIsMaster(null);
                                }
                                $em->flush();
                                $documentent = $olddocumentent;
                            } else 
                            {
                                $documentent = new \Extended\ProjectBundle\Entity\ProjectDocuments();
                                $documentent->setContent($result['content']['default']);
                                $documentent->setCreateDate(new \DateTime('now'));
                                $documentent->setCreaterId($cmsManager->getCurrentUser()->getId());
                                $documentent->setEnabled($olddocumentent->getEnabled());
                                $documentent->setIsPublic($olddocumentent->getIsPublic());
                                $documentent->setProjectId($olddocumentent->getProjectId());
                                $documentent->setReadOnly($olddocumentent->getReadOnly());
                                $documentent->setTitle($result['title']['default']);
                                $documentent->setParentId($olddocumentent->getParentId());
                                if ($premissionMaster != 0)
                                {
                                    $documentent->setIsMaster($result['isMaster']);
                                }
                                $em->persist($documentent);
                                $em->flush();
                            }
                            foreach ($locales as $tlocale) {
                                $locdocent = new \Extended\ProjectBundle\Entity\ProjectDocumentsLocale();
                                $locdocent->setContent($result['content'][$tlocale['shortName']]);
                                $locdocent->setTitle($result['title'][$tlocale['shortName']]);
                                $locdocent->setDocumentId($documentent->getId());
                                $locdocent->setLocale($tlocale['shortName']);
                                $em->persist($locdocent);
                                $em->flush();
                            }
                            
                            $result['status'] = 'OK';
                            // Запись в лог
                            if ($this->container->has('addone.user.alert'))
                            {
                                //$this->container->get('addone.user.alert')->bindAlert('project_'.$documentent->getProjectId(), array('object' => 'project', 'type' => 'edit', 'id' => $documentent->getId(), 'title' => $documentent->getTitle(), 'projectTitle' => $project[0]['title']));
                                $this->container->get('addone.user.alert')->bindAlert('project_'.$documentent->getProjectId(), array(
                                        'object' => 'project', 
                                        'type' => 'edit', 
                                        'id' => $documentent->getId(), 
                                        'title' => $documentent->getTitle().' (версия от '.$documentent->getCreateDate()->format('d.m.Y H:i').' '.$cmsManager->getCurrentUser()->getFullName().')', 
                                        'projectTitle' => $project[0]['title'],
                                        'url' => '<a href="http://'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost().'/'.$project[0]['url'].'?document='.$documentent->getParentId().'&version='.$documentent->getId().'">'.$documentent->getTitle().' (версия от '.$documentent->getCreateDate()->format('d.m.Y H:i').' '.$cmsManager->getCurrentUser()->getFullName().')'.'</a>'
                                    ));
                            }
                            if ($this->container->has('object.knowledge')) {
                                $knowledgeTitle = (isset($postfields['knowledgetitle']) ? $postfields['knowledgetitle'] : null);
                                $knowledgeTypes = (isset($postfields['knowledgetypes']) ? $postfields['knowledgetypes'] : null);
                                $knowledgeDescription = (isset($postfields['knowledgedescription']) ? $postfields['knowledgedescription'] : null);
                                $objectEnt = $this->container->get('object.knowledge')->checkKnowledgeObject(\Extended\KnowledgeBundle\Entity\KnowledgeObject::KO_TYPE_DOCUMENT, $documentent->getParentId(), null);
                                if ($objectEnt == false) {
                                    $this->container->get('object.knowledge')->addKnowledgeObject(
                                        \Extended\KnowledgeBundle\Entity\KnowledgeObject::KO_TYPE_DOCUMENT, 
                                        $documentent->getParentId(), 
                                        null, 
                                        $knowledgeTitle, 
                                        $knowledgeTypes
                                    );
                                } else {
                                    $this->container->get('object.knowledge')->changeKnowledgeObject(
                                        \Extended\KnowledgeBundle\Entity\KnowledgeObject::KO_TYPE_DOCUMENT, 
                                        $documentent->getParentId(), 
                                        null, 
                                        $knowledgeTitle, 
                                        $knowledgeTypes,
                                        $knowledgeDescription
                                    );
                                }
                            }
                            $this->container->get('cms.cmsManager')->logPushFront(0, 'edit', 'Изменение документа к мастерской "'.$result['title']['default'].'"', $this->container->get('router')->generate('extended_project_documents_edit').'?id='.$documentent->getId());
                        } else $result['status'] = 'Check error';
                    }
                }
             }
         }
         if ($action == 'create')
         {
             $cmsManager = $this->container->get('cms.cmsManager');
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             // Найти проект
             $query = $em->createQuery('SELECT p.id, p.createrId, p.isPublic, p.title, sp.url FROM ExtendedProjectBundle:Projects p '.
                                       'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.project\' AND sp.contentAction = \'view\' AND sp.contentId = p.id '.
                                       'WHERE p.id = :id AND p.enabled != 0')->setParameter('id', $id);
             $project = $query->getResult();
             if (isset($project[0]))
             {
                $errors = false;
                $result['status'] = '';
                $result['action'] = 'object.project.create';
                $result['actionId'] = $id;
                $result['title'] = '';
                $result['content'] = '';
                $result['titleError'] = '';
                $result['contentError'] = '';
                if (isset($postfields['title'])) $result['title'] = $postfields['title'];
                if (isset($postfields['content'])) $result['content'] = $postfields['content'];
                $premission = 0;
                // Проверить права на редактирование
                if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2)) 
                {
                     if ($cmsManager->getCurrentUser()->getId() == $project[0]['createrId']) $premission = 1; else
                     {
                         $query = $em->createQuery('SELECT count(pu.id) as usercount FROM ExtendedProjectBundle:ProjectUsers pu '.
                                                   'LEFT JOIN BasicCmsBundle:Users u WITH u.id = pu.userId '.
                                                   'WHERE pu.projectId = :projectid AND pu.userId = :userid AND u.blocked = 2 AND pu.readOnly = 0 AND pu.projectRole = 3')->setParameter('projectid', $id)->setParameter('userid', $cmsManager->getCurrentUser()->getId());
                         $users = $query->getResult();
                         if (isset($users[0]['usercount']) && ($users[0]['usercount'] > 0)) $premission = 1;
                         /*if ($project[0]['isPublic'] == 2) $premission = 1;*/
                     }
                }
                if ($premission == 0)
                {
                    $result['status'] = 'Access error';
                } else
                {
                    // Сохранение и результат
                    if (!preg_match("/^.{3,}$/ui", $result['title'])) {$errors = true; $result['titleError'] = 'Preg error';}
                    if ($errors == false)
                    {
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($result['content'], '');
                        $documentent = new \Extended\ProjectBundle\Entity\ProjectDocuments();
                        $documentent->setContent($result['content']);
                        $documentent->setCreateDate(new \DateTime('now'));
                        $documentent->setCreaterId($cmsManager->getCurrentUser()->getId());
                        $documentent->setEnabled(1);
                        $documentent->setIsPublic(1);
                        $documentent->setProjectId($id);
                        $documentent->setReadOnly(0);
                        $documentent->setTitle($result['title']);
                        $documentent->setParentId(0);
                        $em->persist($documentent);
                        $em->flush();
                        $documentent->setParentId($documentent->getId());
                        $em->flush();
                        $result['status'] = 'OK';
                        // Запись в лог
                        if ($this->container->has('addone.user.alert'))
                        {
                            $this->container->get('addone.user.alert')->bindAlert('project_'.$documentent->getProjectId(), array(
                                    'object' => 'project', 
                                    'type' => 'create', 
                                    'id' => $documentent->getId(), 
                                    'title' => $documentent->getTitle().' (версия от '.$documentent->getCreateDate()->format('d.m.Y H:i').' '.$cmsManager->getCurrentUser()->getFullName().')', 
                                    'projectTitle' => $project[0]['title'],
                                    'url' => '<a href="http://'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost().'/'.$project[0]['url'].'?document='.$documentent->getId().'&version='.$documentent->getId().'">'.$documentent->getTitle().' (версия от '.$documentent->getCreateDate()->format('d.m.Y H:i').' '.$cmsManager->getCurrentUser()->getFullName().')'.'</a>'
                                ));
                        }
                        $this->container->get('cms.cmsManager')->logPushFront(0, 'create', 'Создание документа к мастерской "'.$result['title'].'"', $this->container->get('router')->generate('extended_project_documents_edit').'?id='.$documentent->getId());
                        if ($this->container->has('object.knowledge')) {
                            $knowledgeTitle = (isset($postfields['knowledgetitle']) ? $postfields['knowledgetitle'] : null);
                            $knowledgeTypes = (isset($postfields['knowledgetypes']) ? $postfields['knowledgetypes'] : null);
                            $this->container->get('object.knowledge')->addKnowledgeObject(
                                \Extended\KnowledgeBundle\Entity\KnowledgeObject::KO_TYPE_DOCUMENT, 
                                $documentent->getId(), 
                                null, 
                                $knowledgeTitle, 
                                $knowledgeTypes
                            );
                        }
                    } else $result['status'] = 'Check error';
                }
             }
         }
         if ($action == 'request')
         {
             // Проверить на правильность данные
             $em = $this->container->get('doctrine')->getEntityManager();
             if (($this->container->get('cms.cmsManager')->getCurrentUser() != null) && ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() == 2))
             {
                 $userent = $this->container->get('cms.cmsManager')->getCurrentUser();
                 $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
                 $result['action'] = 'object.project.request';
                 $result['errors'] = false;
                 $result['project']['value'] = (isset($postfields['project']) ? intval($postfields['project']) : -1);
                 $result['project']['error'] = '';
                 $result['role']['value'] = (isset($postfields['role']) ? intval($postfields['role']) : -1);
                 $result['role']['error'] = '';
                 $result['message']['value'] = (isset($postfields['message']) ? trim($postfields['message']) : '');
                 $result['message']['error'] = '';
                 $query = $em->createQuery('SELECT count(p.id) as projectcount FROM ExtendedProjectBundle:Projects p '.
                                           'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.userId = :userid AND pu.projectId = p.id '.
                                           'WHERE p.enabled != 0 AND p.isUnvisible = 0 AND pu.id IS NULL AND p.id = :id')->setParameter('userid', $this->container->get('cms.cmsManager')->getCurrentUser()->getId())->setParameter('id', $result['project']['value']);
                 if ($query->getSingleScalarResult() != 1) {$result['project']['error'] = 'not found'; $result['errors'] = true;}
                 if (($result['role']['value'] < 0) || ($result['role']['value'] > 3)) {$result['role']['error'] = 'not found'; $result['errors'] = true;}
                 if (!preg_match("/^[\S\s]{3,}$/ui", $result['message']['value'])) {$result['errors'] = true; $result['message']['error'] = 'preg';}
                 if ($result['errors'] == false)
                 {
                     $requestent = new \Extended\ProjectBundle\Entity\ProjectsRequests();
                     $requestent->setMessage($result['message']['value']);
                     $requestent->setProjectRole($result['role']['value']);
                     $requestent->setUserId($this->container->get('cms.cmsManager')->getCurrentUser()->getId());
                     $requestent->setProjectId($result['project']['value']);
                     $em->persist($requestent);
                     $em->flush();
                     $result['request'] = $requestent;
                     $result['status'] = 'OK';
                     // Запись в лог
                     $this->container->get('cms.cmsManager')->logPushFront($requestent->getId(), $this->getName().'.other', 'Запрос на добавление к мастерской от пользователя "'.$this->container->get('cms.cmsManager')->getCurrentUser()->getLogin().'"', $this->container->get('router')->generate('extended_project_edit').'?id='.$result['project']['value']);
                 } else $result['status'] = 'error';
             }
         }
         if ($action == 'moderaterequest')
         {
             $actionid = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $requestent = $this->container->get('doctrine')->getRepository('ExtendedProjectBundle:ProjectsRequests')->find($actionid);
             // Проверить на правильность данные
             $em = $this->container->get('doctrine')->getEntityManager();
             if (($this->container->get('cms.cmsManager')->getCurrentUser() != null) && ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() == 2) && (!empty($requestent)))
             {
                 // Проверить права пользователя на мастера проекта
                 $query = $em->createQuery('SELECT p.id, p.createrId, pu.projectRole FROM ExtendedProjectBundle:Projects p '.
                                           'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.userId = :userid AND pu.projectId = p.id '.
                                           'LEFT JOIN ExtendedProjectBundle:ProjectUsers pru WITH pru.userId = :reqid AND pru.projectId = p.id '.
                                           'WHERE p.id = :projectid AND p.enabled != 0 AND pru.id IS NULL')->setParameter('projectid', $requestent->getProjectId())->setParameter('reqid', $requestent->getUserId())->setParameter('userid', $this->container->get('cms.cmsManager')->getCurrentUser()->getId());
                 $projectcheck = $query->getResult();
                 $premission = 0;
                 if (isset($projectcheck[0]))
                 {
                     if ($projectcheck[0]['createrId'] == $this->container->get('cms.cmsManager')->getCurrentUser()->getId()) $premission = 1;
                     if ($projectcheck[0]['projectRole'] == 3) $premission = 1;
                 }
                 if ($premission != 0)
                 {
                     $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
                     $result['action'] = 'object.project.moderaterequest';
                     $result['actionId'] = $requestent->getId();
                     $result['role'] = (isset($postfields['role']) ? intval($postfields['role']) : $requestent->getProjectRole());
                     $result['accept'] = (isset($postfields['accept']) ? intval($postfields['accept']) : 0);
                     $result['readOnly'] = (isset($postfields['readOnly']) ? intval($postfields['readOnly']) : 1);
                     if ($result['accept'] != 0)
                     {
                         $projuserent = new \Extended\ProjectBundle\Entity\ProjectUsers();
                         $projuserent->setProjectId($requestent->getProjectId());
                         $projuserent->setProjectRole($result['role']);
                         $projuserent->setReadOnly($result['readOnly']);
                         $projuserent->setUserId($requestent->getUserId());
                         $em->persist($projuserent);
                         $em->flush();
                     }
                     $em->remove($requestent);
                     $em->flush();
                     $result['status'] = 'OK';
                 }
             }
         }
         if ($action == 'moderaterole')
         {
             $actionid = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $actionuserid = $this->container->get('request_stack')->getCurrentRequest()->get('actionuserid');
             $userent = $this->container->get('doctrine')->getRepository('ExtendedProjectBundle:ProjectUsers')->findOneBy(array('projectId' => $actionid, 'userId' => $actionuserid));
             // Проверить на правильность данные
             $em = $this->container->get('doctrine')->getEntityManager();
             if (($this->container->get('cms.cmsManager')->getCurrentUser() != null) && ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() == 2) && (!empty($userent)))
             {
                 // Проверить права пользователя на мастера проекта
                 $query = $em->createQuery('SELECT p.id, p.createrId, pu.projectRole FROM ExtendedProjectBundle:Projects p '.
                                           'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.userId = :userid AND pu.projectId = p.id '.
                                           'WHERE p.id = :projectid AND p.enabled != 0')->setParameter('projectid', $actionid)->setParameter('userid', $this->container->get('cms.cmsManager')->getCurrentUser()->getId());
                 $projectcheck = $query->getResult();
                 $premission = 0;
                 if (isset($projectcheck[0]))
                 {
                     if ($projectcheck[0]['createrId'] == $this->container->get('cms.cmsManager')->getCurrentUser()->getId()) $premission = 1;
                     if ($projectcheck[0]['projectRole'] == 3) $premission = 1;
                 }
                 if ($premission != 0)
                 {
                     $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
                     $result['action'] = 'object.project.moderaterole';
                     $result['actionId'] = $actionid;
                     $result['actionUserId'] = $actionuserid;
                     $result['role'] = (isset($postfields['role']) ? intval($postfields['role']) : 0);
                     $result['remove'] = (isset($postfields['remove']) ? intval($postfields['remove']) : 0);
                     $result['readOnly'] = (isset($postfields['readOnly']) ? intval($postfields['readOnly']) : 1);
                     if ($result['remove'] != 0)
                     {
                         $em->remove($userent);
                         $em->flush();
                     } else 
                     {
                         $userent->setProjectRole($result['role']);
                         $userent->setReadOnly($result['readOnly']);
                         $em->flush();
                     }
                     $result['status'] = 'OK';
                 }
             }
         }
         if ($action == 'lockproject')
         {
             $actionid = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $actionstate = intval($this->container->get('request_stack')->getCurrentRequest()->get('actionstate'));
             // Проверить на правильность данные
             $em = $this->container->get('doctrine')->getEntityManager();
             if (($this->container->get('cms.cmsManager')->getCurrentUser() != null) && ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() == 2))
             {
                 // Проверить права пользователя на мастера проекта
                 $query = $em->createQuery('SELECT p.id, p.createrId, pu.projectRole FROM ExtendedProjectBundle:Projects p '.
                                           'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.userId = :userid AND pu.projectId = p.id '.
                                           'WHERE p.id = :projectid AND p.enabled != 0')->setParameter('projectid', $actionid)->setParameter('userid', $this->container->get('cms.cmsManager')->getCurrentUser()->getId());
                 $projectcheck = $query->getResult();
                 $premission = 0;
                 if (isset($projectcheck[0]))
                 {
                     if ($projectcheck[0]['createrId'] == $this->container->get('cms.cmsManager')->getCurrentUser()->getId()) $premission = 1;
                     if ($projectcheck[0]['projectRole'] == 3) $premission = 1;
                 }
                 if ($premission != 0)
                 {
                     $projectent = $this->container->get('doctrine')->getRepository('ExtendedProjectBundle:Projects')->find($actionid);
                     $projectent->setIsLocked($actionstate);
                     $em->flush();
                     $result['action'] = 'object.project.lockproject';
                     $result['actionId'] = $projectent->getId();
                     $result['isLocked'] = $actionstate;
                     $result['status'] = 'OK';
                 }
             }
         }
         if ($action == 'remove')
         {
             $actionid = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             // Проверить на правильность данные
             $em = $this->container->get('doctrine')->getEntityManager();
             if (($this->container->get('cms.cmsManager')->getCurrentUser() != null) && ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() == 2))
             {
                 // Проверить права пользователя на мастера проекта
                 $query = $em->createQuery('SELECT p.id, p.createrId, pu.projectRole FROM ExtendedProjectBundle:ProjectDocuments d '.
                                           'LEFT JOIN ExtendedProjectBundle:Projects p WITH p.id = d.projectId '.
                                           'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.userId = :userid AND pu.projectId = p.id '.
                                           'WHERE d.id = :id AND p.enabled != 0')->setParameter('id', $actionid)->setParameter('userid', $this->container->get('cms.cmsManager')->getCurrentUser()->getId());
                 $projectcheck = $query->getResult();
                 $premission = 0;
                 if (isset($projectcheck[0]))
                 {
                     if ($projectcheck[0]['createrId'] == $this->container->get('cms.cmsManager')->getCurrentUser()->getId()) $premission = 1;
                     if ($projectcheck[0]['projectRole'] == 3) $premission = 1;
                 }
                 if ($premission != 0)
                 {
                     $restoreid = null;
                     $documentent = $this->container->get('doctrine')->getRepository('ExtendedProjectBundle:ProjectDocuments')->find($actionid);
                     if (!empty($documentent))
                     {
                         if ($documentent->getId() == $documentent->getParentId())
                         {
                             $restoreid = $documentent->getId();
                         }
                         //$this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $documentent->getContent());
                         $this->removeDocumentImages($documentent->getId(), $documentent->getContent());
                         $em->remove($documentent);
                         $em->flush();
                         unset($documentent); 
                         if ($restoreid !== null)
                         {
                             try 
                             {
                                 $updateid = $em->createQuery('SELECT d.id FROM ExtendedProjectBundle:ProjectDocuments d WHERE d.parentId = :id ORDER BY d.id ASC')->setMaxResults(1)->setParameter('id', $restoreid)->getSingleScalarResult();
                                 $em->createQuery('UPDATE ExtendedProjectBundle:ProjectDocuments d SET d.parentId = :newid WHERE d.parentId = :id')->setParameter('id', $restoreid)->setParameter('newid', $updateid)->execute();
                             } catch (\Doctrine\ORM\NoResultException $e)
                             {
                             }
                         }
                         $locdocents = $em->getRepository('ExtendedProjectBundle:ProjectDocumentsLocale')->findBy(array('documentId' => $actionid));
                         foreach ($locdocents as $locdocent) {
                            $this->removeDocumentImages($locdocent->getDocumentId(), $locdocent->getContent());
                            $em->remove($locdocent);
                            $em->flush();
                         }
                     }
                     $result['action'] = 'object.project.remove';
                     $result['actionId'] = $actionid;
                     $result['status'] = 'OK';
                 }
             }
         }
         if ($action == 'changepublic')
         {
             $actionid = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $actionvalue = $this->container->get('request_stack')->getCurrentRequest()->get('actionvalue');
             // Проверить на правильность данные
             $em = $this->container->get('doctrine')->getEntityManager();
             if (($this->container->get('cms.cmsManager')->getCurrentUser() != null) && ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() == 2))
             {
                 // Проверить права пользователя на мастера проекта
                 $query = $em->createQuery('SELECT p.id, p.createrId, pu.projectRole FROM ExtendedProjectBundle:ProjectDocuments d '.
                                           'LEFT JOIN ExtendedProjectBundle:Projects p WITH p.id = d.projectId '.
                                           'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.userId = :userid AND pu.projectId = p.id '.
                                           'WHERE d.id = :id AND p.enabled != 0')->setParameter('id', $actionid)->setParameter('userid', $this->container->get('cms.cmsManager')->getCurrentUser()->getId());
                 $projectcheck = $query->getResult();
                 $premission = 0;
                 if (isset($projectcheck[0]))
                 {
                     if ($projectcheck[0]['createrId'] == $this->container->get('cms.cmsManager')->getCurrentUser()->getId()) $premission = 1;
                     if ($projectcheck[0]['projectRole'] == 3) $premission = 1;
                 }
                 if ($premission != 0)
                 {
                     $restoreid = null;
                     $documentent = $this->container->get('doctrine')->getRepository('ExtendedProjectBundle:ProjectDocuments')->find($actionid);
                     $em->createQuery('UPDATE ExtendedProjectBundle:ProjectDocuments d SET d.isPublic = :ispublic WHERE d.parentId = :parent')->setParameters(array(
                         'ispublic' => intval($actionvalue),
                         'parent' => $documentent->getParentId()
                     ))->execute();
                     $result['action'] = 'object.project.changepublic';
                     $result['actionId'] = $actionid;
                     $result['actionValue'] = $actionvalue;
                     $result['status'] = 'OK';
                 }
             }
         }
         if ($action == 'editproject')
         {
             $actionid = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $actionfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             // Проверить на правильность данные
             $em = $this->container->get('doctrine')->getEntityManager();
             if (($this->container->get('cms.cmsManager')->getCurrentUser() != null) && ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() == 2))
             {
                 // Проверить права пользователя на мастера проекта
                 $query = $em->createQuery('SELECT p.id, p.createrId, pu.projectRole FROM ExtendedProjectBundle:Projects p '.
                                           'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.userId = :userid AND pu.projectId = p.id '.
                                           'WHERE p.id = :projectid AND p.enabled != 0')->setParameter('projectid', $actionid)->setParameter('userid', $this->container->get('cms.cmsManager')->getCurrentUser()->getId());
                 $projectcheck = $query->getResult();
                 $premission = 0;
                 if (isset($projectcheck[0]))
                 {
                     if ($projectcheck[0]['createrId'] == $this->container->get('cms.cmsManager')->getCurrentUser()->getId()) $premission = 1;
                     if ($projectcheck[0]['projectRole'] == 3) $premission = 1;
                 }
                 if ($premission != 0)
                 {
                     $errors = false;
                     $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
                     $locales = $query->getResult();
                     if (!is_array($locales)) $locales = array();
                     $result['editTitle'] = array();
                     $result['editDescription'] = array();
                     $result['editContent'] = array();
                     $result['editTitle']['default'] = (isset($actionfields['title']['default']) ? $actionfields['title']['default'] : '');
                     $result['editDescription']['default'] = (isset($actionfields['description']['default']) ? $actionfields['description']['default'] : '');
                     $result['editContent']['default'] = (isset($actionfields['content']['default']) ? $actionfields['content']['default'] : '');
                     $result['editTitleError'] = array('default' => '');
                     $result['editDescriptionError'] = array('default' => '');
                     $result['editContentError'] = array('default' => '');
                     if (!preg_match("/^.{3,}$/ui", $result['editTitle']['default'])) {$errors = true; $result['editTitleError']['default'] = 'preg';}
                     foreach ($locales as $loc)
                     {
                        $result['editTitle'][$loc['shortName']] = (isset($actionfields['title'][$loc['shortName']]) ? $actionfields['title'][$loc['shortName']] : '');
                        $result['editDescription'][$loc['shortName']] = (isset($actionfields['description'][$loc['shortName']]) ? $actionfields['description'][$loc['shortName']] : '');
                        $result['editContent'][$loc['shortName']] = (isset($actionfields['content'][$loc['shortName']]) ? $actionfields['content'][$loc['shortName']] : '');
                        $result['editTitleError'][$loc['shortName']] = '';
                        $result['editDescriptionError'][$loc['shortName']] = '';
                        $result['editContentError'][$loc['shortName']] = '';
                     }
                     $projectent = $this->container->get('doctrine')->getRepository('ExtendedProjectBundle:Projects')->find($actionid);
                     if ($errors == false)
                     {
                         $this->container->get('cms.cmsManager')->unlockTemporaryEditor($result['editDescription']['default'], $projectent->getDescription());
                         $this->container->get('cms.cmsManager')->unlockTemporaryEditor($result['editContent']['default'], $projectent->getContent());
                         $projectent->setTitle($result['editTitle']['default']);
                         $projectent->setDescription($result['editDescription']['default']);
                         $projectent->setContent($result['editContent']['default']);
                         $projectent->setModifyDate(new \DateTime('now'));
                         $em->flush();
                         foreach ($locales as $loc)
                         {
                            $projlocent = $this->container->get('doctrine')->getRepository('ExtendedProjectBundle:ProjectsLocale')->findOneBy(array('locale'=>$loc['shortName'], 'projectId'=>$actionid));
                            if (($result['editTitle'][$loc['shortName']] != '') ||
                                ($result['editDescription'][$loc['shortName']] != '') ||
                                ($result['editContent'][$loc['shortName']] != ''))
                            {
                                if (empty($projlocent))
                                {
                                    $projlocent = new \Extended\ProjectBundle\Entity\ProjectsLocale();
                                    $em->persist($projlocent);
                                }
                                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($result['editContent'][$loc['shortName']], $projlocent->getContent());
                                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($result['editDescription'][$loc['shortName']], $projlocent->getDescription());
                                $projlocent->setProjectId($projectent->getId());
                                $projlocent->setLocale($loc['shortName']);
                                if ($result['editTitle'][$loc['shortName']] == '') $projlocent->setTitle(null); else $projlocent->setTitle($result['editTitle'][$loc['shortName']]);
                                if ($result['editDescription'][$loc['shortName']] == '') $projlocent->setDescription(null); else $projlocent->setDescription($result['editDescription'][$loc['shortName']]);
                                if ($result['editContent'][$loc['shortName']] == '') $projlocent->setContent(null); else $projlocent->setContent($result['editContent'][$loc['shortName']]);
                                $em->flush();
                                unset($projlocent);
                            } else
                            {
                                if (!empty($projlocent))
                                {
                                    $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $projlocent->getContent());
                                    $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $projlocent->getDescription());
                                    $em->remove($projlocent);
                                    $em->flush();
                                }
                            }
                            unset($projlocent);
                         }
                         $result['status'] = 'OK';
                     } else 
                     {
                         $result['status'] = 'error';
                     }
                     $result['action'] = 'object.project.editproject';
                     $result['actionId'] = $projectent->getId();
                 }
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) $this->container->get($item)->setFrontAction($locale, $seoPage, $action, $result);
         return $result;
     }
     
     public function getModuleTypes()
     {
         $contents = array('projectRequest' => 'Модуль запроса на добавление к мастерским');
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) $this->container->get($item)->getModuleTypes($contents);
         return $contents;
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result = null)
     {
         $params = null;
         if ($moduleType == 'projectRequest')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $params = array();
             $params['enabled'] = 0;
             $currentUser = $this->container->get('cms.cmsManager')->getCurrentUser();
             if (($currentUser != null) && ($currentUser->getBlocked() == 2)) $params['enabled'] = 1;
             if ($params['enabled'] != 0)
             {
                 $query = $em->createQuery('SELECT p.id, IF(pl.title is not null, pl.title, p.title) as title FROM ExtendedProjectBundle:Projects p '.
                                           'LEFT JOIN ExtendedProjectBundle:ProjectUsers pu WITH pu.userId = :userid AND pu.projectId = p.id '.
                                           'LEFT JOIN ExtendedProjectBundle:ProjectsLocale pl WITH pl.projectId = p.id AND pl.locale = :locale '.
                                           'WHERE p.enabled != 0 AND p.isUnvisible = 0 AND pu.id IS NULL')->setParameter('userid', $currentUser->getId())->setParameter('locale', $locale);
                 $params['projects'] = $query->getResult();
                 if (!is_array($params['projects']) || (count($params['projects']) == 0)) $params['enabled'] = 0; else
                 {
                     $params['project'] = null;
                     if (($seoPage->getContentType() == 'object.project') && ($seoPage->getContentAction() == 'view')) $params['project'] = $seoPage->getContentId();
                     $params['projectError'] = '';
                     $params['message'] = '';
                     $params['messageError'] = '';
                     $params['projectRole'] = 0;
                     $params['projectRoleError'] = '';
                     $params['status'] = '';
                     // Приём данных из экшена
                     if (isset($result['action']) && ($result['action'] == 'object.project.request'))
                     {
                         if ($result['status'] != 'OK') 
                         {
                             if (isset($result['project']['value'])) $params['project'] = $result['project']['value'];
                             if (isset($result['project']['error'])) $params['projectError'] = $result['project']['error'];
                             if (isset($result['role']['value'])) $params['projectRole'] = $result['role']['value'];
                             if (isset($result['role']['error'])) $params['projectRoleError'] = $result['role']['error'];
                             if (isset($result['message']['value'])) $params['message'] = $result['message']['value'];
                             if (isset($result['message']['error'])) $params['messageError'] = $result['message']['error'];
                         } else $params['status'] = 'OK';
                     }
                     
                     
                     
                 }
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             if (strpos($item,'addone.project.') === 0) $params = $this->container->get($item)->getFrontModule($locale, $seoPage, $moduleType, $parameters, $result);
             if ($params != null) return $params;
         }
         return null;
     }
     
    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        if ($module == 'projectRequest')
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
                return $this->container->get('templating')->render('ExtendedProjectBundle:Modules:request.html.twig',array());
            }
        }
        $answer = null;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.project.') === 0) $answer = $this->container->get($item)->getAdminModuleController($request, $module, $actionType, $nullparameters);
            if ($answer != null) return $answer;
        }
        return null;
    }

    public function getInfoTitle($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT p.title FROM ExtendedProjectBundle:Projects p WHERE p.id = :id')->setParameter('id', $contentId);
             $project = $query->getResult();
             if (isset($project[0]['title']))
             {
                 $title = array();
                 $title['default'] = $project[0]['title'];
                 $query = $em->createQuery('SELECT pl.title, pl.locale FROM ExtendedProjectBundle:ProjectsLocale pl WHERE pl.projectId = :id')->setParameter('id', $contentId);
                 $projectlocale = $query->getResult();
                 if (is_array($projectlocale))
                 {
                     foreach ($projectlocale as $pitem) if ($pitem['title'] != null) $title[$pitem['locale']] = $pitem['title'];
                 }
                 return $this->container->get('cms.cmsManager')->encodeLocalString($title);
             }
         }
         if ($contentType == 'edit')
         {
             $title = array();
             $title['default'] = 'Редактирование мастерской';
             $title['en'] = 'Editing workshop';
             return $this->container->get('cms.cmsManager')->encodeLocalString($title);
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.project.') === 0) $answer = $this->container->get($item)->getInfoTitle($contentType, $contentId);
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
             if (strpos($item,'addone.project.') === 0) $answer = $this->container->get($item)->getInfoSitemap($contentType, $contentId);
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
        $params['viewCount'] = array('type'=>'int','ext'=>0,'name'=>'Кол-во просмотров');
        $params['title'] = array('type'=>'text','ext'=>0,'name'=>'Заголовок');
        $params['description'] = array('type'=>'text','ext'=>0,'name'=>'Краткое описание');
        $params['content'] = array('type'=>'text','ext'=>0,'name'=>'Полное описание');
        $params['createrLogin'] = array('type'=>'text','ext'=>1,'name'=>'Логин автора');
        $params['createrFullName'] = array('type'=>'text','ext'=>1,'name'=>'Имя автора');
        $params['createrAvatar'] = array('type'=>'text','ext'=>1,'name'=>'Аватар автора');
        $params['createrEmail'] = array('type'=>'text','ext'=>1,'name'=>'E-mail автора');
        $params['url'] = array('type'=>'text','ext'=>1,'name'=>'URL страницы');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) $this->container->get($item)->getTaxonomyParameters($params);
        return $params;
    }
    
    public function getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $queryCount = false, $itemIds = null)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        $allparameters = array($pageParameters['sortField']);
        $queryparameters = array();
        foreach ($pageParameters['fields'] as $field) $allparameters[] = $field;
        foreach ($filterParameters as $field) $allparameters[] = $field['parameter'];
        $from = ' FROM ExtendedProjectBundle:Projects p';
        if (in_array('title', $allparameters) || 
            in_array('description', $allparameters) || 
            in_array('content', $allparameters)) {$from .= ' LEFT JOIN ExtendedProjectBundle:ProjectsLocale pl WITH pl.projectId = p.id AND pl.locale = :locale'; $queryparameters['locale'] = $locale;}
        if (in_array('createrLogin', $allparameters) || 
            in_array('createrFullName', $allparameters) ||    
            in_array('createrAvatar', $allparameters) ||    
            in_array('createrEmail', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId';}
        if (in_array('url', $allparameters)) {$from .= ' LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.project\' AND sp.contentAction = \'view\' AND sp.contentId = p.id';}
        $select = 'SELECT p.id';
        if (in_array('createDate', $pageParameters['fields'])) $select .=',p.createDate';
        if (in_array('modifyDate', $pageParameters['fields'])) $select .=',p.modifyDate';
        if (in_array('createrId', $pageParameters['fields'])) $select .=',p.createrId';
        if (in_array('viewCount', $pageParameters['fields'])) $select .=',p.viewCount';
        if (in_array('title', $pageParameters['fields']) || in_array('title', $allparameters)) $select .=',IF(pl.title is not null, pl.title, p.title) as title';
        if (in_array('description', $pageParameters['fields']) || in_array('description', $allparameters)) $select .=',IF(pl.description is not null, pl.description, p.description) as description';
        if (in_array('content', $pageParameters['fields']) || in_array('content', $allparameters)) $select .=',IF(pl.content is not null, pl.content, p.content) as content';
        if (in_array('createrLogin', $pageParameters['fields'])) $select .=',u.login as createrLogin';
        if (in_array('createrFullName', $pageParameters['fields'])) $select .=',u.fullName as createrFullName';
        if (in_array('createrAvatar', $pageParameters['fields'])) $select .=',u.avatar as createrAvatar';
        if (in_array('createrEmail', $pageParameters['fields'])) $select .=',u.email as createrEmail';
        if (in_array('url', $pageParameters['fields'])) $select .=',sp.url';
        $where = ' WHERE p.enabled != 0';
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
                    if ($field['compare'] == 'noeq') {$where .= ' AND p.createDate != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND p.createDate = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND p.createDate > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND p.createDate < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND p.createDate >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND p.createDate <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
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
                    if ($field['compare'] == 'noeq') {$where .= ' AND p.modifyDate != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND p.modifyDate = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND p.modifyDate > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND p.modifyDate < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND p.modifyDate >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND p.modifyDate <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $date; $queryparameterscount++;}
                }
                unset($date);
            }
            if ($field['parameter'] == 'createrId')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND p.createrId != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND p.createrId = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND p.createrId > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND p.createrId < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND p.createrId >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND p.createrId <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'viewCount')
            {
                $val = null;
                if (preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $field['value'])) $val = floatval($field['value']);
                if ($val != null)
                {
                    if ($field['compare'] == 'noeq') {$where .= ' AND p.viewCount != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'eq') {$where .= ' AND p.viewCount = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hi') {$where .= ' AND p.viewCount > :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'lo') {$where .= ' AND p.viewCount < :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'hieq') {$where .= ' AND p.viewCount >= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                    if ($field['compare'] == 'loeq') {$where .= ' AND p.viewCount <= :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $val; $queryparameterscount++;}
                }
                unset($val);
            }
            if ($field['parameter'] == 'title')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND IF(pl.title is not null, pl.title, p.title) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND IF(pl.title is not null, pl.title, p.title) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND IF(pl.title is not null, pl.title, p.title) like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'description')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND IF(pl.description is not null, pl.description, p.description) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND IF(pl.description is not null, pl.description, p.description) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND IF(pl.description is not null, pl.description, p.description) like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
            }
            if ($field['parameter'] == 'content')
            {
                if ($field['compare'] == 'noeq') {$where .= ' AND IF(pl.content is not null, pl.content, p.content) != :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'eq') {$where .= ' AND IF(pl.content is not null, pl.content, p.content) = :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = $field['value']; $queryparameterscount++;}
                if ($field['compare'] == 'like') {$where .= ' AND IF(pl.content is not null, pl.content, p.content) like :value'.$queryparameterscount; $queryparameters['value'.$queryparameterscount] = '%'.$field['value'].'%'; $queryparameterscount++;}
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
            $from .= ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks l WITH l.itemId = p.id AND l.taxonomyId IN (:categoryids)';
            $where .= ' AND l.id IS NOT NULL';
            $queryparameters['categoryids'] = $categoryIds;
        }
        if (($itemIds !== null) && (is_array($itemIds)))
        {
            if (count($itemIds) == 0) $itemIds[] = 0;
            $where .= ' AND p.id IN (:itemids)';
            $queryparameters['itemids'] = $itemIds;
        }
        // Добавление обработки невидимости проектов
        /*$cmsManager = $this->container->get('cms.cmsManager');
        if (($cmsManager->getCurrentUser() != null) && ($cmsManager->getCurrentUser()->getBlocked() == 2)) 
        {
            $from .= ' LEFT JOIN ExtendedProjectBundle:ProjectUsers culwp WITH culwp.projectId = p.id AND culwp.userId = :currentuserid';
            $where .= ' AND (p.isUnvisible = 0 OR p.createrId = :currentuserid OR culwp.id IS NOT NULL)';
            $queryparameters['currentuserid'] = $cmsManager->getCurrentUser()->getId();
        } else
        {
            $where .= ' AND p.isUnvisible = 0';
        }*/
        $order = ' ORDER BY p.id ASC';
        if ($pageParameters['sortField'] == 'createDate') $order = ' ORDER BY p.createDate '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'modifyDate') $order = ' ORDER BY p.modifyDate '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrId') $order = ' ORDER BY p.createrId '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'viewCount') $order = ' ORDER BY p.viewCount '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'title') $order = ' ORDER BY title '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'description') $order = ' ORDER BY description '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'content') $order = ' ORDER BY content '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrLogin') $order = ' ORDER BY u.login '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrFullName') $order = ' ORDER BY u.fullName '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrAvatar') $order = ' ORDER BY u.avatar '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'createrEmail') $order = ' ORDER BY u.email '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        if ($pageParameters['sortField'] == 'url') $order = ' ORDER BY sp.url '.($pageParameters['sortDirection'] == 0 ? 'ASC' : 'DESC');
        // Обход плагинов
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) $this->container->get($item)->getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $itemIds, $allparameters, $queryparameters, $queryparameterscount, $select, $from, $where, $order);
        // Создание запроса
        if ($queryCount == true)    
        {
            $query = $em->createQuery('SELECT count(DISTINCT p.id) as itemcount'.$from.$where)->setParameters($queryparameters);
        } else
        {
            $query = $em->createQuery($select.$from.$where.' GROUP BY p.id'.$order)->setParameters($queryparameters);
        }
        return $query;
    }
    
    public function getTaxonomyPostProcess($locale, &$params)
    {
        foreach ($params['items'] as &$textpageitem) 
        {
            if (isset($textpageitem['url']) && ($textpageitem['url'] != '')) $textpageitem['url'] = '/'.($locale != '' ? $locale.'/' : '').$textpageitem['url'];
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) $this->container->get($item)->getTaxonomyPostProcess($locale, $params);
    }
    
    public function getTaxonomyExtCatInfo(&$tree)
    {
        $categoryIds = array();
        foreach ($tree as $cat) $categoryIds[] = $cat['id'];
        $em = $this->container->get('doctrine')->getEntityManager();
        if (count($categoryIds) > 0)
        {
            $query = $em->createQuery('SELECT tl.taxonomyId, count(p.id) as projectCount, max(p.modifyDate) as modifyDate FROM BasicCmsBundle:TaxonomiesLinks tl '.
                                      'LEFT JOIN ExtendedProjectBundle:Projects p WITH p.id = tl.itemId '.
                                      'WHERE tl.taxonomyId IN (:taxonomyids) AND p.enabled != 0 GROUP BY tl.taxonomyId')->setParameter('taxonomyids', $categoryIds);
            $extInfo = $query->getResult();
            $nulltime = new \DateTime('now');
            $nulltime->setTimestamp(0);
            foreach ($tree as &$cat) 
            {
                $cat['projectCount'] = 0;
                $cat['projectMofidyDate'] = $nulltime;
                foreach ($extInfo as $info)
                    if ($info['taxonomyId'] == $cat['id'])
                    {
                        $cat['projectCount'] = $info['projectCount'];
                        $cat['projectMofidyDate'] = $info['modifyDate'];
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
        $options['object.project.on'] = 'Искать информацию в мастерских';
        $options['object.project.onlypage'] = 'Искать информацию в мастерских только со страницей сайта';
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.project.') === 0) $this->container->get($item)->getSearchOptions($options);
        }
    }
    
    public function getSearchItemCount($options, $searchstring, $locale)
    {
        $count = 0;
        if (isset($options['object.project.on']) && ($options['object.project.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT count(p.id) as projectcount FROM ExtendedProjectBundle:Projects p '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.project\' AND sp.contentAction = \'view\' AND sp.contentId = p.id '.
                                      'WHERE (p.title LIKE :searchstring OR p.description LIKE :searchstring OR p.content LIKE :searchstring)'.
                                      ((isset($options['object.project.onlypage']) && ($options['object.project.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : ''))->setParameter('searchstring', $searchstring);
            $projectcount = $query->getResult();
            if (isset($projectcount[0]['projectcount'])) $count += $projectcount[0]['projectcount'];
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.project.') === 0) $count += $this->container->get($item)->getSearchItemCount($options, $searchstring, $locale);
        }
        return $count;
    }
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, &$items)
    {
        if (isset($options['object.project.on']) && ($options['object.project.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT \'project\' as object, p.id, p.title, p.description, '.
                                      'p.modifyDate, \'\' as avatar, p.createrId, u.avatar as createrAvatar, u.login as createrLogin, u.fullName as createrFullName, sp.url FROM ExtendedProjectBundle:Projects p '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.project\' AND sp.contentAction = \'view\' AND sp.contentId = p.id '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.
                                      'WHERE (p.title LIKE :searchstring OR p.description LIKE :searchstring OR p.content LIKE :searchstring)'.
                                      ((isset($options['object.project.onlypage']) && ($options['object.project.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : '').
                                      ($sortdir == 0 ? ' ORDER BY p.modifyDate ASC' : ' ORDER BY p.modifyDate DESC'))->setParameter('searchstring', $searchstring)->setFirstResult($start)->setMaxResults($limit);
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
            if (strpos($item,'addone.project.') === 0) $this->container->get($item)->getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, $items);
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
            if (strpos($item,'addone.project.') === 0) $this->container->get($item)->getHelpChapters($chapters);
        }
        return $chapters;
    }
    
    public function getHelpContent($page, &$answer)
    {
        //if ($page == 'd1537bd0b66a4289001629ac2e06e742') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:index.html.twig',array());
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.project.') === 0) $answer .= $this->container->get($item)->getHelpContent($page);
        }
        return $answer;
    }

//********************************************
// Обработка событий
//********************************************
    
    public function getAlertList($locale, $isAdmin) 
    {
        $cmsManager = $this->container->get('cms.cmsManager');
        $em = $this->container->get('doctrine')->getEntityManager();
        if ($isAdmin == false)
        {
            if (($cmsManager->getCurrentUser() == null) || ($cmsManager->getCurrentUser()->getBlocked() != 2)) 
            {
                return array();
            }
            $query = $em->createQuery('SELECT p.id, IF(pl.title is not null, pl.title, p.title) as title FROM ExtendedProjectBundle:Projects p '.
                                      'LEFT JOIN ExtendedProjectBundle:ProjectsLocale pl WITH pl.projectId = p.id AND pl.locale = :locale '.
                                      'LEFT JOIN ExtendedProjectBundle:ProjectUsers culwp WITH culwp.projectId = p.id AND culwp.userId = :currentuserid '.
                                      'WHERE (p.enabled != 0) AND (p.isUnvisible = 0 OR p.createrId = :currentuserid OR culwp.id IS NOT NULL)')->setParameter('locale', $locale)->setParameter('currentuserid', $cmsManager->getCurrentUser()->getId());
        } else 
        {
            $query = $em->createQuery('SELECT p.id, p.title FROM ExtendedProjectBundle:Projects p '.
                                      'WHERE p.enabled != 0');
        }
        $result = $query->getResult();
        $alerts = array();
        foreach ($result as $item)
        {
            $alerts['project_'.$item['id']] = ($locale != 'en' ? 'Изменения в мастерской ' : 'Changes in workshop ').'&laquo;'.$item['title'].'&raquo;';
        }
        return $alerts;
    }
    
//********************************************
// Удаление неиспользуемых картинок при удалении документа
//********************************************
    public function removeDocumentImages($documentId, $textold)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        preg_match_all('/<[iI][mM][gG]\s+([^>]*)/ui', $textold, $matches);
        preg_match_all('/[sS][rR][cC]=["\']([^"\']*)/ui', implode(' ', $matches[1]), $matches);
        $oldfiles = $matches[1];
        foreach ($oldfiles as $file) if (strpos($file, '/images/editor/') === 0) 
        {
            $docCount = $em->createQuery('SELECT count(d.id) FROM ExtendedProjectBundle:ProjectDocuments d '.
                                         'WHERE d.id != :id AND d.content LIKE :file')->setParameter('id', $documentId)->setParameter('file', '%'.$file.'%')->getSingleScalarResult();
            $locCount = $em->createQuery('SELECT count(d.id) FROM ExtendedProjectBundle:ProjectDocumentsLocale d '.
                                         'WHERE d.documentId != :id AND d.content LIKE :file')->setParameter('id', $documentId)->setParameter('file', '%'.$file.'%')->getSingleScalarResult();
            if (($docCount == 0) && ($locCount == 0))
            {
                @unlink('.'.$file);
            }
        }
    }
    
}
