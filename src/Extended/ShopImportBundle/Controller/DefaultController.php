<?php

namespace Extended\ShopImportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{
// *******************************************
// Главная страница импорта
// *******************************************    
    
    public function importAction()
    {
        if ($this->getUser()->checkAccess('product_import') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Импорт продукции',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
//        $em = $this->getDoctrine()->getEntityManager();
        return $this->render('ExtendedShopImportBundle:Default:import.html.twig', array(
            
        ));
    }
    
// *******************************************
// AJAX загрузка импорта 1
// *******************************************    

    private function taxonomyGetTree($tree, $id, $nesting, &$cats)
    {
        foreach ($tree as $cat)
        {
            if ($cat['parent'] == $id)
            {
                $cats[] = array('title' => $cat['title'], 'id' => $cat['id'], 'enableAdd' => $cat['enableAdd'], 'enabled' => $cat['enabled'], 'nesting' => $nesting);
                $this->taxonomyGetTree($tree, $cat['id'], $nesting + 1, $cats);
            }
        }
    }

    private function import1SortCategories($oldcats, $id, $nesting, &$cats)
    {
        foreach ($oldcats as $oldcat)
        {
            if ($oldcat['parentId'] == $id) 
            {
                for ($i=0;$i<$nesting;$i++) $oldcat['name'] = '- '.$oldcat['name'];
                $cats[] = $oldcat;
                $this->import1SortCategories($oldcats, $oldcat['id'], $nesting + 1, $cats);
            }
        }
    }
    
    
    public function import1LoadFileAction() 
    {
        $file = $this->getRequest()->files->get('file');
        $tmpfile = $file->getPathName();
        // Проверка входного файла
        $xml = @simplexml_load_file($tmpfile);
        if ($xml === false) return new Response('<p>Неправильный формат файла</p>');
        $basepath = '../secured/shopimport/';
        $name = 'import1.yml';
        @unlink($basepath.$name);
        if (!move_uploaded_file($tmpfile, $basepath . $name)) return new Response('<p>Ошибка сохранения файла</p>');
        $em = $this->container->get('doctrine')->getEntityManager();
        // Прочие данные
        $info = array();
        $info['filename'] = $file->getClientOriginalName();
        $info['name'] = $xml->shop->name;
        $info['date'] = $xml['date'];
        // Сохранение категорий
        $importcfg = $this->get('cms.cmsManager')->getConfigValue('shop.import.one.categories');
        if ($importcfg != null) $importcfg = @unserialize($importcfg);
        if (!is_array($importcfg)) $importcfg = array();
        $allcategories = array();
        foreach ($xml->shop->categories->category as $category)
        {
            $count = 0;
            foreach ($xml->shop->offers->offer as $offer) if ((intval($offer->categoryId) == intval($category['id'])) && (intval($category['id']) != 0)) $count++;
            $allcategories[] = array('id' => intval($category['id']), 'parentId' => (isset($category['parentId']) ? intval($category['parentId']) : null), 'name' => (string)$category, 'count' => $count);
        }
        $categories = array();
        $this->import1SortCategories($allcategories, null, 0, $categories);
        // Количество продуктов
        $info['products'] = count($xml->shop->offers->offer);
        // Поиск валют
        $query = $em->createQuery('SELECT c.id, c.name FROM ShopProductBundle:ProductCurrency c ORDER BY c.main DESC, c.id ASC');
        $currencies = $query->getResult();
        $curcfg = $this->get('cms.cmsManager')->getConfigValue('shop.import.one.currency');
        // Найти наши категории
        $ourcategories = array();
        if ($this->container->has('object.taxonomy'))
        {
            $query = $em->createQuery('SELECT t.id, t.title, t.parent, t.enabled, t.enableAdd FROM BasicCmsBundle:Taxonomies t WHERE t.object = :object ORDER BY t.ordering')->setParameter('object', 'object.product');
            $tree = $query->getResult();
            $this->taxonomyGetTree($tree, null, 0, $ourcategories);
        }
        // Параметры страницы
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.product','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.product.view');
        $modules = $query->getResult();
        $templatecfg = $this->get('cms.cmsManager')->getConfigValue('shop.import.one.template');
        $layoutcfg = $this->get('cms.cmsManager')->getConfigValue('shop.import.one.layout');
        $modulecfg = explode(',',$this->get('cms.cmsManager')->getConfigValue('shop.import.one.modules'));
        if ((!is_array($modulecfg)) || (count($modulecfg) == 0))
        {
            $modulecfg = array();
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $modulecfg[] = $module['id'];
        }
        // Вывод
        return $this->render('ExtendedShopImportBundle:Default:importOneTab.html.twig', array(
            'info' => $info,
            'categories' => $categories,
            'ourcategories' => $ourcategories,
            'importcfg' => $importcfg,
            'currencies' => $currencies,
            'curcfg' => $curcfg,
            'templates' => $templates,
            'layouts' => $layouts,
            'modules' => $modules,
            'templatecfg' => $templatecfg,
            'layoutcfg' => $layoutcfg,
            'modulecfg' => $modulecfg
        ));
    }
    
    public function import1ProcessBlockAction() 
    {
        $mode = 'all';
        $em = $this->container->get('doctrine')->getEntityManager();
        // Загрузить файл
        $basepath = '../secured/shopimport/';
        $name = 'import1.yml';
        $xml = @simplexml_load_file($basepath.$name);
        if ($xml === false) return new Response(json_encode(array('result'=>'Ошибка открытия файла')));
        // Записать настройки
        $importcfg = $this->getRequest()->get('categories');
        if (is_array($importcfg))
        {
            $this->container->get('cms.cmsManager')->setConfigValue('shop.import.one.categories', serialize($importcfg));
        } else
        {
            $importcfg = $this->get('cms.cmsManager')->getConfigValue('shop.import.one.categories');
            if ($importcfg != null) $importcfg = @unserialize($importcfg);
            if (!is_array($importcfg)) $importcfg = array();
        }
        $curpost = intval($this->getRequest()->get('currency'));
        $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->find($curpost);
        if (!empty($currency))
        {
            $this->get('cms.cmsManager')->setConfigValue('shop.import.one.currency', $curpost);
        } else
        {
            $curpost = intval($this->get('cms.cmsManager')->getConfigValue('shop.import.one.currency'));
            $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->find($curpost);
            if (empty($currency)) $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->findOneBy(array('main'=>1));
        }
        $layout = $this->getRequest()->get('layout');
        if ($layout !== null)
        {
            $this->container->get('cms.cmsManager')->setConfigValue('shop.import.one.layout', $layout);
        } else
        {
            $layout = $this->container->get('cms.cmsManager')->getConfigValue('shop.import.one.layout');
        }
        $template = $this->getRequest()->get('template');
        if ($layout !== null)
        {
            $this->container->get('cms.cmsManager')->setConfigValue('shop.import.one.template', $template);
        } else
        {
            $template = $this->container->get('cms.cmsManager')->getConfigValue('shop.import.one.template');
        }
        $modules = $this->getRequest()->get('modules');
        if (is_array($modules))
        {
            $this->container->get('cms.cmsManager')->setConfigValue('shop.import.one.modules', implode(',', $modules));
        } else
        {
            $modules = explode(',',$this->container->get('cms.cmsManager')->getConfigValue('shop.import.one.modules'));
            if (!is_array($modules)) $modules = array();
        }
        // Обработать файлы
        $starttime = time();
        $start = intval($this->getRequest()->get('start'));
        for ($i = 0; $i < 30; $i++)
        {
            if ((time() - $starttime) > 20) break;
            if (isset($xml->shop->offers->offer[$start]) && isset($importcfg[intval($xml->shop->offers->offer[$start]->categoryId)]) && ($importcfg[intval($xml->shop->offers->offer[$start]->categoryId)] != ''))
            {
                $product = $xml->shop->offers->offer[$start];
                // Поиск продукта
                $query = $em->createQuery('SELECT p.productId FROM ExtendedShopImportBundle:ProductImport p WHERE p.importType = \'yandex market xml\' AND p.importId = :id')->setParameter('id', $product['id']);
                $findid = $query->getResult();
                if (isset($findid[0]['productId'])) 
                {
                    $findid = $findid[0]['productId']; 
                    $productent = $em->getRepository('ShopProductBundle:Products')->find($findid);
                    if (empty($productent)) $findid = null;
                } else $findid = null;
                if ($findid == null)
                {
                    $query = $em->createQuery('SELECT max(p.ordering) as maxorder FROM ShopProductBundle:Products p');
                    $order = $query->getResult();
                    if (isset($order[0]['maxorder'])) $order = $order[0]['maxorder'] + 1; else $order = 1;
                    $productent = new \Shop\ProductBundle\Entity\Products();
                    $em->persist($productent);
                    $productent->setArticle('');
                    $productent->setCreateDate(new \DateTime('now'));
                    $productent->setCreaterId($this->getUser()->getId());
                    $productent->setDepth(null);
                    $productent->setDiscount(0);
                    $productent->setEnabled(1);
                    $productent->setHeight(null);
                    $productent->setManufacturer('');
                    $productent->setMetaDescription('');
                    $productent->setMetaKeywords('');
                    $productent->setOrdering($order);
                    $productent->setRating(null);
                    $productent->setWeight(null);
                    $productent->setWidth(null);
                    $productent->setTemplate(($template !== null ? $template : ''));
                }
                if (($mode == 'all') || ($findid == null))
                {
                    if ($findid != null) $avatar = $productent->getAvatar(); else $avatar = '';
                    $ch = curl_init();  
                    curl_setopt($ch, CURLOPT_URL, (string)$product->picture);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                    $photobuffer = curl_exec($ch);
                    curl_close($ch);
                    $basepathimg = '../images/product/';
                    $nameimg = $this->getUser()->getId().'_'.md5(time() + rand()).'.jpg';
                    if (@file_put_contents($basepathimg.$nameimg, $photobuffer)) 
                    {
                        if (@getimagesize($basepathimg.$nameimg)) 
                        {
                            if (strpos($avatar,'/images/product/') === 0) @unlink('..'.$avatar);
                            $avatar = '/images/product/'.$nameimg;
                        } else
                        {
                            @unlink($basepathimg.$nameimg);
                        }
                    }
                    $productent->setAvatar($avatar);
                    $productent->setDescription((string)$product->description);
                    $productent->setTitle((string)$product->name);
                }
                $productent->setModifyDate(new \DateTime('now'));
                $productent->setOnStock(($product['available'] == 'true' ? 1 : 0));
                $productent->setPrice(floatval($product->price) / $currency->getExchangeIndex());
                $em->flush();
                if ($findid == null)
                {
                    // Создать страницу товара
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $pageent->setBreadCrumbs('');
                    $pageent->setBreadCrumbsRel('');
                    $pageent->setDescription('Просмотр продукта '.$productent->getTitle());
                    $pageent->setUrl('');
                    $pageent->setTemplate(($layout !== null ? $layout : ''));
                    $pageent->setModules(implode(',', $modules));
                    $pageent->setLocale('');
                    $pageent->setContentType('object.product');
                    $pageent->setContentId($productent->getId());
                    $pageent->setContentAction('view');
                    $em->persist($pageent);
                    $em->flush();
                    $pageent->setUrl($pageent->getId());
                    $em->flush();
                    // Создать ассоциации с категориями
                    if (isset($importcfg[intval($product->categoryId)]))
                    {
                        $taxent = $em->getRepository('BasicCmsBundle:Taxonomies')->findOneBy(array('object'=>'object.product', 'id'=>intval($importcfg[intval($product->categoryId)])));
                        if (!empty($taxent))
                        {
                            $linkent = new \Basic\CmsBundle\Entity\TaxonomiesLinks();
                            $linkent->setItemId($productent->getId());
                            $linkent->setTaxonomyId(intval($importcfg[intval($product->categoryId)]));
                            $em->persist($linkent);
                            $em->flush();
                        }
                    }
                }
                // Добавить данные об импорте
                $importent = $em->getRepository('ExtendedShopImportBundle:ProductImport')->findOneBy(array('importType' => 'yandex market xml', 'importId' => $product['id']));
                if (empty($importent))
                {
                    $importent = new \Extended\ShopImportBundle\Entity\ProductImport();
                    $em->persist($importent);
                    $importent->setImportType('yandex market xml');
                    $importent->setImportId(intval($product['id']));
                }
                $importent->setProductId($productent->getId());
                $importent->setImportDate(new \DateTime('now'));
                $em->flush();
            }
            $start++;
        }
        // Вывести результат
        $result = array();
        $result['result'] = 'OK';
        $result['status'] = ($start >= count($xml->shop->offers->offer) ? 'Fine' : 'OK');
        $result['start'] = $start;
        $result['percent'] = intval($start / count($xml->shop->offers->offer) * 100);
        return new Response(json_encode($result));
    }    
    
// *******************************************
// AJAX загрузка импорта 2
// *******************************************    
    
    public function import2LoadCsv($filename)
    {
        $basepath = '../secured/shopimport/';
        $name = 'import2.tmp';
        $str = @file_get_contents($filename);
        if ($str == null) return false;
        $str = iconv('windows-1251', 'utf-8', $str);
        file_put_contents($basepath.$name, $str);
        $f = @fopen($basepath.$name,"r");
        if ($f === false) return false;
        $headers = fgetcsv($f,0,"\t",'"');
        if (!is_array($headers)) return false;
        if (!in_array('item_code', $headers)) return null;
        if (!in_array('name', $headers)) return null;
        if (!in_array('info', $headers)) return null;
        if (!in_array('price', $headers)) return null;
        if (!in_array('status', $headers)) return null;
        if (!in_array('image', $headers)) return null;
        if (!in_array('type_id', $headers)) return null;
        $result = array();
        while ($csvline = fgetcsv($f,0,"\t",'"'))
        {
            if (is_array($csvline))
            {
                $item = array();
                foreach ($csvline as $key=>$value)
                {
                    if ($headers[$key] == 'item_code') $item['id'] = $value;
                    if ($headers[$key] == 'name') $item['title'] = $value;
                    if ($headers[$key] == 'info') $item['description'] = $value;
                    if ($headers[$key] == 'price') $item['price'] = $value;
                    if ($headers[$key] == 'status') $item['onStock'] = $value;
                    if ($headers[$key] == 'image') $item['avatar'] = $value;
                    if ($headers[$key] == 'type_id') $item['category'] = $value;
                    if ($headers[$key] == '__image2') $item['image1'] = $value;
                    if ($headers[$key] == '__image4') $item['image2'] = $value;
                    if ($headers[$key] == 'seo.description') $item['metaDescription'] = $value;
                    if ($headers[$key] == 'seo.keywords') $item['metaKeywords'] = $value;
                }
                if (isset($item['id']) && isset($item['title']) && isset($item['description']) && isset($item['price']) && isset($item['onStock']) && isset($item['avatar']) && isset($item['category']))
                $result[] = $item;
            }
        }
        fclose($f);
        return $result;
    }
    
    public function import2LoadFileAction() 
    {
        $file = $this->getRequest()->files->get('file');
        $tmpfile = $file->getPathName();
        // Проверка входного файла
        $csv = $this->import2LoadCsv($tmpfile);
        if ($csv === false) return new Response('<p>Неправильный формат файла</p>');
        if ($csv === null) return new Response('<p>Файл должен содержать поля item_code, name, info, price, status, image, type_id.</p>');
        $basepath = '../secured/shopimport/';
        $name = 'import2.csv';
        @unlink($basepath.$name);
        if (!move_uploaded_file($tmpfile, $basepath . $name)) return new Response('<p>Ошибка сохранения файла</p>');
        $em = $this->container->get('doctrine')->getEntityManager();
        // Прочие данные
        $info = array();
        $info['filename'] = $file->getClientOriginalName();
        // Сохранение категорий
        $importcfg = $this->get('cms.cmsManager')->getConfigValue('shop.import.two.categories');
        if ($importcfg != null) $importcfg = @unserialize($importcfg);
        if (!is_array($importcfg)) $importcfg = array();
        $categories = array();
        foreach ($csv as $item)
        {
            if ($item['category'] != null)
            {
                if (!isset($categories[$item['category']])) $categories[$item['category']] = array('id'=>$item['category'],'name'=>'', 'count'=>0);
                if ($categories[$item['category']]['count'] < 3) 
                {
                    $categories[$item['category']]['name'] .= $item['title']."<br />";
                }
                $categories[$item['category']]['count']++;
            }
        }
        // Количество продуктов
        $info['products'] = count($csv);
        // Поиск валют
        $query = $em->createQuery('SELECT c.id, c.name FROM ShopProductBundle:ProductCurrency c ORDER BY c.main DESC, c.id ASC');
        $currencies = $query->getResult();
        $curcfg = $this->get('cms.cmsManager')->getConfigValue('shop.import.two.currency');
        // Найти наши категории
        $ourcategories = array();
        if ($this->container->has('object.taxonomy'))
        {
            $query = $em->createQuery('SELECT t.id, t.title, t.parent, t.enabled, t.enableAdd FROM BasicCmsBundle:Taxonomies t WHERE t.object = :object ORDER BY t.ordering')->setParameter('object', 'object.product');
            $tree = $query->getResult();
            $this->taxonomyGetTree($tree, null, 0, $ourcategories);
        }
        // Параметры страницы
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.product','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.product.view');
        $modules = $query->getResult();
        $templatecfg = $this->get('cms.cmsManager')->getConfigValue('shop.import.two.template');
        $layoutcfg = $this->get('cms.cmsManager')->getConfigValue('shop.import.two.layout');
        $modulecfg = explode(',',$this->get('cms.cmsManager')->getConfigValue('shop.import.two.modules'));
        if ((!is_array($modulecfg)) || (count($modulecfg) == 0))
        {
            $modulecfg = array();
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $modulecfg[] = $module['id'];
        }
        // Вывод
        return $this->render('ExtendedShopImportBundle:Default:importTwoTab.html.twig', array(
            'info' => $info,
            'categories' => $categories,
            'ourcategories' => $ourcategories,
            'importcfg' => $importcfg,
            'currencies' => $currencies,
            'curcfg' => $curcfg,
            'templates' => $templates,
            'layouts' => $layouts,
            'modules' => $modules,
            'templatecfg' => $templatecfg,
            'layoutcfg' => $layoutcfg,
            'modulecfg' => $modulecfg
        ));
    }
    
    public function import2LoadPicFileAction() 
    {
        $file = $this->getRequest()->files->get('file');
        $tmpfile = $file->getPathName();
        // Проверка файла
        $zip = zip_open($tmpfile);
        if (!is_resource($zip)) return new Response('');
        // Подсчёт количества картинок
        $count = 0;
        while ($entry = zip_read($zip))
        {
            $ext = substr(strrchr(zip_entry_name($entry), '.'), 1);
            if (($ext == 'jpg') || ($ext == 'jpeg') || ($ext == 'gif') || ($ext == 'png')) $count++;
        }
        zip_close($zip);
        // Вывод результата
        $basepath = '../secured/shopimport/';
        $name = 'images2.zip';
        @unlink($basepath.$name);
        if (!move_uploaded_file($tmpfile, $basepath . $name)) return new Response('');
        return new Response(json_encode(array('name'=>$file->getClientOriginalName(), 'count'=>$count)));
    }
    
    public function import2ProcessBlockAction() 
    {
        $mode = 'all';
        $em = $this->container->get('doctrine')->getEntityManager();
        // Загрузить файл
        $basepath = '../secured/shopimport/';
        $name = 'import2.csv';
        $nameimage = 'images2.zip';
        $csv = $this->import2LoadCsv($basepath.$name);
        if (!is_array($csv)) return new Response(json_encode(array('result'=>'Ошибка открытия файла')));
        // Записать настройки
        $importcfg = $this->getRequest()->get('categories');
        if (is_array($importcfg))
        {
            $this->container->get('cms.cmsManager')->setConfigValue('shop.import.two.categories', serialize($importcfg));
        } else
        {
            $importcfg = $this->get('cms.cmsManager')->getConfigValue('shop.import.two.categories');
            if ($importcfg != null) $importcfg = @unserialize($importcfg);
            if (!is_array($importcfg)) $importcfg = array();
        }
        $curpost = intval($this->getRequest()->get('currency'));
        $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->find($curpost);
        if (!empty($currency))
        {
            $this->get('cms.cmsManager')->setConfigValue('shop.import.two.currency', $curpost);
        } else
        {
            $curpost = intval($this->get('cms.cmsManager')->getConfigValue('shop.import.two.currency'));
            $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->find($curpost);
            if (empty($currency)) $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->findOneBy(array('main'=>1));
        }
        $layout = $this->getRequest()->get('layout');
        if ($layout !== null)
        {
            $this->container->get('cms.cmsManager')->setConfigValue('shop.import.two.layout', $layout);
        } else
        {
            $layout = $this->container->get('cms.cmsManager')->getConfigValue('shop.import.two.layout');
        }
        $template = $this->getRequest()->get('template');
        if ($layout !== null)
        {
            $this->container->get('cms.cmsManager')->setConfigValue('shop.import.two.template', $template);
        } else
        {
            $template = $this->container->get('cms.cmsManager')->getConfigValue('shop.import.two.template');
        }
        $modules = $this->getRequest()->get('modules');
        if (is_array($modules))
        {
            $this->container->get('cms.cmsManager')->setConfigValue('shop.import.two.modules', implode(',', $modules));
        } else
        {
            $modules = explode(',',$this->container->get('cms.cmsManager')->getConfigValue('shop.import.two.modules'));
            if (!is_array($modules)) $modules = array();
        }
        // Обработать файлы
        $starttime = time();
        $start = intval($this->getRequest()->get('start'));
        for ($i = 0; $i < 30; $i++)
        {
            if ((time() - $starttime) > 20) break;
            if (isset($csv[$start]) && isset($csv[$start]['category']) && ($importcfg[intval($csv[$start]['category'])] != ''))
            {
                $product = $csv[$start];
                // Поиск продукта
                $query = $em->createQuery('SELECT p.productId FROM ExtendedShopImportBundle:ProductImport p WHERE p.importType = \'winshop csv\' AND p.importId = :id')->setParameter('id', $product['id']);
                $findid = $query->getResult();
                if (isset($findid[0]['productId'])) 
                {
                    $findid = $findid[0]['productId']; 
                    $productent = $em->getRepository('ShopProductBundle:Products')->find($findid);
                    if (empty($productent)) $findid = null;
                } else $findid = null;
                if ($findid == null)
                {
                    $query = $em->createQuery('SELECT max(p.ordering) as maxorder FROM ShopProductBundle:Products p');
                    $order = $query->getResult();
                    if (isset($order[0]['maxorder'])) $order = $order[0]['maxorder'] + 1; else $order = 1;
                    $productent = new \Shop\ProductBundle\Entity\Products();
                    $em->persist($productent);
                    $productent->setArticle('');
                    $productent->setCreateDate(new \DateTime('now'));
                    $productent->setCreaterId($this->getUser()->getId());
                    $productent->setDepth(null);
                    $productent->setDiscount(0);
                    $productent->setEnabled(1);
                    $productent->setHeight(null);
                    $productent->setManufacturer('');
                    $productent->setOrdering($order);
                    $productent->setRating(null);
                    $productent->setWeight(null);
                    $productent->setWidth(null);
                    $productent->setTemplate(($template !== null ? $template : ''));
                }
                if (($mode == 'all') || ($findid == null))
                {
                    if ($findid != null) $avatar = $productent->getAvatar(); else $avatar = '';
                    $zipfile = @zip_open($basepath.$nameimage);
                    if (is_resource($zipfile))
                    {
                        while ($entry = zip_read($zipfile))
                        {
                            if (basename(zip_entry_name($entry)) == $product['avatar'])
                            {
                                $photobuffer = zip_entry_read($entry, zip_entry_filesize($entry));
                                $basepathimg = '../images/product/';
                                $nameimg = $this->getUser()->getId().'_'.md5(time() + rand()).'.jpg';
                                if (@file_put_contents($basepathimg.$nameimg, $photobuffer)) 
                                {
                                    if (@getimagesize($basepathimg.$nameimg)) 
                                    {
                                        if (strpos($avatar,'/images/product/') === 0) @unlink('..'.$avatar);
                                        $avatar = '/images/product/'.$nameimg;
                                    } else
                                    {
                                        @unlink($basepathimg.$nameimg);
                                    }
                                }
                                break;
                            }
                        }
                        zip_close($zipfile);
                    }
                    $productent->setAvatar($avatar);
                    $productent->setDescription($product['description']);
                    $productent->setTitle($product['title']);
                    $productent->setMetaDescription((isset($product['metaDescription']) ? $product['metaDescription'] : ''));
                    $productent->setMetaKeywords((isset($product['metaKeywords']) ? $product['metaKeywords'] : ''));
                }
                $productent->setModifyDate(new \DateTime('now'));
                $productent->setOnStock($product['onStock']);
                $productent->setPrice(floatval($product['price']) / $currency->getExchangeIndex());
                $em->flush();
                if ($findid == null)
                {
                    // Создать страницу товара
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $pageent->setBreadCrumbs('');
                    $pageent->setBreadCrumbsRel('');
                    $pageent->setDescription('Просмотр продукта '.$productent->getTitle());
                    $pageent->setUrl('');
                    $pageent->setTemplate(($layout !== null ? $layout : ''));
                    $pageent->setModules(implode(',', $modules));
                    $pageent->setLocale('');
                    $pageent->setContentType('object.product');
                    $pageent->setContentId($productent->getId());
                    $pageent->setContentAction('view');
                    $em->persist($pageent);
                    $em->flush();
                    $pageent->setUrl($pageent->getId());
                    $em->flush();
                    // Создать ассоциации с категориями
                    if (isset($importcfg[intval($product['category'])]))
                    {
                        $taxent = $em->getRepository('BasicCmsBundle:Taxonomies')->findOneBy(array('object'=>'object.product', 'id'=>intval($importcfg[intval($product['category'])])));
                        if (!empty($taxent))
                        {
                            $linkent = new \Basic\CmsBundle\Entity\TaxonomiesLinks();
                            $linkent->setItemId($productent->getId());
                            $linkent->setTaxonomyId(intval($importcfg[intval($product['category'])]));
                            $em->persist($linkent);
                            $em->flush();
                        }
                    }
                    // Создать дополнительные изображения
                    if (isset($product['image1']) && ($product['image1'] != ''))
                    {
                        $zipfile = @zip_open($basepath.$nameimage);
                        if (is_resource($zipfile))
                        {
                            while ($entry = zip_read($zipfile))
                            {
                                if (basename(zip_entry_name($entry)) == $product['image1'])
                                {
                                    $photobuffer = zip_entry_read($entry, zip_entry_filesize($entry));
                                    $basepathimg = '../images/product/';
                                    $nameimg = $this->getUser()->getId().'_'.md5(time() + rand()).'.jpg';
                                    if (@file_put_contents($basepathimg.$nameimg, $photobuffer)) 
                                    {
                                        if (@getimagesize($basepathimg.$nameimg)) 
                                        {
                                            $avatar = '/images/product/'.$nameimg;
                                            $imagelink = new \Shop\GalleryBundle\Entity\ProductImage();
                                            $imagelink->setEnabled(1);
                                            $imagelink->setImage('/images/product/'.$nameimg);
                                            $imagelink->setOrdering(1);
                                            $imagelink->setProductId($productent->getId());
                                            $em->persist($imagelink);
                                            $em->flush();
                                        } else
                                        {
                                            @unlink($basepathimg.$nameimg);
                                        }
                                    }
                                    break;
                                }
                            }
                            zip_close($zipfile);
                        }
                    }
                    if (isset($product['image2']) && ($product['image2'] != ''))
                    {
                        $zipfile = @zip_open($basepath.$nameimage);
                        if (is_resource($zipfile))
                        {
                            while ($entry = zip_read($zipfile))
                            {
                                if (basename(zip_entry_name($entry)) == $product['image2'])
                                {
                                    $photobuffer = zip_entry_read($entry, zip_entry_filesize($entry));
                                    $basepathimg = '../images/product/';
                                    $nameimg = $this->getUser()->getId().'_'.md5(time() + rand()).'.jpg';
                                    if (@file_put_contents($basepathimg.$nameimg, $photobuffer)) 
                                    {
                                        if (@getimagesize($basepathimg.$nameimg)) 
                                        {
                                            $avatar = '/images/product/'.$nameimg;
                                            $imagelink = new \Shop\GalleryBundle\Entity\ProductImage();
                                            $imagelink->setEnabled(1);
                                            $imagelink->setImage('/images/product/'.$nameimg);
                                            $imagelink->setOrdering(2);
                                            $imagelink->setProductId($productent->getId());
                                            $em->persist($imagelink);
                                            $em->flush();
                                        } else
                                        {
                                            @unlink($basepathimg.$nameimg);
                                        }
                                    }
                                    break;
                                }
                            }
                            zip_close($zipfile);
                        }
                    }
                }
                // Добавить данные об импорте
                $importent = $em->getRepository('ExtendedShopImportBundle:ProductImport')->findOneBy(array('importType' => 'winshop csv', 'importId' => $product['id']));
                if (empty($importent))
                {
                    $importent = new \Extended\ShopImportBundle\Entity\ProductImport();
                    $em->persist($importent);
                    $importent->setImportType('winshop csv');
                    $importent->setImportId(intval($product['id']));
                }
                $importent->setProductId($productent->getId());
                $importent->setImportDate(new \DateTime('now'));
                $em->flush();
            }
            $start++;
        }
        // Вывести результат
        $result = array();
        $result['result'] = 'OK';
        $result['status'] = ($start >= count($csv) ? 'Fine' : 'OK');
        $result['start'] = $start;
        $result['percent'] = intval($start / count($csv) * 100);
        return new Response(json_encode($result));
    }    
    
// *******************************************
// AJAX загрузка импорта 3
// *******************************************    
    
    public function import3LoadFileAction() 
    {
        $file = $this->getRequest()->files->get('file');
        $tmpfile = $file->getPathName();
        // Проверка входного файла
        $filetype = \PHPExcel_IOFactory::identify($tmpfile);
        $reader = \PHPExcel_IOFactory::createReader($filetype);
        $reader->setReadDataOnly(true);
        $excel = $reader->load($tmpfile);
        $sheet = $excel->getSheet();
        // Считать 10 строк для определения назначения столбцов
        $cellmatrix = array();
        for ($x = 0; $x < 20; $x++)
        {
            $tmp = array();
            $datadetected = false;
            for ($y = 1; $y < 13; $y++)
            {
                $tmp[$y] = $sheet->getCellByColumnAndRow($x, $y)->getValue();
                if ($tmp[$y] != null) $datadetected = true;
                if (strlen($tmp[$y]) > 120) $tmp[$y] = substr($tmp[$y],0, 120).'...';
            }
            if ($datadetected == true) $cellmatrix[$x] = $tmp;
        }
        // Переместить файл
        $basepath = '../secured/shopimport/';
        $name = 'import3.xls';
        @unlink($basepath.$name);
        if (!move_uploaded_file($tmpfile, $basepath . $name)) return new Response('<p>Ошибка сохранения файла</p>');
        // Вывод
        return $this->render('ExtendedShopImportBundle:Default:importThreeTabStep1.html.twig', array(
            'cellmatrix' => $cellmatrix
        ));
    }
    
    
    public function import3LoadTableCfgAction() 
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        // Принять настройки
        $tablerowcfg = $this->getRequest()->get('tablerowcfg');
        if (!is_array($tablerowcfg)) $tablerowcfg = array();
        foreach ($tablerowcfg as $key=>$val)
        {
            if (!in_array($val, array('title','description','price','onStock','avatar','article','category'))) unset($tablerowcfg[$key]);
        }
        if ((!in_array('title', $tablerowcfg)) || (!in_array('price',$tablerowcfg))) return new Response('<p>Для импорта должны быть указаны как минимум название и цена товара</p>');
        // Считать файл
        $basepath = '../secured/shopimport/';
        $name = 'import3.xls';
        $filetype = \PHPExcel_IOFactory::identify($basepath.$name);
        $reader = \PHPExcel_IOFactory::createReader($filetype);
        $reader->setReadDataOnly(true);
        $excel = $reader->load($basepath.$name);
        $sheet = $excel->getSheet();
        // Найти уникальные значения категорий и статуса
        //$sheet = new \PHPExcel_Worksheet();
        $findcategories = array();
        $findstatuses = array();
        $productcount = 0;
        $maxrow = $sheet->getHighestRow();
        $autocategory = '';
        for ($y = 1; $y <= $maxrow; $y++)
        {
            $productdata = array();
            $productdata['title'] = '';
            $productdata['description'] = '';
            $productdata['price'] = '';
            $productdata['onStock'] = '';
            $productdata['avatar'] = '';
            $productdata['article'] = '';
            $productdata['category'] = '';
            for ($x = 0; $x < 20; $x++)
            {
                $productdata['category'] = $autocategory;
                if (isset($tablerowcfg[$x])) $productdata[$tablerowcfg[$x]] = $sheet->getCellByColumnAndRow($x, $y)->getValue();
            }
            $productdata['price'] = str_replace(',','.',$productdata['price']);
            if (!preg_match('/^\d+((\,|\.)(\d+))?$/ui', $productdata['price'])) $productdata['price'] = '';
            
            if (($productdata['title'] != '') && ($productdata['price'] != ''))
            {
                $productcount++;
                if (!in_array($productdata['onStock'], $findstatuses)) $findstatuses[] = $productdata['onStock'];
                $findcatyes = false;
                foreach ($findcategories as &$findcat) 
                {
                    if ($findcat['name'] == $productdata['category']) {$findcat['count']++; $findcatyes = true; break;}
                }
                unset($findcat);
                if ($findcatyes == false)
                {
                    $findcategories[] = array('name'=>$productdata['category'], 'count'=>1);
                }
            } else
            {
                if ($sheet->getCellByColumnAndRow(0, $y)->getValue() != '') $autocategory = $sheet->getCellByColumnAndRow(0, $y)->getValue();
            }
        }
        // Поиск валют
        $query = $em->createQuery('SELECT c.id, c.name FROM ShopProductBundle:ProductCurrency c ORDER BY c.main DESC, c.id ASC');
        $currencies = $query->getResult();
        $curcfg = $this->get('cms.cmsManager')->getConfigValue('shop.import.three.currency');
        // Найти наши категории
        $ourcategories = array();
        if ($this->container->has('object.taxonomy'))
        {
            $query = $em->createQuery('SELECT t.id, t.title, t.parent, t.enabled, t.enableAdd FROM BasicCmsBundle:Taxonomies t WHERE t.object = :object ORDER BY t.ordering')->setParameter('object', 'object.product');
            $tree = $query->getResult();
            $this->taxonomyGetTree($tree, null, 0, $ourcategories);
        }
        // Параметры страницы
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.product','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.product.view');
        $modules = $query->getResult();
        $templatecfg = $this->get('cms.cmsManager')->getConfigValue('shop.import.three.template');
        $layoutcfg = $this->get('cms.cmsManager')->getConfigValue('shop.import.three.layout');
        $modulecfg = explode(',',$this->get('cms.cmsManager')->getConfigValue('shop.import.three.modules'));
        if ((!is_array($modulecfg)) || (count($modulecfg) == 0))
        {
            $modulecfg = array();
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $modulecfg[] = $module['id'];
        }
        // Вывод
        return $this->render('ExtendedShopImportBundle:Default:importThreeTabStep2.html.twig', array(
            'productcount' => $productcount,
            'findstatuses' => $findstatuses,
            'findcategories' => $findcategories,
            'ourcategories' => $ourcategories,
            'tablerowcfg' => $tablerowcfg,
            'currencies' => $currencies,
            'templates' => $templates,
            'layouts' => $layouts,
            'modules' => $modules,
            'curcfg' => $curcfg,
            'templatecfg' => $templatecfg,
            'layoutcfg' => $layoutcfg,
            'modulecfg' => $modulecfg
        ));
    }
    
