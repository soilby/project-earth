<?php

namespace Shop\BasketBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class HandPayment
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'plugin.basket.payment.hand';
    }
    
    public function getDescription()
    {
        return 'Оплата при получении';
    }

    public function getPaymentInfo()
    {
        $enabled = $this->container->get('cms.cmsManager')->getConfigValue('payment.hand.enabled');
        $price = $this->container->get('cms.cmsManager')->getConfigValue('payment.hand.price');
        $margin = $this->container->get('cms.cmsManager')->getConfigValue('payment.hand.margin');
        return array('name'=>'hand', 'description'=>'Оплата при получении', 'enabled'=>$enabled, 'price'=>$price, 'margin'=>$margin);
    }
    
    public function getAdminController($request, $actionType)
    {
        $em = $this->container->get('doctrine')->getEntityManager();

        $pay = array();
        $payerror = array();
        $pay['enabled'] = $this->container->get('cms.cmsManager')->getConfigValue('payment.hand.enabled');
        $pay['price'] = $this->container->get('cms.cmsManager')->getConfigValue('payment.hand.price');
        $pay['margin'] = $this->container->get('cms.cmsManager')->getConfigValue('payment.hand.margin');
        $payerror['enabled'] = '';
        $payerror['price'] = '';
        $payerror['margin'] = '';
        $errors = false;
        if ($request->getMethod() == 'POST')
        {
            $postpay = $request->get('pay');
            if (isset($postpay['enabled'])) $pay['enabled'] = intval($postpay['enabled']); else $pay['enabled'] = 0;
            if (isset($postpay['price'])) $pay['price'] = $postpay['price'];
            if (isset($postpay['margin'])) $pay['margin'] = $postpay['margin'];
            unset($postpay);
            if (!preg_match("/^\d+((\,|\.)(\d+))?$/ui", $pay['price'])) {$errors = true; $payerror['price'] = 'Должно быть указано положительное число';}
            if ((!preg_match("/^\-?\d+((\,|\.)(\d+))?$/ui", $pay['margin'])) || (floatval($pay['margin']) < -100) || (floatval($pay['margin']) > 100)) {$errors = true; $payerror['margin'] = 'Должно быть указано число от -100 до 100 процентов';}
            if ($actionType == 'validate') return $errors;
            if (($actionType == 'save') && ($errors == false))
            {
                $this->container->get('cms.cmsManager')->setConfigValue('payment.hand.enabled', $pay['enabled']);
                $this->container->get('cms.cmsManager')->setConfigValue('payment.hand.price', str_replace(',','.',$pay['price']));
                $this->container->get('cms.cmsManager')->setConfigValue('payment.hand.margin', str_replace(',','.',$pay['margin']));
            }
        }
        if ($actionType == 'tab')
        {
            return $this->container->get('templating')->render('ShopBasketBundle:Payment:handEdit.html.twig',array('pay'=>$pay, 'payerror'=>$payerror));
        }
        return null;
    }

    public function setEnabled($enabled)
    {
        $this->container->get('cms.cmsManager')->setConfigValue('payment.hand.enabled', $enabled);
        return null;
    }

    public function getEnabled()
    {
        return $this->container->get('cms.cmsManager')->getConfigValue('payment.hand.enabled');
    }
    
// ****************************************
// Обработка корзины
// ****************************************
     public function calcBasketInfo (&$basket)
     {
         $pay = array();
         $pay['enabled'] = $this->container->get('cms.cmsManager')->getConfigValue('payment.hand.enabled');
         $pay['price'] = $this->container->get('cms.cmsManager')->getConfigValue('payment.hand.price');
         $pay['margin'] = $this->container->get('cms.cmsManager')->getConfigValue('payment.hand.margin');
         if ($pay['enabled'] == 0) return null;
         $price = $basket['summ'] * $pay['margin'] / 100 + $pay['price'];
         $basket['summInfo'][] = array('name'=>$this->getName(), 'description'=>$this->getDescription(), 'orderPrice'=>$price);
     }

     public function getBasketJsCalc(&$jsCalc, $products = null)
     {
         $pay = array();
         $pay['enabled'] = $this->container->get('cms.cmsManager')->getConfigValue('payment.hand.enabled');
         $pay['price'] = $this->container->get('cms.cmsManager')->getConfigValue('payment.hand.price');
         $pay['margin'] = $this->container->get('cms.cmsManager')->getConfigValue('payment.hand.margin');
         if ($pay['enabled'] == 0) return null;
         $jsCalc .=
                 '   if (basket[\'payment\'] == \'hand\')'."\r\n".
                 '   {'."\r\n".
                 '      basket[\'summInfo\'][\''.$this->getName().'\'] = basket[\'summ\'] * '.$pay['margin'].' / 100 + '.$pay['price'].';'."\r\n".
                 '   }'."\r\n";
     }
     
// ****************************************
// Обработка платёжной системы
// ****************************************
     public function getPaymentContent($order)
     {
         $em = $this->container->get('doctrine')->getEntityManager();
         $order->setPayStatus(2);
         $em->persist($order);
         $em->flush();
         return null;
     }
}
