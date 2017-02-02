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

class ModuleController extends Controller
{
// *******************************************
// Главная страница модулей
// *******************************************    
    public function moduleListAction()
    {
        if ($this->getUser()->checkAccess('module_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Модули',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $moduletypes = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) $moduletypes[$item] = $this->container->get($item)->getModuleTypes();
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $positions = array();
        foreach ($layouts as $layoutkey => $layoutname) $positions[$layoutkey] = $this->get('cms.cmsManager')->getPositionList($layoutkey);
        $em = $this->getDoctrine()->getEntityManager();
        $page0 = $this->get('request_stack')->getMasterRequest()->get('page0');
        if ($page0 === null) $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_module_list_page0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_module_list_page0', $page0);
        $page0 = intval($page0);
        // Поиск контента
        $query = $em->createQuery('SELECT count(m.id) as modulecount FROM BasicCmsBundle:Modules m');
        $modulecount = $query->getResult();
        if (!empty($modulecount)) $modulecount = $modulecount[0]['modulecount']; else $modulecount = 0;
        $pagecount0 = ceil($modulecount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT m.id, m.name, m.objectName, m.moduleType, m.position, m.layout, m.enabled, m.ordering, m.template, m.locale FROM BasicCmsBundle:Modules m ORDER BY m.layout, m.position, m.ordering')->setFirstResult($start)->setMaxResults(20);
        $modules = $query->getResult();
        foreach ($modules as &$module)
        {
            if (isset($moduletypes[$module['objectName']][$module['moduleType']])) $module['moduleTypeName'] = $moduletypes[$module['objectName']][$module['moduleType']]; else $module['moduleTypeName'] = 'Неизвестный';
            if (isset($positions[$module['layout']][$module['position']])) $module['positionName'] = $positions[$module['layout']][$module['position']]; else $module['positionName'] = 'Неизвестная';
            $templates = $this->get('cms.cmsManager')->getModuleTemplateList($module['objectName'], $module['moduleType']);
            if (isset($templates[$module['template']])) $module['templateName'] = $templates[$module['template']]; else $module['templateName'] = 'По умолчанию';
        }
        return $this->render('BasicCmsBundle:Modules:moduleList.html.twig', array(
            'modules' => $modules,
            'page0' => $page0,
            'pagecount0' => $pagecount0
        ));
        
    }
    
// *******************************************
// Создание нового пользователя
// *******************************************    
    public function moduleCreateAction()
    {
        if ($this->getUser()->checkAccess('module_new') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание нового модуля',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $moduletypes = array();
        $moduletype = $this->get('request_stack')->getMasterRequest()->get('type');
        $moduleobject = $this->get('request_stack')->getMasterRequest()->get('object');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) $moduletypes[$item] = $this->container->get($item)->getModuleTypes();
        if (!isset($moduletypes[$moduleobject][$moduletype]))
        {
            // Выбор типа модуля
            return $this->render('BasicCmsBundle:Modules:modulePreCreate.html.twig', array(
                'moduletypes' => $moduletypes
            ));
        }
        // Собственно создание модуля
        $em = $this->getDoctrine()->getEntityManager();
        $module = array();
        $moduleerror = array();
        $module['name'] = '';
        $module['enabled'] = 1;
        $module['layout'] = '';
        $module['position'] = '';
        $module['locale'] = '';
        $module['template'] = '';
        $module['accessOn'] = '';
        $module['access'] = array();
        $moduleerror['name'] = '';
        $moduleerror['enabled'] = '';
        $moduleerror['layout'] = '';
        $moduleerror['position'] = '';
        $moduleerror['locale'] = '';
        $moduleerror['template'] = '';
        $moduleerror['accessOn'] = '';
        $moduleerror['access'] = '';
        // Найти роли
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        // Найти страницы
        $pages = array();
        $pages['group'] = array('old');
        $pages['on'] = array();
        $pages['off'] = array();
        $groups = array();
        // Найти группы страниц
        $seogroups = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) $seogroups[$item] = $this->container->get($item)->getContentTypes();
        // Найти страницы
        //$query = $em->createQuery('SELECT s.id, s.description, 0 as moduleOn FROM BasicCmsBundle:SeoPage s ORDER BY s.contentType, s.contentAction');
        //$seopages = $query->getResult();
        // Найти позиции и лэйауты
        $templates = $this->get('cms.cmsManager')->getModuleTemplateList($moduleobject, $moduletype);
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $positions = array();
        foreach ($layouts as $layoutkey => $layoutname) $positions[$layoutkey] = $this->get('cms.cmsManager')->getPositionList($layoutkey);
        // Найти локали
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        // Валидация
        $activetab = 0;
        $errors = false;
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postmodule = $this->get('request_stack')->getMasterRequest()->get('module');
            if (isset($postmodule['name'])) $module['name'] = $postmodule['name'];
            if (isset($postmodule['enabled'])) $module['enabled'] = intval($postmodule['enabled']);
            if (isset($postmodule['layout'])) $module['layout'] = $postmodule['layout'];
            if (isset($postmodule['position'][$module['layout']])) $module['position'] = $postmodule['position'][$module['layout']];
            if (isset($postmodule['locale'])) $module['locale'] = $postmodule['locale'];
            if (isset($postmodule['template'])) $module['template'] = $postmodule['template'];
            if (isset($postmodule['accessOn'])) $module['accessOn'] = intval($postmodule['accessOn']);
            $module['access'] = array();
            foreach ($roles as $onerole) if (isset($postmodule['access']) && is_array($postmodule['access']) && (in_array($onerole['id'], $postmodule['access']))) $module['access'][] = $onerole['id'];
            unset($postmodule);
            if (!preg_match("/^[-0-9A-zА-яЁё\s\`]{3,99}$/ui", $module['name'])) {$errors = true; $moduleerror['name'] = 'Имя должно содержать от 3 до 99 букв';}
            if (!isset($layouts[$module['layout']])) {$errors = true; $moduleerror['layout'] = 'Шаблон не найден';}
            if (!isset($positions[$module['layout']][$module['position']])) {$errors = true; $moduleerror['position'] = 'Позиция не найдена';}
            if (($module['template'] != '') && (!isset($templates[$module['template']]))) {$errors = true; $moduleerror['template'] = 'Шаблон не найден';}
            $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $module['locale']) $localecheck = true;}
            if ($localecheck == false) $module['locale'] = '';
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о включении
            $postpages = $this->get('request_stack')->getMasterRequest()->get('pages');
            if (!is_array($postpages)) $postpages = array();
            $pages['group'] = array();
            if (isset($postpages['group']) && is_array($postpages['group']))
            {
                foreach ($seogroups as $objectkey=>$typearray)
                    foreach ($typearray as $typekey=>$typename)
                    {
                        if (in_array($objectkey.'.'.$typekey, $postpages['group'])) $pages['group'][] = $objectkey.'.'.$typekey;
                    }
                if (in_array('all', $postpages['group'])) $pages['group'][] = 'all';
                if (in_array('old', $postpages['group'])) $pages['group'][] = 'old';
            }
            $pages['on'] = array();
            if (isset($postpages['on']) && is_array($postpages['on']))
            {
                foreach ($postpages['on'] as $val)
                {
                    if (preg_match('/^\d+$/ui', $val)) $pages['on'][] = $val;
                }
            }
            $pages['off'] = array();
            if (isset($postpages['off']) && is_array($postpages['off']))
            {
                foreach ($postpages['off'] as $val)
                {
                    if (preg_match('/^\d+$/ui', $val)) $pages['off'][] = $val;
                }
            }
/*            foreach ($seopages as $seopage)
            {
                if (in_array($seopage['id'], $postpages)) $pages[] = $seopage['id'];
            }*/
            $postgroups = $this->get('request_stack')->getMasterRequest()->get('groups');
            if (!is_array($postgroups)) $postgroups = array();
            $groups = array();
            foreach ($seogroups as $objectkey=>$typearray)
                foreach ($typearray as $typekey=>$typename)
                {
                    if (in_array($objectkey.'.'.$typekey, $postgroups)) $groups[] = $objectkey.'.'.$typekey;
                }
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Вылидация таба настройки
            $serv = null;
            if ($this->container->has($moduleobject)) $serv = $this->container->get($moduleobject);
            if ($serv !== null) 
            {
                $localerror = $serv->getAdminModuleController($this->getRequest (), $moduletype, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = 3;
            }
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $query = $em->createQuery('SELECT MAX(m.ordering) as ordering FROM BasicCmsBundle:Modules m WHERE m.layout = :layout AND m.position = :position')->setParameter('layout', $module['layout'])->setParameter('position', $module['position']);
                $ordering = $query->getResult();
                if (isset($ordering[0]['ordering'])) $ordering = $ordering[0]['ordering'] + 1; else $ordering = 1;
                $parameters = null;
                if ($serv !== null) $parameters = $serv->getAdminModuleController($this->getRequest (), $moduletype, 'parameters');
                $moduleent = new \Basic\CmsBundle\Entity\Modules();
                $moduleent->setEnabled($module['enabled']);
                $moduleent->setAutoEnable(implode(',',$groups));
                $moduleent->setLayout($module['layout']);
                $moduleent->setLocale($module['locale']);
                $moduleent->setModuleType($moduletype);
                $moduleent->setName($module['name']);
                $moduleent->setObjectName($moduleobject);
                $moduleent->setOrdering($ordering);
                $moduleent->setParameters(serialize($parameters));
                $moduleent->setPosition($module['position']);
                $moduleent->setTemplate($module['template']);
                if ($module['accessOn'] != 0) $moduleent->setAccess(implode(',', $module['access'])); else $moduleent->setAccess('');
                $em->persist($moduleent);
                $em->flush();
                // Включить страницы
                $x = 0;
                do
                {
                    $query = $em->createQuery('SELECT s FROM BasicCmsBundle:SeoPage s')->setMaxResults(100)->setFirstResult($x * 100);
                    $pagestooff = $query->getResult();
                    if (count($pagestooff) == 0) break;
                    $x++;
                    foreach ($pagestooff as $pagetooff) 
                    {
                        $pagestatus = 0;
                        if (in_array('all', $pages['group'])) $pagestatus = 1;
                        if (in_array($pagetooff->getContentType().'.'.$pagetooff->getContentAction(), $pages['group'])) $pagestatus = 1;
                        if (in_array('old', $pages['group'])) $pagestatus = -1;
                        if (in_array($pagetooff->getId(), $pages['on'])) $pagestatus = 1;
                        if (in_array($pagetooff->getId(), $pages['off'])) $pagestatus = 0;
                        if ($pagestatus != -1)
                        {
                            $modls = explode(',',$pagetooff->getModules());
                            $newmodls = array();
                            foreach ($modls as $item) 
                            {
                                if ($item != $moduleent->getId()) $newmodls[] = $item;
                            }
                            if ($pagestatus == 1) $newmodls[] = $moduleent->getId();
                            $pagetooff->setModules(implode(',',$newmodls));
                        }
                    }
                    $em->flush();    
                    unset($pagestooff);
                } while (0 == 0);
                // Сделано
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание нового модуля',
                    'message'=>'Модуль успешно создан',
                    'paths'=>array('Создать еще один модуль'=>$this->get('router')->generate('basic_cms_module_new'),'Вернуться к списку модулей'=>$this->get('router')->generate('basic_cms_module_list'))
                ));
            }
        }
        // Взять таб настроек модуля
        $tab = '';
        $serv = null;
        if ($this->container->has($moduleobject)) $serv = $this->container->get($moduleobject);
        if ($serv !== null) $tab = $serv->getAdminModuleController($this->getRequest (), $moduletype, 'tab');
        // Отправляем
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Modules:moduleCreate.html.twig', array(
            'module' => $module,
            'moduleerror' => $moduleerror,
            'moduleobject' => $moduleobject,
            'moduletype' => $moduletype,
            'pages' => $pages,
