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

class DefaultController extends Controller
{
// *******************************************
// Генерация капчи
// *******************************************    
    
    private function colorHexToRgb($cvet) 
    {
        if ($cvet[0] == '#') $cvet = substr($cvet, 1);
        if (strlen($cvet) == 6) 
        {
            list($r, $g, $b) = array($cvet[0].$cvet[1], $cvet[2].$cvet[3], $cvet[4].$cvet[5]);
        } elseif (strlen($cvet) == 3) 
        {
            list($r, $g, $b) = array($cvet[0].$cvet[0], $cvet[1].$cvet[1], $cvet[2].$cvet[2]);
        } else 
        {
            return array(null, null, null);
        }
        $r = @hexdec($r);
        $g = @hexdec($g);
        $b = @hexdec($b);
        return array($r, $g, $b);
    }
    
    private function getCoordX($x, $y, $z, $phase, $angle, $scale, $pos)
    {
        return $pos + $x * cos ($angle / 180 * pi()) * $scale - $y * sin ($angle / 180 * pi()) * $scale - $z * 3;// + sin(($phase + $y) / 5 * pi()) * 1;
    }

    private function getCoordY($x, $y, $z, $phase, $angle, $scale, $pos)
    {
        return $pos + $y * cos ($angle / 180 * pi()) * $scale + $x * sin ($angle / 180 * pi()) * $scale - $z * 4 + sin(($phase + $x) / 20 * pi()) * 2;
    }
    
    public function captchaAction()
    {
        $captchaType = $this->container->getParameter('captcha_type');
        $sizex = $this->container->getParameter('captcha_sizex');
        $sizey = $this->container->getParameter('captcha_sizey');
        $backcolor = $this->container->getParameter('captcha_backcolor');
        $color = $this->container->getParameter('captcha_color');
        $noise = $this->container->getParameter('captcha_noise');
        $target = '';
        if ($this->get('request_stack')->getMasterRequest()->get('sizex') !== null) $sizex = $this->get('request_stack')->getMasterRequest()->get('sizex');
        if ($sizex < 10) $sizex = 10;
        if ($this->get('request_stack')->getMasterRequest()->get('sizey') !== null) $sizey = $this->get('request_stack')->getMasterRequest()->get('sizey');
        if ($sizey < 10) $sizey = 10;
        if ($this->get('request_stack')->getMasterRequest()->get('backcolor') !== null) $backcolor = $this->get('request_stack')->getMasterRequest()->get('backcolor');
        if ($this->get('request_stack')->getMasterRequest()->get('color') !== null) $color = $this->get('request_stack')->getMasterRequest()->get('color');
        if ($this->get('request_stack')->getMasterRequest()->get('target') !== null) $target = $this->get('request_stack')->getMasterRequest()->get('target');
        if ($captchaType == 'grid')
        {
            // Генерируем текст и другие случайные величины
            $angle = rand(-15, 15);
            $phaseX = rand(0, 19);
            $phaseY = rand(0, 4);
            $letters = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
            $text = '';
            for ($i = 0; $i < 5; $i++)
            {
                $text .= substr($letters, rand(0, strlen($letters) - 1), 1);
            }
            // Начинаем рисование
            $textimage = imagecreate (100, 30);
            $black = imagecolorallocate ($textimage, 0, 0, 0);
            $white = imagecolorallocate ($textimage, 255, 255, 255);
            $box = imagettfbbox (20, 0, "admincss/captcha.ttf", $text);
            imagettftext ($textimage, 20, 0, 50 - abs($box[4] - $box[0]) / 2, 15 + abs($box[5] - $box[1]) / 2, $white, "admincss/captcha.ttf", $text);
            $captcha = imagecreate ($sizex, $sizey);
            list($r, $g, $b) = $this->colorHexToRgb($backcolor);
            if ($r === null) {$r = 0; $g = 0; $b = 0;}
            $back = imagecolorallocate ($captcha, $r, $g, $b);
            list($r, $g, $b) = $this->colorHexToRgb($color);
            if ($r === null) {$r = 255; $g = 255; $b = 255;}
            $front = imagecolorallocate ($captcha, $r, $g, $b);
            imagefill ($captcha, 0, 0, $back);
            // Определяем границы изображения и подбираем zoom
            $minx = null;
            $miny = null;
            $maxx = null;
            $maxy = null;
            for ($x = 0; $x < 100; $x++)
            {
                for ($y = 0; $y < 30; $y++)
                {
                    $xx = $this->getCoordX($x, $y, 0, $phaseX, $angle, 1, 0);
                    $yy = $this->getCoordY($x, $y, 0, $phaseY, $angle, 1, 0);
                    if (($xx < $minx) || ($minx === null)) $minx = $xx;
                    if (($xx > $maxx) || ($maxx === null)) $maxx = $xx;
                    if (($yy < $miny) || ($miny === null)) $miny = $yy;
                    if (($yy > $maxy) || ($maxy === null)) $maxy = $yy;
                }
            }
            $zoom = 1;
            if ((($sizey - 10) / ($maxy - $miny)) < (($sizex - 10) / ($maxx - $minx))) 
            {
                $zoom = ($sizey - 10) / ($maxy - $miny);
                $posx = ($sizex - 10 - ($maxx - $minx) * $zoom) / 2;
                $posy = 0;
            } else
            {
                $zoom = ($sizex - 10) / ($maxx - $minx);
                $posx = 0;
                $posy = ($sizey - 10 - ($maxy - $miny) * $zoom) / 2;
            }
            $posx = $posx + 5 - $minx * $zoom;
            $posy = $posy + 5 - $miny * $zoom;
            $stepx = 2;
            $stepy = 2;
            // Рисуем вертикальные линии
            if ($stepy != 0)
            {
                for ($liney = 1; $liney < floor(30 / $stepy); $liney++)
                {
                    $oldxx = null;
                    $oldyy = null;
                    $y = $liney * $stepy;
                    for ($x = 0; $x < 100; $x++)
                    {
                        if (imagecolorat($textimage, $x, $y) == $black) $z = 0; else $z = 1;
                        $xx = $this->getCoordX($x, $y, $z, $phaseX, $angle, $zoom, $posx);
                        $yy = $this->getCoordY($x, $y, $z, $phaseY, $angle, $zoom, $posy);
                        if ($oldxx != null)
                        {
                            imageline($captcha, $oldxx, $oldyy, $xx, $yy, $front);
                        }
                        $oldxx = $xx;
                        $oldyy = $yy;
                    }
                }
            }
            // Рисуем горизонтальные линии
            if ($stepx != 0)
            {
                for ($linex = 1; $linex < floor(100 / $stepx); $linex++)
                {
                    $oldxx = null;
                    $oldyy = null;
                    $x = $linex * $stepx;
                    for ($y = 0; $y < 30; $y++)
                    {
                        if (imagecolorat($textimage, $x, $y) == $black) $z = 0; else $z = 1;
                        $xx = $this->getCoordX($x, $y, $z, $phaseX, $angle, $zoom, $posx);
                        $yy = $this->getCoordY($x, $y, $z, $phaseY, $angle, $zoom, $posy);
                        if ($oldxx != null)
                        {
                            imageline($captcha, $oldxx, $oldyy, $xx, $yy, $front);
                        }
                        $oldxx = $xx;
                        $oldyy = $yy;
                    }
                }
            }
        } elseif ($captchaType == 'math')
        {
            $operator = rand(0, 1);
            if ($operator == 0)
            {
                // плюс
                $number1 = rand(1, 97);
                $number2 = rand(1, 99 - $number1);
                $text = $number1 + $number2;
                $viewtext = $number1.'+'.$number2;
            } else
            {
                // минус
                $number1 = rand(3, 99);
                $number2 = rand(1, $number1 - 1);
                $text = $number1 - $number2;
                $viewtext = $number1.'-'.$number2;
            }
            $captcha = imagecreate ($sizex, $sizey);
            list($r, $g, $b) = $this->colorHexToRgb($backcolor);
            if ($r === null) {$r = 0; $g = 0; $b = 0;}
            $back = imagecolorallocate ($captcha, $r, $g, $b);
            list($r, $g, $b) = $this->colorHexToRgb($color);
            if ($r === null) {$r = 255; $g = 255; $b = 255;}
            $front = imagecolorallocate ($captcha, $r, $g, $b);
            imagefill ($captcha, 0, 0, $back);
            for ($i = 0; $i < strlen($viewtext); $i++)
            {
                $letter = substr($viewtext, $i, 1);
                $x = ($sizex - 20) / strlen($viewtext) * $i + 10;
                $x = rand($x, $x + 4);
                $y = $sizey - (($sizey - 14) / 2 );
                $angle = rand(-15, 15);
                imagettftext ($captcha, 14, $angle, $x, $y, $front, "admincss/comic.ttf", $letter);
            }
            if ($noise == true)
            {
                for ($i = 0; $i < 6; $i++)
                {
                    $x1 = rand(0, $sizex - 1);
                    $x = rand(0, $sizex - 1);
                    $y1 = rand(0, $sizey - 1);
                    $y = rand(0, $sizey - 1);
                    imageline($captcha, $x1, $y1, $x, $y, $front);
                }
            }
        } else
        {
            $letters = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
            $text = '';
            $captcha = imagecreate ($sizex, $sizey);
            list($r, $g, $b) = $this->colorHexToRgb($backcolor);
            if ($r === null) {$r = 0; $g = 0; $b = 0;}
            $back = imagecolorallocate ($captcha, $r, $g, $b);
            list($r, $g, $b) = $this->colorHexToRgb($color);
            if ($r === null) {$r = 255; $g = 255; $b = 255;}
            $front = imagecolorallocate ($captcha, $r, $g, $b);
            imagefill ($captcha, 0, 0, $back);
            for ($i = 0; $i < 6; $i++)
            {
                $letter = substr($letters, rand(0, strlen($letters) - 1), 1);
                $text .= $letter;
                $x = ($sizex - 20) / 6 * $i + 10;
                $x = rand($x, $x + 4);
                $y = $sizey - (($sizey - 14) / 2 );
                $angle = rand(-25, 25);
                imagettftext ($captcha, 14, $angle, $x, $y, $front, "admincss/comic.ttf", $letter);
            }
            if ($noise == true)
            {
                for ($i = 0; $i < 6; $i++)
                {
                    $x1 = rand(0, $sizex - 1);
                    $x = rand(0, $sizex - 1);
                    $y1 = rand(0, $sizey - 1);
                    $y = rand(0, $sizey - 1);
                    imageline($captcha, $x1, $y1, $x, $y, $front);
                }
            }
        }
        if ($target != '')
        {
            $this->get('request_stack')->getMasterRequest()->getSession()->set('front_captcha_'.$target, $text);
        }
        ob_start();
        imagepng($captcha);
        $content = ob_get_clean();
        $resp = new Response();
        $resp->headers->set('Content-Type','image/png');
        $resp->headers->set('Cache-Control','no-cache, must-revalidate');
        $resp->headers->set('Expires','Mon, 28 Apr 1997 18:00:00 GMT');
        $resp->headers->set('Content-Length',strlen($content));
        $resp->headers->set('Connection','close');
        $resp->setContent($content);
        return $resp;
    }

// *******************************************
// Служебные функции админки
// *******************************************    
    public function loginAction()
    {
        $time = time() + 5;
        while (time() < $time) {
            
        }
        
        $authenticationUtils = $this->get('security.authentication_utils');
        $error = $authenticationUtils->getLastAuthenticationError();
        
        return $this->render('BasicCmsBundle:Default:login.html.twig', array('error' => $error));
    }
    
    public function menuAction()
    {
        $menu = $this->get('cms.cmsManager')->getAdminMenu();
        return $this->render('BasicCmsBundle:Default:menu.html.twig', array('menu' => $menu));
    }
// *******************************************
// Главная страница пользователей
// *******************************************    
    public function userListAction()
    {
        if ($this->getUser()->checkAccess('user_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Пользователи',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $tab = $this->get('request_stack')->getMasterRequest()->get('tab');
        if ($tab === null) $tab = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_user_list_tab');
                      else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_tab', $tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 4) $tab = 4;
        // Таб 1
        $page0 = $this->get('request_stack')->getMasterRequest()->get('page0');
        $sort0 = $this->get('request_stack')->getMasterRequest()->get('sort0');
        $search0 = $this->get('request_stack')->getMasterRequest()->get('search0');
        if ($page0 === null) $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_user_list_page0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_page0', $page0);
        if ($page0 === null) $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_user_list_page0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_page0', $page0);
        if ($sort0 === null) $sort0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_user_list_sort0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_sort0', $sort0);
        if ($search0 === null) $search0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_user_list_search0');
                          else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_search0', $search0);
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        // Поиск контента для 1 таба (пользователи)
        $query = $em->createQuery('SELECT count(u.id) as usercount FROM BasicCmsBundle:Users u '.                                
                                  'WHERE u.login like :search ')->setParameter('search', '%'.$search0.'%');
        $usercount = $query->getResult();
        if (!empty($usercount)) $usercount = $usercount[0]['usercount']; else $usercount = 0;
        $pagecount0 = ceil($usercount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY u.login ASC';
        if ($sort0 == 1) $sortsql = 'ORDER BY u.login DESC';
        if ($sort0 == 2) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY u.fullName DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY u.regDate ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY u.regDate DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY r.name ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY r.name DESC';
        if ($sort0 == 8) $sortsql = 'ORDER BY u.blocked ASC';
        if ($sort0 == 9) $sortsql = 'ORDER BY u.blocked DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT u.id, u.login, u.fullName, u.email, u.regDate, r.name as rolename, u.blocked, u.visitDate FROM BasicCmsBundle:Users u '.
                                  'LEFT JOIN BasicCmsBundle:Roles r WITH r = u.role '.
                                  'WHERE u.login like :search '.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $users = $query->getResult();
        // Таб 2
        $query = $em->createQuery('SELECT a.id, a.title, a.enabled, a.isDefault, sp.url FROM BasicCmsBundle:UserAuthPages a LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.user\' AND sp.contentAction = \'auth\' AND sp.contentId = a.id ORDER BY a.id');
        $authpages = $query->getResult();
        foreach ($authpages as &$authpage) $authpage['title'] = $this->get('cms.cmsManager')->decodeLocalString($authpage['title'],'default',true);
        unset($authpage);
        // Таб 3
        $query = $em->createQuery('SELECT p.id, p.title, p.enabled, sp.url FROM BasicCmsBundle:UserPasswordPages p LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.user\' AND sp.contentAction = \'password\' AND sp.contentId = p.id ORDER BY p.id');
        $passwordpages = $query->getResult();
        foreach ($passwordpages as &$passwordpage) $passwordpage['title'] = $this->get('cms.cmsManager')->decodeLocalString($passwordpage['title'],'default',true);
        unset($passwordpage);
        // Таб 4
        $query = $em->createQuery('SELECT r.id, r.title, r.enabled, sp.url FROM BasicCmsBundle:UserRegisterPages r LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.user\' AND sp.contentAction = \'register\' AND sp.contentId = r.id ORDER BY r.id');
        $registerpages = $query->getResult();
        foreach ($registerpages as &$registerpage) $registerpage['title'] = $this->get('cms.cmsManager')->decodeLocalString($registerpage['title'],'default',true);
        unset($registerpage);
        // Таб 5
        $query = $em->createQuery('SELECT p.id, p.title, p.enabled, sp.url FROM BasicCmsBundle:UserProfilePages p LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.user\' AND sp.contentAction = \'profile\' AND sp.contentId = p.id ORDER BY p.id');
        $profilepages = $query->getResult();
        foreach ($profilepages as &$profilepage) $profilepage['title'] = $this->get('cms.cmsManager')->decodeLocalString($profilepage['title'],'default',true);
        unset($profilepage);
        
        
        return $this->render('BasicCmsBundle:Default:userList.html.twig', array(
            'users' => $users,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'pagecount0' => $pagecount0,
            'authpages' => $authpages,
            'passwordpages' => $passwordpages,
            'registerpages' => $registerpages,
            'profilepages' => $profilepages,
            
            
            'tab' => $tab
        ));
        
    }

// *******************************************
// AJAX загрузка аватара
// *******************************************    

    public function userAjaxAvatarAction() 
    {
        /*
        $userId = $this->getUser()->getId();
        $file = $this->get('request_stack')->getMasterRequest()->files->get('avatar');
        $tmpfile = $file->getPathName();
        $source = "";
        if (@getimagesize($tmpfile)) 
        {
            $params = getimagesize($tmpfile);
            if ($params[0] < 10) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла меньше 10x10')));
            if ($params[1] < 10) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла меньше 10x10')));
            if ($params[0] > 6000) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла больше 6000x6000')));
            if ($params[1] > 6000) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла больше 6000x6000')));
            $basepath = 'images/user/';
            $name = $this->getUser()->getId().'_'.md5(time()).'.jpg';
            if (move_uploaded_file($tmpfile, $basepath . $name)) 
            {
                return new Response(json_encode(array('file' => '/images/user/'.$name, 'error' => '')));
            }
        } else return new Response(json_encode(array('file' => '', 'error' => 'Неправильный формат файла')));
         * 
         */
        $userId = $this->getUser()->getId();
        $file = $this->get('request_stack')->getMasterRequest()->files->get('avatar');
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
            $basepath = '/images/user/';
            $name = $this->getUser()->getId().'_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
            if (move_uploaded_file($tmpfile, '.'.$basepath.$name)) 
            {
                $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $file->getClientOriginalName());
                return new Response(json_encode(array('file' => $basepath.$name, 'error' => '')));
            }
        }
        return new Response(json_encode(array('file' => '', 'error' => 'Неправильный формат файла')));
    }
    
// *******************************************
// Создание нового пользователя
// *******************************************    
    public function userCreateAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_tab', 0);
        if ($this->getUser()->checkAccess('user_new') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание нового пользователя',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $user = array();
        $usererror = array();

        $user['login'] = '';
        $user['avatar'] = '';
        $user['password'] = '';
        $user['passwordRepeat'] = '';
        $user['email'] = '';
        $user['fullName'] = '';
        $user['userType'] = '';
        $user['blocked'] = '';
        $user['role'] = '';
        $usererror['login'] = '';
        $usererror['avatar'] = '';
        $usererror['password'] = '';
        $usererror['passwordRepeat'] = '';
        $usererror['email'] = '';
        $usererror['fullName'] = '';
        $usererror['userType'] = '';
        $usererror['blocked'] = '';
        $usererror['role'] = '';

        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        
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
        
//        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
//        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.user','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.user.view');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postuser = $this->get('request_stack')->getMasterRequest()->get('user');
            if (isset($postuser['login'])) $user['login'] = $postuser['login'];
            if (isset($postuser['avatar'])) $user['avatar'] = $postuser['avatar'];
            if (isset($postuser['password'])) $user['password'] = $postuser['password'];
            if (isset($postuser['passwordRepeat'])) $user['passwordRepeat'] = $postuser['passwordRepeat'];
            if (isset($postuser['email'])) $user['email'] = $postuser['email'];
            if (isset($postuser['fullName'])) $user['fullName'] = trim($postuser['fullName']);
            if (isset($postuser['userType'])) $user['userType'] = $postuser['userType'];
            if (isset($postuser['blocked'])) $user['blocked'] = intval($postuser['blocked']);
            if (isset($postuser['role'])) $user['role'] = $postuser['role'];
            unset($postuser);
            if (!preg_match("/^[A-Za-z0-9\-]{5,30}$/ui", $user['login'])) {$errors = true; $usererror['login'] = 'Логин должен содержать от 5 до 30 латинских букв, цифр или дефисов';}
            else
            {
                $query = $em->createQuery('SELECT count(u.id) as logincount FROM BasicCmsBundle:Users u WHERE u.login=:login')->setParameter('login', $user['login']);
                $checklogin = $query->getResult();
                if ($checklogin[0]['logincount'] != 0) {$errors = true; $usererror['login'] = 'Пользователь с таким логином уже существует';}
            }
            if (!preg_match("/^[A-Za-z0-9\-]{5,30}$/ui", $user['password'])) {$errors = true; $usererror['password'] = 'Пароль должен содержать от 5 до 30 латинских букв, цифр или дефисов';}
            if ($user['password'] != $user['passwordRepeat']) {$errors = true; $usererror['passwordRepeat'] = 'Пароли не совпадают';}
            if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,24})$/ui", $user['email'])) {$errors = true; $usererror['email'] = 'Неправильный формат адреса электронной почты';}
            elseif ($this->container->getParameter('user_unique_email') == true)
            {
                $query = $em->createQuery('SELECT count(u.id) as emailcount FROM BasicCmsBundle:Users u WHERE u.email=:email')->setParameter('email', $user['email']);
                $checkemail = $query->getResult();
                if ($checkemail[0]['emailcount'] != 0) {$errors = true; $usererror['email'] = 'Пользователь с таким адресом почты уже существует';}
            }
            if (!preg_match("/^[-0-9A-zА-яЁё\s\`]{3,99}$/ui", $user['fullName'])) {$errors = true; $usererror['fullName'] = 'Имя должно содержать от 3 до 99 букв';}
            elseif ($this->container->getParameter('user_unique_fullname') == true)
            {
                $query = $em->createQuery('SELECT count(u.id) as namecount FROM BasicCmsBundle:Users u WHERE u.fullName=:name OR u.login=:name')->setParameter('name', $user['fullName']);
                $checkname = $query->getResult();
                if ($checkname[0]['namecount'] != 0) {$errors = true; $usererror['fullName'] = 'Пользователь с таким именем уже существует';}
            }
            if (($user['userType'] != 'SUPERADMIN') && ($user['userType'] != 'ADMIN') && ($user['userType'] != 'USER')) {$errors = true; $usererror['userType'] = 'Неверный тип пользователя';}
            if (($user['blocked'] < 0) || ($user['blocked'] > 2)) {$errors = true; $usererror['blocked'] = 'Неверный статус блокировки';}
            $query = $em->createQuery('SELECT count(r.id) as rolecount FROM BasicCmsBundle:Roles r WHERE r.id=:roleid')->setParameter('roleid', $user['role']);
            $checkrole = $query->getResult();
            if (!isset($checkrole[0]['rolecount']) || ($checkrole[0]['rolecount'] == 0)) {$errors = true; $usererror['role'] = 'Роль не найдена';}
            unset($checkrole);
            if (($user['avatar'] != '') && (!file_exists('.'.$user['avatar']))) {$errors = true; $usererror['avatar'] = 'Файл не найден';}
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
                    if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
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
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'userCreate', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($user['avatar']);
                $userent = new Users();
                $userent->setLogin($user['login']);
                $userent->setSalt(md5(time()));
                $pass = $this->get('security.encoder_factory')->getEncoder($userent)->encodePassword($user['password'], $userent->getSalt());
                $userent->setPassword($pass);
                $userent->setEmail($user['email']);
                $userent->setFullName($user['fullName']);
                $userent->setUserType($user['userType']);
                $userent->setBlocked($user['blocked']);
                $userent->setRegDate(new \DateTime('now'));
                $userent->setAvatar($user['avatar']);
                $role = $this->getDoctrine()->getRepository('BasicCmsBundle:Roles')->find($user['role']);
                $userent->setRole($role);
                $em->persist($userent);
                $em->flush();
                if ($page['enable'] != 0)
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $pageent->setBreadCrumbs('');
                    $pageent->setBreadCrumbsRel('');
                    $pageent->setDescription('Просмотр пользователя '.$userent->getFullName());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.user');
                    $pageent->setContentId($userent->getId());
                    $pageent->setContentAction('view');
                    $userent->setTemplate($page['template']);
                    if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                    $em->persist($pageent);
                    $em->flush();
                    if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                }
                $i = 0;
                foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'userCreate', $userent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание нового пользователя',
                    'message'=>'Пользователь успешно создан',
                    'paths'=>array('Создать еще одного пользователя'=>$this->get('router')->generate('basic_cms_user_create'),'Вернуться к списку пользователей'=>$this->get('router')->generate('basic_cms_user_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'userCreate', 0, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Default:userCreate.html.twig', array(
            'user' => $user,
            'usererror' => $usererror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
    }

// *******************************************
// Редактирование пользователя
// *******************************************    
    public function userEditAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_tab', 0);
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        if (($this->getUser()->checkAccess('user_viewall') == 0) && ($this->getUser()->getId() != $id))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование пользователя',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        if (($this->getUser()->checkAccess('user_viewown') == 0) && ($this->getUser()->getId() == $id))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование пользователя',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $userent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($id);
        if (empty($userent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование пользователя',
                'message'=>'Пользователь не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к списку пользователей'=>$this->get('router')->generate('basic_cms_user_list'))
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.user','contentId'=>$id,'contentAction'=>'view'));
        
        $user = array();
        $usererror = array();

        $user['login'] = $userent->getLogin();
        $user['avatar'] = $userent->getAvatar();
        $user['password'] = '';
        $user['passwordRepeat'] = '';
        $user['email'] = $userent->getEmail();
        $user['fullName'] = $userent->getFullName();
        $user['userType'] = $userent->getUserType();
        $user['blocked'] = $userent->getBlocked();
        if ($userent->getRole() != null) $user['role'] = $userent->getRole()->getId(); else $user['role'] = '';
        $usererror['login'] = '';
        $usererror['avatar'] = '';
        $usererror['password'] = '';
        $usererror['passwordRepeat'] = '';
        $usererror['email'] = '';
        $usererror['fullName'] = '';
        $usererror['userType'] = '';
        $usererror['blocked'] = '';
        $usererror['role'] = '';

        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        
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
            $page['template'] = $userent->getTemplate();
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
        
//        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
//        $roles = $query->getResult();
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.user','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        //$query = $em->createQuery('SELECT m.id, m.name FROM BasicCmsBundle:Modules m');
        //$modules = $query->getResult();
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.user.view');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            if (($this->getUser()->checkAccess('user_editall') == 0) && ($this->getUser()->getId() != $id))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование пользователя',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            if (($this->getUser()->checkAccess('user_editown') == 0) && ($this->getUser()->getId() == $id))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование пользователя',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            // Проверка основных данных
            $postuser = $this->get('request_stack')->getMasterRequest()->get('user');
            if (isset($postuser['login'])) $user['login'] = $postuser['login'];
            if (isset($postuser['avatar'])) $user['avatar'] = $postuser['avatar'];
            if (isset($postuser['password'])) $user['password'] = $postuser['password'];
            if (isset($postuser['passwordRepeat'])) $user['passwordRepeat'] = $postuser['passwordRepeat'];
            if (isset($postuser['email'])) $user['email'] = $postuser['email'];
            if (isset($postuser['fullName'])) $user['fullName'] = trim($postuser['fullName']);
            if (isset($postuser['userType'])) $user['userType'] = $postuser['userType'];
            if (isset($postuser['blocked'])) $user['blocked'] = intval($postuser['blocked']);
            if (isset($postuser['role'])) $user['role'] = $postuser['role'];
            unset($postuser);
            if (!preg_match("/^[A-Za-z0-9\-]{5,30}$/ui", $user['login'])) {$errors = true; $usererror['login'] = 'Логин должен содержать от 5 до 30 латинских букв, цифр или дефисов';}
            else
            {
                $query = $em->createQuery('SELECT count(u.id) as logincount FROM BasicCmsBundle:Users u WHERE u.login=:login AND u.id != :id')->setParameter('login', $user['login'])->setParameter('id', $id);
                $checklogin = $query->getResult();
                if ($checklogin[0]['logincount'] != 0) {$errors = true; $usererror['login'] = 'Пользователь с таким логином уже существует';}
            }
            if (!preg_match("/^([A-Za-z0-9\-]{5,30})?$/ui", $user['password'])) {$errors = true; $usererror['password'] = 'Пароль должен содержать от 5 до 30 латинских букв, цифр или дефисов';}
            if ($user['password'] != $user['passwordRepeat']) {$errors = true; $usererror['passwordRepeat'] = 'Пароли не совпадают';}
            if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,24})$/ui", $user['email'])) {$errors = true; $usererror['email'] = 'Неправильный формат адреса электронной почты';}
            elseif ($this->container->getParameter('user_unique_email') == true)
            {
                $query = $em->createQuery('SELECT count(u.id) as emailcount FROM BasicCmsBundle:Users u WHERE u.email=:email AND u.id != :id')->setParameter('email', $user['email'])->setParameter('id', $id);
                $checkemail = $query->getResult();
                if ($checkemail[0]['emailcount'] != 0) {$errors = true; $usererror['email'] = 'Пользователь с таким адресом почты уже существует';}
            }
            if (!preg_match("/^[-0-9A-zА-яЁё\s\`]{3,99}$/ui", $user['fullName'])) {$errors = true; $usererror['fullName'] = 'Имя должно содержать от 3 до 99 букв';}
            elseif ($this->container->getParameter('user_unique_fullname') == true)
            {
                $query = $em->createQuery('SELECT count(u.id) as namecount FROM BasicCmsBundle:Users u WHERE (u.fullName=:name OR u.login=:name) AND u.id != :id')->setParameter('name', $user['fullName'])->setParameter('id', $id);
                $checkname = $query->getResult();
                if ($checkname[0]['namecount'] != 0) {$errors = true; $usererror['fullName'] = 'Пользователь с таким именем уже существует';}
            }
            if (($user['userType'] != 'SUPERADMIN') && ($user['userType'] != 'ADMIN') && ($user['userType'] != 'USER')) {$errors = true; $usererror['userType'] = 'Неверный тип пользователя';}
            if (($user['blocked'] < 0) || ($user['blocked'] > 2)) {$errors = true; $usererror['blocked'] = 'Неверный статус блокировки';}
            $query = $em->createQuery('SELECT count(r.id) as rolecount FROM BasicCmsBundle:Roles r WHERE r.id=:roleid')->setParameter('roleid', $user['role']);
            $checkrole = $query->getResult();
            if (!isset($checkrole[0]['rolecount']) || ($checkrole[0]['rolecount'] == 0)) {$errors = true; $usererror['role'] = 'Роль не найдена';}
            unset($checkrole);
            if (($user['avatar'] != '') && (!file_exists('.'.$user['avatar']))) {$errors = true; $usererror['avatar'] = 'Файл не найден';}
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
                    if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                    else
                    {
                        if (empty($pageent)) $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url')->setParameter('url', $page['url']);
                                        else $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url AND s.id != :id')->setParameter('id', $pageent->getId())->setParameter('url', $page['url']);
                        $checkurl = $query->getResult();
                        if ($checkurl[0]['urlcount'] != 0) {$errors = true; $pageerror['url'] = 'Страница с таким адресом уже существует';}
                    }
                }
                /*if (!preg_match("/^[a-z0-9\-]{1,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                if ($pageerror['url'] == '')
                {
                    $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url')->setParameter('url', $page['url']);
                    $checkurl = $query->getResult();
                    if ($checkurl[0]['urlcount'] != 0) $pageerror['url'] = 'Страница с таким адресом уже существует';
                }*/
                $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $page['locale']) $localecheck = true;}
                if ($localecheck == false) $page['locale'] = '';
                if (($page['layout'] != '') && (!isset($layouts[$page['layout']]))) {$errors = true; $pageerror['layout'] = 'Шаблон не найден';}
                if (($page['template'] != '') && (!isset($templates[$page['template']]))) {$errors = true; $pageerror['template'] = 'Шаблон не найден';}
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'userEdit', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                if (($userent->getAvatar() != '') && ($userent->getAvatar() != $user['avatar'])) @unlink('.'.$userent->getAvatar());
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($user['avatar']);
                $userent->setLogin($user['login']);
                if ($user['password'] != '')
                {
                    $pass = $this->get('security.encoder_factory')->getEncoder($userent)->encodePassword($user['password'], $userent->getSalt());
                    $userent->setPassword($pass);
                }
                $userent->setEmail($user['email']);
                $userent->setFullName($user['fullName']);
                $userent->setUserType($user['userType']);
                $userent->setBlocked($user['blocked']);
                $userent->setAvatar($user['avatar']);
                $role = $this->getDoctrine()->getRepository('BasicCmsBundle:Roles')->find($user['role']);
                $userent->setRole($role);
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
                    $pageent->setDescription('Просмотр пользователя '.$userent->getFullName());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.user');
                    $pageent->setContentId($userent->getId());
                    $pageent->setContentAction('view');
                    $userent->setTemplate($page['template']);
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
                $i = 0;
                foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'userEdit', $id, 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование пользователя',
                    'message'=>'Данные пользователя успешно отредактированы',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_user_edit').'?id='.$id, 'Вернуться к списку пользователей'=>$this->get('router')->generate('basic_cms_user_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'userEdit', $id, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Default:userEdit.html.twig', array(
            'user' => $user,
            'usererror' => $usererror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'tabs' => $tabs,
            'activetab' => $activetab,
            'id' => $id
        ));
    }
    
