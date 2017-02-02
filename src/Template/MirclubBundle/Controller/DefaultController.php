<?php

namespace Template\MirclubBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
// *******************************************
// Список изображений галереи
// *******************************************    
    public function imageListAction()
    {
        if ($this->getUser()->checkAccess('template_mirclub') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Настройка цветов и фона',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $tab = 0;
        // Таб 1
        $page0 = $this->get('request_stack')->getMasterRequest()->get('page0');
        if ($page0 === null) $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('template_mirclub_index_page0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('template_mirclub_index_page0', $page0);
        $page0 = intval($page0);
        // Поиск контента для 1 таба (текстовые страницы)
        $query = $em->createQuery('SELECT count(i.id) as imgcount FROM TemplateMirclubBundle:TemplateMirclubImage i');
        $imgcount = $query->getResult();
        if (!empty($imgcount)) $imgcount = $imgcount[0]['imgcount']; else $imgcount = 0;
        $pagecount0 = ceil($imgcount / 50);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $start = $page0 * 50;
        $query = $em->createQuery('SELECT i.id, i.title, i.image FROM TemplateMirclubBundle:TemplateMirclubImage i ')->setFirstResult($start)->setMaxResults(50);
        $images0 = $query->getResult();
        
        $randomSetting0 = $this->get('cms.cmsManager')->getConfigValue('template.mirclub.random');
        
        return $this->render('TemplateMirclubBundle:Default:imagesList.html.twig', array(
            'images0' => $images0,
            'page0' => $page0,
            'pagecount0' => $pagecount0,
            'randomSetting0' => $randomSetting0,
            'tab' => $tab
        ));
    }

// *******************************************
// AJAX загрузка изображения
// *******************************************    

    public function galerryAjaxImageAction() 
    {
        if ($this->getUser()->checkAccess('template_mirclub') == 0)
        {
            return new Response('Доступ запрещен');
        }
        $userId = $this->getUser()->getId();
        $files = $this->get('request_stack')->getMasterRequest()->files->get('image');
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
                if (!isset($imageTypeArray[$params[2]]) || ($imageTypeArray[$params[2]] == '')) $answeritem = array('title' => '', 'file' => '', 'error' => 'Формат файла не поддерживается');
                $basepath = '/images/templatemirclub/';
                if (!is_dir('.'.$basepath)) mkdir('.'.$basepath);
                $name = $this->getUser()->getId().'_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
                if ($answeritem == null)
                {
                    if (move_uploaded_file($tmpfile, '.'.$basepath.$name)) 
                    {
                        $image = new \Template\MirclubBundle\Entity\TemplateMirclubImage();
                        $image->setTitle($file->getClientOriginalName());
                        $image->setImage($basepath.$name);
                        $image->setParameters('');
                        $this->getDoctrine()->getEntityManager()->persist($image);
                        $this->getDoctrine()->getEntityManager()->flush();
                        $answeritem = array('id' => $image->getId(), 'title' => $file->getClientOriginalName(), 'file' => $basepath.$name, 'error' => '');
                    } else $answeritem = array('title' => '', 'file' => '', 'error' => 'Ошибка копирования файла');
                }
            } else $answeritem = array('title' => '', 'file' => '', 'error' => 'Неправильный формат файла');
            $answer[] = $answeritem;
        }
        return new Response(json_encode($answer));
    }
    
    public function galerryUrlImageAction() 
    {
        if ($this->getUser()->checkAccess('template_mirclub') == 0)
        {
            return new Response('Доступ запрещен');
        }
        if (!function_exists('getimagesizefromstring')) 
        {
            function getimagesizefromstring($string_data)
            {
                $uri = 'data://application/octet-stream;base64,'  . base64_encode($string_data);
                return getimagesize($uri);
            }
        }
        $userId = $this->getUser()->getId();
        $files = $this->get('request_stack')->getMasterRequest()->get('image');
        
        preg_match_all('/http:\/\/\S+/ui', $files, $matches);
        if (isset($matches[0])) $files = $matches[0]; else $files = array();
        $answer = array();
        foreach ($files as $file)
        {
            $answeritem = null;
            $ch = curl_init();  
            curl_setopt($ch, CURLOPT_URL, $file);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            $photobuffer = curl_exec($ch);
            curl_close($ch);
            if (@getimagesizefromstring($photobuffer)) 
            {
                $params = getimagesizefromstring($photobuffer);
                if ($params[0] < 10) $answeritem = array('file' => '', 'error' => 'Разрешение файла меньше 10x10');
                if ($params[1] < 10) $answeritem = array('file' => '', 'error' => 'Разрешение файла меньше 10x10');
                if ($params[0] > 6000) $answeritem = array('file' => '', 'error' => 'Разрешение файла больше 6000x6000');
                if ($params[1] > 6000) $answeritem = array('file' => '', 'error' => 'Разрешение файла больше 6000x6000');
                $imageTypeArray = array(0=>'', 1=>'gif', 2=>'jpg', 3=>'png', 4=>'', 5=>'', 6=>'', 7=>'', 8=>'', 9=>'jpg', 10=>'jpg', 11=>'jpg', 12=>'jpg', 13=>'', 14=>'', 15=>'', 16=>'', 17=>'', 18=>'');
                if (!isset($imageTypeArray[$params[2]]) || ($imageTypeArray[$params[2]] == '')) $answeritem = array('title' => '', 'file' => '', 'error' => 'Формат файла не поддерживается');
                $basepath = '/images/templatemirclub/';
                if (!is_dir('.'.$basepath)) mkdir('.'.$basepath);
                $name = $this->getUser()->getId().'_'.md5($file.time()).'.'.$imageTypeArray[$params[2]];
                if ($answeritem == null)
                {
                    if (file_put_contents('.'.$basepath.$name, $photobuffer)) 
                    {
                        $image = new \Template\MirclubBundle\Entity\TemplateMirclubImage();
                        $image->setTitle(basename($file));
                        $image->setImage($basepath.$name);
                        $image->setParameters('');
                        $this->getDoctrine()->getEntityManager()->persist($image);
                        $this->getDoctrine()->getEntityManager()->flush();
                        $answeritem = array('id' => $image->getId(), 'title' => basename($file), 'file' => $basepath.$name, 'error' => '');
                    } else $answeritem = array('title' => '', 'file' => '', 'error' => 'Ошибка копирования файла');
                }
            } else $answeritem = array('title' => '', 'file' => '', 'error' => 'Неправильный формат файла');
            $answer[] = $answeritem;
        }
        return new Response(json_encode($answer));
    }
    
    public function galerryAjaxDeleteAction() 
    {
        if ($this->getUser()->checkAccess('template_mirclub') == 0)
        {
            return new Response('Доступ запрещен');
        }
        
        $id = $this->get('request_stack')->getMasterRequest()->get('id');
        
        $imageent = $this->getDoctrine()->getRepository('TemplateMirclubBundle:TemplateMirclubImage')->find($id);
        if (empty($imageent)) return new Response('Изображение не найдено');
        
        $em = $this->getDoctrine()->getEntityManager();
        
        @unlink('.'.$imageent->getImage());
        $em->remove($imageent);
        $em->flush();
        $em->createQuery('DELETE FROM TemplateMirclubBundle:TemplateMirclubLink l WHERE l.imageId = :id')->setParameter('id', $id)->execute();
        
        return new Response('OK');
    }
    
    public function galerryAjaxChangeRandomAction() 
    {
        if ($this->getUser()->checkAccess('template_mirclub') == 0)
        {
            return new Response('Доступ запрещен');
        }
        
        $state = ($this->get('request_stack')->getMasterRequest()->get('state') == 'true' ? 1 : 0);

        $this->get('cms.cmsManager')->setConfigValue('template.mirclub.random', $state);
        
        return new Response(($state ? 'true' : 'false'));
    }
    
