<?php

namespace Addone\ProfileBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class UserProfileManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'addone.user.profile';
    }
    
    public function getDescription()
    {
        return 'Профиль пользователя';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Профиль пользователя', $this->container->get('router')->generate('addone_profile_parameter_list'), 1, $this->container->get('security.context')->getToken()->getUser()->checkAccess('user_profile'), 'Администрирование');
    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('user_profile','Изменение полей профиля');
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
            $profile = array();
            $query = $em->createQuery('SELECT p.id, p.name, p.description, p.regularExp, \'\' as value, \'\' as error FROM AddoneProfileBundle:UserParameters p ORDER BY p.ordering');
            $parameters = $query->getResult();
            foreach ($parameters as &$item) $item['description'] = $this->container->get('cms.cmsManager')->decodeLocalString($item['description'],'default');
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $profile = $request->get('profile');
                foreach ($parameters as &$item)
                {
                    if (isset($profile[$item['name']])) $item['value'] = $profile[$item['name']];
                    if (!@preg_match("/^".$item['regularExp']."$/ui", $item['value'])) {$errors = true; $item['error'] = 'Поле заполнено неверно';}
                }
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    foreach ($parameters as &$item)
                    {
                        $valueent = new \Addone\ProfileBundle\Entity\UserValues();
                        $valueent->setParameterId($item['id']);
                        $valueent->setUserId($actionId);
                        $valueent->setValue($item['value']);
                        $em->persist($valueent);
                        $em->flush();
                        unset($valueent);
                    }
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('AddoneProfileBundle:Addone:userCreate.html.twig',array('parameters'=>$parameters));
            }
        }
        if ($action == 'userEdit')
        {
            $profile = array();
            $query = $em->createQuery('SELECT p.id, p.name, p.description, p.regularExp, v.value as value, \'\' as error FROM AddoneProfileBundle:UserParameters p '.
                                      'LEFT JOIN AddoneProfileBundle:UserValues v WITH v.userId = :userid AND v.parameterId = p.id '.
                                      'ORDER BY p.ordering')->setParameter('userid', $actionId);
            $parameters = $query->getResult();
            foreach ($parameters as &$item) $item['description'] = $this->container->get('cms.cmsManager')->decodeLocalString($item['description'],'default');
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $profile = $request->get('profile');
                foreach ($parameters as &$item)
                {
                    if (isset($profile[$item['name']])) $item['value'] = $profile[$item['name']];
                    if (!@preg_match("/^".$item['regularExp']."$/ui", $item['value'])) {$errors = true; $item['error'] = 'Поле заполнено неверно';}
                }
                if ($actionType == 'validate') return $errors;
                if (($actionType == 'save') && ($errors == false))
                {
                    foreach ($parameters as &$item)
                    {
                        $valueent = $this->container->get('doctrine')->getRepository('AddoneProfileBundle:UserValues')->findOneBy(array('parameterId'=>$item['id'],'userId'=>$actionId));
                        if (empty($valueent))
                        {
                            $valueent = new \Addone\ProfileBundle\Entity\UserValues();
                            $valueent->setParameterId($item['id']);
                            $valueent->setUserId($actionId);
                            $em->persist($valueent);
                        }
                        $valueent->setValue($item['value']);
                        $em->flush();
                        unset($valueent);
                    }
                }
            }
            if ($actionType == 'tab')
            {
                return $this->container->get('templating')->render('AddoneProfileBundle:Addone:userEdit.html.twig',array('parameters'=>$parameters));
            }
        }
        if ($action == 'userDelete')
        {
            if ($actionType == 'validate') return false;
            if ($actionType == 'tab') return null;
            if ($actionType == 'save')
            {
                $query = $em->createQuery('DELETE FROM AddoneProfileBundle:UserValues v '.
                                          'WHERE v.userId = :userid')->setParameter('userid', $actionId);
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
         if ($contentType == 'view')
         {
             
             $query = $em->createQuery('SELECT p.name, p.description, v.value as value FROM AddoneProfileBundle:UserParameters p '.
                                       'LEFT JOIN AddoneProfileBundle:UserValues v WITH v.userId = :userid AND v.parameterId = p.id '.
                                       'ORDER BY p.ordering')->setParameter('userid', $contentId);
             $parameters = $query->getResult();
             foreach ($parameters as &$item) $item['description'] = $this->container->get('cms.cmsManager')->decodeLocalString($item['description'],$locale);
             foreach ($parameters as $item2) 
             {
                 $params['profile'][$item2['name']] = $item2;
             }
         }
         if ($contentType == 'register')
         {
             $query = $em->createQuery('SELECT p.id, p.name, p.description, p.regularExp FROM AddoneProfileBundle:UserParameters p '.
                                       'ORDER BY p.ordering');
             $parameters = $query->getResult();
             foreach ($parameters as &$item) 
             {
                 $item['description'] = $this->container->get('cms.cmsManager')->decodeLocalString($item['description'],$locale);
                 $item['value'] = '';
                 $item['error'] = '';
                 if (($params['autorized'] == 0) && isset($result['action']) && ($result['action'] == 'object.user.register') && ($result['actionId'] == $contentId))
                 {
                     $item['value'] = (isset($result['profile'][$item['name']]['value']) ? $result['profile'][$item['name']]['value'] : '');
                     $item['error'] = (isset($result['profile'][$item['name']]['error']) ? $result['profile'][$item['name']]['error'] : '');
                 }
             }
             unset($item);
             foreach ($parameters as $item) 
             {
                 $params['profile'][$item['name']] = $item;
             }
         }
         if ($contentType == 'profile')
         {
             if (isset($params['user']))
             {
                 $query = $em->createQuery('SELECT p.id, p.name, p.description, p.regularExp, v.value as value FROM AddoneProfileBundle:UserParameters p '.
                                           'LEFT JOIN AddoneProfileBundle:UserValues v WITH v.userId = :userid AND v.parameterId = p.id '.
                                           'ORDER BY p.ordering')->setParameter('userid', $params['user']->getId());
                 $parameters = $query->getResult();
                 foreach ($parameters as &$item) 
                 {
                     $item['description'] = $this->container->get('cms.cmsManager')->decodeLocalString($item['description'],$locale);
                     $item['error'] = '';
                     if (isset($result['action']) && ($result['action'] == 'object.user.profile') && ($result['actionId'] == $contentId))
                     {
                         if (isset($result['profile'][$item['name']]['value'])) $item['value'] = $result['profile'][$item['name']]['value'];
                         if (isset($result['profile'][$item['name']]['error'])) $item['error'] = $result['profile'][$item['name']]['error'];
                     }
                 }
                 unset($item);
                 foreach ($parameters as $item) 
                 {
                     $params['profile'][$item['name']] = $item;
                 }
             }
         }
     }

     public function setFrontActionPrepare($locale, $seoPage, $action, &$result)
     {
         if ($action == 'register')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $regpage = $this->container->get('doctrine')->getRepository('BasicCmsBundle:UserRegisterPages')->find($id);
             if ((!empty($regpage)) && ($regpage->getEnabled() != 0) && ($this->container->get('cms.cmsManager')->getCurrentUser() == null))
             {
                 $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
                 $query = $em->createQuery('SELECT p.id, p.name, p.description, p.regularExp FROM AddoneProfileBundle:UserParameters p '.
                                           'ORDER BY p.ordering');
                 $parameters = $query->getResult();
                 foreach ($parameters as $item) 
                 {
                     $result['profile'][$item['name']]['value'] = (isset($postfields['profile'][$item['name']]) ? $postfields['profile'][$item['name']] : '');
                     $result['profile'][$item['name']]['error'] = '';
                     if (!preg_match("/^".$item['regularExp']."$/ui", $result['profile'][$item['name']]['value'])) {$result['errors'] = true; $result['profile'][$item['name']]['error'] = 'preg';}
                 }
             }
         }
         if ($action == 'profile')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $profpage = $this->container->get('doctrine')->getRepository('BasicCmsBundle:UserProfilePages')->find($id);
             if ((!empty($profpage)) && ($profpage->getEnabled() != 0) && ($this->container->get('cms.cmsManager')->getCurrentUser() != null) && ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() == 2))
             {
                 $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
                 $query = $em->createQuery('SELECT p.id, p.name, p.description, p.regularExp, v.value as value FROM AddoneProfileBundle:UserParameters p '.
                                           'LEFT JOIN AddoneProfileBundle:UserValues v WITH v.userId = :userid AND v.parameterId = p.id '.
                                           'ORDER BY p.ordering')->setParameter('userid', $this->container->get('cms.cmsManager')->getCurrentUser()->getId());
                 $parameters = $query->getResult();
                 foreach ($parameters as $item) if (isset($postfields['profile'][$item['name']]))
                 {
                     $result['profile'][$item['name']]['value'] = $postfields['profile'][$item['name']];
                     $result['profile'][$item['name']]['error'] = '';
                     if (!preg_match("/^".$item['regularExp']."$/ui", $result['profile'][$item['name']]['value'])) {$result['errors'] = true; $result['profile'][$item['name']]['error'] = 'preg';}
                 }
             }
         }
     }
     
     public function setFrontAction($locale, $seoPage, $action, &$result)
     {
         if ($action == 'register')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             if ((isset($result['errors'])) && ($result['errors'] == false) && (isset($result['registredUser'])))
             {
                 $query = $em->createQuery('SELECT p.id, p.name, p.description, p.regularExp FROM AddoneProfileBundle:UserParameters p '.
                                           'ORDER BY p.ordering');
                 $parameters = $query->getResult();
                 foreach ($parameters as $item) if (isset($result['profile'][$item['name']]['value']))
                 {
                     $valueent = new \Addone\ProfileBundle\Entity\UserValues();
                     $valueent->setParameterId($item['id']);
                     $valueent->setUserId($result['registredUser']->getId());
                     $valueent->setValue($result['profile'][$item['name']]['value']);
                     $em->persist($valueent);
                     $em->flush();
                     unset($valueent);
                 }
             }
         }
         if ($action == 'profile')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             if ((isset($result['errors'])) && ($result['errors'] == false) && (isset($result['profileUser'])))
             {
                 $query = $em->createQuery('SELECT p.id, p.name, p.description, p.regularExp, v.value as value FROM AddoneProfileBundle:UserParameters p '.
                                           'LEFT JOIN AddoneProfileBundle:UserValues v WITH v.userId = :userid AND v.parameterId = p.id '.
                                           'ORDER BY p.ordering')->setParameter('userid', $result['profileUser']->getId());
                 $parameters = $query->getResult();
                 foreach ($parameters as $item) if (isset($result['profile'][$item['name']]['value']))
                 {
                     $valueent = $this->container->get('doctrine')->getRepository('AddoneProfileBundle:UserValues')->findOneBy(array('parameterId'=>$item['id'],'userId'=>$result['profileUser']->getId()));
                     if (empty($valueent))
                     {
                         $valueent = new \Addone\ProfileBundle\Entity\UserValues();
                         $valueent->setParameterId($item['id']);
                         $valueent->setUserId($result['profileUser']->getId());
                         $em->persist($valueent);
                     }
                     $valueent->setValue($result['profile'][$item['name']]['value']);
                     $em->flush();
                     unset($valueent);
                 }
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
    
}