// *******************************************
// AJAX-контроллер
// *******************************************    
    
    public function userAjaxAction()
    {
        if ($this->getUser()->checkAccess('user_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Пользователи',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $tab = intval($this->get('request_stack')->getMasterRequest()->get('tab'));
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->get('request_stack')->getMasterRequest()->get('action');
        $errors = array();
        if ($tab == 0)
        {
            if ($action == 'delete')
            {
                if ($this->getUser()->checkAccess('user_editall') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $userent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($key);
                        if (!empty($userent))
                        {
                            if ($userent->getId() != $this->getUser()->getId())
                            {
                                if ($userent->getAvatar() != '') @unlink('.'.$userent->getAvatar());
                                $em->remove($userent);
                                $em->flush();
                                $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.user\' AND p.contentAction = \'view\' AND p.contentId = :id')->setParameter('id', $key);
                                $query->execute();
                                foreach ($cmsservices as $item) if (strpos($item,'addone.user.') === 0) 
                                {
                                    $serv = $this->container->get($item);
                                    $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'userDelete', $key, 'save');
                                }       
                            } else $errors[$key] = 'Запрещено удалять себя';
                            unset($userent);    
                        }
                    }
            }
            if ($action == 'blocked')
            {
                if ($this->getUser()->checkAccess('user_editall') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $userent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($key);
                        if (!empty($userent))
                        {
                            if ($userent->getId() != $this->getUser()->getId())
                            {
                                $userent->setBlocked(1);
                                $em->flush();
                            } else $errors[$key] = 'Запрещено блокировать себя';
                            unset($userent);    
                        }
                    }
            }
            if ($action == 'unblocked')
            {
                if ($this->getUser()->checkAccess('user_editall') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $userent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($key);
                        if (!empty($userent))
                        {
                            $userent->setBlocked(2);
                            $em->flush();
                            unset($userent);    
                        }
                    }
            }
        }
        if ($tab == 1)
        {
            if ($action == 'deleteauth')
            {
                if ($this->getUser()->checkAccess('user_authpage') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $authpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserAuthPages')->find($key);
                        if (!empty($authpageent))
                        {
                            $em->remove($authpageent);
                            $em->flush();
                            $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.user\' AND p.contentAction = \'auth\' AND p.contentId = :id')->setParameter('id', $key);
                            $query->execute();
                        }
                        unset($authpageent);    
                    }
            }
            if ($action == 'blockedauth')
            {
                if ($this->getUser()->checkAccess('user_authpage') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $authpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserAuthPages')->find($key);
                        if (!empty($authpageent))
                        {
                            $authpageent->setEnabled(0);
                            $em->flush();
                            unset($authpageent);    
                        }
                    }
            }
            if ($action == 'unblockedauth')
            {
                if ($this->getUser()->checkAccess('user_authpage') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $authpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserAuthPages')->find($key);
                        if (!empty($authpageent))
                        {
                            $authpageent->setEnabled(1);
                            $em->flush();
                            unset($authpageent);    
                        }
                    }
            }
            if ($action == 'defaultauth')
            {
                if ($this->getUser()->checkAccess('user_authpage') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
                        'message'=>'Доступ запрещён', 
                        'error'=>true,
                        'paths'=>array()
                    ));
                }
                $check = $this->get('request_stack')->getMasterRequest()->get('check');
                if ($check != null)
                {
                    $authcount = 0;
                    foreach ($check as $key=>$val) if ($val == 1) $authcount++;
                    foreach ($check as $key=>$val)
                        if ($val == 1)
                        {
                            $authpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserAuthPages')->find($key);
                            if ((!empty($authpageent)) && ($authcount == 1))
                            {
                                $query = $em->createQuery('UPDATE BasicCmsBundle:UserAuthPages a SET a.isDefault = 0');
                                $query->execute();
                                $authpageent->setIsDefault(1);
                                $em->flush();
                                unset($authpageent);    
                            } else $errors[$key] = 'Должна быть выделена только одна страница';
                        }
                }
            }
        }
        if ($tab == 2)
        {
            if ($action == 'deletepass')
            {
                if ($this->getUser()->checkAccess('user_passwordpage') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $passpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserPasswordPages')->find($key);
                        if (!empty($passpageent))
                        {
                            $em->remove($passpageent);
                            $em->flush();
                            $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.user\' AND p.contentAction = \'password\' AND p.contentId = :id')->setParameter('id', $key);
                            $query->execute();
                        }
                        unset($passpageent);    
                    }
            }
            if ($action == 'blockedpass')
            {
                if ($this->getUser()->checkAccess('user_passwordpage') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $passpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserPasswordPages')->find($key);
                        if (!empty($passpageent))
                        {
                            $passpageent->setEnabled(0);
                            $em->flush();
                            unset($passpageent);    
                        }
                    }
            }
            if ($action == 'unblockedpass')
            {
                if ($this->getUser()->checkAccess('user_passwordpage') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $passpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserPasswordPages')->find($key);
                        if (!empty($passpageent))
                        {
                            $passpageent->setEnabled(1);
                            $em->flush();
                            unset($passpageent);    
                        }
                    }
            }
        }
        if ($tab == 3)
        {
            if ($action == 'deletereg')
            {
                if ($this->getUser()->checkAccess('user_registerpage') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $regpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserRegisterPages')->find($key);
                        if (!empty($regpageent))
                        {
                            $em->remove($regpageent);
                            $em->flush();
                            $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.user\' AND p.contentAction = \'register\' AND p.contentId = :id')->setParameter('id', $key);
                            $query->execute();
                        }
                        unset($regpageent);    
                    }
            }
            if ($action == 'blockedreg')
            {
                if ($this->getUser()->checkAccess('user_registerpage') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $regpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserRegisterPages')->find($key);
                        if (!empty($regpageent))
                        {
                            $regpageent->setEnabled(0);
                            $em->flush();
                            unset($regpageent);    
                        }
                    }
            }
            if ($action == 'unblockedreg')
            {
                if ($this->getUser()->checkAccess('user_registerpage') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $regpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserRegisterPages')->find($key);
                        if (!empty($regpageent))
                        {
                            $regpageent->setEnabled(1);
                            $em->flush();
                            unset($regpageent);    
                        }
                    }
            }
        }

        if ($tab == 4)
        {
            if ($action == 'deleteprof')
            {
                if ($this->getUser()->checkAccess('user_profilepage') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $profpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserProfilePages')->find($key);
                        if (!empty($profpageent))
                        {
                            $em->remove($profpageent);
                            $em->flush();
                            $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.user\' AND p.contentAction = \'profile\' AND p.contentId = :id')->setParameter('id', $key);
                            $query->execute();
                        }
                        unset($profpageent);    
                    }
            }
            if ($action == 'blockedprof')
            {
                if ($this->getUser()->checkAccess('user_profilepage') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $profpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserProfilePages')->find($key);
                        if (!empty($profpageent))
                        {
                            $profpageent->setEnabled(0);
                            $em->flush();
                            unset($profpageent);    
                        }
                    }
            }
            if ($action == 'unblockedprof')
            {
                if ($this->getUser()->checkAccess('user_profilepage') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $profpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserProfilePages')->find($key);
                        if (!empty($profpageent))
                        {
                            $profpageent->setEnabled(1);
                            $em->flush();
                            unset($profpageent);    
                        }
                    }
            }
        }
        
        if ($tab == 0)
        {
            $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_user_list_page0');
            $sort0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_user_list_sort0');
            $search0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_user_list_search0');
            $page0 = intval($page0);
            $sort0 = intval($sort0);
            $search0 = trim($search0);

            // Поиск контента
            $query = $em->createQuery('SELECT count(u.id) as usercount FROM BasicCmsBundle:Users u '.                                
                                      'WHERE u.login like :search ')->setParameter('search', '%'.$search0.'%');
            $usercount = $query->getResult();
            if (!empty($usercount)) $usercount = $usercount[0]['usercount']; else $usercount = 0;
            $pagecount0 = ceil($usercount / 20);
            if ($pagecount0 < 1) $pagecount0 = 1;
            if ($page0 < 0) $page0 = 0;
            if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
            $sortsql = 'ORDER BY u.login ASC';
            if ($sort0 == 1) $sortsql = 'ORDER BY u.login DESC';
            if ($sort0 == 2) $sortsql = 'ORDER BY u.fullName ASC';
            if ($sort0 == 3) $sortsql = 'ORDER BY u.fullName DESC';
            if ($sort0 == 4) $sortsql = 'ORDER BY u.regDate ASC';
            if ($sort0 == 5) $sortsql = 'ORDER BY u.regDate DESC';
            if ($sort0 == 6) $sortsql = 'ORDER BY r.name ASC';
            if ($sort0 == 7) $sortsql = 'ORDER BY r.name DESC';
            if ($sort0 == 8) $sortsql = 'ORDER BY u.blocked ASC';
            if ($sort0 == 9) $sortsql = 'ORDER BY u.blocked DESC';
            $start = $page0 * 20;
            $query = $em->createQuery('SELECT u.id, u.login, u.fullName, u.email, u.regDate, r.name as rolename, u.blocked, u.visitDate FROM BasicCmsBundle:Users u '.
                                      'LEFT JOIN BasicCmsBundle:Roles r WITH r = u.role '.
                                      'WHERE u.login like :search '.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
            $users = $query->getResult();
            return $this->render('BasicCmsBundle:Default:userListTab1.html.twig', array(
                'users' => $users,
                'search0' => $search0,
                'page0' => $page0,
                'sort0' => $sort0,
                'pagecount0' => $pagecount0,
                'errors0' => $errors,
                'tab' => $tab
            ));
        }
        if ($tab == 1)
        {
            // Таб 2
            $query = $em->createQuery('SELECT a.id, a.title, a.enabled, a.isDefault, sp.url FROM BasicCmsBundle:UserAuthPages a LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.user\' AND sp.contentAction = \'auth\' AND sp.contentId = a.id ORDER BY a.id');
            $authpages = $query->getResult();
            foreach ($authpages as &$authpage) $authpage['title'] = $this->get('cms.cmsManager')->decodeLocalString($authpage['title'],'default',true);
            unset($authpage);
            return $this->render('BasicCmsBundle:Default:userListTab2.html.twig', array(
                'authpages' => $authpages,
                'errors1' => $errors,
                'tab' => $tab
            ));
        }
        if ($tab == 2)
        {
            // Таб 3
            $query = $em->createQuery('SELECT p.id, p.title, p.enabled, sp.url FROM BasicCmsBundle:UserPasswordPages p LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.user\' AND sp.contentAction = \'password\' AND sp.contentId = p.id ORDER BY p.id');
            $passwordpages = $query->getResult();
            foreach ($passwordpages as &$passwordpage) $passwordpage['title'] = $this->get('cms.cmsManager')->decodeLocalString($passwordpage['title'],'default',true);
            unset($passwordpage);
            return $this->render('BasicCmsBundle:Default:userListTab3.html.twig', array(
                'passwordpages' => $passwordpages,
                'errors2' => $errors,
                'tab' => $tab
            ));
        }
        if ($tab == 3)
        {
            // Таб 4
            $query = $em->createQuery('SELECT r.id, r.title, r.enabled, sp.url FROM BasicCmsBundle:UserRegisterPages r LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.user\' AND sp.contentAction = \'register\' AND sp.contentId = r.id ORDER BY r.id');
            $registerpages = $query->getResult();
            foreach ($registerpages as &$registerpage) $registerpage['title'] = $this->get('cms.cmsManager')->decodeLocalString($registerpage['title'],'default',true);
            unset($registerpage);
            return $this->render('BasicCmsBundle:Default:userListTab4.html.twig', array(
                'registerpages' => $registerpages,
                'errors3' => $errors,
                'tab' => $tab
            ));
        }
        if ($tab == 4)
        {
            // Таб 5
            $query = $em->createQuery('SELECT p.id, p.title, p.enabled, sp.url FROM BasicCmsBundle:UserProfilePages p LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.user\' AND sp.contentAction = \'profile\' AND sp.contentId = p.id ORDER BY p.id');
            $profilepages = $query->getResult();
            foreach ($profilepages as &$profilepage) $profilepage['title'] = $this->get('cms.cmsManager')->decodeLocalString($profilepage['title'],'default',true);
            unset($profilepage);
            return $this->render('BasicCmsBundle:Default:userListTab5.html.twig', array(
                'profilepages' => $profilepages,
                'errors4' => $errors,
                'tab' => $tab
            ));
        }

        
    }
    
// *******************************************
// Список ролей
// *******************************************    
    
    public function roleListAction()
    {
        if ($this->getUser()->checkAccess('role_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Роли',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $tab = $this->get('request_stack')->getMasterRequest()->get('tab');
        $page0 = $this->get('request_stack')->getMasterRequest()->get('page0');
        //$page1 = intval($this->get('request_stack')->getMasterRequest()->get('page1'));
        //$page2 = intval($this->get('request_stack')->getMasterRequest()->get('page2'));
        
        if ($page0 === null) $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_role_list_page0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_role_page0', $page0);
        if ($tab === null) $tab = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_role_list_tab');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_role_list_tab', $tab);
        $tab = intval($tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 1) $tab = 1;
        $page0 = intval($page0);
        
        // Поиск контента для 1 таба
        $query = $em->createQuery('SELECT count(r.id) as rolecount FROM BasicCmsBundle:Roles r');
        $rolecount = $query->getResult();
        if (!empty($rolecount)) $rolecount = $rolecount[0]['rolecount']; else $rolecount = 0;
        $pagecount0 = ceil($rolecount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT r.id, r.name, count(u.id) as usercount FROM BasicCmsBundle:Roles r '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH r = u.role '.
                                  'GROUP BY r.id ORDER BY r.id')->setFirstResult($start)->setMaxResults(20);
        $roles = $query->getResult();
        // Поиск контента для 2 таба
        $query = $em->createQuery('SELECT r.id, r.name, count(u.id) as usercount FROM BasicCmsBundle:Roles r '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH r = u.role '.
                                  'GROUP BY r.id ORDER BY r.id');
        $changeroles = $query->getResult();
        return $this->render('BasicCmsBundle:Default:roleList.html.twig', array(
            'roles' => $roles,
            'changeroles' => $changeroles,
            'page0' => $page0,
            'pagecount0' => $pagecount0,
            'tab' => $tab
        ));
        
    }
    
// *******************************************
// Создание роли
// *******************************************    
    
    public function roleCreateAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_role_list_tab', 0);
        if ($this->getUser()->checkAccess('role_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой роли',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $premis = $this->get('cms.cmsManager')->getRoleList();
        $premisobjects = $this->get('cms.cmsManager')->getRoleListByObjects();
        $rolename = '';
        $rolenameerror = '';
        $premiserror = '';
        $premon = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == 'POST')
        {
            $postrolename = $this->get('request_stack')->getMasterRequest()->get('rolename');
            $postpremon = $this->get('request_stack')->getMasterRequest()->get('premon');
            if ($postrolename !== null) $rolename = $postrolename;
            if (is_array($postpremon)) $premon = $postpremon;
            // Валидация
            $errors = false;
            if (!preg_match("/^[-0-9A-zА-яЁё\s\`]{3,99}$/ui", $rolename)) {$errors = true; $rolenameerror = 'Имя должно содержать от 3 до 99 букв';}
            if ($rolenameerror == '')
            {
                $query = $em->createQuery('SELECT count(r.id) as rolecount FROM BasicCmsBundle:Roles r WHERE r.name = :name')->setParameter('name', $rolename);
                $rolecount = $query->getResult();
                if (!empty($rolecount)) $rolecount = $rolecount[0]['rolecount']; else $rolecount = 0;
                if ($rolecount > 0) {$errors = true; $rolenameerror = 'Роль с таким именем уже существует';}
            }
            $premarray = array();
            foreach ($premis as $key=>$val)
            {
                if (isset($premon[$key]) && ($premon[$key] == 1)) $premarray[] = $key;
            }
            if (count($premarray) == 0) {$errors = true; $premiserror = 'Должно быть включено хотя бы одно разрешение';}
            // Сохранение
            if ($errors == false)
            {
                $roleent = new \Basic\CmsBundle\Entity\Roles();
                $roleent->setName($rolename);
                $roleent->setAccess(' '.implode(' ',$premarray).' ');
                $em->persist($roleent);
                $em->flush();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой роли',
                    'message'=>'Роль успешно создана',
                    'paths'=>array('Создать еще одну роль'=>$this->get('router')->generate('basic_cms_role_create'),'Вернуться к списку ролей'=>$this->get('router')->generate('basic_cms_role_list'))
                ));
            }
        }
        return $this->render('BasicCmsBundle:Default:roleCreate.html.twig', array(
            'premis'=>$premisobjects,
            'rolename'=>$rolename,
            'rolenameerror'=>$rolenameerror,
            'premiserror'=>$premiserror,
            'premon'=>$premon
        ));
    }