//            'seopages' => $seopages,
            'groups' => $groups,
            'seogroups' => $seogroups,
            'locales' => $locales,
            'templates' => $templates,
            'layouts' => $layouts,
            'positions' => $positions,
            'tab' => $tab,
            'activetab' => $activetab,
            'roles' => $roles
        ));
    }

// *******************************************
// Редактирование пользователя
// *******************************************    
    public function moduleEditAction()
    {
        if ($this->getUser()->checkAccess('module_edit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование модуля',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $moduleent = $this->getDoctrine()->getRepository('BasicCmsBundle:Modules')->find($id);
        if (empty($moduleent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование модуля',
                'message'=>'Модуль не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к списку модулей'=>$this->get('router')->generate('basic_cms_module_list'))
            ));
        }
        $moduletype = $moduleent->getModuleType();
        $moduleobject = $moduleent->getObjectName();
        // Собственно редактирование модуля
        $em = $this->getDoctrine()->getEntityManager();
        $module = array();
        $moduleerror = array();
        $module['name'] = $moduleent->getName();
        $module['enabled'] = $moduleent->getEnabled();
        $module['layout'] = $moduleent->getLayout();
        $module['position'] = $moduleent->getPosition();
        $module['locale'] = $moduleent->getLocale();
        $module['template'] = $moduleent->getTemplate();
        if (($moduleent->getAccess() == '') || (!is_array(explode(',', $moduleent->getAccess()))))
        {
            $module['accessOn'] = 0;
            $module['access'] = array();
        } else
        {
            $module['accessOn'] = 1;
            $module['access'] = explode(',', $moduleent->getAccess());
        }
        $moduleerror['name'] = '';
        $moduleerror['enabled'] = '';
        $moduleerror['layout'] = '';
        $moduleerror['position'] = '';
        $moduleerror['locale'] = '';
        $moduleerror['template'] = '';
        $moduleerror['accessOn'] = '';
        $moduleerror['access'] = '';
        // Найти роли
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        // Найти страницы
        $pages = array();
        $pages['group'] = array('old');
        $pages['on'] = array();
        $pages['off'] = array();
        $groups = explode(',', $moduleent->getAutoEnable());
        $parameters = @unserialize($moduleent->getParameters());
        // Найти группы страниц
        $seogroups = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) $seogroups[$item] = $this->container->get($item)->getContentTypes();
        // Найти страницы
