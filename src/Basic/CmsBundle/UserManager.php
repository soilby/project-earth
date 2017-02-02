<?php

namespace Basic\CmsBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class UserManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'object.user';
    }
    
    public function getDescription()
    {
        return 'Пользователи и администрирование';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Администрирование', '#', 254, 1);
        $manager->addAdminMenu('Пользователи', $this->container->get('router')->generate('basic_cms_user_list'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('user_list'), 'Администрирование');
        $manager->addAdminMenu('Роли', $this->container->get('router')->generate('basic_cms_role_list'), 10, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('role_list'), 'Администрирование');
        $manager->addAdminMenu('Локализация', $this->container->get('router')->generate('basic_cms_locale_list'), 20, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('locale_edit'), 'Администрирование');
        $manager->addAdminMenu('Карта сайта', $this->container->get('router')->generate('basic_cms_sitemap'), 30, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('site_map'), 'Администрирование');
        $manager->addAdminMenu('Прочие страницы', $this->container->get('router')->generate('basic_cms_sitemap_pageslist'), 40, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('site_pages'), 'Администрирование');
        $manager->addAdminMenu('Оформление ошибок', $this->container->get('router')->generate('basic_cms_errorcfg'), 50, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('error_view'), 'Администрирование');
        $manager->addAdminMenu('Статистика', $this->container->get('router')->generate('basic_cms_statistics'), 60, 1, 'Администрирование');
        $manager->addAdminMenu('Журнал событий', $this->container->get('router')->generate('basic_cms_logs'), 61, 1, 'Администрирование');

        $manager->addAdminMenu('Справка', '#', 255, 1);
        $manager->addAdminMenu('Руководство', $this->container->get('router')->generate('basic_cms_help'), 10, 1, 'Справка');
        $manager->addAdminMenu('Справка о странице', $this->container->get('router')->generate('basic_cms_help').'?page='.md5($this->container->get('request_stack')->getCurrentRequest()->getPathInfo()), 20, 1, 'Справка');
        $manager->addAdminMenu('О программе', $this->container->get('router')->generate('basic_cms_about'), 30, 1, 'Справка');
        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) $this->container->get($item)->registerMenu();

    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('user_list','Просмотр списка пользователей');
        $manager->addRole('user_viewown','Просмотр собственного профиля пользователя');
        $manager->addRole('user_viewall','Просмотр всех профилей пользователей');
        $manager->addRole('user_new','Создание нового пользователя');
        $manager->addRole('user_editown','Редактирование собственного профиля пользователя');
        $manager->addRole('user_editall','Редактирование всех профилей пользователей');

        $manager->addRole('user_authpage','Настройка страниц авторизации');
        $manager->addRole('user_passwordpage','Настройка страниц восстановления пароля');
        $manager->addRole('user_registerpage','Настройка страниц регистрации');
        $manager->addRole('user_profilepage','Настройка страниц изменения профиля');
        
        $manager->addRole('role_list','Просмотр списка ролей');
        $manager->addRole('role_view','Просмотр деталей роли');
        $manager->addRole('role_new','Создание новой роли');
        $manager->addRole('role_edit','Редактирование роли');

        $manager->addRole('site_map','Настройка карты сайта');
        $manager->addRole('locale_edit','Настройка локализации');

        $manager->addRole('error_view','Просмотр оформления ошибок');
        $manager->addRole('error_edit','Настройка оформления ошибок');

        $manager->addRole('site_pages','Настройка прочих страниц сайта');
        $manager->addRole('site_searchpagenew','Создание страниц поиска');
        $manager->addRole('site_searchpageview','Просмотр страниц поиска');
        $manager->addRole('site_searchpageedit','Редактирование страниц поиска');
        $manager->addRole('site_logfront','Настройка оповещений о событиях на сайте');
        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) $this->container->get($item)->registerRoles();
        
     }
     
     public function getContentTypes()
     {
        $contents = array('view' => 'Просмотр пользователя', 'auth' => 'Страница авторизации пользователя', 'password' => 'Страница восстановления пароля', 'register' => 'Страница регистрации новых пользователей', 'profile' => 'Страница изменения профиля пользователя', 'searchpage' => 'Страница поиска по сайту');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) $this->container->get($item)->getContentTypes($contents);
        return $contents;
     }
     
     public function getTaxonomyType()
     {
         return false;
     }

     public function getTemplateTwig($contentType, $template)
     {
         if ($contentType == 'view') return 'BasicCmsBundle:Front:userview.html.twig';
         if ($contentType == 'auth') return 'BasicCmsBundle:Front:userauth.html.twig';
         if ($contentType == 'password') return 'BasicCmsBundle:Front:userpassword.html.twig';
         if ($contentType == 'register') return 'BasicCmsBundle:Front:userregister.html.twig';
         if ($contentType == 'profile') return 'BasicCmsBundle:Front:userprofile.html.twig';
         if ($contentType == 'searchpage') return 'BasicCmsBundle:Front:searchpage.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) 
         {
             $result = $this->container->get($item)->getTemplateTwig($contentType, $template);
             if ($result != null) return $result;
         }
         return 'BasicCmsBundle:Front:null.html.twig';
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         if ($contentType == 'autorization') return 'BasicCmsBundle:Front:moduleautorization.html.twig';
         if ($contentType == 'useronline') return 'BasicCmsBundle:Front:moduleuseronline.html.twig';
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) 
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
             $user = $this->container->get('doctrine')->getRepository('BasicCmsBundle:Users')->find($contentId);
             if (empty($user)) return null;
             $params = array();
             $params['id'] = $user->getId();
             $params['login'] = $user->getLogin();
             $params['email'] = $user->getEmail();
             $params['fullName'] = $user->getFullName();
             $params['avatar'] = $user->getAvatar();
             $params['userType'] = $user->getUserType();
             $params['blocked'] = $user->getBlocked();
             $params['regDate'] = $user->getRegDate();
             $params['role'] = $user->getRole()->getName();
             $params['template'] = $user->getTemplate();
             $params['meta'] = array('title'=>$user->getLogin(), 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         if ($contentType == 'auth')
         {
             $authpage = $this->container->get('doctrine')->getRepository('BasicCmsBundle:UserAuthPages')->find($contentId);
             if (empty($authpage)) return null;
             if ($authpage->getEnabled() == 0) return null;
             $params = array();
             $params['template'] = $authpage->getTemplate();
             $params['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($authpage->getTitle(),$locale,true);
             $params['user'] = $this->container->get('cms.cmsManager')->getCurrentUser();
             $params['autorized'] = ($params['user'] != null ? 1 : 0);
             $params['blocked'] = ((($params['user'] != null) && ($params['user']->getBlocked() != 2)) ? 1 : 0);
             if (isset($result['action']) && ($result['action'] == 'object.user.login')) $params['status'] = $result['status']; else $params['status'] = 'OK';
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
             if (($params['autorized'] == 1) && ($authpage->getRedirectPath() != '')) 
             {
                 $params['rawContent'] = 'Перенаправление...<script>setTimeout(function () {location.href = \''.$authpage->getRedirectPath().'\';}, 2000);</script>';
                 unset($params['template']);
             }
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         if ($contentType == 'password')
         {
             $passpage = $this->container->get('doctrine')->getRepository('BasicCmsBundle:UserPasswordPages')->find($contentId);
             if (empty($passpage)) return null;
             if ($passpage->getEnabled() == 0) return null;
             $params = array();
             $params['template'] = $passpage->getTemplate();
             $params['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($passpage->getTitle(),$locale,true);
             if ($this->container->get('cms.cmsManager')->getCurrentUser() != null)
             {
                 $params['recoveryStep'] = 0;
                 $params['email'] = '';
                 $params['status'] = 'authorized';
             } elseif ($this->container->get('request_stack')->getCurrentRequest()->get('email') != null)
             {
                 // step 2
                 $captchapass = 1;
                 if ($passpage->getCaptchaEnabled() != 0)
                 {
                     $needle = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_captcha_userpassword'.$passpage->getId());
                     $value = $this->container->get('request_stack')->getCurrentRequest()->get('captcha');
                     if (($needle == '') || (strtoupper($value) != strtoupper($needle)) || ($value == null))
                     {
                         $captchapass = 0;
                     }
                     $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_userpassword'.$passpage->getId(), '');
                 }
                 if ($captchapass == 0)
                 {
                     $params['recoveryStep'] = 0;
                     $params['status'] = 'OK';
                     $params['email'] = $this->container->get('request_stack')->getCurrentRequest()->get('email');
                     $params['captchaEnabled'] = 0;
                     $params['captchaPath'] = '';
                     $params['captchaError'] = 'error';
                     if ($passpage->getCaptchaEnabled() != 0)
                     {
                         $params['captchaEnabled'] = 1;
                         $params['captchaPath'] = $this->container->get('router')->generate('basic_cms_captcha').'?target=userpassword'.$passpage->getId();
                         $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_userpassword'.$passpage->getId(), '');
                     }
                 } else
                 {
                     $email = $this->container->get('request_stack')->getCurrentRequest()->get('email');
                     $userent = $this->container->get('doctrine')->getRepository('BasicCmsBundle:Users')->findOneBy(array('email' => $email));
                     if (!empty($userent))
                     {
                         $key2 = md5($userent->getId().'Embedded.CMS');
                         $key1 = md5($userent->getPassword().$userent->getSalt());
                         $url = 'http://'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost().$this->container->get('request_stack')->getCurrentRequest()->getPathInfo().'?key1='.$key1.'&key2='.$key2;
                         $lettertext = $this->container->get('cms.cmsManager')->decodeLocalString($passpage->getLetterText(), $locale, true);
                         $lettertext = str_replace('//url//', $url, $lettertext);
                         $mailer = $this->container->get('mailer');
                         $messages = \Swift_Message::newInstance()
                                 ->setSubject($params['title'])
                                 ->setFrom('recovery@'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost())
                                 ->setTo($userent->getEmail())
                                 ->setContentType('text/html')
                                 ->setBody($lettertext);
                         $mailer->send($messages);
                     }
                     $params['recoveryStep'] = 1;
                     $params['email'] = $email;
                     $params['status'] = 'OK';
                 }
             } elseif (($this->container->get('request_stack')->getCurrentRequest()->get('key1') != null) && ($this->container->get('request_stack')->getCurrentRequest()->get('key2') != null))
             {
                 // step 3 or 4
                 $userid = $this->container->get('request_stack')->getCurrentRequest()->get('key2');
                 $userpass = $this->container->get('request_stack')->getCurrentRequest()->get('key1');
                 $params['url'] = '?key1='.$userpass.'&key2='.$userid;
                 $query = $this->container->get('doctrine')->getEntityManager()->createQuery('SELECT u FROM BasicCmsBundle:Users u WHERE MD5(CONCAT(u.id,\'Embedded.CMS\')) = :userid AND MD5(CONCAT(u.password, u.salt)) = :userpass')->setParameter('userid', $userid)->setParameter('userpass', $userpass);
                 $userEntity = $query->getResult();
                 if ((count($userEntity) == 1) && (isset($userEntity[0])) && (!empty($userEntity[0]))) 
                 {
                     $userEntity = $userEntity[0];
                     $letters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZzbcdefghijklmnopqrstuvwxyz-';
                     $password = '';
                     $width = rand(8, 12);
                     for ($i = 0; $i < $width; $i++)
                     {
                         $password .= substr($letters, rand(0, strlen($letters) - 1), 1);
                     }
                     $goprocess = 1;
                     if ($passpage->getAllowPasswordType() != 0)
                     {
                         if (($this->container->get('request_stack')->getCurrentRequest()->get('password') !== null) && ($this->container->get('request_stack')->getCurrentRequest()->get('passwordRepeat') !== null))
                         {
                             $password = $this->container->get('request_stack')->getCurrentRequest()->get('password');
                             if (!preg_match('/^[A-Za-z0-9\-]{5,30}$/ui', $password)) 
                             {
                                 $goprocess = 0;
                                 $params['passwordError'] = 'preg';
                             }
                             elseif ($password != $this->container->get('request_stack')->getCurrentRequest()->get('passwordRepeat'))
                             {
                                 $goprocess = 0;
                                 $params['passwordError'] = 'repeat';
                             }
                             if ($passpage->getCaptchaEnabled() != 0)
                             {
                                 $needle = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_captcha_userpassword'.$passpage->getId());
                                 $value = $this->container->get('request_stack')->getCurrentRequest()->get('captcha');
                                 if (($needle == '') || (strtoupper($value) != strtoupper($needle)) || ($value == null))
                                 {
                                     $goprocess = 0;
                                     $params['captchaError'] = 'error';
                                 }
                                 $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_userpassword'.$passpage->getId(), '');
                             }
                         } else
                         {
                             $goprocess = 0;
                             $params['passwordError'] = '';
                         }
                     }
                     if ($goprocess == 0)
                     {
                         $params['recoveryStep'] = 2;
                         $params['allowPasswordType'] = $passpage->getAllowPasswordType();
                         $params['captchaEnabled'] = 0;
                         $params['captchaPath'] = '';
                         if (!isset($params['captchaError'])) $params['captchaError'] = '';
                         if (!isset($params['passwordError'])) $params['passwordError'] = '';
                         if ($passpage->getCaptchaEnabled() != 0)
                         {
                             $params['captchaEnabled'] = 1;
                             $params['captchaPath'] = $this->container->get('router')->generate('basic_cms_captcha').'?target=userpassword'.$passpage->getId();
                             $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_userpassword'.$passpage->getId(), '');
                         }
                     } else
                     {
                         $userEntity->setPassword($this->container->get('security.encoder_factory')->getEncoder($userEntity)->encodePassword($password, $userEntity->getSalt()));
                         $this->container->get('doctrine')->getEntityManager()->flush();
                         $params['recoveryStep'] = 3;
                         $params['user'] = $userEntity;
                         $params['status'] = 'OK';
                         $params['password'] = $password;
                         // Запись в лог
                         $this->container->get('cms.cmsManager')->logPushFront($userEntity->getId(), $this->getName().'.edit', 'Изменение пароля пользователя "'.$userEntity->getFullName().'"', $this->container->get('router')->generate('basic_cms_user_edit').'?id='.$userEntity->getId());
                     }
                 } else
                 {
                     $params['recoveryStep'] = 3;
                     $params['user'] = null;
                     $params['status'] = 'error';
                     $params['password'] = '';
                 }
             } else
             {
                 // step 1
                 $params['recoveryStep'] = 0;
                 $params['status'] = 'OK';
                 $params['email'] = '';
                 $params['captchaEnabled'] = 0;
                 $params['captchaPath'] = '';
                 $params['captchaError'] = '';
                 if ($passpage->getCaptchaEnabled() != 0)
                 {
                     $params['captchaEnabled'] = 1;
                     $params['captchaPath'] = $this->container->get('router')->generate('basic_cms_captcha').'?target=userpassword'.$passpage->getId();
                     $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_userpassword'.$passpage->getId(), '');
                 }
             }
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         if ($contentType == 'register')
         {
             $regpage = $this->container->get('doctrine')->getRepository('BasicCmsBundle:UserRegisterPages')->find($contentId);
             if (empty($regpage)) return null;
             if ($regpage->getEnabled() == 0) return null;
             $params = array();
             $params['template'] = $regpage->getTemplate();
             $params['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($regpage->getTitle(),$locale,true);
             $params['status'] = 'OK';
             $params['autorized'] = ($this->container->get('cms.cmsManager')->getCurrentUser() != null ? 1 : 0);
             if ($params['autorized'] != 0) $params['status'] = 'autorized';
             //login, password, email, fullName, captcha
             $params['id'] = $regpage->getId();
             $params['registerStep'] = 0;
             $params['login'] = '';
             $params['loginError'] = '';
             $params['passwordError'] = '';
             $params['email'] = '';
             $params['emailError'] = '';
             $params['fullName'] = '';
             $params['fullNameError'] = '';
             $params['avatar'] = '';
             $params['avatarError'] = '';
             $params['captchaEnabled'] = 0;
             $params['captchaPath'] = '';
             $params['captchaError'] = '';
             if ($regpage->getCaptchaEnabled() != 0)
             {
                 $params['captchaEnabled'] = 1;
                 $params['captchaPath'] = $this->container->get('router')->generate('basic_cms_captcha').'?target=userregister'.$regpage->getId();
                 $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_userregister'.$regpage->getId(), '');
             }
             if (($params['autorized'] == 0) && isset($result['action']) && ($result['action'] == 'object.user.register') && ($result['actionId'] == $contentId))
             {
                 // Если был экшен - записать введённые данные в переменные, проверить успешность срабатывания, если успешно - отправить E-mail или выдать успешное сообщение
                 if (isset($result['login']['value'])) $params['login'] = $result['login']['value'];
                 if (isset($result['login']['error'])) $params['loginError'] = $result['login']['error'];
                 if (isset($result['password']['error'])) $params['passwordError'] = $result['password']['error'];
                 if (isset($result['email']['value'])) $params['email'] = $result['email']['value'];
                 if (isset($result['email']['error'])) $params['emailError'] = $result['email']['error'];
                 if (isset($result['fullName']['value'])) $params['fullName'] = $result['fullName']['value'];
                 if (isset($result['fullName']['error'])) $params['fullNameError'] = $result['fullName']['error'];
                 if (isset($result['avatar']['value'])) $params['avatar'] = $result['avatar']['value'];
                 if (isset($result['avatar']['error'])) $params['avatarError'] = $result['avatar']['error'];
                 if (isset($result['captchaError'])) $params['captchaError'] = $result['captchaError'];
                 if ($result['status'] == 'OK')
                 {
                     //Успешно создали пользователя
                     $params['registredUser'] = $result['registredUser'];
                     if ($regpage->getActivationEnabled() != 0) $params['registerStep'] = 1; else $params['registerStep'] = 2; 
                 }
             }
             // Проверить на запрос подтверждения E-mail
             if (($params['autorized'] == 0) && isset($result['action']) && ($result['action'] == 'object.user.activate'))
             {
                 if (isset($result['registredUser'])) $params['registredUser'] = $result['registredUser'];
                 $params['status'] = $result['status'];
                 $params['registerStep'] = 2;
             }
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         if ($contentType == 'profile')
         {
             $profpage = $this->container->get('doctrine')->getRepository('BasicCmsBundle:UserProfilePages')->find($contentId);
             if (empty($profpage)) return null;
             if ($profpage->getEnabled() == 0) return null;
             if ($this->container->get('cms.cmsManager')->getCurrentUser() == null) {$this->container->get('cms.cmsManager')->setErrorPageNumber(1); return null;}
             $userent = $this->container->get('cms.cmsManager')->getCurrentUser();
             if ($userent->getBlocked() != 2) {$this->container->get('cms.cmsManager')->setErrorPageNumber(1); return null;}
             $params = array();
             $params['template'] = $profpage->getTemplate();
             $params['id'] = $profpage->getId();
             $params['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($profpage->getTitle(),$locale,true);
             $params['status'] = 'OK';
             $params['saveStatus'] = '';
             $params['user'] = $userent;
             $params['passwordError'] = '';
             $params['email'] = $userent->getEmail();
             $params['emailError'] = '';
             $params['fullName'] = $userent->getFullName();
             $params['fullNameError'] = '';
             $params['avatar'] = $userent->getAvatar();
             $params['avatarError'] = '';
             $params['allowEmailChange'] = $profpage->getChangeEmail();
             $params['allowPasswordChange'] = $profpage->getChangePassword();
             if (isset($result['action']) && ($result['action'] == 'object.user.profile') && ($result['actionId'] == $contentId))
             {
                 // Если был экшен - записать введённые данные в переменные, проверить успешность срабатывания, если успешно - отправить E-mail или выдать успешное сообщение
                 if (isset($result['password']['error'])) $params['passwordError'] = $result['password']['error'];
                 if (isset($result['email']['value'])) $params['email'] = $result['email']['value'];
                 if (isset($result['email']['error'])) $params['emailError'] = $result['email']['error'];
                 if (isset($result['fullName']['value'])) $params['fullName'] = $result['fullName']['value'];
                 if (isset($result['fullName']['error'])) $params['fullNameError'] = $result['fullName']['error'];
                 if (isset($result['avatar']['value'])) $params['avatar'] = $result['avatar']['value'];
                 if (isset($result['avatar']['error'])) $params['avatarError'] = $result['avatar']['error'];
                 if ($result['status'] == 'OK') $params['saveStatus'] = 'OK';
             }
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         if ($contentType == 'searchpage')
         {
             $searchpage = $this->container->get('doctrine')->getRepository('BasicCmsBundle:SearchPages')->find($contentId);
             if (empty($searchpage)) return null;
             if ($searchpage->getEnabled() == 0) return null;
             $options = @unserialize($searchpage->getObjectOptions());
             if (!is_array($options)) $options = array();
             $params = array();
             $params['template'] = $searchpage->getTemplate();
             $params['id'] = $searchpage->getId();
             $params['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($searchpage->getTitle(),$locale,true);
             $params['searchText'] = $this->container->get('request_stack')->getCurrentRequest()->get('search');
             $params['searchItems'] = array();
             $params['itemCount'] = 0;
             $params['countInPage'] = $searchpage->getCountInPage();
             if ($searchpage->getGetCountInPage() != 0)
             {
                 $cip = intval($this->container->get('request_stack')->getCurrentRequest()->get('countinpage'));
                 if (($cip > 0) && ($cip < 1000)) $params['countInPage'] = $cip;
             }
             $params['page'] = intval($this->container->get('request_stack')->getCurrentRequest()->get('page'));
             $params['pageCount'] = 1;
             if ($params['searchText'] != '')
             {
                 $cmsservices = $this->container->getServiceIds();
                 $sizes = array();
                 foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) 
                 {
                     $sizes[$item] = $this->container->get($item)->getSearchItemCount($options, '%'.$params['searchText'].'%', $locale);
                     $params['itemCount'] += $sizes[$item];
                 }
                 $params['pageCount'] = ceil($params['itemCount'] / $params['countInPage']);
                 if ($params['pageCount'] < 1) $params['pageCount'] = 1;
                 if ($params['page'] < 0) $params['page'] = 0;
                 if ($params['page'] >= $params['pageCount']) $params['page'] = $params['pageCount'] - 1;
                 $start = $params['page'] * $params['countInPage'];
                 $limit = $params['countInPage'];
                 foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) 
                 {
                     if (($start < $sizes[$item]) && ($limit > 0))
                     {
                         $savecount = count($params['searchItems']);
                         $this->container->get($item)->getSearchItems($options, '%'.$params['searchText'].'%', $locale, $searchpage->getSortDirection(), ($start > 0 ? $start : 0), $limit, $params['searchItems']);
                         $limit = $limit - count($params['searchItems']) + $savecount;
                     }
                     $start = $start - $sizes[$item];
                 }
             }
             $params['meta'] = array('title'=>$params['title'], 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
             // Опрос модулей
             $cmsservices = $this->container->getServiceIds();
             foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
             return $params;
         }
         $params = null;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) $this->container->get($item)->getFrontContent($locale, $contentType, $contentId, $result, $params);
         return $params;
     }
     
     public function setFrontAction($locale, $seoPage, $action)
     {
         $result = null;
         if ($action == 'login')
         {
             $login = $this->container->get('request_stack')->getCurrentRequest()->get('actionlogin');
             $password = $this->container->get('request_stack')->getCurrentRequest()->get('actionpassword');
             $rememberme = $this->container->get('request_stack')->getCurrentRequest()->get('actionrememberme');
             // Ищем пользователя по логину
             $user = $this->container->get('doctrine')->getRepository('BasicCmsBundle:Users')->findOneBy(array('login'=>$login));
             if (!empty($user))
             {
                 $needpass = $this->container->get('security.encoder_factory')->getEncoder($user)->encodePassword($password, $user->getSalt());
                 if ($user->getPassword() == $needpass)
                 {
                     $this->container->get('cms.cmsManager')->setCurrentUser($user);
                     // Устанавливаем пользователя в сессию
                     $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_system_autorized', $user->getId());
                     // Пишем куки
                     if ($rememberme != null)
                     {
                         $this->container->get('cms.cmsManager')->addCookie(new \Symfony\Component\HttpFoundation\Cookie('front_autorization_keytwo', md5($user->getId().'Embedded.CMS'), (time() + 3600 * 24 * 365), $path = '/'));
                         $this->container->get('cms.cmsManager')->addCookie(new \Symfony\Component\HttpFoundation\Cookie('front_autorization_keyone', md5($user->getPassword().$user->getSalt()), (time() + 3600 * 24 * 365), $path = '/'));
                     }
                     $result['status'] = 'OK';
                 } else $result['status'] = 'password error';
             } else $result['status'] = 'login error';
             $result['action'] = 'object.user.login';
         }
         if ($action == 'logout')
         {
             $this->container->get('cms.cmsManager')->setCurrentUser();
             // Устанавливаем пользователя в сессию
             $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_system_autorized', null);
             // Пишем куки
             $this->container->get('cms.cmsManager')->addCookie(new \Symfony\Component\HttpFoundation\Cookie('front_autorization_keytwo', '', (time() + 3600 * 24 * 365), $path = '/'));
             $this->container->get('cms.cmsManager')->addCookie(new \Symfony\Component\HttpFoundation\Cookie('front_autorization_keyone', '', (time() + 3600 * 24 * 365), $path = '/'));
             $result['status'] = 'OK';
             $result['action'] = 'object.user.logout';
         }
         if ($action == 'register')
         {
             // Проверить на правильность данные
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $regpage = $this->container->get('doctrine')->getRepository('BasicCmsBundle:UserRegisterPages')->find($id);
             if ((!empty($regpage)) && ($regpage->getEnabled() != 0) && ($this->container->get('cms.cmsManager')->getCurrentUser() == null))
             {
                 $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
                 $postfiles = $this->container->get('request_stack')->getCurrentRequest()->files->get('actionfiles');
                 $result['action'] = 'object.user.register';
                 $result['actionId'] = $id;
                 $result['errors'] = false;
                 $result['login']['value'] = (isset($postfields['login']) ? trim($postfields['login']) : '');
                 $result['login']['error'] = '';
                 $result['password']['value'] = (isset($postfields['password']) ? $postfields['password'] : '');
                 $result['password']['valueRepeat'] = (isset($postfields['passwordRepeat']) ? $postfields['passwordRepeat'] : '');
                 $result['password']['error'] = '';
                 $result['email']['value'] = (isset($postfields['email']) ? trim($postfields['email']) : '');
                 $result['email']['error'] = '';
                 $result['fullName']['value'] = (isset($postfields['fullName']) ? trim($postfields['fullName']) : '');
                 $result['fullName']['error'] = '';
                 $result['avatar']['value'] = (isset($postfields['avatar']) ? $postfields['avatar'] : '');
                 $result['avatar']['error'] = '';
                 if (isset($postfiles['avatar']))
                 {
                     $tmpfile = $postfiles['avatar']->getPathName();
                     if (@getimagesize($tmpfile)) 
                     {
                         $params = getimagesize($tmpfile);
                         if ($params[0] < 10) {$result['errors'] = true; $result['avatar']['error'] = 'size';}
                         if ($params[1] < 10) {$result['errors'] = true; $result['avatar']['error'] = 'size';} 
                         if ($params[0] > 6000) {$result['errors'] = true; $result['avatar']['error'] = 'size';}
                         if ($params[1] > 6000) {$result['errors'] = true; $result['avatar']['error'] = 'size';}
                         $imageTypeArray = array(0=>'', 1=>'gif', 2=>'jpg', 3=>'png', 4=>'', 5=>'', 6=>'', 7=>'', 8=>'', 9=>'jpg', 10=>'jpg', 11=>'jpg', 12=>'jpg', 13=>'', 14=>'', 15=>'', 16=>'', 17=>'', 18=>'');
                         if (!isset($imageTypeArray[$params[2]]) || ($imageTypeArray[$params[2]] == '')) {$result['errors'] = true; $result['avatar']['error'] = 'format';}
                         $basepath = '/images/user/';
                         $name = 'f_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
                         if (move_uploaded_file($tmpfile, '.'.$basepath.$name)) 
                         {
                             $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $postfiles['avatar']->getClientOriginalName());
                             $result['avatar']['value'] = $basepath.$name;
                         }
                     } else {$result['errors'] = true; $result['avatar']['error'] = 'format';}
                 }
                 if (!preg_match("/^[A-Za-z0-9\-]{5,30}$/ui", $result['login']['value'])) {$result['errors'] = true; $result['login']['error'] = 'preg';}
                 else
                 {
                     $query = $em->createQuery('SELECT count(u.id) as logincount FROM BasicCmsBundle:Users u WHERE u.login=:login')->setParameter('login', $result['login']['value']);
                     $checklogin = $query->getResult();
                     if ($checklogin[0]['logincount'] != 0) {$result['errors'] = true; $result['login']['error'] = 'exist';}
                 }
                 if (!preg_match("/^[A-Za-z0-9\-]{5,30}$/ui", $result['password']['value'])) {$result['errors'] = true; $result['password']['error'] = 'preg';} 
                 elseif ($result['password']['valueRepeat'] != $result['password']['value']) {$result['errors'] = true; $result['password']['error'] = 'diff';} 
                 if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,24})$/ui", $result['email']['value'])) {$result['errors'] = true; $result['email']['error'] = 'preg';} 
                 elseif ($this->container->getParameter('user_unique_email') == true)
                 {
                     $query = $em->createQuery('SELECT count(u.id) as emailcount FROM BasicCmsBundle:Users u WHERE u.email=:email')->setParameter('email', $result['email']['value']);
                     $checkemail = $query->getResult();
                     if ($checkemail[0]['emailcount'] != 0) {$result['errors'] = true; $result['email']['error'] = 'exist';}
                 }
                 if (!preg_match("/^[-0-9A-zА-яЁё\s\`]{3,99}$/ui", $result['fullName']['value'])) {$result['errors'] = true; $result['fullName']['error'] = 'preg';}
                 elseif ($this->container->getParameter('user_unique_fullname') == true)
                 {
                     $query = $em->createQuery('SELECT count(u.id) as namecount FROM BasicCmsBundle:Users u WHERE u.fullName=:name OR u.login=:name')->setParameter('name', $result['fullName']['value']);
                     $checkname = $query->getResult();
                     if ($checkname[0]['namecount'] != 0) {$result['errors'] = true; $result['fullName']['error'] = 'exist';}
                 }
                 if (($result['avatar']['value'] != '') && ($result['avatar']['error'] == '') && (!file_exists('.'.$result['avatar']['value']))) {$result['errors'] = true; $result['avatar']['error'] = 'not found';}
                 if ($regpage->getCaptchaEnabled() != 0)
                 {
                     $needle = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_captcha_userregister'.$regpage->getId());
                     $value = (isset($postfields['captcha']) ? $postfields['captcha'] : null);
                     if (($needle == '') || (strtoupper($value) != strtoupper($needle)) || ($value == null))
                     {
                         $result['captchaError'] = 'Value error';
                         $result['errors'] = true;
                     } else $result['captchaError'] = '';
                     $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_captcha_userregister'.$regpage->getId(), '');
                 }
                 $cmsservices = $this->container->getServiceIds();
                 foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) $this->container->get($item)->setFrontActionPrepare($locale, $seoPage, $action, $result);
                 if ($result['errors'] == false)
                 {
                     $this->container->get('cms.cmsManager')->unlockTemporaryFile($result['avatar']['value']);
                     $userent = new \Basic\CmsBundle\Entity\Users();
                     $userent->setBlocked($regpage->getActivationEnabled() != 0 ? 0 : 2);
                     $userent->setEmail($result['email']['value']);
                     $userent->setFullName($result['fullName']['value']);
                     $userent->setLogin($result['login']['value']);
                     $userent->setRegDate(new \DateTime('now'));
                     $userent->setRole($em->getRepository('BasicCmsBundle:Roles')->find($regpage->getRole()));
                     $userent->setSalt(md5(time()));
                     $userent->setAvatar($result['avatar']['value']);
                     $pass = $this->container->get('security.encoder_factory')->getEncoder($userent)->encodePassword($result['password']['value'], $userent->getSalt());
                     $userent->setPassword($pass);
                     $userent->setTemplate($regpage->getUserTemplate());
                     $userent->setUserType($regpage->getUserType());
                     $em->persist($userent);
                     $em->flush();
                     $result['registredUser'] = $userent;
                     if ($regpage->getSeopageEnabled() != 0)
                     {
                         $pageurl = $this->container->get('cms.cmsManager')->getUniqueUrl($regpage->getSeopageUrl(), array('id'=>$userent->getId(), 'login'=>$userent->getLogin(), 'fullName'=>$userent->getFullName()));
                         $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                         $pageent->setBreadCrumbs('');
                         $pageent->setBreadCrumbsRel('');
                         $pageent->setDescription('Просмотр пользователя '.$userent->getFullName());
                         $pageent->setUrl($pageurl);
                         $pageent->setTemplate($regpage->getSeopageTemplate());
                         $pageent->setModules($regpage->getSeopageModules());
                         $pageent->setLocale($regpage->getSeopageLocale());
                         $pageent->setContentType('object.user');
                         $pageent->setContentId($userent->getId());
                         $pageent->setContentAction('view');
                         $pageent->setAccess($regpage->getSeopageAccess());
                         $em->persist($pageent);
                         $em->flush();
                         if ($pageurl == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                     }
                     $result['status'] = 'OK';
                     // Послать письмо
                     if ($regpage->getActivationEnabled() != 0)
                     {
                         $key1 = md5($userent->getId().'Embedded');
                         $key2 = md5($userent->getEmail().'CMS');
                         $url = 'http://'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost().$this->container->get('request_stack')->getCurrentRequest()->getPathInfo().'?actionobject=user&action=activate&actionkey1='.$key1.'&actionkey2='.$key2;
                         $lettertext = $this->container->get('cms.cmsManager')->decodeLocalString($regpage->getLetterText(), $locale, true);
                         $lettertext = str_replace('//url//', $url, $lettertext);
                         $mailer = $this->container->get('mailer');
                         $messages = \Swift_Message::newInstance()
                                 ->setSubject($this->container->get('cms.cmsManager')->decodeLocalString($regpage->getTitle(), $locale, true))
                                 ->setFrom('register@'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost())
                                 ->setTo($userent->getEmail())
                                 ->setContentType('text/html')
                                 ->setBody($lettertext);
                         $mailer->send($messages);
                     }
                     // Запись в лог
                     $this->container->get('cms.cmsManager')->logPushFront($userent->getId(), $this->getName().'.create', 'Регистрация нового пользователя "'.$userent->getFullName().'"', $this->container->get('router')->generate('basic_cms_user_edit').'?id='.$userent->getId());
                 } else {
                     $result['status'] = 'error';
                     $mailer = $this->container->get('mailer');
                     $messages = \Swift_Message::newInstance()
                                 ->setSubject('Ошибка регистрации')
                                 ->setFrom('register@'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost())
                                 ->setTo('admin@project-earth.community')
                                 ->setContentType('text/html')
                                 ->setBody('<p>Locale:'.$locale.'</p><pre>'.print_r($result, true).'</pre>');
                     $mailer->send($messages);
                 }
             }
         }
         if ($action == 'activate')
         {
             $userid = $this->container->get('request_stack')->getCurrentRequest()->get('actionkey1');
             $usermail = $this->container->get('request_stack')->getCurrentRequest()->get('actionkey2');
             $query = $this->container->get('doctrine')->getEntityManager()->createQuery('SELECT u FROM BasicCmsBundle:Users u WHERE MD5(CONCAT(u.id,\'Embedded\')) = :userid AND MD5(CONCAT(u.email,\'CMS\')) = :usermail')->setParameter('userid', $userid)->setParameter('usermail', $usermail);
             $userent = $query->getResult();
             $result['action'] = 'object.user.activate';
             $result['status'] = 'error';
             if (isset($userent[0]) && ($userent[0]->getBlocked() == 0))
             {
                 $userent[0]->setBlocked(2);
                 $this->container->get('doctrine')->getEntityManager()->flush();
                 $result['status'] = 'OK';
                 $result['registredUser'] = $userent[0];
             }
         }
         if ($action == 'profile')
         {
             // Проверить на правильность данные
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $profpage = $this->container->get('doctrine')->getRepository('BasicCmsBundle:UserProfilePages')->find($id);
             if ((!empty($profpage)) && ($profpage->getEnabled() != 0) && ($this->container->get('cms.cmsManager')->getCurrentUser() != null) && ($this->container->get('cms.cmsManager')->getCurrentUser()->getBlocked() == 2))
             {
                 $userent = $this->container->get('cms.cmsManager')->getCurrentUser();
                 $postfields = $this->container->get('request_stack')->getCurrentRequest()->get('actionfields');
                 $postfiles = $this->container->get('request_stack')->getCurrentRequest()->files->get('actionfiles');
                 $result['action'] = 'object.user.profile';
                 $result['actionId'] = $id;
                 $result['errors'] = false;
                 $result['password']['value'] = (isset($postfields['password']) ? $postfields['password'] : '');
                 $result['password']['valueRepeat'] = (isset($postfields['passwordRepeat']) ? $postfields['passwordRepeat'] : '');
                 $result['password']['error'] = '';
                 $result['email']['value'] = (isset($postfields['email']) ? $postfields['email'] : '');
                 $result['email']['error'] = '';
                 $result['fullName']['value'] = (isset($postfields['fullName']) ? trim($postfields['fullName']) : '');
                 $result['fullName']['error'] = '';
                 $result['avatar']['value'] = (isset($postfields['avatar']) ? $postfields['avatar'] : '');
                 $result['avatar']['error'] = '';
                 if (isset($postfiles['avatar']))
                 {
                     $tmpfile = $postfiles['avatar']->getPathName();
                     if (@getimagesize($tmpfile)) 
                     {
                         $params = getimagesize($tmpfile);
                         if ($params[0] < 10) {$result['errors'] = true; $result['avatar']['error'] = 'size';}
                         if ($params[1] < 10) {$result['errors'] = true; $result['avatar']['error'] = 'size';} 
                         if ($params[0] > 6000) {$result['errors'] = true; $result['avatar']['error'] = 'size';}
                         if ($params[1] > 6000) {$result['errors'] = true; $result['avatar']['error'] = 'size';}
                         $imageTypeArray = array(0=>'', 1=>'gif', 2=>'jpg', 3=>'png', 4=>'', 5=>'', 6=>'', 7=>'', 8=>'', 9=>'jpg', 10=>'jpg', 11=>'jpg', 12=>'jpg', 13=>'', 14=>'', 15=>'', 16=>'', 17=>'', 18=>'');
                         if (!isset($imageTypeArray[$params[2]]) || ($imageTypeArray[$params[2]] == '')) {$result['errors'] = true; $result['avatar']['error'] = 'format';}
                         $basepath = '/images/user/';
                         $name = 'f_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
                         if (move_uploaded_file($tmpfile, '.'.$basepath.$name)) 
                         {
                             $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $postfiles['avatar']->getClientOriginalName());
                             $result['avatar']['value'] = $basepath.$name;
                         }
                     } else {$result['errors'] = true; $result['avatar']['error'] = 'format';}
                 }
                 if (($profpage->getChangePassword() != 0) && ($result['password']['value'] != ''))
                 {
                     if (!preg_match("/^[A-Za-z0-9\-]{5,30}$/ui", $result['password']['value'])) {$result['errors'] = true; $result['password']['error'] = 'preg';} 
                     elseif ($result['password']['valueRepeat'] != $result['password']['value']) {$result['errors'] = true; $result['password']['error'] = 'diff';} 
                 }
                 if ($profpage->getChangeEmail() != 0)
                 {
                     if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,24})$/ui", $result['email']['value'])) {$result['errors'] = true; $result['email']['error'] = 'preg';} 
                     elseif ($this->container->getParameter('user_unique_email') == true)
                     {
                         $query = $em->createQuery('SELECT count(u.id) as emailcount FROM BasicCmsBundle:Users u WHERE u.email=:email AND u.id != :id')->setParameter('email', $result['email']['value'])->setParameter('id', $userent->getId());
                         $checkemail = $query->getResult();
                         if ($checkemail[0]['emailcount'] != 0) {$result['errors'] = true; $result['email']['error'] = 'exist';}
                     }
                 }
                 if (!preg_match("/^[-0-9A-zА-яЁё\s\`]{3,99}$/ui", $result['fullName']['value'])) {$result['errors'] = true; $result['fullName']['error'] = 'preg';}
                 elseif ($this->container->getParameter('user_unique_fullname') == true)
                 {
                     $query = $em->createQuery('SELECT count(u.id) as namecount FROM BasicCmsBundle:Users u WHERE (u.fullName=:name OR u.login=:name) AND u.id != :id')->setParameter('name', $result['fullName']['value'])->setParameter('id', $userent->getId());
                     $checkname = $query->getResult();
                     if ($checkname[0]['namecount'] != 0) {$result['errors'] = true; $result['fullName']['error'] = 'exist';}
                 }
                 if (($result['avatar']['value'] != '') && ($result['avatar']['error'] == '') && (!file_exists('.'.$result['avatar']['value']))) {$result['errors'] = true; $result['avatar']['error'] = 'not found';}
                 $cmsservices = $this->container->getServiceIds();
                 foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) $this->container->get($item)->setFrontActionPrepare($locale, $seoPage, $action, $result);
                 if ($result['errors'] == false)
                 {
                     if (($userent->getAvatar() != '') && ($userent->getAvatar() != $result['avatar']['value'])) @unlink('.'.$userent->getAvatar());
                     $this->container->get('cms.cmsManager')->unlockTemporaryFile($result['avatar']['value']);
                     if ($profpage->getChangeEmail() != 0) $userent->setEmail($result['email']['value']);
                     $userent->setFullName($result['fullName']['value']);
                     $userent->setAvatar($result['avatar']['value']);
                     if (($profpage->getChangePassword() != 0) && ($result['password']['value'] != ''))
                     {
                         $pass = $this->container->get('security.encoder_factory')->getEncoder($userent)->encodePassword($result['password']['value'], $userent->getSalt());
                         $userent->setPassword($pass);
                     }
                     $em->flush();
                     $result['profileUser'] = $userent;
                     $result['status'] = 'OK';
                     // Запись в лог
                     $this->container->get('cms.cmsManager')->logPushFront($userent->getId(), $this->getName().'.edit', 'Изменение профиля пользователя "'.$userent->getFullName().'"', $this->container->get('router')->generate('basic_cms_user_edit').'?id='.$userent->getId());
                 } else $result['status'] = 'error';
             }
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) $this->container->get($item)->setFrontAction($locale, $seoPage, $action, $result);
         return $result;
     }
     
     public function getModuleTypes()
     {
         $contents = array('autorization' => 'Модуль авторизации', 'useronline' => 'Модуль онлайн пользователей');
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) $this->container->get($item)->getModuleTypes($contents);
         return $contents;
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result = null)
     {
         $em = $this->container->get('doctrine')->getEntityManager();
         $conn = $em->getConnection();
         $params = null;
         if ($moduleType == 'autorization')
         {
             $params = array();
             $params['user'] = $this->container->get('cms.cmsManager')->getCurrentUser();
             $params['autorized'] = ($params['user'] != null ? 1 : 0);
             $params['blocked'] = ((($params['user'] != null) && ($params['user']->getBlocked() != 2)) ? 1 : 0);
             if (isset($result['action']) && ($result['action'] == 'object.user.login')) $params['status'] = $result['status']; else $params['status'] = 'OK';
         }
         if ($moduleType == 'useronline')
         {
             $params = array();
             $date = new \DateTime('-'.$parameters['timeOnline'].' minutes');
             $query = $em->createQuery('SELECT u.id, u.login, u.fullName, u.avatar FROM BasicCmsBundle:Users u WHERE u.visitDate >= :date ORDER BY u.visitDate DESC')->setParameter('date', $date);
             $users = $query->getResult();
             $params['users'] = $users;
         }
         if ($params != null) return $params;
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             if (strpos($item,'addone.user.') === 0) $params = $this->container->get($item)->getFrontModule($locale, $seoPage, $moduleType, $parameters, $result);
             if ($params != null) return $params;
         }
         return null;
     }
     
    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        if ($module == 'autorization')
        {
            $parameters = array();
            $parameterserror = array();
            //$parameters['captchaEnabled'] = '';
            //if (isset($nullparameters['captchaEnabled'])) $parameters['captchaEnabled'] = $nullparameters['captchaEnabled'];
            //$parameterserror['captchaEnabled'] = '';
            $errors = false;
            //if ($request->getMethod() == 'POST')
            //{
            //    $postparameters = $request->get('parameters');
            //    if (isset($postparameters['captchaEnabled'])) $parameters['captchaEnabled'] = intval($postparameters['captchaEnabled']); else $parameters['captchaEnabled'] = 0;
            //}
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
                return $this->container->get('templating')->render('BasicCmsBundle:Default:autorizationCfg.html.twig',array('parameters' => $parameters,'parameterserror' => $parameterserror));
            }
        }
        if ($module == 'useronline')
        {
            $parameters = array();
            $parameterserror = array();
            $parameters['timeOnline'] = '';
            if (isset($nullparameters['timeOnline'])) $parameters['timeOnline'] = $nullparameters['timeOnline'];
            $parameterserror['timeOnline'] = '';
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postparameters = $request->get('parameters');
                if (isset($postparameters['timeOnline'])) $parameters['timeOnline'] = intval($postparameters['timeOnline']); else $parameters['timeOnline'] = 0;
                if (($parameters['timeOnline'] > 60) || ($parameters['timeOnline'] < 1)) {$errors = true; $parameterserror['timeOnline'] = 'Время должно быть в пределах от 1 до 60 минут';}
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
                return $this->container->get('templating')->render('BasicCmsBundle:Default:useronlineCfg.html.twig',array('parameters' => $parameters,'parameterserror' => $parameterserror));
            }
        }
        $answer = null;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.user.') === 0) $answer = $this->container->get($item)->getAdminModuleController($request, $module, $actionType, $nullparameters);
            if ($answer != null) return $answer;
        }
        return null;
    }
    
    public function getInfoTitle($contentType, $contentId)
    {
         if ($contentType == 'view')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT u.login FROM BasicCmsBundle:Users u WHERE u.id = :id')->setParameter('id', $contentId);
             $user = $query->getResult();
             if (isset($user[0]['login'])) return $this->container->get('cms.cmsManager')->encodeLocalString(array('default' => $user[0]['login']));
         }
         if ($contentType == 'auth')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT p.title FROM BasicCmsBundle:UserAuthPages p WHERE p.id = :id')->setParameter('id', $contentId);
             $page = $query->getResult();
             if (isset($page[0]['title'])) return $page[0]['title'];
         }
         if ($contentType == 'password')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT p.title FROM BasicCmsBundle:UserPasswordPages p WHERE p.id = :id')->setParameter('id', $contentId);
             $page = $query->getResult();
             if (isset($page[0]['title'])) return $page[0]['title'];
         }
         if ($contentType == 'register')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT p.title FROM BasicCmsBundle:UserRegisterPages p WHERE p.id = :id')->setParameter('id', $contentId);
             $page = $query->getResult();
             if (isset($page[0]['title'])) return $page[0]['title'];
         }
         if ($contentType == 'profile')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT p.title FROM BasicCmsBundle:UserProfilePages p WHERE p.id = :id')->setParameter('id', $contentId);
             $page = $query->getResult();
             if (isset($page[0]['title'])) return $page[0]['title'];
         }
         if ($contentType == 'searchpage')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT p.title FROM BasicCmsBundle:SearchPages p WHERE p.id = :id')->setParameter('id', $contentId);
             $page = $query->getResult();
             if (isset($page[0]['title'])) return $page[0]['title'];
         }
         $cmsservices = $this->container->getServiceIds();
         foreach ($cmsservices as $item) 
         {
             $answer = null;
             if (strpos($item,'addone.user.') === 0) $answer = $this->container->get($item)->getInfoTitle($contentType, $contentId);
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
             if (strpos($item,'addone.user.') === 0) $answer = $this->container->get($item)->getInfoSitemap($contentType, $contentId);
             if ($answer != null) return $answer;
         }
         return null;
    }