// *******************************************
// Редактирование роли
// *******************************************    
    
    public function roleEditAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_role_list_tab', 0);
        if ($this->getUser()->checkAccess('role_view') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование роли',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $premis = $this->get('cms.cmsManager')->getRoleList();
        $premisobjects = $this->get('cms.cmsManager')->getRoleListByObjects();
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $roleent = $this->getDoctrine()->getRepository('BasicCmsBundle:Roles')->find($id);
        if (empty($roleent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование роли',
                'message'=>'Роль не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку ролей'=>$this->get('router')->generate('basic_cms_role_list'))
            ));
        }
        $rolename = $roleent->getName();
        $rolenameerror = '';
        $premiserror = '';
        $premarray = explode(' ', trim($roleent->getAccess()));
        $premon = array();
        foreach ($premarray as $val) $premon[$val] = 1;
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == 'POST')
        {
            if ($this->getUser()->checkAccess('role_edit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование роли',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $postrolename = $this->get('request_stack')->getMasterRequest()->get('rolename');
            $postpremon = $this->get('request_stack')->getMasterRequest()->get('premon');
            if ($postrolename !== null) $rolename = $postrolename;
            if (is_array($postpremon)) $premon = $postpremon;
            // Валидация
            $errors = false;
            if (!preg_match("/^[-0-9A-zА-яЁё\s\`]{3,99}$/ui", $rolename)) {$errors = true; $rolenameerror = 'Имя должно содержать от 3 до 99 букв';}
            if ($rolenameerror == '')
            {
                $query = $em->createQuery('SELECT count(r.id) as rolecount FROM BasicCmsBundle:Roles r WHERE r.name = :name AND r.id != :id')->setParameter('name', $rolename)->setParameter('id', $id);
                $rolecount = $query->getResult();
                if (!empty($rolecount)) $rolecount = $rolecount[0]['rolecount']; else $rolecount = 0;
                if ($rolecount > 0) {$errors = true; $rolenameerror = 'Роль с таким именем уже существует';}
            }
            $premarray = array();
            foreach ($premis as $key=>$val)
            {
                if (isset($premon[$key]) && ($premon[$key] == 1)) $premarray[] = $key;
            }
            if (count($premarray) == 0) {$errors = true; $premiserror = 'Должно быть включено хотя бы одно разрешение';}
            // Сохранение
            if ($errors == false)
            {
                $roleent->setName($rolename);
                $roleent->setAccess(' '.implode(' ',$premarray).' ');
                $em->flush();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование роли',
                    'message'=>'Данные роли успешно отредактированы',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_role_edit').'?id='.$id, 'Вернуться к списку ролей'=>$this->get('router')->generate('basic_cms_role_list'))
                ));
            }
        }
        return $this->render('BasicCmsBundle:Default:roleEdit.html.twig', array(
            'premis'=>$premisobjects,
            'rolename'=>$rolename,
            'rolenameerror'=>$rolenameerror,
            'premiserror'=>$premiserror,
            'premon'=>$premon,
            'id'=>$id
        ));
    }

// *******************************************
// AJAX-контроллер ролей
// *******************************************    
    
    public function roleAjaxAction()
    {
        if ($this->getUser()->checkAccess('role_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Роли',
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
            if ($this->getUser()->checkAccess('role_edit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Роли',
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
                    $roleent = $this->getDoctrine()->getRepository('BasicCmsBundle:Roles')->find($key);
                    if (!empty($roleent))
                    {
                        $userent = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->findOneBy(array('role'=>$roleent));
                        if (empty($userent))
                        {
                            $em->remove($roleent);
                            $em->flush();
                        } else $errors[$key] = 'Запрещено удалять используемую пользователями роль';
                        unset($userent);    
                        unset($roleent);    
                    }
                }
        }
        
        $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_role_list_page0');
        $page0 = intval($page0);
        $tab = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_role_list_tab');
        $tab = intval($tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 1) $tab = 1;
        
        $query = $em->createQuery('SELECT count(r.id) as rolecount FROM BasicCmsBundle:Roles r');
        $rolecount = $query->getResult();
        if (!empty($rolecount)) $rolecount = $rolecount[0]['rolecount']; else $rolecount = 0;
        $pagecount0 = ceil($rolecount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT r.id, r.name, count(u.id) as usercount FROM BasicCmsBundle:Roles r '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH r = u.role '.
                                  'GROUP BY r.id ORDER BY r.id')->setFirstResult($start)->setMaxResults(20);
        $roles = $query->getResult();
        return $this->render('BasicCmsBundle:Default:roleListTab1.html.twig', array(
            'roles' => $roles,
            'page0' => $page0,
            'tab' => $tab,
            'pagecount0' => $pagecount0,
            'errors' => $errors
        ));
    }
    
    public function roleChangeAjaxAction()
    {
        if ($this->getUser()->checkAccess('role_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Роли',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->get('request_stack')->getMasterRequest()->get('action');
        $errors = array();
        if ($action == 'change')
        {
            if ($this->getUser()->checkAccess('user_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Роли',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $change = $this->get('request_stack')->getMasterRequest()->get('change');
            
            if (isset($change['from'])) $from = $change['from']; else $from = 0;
            if (isset($change['to'])) $to = $change['to']; else $to = 0;
            $rolefrom = $this->getDoctrine()->getRepository('BasicCmsBundle:Roles')->find($from);
            $roleto = $this->getDoctrine()->getRepository('BasicCmsBundle:Roles')->find($to);
            if (!empty($rolefrom))
            {
                if (!empty($roleto))
                {
                    if ($rolefrom->getId() != $roleto->getId())
                    {
                        $query = $em->createQuery('UPDATE BasicCmsBundle:Users u SET u.role = :to WHERE u.role = :from')->setParameter('to', $roleto)->setParameter('from', $rolefrom);
                        $query->execute();
                        $errors['success'] = 'Роль успешно переназначена';
                    } else $errors['to'] = 'Выбрана одна и та же роль';
                } else $errors['to'] = 'Новая роль не найдена';
            } else $errors['from'] = 'Старая роль не найдена';
        }
        $tab = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_role_list_tab');
        $tab = intval($tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 1) $tab = 1;
        $query = $em->createQuery('SELECT r.id, r.name, count(u.id) as usercount FROM BasicCmsBundle:Roles r '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH r = u.role '.
                                  'GROUP BY r.id ORDER BY r.id');
        $changeroles = $query->getResult();
        return $this->render('BasicCmsBundle:Default:roleListTab2.html.twig', array(
            'changeroles' => $changeroles,
            'tab' => $tab,
            'errors' => $errors
        ));
    }
    
// *******************************************
// Информация о программе
// *******************************************    
    
    public function aboutAction()
    {
        $modules = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) 
        {
            $serv = $this->container->get($item);
            $modules[] = array('name'=>$item,'description'=>$serv->getDescription(),'type'=>'object');
            $name = substr($item, 7);
            foreach ($cmsservices as $addoneitem) if (strpos($addoneitem,'addone.'.$name.'.') === 0) 
            {
                $serv = $this->container->get($addoneitem);
                $modules[] = array('name'=>$addoneitem,'description'=>$serv->getDescription(),'type'=>'addone');
            }       
        }       
        foreach ($cmsservices as $item) if (strpos($item,'template.') === 0) 
        {
            $serv = $this->container->get($item);
            $modules[] = array('name'=>$item,'description'=>$serv->getDescription(),'type'=>'template');
        }  
        return $this->render('BasicCmsBundle:Default:about.html.twig', array(
            'modules'=>$modules
        ));
    }

// *******************************************
// Главная админки
// *******************************************    
    
    public function indexAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $menu = $this->get('cms.cmsManager')->getAdminMenu();
        $statistic = array();
        // График
        $statistic['weekgraf'][0] = array('name' => '', 'count' => 0, 'countUniq' => 0);
        $statistic['weekgraf'][1] = array('name' => '', 'count' => 0, 'countUniq' => 0);
        $statistic['weekgraf'][2] = array('name' => '', 'count' => 0, 'countUniq' => 0);
        $statistic['weekgraf'][3] = array('name' => '', 'count' => 0, 'countUniq' => 0);
        $statistic['weekgraf'][4] = array('name' => '', 'count' => 0, 'countUniq' => 0);
        $statistic['weekgraf'][5] = array('name' => '', 'count' => 0, 'countUniq' => 0);
        $statistic['weekgraf'][6] = array('name' => '', 'count' => 0, 'countUniq' => 0);
        $statistic['weekgraf']['max'] = 1;
        $statistic['weekgraf']['maxUniq'] = 1;
        
        $statistic['thisMonth'] = 0;
        $statistic['prevMonth'] = 0;
        $statistic['allVisits'] = 0;
        $statistic['thisMonthUniq'] = 0;
        $statistic['prevMonthUniq'] = 0;
        $statistic['allVisitsUniq'] = 0;
        $startDate = new \DateTime('now');
        $stopDate = new \DateTime('now');
        for ($i = 0; $i < 7; $i++)
        {
            $startDate->setTimestamp(time() - $i * 86400);
            $startDate->setTime(0, 0, 0);
            $stopDate->setTimestamp(time() - $i * 86400);
            $stopDate->setTime(23, 59, 59);
            $statistic['weekgraf'][$i]['name'] = $startDate->format('N');
            $query = $em->createQuery('SELECT SUM(s.day1) as day1, SUM(s.day2) as day2, SUM(s.day3) as day3, SUM(s.day4) as day4, SUM(s.day5) as day5, SUM(s.day6) as day6, SUM(s.day7) as day7 '.
                                      'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId != 0 AND s.visitDate >= :start AND s.visitDate <= :stop')->setParameter('start', $startDate)->setParameter('stop', $stopDate);
            $stat = $query->getResult();
            if (is_array($stat))
            {
                for ($j = 0; $j < 7; $j++)
                {
                    if (isset($statistic['weekgraf'][$i + $j]) && isset($stat[0]['day'.($j + 1)])) $statistic['weekgraf'][$i + $j]['count'] += $stat[0]['day'.($j + 1)];
                }
            }
            $query = $em->createQuery('SELECT SUM(s.day1) as day1, SUM(s.day2) as day2, SUM(s.day3) as day3, SUM(s.day4) as day4, SUM(s.day5) as day5, SUM(s.day6) as day6, SUM(s.day7) as day7 '.
                                      'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId = 0 AND s.visitDate >= :start AND s.visitDate <= :stop')->setParameter('start', $startDate)->setParameter('stop', $stopDate);
            $stat = $query->getResult();
            if (is_array($stat))
            {
                for ($j = 0; $j < 7; $j++)
                {
                    if (isset($statistic['weekgraf'][$i + $j]) && isset($stat[0]['day'.($j + 1)])) $statistic['weekgraf'][$i + $j]['countUniq'] += $stat[0]['day'.($j + 1)];
                }
            }
        }
        for ($i = 0; $i < 7; $i++)
        {
            if ($statistic['weekgraf']['max'] < $statistic['weekgraf'][$i]['count']) $statistic['weekgraf']['max'] = $statistic['weekgraf'][$i]['count'];
            if ($statistic['weekgraf']['maxUniq'] < $statistic['weekgraf'][$i]['countUniq']) $statistic['weekgraf']['maxUniq'] = $statistic['weekgraf'][$i]['countUniq'];
        }
        // Данные за месяц
        $startDate->setTimestamp(time());
        $startDate->setTime(0, 0, 0);
        $startDate->setDate($startDate->format('Y'), $startDate->format('m'), 1);
        $query = $em->createQuery('SELECT SUM(s.month1) as month1, SUM(s.month2) as month2 '.
                                  'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId != 0 AND s.visitDate >= :start')->setParameter('start', $startDate);
        $stat = $query->getResult();
        if (is_array($stat))
        {
            $statistic['thisMonth'] += $stat[0]['month1'];
            $statistic['prevMonth'] += $stat[0]['month2'];
        }
        $query = $em->createQuery('SELECT SUM(s.month1) as month1, SUM(s.month2) as month2 '.
                                  'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId = 0 AND s.visitDate >= :start')->setParameter('start', $startDate);
        $stat = $query->getResult();
        if (is_array($stat))
        {
            $statistic['thisMonthUniq'] += $stat[0]['month1'];
            $statistic['prevMonthUniq'] += $stat[0]['month2'];
        }
        $stopDate->setTime(0, 0, 0);
        $stopDate->setDate($startDate->format('Y') - ($startDate->format('m') == 1 ? 1 : 0), ($startDate->format('m') == 1 ? 12 : $startDate->format('m') - 1), 1);
        $query = $em->createQuery('SELECT SUM(s.month1) as month1 '.
                                  'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId != 0 AND s.visitDate < :start AND s.visitDate >= :stop')->setParameter('start', $startDate)->setParameter('stop', $stopDate);
        $stat = $query->getResult();
        if (is_array($stat))
        {
            $statistic['prevMonth'] += $stat[0]['month1'];
        }
        $query = $em->createQuery('SELECT SUM(s.month1) as month1 '.
                                  'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId = 0 AND s.visitDate < :start AND s.visitDate >= :stop')->setParameter('start', $startDate)->setParameter('stop', $stopDate);
        $stat = $query->getResult();
        if (is_array($stat))
        {
            $statistic['prevMonthUniq'] += $stat[0]['month1'];
        }
        $query = $em->createQuery('SELECT SUM(s.allVisits) as allVisits '.
                                  'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId != 0');
        $stat = $query->getResult();
        if (is_array($stat))
        {
            $statistic['allVisits'] += $stat[0]['allVisits'];
        }
        $query = $em->createQuery('SELECT SUM(s.allVisits) as allVisits '.
                                  'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId = 0');
        $stat = $query->getResult();
        if (is_array($stat))
        {
            $statistic['allVisitsUniq'] += $stat[0]['allVisits'];
        }
        // Лог событий
        $query = $em->createQuery('SELECT l.id, l.text, l.logDate, l.viewed FROM BasicCmsBundle:LogFront l ORDER BY l.logDate DESC')->setMaxResults(10);
        $logfront = $query->getResult();
        if (!is_array($logfront)) $logfront = array();
        return $this->render('BasicCmsBundle:Default:index.html.twig', array(
            'menu' => $menu,
            'statistic' => $statistic,
            'logfront' => $logfront
        ));
    }
    
    
// *******************************************
// Подключение к TinyMCE jbimage
// *******************************************    
    
    public function tinyMceBlankAction()
    {
        return $this->render('BasicCmsBundle:Default:tinyMceBlank.html.twig', array());
    }
    
    public function tinyMceUploadAction()
    {
        if ($this->getUser() == null) return $this->render('BasicCmsBundle:Default:tinyMceBlank.html.twig', array());
        $file = $this->get('request_stack')->getMasterRequest()->files->get('userfile');
        $tmpfile = $file->getPathName(); 
        $source = "";
        $error = '';
        if (@getimagesize($tmpfile))
        {
/*             $params = getimagesize($tmpfile);
             if ($params[0] > 6000) $error = 'Разрешение файла больше 6000x6000';
             if ($params[1] > 6000) $error = 'Разрешение файла больше 6000x6000';
             if ($error == '')
             {
                 switch ($params[2]) 
                 {
                      case 1: $source = @imagecreatefromgif($tmpfile); break;
                      case 2: $source = @imagecreatefromjpeg($tmpfile); break;
                      case 3: $source = @imagecreatefrompng($tmpfile); break;                                               
                 }
                 if ($source)
                 {  
                      $basepath = '/images/editor/';
                      $name = $this->getUser()->getId().'_'.md5(time()).'.jpg';
                      if (imagejpeg ($source, '.'.$basepath.$name))
                      {
                          $result = array();
                          $result['result'] = "Файл загружен";
                          $result['resultcode'] = 'ok';
                          $result['file_name'] = $basepath.$name;
                          return $this->render('BasicCmsBundle:Default:tinyMceUpload.html.twig', array('result'=>$result));
                      }
                 } else $error = 'Ошибка формата файла';
             }*/
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
                $name = $this->getUser()->getId().'_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
                if (move_uploaded_file($tmpfile, $basepath . $name)) 
                {
                    $this->container->get('cms.cmsManager')->registerTemporaryFile('/images/editor/'.$name, $file->getClientOriginalName());
                    $result = array();
                    $result['result'] = "Файл загружен";
                    $result['resultcode'] = 'ok';
                    $result['file_name'] = '/images/editor/'.$name;
                    return $this->render('BasicCmsBundle:Default:tinyMceUpload.html.twig', array('result'=>$result));                    
                }
            }
        }
        if ($error == '') $error = 'Ошибка загрузки файла';
        $result = array();
        $result['result'] = $error;
        $result['resultcode']	= 'failed';
        return $this->render('BasicCmsBundle:Default:tinyMceUpload.html.twig', array('result'=>$result));
    }

    
// *******************************************
// Настройка карты сайта
// *******************************************    
    public function siteMapAction()
    {
        if ($this->getUser()->checkAccess('site_map') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Карта сайта',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $page0 = $this->get('request_stack')->getMasterRequest()->get('page0');
        if ($page0 === null) $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_sitemap_page0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_sitemap_page0', $page0);
        $page0 = intval($page0);
        
        // Поиск контента для 1 таба (пользователи)
        $query = $em->createQuery('SELECT count(b.id) as bccount FROM BasicCmsBundle:BreadCrumbPaths b');
        $bccount = $query->getResult();
        if (!empty($bccount)) $bccount = $bccount[0]['bccount']; else $bccount = 0;
        $pagecount0 = ceil($bccount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT b.id, b.seoPageId, b.path, sp.description, sp.url FROM BasicCmsBundle:BreadCrumbPaths b '.
                                  'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.id = b.seoPageId ORDER BY b.id')->setFirstResult($start)->setMaxResults(20);
        $paths = $query->getResult();
        foreach ($paths as &$pathitem)
        {
            $pathitem['pathitems'] = array();
            $ids = explode(',', $pathitem['path']);
            if (!is_array($ids)) $ids = array($pathitem['path']);
            $query = $em->createQuery('SELECT sp.id, sp.description, sp.url FROM BasicCmsBundle:SeoPage sp WHERE sp.id IN (:ids)')->setParameter('ids', $ids);
            $seopages = $query->getResult();
            if (is_array($seopages))
            {
                foreach ($ids as $oneid)
                    foreach ($seopages as $page)
                        if ($page['id'] == $oneid)
                            $pathitem['pathitems'][] = $page;
            }
        }
        return $this->render('BasicCmsBundle:Default:siteMap.html.twig', array(
            'paths0' => $paths,
            'page0' => $page0,
            'pagecount0' => $pagecount0,
        ));
    }
    
// *******************************************
// Создание нового пути
// *******************************************    
    public function pathCreateAction()
    {
        if ($this->getUser()->checkAccess('site_map') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание нового пути',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $path = array();
        $patherror = array();
        $path['pageid'] = '';
        $path['pagedescription'] = '';
        $path['path'] = array();
        $patherror['pageid'] = '';
        
        // Валидация
        $errors = false;
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postpath = $this->get('request_stack')->getMasterRequest()->get('path');
            if (isset($postpath['pageid'])) $path['pageid'] = $postpath['pageid'];
            if (is_array($postpath['path']))
            {
                $index = 0;
                foreach ($postpath['path'] as $item)
                    if ((isset($item['ordering'])) && (isset($item['id'])))
                    {
                        $seopage = $em->getRepository('BasicCmsBundle:SeoPage')->find($item['id']);
                        if (!empty($seopage)) 
                        {
                            $path['path'][$item['ordering']] = array('id' => $item['id'], 'description' => $seopage->getDescription(), 'ordering' => $index);
                            $index++;
                        }
                        unset($seopage);
                    }
            }
            unset($postpath);
            $seopage = $em->getRepository('BasicCmsBundle:SeoPage')->find($path['pageid']);
            if (empty($seopage)) {$errors = true; $patherror['pageid'] = 'Страница не найдена';} else
            {
                $path['pagedescription'] = $seopage->getDescription();
            }
            unset($seopage);
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $pathent = new \Basic\CmsBundle\Entity\BreadCrumbPaths();
                $pathent->setSeoPageId($path['pageid']);
                $ids = array();
                foreach ($path['path'] as $item) $ids[] = $item['id'];
                $pathent->setPath(implode(',', $ids));
                $em->persist($pathent);
                $em->flush();
                $this->get('cms.cmsManager')->updateBreadCrumbs($path['pageid']);
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание нового пути',
                    'message'=>'Путь хлебных крошек успешно создан',
                    'paths'=>array('Создать еще один путь'=>$this->get('router')->generate('basic_cms_path_create'),'Вернуться к карте сайта'=>$this->get('router')->generate('basic_cms_sitemap'))
                ));
            }
        }
        return $this->render('BasicCmsBundle:Default:pathCreate.html.twig', array(
            'path' => $path,
            'patherror' => $patherror
        ));
    }
    
    public function ajaxGetPagesAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $search = trim($this->get('request_stack')->getMasterRequest()->get('search'));
        $page = intval($this->get('request_stack')->getMasterRequest()->get('page'));
        if ($page < 0) $page = 0;
        $query = $em->createQuery('SELECT count(p.id) as pagecount FROM BasicCmsBundle:SeoPage p WHERE p.description LIKE :search OR p.url LIKE :search')
                    ->setParameter('search', '%'.$search.'%');
        $pagecount = $query->getResult();
        if (isset($pagecount[0]['pagecount'])) $pagecount = ceil(intval($pagecount[0]['pagecount'])/20); else $pagecount = 0;
        if ($pagecount < 1) $pagecount = 1;
        if ($page >= $pagecount) $page = $pagecount - 1;
        $query = $em->createQuery('SELECT p.id, p.description, p.url FROM BasicCmsBundle:SeoPage p WHERE p.description LIKE :search OR p.url LIKE :search')
                    ->setParameter('search', '%'.$search.'%')->setFirstResult($page * 20)->setMaxResults(20);
        $pages = $query->getResult();
        if (!is_array($pages)) $pages = array();
        return new Response(json_encode(array('page'=>$page, 'pagecount'=>$pagecount, 'pages'=>$pages)));
    }
    
// *******************************************
// Редактирование пути
// *******************************************    
    public function pathEditAction()
    {
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $pathent = $this->getDoctrine()->getRepository('BasicCmsBundle:BreadCrumbPaths')->find($id);
        if (empty($pathent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование пути',
                'message'=>'Путь хлебных крошек не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к карте сайта'=>$this->get('router')->generate('basic_cms_sitemap'))
            ));
        }
        if ($this->getUser()->checkAccess('site_map') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование пути',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $path = array();
        $patherror = array();
        $path['pageid'] = $pathent->getSeoPageId();
        $seopage = $em->getRepository('BasicCmsBundle:SeoPage')->find($path['pageid']);
        if (!empty($seopage)) $path['pagedescription'] = $seopage->getDescription(); else $path['pagedescription'] = '';
        unset($seopage);
        $path['path'] = array();
        $patherror['pageid'] = '';
        $ids = explode(',', $pathent->getPath());
        if (is_array($ids))
        {
            $index = 0;
            foreach ($ids as $item)
            {
                $seopage = $em->getRepository('BasicCmsBundle:SeoPage')->find($item);
                if (!empty($seopage)) 
                {
                    $path['path'][] = array('id' => $item, 'description' => $seopage->getDescription(), 'ordering' => $index);
                    $index++;
                }
                unset($seopage);
            }
        }
        // Валидация
        $errors = false;
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            $path['path'] = array();
            // Проверка основных данных
            $postpath = $this->get('request_stack')->getMasterRequest()->get('path');
            if (isset($postpath['pageid'])) $path['pageid'] = $postpath['pageid'];
            if (is_array($postpath['path']))
            {
                $index = 0;
                foreach ($postpath['path'] as $item)
                    if ((isset($item['ordering'])) && (isset($item['id'])))
                    {
                        $seopage = $em->getRepository('BasicCmsBundle:SeoPage')->find($item['id']);
                        if (!empty($seopage)) 
                        {
                            $path['path'][$item['ordering']] = array('id' => $item['id'], 'description' => $seopage->getDescription(), 'ordering' => $index);
                            $index++;
                        }
                        unset($seopage);
                    }
            }
            unset($postpath);
            $seopage = $em->getRepository('BasicCmsBundle:SeoPage')->find($path['pageid']);
            if (empty($seopage)) {$errors = true; $patherror['pageid'] = 'Страница не найдена';} else
            {
                $path['pagedescription'] = $seopage->getDescription();
            }
            unset($seopage);
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $pathent->setSeoPageId($path['pageid']);
                $ids = array();
                foreach ($path['path'] as $item) $ids[] = $item['id'];
                $pathent->setPath(implode(',', $ids));
                $em->flush();
                $this->get('cms.cmsManager')->updateBreadCrumbs($path['pageid']);
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование пути',
                    'message'=>'Путь хлебных крошек успешно отредактирован',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_path_edit').'?id='.$id, 'Вернуться к карте сайта'=>$this->get('router')->generate('basic_cms_sitemap'))
                ));
            }
        }
        return $this->render('BasicCmsBundle:Default:pathEdit.html.twig', array(
            'id' => $id,
            'path' => $path,
            'patherror' => $patherror
        ));
    }

