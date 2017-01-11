<?php

namespace Extended\AlertBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class UserAlertManager
{
    private $container;
    private $config;

    public function __construct(Container $container, $config) 
    {
        $this->container = $container;
        $this->config = $config;
    }

    public function getName()
    {
        return 'addone.user.alert';
    }
    
    public function getDescription()
    {
        return 'Оповещения пользователей';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Оповещения пользователей', $this->container->get('router')->generate('extended_alert_config'), 2, $this->container->get('security.context')->getToken()->getUser()->checkAccess('user_alert'), 'Администрирование');
        $manager->addAdminMenu('Социальные сети', $this->container->get('router')->generate('extended_alert_social'), 3, $this->container->get('security.context')->getToken()->getUser()->checkAccess('user_social'), 'Администрирование');
        $manager->addAdminMenu('Рассылка', $this->container->get('router')->generate('extended_alert_subscribe'), 4, $this->container->get('security.context')->getToken()->getUser()->checkAccess('user_subscribe'), 'Администрирование');
    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('user_alert','Изменение оповещения пользователей');
        $manager->addRole('user_social','Изменение социальных сетей');
        $manager->addRole('user_subscribe','Рассылка сообщений всем пользователям');
    }
// ************************************
// Обработка административной части
// avtionType (validate, tab, save)
// ************************************
    public function getAdminController($request, $action, $actionId, $actionType)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        if ($action == 'userCreate')
        {
            $alerts = $this->getAlertList('', true);
            $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
            $alertsLocales = $query->getResult();
            if (!is_array($alertsLocales)) 
            {
                $alertsLocales = array();
            }
            $subscribes = array();
            $alertsLocale = '';
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postAlerts = $request->get('alerts');
                $postAlertsLocale = $request->get('alertsLocale');
                $subscribes = array();
                if (is_array($postAlerts))
                {
                    foreach ($postAlerts as $alertitem)
                    {
                        if (($alertitem != '') && (is_string($alertitem)) && (isset($alerts[$alertitem])))
                        {
                            $subscribes[] = $alertitem;
                        }
                    }
                }
                $alertsLocale = '';
                foreach ($alertsLocales as $localeItem)
                {
                    if ($localeItem['shortName'] == $postAlertsLocale)
                    {
                        $alertsLocale = $localeItem['shortName'];
                    }
                }
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    $valueent = new \Extended\AlertBundle\Entity\UserAlerts();
                    $valueent->setUserId($actionId);
                    $valueent->setSubscribe(implode(',', $subscribes));
                    $valueent->setLocale($alertsLocale);
                    $em->persist($valueent);
                    $em->flush();
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ExtendedAlertBundle:Addone:userCreate.html.twig',array('alerts' => $alerts, 'subscribes' => $subscribes, 'alertsLocale' => $alertsLocale, 'alertsLocales' => $alertsLocales));
            }
        }
        if ($action == 'userEdit')
        {
            $alerts = $this->getAlertList('', true);
            $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
            $alertsLocales = $query->getResult();
            if (!is_array($alertsLocales)) 
            {
                $alertsLocales = array();
            }
            $valueent = $this->container->get('doctrine')->getRepository('ExtendedAlertBundle:UserAlerts')->findOneBy(array('userId'=>$actionId));
            if (empty($valueent))
            {
                $subscribes = array();
                $alertsLocale = '';
            } else 
            {
                $subscribes = explode(',', $valueent->getSubscribe());
                $alertsLocale = $valueent->getLocale();
            }
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postAlerts = $request->get('alerts');
                $postAlertsLocale = $request->get('alertsLocale');
                $subscribes = array();
                if (is_array($postAlerts))
                {
                    foreach ($postAlerts as $alertitem)
                    {
                        if (($alertitem != '') && (is_string($alertitem)) && (isset($alerts[$alertitem])))
                        {
                            $subscribes[] = $alertitem;
                        }
                    }
                }
                $alertsLocale = '';
                foreach ($alertsLocales as $localeItem)
                {
                    if ($localeItem['shortName'] == $postAlertsLocale)
                    {
                        $alertsLocale = $localeItem['shortName'];
                    }
                }
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    if (empty($valueent)) 
                    {
                        $valueent = new \Extended\AlertBundle\Entity\UserAlerts();
                        $valueent->setUserId($actionId);
                        $em->persist($valueent);
                    }
                    $valueent->setSubscribe(implode(',', $subscribes));
                    $valueent->setLocale($alertsLocale);
                    $em->flush();
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('ExtendedAlertBundle:Addone:userEdit.html.twig',array('alerts' => $alerts, 'subscribes' => $subscribes, 'alertsLocale' => $alertsLocale, 'alertsLocales' => $alertsLocales));
            }
        }
        if ($action == 'userDelete')
        {
            if ($actionType == 'validate') return false;
            if ($actionType == 'tab') return null;
            if ($actionType == 'save')
            {
                $query = $em->createQuery('DELETE FROM ExtendedAlertBundle:UserAlerts a '.
                                          'WHERE a.userId = :userid')->setParameter('userid', $actionId);
                $query->execute();
            }
        }
        return null;
    }

    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        return null;
    }
