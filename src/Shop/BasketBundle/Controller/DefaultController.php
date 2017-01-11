<?php

namespace Shop\BasketBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{
    
// *******************************************
// Список заказов
// *******************************************    
    public function orderListAction()
    {
        if ($this->getUser()->checkAccess('basket_orderlist') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр заказов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $tab = $this->getRequest()->get('tab');
        if ($tab === null) $tab = $this->getRequest()->getSession()->get('shop_basket_orderlist_tab');
                      else $this->getRequest()->getSession()->set('shop_basket_orderlist_tab', $tab);
        $tab = intval($tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 1) $tab = 1;
        // Таб 1
        $page0 = $this->getRequest()->get('page0');
        $sort0 = $this->getRequest()->get('sort0');
        $search0 = $this->getRequest()->get('search0');
        if ($page0 === null) $page0 = $this->getRequest()->getSession()->get('shop_basket_orderlist_page0');
                        else $this->getRequest()->getSession()->set('shop_basket_orderlist_page0', $page0);
        if ($sort0 === null) $sort0 = $this->getRequest()->getSession()->get('shop_basket_orderlist_sort0');
                        else $this->getRequest()->getSession()->set('shop_basket_orderlist_sort0', $sort0);
        if ($search0 === null) $search0 = $this->getRequest()->getSession()->get('shop_basket_orderlist_search0');
                          else $this->getRequest()->getSession()->set('shop_basket_orderlist_search0', $search0);
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        // Поиск контента для 1 таба (письма)
        $query = $em->createQuery('SELECT count(o.id) as ordercount FROM ShopBasketBundle:ProductOrders o '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = o.viewUserId '.
                                  'WHERE (u.fullName is null) OR (u.fullName like :search)')->setParameter('search', '%'.$search0.'%');
        $ordercount = $query->getResult();
        if (!empty($ordercount)) $ordercount = $ordercount[0]['ordercount']; else $ordercount = 0;
        $pagecount0 = ceil($ordercount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY o.createDate DESC';
        if ($sort0 == 1) $sortsql = 'ORDER BY o.createDate ASC';
        if ($sort0 == 2) $sortsql = 'ORDER BY o.summ ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY o.summ DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY o.payStatus ASC, o.status ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY o.payStatus DESC, o.status DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT o.id, o.status, o.payStatus, o.createDate, o.viewDate, o.viewUserId, u.fullName, o.summ, o.summCurrencies FROM ShopBasketBundle:ProductOrders o '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = o.viewUserId '.
                                  'WHERE (u.fullName is null) OR (u.fullName like :search) '.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $orders0 = $query->getResult();
        // Таб 2
        $page1 = $this->getRequest()->get('page1');
        $sort1 = $this->getRequest()->get('sort1');
        if ($page1 === null) $page1 = $this->getRequest()->getSession()->get('shop_basket_orderlist_page1');
                        else $this->getRequest()->getSession()->set('shop_basket_orderlist_page1', $page1);
        if ($sort1 === null) $sort1 = $this->getRequest()->getSession()->get('shop_basket_orderlist_sort1');
                        else $this->getRequest()->getSession()->set('shop_basket_orderlist_sort1', $sort1);
        $page1 = intval($page1);
        $sort1 = intval($sort1);
        $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->findOneBy(array('main' => 1));
        if (!empty($currency))
        {
            foreach ($orders0 as &$order0)
            {
                $summs = @unserialize($order0['summCurrencies']);
                if (is_array($summs) && isset($summs[$currency->getId()]))
                {
                    $order0['summ'] = $currency->getPrefix().number_format($summs[$currency->getId()], $currency->getDecimalSigns(), $currency->getDecimalSep(), $currency->getThousandSep()).$currency->getPostfix();
                }
            }
        }
        unset($order0);
        // Поиск контента для 2 таба (письма)
        $query = $em->createQuery('SELECT count(o.id) as ordercount FROM ShopBasketBundle:ProductOrders o '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = o.viewUserId '.
                                  'WHERE u.id = :userid')->setParameter('userid', $this->getUser()->getId());
        $ordercount = $query->getResult();
        if (!empty($ordercount)) $ordercount = $ordercount[0]['ordercount']; else $ordercount = 0;
        $pagecount1 = ceil($ordercount / 20);
        if ($pagecount1 < 1) $pagecount1 = 1;
        if ($page1 < 0) $page1 = 0;
        if ($page1 >= $pagecount1) $page1 = $pagecount1 - 1;
        $sortsql = 'ORDER BY o.createDate DESC';
        if ($sort1 == 1) $sortsql = 'ORDER BY o.createDate ASC';
        if ($sort1 == 2) $sortsql = 'ORDER BY o.summ ASC';
        if ($sort1 == 3) $sortsql = 'ORDER BY o.summ DESC';
        if ($sort1 == 4) $sortsql = 'ORDER BY o.payStatus ASC, o.status ASC';
        if ($sort1 == 5) $sortsql = 'ORDER BY o.payStatus DESC, o.status DESC';
        if ($sort1 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort1 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        $start = $page1 * 20;
        $query = $em->createQuery('SELECT o.id, o.status, o.payStatus, o.createDate, o.viewDate, o.viewUserId, u.fullName, o.summ, o.summCurrencies FROM ShopBasketBundle:ProductOrders o '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = o.viewUserId '.
                                  'WHERE (u.id = :userid) '.$sortsql)->setParameter('userid', $this->getUser()->getId())->setFirstResult($start)->setMaxResults(20);
        $orders1 = $query->getResult();
        $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->findOneBy(array('main' => 1));
        if (!empty($currency))
        {
            foreach ($orders1 as &$order1)
            {
                $summs = @unserialize($order1['summCurrencies']);
                if (is_array($summs) && isset($summs[$currency->getId()]))
                {
                    $order1['summ'] = $currency->getPrefix().number_format($summs[$currency->getId()], $currency->getDecimalSigns(), $currency->getDecimalSep(), $currency->getThousandSep()).$currency->getPostfix();
                }
            }
        }
        unset($order1);
        return $this->render('ShopBasketBundle:Default:orderList.html.twig', array(
            'pagecount0' => $pagecount0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'orders0' => $orders0,
            'pagecount1' => $pagecount1,
            'page1' => $page1,
            'sort1' => $sort1,
            'orders1' => $orders1,
            'tab' => $tab
        ));
        
    }

// *******************************************
// Изменение статуса заказа
// *******************************************    
    public function orderAjaxStatusAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $order = $this->getDoctrine()->getRepository('ShopBasketBundle:ProductOrders')->find($id);
        if (empty($order))
        {
            return new Response(json_encode(array('result'=>'Заказ не найден')));
        }
        if (($this->getUser()->checkAccess('basket_ordereditall') == 0) && (($this->getUser()->checkAccess('basket_ordereditnew') == 0) || (($order->getViewUserId() != null) && ($order->getViewUserId() != $this->getUser()->getId()))))
        {
            return new Response(json_encode(array('result'=>'Доступ запрещен')));
        }
        if ($order->getPayStatus() != 2)
        {
            return new Response(json_encode(array('result'=>'Заказ еще не оплачен')));
        }
        $status = intval($this->getRequest()->get('status'));
        if ($status < 0) $status = 0;
        if ($status > 3) $status = 3;
        $order->setStatus($status);
        $order->setViewUserId($this->getUser()->getId());
        $now = new \DateTime('now');
        $order->setViewDate($now);
        $em->flush();
        return new Response(json_encode(array('result'=>'OK','date'=>$now->format('d.m.Y G:i'), 'userName'=> $this->getUser()->getFullName())));
    }

// *******************************************
// Просмотр заказа
// *******************************************    
    
    public function orderViewAction()
    {
        $tab = $this->getRequest()->get('tab');
        if ($tab !== null)
        {
            $tab = intval($tab);
            if ($tab < 0) $tab = 0;
            if ($tab > 1) $tab = 1;
            $this->getRequest()->getSession()->set('shop_basket_orderlist_tab', $tab);
        }
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $orderent = $this->getDoctrine()->getRepository('ShopBasketBundle:ProductOrders')->find($id);
        if (empty($orderent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр заказа',
                'message'=>'Заказ не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к списку заказов'=>$this->get('router')->generate('shop_basket_orderlist'))
            ));
        }
        if ($this->getUser()->checkAccess('basket_orderlist') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр заказа',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $locale = $this->getDoctrine()->getRepository('BasicCmsBundle:Locales')->findOneBy(array('shortName'=>$orderent->getLocale()));
        if ($orderent->getViewUserId() != null) $user = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($orderent->getViewUserId());
        $order = array();
        $order['id'] = $orderent->getId();
        if (empty($locale))
        {
            $order['locale'] = 'По умолчанию';
        } else
        {
            $order['locale'] = $locale->getFullName();
        }
        $order['ipAddress'] = $orderent->getIpAddress();
        if (!empty($user))
        {
            $order['viewUser'] = $user->getFullName();
            $order['viewUserId'] = $user->getId();
        } else
        {
            $order['viewUser'] = '';
            $order['viewUserId'] = null;
        }
        $order['viewDate'] = $orderent->getViewDate();
        $order['createDate'] = $orderent->getCreateDate();
        $order['fields'] = @unserialize($orderent->getFields());
        if (!is_array($order['fields'])) $order['fields'] = array();
        $order['status'] = $orderent->getStatus();
        $order['payStatus'] = $orderent->getPayStatus();
        if ($this->container->has('plugin.basket.payment.'.$orderent->getPaySystem())) 
        {
            $order['paySystem'] = $this->container->get('plugin.basket.payment.'.$orderent->getPaySystem())->getDescription();
        } else
        {
            $order['paySystem'] = '';
        }
        $order['payInfo'] = @unserialize($orderent->getPayInfo());
        if (!is_array($order['payInfo'])) $order['payInfo'] = array();
        if ($this->container->has('plugin.basket.shipment.'.$orderent->getShipSystem())) 
        {
            $order['shipSystem'] = $this->container->get('plugin.basket.shipment.'.$orderent->getShipSystem())->getDescription();
        } else
        {
            $order['shipSystem'] = '';
        }
        $order['products'] = @unserialize($orderent->getProducts());
        if (!is_array($order['products'])) $order['products'] = array();
        $order['summ'] = $orderent->getSumm();
        $order['summInfo'] = @unserialize($orderent->getSummInfo());
        if (!is_array($order['summInfo'])) $order['summInfo'] = array();
        $order['summCurrencies'] = @unserialize($orderent->getSummCurrencies());
        if (!is_array($order['summCurrencies'])) $order['summCurrencies'] = array();
        $query = $em->createQuery('SELECT c.id, c.decimalSigns, c.decimalSep, c.thousandSep, c.prefix, c.postfix, c.main, c.minNote FROM ShopProductBundle:ProductCurrency c ORDER BY c.main DESC, c.id ASC');
        $currencies = $query->getResult();
        $order['formatSumm'] = array();
        foreach ($currencies as $currency)
        {
            if (isset($order['summCurrencies'][$currency['id']])) $order['formatSumm'][] = $currency['prefix'].number_format($order['summCurrencies'][$currency['id']], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
        }
        if (count($order['formatSumm']) == 0) $order['formatSumm'][] = $order['summ'];
        foreach ($order['products'] as &$product)
        {
            $product['formatPrice'] = array();
            $product['formatOrderPrice'] = array();
            foreach ($currencies as $currency)
            {
                if (isset($product['priceCurrencies'][$currency['id']])) 
                {
                    $product['formatPrice'][] = $currency['prefix'].number_format(round($product['priceCurrencies'][$currency['id']] / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                    $product['formatOrderPrice'][] = $currency['prefix'].number_format(round($product['priceCurrencies'][$currency['id']] / $currency['minNote']) * $currency['minNote'] * $product['count'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                }
            }
            if (count($product['formatPrice']) == 0) $product['formatPrice'][] = $product['price'];
            if (count($product['formatOrderPrice']) == 0) $product['formatOrderPrice'][] = $product['price'] * $product['count'];
            if (!isset($product['options']) || !is_array($product['options'])) $product['options'] = array();
        }
        unset($product);
        foreach ($order['summInfo'] as &$summinfo)
        {
            $summinfo['formatPrice'] = array();
            foreach ($currencies as $currency)
            {
                if (isset($summinfo['priceCurrencies'][$currency['id']])) 
                {
                    $summinfo['formatPrice'][] = $currency['prefix'].number_format(round($summinfo['priceCurrencies'][$currency['id']] / $currency['minNote']) * $currency['minNote'], $currency['decimalSigns'], $currency['decimalSep'], $currency['thousandSep']).$currency['postfix'];
                }
            }
            if (count($summinfo['formatPrice']) == 0) $summinfo['formatPrice'][] = $summinfo['orderPrice'];
        }
        unset($summinfo);
        return $this->render('ShopBasketBundle:Default:orderView.html.twig', array(
            'order' => $order,
            'activetab' => 1
        ));
        
    }
    
// *******************************************
// AJAX-контроллер заказов
// *******************************************    
    
    public function orderAjaxAction()
    {
        if ($this->getUser()->checkAccess('basket_orderlist') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр заказов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->getRequest()->get('action');
        $errors = array();
        if (($action == 'status0') || ($action == 'status1') || ($action == 'status2') || ($action == 'status3'))
        {
            $cmsservices = $this->container->getServiceIds();
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $orderent = $this->getDoctrine()->getRepository('ShopBasketBundle:ProductOrders')->find($key);
                    if (!empty($orderent))
                    {
                        if (($this->getUser()->checkAccess('basket_ordereditall') == 0) && (($this->getUser()->checkAccess('basket_ordereditnew') == 0) || (($orderent->getViewUserId() != null) && ($orderent->getViewUserId() != $this->getUser()->getId()))))
                        {
                            $errors[$key] = 'У вас нет прав изменять этот заказ';
                        } else 
                        {
                            if ($orderent->getPayStatus() != 2)
                            {
                                $errors[$key] = 'Заказ еще не оплачен';
                            } else
                            {
                                if ($action == 'status0') $orderent->setStatus(0);
                                if ($action == 'status1') $orderent->setStatus(1);
                                if ($action == 'status2') $orderent->setStatus(2);
                                if ($action == 'status3') $orderent->setStatus(3);
                                $orderent->setViewUserId($this->getUser()->getId());
                                $orderent->setViewDate(new \DateTime('now'));
                                $em->flush();
                            }
                        }
                    }
                    unset($orderent);
                }
        }
        $tab = intval($this->getRequest()->get('tab'));
        // Таб 1
        if ($tab == 0)
        {
            $page0 = $this->getRequest()->getSession()->get('shop_basket_orderlist_page0');
            $sort0 = $this->getRequest()->getSession()->get('shop_basket_orderlist_sort0');
            $search0 = $this->getRequest()->getSession()->get('shop_basket_orderlist_search0');
            $page0 = intval($page0);
            $sort0 = intval($sort0);
            $search0 = trim($search0);
            // Поиск контента для 1 таба (письма)
            $query = $em->createQuery('SELECT count(o.id) as ordercount FROM ShopBasketBundle:ProductOrders o '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = o.viewUserId '.
                                      'WHERE (u.fullName is null) OR (u.fullName like :search)')->setParameter('search', '%'.$search0.'%');
            $ordercount = $query->getResult();
            if (!empty($ordercount)) $ordercount = $ordercount[0]['ordercount']; else $ordercount = 0;
            $pagecount0 = ceil($ordercount / 20);
            if ($pagecount0 < 1) $pagecount0 = 1;
            if ($page0 < 0) $page0 = 0;
            if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
            $sortsql = 'ORDER BY o.createDate DESC';
            if ($sort0 == 1) $sortsql = 'ORDER BY o.createDate ASC';
            if ($sort0 == 2) $sortsql = 'ORDER BY o.summ ASC';
            if ($sort0 == 3) $sortsql = 'ORDER BY o.summ DESC';
            if ($sort0 == 4) $sortsql = 'ORDER BY o.payStatus ASC, o.status ASC';
            if ($sort0 == 5) $sortsql = 'ORDER BY o.payStatus DESC, o.status DESC';
            if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
            if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
            $start = $page0 * 20;
            $query = $em->createQuery('SELECT o.id, o.status, o.payStatus, o.createDate, o.viewDate, o.viewUserId, u.fullName, o.summ, o.summCurrencies FROM ShopBasketBundle:ProductOrders o '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = o.viewUserId '.
                                      'WHERE (u.fullName is null) OR (u.fullName like :search) '.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
            $orders0 = $query->getResult();
            $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->findOneBy(array('main' => 1));
            if (!empty($currency))
            {
                foreach ($orders0 as &$order0)
                {
                    $summs = @unserialize($order0['summCurrencies']);
                    if (is_array($summs) && isset($summs[$currency->getId()]))
                    {
                        $order0['summ'] = $currency->getPrefix().number_format($summs[$currency->getId()], $currency->getDecimalSigns(), $currency->getDecimalSep(), $currency->getThousandSep()).$currency->getPostfix();
                    }
                }
            }
            unset($order0);
            return $this->render('ShopBasketBundle:Default:orderListTab1.html.twig', array(
                'pagecount0' => $pagecount0,
                'search0' => $search0,
                'page0' => $page0,
                'sort0' => $sort0,
                'orders0' => $orders0,
                'errors0' => $errors,
                'tab' => $tab
            ));
        }
        if ($tab == 1)
        {
            // Таб 2
            $page1 = $this->getRequest()->getSession()->get('shop_basket_orderlist_page1');
            $sort1 = $this->getRequest()->getSession()->get('shop_basket_orderlist_sort1');
            $page1 = intval($page1);
            $sort1 = intval($sort1);
            // Поиск контента для 2 таба (письма)
            $query = $em->createQuery('SELECT count(o.id) as ordercount FROM ShopBasketBundle:ProductOrders o '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = o.viewUserId '.
                                      'WHERE u.id = :userid')->setParameter('userid', $this->getUser()->getId());
            $ordercount = $query->getResult();
            if (!empty($ordercount)) $ordercount = $ordercount[0]['ordercount']; else $ordercount = 0;
            $pagecount1 = ceil($ordercount / 20);
            if ($pagecount1 < 1) $pagecount1 = 1;
            if ($page1 < 0) $page1 = 0;
            if ($page1 >= $pagecount1) $page1 = $pagecount1 - 1;
            $sortsql = 'ORDER BY o.createDate DESC';
            if ($sort1 == 1) $sortsql = 'ORDER BY o.createDate ASC';
            if ($sort1 == 2) $sortsql = 'ORDER BY o.summ ASC';
            if ($sort1 == 3) $sortsql = 'ORDER BY o.summ DESC';
            if ($sort1 == 4) $sortsql = 'ORDER BY o.payStatus ASC, o.status ASC';
            if ($sort1 == 5) $sortsql = 'ORDER BY o.payStatus DESC, o.status DESC';
            if ($sort1 == 6) $sortsql = 'ORDER BY u.fullName ASC';
            if ($sort1 == 7) $sortsql = 'ORDER BY u.fullName DESC';
            $start = $page1 * 20;
            $query = $em->createQuery('SELECT o.id, o.status, o.payStatus, o.createDate, o.viewDate, o.viewUserId, u.fullName, o.summ, o.summCurrencies FROM ShopBasketBundle:ProductOrders o '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = o.viewUserId '.
                                      'WHERE (u.id = :userid) '.$sortsql)->setParameter('userid', $this->getUser()->getId())->setFirstResult($start)->setMaxResults(20);
            $orders1 = $query->getResult();
            $currency = $em->getRepository('ShopProductBundle:ProductCurrency')->findOneBy(array('main' => 1));
            if (!empty($currency))
            {
                foreach ($orders1 as &$order1)
                {
                    $summs = @unserialize($order1['summCurrencies']);
                    if (is_array($summs) && isset($summs[$currency->getId()]))
                    {
                        $order1['summ'] = $currency->getPrefix().number_format($summs[$currency->getId()], $currency->getDecimalSigns(), $currency->getDecimalSep(), $currency->getThousandSep()).$currency->getPostfix();
                    }
                }
            }
            unset($order1);
            return $this->render('ShopBasketBundle:Default:orderListTab2.html.twig', array(
                'pagecount1' => $pagecount1,
                'page1' => $page1,
                'sort1' => $sort1,
                'orders1' => $orders1,
                'errors1' => $errors,
                'tab' => $tab
            ));
        }
    }
    
// *******************************************
// Настройка корзины
// *******************************************    
    public function basketListAction()
    {
        if ($this->getUser()->checkAccess('basket_orderlist') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр заказов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $tab = $this->getRequest()->get('tab');
        if ($tab === null) $tab = $this->getRequest()->getSession()->get('shop_basket_list_tab');
                      else $this->getRequest()->getSession()->set('shop_basket_list_tab', $tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 3) $tab = 3;
        // Таб 1 (страницы корзины)
        $query = $em->createQuery('SELECT b.id, b.names, b.enabled, sp.url FROM ShopBasketBundle:ProductBaskets b LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'basket\' AND sp.contentId = b.id ORDER BY b.id');
        $baskets0 = $query->getResult();
        foreach ($baskets0 as &$basket) 
        {
            $names = @unserialize($basket['names']);
            if (is_array($names) && isset($names[0])) $basket['name'] = $this->get('cms.cmsManager')->decodeLocalString($names[0],'default',true); else $basket['name'] = '';
        }
        // Таб 2 (способы оплаты)
        $payments1 = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'plugin.basket.payment.') === 0) 
        {
            $serv = $this->container->get($item);
            $payments1[] = $serv->getPaymentInfo();
        }
        // Таб 3 (способы доставки)
        $shipments2 = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'plugin.basket.shipment.') === 0) 
        {
            $serv = $this->container->get($item);
            $shipments2[] = $serv->getShipmentInfo();
        }
        // Таб 4 (параметры заказа)
        $query = $em->createQuery('SELECT f.id, f.techName, f.name, f.type, f.ordering, f.enabled FROM ShopBasketBundle:ProductBasketFields f ORDER BY f.ordering');
        $fields3 = $query->getResult();
        foreach ($fields3 as &$field) $field['name'] = $this->get('cms.cmsManager')->decodeLocalString($field['name'],'default',true);
        // Вывод
        return $this->render('ShopBasketBundle:Default:basketList.html.twig', array(
            'baskets0' => $baskets0,
            'payments1' => $payments1,
            'shipments2' => $shipments2,
            'fields3' => $fields3,
            'tab' => $tab
        ));
    }

// *******************************************
// Создание параметра заказа
// *******************************************    
    public function fieldCreateAction()
    {
        $this->getRequest()->getSession()->set('shop_basket_list_tab', 3);
        if ($this->getUser()->checkAccess('bakset_configedit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание параметра заказа',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $field = array();
        $fielderror = array();
        $field['techName'] = '';
        $field['name'] = array();
        $field['type'] = '';
        $field['paramRegExp'] = array();
        $field['paramItems'] = array();
        $field['enabled'] = 1;
        $field['defaultEnable'] = '';
        $field['defaultValue'] = '';
        $fielderror['techName'] = '';
        $fielderror['name'] = array();
        $fielderror['type'] = '';
        $fielderror['paramRegExp'] = array();
        $fielderror['paramItems'] = array();
        $fielderror['enabled'] = '';
        $fielderror['defaultEnable'] = '';
        $fielderror['defaultValue'] = '';
        $field['name']['default'] = '';
        $fielderror['name']['default'] = '';
        $field['paramRegExp']['default'] = '';
        $fielderror['paramRegExp']['default'] = '';
        $field['paramItems']['default'] = '';
        $fielderror['paramItems']['default'] = '';
        foreach ($locales as $locale)
        {
            $field['name'][$locale['shortName']] = '';
            $fielderror['name'][$locale['shortName']] = '';
            $field['paramRegExp'][$locale['shortName']] = '';
            $fielderror['paramRegExp'][$locale['shortName']] = '';
            $field['paramItems'][$locale['shortName']] = '';
            $fielderror['paramItems'][$locale['shortName']] = '';
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postfield = $this->getRequest()->get('field');
            if (isset($postfield['techName'])) $field['techName'] = $postfield['techName'];
            if (isset($postfield['name']['default'])) $field['name']['default'] = $postfield['name']['default'];
            if (isset($postfield['paramRegExp']['default'])) $field['paramRegExp']['default'] = $postfield['paramRegExp']['default'];
            if (isset($postfield['paramItems']['default'])) $field['paramItems']['default'] = $postfield['paramItems']['default'];
            foreach ($locales as $locale)
            {
                if (isset($postfield['name'][$locale['shortName']])) $field['name'][$locale['shortName']] = $postfield['name'][$locale['shortName']];
                if (isset($postfield['paramRegExp'][$locale['shortName']])) $field['paramRegExp'][$locale['shortName']] = $postfield['paramRegExp'][$locale['shortName']];
                if (isset($postfield['paramItems'][$locale['shortName']])) $field['paramItems'][$locale['shortName']] = $postfield['paramItems'][$locale['shortName']];
            }            
            if (isset($postfield['type'])) $field['type'] = $postfield['type'];
            if (isset($postfield['enabled'])) $field['enabled'] = intval($postfield['enabled']); else $field['enabled'] = 0;
            if (isset($postfield['defaultEnable'])) $field['defaultEnable'] = intval($postfield['defaultEnable']); else $field['defaultEnable'] = 0;
            if (isset($postfield['defaultValue'])) $field['defaultValue'] = $postfield['defaultValue'];
            unset($postfield);
            if (!preg_match("/^[A-z][A-z0-9_]{2,98}$/ui", $field['techName'])) {$errors = true; $fielderror['techName'] = 'Техническое имя должно начинатся с латинской буквы, и содержать от 3 до 99 латинских букв, цифр или подчёркивания';}
            else
            {
                $query = $em->createQuery('SELECT count(f.id) as checkcount FROM ShopBasketBundle:ProductBasketFields f WHERE f.techName = :name')->setParameter('name', $field['techName']);
                $checkcount = $query->getResult();
                if (isset($checkcount[0]['checkcount'])) $checkcount = $checkcount[0]['checkcount']; else $checkcount = 0;
                if ($checkcount != 0) {$errors = true; $fielderror['techName'] = 'Поле с таким именем уже существует';}
            }
            if (!preg_match("/^.{3,255}$/ui", $field['name']['default'])) {$errors = true; $fielderror['name']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale)
            {
                if (!preg_match("/^(.{3,255})?$/ui", $field['name'][$locale['shortName']])) {$errors = true; $fielderror['name'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов';}
            }
            if (($field['type'] == 'text') || ($field['type'] == 'textarea'))
            {
                if (!preg_match("/^.{1,1000}$/ui", $field['paramRegExp']['default'])) {$errors = true; $fielderror['paramRegExp']['default'] = 'Регулярное выражение должно содержать от 1 до 1000 символов';}
                elseif (@preg_match('/^'.$field['paramRegExp']['default'].'$/ui','') === false) {$errors = true; $fielderror['paramRegExp']['default'] = 'Регулярное выражение некорректно';} 
                foreach ($locales as $locale)
                {
                    if (!preg_match("/^(.{1,1000})?$/ui", $field['paramRegExp'][$locale['shortName']])) {$errors = true; $fielderror['paramRegExp'][$locale['shortName']] = 'Регулярное выражение должно содержать от 1 до 1000 символов';}
                    elseif (($field['paramRegExp'][$locale['shortName']] != '') && (@preg_match('/^'.$field['paramRegExp'][$locale['shortName']].'$/ui','') === false)) {$errors = true; $fielderror['paramRegExp'][$locale['shortName']] = 'Регулярное выражение некорректно';} 
                }
            } else
            if (($field['type'] == 'radio') || ($field['type'] == 'select'))
            {
                if (!preg_match("/^([- _A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\,]{3,250}([\r\n]+)?)+$/ui", $field['paramItems']['default'])) {$errors = true; $fielderror['paramItems']['default'] = 'Ошибка в поле пунктов';}
                foreach ($locales as $locale)
                {
                    if (!preg_match("/^(([- _A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\,]{3,250}([\r\n]+)?)+)?$/ui", $field['paramItems'][$locale['shortName']])) {$errors = true; $fielderror['paramItems']['default'] = 'Ошибка в поле пунктов';}
                }
            } else if ($field['type'] != 'checkbox') {$errors = true; $fielderror['type'] = 'Неизвестный тип';}
            if ($field['defaultEnable'] != 0)
            {
                if (!preg_match("/^.{0,65535}$/ui", $field['defaultValue'])) {$errors = true; $fielderror['defaultValue'] = 'Значение по умолчанию должно содержать до 65535 символов';}
            } else $field['defaultValue'] = '';
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $query = $em->createQuery('SELECT max(f.ordering) as maxorder FROM ShopBasketBundle:ProductBasketFields f');
                $order = $query->getResult();
                if (isset($order[0]['maxorder'])) $order = $order[0]['maxorder'] + 1; else $order = 1;
                $fieldent = new \Shop\BasketBundle\Entity\ProductBasketFields();
                $fieldent->setTechName(strtolower($field['techName']));
                $fieldent->setDefaultEnable($field['defaultEnable']);
                $fieldent->setDefaultValue($field['defaultValue']);
                $fieldent->setEnabled($field['enabled']);
                $fieldent->setName($this->get('cms.cmsManager')->encodeLocalString($field['name']));
                $fieldent->setOrdering($order);
                if (($field['type'] == 'text') || ($field['type'] == 'textarea'))
                {
                    $fieldent->setParameters($this->get('cms.cmsManager')->encodeLocalString($field['paramRegExp']));
                } else
                if (($field['type'] == 'radio') || ($field['type'] == 'select'))
                {
                    $fieldent->setParameters($this->get('cms.cmsManager')->encodeLocalString($field['paramItems']));
                } else $fieldent->setParameters('');
                $fieldent->setType($field['type']);
                $em->persist($fieldent);
                $em->flush();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание параметра заказа',
                    'message'=>'Параметр заказа успешно создан',
                    'paths'=>array('Создать еще один параметр заказа'=>$this->get('router')->generate('shop_basket_fieldcreate'),'Вернуться к настройке корзины'=>$this->get('router')->generate('shop_basket_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('ShopBasketBundle:Default:fieldCreate.html.twig', array(
            'field' => $field,
            'fielderror' => $fielderror,
            'locales' => $locales,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// Редактирование параметра заказа
// *******************************************    
    public function fieldEditAction()
    {
        $this->getRequest()->getSession()->set('shop_basket_list_tab', 3);
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $fieldent = $this->getDoctrine()->getRepository('ShopBasketBundle:ProductBasketFields')->find($id);
        if (empty($fieldent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование параметра заказа',
                'message'=>'Параметр не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к настройке корзины'=>$this->get('router')->generate('shop_basket_list'))
            ));
        }
        if ($this->getUser()->checkAccess('bakset_configview') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование параметра заказа',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $field = array();
        $fielderror = array();
        $field['techName'] = $fieldent->getTechName();
        $field['name'] = array();
        $field['type'] = $fieldent->getType();
        $field['paramRegExp'] = array();
        $field['paramItems'] = array();
        $field['enabled'] = $fieldent->getEnabled();
        $field['defaultEnable'] = $fieldent->getDefaultEnable();
        $field['defaultValue'] = $fieldent->getDefaultValue();
        $fielderror['techName'] = '';
        $fielderror['name'] = array();
        $fielderror['type'] = '';
        $fielderror['paramRegExp'] = array();
        $fielderror['paramItems'] = array();
        $fielderror['enabled'] = '';
        $fielderror['defaultEnable'] = '';
        $fielderror['defaultValue'] = '';
        $field['name']['default'] = $this->get('cms.cmsManager')->decodeLocalString($fieldent->getName(),'default',false);
        $fielderror['name']['default'] = '';
        $field['paramRegExp']['default'] = (($fieldent->getType() == 'text') || ($fieldent->getType() == 'textarea') ? $this->get('cms.cmsManager')->decodeLocalString($fieldent->getParameters(),'default',false) : '');
        $fielderror['paramRegExp']['default'] = '';
        $field['paramItems']['default'] = (($fieldent->getType() == 'select') || ($fieldent->getType() == 'radio') ? $this->get('cms.cmsManager')->decodeLocalString($fieldent->getParameters(),'default',false) : '');
        $fielderror['paramItems']['default'] = '';
        
        foreach ($locales as $locale)
        {
            $field['name'][$locale['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($fieldent->getName(),$locale['shortName'],false);
            $fielderror['name'][$locale['shortName']] = '';
            $field['paramRegExp'][$locale['shortName']] = (($fieldent->getType() == 'text') || ($fieldent->getType() == 'textarea') ? $this->get('cms.cmsManager')->decodeLocalString($fieldent->getParameters(),$locale['shortName'],false) : '');
            $fielderror['paramRegExp'][$locale['shortName']] = '';
            $field['paramItems'][$locale['shortName']] = (($fieldent->getType() == 'select') || ($fieldent->getType() == 'radio') ? $this->get('cms.cmsManager')->decodeLocalString($fieldent->getParameters(),$locale['shortName'],false) : '');
            $fielderror['paramItems'][$locale['shortName']] = '';
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            if ($this->getUser()->checkAccess('bakset_configedit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование параметра заказа',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            // Проверка основных данных
            $postfield = $this->getRequest()->get('field');
            if (isset($postfield['techName'])) $field['techName'] = $postfield['techName'];
            if (isset($postfield['name']['default'])) $field['name']['default'] = $postfield['name']['default'];
            if (isset($postfield['paramRegExp']['default'])) $field['paramRegExp']['default'] = $postfield['paramRegExp']['default'];
            if (isset($postfield['paramItems']['default'])) $field['paramItems']['default'] = $postfield['paramItems']['default'];
            foreach ($locales as $locale)
            {
                if (isset($postfield['name'][$locale['shortName']])) $field['name'][$locale['shortName']] = $postfield['name'][$locale['shortName']];
                if (isset($postfield['paramRegExp'][$locale['shortName']])) $field['paramRegExp'][$locale['shortName']] = $postfield['paramRegExp'][$locale['shortName']];
                if (isset($postfield['paramItems'][$locale['shortName']])) $field['paramItems'][$locale['shortName']] = $postfield['paramItems'][$locale['shortName']];
            }            
            if (isset($postfield['type'])) $field['type'] = $postfield['type'];
            if (isset($postfield['enabled'])) $field['enabled'] = intval($postfield['enabled']); else $field['enabled'] = 0;
            if (isset($postfield['defaultEnable'])) $field['defaultEnable'] = intval($postfield['defaultEnable']); else $field['defaultEnable'] = 0;
            if (isset($postfield['defaultValue'])) $field['defaultValue'] = $postfield['defaultValue'];
            unset($postfield);
            if (!preg_match("/^[A-z][A-z0-9_]{2,98}$/ui", $field['techName'])) {$errors = true; $fielderror['techName'] = 'Техническое имя должно начинатся с латинской буквы, и содержать от 3 до 99 латинских букв, цифр или подчёркивания';}
            else
            {
                $query = $em->createQuery('SELECT count(f.id) as checkcount FROM ShopBasketBundle:ProductBasketFields f WHERE f.techName = :name AND f.id != :id')->setParameter('id', $id)->setParameter('name', $field['techName']);
                $checkcount = $query->getResult();
                if (isset($checkcount[0]['checkcount'])) $checkcount = $checkcount[0]['checkcount']; else $checkcount = 0;
                if ($checkcount != 0) {$errors = true; $fielderror['techName'] = 'Поле с таким именем уже существует';}
            }
            if (!preg_match("/^.{3,255}$/ui", $field['name']['default'])) {$errors = true; $fielderror['name']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale)
            {
                if (!preg_match("/^(.{3,255})?$/ui", $field['name'][$locale['shortName']])) {$errors = true; $fielderror['name'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов';}
            }
            if (($field['type'] == 'text') || ($field['type'] == 'textarea'))
            {
                if (!preg_match("/^.{1,1000}$/ui", $field['paramRegExp']['default'])) {$errors = true; $fielderror['paramRegExp']['default'] = 'Регулярное выражение должно содержать от 1 до 1000 символов';}
                elseif (@preg_match('/^'.$field['paramRegExp']['default'].'$/ui','') === false) {$errors = true; $fielderror['paramRegExp']['default'] = 'Регулярное выражение некорректно';} 
                foreach ($locales as $locale)
                {
                    if (!preg_match("/^(.{1,1000})?$/ui", $field['paramRegExp'][$locale['shortName']])) {$errors = true; $fielderror['paramRegExp'][$locale['shortName']] = 'Регулярное выражение должно содержать от 1 до 1000 символов';}
                    elseif (($field['paramRegExp'][$locale['shortName']] != '') && (@preg_match('/^'.$field['paramRegExp'][$locale['shortName']].'$/ui','') === false)) {$errors = true; $fielderror['paramRegExp'][$locale['shortName']] = 'Регулярное выражение некорректно';} 
                }
            } else
            if (($field['type'] == 'radio') || ($field['type'] == 'select'))
            {
                if (!preg_match("/^([- _A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\,]{3,250}([\r\n]+)?)+$/ui", $field['paramItems']['default'])) {$errors = true; $fielderror['paramItems']['default'] = 'Ошибка в поле пунктов';}
                foreach ($locales as $locale)
                {
                    if (!preg_match("/^(([- _A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\,]{3,250}([\r\n]+)?)+)?$/ui", $field['paramItems'][$locale['shortName']])) {$errors = true; $fielderror['paramItems']['default'] = 'Ошибка в поле пунктов';}
                }
            } else if ($field['type'] != 'checkbox') {$errors = true; $fielderror['type'] = 'Неизвестный тип';}
            if ($field['defaultEnable'] != 0)
            {
                if (!preg_match("/^.{0,65535}$/ui", $field['defaultValue'])) {$errors = true; $fielderror['defaultValue'] = 'Значение по умолчанию должно содержать до 65535 символов';}
            } else $field['defaultValue'] = '';
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $fieldent->setTechName(strtolower($field['techName']));
                $fieldent->setDefaultEnable($field['defaultEnable']);
                $fieldent->setDefaultValue($field['defaultValue']);
                $fieldent->setEnabled($field['enabled']);
                $fieldent->setName($this->get('cms.cmsManager')->encodeLocalString($field['name']));
                if (($field['type'] == 'text') || ($field['type'] == 'textarea'))
                {
                    $fieldent->setParameters($this->get('cms.cmsManager')->encodeLocalString($field['paramRegExp']));
                } else
                if (($field['type'] == 'radio') || ($field['type'] == 'select'))
                {
                    $fieldent->setParameters($this->get('cms.cmsManager')->encodeLocalString($field['paramItems']));
                } else $fieldent->setParameters('');
                $fieldent->setType($field['type']);
                $em->flush();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование параметра заказа',
                    'message'=>'Параметр заказа успешно отредактирован',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('shop_basket_fieldedit').'?id='.$id, 'Вернуться к настройке корзины'=>$this->get('router')->generate('shop_basket_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('ShopBasketBundle:Default:fieldEdit.html.twig', array(
            'id' => $id,
            'field' => $field,
            'fielderror' => $fielderror,
            'locales' => $locales,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// AJAX-контроллер параметров заказа
// *******************************************    
    
    public function fieldAjaxAction()
    {
        if ($this->getUser()->checkAccess('bakset_configedit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Настройка корзины',
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
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $fieldent = $this->getDoctrine()->getRepository('ShopBasketBundle:ProductBasketFields')->find($key);
                    if (!empty($fieldent))
                    {
                        $em->remove($fieldent);
                        $em->flush();
                    }
                    unset($fieldent);    
                }
        }
        if ($action == 'blocked')
        {
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $fieldent = $this->getDoctrine()->getRepository('ShopBasketBundle:ProductBasketFields')->find($key);
                    if (!empty($fieldent))
                    {
                        $fieldent->setEnabled(0);
                        $em->flush();
                    }
                    unset($fieldent);    
                }
        }
        if ($action == 'unblocked')
        {
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $fieldent = $this->getDoctrine()->getRepository('ShopBasketBundle:ProductBasketFields')->find($key);
                    if (!empty($fieldent))
                    {
                        $fieldent->setEnabled(1);
                        $em->flush();
                    }
                    unset($fieldent);    
                }
        }
        if ($action == 'ordering')
        {
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
                        $query = $em->createQuery('SELECT count(f.id) as checkid FROM ShopBasketBundle:ProductBasketFields f '.
                                                  'WHERE f.ordering = :order AND f.id NOT IN (:ids)')->setParameter('ids', $ids)->setParameter('order', $val);
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
                    $fieldent = $this->getDoctrine()->getRepository('ShopBasketBundle:ProductBasketFields')->find($key);
                    $fieldent->setOrdering($val);
                }
                $em->flush();
            }
        }
        // Таб 4 (параметры заказа)
        $query = $em->createQuery('SELECT f.id, f.techName, f.name, f.type, f.ordering, f.enabled FROM ShopBasketBundle:ProductBasketFields f ORDER BY f.ordering');
        $fields3 = $query->getResult();
        foreach ($fields3 as &$field) 
        {
            $field['name'] = $this->get('cms.cmsManager')->decodeLocalString($field['name'],'default',true);
            if (($action == 'ordering') && (isset($ordering[$field['id']]))) $field['ordering'] = $ordering[$field['id']];
        }
        // Вывод
        return $this->render('ShopBasketBundle:Default:basketListTab4.html.twig', array(
            'fields3' => $fields3,
            'errors3' => $errors,
            'errorsorder3' => $errorsorder
        ));
    }
    
// *******************************************
// Редактирование способа доставки
// *******************************************    
    public function shipmentEditAction()
    {
        $this->getRequest()->getSession()->set('shop_basket_list_tab', 2);
        $id = $this->getRequest()->get('id');
        if (!$this->container->has('plugin.basket.shipment.'.$id))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование способа доставки',
                'message'=>'Способ доставки не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к настройке корзины'=>$this->get('router')->generate('shop_basket_list'))
            ));
        }
        if ($this->getUser()->checkAccess('basket_configview') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование способа доставки',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $cmsservice = $this->container->get('plugin.basket.shipment.'.$id);
        $errors = false;
        $tab = '';
        if ($this->getRequest()->getMethod() == "POST")
        {
            if ($this->getUser()->checkAccess('basket_configedit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование способа доставки',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $errors = $cmsservice->getAdminController($this->getRequest(), 'validate');
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $cmsservice->getAdminController($this->getRequest(), 'save');
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование способа доставки',
                    'message'=>'Настройки способа доставки сохранены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('shop_basket_shipmentedit').'?id='.$id, 'Вернуться к настройке корзины'=>$this->get('router')->generate('shop_basket_list'))
                ));
            }
        }
        $tab = $cmsservice->getAdminController($this->getRequest(), 'tab');
        return $this->render('ShopBasketBundle:Default:shipmentEdit.html.twig', array(
            'tab' => $tab,
            'id' => $id
        ));
    }
    
// *******************************************
// AJAX-контроллер способов доставки
// *******************************************    
    
    public function shipmentAjaxAction()
    {
        if ($this->getUser()->checkAccess('bakset_configedit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Настройка корзины',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->getRequest()->get('action');
        $errors = array();
        if ($action == 'on')
        {
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    if ($this->container->has('plugin.basket.shipment.'.$key))
                    {
                        $error = $this->container->get('plugin.basket.shipment.'.$key)->setEnabled(1);
                        if ($error != null) $errors[$key] = $error;
                    }
                }
        }
        if ($action == 'off')
        {
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    if ($this->container->has('plugin.basket.shipment.'.$key))
                    {
                        $error = $this->container->get('plugin.basket.shipment.'.$key)->setEnabled(0);
                        if ($error != null) $errors[$key] = $error;
                    }
                }
        }
        // Таб 3 (способы доставки)
        $shipments2 = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'plugin.basket.shipment.') === 0) 
        {
            $serv = $this->container->get($item);
            $shipments2[] = $serv->getShipmentInfo();
        }
        // Вывод
        return $this->render('ShopBasketBundle:Default:basketListTab3.html.twig', array(
            'shipments2' => $shipments2,
            'errors2' => $errors
        ));
    }
    
// *******************************************
// Редактирование способа оплаты
// *******************************************    
    public function paymentEditAction()
    {
        $this->getRequest()->getSession()->set('shop_basket_list_tab', 1);
        $id = $this->getRequest()->get('id');
        if (!$this->container->has('plugin.basket.payment.'.$id))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование способа оплаты',
                'message'=>'Способ оплаты не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к настройке корзины'=>$this->get('router')->generate('shop_basket_list'))
            ));
        }
        if ($this->getUser()->checkAccess('basket_configview') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование способа оплаты',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $cmsservice = $this->container->get('plugin.basket.payment.'.$id);
        $errors = false;
        $tab = '';
        if ($this->getRequest()->getMethod() == "POST")
        {
            if ($this->getUser()->checkAccess('basket_configedit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование способа оплаты',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $errors = $cmsservice->getAdminController($this->getRequest(), 'validate');
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $cmsservice->getAdminController($this->getRequest(), 'save');
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование способа оплаты',
                    'message'=>'Настройки способа оплаты сохранены',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('shop_basket_paymentedit').'?id='.$id, 'Вернуться к настройке корзины'=>$this->get('router')->generate('shop_basket_list'))
                ));
            }
        }
        $tab = $cmsservice->getAdminController($this->getRequest(), 'tab');
        return $this->render('ShopBasketBundle:Default:paymentEdit.html.twig', array(
            'tab' => $tab,
            'id' => $id
        ));
    }
    
// *******************************************
// AJAX-контроллер способов доставки
// *******************************************    
    
    public function paymentAjaxAction()
    {
        if ($this->getUser()->checkAccess('bakset_configedit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Настройка корзины',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->getRequest()->get('action');
        $errors = array();
        if ($action == 'on')
        {
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    if ($this->container->has('plugin.basket.payment.'.$key))
                    {
                        $error = $this->container->get('plugin.basket.payment.'.$key)->setEnabled(1);
                        if ($error != null) $errors[$key] = $error;
                    }
                }
        }
        if ($action == 'off')
        {
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    if ($this->container->has('plugin.basket.payment.'.$key))
                    {
                        $error = $this->container->get('plugin.basket.payment.'.$key)->setEnabled(0);
                        if ($error != null) $errors[$key] = $error;
                    }
                }
        }
        // Таб 2 (способы оплаты)
        $payments1 = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'plugin.basket.payment.') === 0) 
        {
            $serv = $this->container->get($item);
            $payments1[] = $serv->getPaymentInfo();
        }
        // Вывод
        return $this->render('ShopBasketBundle:Default:basketListTab2.html.twig', array(
            'payments1' => $payments1,
            'errors1' => $errors
        ));
    }
    
// *******************************************
// Создание новой формы
// *******************************************    
    public function basketCreateAction()
    {
        $this->getRequest()->getSession()->set('shop_basket_list_tab', 0);
        if ($this->getUser()->checkAccess('bakset_configedit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание страницы корзины',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $basket = array();
        $basketerror = array();

        $basket['enabled'] = '';
        $basket['sendEmail'] = '';
        $basket['email'] = '';
        $basket['stepCount'] = '';
        $basket['names'] = array();
        $basket['templates'] = array();
        $basketerror['enabled'] = 1;
        $basketerror['sendEmail'] = '';
        $basketerror['email'] = '';
        $basketerror['stepCount'] = '';
        $basketerror['names'] = array();
        $basketerror['templates'] = array();
        $i = 'check';
        $basket['templates'][$i] = '';
        $basketerror['templates'][$i] = '';
        $basket['names'][$i]['default'] = '';
        $basketerror['names'][$i]['default'] = '';
        foreach ($locales as $locale)
        {
            $basket['names'][$i][$locale['shortName']] = '';
            $basketerror['names'][$i][$locale['shortName']] = '';
        }
        for ($i = 0; $i < 6; $i++)
        {
            $basket['templates'][$i] = '';
            $basketerror['templates'][$i] = '';
            $basket['names'][$i]['default'] = '';
            $basketerror['names'][$i]['default'] = '';
            foreach ($locales as $locale)
            {
                $basket['names'][$i][$locale['shortName']] = '';
                $basketerror['names'][$i][$locale['shortName']] = '';
            }
        }
        
        $page = array();
        $pageerror = array();
        
        $page['url'] = '';
        $page['modules'] = array();
        $page['locale'] = '';
        $page['layout'] = '';
        $page['accessOn'] = '';
        $page['access'] = array();
        $pageerror['url'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.product','basket');
        $checktemplates = $this->get('cms.cmsManager')->getTemplateList('object.product','basket.check');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.product.basket');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postbasket = $this->getRequest()->get('basket');
            if (isset($postbasket['enabled'])) $basket['enabled'] = intval($postbasket['enabled']); else $basket['enabled'] = 0;
            if (isset($postbasket['sendEmail'])) $basket['sendEmail'] = intval($postbasket['sendEmail']); else $basket['sendEmail'] = 0;
            if (isset($postbasket['email'])) $basket['email'] = $postbasket['email'];
            if (isset($postbasket['stepCount'])) $basket['stepCount'] = intval($postbasket['stepCount']);
            $i = 'check';
            if (isset($postbasket['templates'][$i])) $basket['templates'][$i] = $postbasket['templates'][$i];
            if (isset($postbasket['names'][$i]['default'])) $basket['names'][$i]['default'] = $postbasket['names'][$i]['default'];
            foreach ($locales as $locale) if (isset($postbasket['names'][$i][$locale['shortName']])) $basket['names'][$i][$locale['shortName']] = $postbasket['names'][$i][$locale['shortName']];
            for ($i = 0; $i < 6; $i++)
            {
                if (isset($postbasket['templates'][$i])) $basket['templates'][$i] = $postbasket['templates'][$i];
                if (isset($postbasket['names'][$i]['default'])) $basket['names'][$i]['default'] = $postbasket['names'][$i]['default'];
                foreach ($locales as $locale) if (isset($postbasket['names'][$i][$locale['shortName']])) $basket['names'][$i][$locale['shortName']] = $postbasket['names'][$i][$locale['shortName']];
            }
            unset($postbasket);
            if (($basket['sendEmail'] != 0) && (!preg_match("/^[_\-\.0-9a-z]+@([0-9a-z][_0-9a-z\.]+)\.([a-z]{2,4})$/ui", $basket['email']))) {$errors = true; $basketerror['email'] = 'Введен некорректный E-Mail';}
            if (($basket['stepCount'] < 1) || ($basket['stepCount'] > 6)) {$errors = true; $basketerror['stepCount'] = 'Указано некорректное количество шагов';}
            $i = 'check';
            if (($basket['templates'][$i] != '') && (!isset($checktemplates[$basket['templates'][$i]]))) {$errors = true; $basketerror['templates'][$i] = 'Шаблон не найден';}
            if (!preg_match("/^.{3,255}$/ui", $basket['names'][$i]['default'])) {$errors = true; $basketerror['names'][$i]['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) 
            {
                if (!preg_match("/^(.{3,255})?$/ui", $basket['names'][$i][$locale['shortName']])) {$errors = true; $basketerror['names'][$i][$locale['shortName']] = 'Заголовок должен содержать более 3 символов';}
            }
            for ($i = 0; $i < $basket['stepCount']; $i++)
            {
                if (($basket['templates'][$i] != '') && (!isset($templates[$basket['templates'][$i]]))) {$errors = true; $basketerror['templates'][$i] = 'Шаблон не найден';}
                if (!preg_match("/^.{3,255}$/ui", $basket['names'][$i]['default'])) {$errors = true; $basketerror['names'][$i]['default'] = 'Заголовок должен содержать более 3 символов';}
                foreach ($locales as $locale) 
                {
                    if (!preg_match("/^(.{3,255})?$/ui", $basket['names'][$i][$locale['shortName']])) {$errors = true; $basketerror['names'][$i][$locale['shortName']] = 'Заголовок должен содержать более 3 символов';}
                }
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->getRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
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
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $basketent = new \Shop\BasketBundle\Entity\ProductBaskets();
                $basketent->setEnabled($basket['enabled']);
                $basketent->setSendEmail($basket['sendEmail']);
                $basketent->setEmail($basket['email']);
                $basketent->setStepCount($basket['stepCount']);
                $namessave = array();
                $namessave['check'] = $this->get('cms.cmsManager')->encodeLocalString($basket['names']['check']);
                for ($i = 0; $i < 6; $i++) $namessave[$i] = $this->get('cms.cmsManager')->encodeLocalString($basket['names'][$i]);
                $basketent->setNames(serialize($namessave));
                $basketent->setTemplates(serialize($basket['templates']));
                $em->persist($basketent);
                $em->flush();
                $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Просмотр корзины продуктов '.$basket['names'][0]['default']);
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.product');
                $pageent->setContentId($basketent->getId());
                $pageent->setContentAction('basket');
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->persist($pageent);
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание страницы корзины',
                    'message'=>'Страница корзины успешно создана',
                    'paths'=>array('Создать еще одну страницу корзины'=>$this->get('router')->generate('shop_basket_create'),'Вернуться к настройке корзины'=>$this->get('router')->generate('shop_basket_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('ShopBasketBundle:Default:basketCreate.html.twig', array(
            'basket' => $basket,
            'basketerror' => $basketerror,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'checktemplates' => $checktemplates,
            'modules' => $modules,
            'layouts' => $layouts,
            'roles' => $roles,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// Редактирование формы
// *******************************************    
    public function basketEditAction()
    {
        $this->getRequest()->getSession()->set('shop_basket_list_tab', 0);
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $basketent = $this->getDoctrine()->getRepository('ShopBasketBundle:ProductBaskets')->find($id);
        if (empty($basketent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование формы',
                'message'=>'Форма не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к настройке корзины'=>$this->get('router')->generate('shop_basket_list'))
            ));
        }
        if ($this->getUser()->checkAccess('bakset_configview') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование страницы корзины',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.product','contentId'=>$id,'contentAction'=>'basket'));
        
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $basket = array();
        $basketerror = array();

        $baskettemplates = @unserialize($basketent->getTemplates());
        $basketnames = @unserialize($basketent->getNames());
        
        $basket['enabled'] = $basketent->getEnabled();
        $basket['sendEmail'] = $basketent->getSendEmail();
        $basket['email'] = $basketent->getEmail();
        $basket['stepCount'] = $basketent->getStepCount();
        $basket['names'] = array();
        $basket['templates'] = array();
        $basketerror['enabled'] = '';
        $basketerror['sendEmail'] = '';
        $basketerror['email'] = '';
        $basketerror['stepCount'] = '';
        $basketerror['names'] = array();
        $basketerror['templates'] = array();
        $i = 'check';
        $basket['templates'][$i] = (isset($baskettemplates[$i]) ? $baskettemplates[$i] : '');
        $basketerror['templates'][$i] = '';
        $basket['names'][$i]['default'] = (isset($basketnames[$i]) ? $this->get('cms.cmsManager')->decodeLocalString($basketnames[$i],'default',false) : '');
        $basketerror['names'][$i]['default'] = '';
        foreach ($locales as $locale)
        {
            $basket['names'][$i][$locale['shortName']] = (isset($basketnames[$i]) ? $this->get('cms.cmsManager')->decodeLocalString($basketnames[$i],$locale['shortName'],false) : '');
            $basketerror['names'][$i][$locale['shortName']] = '';
        }
        for ($i = 0; $i < 6; $i++)
        {
            $basket['templates'][$i] = (isset($baskettemplates[$i]) ? $baskettemplates[$i] : '');
            $basketerror['templates'][$i] = '';
            $basket['names'][$i]['default'] = (isset($basketnames[$i]) ? $this->get('cms.cmsManager')->decodeLocalString($basketnames[$i],'default',false) : '');
            $basketerror['names'][$i]['default'] = '';
            foreach ($locales as $locale)
            {
                $basket['names'][$i][$locale['shortName']] = (isset($basketnames[$i]) ? $this->get('cms.cmsManager')->decodeLocalString($basketnames[$i],$locale['shortName'],false) : '');
                $basketerror['names'][$i][$locale['shortName']] = '';
            }
        }
        
        $page = array();
        $pageerror = array();
        
        if (empty($pageent))
        {
            $page['url'] = '';
            $page['modules'] = array();
            $page['locale'] = '';
            $page['layout'] = '';
            $page['accessOn'] = '';
            $page['access'] = array();
        } else
        {
            $page['url'] = ($pageent->getUrl() != $pageent->getId() ? $pageent->getUrl() : '');
            $page['modules'] = explode(',',$pageent->getModules());
            $page['locale'] = $pageent->getLocale();
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
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.product','basket');
        $checktemplates = $this->get('cms.cmsManager')->getTemplateList('object.product','basket.check');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.product.basket');
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
            // Проверка основных данных
            $postbasket = $this->getRequest()->get('basket');
            if (isset($postbasket['enabled'])) $basket['enabled'] = intval($postbasket['enabled']); else $basket['enabled'] = 0;
            if (isset($postbasket['sendEmail'])) $basket['sendEmail'] = intval($postbasket['sendEmail']); else $basket['sendEmail'] = 0;
            if (isset($postbasket['email'])) $basket['email'] = $postbasket['email'];
            if (isset($postbasket['stepCount'])) $basket['stepCount'] = intval($postbasket['stepCount']);
            $i = 'check';
            if (isset($postbasket['templates'][$i])) $basket['templates'][$i] = $postbasket['templates'][$i];
            if (isset($postbasket['names'][$i]['default'])) $basket['names'][$i]['default'] = $postbasket['names'][$i]['default'];
            foreach ($locales as $locale) if (isset($postbasket['names'][$i][$locale['shortName']])) $basket['names'][$i][$locale['shortName']] = $postbasket['names'][$i][$locale['shortName']];
            for ($i = 0; $i < 6; $i++)
            {
                if (isset($postbasket['templates'][$i])) $basket['templates'][$i] = $postbasket['templates'][$i];
                if (isset($postbasket['names'][$i]['default'])) $basket['names'][$i]['default'] = $postbasket['names'][$i]['default'];
                foreach ($locales as $locale) if (isset($postbasket['names'][$i][$locale['shortName']])) $basket['names'][$i][$locale['shortName']] = $postbasket['names'][$i][$locale['shortName']];
            }
            unset($postbasket);
            if (($basket['sendEmail'] != 0) && (!preg_match("/^[_\-\.0-9a-z]+@([0-9a-z][_0-9a-z\.]+)\.([a-z]{2,4})$/ui", $basket['email']))) {$errors = true; $basketerror['email'] = 'Введен некорректный E-Mail';}
            if (($basket['stepCount'] < 1) || ($basket['stepCount'] > 6)) {$errors = true; $basketerror['stepCount'] = 'Указано некорректное количество шагов';}
            $i = 'check';
            if (($basket['templates'][$i] != '') && (!isset($checktemplates[$basket['templates'][$i]]))) {$errors = true; $basketerror['templates'][$i] = 'Шаблон не найден';}
            if (!preg_match("/^.{3,255}$/ui", $basket['names'][$i]['default'])) {$errors = true; $basketerror['names'][$i]['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale) 
            {
                if (!preg_match("/^(.{3,255})?$/ui", $basket['names'][$i][$locale['shortName']])) {$errors = true; $basketerror['names'][$i][$locale['shortName']] = 'Заголовок должен содержать более 3 символов';}
            }
            for ($i = 0; $i < $basket['stepCount']; $i++)
            {
                if (($basket['templates'][$i] != '') && (!isset($templates[$basket['templates'][$i]]))) {$errors = true; $basketerror['templates'][$i] = 'Шаблон не найден';}
                if (!preg_match("/^.{3,255}$/ui", $basket['names'][$i]['default'])) {$errors = true; $basketerror['names'][$i]['default'] = 'Заголовок должен содержать более 3 символов';}
                foreach ($locales as $locale) 
                {
                    if (!preg_match("/^(.{3,255})?$/ui", $basket['names'][$i][$locale['shortName']])) {$errors = true; $basketerror['names'][$i][$locale['shortName']] = 'Заголовок должен содержать более 3 символов';}
                }
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->getRequest()->get('page');
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['modules']) && is_array($postpage['modules'])) $page['modules'] = $postpage['modules']; else $page['modules'] = array();
            if (isset($postpage['locale'])) $page['locale'] = $postpage['locale'];
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
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $basketent->setEnabled($basket['enabled']);
                $basketent->setSendEmail($basket['sendEmail']);
                $basketent->setEmail($basket['email']);
                $basketent->setStepCount($basket['stepCount']);
                $namessave = array();
                $namessave['check'] = $this->get('cms.cmsManager')->encodeLocalString($basket['names']['check']);
                for ($i = 0; $i < 6; $i++) $namessave[$i] = $this->get('cms.cmsManager')->encodeLocalString($basket['names'][$i]);
                $basketent->setNames(serialize($namessave));
                $basketent->setTemplates(serialize($basket['templates']));
                $em->flush();
                if (empty($pageent))
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $em->persist($pageent);
                }
                $pageent->setBreadCrumbs('');
                $pageent->setBreadCrumbsRel('');
                $pageent->setDescription('Просмотр корзины продуктов '.$basket['names'][0]['default']);
                $pageent->setUrl($page['url']);
                $pageent->setTemplate($page['layout']);
                $pageent->setModules(implode(',', $page['modules']));
                $pageent->setLocale($page['locale']);
                $pageent->setContentType('object.product');
                $pageent->setContentId($basketent->getId());
                $pageent->setContentAction('basket');
                if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                $em->flush();
                if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование страницы корзины',
                    'message'=>'Страница корзины успешно отредактирована',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('shop_basket_edit').'?id='.$id, 'Вернуться к настройке корзины'=>$this->get('router')->generate('shop_basket_list'))
                ));
            }
        }
        if ($activetab == 0) $activetab = 1;
        return $this->render('ShopBasketBundle:Default:basketEdit.html.twig', array(
            'id' => $id,
            'basket' => $basket,
            'basketerror' => $basketerror,
            'page' => $page,
            'pageerror' => $pageerror,
            'locales' => $locales,
            'templates' => $templates,
            'checktemplates' => $checktemplates,
            'modules' => $modules,
            'layouts' => $layouts,
            'roles' => $roles,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// AJAX-контроллер страниц корзины
// *******************************************    
    
    public function basketAjaxAction()
    {
        if ($this->getUser()->checkAccess('basket_configedit') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Настройка корзины',
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
            $cmsservices = $this->container->getServiceIds();
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.product\' AND p.contentAction = \'basket\' AND p.contentId = :id')->setParameter('id', $key);
                    $query->execute();
                    $basketent = $this->getDoctrine()->getRepository('ShopBasketBundle:ProductBaskets')->find($key);
                    if (!empty($basketent))
                    {
                        $em->remove($basketent);
                        $em->flush();
                    }
                    unset($basketent);
                }
        }
        if ($action == 'blocked')
        {
            $cmsservices = $this->container->getServiceIds();
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $basketent = $this->getDoctrine()->getRepository('ShopBasketBundle:ProductBaskets')->find($key);
                    if (!empty($basketent))
                    {
                        $basketent->setEnabled(0);
                        $em->flush();
                    }
                    unset($basketent);
                }
        }
        if ($action == 'unblocked')
        {
            $cmsservices = $this->container->getServiceIds();
            $check = $this->getRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $basketent = $this->getDoctrine()->getRepository('ShopBasketBundle:ProductBaskets')->find($key);
                    if (!empty($basketent))
                    {
                        $basketent->setEnabled(1);
                        $em->flush();
                    }
                    unset($basketent);
                }
        }
        
        // Таб 1 (страницы корзины)
        $query = $em->createQuery('SELECT b.id, b.names, b.enabled, sp.url FROM ShopBasketBundle:ProductBaskets b LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.product\' AND sp.contentAction = \'basket\' AND sp.contentId = b.id ORDER BY b.id');
        $baskets0 = $query->getResult();
        foreach ($baskets0 as &$basket) 
        {
            $names = @unserialize($basket['names']);
            if (is_array($names) && isset($names[0])) $basket['name'] = $this->get('cms.cmsManager')->decodeLocalString($names[0],'default',true); else $basket['name'] = '';
        }
        // Вывод
        return $this->render('ShopBasketBundle:Default:basketListTab1.html.twig', array(
            'baskets0' => $baskets0,
            'errors0' => $errors
        ));
        
    }
    
}