// *******************************************
// AJAX-контроллер путей
// *******************************************    
    
    public function pathAjaxAction()
    {
        if ($this->getUser()->checkAccess('site_map') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Карта сайта',
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
            $check = $this->get('request_stack')->getMasterRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $pathent = $this->getDoctrine()->getRepository('BasicCmsBundle:BreadCrumbPaths')->find($key);
                    if (!empty($pathent))
                    {
                        $this->get('cms.cmsManager')->updateBreadCrumbs($pathent->getSeoPageId());
                        $em->remove($pathent);
                        $em->flush();
                        unset($pathent);    
                    }
                }
        }
        $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_sitemap_page0');
        $page0 = intval($page0);
        // Поиск контента для 1 таба (пользователи)
        $query = $em->createQuery('SELECT count(b.id) as bccount FROM BasicCmsBundle:BreadCrumbPaths b');
        $bccount = $query->getResult();
        if (!empty($bccount)) $bccount = $bccount[0]['bccount']; else $bccount = 0;
        $pagecount0 = ceil($bccount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT b.id, b.seoPageId, b.path, sp.description, sp.url FROM BasicCmsBundle:BreadCrumbPaths b '.
                                  'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.id = b.seoPageId ORDER BY b.id')->setFirstResult($start)->setMaxResults(20);
        $paths = $query->getResult();
        foreach ($paths as &$pathitem)
        {
            $pathitem['pathitems'] = array();
            $ids = explode(',', $pathitem['path']);
            if (!is_array($ids)) $ids = array($pathitem['path']);
            $query = $em->createQuery('SELECT sp.id, sp.description, sp.url FROM BasicCmsBundle:SeoPage sp WHERE sp.id IN (:ids)')->setParameter('ids', $ids);
            $seopages = $query->getResult();
            if (is_array($seopages))
            {
                foreach ($ids as $oneid)
                    foreach ($seopages as $page)
                        if ($page['id'] == $oneid)
                            $pathitem['pathitems'][] = $page;
            }
        }
        return $this->render('BasicCmsBundle:Default:siteMapTab1.html.twig', array(
            'paths0' => $paths,
            'page0' => $page0,
            'pagecount0' => $pagecount0,
        ));
    }

// *******************************************
// AJAX-генератор карты сайта
// *******************************************    
    
    public function siteMapAjaxGenerateAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        $id = trim($this->get('request_stack')->getMasterRequest()->get('id'));
        // Если ID = 0 очистить tmp файл
        if ($id == 0) 
        {
            $file = @fopen('sitemap.tmp', "w"); 
            fwrite($file,
                '<?xml version="1.0" encoding="UTF-8"?>'."\r\n".
                '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\r\n");
        } else $file = @fopen('sitemap.tmp', "a");
        if ($file === false) return new Response(json_encode(array('result'=>'Ошибка открытия файла')));
        //Найти страницы 100 штук
        $query = $em->createQuery('SELECT p.id, p.locale, p.url, p.contentType, p.contentAction, p.contentId, p.access FROM BasicCmsBundle:SeoPage p WHERE p.id > :id')->setParameter('id',$id)->setMaxResults(100);
        $pages = $query->getResult();
        // Обработать
        if ((is_array($pages)) && (count($pages) > 0))
        {
            foreach ($pages as $page)
            if (($page['access'] == '') || (!is_array(explode(',', $page['access']))))
            {
                $id = $page['id'];
                $objectinfo = null;
                if ($this->has($page['contentType'])) $objectinfo = $this->get($page['contentType'])->getInfoSitemap($page['contentAction'], $page['contentId']);
                if ($objectinfo != null)
                {
                    $urls = array();
                    if ($page['locale'] == '')
                    {
                        $urls[] = 'http://'.$this->get('request_stack')->getMasterRequest()->getHttpHost().'/'.($page['url'] != 'index.html' ? $page['url'] : '');
                        foreach ($locales as $locale) $urls[] = 'http://'.$this->get('request_stack')->getMasterRequest()->getHttpHost().'/'.$locale['shortName'].'/'.$page['url'];
                    } else
                    {
                        foreach ($locales as $locale) if ($item['locale'] == $locales['shortName']) $urls[] = 'http://'.$this->get('request_stack')->getMasterRequest()->getHttpHost().'/'.$locale['shortName'].'/'.$page['url'];
                    }
                    if (count($urls) > 0)
                    {
                        foreach ($urls as $url)
                        {
                            $info = '<url>'."\r\n".
                                    '<loc>'.$url.'</loc>'."\r\n";
                            if (isset($objectinfo['avatar']) && ($objectinfo['avatar'] != ''))
                            {
                                $info = $info.'<image:image>'."\r\n".
                                              '<image:loc>http://'.$this->get('request_stack')->getMasterRequest()->getHttpHost().$objectinfo['avatar'].'</image:loc>'."\r\n".
                                              '</image:image>'."\r\n";
                            }
                            if (isset($objectinfo['modifyDate']) && ($objectinfo['modifyDate'] != null))
                            {
                                $info = $info.'<lastmod>'.$objectinfo['modifyDate']->format('Y-m-d').'</lastmod>'."\r\n";
                            }
                            if (isset($objectinfo['priority']) && ($objectinfo['priority'] != null))
                            {
                                $info = $info.'<priority>'.$objectinfo['priority'].'</priority>'."\r\n";
                            }
                            $info = $info.'</url>'."\r\n";
                            fwrite($file, $info);
                        }
                    }
                }
            }
            fclose($file);
            $status = 'OK';
        } else
        {
            fwrite($file, '</urlset>'."\r\n");
            fclose($file);
            // переместить файл в sitemap.xml
            @unlink('sitemap.xml');
            rename('sitemap.tmp', 'sitemap.xml');
            // записать robots.txt
            $filer = @fopen('robots.txt', "w"); 
            fwrite($filer,
                    'User-agent: *'."\r\n".
                    'Disallow: /admin/'."\r\n".
                    'Disallow: /system/'."\r\n".
                    'Host: '.$this->get('request_stack')->getMasterRequest()->getHttpHost()."\r\n".
                    'Sitemap: http://'.$this->get('request_stack')->getMasterRequest()->getHttpHost().'/sitemap.xml'."\r\n");
            fclose($filer);
            $status = 'Fine';
        }
        // Вывести результат
        $query = $em->createQuery('SELECT count(p.id) as pagecount FROM BasicCmsBundle:SeoPage p WHERE p.id < :id')->setParameter('id',$id);
        $pagecount = $query->getResult();
        if (isset($pagecount[0]['pagecount'])) $pagecount = $pagecount[0]['pagecount']; else $pagecount = 0;
        $query = $em->createQuery('SELECT count(p.id) as pagecount FROM BasicCmsBundle:SeoPage p');
        $allpagecount = $query->getResult();
        if (isset($allpagecount[0]['pagecount'])) $allpagecount = $allpagecount[0]['pagecount']; else $allpagecount = 0;
        $percent = 0;
        if ($allpagecount != 0) $percent = intval($pagecount * 100 / $allpagecount);
        if ($percent > 100) $percent = 100;
        return new Response(json_encode(array('result'=>'OK', 'status'=>$status, 'percent'=>$percent, 'id'=>$id)));
    }

// *******************************************
// Список локализаций
// *******************************************    
    public function localeListAction()
    {
        if ($this->getUser()->checkAccess('locale_edit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Локализация',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        // Поиск контента для 1 таба (пользователи)
        $query = $em->createQuery('SELECT l.id, l.shortName, l.fullName FROM BasicCmsBundle:Locales l ORDER BY l.id');
        $locales = $query->getResult();
        return $this->render('BasicCmsBundle:Default:localeList.html.twig', array(
            'locales' => $locales,
        ));
    }
    
// *******************************************
// Создание новой локали
// *******************************************    
    public function localeCreateAction()
    {
        if ($this->getUser()->checkAccess('locale_edit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой локали',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $locale = array();
        $localeerror = array();

        $locale['shortName'] = '';
        $locale['fullName'] = '';
        $localeerror['shortName'] = '';
        $localeerror['fullName'] = '';

        // Валидация
        $errors = false;
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postlocale = $this->get('request_stack')->getMasterRequest()->get('locale');
            if (isset($postlocale['shortName'])) $locale['shortName'] = $postlocale['shortName'];
            if (isset($postlocale['fullName'])) $locale['fullName'] = $postlocale['fullName'];
            unset($postlocale);
            if (!preg_match("/^[A-z][A-z]$/ui", $locale['shortName'])) {$errors = true; $localeerror['shortName'] = 'Код локали должен содержать 2 латинские буквы';}
            else
            {
                $query = $em->createQuery('SELECT count(l.id) as loccount FROM BasicCmsBundle:Locales l WHERE l.shortName = :name')->setParameter('name', strtolower($locale['shortName']));
                $checkloc = $query->getResult();
                if (!isset($checkloc[0]['loccount']) || ($checkloc[0]['loccount'] != 0)) {$errors = true; $localeerror['shortName'] = 'Код локали уже используется';}
                unset($checkloc);
            }
            if (!preg_match("/^[-0-9A-zА-яЁё\s\`]{3,99}$/ui", $locale['fullName'])) {$errors = true; $localeerror['fullName'] = 'Название должно содержать от 3 до 99 букв';}
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $locent = new \Basic\CmsBundle\Entity\Locales();
                $locent->setShortName(strtolower($locale['shortName']));
                $locent->setFullName($locale['fullName']);
                $em->persist($locent);
                $em->flush();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой локали',
                    'message'=>'Локаль успешно создана',
                    'paths'=>array('Создать еще одну локаль'=>$this->get('router')->generate('basic_cms_locale_create'),'Вернуться к списку локалей'=>$this->get('router')->generate('basic_cms_locale_list'))
                ));
            }
        }
        return $this->render('BasicCmsBundle:Default:localeCreate.html.twig', array(
            'locale' => $locale,
            'localeerror' => $localeerror,
        ));
    }
    
// *******************************************
// Редактирование локали
// *******************************************    
    public function localeEditAction()
    {
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $locent = $this->getDoctrine()->getRepository('BasicCmsBundle:Locales')->find($id);
        if (empty($locent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование локали',
                'message'=>'Локаль не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку локалей'=>$this->get('router')->generate('basic_cms_locale_list'))
            ));
        }
        if ($this->getUser()->checkAccess('locale_edit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой локали',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $locale = array();
        $localeerror = array();

        $locale['shortName'] = $locent->getShortName();
        $locale['fullName'] = $locent->getFullName();
        $localeerror['shortName'] = '';
        $localeerror['fullName'] = '';

        // Валидация
        $errors = false;
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postlocale = $this->get('request_stack')->getMasterRequest()->get('locale');
            if (isset($postlocale['shortName'])) $locale['shortName'] = $postlocale['shortName'];
            if (isset($postlocale['fullName'])) $locale['fullName'] = $postlocale['fullName'];
            unset($postlocale);
            if (!preg_match("/^[A-z][A-z]$/ui", $locale['shortName'])) {$errors = true; $localeerror['shortName'] = 'Код локали должен содержать 2 латинские буквы';}
            else
            {
                $query = $em->createQuery('SELECT count(l.id) as loccount FROM BasicCmsBundle:Locales l WHERE l.shortName = :name')->setParameter('name', strtolower($locale['shortName']));
                $checkloc = $query->getResult();
                if (!isset($checkloc[0]['loccount']) || ($checkloc[0]['loccount'] != 0)) {$errors = true; $localeerror['shortName'] = 'Код локали уже используется';}
                unset($checkloc);
            }
            if (!preg_match("/^[-0-9A-zА-яЁё\s\`]{3,99}$/ui", $locale['fullName'])) {$errors = true; $localeerror['fullName'] = 'Название должно содержать от 3 до 99 букв';}
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $locent->setShortName(strtolower($locale['shortName']));
                $locent->setFullName($locale['fullName']);
                $em->flush();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование локали',
                    'message'=>'Локаль успешно изменена',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_locale_edit').'?id='.$id, 'Вернуться к списку локалей'=>$this->get('router')->generate('basic_cms_locale_list'))
                ));
            }
        }
        return $this->render('BasicCmsBundle:Default:localeEdit.html.twig', array(
            'id' => $id,
            'locale' => $locale,
            'localeerror' => $localeerror,
        ));
    }
    
// *******************************************
// AJAX-контроллер локалей
// *******************************************    
    
    public function localeAjaxAction()
    {
        if ($this->getUser()->checkAccess('locale_edit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Локализация',
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
            $check = $this->get('request_stack')->getMasterRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $locent = $this->getDoctrine()->getRepository('BasicCmsBundle:Locales')->find($key);
                    if (!empty($locent))
                    {
                        $em->remove($locent);
                        $em->flush();
                    }
                    unset($locent);    
                }
        }
        // Поиск контента для 1 таба (пользователи)
        $query = $em->createQuery('SELECT l.id, l.shortName, l.fullName FROM BasicCmsBundle:Locales l ORDER BY l.id');
        $locales = $query->getResult();
        return $this->render('BasicCmsBundle:Default:localeListTab1.html.twig', array(
            'locales' => $locales,
        ));
    }
    
// *******************************************
// Настройка страниц ошибок
// *******************************************    
    public function errorCfgAction()
    {
        if ($this->getUser()->checkAccess('error_view') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Настройка оформления ошибок',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $cmsManager = $this->get('cms.cmsManager');
        $em = $this->getDoctrine()->getEntityManager();
        
        $page = array();
        $pageerror = array();
        
        $page['modules'] = @explode(',', $cmsManager->getConfigValue('system.errorpage.modules'));
        if (!is_array($page['modules'])) $page['modules'] = array();
        $page['template'] = $cmsManager->getConfigValue('system.errorpage.template');
        $page['layout'] = $cmsManager->getConfigValue('system.errorpage.layout');
        $pageerror['modules'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        
        $templates = $this->get('cms.cmsManager')->getErrorTemplateList();
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, 0 as moduleDefOn FROM BasicCmsBundle:Modules m');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            if ($this->getUser()->checkAccess('error_edit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Настройка оформления ошибок',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            // Проверка данных о странице
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            unset($postpage);
            if (($page['layout'] != '') && (!isset($layouts[$page['layout']]))) {$errors = true; $pageerror['layout'] = 'Шаблон не найден';}
            if (($page['template'] != '') && (!isset($templates[$page['template']]))) {$errors = true; $pageerror['template'] = 'Шаблон не найден';}
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $cmsManager->setConfigValue('system.errorpage.layout', $page['layout']);
                $cmsManager->setConfigValue('system.errorpage.template', $page['template']);
                $cmsManager->setConfigValue('system.errorpage.modules', implode(',', $page['modules']));
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Настройка оформления ошибок',
                    'message'=>'Настройки успешно сохранены',
                    'paths'=>array('Вернуться к настройке'=>$this->get('router')->generate('basic_cms_errorcfg'),'Перейти на главную'=>$this->get('router')->generate('basic_cms_index'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Default:errorPageCfg.html.twig', array(
            'page' => $page,
            'pageerror' => $pageerror,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'activetab' => $activetab
        ));
    }

// *******************************************
// Создание страницы авторизации пользователя
// *******************************************    
    public function userAuthpageCreateAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_tab', 1);
        if ($this->getUser()->checkAccess('user_authpage') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой страницы авторизации',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $authpage = array();
        $authpageerror = array();
        
        $authpage['title'] = array();
        $authpage['title']['default'] = '';
        foreach ($locales as $locale) $authpage['title'][$locale['shortName']] = '';
        $authpage['enabled'] = 1;
        $authpage['redirectPath'] = '';
        $authpageerror['title'] = array();
        $authpageerror['title']['default'] = '';
        foreach ($locales as $locale) $authpageerror['title'][$locale['shortName']] = '';
        $authpageerror['enabled'] = '';
        $authpageerror['redirectPath'] = '';
        
        $page = array();
        $pageerror = array();
        
        $page['url'] = '';
        $page['modules'] = array();
        $page['locale'] = '';
        $page['template'] = '';
        $page['layout'] = '';
        $page['accessOn'] = '';
        $page['access'] = array();
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.user','auth');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.user.auth');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postauthpage = $this->get('request_stack')->getMasterRequest()->get('authpage');
            if (isset($postauthpage['title']['default'])) $authpage['title']['default'] = $postauthpage['title']['default'];
            foreach ($locales as $locale) if (isset($postauthpage['title'][$locale['shortName']])) $authpage['title'][$locale['shortName']] = $postauthpage['title'][$locale['shortName']];
            if (isset($postauthpage['enabled'])) $authpage['enabled'] = intval($postauthpage['enabled']); else $authpage['enabled'] = 0;
            if (isset($postauthpage['redirectPath'])) $authpage['redirectPath'] = $postauthpage['redirectPath'];
            unset($postauthpage);
            if (!preg_match("/^.{3,}$/ui", $authpage['title']['default'])) {$errors = true; $authpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $authpage['title'][$locale['shortName']])) {$errors = true; $authpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if (!preg_match("/^.{0,255}$/ui", $authpage['redirectPath'])) {$errors = true; $authpageerror['redirectPath'] = 'Путь должен содержать до 255 символов';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['url'] != '')
            {
                if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
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
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $query = $em->createQuery('SELECT count(a.id) as authcount FROM BasicCmsBundle:UserAuthPages a');
                $authcount = $query->getResult();
                if (isset($authcount[0]['authcount'])) $authcount = $authcount[0]['authcount']; else $authcount = 0;
                $authpageent = new \Basic\CmsBundle\Entity\UserAuthPages();
                $authpageent->setEnabled($authpage['enabled']);
                $authpageent->setIsDefault(($authcount == 0 ? 1 : 0));
                $authpageent->setRedirectPath($authpage['redirectPath']);
                $authpageent->setTemplate($page['template']);
                $authpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($authpage['title']));
                $em->persist($authpageent);
                $em->flush();
                
                $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница авторизации '.$this->get('cms.cmsManager')->decodeLocalString($authpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.user');
                $pageent->setContentId($authpageent->getId());
                $pageent->setContentAction('auth');
                $authpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->persist($pageent);
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой страницы авторизации',
                    'message'=>'Страница авторизации успешно создана',
                    'paths'=>array('Создать еще одну страницу авторизации'=>$this->get('router')->generate('basic_cms_user_authpage_create'),'Вернуться к списку пользователей'=>$this->get('router')->generate('basic_cms_user_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Default:userAuthpageCreate.html.twig', array(
            'authpage' => $authpage,
            'authpageerror' => $authpageerror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'activetab' => $activetab
        ));
    }

// *******************************************
// Редактирование страницы авторизации пользователя
// *******************************************    
    public function userAuthpageEditAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_tab', 1);
        if ($this->getUser()->checkAccess('user_authpage') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы авторизации',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $authpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserAuthPages')->find($id);
        if (empty($authpageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы авторизации',
                'message'=>'Страница не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку пользователь'=>$this->get('router')->generate('basic_cms_user_list'))
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.user','contentId'=>$id,'contentAction'=>'auth'));
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $authpage = array();
        $authpageerror = array();
        
        $authpage['title'] = array();
        $authpage['title']['default'] = $this->get('cms.cmsManager')->decodeLocalString($authpageent->getTitle(),'default',false);
        foreach ($locales as $locale) $authpage['title'][$locale['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($authpageent->getTitle(),$locale['shortName'],false);;
        $authpage['enabled'] = $authpageent->getEnabled();
        $authpage['redirectPath'] = $authpageent->getRedirectPath();
        $authpageerror['title'] = array();
        $authpageerror['title']['default'] = '';
        foreach ($locales as $locale) $authpageerror['title'][$locale['shortName']] = '';
        $authpageerror['enabled'] = '';
        $authpageerror['redirectPath'] = '';
        
        $page = array();
        $pageerror = array();
        
        if (empty($pageent))
        {
            $page['url'] = '';
            $page['modules'] = array();
            $page['locale'] = '';
            $page['template'] = '';
            $page['layout'] = '';
            $page['accessOn'] = '';
            $page['access'] = array();
        } else
        {
            $page['url'] = ($pageent->getUrl() != $pageent->getId() ? $pageent->getUrl() : '');
            $page['modules'] = explode(',',$pageent->getModules());
            $page['locale'] = $pageent->getLocale();
            $page['template'] = $authpageent->getTemplate();
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
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.user','auth');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (empty($pageent))
        {
            if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
            if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        }
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.user.auth');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postauthpage = $this->get('request_stack')->getMasterRequest()->get('authpage');
            if (isset($postauthpage['title']['default'])) $authpage['title']['default'] = $postauthpage['title']['default'];
            foreach ($locales as $locale) if (isset($postauthpage['title'][$locale['shortName']])) $authpage['title'][$locale['shortName']] = $postauthpage['title'][$locale['shortName']];
            if (isset($postauthpage['enabled'])) $authpage['enabled'] = intval($postauthpage['enabled']); else $authpage['enabled'] = 0;
            if (isset($postauthpage['redirectPath'])) $authpage['redirectPath'] = $postauthpage['redirectPath'];
            unset($postauthpage);
            if (!preg_match("/^.{3,}$/ui", $authpage['title']['default'])) {$errors = true; $authpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $authpage['title'][$locale['shortName']])) {$errors = true; $authpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if (!preg_match("/^.{0,255}$/ui", $authpage['redirectPath'])) {$errors = true; $authpageerror['redirectPath'] = 'Путь должен содержать до 255 символов';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['url'] != '')
            {
                if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
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
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $authpageent->setEnabled($authpage['enabled']);
                $authpageent->setRedirectPath($authpage['redirectPath']);
                $authpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($authpage['title']));
                $em->flush();
                if (!empty($pageent)) $this->get('cms.cmsManager')->updateBreadCrumbs($pageent->getId());
                if (empty($pageent))
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $em->persist($pageent);
                }
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница авторизации '.$this->get('cms.cmsManager')->decodeLocalString($authpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.user');
                $pageent->setContentId($authpageent->getId());
                $pageent->setContentAction('auth');
                $authpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование страницы авторизации',
                    'message'=>'Страница авторизации успешно отредактирована',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_user_authpage_edit').'?id='.$id, 'Вернуться к списку пользователей'=>$this->get('router')->generate('basic_cms_user_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Default:userAuthpageEdit.html.twig', array(
            'id' => $id,
            'authpage' => $authpage,
            'authpageerror' => $authpageerror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'activetab' => $activetab
        ));
    }

