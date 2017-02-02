<?php

namespace Basic\CmsBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class ModuleManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'object.module';
    }
    
    public function getDescription()
    {
        return 'Управление модулями';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Модули', $this->container->get('router')->generate('basic_cms_module_list'), 100, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('module_list'));
        $manager->addAdminMenu('Создать новый', $this->container->get('router')->generate('basic_cms_module_new'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('module_new'), 'Модули');
        $manager->addAdminMenu('Список модулей', $this->container->get('router')->generate('basic_cms_module_list'), 1, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('module_list'), 'Модули');
        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.module.') === 0) $this->container->get($item)->registerMenu();

    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('module_list','Просмотр списка модулей');
        $manager->addRole('module_new','Создание нового модуля');
        $manager->addRole('module_edit','Редактирование модуля');

        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.module.') === 0) $this->container->get($item)->registerRoles();
        
     }
     
     public function getContentTypes()
     {
        $contents = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.module.') === 0) $this->container->get($item)->getContentTypes($contents);
        return $contents;
     }
     
     public function getTaxonomyType()
     {
         return false;
     }

     public function getTemplateTwig($contentType, $template)
     {
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.module.') === 0) 
         {
             $result = $this->container->get($item)->getTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         if ($contentType == 'custom_html') return 'BasicCmsBundle:Front:modulehtml.html.twig';
         if ($contentType == 'menu') return 'BasicCmsBundle:Front:modulemenu.html.twig';
         if ($contentType == 'breadcrumbs') return 'BasicCmsBundle:Front:modulebreadcrumbs.html.twig';
         if ($contentType == 'banner') return 'BasicCmsBundle:Front:modulebanner.html.twig';
         if ($contentType == 'locale') return 'BasicCmsBundle:Front:modulelocale.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.module.') === 0) 
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
         foreach ($cmsservices as $item) if (strpos($item,'addone.module.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
         return $params;
     }
     
     public function setFrontAction($locale, $seoPage, $action)
     {
         $result = null;
         if ($action == 'changelocale')
         {
             $getlocale = $this->container->get('request_stack')->getCurrentRequest()->get('actionlocale');
             $result['action'] = 'object.module.changelocale';
             $result['locale'] = $getlocale;
             if ($getlocale == 'default')
             {
                 $result['status'] = 'OK';
                 $this->container->get('cms.cmsManager')->addCookie(new \Symfony\Component\HttpFoundation\Cookie('front_locale_current', $getlocale, (time() + 3600 * 24 * 365), $path = '/'));
             } else
             {
                 $getlocaleent = $this->container->get('doctrine')->getRepository('BasicCmsBundle:Locales')->findOneBy(array('shortName'=>$getlocale));
                 if (!empty($getlocaleent)) 
                 {
                     $result['status'] = 'OK';
                     $this->container->get('cms.cmsManager')->addCookie(new \Symfony\Component\HttpFoundation\Cookie('front_locale_current', $getlocale, (time() + 3600 * 24 * 365), $path = '/'));
                 } else $result['status'] = 'Not found';
             }
         }
         
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.module.') === 0) $this->container->get($item)->setFrontAction($locale, $seoPage, $action, $result);
         return $result;
     }
     
     public function getModuleTypes()
     {
         $contents = array('custom_html'=>'Модуль с пользовательской вёрсткой', 'menu'=>'Модуль меню', 'breadcrumbs'=>'Модуль хлебных крошек', 'banner'=>'Модуль баннеров/слайдеров', 'locale'=>'Модуль смены языка');
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.module.') === 0) $this->container->get($item)->getModuleTypes($contents);
         return $contents;
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result = null)
     {
         $em = $this->container->get('doctrine')->getEntityManager();
         $conn = $em->getConnection();
         $params = null;
         if ($moduleType == 'custom_html')
         {
             $params = array();
             $params['html'] = '';
             if (isset($parameters['html'])) $params['html'] = $parameters['html'];
             if (isset($parameters['variables']) && is_array($parameters['variables']))
             {
                 foreach ($parameters['variables'] as $variable)
                 {
                     $variable['name'] = str_replace(array('\\','^','.','$','|','(',')','[',']','*','+','?','{','}',','), array('\\\\','\^','\.','\$','\|','\(','\)','\[','\]','\*','\+','\?','\{','\}','\,'), $variable['name']);
                     $params['html'] = preg_replace('/\{\{'.$variable['name'].'(\?\?[A-z0-9А-яЁё\s\?\|\'!@"№#\$;%:\^&\*\(\)\.,=-_\+]+)?\}\}/', $variable['value'], $params['html']);
                     if (isset($variable['name']) && isset($variable['value'])) $parameters['variables'][] = array('name'=>$variable['name'], 'value'=>$variable['value']);
                 }
             }
         }
         if ($moduleType == 'menu')
         {
             $breadcrumbs = $this->container->get('cms.cmsManager')->getBreadCrumbs($seoPage, $locale);
             $params = array();
             $params['items'] = array();
             if (isset($parameters['items']) && (is_array($parameters['items']))) 
             {
                 $pagesearch = '';
                 foreach ($parameters['items'] as $item)
                 {
                     if (isset($item['nesting']) && isset($item['enabled']) && isset($item['type']) && isset($item['object']) && isset($item['action']) && isset($item['contentId']) && isset($item['url']) && isset($item['name']))
                     {
                         if ($item['type'] == 1)
                         {
                             if ($pagesearch != '') $pagesearch .= ' OR ';
                             $pagesearch .= '(s.contentType ='.$conn->quote($item['object']).' AND s.contentAction = '.$conn->quote($item['action']).' AND s.contentId = '.intval($item['contentId']).')';
                         }
                     }
                 }
                 $pages = array();
                 if ($pagesearch != '')
                 {
                     $query = $em->createQuery('SELECT s.id, s.contentType, s.contentAction, s.contentId, s.url FROM BasicCmsBundle:SeoPage s WHERE '.$pagesearch);
                     $pages = $query->getResult();
                     if (!is_array($pages)) $pages = array();
                 }
                 foreach ($parameters['items'] as $item)
                 {
                     if (isset($item['nesting']) && isset($item['enabled']) && isset($item['type']) && isset($item['object']) && isset($item['action']) && isset($item['contentId']) && isset($item['url']) && isset($item['name']))
                     {
                         $active = 0;
                         if ($item['type'] == 1)
                         {
                             $url = '';
                             foreach ($pages as $page)
                             {
                                 if (($page['contentType'] == $item['object']) && ($page['contentAction'] == $item['action']) && ($page['contentId'] == $item['contentId'])) 
                                 {
                                     $url = '/'.($locale != '' ? $locale.'/' : '').$page['url'];
                                     foreach ($breadcrumbs as $breadcrumb) if ($breadcrumb['id'] == $page['id']) $active = 1;
                                 }
                             }
                             if ($url != '') $item['url'] = $url;
                         }
                         $params['items'][] = array('active'=>$active,'nesting'=>$item['nesting'], 'enabled'=>$item['enabled'], 'name'=>$this->container->get('cms.cmsManager')->decodeLocalString($item['name'], $locale, true), 'url'=>$item['url']);
                     }
                 }
             }
         }
         if ($moduleType == 'breadcrumbs')
         {
             $params = array();
             $params['breadcrumbs'] = $this->container->get('cms.cmsManager')->getBreadCrumbs($seoPage, $locale);
             foreach ($params['breadcrumbs'] as &$breaditem) 
             {
                 if (isset($breaditem['url']) && ($breaditem['url'] != '')) $breaditem['url'] = '/'.($locale != '' ? $locale.'/' : '').$breaditem['url'];
             }
         }
         if ($moduleType == 'banner')
         {
             $params = array();
             $params['banners'] = array();
             if (isset($parameters['banners']) && (is_array($parameters['banners']))) 
             {
                 $pagesearch = '';
                 foreach ($parameters['banners'] as $item)
                 {
                     if (isset($item['image']) && isset($item['enabled']) && isset($item['type']) && isset($item['object']) && isset($item['action']) && isset($item['contentId']) && isset($item['url']) && isset($item['name']) && isset($item['description']))
                     {
                         if ($item['type'] == 1)
                         {
                             if ($pagesearch != '') $pagesearch .= ' OR ';
                             $pagesearch .= '(s.contentType ='.$conn->quote($item['object']).' AND s.contentAction = '.$conn->quote($item['action']).' AND s.contentId = '.intval($item['contentId']).')';
                         }
                     }
                 }
                 $pages = array();
                 if ($pagesearch != '')
                 {
                     $query = $em->createQuery('SELECT s.id, s.contentType, s.contentAction, s.contentId, s.url FROM BasicCmsBundle:SeoPage s WHERE '.$pagesearch);
                     $pages = $query->getResult();
                     if (!is_array($pages)) $pages = array();
                 }
                 foreach ($parameters['banners'] as $item)
                 {
                     if (isset($item['image']) && isset($item['enabled']) && isset($item['type']) && isset($item['object']) && isset($item['action']) && isset($item['contentId']) && isset($item['url']) && isset($item['name']) && isset($item['description']))
                     {
                         if ($item['type'] == 1)
                         {
                             $url = '';
                             foreach ($pages as $page)
                             {
                                 if (($page['contentType'] == $item['object']) && ($page['contentAction'] == $item['action']) && ($page['contentId'] == $item['contentId'])) 
                                 {
                                     $url = '/'.($locale != '' ? $locale.'/' : '').$page['url'];
                                 }
                             }
                             if ($url != '') $item['url'] = $url;
                         }
                         $params['banners'][] = array('image'=>$item['image'], 'enabled'=>$item['enabled'], 'name'=>$this->container->get('cms.cmsManager')->decodeLocalString($item['name'], $locale, true), 'description'=>$this->container->get('cms.cmsManager')->decodeLocalString($item['description'], $locale, true), 'url'=>$item['url']);
                     }
                 }
             }
         }
         if ($moduleType == 'locale')
         {
             $params = array();
             $params['locales'] = array();
             if (isset($parameters['defaultname'])) $params['locales'][] = array('shortName' => 'default', 'fullName' => $parameters['defaultname'], 'active' => ($locale == '' ? 1 : 0)); else  $params['locales'][] = array('shortName' => 'default', 'fullName' => 'Default', 'active' => ($locale == '' ? 1 : 0));
             $query = $em->createQuery('SELECT l.id, l.shortName, l.fullName FROM BasicCmsBundle:Locales l ORDER BY l.id');
             $locales = $query->getResult();
             foreach ($locales as $onelocale)
             {
                 $params['locales'][] = array('shortName' => $onelocale['shortName'], 'fullName' => $onelocale['fullName'], 'active' => ($locale == $onelocale['shortName'] ? 1 : 0));
             }
         }
         if ($params != null) return $params;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             if (strpos($item,'addone.module.') === 0) $params = $this->container->get($item)->getFrontModule($locale, $seoPage, $moduleType, $parameters, $result);
             if ($params != null) return $params;
         }
         return null;
     }
     
    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        if ($module == 'custom_html')
        {
            $parameters = array();
            $parameters['html'] = '';
            $parameters['variables'] = '';
            if (isset($nullparameters['html'])) $parameters['html'] = $nullparameters['html'];
            if (isset($nullparameters['variables']) && is_array($nullparameters['variables'])) $parameters['variables'] = $nullparameters['variables'];
            $parameterserror['html'] = '';
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postparameters = $request->get('parameters');
                if (isset($postparameters['html'])) $parameters['html'] = $postparameters['html'];
                if (isset($postparameters['variables']) && is_array($postparameters['variables']))
                {
                    foreach ($postparameters['variables'] as $variable)
                    {
                        if (isset($variable['name']) && isset($variable['value'])) $parameters['variables'][] = array('name'=>$variable['name'], 'value'=>$variable['value']);
                    }
                }
                if (strlen($parameters['html']) < 3) {$errors = true; $parameterserror['html'] = 'Текст должен содержать не менее 3 символов';}
            }
            if ($actionType == 'validate')
            {
                return $errors;
            }
            if ($actionType == 'parameters')
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($parameters['html'], (isset($nullparameters['html']) ? $nullparameters['html'] : ''));
                return $parameters;
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('BasicCmsBundle:Modules:basicHtml.html.twig',array('parameters' => $parameters,'parameterserror' => $parameterserror));
            }
        }
        if ($module == 'menu')
        {
            $parameters = array();
            $parameters['items'] = array();
            if (isset($nullparameters['items']) && (is_array($nullparameters['items']))) $parameters['items'] = $nullparameters['items'];
            $parameterserror['items'] = '';
            $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
            $locales = $query->getResult();
            if (!is_array($locales)) $locales = array();
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postparameters = $request->get('parameters');
                if (isset($postparameters['items']) && (is_array($postparameters['items']))) 
                {
                    $parameters['items'] = array();
                    foreach ($postparameters['items'] as $item)
                    {
                        if (isset($item['nesting']) && isset($item['enabled']) && isset($item['type']) && isset($item['object']) && isset($item['action']) && isset($item['contentId']) && isset($item['url']) && isset($item['name']))
                        {
                            // Сложить локали
                            $item['name'] = $this->container->get('cms.cmsManager')->encodeLocalString($item['name']);
                            $parameters['items'][] = array('nesting'=>$item['nesting'],'enabled'=>$item['enabled'],'type'=>$item['type'],'object'=>$item['object'],'action'=>$item['action'],'contentId'=>$item['contentId'],'url'=>$item['url'],'name'=>$item['name']);
                        }
                    }
                }
                foreach ($parameters['items'] as &$item)
                {
                    $item['contentName'] = '';
                    $item['error'] = '';
                    if ($item['type'] == 0)
                    {
                        if (!preg_match("/^[-_A-z0-9\?\:\;\+\=\@\#\$\%\&\~\.\/\\\\]{1,999}$/ui", $item['url'])) {$errors = true; $item['error'] = 'URL должен содержать от 1 до 999 разрешенных символов';}
                    } else
                    {
                        $page = $em->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>$item['object'],'contentAction'=>$item['action'],'contentId'=>$item['contentId']));
                        if (empty($page)) {$errors = true; $item['error'] = 'Страница не найдена';} else {$item['url'] = $page->getUrl(); $item['contentName'] = $page->getDescription();}
                        unset($page);
                    }
                    // Разложить локали и проверить
                    if (!preg_match("/^[-\s_A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\/\\\\,]{3,999}$/ui", $this->container->get('cms.cmsManager')->decodeLocalString($item['name'],'default',false))) {$errors = true; $item['error'] = 'Имя должно содержать от 3 до 999 букв';}
                    foreach ($locales as $locale)
                    {
                        if (!preg_match("/^([-\s_A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\/\\\\,]{3,999})?$/ui", $this->container->get('cms.cmsManager')->decodeLocalString($item['name'],$locale['shortName'],false))) {$errors = true; $item['error'] = 'Имя должно содержать от 3 до 999 букв';}
                    }
                }
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
                // Выбрать локали
                foreach ($parameters['items'] as &$item)
                {
                    $name = $item['name'];
                    $item['name'] = array();
                    $item['name']['default'] = $this->container->get('cms.cmsManager')->decodeLocalString($name,'default',false);
                    foreach ($locales as $locale)
                    {
                        $item['name'][$locale['shortName']] = $this->container->get('cms.cmsManager')->decodeLocalString($name,$locale['shortName'],false);
                    }
                }
                return $this->container->get('templating')->render('BasicCmsBundle:Modules:menu.html.twig',array('parameters' => $parameters,'parameterserror' => $parameterserror,'locales'=>$locales));
            }
        }
        if ($module == 'breadcrumbs')
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
                return $this->container->get('templating')->render('BasicCmsBundle:Modules:breadCrumbs.html.twig',array());
            }
        }
        if ($module == 'banner')
        {
            $parameters = array();
            $parameters['banners'] = array();
            if (isset($nullparameters['banners']) && (is_array($nullparameters['banners']))) $parameters['banners'] = $nullparameters['banners'];
            $parameterserror['banners'] = '';
            $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
            $locales = $query->getResult();
            if (!is_array($locales)) $locales = array();
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postparameters = $request->get('parameters');
                if (isset($postparameters['banners']) && (is_array($postparameters['banners']))) 
                {
                    $parameters['banners'] = array();
                    foreach ($postparameters['banners'] as $item)
                    {
                        if (isset($item['image']) && isset($item['enabled']) && isset($item['type']) && isset($item['object']) && isset($item['action']) && isset($item['contentId']) && isset($item['url']) && isset($item['name']) && isset($item['description']))
                        {
                            // Сложить локали
                            $item['name'] = $this->container->get('cms.cmsManager')->encodeLocalString($item['name']);
                            $item['description'] = $this->container->get('cms.cmsManager')->encodeLocalString($item['description']);
                            $parameters['banners'][] = array('image'=>$item['image'],'enabled'=>$item['enabled'],'type'=>$item['type'],'object'=>$item['object'],'action'=>$item['action'],'contentId'=>$item['contentId'],'url'=>$item['url'],'name'=>$item['name'],'description'=>$item['description']);
                        }
                    }
                }
                foreach ($parameters['banners'] as &$item)
                {
                    $item['contentName'] = '';
                    $item['error'] = '';
                    if ($item['type'] == 0)
                    {
                        if (!preg_match("/^[-_A-z0-9\?\:\;\+\=\@\#\$\%\&\~\.\/\\\\]{1,999}$/ui", $item['url'])) {$errors = true; $item['error'] = 'URL должен содержать от 1 до 999 разрешенных символов';}
                    } else
                    {
                        $page = $em->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>$item['object'],'contentAction'=>$item['action'],'contentId'=>$item['contentId']));
                        if (empty($page)) {$errors = true; $item['error'] = 'Страница не найдена';} else {$item['url'] = '/'.$page->getUrl(); $item['contentName'] = $page->getDescription();}
                        unset($page);
                    }
                    // Разложить локали и проверить
                    if (!preg_match("/^[-\s_A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\/\\\\,]{0,999}$/ui", $this->container->get('cms.cmsManager')->decodeLocalString($item['name'],'default',false))) {$errors = true; $item['error'] = 'Имя должно содержать до 999 букв';}
                    if (!preg_match("/^[\s\S]{0,65535}$/ui", $this->container->get('cms.cmsManager')->decodeLocalString($item['description'],'default',false))) {$errors = true; $item['error'] = 'Описание должно содержать до 65535 букв';}
                    foreach ($locales as $locale)
                    {
                        if (!preg_match("/^[-\s_A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\/\\\\,]{0,999}$/ui", $this->container->get('cms.cmsManager')->decodeLocalString($item['name'],$locale['shortName'],false))) {$errors = true; $item['error'] = 'Имя должно содержать до 999 букв';}
                        if (!preg_match("/^[\s\S]{0,65535}$/ui", $this->container->get('cms.cmsManager')->decodeLocalString($item['description'],$locale['shortName'],false))) {$errors = true; $item['error'] = 'Описание должно содержать до 65535 букв';}
                    }
                    if (!file_exists('.'.$item['image'])) {$errors = true; $item['error'] = 'Изображение не найдено';}
                }
            }
            if ($actionType == 'validate')
            {
                return $errors;
            }
            if ($actionType == 'parameters')
            {
                if (isset($nullparameters['banners']) && (is_array($nullparameters['banners']))) 
                {
                    foreach ($nullparameters['banners'] as $oldbanner)
                        if (isset($oldbanner['image']) && ($oldbanner['image'] != ''))
                        {
                            $finded = false;
                            foreach ($parameters['banners'] as $banner) if ($banner['image'] == $oldbanner['image']) $finded = true;
                            if ($finded == false) @unlink('.'.$oldbanner['image']);
                        }
                }
                foreach ($parameters['banners'] as $banner) $this->container->get('cms.cmsManager')->unlockTemporaryFile($banner['image']);
                return $parameters;
            }
            if ($actionType == 'delete')
            {
                if (isset($nullparameters['banners']) && (is_array($nullparameters['banners']))) 
                {
                    foreach ($nullparameters['banners'] as $oldbanner)
                        if (isset($oldbanner['image']) && ($oldbanner['image'] != '')) @unlink('.'.$oldbanner['image']);
                }
            }
            if ($actionType == 'tab')
            {
                // Выбрать локали
                foreach ($parameters['banners'] as &$item)
                {
                    $name = $item['name'];
                    $item['name'] = array();
                    $item['name']['default'] = $this->container->get('cms.cmsManager')->decodeLocalString($name,'default',false);
                    $description = $item['description'];
                    $item['description'] = array();
                    $item['description']['default'] = $this->container->get('cms.cmsManager')->decodeLocalString($description,'default',false);
                    foreach ($locales as $locale)
                    {
                        $item['name'][$locale['shortName']] = $this->container->get('cms.cmsManager')->decodeLocalString($name,$locale['shortName'],false);
                        $item['description'][$locale['shortName']] = $this->container->get('cms.cmsManager')->decodeLocalString($description,$locale['shortName'],false);
                    }
                }
                return $this->container->get('templating')->render('BasicCmsBundle:Modules:banners.html.twig',array('parameters' => $parameters,'parameterserror' => $parameterserror,'locales'=>$locales));
            }
        }
        if ($module == 'locale')
        {
            $parameters = array();
            $parameters['defaultname'] = '';
            if (isset($nullparameters['defaultname'])) $parameters['defaultname'] = $nullparameters['defaultname'];
            $parameterserror['defaultname'] = '';
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postparameters = $request->get('parameters');
                if (isset($postparameters['defaultname'])) $parameters['defaultname'] = $postparameters['defaultname'];
                if (!preg_match("/^[-0-9A-zА-яЁё\s\`]{3,99}$/ui", $parameters['defaultname'])) {$errors = true; $parameterserror['defaultname'] = 'Название должно содержать от 3 до 99 букв';}
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
                return $this->container->get('templating')->render('BasicCmsBundle:Modules:locale.html.twig',array('parameters' => $parameters,'parameterserror' => $parameterserror));
            }
        }
        $answer = null;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.module.') === 0) $answer = $this->container->get($item)->getAdminModuleController($request, $module, $actionType, $nullparameters);
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
             if (strpos($item,'addone.module.') === 0) $answer = $this->container->get($item)->getInfoTitle($contentType, $contentId);
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
             if (strpos($item,'addone.module.') === 0) $answer = $this->container->get($item)->getInfoSitemap($contentType, $contentId);
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
            if (strpos($item,'addone.module.') === 0) $this->container->get($item)->getSearchOptions($options);
        }
    }
    
    public function getSearchItemCount($options, $searchstring, $locale)
    {
        $count = 0;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.module.') === 0) $count += $this->container->get($item)->getSearchItemCount($options, $searchstring, $locale);
        }
        return $count;
    }
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, &$items)
    {
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.module.') === 0) $this->container->get($item)->getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, $items);
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
            if (strpos($item,'addone.module.') === 0) $this->container->get($item)->getHelpChapters($chapters);
        }
        return $chapters;
    }
    
    public function getHelpContent($page, &$answer)
    {
        //if ($page == 'd1537bd0b66a4289001629ac2e06e742') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:index.html.twig',array());
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.module.') === 0) $answer .= $this->container->get($item)->getHelpContent($page);
        }
        return $answer;
    }
    
}
