<?php

namespace Extended\ProjectBundle\Controller;

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
// Список мастерских
// *******************************************    
    public function projectListAction()
    {
        if ($this->getUser()->checkAccess('project_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Мастерские',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $tab = $this->get('request_stack')->getMasterRequest()->get('tab');
        if ($tab === null) $tab = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_list_tab');
                      else $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_list_tab', $tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 0) $tab = 0;
        // Таб 1
        $page0 = $this->get('request_stack')->getMasterRequest()->get('page0');
        $sort0 = $this->get('request_stack')->getMasterRequest()->get('sort0');
        $search0 = $this->get('request_stack')->getMasterRequest()->get('search0');
        $taxonomy0 = $this->get('request_stack')->getMasterRequest()->get('taxonomy0');
        if ($page0 === null) $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_list_page0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_list_page0', $page0);
        if ($sort0 === null) $sort0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_list_sort0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_list_sort0', $sort0);
        if ($search0 === null) $search0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_list_search0');
                          else $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_list_search0', $search0);
        if ($taxonomy0 === null) $taxonomy0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_list_taxonomy0');
                          else $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_list_taxonomy0', $taxonomy0);
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        $taxonomy0 = intval($taxonomy0);
        // Поиск контента для 1 таба (текстовые страницы)
        $querytaxonomyleft0 = '';
        $querytaxonomywhere0 = '';
        if (($this->container->has('object.taxonomy')) && ($taxonomy0 != 0))
        {
            $querytaxonomyleft0 = ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks txl WITH txl.taxonomyId = '.$taxonomy0.' AND txl.itemId = p.id ';
            $querytaxonomywhere0 = ' AND txl.id IS NOT NULL ';
        }
        $query = $em->createQuery('SELECT count(p.id) as projectcount FROM ExtendedProjectBundle:Projects p '.$querytaxonomyleft0.
                                  'WHERE p.title like :search '.$querytaxonomywhere0)->setParameter('search', '%'.$search0.'%');
        $projectcount = $query->getResult();
        if (!empty($projectcount)) $projectcount = $projectcount[0]['projectcount']; else $projectcount = 0;
        $pagecount0 = ceil($projectcount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY p.title ASC';
        if ($sort0 == 1) $sortsql = 'ORDER BY p.title DESC';
        if ($sort0 == 2) $sortsql = 'ORDER BY p.enabled ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY p.enabled DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY p.createDate ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY p.createDate DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        if ($sort0 == 8) $sortsql = 'ORDER BY p.viewCount ASC';
        if ($sort0 == 9) $sortsql = 'ORDER BY p.viewCount DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT p.id, p.title, p.createDate, p.modifyDate, u.fullName, p.enabled, p.viewCount, (SELECT count(d.id) FROM ExtendedProjectBundle:ProjectDocuments d WHERE d.projectId = p.id) as documents FROM ExtendedProjectBundle:Projects p '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.$querytaxonomyleft0.
                                  'WHERE p.title like :search '.$querytaxonomywhere0.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $projects0 = $query->getResult();
        if ($this->container->has('object.taxonomy'))
        {
            $taxonomyenabled = 1;
            $actionIds = array();
            foreach ($projects0 as $project) $actionIds[] = $project['id'];
            $taxonomyinfo0 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.project', $actionIds);
        } else
        {
            $taxonomyenabled = 0;
            $taxonomyinfo0 = null;
        }
        return $this->render('ExtendedProjectBundle:Default:projectList.html.twig', array(
            'projects0' => $projects0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'pagecount0' => $pagecount0,
            'taxonomy0' => $taxonomy0,
            'taxonomyenabled' => $taxonomyenabled,
            'taxonomyinfo0' => $taxonomyinfo0,
            'tab' => $tab,
        ));
        
    }
    
// *******************************************
// Создание новой мастерской
// *******************************************    
    public function projectCreateAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_list_tab', 0);
        if ($this->getUser()->checkAccess('project_new') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой мастерской',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $project = array();
        $projecterror = array();
        
        $project['title'] = '';
        $project['description'] = '';
        $project['content'] = '';
        $project['enabled'] = 1;
        $project['viewCount'] = 0;
        $project['isPublic'] = 0;
        $project['isUnvisible'] = 0;
        $project['isLocked'] = 0;
        $projecterror['title'] = '';
        $projecterror['description'] = '';
        $projecterror['content'] = '';
        $projecterror['enabled'] = '';
        $projecterror['viewCount'] = '';
        $projecterror['isPublic'] = '';
        $projecterror['isUnvisible'] = '';
        $projecterror['isLocked'] = '';
        
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
        
        $users = array();
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.project','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.project.view');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];

        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        $projloc = array();
        $projlocerror = array();
        foreach ($locales as $locale)
        {
            $projloc[$locale['shortName']]['title'] = '';
            $projloc[$locale['shortName']]['description'] = '';
            $projloc[$locale['shortName']]['content'] = '';
            $projlocerror[$locale['shortName']]['title'] = '';
            $projlocerror[$locale['shortName']]['description'] = '';
            $projlocerror[$locale['shortName']]['content'] = '';
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postproject = $this->get('request_stack')->getMasterRequest()->get('project');
            if (isset($postproject['title'])) $project['title'] = $postproject['title'];
            if (isset($postproject['description'])) $project['description'] = $postproject['description'];
            if (isset($postproject['content'])) $project['content'] = $postproject['content'];
            if (isset($postproject['enabled'])) $project['enabled'] = intval($postproject['enabled']); else $project['enabled'] = 0;
            if (isset($postproject['viewCount'])) $project['viewCount'] = intval($postproject['viewCount']);
            if (isset($postproject['isPublic'])) $project['isPublic'] = intval($postproject['isPublic']); else $project['isPublic'] = 0;
            if (isset($postproject['isUnvisible'])) $project['isUnvisible'] = intval($postproject['isUnvisible']); else $project['isUnvisible'] = 0;
            if (isset($postproject['isLocked'])) $project['isLocked'] = intval($postproject['isLocked']); else $project['isLocked'] = 0;
            unset($postproject);
            if (!preg_match("/^.{3,}$/ui", $project['title'])) {$errors = true; $projecterror['title'] = 'Заголовок должен содержать более 3 символов';}
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
                    if (!preg_match("/^[a-z][a-z0-9\-]{1,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
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
            $postprojloc = $this->get('request_stack')->getMasterRequest()->get('projloc');
            foreach ($locales as $locale)
            {
                if (isset($postprojloc[$locale['shortName']]['title'])) $projloc[$locale['shortName']]['title'] = $postprojloc[$locale['shortName']]['title'];
                if (isset($postprojloc[$locale['shortName']]['description'])) $projloc[$locale['shortName']]['description'] = $postprojloc[$locale['shortName']]['description'];
                if (isset($postprojloc[$locale['shortName']]['content'])) $projloc[$locale['shortName']]['content'] = $postprojloc[$locale['shortName']]['content'];
                if (($projloc[$locale['shortName']]['title'] != '') && (!preg_match("/^.{3,}$/ui", $projloc[$locale['shortName']]['title']))) {$errors = true; $projlocerror[$locale['shortName']]['title'] = 'Заголовок должен содержать более 3 символов';}
            }
            unset($postprojloc);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Валидация пользователей
            $postusers = $this->get('request_stack')->getMasterRequest()->get('users');
            if (!is_array($postusers)) $postusers = array();
            $postusersrole = $this->get('request_stack')->getMasterRequest()->get('usersrole');
            if (!is_array($postusersrole)) $postusersrole = array();
            $users = array();
            foreach ($postusers as $postuserid=>$postuserro)
            {
                $query = $em->createQuery('SELECT u.id, u.login, u.fullName FROM BasicCmsBundle:Users u WHERE u.id = :id')->setParameter('id', $postuserid);
                $postuserent = $query->getResult();
                if (isset($postuserent[0]))
                {
                    $users[] = array('id'=>$postuserent[0]['id'], 'login'=>$postuserent[0]['login'], 'fullName'=>$postuserent[0]['fullName'], 'projectRole' => (isset($postusersrole[$postuserid]) ? $postusersrole[$postuserid] : 0), 'readOnly' => intval($postuserro), 'error' => '');
                }
            }
            unset($postusers);
            unset($postusersrole);
            if (($errors == true) && ($activetab == 0)) $activetab = 4;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            if ($this->container->has('object.taxonomy')) 
            {
                $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->get('request_stack')->getMasterRequest(), 'create', 'object.project', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                $i++;
            }
            foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'projectCreate', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($project['content'], '');
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($project['description'], '');
                $projectent = new \Extended\ProjectBundle\Entity\Projects();
                $projectent->setContent($project['content']);
                $projectent->setCreateDate(new \DateTime('now'));
                $projectent->setCreaterId($this->getUser()->getId());
                $projectent->setDescription($project['description']);
                $projectent->setEnabled($project['enabled']);
                $projectent->setModifyDate(new \DateTime('now'));
                $projectent->setTitle($project['title']);
                $projectent->setViewCount($project['viewCount']);
                $projectent->setIsPublic($project['isPublic']);
                $projectent->setIsUnvisible($project['isUnvisible']);
                $projectent->setIsLocked($project['isLocked']);
                $em->persist($projectent);
                $em->flush();
                if ($page['enable'] != 0)
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $pageent->setBreadCrumbs('');
                    $pageent->setBreadCrumbsRel('');
                    $pageent->setDescription('Просмотр мастерской '.$projectent->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.project');
                    $pageent->setContentId($projectent->getId());
                    $pageent->setContentAction('view');
                    $projectent->setTemplate($page['template']);
                    if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                    $em->persist($pageent);
                    $em->flush();
                    if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                }
                foreach ($users as $useritem)
                {
                    $projectuserent = new \Extended\ProjectBundle\Entity\ProjectUsers();
                    $projectuserent->setProjectId($projectent->getId());
                    $projectuserent->setReadOnly($useritem['readOnly']);
                    $projectuserent->setProjectRole($useritem['projectRole']);
                    $projectuserent->setUserId($useritem['id']);
                    $em->persist($projectuserent);
                    $em->flush();
                }
                foreach ($locales as $locale)
                {
                    if (($projloc[$locale['shortName']]['title'] != '') ||
                        ($projloc[$locale['shortName']]['description'] != '') ||
                        ($projloc[$locale['shortName']]['content'] != ''))
                    {
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($projloc[$locale['shortName']]['content'], '');
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($projloc[$locale['shortName']]['description'], '');
                        $projlocent = new \Extended\ProjectBundle\Entity\ProjectsLocale();
                        $projlocent->setProjectId($projectent->getId());
                        $projlocent->setLocale($locale['shortName']);
                        if ($projloc[$locale['shortName']]['title'] == '') $projlocent->setTitle(null); else $projlocent->setTitle($projloc[$locale['shortName']]['title']);
                        if ($projloc[$locale['shortName']]['description'] == '') $projlocent->setDescription(null); else $projlocent->setDescription($projloc[$locale['shortName']]['description']);
                        if ($projloc[$locale['shortName']]['content'] == '') $projlocent->setContent(null); else $projlocent->setContent($projloc[$locale['shortName']]['content']);
                        $em->persist($projlocent);
                        $em->flush();
                        unset($projlocent);
                    }
                }
                $i = 0;
                if ($this->container->has('object.taxonomy')) 
                {
                    $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->get('request_stack')->getMasterRequest(), 'create', 'object.project', $projectent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                    $i++;
                }
                foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'projectCreate', $projectent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой мастерской',
                    'message'=>'Мастерская успешно создана',
                    'paths'=>array('Создать еще одну мастерскую'=>$this->get('router')->generate('extended_project_create'),'Вернуться к списку мастерских'=>$this->get('router')->generate('extended_project_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        if ($this->container->has('object.taxonomy')) $tabs[] =  array('name'=>'Категории классификации','content'=>$this->container->get('object.taxonomy')->getTaxonomyController($this->get('request_stack')->getMasterRequest(), 'create', 'object.project', 0, 'tab'));
        foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'projectCreate', 0, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('ExtendedProjectBundle:Default:projectCreate.html.twig', array(
            'project' => $project,
            'projecterror' => $projecterror,
            'page' => $page,
            'pageerror' => $pageerror,
            'projloc' => $projloc,
            'projlocerror' => $projlocerror,
            'users' => $users,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'roles' => $roles,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
    }

    public function projectAjaxUsersAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $search = trim($this->get('request_stack')->getMasterRequest()->get('search'));
        $page = intval($this->get('request_stack')->getMasterRequest()->get('page'));
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
// Редактирование мастерской
// *******************************************    
    public function projectEditAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_list_tab', 0);
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $projectent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:Projects')->find($id);
        if (empty($projectent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование мастерской',
                'message'=>'Мастерская не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку мастерских'=>$this->get('router')->generate('extended_project_list'))
            ));
        }
        if ((($this->getUser()->checkAccess('project_viewall') == 0) && ($this->getUser()->getId() != $projectent->getCreaterId())) || 
            (($this->getUser()->checkAccess('project_viewown') == 0) && ($this->getUser()->getId() == $projectent->getCreaterId())))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование мастерской',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.project','contentId'=>$id,'contentAction'=>'view'));

        $project = array();
        $projecterror = array();
        
        $project['title'] = $projectent->getTitle();
        $project['description'] = $projectent->getDescription();
        $project['content'] = $projectent->getContent();
        $project['enabled'] = $projectent->getEnabled();
        $project['viewCount'] = $projectent->getViewCount();
        $project['isPublic'] = $projectent->getIsPublic();
        $project['isUnvisible'] = $projectent->getIsUnvisible();
        $project['isLocked'] = $projectent->getIsLocked();
        $projecterror['title'] = '';
        $projecterror['description'] = '';
        $projecterror['content'] = '';
        $projecterror['enabled'] = '';
        $projecterror['viewCount'] = '';
        $projecterror['isPublic'] = '';
        $projecterror['isUnvisible'] = '';
        $projecterror['isLocked'] = '';
        
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
            $page['template'] = $projectent->getTemplate();
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
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.project','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.project.view');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        $projloc = array();
        $projlocerror = array();
        foreach ($locales as $locale)
        {
            $projlocent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectsLocale')->findOneBy(array('locale'=>$locale['shortName'],'projectId'=>$id));
            if (!empty($projlocent))
            {
                $projloc[$locale['shortName']]['title'] = $projlocent->getTitle();
                $projloc[$locale['shortName']]['description'] = $projlocent->getDescription();
                $projloc[$locale['shortName']]['content'] = $projlocent->getContent();
                unset($projlocent);
            } else 
            {
                $projloc[$locale['shortName']]['title'] = '';
                $projloc[$locale['shortName']]['description'] = '';
                $projloc[$locale['shortName']]['content'] = '';
            }
            $projlocerror[$locale['shortName']]['title'] = '';
            $projlocerror[$locale['shortName']]['description'] = '';
            $projlocerror[$locale['shortName']]['content'] = '';
        }
        
        $query = $em->createQuery('SELECT u.id, u.login, u.fullName, pu.readOnly, pu.projectRole, \'\' as error FROM ExtendedProjectBundle:ProjectUsers pu '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = pu.userId '.
                                  'WHERE pu.projectId = :id')->setParameter('id', $id);
        $users = $query->getResult();
        if (!is_array($users)) $users = array();
        // Инфа для калькулятора прав доступа
        $query = $em->createQuery('SELECT d.id, d.title, d.isPublic, d.readOnly FROM ExtendedProjectBundle:ProjectDocuments d '.
                                  'WHERE d.projectId = :id AND d.enabled != 0')->setParameter('id', $id);
        $documentList = $query->getResult();
        $createrEnt = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($projectent->getCreaterId());
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            if ((($this->getUser()->checkAccess('project_editall') == 0) && ($this->getUser()->getId() != $projectent->getCreaterId())) || 
                (($this->getUser()->checkAccess('project_editown') == 0) && ($this->getUser()->getId() == $projectent->getCreaterId())))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование мастерской',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            // Проверка основных данных
            $postproject = $this->get('request_stack')->getMasterRequest()->get('project');
            if (isset($postproject['title'])) $project['title'] = $postproject['title'];
            if (isset($postproject['description'])) $project['description'] = $postproject['description'];
            if (isset($postproject['content'])) $project['content'] = $postproject['content'];
            if (isset($postproject['enabled'])) $project['enabled'] = intval($postproject['enabled']); else $project['enabled'] = 0;
            if (isset($postproject['viewCount'])) $project['viewCount'] = intval($postproject['viewCount']);
            if (isset($postproject['isPublic'])) $project['isPublic'] = intval($postproject['isPublic']); else $project['isPublic'] = 0;
            if (isset($postproject['isUnvisible'])) $project['isUnvisible'] = intval($postproject['isUnvisible']); else $project['isUnvisible'] = 0;
            if (isset($postproject['isLocked'])) $project['isLocked'] = intval($postproject['isLocked']); else $project['isLocked'] = 0;
            unset($postproject);
            if (!preg_match("/^.{3,}$/ui", $project['title'])) {$errors = true; $projecterror['title'] = 'Заголовок должен содержать более 3 символов';}
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
                    if (!preg_match("/^[a-z][a-z0-9\-]{1,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
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
            $postprojloc = $this->get('request_stack')->getMasterRequest()->get('projloc');
            foreach ($locales as $locale)
            {
                if (isset($postprojloc[$locale['shortName']]['title'])) $projloc[$locale['shortName']]['title'] = $postprojloc[$locale['shortName']]['title'];
                if (isset($postprojloc[$locale['shortName']]['description'])) $projloc[$locale['shortName']]['description'] = $postprojloc[$locale['shortName']]['description'];
                if (isset($postprojloc[$locale['shortName']]['content'])) $projloc[$locale['shortName']]['content'] = $postprojloc[$locale['shortName']]['content'];
                if (($projloc[$locale['shortName']]['title'] != '') && (!preg_match("/^.{3,}$/ui", $projloc[$locale['shortName']]['title']))) {$errors = true; $projlocerror[$locale['shortName']]['title'] = 'Заголовок должен содержать более 3 символов';}
            }
            unset($postprojloc);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Валидация пользователей
            $postusers = $this->get('request_stack')->getMasterRequest()->get('users');
            if (!is_array($postusers)) $postusers = array();
            $postusersrole = $this->get('request_stack')->getMasterRequest()->get('usersrole');
            if (!is_array($postusersrole)) $postusersrole = array();
            $users = array();
            foreach ($postusers as $postuserid=>$postuserro)
            {
                $query = $em->createQuery('SELECT u.id, u.login, u.fullName FROM BasicCmsBundle:Users u WHERE u.id = :id')->setParameter('id', $postuserid);
                $postuserent = $query->getResult();
                if (isset($postuserent[0]))
                {
                    $users[] = array('id'=>$postuserent[0]['id'], 'login'=>$postuserent[0]['login'], 'fullName'=>$postuserent[0]['fullName'], 'projectRole' => (isset($postusersrole[$postuserid]) ? $postusersrole[$postuserid] : 0), 'readOnly' => intval($postuserro), 'error' => '');
                }
            }
            unset($postusers);
            unset($postusersrole);
            if (($errors == true) && ($activetab == 0)) $activetab = 4;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            if ($this->container->has('object.taxonomy')) 
            {
                $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->get('request_stack')->getMasterRequest(), 'edit', 'object.project', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                $i++;
            }
            foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'projectEdit', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($project['content'], $projectent->getContent());
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($project['description'], $projectent->getDescription());
                $projectent->setContent($project['content']);
                $projectent->setDescription($project['description']);
                $projectent->setEnabled($project['enabled']);
                $projectent->setModifyDate(new \DateTime('now'));
                $projectent->setTitle($project['title']);
                $projectent->setViewCount($project['viewCount']);
                $projectent->setIsPublic($project['isPublic']);
                $projectent->setIsUnvisible($project['isUnvisible']);
                $projectent->setIsLocked($project['isLocked']);
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
                    $pageent->setDescription('Просмотр мастерской '.$projectent->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.project');
                    $pageent->setContentId($projectent->getId());
                    $pageent->setContentAction('view');
                    $projectent->setTemplate($page['template']);
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
                    $projlocent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectsLocale')->findOneBy(array('locale'=>$locale['shortName'],'projectId'=>$id));
                    if (($projloc[$locale['shortName']]['title'] != '') ||
                        ($projloc[$locale['shortName']]['description'] != '') ||
                        ($projloc[$locale['shortName']]['content'] != ''))
                    {
                        if (empty($projlocent))
                        {
                            $projlocent = new \Extended\ProjectBundle\Entity\ProjectsLocale();
                            $em->persist($projlocent);
                        }
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($projloc[$locale['shortName']]['content'], $projlocent->getContent());
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($projloc[$locale['shortName']]['description'], $projlocent->getDescription());
                        $projlocent->setProjectId($projectent->getId());
                        $projlocent->setLocale($locale['shortName']);
                        if ($projloc[$locale['shortName']]['title'] == '') $projlocent->setTitle(null); else $projlocent->setTitle($projloc[$locale['shortName']]['title']);
                        if ($projloc[$locale['shortName']]['description'] == '') $projlocent->setDescription(null); else $projlocent->setDescription($projloc[$locale['shortName']]['description']);
                        if ($projloc[$locale['shortName']]['content'] == '') $projlocent->setContent(null); else $projlocent->setContent($projloc[$locale['shortName']]['content']);
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
                $projectuserids = array();
                foreach ($users as $useritem)
                {
                    $projectuserent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectUsers')->findOneBy(array('userId'=>$useritem['id'],'projectId'=>$id));
                    if (empty($projectuserent))
                    {
                        $projectuserent = new \Extended\ProjectBundle\Entity\ProjectUsers();
                        $em->persist($projectuserent);
                    }
                    $projectuserent->setProjectId($projectent->getId());
                    $projectuserent->setReadOnly($useritem['readOnly']);
                    $projectuserent->setProjectRole($useritem['projectRole']);
                    $projectuserent->setUserId($useritem['id']);
                    $em->flush();
                    $projectuserids[] = $projectuserent->getId();
                    unset($projectuserent);
                }
                if (count($projectuserids) > 0)
                {
                    $query = $em->createQuery('DELETE FROM ExtendedProjectBundle:ProjectUsers u WHERE u.projectId = :projectid AND u.id NOT IN (:ids)')->setParameter('projectid', $id)->setParameter('ids', $projectuserids);
                    $query->execute();
                } else
                {
                    $query = $em->createQuery('DELETE FROM ExtendedProjectBundle:ProjectUsers u WHERE u.projectId = :projectid')->setParameter('projectid', $id);
                    $query->execute();
                }
                $i = 0;
                if ($this->container->has('object.taxonomy')) 
                {
                    $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->get('request_stack')->getMasterRequest(), 'edit', 'object.project', $projectent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                    $i++;
                }
                foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'projectEdit', $projectent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование мастерской',
                    'message'=>'Мастерская успешно изменена',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('extended_project_edit').'?id='.$id, 'Вернуться к списку мастерских'=>$this->get('router')->generate('extended_project_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        if ($this->container->has('object.taxonomy')) $tabs[] =  array('name'=>'Категории классификации','content'=>$this->container->get('object.taxonomy')->getTaxonomyController($this->get('request_stack')->getMasterRequest(), 'edit', 'object.project', $id, 'tab'));
        foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'projectEdit', $id, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('ExtendedProjectBundle:Default:projectEdit.html.twig', array(
            'id' => $id,
            'createrId' => $projectent->getCreaterId(),
            'project' => $project,
            'projecterror' => $projecterror,
            'page' => $page,
            'pageerror' => $pageerror,
            'projloc' => $projloc,
            'projlocerror' => $projlocerror,
            'users' => $users,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'roles' => $roles,
            'tabs' => $tabs,
            'activetab' => $activetab,
            'documentList' => $documentList,
            'createrEnt' => $createrEnt,
        ));
    }
    
// *******************************************
// AJAX-контроллер
// *******************************************    
    
    public function projectAjaxAction()
    {
        $tab = intval($this->get('request_stack')->getMasterRequest()->get('tab'));
        if ($this->getUser()->checkAccess('project_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Мастерские',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->get('request_stack')->getMasterRequest()->get('action');
        $errors = array();
        if ($action == 'delete')
        {
            if ($this->getUser()->checkAccess('project_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Мастерские',
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
                    $projectent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:Projects')->find($key);
                    if (!empty($projectent))
                    {
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $projectent->getContent());
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $projectent->getDescription());
                        $em->remove($projectent);
                        $em->flush();
                        $query = $em->createQuery('SELECT d.content FROM ExtendedProjectBundle:ProjectDocuments d WHERE d.projectId = :id')->setParameter('id', $key);
                        $delpages = $query->getResult();
                        if (is_array($delpages))
                        {
                            foreach ($delpages as $delpage)
                            {
                                if ($delpage['content'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $delpage['content']);
                            }
                        }
                        $query = $em->createQuery('DELETE FROM ExtendedProjectBundle:ProjectDocuments d WHERE d.projectId = :id')->setParameter('id', $key);
                        $query->execute();
                        $query = $em->createQuery('DELETE FROM ExtendedProjectBundle:ProjectUsers u WHERE u.projectId = :id')->setParameter('id', $key);
                        $query->execute();
                        if ($this->container->has('object.taxonomy')) $this->container->get('object.taxonomy')->getTaxonomyController($this->get('request_stack')->getMasterRequest(), 'delete', 'object.project', $key, 'save');
                        foreach ($cmsservices as $item) if (strpos($item,'addone.project.') === 0) 
                        {
                            $serv = $this->container->get($item);
                            $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'projectDelete', $key, 'save');
                        }       
                        unset($projectent);    
                    }
                }
        }
        if ($action == 'blocked')
        {
            if ($this->getUser()->checkAccess('project_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Мастерские',
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
                    $projectent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:Projects')->find($key);
                    if (!empty($projectent))
                    {
                        $projectent->setEnabled(0);
                        $em->flush();
                        unset($projectent);    
                    }
                }
        }
        if ($action == 'unblocked')
        {
            if ($this->getUser()->checkAccess('project_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Мастерские',
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
                    $projectent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:Projects')->find($key);
                    if (!empty($projectent))
                    {
                        $projectent->setEnabled(1);
                        $em->flush();
                        unset($projectent);    
                    }
                }
        }
        /*if ($tab == 0)
        {*/
        // Таб 1
        $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_list_page0');
        $sort0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_list_sort0');
        $search0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_list_search0');
        $taxonomy0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_list_taxonomy0');
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        $taxonomy0 = intval($taxonomy0);
        // Поиск контента для 1 таба (текстовые страницы)
        $querytaxonomyleft0 = '';
        $querytaxonomywhere0 = '';
        if (($this->container->has('object.taxonomy')) && ($taxonomy0 != 0))
        {
            $querytaxonomyleft0 = ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks txl WITH txl.taxonomyId = '.$taxonomy0.' AND txl.itemId = p.id ';
            $querytaxonomywhere0 = ' AND txl.id IS NOT NULL ';
        }
        $query = $em->createQuery('SELECT count(p.id) as projectcount FROM ExtendedProjectBundle:Projects p '.$querytaxonomyleft0.
                                  'WHERE p.title like :search '.$querytaxonomywhere0)->setParameter('search', '%'.$search0.'%');
        $projectcount = $query->getResult();
        if (!empty($projectcount)) $projectcount = $projectcount[0]['projectcount']; else $projectcount = 0;
        $pagecount0 = ceil($projectcount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY p.title ASC';
        if ($sort0 == 1) $sortsql = 'ORDER BY p.title DESC';
        if ($sort0 == 2) $sortsql = 'ORDER BY p.enabled ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY p.enabled DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY p.createDate ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY p.createDate DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        if ($sort0 == 8) $sortsql = 'ORDER BY p.viewCount ASC';
        if ($sort0 == 9) $sortsql = 'ORDER BY p.viewCount DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT p.id, p.title, p.createDate, p.modifyDate, u.fullName, p.enabled, p.viewCount, (SELECT count(d.id) FROM ExtendedProjectBundle:ProjectDocuments d WHERE d.projectId = p.id) as documents FROM ExtendedProjectBundle:Projects p '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.$querytaxonomyleft0.
                                  'WHERE p.title like :search '.$querytaxonomywhere0.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $projects0 = $query->getResult();
        if ($this->container->has('object.taxonomy'))
        {
            $taxonomyenabled = 1;
            $actionIds = array();
            foreach ($projects0 as $project) $actionIds[] = $project['id'];
            $taxonomyinfo0 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.project', $actionIds);
        } else
        {
            $taxonomyenabled = 0;
            $taxonomyinfo0 = null;
        }
        return $this->render('ExtendedProjectBundle:Default:projectListTab1.html.twig', array(
            'projects0' => $projects0,
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
        //}
    }

    
// *******************************************
// Список документов мастерской
// *******************************************    
    
    public function projectDocumentsListAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_documents_list_tab', 0);
        $em = $this->getDoctrine()->getEntityManager();
        // Таб 1
        $id = $this->get('request_stack')->getMasterRequest()->get('id');
        $page0 = $this->get('request_stack')->getMasterRequest()->get('page0');
        $sort0 = $this->get('request_stack')->getMasterRequest()->get('sort0');
        $search0 = $this->get('request_stack')->getMasterRequest()->get('search0');
        if ($id === null) $id = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_documents_list_id');
                     else $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_documents_list_id', $id);
        if ($page0 === null) $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_documents_list_page0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_documents_list_page0', $page0);
        if ($sort0 === null) $sort0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_documents_list_sort0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_documents_list_sort0', $sort0);
        if ($search0 === null) $search0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_documents_list_search0');
                          else $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_documents_list_search0', $search0);
        $projectent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:Projects')->find($id);
        if (empty($projectent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр документов мастерской',
                'message'=>'Мастерская не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку мастерских'=>$this->get('router')->generate('extended_project_list'))
            ));
        }
        if ((($this->getUser()->checkAccess('project_viewall') == 0) && ($this->getUser()->getId() != $projectent->getCreaterId())) || 
            (($this->getUser()->checkAccess('project_viewown') == 0) && ($this->getUser()->getId() == $projectent->getCreaterId())))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр документов мастерской',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $oldid = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_documents_list_oldid');
        if ($id != $oldid)
        {
            $page0 = null;
            $sort0 = null;
            $search0 = null;
            $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_documents_list_oldid', $id);
            $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_documents_list_page0', $page0);
            $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_documents_list_sort0', $sort0);
            $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_documents_list_search0', $search0);
        }
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        // Поиск контента для 1 таба (текстовые страницы)
        $query = $em->createQuery('SELECT count(d.id) as documentcount FROM ExtendedProjectBundle:ProjectDocuments d '.
                                  'WHERE d.title like :search AND d.projectId = :id')->setParameter('search', '%'.$search0.'%')->setParameter('id', $id);
        $documentcount = $query->getResult();
        if (!empty($documentcount)) $documentcount = $documentcount[0]['documentcount']; else $documentcount = 0;
        $pagecount0 = ceil($documentcount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY d.title ASC';
        if ($sort0 == 1) $sortsql = 'ORDER BY d.title DESC';
        if ($sort0 == 2) $sortsql = 'ORDER BY d.enabled ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY d.enabled DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY d.createDate ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY d.createDate DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT d.id, d.title, d.createDate, u.fullName, d.enabled FROM ExtendedProjectBundle:ProjectDocuments d '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = d.createrId '.
                                  'WHERE d.title like :search AND d.projectId = :id '.$sortsql)->setParameter('search', '%'.$search0.'%')->setParameter('id', $id)->setFirstResult($start)->setMaxResults(20);
        $documents0 = $query->getResult();
        return $this->render('ExtendedProjectBundle:Default:documentsList.html.twig', array(
            'project' => $projectent,
            'documents0' => $documents0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'pagecount0' => $pagecount0
        ));
    }
    
// *******************************************
// Создание нового документа
// *******************************************    
    public function projectDocumentsCreateAction()
    {
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $projectent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:Projects')->find($id);
        if (empty($projectent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание нового документа к мастеркой',
                'message'=>'Мастерская не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку мастерских'=>$this->get('router')->generate('extended_project_list'))
            ));
        }
        if ((($this->getUser()->checkAccess('project_editall') == 0) && ($this->getUser()->getId() != $projectent->getCreaterId())) || 
            (($this->getUser()->checkAccess('project_editown') == 0) && ($this->getUser()->getId() == $projectent->getCreaterId())))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание нового документа к мастерской',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $document = array();
        $documenterror = array();
        
        $document['enabled'] = 1;
        $document['title'] = '';
        $document['content'] = '';
        $document['readOnly'] = 0;
        $document['isPublic'] = 0;
        
        $documenterror['enabled'] = '';
        $documenterror['title'] = '';
        $documenterror['content'] = '';
        $documenterror['readOnly'] = '';
        $documenterror['isPublic'] = '';

        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $document['titleLocales'] = array();
        $document['contentLocales'] = array();
        foreach ($locales as $locale) {
            $document['titleLocales'][$locale['shortName']] = '';
            $document['contentLocales'][$locale['shortName']] = '';
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postdocument = $this->get('request_stack')->getMasterRequest()->get('document');
            if (isset($postdocument['title'])) $document['title'] = $postdocument['title'];
            if (isset($postdocument['content'])) $document['content'] = $postdocument['content'];
            if (isset($postdocument['readOnly'])) $document['readOnly'] = intval($postdocument['readOnly']); else $document['readOnly'] = 0;
            if (isset($postdocument['isPublic'])) $document['isPublic'] = intval($postdocument['isPublic']); else $document['isPublic'] = 0;
            if (isset($postdocument['enabled'])) $document['enabled'] = intval($postdocument['enabled']); else $document['enabled'] = 0;
            foreach ($locales as $locale) {
                $document['titleLocales'][$locale['shortName']] = (isset($postdocument['titleLocale'][$locale['shortName']]) ? $postdocument['titleLocale'][$locale['shortName']] : '');
                $document['contentLocales'][$locale['shortName']] = (isset($postdocument['contentLocales'][$locale['shortName']]) ? $postdocument['contentLocales'][$locale['shortName']] : '');;
            }
            unset($postdocument);
            if (!preg_match("/^.{3,}$/ui", $document['title'])) {$errors = true; $documenterror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($document['content'], '');
                
                $documentent = new \Extended\ProjectBundle\Entity\ProjectDocuments();
                $documentent->setContent($document['content']);
                $documentent->setCreateDate(new \DateTime('now'));
                $documentent->setCreaterId($this->getUser()->getId());
                $documentent->setEnabled($document['enabled']);
                $documentent->setIsPublic($document['isPublic']);
                $documentent->setProjectId($projectent->getId());
                $documentent->setReadOnly($document['readOnly']);
                $documentent->setTitle($document['title']);
                $documentent->setParentId(0);
                $em->persist($documentent);
                $em->flush();
                $documentent->setParentId($documentent->getId());
                $em->flush();
                foreach ($locales as $locale) {
                    $this->container->get('cms.cmsManager')->unlockTemporaryEditor($document['contentLocales'][$locale['shortName']], '');
                    $locEnt = new \Extended\ProjectBundle\Entity\ProjectDocumentsLocale();
                    $locEnt->setTitle($document['titleLocales'][$locale['shortName']]);
                    $locEnt->setContent($document['contentLocales'][$locale['shortName']]);
                    $locEnt->setDocumentId($documentent->getId());
                    $locEnt->setLocale($locale['shortName']);
                    $em->persist($locEnt);
                    $em->flush();
                }
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание нового документа к мастерской',
                    'message'=>'Документ успешно создан',
                    'paths'=>array('Создать еще один документ'=>$this->get('router')->generate('extended_project_documents_create').'?id='.$id,'Вернуться к списку документов мастерской'=>$this->get('router')->generate('extended_project_documents_list').'?id='.$projectent->getId())
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('ExtendedProjectBundle:Default:documentsCreate.html.twig', array(
            'id' => $id,
            'project' => $projectent,
            'document' => $document,
            'documenterror' => $documenterror,
            'locales' => $locales,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// Редактирование документа
// *******************************************    
    public function projectDocumentsEditAction()
    {
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $documentent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectDocuments')->find($id);
        if (empty($documentent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование документа к мастерской',
                'message'=>'Документ не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к списку мастерских'=>$this->get('router')->generate('extended_project_list'))
            ));
        }
        $projectent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:Projects')->find($documentent->getProjectId());
        if (empty($projectent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование документа к мастерской',
                'message'=>'Мастерская не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку мастерских'=>$this->get('router')->generate('extended_project_list'))
            ));
        }
        if ((($this->getUser()->checkAccess('project_viewall') == 0) && ($this->getUser()->getId() != $projectent->getCreaterId())) || 
            (($this->getUser()->checkAccess('project_viewown') == 0) && ($this->getUser()->getId() == $projectent->getCreaterId())))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование документа к мастерской',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $document = array();
        $documenterror = array();
        
        $document['enabled'] = $documentent->getEnabled();
        $document['title'] = $documentent->getTitle();
        $document['content'] = $documentent->getContent();
        $document['readOnly'] = $documentent->getReadOnly();
        $document['isPublic'] = $documentent->getIsPublic();
        
        $documenterror['enabled'] = '';
        $documenterror['title'] = '';
        $documenterror['content'] = '';
        $documenterror['readOnly'] = '';
        $documenterror['isPublic'] = '';
        
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $document['titleLocales'] = array();
        $document['contentLocales'] = array();
        foreach ($locales as $locale) {
            $locEnt = $em->getRepository('ExtendedProjectBundle:ProjectDocumentsLocale')->findOneBy(array('documentId' => $id, 'locale' => $locale['shortName']));
            $document['titleLocales'][$locale['shortName']] = (!empty($locEnt) ? $locEnt->getTitle() : '');
            $document['contentLocales'][$locale['shortName']] = (!empty($locEnt) ? $locEnt->getContent() : '');
        }
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            if ((($this->getUser()->checkAccess('project_editall') == 0) && ($this->getUser()->getId() != $projectent->getCreaterId())) || 
                (($this->getUser()->checkAccess('project_editown') == 0) && ($this->getUser()->getId() == $projectent->getCreaterId())))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование документа к мастерской',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            // Проверка основных данных
            $postdocument = $this->get('request_stack')->getMasterRequest()->get('document');
            if (isset($postdocument['title'])) $document['title'] = $postdocument['title'];
            if (isset($postdocument['content'])) $document['content'] = $postdocument['content'];
            if (isset($postdocument['readOnly'])) $document['readOnly'] = intval($postdocument['readOnly']); else $document['readOnly'] = 0;
            if (isset($postdocument['isPublic'])) $document['isPublic'] = intval($postdocument['isPublic']); else $document['isPublic'] = 0;
            if (isset($postdocument['enabled'])) $document['enabled'] = intval($postdocument['enabled']); else $document['enabled'] = 0;
            foreach ($locales as $locale) {
                $document['titleLocales'][$locale['shortName']] = (isset($postdocument['titleLocale'][$locale['shortName']]) ? $postdocument['titleLocale'][$locale['shortName']] : '');
                $document['contentLocales'][$locale['shortName']] = (isset($postdocument['contentLocales'][$locale['shortName']]) ? $postdocument['contentLocales'][$locale['shortName']] : '');;
            }
            unset($postdocument);
            if (!preg_match("/^.{3,}$/ui", $document['title'])) {$errors = true; $documenterror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($document['content'], $documentent->getContent());
                $documentent->setContent($document['content']);
                $documentent->setEnabled($document['enabled']);
                $documentent->setIsPublic($document['isPublic']);
                $documentent->setReadOnly($document['readOnly']);
                $documentent->setTitle($document['title']);
                $em->flush();
                foreach ($locales as $locale) {
                    $locEnt = $em->getRepository('ExtendedProjectBundle:ProjectDocumentsLocale')->findOneBy(array('documentId' => $id, 'locale' => $locale['shortName']));
                    if (empty($locEnt)) {
                        $locEnt = new \Extended\ProjectBundle\Entity\ProjectDocumentsLocale();
                        $locEnt->setDocumentId($documentent->getId());
                        $locEnt->setLocale($locale['shortName']);
                        $em->persist($locEnt);
                    }
                    $this->container->get('cms.cmsManager')->unlockTemporaryEditor($document['contentLocales'][$locale['shortName']], $locEnt->getContent());
                    $locEnt->setTitle($document['titleLocales'][$locale['shortName']]);
                    $locEnt->setContent($document['contentLocales'][$locale['shortName']]);
                    $em->flush();
                }
                
                $em->createQuery('UPDATE ExtendedProjectBundle:ProjectDocuments d SET d.enabled = :enabled, d.readOnly = :readonly, d.isPublic = :ispublic WHERE d.parentId = :parent')->setParameters(array(
                    'enabled' => $documentent->getEnabled(),
                    'readonly' => $documentent->getReadOnly(),
                    'ispublic' => $documentent->getIsPublic(),
                    'parent' => $documentent->getParentId()
                ))->execute();
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование документа к мастерской',
                    'message'=>'Документ успешно изменён',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('extended_project_documents_edit').'?id='.$id,'Вернуться к списку документов'=>$this->get('router')->generate('extended_project_documents_list').'?id='.$projectent->getId())
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('ExtendedProjectBundle:Default:documentsEdit.html.twig', array(
            'id' => $id,
            'project' => $projectent,
            'document' => $document,
            'documenterror' => $documenterror,
            'locales' => $locales,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// AJAX-контроллер
// *******************************************    
    
    public function projectDocumentsAjaxAction()
    {
        //$tab = intval($this->get('request_stack')->getMasterRequest()->get('tab'));
        $tab = 0;
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->get('request_stack')->getMasterRequest()->get('action');
        $errors = array();
        if ($action == 'deletedocument')
        {
            $cmsservices = $this->container->getServiceIds();
            $check = $this->get('request_stack')->getMasterRequest()->get('check');
            $restoreids = array();
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $documentent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectDocuments')->find($key);
                    if (!empty($documentent))
                    {
                        if ($documentent->getId() == $documentent->getParentId())
                        {
                            $restoreids[] = $documentent->getId();
                        }
                        //$this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $documentent->getContent());
                        $this->container->get('object.project')->removeDocumentImages($documentent->getId(), $documentent->getContent());
                        $em->remove($documentent);
                        $em->flush();
                        unset($documentent); 
                    }
                    $locdocents = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectDocuments')->findBy(array('documentId' => $key));
                    foreach ($locdocents as $locdocent) {
                        $this->container->get('object.project')->removeDocumentImages($locdocent->getDocumentId(), $locdocent->getContent());
                        $em->remove($locdocent);
                        $em->flush();
                        unset($locdocent); 
                    }
                }
            if (count($restoreids) > 0)
            {
                foreach ($restoreids as $restoreid)
                {
                    try 
                    {
                        $updateid = $em->createQuery('SELECT d.id FROM ExtendedProjectBundle:ProjectDocuments d WHERE d.parentId = :id ORDER BY d.id ASC')->setMaxResults(1)->setParameter('id', $restoreid)->getSingleScalarResult();
                        $em->createQuery('UPDATE ExtendedProjectBundle:ProjectDocuments d SET d.parentId = :newid WHERE d.parentId = :id')->setParameter('id', $restoreid)->setParameter('newid', $updateid)->execute();
                    } catch (\Doctrine\ORM\NoResultException $e)
                    {
                    }
                }
            }
        }
        if ($action == 'blockeddocument')
        {
            $check = $this->get('request_stack')->getMasterRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $documentent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectDocuments')->find($key);
                    if (!empty($documentent))
                    {
                        $documentent->setEnabled(0);
                        $em->flush();
                        unset($documentent);    
                    }
                }
        }
        if ($action == 'unblockeddocument')
        {
            $check = $this->get('request_stack')->getMasterRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $documentent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectDocuments')->find($key);
                    if (!empty($documentent))
                    {
                        $documentent->setEnabled(1);
                        $em->flush();
                        unset($documentent);    
                    }
                }
        }

        // Таб 1
        $id = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_documents_list_id');
        $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_documents_list_page0');
        $sort0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_documents_list_sort0');
        $search0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('extended_project_documents_list_search0');
        $projectent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:Projects')->find($id);
        if (empty($projectent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр документов мастерской',
                'message'=>'Мастерская не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку мастерских'=>$this->get('router')->generate('extended_project_list'))
            ));
        }
        if ((($this->getUser()->checkAccess('project_viewall') == 0) && ($this->getUser()->getId() != $projectent->getCreaterId())) || 
            (($this->getUser()->checkAccess('project_viewown') == 0) && ($this->getUser()->getId() == $projectent->getCreaterId())))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр документов мастерской',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        // Поиск контента для 1 таба (текстовые страницы)
        $query = $em->createQuery('SELECT count(d.id) as documentcount FROM ExtendedProjectBundle:ProjectDocuments d '.
                                  'WHERE d.title like :search AND d.projectId = :id')->setParameter('search', '%'.$search0.'%')->setParameter('id', $id);
        $documentcount = $query->getResult();
        if (!empty($documentcount)) $documentcount = $documentcount[0]['documentcount']; else $documentcount = 0;
        $pagecount0 = ceil($documentcount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY d.title ASC';
        if ($sort0 == 1) $sortsql = 'ORDER BY d.title DESC';
        if ($sort0 == 2) $sortsql = 'ORDER BY d.enabled ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY d.enabled DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY d.createDate ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY d.createDate DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT d.id, d.title, d.createDate, u.fullName, d.enabled FROM ExtendedProjectBundle:ProjectDocuments d '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = d.createrId '.
                                  'WHERE d.title like :search AND d.projectId = :id '.$sortsql)->setParameter('search', '%'.$search0.'%')->setParameter('id', $id)->setFirstResult($start)->setMaxResults(20);
        $documents0 = $query->getResult();
        return $this->render('ExtendedProjectBundle:Default:documentsListTab1.html.twig', array(
            'project' => $projectent,
            'documents0' => $documents0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'pagecount0' => $pagecount0,
            'errors0' => $errors
        ));
    }

// *******************************************
// Подключение к TinyMCE jbimage
// *******************************************    
    
    public function tinyMceBlankAction()
    {
        return $this->render('ExtendedProjectBundle:Default:tinyMceBlank.html.twig', array());
    }
    
    public function tinyMceUploadAction()
    {
        $userid = $this->get('request_stack')->getMasterRequest()->getSession()->get('front_system_autorized');
        $userEntity = null;
        if ($userid != null) $userEntity = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($userid);
        if (empty($userEntity))
        {
            $userid = $this->get('request_stack')->getMasterRequest()->cookies->get('front_autorization_keytwo');
            $userpass = $this->get('request_stack')->getMasterRequest()->cookies->get('front_autorization_keyone');
            $query = $this->getDoctrine()->getEntityManager()->createQuery('SELECT u FROM BasicCmsBundle:Users u WHERE MD5(CONCAT(u.id,\'Embedded.CMS\')) = :userid AND MD5(CONCAT(u.password, u.salt)) = :userpass')->setParameter('userid', $userid)->setParameter('userpass', $userpass);
            $userEntity = $query->getResult();
            if ((count($userEntity) == 1) && (isset($userEntity[0])) && (!empty($userEntity[0]))) $userEntity = $userEntity[0];
        }
        if (empty($userEntity)) return $this->render('ExtendedProjectBundle:Default:tinyMceBlank.html.twig', array());
        if ($userEntity->getBlocked() != 2) return $this->render('ExtendedProjectBundle:Default:tinyMceBlank.html.twig', array());
        
        $file = $this->get('request_stack')->getMasterRequest()->files->get('userfile');
        $tmpfile = $file->getPathName(); 
        $source = "";
        $error = '';
        if (@getimagesize($tmpfile))
        {
            $params = getimagesize($tmpfile);
            if ($params[0] < 10) $error = 'Разрешение файла меньше 10x10';
            if ($params[1] < 10) $error = 'Разрешение файла меньше 10x10';
            if ($params[0] > 6000) $error = 'Разрешение файла больше 6000x6000';
            if ($params[1] > 6000) $error = 'Разрешение файла больше 6000x6000';
            $imageTypeArray = array(0=>'', 1=>'gif', 2=>'jpg', 3=>'png', 4=>'', 5=>'', 6=>'', 7=>'', 8=>'', 9=>'jpg', 10=>'jpg', 11=>'jpg', 12=>'jpg', 13=>'', 14=>'', 15=>'', 16=>'', 17=>'', 18=>'');
            if (!isset($imageTypeArray[$params[2]]) || ($imageTypeArray[$params[2]] == '')) $error = 'Формат файла не поддерживается';
            if ($error == '')
            {
                $basepath = 'images/editor/';
                $name = 'f_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
                if (move_uploaded_file($tmpfile, $basepath . $name)) 
                {
                    $this->container->get('cms.cmsManager')->registerTemporaryFile('/images/editor/'.$name, $file->getClientOriginalName());
                    $result = array();
                    $result['result'] = "Файл загружен";
                    $result['resultcode'] = 'ok';
                    $result['file_name'] = '/images/editor/'.$name;
                    return $this->render('ExtendedProjectBundle:Default:tinyMceUpload.html.twig', array('result'=>$result));                    
                }
            }
        }
        if ($error == '') $error = 'Ошибка загрузки файла';
        $result = array();
        $result['result'] = $error;
        $result['resultcode']	= 'failed';
        $result['file_name'] = '';
        return $this->render('ExtendedProjectBundle:Default:tinyMceUpload.html.twig', array('result'=>$result));
    }
    
// *******************************************
// Подключение к TinyMCE файл-менеджера sxmFileManager
// *******************************************    
    private function cmp_array_list($a, $b)
    {
        $i = 0;
        if ($a['name'] != $b['name']) $i = ($a['name'] < $b['name']) ? -1 : 1;
        if ($a['isfile'] != $b['isfile']) $i = ($a['isfile'] < $b['isfile']) ? -1 : 1;
        return $i;
    }
    
    public function tinyMceFileListAction()
    {
        $userid = $this->get('request_stack')->getMasterRequest()->getSession()->get('front_system_autorized');
        $userEntity = null;
        if ($userid != null) $userEntity = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($userid);
        if (empty($userEntity))
        {
            $userid = $this->get('request_stack')->getMasterRequest()->cookies->get('front_autorization_keytwo');
            $userpass = $this->get('request_stack')->getMasterRequest()->cookies->get('front_autorization_keyone');
            $query = $this->getDoctrine()->getEntityManager()->createQuery('SELECT u FROM BasicCmsBundle:Users u WHERE MD5(CONCAT(u.id,\'Embedded.CMS\')) = :userid AND MD5(CONCAT(u.password, u.salt)) = :userpass')->setParameter('userid', $userid)->setParameter('userpass', $userpass);
            $userEntity = $query->getResult();
            if ((count($userEntity) == 1) && (isset($userEntity[0])) && (!empty($userEntity[0]))) $userEntity = $userEntity[0];
        }
        if (empty($userEntity)) return new Response(json_encode(array('result'=>'Error', 'message'=>'Доступ запрещен', 'list'=>array())));
        if ($userEntity->getBlocked() != 2) return new Response(json_encode(array('result'=>'Error', 'message'=>'Доступ запрещен', 'list'=>array())));
        // Найти права пользователя
        $premissionRead = 0;
        $premissionWrite = 0;
        $documentId = $this->get('request_stack')->getMasterRequest()->get('documentid');
        $projectId = null;
        if ($documentId !== null)
        {
            $documentent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectDocuments')->findOneBy(array('id'=>$documentId, 'enabled'=>1));
            if (empty($documentent)) return new Response(json_encode(array('result'=>'Error', 'message'=>'Доступ запрещен', 'list'=>array())));
            $projectent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:Projects')->findOneBy(array('id'=>$documentent->getProjectId(), 'enabled'=>1));
            if (empty($projectent)) return new Response(json_encode(array('result'=>'Error', 'message'=>'Доступ запрещен', 'list'=>array())));
        } else
        {
            $projectId = $this->get('request_stack')->getMasterRequest()->get('projectid');
            $projectent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:Projects')->findOneBy(array('id'=>$projectId, 'enabled'=>1));
            if (empty($projectent)) return new Response(json_encode(array('result'=>'Error', 'message'=>'Доступ запрещен', 'list'=>array())));
        }
        if ($userEntity->getId() == $projectent->getCreaterId())
        {
            $premissionRead = 1;
            $premissionWrite = 1;
        }
        $userlink = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectUsers')->findOneBy(array('projectId'=>$projectent->getId(), 'userId'=>$userEntity->getId()));
        if (!empty($userlink))
        {
            $premissionRead = 1;
            if ($userlink->getReadOnly() == 0) $premissionWrite = 1;
        }
        if ((!empty($documentent)) && ($documentent->getIsPublic() != 0))
        {
            $premissionRead = 1;
            $premissionWrite = 1;
        }
        if ((!empty($documentent)) && ($documentent->getReadOnly() != 0)) $premissionWrite = 0;
        if ($premissionRead == 0) return new Response(json_encode(array('result'=>'Error', 'message'=>'Доступ запрещен', 'list'=>array())));
        // Выполнить действия
        $projectFolder = 'secured/project';
        $projectFolderBase = $projectFolder;
        $path = $this->get('request_stack')->getMasterRequest()->get('path');
        if ($this->get('request_stack')->getMasterRequest()->headers->get('x-ajaxupload-partial-module') == 'ajaxupload.partial') {
            $path = urldecode($this->get('request_stack')->getMasterRequest()->headers->get('x-ajaxupload-partial-path'));
        }
        if (($path != '') && (is_dir($projectFolder.'/'.$path))) $projectFolder = $projectFolder.'/'.$path;
        $operationResult = 'OK';
        $operationMessage = '';
        $operation = $this->get('request_stack')->getMasterRequest()->get('operation');
        if ($operation == 'upload')
        {
            if ($premissionWrite != 0)
            {
                if ($this->get('request_stack')->getMasterRequest()->headers->get('x-ajaxupload-partial-module') == 'ajaxupload.partial') {
                    $request = $this->get('request_stack')->getMasterRequest();
                    $session = $request->getSession()->get('ajaxupload.partial');
                    if (!is_array($session)) {
                        $session = array();
                    }
                    $fileUploadId = $request->headers->get('x-ajaxupload-partial-id');
                    $fileUploadSize = $request->headers->get('x-ajaxupload-partial-size');
                    $fileUploadOriginalName = urldecode($request->headers->get('x-ajaxupload-partial-filename'));
                    $fileUploadPosition = $request->headers->get('x-ajaxupload-partial-position');
                    $fileUploadFileName = null;
                    
                    if (isset($session[$fileUploadId])) {
                        if ($fileUploadSize != $session[$fileUploadId]['size'] || $fileUploadOriginalName != $session[$fileUploadId]['originalName']) {
                            $operationResult = 'Error';$operationMessage = 'Файлы не соотвествуют';
                        } else {
                            $fileUploadFileName = $session[$fileUploadId]['fileName'];
                        }
                    } else {
                        $fileInfo = @unserialize(file_get_contents($projectFolder.'/'.'.folderinfo'));
                        if (!is_array($fileInfo)) $fileInfo = array();
                        if (!isset($fileInfo[$fileUploadOriginalName]) || ($fileInfo[$fileUploadOriginalName] == $documentId) || ($fileInfo[$fileUploadOriginalName] == 'proj'.$projectId))
                        {
                            $fileUploadFileName = $projectFolder.'/'.$fileUploadOriginalName;
                            $session[$fileUploadId] = array(
                                'fileName' => $fileUploadFileName,
                                'originalName' => $fileUploadOriginalName,
                                'size' => $fileUploadSize
                            );
                            $request->getSession()->set('ajaxupload.partial', $session);
                        } else {$operationResult = 'Error';$operationMessage = 'Файл уже существует для другого документа';}
                    }
                    if ($fileUploadFileName) {
                        if ($documentId !== null) $fileInfo[$fileUploadOriginalName] = $documentId; else $fileInfo[$fileUploadOriginalName] = 'proj'.$projectId;
                        file_put_contents($projectFolder.'/'.'.folderinfo', serialize($fileInfo));
                        if (file_exists($fileUploadFileName)) {
                            $fileUploadCurrentPosition = filesize($fileUploadFileName);
                        } else {
                            $fileUploadCurrentPosition = 0;
                        }
                        if ($fileUploadCurrentPosition != $fileUploadPosition) {
                            return new Response(json_encode(array('next' => $fileUploadCurrentPosition)));
                        }
                        $f = fopen($fileUploadFileName, 'a+b');
                        $data = $request->getContent();
                        fwrite($f, $data);
                        fclose($f);
                        $fileUploadCurrentPosition += strlen($data);
                        if ($fileUploadCurrentPosition < $fileUploadSize) {
                            return new Response(json_encode(array('next' => $fileUploadCurrentPosition)));
                        }
                    }
                } else {
                    $file = $this->get('request_stack')->getMasterRequest()->files->get('file');
                    $tmpfile = $file->getPathName(); 
                    $fileInfo = @unserialize(file_get_contents($projectFolder.'/'.'.folderinfo'));
                    if (!is_array($fileInfo)) $fileInfo = array();
                    if (!isset($fileInfo[$file->getClientOriginalName()]) || ($fileInfo[$file->getClientOriginalName()] == $documentId) || ($fileInfo[$file->getClientOriginalName()] == 'proj'.$projectId))
                    {
                        if (move_uploaded_file($tmpfile, $projectFolder.'/'.$file->getClientOriginalName())) 
                        {
                            if ($documentId !== null) $fileInfo[$file->getClientOriginalName()] = $documentId; else $fileInfo[$file->getClientOriginalName()] = 'proj'.$projectId;
                            file_put_contents($projectFolder.'/'.'.folderinfo', serialize($fileInfo));
                        } else {$operationResult = 'Error';$operationMessage = 'Ошибка загрузки файла';}
                    } else {$operationResult = 'Error';$operationMessage = 'Файл уже существует для другого документа';}
                }
            } else {$operationResult = 'Error';$operationMessage = 'Загрузка файлов вам запрещена';}
        }
        if ($operation == 'folder')
        {
            if ($premissionWrite != 0)
            {
                $name = $this->get('request_stack')->getMasterRequest()->get('name');
                if (preg_match("/^[\w\s]{3,}(#[\w\s]{3,})?$/ui", $name)) 
                {
                    mkdir($projectFolder.'/'.$name);
                } else {$operationResult = 'Error';$operationMessage = 'Имя папки некорректно';}
            } else {$operationResult = 'Error';$operationMessage = 'Создание папок вам запрещено';}
        }
        if ($operation == 'folderrename')
        {
            if ($premissionWrite != 0)
            {
                $oldname = $this->get('request_stack')->getMasterRequest()->get('oldname');
                $name = $this->get('request_stack')->getMasterRequest()->get('name');
                if (preg_match("/^[\w\s]{3,}(#[\w\s]{3,})?$/ui", $name) || !is_dir($projectFolder.'/'.$oldname)) 
                {
                    rename($projectFolder.'/'.$oldname, $projectFolder.'/'.$name);
                    $projectLink = @unserialize(file_get_contents($projectFolderBase.'/'.'.projectlink'));
                    if (!is_array($projectLink)) $projectLink = array();
                    foreach ($projectLink as $projectKey=>$projectVal) 
                    {
                        if (strpos($projectVal['path'], ($path != '' ? $path.'/' : '').$oldname) === 0) {
                            $projectLink[$projectKey]['path'] = str_replace(($path != '' ? $path.'/' : '').$oldname, ($path != '' ? $path.'/' : '').$name, $projectVal['path']);
                        }
                    }
                    file_put_contents($projectFolderBase.'/'.'.projectlink', serialize($projectLink));
                } else {$operationResult = 'Error';$operationMessage = 'Имя папки некорректно';}
            } else {$operationResult = 'Error';$operationMessage = 'Создание папок вам запрещено';}
        }
        if ($operation == 'delete')
        {
            if ($premissionWrite != 0)
            {
                $file = $this->get('request_stack')->getMasterRequest()->get('file');
                if (is_file($projectFolder.'/'.$file))
                {
                    $fileInfo = @unserialize(file_get_contents($projectFolder.'/'.'.folderinfo'));
                    if (!is_array($fileInfo)) $fileInfo = array();
                    if (isset($fileInfo[$file]) && ((($documentId !== null) && ($fileInfo[$file] == $documentId)) || (($documentId === null) && ($fileInfo[$file] == 'proj'.$projectId)))) //($fileInfo[$file] == $documentId))
                    {
                        unset($fileInfo[$file]);
                        file_put_contents($projectFolder.'/'.'.folderinfo', serialize($fileInfo));
                        unlink($projectFolder.'/'.$file);
                        $projectLink = @unserialize(file_get_contents($projectFolderBase.'/'.'.projectlink'));
                        if (!is_array($projectLink)) $projectLink = array();
                        foreach ($projectLink as $projectKey=>$projectVal) 
                        {
                            if (($projectVal['file'] == $file) && ($projectVal['path'] == $path)) unset($projectLink[$projectKey]);
                        }
                        file_put_contents($projectFolderBase.'/'.'.projectlink', serialize($projectLink));
                        $this->getDoctrine()->getEntityManager()->createQuery('DELETE FROM ExtendedKnowledgeBundle:KnowledgeObject o WHERE o.objectType = :type AND o.objectTextId = :textid')
                            ->setParameter('type', 1)
                            ->setParameter('textid', $path.'/'.$file)
                            ->execute();
                    } else {$operationResult = 'Error';$operationMessage = 'Удаление этого файла вам запрещено';}
                } elseif (is_dir($projectFolder.'/'.$file))
                {
                    $allowdelete = (($tmpfiles = @scandir($projectFolder.'/'.$file)) && count($tmpfiles) <= 2); 
                    if ($allowdelete != 0) @rmdir($projectFolder.'/'.$file); else {$operationResult = 'Error';$operationMessage = 'Папка содержит файлы, удаление невозможно';}
                } else   
                {
                    $operationResult = 'Error';$operationMessage = 'Файл "'.$file.'" не найден';
                }
            } else {$operationResult = 'Error';$operationMessage = 'Удаление файлов вам запрещено';}
        }
        if ($operation == 'addproject')
        {
            if ($premissionWrite != 0)
            {
                $file = $this->get('request_stack')->getMasterRequest()->get('file');
                if (is_file($projectFolder.'/'.$file))
                {
                    $projectLink = @unserialize(file_get_contents($projectFolderBase.'/'.'.projectlink'));
                    if (!is_array($projectLink)) $projectLink = array();
                    $existLink = 0;
                    foreach ($projectLink as $projectKey=>$projectVal) 
                    {
                        if (($projectVal['file'] == $file) && ($projectVal['path'] == $path) && ($projectVal['projectId'] == $projectId)) $existLink = 1;
                    }
                    if ($existLink == 0) 
                    {
                        $projectLink[] = array('projectId'=>$projectId, 'file'=>$file, 'path'=>$path);
                        file_put_contents($projectFolderBase.'/'.'.projectlink', serialize($projectLink));
                    }
                } else   
                {
                    $operationResult = 'Error';$operationMessage = 'Файл "'.$file.'" не найден';
                }
            } else {$operationResult = 'Error';$operationMessage = 'Удаление файлов вам запрещено';}
        }
        if ($operation == 'unlink')
        {
            if ($premissionWrite != 0)
            {
                $file = $this->get('request_stack')->getMasterRequest()->get('file');
                if (is_file($projectFolder.'/'.$file))
                {
                    $fileInfo = @unserialize(file_get_contents($projectFolder.'/'.'.folderinfo'));
                    if (!is_array($fileInfo)) $fileInfo = array();
                    $projectLink = @unserialize(file_get_contents($projectFolderBase.'/'.'.projectlink'));
                    if (!is_array($projectLink)) $projectLink = array();
                    foreach ($projectLink as $projectKey=>$projectVal) 
                    {
                        if (($projectVal['file'] == $file) && ($projectVal['path'] == $path) && ($projectVal['projectId'] == $projectId)) unset($projectLink[$projectKey]);
                    }
                    file_put_contents($projectFolderBase.'/'.'.projectlink', serialize($projectLink));
                    $this->getDoctrine()->getEntityManager()->createQuery('DELETE FROM ExtendedKnowledgeBundle:KnowledgeObject o WHERE o.objectType = :type AND o.objectId = :id AND o.objectTextId = :textid')
                        ->setParameter('type', 1)
                        ->setParameter('id', $projectId)
                        ->setParameter('textid', $path.'/'.$file)
                        ->execute();
                } else   
                {
                    $operationResult = 'Error';$operationMessage = 'Файл "'.$file.'" не найден';
                }
            } else {$operationResult = 'Error';$operationMessage = 'Отсоединение файлов вам запрещено';}
        }
        if ($operation == 'move')
        {
            if ($premissionWrite != 0)
            {
                $file = $this->get('request_stack')->getMasterRequest()->get('file');
                $baseName = basename($file);
                if (is_file($projectFolderBase.'/'.$file))
                {
                    $oldPath = dirname($file);
                    $fileInfo = @unserialize(file_get_contents($projectFolderBase.'/'.$oldPath.'/'.'.folderinfo'));
                    if (!is_array($fileInfo)) $fileInfo = array();
                    $newFileInfo = @unserialize(file_get_contents($projectFolder.'/'.'.folderinfo'));
                    if (!is_array($newFileInfo)) $newFileInfo = array();
                    if (isset($fileInfo[$baseName])) {
                        $newFileInfo[$baseName] = $fileInfo[$baseName];
                        unset($fileInfo[$baseName]);
                        file_put_contents($projectFolder.'/'.'.folderinfo', serialize($newFileInfo));
                        file_get_contents($projectFolderBase.'/'.$oldPath.'/'.'.folderinfo', serialize($fileInfo));
                    }
                    $projectLink = @unserialize(file_get_contents($projectFolderBase.'/'.'.projectlink'));
                    if (!is_array($projectLink)) $projectLink = array();
                    foreach ($projectLink as $projectKey=>$projectVal) 
                    {
                        if (($projectVal['file'] == $baseName) && ($projectVal['path'] == $oldPath) && ($projectVal['projectId'] == $projectId)) {
                            $projectLink[$projectKey]['path'] = $path;
                        }
                    }
                    file_put_contents($projectFolderBase.'/'.'.projectlink', serialize($projectLink));
                    rename($projectFolderBase.'/'.$file, $projectFolder.'/'.$baseName);
                    $this->getDoctrine()->getEntityManager()->createQuery('UPDATE ExtendedKnowledgeBundle:KnowledgeObject o SET o.objectTextId = :newtextid WHERE o.objectType = :type AND o.objectTextId = :textid')
                        ->setParameter('type', 1)
                        ->setParameter('newtextid', $path.'/'.$baseName)
                        ->setParameter('textid', $oldPath.'/'.$baseName)
                        ->execute();
                } else   
                {
                    $operationResult = 'Error';$operationMessage = 'Файл "'.$file.'" не найден';
                }
            } else {$operationResult = 'Error';$operationMessage = 'Отсоединение файлов вам запрещено';}
        }
        // Получить список файлов
        $fileList = array();
        if (is_dir($projectFolder))
        {
            $projectLink = @unserialize(file_get_contents($projectFolderBase.'/'.'.projectlink'));
            if (!is_array($projectLink)) $projectLink = array();
            $fileInfo = @unserialize(file_get_contents($projectFolder.'/'.'.folderinfo'));
            if (!is_array($fileInfo)) $fileInfo = array();
            $dir = opendir($projectFolder);
            while($file = readdir($dir))
            {
                if (($file != '.') && ($file != '..') && ($file != '.folderinfo') && ($file != '.projectlink'))
                {
                    if  (is_file($projectFolder.'/'.$file)) 
                    {
                        $allowunlink = 0;
                        foreach ($projectLink as $projectKey=>$projectVal) 
                        {
                            if (($projectVal['file'] == $file) && ($projectVal['path'] == $path) && ($projectVal['projectId'] == $projectId)) $allowunlink = 1;
                        }
                        $allowdelete = 0;
                        if (isset($fileInfo[$file]) && ((($documentId !== null) && ($fileInfo[$file] == $documentId)) || (($documentId === null) && ($fileInfo[$file] == 'proj'.$projectId)))) $allowdelete = 1;
                        //if (isset($fileInfo[$file]) && ($fileInfo[$file] == $documentId)) $allowdelete = 1;
                        $fileList[] = array('isfile'=>1, 'name'=>$file, 'size'=>filesize($projectFolder.'/'.$file), 'fileurl'=>urlencode($file), 'fullfileurl'=>urlencode(($path != '' ? $path.'/'.$file : $file)), 'allowdelete'=>$allowdelete, 'allowunlink'=>$allowunlink);
                    }
                    if  (is_dir($projectFolder.'/'.$file)) 
                    {
                        $allowdelete = (($tmpfiles = @scandir($projectFolder.'/'.$file)) && count($tmpfiles) <= 2); 
                        $fileList[] = array('isfile'=>0, 'name'=>$file, 'size'=>0, 'fileurl'=>urlencode($file), 'fullfileurl'=>urlencode(($path != '' ? $path.'/'.$file : $file)), 'allowdelete'=>$allowdelete, 'allowunlink'=>0);
                    }
                }
            }
        }
        usort($fileList, array($this,'cmp_array_list'));
        return new Response(json_encode(array('result'=>$operationResult, 'message'=>$operationMessage, 'list'=>$fileList)));
    }

    public function tinyMceFileDownloadAction()
    {
        $userid = $this->get('request_stack')->getMasterRequest()->getSession()->get('front_system_autorized');
        $userEntity = null;
        if ($userid != null) $userEntity = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($userid);
        if (empty($userEntity))
        {
            $userid = $this->get('request_stack')->getMasterRequest()->cookies->get('front_autorization_keytwo');
            $userpass = $this->get('request_stack')->getMasterRequest()->cookies->get('front_autorization_keyone');
            $query = $this->getDoctrine()->getEntityManager()->createQuery('SELECT u FROM BasicCmsBundle:Users u WHERE MD5(CONCAT(u.id,\'Embedded.CMS\')) = :userid AND MD5(CONCAT(u.password, u.salt)) = :userpass')->setParameter('userid', $userid)->setParameter('userpass', $userpass);
            $userEntity = $query->getResult();
            if ((count($userEntity) == 1) && (isset($userEntity[0])) && (!empty($userEntity[0]))) $userEntity = $userEntity[0];
        }
        // Найти права пользователя
        $premissionRead = 0;
        $documentId = $this->get('request_stack')->getMasterRequest()->get('documentid');
        if ($documentId !== null)
        {
            $documentent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectDocuments')->findOneBy(array('id'=>$documentId, 'enabled'=>1));
            if (empty($documentent)) return new Response('Access denied', 403);
            $projectent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:Projects')->findOneBy(array('id'=>$documentent->getProjectId(), 'enabled'=>1));
            if (empty($projectent)) return new Response('Access denied', 403);
        } else 
        {
            $projectId = $this->get('request_stack')->getMasterRequest()->get('projectid');
            $projectent = $this->getDoctrine()->getRepository('ExtendedProjectBundle:Projects')->findOneBy(array('id'=>$projectId, 'enabled'=>1));
            if (empty($projectent)) return new Response('Access denied', 403);
        }
        if (!empty($userEntity) && ($userEntity->getBlocked() == 2))
        {
        
            if ($userEntity->getId() == $projectent->getCreaterId())
            {
                $premissionRead = 1;
            }
            $userlink = $this->getDoctrine()->getRepository('ExtendedProjectBundle:ProjectUsers')->findOneBy(array('projectId'=>$projectent->getId(), 'userId'=>$userEntity->getId()));
            if (!empty($userlink))
            {
                $premissionRead = 1;
            }
        }
        if ((!empty($documentent)) && ($documentent->getIsPublic() != 0))
        {
            $premissionRead = 1;
        }
        if ($premissionRead == 0) return new Response('Access denied', 403);
        // Выдать файл
        $file = $this->get('request_stack')->getMasterRequest()->get('file');
        $filePath = 'secured/project/'.$file;
        $newFileName = $file;
        if (is_file($filePath)) 
        {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            header("Content-type: ".finfo_file($finfo, $filePath));
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
            if (strpos($_SERVER['HTTP_USER_AGENT'],"MSIE" ) > 0 ) header('Content-Disposition: inline; filename="'.rawurlencode(basename($newFileName)).'";');
            else header('Content-Disposition: inline; filename*=UTF-8\'\''.rawurlencode(basename($newFileName)).';');

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
// Редактирование сообщений пользователям
// *******************************************    
    public function projectMessagesAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('extended_project_list_tab', 0);
        if ($this->getUser()->checkAccess('project_editall') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Прочие настройки',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $saveparameters = $this->container->get('cms.cmsManager')->getConfigValue('project.lockmessages');
        
        $parameters = array();
        $parameters['default'] = $this->container->get('cms.cmsManager')->decodeLocalString($saveparameters, 'default', false);
                
        foreach ($locales as $locale)
        {
            $parameters[$locale['shortName']] = $this->container->get('cms.cmsManager')->decodeLocalString($saveparameters, $locale['shortName'], false);
        }
        
        $errors = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            $postparameters = $this->get('request_stack')->getMasterRequest()->get('parameters');
            
            if (isset($postparameters['default']))
            {
                $parameters['default'] = $postparameters['default'];
            }
            foreach ($locales as $locale)
            {
                if (isset($postparameters[$locale['shortName']]))
                {
                    $parameters[$locale['shortName']] = $postparameters[$locale['shortName']];
                }
            }
            // save
            if (count($errors) == 0)
            {
                $saveparameters = $this->container->get('cms.cmsManager')->encodeLocalString($parameters);
                $this->container->get('cms.cmsManager')->setConfigValue('project.lockmessages', $saveparameters);
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Прочие настройки',
                    'message'=>'Настройки успешно изменены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('extended_project_messages'))
                ));
            }
        }        
        return $this->render('ExtendedProjectBundle:Default:messages.html.twig', array(
            'parameters' => $parameters, 
            'errors' => $errors, 
            'locales' => $locales,
        ));
        
        
        
        
        
        
    }
    
    
}