// *******************************************
// Создание страницы восстановления пароля
// *******************************************    
    public function userPasswordpageCreateAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_tab', 2);
        if ($this->getUser()->checkAccess('user_passwordpage') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой страницы восстановления пароля',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $passpage = array();
        $passpageerror = array();
        
        $passpage['title'] = array();
        $passpage['letterText'] = array();
        $passpage['title']['default'] = '';
        $passpage['letterText']['default'] = '';
        foreach ($locales as $locale) 
        {
            $passpage['title'][$locale['shortName']] = '';
            $passpage['letterText'][$locale['shortName']] = '';
        }
        $passpage['enabled'] = 1;
        $passpage['captchaEnabled'] = '';
        $passpage['allowPasswordType'] = '';
        
        $passpageerror['title'] = array();
        $passpageerror['letterText'] = array();
        $passpageerror['title']['default'] = '';
        $passpageerror['letterText']['default'] = '';
        foreach ($locales as $locale) 
        {
            $passpageerror['title'][$locale['shortName']] = '';
            $passpageerror['letterText'][$locale['shortName']] = '';
        }
        $passpageerror['enabled'] = '';
        $passpageerror['captchaEnabled'] = '';
        $passpageerror['allowPasswordType'] = '';
        
        $page = array();
        $pageerror = array();
        
        $page['url'] = '';
        $page['modules'] = array();
        $page['locale'] = '';
        $page['template'] = '';
        $page['layout'] = '';
        $page['accessOn'] = '';
        $page['access'] = array();
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.user','password');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.user.password');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postpasspage = $this->get('request_stack')->getMasterRequest()->get('passpage');
            if (isset($postpasspage['title']['default'])) $passpage['title']['default'] = $postpasspage['title']['default'];
            if (isset($postpasspage['letterText']['default'])) $passpage['letterText']['default'] = $postpasspage['letterText']['default'];
            foreach ($locales as $locale) 
            {
                if (isset($postpasspage['title'][$locale['shortName']])) $passpage['title'][$locale['shortName']] = $postpasspage['title'][$locale['shortName']];
                if (isset($postpasspage['letterText'][$locale['shortName']])) $passpage['letterText'][$locale['shortName']] = $postpasspage['letterText'][$locale['shortName']];
            }
            if (isset($postpasspage['enabled'])) $passpage['enabled'] = intval($postpasspage['enabled']); else $passpage['enabled'] = 0;
            if (isset($postpasspage['captchaEnabled'])) $passpage['captchaEnabled'] = intval($postpasspage['captchaEnabled']); else $passpage['captchaEnabled'] = 0;
            if (isset($postpasspage['allowPasswordType'])) $passpage['allowPasswordType'] = intval($postpasspage['allowPasswordType']); else $passpage['allowPasswordType'] = 0;
            unset($postpasspage);
            if (!preg_match("/^.{3,}$/ui", $passpage['title']['default'])) {$errors = true; $passpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $passpage['title'][$locale['shortName']])) {$errors = true; $passpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if (!preg_match("/^[\s\S]{10,}$/ui", $passpage['letterText']['default'])) {$errors = true; $passpageerror['letterText']['default'] = 'Текст письма должен содержать более 10 символов';}
            elseif (strpos($passpage['letterText']['default'], '//url//') === false) {$errors = true; $passpageerror['letterText']['default'] = 'Текст письма должен содержать строку //url// для подстановки ссылки';}
            foreach ($locales as $locale) if ($passpage['letterText'][$locale['shortName']] != '')
            {
                if (!preg_match("/^[\s\S]{10,}$/ui", $passpage['letterText'][$locale['shortName']])) {$errors = true; $passpageerror['letterText'][$locale['shortName']] = 'Текст письма должен содержать более 10 символов или оставьте поле пустым';}
                elseif (strpos($passpage['letterText'][$locale['shortName']], '//url//') === false) {$errors = true; $passpageerror['letterText'][$locale['shortName']] = 'Текст письма должен содержать строку //url// для подстановки ссылки';}
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['url'] != '')
            {
                if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
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
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                
                $passpageent = new \Basic\CmsBundle\Entity\UserPasswordPages();
                $passpageent->setEnabled($passpage['enabled']);
                $passpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($passpage['title']));
                $passpageent->setAllowPasswordType($passpage['allowPasswordType']);
                $passpageent->setCaptchaEnabled($passpage['captchaEnabled']);
                $passpageent->setLetterText($this->get('cms.cmsManager')->encodeLocalString($passpage['letterText']));
                $em->persist($passpageent);
                $em->flush();
                
                $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница восстановления пароля '.$this->get('cms.cmsManager')->decodeLocalString($passpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.user');
                $pageent->setContentId($passpageent->getId());
                $pageent->setContentAction('password');
                $passpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->persist($pageent);
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой страницы восстановления пароля',
                    'message'=>'Страница восстановления пароля успешно создана',
                    'paths'=>array('Создать еще одну страницу восстановления пароля'=>$this->get('router')->generate('basic_cms_user_passwordpage_create'),'Вернуться к списку пользователей'=>$this->get('router')->generate('basic_cms_user_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Default:userPasswordpageCreate.html.twig', array(
            'passpage' => $passpage,
            'passpageerror' => $passpageerror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// Редактирование страницы восстановления пароля
// *******************************************    
    public function userPasswordpageEditAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_tab', 2);
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $passpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserPasswordPages')->find($id);
        if (empty($passpageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы восстановления пароля',
                'message'=>'Страница восстановления пароля не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку пользователей'=>$this->get('router')->generate('basic_cms_user_list'))
            ));
        }
        if ($this->getUser()->checkAccess('user_passwordpage') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы восстановления пароля',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.user','contentId'=>$id,'contentAction'=>'password'));
        
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $passpage = array();
        $passpageerror = array();
        
        $passpage['title'] = array();
        $passpage['letterText'] = array();
        $passpage['title']['default'] = $this->get('cms.cmsManager')->decodeLocalString($passpageent->getTitle(),'default',false);
        $passpage['letterText']['default'] = $this->get('cms.cmsManager')->decodeLocalString($passpageent->getLetterText(),'default',false);
        foreach ($locales as $locale) 
        {
            $passpage['title'][$locale['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($passpageent->getTitle(),$locale['shortName'],false);;
            $passpage['letterText'][$locale['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($passpageent->getLetterText(),$locale['shortName'],false);
        }
        $passpage['enabled'] = $passpageent->getEnabled();
        $passpage['captchaEnabled'] = $passpageent->getCaptchaEnabled();
        $passpage['allowPasswordType'] = $passpageent->getAllowPasswordType();
        
        $passpageerror['title'] = array();
        $passpageerror['letterText'] = array();
        $passpageerror['title']['default'] = '';
        $passpageerror['letterText']['default'] = '';
        foreach ($locales as $locale) 
        {
            $passpageerror['title'][$locale['shortName']] = '';
            $passpageerror['letterText'][$locale['shortName']] = '';
        }
        $passpageerror['enabled'] = '';
        $passpageerror['captchaEnabled'] = '';
        $passpageerror['allowPasswordType'] = '';
        
        $page = array();
        $pageerror = array();
        
        if (empty($pageent))
        {
            $page['url'] = '';
            $page['modules'] = array();
            $page['locale'] = '';
            $page['template'] = '';
            $page['layout'] = '';
            $page['accessOn'] = '';
            $page['access'] = array();
        } else
        {
            $page['url'] = ($pageent->getUrl() != $pageent->getId() ? $pageent->getUrl() : '');
            $page['modules'] = explode(',',$pageent->getModules());
            $page['locale'] = $pageent->getLocale();
            $page['template'] = $passpageent->getTemplate();
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
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.user','password');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (empty($pageent))
        {
            if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
            if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        }
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.user.password');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postpasspage = $this->get('request_stack')->getMasterRequest()->get('passpage');
            if (isset($postpasspage['title']['default'])) $passpage['title']['default'] = $postpasspage['title']['default'];
            if (isset($postpasspage['letterText']['default'])) $passpage['letterText']['default'] = $postpasspage['letterText']['default'];
            foreach ($locales as $locale) 
            {
                if (isset($postpasspage['title'][$locale['shortName']])) $passpage['title'][$locale['shortName']] = $postpasspage['title'][$locale['shortName']];
                if (isset($postpasspage['letterText'][$locale['shortName']])) $passpage['letterText'][$locale['shortName']] = $postpasspage['letterText'][$locale['shortName']];
            }
            if (isset($postpasspage['enabled'])) $passpage['enabled'] = intval($postpasspage['enabled']); else $passpage['enabled'] = 0;
            if (isset($postpasspage['captchaEnabled'])) $passpage['captchaEnabled'] = intval($postpasspage['captchaEnabled']); else $passpage['captchaEnabled'] = 0;
            if (isset($postpasspage['allowPasswordType'])) $passpage['allowPasswordType'] = intval($postpasspage['allowPasswordType']); else $passpage['allowPasswordType'] = 0;
            unset($postpasspage);
            if (!preg_match("/^.{3,}$/ui", $passpage['title']['default'])) {$errors = true; $passpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $passpage['title'][$locale['shortName']])) {$errors = true; $passpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if (!preg_match("/^[\s\S]{10,}$/ui", $passpage['letterText']['default'])) {$errors = true; $passpageerror['letterText']['default'] = 'Текст письма должен содержать более 10 символов';}
            elseif (strpos($passpage['letterText']['default'], '//url//') === false) {$errors = true; $passpageerror['letterText']['default'] = 'Текст письма должен содержать строку //url// для подстановки ссылки';}
            foreach ($locales as $locale) if ($passpage['letterText'][$locale['shortName']] != '')
            {
                if (!preg_match("/^[\s\S]{10,}$/ui", $passpage['letterText'][$locale['shortName']])) {$errors = true; $passpageerror['letterText'][$locale['shortName']] = 'Текст письма должен содержать более 10 символов или оставьте поле пустым';}
                elseif (strpos($passpage['letterText'][$locale['shortName']], '//url//') === false) {$errors = true; $passpageerror['letterText'][$locale['shortName']] = 'Текст письма должен содержать строку //url// для подстановки ссылки';}
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['url'] != '')
            {
                if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
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
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                
                $passpageent->setEnabled($passpage['enabled']);
                $passpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($passpage['title']));
                $passpageent->setAllowPasswordType($passpage['allowPasswordType']);
                $passpageent->setCaptchaEnabled($passpage['captchaEnabled']);
                $passpageent->setLetterText($this->get('cms.cmsManager')->encodeLocalString($passpage['letterText']));
                $em->flush();
                if (!empty($pageent)) $this->get('cms.cmsManager')->updateBreadCrumbs($pageent->getId());
                if (empty($pageent))
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $em->persist($pageent);
                }
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница восстановления пароля '.$this->get('cms.cmsManager')->decodeLocalString($passpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.user');
                $pageent->setContentId($passpageent->getId());
                $pageent->setContentAction('password');
                $passpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование страницы восстановления пароля',
                    'message'=>'Страница восстановления пароля успешно отредактирована',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_user_passwordpage_edit').'?id='.$id,'Вернуться к списку пользователей'=>$this->get('router')->generate('basic_cms_user_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Default:userPasswordpageEdit.html.twig', array(
            'id' => $id,
            'passpage' => $passpage,
            'passpageerror' => $passpageerror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// Создание страницы регистрации пользователя
// *******************************************    
    public function userRegisterpageCreateAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_tab', 3);
        if ($this->getUser()->checkAccess('user_registerpage') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой страницы регистрации',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $regpage = array();
        $regpageerror = array();
        
        $regpage['title'] = array();
        $regpage['letterText'] = array();
        $regpage['title']['default'] = '';
        $regpage['letterText']['default'] = '';
        foreach ($locales as $locale) 
        {
            $regpage['title'][$locale['shortName']] = '';
            $regpage['letterText'][$locale['shortName']] = '';
        }
        $regpage['enabled'] = 1;
        $regpage['captchaEnabled'] = '';
        $regpage['activationEnabled'] = '';
        $regpage['userType'] = '';
        $regpage['role'] = '';
        $regpage['seopageEnable'] = '';
        $regpage['seopageLayout'] = '';
        $regpage['seopageTemplate'] = '';
        $regpage['seopageUrl'] = '';
        $regpage['seopageModules'] = array();
        $regpage['seopageAccessOn'] = '';
        $regpage['seopageAccess'] = array();
        $regpage['seopageLocale'] = '';
        
        $regpageerror['title'] = array();
        $regpageerror['letterText'] = array();
        $regpageerror['title']['default'] = '';
        $regpageerror['letterText']['default'] = '';
        foreach ($locales as $locale) 
        {
            $regpageerror['title'][$locale['shortName']] = '';
            $regpageerror['letterText'][$locale['shortName']] = '';
        }
        $regpageerror['enabled'] = '';
        $regpageerror['captchaEnabled'] = '';
        $regpageerror['activationEnabled'] = '';
        $regpageerror['userType'] = '';
        $regpageerror['role'] = '';
        $regpageerror['seopageEnable'] = '';
        $regpageerror['seopageLayout'] = '';
        $regpageerror['seopageTemplate'] = '';
        $regpageerror['seopageUrl'] = '';
        $regpageerror['seopageModules'] = '';
        $regpageerror['seopageAccessOn'] = '';
        $regpageerror['seopageAccess'] = '';
        $regpageerror['seopageLocale'] = '';
        
        $page = array();
        $pageerror = array();
        
        $page['url'] = '';
        $page['modules'] = array();
        $page['locale'] = '';
        $page['template'] = '';
        $page['layout'] = '';
        $page['accessOn'] = '';
        $page['access'] = array();
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.user','register');
        $usertemplates = $this->get('cms.cmsManager')->getTemplateList('object.user','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($usertemplates) > 0) {reset($usertemplates); $regpage['seopageTemplate'] = key($usertemplates);}
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts); $regpage['seopageLayout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn, LOCATE(:moduleuserid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleUserDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.user.register')->setParameter('moduleuserid','object.user.view');
        $modules = $query->getResult();
        foreach ($modules as $module) 
        {
            if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
            if ($module['moduleUserDefOn'] != 0) $regpage['seopageModules'][] = $module['id'];
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postregpage = $this->get('request_stack')->getMasterRequest()->get('regpage');
            if (isset($postregpage['title']['default'])) $regpage['title']['default'] = $postregpage['title']['default'];
            if (isset($postregpage['letterText']['default'])) $regpage['letterText']['default'] = $postregpage['letterText']['default'];
            foreach ($locales as $locale) 
            {
                if (isset($postregpage['title'][$locale['shortName']])) $regpage['title'][$locale['shortName']] = $postregpage['title'][$locale['shortName']];
                if (isset($postregpage['letterText'][$locale['shortName']])) $regpage['letterText'][$locale['shortName']] = $postregpage['letterText'][$locale['shortName']];
            }
            if (isset($postregpage['enabled'])) $regpage['enabled'] = intval($postregpage['enabled']); else $regpage['enabled'] = 0;
            if (isset($postregpage['captchaEnabled'])) $regpage['captchaEnabled'] = intval($postregpage['captchaEnabled']); else $regpage['captchaEnabled'] = 0;
            if (isset($postregpage['activationEnabled'])) $regpage['activationEnabled'] = intval($postregpage['activationEnabled']); else $regpage['activationEnabled'] = 0;
            if (isset($postregpage['userType'])) $regpage['userType'] = $postregpage['userType'];
            if (isset($postregpage['role'])) $regpage['role'] = $postregpage['role'];
            
            if (isset($postregpage['seopageEnable'])) $regpage['seopageEnable'] = intval($postregpage['seopageEnable']); else $regpage['seopageEnable'] = 0;
            if (isset($postregpage['seopageUrl'])) $regpage['seopageUrl'] = trim($postregpage['seopageUrl']);
            if (isset($postregpage['seopageModules']) && is_array($postregpage['seopageModules'])) $regpage['seopageModules'] = $postregpage['seopageModules']; else $regpage['seopageModules'] = array();
            if (isset($postregpage['seopageLocale'])) $regpage['seopageLocale'] = $postregpage['seopageLocale'];
            if (isset($postregpage['seopageTemplate'])) $regpage['seopageTemplate'] = $postregpage['seopageTemplate'];
            if (isset($postregpage['seopageLayout'])) $regpage['seopageLayout'] = $postregpage['seopageLayout'];
            if (isset($postregpage['seopageAccessOn'])) $regpage['seopageAccessOn'] = intval($postregpage['seopageAccessOn']);
            $regpage['seopageAccess'] = array();
            foreach ($roles as $onerole) if (isset($postregpage['seopageAccess']) && is_array($postregpage['seopageAccess']) && (in_array($onerole['id'], $postregpage['seopageAccess']))) $regpage['seopageAccess'][] = $onerole['id'];
            unset($postregpage);
            if (!preg_match("/^.{3,}$/ui", $regpage['title']['default'])) {$errors = true; $regpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $regpage['title'][$locale['shortName']])) {$errors = true; $regpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if ($regpage['activationEnabled'] != 0)
            {
                if (!preg_match("/^[\s\S]{10,}$/ui", $regpage['letterText']['default'])) {$errors = true; $regpageerror['letterText']['default'] = 'Текст письма должен содержать более 10 символов';}
                elseif (strpos($regpage['letterText']['default'], '//url//') === false) {$errors = true; $regpageerror['letterText']['default'] = 'Текст письма должен содержать строку //url// для подстановки ссылки';}
                foreach ($locales as $locale) if ($regpage['letterText'][$locale['shortName']] != '')
                {
                    if (!preg_match("/^[\s\S]{10,}$/ui", $regpage['letterText'][$locale['shortName']])) {$errors = true; $regpageerror['letterText'][$locale['shortName']] = 'Текст письма должен содержать более 10 символов или оставьте поле пустым';}
                    elseif (strpos($regpage['letterText'][$locale['shortName']], '//url//') === false) {$errors = true; $regpageerror['letterText'][$locale['shortName']] = 'Текст письма должен содержать строку //url// для подстановки ссылки';}
                }
                
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['url'] != '')
            {
                if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
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
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Проверка 3 таба
            if (($regpage['userType'] != 'ADMIN') && ($regpage['userType'] != 'USER')) {$errors = true; $regpageerror['userType'] = 'Неверный тип пользователя';}
            $query = $em->createQuery('SELECT count(r.id) as rolecount FROM BasicCmsBundle:Roles r WHERE r.id=:roleid')->setParameter('roleid', $regpage['role']);
            $checkrole = $query->getResult();
            if (!isset($checkrole[0]['rolecount']) || ($checkrole[0]['rolecount'] == 0)) {$errors = true; $regpageerror['role'] = 'Роль не найдена';}
            unset($checkrole);
            if ($regpage['seopageEnable'] != 0)
            {
                if ($regpage['seopageUrl'] != '')
                {
                    if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", str_replace(array('//id//','//login//'),'test',$regpage['seopageUrl']))) {$errors = true; $regpageerror['seopageUrl'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                }
                $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $regpage['seopageLocale']) $localecheck = true;}
                if ($localecheck == false) $regpage['seopageLocale'] = '';
                if (($regpage['seopageLayout'] != '') && (!isset($layouts[$regpage['seopageLayout']]))) {$errors = true; $regpageerror['seopageLayout'] = 'Шаблон не найден';}
                if (($regpage['seopageTemplate'] != '') && (!isset($usertemplates[$regpage['seopageTemplate']]))) {$errors = true; $regpageerror['seopageTemplate'] = 'Шаблон не найден';}
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $regpageent = new \Basic\CmsBundle\Entity\UserRegisterPages();
                
                $regpageent->setActivationEnabled($regpage['activationEnabled']);
                $regpageent->setCaptchaEnabled($regpage['captchaEnabled']);
                $regpageent->setEnabled($regpage['enabled']);
                $regpageent->setLetterText($this->get('cms.cmsManager')->encodeLocalString($regpage['letterText']));
                $regpageent->setRole($regpage['role']);
                $regpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($regpage['title']));
                $regpageent->setUserType($regpage['userType']);
                $regpageent->setUserTemplate($regpage['seopageTemplate']);
                $regpageent->setSeopageEnabled($regpage['seopageEnable']);
                if ($regpage['seopageAccessOn'] != 0) $regpageent->setSeopageAccess(implode(',', $regpage['seopageAccess'])); else $regpageent->setSeopageAccess('');
                $regpageent->setSeopageLocale($regpage['seopageLocale']);
                $regpageent->setSeopageModules(implode(',', $regpage['seopageModules']));
                $regpageent->setSeopageTemplate($regpage['seopageLayout']);
                $regpageent->setSeopageUrl($regpage['seopageUrl']);
                $em->persist($regpageent);
                $em->flush();
                
                $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница регистрации '.$this->get('cms.cmsManager')->decodeLocalString($regpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.user');
                $pageent->setContentId($regpageent->getId());
                $pageent->setContentAction('register');
                $regpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->persist($pageent);
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой страницы регистрации',
                    'message'=>'Страница регистрации успешно создана',
                    'paths'=>array('Создать еще одну страницу регистрации'=>$this->get('router')->generate('basic_cms_user_registerpage_create'),'Вернуться к списку пользователей'=>$this->get('router')->generate('basic_cms_user_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Default:userRegisterpageCreate.html.twig', array(
            'regpage' => $regpage,
            'regpageerror' => $regpageerror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'usertemplates' => $usertemplates,
            'modules' => $modules,
            'layouts' => $layouts,
            'activetab' => $activetab
        ));
    }

// *******************************************
// Редактирование страницы регистрации пользователя
// *******************************************    
    public function userRegisterpageEditAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_tab', 3);
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $regpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserRegisterPages')->find($id);
        if (empty($regpageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы регистрации',
                'message'=>'Страница регистрации не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку пользователей'=>$this->get('router')->generate('basic_cms_user_list'))
            ));
        }
        if ($this->getUser()->checkAccess('user_registerpage') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы регистрации',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.user','contentId'=>$id,'contentAction'=>'register'));
        
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $regpage = array();
        $regpageerror = array();
        
        $regpage['title'] = array();
        $regpage['letterText'] = array();
        $regpage['title']['default'] = $this->get('cms.cmsManager')->decodeLocalString($regpageent->getTitle(),'default',false);
        $regpage['letterText']['default'] = $this->get('cms.cmsManager')->decodeLocalString($regpageent->getLetterText(),'default',false);
        foreach ($locales as $locale) 
        {
            $regpage['title'][$locale['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($regpageent->getTitle(),$locale['shortName'],false);
            $regpage['letterText'][$locale['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($regpageent->getLetterText(),$locale['shortName'],false);;
        }
        $regpage['enabled'] = $regpageent->getEnabled();
        $regpage['captchaEnabled'] = $regpageent->getCaptchaEnabled();
        $regpage['activationEnabled'] = $regpageent->getActivationEnabled();
        $regpage['userType'] = $regpageent->getUserType();
        $regpage['role'] = $regpageent->getRole();
        $regpage['seopageEnable'] = $regpageent->getSeopageEnabled();
        $regpage['seopageLayout'] = $regpageent->getSeopageTemplate();
        $regpage['seopageTemplate'] = $regpageent->getUserTemplate();
        $regpage['seopageUrl'] = $regpageent->getSeopageUrl();
        $regpage['seopageModules'] = explode(',',$regpageent->getSeopageModules());
        if (($regpageent->getSeopageAccess() == '') || (!is_array(explode(',', $regpageent->getSeopageAccess()))))
        {
            $regpage['seopageAccessOn'] = 0;
            $regpage['seopageAccess'] = array();
        } else
        {
            $regpage['seopageAccessOn'] = 1;
            $regpage['seopageAccess'] = explode(',', $regpageent->getSeopageAccess());
        }
        $regpage['seopageLocale'] = $regpageent->getSeopageLocale();
        
        $regpageerror['title'] = array();
        $regpageerror['letterText'] = array();
        $regpageerror['title']['default'] = '';
        $regpageerror['letterText']['default'] = '';
        foreach ($locales as $locale) 
        {
            $regpageerror['title'][$locale['shortName']] = '';
            $regpageerror['letterText'][$locale['shortName']] = '';
        }
        $regpageerror['enabled'] = '';
        $regpageerror['captchaEnabled'] = '';
        $regpageerror['activationEnabled'] = '';
        $regpageerror['userType'] = '';
        $regpageerror['role'] = '';
        $regpageerror['seopageEnable'] = '';
        $regpageerror['seopageLayout'] = '';
        $regpageerror['seopageTemplate'] = '';
        $regpageerror['seopageUrl'] = '';
        $regpageerror['seopageModules'] = '';
        $regpageerror['seopageAccessOn'] = '';
        $regpageerror['seopageAccess'] = '';
        $regpageerror['seopageLocale'] = '';
        
        $page = array();
        $pageerror = array();
        
        if (empty($pageent))
        {
            $page['url'] = '';
            $page['modules'] = array();
            $page['locale'] = '';
            $page['template'] = '';
            $page['layout'] = '';
            $page['accessOn'] = '';
            $page['access'] = array();
        } else
        {
            $page['url'] = ($pageent->getUrl() != $pageent->getId() ? $pageent->getUrl() : '');
            $page['modules'] = explode(',',$pageent->getModules());
            $page['locale'] = $pageent->getLocale();
            $page['template'] = $regpageent->getTemplate();
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
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.user','register');
        $usertemplates = $this->get('cms.cmsManager')->getTemplateList('object.user','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($usertemplates) > 0) {reset($usertemplates); $regpage['seopageTemplate'] = key($usertemplates);}
        if (empty($pageent))
        {
            if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
            if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts); $regpage['seopageLayout'] = key($layouts);}
        }
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn, LOCATE(:moduleuserid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleUserDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.user.register')->setParameter('moduleuserid','object.user.view');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        if (($regpage['seopageEnable'] != 0) && (count($regpage['seopageModules']) == 0))
        {
            foreach ($modules as $module) if ($module['moduleUserDefOn'] != 0) $regpage['seopageModules'][] = $module['id'];
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postregpage = $this->get('request_stack')->getMasterRequest()->get('regpage');
            if (isset($postregpage['title']['default'])) $regpage['title']['default'] = $postregpage['title']['default'];
            if (isset($postregpage['letterText']['default'])) $regpage['letterText']['default'] = $postregpage['letterText']['default'];
            foreach ($locales as $locale) 
            {
                if (isset($postregpage['title'][$locale['shortName']])) $regpage['title'][$locale['shortName']] = $postregpage['title'][$locale['shortName']];
                if (isset($postregpage['letterText'][$locale['shortName']])) $regpage['letterText'][$locale['shortName']] = $postregpage['letterText'][$locale['shortName']];
            }
            if (isset($postregpage['enabled'])) $regpage['enabled'] = intval($postregpage['enabled']); else $regpage['enabled'] = 0;
            if (isset($postregpage['captchaEnabled'])) $regpage['captchaEnabled'] = intval($postregpage['captchaEnabled']); else $regpage['captchaEnabled'] = 0;
            if (isset($postregpage['activationEnabled'])) $regpage['activationEnabled'] = intval($postregpage['activationEnabled']); else $regpage['activationEnabled'] = 0;
            if (isset($postregpage['userType'])) $regpage['userType'] = $postregpage['userType'];
            if (isset($postregpage['role'])) $regpage['role'] = $postregpage['role'];
            
            if (isset($postregpage['seopageEnable'])) $regpage['seopageEnable'] = intval($postregpage['seopageEnable']); else $regpage['seopageEnable'] = 0;
            if (isset($postregpage['seopageUrl'])) $regpage['seopageUrl'] = trim($postregpage['seopageUrl']);
            if (isset($postregpage['seopageModules']) && is_array($postregpage['seopageModules'])) $regpage['seopageModules'] = $postregpage['seopageModules']; else $regpage['seopageModules'] = array();
            if (isset($postregpage['seopageLocale'])) $regpage['seopageLocale'] = $postregpage['seopageLocale'];
            if (isset($postregpage['seopageTemplate'])) $regpage['seopageTemplate'] = $postregpage['seopageTemplate'];
            if (isset($postregpage['seopageLayout'])) $regpage['seopageLayout'] = $postregpage['seopageLayout'];
            if (isset($postregpage['seopageAccessOn'])) $regpage['seopageAccessOn'] = intval($postregpage['seopageAccessOn']);
            $regpage['seopageAccess'] = array();
            foreach ($roles as $onerole) if (isset($postregpage['seopageAccess']) && is_array($postregpage['seopageAccess']) && (in_array($onerole['id'], $postregpage['seopageAccess']))) $regpage['seopageAccess'][] = $onerole['id'];
            unset($postregpage);
            if (!preg_match("/^.{3,}$/ui", $regpage['title']['default'])) {$errors = true; $regpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $regpage['title'][$locale['shortName']])) {$errors = true; $regpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if ($regpage['activationEnabled'] != 0)
            {
                if (!preg_match("/^[\s\S]{10,}$/ui", $regpage['letterText']['default'])) {$errors = true; $regpageerror['letterText']['default'] = 'Текст письма должен содержать более 10 символов';}
                elseif (strpos($regpage['letterText']['default'], '//url//') === false) {$errors = true; $regpageerror['letterText']['default'] = 'Текст письма должен содержать строку //url// для подстановки ссылки';}
                foreach ($locales as $locale) if ($regpage['letterText'][$locale['shortName']] != '')
                {
                    if (!preg_match("/^[\s\S]{10,}$/ui", $regpage['letterText'][$locale['shortName']])) {$errors = true; $regpageerror['letterText'][$locale['shortName']] = 'Текст письма должен содержать более 10 символов или оставьте поле пустым';}
                    elseif (strpos($regpage['letterText'][$locale['shortName']], '//url//') === false) {$errors = true; $regpageerror['letterText'][$locale['shortName']] = 'Текст письма должен содержать строку //url// для подстановки ссылки';}
                }
                
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['url'] != '')
            {
                if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
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
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Проверка 3 таба
            if (($regpage['userType'] != 'ADMIN') && ($regpage['userType'] != 'USER')) {$errors = true; $regpageerror['userType'] = 'Неверный тип пользователя';}
            $query = $em->createQuery('SELECT count(r.id) as rolecount FROM BasicCmsBundle:Roles r WHERE r.id=:roleid')->setParameter('roleid', $regpage['role']);
            $checkrole = $query->getResult();
            if (!isset($checkrole[0]['rolecount']) || ($checkrole[0]['rolecount'] == 0)) {$errors = true; $regpageerror['role'] = 'Роль не найдена';}
            unset($checkrole);
            if ($regpage['seopageEnable'] != 0)
            {
                if ($regpage['seopageUrl'] != '')
                {
                    if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", str_replace(array('//id//','//login//'),'test',$regpage['seopageUrl']))) {$errors = true; $regpageerror['seopageUrl'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
                }
                $localecheck = false; foreach ($locales as $item) {if ($item['shortName'] == $regpage['seopageLocale']) $localecheck = true;}
                if ($localecheck == false) $regpage['seopageLocale'] = '';
                if (($regpage['seopageLayout'] != '') && (!isset($layouts[$regpage['seopageLayout']]))) {$errors = true; $regpageerror['seopageLayout'] = 'Шаблон не найден';}
                if (($regpage['seopageTemplate'] != '') && (!isset($usertemplates[$regpage['seopageTemplate']]))) {$errors = true; $regpageerror['seopageTemplate'] = 'Шаблон не найден';}
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $regpageent->setActivationEnabled($regpage['activationEnabled']);
                $regpageent->setCaptchaEnabled($regpage['captchaEnabled']);
                $regpageent->setEnabled($regpage['enabled']);
                $regpageent->setLetterText($this->get('cms.cmsManager')->encodeLocalString($regpage['letterText']));
                $regpageent->setRole($regpage['role']);
                $regpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($regpage['title']));
                $regpageent->setUserType($regpage['userType']);
                $regpageent->setUserTemplate($regpage['seopageTemplate']);
                $regpageent->setSeopageEnabled($regpage['seopageEnable']);
                if ($regpage['seopageAccessOn'] != 0) $regpageent->setSeopageAccess(implode(',', $regpage['seopageAccess'])); else $regpageent->setSeopageAccess('');
                $regpageent->setSeopageLocale($regpage['seopageLocale']);
                $regpageent->setSeopageModules(implode(',', $regpage['seopageModules']));
                $regpageent->setSeopageTemplate($regpage['seopageLayout']);
                $regpageent->setSeopageUrl($regpage['seopageUrl']);
                $em->flush();
                
                if (empty($pageent))
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $em->persist($pageent);
                }
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница регистрации '.$this->get('cms.cmsManager')->decodeLocalString($regpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.user');
                $pageent->setContentId($regpageent->getId());
                $pageent->setContentAction('register');
                $regpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование страницы регистрации',
                    'message'=>'Страница регистрации успешно отредактирована',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_user_registerpage_edit').'?id='.$id,'Вернуться к списку пользователей'=>$this->get('router')->generate('basic_cms_user_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Default:userRegisterpageEdit.html.twig', array(
            'id' => $id,
            'regpage' => $regpage,
            'regpageerror' => $regpageerror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'usertemplates' => $usertemplates,
            'modules' => $modules,
            'layouts' => $layouts,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// Создание страницы изменения профиля
// *******************************************    
    public function userProfilepageCreateAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_tab', 4);
        if ($this->getUser()->checkAccess('user_profilepage') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой страницы изменения профиля',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $profpage = array();
        $profpageerror = array();
        
        $profpage['title'] = array();
        $profpage['title']['default'] = '';
        foreach ($locales as $locale) $profpage['title'][$locale['shortName']] = '';
        $profpage['enabled'] = 1;
        $profpage['changeEmail'] = '';
        $profpage['changePassword'] = '';

        $profpageerror['title'] = array();
        $profpageerror['title']['default'] = '';
        foreach ($locales as $locale) $profpageerror['title'][$locale['shortName']] = '';
        $profpageerror['enabled'] = '';
        $profpageerror['changeEmail'] = '';
        $profpageerror['changePassword'] = '';
        
        $page = array();
        $pageerror = array();
        
        $page['url'] = '';
        $page['modules'] = array();
        $page['locale'] = '';
        $page['template'] = '';
        $page['layout'] = '';
        $page['accessOn'] = '';
        $page['access'] = array();
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.user','profile');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.user.profile');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postprofpage = $this->get('request_stack')->getMasterRequest()->get('profpage');
            if (isset($postprofpage['title']['default'])) $profpage['title']['default'] = $postprofpage['title']['default'];
            foreach ($locales as $locale) if (isset($postprofpage['title'][$locale['shortName']])) $profpage['title'][$locale['shortName']] = $postprofpage['title'][$locale['shortName']];
            if (isset($postprofpage['enabled'])) $profpage['enabled'] = intval($postprofpage['enabled']); else $profpage['enabled'] = 0;
            if (isset($postprofpage['changeEmail'])) $profpage['changeEmail'] = intval($postprofpage['changeEmail']); else $profpage['changeEmail'] = 0;
            if (isset($postprofpage['changePassword'])) $profpage['changePassword'] = intval($postprofpage['changePassword']); else $profpage['changePassword'] = 0;
            unset($postpasspage);
            if (!preg_match("/^.{3,}$/ui", $profpage['title']['default'])) {$errors = true; $profpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $profpage['title'][$locale['shortName']])) {$errors = true; $profpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['url'] != '')
            {
                if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
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
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                
                $profpageent = new \Basic\CmsBundle\Entity\UserProfilePages();
                $profpageent->setEnabled($profpage['enabled']);
                $profpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($profpage['title']));
                $profpageent->setChangeEmail($profpage['changeEmail']);
                $profpageent->setChangePassword($profpage['changePassword']);
                $em->persist($profpageent);
                $em->flush();
                
                $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница изменения профиля '.$this->get('cms.cmsManager')->decodeLocalString($profpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.user');
                $pageent->setContentId($profpageent->getId());
                $pageent->setContentAction('profile');
                $profpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->persist($pageent);
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой страницы изменения профиля',
                    'message'=>'Страница изменения профиля успешно создана',
                    'paths'=>array('Создать еще одну страницу изменения профиля'=>$this->get('router')->generate('basic_cms_user_profilepage_create'),'Вернуться к списку пользователей'=>$this->get('router')->generate('basic_cms_user_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Default:userProfilepageCreate.html.twig', array(
            'profpage' => $profpage,
            'profpageerror' => $profpageerror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// Редактирование страницы изменения профиля
// *******************************************    
    public function userProfilepageEditAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_user_list_tab', 4);
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $profpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:UserProfilePages')->find($id);
        if (empty($profpageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы изменения профиля',
                'message'=>'Страница изменения профиля не найдена не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку пользователей'=>$this->get('router')->generate('basic_cms_user_list'))
            ));
        }
        if ($this->getUser()->checkAccess('user_profilepage') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы изменения профиля',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.user','contentId'=>$id,'contentAction'=>'profile'));
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $profpage = array();
        $profpageerror = array();
        
        $profpage['title'] = array();
        $profpage['title']['default'] = $this->get('cms.cmsManager')->decodeLocalString($profpageent->getTitle(),'default',false);;
        foreach ($locales as $locale) $profpage['title'][$locale['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($profpageent->getTitle(),$locale['shortName'],false);;
        $profpage['enabled'] = $profpageent->getEnabled();
        $profpage['changeEmail'] = $profpageent->getChangeEmail();
        $profpage['changePassword'] = $profpageent->getChangePassword();

        $profpageerror['title'] = array();
        $profpageerror['title']['default'] = '';
        foreach ($locales as $locale) $profpageerror['title'][$locale['shortName']] = '';
        $profpageerror['enabled'] = '';
        $profpageerror['changeEmail'] = '';
        $profpageerror['changePassword'] = '';
        
        $page = array();
        $pageerror = array();
        
        if (empty($pageent))
        {
            $page['url'] = '';
            $page['modules'] = array();
            $page['locale'] = '';
            $page['template'] = '';
            $page['layout'] = '';
            $page['accessOn'] = '';
            $page['access'] = array();
        } else
        {
            $page['url'] = ($pageent->getUrl() != $pageent->getId() ? $pageent->getUrl() : '');
            $page['modules'] = explode(',',$pageent->getModules());
            $page['locale'] = $pageent->getLocale();
            $page['template'] = $profpageent->getTemplate();
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
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.user','profile');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (empty($pageent))
        {
            if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
            if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        }
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.user.profile');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postprofpage = $this->get('request_stack')->getMasterRequest()->get('profpage');
            if (isset($postprofpage['title']['default'])) $profpage['title']['default'] = $postprofpage['title']['default'];
            foreach ($locales as $locale) if (isset($postprofpage['title'][$locale['shortName']])) $profpage['title'][$locale['shortName']] = $postprofpage['title'][$locale['shortName']];
            if (isset($postprofpage['enabled'])) $profpage['enabled'] = intval($postprofpage['enabled']); else $profpage['enabled'] = 0;
            if (isset($postprofpage['changeEmail'])) $profpage['changeEmail'] = intval($postprofpage['changeEmail']); else $profpage['changeEmail'] = 0;
            if (isset($postprofpage['changePassword'])) $profpage['changePassword'] = intval($postprofpage['changePassword']); else $profpage['changePassword'] = 0;
            unset($postpasspage);
            if (!preg_match("/^.{3,}$/ui", $profpage['title']['default'])) {$errors = true; $profpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $profpage['title'][$locale['shortName']])) {$errors = true; $profpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['url'] != '')
            {
                if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
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
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $profpageent->setEnabled($profpage['enabled']);
                $profpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($profpage['title']));
                $profpageent->setChangeEmail($profpage['changeEmail']);
                $profpageent->setChangePassword($profpage['changePassword']);
                $em->flush();
                if (!empty($pageent)) $this->get('cms.cmsManager')->updateBreadCrumbs($pageent->getId());
                if (empty($pageent))
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $em->persist($pageent);
                }
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница изменения профиля '.$this->get('cms.cmsManager')->decodeLocalString($profpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.user');
                $pageent->setContentId($profpageent->getId());
                $pageent->setContentAction('profile');
                $profpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование страницы изменения профиля',
                    'message'=>'Страница изменения профиля успешно отредактирована',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_user_profilepage_edit').'?id='.$id,'Вернуться к списку пользователей'=>$this->get('router')->generate('basic_cms_user_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Default:userProfilepageEdit.html.twig', array(
            'id' => $id,
            'profpage' => $profpage,
            'profpageerror' => $profpageerror,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// AJAX загрузка аватара через фронт
// *******************************************    

    public function userFrontAjaxAvatarAction() 
    {
/*        $file = $this->get('request_stack')->getMasterRequest()->files->get('avatar');
        $tmpfile = $file->getPathName();
        $source = "";
        if (@getimagesize($tmpfile)) 
        {
            $params = getimagesize($tmpfile);
            if ($params[0] < 10) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла меньше 10x10')));
            if ($params[1] < 10) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла меньше 10x10')));
            if ($params[0] > 6000) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла больше 6000x6000')));
            if ($params[1] > 6000) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла больше 6000x6000')));
            $basepath = 'images/user/';
            $name = 'f_'.md5($tmpfile.time()).'.jpg';
            if (move_uploaded_file($tmpfile, $basepath . $name)) return new Response(json_encode(array('file' => '/images/user/'.$name, 'error' => '')));
        } else return new Response(json_encode(array('file' => '', 'error' => 'Неправильный формат файла')));
*/        
        $file = $this->get('request_stack')->getMasterRequest()->files->get('avatar');
        $tmpfile = $file->getPathName();
        if (@getimagesize($tmpfile)) 
        {
            $params = getimagesize($tmpfile);
            if ($params[0] < 10) return new Response(json_encode(array('file' => '', 'error' => 'size')));
            if ($params[1] < 10) return new Response(json_encode(array('file' => '', 'error' => 'size')));
            if ($params[0] > 6000) return new Response(json_encode(array('file' => '', 'error' => 'size')));
            if ($params[1] > 6000) return new Response(json_encode(array('file' => '', 'error' => 'size')));
            $imageTypeArray = array(0=>'', 1=>'gif', 2=>'jpg', 3=>'png', 4=>'', 5=>'', 6=>'', 7=>'', 8=>'', 9=>'jpg', 10=>'jpg', 11=>'jpg', 12=>'jpg', 13=>'', 14=>'', 15=>'', 16=>'', 17=>'', 18=>'');
            if (!isset($imageTypeArray[$params[2]]) || ($imageTypeArray[$params[2]] == ''))  return new Response(json_encode(array('file' => '', 'error' => 'format')));
            $basepath = '/images/user/';
            $name = 'f_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
            if (move_uploaded_file($tmpfile, '.'.$basepath.$name)) 
            {
                $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $file->getClientOriginalName());
                return new Response(json_encode(array('file' => $basepath.$name, 'error' => '')));
            }
        }
        return new Response(json_encode(array('file' => '', 'error' => 'format')));
    }

// *******************************************
// Статистика
// *******************************************    
    
    public function statisticsAction()
    {
        $id = $this->get('request_stack')->getMasterRequest()->get('id');
        if ($id === null)
        {
            $em = $this->getDoctrine()->getEntityManager();
            $tab = 0;
            // Таб 1
            $page0 = $this->get('request_stack')->getMasterRequest()->get('page0');
            $sort0 = $this->get('request_stack')->getMasterRequest()->get('sort0');
            $search0 = $this->get('request_stack')->getMasterRequest()->get('search0');
            if ($page0 === null) $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_statistics_page0');
                            else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_statistics_page0', $page0);
            if ($page0 === null) $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_statistics_page0');
                            else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_statistics_page0', $page0);
            if ($sort0 === null) $sort0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_statistics_sort0');
                            else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_statistics_sort0', $sort0);
            if ($search0 === null) $search0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_statistics_search0');
                              else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_statistics_search0', $search0);
            $page0 = intval($page0);
            $sort0 = intval($sort0);
            $search0 = trim($search0);
            // Поиск контента для 1 таба (пользователи)
            $query = $em->createQuery('SELECT count(p.id) as spagecount FROM BasicCmsBundle:SeoPage p '.
                                      'WHERE p.url like :search OR p.description like :search ')->setParameter('search', '%'.$search0.'%');
            $spagecount = $query->getResult();
            if (!empty($spagecount)) $spagecount = $spagecount[0]['spagecount']; else $spagecount = 0;
            $pagecount0 = ceil($spagecount / 20);
            if ($pagecount0 < 1) $pagecount0 = 1;
            if ($page0 < 0) $page0 = 0;
            if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
            $sortsql = 'ORDER BY p.url ASC';
            if ($sort0 == 1) $sortsql = 'ORDER BY p.url DESC';
            if ($sort0 == 2) $sortsql = 'ORDER BY p.description ASC';
            if ($sort0 == 3) $sortsql = 'ORDER BY p.description DESC';
            if ($sort0 == 4) $sortsql = 'ORDER BY s.visitDate ASC';
            if ($sort0 == 5) $sortsql = 'ORDER BY s.visitDate DESC';
            if ($sort0 == 6) $sortsql = 'ORDER BY s.allVisits ASC';
            if ($sort0 == 7) $sortsql = 'ORDER BY s.allVisits DESC';
            if ($sort0 == 8) $sortsql = 'ORDER BY weekVisits ASC';
            if ($sort0 == 9) $sortsql = 'ORDER BY weekVisits DESC';
            $start = $page0 * 20;
            $query = $em->createQuery('SELECT p.id, p.url, p.description, s.visitDate, s.allVisits, '.
                                      '(IF(DATEDIFF(:now, s.visitDate) <= 0,s.day7,0)+IF(DATEDIFF(:now, s.visitDate) <= 1,s.day6,0)+IF(DATEDIFF(:now, s.visitDate) <= 2,s.day5,0)+'.
                                      'IF(DATEDIFF(:now, s.visitDate) <= 3,s.day4,0)+IF(DATEDIFF(:now, s.visitDate) <= 4,s.day3,0)+IF(DATEDIFF(:now, s.visitDate) <= 5,s.day2,0)+'.
                                      'IF(DATEDIFF(:now, s.visitDate) <= 6,s.day1,0)) as weekVisits '.
                                      'FROM BasicCmsBundle:SeoPage p '.
                                      'LEFT JOIN BasicCmsBundle:SeoStatistic s WITH s.pageId = p.id '.
                                      'WHERE (p.url like :search OR p.description like :search) '.$sortsql)->setParameter('search', '%'.$search0.'%')->setParameter('now', new \DateTime('now'))->setFirstResult($start)->setMaxResults(20);
            $seopages = $query->getResult();
            // Общие графики
            $statistic = array();
            $statistic['daygraf'][0] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['daygraf'][1] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['daygraf'][2] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['daygraf'][3] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['daygraf'][4] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['daygraf'][5] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['daygraf'][6] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['daygraf'][7] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['daygraf'][8] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['daygraf'][9] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['daygraf'][10] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['daygraf'][11] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['daygraf']['max'] = 1;
            $statistic['daygraf']['maxUniq'] = 1;
            $statistic['allVisits'] = 0;
            $statistic['allVisitsUniq'] = 0;
            $startDate = new \DateTime('now');
            $stopDate = new \DateTime('now');
            for ($i = 0; $i < 12; $i++)
            {
                $startDate->setTimestamp(time() - $i * 86400);
                $startDate->setTime(0, 0, 0);
                $stopDate->setTimestamp(time() - $i * 86400);
                $stopDate->setTime(23, 59, 59);
                $statistic['daygraf'][$i]['name'] = $startDate->format('d.m.Y');
                /*if ($statistic['daygraf'][$i]['name'] == 1) $statistic['daygraf'][$i]['name'] = 'Понедельник'; else
                if ($statistic['daygraf'][$i]['name'] == 2) $statistic['daygraf'][$i]['name'] = 'Вторник'; else
                if ($statistic['daygraf'][$i]['name'] == 3) $statistic['daygraf'][$i]['name'] = 'Среда'; else
                if ($statistic['daygraf'][$i]['name'] == 4) $statistic['daygraf'][$i]['name'] = 'Четверг'; else
                if ($statistic['daygraf'][$i]['name'] == 5) $statistic['daygraf'][$i]['name'] = 'Пятница'; else
                if ($statistic['daygraf'][$i]['name'] == 6) $statistic['daygraf'][$i]['name'] = 'Суббота'; else
                if ($statistic['daygraf'][$i]['name'] == 7) $statistic['daygraf'][$i]['name'] = 'Воскресенье';*/
                $query = $em->createQuery('SELECT SUM(s.day1) as day1, SUM(s.day2) as day2, SUM(s.day3) as day3, SUM(s.day4) as day4, SUM(s.day5) as day5, SUM(s.day6) as day6, '.
                                          'SUM(s.day7) as day7, SUM(s.day8) as day8, SUM(s.day9) as day9, SUM(s.day10) as day10, SUM(s.day11) as day11, SUM(s.day12) as day12 '.
                                          'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId != 0 AND s.visitDate >= :start AND s.visitDate <= :stop')->setParameter('start', $startDate)->setParameter('stop', $stopDate);
                $stat = $query->getResult();
                if (is_array($stat))
                {
                    for ($j = 0; $j < 12; $j++)
                    {
                        if (isset($statistic['daygraf'][$i + $j]) && isset($stat[0]['day'.($j + 1)])) $statistic['daygraf'][$i + $j]['count'] += $stat[0]['day'.($j + 1)];
                    }
                }
                $query = $em->createQuery('SELECT SUM(s.day1) as day1, SUM(s.day2) as day2, SUM(s.day3) as day3, SUM(s.day4) as day4, SUM(s.day5) as day5, SUM(s.day6) as day6, '.
                                          'SUM(s.day7) as day7, SUM(s.day8) as day8, SUM(s.day9) as day9, SUM(s.day10) as day10, SUM(s.day11) as day11, SUM(s.day12) as day12 '.
                                          'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId = 0 AND s.visitDate >= :start AND s.visitDate <= :stop')->setParameter('start', $startDate)->setParameter('stop', $stopDate);
                $stat = $query->getResult();
                if (is_array($stat))
                {
                    for ($j = 0; $j < 12; $j++)
                    {
                        if (isset($statistic['daygraf'][$i + $j]) && isset($stat[0]['day'.($j + 1)])) $statistic['daygraf'][$i + $j]['countUniq'] += $stat[0]['day'.($j + 1)];
                    }
                }
            }
            for ($i = 0; $i < 12; $i++)
            {
                if ($statistic['daygraf']['max'] < $statistic['daygraf'][$i]['count']) $statistic['daygraf']['max'] = $statistic['daygraf'][$i]['count'];
                if ($i < 11)
                {
                    if ($statistic['daygraf'][$i]['count'] > $statistic['daygraf'][$i + 1]['count']) $statistic['daygraf'][$i]['tend'] = +1;
                    if ($statistic['daygraf'][$i]['count'] < $statistic['daygraf'][$i + 1]['count']) $statistic['daygraf'][$i]['tend'] = -1;
                }
                if ($statistic['daygraf']['maxUniq'] < $statistic['daygraf'][$i]['countUniq']) $statistic['daygraf']['maxUniq'] = $statistic['daygraf'][$i]['countUniq'];
                if ($i < 11)
                {
                    if ($statistic['daygraf'][$i]['countUniq'] > $statistic['daygraf'][$i + 1]['countUniq']) $statistic['daygraf'][$i]['tendUniq'] = +1;
                    if ($statistic['daygraf'][$i]['countUniq'] < $statistic['daygraf'][$i + 1]['countUniq']) $statistic['daygraf'][$i]['tendUniq'] = -1;
                }
            }
            // Данные по неделям
            $statistic['weekgraf'][0] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['weekgraf'][1] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['weekgraf'][2] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['weekgraf'][3] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['weekgraf'][4] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['weekgraf'][5] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['weekgraf'][6] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['weekgraf'][7] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['weekgraf'][8] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['weekgraf'][9] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['weekgraf'][10] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['weekgraf'][11] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['weekgraf']['max'] = 1;
            $statistic['weekgraf']['maxUniq'] = 1;
            
            $startDate->setTimestamp(time());
            $startDate->setTime(0, 0, 0);
            $startDate->setTimestamp($startDate->getTimestamp() - ($startDate->format('N') - 1) * 86400);
            
            for ($i = 0; $i < 12; $i++)
            {
                $stopDate->setTimestamp($startDate->getTimestamp() + 7 * 86400 - 1);
                $statistic['weekgraf'][$i]['name'] = $startDate->format('d.m.Y').' - '.$stopDate->format('d.m.Y');
                $stopDate->setTimestamp($startDate->getTimestamp() + 7 * 86400);
                $query = $em->createQuery('SELECT SUM(s.week1) as week1, SUM(s.week2) as week2, SUM(s.week3) as week3, SUM(s.week4) as week4, SUM(s.week5) as week5, SUM(s.week6) as week6, '.
                                          'SUM(s.week7) as week7, SUM(s.week8) as week8, SUM(s.week9) as week9, SUM(s.week10) as week10, SUM(s.week11) as week11, SUM(s.week12) as week12 '.
                                          'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId != 0 AND s.visitDate >= :start AND s.visitDate < :stop')->setParameter('start', $startDate)->setParameter('stop', $stopDate);
                $stat = $query->getResult();
                if (is_array($stat))
                {
                    for ($j = 0; $j < 12; $j++)
                    {
                        if (isset($statistic['weekgraf'][$i + $j]) && isset($stat[0]['week'.($j + 1)])) $statistic['weekgraf'][$i + $j]['count'] += $stat[0]['week'.($j + 1)];
                    }
                }
                $query = $em->createQuery('SELECT SUM(s.week1) as week1, SUM(s.week2) as week2, SUM(s.week3) as week3, SUM(s.week4) as week4, SUM(s.week5) as week5, SUM(s.week6) as week6, '.
                                          'SUM(s.week7) as week7, SUM(s.week8) as week8, SUM(s.week9) as week9, SUM(s.week10) as week10, SUM(s.week11) as week11, SUM(s.week12) as week12 '.
                                          'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId = 0 AND s.visitDate >= :start AND s.visitDate < :stop')->setParameter('start', $startDate)->setParameter('stop', $stopDate);
                $stat = $query->getResult();
                if (is_array($stat))
                {
                    for ($j = 0; $j < 12; $j++)
                    {
                        if (isset($statistic['weekgraf'][$i + $j]) && isset($stat[0]['week'.($j + 1)])) $statistic['weekgraf'][$i + $j]['countUniq'] += $stat[0]['week'.($j + 1)];
                    }
                }
                $startDate->setTimestamp($startDate->getTimestamp() - 7 * 86400);
            }
            for ($i = 0; $i < 12; $i++)
            {
                if ($statistic['weekgraf']['max'] < $statistic['weekgraf'][$i]['count']) $statistic['weekgraf']['max'] = $statistic['weekgraf'][$i]['count'];
                if ($i < 11)
                {
                    if ($statistic['weekgraf'][$i]['count'] > $statistic['weekgraf'][$i + 1]['count']) $statistic['weekgraf'][$i]['tend'] = +1;
                    if ($statistic['weekgraf'][$i]['count'] < $statistic['weekgraf'][$i + 1]['count']) $statistic['weekgraf'][$i]['tend'] = -1;
                }
                if ($statistic['weekgraf']['maxUniq'] < $statistic['weekgraf'][$i]['countUniq']) $statistic['weekgraf']['maxUniq'] = $statistic['weekgraf'][$i]['countUniq'];
                if ($i < 11)
                {
                    if ($statistic['weekgraf'][$i]['countUniq'] > $statistic['weekgraf'][$i + 1]['countUniq']) $statistic['weekgraf'][$i]['tendUniq'] = +1;
                    if ($statistic['weekgraf'][$i]['countUniq'] < $statistic['weekgraf'][$i + 1]['countUniq']) $statistic['weekgraf'][$i]['tendUniq'] = -1;
                }
            }
            
            // Данные по месяцам
            $statistic['monthgraf'][0] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['monthgraf'][1] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['monthgraf'][2] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['monthgraf'][3] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['monthgraf'][4] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['monthgraf'][5] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['monthgraf'][6] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['monthgraf'][7] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['monthgraf'][8] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['monthgraf'][9] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['monthgraf'][10] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['monthgraf'][11] = array('name' => '', 'count' => 0, 'tend' => 0, 'countUniq' => 0, 'tendUniq' => 0);
            $statistic['monthgraf']['max'] = 1;
            $statistic['monthgraf']['maxUniq'] = 1;
            $startDate->setTimestamp(time());
            $startDate->setTime(0, 0, 0);
            $startDate->setDate($startDate->format('Y'), $startDate->format('m'), 1);
            $stopDate->setTimestamp(time()+ 3600);
            for ($i = 0; $i < 12; $i++)
            {
                $statistic['monthgraf'][$i]['name'] = $startDate->format('1.m.Y - t.m.Y');
                $query = $em->createQuery('SELECT SUM(s.month1) as month1, SUM(s.month2) as month2, SUM(s.month3) as month3, SUM(s.month4) as month4, SUM(s.month5) as month5, SUM(s.month6) as month6, '.
                                          'SUM(s.month7) as month7, SUM(s.month8) as month8, SUM(s.month9) as month9, SUM(s.month10) as month10, SUM(s.month11) as month11, SUM(s.month12) as month12 '.
                                          'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId != 0 AND s.visitDate >= :start AND s.visitDate < :stop')->setParameter('start', $startDate)->setParameter('stop', $stopDate);
                $stat = $query->getResult();
                if (is_array($stat))
                {
                    for ($j = 0; $j < 12; $j++)
                    {
                        if (isset($statistic['monthgraf'][$i + $j]) && isset($stat[0]['month'.($j + 1)])) $statistic['monthgraf'][$i + $j]['count'] += $stat[0]['month'.($j + 1)];
                    }
                }
                $query = $em->createQuery('SELECT SUM(s.month1) as month1, SUM(s.month2) as month2, SUM(s.month3) as month3, SUM(s.month4) as month4, SUM(s.month5) as month5, SUM(s.month6) as month6, '.
                                          'SUM(s.month7) as month7, SUM(s.month8) as month8, SUM(s.month9) as month9, SUM(s.month10) as month10, SUM(s.month11) as month11, SUM(s.month12) as month12 '.
                                          'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId = 0 AND s.visitDate >= :start AND s.visitDate < :stop')->setParameter('start', $startDate)->setParameter('stop', $stopDate);
                $stat = $query->getResult();
                if (is_array($stat))
                {
                    for ($j = 0; $j < 12; $j++)
                    {
                        if (isset($statistic['monthgraf'][$i + $j]) && isset($stat[0]['month'.($j + 1)])) $statistic['monthgraf'][$i + $j]['countUniq'] += $stat[0]['month'.($j + 1)];
                    }
                }
                $stopDate->setTimestamp($startDate->getTimestamp());
                $startDate->setTime(0, 0, 0);
                $startDate->setDate($stopDate->format('Y') - ($stopDate->format('m') == 1 ? 1 : 0), ($stopDate->format('m') == 1 ? 12 : $stopDate->format('m') - 1), 1);
            }
            
            for ($i = 0; $i < 12; $i++)
            {
                if ($statistic['monthgraf']['max'] < $statistic['monthgraf'][$i]['count']) $statistic['monthgraf']['max'] = $statistic['monthgraf'][$i]['count'];
                if ($i < 11)
                {
                    if ($statistic['monthgraf'][$i]['count'] > $statistic['monthgraf'][$i + 1]['count']) $statistic['monthgraf'][$i]['tend'] = +1;
                    if ($statistic['monthgraf'][$i]['count'] < $statistic['monthgraf'][$i + 1]['count']) $statistic['monthgraf'][$i]['tend'] = -1;
                }
                if ($statistic['monthgraf']['maxUniq'] < $statistic['monthgraf'][$i]['countUniq']) $statistic['monthgraf']['maxUniq'] = $statistic['monthgraf'][$i]['countUniq'];
                if ($i < 11)
                {
                    if ($statistic['monthgraf'][$i]['countUniq'] > $statistic['monthgraf'][$i + 1]['countUniq']) $statistic['monthgraf'][$i]['tendUniq'] = +1;
                    if ($statistic['monthgraf'][$i]['countUniq'] < $statistic['monthgraf'][$i + 1]['countUniq']) $statistic['monthgraf'][$i]['tendUniq'] = -1;
                }
            }
            // Общие данные
            $query = $em->createQuery('SELECT SUM(s.allVisits) as allVisits '.
                                      'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId != 0');
            $stat = $query->getResult();
            if (is_array($stat))
            {
                $statistic['allVisits'] += $stat[0]['allVisits'];
            }
            $query = $em->createQuery('SELECT SUM(s.allVisits) as allVisits '.
                                      'FROM BasicCmsBundle:SeoStatistic s WHERE s.pageId = 0');
            $stat = $query->getResult();
            if (is_array($stat))
            {
                $statistic['allVisitsUniq'] += $stat[0]['allVisits'];
            }
            return $this->render('BasicCmsBundle:Default:statisticList.html.twig', array(
                'seopages0' => $seopages,
                'search0' => $search0,
                'page0' => $page0,
                'sort0' => $sort0,
                'pagecount0' => $pagecount0,
                'statistic' => $statistic,
                'tab' => $tab
            ));
        } else
        {
            $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->find($id);
            if (empty($pageent))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Статистика',
                    'message'=>'Страница не найдена', 
                    'error'=>true,
                    'paths'=>array('Вернуться к статистике'=>$this->get('router')->generate('basic_cms_statistics'))
                ));
            }
            $em = $this->getDoctrine()->getEntityManager();
            $statinfo = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoStatistic')->findOneBy(array('pageId'=>$pageent->getId()));
            $info = array();
            $info['url'] = $pageent->getUrl();
            $info['description'] = $pageent->getDescription();
            // Данные по дням
            $statistic = array();
            $statistic['daygraf'][0] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['daygraf'][1] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['daygraf'][2] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['daygraf'][3] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['daygraf'][4] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['daygraf'][5] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['daygraf'][6] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['daygraf'][7] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['daygraf'][8] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['daygraf'][9] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['daygraf'][10] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['daygraf'][11] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['daygraf']['max'] = 1;
            $statistic['allVisits'] = 0;
            $statistic['visitDate'] = null;
            if (!empty($statinfo))
            {
                $statistic['visitDate'] = $statinfo->getVisitDate();
                $statistic['allVisits'] = $statinfo->getAllVisits();
                $startDate = new \DateTime('now');
                $startDate->setTimestamp($statinfo->getVisitDate()->getTimestamp());
                $startDate->setTime(0, 0, 0);
                for ($i = 0; $i < 12; $i++)
                {
                    $statistic['daygraf'][$i]['name'] = $startDate->format('d.m.Y');
                    $getmethod = 'getDay'.($i + 1);
                    if (method_exists($statinfo, $getmethod)) $statistic['daygraf'][$i]['count'] += $statinfo->$getmethod();
                    $startDate->setTimestamp($startDate->getTimestamp() - 86400);
                }
                for ($i = 0; $i < 12; $i++)
                {
                    if ($statistic['daygraf']['max'] < $statistic['daygraf'][$i]['count']) $statistic['daygraf']['max'] = $statistic['daygraf'][$i]['count'];
                    if ($i < 11)
                    {
                        if ($statistic['daygraf'][$i]['count'] > $statistic['daygraf'][$i + 1]['count']) $statistic['daygraf'][$i]['tend'] = +1;
                        if ($statistic['daygraf'][$i]['count'] < $statistic['daygraf'][$i + 1]['count']) $statistic['daygraf'][$i]['tend'] = -1;
                    }
                }
            }
            // Данные по неделям
            $statistic['weekgraf'][0] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['weekgraf'][1] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['weekgraf'][2] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['weekgraf'][3] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['weekgraf'][4] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['weekgraf'][5] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['weekgraf'][6] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['weekgraf'][7] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['weekgraf'][8] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['weekgraf'][9] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['weekgraf'][10] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['weekgraf'][11] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['weekgraf']['max'] = 1;
            if (!empty($statinfo))
            {
                $startDate->setTimestamp($statinfo->getVisitDate()->getTimestamp());
                $startDate->setTime(0, 0, 0);
                $startDate->setTimestamp($startDate->getTimestamp() - ($startDate->format('N') - 1) * 86400);
                $stopDate = new \DateTime('now');
                for ($i = 0; $i < 12; $i++)
                {
                    $stopDate->setTimestamp($startDate->getTimestamp() + 7 * 86400 - 1);
                    $statistic['weekgraf'][$i]['name'] = $startDate->format('d.m.Y').' - '.$stopDate->format('d.m.Y');
                    $getmethod = 'getWeek'.($i + 1);
                    if (method_exists($statinfo, $getmethod)) $statistic['weekgraf'][$i]['count'] += $statinfo->$getmethod();
                    $startDate->setTimestamp($startDate->getTimestamp() - 7 * 86400);
                }
                for ($i = 0; $i < 12; $i++)
                {
                    if ($statistic['weekgraf']['max'] < $statistic['weekgraf'][$i]['count']) $statistic['weekgraf']['max'] = $statistic['weekgraf'][$i]['count'];
                    if ($i < 11)
                    {
                        if ($statistic['weekgraf'][$i]['count'] > $statistic['weekgraf'][$i + 1]['count']) $statistic['weekgraf'][$i]['tend'] = +1;
                        if ($statistic['weekgraf'][$i]['count'] < $statistic['weekgraf'][$i + 1]['count']) $statistic['weekgraf'][$i]['tend'] = -1;
                    }
                }
            }
            // Данные по месяцам
            $statistic['monthgraf'][0] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['monthgraf'][1] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['monthgraf'][2] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['monthgraf'][3] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['monthgraf'][4] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['monthgraf'][5] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['monthgraf'][6] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['monthgraf'][7] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['monthgraf'][8] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['monthgraf'][9] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['monthgraf'][10] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['monthgraf'][11] = array('name' => '', 'count' => 0, 'tend' => 0);
            $statistic['monthgraf']['max'] = 1;
            if (!empty($statinfo))
            {
                $startDate->setTimestamp($statinfo->getVisitDate()->getTimestamp());
                $startDate->setTime(0, 0, 0);
                $startDate->setDate($startDate->format('Y'), $startDate->format('m'), 1);
                for ($i = 0; $i < 12; $i++)
                {
                    $statistic['monthgraf'][$i]['name'] = $startDate->format('1.m.Y - t.m.Y');
                    $getmethod = 'getMonth'.($i + 1);
                    if (method_exists($statinfo, $getmethod)) $statistic['monthgraf'][$i]['count'] += $statinfo->$getmethod();
                    $startDate->setTime(0, 0, 0);
                    $startDate->setDate($startDate->format('Y') - ($startDate->format('m') == 1 ? 1 : 0), ($startDate->format('m') == 1 ? 12 : $startDate->format('m') - 1), 1);
                }

                for ($i = 0; $i < 12; $i++)
                {
                    if ($statistic['monthgraf']['max'] < $statistic['monthgraf'][$i]['count']) $statistic['monthgraf']['max'] = $statistic['monthgraf'][$i]['count'];
                    if ($i < 11)
                    {
                        if ($statistic['monthgraf'][$i]['count'] > $statistic['monthgraf'][$i + 1]['count']) $statistic['monthgraf'][$i]['tend'] = +1;
                        if ($statistic['monthgraf'][$i]['count'] < $statistic['monthgraf'][$i + 1]['count']) $statistic['monthgraf'][$i]['tend'] = -1;
                    }
                }
            }
            return $this->render('BasicCmsBundle:Default:statistic.html.twig', array(
                'statistic' => $statistic,
                'info' => $info
            ));
        }
    }