// ************************************
// Обработка фронт части
// ************************************
     public function getTemplateTwig($contentType, $template)
     {
         return null;
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         return null;
     }
     
     public function getContentTypes(&$contents)
     {
     }

     public function getFrontContent($locale, $contentType, $contentId, $result, &$params)
     {
         $em = $this->container->get('doctrine')->getEntityManager();
         if ($contentType == 'profile')
         {
             if (isset($params['user']))
             {
                 $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
                 $params['alertsLocales'] = $query->getResult();
                 if (!is_array($params['alertsLocales'])) 
                 {
                     $params['alertsLocales'] = array();
                 }
                 $params['alerts'] = array();
                 $alerts = $this->getAlertList($locale);
                 $query = $em->createQuery('SELECT a.subscribe, a.locale FROM ExtendedAlertBundle:UserAlerts a '.
                                           'WHERE a.userId = :userid')->setParameter('userid', $params['user']->getId());
                 $subscribes = $query->getResult();
                 if (isset($subscribes[0]['subscribe']))
                 {
                     $userLocale = $subscribes[0]['locale'];
                     $subscribes = explode(',', $subscribes[0]['subscribe']);
                 } else 
                 {
                     $userLocale = '';
                     $subscribes = array();
                 }
                 if (isset($result['action']) && ($result['action'] == 'object.user.profile') && ($result['actionId'] == $contentId))
                 {
                     if (isset($result['alerts']) && is_array($result['alerts']))
                     {
                         $subscribes = $result['alerts'];
                     }
                     if (isset($result['alertsLocale']))
                     {
                         $userLocale = $result['alertsLocale'];
                     }
                 }
                 foreach ($alerts as $alertkey=>$alert)
                 {
                     $value = (in_array($alertkey, $subscribes) ? 1 : 0);
                     $params['alerts'][] = array('name' => $alertkey, 'description' => $alert, 'value' => $value);
                 }
                 $params['alertsLocale'] = $userLocale;
                 unset($alerts);
                 unset($subscribes);
             }
         }
     }

     public function setFrontActionPrepare($locale, $seoPage, $action, &$result)
     {
     }
     
     public function setFrontAction($locale, $seoPage, $action, &$result)
     {
         if ($action == 'profile')
         {
             $alerts = $this->getAlertList($locale);
             $em = $this->container->get('doctrine')->getEntityManager();
             $actionfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
             $subscribes = array();
             if (isset($actionfields['alerts']) && is_array($actionfields['alerts']))
             {
                 foreach ($actionfields['alerts'] as $alertitem)
                 {
                     if (($alertitem != '') && (is_string($alertitem)) && (isset($alerts[$alertitem])))
                     {
                         $subscribes[] = $alertitem;
                     }
                 }
             }
             $result['alerts'] = $subscribes;
             $result['alertsLocale'] = '';
             $query = $em->createQuery('SELECT l.shortName FROM BasicCmsBundle:Locales l');
             $locales = $query->getResult();
             if (!is_array($locales)) 
             {
                 $locales = array();
             }
             foreach ($locales as $localeItem)
             {
                 if (isset($actionfields['alertsLocale']) && ($localeItem['shortName'] == $actionfields['alertsLocale']))
                 {
                     $result['alertsLocale'] = $localeItem['shortName'];
                 }
             }
             if ((isset($result['errors'])) && ($result['errors'] == false) && (isset($result['profileUser'])))
             {
                 $valueent = $this->container->get('doctrine')->getRepository('ExtendedAlertBundle:UserAlerts')->findOneBy(array('userId'=>$result['profileUser']->getId()));
                 if (empty($valueent))
                 {
                     $valueent = new \Extended\AlertBundle\Entity\UserAlerts();
                     $valueent->setUserId($result['profileUser']->getId());
                     $em->persist($valueent);
                 }
                 $valueent->setSubscribe(implode(',', $subscribes));
                 $valueent->setLocale($result['alertsLocale']);
                 $em->flush();
                 unset($valueent);
             }
         }
     }
     
     public function getModuleTypes(&$contents)
     {
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result)
     {
         return null;
     }

     public function getInfoTitle($contentType, $contentId)
     {
          return null;
     }

     public function getInfoSitemap($contentType, $contentId)
     {
          return null;
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

    
//********************************************
//********************************************
// Обработка событий
//********************************************
//********************************************
    
    public function bindAlert($alertName, $description, $filter = null)
    {
        if (!isset($description['object']) || !isset($description['type']))
        {
            return;
        }
        $config = $this->container->get('cms.cmsManager')->getConfigValue('alerts.config');
        if (!isset($config[$description['object'].'.'.$description['type']]))
        {
            return;
        }
        if (isset($config[$description['object'].'.'.$description['type']]['title']))
        {
            $letterTitle = $config[$description['object'].'.'.$description['type']]['title'];
        } else 
        {
            $letterTitle = '';
        }
        if (isset($config[$description['object'].'.'.$description['type']]['body']))
        {
            $letterBody = $config[$description['object'].'.'.$description['type']]['body'];
        } else 
        {
            $letterBody = '';
        }
        
        $em = $this->container->get('doctrine')->getEntityManager();
        // Рассылка на почту пользователям
        if (($filter != null) && (is_array($filter)))
        {
            if (count($filter) > 0)
            {
                $query = $em->createQuery('SELECT u.email, a.locale FROM ExtendedAlertBundle:UserAlerts a '.
                                          'LEFT JOIN BasicCmsBundle:Users u WITH u.id = a.userId '.
                                          'WHERE u.id IN (:ids) AND LOCATE(:alertname,CONCAT(\',\',CONCAT(a.subscribe,\',\'))) > 0')->setParameter('alertname', $alertName)->setParameter('ids', $filter);
                $emails = $query->getResult();
            } else 
            {
                $emails = array();
            }
        } else 
        {
            $query = $em->createQuery('SELECT u.email, a.locale FROM ExtendedAlertBundle:UserAlerts a '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = a.userId '.
                                      'WHERE LOCATE(:alertname,CONCAT(\',\',CONCAT(a.subscribe,\',\'))) > 0')->setParameter('alertname', $alertName);
            $emails = $query->getResult();
        }
        if (count($emails) > 0)
        {
            $mailer = $this->container->get('mailer');
            foreach ($emails as $email)
            {
                $to = $email['email'];
                if ($email['locale'] == '')
                {
                    $email['locale'] = 'default';
                }
                $title = $this->container->get('cms.cmsManager')->decodeLocalString($letterTitle, $email['locale'], true);
                $body = $this->container->get('cms.cmsManager')->decodeLocalString($letterBody, $email['locale'], true);
                $title = str_replace(preg_replace('/^.+$/ui', '//$0//', array_keys($description)), array_values($description), $title);
                $body = str_replace(preg_replace('/^.+$/ui', '//$0//', array_keys($description)), array_values($description), $body);
                $message = \Swift_Message::newInstance()
                    ->setSubject($title)
                    ->setTo($email['email'])
                    ->setFrom('alerts@'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost())
                    ->setContentType('text/html')
                    ->setBody($body);
                $mailer->send($message);
            }
        }
    }
    
    private function getAlertList($locale = '', $isAdmin = false)
    {
        $alerts = array();
        foreach ($this->config['bundles'] as $item) 
        {
            if ($this->container->has($item)) 
            {
                $alerts = array_merge($alerts, $this->container->get($item)->getAlertList($locale, $isAdmin));
            }
        }
        return $alerts;
    }
    
}