/*        $query = $em->createQuery('SELECT s.id, s.description, '.
                                  'LOCATE(:moduleid,CONCAT(\',\',CONCAT(s.modules,\',\'))) '.
                                  'as moduleOn FROM BasicCmsBundle:SeoPage s ORDER BY s.contentType, s.contentAction')->setParameter('moduleid',','.$moduleent->getId().',');
        $seopages = $query->getResult();
        $pages = array();
        foreach ($seopages as $seopage)
        {
            if (($seopage['moduleOn'] != 0)) $pages[] = $seopage['id'];
        }*/
        // Найти позиции и лэйауты
        $templates = $this->get('cms.cmsManager')->getModuleTemplateList($moduleobject, $moduletype);
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $positions = array();
        foreach ($layouts as $layoutkey => $layoutname) $positions[$layoutkey] = $this->get('cms.cmsManager')->getPositionList($layoutkey);
        // Найти локали
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        // Валидация
        $activetab = 0;
        $errors = false;
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postmodule = $this->get('request_stack')->getMasterRequest()->get('module');
            if (isset($postmodule['name'])) $module['name'] = $postmodule['name'];
            if (isset($postmodule['enabled'])) $module['enabled'] = intval($postmodule['enabled']); else $module['enabled'] = 0;
            if (isset($postmodule['layout'])) $module['layout'] = $postmodule['layout'];
            if (isset($postmodule['position'][$module['layout']])) $module['position'] = $postmodule['position'][$module['layout']];
            if (isset($postmodule['locale'])) $module['locale'] = $postmodule['locale'];
            if (isset($postmodule['template'])) $module['template'] = $postmodule['template'];
            if (isset($postmodule['accessOn'])) $module['accessOn'] = intval($postmodule['accessOn']);
            $module['access'] = array();
            foreach ($roles as $onerole) if (isset($postmodule['access']) && is_array($postmodule['access']) && (in_array($onerole['id'], $postmodule['access']))) $module['access'][] = $onerole['id'];
            unset($postmodule);
            if (!preg_match("/^[-0-9A-zА-яЁё\s\`]{3,99}$/ui", $module['name'])) {$errors = true; $moduleerror['name'] = 'Имя должно содержать от 3 до 99 букв';}
            if (!isset($layouts[$module['layout']])) {$errors = true; $moduleerror['layout'] = 'Шаблон не найден';}
            if (!isset($positions[$module['layout']][$module['position']])) {$errors = true; $moduleerror['position'] = 'Позиция не найдена';}
            if (($module['template'] != '') && (!isset($templates[$module['template']]))) {$errors = true; $moduleerror['template'] = 'Шаблон не найден';}
            $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $module['locale']) $localecheck = true;}
            if ($localecheck == false) $module['locale'] = '';
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о включении
            $postpages = $this->get('request_stack')->getMasterRequest()->get('pages');
            if (!is_array($postpages)) $postpages = array();
            $pages['group'] = array();
            if (isset($postpages['group']) && is_array($postpages['group']))
            {
                foreach ($seogroups as $objectkey=>$typearray)
                    foreach ($typearray as $typekey=>$typename)
                    {
                        if (in_array($objectkey.'.'.$typekey, $postpages['group'])) $pages['group'][] = $objectkey.'.'.$typekey;
                    }
                if (in_array('all', $postpages['group'])) $pages['group'][] = 'all';
                if (in_array('old', $postpages['group'])) $pages['group'][] = 'old';
            }
            $pages['on'] = array();
            if (isset($postpages['on']) && is_array($postpages['on']))
            {
                foreach ($postpages['on'] as $val)
                {
                    if (preg_match('/^\d+$/ui', $val)) $pages['on'][] = $val;
                }
            }
            $pages['off'] = array();
            if (isset($postpages['off']) && is_array($postpages['off']))
            {
                foreach ($postpages['off'] as $val)
                {
                    if (preg_match('/^\d+$/ui', $val)) $pages['off'][] = $val;
                }
            }
            /*$pages = array();
            foreach ($seopages as $seopage)
            {
                if (in_array($seopage['id'], $postpages)) $pages[] = $seopage['id'];
            }*/
            $postgroups = $this->get('request_stack')->getMasterRequest()->get('groups');
            if (!is_array($postgroups)) $postgroups = array();
            $groups = array();
            foreach ($seogroups as $objectkey=>$typearray)
                foreach ($typearray as $typekey=>$typename)
                {
                    if (in_array($objectkey.'.'.$typekey, $postgroups)) $groups[] = $objectkey.'.'.$typekey;
                }
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Вылидация таба настройки
            $serv = null;
            if ($this->container->has($moduleobject)) $serv = $this->container->get($moduleobject);
            if ($serv !== null) 
            {
                $localerror = $serv->getAdminModuleController($this->getRequest (), $moduletype, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = 3;
            }
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                if ($serv !== null) $parameters = $serv->getAdminModuleController($this->getRequest (), $moduletype, 'parameters', $parameters);
                $moduleent->setEnabled($module['enabled']);
                $moduleent->setAutoEnable(implode(',',$groups));
                $moduleent->setLayout($module['layout']);
                $moduleent->setLocale($module['locale']);
                $moduleent->setName($module['name']);
                $moduleent->setParameters(serialize($parameters));
                $moduleent->setPosition($module['position']);
                $moduleent->setTemplate($module['template']);
                if ($module['accessOn'] != 0) $moduleent->setAccess(implode(',', $module['access'])); else $moduleent->setAccess('');
                $em->flush();
                // Включить страницы
                $x = 0;
                do
                {
                    $query = $em->createQuery('SELECT s FROM BasicCmsBundle:SeoPage s')->setMaxResults(100)->setFirstResult($x * 100);
                    $pagestooff = $query->getResult();
                    if (count($pagestooff) == 0) break;
                    $x++;
                    foreach ($pagestooff as $pagetooff) 
                    {
                        $pagestatus = 0;
                        if (in_array('all', $pages['group'])) $pagestatus = 1;
                        if (in_array($pagetooff->getContentType().'.'.$pagetooff->getContentAction(), $pages['group'])) $pagestatus = 1;
                        if (in_array('old', $pages['group'])) $pagestatus = -1;
                        if (in_array($pagetooff->getId(), $pages['on'])) $pagestatus = 1;
                        if (in_array($pagetooff->getId(), $pages['off'])) $pagestatus = 0;
                        if ($pagestatus != -1)
                        {
                            $modls = explode(',',$pagetooff->getModules());
                            $newmodls = array();
                            foreach ($modls as $item) 
                            {
                                if ($item != $moduleent->getId()) $newmodls[] = $item;
                            }
                            if ($pagestatus == 1) $newmodls[] = $moduleent->getId();
                            $pagetooff->setModules(implode(',',$newmodls));
                        }
                    }
                    $em->flush();    
                    unset($pagestooff);
                } while (0 == 0);
                /*do
                {
                    $query = $em->createQuery('SELECT s FROM BasicCmsBundle:SeoPage s WHERE LOCATE(:moduleid,CONCAT(\',\',CONCAT(s.modules,\',\'))) > 0')->setParameter('moduleid',','.$moduleent->getId().',')->setMaxResults(100);
                    $pagestooff = $query->getResult();
                    if (count($pagestooff) == 0) break;
                    foreach ($pagestooff as $pagetooff) 
                    {
                        $modls = explode(',',$pagetooff->getModules());
                        $newmodls = array();
                        foreach ($modls as $item) 
                        {
                            if ($item != $moduleent->getId()) $newmodls[] = $item;
                        }
                        $pagetooff->setModules(implode(',',$newmodls));
                    }
                    $em->flush();    
                    unset($pagestooff);
                } while (0 == 0);
                if (count($pages) > 0)
                {
                    $query = $em->createQuery('UPDATE BasicCmsBundle:SeoPage s SET s.modules = CONCAT(s.modules,:addstring) WHERE s.id IN (:pages) AND s.modules != \'\'')->setParameter('addstring', ','.$moduleent->getId())->setParameter('pages', $pages);
                    $query->execute();
                    $query = $em->createQuery('UPDATE BasicCmsBundle:SeoPage s SET s.modules = CONCAT(s.modules,:addstring) WHERE s.id IN (:pages) AND s.modules = \'\'')->setParameter('addstring', $moduleent->getId())->setParameter('pages', $pages);
                    $query->execute();
                }*/
                // Сделано
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование модуля',
                    'message'=>'Настройки модуля успешно изменены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_module_edit').'?id='.$id, 'Вернуться к списку модулей'=>$this->get('router')->generate('basic_cms_module_list'))
                ));
            }
        }
        // Взять таб настроек модуля
        $tab = '';
        $serv = null;
        if ($this->container->has($moduleobject)) $serv = $this->container->get($moduleobject);
        if ($serv !== null) $tab = $serv->getAdminModuleController($this->getRequest (), $moduletype, 'tab', $parameters);
        // Отправляем
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Modules:moduleEdit.html.twig', array(
            'module' => $module,
            'moduleerror' => $moduleerror,
            'moduleobject' => $moduleobject,
            'moduletype' => $moduletype,
            'pages' => $pages,
