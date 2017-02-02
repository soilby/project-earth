<?php

namespace Form\FormBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class FormManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'object.form';
    }
    
    public function getDescription()
    {
        return 'Формы';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Формы', $this->container->get('router')->generate('form_form_list'), 5, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('form_list') | $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('form_letterlist'));
        $manager->addAdminMenu('Создать форму', $this->container->get('router')->generate('form_form_create'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('form_new'), 'Формы');
        $manager->addAdminMenu('Список форм', $this->container->get('router')->generate('form_form_list'), 10, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('form_list') | $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('form_letterlist'), 'Формы');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) $this->container->get($item)->registerMenu();

    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('form_list','Просмотр списка форм');
        $manager->addRole('form_viewown','Просмотр собственных форм');
        $manager->addRole('form_viewall','Просмотр всех форм');
        $manager->addRole('form_new','Создание новых форм');
        $manager->addRole('form_editown','Редактирование собственных форм');
        $manager->addRole('form_editall','Редактирование всех форм');
        $manager->addRole('form_letterlist','Просмотр списка писем от форм');
        $manager->addRole('form_letterviewown','Просмотр писем от собственных форм');
        $manager->addRole('form_letterviewall','Просмотр писем от всех форм');
        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) $this->container->get($item)->registerRoles();
        
     }
     
     public function getContentTypes()
     {
        $contents = array('view' => 'Просмотр формы');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) $this->container->get($item)->getContentTypes($contents);
        return $contents;
     }
     
     public function getTaxonomyType()
     {
         return false;
     }
     
     public function getTemplateTwig($contentType, $template)
     {
         if ($contentType == 'view') return 'FormFormBundle:Front:formview.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) 
         {
             $result = $this->container->get($item)->getTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         if ($contentType == 'moduleform') return 'FormFormBundle:Front:moduleform.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) 
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
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT f.id, f.title, f.template, f.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, f.captchaEnabled FROM FormFormBundle:Forms f '.
                                       'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.
                                       'WHERE f.id = :id AND f.enabled != 0')->setParameter('id', $contentId);
             $form = $query->getResult();
             if (empty($form)) return null;
             if (isset($form[0])) $params = $form[0]; else return null;
             $params['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($params['title'], $locale, true);
             $params['fields'] = array();
             if (isset($result['action']) && ($result['action'] == 'object.form.sendform') && isset($result['actionId']) && ($result['actionId'] == $contentId))
             {
                 $params['status'] = 'get';
                 $getvalues = true;
                 if ($result['status'] == 'OK') {$params['status'] = 'OK'; $getvalues = false;}
             } else 
             {
                 $params['status'] = '';
                 $getvalues = false;
             }
             if ($params['captchaEnabled'] != 0)
             {
                 $params['captchaPath'] = $this->container->get('router')->generate('basic_cms_captcha').'?target=form'.$params['id'];
                 $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_form'.$params['id'], '');
                 $params['captchaError'] = '';
                 if ($getvalues == true) $params['captchaError'] = (isset($result['errors']['captcha']) ? $result['errors']['captcha'] : '');
             }
             $query = $em->createQuery('SELECT f.id, f.name, f.type, f.nesting, f.required, f.parameters FROM FormFormBundle:FormFields f WHERE f.formId = :id AND f.enabled != 0 ORDER BY f.ordering')->setParameter('id', $contentId);
             $fields = $query->getResult();
             if (is_array($fields))
             {
                 foreach ($fields as $field)
                 {
                     $newitem = array();
                     $newitem['id'] = $field['id'];
                     $newitem['nesting'] = $field['nesting'];
                     $newitem['required'] = $field['required'];
                     $newitem['name'] = $this->container->get('cms.cmsManager')->decodeLocalString($field['name'], $locale, true);
                     $newitem['type'] = $field['type'];
                     if ($getvalues == true)
                     {
                         $newitem['value'] = (isset($result['values'][$field['id']]) ? $result['values'][$field['id']] : '');
                         $newitem['error'] = (isset($result['errors'][$field['id']]) ? $result['errors'][$field['id']] : '');
                     } else 
                     {
                         $newitem['value'] = '';
                         $newitem['error'] = '';
                     }
                     if (($field['type'] == 'text') || ($field['type'] == 'textarea'))
                     {
                         $newitem['regularExp'] = $this->container->get('cms.cmsManager')->decodeLocalString($field['parameters'], $locale, true);
                         $params['fields'][] = $newitem;
                     }
                     if (($field['type'] == 'radio') || ($field['type'] == 'select'))
                     {
                         $newitem['items'] = array();
                         $lines = preg_split('/\\r\\n?|\\n/', $this->container->get('cms.cmsManager')->decodeLocalString($field['parameters'], $locale, true));
                         if (is_array($lines))
                         {
                             foreach ($lines as $line) if (trim($line) != '') $newitem['items'][] = trim($line);
                         }
                         if (count($newitem['items']) > 0) $params['fields'][] = $newitem;
                     }
                     if (($field['type'] == 'checkbox') || ($field['type'] == 'group'))
                     {
                         $params['fields'][] = $newitem;
                     }
                     unset($newitem);
                 }
             }
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         $params = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
         return $params;
     }
     
     public function setFrontAction($locale, $seoPage, $action)
     {
         $result = null;
         if ($action == 'sendform')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             $query = $em->createQuery('SELECT f.id, f.sendEmail, f.email, f.title, f.captchaEnabled FROM FormFormBundle:Forms f '.
                                       'WHERE f.id = :id AND f.enabled != 0')->setParameter('id', $id);
             $form = $query->getResult();
             if (isset($form[0])) 
             {
                 $errors = false;
                 $savefields = array();
                 $query = $em->createQuery('SELECT f.id, f.name, f.type, f.parameters, f.required, f.nesting FROM FormFormBundle:FormFields f WHERE f.formId = :id AND f.enabled != 0 ORDER BY f.ordering')->setParameter('id', $id);
                 $fields = $query->getResult();
                 if (is_array($fields))
                 {
                     foreach ($fields as $field)
                     {
                         $value = (isset($postfields[$field['id']]) ? $postfields[$field['id']] : null);
                         $name = $this->container->get('cms.cmsManager')->decodeLocalString($field['name'], 'default', true);
                         if (($field['type'] == 'text') || ($field['type'] == 'textarea'))
                         {
                             $result['values'][$field['id']] = $value;
                             $regexp = $this->container->get('cms.cmsManager')->decodeLocalString($field['parameters'], $locale, true);
                             if (($value == null) && ($field['required'] != 0)) {$errors = true; $result['errors'][$field['id']] = 'Required error';}
                             if (($value != null) && (!preg_match('/^'.$regexp.'$/ui', $value))) {$errors = true; $result['errors'][$field['id']] = 'RegExp error';}
                             $savefields[] = array('name' => $name, 'value' => $value, 'nesting' => $field['nesting']);
                         }
                         if (($field['type'] == 'radio') || ($field['type'] == 'select'))
                         {
                             $result['values'][$field['id']] = $value;
                             $findvalue = false;
                             $lines = preg_split('/\\r\\n?|\\n/', $this->container->get('cms.cmsManager')->decodeLocalString($field['parameters'], $locale, true));
                             if (is_array($lines))
                             {
                                 foreach ($lines as $line) if ((trim($line) != '') && (trim($line) == $value)) $findvalue = true;
                             }
                             if (($value == null) && ($field['required'] != 0)) {$errors = true; $result['errors'][$field['id']] = 'Required error';}
                             if (($value != null) && ($findvalue == false)) {$errors = true; $result['errors'][$field['id']] = 'Value error';}
                             $savefields[] = array('name' => $name, 'value' => $value, 'nesting' => $field['nesting']);
                         }
                         if ($field['type'] == 'checkbox')
                         {
                             $result['values'][$field['id']] = $value;
                             $savefields[] = array('name' => $name, 'value' => ($value == null ? 'Нет' : 'Да'), 'nesting' => $field['nesting']);
                         }
                         if ($field['type'] == 'group')
                         {
                             $savefields[] = array('name' => $name, 'value' => null, 'nesting' => $field['nesting']);
                         }
                     }
                 }
                 if ($form[0]['captchaEnabled'] != 0)
                 {
                     $needle = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_captcha_form'.$form[0]['id']);
                     $value = (isset($postfields['captcha']) ? $postfields['captcha'] : null);
                     if (($needle == '') || (strtoupper($value) != strtoupper($needle)) || ($value == null))
                     {
                         $result['errors']['captcha'] = 'Value error';
                         $errors = true;
                     }
                     $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_form'.$form[0]['id'], '');
                 }
                 if ($errors == false)
                 {
                     $letterent = new \Form\FormBundle\Entity\FormLetters();
                     $letterent->setCreateDate(new \DateTime('now'));
                     $letterent->setFields(serialize($savefields));
                     $letterent->setFormId($id);
                     $letterent->setIpAddress($this->container->get('request_stack')->getCurrentRequest()->getClientIp());
                     $letterent->setLocale($locale);
                     $letterent->setSeoPageAction($seoPage->getContentAction());
                     $letterent->setSeoPageId($seoPage->getContentId());
                     $letterent->setSeoPageObject($seoPage->getContentType());
                     $letterent->setViewed(0);
                     $em->persist($letterent);
                     $em->flush();
                     $result['status'] = 'OK';
                     // Отправка сообщения на почту
                     $form[0]['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($form[0]['title'], 'default', true);
                     if ($form[0]['sendEmail'] != 0)
                     {
                         $mailer = $this->container->get('mailer');
                         $messages = \Swift_Message::newInstance()
                                 ->setSubject('Новое письмо формы')
                                 ->setFrom('forms@'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost())
                                 ->setTo($form[0]['email'])
                                 ->setContentType('text/html')
                                 ->setBody($this->container->get('templating')->render('FormFormBundle:Default:email.html.twig', array(
                                     'title' => $form[0]['title'],
                                     'url' => 'http://'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost().$this->container->get('router')->generate('form_form_viewletter').'?id='.$letterent->getId(),
                                     'fields' => $savefields
                                 )));
                         $mailer->send($messages);
                     }
                     // Запись в лог
                     $this->container->get('cms.cmsManager')->logPushFront(0, $this->getName().'.create', 'Отправка сообщения через форму "'.$form[0]['title'].'"', $this->container->get('router')->generate('form_form_viewletter').'?id='.$letterent->getId());
                 } else $result['status'] = 'Check error';
             } else $result['status'] = 'Not found';
             $result['action'] = 'object.form.sendform';
             $result['actionId'] = $id;
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) $this->container->get($item)->setFrontAction($locale, $seoPage, $action, $result);
         return $result;
     }
     
     public function getModuleTypes()
     {
         $contents = array('moduleform' => 'Модуль формы');
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) $this->container->get($item)->getModuleTypes($contents);
         return $contents;
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result = null)
     {
         $params = null;
         if ($moduleType == 'moduleform')
         {
             if (isset($parameters['formId']))
             {
                 
                 $params = array();
                 $em = $this->container->get('doctrine')->getEntityManager();
                 $query = $em->createQuery('SELECT f.id, f.title, f.template, f.createrId, u.login as createrLogin, u.fullName as createrFullName, u.avatar as createrAvatar, f.captchaEnabled FROM FormFormBundle:Forms f '.
                                           'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.
                                           'WHERE f.id = :id AND f.enabled != 0')->setParameter('id', $parameters['formId']);
                 $form = $query->getResult();
                 if (empty($form)) return null;
                 if (isset($form[0])) $params = $form[0]; else return null;
                 $params['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($params['title'], $locale, true);
                 $params['fields'] = array();
                 if (isset($result['action']) && ($result['action'] == 'object.form.sendform') && isset($result['actionId']) && ($result['actionId'] == $parameters['formId']))
                 {
                     $params['status'] = 'get';
                     $getvalues = true;
                     if ($result['status'] == 'OK') {$params['status'] = 'OK'; $getvalues = false;}
                 } else 
                 {
                     $params['status'] = '';
                     $getvalues = false;
                 }
                 if ($params['captchaEnabled'] != 0)
                 {
                     $params['captchaPath'] = $this->container->get('router')->generate('basic_cms_captcha').'?target=form'.$params['id'];
                     $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_form'.$params['id'], '');
                     $params['captchaError'] = (isset($result['errors']['captcha']) ? $result['errors']['captcha'] : '');
                 }
                 $query = $em->createQuery('SELECT f.id, f.name, f.type, f.parameters, f.nesting, f.required FROM FormFormBundle:FormFields f WHERE f.formId = :id AND f.enabled != 0 ORDER BY f.ordering')->setParameter('id', $parameters['formId']);
                 $fields = $query->getResult();
                 if (is_array($fields))
                 {
                     foreach ($fields as $field)
                     {
                         $newitem = array();
                         $newitem['id'] = $field['id'];
                         $newitem['nesting'] = $field['nesting'];
                         $newitem['required'] = $field['required'];
                         $newitem['name'] = $this->container->get('cms.cmsManager')->decodeLocalString($field['name'], $locale, true);
                         $newitem['type'] = $field['type'];
                         if ($getvalues == true)
                         {
                             $newitem['value'] = (isset($result['values'][$field['id']]) ? $result['values'][$field['id']] : '');
                             $newitem['error'] = (isset($result['errors'][$field['id']]) ? $result['errors'][$field['id']] : '');
                         } else 
                         {
                             $newitem['value'] = '';
                             $newitem['error'] = '';
                         }
                         if (($field['type'] == 'text') || ($field['type'] == 'textarea'))
                         {
                             $newitem['regularExp'] = $this->container->get('cms.cmsManager')->decodeLocalString($field['parameters'], $locale, true);
                             $params['fields'][] = $newitem;
                         }
                         if (($field['type'] == 'radio') || ($field['type'] == 'select'))
                         {
                             $newitem['items'] = array();
                             $lines = preg_split('/\\r\\n?|\\n/', $this->container->get('cms.cmsManager')->decodeLocalString($field['parameters'], $locale, true));
                             if (is_array($lines))
                             {
                                 foreach ($lines as $line) if (trim($line) != '') $newitem['items'][] = trim($line);
                             }
                             if (count($newitem['items']) > 0) $params['fields'][] = $newitem;
                         }
                         if (($field['type'] == 'checkbox') || ($field['type'] == 'group'))
                         {
                             $params['fields'][] = $newitem;
                         }
                         unset($newitem);
                     }
                 }
             }
         }
         if ($params != null) return $params;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             if (strpos($item,'addone.form.') === 0) $params = $this->container->get($item)->getFrontModule($locale, $seoPage, $moduleType, $parameters, $result);
             if ($params != null) return $params;
         }
         return null;
     }

     
    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        if ($module == 'moduleform')
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $parameters = array();
            $parameters['formId'] = '';
            if (isset($nullparameters['formId'])) $parameters['formId'] = $nullparameters['formId'];
            $parameterserror['formId'] = '';
            $query = $em->createQuery('SELECT f.id, f.title, f.enabled FROM FormFormBundle:Forms f ORDER BY f.id');
            $formlist = $query->getResult();
            foreach ($formlist as &$formtmp) $formtmp['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($formtmp['title'], 'default');
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postparameters = $request->get('parameters');
                if (isset($postparameters['formId'])) $parameters['formId'] = $postparameters['formId'];
                $finded = false;
                foreach ($formlist as $formitem) if ($formitem['id'] == $parameters['formId']) $finded = true;
                if ($finded == false) {$errors = true; $parameterserror['formId'] = 'Форма не найдена';}
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
                return $this->container->get('templating')->render('FormFormBundle:Default:moduleForm.html.twig',array('parameters' => $parameters,'parameterserror' => $parameterserror,'formlist'=>$formlist));
            }
        }
        $answer = null;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.form.') === 0) $answer = $this->container->get($item)->getAdminModuleController($request, $module, $actionType, $nullparameters);
            if ($answer != null) return $answer;
        }
        return null;
    }
    
    public function getInfoTitle($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT f.title FROM FormFormBundle:Forms f WHERE f.id = :id')->setParameter('id', $contentId);
             $form = $query->getResult();
             if (isset($form[0]['title'])) return $form[0]['title'];
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.form.') === 0) $answer = $this->container->get($item)->getInfoTitle($contentType, $contentId);
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
             if (strpos($item,'addone.form.') === 0) $answer = $this->container->get($item)->getInfoSitemap($contentType, $contentId);
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
            if (strpos($item,'addone.form.') === 0) $this->container->get($item)->getSearchOptions($options);
        }
    }
    
    public function getSearchItemCount($options, $searchstring, $locale)
    {
        $count = 0;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.form.') === 0) $count += $this->container->get($item)->getSearchItemCount($options, $searchstring, $locale);
        }
        return $count;
    }
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, &$items)
    {
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.form.') === 0) $this->container->get($item)->getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, $items);
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
            if (strpos($item,'addone.form.') === 0) $this->container->get($item)->getHelpChapters($chapters);
        }
        return $chapters;
    }
    
    public function getHelpContent($page, &$answer)
    {
        //if ($page == 'd1537bd0b66a4289001629ac2e06e742') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:index.html.twig',array());
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.form.') === 0) $answer .= $this->container->get($item)->getHelpContent($page);
        }
        return $answer;
    }
    
    
}
