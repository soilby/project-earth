<?php

namespace Extended\ShopExportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{
    public function exportAction()
    {
        if ($this->getUser()->checkAccess('product_export') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Экспорт товаров',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $query = $em->createQuery('SELECT e.id, e.fileName FROM ExtendedShopExportBundle:ProductExportPages e '.
                                  'ORDER BY e.id');
        $exportpages = $query->getResult();
        return $this->render('ExtendedShopExportBundle:Default:exportList.html.twig', array(
            'exportpages' => $exportpages,
            'host' => $this->get('request_stack')->getMasterRequest()->getHttpHost()
            ));
    }
    
    
    public function exportOneCreateAction()
    {
        if ($this->getUser()->checkAccess('product_export') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой страницы экспорта',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        // Данные
        $page = array();
        $pageerror = array();
        $page['fileName'] = '';
        $page['onlyOnStock'] = '';
        $page['currency'] = '';
        $page['categories'] = array();
        $pageerror['fileName'] = '';
        $pageerror['onlyOnStock'] = '';
        $pageerror['currency'] = '';
        $pageerror['categories'] = '';
        // Считать валюты
        $query = $em->createQuery('SELECT c.id, c.name FROM ShopProductBundle:ProductCurrency c ORDER BY c.main DESC, c.id ASC');
        $currencies = $query->getResult();
        // Считать категории
        $categories = array();
        if ($this->container->has('object.taxonomy'))
        {
            $query = $em->createQuery('SELECT t.id, t.title, t.parent, t.enabled, t.enableAdd FROM BasicCmsBundle:Taxonomies t WHERE t.object = :object ORDER BY t.ordering')->setParameter('object', 'object.product');
            $tree = $query->getResult();
            $this->taxonomyGetTree($tree, null, 0, $categories);
        }
        // Валидация
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == 'POST')
        {
            $errors = false;
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
            if (isset($postpage['fileName'])) $page['fileName'] = $postpage['fileName'];
            if (isset($postpage['onlyOnStock'])) $page['onlyOnStock'] = intval($postpage['onlyOnStock']); else $page['onlyOnStock'] = 0;
            if (isset($postpage['currency'])) $page['currency'] = $postpage['currency'];
            if (isset($postpage['categories'])) $page['categories'] = $postpage['categories'];
            if (!is_array($page['categories'])) $page['categories'] = array();
            unset($postpage);
            if (!preg_match("/^[_A-Za-z0-9]{1,90}(\.[_A-Za-z0-9]{1,3})?$/ui", $page['fileName'])) {$errors = true; $pageerror['fileName'] = 'Имя долно содержать от 1 до 90 латинских букв, цифр или знаков подчёркивания имени и, через точку, от 1 до 3 символов расширения';} 
            $curfind = false;
            foreach ($currencies as $currency) if ($currency['id'] == $page['currency']) $curfind = true;
            if ($curfind == false) {$errors = true; $pageerror['currency'] = 'Валюта не найдена';} 
            foreach ($page['categories'] as $key=>$pagecat)
            {
                $catfind = false;
                foreach ($categories as $cat) if ($cat['id'] == $pagecat) $catfind = true;
                if ($catfind == false) unset($page['categories'][$key]);
            }
            if (count($page['categories']) == 0) {$errors = true; $pageerror['categories'] = 'Должна быть выбрана хотя бы одна категория';}
            if ($errors == false)
            {
                // Сохранение
                $pageent = new \Extended\ShopExportBundle\Entity\ProductExportPages();
                $pageent->setCategories(implode(',', $page['categories']));
                $pageent->setCurrencyId($page['currency']);
                $pageent->setFileName($page['fileName']);
                $pageent->setOnlyOnStock($page['onlyOnStock']);
                $em->persist($pageent);
                $em->flush();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой страницы экспорта',
                    'message'=>'Страница экспорта успешно создана',
                    'paths'=>array('Создать еще одну страницу экспорта'=>$this->get('router')->generate('extended_shop_export_one_create'),'Вернуться к экспорту товаров'=>$this->get('router')->generate('extended_shop_export'))
                ));
            }
        }
        // Вывод шаблона
        return $this->render('ExtendedShopExportBundle:Default:exportCreate.html.twig', array(
            'page' => $page,
            'pageerror' => $pageerror,
            'currencies' => $currencies,
            'categories' => $categories
            ));
    }
    
    public function exportOneEditAction()
    {
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $pageent = $this->getDoctrine()->getRepository('ExtendedShopExportBundle:ProductExportPages')->find($id);
        if (empty($pageent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы экспорта',
                'message'=>'Страница экспорта не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к экспорту товаров'=>$this->get('router')->generate('extended_shop_export'))
            ));
        }
        if ($this->getUser()->checkAccess('product_export') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы экспорта',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        // Данные
        $page = array();
        $pageerror = array();
        $page['fileName'] = $pageent->getFileName();
        $page['onlyOnStock'] = $pageent->getOnlyOnStock();
        $page['currency'] = $pageent->getCurrencyId();
        $page['categories'] = explode(',', $pageent->getCategories());
        if (!is_array($page['categories'])) $page['categories'] = array();
        $pageerror['fileName'] = '';
        $pageerror['onlyOnStock'] = '';
        $pageerror['currency'] = '';
        $pageerror['categories'] = '';
        // Считать валюты
        $query = $em->createQuery('SELECT c.id, c.name FROM ShopProductBundle:ProductCurrency c ORDER BY c.main DESC, c.id ASC');
        $currencies = $query->getResult();
        // Считать категории
        $categories = array();
        if ($this->container->has('object.taxonomy'))
        {
            $query = $em->createQuery('SELECT t.id, t.title, t.parent, t.enabled, t.enableAdd FROM BasicCmsBundle:Taxonomies t WHERE t.object = :object ORDER BY t.ordering')->setParameter('object', 'object.product');
            $tree = $query->getResult();
            $this->taxonomyGetTree($tree, null, 0, $categories);
        }
        // Валидация
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == 'POST')
        {
            $errors = false;
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
            if (isset($postpage['fileName'])) $page['fileName'] = $postpage['fileName'];
            if (isset($postpage['onlyOnStock'])) $page['onlyOnStock'] = intval($postpage['onlyOnStock']); else $page['onlyOnStock'] = 0;
            if (isset($postpage['currency'])) $page['currency'] = $postpage['currency'];
            if (isset($postpage['categories'])) $page['categories'] = $postpage['categories'];
            if (!is_array($page['categories'])) $page['categories'] = array();
            unset($postpage);
            if (!preg_match("/^[_A-Za-z0-9]{1,90}(\.[_A-Za-z0-9]{1,3})?$/ui", $page['fileName'])) {$errors = true; $pageerror['fileName'] = 'Имя долно содержать от 1 до 90 латинских букв, цифр или знаков подчёркивания имени и, через точку, от 1 до 3 символов расширения';} 
            $curfind = false;
            foreach ($currencies as $currency) if ($currency['id'] == $page['currency']) $curfind = true;
            if ($curfind == false) {$errors = true; $pageerror['currency'] = 'Валюта не найдена';} 
            foreach ($page['categories'] as $key=>$pagecat)
            {
                $catfind = false;
                foreach ($categories as $cat) if ($cat['id'] == $pagecat) $catfind = true;
                if ($catfind == false) unset($page['categories'][$key]);
            }
            if (count($page['categories']) == 0) {$errors = true; $pageerror['categories'] = 'Должна быть выбрана хотя бы одна категория';}
            if ($errors == false)
            {
                // Сохранение
                $pageent->setCategories(implode(',', $page['categories']));
                $pageent->setCurrencyId($page['currency']);
                $pageent->setFileName($page['fileName']);
                $pageent->setOnlyOnStock($page['onlyOnStock']);
                $em->flush();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование страницы экспорта',
                    'message'=>'Страница экспорта успешно отредактирована',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('extended_shop_export_one_edit').'?id='.$id, 'Вернуться к экспорту товаров'=>$this->get('router')->generate('extended_shop_export'))
                ));
            }
        }
        // Вывод шаблона
        return $this->render('ExtendedShopExportBundle:Default:exportEdit.html.twig', array(
            'id' => $id,
            'page' => $page,
            'pageerror' => $pageerror,
            'currencies' => $currencies,
            'categories' => $categories
            ));
    }

    private function taxonomyGetTree($tree, $id, $nesting, &$cats)
    {
        foreach ($tree as $cat)
        {
            if ($cat['parent'] == $id)
            {
                $cats[] = array('name' => $cat['title'], 'id' => $cat['id'], 'enableAdd' => $cat['enableAdd'], 'enabled' => $cat['enabled'], 'nesting' => $nesting, 'error' => '');
                $this->taxonomyGetTree($tree, $cat['id'], $nesting + 1, $cats);
            }
        }
    }

    public function exportOneAjaxAction()
    {
        if ($this->getUser()->checkAccess('product_export') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Экспорт товаров',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->get('request_stack')->getMasterRequest()->get('action');
        
        if ($action == 'delete')
        {
            $check = $this->get('request_stack')->getMasterRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $query = $em->createQuery('DELETE FROM ExtendedShopExportBundle:ProductExportPages e '.
                                              'WHERE e.id = :id')->setParameter('id', $key);
                    $query->execute();
                }
        }
        
        $query = $em->createQuery('SELECT e.id, e.fileName FROM ExtendedShopExportBundle:ProductExportPages e '.
                                  'ORDER BY e.id');
        $exportpages = $query->getResult();
        return $this->render('ExtendedShopExportBundle:Default:exportListTab1.html.twig', array(
            'exportpages' => $exportpages,
            'host' => $this->get('request_stack')->getMasterRequest()->getHttpHost()
            ));
    }
    
    public function exportOneGenerateAction()
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        $basepath = 'secured/shopexport/';
        $name = 'export1.xml';
        $namezip = 'export1.zip';
        $host = $this->get('request_stack')->getMasterRequest()->getHttpHost();
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $pageent = $this->getDoctrine()->getRepository('ExtendedShopExportBundle:ProductExportPages')->find($id);
        if (empty($pageent)) return new Response('', 404);
        $currency = $this->getDoctrine()->getRepository('ShopProductBundle:ProductCurrency')->find($pageent->getCurrencyId());
        if (empty($currency)) return new Response('');
        // Заголовок файла
        $f = fopen($basepath.$name, 'w');
        fwrite($f,
'       <?xml version="1.0" encoding="windows-1251"?>'."\r\n".
'	<?mso-application progid="Excel.Sheet"?>'."\r\n".
'	<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'."\r\n".
'		xmlns:o="urn:schemas-microsoft-com:office:office"'."\r\n".
'		xmlns:x="urn:schemas-microsoft-com:office:excel"'."\r\n".
'		xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"'."\r\n".
'		xmlns:html="http://www.w3.org/TR/REC-html40">'."\r\n".
'		<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">'."\r\n".
'			<Author>SHOP.BY</Author>'."\r\n".
'		</DocumentProperties><!-- СТИЛИ -->'."\r\n".
'	<Styles>'."\r\n".
'		<Style ss:ID="Default" ss:Name="Normal">'."\r\n".
'			<Alignment ss:Vertical="Bottom"/>'."\r\n".
'			<Font ss:FontName="Arial"/>'."\r\n".
'		</Style>'."\r\n".
'		<!-- Заголовок прайса -->'."\r\n".
'		<Style ss:ID="title">'."\r\n".
'			<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'."\r\n".
'			<Borders>'."\r\n".
'				<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" 	ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'			</Borders>'."\r\n".
'			<Interior ss:Color="#808080" ss:Pattern="Solid"/>'."\r\n".
'			<Font ss:Color="#FFFFFF" ss:Bold="1"/>'."\r\n".
'		</Style>'."\r\n".
'		<!-- разделяющая строка -->'."\r\n".
'		<Style ss:ID="line">'."\r\n".
'			<Alignment ss:Vertical="Bottom" ss:WrapText="1"/>'."\r\n".
'			<Borders>'."\r\n".
'				<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'			</Borders>'."\r\n".
'		</Style>'."\r\n".
'		<Style ss:ID="line_gray">'."\r\n".
'			<Alignment ss:Vertical="Bottom" ss:WrapText="1"/>'."\r\n".
'			<Borders>'."\r\n".
'				<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'			</Borders>'."\r\n".
'			<Font x:Family="Swiss" ss:Color="#808080"/>'."\r\n".
'		</Style>'."\r\n".
'		<!-- Заголовок поля -->'."\r\n".
'		<Style ss:ID="field_title">'."\r\n".
'			<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'."\r\n".
'			<Borders>'."\r\n".
'				<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'			</Borders>'."\r\n".
'			<Font ss:Bold="1"/>'."\r\n".
'			<Interior ss:Color="#C0C0C0" ss:Pattern="Solid"/>'."\r\n".
'		</Style>'."\r\n".
'		<!-- Нечетный -->'."\r\n".
'		<Style ss:ID="odd">'."\r\n".
'			<Alignment ss:Vertical="Top" ss:Indent="1" ss:Horizontal="Left" ss:WrapText="1"/>'."\r\n".
'			<Borders>'."\r\n".
'				<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'			</Borders>'."\r\n".
'		</Style>'."\r\n".
'		<Style ss:ID="odd_link" ss:Parent="odd">'."\r\n".
'			<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'."\r\n".
'			<Font ss:Color="#0000FF" ss:Underline="Single"/>'."\r\n".
'		</Style>'."\r\n".
'		<Style ss:ID="odd_link_gray" ss:Parent="odd">'."\r\n".
'			<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'."\r\n".
'			<Font ss:Color="#808080"/>'."\r\n".
'		</Style>'."\r\n".
'		<Style ss:ID="odd_price" ss:Parent="odd">'."\r\n".
'			<Alignment ss:Horizontal="Center" ss:Vertical="Top" ss:WrapText="1"/>'."\r\n".
'		</Style>'."\r\n".
'		<!-- Четный -->'."\r\n".
'		<Style ss:ID="even">'."\r\n".
'			<Alignment ss:Vertical="Top" ss:Indent="1" ss:Horizontal="Left"  ss:WrapText="1"/>'."\r\n".
'			<Borders>'."\r\n".
'				<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'			</Borders>'."\r\n".
'			<Interior ss:Color="#D0D0D0" ss:Pattern="Solid"/>'."\r\n".
'		</Style>'."\r\n".
'		<Style ss:ID="even_link" ss:Parent="even">'."\r\n".
'			<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'."\r\n".
'			<Font ss:Color="#0000FF" ss:Underline="Single"/>'."\r\n".
'		</Style>'."\r\n".
'		<Style ss:ID="even_link_gray" ss:Parent="even">'."\r\n".
'			<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>'."\r\n".
'			<Font ss:Color="#808080"/>'."\r\n".
'		</Style>'."\r\n".
'		<Style ss:ID="even_price" ss:Parent="even">'."\r\n".
'			<Alignment ss:Horizontal="Center" ss:Vertical="Top" ss:WrapText="1"/>'."\r\n".
'		</Style>'."\r\n".
'		<!-- Заголовок секции (уровень 0)-->'."\r\n".
'		<Style ss:ID="section_title_0">'."\r\n".
'			<Alignment ss:Vertical="Bottom" ss:WrapText="1"/>'."\r\n".
'			<Borders>'."\r\n".
'				<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'			</Borders>'."\r\n".
'			<Font ss:Size="14" ss:Color="#FF0000" ss:Bold="1"/>'."\r\n".
'		</Style>'."\r\n".
'		<!-- Заголовок секции (уровень 1)-->'."\r\n".
'		<Style ss:ID="section_title_1">'."\r\n".
'			<Alignment ss:Vertical="Bottom" ss:WrapText="1"/>'."\r\n".
'			<Borders>'."\r\n".
'				<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'			</Borders>'."\r\n".
'			<Font ss:Size="13" ss:Color="#FF0000" ss:Bold="1"/>'."\r\n".
'		</Style>'."\r\n".
'		<!-- Заголовок секции (уровень 2)-->'."\r\n".
'		<Style ss:ID="section_title_2">'."\r\n".
'			<Alignment ss:Vertical="Bottom" ss:WrapText="1"/>'."\r\n".
'			<Borders>'."\r\n".
'				<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'			</Borders>'."\r\n".
'			<Font ss:Size="12" ss:Color="#FF0000" ss:Bold="1"/>'."\r\n".
'		</Style>'."\r\n".
'		<!-- Заголовок секции (уровень 3)-->'."\r\n".
'		<Style ss:ID="section_title_3">'."\r\n".
'			<Alignment ss:Vertical="Bottom" ss:WrapText="1"/>'."\r\n".
'			<Borders>'."\r\n".
'				<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'			</Borders>'."\r\n".
'			<Font ss:Size="11" ss:Color="#FF0000" ss:Bold="1"/>'."\r\n".
'		</Style>'."\r\n".
'		<!-- Заголовок секции (уровень 4)-->'."\r\n".
'		<Style ss:ID="section_title_4">'."\r\n".
'			<Alignment ss:Vertical="Bottom" ss:WrapText="1"/>'."\r\n".
'			<Borders>'."\r\n".
'				<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'			</Borders>'."\r\n".
'			<Font ss:Size="10" ss:Color="#FF0000" ss:Bold="1"/>'."\r\n".
'		</Style>'."\r\n".
'		<!-- Заголовок секции (уровень 5)-->'."\r\n".
'		<Style ss:ID="section_title_5">'."\r\n".
'			<Alignment ss:Vertical="Bottom" ss:WrapText="1"/>'."\r\n".
'			<Borders>'."\r\n".
'				<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'			</Borders>'."\r\n".
'			<Font ss:Size="9" ss:Color="#FF0000" ss:Bold="1"/>'."\r\n".
'		</Style>'."\r\n".
'		<!-- Заголовок секции (уровень 6)-->'."\r\n".
'		<Style ss:ID="section_title_6">'."\r\n".
'			<Alignment ss:Vertical="Bottom" ss:WrapText="1"/>'."\r\n".
'			<Borders>'."\r\n".
'				<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'			</Borders>'."\r\n".
'			<Font ss:Size="9" ss:Color="#000000" ss:Bold="1"/>'."\r\n".
'		</Style>'."\r\n".
'		<!-- Заголовок типа -->'."\r\n".
'		<Style ss:ID="type_title">'."\r\n".
'			<Alignment ss:Vertical="Bottom" ss:WrapText="1"/>'."\r\n".
'			<Borders>'."\r\n".
'				<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'				<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>'."\r\n".
'			</Borders>'."\r\n".
'			<Font ss:Size="14" ss:Color="#FF0000" ss:Bold="1"/>'."\r\n".
'		</Style>'."\r\n".
'	</Styles><!-- РАБОЧАЯ ОБЛАСТЬ -->'."\r\n".
'	<Worksheet ss:Name="Прайс-лист 1">'."\r\n".
'		<!-- ПАРАМЕТРЫ РАБОЧЕЙ ОБЛАСТИ -->'."\r\n".
'		<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">'."\r\n".
'			<!-- Не отоброжать сетку -->'."\r\n".
'			<DoNotDisplayGridlines/>'."\r\n".
'			<FreezePanes/>'."\r\n".
'			<FrozenNoSplit/>'."\r\n".
'			<SplitHorizontal>4</SplitHorizontal>'."\r\n".
'			<TopRowBottomPane>4</TopRowBottomPane>'."\r\n".
'			<ActivePane>2</ActivePane>'."\r\n".
'		</WorksheetOptions>'."\r\n".
'		<Table ss:ExpandedColumnCount="5" x:FullColumns="1" x:FullRows="1"><Column ss:AutoFitWidth="0" ss:Width="180"/><Column ss:AutoFitWidth="0" ss:Width="180"/><Column ss:AutoFitWidth="0" ss:Width="40"/><Column ss:AutoFitWidth="0" ss:Width="180"/><Column ss:AutoFitWidth="0" ss:Width="180"/><Row ss:AutoFitHeight="0" ss:Height="22.5">'."\r\n".
'				<Cell ss:MergeAcross="4" ss:StyleID="title"><Data ss:Type="String">'.$pageent->getFileName().' '.$host.'</Data></Cell>'."\r\n".
'			</Row>'."\r\n".
'			<Row ss:AutoFitHeight="0">'."\r\n".
'				<Cell ss:MergeAcross="4" ss:StyleID="line"><Data ss:Type="String">Дата создания: '.date('Y-m-d').'</Data></Cell>'."\r\n".
'			</Row>'."\r\n".
'			<Row ss:AutoFitHeight="0">'."\r\n".
'				<Cell ss:MergeAcross="4" ss:StyleID="line"/>'."\r\n".
'			</Row><Row><Cell ss:StyleID="field_title"><Data ss:Type="String">Краткое название</Data></Cell><Cell ss:StyleID="field_title"><Data ss:Type="String">Краткое описание</Data></Cell><Cell ss:StyleID="field_title"><Data ss:Type="String">Цена ('.$currency->getName().')</Data></Cell><Cell ss:StyleID="field_title"><Data ss:Type="String">Ссылка на иконку</Data></Cell><Cell ss:StyleID="field_title"><Data ss:Type="String">Ссылка на страницу</Data></Cell></Row>                '."\r\n".
'');
        // Обход категорий
        $categories = explode(',', $pageent->getCategories());
        if (!is_array($categories)) $categories = array();
        foreach ($categories as $categoryid)
        {
            // Считываем вложенные категории если включена вложенность
             $query = $em->createQuery('SELECT t.title as title, t.parent, t.firstParent, t.object, t.pageParameters, t.filterParameters FROM BasicCmsBundle:Taxonomies t '.
                                       'WHERE t.id = :id AND t.enabled != 0 AND t.object = \'object.product\'')->setParameter('id', $categoryid);
             $taxcategory = $query->getResult();
             if (empty($taxcategory)) continue;
             if (isset($taxcategory[0])) $taxcategory = $taxcategory[0]; else continue;
             $pageParameters = @unserialize($taxcategory['pageParameters']);
             if (!is_array($pageParameters)) continue;
             $filterParameters = @unserialize($taxcategory['filterParameters']);
             if (!is_array($filterParameters)) continue;
             // Получение Get параметров
             $gets = array();
             foreach ($filterParameters as &$filter)
             {
                 if ($filter['getoff'] != 0) $filter['active'] = false; else $filter['active'] = true;
             }
             // Вычисляем перечень категорий для вывода
             $categoryIds = array($categoryid);
             if ($pageParameters['includeChilds'])
             {
                 $query = $em->createQuery('SELECT t.id, t.enabled, t.parent FROM BasicCmsBundle:Taxonomies t '.
                                           'WHERE t.firstParent = :id AND t.parent is not null '.
                                           'ORDER BY t.ordering')->setParameter('id', $taxcategory['firstParent']);
                 $tree = $query->getResult();
                 do
                 {
                     $count = 0;
                     foreach ($tree as $cat) if ((in_array($cat['parent'], $categoryIds)) && (!in_array($cat['id'], $categoryIds))) {$categoryIds[] = $cat['id'];$count++;}
                 } while ($count > 0);
             }
             if (($pageParameters['includeParent']) && ($taxcategory['parent'] != null)) $categoryIds[] = $taxcategory['parent'];
             // Пропускаем через обработчик объекта и получаем запрос
             $pageParameters['fields'] = array('title', 'description', 'price', 'avatar', 'url');
             if ($this->container->has($taxcategory['object'])) $query = $this->container->get($taxcategory['object'])->getTaxonomyQuery('', $pageParameters, $filterParameters, $categoryIds);
            // Находим товары пачками и записываем в файл
             fwrite($f,'<Row ss:AutoFitHeight="0"><Cell ss:MergeAcross="4" ss:StyleID="line"/></Row><Row ss:AutoFitHeight="0" ss:Height="18">'.
                       '<Cell ss:MergeAcross="4" ss:StyleID="section_title_1"><Data ss:Type="String"><![CDATA['.str_replace(array(']','['),'',$taxcategory['title']).']]></Data></Cell>'.
		       '</Row>'."\r\n");
             $page = 0;
             $productfind = false;
             $counter = 0;
             do
             {
                 $query->setFirstResult($page)->setMaxResults(500);
                 $items = $query->getResult();
                 if (!is_array($items)) $items = array();
                 foreach ($items as $product)
                 {
                     if ($counter == 0) {$counter = 1; $stylecell = 'odd';} else {$counter = 0; $stylecell = 'even';}
                     if ($product['avatar'] != '') $product['avatar'] = 'http://'.$host.$product['avatar'];
                     if ($product['url'] != '') $product['url'] = 'http://'.$host.'/'.$product['url'];
                     $productfind = true;
                     fwrite($f,'<Row ss:Height="75">'.
                               '<Cell ss:StyleID="'.$stylecell.'"><Data ss:Type="String"><![CDATA['.str_replace(array(']','['),'',$product['title']).']]></Data></Cell>'.
                               '<Cell ss:StyleID="'.$stylecell.'"><Data ss:Type="String"><![CDATA['.str_replace(array(']','['),'',$product['description']).']]></Data></Cell>'.
                               '<Cell ss:StyleID="'.$stylecell.'_price"><Data ss:Type="Number">'.round(($product['price'] * $currency->getExchangeIndex()) / $currency->getMinNote()) * $currency->getMinNote().'</Data></Cell>'.
                               '<Cell ss:StyleID="'.$stylecell.'_link" ss:HRef="'.$product['avatar'].'"><Data ss:Type="String">'.$product['avatar'].'</Data></Cell>'.
                               '<Cell ss:StyleID="'.$stylecell.'_link" ss:HRef="'.$product['url'].'"><Data ss:Type="String">'.$product['url'].'</Data></Cell>'.
                               '</Row>'."\r\n");
                 }
                 $page = $page + 500;
             } while (count($items) == 500);
             if ($productfind == false) fwrite($f,'<Row ss:AutoFitHeight="0">'.
                                                  '<Cell ss:MergeAcross="4" ss:StyleID="line_gray"><Data ss:Type="String">&lt;товары отсутствуют&gt;</Data></Cell>'.
                                                  '</Row>'."\r\n");
        }
        // Окончание файла
        fwrite($f,'</Table></Worksheet></Workbook>'."\r\n");
        fclose($f);
        // Преобразование в UTF-8 и архивация файла 
        @unlink($basepath.$namezip);
        $zip = new \ZipArchive(); 
        $zip->open($basepath.$namezip, \ZipArchive::CREATE); 
        $zip->addFromString($pageent->getFileName().'.xls', iconv('utf-8','windows-1251',file_get_contents($basepath.$name)));
        $zip->close();         
        // Вывод
        $resp = new Response();
        $resp->headers->set('Content-Type','application/octet-stream');
        $resp->headers->set('Last-Modified',gmdate('r', filemtime($basepath.$namezip)));
        $resp->headers->set('Content-Length',filesize($basepath.$namezip));
        $resp->headers->set('Connection','close');
        if (strpos($_SERVER['HTTP_USER_AGENT'],"MSIE" ) > 0 )
            $resp->headers->set('Content-Disposition','attachment; filename="' . rawurlencode(basename($pageent->getFileName().'.zip')) . '";' );
        else
            $resp->headers->set('Content-Disposition','attachment; filename*=UTF-8\'\'' . rawurlencode(basename($pageent->getFileName().'.zip')).';');
        $resp->setContent(file_get_contents($basepath.$namezip));
        return $resp;
    }
    
}
