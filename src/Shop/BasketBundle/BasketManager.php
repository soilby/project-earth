<?php

namespace Shop\BasketBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class BasketManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'addone.product.basket';
    }
    
    public function getDescription()
    {
        return 'Корзина продуктов';
    }

    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addAdminMenu('Просмотр заказов', $this->container->get('router')->generate('shop_basket_orderlist'), 90, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('basket_orderlist'), 'Магазин');
        $manager->addAdminMenu('Настройки корзины', $this->container->get('router')->generate('shop_basket_list'), 100, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('basket_configview'), 'Магазин');
    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        
        $manager->addRole('basket_orderlist','Просмотр заказов');
        $manager->addRole('basket_ordereditnew','Обработка новых заказов');
        $manager->addRole('basket_ordereditall','Обработка всех заказов');
        $manager->addRole('basket_configview','Просмотр настроек корзины');
        $manager->addRole('bakset_configedit','Изменение настроек корзины');
    }
// ************************************
// Обработка административной части
// avtionType (validate, tab, save)
// ************************************
    public function getAdminController($request, $action, $actionId, $actionType)
    {
        return null;
    }

    public function getAdminModuleController($request, $module, $actionType, $nullparameters = null)
    {
        if ($module == 'basket')
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $parameters = array();
            $parameters['basketId'] = '';
            $parameters['fields'] = array();
            if (isset($nullparameters['basketId'])) $parameters['basketId'] = $nullparameters['basketId'];
            if (isset($nullparameters['fields']) && (is_array($nullparameters['fields']))) $parameters['fields'] = $nullparameters['fields'];
            $parameterserror['basketId'] = '';
            $parameterserror['fields'] = '';
            $fieldlist = $this->container->get('object.product')->getTaxonomyParameters();
            $query = $em->createQuery('SELECT b.id, b.names as name, b.enabled FROM ShopBasketBundle:ProductBaskets b ORDER BY b.id');
            $basketpages = $query->getResult();
            foreach ($basketpages as &$basketpage) 
            {
                $basketpage['name'] = @unserialize($basketpage['name']);
                if (isset($basketpage['name'][0])) $basketpage['name'] = $this->container->get('cms.cmsManager')->decodeLocalString($basketpage['name'][0], 'default', true); else $basketpage['name'] = '';
            }
            $errors = false;
            if ($request->getMethod() == 'POST')
            {
                $postparameters = $request->get('parameters');
                if (isset($postparameters['basketId'])) $parameters['basketId'] = $postparameters['basketId'];
                $parameters['fields'] = array();
                if (isset($postparameters['fields']) && (is_array($postparameters['fields']))) 
                {
                    foreach ($postparameters['fields'] as $field) if (isset($fieldlist[$field])) $parameters['fields'][] = $field;
                }
                $search = false;
                foreach ($basketpages as $page) if ($page['id'] == $parameters['basketId']) $search = true;
                if ($search == false) {$errors = true; $parameterserror['basketId'] = 'Выберите страницу корзины';}
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
                return $this->container->get('templating')->render('ShopBasketBundle:Default:moduleBasket.html.twig',array('parameters' => $parameters,'parameterserror' => $parameterserror,'fieldlist' => $fieldlist, 'basketpages' => $basketpages));
            }
        }
        return null;
    }