//            'seopages' => $seopages,
            'groups' => $groups,
            'seogroups' => $seogroups,
            'locales' => $locales,
            'templates' => $templates,
            'layouts' => $layouts,
            'positions' => $positions,
            'tab' => $tab,
            'activetab' => $activetab,
            'roles' => $roles,
            'id' => $id
        ));
    }
    
// *******************************************
// AJAX-контроллер
// *******************************************    
    
    public function moduleAjaxAction()
    {
        if ($this->getUser()->checkAccess('module_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Модули',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->get('request_stack')->getMasterRequest()->get('action');
        $errors = array();
        $errorsorder = array();
        if ($action == 'ordering')
        {
            $ordering = $this->get('request_stack')->getMasterRequest()->get('ordering');
            $error = false;
            $ids = array();
            $moduleents = array();
            foreach ($ordering as $key=>$val) 
            {
                $moduleents[$key] = $this->getDoctrine()->getRepository('BasicCmsBundle:Modules')->find($key);
                if (empty($moduleents[$key])) {$error = true; $errorsorder[$key] = 'Модуль не найден';}
                $ids[] = $key;
            }
            if ($error == false)
            {
                foreach ($ordering as $key=>$val)
                {
                    if (!preg_match("/^\d{1,20}$/ui", $val)) {$error = true; $errorsorder[$key] = 'Введено не число';} else
                    {
                        foreach ($ordering as $key2=>$val2) 
                        {
                            if (($key != $key2) && ($val == $val2) && ($moduleents[$key]->getLayout() == $moduleents[$key2]->getLayout()) && ($moduleents[$key]->getPosition() == $moduleents[$key2]->getPosition())) 
                            {
                                $error = true; 
                                $errorsorder[$key] = 'Введено два одинаковых числа';
                            }
                        }
                        if (!isset($errorsorder[$key]))
                        {
                            $query = $em->createQuery('SELECT count(m.id) as checkid FROM BasicCmsBundle:Modules m '.
                                                      'WHERE m.ordering = :order AND m.layout = :layout AND m.position = :position AND m.id NOT IN (:ids)')->setParameter('ids', $ids)->setParameter('order', $val)->setParameter('layout', $moduleents[$key]->getLayout())->setParameter('position', $moduleents[$key]->getPosition());
                            $checkid = $query->getResult();
                            if (isset($checkid[0]['checkid'])) $checkid = $checkid[0]['checkid']; else $checkid = 0;
                            if ($checkid != 0) {$error = true; $errorsorder[$key] = 'Число уже используется';}
                        }
                    }
                }
            }
            // изменить порядок
            if ($error == false)
            {
                foreach ($ordering as $key=>$val)
                {
                    $moduleents[$key]->setOrdering($val);
                }
                $em->flush();
            }
        }
        if ($action == 'delete')
        {
            $check = $this->get('request_stack')->getMasterRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $moduleent = $this->getDoctrine()->getRepository('BasicCmsBundle:Modules')->find($key);
                    if (!empty($moduleent))
                    {
                        if ($this->container->has($moduleent->getObjectName())) $this->container->get($moduleent->getObjectName())->getAdminModuleController($this->get('request_stack')->getMasterRequest(), $moduleent->getModuleType(), 'delete', @unserialize($moduleent->getParameters()));
                        $em->remove($moduleent);
                        $em->flush();
                        unset($moduleent);    
                    }
                }
        }
        if ($action == 'blocked')
        {
            $check = $this->get('request_stack')->getMasterRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $moduleent = $this->getDoctrine()->getRepository('BasicCmsBundle:Modules')->find($key);
                    if (!empty($moduleent))
                    {
                        $moduleent->setEnabled(0);
                        $em->flush();
                        unset($moduleent);    
                    }
                }
        }
        if ($action == 'unblocked')
        {
            $check = $this->get('request_stack')->getMasterRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $moduleent = $this->getDoctrine()->getRepository('BasicCmsBundle:Modules')->find($key);
                    if (!empty($moduleent))
                    {
                        $moduleent->setEnabled(1);
                        $em->flush();
                        unset($moduleent);    
                    }
                }
        }

        $moduletypes = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) $moduletypes[$item] = $this->container->get($item)->getModuleTypes();
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $positions = array();
        foreach ($layouts as $layoutkey => $layoutname) $positions[$layoutkey] = $this->get('cms.cmsManager')->getPositionList($layoutkey);
        $em = $this->getDoctrine()->getEntityManager();
        $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_module_list_page0');
        $page0 = intval($page0);
        // Поиск контента
        $query = $em->createQuery('SELECT count(m.id) as modulecount FROM BasicCmsBundle:Modules m');
        $modulecount = $query->getResult();
        if (!empty($modulecount)) $modulecount = $modulecount[0]['modulecount']; else $modulecount = 0;
        $pagecount0 = ceil($modulecount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT m.id, m.name, m.objectName, m.moduleType, m.position, m.layout, m.enabled, m.ordering, m.template, m.locale FROM BasicCmsBundle:Modules m ORDER BY m.layout, m.position, m.ordering')->setFirstResult($start)->setMaxResults(20);
        $modules = $query->getResult();
        foreach ($modules as &$module)
        {
            if (isset($moduletypes[$module['objectName']][$module['moduleType']])) $module['moduleTypeName'] = $moduletypes[$module['objectName']][$module['moduleType']]; else $module['moduleTypeName'] = 'Неизвестный';
            if (isset($positions[$module['layout']][$module['position']])) $module['positionName'] = $positions[$module['layout']][$module['position']]; else $module['positionName'] = 'Неизвестная';
            $templates = $this->get('cms.cmsManager')->getModuleTemplateList($module['objectName'], $module['moduleType']);
            if (isset($templates[$module['template']])) $module['templateName'] = $templates[$module['template']]; else $module['templateName'] = 'По умолчанию';
            if (($action == 'ordering') && (isset($ordering[$module['id']]))) $module['ordering'] = $ordering[$module['id']];
        }
        return $this->render('BasicCmsBundle:Modules:moduleListTab1.html.twig', array(
            'modules' => $modules,
            'page0' => $page0,
            'pagecount0' => $pagecount0,
            'errors' => $errors,
            'errorsorder' => $errorsorder
        ));
    }

