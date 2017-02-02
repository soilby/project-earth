<?php

namespace Extended\AlertBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Facebook\Facebook;


class DefaultController extends Controller
{
    public function indexAction()
    {
        if ($this->getUser()->checkAccess('user_alert') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Оповещения пользователей',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $saveparameters = $this->container->get('cms.cmsManager')->getConfigValue('alerts.config');
        
        $objectTypes = array(
            'project.create' => 'Создание документа',
            'project.edit' => 'Редактирование документа',
            'chat.post' => 'Оффлайн сообщение чата',
            
        );
        $objectTypesMiniNote = array(
            'project.create' => 'Доступны подстановки //id//, //title//, //projectTitle//, //url//',
            'project.edit' => 'Доступны подстановки //id//, //title//, //projectTitle//, //url//',
            'chat.post' => 'Доступны подстановки //id//, //chatId//, //title//, //message//',
            
        );
        
        if (!is_array($saveparameters))
        {
            $saveparameters = array();
        }
        $parameters = array();
        foreach ($objectTypes as $objectTypesName=>$objectTypesDescription)
        {
            if (!isset($saveparameters[$objectTypesName]))
            {
                $saveparameters[$objectTypesName] = array();
            }
            if (!isset($saveparameters[$objectTypesName]['title'])) 
            {
                $saveparameters[$objectTypesName]['title'] = '';
            }
            if (!isset($saveparameters[$objectTypesName]['body'])) 
            {
                $saveparameters[$objectTypesName]['body'] = '';
            }
            $parameters[$objectTypesName]['title'] = array('default' => $this->container->get('cms.cmsManager')->decodeLocalString($saveparameters[$objectTypesName]['title'], 'default', false));
            $parameters[$objectTypesName]['body'] = array('default' => $this->container->get('cms.cmsManager')->decodeLocalString($saveparameters[$objectTypesName]['body'], 'default', false));
            foreach ($locales as $locale)
            {
                $parameters[$objectTypesName]['title'][$locale['shortName']] = $this->container->get('cms.cmsManager')->decodeLocalString($saveparameters[$objectTypesName]['title'], $locale['shortName'], false);
                $parameters[$objectTypesName]['body'][$locale['shortName']] = $this->container->get('cms.cmsManager')->decodeLocalString($saveparameters[$objectTypesName]['body'], $locale['shortName'], false);
            }
            
        }
        $errors = array();
        $activetab = null;
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            $posttitle = $this->get('request_stack')->getMasterRequest()->get('title');
            $postbody = $this->get('request_stack')->getMasterRequest()->get('body');
            foreach ($objectTypes as $objectTypesName=>$objectTypesDescription)
            {
                $parameters[$objectTypesName]['title'] = array('default' => '');
                $parameters[$objectTypesName]['body'] = array('default' => '');
                if (isset($posttitle[$objectTypesName]['default']))
                {
                    $parameters[$objectTypesName]['title']['default'] = $posttitle[$objectTypesName]['default'];
                }
                if (isset($postbody[$objectTypesName]['default']))
                {
                    $parameters[$objectTypesName]['body']['default'] = $postbody[$objectTypesName]['default'];
                }
                if (!preg_match("/^.{3,}$/ui", $parameters[$objectTypesName]['title']['default']))
                {
                    $errors[$objectTypesName]['title']['default'] = 'Поле должно содержать более 3 символов';
                }
                if (!preg_match("/^[\s\S]{3,}$/ui", $parameters[$objectTypesName]['body']['default']))
                {
                    $errors[$objectTypesName]['body']['default'] = 'Поле должно содержать более 3 символов';
                }
                foreach ($locales as $locale)
                {
                    $parameters[$objectTypesName]['title'][$locale['shortName']] = '';
                    $parameters[$objectTypesName]['body'][$locale['shortName']] = '';
                    if (isset($posttitle[$objectTypesName][$locale['shortName']]))
                    {
                        $parameters[$objectTypesName]['title'][$locale['shortName']] = $posttitle[$objectTypesName][$locale['shortName']];
                    }
                    if (isset($postbody[$objectTypesName][$locale['shortName']]))
                    {
                        $parameters[$objectTypesName]['body'][$locale['shortName']] = $postbody[$objectTypesName][$locale['shortName']];
                    }
                    if (!preg_match("/^(.{3,})?$/ui", $parameters[$objectTypesName]['title'][$locale['shortName']]))
                    {
                        $errors[$objectTypesName]['title'][$locale['shortName']] = 'Поле должно содержать более 3 символов или оставаться пустым';
                    }
                    if (!preg_match("/^([\s\S]{3,})?$/ui", $parameters[$objectTypesName]['body'][$locale['shortName']]))
                    {
                        $errors[$objectTypesName]['body'][$locale['shortName']] = 'Поле должно содержать более 3 символов или оставаться пустым';
                    }
                }
                if (($activetab == null) && (count($errors) > 0))
                {
                    $activetab = $objectTypesName;
                }
            }
            // save
            if (count($errors) == 0)
            {
                $saveparameters = array();
                foreach ($objectTypes as $objectTypesName=>$objectTypesDescription)
                {
                    $saveparameters[$objectTypesName] = array();
                    $saveparameters[$objectTypesName]['title'] = $this->container->get('cms.cmsManager')->encodeLocalString($parameters[$objectTypesName]['title']);
                    $saveparameters[$objectTypesName]['body'] = $this->container->get('cms.cmsManager')->encodeLocalString($parameters[$objectTypesName]['body']);
                }                
                $this->container->get('cms.cmsManager')->setConfigValue('alerts.config', $saveparameters);
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Оповещения пользователей',
                    'message'=>'Настройки успешно изменены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('extended_alert_config'))
                ));
            }
        }        
        if ($activetab == null)
        {
            $activetab = array_keys($objectTypes);
            $activetab = $activetab[0];
        }
        return $this->render('ExtendedAlertBundle:Default:index.html.twig', array(
            'parameters' => $parameters, 
            'errors' => $errors, 
            'locales' => $locales,
            'objectTypes' => $objectTypes,
            'objectTypesMiniNote' => $objectTypesMiniNote,
            'activetab' => $activetab,
        ));
    }
    
    
    public function socialAction()
    {
        if ($this->getUser()->checkAccess('user_social') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Социальные сети',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT m.name, m.id FROM BasicCmsBundle:Modules m WHERE m.objectName = \'object.module\' AND m.moduleType = \'menu\'');
        $modules = $query->getResult();
        if (!is_array($modules)) $modules = array();
        
        $descriptionarray = $this->container->get('cms.cmsManager')->getConfigValue('alerts.facebookimage');
        $descriptionarray = explode('###', $descriptionarray);
        $title = '';
        $description = '';
        $message = '';
        if (isset($descriptionarray[1]))
        {
            $description = $descriptionarray[1];
        }
        if (isset($descriptionarray[2]))
        {
            $title = $descriptionarray[2];
        }
        if (isset($descriptionarray[3]))
        {
            $message = $descriptionarray[3];
        }
        
        $facebook = $this->container->get('cms.cmsManager')->getConfigValue('alerts.facebookcfg');
        
        if (($this->get('request_stack')->getMasterRequest()->getMethod() == 'POST') && ($this->get('request_stack')->getMasterRequest()->get('facebook') != null))
        {
            $facebook = $this->get('request_stack')->getMasterRequest()->get('facebook');
            if (!is_array($facebook)) 
            {
                $facebook = array('appid' => '', 'secret' => '', 'token' => '', 'group' => '');
            }
            $this->container->get('cms.cmsManager')->setConfigValue('alerts.facebookcfg', $facebook);
        }

        $vkontakte = $this->container->get('cms.cmsManager')->getConfigValue('alerts.vkontaktecfg');
        
        if (($this->get('request_stack')->getMasterRequest()->getMethod() == 'POST') && ($this->get('request_stack')->getMasterRequest()->get('vkontakte') != null))
        {
            $vkontakte = $this->get('request_stack')->getMasterRequest()->get('vkontakte');
            if (!is_array($vkontakte)) 
            {
                $vkontakte = array('appid' => '', 'secret' => '', 'token' => '', 'group' => '');
            }
            $this->container->get('cms.cmsManager')->setConfigValue('alerts.vkontaktecfg', $vkontakte);
        }
        
        return $this->render('ExtendedAlertBundle:Default:social.html.twig', array(
            'modules' => $modules,
            'facebook' => $facebook,
            'vkontakte' => $vkontakte,
            'description' => $description,
            'title' => $title,
            'message' => $message,
        ));
    }

    public function facebookUpdateImageAction()
    {
        if ($this->getUser()->checkAccess('user_social') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Социальные сети',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $module = intval($this->get('request_stack')->getMasterRequest()->get('module'));
        $description = trim($this->get('request_stack')->getMasterRequest()->get('description'));
        $title = trim($this->get('request_stack')->getMasterRequest()->get('title'));
        $message = trim($this->get('request_stack')->getMasterRequest()->get('message'));
        
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT m.name, m.id, m.parameters FROM BasicCmsBundle:Modules m WHERE m.objectName = \'object.module\' AND m.moduleType = \'menu\' AND m.id = :module')->setParameter('module', $module);
        $module = $query->getSingleResult();
        $module['parameters'] = @unserialize($module['parameters']);
        $items = array();
        if (isset($module['parameters']['items']) && (is_array($module['parameters']['items']))) 
        {
            foreach ($module['parameters']['items'] as $item)
            {
                if (isset($item['nesting']) && isset($item['enabled']) && isset($item['type']) && isset($item['object']) && isset($item['action']) && isset($item['contentId']) && isset($item['url']) && isset($item['name']))
                {
                    if (($item['nesting'] == 0) && ($item['enabled'] != 0))
                    {
                        $items[] = array('nesting'=>$item['nesting'], 'enabled'=>$item['enabled'], 'name'=>$this->container->get('cms.cmsManager')->decodeLocalString($item['name'], 'default', true), 'nameen'=>$this->container->get('cms.cmsManager')->decodeLocalString($item['name'], 'en', true), 'url'=>$item['url']);
                    }
                }
            }
        }
        // Генерация картинки
        $height = 60 + 32 * count($items);
        $height = max($height, 260);
        $image = imagecreate (470, $height);
        
        $black = imagecolorallocate ($image, 0, 0, 0);
        $white = imagecolorallocate ($image, 255, 255, 255);
        $biruza = imagecolorallocate ($image, 24, 83, 89);
        $gray = imagecolorallocate ($image, 204, 204, 204);
        
        imagefill ($image, 0, 0, $white);
        
        imagettftext ($image, 18, 0, 5, 30, $biruza, __DIR__."/../Resources/views/Default/font.ttf", "Main menu");
        imageline($image, 0, 40, 230, 40, $gray);
        imagettftext ($image, 18, 0, 245, 30, $biruza, __DIR__."/../Resources/views/Default/font.ttf", "Главное меню");
        imageline($image, 240, 40, 470, 40, $gray);
        $i = 0;
        foreach ($items as $item)
        {
            imagettftext ($image, 12, 0, 35, 62 + 32 * $i, $black, __DIR__."/../Resources/views/Default/font.ttf", $item['nameen']);
            imagefilledellipse ($image, 25, 56 + 32 * $i, 6, 6, $black);
            imageline($image, 15, 72 + 32 * $i, 230, 72 + 32 * $i, $gray);
            imagettftext ($image, 12, 0, 275, 62 + 32 * $i, $black, __DIR__."/../Resources/views/Default/font.ttf", $item['name']);
            imagefilledellipse ($image, 265, 56 + 32 * $i, 6, 6, $black);
            imageline($image, 255, 72 + 32 * $i, 470, 72 + 32 * $i, $gray);
            $i++;
        }
        
        $filename = '/images/facebook_'.md5(time()).'.jpg';
        /*$source = imagecreatefromjpeg('img/logo.jpg');
        imagecopyresampled($image, $source, 73 , 10, 0, 0, 217, 239, 217, 239);*/
        imagejpeg($image, '.'.$filename, 95);
        
        // сохранение имени файла
        $oldfilename = $this->container->get('cms.cmsManager')->getConfigValue('alerts.facebookimage');
        $oldfilename = explode('###', $oldfilename);
        
        if (isset($oldfilename[0]) && ($oldfilename[0] != '') && (file_exists('.'.$oldfilename[0])))
        {
            @unlink('.'.$oldfilename[0]);
        }
        $this->container->get('cms.cmsManager')->setConfigValue('alerts.facebookimage', $filename.'###'.$description. '###'.$title.'###'.$message);
        
            $url = "http://".$this->get('request_stack')->getMasterRequest()->getHttpHost().'/';
            $vars = array('id' => $url, 'scrape' => 'true');
            $body = http_build_query($vars);
            $fp = fsockopen('ssl://graph.facebook.com', 443);
            fwrite($fp, "POST / HTTP/1.1\r\n");
            fwrite($fp, "Host: graph.facebook.com\r\n");
            fwrite($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
            fwrite($fp, "Content-Length: ".strlen($body)."\r\n");
            fwrite($fp, "Connection: close\r\n");
            fwrite($fp, "\r\n");
            fwrite($fp, $body);
            fclose($fp);

        return new \Symfony\Component\HttpFoundation\Response(json_encode(array('result' => 'OK')));
    }
    
    
    
    public function facebookUpdateAction()
    {
        if ($this->getUser()->checkAccess('user_social') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Социальные сети',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        /*$module = intval($this->get('request_stack')->getMasterRequest()->get('module'));
        $description = trim($this->get('request_stack')->getMasterRequest()->get('description'));
        $title = trim($this->get('request_stack')->getMasterRequest()->get('title'));
        $message = trim($this->get('request_stack')->getMasterRequest()->get('message'));
        
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT m.name, m.id, m.parameters FROM BasicCmsBundle:Modules m WHERE m.objectName = \'object.module\' AND m.moduleType = \'menu\' AND m.id = :module')->setParameter('module', $module);
        $module = $query->getSingleResult();
        $module['parameters'] = @unserialize($module['parameters']);
        $items = array();
        if (isset($module['parameters']['items']) && (is_array($module['parameters']['items']))) 
        {
            foreach ($module['parameters']['items'] as $item)
            {
                if (isset($item['nesting']) && isset($item['enabled']) && isset($item['type']) && isset($item['object']) && isset($item['action']) && isset($item['contentId']) && isset($item['url']) && isset($item['name']))
                {
                    if (($item['nesting'] == 0) && ($item['enabled'] != 0))
                    {
                        $items[] = array('nesting'=>$item['nesting'], 'enabled'=>$item['enabled'], 'name'=>$this->container->get('cms.cmsManager')->decodeLocalString($item['name'], 'default', true), 'nameen'=>$this->container->get('cms.cmsManager')->decodeLocalString($item['name'], 'en', true), 'url'=>$item['url']);
                    }
                }
            }
        }
        // Генерация картинки
        $height = 60 + 32 * count($items);
        $height = max($height, 260);
        $image = imagecreate (470, $height);
        
        $black = imagecolorallocate ($image, 0, 0, 0);
        $white = imagecolorallocate ($image, 255, 255, 255);
        $biruza = imagecolorallocate ($image, 24, 83, 89);
        $gray = imagecolorallocate ($image, 204, 204, 204);
        
        imagefill ($image, 0, 0, $white);
        
        imagettftext ($image, 18, 0, 5, 30, $biruza, __DIR__."/../Resources/views/Default/font.ttf", "Main menu");
        imageline($image, 0, 40, 230, 40, $gray);
        imagettftext ($image, 18, 0, 245, 30, $biruza, __DIR__."/../Resources/views/Default/font.ttf", "Главное меню");
        imageline($image, 240, 40, 470, 40, $gray);
        $i = 0;
        foreach ($items as $item)
        {
            imagettftext ($image, 12, 0, 35, 62 + 32 * $i, $black, __DIR__."/../Resources/views/Default/font.ttf", $item['nameen']);
            imagefilledellipse ($image, 25, 56 + 32 * $i, 6, 6, $black);
            imageline($image, 15, 72 + 32 * $i, 230, 72 + 32 * $i, $gray);
            imagettftext ($image, 12, 0, 275, 62 + 32 * $i, $black, __DIR__."/../Resources/views/Default/font.ttf", $item['name']);
            imagefilledellipse ($image, 265, 56 + 32 * $i, 6, 6, $black);
            imageline($image, 255, 72 + 32 * $i, 470, 72 + 32 * $i, $gray);
            $i++;
        }
        
        $filename = '/images/facebook_'.md5(time()).'.jpg';
        imagejpeg($image, '.'.$filename, 95);
        
        // сохранение имени файла
        $oldfilename = $this->container->get('cms.cmsManager')->getConfigValue('alerts.facebookimage');
        $oldfilename = explode('###', $oldfilename);
        
        if (isset($oldfilename[0]) && ($oldfilename[0] != '') && (file_exists('.'.$oldfilename[0])))
        {
            @unlink('.'.$oldfilename[0]);
        }
        $this->container->get('cms.cmsManager')->setConfigValue('alerts.facebookimage', $filename.'###'.$description. '###'.$title.'###'.$message);
        */
        
        $configvalue = explode('###', $this->container->get('cms.cmsManager')->getConfigValue('alerts.facebookimage'));
        if (!isset($configvalue[0]) || !isset($configvalue[1]) || !isset($configvalue[2]) || !isset($configvalue[3])) 
        {
            return new \Symfony\Component\HttpFoundation\Response(json_encode(array('result' => 'Error', 'message' => 'Ошибка')));
        }
        $message = $configvalue[3];
        
        
        // Обновление информации в facebook
        $facebookcfg = $this->container->get('cms.cmsManager')->getConfigValue('alerts.facebookcfg');
        if (!is_array($facebookcfg))
        {
            $facebookcfg = array();
        }
        if (isset($facebookcfg['appid']) && isset($facebookcfg['secret']) && isset($facebookcfg['token']) && isset($facebookcfg['group']) && ($facebookcfg['appid'] != '') && ($facebookcfg['secret'] != '') && ($facebookcfg['token'] != '') && ($facebookcfg['group'] != ''))
        {
            $url = "http://".$this->get('request_stack')->getMasterRequest()->getHttpHost().'/';

            $facebook = new Facebook(array(
                'app_id'  => $facebookcfg['appid'],
                'app_secret' => $facebookcfg['secret'],
                'default_access_token' => $facebookcfg['token'],
            ));

            /*$facebook->post('', array (
                'id' => $url,
                'scrape' => 'true',
            ));*/

            /*$previd = $this->container->get('cms.cmsManager')->getConfigValue('alerts.facebookmsgid');
            if ($previd != '') 
            {
                $facebook->delete('/'.$previd);
            }*/
            try {
                $response = $facebook->post('/'.$facebookcfg['group'].'/feed', array (
                    'link' => $url,
                    'message' => $message,
                ));
                $graphObject = $response->getGraphObject();

                if ($graphObject->getProperty('id') != '') 
                {
                    $this->container->get('cms.cmsManager')->setConfigValue('alerts.facebookmsgid', $graphObject->getProperty('id'));
                }
            } catch (\Exception $e) {
                
            }
        }
        $vkcfg = $this->container->get('cms.cmsManager')->getConfigValue('alerts.vkontaktecfg');
        if (!is_array($vkcfg))
        {
            $vkcfg = array();
        }
        if (isset($vkcfg['appid']) && isset($vkcfg['secret']) && isset($vkcfg['token']) && isset($vkcfg['group']) && ($vkcfg['appid'] != '') && ($vkcfg['secret'] != '') && ($vkcfg['token'] != '') && ($vkcfg['group'] != ''))
        {
            $url = "http://".$this->get('request_stack')->getMasterRequest()->getHttpHost().'/';
            $vkurl = "https://api.vk.com/method/wall.post?access_token={$vkcfg['token']}";
            $data = array(
                'owner_id' => 0 - intval($vkcfg['group']),
                'friends_only' => 0,
                'from_group' => 1,
                'message' => $message,
                'attachments' => $url,
                'signed' => 1,
            );
            $ch = curl_init($vkurl);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
        }        
        return new \Symfony\Component\HttpFoundation\Response(json_encode(array('result' => 'OK')));
    }
    
    public function facebookGetTokenAction()
    {
        if ($this->getUser()->checkAccess('user_social') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Социальные сети',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $facebookcfg = $this->container->get('cms.cmsManager')->getConfigValue('alerts.facebookcfg');
        
        if (!is_array($facebookcfg))
        {
            $facebookcfg = array();
        }
        if (!isset($facebookcfg['appid']) || !isset($facebookcfg['secret']) || !isset($facebookcfg['group']) || ($facebookcfg['appid'] == '') || ($facebookcfg['secret'] == '') || ($facebookcfg['group'] == ''))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Социальные сети',
                'message'=>'Настройки не найдены', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $facebook = new Facebook(array(
            'app_id'  => $facebookcfg['appid'],
            'app_secret' => $facebookcfg['secret'],
        ));
        
        $helper = $facebook->getRedirectLoginHelper();
        try 
        {
            $accessToken = $helper->getAccessToken();
            
        } catch(\Facebook\Exceptions\FacebookSDKException $e) 
        {
            $accessToken = null;
        }
        
        if ($accessToken) 
        {
            $oAuth2Client = $facebook->getOAuth2Client();
            $longToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            $request = $facebook->get('/me/accounts?fields=name,access_token,perms', $longToken);
            
            $pagelist = $request->getGraphEdge()->asArray();
            foreach ($pagelist as $page)
            {
                if ($page['id'] == $facebookcfg['group'])
                {
                    $longToken = $page['access_token'];
                }
            }
            $facebookcfg['token'] = $longToken;
            $this->container->get('cms.cmsManager')->setConfigValue('alerts.facebookcfg', $facebookcfg);
            return new \Symfony\Component\HttpFoundation\RedirectResponse($this->generateUrl('extended_alert_social'));
        } else {
            $permissions = ['manage_pages'];
            $loginUrl = $helper->getLoginUrl($this->generateUrl('extended_alert_social_facebookgettoken', array(), true), $permissions);
            return new \Symfony\Component\HttpFoundation\RedirectResponse($loginUrl);
        }
    }    
    
    public function vkontakteGetTokenAction()
    {
        if ($this->getUser()->checkAccess('user_social') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Социальные сети',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $vkcfg = $this->container->get('cms.cmsManager')->getConfigValue('alerts.vkontaktecfg');
        
        if (!is_array($vkcfg))
        {
            $vkcfg = array();
        }
        if (!isset($vkcfg['appid']) || !isset($vkcfg['secret']) || !isset($vkcfg['group']) || ($vkcfg['appid'] == '') || ($vkcfg['secret'] == '') || ($vkcfg['group'] == ''))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Социальные сети',
                'message'=>'Настройки не найдены', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $code = $this->get('request_stack')->getMasterRequest()->get('code');
        $mUrl = $this->generateUrl('extended_alert_social_vkontaktegettoken', array(), true);
        if ($code) {
            $sUrl = "https://api.vk.com/oauth/access_token?client_id={$vkcfg['appid']}&client_secret={$vkcfg['secret']}&code=$code&redirect_uri=$mUrl";
            $oResponce = json_decode(file_get_contents($sUrl));
            $vkcfg['token'] = $oResponce->access_token;
            $this->container->get('cms.cmsManager')->setConfigValue('alerts.vkontaktecfg', $vkcfg);
            return new \Symfony\Component\HttpFoundation\RedirectResponse($this->generateUrl('extended_alert_social'));
        } else {
            $mUrl = 'https://oauth.vk.com/blank.html';
            $sUrl = "http://api.vk.com/oauth/authorize?client_id={$vkcfg['appid']}&scope=offline,wall,groups&redirect_uri=$mUrl&response_type=token&display=page&v=5.52";
            return new \Symfony\Component\HttpFoundation\RedirectResponse($sUrl);
        }
    }    
    
    
    public function subscribeAction()
    {
        if ($this->getUser()->checkAccess('user_subscribe') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Рассылка',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $subject = $this->get('request_stack')->getMasterRequest()->get('subject');
        $from = $this->get('request_stack')->getMasterRequest()->get('from');
        $message = $this->get('request_stack')->getMasterRequest()->get('message');
        $result = false;
        if (($subject != null) && ($from != null) && ($message != null)) {
            // Отправка сообщения
            $em = $this->getDoctrine()->getEntityManager();
            $query = $em->createQuery('SELECT u.email FROM BasicCmsBundle:Users u WHERE u.blocked = 2');
            $emails = $query->getResult();
            if (count($emails) > 0)
            {
                $mailer = $this->container->get('mailer');
                foreach ($emails as $email)
                {
                    $messageEnt = \Swift_Message::newInstance()
                        ->setSubject($subject)
                        ->setTo($email['email'])
                        ->setFrom($from)
                        ->setContentType('text/html')
                        ->setBody($message);
                    $mailer->send($messageEnt);
                }
            }
            $message = '';
            $subject = '';
            $from = '';
            $result = true;
        }
        if ($from == '') {
            $from = 'admin@'.$this->get('request_stack')->getMasterRequest()->getHttpHost();
        }
        
        return $this->render('ExtendedAlertBundle:Default:subscribe.html.twig', array(
            'subject' => $subject,
            'from' => $from,
            'message' => $message,
            'result' => $result,
        ));
    }

    
    
}
