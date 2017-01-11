<?php

namespace Shop\ProductBundle\Controller;

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
// Список продукции
// *******************************************    
    public function productListAction()
    {
        if ($this->getUser()->checkAccess('product_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Список продукции',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $tab = 0;
        /*$tab = intval($this->getRequest()->get('tab'));
        if ($tab < 0) $tab = 0;
        if ($tab > 0) $tab = 0;
        if ($tab === null) $tab = $this->getRequest()->getSession()->get('shop_product_list_tab');
                      else $this->getRequest()->getSession()->set('shop_product_list_tab', $tab);*/
        // Таб 1
        $page0 = $this->getRequest()->get('page0');
        $sort0 = $this->getRequest()->get('sort0');
        $search0 = $this->getRequest()->get('search0');
        $taxonomy0 = $this->getRequest()->get('taxonomy0');
        if ($page0 === null) $page0 = $this->getRequest()->getSession()->get('shop_product_list_page0');
                        else $this->getRequest()->getSession()->set('shop_product_list_page0', $page0);
        if ($sort0 === null) $sort0 = $this->getRequest()->getSession()->get('shop_product_list_sort0');
                        else $this->getRequest()->getSession()->set('shop_product_list_sort0', $sort0);
        if ($search0 === null) $search0 = $this->getRequest()->getSession()->get('shop_product_list_search0');
                          else $this->getRequest()->getSession()->set('shop_product_list_search0', $search0);
        if ($taxonomy0 === null) $taxonomy0 = $this->getRequest()->getSession()->get('shop_product_list_taxonomy0');
                          else $this->getRequest()->getSession()->set('shop_product_list_taxonomy0', $taxonomy0);
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        $taxonomy0 = intval($taxonomy0);
        // Поиск контента для 1 таба (текстовые страницы)
        $querytaxonomyleft0 = '';
        $querytaxonomywhere0 = '';
        if (($this->container->has('object.taxonomy')) && ($taxonomy0 != 0))
        {
            $querytaxonomyleft0 = ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks txl WITH txl.taxonomyId = '.$taxonomy0.' AND txl.itemId = p.id ';
            $querytaxonomywhere0 = ' AND txl.id IS NOT NULL ';
        }
        $query = $em->createQuery('SELECT count(p.id) as prodcount FROM ShopProductBundle:Products p '.$querytaxonomyleft0.
                                  'WHERE (p.title like :search OR p.article like :search) '.$querytaxonomywhere0)->setParameter('search', '%'.$search0.'%');
        $prodcount = $query->getResult();
        if (!empty($prodcount)) $prodcount = $prodcount[0]['prodcount']; else $prodcount = 0;
        $pagecount0 = ceil($prodcount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY p.title ASC';
        if ($sort0 == 1) $sortsql = 'ORDER BY p.title DESC';
        if ($sort0 == 2) $sortsql = 'ORDER BY p.enabled ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY p.enabled DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY p.createDate ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY p.createDate DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        if ($sort0 == 8) $sortsql = 'ORDER BY p.ordering ASC';
        if ($sort0 == 9) $sortsql = 'ORDER BY p.ordering DESC';
        if ($sort0 == 10) $sortsql = 'ORDER BY p.price ASC';
        if ($sort0 == 11) $sortsql = 'ORDER BY p.price DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT p.id, p.article, p.title, p.createDate, p.modifyDate, u.fullName, p.enabled, p.price, p.discount, p.onStock, p.ordering FROM ShopProductBundle:Products p '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.$querytaxonomyleft0.
                                  'WHERE (p.title like :search OR p.article like :search) '.$querytaxonomywhere0.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $products0 = $query->getResult();
        $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->findOneBy(array('main' => 1));
        foreach ($products0 as &$product0)
        {
            $product0['price'] = $product0['price'] * (1 - $product0['discount'] / 100);
            if (!empty($currency))
            {
                $product0['price'] = $currency->getPrefix().number_format($product0['price'], $currency->getDecimalSigns(), $currency->getDecimalSep(), $currency->getThousandSep()).$currency->getPostfix();
            }
        }
        unset($product0);
        if ($this->container->has('object.taxonomy'))
        {
            $taxonomyenabled = 1;
            $actionIds = array();
            foreach ($products0 as $product) $actionIds[] = $product['id'];
            $taxonomyinfo0 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.product', $actionIds);
        } else
        {
            $taxonomyenabled = 0;
            $taxonomyinfo0 = null;
        }
        return $this->render('ShopProductBundle:Default:productList.html.twig', array(
            'pagecount0' => $pagecount0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'products0' => $products0,
            'taxonomy0' => $taxonomy0,
            'taxonomyenabled' => $taxonomyenabled,
            'taxonomyinfo0' => $taxonomyinfo0,
            'tab' => $tab
        ));
        
    }
    
// *******************************************
// AJAX загрузка аватара
// *******************************************    

    public function productAjaxAvatarAction() 
    {
        $userId = $this->getUser()->getId();
        $file = $this->getRequest()->files->get('avatar');
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
            $basepath = '/images/product/';
            $name = $this->getUser()->getId().'_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
            if (move_uploaded_file($tmpfile, '..'.$basepath.$name)) 
            {
                $this->container->get('cms.cmsManager')->registerTemporaryFile($basepath.$name, $file->getClientOriginalName());
                return new Response(json_encode(array('file' => $basepath.$name, 'error' => '')));
            }
        }
        return new Response(json_encode(array('file' => '', 'error' => 'Неправильный формат файла')));
    }
    
    
// *******************************************
// Создание нового продукта
// *******************************************    
    public function productCreateAction()
    {
        if ($this->getUser()->checkAccess('product_new') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание нового продукта',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $prod = array();
        $proderror = array();
        
        $prod['article'] = '';
        $prod['title'] = '';
        $prod['description'] = '';
        $prod['avatar'] = '';
        $prod['enabled'] = 1;
        $prod['metadescr'] = '';
        $prod['metakey'] = '';
        $prod['price'] = '';
        $prod['discount'] = '';
        $prod['rating'] = '';
        $prod['height'] = '';
        $prod['width'] = '';
        $prod['depth'] = '';
        $prod['weight'] = '';
        $prod['manufacturer'] = '';
        $prod['onStock'] = '';
        $proderror['article'] = '';
        $proderror['title'] = '';
        $proderror['description'] = '';
        $proderror['avatar'] = '';
        $proderror['enabled'] = '';
        $proderror['metadescr'] = '';
        $proderror['metakey'] = '';
        $proderror['price'] = '';
        $proderror['discount'] = '';
        $proderror['rating'] = '';
        $proderror['height'] = '';
        $proderror['width'] = '';
        $proderror['depth'] = '';
        $proderror['weight'] = '';
        $proderror['manufacturer'] = '';
        $proderror['onStock'] = '';
        
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
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.product','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.product.view');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        $prodloc = array();
        $prodlocerror = array();
        foreach ($locales as $locale)
        {
            $prodloc[$locale['shortName']]['title'] = '';
            $prodloc[$locale['shortName']]['description'] = '';
            $prodloc[$locale['shortName']]['metadescr'] = '';
            $prodloc[$locale['shortName']]['metakey'] = '';
            $prodlocerror[$locale['shortName']]['title'] = '';
            $prodlocerror[$locale['shortName']]['description'] = '';
            $prodlocerror[$locale['shortName']]['metadescr'] = '';
            $prodlocerror[$locale['shortName']]['metakey'] = '';
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postprod = $this->getRequest()->get('prod');
            if (isset($postprod['article'])) $prod['article'] = $postprod['article'];
            if (isset($postprod['title'])) $prod['title'] = $postprod['title'];
            if (isset($postprod['description'])) $prod['description'] = $postprod['description'];
            if (isset($postprod['avatar'])) $prod['avatar'] = $postprod['avatar'];
            if (isset($postprod['enabled'])) $prod['enabled'] = intval($postprod['enabled']); else $prod['enabled'] = 0;
            if (isset($postprod['metadescr'])) $prod['metadescr'] = $postprod['metadescr'];
            if (isset($postprod['metakey'])) $prod['metakey'] = $postprod['metakey'];
            
            if (isset($postprod['price'])) $prod['price'] = $postprod['price'];
            if (isset($postprod['discount'])) $prod['discount'] = $postprod['discount'];
            if (isset($postprod['rating'])) $prod['rating'] = $postprod['rating'];
            if (isset($postprod['height'])) $prod['height'] = $postprod['height'];
            if (isset($postprod['width'])) $prod['width'] = $postprod['width'];
            if (isset($postprod['depth'])) $prod['depth'] = $postprod['depth'];
            if (isset($postprod['weight'])) $prod['weight'] = $postprod['weight'];
            if (isset($postprod['manufacturer'])) $prod['manufacturer'] = $postprod['manufacturer'];
            if (isset($postprod['onStock'])) $prod['onStock'] = intval($postprod['onStock']); else $prod['onStock'] = 0;
            
            unset($postprod);
            if (!preg_match("/^.{0,255}$/ui", $prod['article'])) {$errors = true; $proderror['article'] = 'Артикул должен содержать до 255 символов';}
            if (!preg_match("/^.{3,255}$/ui", $prod['title'])) {$errors = true; $proderror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (($prod['avatar'] != '') && (!file_exists('..'.$prod['avatar']))) {$errors = true; $proderror['avatar'] = 'Файл не найден';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $prod['metadescr'])) {$errors = true; $proderror['metadescr'] = 'Использованы недопустимые символы';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $prod['metakey'])) {$errors = true; $proderror['metakey'] = 'Использованы недопустимые символы';}
            
            if (!preg_match("/^\d+((\,|\.)(\d+))?$/ui", $prod['price'])) {$errors = true; $proderror['price'] = 'Должно быть указано положительное число';}
            if ((!preg_match("/^(\-?\d+((\,|\.)(\d+))?)?$/ui", $prod['discount'])) || (floatval($prod['discount']) < -100) || (floatval($prod['discount']) > 100)) {$errors = true; $proderror['discount'] = 'Должно быть указано число от -100 до 100 процентов';}
            if ((!preg_match("/^(\d+)?$/ui", $prod['rating'])) || (floatval($prod['rating']) < 0) || (floatval($prod['rating']) > 100)) {$errors = true; $proderror['rating'] = 'Должно быть указано число от 0 до 100 либо оставлена пустая строка';}
            if (!preg_match("/^(\d+((\,|\.)(\d+))?)?$/ui", $prod['height'])) {$errors = true; $proderror['height'] = 'Должно быть указано положительное число либо оставлена пустая строка';}
            if (!preg_match("/^(\d+((\,|\.)(\d+))?)?$/ui", $prod['width'])) {$errors = true; $proderror['width'] = 'Должно быть указано положительное число либо оставлена пустая строка';}
            if (!preg_match("/^(\d+((\,|\.)(\d+))?)?$/ui", $prod['depth'])) {$errors = true; $proderror['depth'] = 'Должно быть указано положительное число либо оставлена пустая строка';}
            if (!preg_match("/^(\d+((\,|\.)(\d+))?)?$/ui", $prod['weight'])) {$errors = true; $proderror['weight'] = 'Должно быть указано положительное число либо оставлена пустая строка';}
            if (!preg_match("/^.{0,255}$/ui", $prod['manufacturer'])) {$errors = true; $proderror['manufacturer'] = 'Поле должно содержать до 255 символов';}
            
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->getRequest()->get('page');
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
            // Валидация локлизации
            $postprodloc = $this->getRequest()->get('prodloc');
            foreach ($locales as $locale)
            {
                if (isset($postprodloc[$locale['shortName']]['title'])) $prodloc[$locale['shortName']]['title'] = $postprodloc[$locale['shortName']]['title'];
                if (isset($postprodloc[$locale['shortName']]['description'])) $prodloc[$locale['shortName']]['description'] = $postprodloc[$locale['shortName']]['description'];
                if (isset($postprodloc[$locale['shortName']]['content'])) $prodloc[$locale['shortName']]['content'] = $postprodloc[$locale['shortName']]['content'];
                if (isset($postprodloc[$locale['shortName']]['metadescr'])) $prodloc[$locale['shortName']]['metadescr'] = $postprodloc[$locale['shortName']]['metadescr'];
                if (isset($postprodloc[$locale['shortName']]['metakey'])) $prodloc[$locale['shortName']]['metakey'] = $postprodloc[$locale['shortName']]['metakey'];
                if (($prodloc[$locale['shortName']]['title'] != '') && (!preg_match("/^.{3,}$/ui", $prodloc[$locale['shortName']]['title']))) {$errors = true; $prodlocerror[$locale['shortName']]['title'] = 'Заголовок должен содержать более 3 символов';}
                if (($prodloc[$locale['shortName']]['metadescr'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $prodloc[$locale['shortName']]['metadescr']))) {$errors = true; $prodlocerror[$locale['shortName']]['metadescr'] = 'Использованы недопустимые символы';}
                if (($prodloc[$locale['shortName']]['metakey'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $prodloc[$locale['shortName']]['metakey']))) {$errors = true; $prodlocerror[$locale['shortName']]['metakey'] = 'Использованы недопустимые символы';}
            }
            unset($postprodloc);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            if ($this->container->has('object.taxonomy')) 
            {
                $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'create', 'object.product', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }
            foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'productCreate', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($prod['avatar']);
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($prod['description'], '');
                $query = $em->createQuery('SELECT max(p.ordering) as maxorder FROM ShopProductBundle:Products p');
                $order = $query->getResult();
                if (isset($order[0]['maxorder'])) $order = $order[0]['maxorder'] + 1; else $order = 1;
                $productent = new \Shop\ProductBundle\Entity\Products();
                $productent->setArticle($prod['article']);
                $productent->setAvatar($prod['avatar']);
                $productent->setCreateDate(new \DateTime('now'));
                $productent->setCreaterId($this->getUser()->getId());
                $productent->setDescription($prod['description']);
                $productent->setEnabled($prod['enabled']);
                $productent->setMetaDescription($prod['metadescr']);
                $productent->setMetaKeywords($prod['metakey']);
                $productent->setModifyDate(new \DateTime('now'));
                $productent->setOrdering($order);
                $productent->setTitle($prod['title']);
                $productent->setPrice(str_replace(',','.',$prod['price']));
                $productent->setDiscount(($prod['discount'] != '') ? str_replace(',','.',$prod['discount']) : null);
                $productent->setRating(($prod['rating'] != '') ? $prod['rating'] : null);
                $productent->setHeight(($prod['height'] != '') ? str_replace(',','.',$prod['height']) : null);
                $productent->setWidth(($prod['width'] != '') ? str_replace(',','.',$prod['width']) : null);
                $productent->setDepth(($prod['depth'] != '') ? str_replace(',','.',$prod['depth']) : null);
                $productent->setWeight(($prod['weight'] != '') ? str_replace(',','.',$prod['weight']) : null);
                $productent->setManufacturer($prod['manufacturer']);
                $productent->setOnStock($prod['onStock']);
                $em->persist($productent);
                $em->flush();
                if ($page['enable'] != 0)
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $pageent->setBreadCrumbs('');
                    $pageent->setBreadCrumbsRel('');
                    $pageent->setDescription('Просмотр продукта '.$productent->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.product');
                    $pageent->setContentId($productent->getId());
                    $pageent->setContentAction('view');
                    $productent->setTemplate($page['template']);
                    if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                    $em->persist($pageent);
                    $em->flush();
                    if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                }
                foreach ($locales as $locale)
                {
                    if (($prodloc[$locale['shortName']]['title'] != '') ||
                        ($prodloc[$locale['shortName']]['description'] != '') ||
                        ($prodloc[$locale['shortName']]['metadescr'] != '') ||
                        ($prodloc[$locale['shortName']]['metakey'] != ''))
                    {
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($prodloc[$locale['shortName']]['description'], '');
                        $prodlocent = new \Shop\ProductBundle\Entity\ProductsLocale();
                        $prodlocent->setProductId($productent->getId());
                        $prodlocent->setLocale($locale['shortName']);
                        if ($prodloc[$locale['shortName']]['title'] == '') $prodlocent->setTitle(null); else $prodlocent->setTitle($prodloc[$locale['shortName']]['title']);
                        if ($prodloc[$locale['shortName']]['description'] == '') $prodlocent->setDescription(null); else $prodlocent->setDescription($prodloc[$locale['shortName']]['description']);
                        if ($prodloc[$locale['shortName']]['metadescr'] == '') $prodlocent->setMetaDescription(null); else $prodlocent->setMetaDescription($prodloc[$locale['shortName']]['metadescr']);
                        if ($prodloc[$locale['shortName']]['metakey'] == '') $prodlocent->setMetaKeywords(null); else $prodlocent->setMetaKeywords($prodloc[$locale['shortName']]['metakey']);
                        $em->persist($prodlocent);
                        $em->flush();
                        unset($prodlocent);
                    }
                }
                $i = 0;
                if ($this->container->has('object.taxonomy')) 
                {
                    $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'create', 'object.product', $productent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }
                foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'productCreate', $productent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание нового продукта',
                    'message'=>'Продукт успешно создан',
                    'paths'=>array('Создать еще один продукт'=>$this->get('router')->generate('shop_product_create'),'Вернуться к списку продукции'=>$this->get('router')->generate('shop_product_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        if ($this->container->has('object.taxonomy')) $tabs[] =  array('name'=>'Категории классификации','content'=>$this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'create', 'object.product', 0, 'tab'));
        foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'productCreate', 0, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('ShopProductBundle:Default:productCreate.html.twig', array(
            'prod' => $prod,
            'proderror' => $proderror,
            'page' => $page,
            'pageerror' => $pageerror,
            'prodloc' => $prodloc,
            'prodlocerror' => $prodlocerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'roles' => $roles,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
    }

// *******************************************
// Редактирование продукта
// *******************************************    
    public function productEditAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $productent = $this->getDoctrine()->getRepository('ShopProductBundle:Products')->find($id);
        if (empty($productent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование продукта',
                'message'=>'Продукт не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к списку продуктов'=>$this->get('router')->generate('shop_product_list'))
            ));
        }
        if (($this->getUser()->checkAccess('product_viewall') == 0) && ($this->getUser()->getId() != $productent->getCreaterId()))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование продукта',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        if (($this->getUser()->checkAccess('product_viewown') == 0) && ($this->getUser()->getId() == $productent->getCreaterId()))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование продукта',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.product','contentId'=>$id,'contentAction'=>'view'));
        
        $prod = array();
        $proderror = array();
        
        $prod['article'] = $productent->getArticle();
        $prod['title'] = $productent->getTitle();
        $prod['description'] = $productent->getDescription();
        $prod['avatar'] = $productent->getAvatar();
        $prod['enabled'] = $productent->getEnabled();
        $prod['metadescr'] = $productent->getMetaDescription();
        $prod['metakey'] = $productent->getMetaKeywords();
        $prod['price'] = $productent->getPrice();
        $prod['discount'] = $productent->getDiscount();
        $prod['rating'] = $productent->getRating();
        $prod['height'] = $productent->getHeight();
        $prod['width'] = $productent->getWidth();
        $prod['depth'] = $productent->getDepth();
        $prod['weight'] = $productent->getWeight();
        $prod['manufacturer'] = $productent->getManufacturer();
        $prod['onStock'] = $productent->getOnStock();
        $proderror['article'] = '';
        $proderror['title'] = '';
        $proderror['description'] = '';
        $proderror['avatar'] = '';
        $proderror['enabled'] = '';
        $proderror['metadescr'] = '';
        $proderror['metakey'] = '';
        $proderror['price'] = '';
        $proderror['discount'] = '';
        $proderror['rating'] = '';
        $proderror['height'] = '';
        $proderror['width'] = '';
        $proderror['depth'] = '';
        $proderror['weight'] = '';
        $proderror['manufacturer'] = '';
        $proderror['onStock'] = '';
        
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
            $page['template'] = $productent->getTemplate();
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
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.product','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.product.view');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        $prodloc = array();
        $prodlocerror = array();
        foreach ($locales as $locale)
        {
            $prodlocent = $this->getDoctrine()->getRepository('ShopProductBundle:ProductsLocale')->findOneBy(array('locale'=>$locale['shortName'],'productId'=>$id));
            if (!empty($prodlocent))
            {
                $prodloc[$locale['shortName']]['title'] = $prodlocent->getTitle();
                $prodloc[$locale['shortName']]['description'] = $prodlocent->getDescription();
                $prodloc[$locale['shortName']]['metadescr'] = $prodlocent->getMetaDescription();
                $prodloc[$locale['shortName']]['metakey'] = $prodlocent->getMetaKeywords();
                unset($prodlocent);
            } else 
            {
                $prodloc[$locale['shortName']]['title'] = '';
                $prodloc[$locale['shortName']]['description'] = '';
                $prodloc[$locale['shortName']]['metadescr'] = '';
                $prodloc[$locale['shortName']]['metakey'] = '';
            }
            $prodlocerror[$locale['shortName']]['title'] = '';
            $prodlocerror[$locale['shortName']]['description'] = '';
            $prodlocerror[$locale['shortName']]['metadescr'] = '';
            $prodlocerror[$locale['shortName']]['metakey'] = '';
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            if (($this->getUser()->checkAccess('product_editall') == 0) && ($this->getUser()->getId() != $productent->getCreaterId()))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование продукта',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            if (($this->getUser()->checkAccess('product_editown') == 0) && ($this->getUser()->getId() == $productent->getCreaterId()))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование продукта',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            // Проверка основных данных
            $postprod = $this->getRequest()->get('prod');
            if (isset($postprod['article'])) $prod['article'] = $postprod['article'];
            if (isset($postprod['title'])) $prod['title'] = $postprod['title'];
            if (isset($postprod['description'])) $prod['description'] = $postprod['description'];
            if (isset($postprod['avatar'])) $prod['avatar'] = $postprod['avatar'];
            if (isset($postprod['enabled'])) $prod['enabled'] = intval($postprod['enabled']); else $prod['enabled'] = 0;
            if (isset($postprod['metadescr'])) $prod['metadescr'] = $postprod['metadescr'];
            if (isset($postprod['metakey'])) $prod['metakey'] = $postprod['metakey'];
            
            if (isset($postprod['price'])) $prod['price'] = $postprod['price'];
            if (isset($postprod['discount'])) $prod['discount'] = $postprod['discount'];
            if (isset($postprod['rating'])) $prod['rating'] = $postprod['rating'];
            if (isset($postprod['height'])) $prod['height'] = $postprod['height'];
            if (isset($postprod['width'])) $prod['width'] = $postprod['width'];
            if (isset($postprod['depth'])) $prod['depth'] = $postprod['depth'];
            if (isset($postprod['weight'])) $prod['weight'] = $postprod['weight'];
            if (isset($postprod['manufacturer'])) $prod['manufacturer'] = $postprod['manufacturer'];
            if (isset($postprod['onStock'])) $prod['onStock'] = intval($postprod['onStock']); else $prod['onStock'] = 0;
            
            unset($postprod);
            if (!preg_match("/^.{0,255}$/ui", $prod['article'])) {$errors = true; $proderror['article'] = 'Артикул должен содержать до 255 символов';}
            if (!preg_match("/^.{3,255}$/ui", $prod['title'])) {$errors = true; $proderror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (($prod['avatar'] != '') && (!file_exists('..'.$prod['avatar']))) {$errors = true; $proderror['avatar'] = 'Файл не найден';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $prod['metadescr'])) {$errors = true; $proderror['metadescr'] = 'Использованы недопустимые символы';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $prod['metakey'])) {$errors = true; $proderror['metakey'] = 'Использованы недопустимые символы';}
            
            if (!preg_match("/^\d+((\,|\.)(\d+))?$/ui", $prod['price'])) {$errors = true; $proderror['price'] = 'Должно быть указано положительное число';}
            if ((!preg_match("/^(\-?\d+((\,|\.)(\d+))?)?$/ui", $prod['discount'])) || (floatval($prod['discount']) < -100) || (floatval($prod['discount']) > 100)) {$errors = true; $proderror['discount'] = 'Должно быть указано число от -100 до 100 процентов';}
            if ((!preg_match("/^(\d+)?$/ui", $prod['rating'])) || (floatval($prod['rating']) < 0) || (floatval($prod['rating']) > 100)) {$errors = true; $proderror['rating'] = 'Должно быть указано число от 0 до 100 либо оставлена пустая строка';}
            if (!preg_match("/^(\d+((\,|\.)(\d+))?)?$/ui", $prod['height'])) {$errors = true; $proderror['height'] = 'Должно быть указано положительное число либо оставлена пустая строка';}
            if (!preg_match("/^(\d+((\,|\.)(\d+))?)?$/ui", $prod['width'])) {$errors = true; $proderror['width'] = 'Должно быть указано положительное число либо оставлена пустая строка';}
            if (!preg_match("/^(\d+((\,|\.)(\d+))?)?$/ui", $prod['depth'])) {$errors = true; $proderror['depth'] = 'Должно быть указано положительное число либо оставлена пустая строка';}
            if (!preg_match("/^(\d+((\,|\.)(\d+))?)?$/ui", $prod['weight'])) {$errors = true; $proderror['weight'] = 'Должно быть указано положительное число либо оставлена пустая строка';}
            if (!preg_match("/^.{0,255}$/ui", $prod['manufacturer'])) {$errors = true; $proderror['manufacturer'] = 'Поле должно содержать до 255 символов';}
            
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->getRequest()->get('page');
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
            // Валидация локлизации
            $postprodloc = $this->getRequest()->get('prodloc');
            foreach ($locales as $locale)
            {
                if (isset($postprodloc[$locale['shortName']]['title'])) $prodloc[$locale['shortName']]['title'] = $postprodloc[$locale['shortName']]['title'];
                if (isset($postprodloc[$locale['shortName']]['description'])) $prodloc[$locale['shortName']]['description'] = $postprodloc[$locale['shortName']]['description'];
                if (isset($postprodloc[$locale['shortName']]['content'])) $prodloc[$locale['shortName']]['content'] = $postprodloc[$locale['shortName']]['content'];
                if (isset($postprodloc[$locale['shortName']]['metadescr'])) $prodloc[$locale['shortName']]['metadescr'] = $postprodloc[$locale['shortName']]['metadescr'];
                if (isset($postprodloc[$locale['shortName']]['metakey'])) $prodloc[$locale['shortName']]['metakey'] = $postprodloc[$locale['shortName']]['metakey'];
                if (($prodloc[$locale['shortName']]['title'] != '') && (!preg_match("/^.{3,}$/ui", $prodloc[$locale['shortName']]['title']))) {$errors = true; $prodlocerror[$locale['shortName']]['title'] = 'Заголовок должен содержать более 3 символов';}
                if (($prodloc[$locale['shortName']]['metadescr'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $prodloc[$locale['shortName']]['metadescr']))) {$errors = true; $prodlocerror[$locale['shortName']]['metadescr'] = 'Использованы недопустимые символы';}
                if (($prodloc[$locale['shortName']]['metakey'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $prodloc[$locale['shortName']]['metakey']))) {$errors = true; $prodlocerror[$locale['shortName']]['metakey'] = 'Использованы недопустимые символы';}
            }
            unset($postprodloc);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            if ($this->container->has('object.taxonomy')) 
            {
                $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'edit', 'object.product', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }
            foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'productEdit', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                if (($productent->getAvatar() != '') && ($productent->getAvatar() != $prod['avatar'])) @unlink('..'.$productent->getAvatar());
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($prod['avatar']);
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($prod['description'], $productent->getDescription());
                $productent->setArticle($prod['article']);
                $productent->setAvatar($prod['avatar']);
                $productent->setDescription($prod['description']);
                $productent->setEnabled($prod['enabled']);
                $productent->setMetaDescription($prod['metadescr']);
                $productent->setMetaKeywords($prod['metakey']);
                $productent->setModifyDate(new \DateTime('now'));
                $productent->setTitle($prod['title']);
                $productent->setPrice(str_replace(',','.',$prod['price']));
                $productent->setDiscount(($prod['discount'] != '') ? str_replace(',','.',$prod['discount']) : null);
                $productent->setRating(($prod['rating'] != '') ? $prod['rating'] : null);
                $productent->setHeight(($prod['height'] != '') ? str_replace(',','.',$prod['height']) : null);
                $productent->setWidth(($prod['width'] != '') ? str_replace(',','.',$prod['width']) : null);
                $productent->setDepth(($prod['depth'] != '') ? str_replace(',','.',$prod['depth']) : null);
                $productent->setWeight(($prod['weight'] != '') ? str_replace(',','.',$prod['weight']) : null);
                $productent->setManufacturer($prod['manufacturer']);
                $productent->setOnStock($prod['onStock']);
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
                    $pageent->setDescription('Просмотр продукта '.$productent->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.product');
                    $pageent->setContentId($productent->getId());
                    $pageent->setContentAction('view');
                    $productent->setTemplate($page['template']);
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
                foreach ($locales as $locale)
                {
                    $prodlocent = $this->getDoctrine()->getRepository('ShopProductBundle:ProductsLocale')->findOneBy(array('locale'=>$locale['shortName'],'productId'=>$id));
                    if (($prodloc[$locale['shortName']]['title'] != '') ||
                        ($prodloc[$locale['shortName']]['description'] != '') ||
                        ($prodloc[$locale['shortName']]['metadescr'] != '') ||
                        ($prodloc[$locale['shortName']]['metakey'] != ''))
                    {
                        if (empty($prodlocent))
                        {
                            $prodlocent = new \Shop\ProductBundle\Entity\ProductsLocale();
                            $em->persist($prodlocent);
                        }
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($prodloc[$locale['shortName']]['description'], $prodlocent->getDescription());
                        $prodlocent->setProductId($productent->getId());
                        $prodlocent->setLocale($locale['shortName']);
                        if ($prodloc[$locale['shortName']]['title'] == '') $prodlocent->setTitle(null); else $prodlocent->setTitle($prodloc[$locale['shortName']]['title']);
                        if ($prodloc[$locale['shortName']]['description'] == '') $prodlocent->setDescription(null); else $prodlocent->setDescription($prodloc[$locale['shortName']]['description']);
                        if ($prodloc[$locale['shortName']]['metadescr'] == '') $prodlocent->setMetaDescription(null); else $prodlocent->setMetaDescription($prodloc[$locale['shortName']]['metadescr']);
                        if ($prodloc[$locale['shortName']]['metakey'] == '') $prodlocent->setMetaKeywords(null); else $prodlocent->setMetaKeywords($prodloc[$locale['shortName']]['metakey']);
                        $em->flush();
                        unset($prodlocent);
                    } else
                    {
                        if (!empty($prodlocent))
                        {
                            $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $prodlocent->getDescription());
                            $em->remove($prodlocent);
                            $em->flush();
                        }
                    }
                }
                $i = 0;
                if ($this->container->has('object.taxonomy')) 
                {
                    $localerror = $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'edit', 'object.product', $productent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }
                foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'productEdit', $productent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование продукта',
                    'message'=>'Данные продукта успешно сохранены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('shop_product_edit').'?id='.$id, 'Вернуться к списку продукции'=>$this->get('router')->generate('shop_product_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        if ($this->container->has('object.taxonomy')) $tabs[] =  array('name'=>'Категории классификации','content'=>$this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'edit', 'object.product', $id, 'tab'));
        foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'productEdit', $id, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('ShopProductBundle:Default:productEdit.html.twig', array(
            'id' => $id,
            'createrId' => $productent->getCreaterId(),
            'prod' => $prod,
            'proderror' => $proderror,
            'page' => $page,
            'pageerror' => $pageerror,
            'prodloc' => $prodloc,
            'prodlocerror' => $prodlocerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'roles' => $roles,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// AJAX-контроллер
// *******************************************    
    
    public function productAjaxAction()
    {
        $tab = intval($this->getRequest()->get('tab'));
        if ($this->getUser()->checkAccess('product_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Список продукции',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->getRequest()->get('action');
        $errors = array();
        $errorsorder = array();
        if ($action == 'delete')
        {
            if ($this->getUser()->checkAccess('product_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Список продукции',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $cmsservices = $this->container->getServiceIds();
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $productent = $this->getDoctrine()->getRepository('ShopProductBundle:Products')->find($key);
                    if (!empty($productent))
                    {
                        if ($productent->getAvatar() != '') @unlink('..'.$productent->getAvatar());
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $productent->getDescription());
                        $em->remove($productent);
                        $em->flush();
                        $query = $em->createQuery('SELECT tl.description FROM ShopProductBundle:ProductsLocale tl WHERE tl.productId = :id')->setParameter('id', $key);
                        $delpages = $query->getResult();
                        if (is_array($delpages))
                        {
                            foreach ($delpages as $delpage)
                            {
                                if ($delpage['description'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $delpage['description']);
                            }
                        }
                        $query = $em->createQuery('DELETE FROM ShopProductBundle:ProductsLocale tl WHERE tl.productId = :id')->setParameter('id', $key);
                        $query->execute();
                        $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.product\' AND p.contentAction = \'view\' AND p.contentId = :id')->setParameter('id', $key);
                        $query->execute();
                        if ($this->container->has('object.taxonomy')) $this->container->get('object.taxonomy')->getTaxonomyController($this->getRequest(), 'delete', 'object.product', $key, 'save');
                        foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
                        {
                            $serv = $this->container->get($item);
                            $serv->getAdminController($this->getRequest(), 'productDelete', $key, 'save');
                        }       
                        unset($productent);    
                    }
                }
        }
        if ($action == 'blocked')
        {
            if ($this->getUser()->checkAccess('product_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Список продукции',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $productent = $this->getDoctrine()->getRepository('ShopProductBundle:Products')->find($key);
                    if (!empty($productent))
                    {
                        $productent->setEnabled(0);
                        $em->flush();
                        unset($productent);    
                    }
                }
        }
        if ($action == 'unblocked')
        {
            if ($this->getUser()->checkAccess('product_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Список продукции',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $productent = $this->getDoctrine()->getRepository('ShopProductBundle:Products')->find($key);
                    if (!empty($productent))
                    {
                        $productent->setEnabled(1);
                        $em->flush();
                        unset($productent);    
                    }
                }
        }
        if ($action == 'ordering')
        {
            if ($this->getUser()->checkAccess('product_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Список продукции',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $ordering = $this->getRequest()->get('ordering');
            $error = false;
            $ids = array();
            foreach ($ordering as $key=>$val) $ids[] = $key;
            foreach ($ordering as $key=>$val)
            {
                if (!preg_match("/^\d{1,20}$/ui", $val)) {$error = true; $errorsorder[$key] = 'Введено не число';} else
                {
                    foreach ($ordering as $key2=>$val2) if (($key != $key2) && ($val == $val2)) {$error = true; $errorsorder[$key] = 'Введено два одинаковых числа';} else
                    {
                        $query = $em->createQuery('SELECT count(t.id) as checkid FROM ShopProductBundle:Products t '.
                                                  'WHERE t.ordering = :order AND t.id NOT IN (:ids)')->setParameter('ids', $ids)->setParameter('order', $val);
                        $checkid = $query->getResult();
                        if (isset($checkid[0]['checkid'])) $checkid = $checkid[0]['checkid']; else $checkid = 0;
                        if ($checkid != 0) {$error = true; $errorsorder[$key] = 'Число уже используется';}
                    }
                }
            }
            // изменить порядок
            if ($error == false)
            {
                foreach ($ordering as $key=>$val)
                {
                    $productent = $this->getDoctrine()->getRepository('ShopProductBundle:Products')->find($key);
                    $productent->setOrdering($val);
                }
                $em->flush();
            }
        }

        $page0 = $this->getRequest()->getSession()->get('shop_product_list_page0');
        $sort0 = $this->getRequest()->getSession()->get('shop_product_list_sort0');
        $search0 = $this->getRequest()->getSession()->get('shop_product_list_search0');
        $taxonomy0 = $this->getRequest()->getSession()->get('shop_product_list_taxonomy0');
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        $taxonomy0 = intval($taxonomy0);
        // Поиск контента для 1 таба (текстовые страницы)
        $querytaxonomyleft0 = '';
        $querytaxonomywhere0 = '';
        if (($this->container->has('object.taxonomy')) && ($taxonomy0 != 0))
        {
            $querytaxonomyleft0 = ' LEFT JOIN BasicCmsBundle:TaxonomiesLinks txl WITH txl.taxonomyId = '.$taxonomy0.' AND txl.itemId = p.id ';
            $querytaxonomywhere0 = ' AND txl.id IS NOT NULL ';
        }
        $query = $em->createQuery('SELECT count(p.id) as prodcount FROM ShopProductBundle:Products p '.$querytaxonomyleft0.
                                  'WHERE (p.title like :search OR p.article like :search) '.$querytaxonomywhere0)->setParameter('search', '%'.$search0.'%');
        $prodcount = $query->getResult();
        if (!empty($prodcount)) $prodcount = $prodcount[0]['prodcount']; else $prodcount = 0;
        $pagecount0 = ceil($prodcount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY p.title ASC';
        if ($sort0 == 1) $sortsql = 'ORDER BY p.title DESC';
        if ($sort0 == 2) $sortsql = 'ORDER BY p.enabled ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY p.enabled DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY p.createDate ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY p.createDate DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        if ($sort0 == 8) $sortsql = 'ORDER BY p.ordering ASC';
        if ($sort0 == 9) $sortsql = 'ORDER BY p.ordering DESC';
        if ($sort0 == 10) $sortsql = 'ORDER BY p.price ASC';
        if ($sort0 == 11) $sortsql = 'ORDER BY p.price DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT p.id, p.article, p.title, p.createDate, p.modifyDate, u.fullName, p.enabled, p.price, p.discount, p.onStock, p.ordering FROM ShopProductBundle:Products p '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.$querytaxonomyleft0.
                                  'WHERE (p.title like :search OR p.article like :search) '.$querytaxonomywhere0.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $products0 = $query->getResult();
        $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->findOneBy(array('main' => 1));
        foreach ($products0 as &$product0)
        {
            $product0['price'] = $product0['price'] * (1 - $product0['discount'] / 100);
            if (!empty($currency))
            {
                $product0['price'] = $currency->getPrefix().number_format($product0['price'], $currency->getDecimalSigns(), $currency->getDecimalSep(), $currency->getThousandSep()).$currency->getPostfix();
            }
        }
        unset($product0);
        if ($this->container->has('object.taxonomy'))
        {
            $taxonomyenabled = 1;
            $actionIds = array();
            foreach ($products0 as $product) $actionIds[] = $product['id'];
            $taxonomyinfo0 = $this->container->get('object.taxonomy')->getTaxonomyListInfo('object.product', $actionIds);
        } else
        {
            $taxonomyenabled = 0;
            $taxonomyinfo0 = null;
        }
        foreach ($products0 as &$product)
        {
            if (($action == 'ordering') && (isset($ordering[$product['id']]))) $product['ordering'] = $ordering[$product['id']];
        }
        return $this->render('ShopProductBundle:Default:productListTab1.html.twig', array(
            'pagecount0' => $pagecount0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'products0' => $products0,
            'taxonomy0' => $taxonomy0,
            'taxonomyenabled' => $taxonomyenabled,
            'taxonomyinfo0' => $taxonomyinfo0,
            'errors0' => $errors,
            'errorsorder0' => $errorsorder
        ));
    }

// *******************************************    
// Настройка валюты
// *******************************************    
    
    public function productCurrencyListAction()
    {
        if ($this->getUser()->checkAccess('product_currencyview') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Настройки валюты',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        // Поиск контента для 1 таба (текстовые страницы)
        $query = $em->createQuery('SELECT c.id, c.name, c.prefix, c.postfix, c.decimalSigns, c.decimalSep, c.thousandSep, c.main, c.enabled, c.minNote, c.exchangeIndex FROM ShopProductBundle:ProductCurrency c ORDER BY c.id');
        $currencies = $query->getResult();
        foreach ($currencies as &$currency)
        {
            $currency['exchangeIndex'] = preg_replace('/(\.?)0+$/','',number_format($currency['exchangeIndex'],10,'.',''));
        }
        return $this->render('ShopProductBundle:Default:currencyList.html.twig', array(
            'currencies' => $currencies
        ));
    }
    
// *******************************************
// Создание новой валюты
// *******************************************    
    public function productCurrencyCreateAction()
    {
        if ($this->getUser()->checkAccess('product_currencyedit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой валюты',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $cur = array();
        $curerror = array();
        
        $cur['name'] = '';
        $cur['enabled'] = 1;
        $cur['prefix'] = '';
        $cur['postfix'] = '';
        $cur['decimalSigns'] = '';
        $cur['decimalSep'] = '';
        $cur['thousandSep'] = '';
        $cur['minNote'] = '';
        $cur['exchangeIndex'] = '';
        $curerror['name'] = '';
        $curerror['enabled'] = '';
        $curerror['prefix'] = '';
        $curerror['postfix'] = '';
        $curerror['decimalSigns'] = '';
        $curerror['decimalSep'] = '';
        $curerror['thousandSep'] = '';
        $curerror['minNote'] = '';
        $curerror['exchangeIndex'] = '';
        // Валидация
        $errors = false;
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postcur = $this->getRequest()->get('cur');
            if (isset($postcur['name'])) $cur['name'] = $postcur['name'];
            if (isset($postcur['enabled'])) $cur['enabled'] = intval($postcur['enabled']); else $cur['enabled'] = 0;
            if (isset($postcur['prefix'])) $cur['prefix'] = $postcur['prefix'];
            if (isset($postcur['postfix'])) $cur['postfix'] = $postcur['postfix'];
            if (isset($postcur['decimalSigns'])) $cur['decimalSigns'] = $postcur['decimalSigns'];
            if (isset($postcur['decimalSep'])) $cur['decimalSep'] = $postcur['decimalSep'];
            if (isset($postcur['thousandSep'])) $cur['thousandSep'] = $postcur['thousandSep'];
            if (isset($postcur['minNote'])) $cur['minNote'] = $postcur['minNote'];
            if (isset($postcur['exchangeIndex'])) $cur['exchangeIndex'] = $postcur['exchangeIndex'];
            unset($postcur);
            if (!preg_match("/^.{3,255}$/ui", $cur['name'])) {$errors = true; $curerror['name'] = 'Название должно содержать более 3 символов';}
            if (!preg_match("/^.{0,99}$/ui", $cur['prefix'])) {$errors = true; $curerror['prefix'] = 'Префикс должен содержать до 99 символов';}
            if (!preg_match("/^.{0,99}$/ui", $cur['postfix'])) {$errors = true; $curerror['postfix'] = 'Постфикс должен содержать до 99 символов';}
            if ((!preg_match("/^\d$/ui", $cur['decimalSigns'])) || (intval($cur['decimalSigns']) < 0) || (intval($cur['decimalSigns']) > 3)) {$errors = true; $curerror['decimalSigns'] = 'Должно быть указано число от 0 до 3';}
            if (!preg_match("/^.{0,5}$/ui", $cur['decimalSep'])) {$errors = true; $curerror['decimalSep'] = 'Разделитель должен содержать до 5 символов';}
            if (!preg_match("/^.{0,5}$/ui", $cur['thousandSep'])) {$errors = true; $curerror['thousandSep'] = 'Разделитель должен содержать до 5 символов';}
            if (!preg_match("/^(\d+((\,|\.)(\d+))?)?$/ui", $cur['minNote'])) {$errors = true; $curerror['minNote'] = 'Должно быть указано положительное число либо оставлена пустая строка';}
            if (!preg_match("/^\d+((\,|\.)(\d+))?$/ui", $cur['exchangeIndex']) || (floatval($cur['exchangeIndex']) <= 0)) {$errors = true; $curerror['exchangeIndex'] = 'Должно быть указано положительное число';}
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $minNote = 1;
                if ($cur['decimalSigns'] == 1) $minNote = 0.1;
                if ($cur['decimalSigns'] == 2) $minNote = 0.01;
                if ($cur['decimalSigns'] == 3) $minNote = 0.001;
                $currency = new \Shop\ProductBundle\Entity\ProductCurrency();
                $currency->setDecimalSep($cur['decimalSep']);
                $currency->setDecimalSigns($cur['decimalSigns']);
                $currency->setEnabled($cur['enabled']);
                $currency->setExchangeIndex(str_replace(',','.',$cur['exchangeIndex']));
                $currency->setMain(0);
                $currency->setMinNote(($cur['minNote'] != '' ? str_replace(',','.',$cur['minNote']) : $minNote));
                $currency->setName($cur['name']);
                $currency->setPostfix($cur['postfix']);
                $currency->setPrefix($cur['prefix']);
                $currency->setThousandSep($cur['thousandSep']);
                $em->persist($currency);
                $em->flush();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой валюты',
                    'message'=>'Валюта успешно создана',
                    'paths'=>array('Создать еще одну валюту'=>$this->get('router')->generate('shop_product_currency_create'),'Вернуться к списку валют'=>$this->get('router')->generate('shop_product_currency_list'))
                ));
            }
        }
        return $this->render('ShopProductBundle:Default:currencyCreate.html.twig', array(
            'cur' => $cur,
            'curerror' => $curerror,
        ));
    }
    
// *******************************************
// Редактирование валюты
// *******************************************    
    public function productCurrencyEditAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $currency = $this->getDoctrine()->getRepository('ShopProductBundle:ProductCurrency')->find($id);
        if (empty($currency))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование валюты',
                'message'=>'Валюта не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку валюты'=>$this->get('router')->generate('shop_product_currency_list'))
            ));
        }
        if ($this->getUser()->checkAccess('product_currencyview') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование валюты',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $cur = array();
        $curerror = array();
        
        $cur['name'] = $currency->getName();
        $cur['enabled'] = $currency->getEnabled();
        $cur['prefix'] = $currency->getPrefix();
        $cur['postfix'] = $currency->getPostfix();
        $cur['decimalSigns'] = $currency->getDecimalSigns();
        $cur['decimalSep'] = $currency->getDecimalSep();
        $cur['thousandSep'] = $currency->getThousandSep();
        $cur['minNote'] = $currency->getMinNote();
        $cur['exchangeIndex'] = preg_replace('/(\.?)0+$/','',number_format($currency->getExchangeIndex(),10,'.',''));
        $curerror['name'] = '';
        $curerror['enabled'] = '';
        $curerror['prefix'] = '';
        $curerror['postfix'] = '';
        $curerror['decimalSigns'] = '';
        $curerror['decimalSep'] = '';
        $curerror['thousandSep'] = '';
        $curerror['minNote'] = '';
        $curerror['exchangeIndex'] = '';
        // Валидация
        $errors = false;
        if ($this->getRequest()->getMethod() == "POST")
        {
            if ($this->getUser()->checkAccess('product_currencyedit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование валюты',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            // Проверка основных данных
            $postcur = $this->getRequest()->get('cur');
            if (isset($postcur['name'])) $cur['name'] = $postcur['name'];
            if (isset($postcur['enabled'])) $cur['enabled'] = intval($postcur['enabled']); else $cur['enabled'] = 0;
            if (isset($postcur['prefix'])) $cur['prefix'] = $postcur['prefix'];
            if (isset($postcur['postfix'])) $cur['postfix'] = $postcur['postfix'];
            if (isset($postcur['decimalSigns'])) $cur['decimalSigns'] = $postcur['decimalSigns'];
            if (isset($postcur['decimalSep'])) $cur['decimalSep'] = $postcur['decimalSep'];
            if (isset($postcur['thousandSep'])) $cur['thousandSep'] = $postcur['thousandSep'];
            if (isset($postcur['minNote'])) $cur['minNote'] = $postcur['minNote'];
            if (($currency->getMain() == 0) && (isset($postcur['exchangeIndex']))) $cur['exchangeIndex'] = $postcur['exchangeIndex'];
            unset($postcur);
            if (!preg_match("/^.{3,255}$/ui", $cur['name'])) {$errors = true; $curerror['name'] = 'Название должно содержать более 3 символов';}
            if (!preg_match("/^.{0,99}$/ui", $cur['prefix'])) {$errors = true; $curerror['prefix'] = 'Префикс должен содержать до 99 символов';}
            if (!preg_match("/^.{0,99}$/ui", $cur['postfix'])) {$errors = true; $curerror['postfix'] = 'Постфикс должен содержать до 99 символов';}
            if ((!preg_match("/^\d$/ui", $cur['decimalSigns'])) || (intval($cur['decimalSigns']) < 0) || (intval($cur['decimalSigns']) > 3)) {$errors = true; $curerror['decimalSigns'] = 'Должно быть указано число от 0 до 3';}
            if (!preg_match("/^.{0,5}$/ui", $cur['decimalSep'])) {$errors = true; $curerror['decimalSep'] = 'Разделитель должен содержать до 5 символов';}
            if (!preg_match("/^.{0,5}$/ui", $cur['thousandSep'])) {$errors = true; $curerror['thousandSep'] = 'Разделитель должен содержать до 5 символов';}
            if (!preg_match("/^(\d+((\,|\.)(\d+))?)?$/ui", $cur['minNote'])) {$errors = true; $curerror['minNote'] = 'Должно быть указано положительное число либо оставлена пустая строка';}
            if (!preg_match("/^\d+((\,|\.)(\d+))?$/ui", $cur['exchangeIndex']) || (floatval($cur['exchangeIndex']) <= 0)) {$errors = true; $curerror['exchangeIndex'] = 'Должно быть указано положительное число';}
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $minNote = 1;
                if ($cur['decimalSigns'] == 1) $minNote = 0.1;
                if ($cur['decimalSigns'] == 2) $minNote = 0.01;
                if ($cur['decimalSigns'] == 3) $minNote = 0.001;
                $currency->setDecimalSep($cur['decimalSep']);
                $currency->setDecimalSigns($cur['decimalSigns']);
                $currency->setEnabled($cur['enabled']);
                $currency->setExchangeIndex(str_replace(',','.',$cur['exchangeIndex']));
                $currency->setMinNote(($cur['minNote'] != '' ? str_replace(',','.',$cur['minNote']) : $minNote));
                $currency->setName($cur['name']);
                $currency->setPostfix($cur['postfix']);
                $currency->setPrefix($cur['prefix']);
                $currency->setThousandSep($cur['thousandSep']);
                $em->flush();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование валюты',
                    'message'=>'Данные валюты успешно сохранены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('shop_product_currency_edit').'?id='.$id, 'Вернуться к списку валют'=>$this->get('router')->generate('shop_product_currency_list'))
                ));
            }
        }
        return $this->render('ShopProductBundle:Default:currencyEdit.html.twig', array(
            'id' => $id,
            'main' => $currency->getMain(),
            'cur' => $cur,
            'curerror' => $curerror,
        ));
    }

// *******************************************
// AJAX-контроллер
// *******************************************    
    
    public function productCurrencyAjaxAction()
    {
        $tab = intval($this->getRequest()->get('tab'));
        if ($this->getUser()->checkAccess('product_currencyview') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Список валют',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->getRequest()->get('action');
        $errors = array();
        if ($action == 'delete')
        {
            if ($this->getUser()->checkAccess('product_currencyedit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Список валют',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $currencyent = $this->getDoctrine()->getRepository('ShopProductBundle:ProductCurrency')->find($key);
                    if (!empty($currencyent))
                    {
                        if ($currencyent->getMain() == 0)
                        {
                            $em->remove($currencyent);
                            $em->flush();
                        } else $errors[$key] = 'Невозможно удалить основную валюту';
                        unset($currencyent);    
                    }
                }
        }
        if ($action == 'setmain')
        {
            if ($this->getUser()->checkAccess('product_currencyedit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Список валют',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $check = $this->getRequest()->get('check');
            if ($check != null)
            {
                $count = 0;
                foreach ($check as $key=>$val)
                    if ($val == 1)
                    {
                        $count++;
                        $currencyent = $this->getDoctrine()->getRepository('ShopProductBundle:ProductCurrency')->find($key);
                    }
                if ($count != 1)
                {
                foreach ($check as $key=>$val)
                    if ($val == 1) $errors[$key] = 'Должна быть выбрана только одна валюта';
                } else
                {
                    $rate = $currencyent->getExchangeIndex();
                    $mainid = $currencyent->getId();
                    $query = $em->createQuery('UPDATE ShopProductBundle:ProductCurrency c SET c.main = IF(c.id = :id, \'1\', \'0\'), c.exchangeIndex = c.exchangeIndex / :rate')->setParameter('id', $mainid)->setParameter('rate', $rate);
                    $query->execute();
                }
                unset($currencyent);    
            }
        }
        $query = $em->createQuery('SELECT c.id, c.name, c.prefix, c.postfix, c.decimalSigns, c.decimalSep, c.thousandSep, c.main, c.enabled, c.minNote, c.exchangeIndex FROM ShopProductBundle:ProductCurrency c ORDER BY c.id');
        $currencies = $query->getResult();
        foreach ($currencies as &$currency)
        {
            $currency['exchangeIndex'] = preg_replace('/(\.?)0+$/','',number_format($currency['exchangeIndex'],10,'.',''));
        }
        return $this->render('ShopProductBundle:Default:currencyListTab1.html.twig', array(
            'currencies' => $currencies,
            'errors' => $errors
        ));
    }
    
// *******************************************    
// Страницы сравнения товаров
// *******************************************    
    
    public function productCompareListAction()
    {
        if ($this->getUser()->checkAccess('product_compareview') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Настройка сраниц сравнения товаров',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        // Поиск контента для 1 таба (текстовые страницы)
        $query = $em->createQuery('SELECT c.id, c.name, c.productLimit, c.enabled, sp.url FROM ShopProductBundle:ProductCompare c LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'compare\' AND sp.contentId = c.id ORDER BY c.id');
        $compares = $query->getResult();
        foreach ($compares as &$compare) $compare['name'] = $this->get('cms.cmsManager')->decodeLocalString($compare['name'],'default',true);
        return $this->render('ShopProductBundle:Default:compareList.html.twig', array(
            'compares' => $compares
        ));
    }
    
// *******************************************
// Создание новой страницы сравнения товаров
// *******************************************    
    public function productCompareCreateAction()
    {
        if ($this->getUser()->checkAccess('product_compareedit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой страницы сравнения продуктов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $comp = array();
        $comperror = array();
        $comp['name'] = array();
        $comp['enabled'] = 1;
        $comp['productLimit'] = '';
        $comperror['name'] = array();
        $comperror['enabled'] = '';
        $comperror['productLimit'] = '';
        $comp['name']['default'] = '';
        $comperror['name']['default'] = '';
        foreach ($locales as $local) 
        {
            $comp['name'][$local['shortName']] = '';
            $comperror['name'][$local['shortName']] = '';
        }
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
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.product','compare');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.product.compare');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postcomp = $this->getRequest()->get('comp');
            if (isset($postcomp['name']['default'])) $comp['name']['default'] = $postcomp['name']['default'];
            foreach ($locales as $local) if (isset($postcomp['name'][$local['shortName']])) $comp['name'][$local['shortName']] = $postcomp['name'][$local['shortName']];
            if (isset($postcomp['enabled'])) $comp['enabled'] = intval($postcomp['enabled']); else $comp['enabled'] = 0;
            if (isset($postcomp['productLimit'])) $comp['productLimit'] = $postcomp['productLimit'];
            unset($postcomp);
            if (!preg_match("/^.{3,255}$/ui", $comp['name']['default'])) {$errors = true; $comperror['name']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $local) 
            {
                if (!preg_match("/^(.{3,255})?$/ui", $comp['name'][$local['shortName']])) {$errors = true; $comperror['name'][$local['shortName']] = 'Заголовок должен содержать более 3 символов';}
            }
            if ((!preg_match("/^\d+$/ui", $comp['productLimit'])) || (floatval($comp['productLimit']) < 2) || (floatval($comp['productLimit']) > 50)) {$errors = true; $comperror['productLimit'] = 'Должно быть указано число от 2 до 50';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->getRequest()->get('page');
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
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'productCompareCreate', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $compent = new \Shop\ProductBundle\Entity\ProductCompare();
                $compent->setEnabled($comp['enabled']);
                $compent->setName($this->get('cms.cmsManager')->encodeLocalString($comp['name']));
                $compent->setProductLimit($comp['productLimit']);
                $em->persist($compent);
                $em->flush();
                $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Сравнение продуктов '.$this->get('cms.cmsManager')->decodeLocalString($compent->getName(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.product');
                $pageent->setContentId($compent->getId());
                $pageent->setContentAction('compare');
                $compent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->persist($pageent);
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                $i = 0;
                foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'productCompareCreate', $compent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой страницы сравнения продуктов',
                    'message'=>'Сраница успешно создана',
                    'paths'=>array('Создать еще одну страницу сранения'=>$this->get('router')->generate('shop_product_compare_create'),'Вернуться к списку страниц сравнения'=>$this->get('router')->generate('shop_product_compare_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'productCompareCreate', 0, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('ShopProductBundle:Default:compareCreate.html.twig', array(
            'comp' => $comp,
            'comperror' => $comperror,
            'page' => $page,
            'pageerror' => $pageerror,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'locales' => $locales,
            'roles' => $roles,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// Редактирование страницы сравнения товаров
// *******************************************    
    public function productCompareEditAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $compent = $this->getDoctrine()->getRepository('ShopProductBundle:ProductCompare')->find($id);
        if (empty($compent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование траницы сравнения продуктов',
                'message'=>'Страница не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку страниц сравнения'=>$this->get('router')->generate('shop_product_compare_list'))
            ));
        }
        if ($this->getUser()->checkAccess('product_compareview') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы сравнения продуктов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.product','contentId'=>$id,'contentAction'=>'compare'));
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();

        $comp = array();
        $comperror = array();
        $comp['name'] = array();
        $comp['enabled'] = $compent->getEnabled();
        $comp['productLimit'] = $compent->getProductLimit();
        $comperror['name'] = array();
        $comperror['enabled'] = '';
        $comperror['productLimit'] = '';
        $comp['name']['default'] = $this->get('cms.cmsManager')->decodeLocalString($compent->getName(),'default',false);
        $comperror['name']['default'] = '';
        foreach ($locales as $local) 
        {
            $comp['name'][$local['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($compent->getName(),$local['shortName'],false);;
            $comperror['name'][$local['shortName']] = '';
        }
        
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
            $page['template'] = $compent->getTemplate();
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
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.product','compare');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.product.compare');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            if ($this->getUser()->checkAccess('product_compareedit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование страницы сравнения продуктов',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            // Проверка основных данных
            $postcomp = $this->getRequest()->get('comp');
            if (isset($postcomp['name']['default'])) $comp['name']['default'] = $postcomp['name']['default'];
            foreach ($locales as $local) if (isset($postcomp['name'][$local['shortName']])) $comp['name'][$local['shortName']] = $postcomp['name'][$local['shortName']];
            if (isset($postcomp['enabled'])) $comp['enabled'] = intval($postcomp['enabled']); else $comp['enabled'] = 0;
            if (isset($postcomp['productLimit'])) $comp['productLimit'] = $postcomp['productLimit'];
            unset($postcomp);
            if (!preg_match("/^.{3,255}$/ui", $comp['name']['default'])) {$errors = true; $comperror['name']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $local) 
            {
                if (!preg_match("/^(.{3,255})?$/ui", $comp['name'][$local['shortName']])) {$errors = true; $comperror['name'][$local['shortName']] = 'Заголовок должен содержать более 3 символов';}
            }
            if ((!preg_match("/^\d+$/ui", $comp['productLimit'])) || (floatval($comp['productLimit']) < 2) || (floatval($comp['productLimit']) > 50)) {$errors = true; $comperror['productLimit'] = 'Должно быть указано число от 2 до 50';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->getRequest()->get('page');
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
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'productCompareEdit', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $compent->setEnabled($comp['enabled']);
                $compent->setName($this->get('cms.cmsManager')->encodeLocalString($comp['name']));
                $compent->setProductLimit($comp['productLimit']);
                $em->flush();
                if (!empty($pageent)) $this->get('cms.cmsManager')->updateBreadCrumbs($pageent->getId());
                if (empty($pageent))
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $em->persist($pageent);
                }
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Сравнение продуктов '.$this->get('cms.cmsManager')->decodeLocalString($compent->getName(),'default',true));
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.product');
                $pageent->setContentId($compent->getId());
                $pageent->setContentAction('compare');
                $compent->setTemplate($page['template']);
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                $i = 0;
                foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'productCompareEdit', $compent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование страницы сравнения продуктов',
                    'message'=>'Данные сраницы сравнения успешно отредактированы',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('shop_product_compare_edit').'?id='.$id, 'Вернуться к списку страниц сравнения'=>$this->get('router')->generate('shop_product_compare_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'productCompareEdit', $id, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('ShopProductBundle:Default:compareEdit.html.twig', array(
            'id' => $id,
            'comp' => $comp,
            'comperror' => $comperror,
            'page' => $page,
            'pageerror' => $pageerror,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'locales' => $locales,
            'roles' => $roles,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// AJAX-контроллер
// *******************************************    
    
    public function productCompareAjaxAction()
    {
        if ($this->getUser()->checkAccess('product_compareview') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Настройка сраниц сравнения товаров',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->getRequest()->get('action');
        $errors = array();
        if ($action == 'delete')
        {
            if ($this->getUser()->checkAccess('product_compareedit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Настройка сраниц сравнения товаров',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $compent = $this->getDoctrine()->getRepository('ShopProductBundle:ProductCompare')->find($key);
                    if (!empty($compent))
                    {
                        $em->remove($compent);
                        $em->flush();
                        $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.product\' AND p.contentAction = \'compare\' AND p.contentId = :id')->setParameter('id', $key);
                        $query->execute();
                        foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
                        {
                            $serv = $this->container->get($item);
                            $serv->getAdminController($this->getRequest(), 'productCompareDelete', $key, 'save');
                        }       
                        unset($compent);    
                    }
                }
        }
        
        // Поиск контента для 1 таба (текстовые страницы)
        $query = $em->createQuery('SELECT c.id, c.name, c.productLimit, c.enabled, sp.url FROM ShopProductBundle:ProductCompare c LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'compare\' AND sp.contentId = c.id ORDER BY c.id');
        $compares = $query->getResult();
        foreach ($compares as &$compare) $compare['name'] = $this->get('cms.cmsManager')->decodeLocalString($compare['name'],'default',true);
        return $this->render('ShopProductBundle:Default:compareListTab1.html.twig', array(
            'compares' => $compares,
            'errors' => $errors
        ));
    }
    
    
}