// *******************************************
// AJAX-контроллер
// *******************************************    
    
    public function moduleAjaxMenuPagesAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $search = trim($this->get('request_stack')->getMasterRequest()->get('search'));
        $page = intval($this->get('request_stack')->getMasterRequest()->get('page'));
        if ($page < 0) $page = 0;
        $query = $em->createQuery('SELECT count(s.id) as pagecount FROM BasicCmsBundle:SeoPage s WHERE s.description LIKE :search OR s.url LIKE :search')
                    ->setParameter('search', '%'.$search.'%');
        $pagecount = $query->getResult();
        if (isset($pagecount[0]['pagecount'])) $pagecount = ceil(intval($pagecount[0]['pagecount'])/20); else $pagecount = 0;
        if ($pagecount < 1) $pagecount = 1;
        if ($page >= $pagecount) $page = $pagecount - 1;
        $query = $em->createQuery('SELECT s.contentType, s.contentAction, s.contentId, s.url, s.description FROM BasicCmsBundle:SeoPage s WHERE s.description LIKE :search OR s.url LIKE :search')
                    ->setParameter('search', '%'.$search.'%')->setFirstResult($page * 20)->setMaxResults(20);
        $pages = $query->getResult();
        if (!is_array($pages)) $pages = array();
        return new Response(json_encode(array('page'=>$page, 'pagecount'=>$pagecount, 'pages'=>$pages)));
    }