// *******************************************
// Редактирование изображения
// *******************************************    
    public function imageEditAction()
    {
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $imageent = $this->getDoctrine()->getRepository('TemplateMirclubBundle:TemplateMirclubImage')->find($id);
        if (empty($imageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование изображения',
                'message'=>'Изображение не найдено', 
                'error'=>true,
                'paths'=>array('Вернуться к списку изображений'=>$this->get('router')->generate('template_mirclub_index'))
            ));
        }
        if ($this->getUser()->checkAccess('template_mirclub') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование изображения',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $em = $this->getDoctrine()->getEntityManager();
        
        $image = array();
        $imageerror = array();
        
        $image['title'] = $imageent->getTitle();
        $image['image'] = $imageent->getImage();
        $imageerror['title'] = '';

        $parameters = @unserialize($imageent->getParameters());
        if (!is_array($parameters)) $parameters = array();

        $query = $em->createQuery('SELECT s.id, s.description, s.url FROM TemplateMirclubBundle:TemplateMirclubLink l '.
                                  'LEFT JOIN BasicCmsBundle:SeoPage s WITH s.id = l.seoPageId '.
                                  'WHERE l.imageId = :id')->setParameter('id', $id);
        $pages = $query->getResult();
        if (!is_array($pages)) $pages = array();
        
        // Валидация
        $activetab = 0;
        $errors = false;
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postimage = $this->get('request_stack')->getMasterRequest()->get('image');
            if (isset($postimage['title'])) $image['title'] = $postimage['title'];
            unset($postimage);
            if (!preg_match("/^.{3,}$/ui", $image['title'])) {$errors = true; $imageerror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка параметров
            $parameters = $this->get('request_stack')->getMasterRequest()->get('parameters');
            if (!is_array($parameters)) $parameters = array();
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Валидация страниц
            $postpages = $this->get('request_stack')->getMasterRequest()->get('pages');
            $pages = array();
            if (is_array($postpages))
            {
                foreach ($postpages as $postpage)
                {
                    $seopage = $em->getRepository('BasicCmsBundle:SeoPage')->find($postpage);
                    if (!empty($seopage)) 
                    {
                        $pages[] = array('id' => $seopage->getId(), 'description' => $seopage->getDescription(), 'url' => $seopage->getUrl());
                    }
                    unset($seopage);
                }
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $imageent->setTitle($image['title']);
                $imageent->setParameters(serialize($parameters));
                $em->flush();
                // Сохранение страниц
                $linkIds = array();
                foreach ($pages as $page)
                {
                    $linkent = $this->getDoctrine()->getRepository('TemplateMirclubBundle:TemplateMirclubLink')->findOneBy(array('seoPageId' => $page['id'], 'imageId' => $id));
                    if (empty($linkent))
                    {
                        $linkent = new \Template\MirclubBundle\Entity\TemplateMirclubLink();
                        $linkent->setImageId($id);
                        $linkent->setSeoPageId($page['id']);
                        $em->persist($linkent);
                        $em->flush();
                        
                    }
                    $linkIds[] = $linkent->getId();
                    unset($linkent);
                }
                if (count($linkIds) > 0) $em->createQuery('DELETE FROM TemplateMirclubBundle:TemplateMirclubLink l WHERE l.imageId = :id AND l.id NOT IN (:ids)')->setParameter('id', $id)->setParameter('ids', $linkIds)->execute(); 
                                    else $em->createQuery('DELETE FROM TemplateMirclubBundle:TemplateMirclubLink l WHERE l.imageId = :id')->setParameter('id', $id)->execute(); 
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование изображения',
                    'message'=>'Данные изображения успешно изменены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('template_mirclub_image_edit').'?id='.$id,'Вернуться к списку изображений'=>$this->get('router')->generate('template_mirclub_index'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('TemplateMirclubBundle:Default:imagesEdit.html.twig', array(
            'id' => $id,
            'image' => $image,
            'imageerror' => $imageerror,
            'parameters' => $parameters,
            'pages' => $pages,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// Фронт
// *******************************************    
    public function frontAction ($seoPage) 
    {
        $randomSetting = $this->get('cms.cmsManager')->getConfigValue('template.mirclub.random');
        $image = null;
        $parameters = array();
        $em = $this->getDoctrine()->getEntityManager();
        // найти ID 
        $imageIds = array();
        if ($seoPage->getId())
        {
            $images = $em->createQuery('SELECT i.id FROM TemplateMirclubBundle:TemplateMirclubLink l '.
                                         'LEFT JOIN TemplateMirclubBundle:TemplateMirclubImage i WITH i.id = l.imageId '.
                                         'WHERE l.seoPageId = :seoId AND i.id IS NOT NULL')->setParameter('seoId', $seoPage->getId())->getResult();
            if (is_array($images)) 
            {
                foreach ($images as $imagetmp) $imageIds[] = $imagetmp['id'];
            }
        }
        if ((count($imageIds) == 0) && ($randomSetting == 1))
        {
            $images = $em->createQuery('SELECT i.id FROM TemplateMirclubBundle:TemplateMirclubImage i')->getResult();
            if (is_array($images)) 
            {
                foreach ($images as $imagetmp) $imageIds[] = $imagetmp['id'];
            }
        }
        // если изображение есть - найти случайное из массива
        if (count($imageIds) > 0)
        {
            $rand = rand(0, count($imageIds) - 1);
            if (isset($imageIds[$rand]))
            {
                $imageent = $this->getDoctrine()->getRepository('TemplateMirclubBundle:TemplateMirclubImage')->find($imageIds[$rand]);
                if (!empty($imageent))
                {
                    $image = $imageent->getImage();
                    $parameters = @unserialize($imageent->getParameters());
                    if (!is_array($parameters)) $parameters = array();
                }
            }
        }
        return $this->render('TemplateMirclubBundle:Default:front.html.twig', array(
            'image' => $image,
            'parameters' => $parameters,
        ));
        
    }
    
    
}
