<?php

namespace Shop\ReferenceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{
// *******************************************
// AJAX-контроллер
// *******************************************    
    
    public function referenceAjaxProductsAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $search = trim($this->get('request_stack')->getMasterRequest()->get('search'));
        $page = intval($this->get('request_stack')->getMasterRequest()->get('page'));
        if ($page < 0) $page = 0;
        $query = $em->createQuery('SELECT count(p.id) as prodcount FROM ShopProductBundle:Products p WHERE p.title LIKE :search')
                    ->setParameter('search', '%'.$search.'%');
        $pagecount = $query->getResult();
        if (isset($pagecount[0]['prodcount'])) $pagecount = ceil(intval($pagecount[0]['prodcount'])/20); else $pagecount = 0;
        if ($pagecount < 1) $pagecount = 1;
        if ($page >= $pagecount) $page = $pagecount - 1;
        $query = $em->createQuery('SELECT p.id, p.avatar, p.title FROM ShopProductBundle:Products p WHERE p.title LIKE :search')
                    ->setParameter('search', '%'.$search.'%')->setFirstResult($page * 20)->setMaxResults(20);
        $products = $query->getResult();
        if (!is_array($products)) $products = array();
        return new Response(json_encode(array('page'=>$page, 'pagecount'=>$pagecount, 'products'=>$products)));
    }
}