// *******************************************
// AJAX-контроллер загрузки страниц в выборе
// *******************************************    
    
    public function moduleAjaxSeoPagesAction()
    {
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) $seogroups[$item] = $this->container->get($item)->getContentTypes();
        $em = $this->getDoctrine()->getEntityManager();
        $search = trim($this->get('request_stack')->getMasterRequest()->get('search'));
        $page = intval($this->get('request_stack')->getMasterRequest()->get('page'));
        $pages = array();
        $postpages = $this->get('request_stack')->getMasterRequest()->get('pages');
        if (!is_array($postpages)) $postpages = array();
        $pages['group'] = array();
        if (isset($postpages['group']) && is_array($postpages['group']))
        {
            foreach ($seogroups as $objectkey=>$typearray)
                foreach ($typearray as $typekey=>$typename)
                {
                    if (in_array($objectkey.'.'.$typekey, $postpages['group'])) $pages['group'][] = $objectkey.'.'.$typekey;
                }
            if (in_array('all', $postpages['group'])) $pages['group'][] = 'all';
            if (in_array('old', $postpages['group'])) $pages['group'][] = 'old';
        }
        $pages['on'] = array();
        if (isset($postpages['on']) && is_array($postpages['on']))
        {
            foreach ($postpages['on'] as $val)
            {
                if (preg_match('/^\d+$/ui', $val)) $pages['on'][] = $val;
            }
        }
        $pages['off'] = array();
        if (isset($postpages['off']) && is_array($postpages['off']))
        {
            foreach ($postpages['off'] as $val)
            {
                if (preg_match('/^\d+$/ui', $val)) $pages['off'][] = $val;
            }
        }
        $pages['group'][] = 'empty';
        $pages['on'][] = 0;
        $pages['off'][] = 0;
        $moduleid = $this->get('request_stack')->getMasterRequest()->get('moduleid');
        if ($page < 0) $page = 0;
        $query = $em->createQuery('SELECT count(s.id) as pagecount FROM BasicCmsBundle:SeoPage s WHERE s.description LIKE :search OR s.url LIKE :search')
                    ->setParameter('search', '%'.$search.'%');
        $pagecount = $query->getResult();
        if (isset($pagecount[0]['pagecount'])) $pagecount = ceil(intval($pagecount[0]['pagecount'])/20); else $pagecount = 0;
        if ($pagecount < 1) $pagecount = 1;
        if ($page >= $pagecount) $page = $pagecount - 1;
        $query = $em->createQuery('SELECT s.id, s.contentType, s.contentAction, s.url, s.description, s.modules FROM BasicCmsBundle:SeoPage s WHERE s.description LIKE :search OR s.url LIKE :search')
                    ->setParameter('search', '%'.$search.'%')->setFirstResult($page * 20)->setMaxResults(20);
        $seopages = $query->getResult();
        if (!is_array($seopages)) $seopages = array();
        // устанавливаем чек-боксы
        foreach ($seopages as &$item)
        {
            $checkbox = 0;
            if (in_array('all', $pages['group'])) $checkbox = 1;
            if (in_array($item['contentType'].'.'.$item['contentAction'], $pages['group'])) $checkbox = 1;
            if (in_array('old', $pages['group'])) $checkbox = (strpos(','.$item['modules'].',', ','.$moduleid.',') !== false ? 1 : 0);
            if (in_array($item['id'], $pages['on'])) $checkbox = 1;
            if (in_array($item['id'], $pages['off'])) $checkbox = 0;
            $item['checkbox'] = $checkbox;
        }
        // подсчитываем общее количество страниц
        $query = $em->createQuery('SELECT count(s.id) as allcount FROM BasicCmsBundle:SeoPage s');
        $allcount = $query->getResult();
        if (isset($allcount[0]['allcount'])) $allcount = intval($allcount[0]['allcount']); else $allcount = 0;
        // подсчитываем количество выбранных страниц
        if (in_array('all', $pages['group'])) 
        {
            $query = $em->createQuery('SELECT count(s.id) as checkcount FROM BasicCmsBundle:SeoPage s WHERE s.id NOT IN (:idsoff)')->setParameter('idsoff', $pages['off']);
            $checkcount = $query->getResult();
            if (isset($checkcount[0]['checkcount'])) $checkcount = intval($checkcount[0]['checkcount']); else $checkcount = 0;
        } elseif (in_array('old', $pages['group'])) 
        {
            $query = $em->createQuery('SELECT count(s.id) as checkcount FROM BasicCmsBundle:SeoPage s WHERE (LOCATE(:moduleid,CONCAT(\',\',CONCAT(s.modules,\',\'))) > 0 AND s.id NOT IN (:idsoff)) OR s.id IN (:idson)')->setParameter('idsoff', $pages['off'])->setParameter('idson', $pages['on'])->setParameter('moduleid', ','.$moduleid.',');
            $checkcount = $query->getResult();
            if (isset($checkcount[0]['checkcount'])) $checkcount = intval($checkcount[0]['checkcount']); else $checkcount = 0;
        } else
        {
            $query = $em->createQuery('SELECT count(s.id) as checkcount FROM BasicCmsBundle:SeoPage s WHERE (CONCAT(s.contentType,CONCAT(\'.\', s.contentAction)) IN (:types) AND s.id NOT IN (:idsoff)) OR s.id IN (:idson)')->setParameter('idsoff', $pages['off'])->setParameter('idson', $pages['on'])->setParameter('types', $pages['group']);
            $checkcount = $query->getResult();
            if (isset($checkcount[0]['checkcount'])) $checkcount = intval($checkcount[0]['checkcount']); else $checkcount = 0;
        }
        return new Response(json_encode(array('page'=>$page, 'pagecount'=>$pagecount, 'pages'=>$seopages, 'allcount'=>$allcount, 'checkcount'=>$checkcount)));
    }
 
// *******************************************
// AJAX-контроллер загрузки изображения баннера
// *******************************************    
    
    public function moduleAjaxBannerAction() 
    {
        $userId = $this->getUser()->getId();
        $file = $this->get('request_stack')->getMasterRequest()->files->get('banner');
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
            $basepath = '/images/banner/';
            $name = $this->getUser()->getId().'_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
            if (move_uploaded_file($tmpfile, '.'.$basepath.$name)) 
            {
                $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $file->getClientOriginalName());
                return new Response(json_encode(array('file' => $basepath.$name, 'error' => '')));
            }
        }
        return new Response(json_encode(array('file' => '', 'error' => 'Неправильный формат файла')));
        
        
    }
    
    
    
    
}
