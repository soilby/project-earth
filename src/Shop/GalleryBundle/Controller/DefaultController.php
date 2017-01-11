<?php

namespace Shop\GalleryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{
// *******************************************
// AJAX загрузка изображения
// *******************************************    

    public function galerryAjaxImageAction() 
    {
        $userId = $this->getUser()->getId();
        $files = $this->getRequest()->files->get('image');
        $answer = array();
        foreach ($files as $file)
        {
            $answeritem = null;
            $tmpfile = $file->getPathName();
            if (@getimagesize($tmpfile)) 
            {
                $params = getimagesize($tmpfile);
                if ($params[0] < 10) $answeritem = array('file' => '', 'error' => 'Разрешение файла меньше 10x10');
                if ($params[1] < 10) $answeritem = array('file' => '', 'error' => 'Разрешение файла меньше 10x10');
                if ($params[0] > 6000) $answeritem = array('file' => '', 'error' => 'Разрешение файла больше 6000x6000');
                if ($params[1] > 6000) $answeritem = array('file' => '', 'error' => 'Разрешение файла больше 6000x6000');
                $imageTypeArray = array(0=>'', 1=>'gif', 2=>'jpg', 3=>'png', 4=>'', 5=>'', 6=>'', 7=>'', 8=>'', 9=>'jpg', 10=>'jpg', 11=>'jpg', 12=>'jpg', 13=>'', 14=>'', 15=>'', 16=>'', 17=>'', 18=>'');
                if (!isset($imageTypeArray[$params[2]]) || ($imageTypeArray[$params[2]] == '')) $answeritem = array('file' => '', 'error' => 'Формат файла не поддерживается');
                $basepath = '/images/productgallery/';
                $name = $this->getUser()->getId().'_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
                if ($answeritem == null)
                {
                    if (move_uploaded_file($tmpfile, '..'.$basepath.$name)) 
                    {
                        $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $file->getClientOriginalName());
                        $answeritem = array('file' => $basepath.$name, 'error' => '');
                    } else $answeritem = array('file' => '', 'error' => 'Ошибка копирования файла');
                }
            } else $answeritem = array('file' => '', 'error' => 'Неправильный формат файла');
            $answer[] = $answeritem;
        }
        return new Response(json_encode($answer));
    }
}