    public function import3ProcessBlockAction() 
    {
        $mode = 'all';
        $em = $this->container->get('doctrine')->getEntityManager();
        // Загрузить файл
        $basepath = '../secured/shopimport/';
        $name = 'import3.xls';
        $filetype = \PHPExcel_IOFactory::identify($basepath.$name);
        $reader = \PHPExcel_IOFactory::createReader($filetype);
        $reader->setReadDataOnly(true);
        $excel = $reader->load($basepath.$name);
        $sheet = $excel->getSheet();
        // Записать настройки
        $categorycfg = $this->getRequest()->get('categories');
        $statuscfg = $this->getRequest()->get('statuses');
        $tablerowcfg = $this->getRequest()->get('tablerowcfg');
        if (!is_array($tablerowcfg)) $tablerowcfg = array();
        foreach ($tablerowcfg as $key=>$val)
        {
            if (!in_array($val, array('title','description','price','onStock','avatar','article','category'))) unset($tablerowcfg[$key]);
        }
        
        $curpost = intval($this->getRequest()->get('currency'));
        $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->find($curpost);
        if (!empty($currency))
        {
            $this->get('cms.cmsManager')->setConfigValue('shop.import.three.currency', $curpost);
        } else
        {
            $curpost = intval($this->get('cms.cmsManager')->getConfigValue('shop.import.three.currency'));
            $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->find($curpost);
            if (empty($currency)) $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->findOneBy(array('main'=>1));
        }
        $layout = $this->getRequest()->get('layout');
        if ($layout !== null)
        {
            $this->container->get('cms.cmsManager')->setConfigValue('shop.import.three.layout', $layout);
        } else
        {
            $layout = $this->container->get('cms.cmsManager')->getConfigValue('shop.import.three.layout');
        }
        $template = $this->getRequest()->get('template');
        if ($layout !== null)
        {
            $this->container->get('cms.cmsManager')->setConfigValue('shop.import.three.template', $template);
        } else
        {
            $template = $this->container->get('cms.cmsManager')->getConfigValue('shop.import.three.template');
        }
        $modules = $this->getRequest()->get('modules');
        if (is_array($modules))
        {
            $this->container->get('cms.cmsManager')->setConfigValue('shop.import.three.modules', implode(',', $modules));
        } else
        {
            $modules = explode(',',$this->container->get('cms.cmsManager')->getConfigValue('shop.import.three.modules'));
            if (!is_array($modules)) $modules = array();
        }
        // Обработать файлы
        $starttime = time();
        $start = intval($this->getRequest()->get('start'));
        $savestart = $start;
        $findcategories = array();
        $findstatuses = array();
        $maxrow = $sheet->getHighestRow();
        $autocategory = '';
        for ($y = 1; $y <= $maxrow; $y++)
        {
            $productdata = array();
            $productdata['title'] = '';
            $productdata['description'] = '';
            $productdata['price'] = '';
            $productdata['onStock'] = '';
            $productdata['avatar'] = '';
            $productdata['article'] = '';
            $productdata['category'] = '';
            for ($x = 0; $x < 20; $x++)
            {
                $productdata['category'] = $autocategory;
                if (isset($tablerowcfg[$x])) $productdata[$tablerowcfg[$x]] = $sheet->getCellByColumnAndRow($x, $y)->getValue();
            }
            $productdata['price'] = str_replace(',','.',$productdata['price']);
            if (!preg_match('/^\d+((\,|\.)(\d+))?$/ui', $productdata['price'])) $productdata['price'] = '';
            if (($productdata['title'] != '') && ($productdata['price'] != ''))
            {
                if (!in_array($productdata['onStock'], $findstatuses)) $findstatuses[] = $productdata['onStock'];
                $findcatyes = false;
                foreach ($findcategories as &$findcat) 
                {
                    if ($findcat['name'] == $productdata['category']) {$findcat['count']++; $findcatyes = true; break;}
                }
                unset($findcat);
                if ($findcatyes == false)
                {
                    $findcategories[] = array('name'=>$productdata['category'], 'count'=>1);
                }
                $findcatid = null;
                foreach ($findcategories as $findcatkey=>$findcat2) 
                {
                    if ($findcat2['name'] == $productdata['category']) {$findcatid = $findcatkey; break;}
                }
                if (($findcatid !== null) && (isset($categorycfg[$findcatid])) && ($categorycfg[$findcatid] != '')) $findcatid = $categorycfg[$findcatid]; else $findcatid = null;
                $findstatusid = null;
                foreach ($findstatuses as $findstatuskey=>$findstatusname)
                {
                    if ($findstatusname == $productdata['onStock']) {$findstatusid = $findstatuskey; break;}
                }
                if (($findstatusid !== null) && (isset($statuscfg[$findstatusid]))) $findstatusid = intval($statuscfg[$findstatusid]); else $findstatusid = null;
                if (($y >= $savestart) && ($findcatid !== null) && ($findstatusid !== null))
                {
                    if ((time() - $starttime) > 20) break;
                    if ($y > $savestart + 30) break;
                    // Добавление товара
                    $query = $em->createQuery('SELECT max(p.ordering) as maxorder FROM ShopProductBundle:Products p');
                    $order = $query->getResult();
                    if (isset($order[0]['maxorder'])) $order = $order[0]['maxorder'] + 1; else $order = 1;
                    $productent = new \Shop\ProductBundle\Entity\Products();
                    $em->persist($productent);
                    $productent->setCreateDate(new \DateTime('now'));
                    $productent->setCreaterId($this->getUser()->getId());
                    $productent->setDepth(null);
                    $productent->setDiscount(0);
                    $productent->setEnabled(1);
                    $productent->setHeight(null);
                    $productent->setManufacturer('');
                    $productent->setMetaDescription('');
                    $productent->setMetaKeywords('');
                    $productent->setOrdering($order);
                    $productent->setRating(null);
                    $productent->setWeight(null);
                    $productent->setWidth(null);
                    $productent->setTemplate(($template !== null ? $template : ''));
                    $productent->setArticle($productdata['article']);
                    $avatar = '';
                    if (strtolower(substr($productdata['avatar'],0,7)) == 'http://') 
                    {
                        $ch = curl_init();  
                        curl_setopt($ch, CURLOPT_URL, $productdata['avatar']);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                        $photobuffer = curl_exec($ch);
                        curl_close($ch);
                        $basepathimg = '../images/product/';
                        $nameimg = $this->getUser()->getId().'_'.md5(time() + rand()).'.jpg';
                        if (@file_put_contents($basepathimg.$nameimg, $photobuffer)) 
                        {
                            if (@getimagesize($basepathimg.$nameimg)) 
                            {
                                $avatar = '/images/product/'.$nameimg;
                            } else
                            {
                                @unlink($basepathimg.$nameimg);
                            }
                        }
                        
                    }
                    $productent->setAvatar($avatar);
                    $productent->setDescription($productdata['description']);
                    $productent->setTitle($productdata['title']);
                    $productent->setModifyDate(new \DateTime('now'));
                    $productent->setOnStock($findstatusid);
                    $productent->setPrice(floatval($productdata['price']) / $currency->getExchangeIndex());
                    $em->flush();
                    // Создать страницу товара
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $pageent->setBreadCrumbs('');
                    $pageent->setBreadCrumbsRel('');
                    $pageent->setDescription('Просмотр продукта '.$productent->getTitle());
                    $pageent->setUrl('');
                    $pageent->setTemplate(($layout !== null ? $layout : ''));
                    $pageent->setModules(implode(',', $modules));
                    $pageent->setLocale('');
                    $pageent->setContentType('object.product');
                    $pageent->setContentId($productent->getId());
                    $pageent->setContentAction('view');
                    $em->persist($pageent);
                    $em->flush();
                    $pageent->setUrl($pageent->getId());
                    $em->flush();
                    // Создать ассоциации с категориями
                    $taxent = $em->getRepository('BasicCmsBundle:Taxonomies')->findOneBy(array('object'=>'object.product', 'id'=>intval($findcatid)));
                    if (!empty($taxent))
                    {
                        $linkent = new \Basic\CmsBundle\Entity\TaxonomiesLinks();
                        $linkent->setItemId($productent->getId());
                        $linkent->setTaxonomyId(intval($findcatid));
                        $em->persist($linkent);
                        $em->flush();
                    }
                    // Готово!
                }
            } else
            {
                if ($sheet->getCellByColumnAndRow(0, $y)->getValue() != '') $autocategory = $sheet->getCellByColumnAndRow(0, $y)->getValue();
            }
            $start = $y + 1;
        }
        // Вывести результат
        $result = array();
        $result['result'] = 'OK';
        $result['status'] = ($start >= $maxrow ? 'Fine' : 'OK');
        $result['start'] = $start;
        $result['percent'] = intval($start / $maxrow * 100);
        return new Response(json_encode($result));
    }    
    
    
    
}