// ************************************
// Обработка фронт части
// ************************************
     public function getTemplateTwig($contentType, $template)
     {
         if ($contentType == 'basket') return 'ShopBasketBundle:Front:basketstep.html.twig';
         return null;
     }

     public function getModuleTemplateTwig($contentType, $template)
     {
         if ($contentType == 'basket') return 'ShopBasketBundle:Front:modulebasket.html.twig';
         return null;
     }
     
     public function getContentTypes(&$contents)
     {
         $contents['basket'] = 'Просмотр корзины продуктов';
     }

     public function getFrontContent($locale, $contentType, $contentId, $result, &$params)
     {
         if ($contentType == 'view')
         {
             $basket = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_basket');
             if ((!isset($basket['products'])) || (!is_array($basket['products']))) $basket['products'] = array();
             $productIds = array();
             foreach ($basket['products'] as $item) $productIds[] = $item['id'];
             $params['productBasket'] = $productIds;
         }
         if ($contentType == 'basket')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             // Находим страницу 
             $query = $em->createQuery('SELECT b.names, b.stepCount, b.templates, b.sendEmail, b.email FROM ShopBasketBundle:ProductBaskets b WHERE b.id = :id AND b.enabled != 0')->setParameter('id', $contentId);
             $basketpage = $query->getResult();
             if (empty($basketpage)) return null;
             if (isset($basketpage[0])) $basketpage = $basketpage[0]; else return null;
             $page = $this->container->get('request_stack')->getCurrentRequest()->get('page');
             if (strtolower($page) == 'check') $page = 'check'; 
             elseif (strtolower($page) == 'pay') $page = 'pay'; 
             else
             {
                 $page = intval($page);
                 if ($page < 0) $page = 0;
                 if ($page >= $basketpage['stepCount']) $page = $basketpage['stepCount'] - 1;
             }
             // Проверяем пришедшие данные
             $errors = array();
             if (($this->container->get('request_stack')->getCurrentRequest()->get('products') !== null) || ($this->container->get('request_stack')->getCurrentRequest()->get('shipment') !== null) ||
                 ($this->container->get('request_stack')->getCurrentRequest()->get('payment') !== null) || ($this->container->get('request_stack')->getCurrentRequest()->get('parameters') !== null))
             {
                 $sessbasket = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_basket');
                 if (!is_array($sessbasket)) $sessbasket = array();
                 if (($this->container->get('request_stack')->getCurrentRequest()->get('products') !== null) && (is_array($this->container->get('request_stack')->getCurrentRequest()->get('products'))))
                 {
                     $postproducts = $this->container->get('request_stack')->getCurrentRequest()->get('products');
                     // Преобразовать массив
                     $sessproducts = array();
                     $productIds = array();
                     foreach ($postproducts as $postprod)
                     {
                         $item = array();
                         if (isset($postprod['id']) && (isset($postprod['count']))) 
                         {
                             $item['id'] = $postprod['id']; 
                             $item['count'] = $postprod['count'];
                             $item['options'] = array();
                             $productIds[] = $postprod['id'];
                             if (isset($postprod['options']) && is_array($postprod['options']) && (count($postprod['options']) > 0))
                             {
                                 $productoptions = $this->container->get('object.product')->getProductOptions(intval($postprod['id']), $locale);
                                 foreach ($productoptions as $option)
                                 {
                                     foreach ($postprod['options'] as $getoptionname => $getoptionval)
                                     {
                                         if (($getoptionname == $option['name']) && (isset($option['items'][$getoptionval])))
                                         {
                                             $item['options'][$getoptionname] = $getoptionval;
                                         }
                                     }
                                 }
                             }
                             $sessproducts[] = $item;
                         }
                     }
                     // Проверить продукты
                     if (count($productIds) > 0)
                     {
                         $query = $em->createQuery('SELECT p.id FROM ShopProductBundle:Products p WHERE p.id IN (:ids) AND p.enabled != 0')->setParameter('ids', $productIds);
                         $products = $query->getResult();
                     } else $products = array();
                     $allcount = 0;
                     foreach ($sessproducts as $key => $sessprod)
                     {
                         if (intval($sessprod['count']) < 0) $sessprod['count'] = 0;
                         $founded = false;
                         foreach ($products as $product) if ($product['id'] == $sessprod['id']) $founded = true;
                         if ($founded == true) 
                         {
                             $sessproducts[$key]['count'] = intval($sessprod['count']);
                             $allcount += intval($sessprod['count']);
                         } else unset($sessproducts[$key]);
                     }
                     $sessbasket['products'] = $sessproducts;
                     if ((count($sessproducts) == 0) || ($allcount == 0))
                     {
                         $errors['basket'] = 'Empty';
                     }
                     unset($products);
                     unset($postproducts);
                     unset($sessproducts);
                 }
                 if ($this->container->get('request_stack')->getCurrentRequest()->get('shipment') !== null)
                 {
                     $sessbasket['shipment'] = $this->container->get('request_stack')->getCurrentRequest()->get('shipment');
                     if (!$this->container->has('plugin.basket.shipment.'.$sessbasket['shipment'])) $errors['shipment'] = 'Not found';
                 }
                 if ($this->container->get('request_stack')->getCurrentRequest()->get('payment') !== null)
                 {
                     $sessbasket['payment'] = $this->container->get('request_stack')->getCurrentRequest()->get('payment');
                     if (!$this->container->has('plugin.basket.payment.'.$sessbasket['payment'])) $errors['payment'] = 'Not found';
                 }
                 if (($this->container->get('request_stack')->getCurrentRequest()->get('parameters') !== null) && (is_array($this->container->get('request_stack')->getCurrentRequest()->get('parameters'))))
                 {
                     $postparameters = $this->container->get('request_stack')->getCurrentRequest()->get('parameters');
                     $sessbasket['parameters'] = array();
                     $query = $em->createQuery('SELECT f.id, f.techName, f.type, f.parameters FROM ShopBasketBundle:ProductBasketFields f WHERE f.enabled != 0 ORDER BY f.ordering');
                     $fields = $query->getResult();
                     if (!is_array($fields)) $fields = array();
                     foreach ($fields as $field)
                     {
                         if (isset($postparameters[$field['techName']]))
                         {
                             $sessbasket['parameters'][$field['techName']] = $postparameters[$field['techName']];
                             if (($field['type'] == 'text') || ($field['type'] == 'textarea'))
                             {
                                 $regularExp = $this->container->get('cms.cmsManager')->decodeLocalString($field['parameters'], $locale, true);
                                 if (!preg_match('/^'.$regularExp.'$/ui', $sessbasket['parameters'][$field['techName']])) $errors['parameters'][$field['techName']] = 'Error';
                             }
                             if (($field['type'] == 'radio') || ($field['type'] == 'select'))
                             {
                                 $linefind = false;
                                 $lines = preg_split('/\\r\\n?|\\n/', $this->container->get('cms.cmsManager')->decodeLocalString($field['parameters'], $locale, true));
                                 if (is_array($lines))
                                 {
                                     foreach ($lines as $line) if ((trim($line) != '') && ($sessbasket['parameters'][$field['techName']] == trim($line))) $linefind = true;
                                 }
                                 if ($linefind == false) $errors['parameters'][$field['techName']] = 'Error';
                             }
                         }
                     }
                     unset($fields);
                 }
                 $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_shop_product_basket', $sessbasket);
                 unset($sessbasket);
                 // переходим на следующий шаг
                 if (count($errors) == 0)
                 {
                     $newpage = $this->container->get('request_stack')->getCurrentRequest()->get('nextpage');
                     if ($newpage === null) $newpage = $page + 1;
                     $newpage = intval($newpage);
                     if ($newpage < 0) $newpage = 0;
                     if ($newpage >= $basketpage['stepCount']) $newpage = $basketpage['stepCount'] - 1;
                     $page = $newpage;
                 }
             }
             // Проводим финализацию
             if (($this->container->get('request_stack')->getCurrentRequest()->get('finalize') === 'true') || ($this->container->get('request_stack')->getCurrentRequest()->get('finalize') === 'quick'))
             {
                 $errors = array();
                 $sessbasket = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_basket');
                 if (!is_array($sessbasket)) $sessbasket = array();
                 if (!isset($sessbasket['products']) || !is_array($sessbasket['products'])) $sessbasket['products'] = array();
                 $productIds = array();
                 foreach ($sessbasket['products'] as $proditem) $productIds[] = $proditem['id'];
                 if (count($productIds) > 0)
                 {
                     $query = $em->createQuery('SELECT p.id, p.title, p.price, p.discount FROM ShopProductBundle:Products p WHERE p.id IN (:ids) AND p.enabled != 0')->setParameter('ids', $productIds);
                     $products = $query->getResult();
                 } else $products = array();
                 $allcount = 0;
                 foreach ($sessbasket['products'] as $proditem)
                 {
                     $founded = false;
                     foreach ($products as $product) if ($product['id'] == $proditem['id']) $founded = true;
                     if ($founded == true) 
                     {
                         $allcount += intval($proditem['count']);
                     }
                 }
                 if ((count($sessbasket['products']) == 0) || ($allcount == 0))
                 {
                     $errors['basket'] = 'Empty';
                 }
                 if ((!$this->container->has('plugin.basket.shipment.'.$sessbasket['shipment'])) || ($this->container->get('plugin.basket.shipment.'.$sessbasket['shipment'])->getEnabled() == 0)) $errors['shipment'] = 'Not found';
                 if ((!$this->container->has('plugin.basket.payment.'.$sessbasket['payment'])) || ($this->container->get('plugin.basket.payment.'.$sessbasket['payment'])->getEnabled() == 0)) $errors['payment'] = 'Not found';

                 $query = $em->createQuery('SELECT f.id, f.techName, f.name, f.type, f.parameters, f.defaultEnable, f.defaultValue FROM ShopBasketBundle:ProductBasketFields f WHERE f.enabled != 0 ORDER BY f.ordering');
                 $fields = $query->getResult();
                 if (!is_array($fields)) $fields = array();
                 foreach ($fields as $field)
                 {
                     if (isset($sessbasket['parameters'][$field['techName']]))
                     {
                         if (($field['type'] == 'text') || ($field['type'] == 'textarea'))
                         {
                             $regularExp = $this->container->get('cms.cmsManager')->decodeLocalString($field['parameters'], $locale, true);
                             if ((!preg_match('/^'.$regularExp.'$/ui', $sessbasket['parameters'][$field['techName']])) && ($this->container->get('request_stack')->getCurrentRequest()->get('finalize') !== 'quick')) $errors['parameters'][$field['techName']] = 'Error';
                         }
                         if (($field['type'] == 'radio') || ($field['type'] == 'select'))
                         {
                             $linefind = false;
                             $lines = preg_split('/\\r\\n?|\\n/', $this->container->get('cms.cmsManager')->decodeLocalString($field['parameters'], $locale, true));
                             if (is_array($lines))
                             {
                                 foreach ($lines as $line) if ((trim($line) != '') && ($sessbasket['parameters'][$field['techName']] == trim($line))) $linefind = true;
                             }
                             if (($linefind == false) && ($this->container->get('request_stack')->getCurrentRequest()->get('finalize') !== 'quick')) $errors['parameters'][$field['techName']] = 'Error';
                         }
                     } elseif ($field['defaultEnable'] != 0)
                     {
                         $sessbasket['parameters'][$field['techName']] = $field['defaultValue'];
                     } else 
                     {
                         if ($this->container->get('request_stack')->getCurrentRequest()->get('finalize') !== 'quick') $errors['parameters'][$field['techName']] = 'Error'; else $sessbasket['parameters'][$field['techName']]='';
                     }
                 }
                 if (count($errors) == 0)
                 {
                     $query = $em->createQuery('SELECT c.id, c.minNote, c.exchangeIndex, c.main, c.prefix, c.postfix, c.decimalSigns, c.decimalSep, c.thousandSep FROM ShopProductBundle:ProductCurrency c');
                     $allcurrencies = $query->getResult();
                     if (!is_array($allcurrencies)) $allcurrencies = array();
                     $order = new \Shop\BasketBundle\Entity\ProductOrders();
                     $order->setCreateDate(new \DateTime('now'));
                     $orderfields = array();
                     foreach ($fields as $field)
                     {
                         $orderfields[] = array('name' => $this->container->get('cms.cmsManager')->decodeLocalString($field['name'], 'default', true), 'value' => (isset($sessbasket['parameters'][$field['techName']]) ? $sessbasket['parameters'][$field['techName']] : ''));
                     }
                     $order->setFields(serialize($orderfields));
                     $order->setIpAddress($this->container->get('request_stack')->getCurrentRequest()->getClientIp());
                     $order->setLocale($locale);
                     $order->setShipSystem($sessbasket['shipment']);
                     $order->setPayInfo('');
                     $order->setPayStatus(0);
                     $order->setPaySystem($sessbasket['payment']);
                     $orderproduct = array();
                     foreach ($sessbasket['products'] as $proditem)
                     {
                         $founded = false;
                         foreach ($products as $product) if ($product['id'] == $proditem['id']) 
                         {
                             $priceCurrencies = array();
                             foreach ($allcurrencies as $currency)
                             {
                                 $priceCurrencies[$currency['id']] = round((($product['price'] * (1 - $product['discount'] / 100)) * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'];
                             }
                             // Проверка и заполнение опций
                             $options = array();
                             if (isset($proditem['options']) && (count($proditem['options']) > 0))
                             {
                                 $productoptions = $this->container->get('object.product')->getProductOptions($proditem['id'], 'default');
                                 foreach ($proditem['options'] as $getoptionname => $getoptionval)
                                 {
                                     foreach ($productoptions as $option)
                                     {
                                         if (($getoptionname == $option['name']) && (isset($option['items'][$getoptionval])))
                                         {
                                             $options[] = array('name' => $option['name'], 'type' => $option['type'], 'title' => $option['title'], 'valueRaw' => $getoptionval, 'value' => $option['items'][$getoptionval]);
                                         }
                                     }                                     
                                 }
                             }
                             $orderproduct[] = array('id' => $proditem['id'], 'title' => $product['title'], 'price' => $product['price'] * (1 - $product['discount'] / 100), 'count' => $proditem['count'], 'priceCurrencies' => $priceCurrencies, 'options' => $options);
                         }
                     }
                     $order->setProducts(serialize($orderproduct));
                     $order->setStatus(0);
                     $sessbasket['products'] = $orderproduct;
                     $sessbasket['summ'] = 0;
                     foreach ($orderproduct as $product) 
                     {
                         $sessbasket['summ'] += $product['price'] * $product['count'];
                     }
                     $sessbasket['summInfo'] = array();
                     $this->container->get('plugin.basket.shipment.'.$sessbasket['shipment'])->calcBasketInfo($sessbasket);
                     $this->container->get('plugin.basket.payment.'.$sessbasket['payment'])->calcBasketInfo($sessbasket);
                     $cmsservices = $this->container->getServiceIds();
                     foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
                     {
                         $this->container->get($item)->calcBasketInfo($sessbasket);
                     }
                     foreach ($sessbasket['summInfo'] as &$summinfo) 
                     {
                         $sessbasket['summ'] += $summinfo['orderPrice'];
                         $priceCurrencies = array();
                         foreach ($allcurrencies as $currency)
                         {
                             $priceCurrencies[$currency['id']] = round(($summinfo['orderPrice'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'];
                         }
                         $summinfo['priceCurrencies'] = $priceCurrencies;
                     }
                     unset($summinfo);
                     $sessbasket['summCurrencies'] = array();
                     $mainSumm = '';
                     foreach ($allcurrencies as $currency)
                     {
                         $sessbasket['summCurrencies'][$currency['id']] = 0;
                         foreach ($orderproduct as $product) 
                         {
                             $sessbasket['summCurrencies'][$currency['id']] += round(($product['price'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'] * $product['count'];
                         }
                         foreach ($sessbasket['summInfo'] as $summinfo) 
                         {
                             $sessbasket['summCurrencies'][$currency['id']] += round(($summinfo['orderPrice'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'];
                         }
                         if ($currency['main'] != 0) $mainSumm = $currency['prefix'].number_format($sessbasket['summCurrencies'][$currency['id']], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                     }
                     $order->setSummCurrencies(serialize($sessbasket['summCurrencies']));
                     $order->setSumm($sessbasket['summ']);
                     $order->setSummInfo(serialize($sessbasket['summInfo']));
                     $em->persist($order);
                     $em->flush();
                     if ($basketpage['sendEmail'] != 0)
                     {
                         $mailer = $this->container->get('mailer');
                         $messages = \Swift_Message::newInstance()
                                 ->setSubject('Новый заказ')
                                 ->setFrom('products@'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost())
                                 ->setTo($basketpage['email'])
                                 ->setContentType('text/html')
                                 ->setBody($this->container->get('templating')->render('ShopBasketBundle:Default:email.html.twig', array(
                                     'title' => (isset($basketpagenames[0]) ? $this->container->get('cms.cmsManager')->decodeLocalString($basketpagenames[0], 'default', true) : ''),
                                     'url' => 'http://'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost().$this->container->get('router')->generate('shop_basket_orderview').'?id='.$order->getId(),
                                     'fields' => $orderfields,
                                     'products' => $orderproduct,
                                     'summ' => $mainSumm,
                                     'shipment' => $this->container->get('plugin.basket.shipment.'.$sessbasket['shipment'])->getDescription(),
                                     'payment' => $this->container->get('plugin.basket.payment.'.$sessbasket['payment'])->getDescription())));
                         $mailer->send($messages);
                     }
                     $sessbasket = array();
                     $sessbasket['orderId'] = $order->getId();
                     $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_shop_product_basket', $sessbasket);
                     $page = 'pay';
                     // Запись в лог
                     $this->container->get('cms.cmsManager')->logPushFront(0, $this->getName().'.create', 'Создание заказа в магазине', $this->container->get('router')->generate('shop_basket_orderview').'?id='.$order->getId());
                 }
                 unset($fields);
                 unset($sessbasket);
             }
             // Считываем данные корзины
             $basketpagenames = @unserialize($basketpage['names']);
             $basketpagetemplates = @unserialize($basketpage['templates']);
             if (($page !== 'check') && ($page !== 'pay'))
             {
                 $params['name'] = (isset($basketpagenames[$page]) ? $this->container->get('cms.cmsManager')->decodeLocalString($basketpagenames[$page], $locale, true) : '');
                 $params['template'] = (isset($basketpagetemplates[$page]) ? $basketpagetemplates[$page] : '');
                 $sessbasket = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_basket');
                 if (!is_array($sessbasket)) $sessbasket = array();
                 if (!isset($sessbasket['products']) || !is_array($sessbasket['products'])) $sessbasket['products'] = array();
                 $productIds = array();
                 foreach ($sessbasket['products'] as $proditem) $productIds[] = $proditem['id'];
                 // Находим продукты
                 if (count($productIds) > 0)
                 {
                     $query = $em->createQuery('SELECT p.id, p.article, p.createDate, p.modifyDate, p.createrId, u.login as createrLogin, u.fullName as createrFullName, '.
                                               'IF(pl.title is not null, pl.title, p.title) as title, '.
                                               'IF(pl.description is not null, pl.description, p.description) as description, '.
                                               'p.price, p.discount, p.rating, p.height, p.width, p.depth, p.weight, p.manufacturer, p.onStock, '.
                                               'p.template, p.avatar, sp.url FROM ShopProductBundle:Products p '.
                                               'LEFT JOIN ShopProductBundle:ProductsLocale pl WITH pl.productId = p.id AND pl.locale = :locale '.
                                               'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.
                                               'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'view\' AND sp.contentId = p.id '.
                                               'WHERE p.id IN (:id) AND p.enabled != 0')->setParameter('id', $productIds)->setParameter('locale', $locale);
                     $products = $query->getResult();
                     if (!is_array($products)) $products = array();
                     foreach ($products as &$product)
                     {
                         $options = $this->container->get('object.product')->getProductOptions($product['id'], $locale);
                         //foreach ($options as &$option) $option['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($option['title'], $locale, true);
                         //unset($option);
                         $product['options'] = $options;
                     }
                     unset($product);
                 } else
                 {
                     $products = array();
                 }
                 $params['basket']['products'] = array();
                 foreach ($sessbasket['products'] as $proditem)
                 {
                     foreach ($products as $product) 
                         if ($product['id'] == $proditem['id'])
                         {
                             if (!isset($proditem['options']) || !is_array($proditem['options'])) $proditem['options'] = array(); 
                             $params['basket']['products'][] = array_merge($product, array('count' => $proditem['count'], 'optionValues' => $proditem['options']));
                         }
                 }
                 $query = $em->createQuery('SELECT c.name, c.prefix, c.postfix, c.decimalSigns, c.decimalSep, c.thousandSep, c.main, c.minNote, c.exchangeIndex FROM ShopProductBundle:ProductCurrency c WHERE c.enabled != 0 ORDER BY c.main DESC, c.id ASC');
                 $currencies = $query->getResult();
                 if (!is_array($currencies)) $currencies = array();
                 $params['currencies'] = $currencies;
                 foreach ($params['basket']['products'] as &$product)
                 {
                     if (isset($product['url']) && ($product['url'] != '')) $product['url'] = '/'.($locale != '' ? $locale.'/' : '').$product['url'];
                     $product['priceWithDiscount'] = $product['price'] * (1 - $product['discount'] / 100);
                     $product['formatPrice'] = array();
                     $product['formatPriceWithDiscount'] = array();
                     $product['formatOrderPrice'] = array();
                     foreach ($currencies as $currency)
                     {
                         $product['formatPrice'][] = $currency['prefix'].number_format(round(($product['price'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                         $product['formatPriceWithDiscount'][] = $currency['prefix'].number_format(round(($product['priceWithDiscount'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                         $product['formatOrderPrice'][] = $currency['prefix'].number_format(round(($product['priceWithDiscount'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'] * $product['count'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                     }
                 }
                 unset($product);
                 unset($products);
                 // Заполняем способ оплаты и доставки
                 $params['basket']['basketerror'] = (isset($errors['basket']) ? $errors['basket'] : '');
                 $params['basket']['shipmenterror'] = (isset($errors['shipment']) ? $errors['shipment'] : '');
                 $params['basket']['paymenterror'] = (isset($errors['payment']) ? $errors['payment'] : '');
                 if (isset($sessbasket['shipment']) && $this->container->has('plugin.basket.shipment.'.$sessbasket['shipment'])) $params['basket']['shipment'] = $sessbasket['shipment']; else $params['basket']['shipment'] = null;
                 if (isset($sessbasket['payment']) && $this->container->has('plugin.basket.payment.'.$sessbasket['payment'])) $params['basket']['payment'] = $sessbasket['payment']; else $params['basket']['payment'] = null;
                 // Заполняем параметры
                 $query = $em->createQuery('SELECT f.id, f.techName, f.name, f.type, f.parameters, f.defaultEnable FROM ShopBasketBundle:ProductBasketFields f WHERE f.enabled != 0 ORDER BY f.ordering');
                 $fields = $query->getResult();
                 if (!is_array($fields)) $fields = array();
                 foreach ($fields as $field)
                 {
                     $newitem = array();
                     $newitem['id'] = $field['id'];
                     $newitem['techName'] = $field['techName'];
                     $newitem['name'] = $this->container->get('cms.cmsManager')->decodeLocalString($field['name'], $locale, true);
                     $newitem['type'] = $field['type'];
                     $newitem['defaultEnable'] = $field['defaultEnable'];
                     $newitem['value'] = (isset($sessbasket['parameters'][$field['techName']]) ? $sessbasket['parameters'][$field['techName']] : null);
                     $newitem['error'] = (isset($errors['parameters'][$field['techName']]) ? $errors['parameters'][$field['techName']] : '');
                     if (($field['type'] == 'text') || ($field['type'] == 'textarea'))
                     {
                         $newitem['regularExp'] = $this->container->get('cms.cmsManager')->decodeLocalString($field['parameters'], $locale, true);
                         $params['basket']['parameters'][] = $newitem;
                     }
                     if (($field['type'] == 'radio') || ($field['type'] == 'select'))
                     {
                         $newitem['items'] = array();
                         $lines = preg_split('/\\r\\n?|\\n/', $this->container->get('cms.cmsManager')->decodeLocalString($field['parameters'], $locale, true));
                         if (is_array($lines))
                         {
                             foreach ($lines as $line) if (trim($line) != '') $newitem['items'][] = trim($line);
                         }
                         if (count($newitem['items']) > 0) $params['basket']['parameters'][] = $newitem;
                     }
                     if ($field['type'] == 'checkbox')
                     {
                         $params['basket']['parameters'][] = $newitem;
                     }
                     unset($newitem);
                 }
                 unset($fields);
                 // Формируем чек, итоговую стоимость и онлайн-калькулятор
                 $params['basket']['summ'] = 0;
                 $params['basket']['summCurrencies'] = array();
                 $params['basket']['jsCalc'] = '';
                 foreach ($params['basket']['products'] as $productkey => $product) 
                 {
                     $params['basket']['jsCalc'] .= '   basket[\'products\']['.$productkey.'][\'id\'] = '.$product['id'].';'."\r\n";
                     $params['basket']['jsCalc'] .= '   basket[\'products\']['.$productkey.'][\'price\'] = '.$product['priceWithDiscount'].';'."\r\n";
                     $params['basket']['summ'] += $product['priceWithDiscount'] * $product['count'];
                 }
                 foreach ($currencies as $key=>$currency)
                 {
                     $params['basket']['summCurrencies'][$key] = 0;
                     foreach ($params['basket']['products'] as $product) 
                     {
                         $params['basket']['summCurrencies'][$key] += round(($product['priceWithDiscount'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'] * $product['count'];
                     }
                 }
                 $params['basket']['jsCalc'] .= 
                         '   basket[\'summ\'] = 0;'."\r\n".
                         '   basket[\'summCurrencies\'] = [];'."\r\n";
                 foreach ($currencies as $key=>$currency)
                 {
                     $params['basket']['jsCalc'] .= 
                             '         basket[\'summCurrencies\']['.$key.'] = 0;'."\r\n";
                 }
                 $params['basket']['jsCalc'] .= 
                         '   basket[\'summInfo\'] = [];'."\r\n".
                         '   for (i in basket[\'products\'])'."\r\n".
                         '      if ((basket[\'products\'][i][\'price\'] !== undefined) && (basket[\'products\'][i][\'count\'] !== undefined))'."\r\n".
                         '      {'."\r\n".
                         '         basket[\'products\'][i][\'orderPrice\'] = basket[\'products\'][i][\'price\'] * basket[\'products\'][i][\'count\'];'."\r\n".
                         '         basket[\'products\'][i][\'formatOrderPrice\'] = [];'."\r\n".
                         '         basket[\'summ\'] += basket[\'products\'][i][\'orderPrice\'];'."\r\n";
                 foreach ($currencies as $key=>$currency)
                 {
                     $params['basket']['jsCalc'] .= 
                             '   basket[\'summCurrencies\']['.$key.'] += Math.round(basket[\'products\'][i][\'price\'] * '.$currency['exchangeIndex'].' / '.$currency['minNote'].') * '.$currency['minNote'].' * basket[\'products\'][i][\'count\'];'."\r\n".
                             '   basket[\'products\'][i][\'formatOrderPrice\']['.$key.'] = \''.$currency['prefix'].'\'+number_format(Math.round(basket[\'products\'][i][\'price\'] * '.$currency['exchangeIndex'].' / '.$currency['minNote'].') * '.$currency['minNote'].' * basket[\'products\'][i][\'count\'],\''.$currency['decimalSigns'].'\',\''.$currency['decimalSep'].'\',\''.$currency['thousandSep'].'\')+\''.$currency['postfix'].'\';'."\r\n";
                 }
                 $params['basket']['jsCalc'] .= 
                         '      }'."\r\n";
                 $params['basket']['summInfo'] = array();
                 if (isset($sessbasket['shipment']) && $this->container->has('plugin.basket.shipment.'.$sessbasket['shipment'])) 
                 {
                     $this->container->get('plugin.basket.shipment.'.$sessbasket['shipment'])->calcBasketInfo($params['basket']);
                 }
                 if (isset($sessbasket['payment']) && $this->container->has('plugin.basket.payment.'.$sessbasket['payment'])) 
                 {
                     $this->container->get('plugin.basket.payment.'.$sessbasket['payment'])->calcBasketInfo($params['basket']);
                 }
                 $cmsservices = $this->container->getServiceIds();
                 foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
                 {
                     $this->container->get($item)->calcBasketInfo($params['basket']);
                     $this->container->get($item)->getBasketJsCalc($params['basket']['jsCalc'], $params['basket']['products']);
                 }
                 foreach ($params['basket']['summInfo'] as &$summinfo) 
                 {
                     $params['basket']['summ'] += $summinfo['orderPrice'];
                     foreach ($currencies as $key=>$currency)
                     {
                         $params['basket']['summCurrencies'][$key] += round(($summinfo['orderPrice'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'];
                         $summinfo['formatOrderPrice'][] = $currency['prefix'].number_format(round(($summinfo['orderPrice'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                     }
                 }
                 foreach ($currencies as $key=>$currency)
                 {
                     $params['basket']['formatSumm'][] = $currency['prefix'].number_format($params['basket']['summCurrencies'][$key], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                 }
                 $params['shipments'] = array();
                 foreach ($cmsservices as $item) if (strpos($item,'plugin.basket.shipment.') === 0) 
                     if ($this->container->get($item)->getEnabled() != 0) 
                     {
                         $params['shipments'][substr($item, 23)] = $this->container->get($item)->getShipmentInfo();
                         $this->container->get($item)->getBasketJsCalc($params['basket']['jsCalc'], $params['basket']['products']);
                     }
                 $params['payments'] = array();
                 foreach ($cmsservices as $item) if (strpos($item,'plugin.basket.payment.') === 0) 
                     if ($this->container->get($item)->getEnabled() != 0) 
                     {
                         $params['payments'][substr($item, 22)] = $this->container->get($item)->getPaymentInfo();
                         $this->container->get($item)->getBasketJsCalc($params['basket']['jsCalc'], $params['basket']['products']);
                     }
                 $params['basket']['jsCalc'] .= 
                         '   basket[\'formatSummInfo\'] = [];'."\r\n".
                         '   for (i in basket[\'summInfo\'])'."\r\n".
                         '   {'."\r\n".
                         '      basket[\'formatSummInfo\'][i] = [];'."\r\n".
                         '      basket[\'summ\'] += basket[\'summInfo\'][i];'."\r\n";
                 foreach ($currencies as $key=>$currency)
                 {
                     $params['basket']['jsCalc'] .= 
                             '      basket[\'formatSummInfo\'][i]['.$key.'] = \''.$currency['prefix'].'\'+number_format(Math.round(basket[\'summInfo\'][i] * '.$currency['exchangeIndex'].' / '.$currency['minNote'].') * '.$currency['minNote'].',\''.$currency['decimalSigns'].'\',\''.$currency['decimalSep'].'\',\''.$currency['thousandSep'].'\')+\''.$currency['postfix'].'\';'."\r\n".
                             '      basket[\'summCurrencies\']['.$key.'] += Math.round(basket[\'summInfo\'][i] * '.$currency['exchangeIndex'].' / '.$currency['minNote'].') * '.$currency['minNote'].';'."\r\n";
                 }
                 $params['basket']['jsCalc'] .= 
                         '   }'."\r\n";
                 $params['basket']['jsCalc'] .= 
                         '   basket[\'formatSumm\'] = [];'."\r\n";
                 foreach ($currencies as $key=>$currency)
                 {
                     $params['basket']['jsCalc'] .= 
                             '   basket[\'formatSumm\']['.$key.'] = \''.$currency['prefix'].'\'+number_format(basket[\'summCurrencies\']['.$key.'],\''.$currency['decimalSigns'].'\',\''.$currency['decimalSep'].'\',\''.$currency['thousandSep'].'\')+\''.$currency['postfix'].'\';'."\r\n";
                 }
                 // имя, шаблон, продукты с количеством, данные чека, итоговая цена корзины, онлайн-калькулятор, пармаетры оплаты, доставки и прочие параметры
             }
             if ($page === 'pay')
             {
                 $sessbasket = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_basket');
                 $page = 'check';
                 if (!is_array($sessbasket)) $sessbasket = array();
                 if (isset($sessbasket['orderId'])) 
                 {
                     $params['orderId'] = $sessbasket['orderId'];
                     $order = $em->getRepository('ShopBasketBundle:ProductOrders')->find($sessbasket['orderId']);
                     if ((!empty($order)) && ($this->container->has('plugin.basket.payment.'.$order->getPaySystem())))
                     {
                         $content = $this->container->get('plugin.basket.payment.'.$order->getPaySystem())->getPaymentContent($order);
                         if ($content !== null)
                         {
                             $params['name'] = (isset($basketpagenames['check']) ? $this->container->get('cms.cmsManager')->decodeLocalString($basketpagenames['check'], $locale, true) : '');
                             $params['rawContent'] = $content;
                             $page = 'pay';
                         }
                     }
                 }
             }
             if ($page === 'check')
             {
                 $params['name'] = (isset($basketpagenames['check']) ? $this->container->get('cms.cmsManager')->decodeLocalString($basketpagenames['check'], $locale, true) : '');
                 $params['template'] = (isset($basketpagetemplates['check']) ? $basketpagetemplates['check'] : '');
                 $sessbasket = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_basket');
                 if (!is_array($sessbasket)) $sessbasket = array();
                 if (!isset($sessbasket['orderId'])) 
                 {
                     $params['orderId'] = 0;
                     $params['orderStatus'] = 'Not found';
                 } else
                 {
                     $params['orderId'] = $sessbasket['orderId'];
                     $order = $em->getRepository('ShopBasketBundle:ProductOrders')->find($sessbasket['orderId']);
                     if (empty($order)) $params['orderStatus'] = 'Not found'; 
                     else
                     {
                         if ($order->getPayStatus() != 2) $params['orderStatus'] = 'Not payed'; else $params['orderStatus'] = 'OK';
                     }
                 }
             } 
             $params['page'] = $page;
             $params['meta'] = array('title'=>$params['name'], 'keywords'=>'', 'description'=>'', 'robotindex'=>false);
         }
     }

     public function setFrontActionPrepare($locale, $seoPage, $action, &$result)
     {
     }
     
     public function setFrontAction($locale, $seoPage, $action, &$result)
     {
         if ($action == 'basketadd')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $count = ($this->container->get('request_stack')->getCurrentRequest()->get('actioncount') !== null ? intval($this->container->get('request_stack')->getCurrentRequest()->get('actioncount')) : 1);
             $getoptions = $this->container->get('request_stack')->getCurrentRequest()->get('actionoptions');
             if (!is_array($getoptions)) $getoptions = array();
             $query = $em->createQuery('SELECT count(p.id) as prodcount FROM ShopProductBundle:Products p WHERE p.id = :id AND p.enabled != 0')->setParameter('id', $id);
             $prodcount = $query->getResult();
             if (isset($prodcount[0]['prodcount'])) $prodcount = $prodcount[0]['prodcount']; else $prodcount = 0;
             $basket = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_basket');
             if (!is_array($basket)) $basket = array();
             if (!isset($basket['products']) || !is_array($basket['products'])) $basket['products'] = array();
             if ($prodcount > 0)
             {
                 $getinfo = intval($this->container->get('request_stack')->getCurrentRequest()->get('actiongetinfo'));
                 // Фильтруем пришедшие параметры
                 $options = array();
                 $productoptions = $this->container->get('object.product')->getProductOptions($id, $locale);
                 foreach ($productoptions as $option)
                 {
                     foreach ($getoptions as $getoptionname => $getoptionval)
                     {
                         if (($getoptionname == $option['name']) && (isset($option['items'][$getoptionval])))
                         {
                             $options[$getoptionname] = $getoptionval;
                         }
                     }
                 }
                 // Пробуем найти какой же продукт в корзине
                 $keyfind = null;
                 foreach ($basket['products'] as $key=>$prod)
                 {
                     if ((!isset($prod['options'])) || (!is_array($prod['options']))) $prod['options'] = array();
                     if ($prod['id'] == $id)
                     {
                         // Сравниваем параметры
                         if (count($prod['options']) == count($options))
                         {
                             $arraysequal = true;
                             foreach ($prod['options'] as $optkey=>$optval)
                             {
                                 if (!isset($options[$optkey]) || ($options[$optkey] != $optval)) $arraysequal = false;
                             }
                             if ($arraysequal == true) {$keyfind = $key; break;}
                         }
                     }
                 }
                 // Если продукт найден - добавляем
                 if ($count > 0)
                 {
                     if ($keyfind !== null)
                     {
                         if (!isset($basket['products'][$keyfind]['count'])) $basket['products'][$keyfind]['count'] = 0;
                         $basket['products'][$keyfind]['count'] += $count;
                     } else
                     {
                         $basket['products'][] = array('id' => $id, 'count' => $count, 'options' => $options);
                     }
                     $result['status'] = 'OK';
                     $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_shop_product_basket', $basket);
                 } else $result['status'] = 'Count is not valid';
                 if ($getinfo != 0)
                 {
                     $query = $em->createQuery('SELECT p.id, p.article, p.createDate, p.modifyDate, p.createrId, u.login as createrLogin, u.fullName as createrFullName, '.
                                               'IF(pl.title is not null, pl.title, p.title) as title, '.
                                               'IF(pl.description is not null, pl.description, p.description) as description, '.
                                               'p.price, p.discount, p.rating, p.height, p.width, p.depth, p.weight, p.manufacturer, p.onStock, '.
                                               'p.template, p.avatar, sp.url FROM ShopProductBundle:Products p '.
                                               'LEFT JOIN ShopProductBundle:ProductsLocale pl WITH pl.productId = p.id AND pl.locale = :locale '.
                                               'LEFT JOIN BasicCmsBundle:Users u WITH u.id = p.createrId '.
                                               'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'view\' AND sp.contentId = p.id '.
                                               'WHERE p.id = :id AND p.enabled != 0')->setParameter('id', $id)->setParameter('locale', $locale);
                     $product = $query->getResult();
                     if (isset($product[0])) $result['product'] = $product[0];
                     $query = $em->createQuery('SELECT c.name, c.prefix, c.postfix, c.decimalSigns, c.decimalSep, c.thousandSep, c.main, c.minNote, c.exchangeIndex FROM ShopProductBundle:ProductCurrency c WHERE c.enabled != 0 ORDER BY c.main DESC, c.id ASC');
                     $currencies = $query->getResult();
                     if (!is_array($currencies)) $currencies = array();
                     $result['product']['priceWithDiscount'] = $result['product']['price'] * (1 - $result['product']['discount'] / 100);
                     $result['product']['formatPrice'] = array();
                     $result['product']['formatPriceWithDiscount'] = array();
                     foreach ($currencies as $currency)
                     {
                         $result['product']['formatPrice'][] = $currency['prefix'].number_format(round(($result['product']['price'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                         $result['product']['formatPriceWithDiscount'][] = $currency['prefix'].number_format(round(($result['product']['priceWithDiscount'] * $currency['exchangeIndex']) / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                     }
                 }
             } else $result['status'] = 'Not found';
             $result['count'] = count($basket['products']);
             $result['action'] = 'object.product.basketadd';
             $result['actionId'] = $id;
         }
         if ($action == 'basketremove')
         {
             $basketid = $this->container->get('request_stack')->getCurrentRequest()->get('actionbasketid');
             $id = $this->container->get('request_stack')->getCurrentRequest()->get('actionid');
             $getoptions = $this->container->get('request_stack')->getCurrentRequest()->get('actionoptions');
             if (is_array($getoptions)) $getoptions = array();
             $basket = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_basket');
             if (!is_array($basket)) $basket = array();
             if (!isset($basket['products']) || !is_array($basket['products'])) $basket['products'] = array();
             if ($basketid !== null)
             {
                 if (isset($basket['products'][$basketid]))
                 {
                     unset($basket['products'][$basketid]);
                     $result['status'] = 'OK';
                     $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_shop_product_basket', $basket);
                 } else $result['status'] = 'Not found';
                 $result['count'] = count($basket['products']);
                 $result['action'] = 'object.product.basketremove';
             } else
             {
                 // ищем продукт по параметрам и id
                 $options = array();
                 $productoptions = $this->container->get('object.product')->getProductOptions($id, $locale);
                 foreach ($productoptions as $option)
                 {
                     foreach ($getoptions as $getoptionname => $getoptionval)
                     {
                         if (($getoptionname == $option['name']) && (isset($option['items'][$getoptionval])))
                         {
                             $options[$getoptionname] = $getoptionval;
                         }
                     }
                 }
                 // Пробуем найти какой же продукт в корзине
                 $keyfind = null;
                 foreach ($basket['products'] as $key=>$prod)
                 {
                     if ((!isset($prod['options'])) || (!is_array($prod['options']))) $prod['options'] = array();
                     if ($prod['id'] == $id)
                     {
                         // Сравниваем параметры
                         if (count($prod['options']) == count($options))
                         {
                             $arraysequal = true;
                             foreach ($prod['options'] as $optkey=>$optval)
                             {
                                 if (!isset($options[$optkey]) || ($options[$optkey] != $optval)) $arraysequal = false;
                             }
                             if ($arraysequal == true) {$keyfind = $key; break;}
                         }
                     }
                 }
                 if ($keyfind != null)
                 {
                     unset($basket['products'][$keyfind]);
                     $result['status'] = 'OK';
                     $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_shop_product_basket', $basket);
                 } else $result['status'] = 'Not found';
                 $result['count'] = count($basket['products']);
                 $result['action'] = 'object.product.basketremove';
             }
         }
         if ($action == 'basketclear')
         {
             $basket = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_basket');
             if (!is_array($basket)) $basket = array();
             $basket['products'] = array();
             $this->container->get('request_stack')->getCurrentRequest()->getSession()->set('front_shop_product_basket', $basket);
             $result['count'] = 0;
             $result['status'] = 'OK';
             $result['action'] = 'object.product.basketclear';
         }
     }
     
     public function getModuleTypes(&$contents)
     {
         $contents['basket'] = 'Модуль корзины';
     }
     
     public function getFrontModule($locale, $seoPage, $moduleType, $parameters, $result)
     {
         if ($moduleType == 'basket')
         {
             if (isset($parameters['basketId']))
             {
                 $em = $this->container->get('doctrine')->getEntityManager();
                 $query = $em->createQuery('SELECT b.names as name, sp.url FROM ShopBasketBundle:ProductBaskets b '.
                                           'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'basket\' AND sp.contentId = b.id '.
                                           'WHERE b.id = :id AND b.enabled != 0')->setParameter('id', $parameters['basketId']);
                 $page = $query->getResult();
                 if (empty($page)) return null;
                 if (isset($page[0])) $params = $page[0]; else return null;
                 $params['url'] = '/'.($locale != '' ? $locale.'/' : '').$params['url'];
                 $params['name'] = @unserialize($params['name']);
                 if (isset($params['name'][0])) $params['name'] = $this->container->get('cms.cmsManager')->decodeLocalString($params['name'][0], $locale, true); else $params['name'] = '';
                 $sessbasket = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_basket');
                 if (!is_array($sessbasket)) $sessbasket = array();
                 if (!isset($sessbasket['products']) || !is_array($sessbasket['products'])) $sessbasket['products'] = array();
                 $productIds = array();
                 foreach ($sessbasket['products'] as $item) $productIds[] = $item['id'];
                 if (!is_array($parameters['fields'])) $parameters['fields'] = array();
                 if (!in_array('price', $parameters['fields'])) $parameters['fields'][] = 'price';
                 if (!in_array('discount', $parameters['fields'])) $parameters['fields'][] = 'discount';
                 if ((count($productIds) > 0) && (is_array($parameters['fields'])) && (count($parameters['fields']) > 0))
                 {
                     $pageparams['sortField'] = '';
                     $pageparams['sortDirection'] = 0;
                     $pageparams['fields'] = $parameters['fields'];
                     $query = $this->container->get('object.product')->getTaxonomyQuery($locale, $pageparams, array(), null, false, $productIds);
                     $products = $query->getResult();
                 } else
                 {
                     $products = array();
                 }
                 $params['items'] = $products;
                 $params['itemCount'] = count($productIds);
                 if (count($products) > 0) $this->container->get('object.product')->getTaxonomyPostProcess($locale, $params);
                 unset($products);
                 $params['products'] = array();
                 $params['summ'] = 0;
                 foreach ($sessbasket['products'] as $item)
                 {
                     foreach ($params['items'] as $product) 
                         if ($product['id'] == $item['id'])
                         {
                             $params['products'][] = array_merge($product, array('count' => $item['count']));
                             $params['summ'] += $product['price'] * (1 - $product['discount'] / 100) * $item['count'];
                         }
                 }
                 if (!isset($params['currencies']))
                 {
                     $query = $em->createQuery('SELECT c.name, c.prefix, c.postfix, c.decimalSigns, c.decimalSep, c.thousandSep, c.main, c.minNote, c.exchangeIndex FROM ShopProductBundle:ProductCurrency c WHERE c.enabled != 0 ORDER BY c.main DESC, c.id ASC');
                     $currencies = $query->getResult();
                     if (!is_array($currencies)) $currencies = array();
                     $params['currencies'] = $currencies;
                 }
                 foreach ($params['currencies'] as $currency)
                 {
                     $summ = 0;
                     foreach ($params['products'] as $product)
                     {
                         $summ += round(($product['price'] * (1 - $product['discount'] / 100)) * $currency['exchangeIndex'] / $currency['minNote']) * $currency['minNote'] * $product['count'];
                     }
                     $params['formatSumm'][] = $currency['prefix'].number_format($summ, $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                 }
                 return $params;
             }
         }
         return null;
     }
     
     public function getInfoTitle($contentType, $contentId)
     {
         if ($contentType == 'basket')
         {
             $em = $this->container->get('doctrine')->getEntityManager();
             $query = $em->createQuery('SELECT b.names as name FROM ShopBasketBundle:ProductBaskets b WHERE b.id = :id')->setParameter('id', $contentId);
             $pages = $query->getResult();
             if (isset($pages[0]['name']))
             {
                 $pages[0]['name'] = @unserialize($pages[0]['name']);
                 if (isset($pages[0]['name'][0])) return $pages[0]['name'][0];
             }
         }
         return null;
     }

     public function getInfoSitemap($contentType, $contentId)
     {
          return null;
     }
     
// ************************************
// Обработка таксономии
// ************************************
     public function getTaxonomyParameters(&$params)
     {

     }
     
     public function getTaxonomyQuery($locale, $pageParameters, $filterParameters, $categoryIds, $itemIds, $allparameters, &$queryparameters, &$queryparameterscount, &$select, &$from, &$where, &$order)
     {
         
     }
     
     public function getTaxonomyPostProcess($locale, &$params)
     {
         $basket = $this->container->get('request_stack')->getCurrentRequest()->getSession()->get('front_shop_product_basket');
         if ((!isset($basket['products'])) || (!is_array($basket['products']))) $basket['products'] = array();
         $productIds = array();
         foreach ($basket['products'] as $item) $productIds[] = $item['id'];
         $params['productBasket'] = $productIds;
     }

// ****************************************
// Обработка корзины
// ****************************************
     public function calcBasketInfo (&$basket)
     {
         
     }
     public function getBasketJsCalc(&$jsCalc, $products = null)
     {
         
     }
     
     public function getProductOptions($productId, &$options, $locale)
     {
     
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