//**************************************************
// Поиск по сайту
//**************************************************

    public function getSearchOptions(&$options)
    {
        $options['object.user.on'] = 'Искать информацию о пользователях';
        $options['object.user.onlypage'] = 'Искать информацию о пользователях только среди страниц сайта';
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.user.') === 0) $this->container->get($item)->getSearchOptions($options);
        }
    }
    
    public function getSearchItemCount($options, $searchstring, $locale)
    {
        $count = 0;
        if (isset($options['object.user.on']) && ($options['object.user.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT count(u.id) as usercount FROM BasicCmsBundle:Users u '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.user\' AND sp.contentAction = \'view\' AND sp.contentId = u.id '.
                                      'WHERE (u.login LIKE :searchstring OR u.fullName LIKE :searchstring)'.
                                      ((isset($options['object.user.onlypage']) && ($options['object.user.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : ''))->setParameter('searchstring', $searchstring);
            $usercount = $query->getResult();
            if (isset($usercount[0]['usercount'])) $count += $usercount[0]['usercount'];
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.user.') === 0) $count += $this->container->get($item)->getSearchItemCount($options, $searchstring, $locale);
        }
        return $count;
    }
    
    public function getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, &$items)
    {
        if (isset($options['object.user.on']) && ($options['object.user.on'] != 0))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $query = $em->createQuery('SELECT \'user\' as object, u.id, u.login as title, u.regDate as modifyDate, u.fullName as description, u.avatar, u.id as createrId, u.avatar as createrAvatar, u.login as createrLogin, u.fullName as createrFullName, sp.url FROM BasicCmsBundle:Users u '.
                                      'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.user\' AND sp.contentAction = \'view\' AND sp.contentId = u.id '.
                                      'WHERE (u.login LIKE :searchstring OR u.fullName LIKE :searchstring)'.
                                      ((isset($options['object.user.onlypage']) && ($options['object.user.onlypage'] != 0)) ? ' AND sp.id IS NOT NULL' : '').
                                      ($sortdir == 0 ? ' ORDER BY u.regDate ASC' : ' ORDER BY u.regDate DESC'))->setParameter('searchstring', $searchstring)->setFirstResult($start)->setMaxResults($limit);
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
            if (strpos($item,'addone.user.') === 0) $this->container->get($item)->getSearchItems($options, $searchstring, $locale, $sortdir, $start, $limit, $items);
        }
    }
    
//****************************************
// Справочная система
//****************************************

    public function getHelpChapters()
    {
        $chapters = array();
        $chapters['basiccms1'] = 'Введение';
        $chapters['basiccms2'] = 'Страницы сайта и система шаблонов';
        $chapters['basiccms3'] = 'Локализация';
        $chapters['d1537bd0b66a4289001629ac2e06e742'] = 'Главная страница CMS';
        $chapters['11c70d111af0888db539cf12c24ccd91'] = 'Статистика';
        $chapters['d3df9963e78597ac7b243cf1ffe6f16d'] = 'Пользователи';

        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.user.') === 0) $this->container->get($item)->getHelpChapters($chapters);
        }
        return $chapters;
    }
    
    public function getHelpContent($page, &$answer)
    {
        if ($page == 'basiccms1') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:begin1.html.twig',array());
        if ($page == 'basiccms2') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:begin2.html.twig',array());
        if ($page == 'basiccms3') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:begin3.html.twig',array());
        if ($page == 'd1537bd0b66a4289001629ac2e06e742') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:index.html.twig',array());
        if ($page == '11c70d111af0888db539cf12c24ccd91') $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:statistic.html.twig',array());
        if (($page == 'd3df9963e78597ac7b243cf1ffe6f16d')) $answer .= $this->container->get('templating')->render('BasicCmsBundle:Help:users.html.twig',array());
        
        
        
        
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) 
        {
            if (strpos($item,'addone.user.') === 0) $answer .= $this->container->get($item)->getHelpContent($page);
        }
        return $answer;
    }
    
}