// *******************************************
// Список прочих страниц сайта
// *******************************************    
    public function siteMapPagesListAction()
    {
        if ($this->getUser()->checkAccess('site_pages') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Прочие страницы',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $tab = $this->get('request_stack')->getMasterRequest()->get('tab');
        if ($tab === null) $tab = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_sitemap_pageslist_tab');
                      else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_sitemap_pageslist_tab', $tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 0) $tab = 0;
        // Таб 1
        $query = $em->createQuery('SELECT s.id, s.title, s.enabled, sp.url FROM BasicCmsBundle:SearchPages s LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.user\' AND sp.contentAction = \'searchpage\' AND sp.contentId = s.id ORDER BY s.id');
        $searchpages0 = $query->getResult();
        foreach ($searchpages0 as &$searchpage) $searchpage['title'] = $this->get('cms.cmsManager')->decodeLocalString($searchpage['title'],'default',true);
        unset($searchpage);
        
        return $this->render('BasicCmsBundle:Default:sitemapPagesList.html.twig', array(
            'searchpages0' => $searchpages0,
            'tab' => $tab
        ));
    }
    
// *******************************************
// Создание страницы поиска по сайту
// *******************************************    
    public function siteMapSearchpageCreateAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_sitemap_pageslist_tab', 0);
        if ($this->getUser()->checkAccess('site_searchpagenew') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой страницы поиска по сайту',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $searchoptions = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) 
        {
            $this->container->get($item)->getSearchOptions($searchoptions);
        }     
        
        $searchpage = array();
        $searchpageerror = array();
        
        $searchpage['title'] = array();
        $searchpage['title']['default'] = '';
        foreach ($locales as $locale) $searchpage['title'][$locale['shortName']] = '';
        $searchpage['enabled'] = 1;
        $searchpage['objectOptions'] = array();
        $searchpage['countInPage'] = '';
        $searchpage['getCountInPage'] = '';
        $searchpage['sortDirection'] = '';

        $searchpageerror['title'] = array();
        $searchpageerror['title']['default'] = '';
        foreach ($locales as $locale) $searchpageerror['title'][$locale['shortName']] = '';
        $searchpageerror['enabled'] = '';
        $searchpageerror['objectOptions'] = '';
        $searchpageerror['countInPage'] = '';
        $searchpageerror['getCountInPage'] = '';
        $searchpageerror['sortDirection'] = '';
        
        $page = array();
        $pageerror = array();
        
        $page['url'] = '';
        $page['modules'] = array();
        $page['locale'] = '';
        $page['template'] = '';
        $page['layout'] = '';
        $page['accessOn'] = '';
        $page['access'] = array();
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.user','searchpage');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.user.searchpage');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postsearchpage = $this->get('request_stack')->getMasterRequest()->get('searchpage');
            if (isset($postsearchpage['title']['default'])) $searchpage['title']['default'] = $postsearchpage['title']['default'];
            foreach ($locales as $locale) if (isset($postsearchpage['title'][$locale['shortName']])) $searchpage['title'][$locale['shortName']] = $postsearchpage['title'][$locale['shortName']];
            if (isset($postsearchpage['enabled'])) $searchpage['enabled'] = intval($postsearchpage['enabled']); else $searchpage['enabled'] = 0;
            if (isset($postsearchpage['getCountInPage'])) $searchpage['getCountInPage'] = intval($postsearchpage['getCountInPage']); else $searchpage['getCountInPage'] = 0;
            if (isset($postsearchpage['countInPage'])) $searchpage['countInPage'] = $postsearchpage['countInPage'];
            if (isset($postsearchpage['sortDirection'])) $searchpage['sortDirection'] = intval($postsearchpage['sortDirection']); else $searchpage['sortDirection'] = 0;
            $searchpage['objectOptions'] = array();
            foreach ($searchoptions as $searchoptionkey => $searchoption)
            {
                if (isset($postsearchpage['objectOptions']) && is_array($postsearchpage['objectOptions']) && in_array($searchoptionkey, $postsearchpage['objectOptions'])) $searchpage['objectOptions'][$searchoptionkey] = 1; 
                    else $searchpage['objectOptions'][$searchoptionkey] = 0;
            }
            unset($postsearchpage);
            if (!preg_match("/^.{3,}$/ui", $searchpage['title']['default'])) {$errors = true; $searchpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $searchpage['title'][$locale['shortName']])) {$errors = true; $searchpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if ((!preg_match("/^\d+$/ui", $searchpage['countInPage'])) || ($searchpage['countInPage'] < 1) || ($searchpage['countInPage'] > 1000)) {$errors = true; $searchpageerror['countInPage'] = 'Должно быть указано число от 1 до 1000';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['url'] != '')
            {
                if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
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
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $searchpageent = new \Basic\CmsBundle\Entity\SearchPages();
                $searchpageent->setCountInPage($searchpage['countInPage']);
                $searchpageent->setEnabled($searchpage['enabled']);
                $searchpageent->setGetCountInPage($searchpage['getCountInPage']);
                $searchpageent->setObjectOptions(serialize($searchpage['objectOptions']));
                $searchpageent->setSortDirection($searchpage['sortDirection']);
                $searchpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($searchpage['title']));
                $em->persist($searchpageent);
                $em->flush();
                
                $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница поиска по сайту '.$this->get('cms.cmsManager')->decodeLocalString($searchpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.user');
                $pageent->setContentId($searchpageent->getId());
                $pageent->setContentAction('searchpage');
                $searchpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->persist($pageent);
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой страницы поиска по сайту',
                    'message'=>'Страница поиска по сайту успешно создана',
                    'paths'=>array('Создать еще одну страницу поиска'=>$this->get('router')->generate('basic_cms_sitemap_searchpage_create'),'Вернуться к списку прочих страниц'=>$this->get('router')->generate('basic_cms_sitemap_pageslist'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Default:searchpageCreate.html.twig', array(
            'searchpage' => $searchpage,
            'searchpageerror' => $searchpageerror,
            'searchoptions' => $searchoptions,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// Редактирование страницы поиска по сайту
// *******************************************    
    public function siteMapSearchpageEditAction()
    {
        $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_sitemap_pageslist_tab', 0);
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $searchpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SearchPages')->find($id);
        if (empty($searchpageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы поиска по сайту',
                'message'=>'Страница поиска не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку прочих страниц'=>$this->get('router')->generate('basic_cms_sitemap_pageslist'))
            ));
        }
        if ($this->getUser()->checkAccess('site_searchpageview') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы поиска по сайту',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.user','contentId'=>$id,'contentAction'=>'searchpage'));
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $searchoptions = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) 
        {
            $this->container->get($item)->getSearchOptions($searchoptions);
        }     
        
        $searchpage = array();
        $searchpageerror = array();
        
        $searchpage['title'] = array();
        $searchpage['title']['default'] = $this->get('cms.cmsManager')->decodeLocalString($searchpageent->getTitle(),'default',false);
        foreach ($locales as $locale) $searchpage['title'][$locale['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($searchpageent->getTitle(),$locale['shortName'],false);
        $searchpage['enabled'] = $searchpageent->getEnabled();
        $searchpage['objectOptions'] = @unserialize($searchpageent->getObjectOptions());
        if (!is_array($searchpage['objectOptions'])) $searchpage['objectOptions'] = array();
        $searchpage['countInPage'] = $searchpageent->getCountInPage();
        $searchpage['getCountInPage'] = $searchpageent->getGetCountInPage();
        $searchpage['sortDirection'] = $searchpageent->getSortDirection();

        $searchpageerror['title'] = array();
        $searchpageerror['title']['default'] = '';
        foreach ($locales as $locale) $searchpageerror['title'][$locale['shortName']] = '';
        $searchpageerror['enabled'] = '';
        $searchpageerror['objectOptions'] = '';
        $searchpageerror['countInPage'] = '';
        $searchpageerror['getCountInPage'] = '';
        $searchpageerror['sortDirection'] = '';
        
        $page = array();
        $pageerror = array();
        
        if (empty($pageent))
        {
            $page['url'] = '';
            $page['modules'] = array();
            $page['locale'] = '';
            $page['template'] = '';
            $page['layout'] = '';
            $page['accessOn'] = '';
            $page['access'] = array();
        } else
        {
            $page['url'] = ($pageent->getUrl() != $pageent->getId() ? $pageent->getUrl() : '');
            $page['modules'] = explode(',',$pageent->getModules());
            $page['locale'] = $pageent->getLocale();
            $page['template'] = $searchpageent->getTemplate();
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
        
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.user','searchpage');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (empty($pageent))
        {
            if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
            if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        }
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.user.searchpage');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }    
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postsearchpage = $this->get('request_stack')->getMasterRequest()->get('searchpage');
            if (isset($postsearchpage['title']['default'])) $searchpage['title']['default'] = $postsearchpage['title']['default'];
            foreach ($locales as $locale) if (isset($postsearchpage['title'][$locale['shortName']])) $searchpage['title'][$locale['shortName']] = $postsearchpage['title'][$locale['shortName']];
            if (isset($postsearchpage['enabled'])) $searchpage['enabled'] = intval($postsearchpage['enabled']); else $searchpage['enabled'] = 0;
            if (isset($postsearchpage['getCountInPage'])) $searchpage['getCountInPage'] = intval($postsearchpage['getCountInPage']); else $searchpage['getCountInPage'] = 0;
            if (isset($postsearchpage['countInPage'])) $searchpage['countInPage'] = $postsearchpage['countInPage'];
            if (isset($postsearchpage['sortDirection'])) $searchpage['sortDirection'] = intval($postsearchpage['sortDirection']); else $searchpage['sortDirection'] = 0;
            $searchpage['objectOptions'] = array();
            foreach ($searchoptions as $searchoptionkey => $searchoption)
            {
                if (isset($postsearchpage['objectOptions']) && is_array($postsearchpage['objectOptions']) && in_array($searchoptionkey, $postsearchpage['objectOptions'])) $searchpage['objectOptions'][$searchoptionkey] = 1; 
                    else $searchpage['objectOptions'][$searchoptionkey] = 0;
            }
            unset($postsearchpage);
            if (!preg_match("/^.{3,}$/ui", $searchpage['title']['default'])) {$errors = true; $searchpageerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) if (!preg_match("/^(.{3,})?$/ui", $searchpage['title'][$locale['shortName']])) {$errors = true; $searchpageerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов или оставьте поле пустым';}
            if ((!preg_match("/^\d+$/ui", $searchpage['countInPage'])) || ($searchpage['countInPage'] < 1) || ($searchpage['countInPage'] > 1000)) {$errors = true; $searchpageerror['countInPage'] = 'Должно быть указано число от 1 до 1000';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
            if (isset($postpage['template'])) $page['template'] = $postpage['template'];
            if (isset($postpage['layout'])) $page['layout'] = $postpage['layout'];
            if (isset($postpage['accessOn'])) $page['accessOn'] = intval($postpage['accessOn']);
            $page['access'] = array();
            foreach ($roles as $onerole) if (isset($postpage['access']) && is_array($postpage['access']) && (in_array($onerole['id'], $postpage['access']))) $page['access'][] = $onerole['id'];
            unset($postpage);
            if ($page['url'] != '')
            {
                if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", $page['url'])) {$errors = true; $pageerror['url'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
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
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $searchpageent->setCountInPage($searchpage['countInPage']);
                $searchpageent->setEnabled($searchpage['enabled']);
                $searchpageent->setGetCountInPage($searchpage['getCountInPage']);
                $searchpageent->setObjectOptions(serialize($searchpage['objectOptions']));
                $searchpageent->setSortDirection($searchpage['sortDirection']);
                $searchpageent->setTitle($this->get('cms.cmsManager')->encodeLocalString($searchpage['title']));
                $em->flush();
                
                if (!empty($pageent)) $this->get('cms.cmsManager')->updateBreadCrumbs($pageent->getId());
                if (empty($pageent))
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $em->persist($pageent);
                }
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Страница поиска по сайту '.$this->get('cms.cmsManager')->decodeLocalString($searchpageent->getTitle(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.user');
                $pageent->setContentId($searchpageent->getId());
                $pageent->setContentAction('searchpage');
                $searchpageent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование страницы поиска по сайту',
                    'message'=>'Страница поиска по сайту успешно отредактирована',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_sitemap_searchpage_edit').'?id='.$id,'Вернуться к списку прочих страниц'=>$this->get('router')->generate('basic_cms_sitemap_pageslist'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Default:searchpageEdit.html.twig', array(
            'id' => $id,
            'searchpage' => $searchpage,
            'searchpageerror' => $searchpageerror,
            'searchoptions' => $searchoptions,
            'roles' => $roles,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// AJAX-контроллер
// *******************************************    
    
    public function siteMapPagesAjaxAction()
    {
        if ($this->getUser()->checkAccess('site_pages') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Прочие страницы',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
//        $tab = intval($this->get('request_stack')->getMasterRequest()->get('tab'));
        $tab = 0;
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->get('request_stack')->getMasterRequest()->get('action');
        $errors = array();
        if ($tab == 0)
        {
            if ($action == 'delete')
            {
                if ($this->getUser()->checkAccess('site_searchpageedit') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Прочие страницы',
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
                        $searchpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SearchPages')->find($key);
                        if (!empty($searchpageent))
                        {
                            $em->remove($searchpageent);
                            $em->flush();
                            $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.user\' AND p.contentAction = \'searchpage\' AND p.contentId = :id')->setParameter('id', $key);
                            $query->execute();
                        }
                        unset($searchpageent);    
                    }
            }
            if ($action == 'blocked')
            {
                if ($this->getUser()->checkAccess('site_searchpageedit') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $searchpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SearchPages')->find($key);
                        if (!empty($searchpageent))
                        {
                            $searchpageent->setEnabled(0);
                            $em->flush();
                            unset($searchpageent);    
                        }
                    }
            }
            if ($action == 'unblocked')
            {
                if ($this->getUser()->checkAccess('site_searchpageedit') == 0)
                {
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Пользователи',
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
                        $searchpageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SearchPages')->find($key);
                        if (!empty($searchpageent))
                        {
                            $searchpageent->setEnabled(1);
                            $em->flush();
                            unset($searchpageent);    
                        }
                    }
            }
        }
            
        if ($tab == 0)
        {
            // Таб 1
            $query = $em->createQuery('SELECT s.id, s.title, s.enabled, sp.url FROM BasicCmsBundle:SearchPages s LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.user\' AND sp.contentAction = \'searchpage\' AND sp.contentId = s.id ORDER BY s.id');
            $searchpages0 = $query->getResult();
            foreach ($searchpages0 as &$searchpage) $searchpage['title'] = $this->get('cms.cmsManager')->decodeLocalString($searchpage['title'],'default',true);
            unset($searchpage);

            return $this->render('BasicCmsBundle:Default:sitemapPagesListTab1.html.twig', array(
                'searchpages0' => $searchpages0,
                'errors0' => $errors,
                'tab' => $tab
            ));
        }
        
    }
    
// *******************************************
// Справочная система
// *******************************************    
    
    public function helpAction()
    {
        $help = array();
        $cmsservices = $this->container->getServiceIds();
        $helppage = $this->get('request_stack')->getMasterRequest()->get('page');
        $helpcontent = '';
        foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) 
        {
            $serv = $this->container->get($item);
            $help[] = array('name'=>$item,'description'=>$serv->getDescription(),'pages'=>$serv->getHelpChapters());
            if ($helppage != null) $serv->getHelpContent($helppage, $helpcontent);
        }       
        
        return $this->render('BasicCmsBundle:Default:help.html.twig', array(
            'help' => $help,
            'helppage' => $helppage,
            'helpcontent' => $helpcontent
        ));
    }

// *******************************************
// Логи
// *******************************************    

    public function logsListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $tab = $this->get('request_stack')->getMasterRequest()->get('tab');
        if ($tab === null) $tab = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_logs_tab');
                      else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_logs_tab', $tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 1) $tab = 1;
        if (($tab == 1) && ($this->getUser()->checkAccess('site_logfront') == 0)) $tab = 0;
        // Таб 1
        $page0 = $this->get('request_stack')->getMasterRequest()->get('page0');
        $sort0 = $this->get('request_stack')->getMasterRequest()->get('sort0');
        $search0 = $this->get('request_stack')->getMasterRequest()->get('search0');
        $filter0 = $this->get('request_stack')->getMasterRequest()->get('filter0');
        $object0 = $this->get('request_stack')->getMasterRequest()->get('object0');
        if ($page0 === null) $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_logs_page0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_logs_page0', $page0);
        if ($sort0 === null) $sort0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_logs_sort0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_logs_sort0', $sort0);
        if ($search0 === null) $search0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_logs_search0');
                          else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_logs_search0', $search0);
        if ($filter0 === null) $filter0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_logs_filter0');
                          else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_logs_filter0', $filter0);
        if ($object0 === null) $object0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_logs_object0');
                          else $this->get('request_stack')->getMasterRequest()->getSession()->set('basic_cms_logs_object0', $object0);
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        if (($filter0 != 'create') && ($filter0 != 'edit') && ($filter0 != 'moderate') && ($filter0 != 'other')) $filter0 = '';
        $modules = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) $modules[$item] = $this->container->get($item)->getDescription();
        if (!isset($modules[$object0])) $object0 = '';
        // Поиск контента для 1 таба
        $query = $em->createQuery('SELECT count(l.id) as logcount FROM BasicCmsBundle:LogFront l '.
                                  'WHERE l.text like :search '.($filter0 != '' ? 'AND l.actionType like \'%.'.$filter0.'\' ' : ' ').($object0 != '' ? 'AND l.actionType like \''.$object0.'.%\' ' : ' '))->setParameter('search', '%'.$search0.'%');
        $logcount = $query->getResult();
        if (!empty($logcount)) $logcount = $logcount[0]['logcount']; else $logcount = 0;
        $pagecount0 = ceil($logcount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY l.logDate DESC';
        if ($sort0 == 1) $sortsql = 'ORDER BY l.logDate ASC';
        if ($sort0 == 2) $sortsql = 'ORDER BY l.actionType ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY l.actionType DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY l.sourceId ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY l.sourceId DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY l.text ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY l.text DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT l.id, l.actionType, l.text, l.viewed, l.logDate, l.viewedDate, su.fullName as sourceName, vu.fullName as viewedName FROM BasicCmsBundle:LogFront l '.
                                  'LEFT JOIN BasicCmsBundle:Users su WITH su.id = l.sourceId '.
                                  'LEFT JOIN BasicCmsBundle:Users vu WITH vu.id = l.viewedUserId '.
                                  'WHERE l.text like :search '.($filter0 != '' ? 'AND l.actionType like \'%.'.$filter0.'\' ' : ' ').($object0 != '' ? 'AND l.actionType like \''.$object0.'.%\' ' : ' ').$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $logitems0 = $query->getResult();
        foreach ($logitems0 as &$logitem)
        {
            $logitem['actionObject'] = '';
            if (strrpos($logitem['actionType'], '.') !== false)
            {
                $logitem['actionObject'] = substr($logitem['actionType'], 0, strrpos($logitem['actionType'], '.'));
                if (isset($modules[$logitem['actionObject']])) $logitem['actionObject'] = $modules[$logitem['actionObject']];
                $logitem['actionType'] = substr($logitem['actionType'], strrpos($logitem['actionType'], '.') + 1);
            }
        }
        unset($logitem);
        // Таб 2
        $subscribe1 = $this->get('cms.cmsManager')->getConfigValue('object.user.logsubscribe');
        if (!is_array($subscribe1)) $subscribe1 = array();
        foreach ($subscribe1 as $sbkey=>&$sbitem)
        {
            if (isset($sbitem['email']) && isset($sbitem['actions']))
            {
                $actionsbymodules = array();
                $actionstring = '';
                $actions = explode(',', $sbitem['actions']);
                foreach ($actions as $action)
                {
                    if (strrpos($action, '.') !== false)
                    {
                        $actname = '';
                        if ((substr($action, strrpos($action, '.') + 1)) == 'create') $actname = 'создание';
                        if ((substr($action, strrpos($action, '.') + 1)) == 'edit') $actname = 'изменение';
                        if ((substr($action, strrpos($action, '.') + 1)) == 'moderate') $actname = 'модерация';
                        if ((substr($action, strrpos($action, '.') + 1)) == 'other') $actname = 'прочее';
                        if (!isset($actionsbymodules[substr($action, 0, strrpos($action, '.'))])) $actionsbymodules[substr($action, 0, strrpos($action, '.'))] = $actname; else
                        $actionsbymodules[substr($action, 0, strrpos($action, '.'))] .= ', '.$actname;
                    }
                }
                foreach ($actionsbymodules as $amodule=>$action)
                {
                    if (isset($modules[$amodule]))
                    {
                        if ($actionstring != '') $actionstring .= ', ';
                        $actionstring .= $modules[$amodule].' ('.$action.')';
                    }
                }
                $sbitem['actions'] = $actionstring;
            } else unset($subscribe1[$sbkey]);
        }
        unset($sbitem);
        return $this->render('BasicCmsBundle:Default:logsList.html.twig', array(
            'logitems0' => $logitems0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'pagecount0' => $pagecount0,
            'filter0' => $filter0,
            'object0' => $object0,
            'tab' => $tab,
            'modules' => $modules,
            'subscribe1' => $subscribe1,
            'email1' => '',
            'actions1' => array()
        ));
        
    }
    
    public function logsAjaxAction()
    {
        $tab = intval($this->get('request_stack')->getMasterRequest()->get('tab'));
        
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->get('request_stack')->getMasterRequest()->get('action');
        $errors = array();
        if ($tab == 0)
        {
            if ($action == 'viewed')
            {
                $check = $this->get('request_stack')->getMasterRequest()->get('check');
                if ($check != null)
                foreach ($check as $key=>$val)
                    if ($val == 1)
                    {
                        $logent = $this->getDoctrine()->getRepository('BasicCmsBundle:LogFront')->find($key);
                        if (!empty($logent))
                        {
                            $logent->setViewed(1);
                            $logent->setViewedUserId($this->getUser()->getId());
                            $logent->setViewedDate(new \DateTime('now'));
                            $em->flush();
                            unset($logent);    
                        }
                    }
            }
            // Таб 1
            $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_logs_page0');
            $sort0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_logs_sort0');
            $search0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_logs_search0');
            $filter0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_logs_filter0');
            $object0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('basic_cms_logs_object0');
            $page0 = intval($page0);
            $sort0 = intval($sort0);
            $search0 = trim($search0);
            if (($filter0 != 'create') && ($filter0 != 'edit') && ($filter0 != 'moderate') && ($filter0 != 'other')) $filter0 = '';
            $modules = array();
            $cmsservices = $this->container->getServiceIds();
            foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) $modules[$item] = $this->container->get($item)->getDescription();
            if (!isset($modules[$object0])) $object0 = '';
            // Поиск контента для 1 таба
            $query = $em->createQuery('SELECT count(l.id) as logcount FROM BasicCmsBundle:LogFront l '.
                                      'WHERE l.text like :search '.($filter0 != '' ? 'AND l.actionType like \'%.'.$filter0.'\' ' : ' ').($object0 != '' ? 'AND l.actionType like \''.$object0.'.%\' ' : ' '))->setParameter('search', '%'.$search0.'%');
            $logcount = $query->getResult();
            if (!empty($logcount)) $logcount = $logcount[0]['logcount']; else $logcount = 0;
            $pagecount0 = ceil($logcount / 20);
            if ($pagecount0 < 1) $pagecount0 = 1;
            if ($page0 < 0) $page0 = 0;
            if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
            $sortsql = 'ORDER BY l.logDate DESC';
            if ($sort0 == 1) $sortsql = 'ORDER BY l.logDate ASC';
            if ($sort0 == 2) $sortsql = 'ORDER BY l.actionType ASC';
            if ($sort0 == 3) $sortsql = 'ORDER BY l.actionType DESC';
            if ($sort0 == 4) $sortsql = 'ORDER BY l.sourceId ASC';
            if ($sort0 == 5) $sortsql = 'ORDER BY l.sourceId DESC';
            if ($sort0 == 6) $sortsql = 'ORDER BY l.text ASC';
            if ($sort0 == 7) $sortsql = 'ORDER BY l.text DESC';
            $start = $page0 * 20;
            $query = $em->createQuery('SELECT l.id, l.actionType, l.text, l.viewed, l.logDate, l.viewedDate, su.fullName as sourceName, vu.fullName as viewedName FROM BasicCmsBundle:LogFront l '.
                                      'LEFT JOIN BasicCmsBundle:Users su WITH su.id = l.sourceId '.
                                      'LEFT JOIN BasicCmsBundle:Users vu WITH vu.id = l.viewedUserId '.
                                      'WHERE l.text like :search '.($filter0 != '' ? 'AND l.actionType like \'%.'.$filter0.'\' ' : ' ').($object0 != '' ? 'AND l.actionType like \''.$object0.'.%\' ' : ' ').$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
            $logitems0 = $query->getResult();
            foreach ($logitems0 as &$logitem)
            {
                $logitem['actionObject'] = '';
                if (strrpos($logitem['actionType'], '.') !== false)
                {
                    $logitem['actionObject'] = substr($logitem['actionType'], 0, strrpos($logitem['actionType'], '.'));
                    if (isset($modules[$logitem['actionObject']])) $logitem['actionObject'] = $modules[$logitem['actionObject']];
                    $logitem['actionType'] = substr($logitem['actionType'], strrpos($logitem['actionType'], '.') + 1);
                }
            }
            unset($logitem);
            return $this->render('BasicCmsBundle:Default:logsListTab1.html.twig', array(
                'logitems0' => $logitems0,
                'search0' => $search0,
                'page0' => $page0,
                'sort0' => $sort0,
                'pagecount0' => $pagecount0,
                'filter0' => $filter0,
                'object0' => $object0,
                'tab' => $tab,
                'modules' => $modules,
                'errors0' => $errors,
            ));
        }
        if ($tab == 1)
        {
            if ($this->getUser()->checkAccess('site_logfront') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Журнал событий',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $modules = array();
            $cmsservices = $this->container->getServiceIds();
            foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) $modules[$item] = $this->container->get($item)->getDescription();
            $email1 = '';
            $actions1 = array();
            if ($action == 'delete')
            {
                $check = $this->get('request_stack')->getMasterRequest()->get('check');
                $subscribe1 = $this->get('cms.cmsManager')->getConfigValue('object.user.logsubscribe');
                if (!is_array($subscribe1)) $subscribe1 = array();
                if ($check != null)
                foreach ($check as $key=>$val)
                    if ($val == 1)
                    {
                        unset($subscribe1[$key]);
                    }
                $this->get('cms.cmsManager')->setConfigValue('object.user.logsubscribe', $subscribe1);
            }
            if ($action == 'create')
            {
                $email1 = $this->get('request_stack')->getMasterRequest()->get('email');
                $postactions1 = $this->get('request_stack')->getMasterRequest()->get('actions');
                if (!is_array($postactions1)) $postactions1 = array();
                $checkerror = false;
                if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,24})$/ui", $email1)) {$checkerror = true; $errors['email'] = 'Неправильный формат адреса электронной почты';}
                $actions1 = array();
                foreach ($postactions1 as $tmpaction)
                {
                    if (strrpos($tmpaction, '.') !== false)
                    {
                        if ((isset($modules[substr($tmpaction, 0, strrpos($tmpaction, '.'))])) && 
                            ((substr($tmpaction, strrpos($tmpaction, '.') + 1) == 'create') || 
                             (substr($tmpaction, strrpos($tmpaction, '.') + 1) == 'edit') || 
                             (substr($tmpaction, strrpos($tmpaction, '.') + 1) == 'moderate') || 
                             (substr($tmpaction, strrpos($tmpaction, '.') + 1) == 'other'))) $actions1[] = $tmpaction;
                    }
                }
                if (count($actions1) == 0) {$checkerror = true; $errors['actions'] = 'Необходимо отметить хотя-бы одну подписку';}
                if ($checkerror == false)
                {
                    $subscribe1 = $this->get('cms.cmsManager')->getConfigValue('object.user.logsubscribe');
                    if (!is_array($subscribe1)) $subscribe1 = array();
                    $subscribe1[] = array('email' => $email1, 'actions' => implode(',', $actions1));
                    $this->get('cms.cmsManager')->setConfigValue('object.user.logsubscribe', $subscribe1);
                }
            }
            $subscribe1 = $this->get('cms.cmsManager')->getConfigValue('object.user.logsubscribe');
            if (!is_array($subscribe1)) $subscribe1 = array();
            foreach ($subscribe1 as $sbkey=>&$sbitem)
            {
                if (isset($sbitem['email']) && isset($sbitem['actions']))
                {
                    $actionsbymodules = array();
                    $actionstring = '';
                    $actions = explode(',', $sbitem['actions']);
                    foreach ($actions as $action)
                    {
                        if (strrpos($action, '.') !== false)
                        {
                            $actname = '';
                            if ((substr($action, strrpos($action, '.') + 1)) == 'create') $actname = 'создание';
                            if ((substr($action, strrpos($action, '.') + 1)) == 'edit') $actname = 'изменение';
                            if ((substr($action, strrpos($action, '.') + 1)) == 'moderate') $actname = 'модерация';
                            if ((substr($action, strrpos($action, '.') + 1)) == 'other') $actname = 'прочее';
                            if (!isset($actionsbymodules[substr($action, 0, strrpos($action, '.'))])) $actionsbymodules[substr($action, 0, strrpos($action, '.'))] = $actname; else
                            $actionsbymodules[substr($action, 0, strrpos($action, '.'))] .= ', '.$actname;
                        }
                    }
                    foreach ($actionsbymodules as $amodule=>$action)
                    {
                        if (isset($modules[$amodule]))
                        {
                            if ($actionstring != '') $actionstring .= ', ';
                            $actionstring .= $modules[$amodule].' ('.$action.')';
                        }
                    }
                    $sbitem['actions'] = $actionstring;
                } else unset($subscribe1[$sbkey]);
            }
            unset($sbitem);
            return $this->render('BasicCmsBundle:Default:logsListTab2.html.twig', array(
                'tab' => $tab,
                'modules' => $modules,
                'subscribe1' => $subscribe1,
                'email1' => $email1,
                'actions1' => $actions1,
                'errors1' => $errors
            ));
            
        }
    }

    public function logsViewAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $logent = $this->getDoctrine()->getRepository('BasicCmsBundle:LogFront')->find($id);
        if (empty($logent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр события',
                'message'=>'Событие не найдено', 
                'error'=>true,
                'paths'=>array('Вернуться к списку событий'=>$this->get('router')->generate('basic_cms_logs'))
            ));
        }
        $logent->setViewed(1);
        $logent->setViewedUserId($this->getUser()->getId());
        $logent->setViewedDate(new \DateTime('now'));
        $em->flush();
        return $this->redirect('http://'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost().$logent->getUrl());
    }
    
}
