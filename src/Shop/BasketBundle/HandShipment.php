<?php

namespace Shop\BasketBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class HandShipment
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'plugin.basket.shipment.hand';
    }
    
    public function getDescription()
    {
        return 'Самовывоз';
    }

    public function getShipmentInfo()
    {
        $enabled = $this->container->get('cms.cmsManager')->getConfigValue('shipment.hand.enabled');
        $price = $this->container->get('cms.cmsManager')->getConfigValue('shipment.hand.price');
        $margin = $this->container->get('cms.cmsManager')->getConfigValue('shipment.hand.margin');
        return array('name'=>'hand', 'description'=>'Самовывоз', 'enabled'=>$enabled, 'price'=>$price, 'margin'=>$margin);
    }
    
    public function getAdminController($request, $actionType)
    {
        $em = $this->container->get('doctrine')->getEntityManager();

        $ship = array();
        $shiperror = array();
        $ship['enabled'] = $this->container->get('cms.cmsManager')->getConfigValue('shipment.hand.enabled');
        $ship['price'] = $this->container->get('cms.cmsManager')->getConfigValue('shipment.hand.price');
        $ship['margin'] = $this->container->get('cms.cmsManager')->getConfigValue('shipment.hand.margin');
        $shiperror['enabled'] = '';
        $shiperror['price'] = '';
        $shiperror['margin'] = '';
        $errors = false;
        if ($request->getMethod() == 'POST')
        {
            $postship = $request->get('ship');
            if (isset($postship['enabled'])) $ship['enabled'] = intval($postship['enabled']); else $ship['enabled'] = 0;
            if (isset($postship['price'])) $ship['price'] = $postship['price'];
            if (isset($postship['margin'])) $ship['margin'] = $postship['margin'];
            unset($postship);
            if (!preg_match("/^\d+((\,|\.)(\d+))?$/ui", $ship['price'])) {$errors = true; $shiperror['price'] = 'Должно быть указано положительное число';}
            if ((!preg_match("/^\-?\d+((\,|\.)(\d+))?$/ui", $ship['margin'])) || (floatval($ship['margin']) < -100) || (floatval($ship['margin']) > 100)) {$errors = true; $shiperror['margin'] = 'Должно быть указано число от -100 до 100 процентов';}
            if ($actionType == 'validate') return $errors;
            if (($actionType == 'save') && ($errors == false))
            {
                $this->container->get('cms.cmsManager')->setConfigValue('shipment.hand.enabled', $ship['enabled']);
                $this->container->get('cms.cmsManager')->setConfigValue('shipment.hand.price', str_replace(',','.',$ship['price']));
                $this->container->get('cms.cmsManager')->setConfigValue('shipment.hand.margin', str_replace(',','.',$ship['margin']));
            }
        }
        if ($actionType == 'tab')
        {
            return $this->container->get('templating')->render('ShopBasketBundle:Shipment:handEdit.html.twig',array('ship'=>$ship, 'shiperror'=>$shiperror));
        }
        return null;
    }

    public function setEnabled($enabled)
    {
        $this->container->get('cms.cmsManager')->setConfigValue('shipment.hand.enabled', $enabled);
        return null;
    }

    public function getEnabled()
    {
        return $this->container->get('cms.cmsManager')->getConfigValue('shipment.hand.enabled');
    }
    
// ****************************************
// Обработка корзины
// ****************************************
     public function calcBasketInfo (&$basket)
     {
         $ship = array();
         $ship['enabled'] = $this->container->get('cms.cmsManager')->getConfigValue('shipment.hand.enabled');
         $ship['price'] = $this->container->get('cms.cmsManager')->getConfigValue('shipment.hand.price');
         $ship['margin'] = $this->container->get('cms.cmsManager')->getConfigValue('shipment.hand.margin');
         if ($ship['enabled'] == 0) return null;
         $summ = $basket['summ'] * $ship['margin'] / 100 + $ship['price'];
         $basket['summInfo'][] = array('name'=>$this->getName(), 'description'=>$this->getDescription(), 'orderPrice'=>$summ);
     }
    
     
     public function getBasketJsCalc(&$jsCalc, $products = null)
     {
         $ship = array();
         $ship['enabled'] = $this->container->get('cms.cmsManager')->getConfigValue('shipment.hand.enabled');
         $ship['price'] = $this->container->get('cms.cmsManager')->getConfigValue('shipment.hand.price');
         $ship['margin'] = $this->container->get('cms.cmsManager')->getConfigValue('shipment.hand.margin');
         if ($ship['enabled'] == 0) return null;
         $jsCalc .=
                 '   if (basket[\'shipment\'] == \'hand\')'."\r\n".
                 '   {'."\r\n".
                 '      basket[\'summInfo\'][\''.$this->getName().'\'] = basket[\'summ\'] * '.$ship['margin'].' / 100 + '.$ship['price'].';'."\r\n".
                 '   }'."\r\n";
     }
}
