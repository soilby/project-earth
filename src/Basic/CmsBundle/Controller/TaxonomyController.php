<?php

namespace Basic\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Basic\CmsBundle\Entity\Users;
use Basic\CmsBundle\Entity\Roles;

class TaxonomyController extends Controller
{
// *******************************************
// Список классификаций
// *******************************************    
    public function taxonomyListAction()
    {
        if (($this->getUser()->checkAccess('taxonomy_list') == 0) && ($this->getUser()->checkAccess('taxonomy_listshow') == 0))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Категории',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $taxonomyobject = $this->getRequest()->get('object');
        if ($taxonomyobject === null) $taxonomyobject = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_object');
                                 else $this->getRequest()->getSession()->set('basic_cms_taxonomy_list_object', $taxonomyobject);
        $taxonomydescription = '';
        $taxonomyobjectsql = '';
        if ($taxonomyobject != '')
        {
            if ((strpos($taxonomyobject,'object.') === 0) && ($this->container->has($taxonomyobject)) && ($this->container->get($taxonomyobject)->getTaxonomyType() == true))
            {
                $taxonomydescription = $this->container->get($taxonomyobject)->getDescription();
                $taxonomyobjectsql = 'AND t.object = \''.$taxonomyobject.'\' ';
            } else
            {
                $taxonomyobject = '';
            }
        }
        $tab = $this->getRequest()->get('tab');
        if ($tab === null) $tab = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_tab');
                      else $this->getRequest()->getSession()->set('basic_cms_taxonomy_list_tab', $tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 1) $tab = 1;
        if ($this->getUser()->checkAccess('taxonomy_list') == 0) $tab = 1;
        if ($this->getUser()->checkAccess('taxonomy_listshow') == 0) $tab = 0;
        // Таб 1
        $page0 = $this->getRequest()->get('page0');
        $sort0 = $this->getRequest()->get('sort0');
        $search0 = $this->getRequest()->get('search0');
        if ($page0 === null) $page0 = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_page0');
                        else $this->getRequest()->getSession()->set('basic_cms_taxonomy_list_page0', $page0);
        if ($sort0 === null) $sort0 = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_sort0');
                        else $this->getRequest()->getSession()->set('basic_cms_taxonomy_list_sort0', $sort0);
        if ($search0 === null) $search0 = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_search0');
                          else $this->getRequest()->getSession()->set('basic_cms_taxonomy_list_search0', $search0);
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        // Поиск контента для 1 таба (тексоны)
        $query = $em->createQuery('SELECT count(t.id) as taxcount FROM BasicCmsBundle:Taxonomies t '.
                                  'WHERE t.parent is null AND t.title like :search '.$taxonomyobjectsql)->setParameter('search', '%'.$search0.'%');
        $taxcount = $query->getResult();
        if (!empty($taxcount)) $taxcount = $taxcount[0]['taxcount']; else $taxcount = 0;
        $pagecount0 = ceil($taxcount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY t.title ASC';
        if ($sort0 == 1) $sortsql = 'ORDER BY t.title DESC';
        if ($sort0 == 2) $sortsql = 'ORDER BY t.object ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY t.object DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY t.createDate ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY t.createDate DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT t.id, t.title, t.object, t.createDate, t.modifyDate, u.fullName FROM BasicCmsBundle:Taxonomies t '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.
                                  'WHERE t.parent is null AND t.title like :search '.$taxonomyobjectsql.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $taxons0 = $query->getResult();
        foreach ($taxons0 as &$taxon)
        {
            if ($this->container->has($taxon['object'])) $taxon['objectName'] = $this->container->get($taxon['object'])->getDescription(); else $taxon['objectName'] = '';
        }
        // Таб 2
        $page1 = $this->getRequest()->get('page1');
        $sort1 = $this->getRequest()->get('sort1');
        $search1 = $this->getRequest()->get('search1');
        if ($page1 === null) $page1 = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_page1');
                        else $this->getRequest()->getSession()->set('basic_cms_taxonomy_list_page1', $page1);
        if ($sort1 === null) $sort1 = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_sort1');
                        else $this->getRequest()->getSession()->set('basic_cms_taxonomy_list_sort1', $sort1);
        if ($search1 === null) $search1 = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_search1');
                          else $this->getRequest()->getSession()->set('basic_cms_taxonomy_list_search1', $search1);
        $page1 = intval($page1);
        $sort1 = intval($sort1);
        $search1 = trim($search1);
        // Поиск контента для 2 таба (текстовые страницы)
        $query = $em->createQuery('SELECT count(t.id) as showcount FROM BasicCmsBundle:TaxonomyShows t '.
                                  'WHERE t.title like :search '.$taxonomyobjectsql)->setParameter('search', '%'.$search1.'%');
        $showcount = $query->getResult();
        if (!empty($showcount)) $showcount = $showcount[0]['showcount']; else $showcount = 0;
        $pagecount1 = ceil($showcount / 20);
        if ($pagecount1 < 1) $pagecount1 = 1;
        if ($page1 < 0) $page1 = 0;
        if ($page1 >= $pagecount1) $page1 = $pagecount1 - 1;
        $sortsql = 'ORDER BY t.title ASC';
        if ($sort1 == 1) $sortsql = 'ORDER BY t.title DESC';
        if ($sort1 == 2) $sortsql = 'ORDER BY t.object ASC';
        if ($sort1 == 3) $sortsql = 'ORDER BY t.object DESC';
        if ($sort1 == 4) $sortsql = 'ORDER BY t.createDate ASC';
        if ($sort1 == 5) $sortsql = 'ORDER BY t.createDate DESC';
        if ($sort1 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort1 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        $start = $page1 * 20;
        $query = $em->createQuery('SELECT t.id, t.title, t.enabled, t.object, t.createDate, t.modifyDate, u.fullName FROM BasicCmsBundle:TaxonomyShows t '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.
                                  'WHERE t.title like :search '.$taxonomyobjectsql.$sortsql)->setParameter('search', '%'.$search1.'%')->setFirstResult($start)->setMaxResults(20);
        $shows1 = $query->getResult();
        foreach ($shows1 as &$show)
        {
            if ($this->container->has($show['object'])) $show['objectName'] = $this->container->get($show['object'])->getDescription(); else $show['objectName'] = '';
        }
        return $this->render('BasicCmsBundle:Taxonomy:taxonomyList.html.twig', array(
            'taxonomyobject' => $taxonomyobject,
            'taxonomydescription' => $taxonomydescription,
            'taxons0' => $taxons0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'pagecount0' => $pagecount0,
            'shows1' => $shows1,
            'search1' => $search1,
            'page1' => $page1,
            'sort1' => $sort1,
            'pagecount1' => $pagecount1,
            'tab' => $tab,
        ));
        
    }
    
// *******************************************
// Создание новой классификации
// *******************************************    
    public function taxonomyCreateAction()
    {
        $this->getRequest()->getSession()->set('basic_cms_taxonomy_list_tab', 0);
        if (($this->getUser()->checkAccess('taxonomy_new') == 0) && ($pagetype == 0))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой группы категорий',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $taxonomyobjects = array();
        $taxonomyobject = $this->getRequest()->get('object');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if ((strpos($item,'object.') === 0) && ($this->container->get($item)->getTaxonomyType() == true)) $taxonomyobjects[$item] = $this->container->get($item)->getDescription();

        $tax = array();
        $taxerror = array();
        $tax['title'] = '';
        $tax['object'] = '';
        $taxerror['title'] = '';
        $taxerror['object'] = '';
        $categories = array();
        
        $activetab = 0;
        $tabs = array();
        $errors = false;
        if ($this->getRequest()->getMethod() == 'POST')
        {
            $taxpost = $this->getRequest()->get('tax');
            if (isset($taxpost['title'])) $tax['title'] = $taxpost['title'];
            if (isset($taxpost['object'])) $tax['object'] = $taxpost['object'];
            unset($taxpost);
            if (!preg_match("/^.{3,}$/ui", $tax['title'])) {$errors = true; $taxerror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (!isset($taxonomyobjects[$tax['object']])) {$errors = true; $taxerror['object'] = 'Объект не найден';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            $categoriespost = $this->getRequest()->get('categories');
            if (is_array($categoriespost))
            {
                foreach ($categoriespost as $cat)
                {
                    if (isset($cat['nesting']) && isset($cat['name'])) $categories[] = array('nesting'=>$cat['nesting'],'name'=>$cat['name'],'error'=>'');
                }
            }
            unset($categoriespost);
            foreach ($categories as &$cat)
            {
                if (!preg_match("/^.{3,}$/ui", $cat['name'])) {$errors = true; $cat['error'] = 'Заголовок должен содержать более 3 символов';}
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 2;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'taxonomyCreate', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                $i++;
            }     
            //Сохранение
            if ($errors == false)
            {
                $taxent = new \Basic\CmsBundle\Entity\Taxonomies();
                $taxent->setAvatar('');
                $taxent->setCreateDate(new \DateTime('now'));
                $taxent->setCreaterId($this->getUser()->getId());
                $taxent->setDescription('');
                $taxent->setEnabled(1);
                $taxent->setEnableAdd(1);
                $taxent->setMetaDescription('');
                $taxent->setMetaKeywords('');
                $taxent->setModifyDate(new \DateTime('now'));
                $taxent->setObject($tax['object']);
                $taxent->setOrdering(1);
                $taxent->setTemplate('');
                $taxent->setTitle($tax['title']);
                $taxent->setFirstParent(null);
                $taxent->setParent(null);
                $em->persist($taxent);
                $em->flush();
                $taxent->setFirstParent($taxent->getId());
                $em->flush();
                for ($i = 0; $i < count($categories); $i++)
                {
                    $parentid = $taxent->getId();
                    $j = $i - 1;
                    $ordering = 1;
                    while ($j >= 0)
                    {
                        if (($categories[$j]['nesting'] < $categories[$i]['nesting']) && isset($categories[$j]['id'])) {$parentid = $categories[$j]['id'];break;}
                        if ($categories[$j]['nesting'] == $categories[$i]['nesting']) $ordering++;
                        $j--;
                    }
                    $cattaxent = new \Basic\CmsBundle\Entity\Taxonomies();
                    $cattaxent->setAvatar('');
                    $cattaxent->setCreateDate(new \DateTime('now'));
                    $cattaxent->setCreaterId($this->getUser()->getId());
                    $cattaxent->setDescription('');
                    $cattaxent->setEnabled(1);
                    $cattaxent->setEnableAdd(1);
                    $cattaxent->setMetaDescription('');
                    $cattaxent->setMetaKeywords('');
                    $cattaxent->setModifyDate(new \DateTime('now'));
                    $cattaxent->setObject($tax['object']);
                    $cattaxent->setOrdering($ordering);
                    $cattaxent->setTemplate('');
                    $cattaxent->setTitle($categories[$i]['name']);
                    $cattaxent->setFirstParent($taxent->getId());
                    $cattaxent->setParent($parentid);
                    $em->persist($cattaxent);
                    $em->flush();
                    $categories[$i]['id'] = $cattaxent->getId();
                    unset($cattaxent);
                }
                // Другие табы
                $i = 0;
                foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'taxonomyCreate', $taxent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой группы категорий',
                    'message'=>'Группа категорий успешно создана',
                    'paths'=>array('Создать еще одну группу категорий'=>$this->get('router')->generate('basic_cms_taxonomy_create'),'Перейти к редактированию группы категорий'=>$this->get('router')->generate('basic_cms_taxonomy_edit',array('id'=>$taxent->getId())),'Вернуться к списку классификаций'=>$this->get('router')->generate('basic_cms_taxonomy_list'))
                ));
                
            }
        }
        // Другие табы
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'taxonomyCreate', 0, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Taxonomy:taxonomyCreate.html.twig', array(
            'tax' => $tax,
            'taxerror' => $taxerror,
            'categories' => $categories,
            'taxonomyobjects' => $taxonomyobjects,
            'tabs'  => $tabs,
            'activetab' => $activetab
        ));
    }

// *******************************************
// Редактирование классификации и AJAX-функции
// *******************************************    
    private function taxonomyGetTree($tree, $id, $nesting, &$cats)
    {
        foreach ($tree as $cat)
        {
            if ($cat['parent'] == $id)
            {
                $cats[] = array('name' => $cat['title'], 'id' => $cat['id'], 'enabled' => $cat['enabled'], 'nesting' => $nesting, 'error' => '');
                $this->taxonomyGetTree($tree, $cat['id'], $nesting + 1, $cats);
            }
        }
    }
    
    public function taxonomyEditAction()
    {
        $this->getRequest()->getSession()->set('basic_cms_taxonomy_list_tab', 0);
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $taxent = $this->getDoctrine()->getRepository('BasicCmsBundle:Taxonomies')->findOneBy(array('id'=>$id,'parent'=>null));
        if (empty($taxent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование группы категорий',
                'message'=>'Группа категорий не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку групп категорий'=>$this->get('router')->generate('basic_cms_taxonomy_list'))
            ));
        }
        if ($this->getUser()->checkAccess('taxonomy_view') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование группы категорий',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $tax = array();
        $taxerror = array();
        $tax['title'] = $taxent->getTitle();
        $tax['object'] = $taxent->getObject();
        $tax['enabled'] = $taxent->getEnabled();
        $taxerror['title'] = '';
        $taxerror['object'] = '';
        $categories = array();
        $query = $em->createQuery('SELECT t.id, t.title, t.enabled, t.parent FROM BasicCmsBundle:Taxonomies t '.
                                  'WHERE t.firstParent = :id AND t.parent is not null '.
                                  'ORDER BY t.ordering')->setParameter('id', $id);
        $tree = $query->getResult();
        $this->taxonomyGetTree($tree, $id, 0, $categories);
        $activetab = 0;
        $tabs = array();
        $errors = false;
        if ($this->getRequest()->getMethod() == 'POST')
        {
            if ($this->getUser()->checkAccess('taxonomy_edit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование группы категорий',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $categoriespost = $this->getRequest()->get('categories');
            if (is_array($categoriespost))
            {
                $categories = array();
                foreach ($categoriespost as $cat)
                {
                    if (isset($cat['nesting']) && isset($cat['id'])) 
                    {
                        foreach ($tree as $treecat) if ($treecat['id'] == $cat['id'])
                        {
                            $categories[] = array('nesting'=>$cat['nesting'],'id'=>$cat['id'],'error'=>'','name'=>$treecat['title'],'enabled'=>$treecat['enabled']);
                            break;
                        }
                    }
                }
            }
            unset($categoriespost);
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'taxonomyEdit', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                $i++;
            }     
            //Сохранение
            if ($errors == false)
            {
                for ($i = 0; $i < count($categories); $i++)
                {
                    $parentid = $id;
                    $j = $i - 1;
                    $ordering = 1;
                    while ($j >= 0)
                    {
                        if (($categories[$j]['nesting'] < $categories[$i]['nesting']) && isset($categories[$j]['id'])) {$parentid = $categories[$j]['id'];break;}
                        if ($categories[$j]['nesting'] == $categories[$i]['nesting']) $ordering++;
                        $j--;
                    }
                    $cattaxent = $em->getRepository('BasicCmsBundle:Taxonomies')->find($categories[$i]['id']);
                    if (!empty($cattaxent))
                    {
                        $cattaxent->setModifyDate(new \DateTime('now'));
                        $cattaxent->setOrdering($ordering);
                        $cattaxent->setParent($parentid);
                        $em->flush();
                        $seopageent = $em->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.taxonomy', 'contentAction'=>'view','contentId'=>$categories[$i]['id']));
                        if (!empty($seopage)) $this->get('cms.cmsManager')->updateBreadCrumbs($seopageent->getId());
                        unset($seopageent);
                    }
                    unset($cattaxent);
                }
                // Другие табы
                $i = 0;
                foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'taxonomyEdit', $id, 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование группы категорий',
                    'message'=>'Группы категорий успешно отредактирована',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_taxonomy_edit',array('id'=>$id)),'Вернуться к списку групп категорий'=>$this->get('router')->generate('basic_cms_taxonomy_list'))
                ));
                
            }
        }
        // Другие табы
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'taxonomyEdit', $id, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Taxonomy:taxonomyEdit.html.twig', array(
            'id' => $id,
            'tax' => $tax,
            'categories' => $categories,
            'tabs'  => $tabs,
            'activetab' => $activetab
        ));
    }
    
    public function taxonomyEditAjaxDeleteAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $taxent = $this->getDoctrine()->getRepository('BasicCmsBundle:Taxonomies')->find($id);
        if (empty($taxent)) return new Response(json_encode(array('result'=>'error','message'=>'Категория не найдена')));
        if ($this->getUser()->checkAccess('taxonomy_edit') == 0) return new Response(json_encode(array('result'=>'error','message'=>'Доступ запрещён')));
        $categories = array();
        $query = $em->createQuery('SELECT t.id, t.title, t.enabled, t.parent FROM BasicCmsBundle:Taxonomies t '.
                                  'WHERE t.firstParent = :id AND t.parent is not null '.
                                  'ORDER BY t.ordering')->setParameter('id', $taxent->getFirstParent());
        $tree = $query->getResult();
        $this->taxonomyGetTree($tree, $id, 0, $categories);
        $ids = array($id);
        foreach ($categories as $category) $ids[] = $category['id'];
        $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.taxonomy\' AND p.contentAction = \'view\' AND p.contentId IN (:ids)')->setParameter('ids', $ids);
        $query->execute();
        $query = $em->createQuery('DELETE FROM BasicCmsBundle:TaxonomiesLinks tl WHERE tl.taxonomyId IN (:ids)')->setParameter('ids', $ids);
        $query->execute();
        $query = $em->createQuery('SELECT tl.description FROM BasicCmsBundle:TaxonomiesLocale tl WHERE tl.taxonomyId IN (:ids)')->setParameter('ids', $ids);
        $deltaxes = $query->getResult();
        if (is_array($deltaxes))
        {
            foreach ($deltaxes as $deltax)
            {
                if ($deltax['description'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $deltax['description']);
            }
        }
        $query = $em->createQuery('DELETE FROM BasicCmsBundle:TaxonomiesLocale tl WHERE tl.taxonomyId IN (:ids)')->setParameter('ids', $ids);
        $query->execute();
        $query = $em->createQuery('SELECT t.avatar, t.description FROM BasicCmsBundle:Taxonomies t WHERE t.id IN (:ids)')->setParameter('ids', $ids);
        $deltaxes = $query->getResult();
        if (is_array($deltaxes))
        {
            foreach ($deltaxes as $deltax)
            {
                if ($deltax['avatar'] != '') @unlink('..'.$deltax['avatar']);
                if ($deltax['description'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $deltax['description']);
            }
        }
        $query = $em->createQuery('DELETE FROM BasicCmsBundle:Taxonomies t WHERE t.id IN (:ids)')->setParameter('ids', $ids);
        $query->execute();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
        {
            $serv = $this->container->get($item);
            $serv->getAdminController($this->getRequest(), 'taxonomyEditDelete', $id, 'save');
        }       
        return new Response(json_encode(array('result'=>'OK','message'=>'')));
    }

    public function taxonomyEditAjaxAddAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $name = $this->getRequest()->get('name');
        $taxent = $this->getDoctrine()->getRepository('BasicCmsBundle:Taxonomies')->findOneBy(array('id'=>$id,'parent'=>null));
        if (empty($taxent)) return new Response(json_encode(array('result'=>'error','message'=>'Категория не найдена')));
        if ($this->getUser()->checkAccess('taxonomy_edit') == 0) return new Response(json_encode(array('result'=>'error','message'=>'Доступ запрещён')));
        if (!preg_match("/^.{3,}$/ui", $name)) return new Response(json_encode(array('result'=>'error','message'=>'Заголовок должен содержать более 3 символов')));
        $query = $em->createQuery('SELECT MAX(t.ordering) as maxordering FROM BasicCmsBundle:Taxonomies t WHERE t.parent = :id')->setParameter('id', $id);
        $ordering = $query->getResult();
        if (isset($ordering[0]['maxordering'])) $ordering = $ordering[0]['maxordering'] + 1; else $ordering = 1;
        $query = $em->createQuery('SELECT t.id, t.template, t.pageParameters, t.filterParameters, sp.locale, sp.modules, sp.template as layout, sp.access, sp.id as seoPageId FROM BasicCmsBundle:Taxonomies t '.
                                  'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.taxonomy\' AND sp.contentAction = \'view\' AND sp.contentId = t.id '.
                                  'WHERE t.firstParent = :id')->setParameter('id', $id);
        $defparameterssql = $query->getResult();
        $defparameters = null;
        if (is_array($defparameterssql))
        {
            $defparametersmain = null;
            $defparametersord = null;
            $defparameterssame = true;
            foreach ($defparameterssql as $defparam)
            {
                if (($defparam['id'] == $id) && ($defparametersmain == null)) $defparametersmain = $defparam;
                if ($defparam['id'] != $id) 
                {
                    if ($defparametersord == null) $defparametersord = $defparam; else
                    {
                        if (($defparametersord['template'] != $defparametersord['template']) ||
                            ($defparametersord['pageParameters'] != $defparametersord['pageParameters']) ||
                            ($defparametersord['filterParameters'] != $defparametersord['filterParameters']) ||
                            ($defparametersord['locale'] != $defparametersord['locale']) ||
                            ($defparametersord['modules'] != $defparametersord['modules']) ||
                            ($defparametersord['layout'] != $defparametersord['layout']) ||
                            ($defparametersord['access'] != $defparametersord['access'])) $defparameterssame = false;
                    }
                }
            }
            if ($defparameterssame == false) $defparametersord = null;
            if ($defparametersord != null) $defparameters = $defparametersord; else $defparameters = $defparametersmain;
        }
        $cattaxent = new \Basic\CmsBundle\Entity\Taxonomies();
        $cattaxent->setAvatar('');
        $cattaxent->setCreateDate(new \DateTime('now'));
        $cattaxent->setCreaterId($this->getUser()->getId());
        $cattaxent->setDescription('');
        $cattaxent->setEnabled(1);
        $cattaxent->setEnableAdd(1);
        $cattaxent->setMetaDescription('');
        $cattaxent->setMetaKeywords('');
        $cattaxent->setModifyDate(new \DateTime('now'));
        $cattaxent->setObject($taxent->getObject());
        $cattaxent->setOrdering($ordering);
        $cattaxent->setTemplate('');
        $cattaxent->setTitle($name);
        $cattaxent->setFirstParent($taxent->getId());
        $cattaxent->setParent($taxent->getId());
        if ($defparameters != null)
        {
            $cattaxent->setTemplate($defparameters['template']);
            $cattaxent->setPageParameters($defparameters['pageParameters']);
            $cattaxent->setFilterParameters($defparameters['filterParameters']);
        }
        $em->persist($cattaxent);
        $em->flush();
        if (($defparameters != null) && ($defparameters['seoPageId'] != null))
        {
            $pageent = new \Basic\CmsBundle\Entity\SeoPage();
            $pageent->setBreadCrumbs('');
            $pageent->setBreadCrumbsRel('');
            $pageent->setDescription('Просмотр категории '.$cattaxent->getTitle());
            $pageent->setUrl('');
            $pageent->setTemplate($defparameters['layout']);
            $pageent->setModules($defparameters['modules']);
            $pageent->setLocale($defparameters['locale']);
            $pageent->setContentType('object.taxonomy');
            $pageent->setContentId($cattaxent->getId());
            $pageent->setContentAction('view');
            $pageent->setAccess($defparameters['access']);
            $em->persist($pageent);
            $em->flush();
            $pageent->setUrl($pageent->getId());
            $em->flush();
        }
        return new Response(json_encode(array('result'=>'OK','message'=>'','id'=>$cattaxent->getId())));
    }
    

    public function taxonomyEditAjaxSaveAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $taxent = $this->getDoctrine()->getRepository('BasicCmsBundle:Taxonomies')->findOneBy(array('id'=>$id,'parent'=>null));
        if (empty($taxent)) return new Response(json_encode(array('result'=>'error','message'=>'Группа категорий не найдена')));
        if ($this->getUser()->checkAccess('taxonomy_edit') == 0) return new Response(json_encode(array('result'=>'error','message'=>'Доступ запрещён')));

        $query = $em->createQuery('SELECT t.id, t.title, t.enabled, t.parent FROM BasicCmsBundle:Taxonomies t '.
                                  'WHERE t.firstParent = :id AND t.parent is not null '.
                                  'ORDER BY t.ordering')->setParameter('id', $id);
        $tree = $query->getResult();
        $errors = false;
        if ($this->getRequest()->getMethod() == 'POST')
        {
            $categoriespost = $this->getRequest()->get('categories');
            if (is_array($categoriespost))
            {
                $categories = array();
                foreach ($categoriespost as $cat)
                {
                    if (isset($cat['nesting']) && isset($cat['id'])) 
                    {
                        foreach ($tree as $treecat) if ($treecat['id'] == $cat['id'])
                        {
                            $categories[] = array('nesting'=>$cat['nesting'],'id'=>$cat['id'],'error'=>'','name'=>$treecat['title'],'enabled'=>$treecat['enabled']);
                            break;
                        }
                    }
                }
            } else $errors = true;
            unset($categoriespost);
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'taxonomyEdit', $id, 'validate');
                if ($localerror == true) $errors = true;
                $i++;
            }     
            //Сохранение
            if ($errors == false)
            {
                for ($i = 0; $i < count($categories); $i++)
                {
                    $parentid = $id;
                    $j = $i - 1;
                    $ordering = 1;
                    while ($j >= 0)
                    {
                        if (($categories[$j]['nesting'] < $categories[$i]['nesting']) && isset($categories[$j]['id'])) {$parentid = $categories[$j]['id'];break;}
                        if ($categories[$j]['nesting'] == $categories[$i]['nesting']) $ordering++;
                        $j--;
                    }
                    $cattaxent = $em->getRepository('BasicCmsBundle:Taxonomies')->find($categories[$i]['id']);
                    if (!empty($cattaxent))
                    {
                        $cattaxent->setModifyDate(new \DateTime('now'));
                        $cattaxent->setOrdering($ordering);
                        $cattaxent->setParent($parentid);
                        $em->flush();
                    }
                    unset($cattaxent);
                }
                // Другие табы
                $i = 0;
                foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'taxonomyEdit', $id, 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                    $i++;
                }       
                //ok
                return new Response(json_encode(array('result'=>'OK','message'=>'')));
            }
        }
        return new Response(json_encode(array('result'=>'error','message'=>'Ошибка данных')));
    }
    
// *******************************************
// AJAX загрузка аватара
// *******************************************    

    public function taxonomyAjaxAvatarAction() 
    {
        /*$userId = $this->getUser()->getId();
        $file = $this->getRequest()->files->get('avatar');
        $tmpfile = $file->getPathName();
        $source = "";
        if (@getimagesize($tmpfile)) 
        {
            $params = getimagesize($tmpfile);
            if ($params[0] < 10) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла меньше 10x10')));
            if ($params[1] < 10) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла меньше 10x10')));
            if ($params[0] > 6000) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла больше 6000x6000')));
            if ($params[1] > 6000) return new Response(json_encode(array('file' => '', 'error' => 'Разрешение файла больше 6000x6000')));
            *switch ($params[2]) 
            {
                case 1: $source = @imagecreatefromgif($tmpfile); break;
                case 2: $source = @imagecreatefromjpeg($tmpfile); break;
                case 3: $source = @imagecreatefrompng($tmpfile); break;
            }
            if ($source) 
            {
                $basepath = '../images/taxonomy/';
                $name = $this->getUser()->getId().'_'.md5(time()).'.jpg';
                if (imagejpeg($source, $basepath . $name)) return new Response(json_encode(array('file' => '/images/taxonomy/'.$name, 'error' => '')));
            }
            unset($source);*
            $basepath = '../images/taxonomy/';
            $name = $this->getUser()->getId().'_'.md5(time()).'.jpg';
            if (move_uploaded_file($tmpfile, $basepath . $name)) return new Response(json_encode(array('file' => '/images/taxonomy/'.$name, 'error' => '')));
        } else return new Response(json_encode(array('file' => '', 'error' => 'Неправильный формат файла')));*/
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
            $basepath = '/images/taxonomy/';
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
// Редактирование категории
// *******************************************    
    public function taxonomyCatEditAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $taxonomyent = $this->getDoctrine()->getRepository('BasicCmsBundle:Taxonomies')->find($id);
        if (empty($taxonomyent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование категории',
                'message'=>'Категория не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку групп категорий'=>$this->get('router')->generate('basic_cms_taxonomy_list'))
            ));
        }
        if ($this->getUser()->checkAccess('taxonomy_view') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование категории',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.taxonomy','contentId'=>$id,'contentAction'=>'view'));
        $taxonomyparent = $this->getDoctrine()->getRepository('BasicCmsBundle:Taxonomies')->find($taxonomyent->getFirstParent());
        
        $tax = array();
        $texterror = array();
        
        $tax['title'] = $taxonomyent->getTitle();
        $tax['description'] = $taxonomyent->getDescription();
        $tax['avatar'] = $taxonomyent->getAvatar();
        $tax['enabled'] = $taxonomyent->getEnabled();
        $tax['enableadd'] = $taxonomyent->getEnableAdd();
        $tax['metadescr'] = $taxonomyent->getMetaDescription();
        $tax['metakey'] = $taxonomyent->getMetaKeywords();
        $taxerror['title'] = '';
        $taxerror['description'] = '';
        $taxerror['avatar'] = '';
        $taxerror['enabled'] = '';
        $taxerror['enableadd'] = '';
        $taxerror['metadescr'] = '';
        $taxerror['metakey'] = '';
        
        $page = array();
        $pageerror = array();
        
        if (empty($pageent))
        {
            $page['enable'] = 0;
            $page['url'] = '';
            $page['childsUrl'] = '';
            $page['modules'] = array();
            $page['locale'] = '';
            $page['template'] = '';
            $page['layout'] = '';
            $page['accessOn'] = '';
            $page['access'] = array();
        } else
        {
            $page['enable'] = 1;
            $page['url'] = ($pageent->getUrl() != $pageent->getId() ? $pageent->getUrl() : '');//$pageent->getUrl();
            $page['childsUrl'] = '';
            $page['modules'] = explode(',',$pageent->getModules());
            $page['locale'] = $pageent->getLocale();
            $page['template'] = $taxonomyent->getTemplate();
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
        $pageerror['childsUrl'] = '';
        $pageerror['modules'] = '';
        $pageerror['locale'] = '';
        $pageerror['template'] = '';
        $pageerror['layout'] = '';
        $pageerror['accessOn'] = '';
        $pageerror['access'] = '';
        
        $query = $em->createQuery('SELECT r.id, r.name FROM BasicCmsBundle:Roles r ORDER BY r.id');
        $roles = $query->getResult();
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.taxonomy','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.taxonomy.view');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $taxloc = array();
        $taxlocerror = array();
        foreach ($locales as $locale)
        {
            $taxlocent = $this->getDoctrine()->getRepository('BasicCmsBundle:TaxonomiesLocale')->findOneBy(array('locale'=>$locale['shortName'],'taxonomyId'=>$id));
            if (!empty($taxlocent))
            {
                $taxloc[$locale['shortName']]['title'] = $taxlocent->getTitle();
                $taxloc[$locale['shortName']]['description'] = $taxlocent->getDescription();
                $taxloc[$locale['shortName']]['metadescr'] = $taxlocent->getMetaDescription();
                $taxloc[$locale['shortName']]['metakey'] = $taxlocent->getMetaKeywords();
                unset($taxlocent);
            } else 
            {
                $taxloc[$locale['shortName']]['title'] = '';
                $taxloc[$locale['shortName']]['description'] = '';
                $taxloc[$locale['shortName']]['metadescr'] = '';
                $taxloc[$locale['shortName']]['metakey'] = '';
            }
            $taxlocerror[$locale['shortName']]['title'] = '';
            $taxlocerror[$locale['shortName']]['description'] = '';
            $taxlocerror[$locale['shortName']]['metadescr'] = '';
            $taxlocerror[$locale['shortName']]['metakey'] = '';
        }
        
        $objectparameters = array();
        if ($this->container->has($taxonomyent->getObject())) $objectparameters = $this->container->get($taxonomyent->getObject())->getTaxonomyParameters();
        
        $pageparams = array();
        $pageparamserror = array();
        $pageparams['includeCategories'] = '';
        $pageparams['getExtCatInfo'] = '';
        $pageparams['includeChilds'] = '';
        $pageparams['includeParent'] = '';
        $pageparams['sortField'] = '';
        $pageparams['sortDirection'] = '';
        $pageparams['sortGetOn'] = '';
        $pageparams['countValue'] = '';
        $pageparams['countGetOn'] = '';
        $pageparams['pageValue'] = '';
        $pageparams['pageGetOn'] = '';
        $pageparams['fields'] = array();
        $pageparamserror['includeCategories'] = '';
        $pageparamserror['getExtCatInfo'] = '';
        $pageparamserror['includeChilds'] = '';
        $pageparamserror['includeParent'] = '';
        $pageparamserror['sortField'] = '';
        $pageparamserror['sortDirection'] = '';
        $pageparamserror['sortGetOn'] = '';
        $pageparamserror['countValue'] = '';
        $pageparamserror['countGetOn'] = '';
        $pageparamserror['pageValue'] = '';
        $pageparamserror['pageGetOn'] = '';
        $pageparamserror['fields'] = '';
        $pageparamstemp = @unserialize($taxonomyent->getPageParameters());
        if (is_array($pageparamstemp))
        {
            if (isset($pageparamstemp['includeCategories'])) $pageparams['includeCategories'] = $pageparamstemp['includeCategories'];
            if (isset($pageparamstemp['getExtCatInfo'])) $pageparams['getExtCatInfo'] = $pageparamstemp['getExtCatInfo'];
            if (isset($pageparamstemp['includeChilds'])) $pageparams['includeChilds'] = $pageparamstemp['includeChilds'];
            if (isset($pageparamstemp['includeParent'])) $pageparams['includeParent'] = $pageparamstemp['includeParent'];
            if (isset($pageparamstemp['sortField'])) $pageparams['sortField'] = $pageparamstemp['sortField'];
            if (isset($pageparamstemp['sortDirection'])) $pageparams['sortDirection'] = $pageparamstemp['sortDirection'];
            if (isset($pageparamstemp['sortGetOn'])) $pageparams['sortGetOn'] = $pageparamstemp['sortGetOn'];
            if (isset($pageparamstemp['countValue'])) $pageparams['countValue'] = $pageparamstemp['countValue'];
            if (isset($pageparamstemp['countGetOn'])) $pageparams['countGetOn'] = $pageparamstemp['countGetOn'];
            if (isset($pageparamstemp['pageValue'])) $pageparams['pageValue'] = $pageparamstemp['pageValue'];
            if (isset($pageparamstemp['pageGetOn'])) $pageparams['pageGetOn'] = $pageparamstemp['pageGetOn'];
            if (isset($pageparamstemp['fields']) && is_array($pageparamstemp['fields'])) $pageparams['fields'] = $pageparamstemp['fields'];
        }
        unset($pageparamstemp);
        
        $filterparams = array();
        $filterparamstemp = @unserialize($taxonomyent->getFilterParameters());
        if (is_array($filterparamstemp))
        {
            foreach ($filterparamstemp as $onefilter)
            {
                if (isset($objectparameters[$onefilter['parameter']]) && isset($onefilter['compare']) && isset($onefilter['value']) && isset($onefilter['get']))
                {
                    if (isset($onefilter['getoff'])) $getoff = intval($onefilter['getoff']); else $getoff = 0;
                    $filterparams[] = array('parameter'=>$onefilter['parameter'], 'compare'=>$onefilter['compare'], 'value'=>$onefilter['value'], 'get'=>$onefilter['get'], 'getoff'=>$getoff);
                }
            }
        }
        unset($filterparamstemp);
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            if ($this->getUser()->checkAccess('taxonomy_edit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование категории',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            $changeCategoryChilds = intval($this->getRequest()->get('changecategorychilds'));
            // Проверка основных данных
            $posttax = $this->getRequest()->get('tax');
            if (isset($posttax['title'])) $tax['title'] = $posttax['title'];
            if (isset($posttax['description'])) $tax['description'] = $posttax['description'];
            if (isset($posttax['avatar'])) $tax['avatar'] = $posttax['avatar'];
            if (isset($posttax['enabled'])) $tax['enabled'] = intval($posttax['enabled']); else $tax['enabled'] = 0;
            if (isset($posttax['enableadd'])) $tax['enableadd'] = intval($posttax['enableadd']); else $tax['enableadd'] = 0;
            if (isset($posttax['metadescr'])) $tax['metadescr'] = $posttax['metadescr'];
            if (isset($posttax['metakey'])) $tax['metakey'] = $posttax['metakey'];
            unset($posttax);
            if (!preg_match("/^.{3,}$/ui", $tax['title'])) {$errors = true; $taxerror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (($tax['avatar'] != '') && (!file_exists('..'.$tax['avatar']))) {$errors = true; $taxerror['avatar'] = 'Файл не найден';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $tax['metadescr'])) {$errors = true; $taxerror['metadescr'] = 'Использованы недопустимые символы';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $tax['metakey'])) {$errors = true; $taxerror['metakey'] = 'Использованы недопустимые символы';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->getRequest()->get('page');
            if (isset($postpage['enable'])) $page['enable'] = intval($postpage['enable']); else $page['enable'] = 0;
            if (isset($postpage['url'])) $page['url'] = trim($postpage['url']);
            if (isset($postpage['childsUrl'])) $page['childsUrl'] = trim($postpage['childsUrl']);
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
                if (($changeCategoryChilds != 0) && ($page['childsUrl'] != ''))
                {
                    if (!preg_match("/^([a-z][a-z0-9\-]{0,90}\/)*[a-z][a-z0-9\-]{0,90}(\.([a-z0-9]{1,7}))?$/ui", str_replace(array('//id//','//title//'),'test',$page['childsUrl']))) {$errors = true; $pageerror['childsUrl'] = 'Адрес должен начинаться с латинской буквы, содержать латинские буквы, цифры и дефис, также допускается указание расширения через точку';}
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
            $posttaxloc = $this->getRequest()->get('taxloc');
            foreach ($locales as $locale)
            {
                if (isset($posttaxloc[$locale['shortName']]['title'])) $taxloc[$locale['shortName']]['title'] = $posttaxloc[$locale['shortName']]['title'];
                if (isset($posttaxloc[$locale['shortName']]['description'])) $taxloc[$locale['shortName']]['description'] = $posttaxloc[$locale['shortName']]['description'];
                if (isset($posttaxloc[$locale['shortName']]['metadescr'])) $taxloc[$locale['shortName']]['metadescr'] = $posttaxloc[$locale['shortName']]['metadescr'];
                if (isset($posttaxloc[$locale['shortName']]['metakey'])) $taxloc[$locale['shortName']]['metakey'] = $posttaxloc[$locale['shortName']]['metakey'];
                if (($taxloc[$locale['shortName']]['title'] != '') && (!preg_match("/^.{3,}$/ui", $taxloc[$locale['shortName']]['title']))) {$errors = true; $taxlocerror[$locale['shortName']]['title'] = 'Заголовок должен содержать более 3 символов';}
                if (($taxloc[$locale['shortName']]['metadescr'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $taxloc[$locale['shortName']]['metadescr']))) {$errors = true; $taxlocerror[$locale['shortName']]['metadescr'] = 'Использованы недопустимые символы';}
                if (($taxloc[$locale['shortName']]['metakey'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $taxloc[$locale['shortName']]['metakey']))) {$errors = true; $taxlocerror[$locale['shortName']]['metakey'] = 'Использованы недопустимые символы';}
            }
            unset($posttaxloc);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Валидация данныx о выводе
            $postpageparams = $this->getRequest()->get('pageparams');
            if (is_array($postpageparams))
            {
                if (isset($postpageparams['includeCategories'])) $pageparams['includeCategories'] = intval($postpageparams['includeCategories']); else $pageparams['includeCategories'] = 0;
                if (isset($postpageparams['getExtCatInfo'])) $pageparams['getExtCatInfo'] = intval($postpageparams['getExtCatInfo']); else $pageparams['getExtCatInfo'] = 0;
                if (isset($postpageparams['includeChilds'])) $pageparams['includeChilds'] = intval($postpageparams['includeChilds']); else $pageparams['includeChilds'] = 0;
                if (isset($postpageparams['includeParent'])) $pageparams['includeParent'] = intval($postpageparams['includeParent']); else $pageparams['includeParent'] = 0;
                if (isset($postpageparams['sortField'])) $pageparams['sortField'] = $postpageparams['sortField'];
                if (isset($postpageparams['sortDirection'])) $pageparams['sortDirection'] = intval($postpageparams['sortDirection']); else $pageparams['sortDirection'] = 0;
                if (isset($postpageparams['sortGetOn'])) $pageparams['sortGetOn'] = intval($postpageparams['sortGetOn']); else $pageparams['sortGetOn'] = 0;
                if (isset($postpageparams['countValue'])) $pageparams['countValue'] = $postpageparams['countValue'];
                if (isset($postpageparams['countGetOn'])) $pageparams['countGetOn'] = intval($postpageparams['countGetOn']); else $pageparams['countGetOn'] = 0;
                if (isset($postpageparams['pageValue'])) $pageparams['pageValue'] = $postpageparams['pageValue'];
                if (isset($postpageparams['pageGetOn'])) $pageparams['pageGetOn'] = intval($postpageparams['pageGetOn']); else $pageparams['pageGetOn'] = 0;
                if (isset($postpageparams['fields']) && is_array($postpageparams['fields'])) $pageparams['fields'] = $postpageparams['fields'];
            }
            unset($postpageparams);
            if (($pageparams['sortField'] != '') && (!isset($objectparameters[$pageparams['sortField']]))) {$errors = true; $pageparamserror['sortField'] = 'Указанное поле не найдено';}
            if ((!preg_match("/^\d+$/ui", $pageparams['countValue'])) || ($pageparams['countValue'] < 1) || ($pageparams['countValue'] > 1000)) {$errors = true; $pageparamserror['countValue'] = 'Должно быть указано число от 1 до 1000';}
            if ((!preg_match("/^\d+$/ui", $pageparams['pageValue'])) || ($pageparams['pageValue'] < 0)) {$errors = true; $pageparamserror['pageValue'] = 'Должно быть указано положительное число';}
            // Валидация фильтров
            $postfilterparams = $this->getRequest()->get('filterparams');
            $filterparams = array();
            if (is_array($postfilterparams))
            {
                foreach ($postfilterparams as $onefilter)
                {
                    if (isset($objectparameters[$onefilter['parameter']]) && isset($onefilter['compare']) && isset($onefilter['value']) && isset($onefilter['get']))
                    {
                        if (isset($onefilter['getoff'])) $getoff = intval($onefilter['getoff']); else $getoff = 0;
                        $filterparams[] = array('parameter'=>$onefilter['parameter'], 'compare'=>$onefilter['compare'], 'value'=>$onefilter['value'], 'get'=>$onefilter['get'], 'getoff'=>$getoff);
                    }
                }
            }
            unset($postfilterparams);
            foreach ($filterparams as &$onefilter)
            {
                if (isset($objectparameters[$onefilter['parameter']]['type']) && ($objectparameters[$onefilter['parameter']]['type'] == 'text'))
                {
                    if (($onefilter['compare'] != 'eq') && ($onefilter['compare'] != 'noeq') && ($onefilter['compare'] != 'like')) {$errors = true; $onefilter['compareerror'] = 'Неизвестный метод сравнения';}
                    if (!preg_match("/^[_A-z0-9]{5,}$/ui", $onefilter['get'])) {$errors = true; $onefilter['geterror'] = 'Имя параметра должно состоять из не менее 5 букв, цифр или подчёркивания';}
                }
                if (isset($objectparameters[$onefilter['parameter']]['type']) && ($objectparameters[$onefilter['parameter']]['type'] == 'int'))
                {
                    if (($onefilter['compare'] != 'eq') && ($onefilter['compare'] != 'noeq') && ($onefilter['compare'] != 'hi') && ($onefilter['compare'] != 'lo') && ($onefilter['compare'] != 'hieq') && ($onefilter['compare'] != 'loeq')) {$errors = true; $onefilter['compareerror'] = 'Неизвестный метод сравнения';}
                    if (!preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $onefilter['value'])) {$errors = true; $onefilter['valueerror'] = 'Должно быть указано число';}
                    if (!preg_match("/^[_A-z0-9]{5,}$/ui", $onefilter['get'])) {$errors = true; $onefilter['geterror'] = 'Имя параметра должно состоять из не менее 5 букв, цифр или подчёркивания';}
                }
                if (isset($objectparameters[$onefilter['parameter']]['type']) && ($objectparameters[$onefilter['parameter']]['type'] == 'date'))
                {
                    if (($onefilter['compare'] != 'eq') && ($onefilter['compare'] != 'noeq') && ($onefilter['compare'] != 'hi') && ($onefilter['compare'] != 'lo') && ($onefilter['compare'] != 'hieq') && ($onefilter['compare'] != 'loeq')) {$errors = true; $onefilter['compareerror'] = 'Неизвестный метод сравнения';}
                    if ((!preg_match("/^(\d?)\d\.\d\d\.\d\d\d\d(( \d\d:\d\d)?)$/ui", $onefilter['value'])) && (!preg_match("/^\-(\d+)$/ui", $onefilter['value']))) {$errors = true; $onefilter['valueerror'] = 'Должна быть указана дата';}
                    if (!preg_match("/^[_A-z0-9]{5,}$/ui", $onefilter['get'])) {$errors = true; $onefilter['geterror'] = 'Имя параметра должно состоять из не менее 5 букв, цифр или подчёркивания';}
                }
                if (isset($objectparameters[$onefilter['parameter']]['type']) && ($objectparameters[$onefilter['parameter']]['type'] == 'bool'))
                {
                    if (($onefilter['value'] != 1) && ($onefilter['value'] != 0)) {$errors = true; $onefilter['valueerror'] = 'Неизвестный метод сравнения';}
                    if (!preg_match("/^[_A-z0-9]{5,}$/ui", $onefilter['get'])) {$errors = true; $onefilter['geterror'] = 'Имя параметра должно состоять из не менее 5 букв, цифр или подчёркивания';}
                }
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 4;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'taxonomyCatEdit', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                if (($taxonomyent->getAvatar() != '') && ($taxonomyent->getAvatar() != $tax['avatar'])) @unlink('..'.$taxonomyent->getAvatar());
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($tax['avatar']);
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($tax['description'], $taxonomyent->getDescription());
                $taxonomyent->setTitle($tax['title']);
                $taxonomyent->setDescription($tax['description']);
                $taxonomyent->setAvatar($tax['avatar']);
                $taxonomyent->setEnabled($tax['enabled']);
                $taxonomyent->setEnableAdd($tax['enableadd']);
                $taxonomyent->setMetaDescription($tax['metadescr']);
                $taxonomyent->setMetaKeywords($tax['metakey']);
                $taxonomyent->setModifyDate(new \DateTime('now'));
                $taxonomyent->setPageParameters(serialize($pageparams));
                $taxonomyent->setFilterParameters(serialize($filterparams));
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
                    $pageent->setDescription('Просмотр категории '.$taxonomyent->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.taxonomy');
                    $pageent->setContentId($taxonomyent->getId());
                    $pageent->setContentAction('view');
                    $taxonomyent->setTemplate($page['template']);
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
                    $taxlocent = $this->getDoctrine()->getRepository('BasicCmsBundle:TaxonomiesLocale')->findOneBy(array('locale'=>$locale['shortName'],'taxonomyId'=>$id));
                    if (($taxloc[$locale['shortName']]['title'] != '') ||
                        ($taxloc[$locale['shortName']]['description'] != '') ||
                        ($taxloc[$locale['shortName']]['metadescr'] != '') ||
                        ($taxloc[$locale['shortName']]['metakey'] != ''))
                    {
                        if (empty($taxlocent))
                        {
                            $taxlocent = new \Basic\CmsBundle\Entity\TaxonomiesLocale();
                            $em->persist($taxlocent);
                        }
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($taxloc[$locale['shortName']]['description'], $taxlocent->getDescription());
                        $taxlocent->setTaxonomyId($taxonomyent->getId());
                        $taxlocent->setLocale($locale['shortName']);
                        if ($taxloc[$locale['shortName']]['title'] == '') $taxlocent->setTitle(null); else $taxlocent->setTitle($taxloc[$locale['shortName']]['title']);
                        if ($taxloc[$locale['shortName']]['description'] == '') $taxlocent->setDescription(null); else $taxlocent->setDescription($taxloc[$locale['shortName']]['description']);
                        if ($taxloc[$locale['shortName']]['metadescr'] == '') $taxlocent->setMetaDescription(null); else $taxlocent->setMetaDescription($taxloc[$locale['shortName']]['metadescr']);
                        if ($taxloc[$locale['shortName']]['metakey'] == '') $taxlocent->setMetaKeywords(null); else $taxlocent->setMetaKeywords($taxloc[$locale['shortName']]['metakey']);
                        $em->flush();
                    } else
                    {
                        if (!empty($taxlocent))
                        {
                            $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $taxlocent->getDescription());
                            $em->remove($taxlocent);
                            $em->flush();
                        }
                    }
                    unset($taxlocent);
                }
                $i = 0;
                foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'taxonomyCatEdit', $taxonomyent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }       
                // Изменение потомков
                if ($changeCategoryChilds != 0)
                {
                    $childsCount = 0;
                    // Найти ID потомков
                    $categoryIds = array($id);
                    $query = $em->createQuery('SELECT t.id, t.parent FROM BasicCmsBundle:Taxonomies t '.
                                              'WHERE t.firstParent = :id AND t.parent is not null '.
                                              'ORDER BY t.ordering')->setParameter('id', $taxonomyent->getFirstParent());
                    $tree = $query->getResult();
                    do
                    {
                        $count = 0;
                        foreach ($tree as $cat) if ((in_array($cat['parent'], $categoryIds)) && (!in_array($cat['id'], $categoryIds))) {$categoryIds[] = $cat['id'];$count++;}
                    } while ($count > 0);
                    // Изменяем категории
                    foreach ($categoryIds as $categoryId) if ($categoryId != $id)
                    {
                        $taxonomychildent = $this->getDoctrine()->getRepository('BasicCmsBundle:Taxonomies')->find($categoryId);
                        $pagechildent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.taxonomy','contentId'=>$categoryId,'contentAction'=>'view'));
                        if (!empty($taxonomychildent))
                        {
                            $taxonomychildent->setModifyDate(new \DateTime('now'));
                            $taxonomychildent->setPageParameters(serialize($pageparams));
                            $taxonomychildent->setFilterParameters(serialize($filterparams));
                            $em->flush();
                            if (!empty($pagechildent)) $this->get('cms.cmsManager')->updateBreadCrumbs($pagechildent->getId());
                            if ($page['enable'] != 0)
                            {
                                $pageurl = $this->container->get('cms.cmsManager')->getUniqueUrl($page['childsUrl'], array('id'=>$taxonomychildent->getId(), 'title'=>$taxonomychildent->getTitle()));
                                if (empty($pagechildent))
                                {
                                    $pagechildent = new \Basic\CmsBundle\Entity\SeoPage();
                                    $em->persist($pagechildent);
                                }
                                $pagechildent->setBreadCrumbs('');
                                $pagechildent->setBreadCrumbsRel('');
                                $pagechildent->setDescription('Просмотр категории '.$taxonomychildent->getTitle());
                                $pagechildent->setUrl($pageurl);
                                $pagechildent->setTemplate($page['layout']);
                                $pagechildent->setModules(implode(',', $page['modules']));
                                $pagechildent->setLocale($page['locale']);
                                $pagechildent->setContentType('object.taxonomy');
                                $pagechildent->setContentId($taxonomychildent->getId());
                                $pagechildent->setContentAction('view');
                                $taxonomychildent->setTemplate($page['template']);
                                if ($page['accessOn'] != 0) $pagechildent->setAccess(implode(',', $page['access'])); else $pagechildent->setAccess('');
                                $em->flush();
                                if ($pageurl == '') {$pagechildent->setUrl($pagechildent->getId());$em->flush();}
                            } else
                            {
                                if (!empty($pagechildent))
                                {
                                    $em->remove($pagechildent);
                                    $em->flush();
                                }
                            }
                            // остальные модули
                            $i = 0;
                            foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
                            {
                                $serv = $this->container->get($item);
                                $localerror = $serv->getAdminController($this->getRequest(), 'taxonomyCatEdit', $taxonomychildent->getId(), 'saveChilds');
                                if ($localerror == true) $errors = true;
                                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                                $i++;
                            }       
                            unset($pagechildent);
                            unset($taxonomychildent);
                            $childsCount++;
                        }
                    }
                    return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                        'title'=>'Редактирование категории',
                        'message'=>'Категория и её потомки ('.$childsCount.') успешно изменены',
                        'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_taxonomy_catedit').'?id='.$id, 'Вернуться к списку категорий'=>$this->get('router')->generate('basic_cms_taxonomy_edit', array('id'=>$taxonomyent->getFirstParent())))
                    ));
                }
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование категории',
                    'message'=>'Категория успешно изменена',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_taxonomy_catedit').'?id='.$id, 'Вернуться к списку категорий'=>$this->get('router')->generate('basic_cms_taxonomy_edit', array('id'=>$taxonomyent->getFirstParent())))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'taxonomyCatEdit', $id, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Taxonomy:categoryEdit.html.twig', array(
            'id' => $id,
            'taxonomyid' => $taxonomyent->getFirstParent(),
            'taxonomyname' => (empty($taxonomyparent) ? '' : $taxonomyparent->getTitle()),
            'tax' => $tax,
            'taxerror' => $taxerror,
            'page' => $page,
            'pageerror' => $pageerror,
            'taxloc' => $taxloc,
            'taxlocerror' => $taxlocerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'objectparameters' => $objectparameters,
            'pageparams' => $pageparams,
            'pageparamserror' => $pageparamserror,
            'filterparams' => $filterparams,
            'roles' => $roles,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
    }

// *******************************************
// Создание представления
// *******************************************    
    public function taxonomyShowCreateAction()
    {
        $this->getRequest()->getSession()->set('basic_cms_taxonomy_list_tab', 1);
        $em = $this->getDoctrine()->getEntityManager();
        if ($this->getUser()->checkAccess('taxonomy_newshow') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание нового представления',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        // Предварительный выбор объекта классификации
        $taxonomyobjects = array();
        $taxonomyobject = $this->getRequest()->get('object');
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if ((strpos($item,'object.') === 0) && ($this->container->get($item)->getTaxonomyType() == true)) $taxonomyobjects[$item] = $this->container->get($item)->getDescription();
        if (!isset($taxonomyobjects[$taxonomyobject]))
        {
            return $this->render('BasicCmsBundle:Taxonomy:showPreCreate.html.twig', array(
                'taxonomyobjects'=>$taxonomyobjects
            ));
        }
        
        $tax = array();
        $texterror = array();
        $tax['title'] = '';
        $tax['description'] = '';
        $tax['avatar'] = '';
        $tax['enabled'] = 1;
        $tax['metadescr'] = '';
        $tax['metakey'] = '';
        $taxerror['title'] = '';
        $taxerror['description'] = '';
        $taxerror['avatar'] = '';
        $taxerror['enabled'] = '';
        $taxerror['metadescr'] = '';
        $taxerror['metakey'] = '';
        
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
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.taxonomy','viewshow');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.taxonomy.viewshow');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $taxloc = array();
        $taxlocerror = array();
        foreach ($locales as $locale)
        {
            $taxloc[$locale['shortName']]['title'] = '';
            $taxloc[$locale['shortName']]['description'] = '';
            $taxloc[$locale['shortName']]['metadescr'] = '';
            $taxloc[$locale['shortName']]['metakey'] = '';
            $taxlocerror[$locale['shortName']]['title'] = '';
            $taxlocerror[$locale['shortName']]['description'] = '';
            $taxlocerror[$locale['shortName']]['metadescr'] = '';
            $taxlocerror[$locale['shortName']]['metakey'] = '';
        }
        
        $objectparameters = array();
        if ($this->container->has($taxonomyobject)) $objectparameters = $this->container->get($taxonomyobject)->getTaxonomyParameters();
        
        $pageparams = array();
        $pageparamserror = array();
        $pageparams['sortField'] = '';
        $pageparams['sortDirection'] = '';
        $pageparams['sortGetOn'] = '';
        $pageparams['countValue'] = '';
        $pageparams['countGetOn'] = '';
        $pageparams['pageValue'] = '';
        $pageparams['pageGetOn'] = '';
        $pageparams['fields'] = array();
        $pageparams['categoryGetSeo'] = '';
        $pageparamserror['sortField'] = '';
        $pageparamserror['sortDirection'] = '';
        $pageparamserror['sortGetOn'] = '';
        $pageparamserror['countValue'] = '';
        $pageparamserror['countGetOn'] = '';
        $pageparamserror['pageValue'] = '';
        $pageparamserror['pageGetOn'] = '';
        $pageparamserror['fields'] = '';
        $pageparamserror['categoryGetSeo'] = '';
        
        $filterparams = array();
        
        $categories = array();
        $query = $em->createQuery('SELECT t.id, t.title, t.enabled, t.parent FROM BasicCmsBundle:Taxonomies t '.
                                  'WHERE t.object = :object '.
                                  'ORDER BY t.ordering')->setParameter('object', $taxonomyobject);
        $tree = $query->getResult();
        $this->taxonomyGetTree($tree, null, 0, $categories);
        $selcategories = array();
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $posttax = $this->getRequest()->get('tax');
            if (isset($posttax['title'])) $tax['title'] = $posttax['title'];
            if (isset($posttax['description'])) $tax['description'] = $posttax['description'];
            if (isset($posttax['avatar'])) $tax['avatar'] = $posttax['avatar'];
            if (isset($posttax['enabled'])) $tax['enabled'] = intval($posttax['enabled']); else $tax['enabled'] = 0;
            if (isset($posttax['metadescr'])) $tax['metadescr'] = $posttax['metadescr'];
            if (isset($posttax['metakey'])) $tax['metakey'] = $posttax['metakey'];
            unset($posttax);
            if (!preg_match("/^.{3,}$/ui", $tax['title'])) {$errors = true; $taxerror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (($tax['avatar'] != '') && (!file_exists('..'.$tax['avatar']))) {$errors = true; $taxerror['avatar'] = 'Файл не найден';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $tax['metadescr'])) {$errors = true; $taxerror['metadescr'] = 'Использованы недопустимые символы';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $tax['metakey'])) {$errors = true; $taxerror['metakey'] = 'Использованы недопустимые символы';}
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
            $posttaxloc = $this->getRequest()->get('taxloc');
            foreach ($locales as $locale)
            {
                if (isset($posttaxloc[$locale['shortName']]['title'])) $taxloc[$locale['shortName']]['title'] = $posttaxloc[$locale['shortName']]['title'];
                if (isset($posttaxloc[$locale['shortName']]['description'])) $taxloc[$locale['shortName']]['description'] = $posttaxloc[$locale['shortName']]['description'];
                if (isset($posttaxloc[$locale['shortName']]['metadescr'])) $taxloc[$locale['shortName']]['metadescr'] = $posttaxloc[$locale['shortName']]['metadescr'];
                if (isset($posttaxloc[$locale['shortName']]['metakey'])) $taxloc[$locale['shortName']]['metakey'] = $posttaxloc[$locale['shortName']]['metakey'];
                if (($taxloc[$locale['shortName']]['title'] != '') && (!preg_match("/^.{3,}$/ui", $taxloc[$locale['shortName']]['title']))) {$errors = true; $taxlocerror[$locale['shortName']]['title'] = 'Заголовок должен содержать более 3 символов';}
                if (($taxloc[$locale['shortName']]['metadescr'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $taxloc[$locale['shortName']]['metadescr']))) {$errors = true; $taxlocerror[$locale['shortName']]['metadescr'] = 'Использованы недопустимые символы';}
                if (($taxloc[$locale['shortName']]['metakey'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $taxloc[$locale['shortName']]['metakey']))) {$errors = true; $taxlocerror[$locale['shortName']]['metakey'] = 'Использованы недопустимые символы';}
            }
            unset($posttaxloc);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Валидация данныx о выводе
            $postpageparams = $this->getRequest()->get('pageparams');
            if (is_array($postpageparams))
            {
                if (isset($postpageparams['sortField'])) $pageparams['sortField'] = $postpageparams['sortField'];
                if (isset($postpageparams['sortDirection'])) $pageparams['sortDirection'] = intval($postpageparams['sortDirection']); else $pageparams['sortDirection'] = 0;
                if (isset($postpageparams['sortGetOn'])) $pageparams['sortGetOn'] = intval($postpageparams['sortGetOn']); else $pageparams['sortGetOn'] = 0;
                if (isset($postpageparams['countValue'])) $pageparams['countValue'] = $postpageparams['countValue'];
                if (isset($postpageparams['countGetOn'])) $pageparams['countGetOn'] = intval($postpageparams['countGetOn']); else $pageparams['countGetOn'] = 0;
                if (isset($postpageparams['pageValue'])) $pageparams['pageValue'] = $postpageparams['pageValue'];
                if (isset($postpageparams['pageGetOn'])) $pageparams['pageGetOn'] = intval($postpageparams['pageGetOn']); else $pageparams['pageGetOn'] = 0;
                if (isset($postpageparams['fields']) && is_array($postpageparams['fields'])) $pageparams['fields'] = $postpageparams['fields'];
                if (isset($postpageparams['categoryGetSeo'])) $pageparams['categoryGetSeo'] = intval($postpageparams['categoryGetSeo']); else $pageparams['categoryGetSeo'] = 0;
            }
            unset($postpageparams);
            if (($pageparams['sortField'] != '') && (!isset($objectparameters[$pageparams['sortField']]))) {$errors = true; $pageparamserror['sortField'] = 'Указанное поле не найдено';}
            if ((!preg_match("/^\d+$/ui", $pageparams['countValue'])) || ($pageparams['countValue'] < 1) || ($pageparams['countValue'] > 1000)) {$errors = true; $pageparamserror['countValue'] = 'Должно быть указано число от 1 до 1000';}
            if ((!preg_match("/^\d+$/ui", $pageparams['pageValue'])) || ($pageparams['pageValue'] < 0)) {$errors = true; $pageparamserror['pageValue'] = 'Должно быть указано положительное число';}
            // Валидация фильтров
            $postfilterparams = $this->getRequest()->get('filterparams');
            $filterparams = array();
            if (is_array($postfilterparams))
            {
                foreach ($postfilterparams as $onefilter)
                {
                    if (isset($objectparameters[$onefilter['parameter']]) && isset($onefilter['compare']) && isset($onefilter['value']) && isset($onefilter['get']))
                    {
                        if (isset($onefilter['getoff'])) $getoff = intval($onefilter['getoff']); else $getoff = 0;
                        $filterparams[] = array('parameter'=>$onefilter['parameter'], 'compare'=>$onefilter['compare'], 'value'=>$onefilter['value'], 'get'=>$onefilter['get'], 'getoff'=>$getoff);
                    }
                }
            }
            unset($postfilterparams);
            foreach ($filterparams as &$onefilter)
            {
                if (isset($objectparameters[$onefilter['parameter']]['type']) && ($objectparameters[$onefilter['parameter']]['type'] == 'text'))
                {
                    if (($onefilter['compare'] != 'eq') && ($onefilter['compare'] != 'noeq') && ($onefilter['compare'] != 'like')) {$errors = true; $onefilter['compareerror'] = 'Неизвестный метод сравнения';}
                    if (!preg_match("/^([_A-z0-9]{5,})?$/ui", $onefilter['get'])) {$errors = true; $onefilter['geterror'] = 'Имя параметра должно состоять из не менее 5 букв, цифр или подчёркивания';}
                }
                if (isset($objectparameters[$onefilter['parameter']]['type']) && ($objectparameters[$onefilter['parameter']]['type'] == 'int'))
                {
                    if (($onefilter['compare'] != 'eq') && ($onefilter['compare'] != 'noeq') && ($onefilter['compare'] != 'hi') && ($onefilter['compare'] != 'lo') && ($onefilter['compare'] != 'hieq') && ($onefilter['compare'] != 'loeq')) {$errors = true; $onefilter['compareerror'] = 'Неизвестный метод сравнения';}
                    if (!preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $onefilter['value'])) {$errors = true; $onefilter['valueerror'] = 'Должно быть указано число';}
                    if (!preg_match("/^([_A-z0-9]{5,})?$/ui", $onefilter['get'])) {$errors = true; $onefilter['geterror'] = 'Имя параметра должно состоять из не менее 5 букв, цифр или подчёркивания';}
                }
                if (isset($objectparameters[$onefilter['parameter']]['type']) && ($objectparameters[$onefilter['parameter']]['type'] == 'date'))
                {
                    if (($onefilter['compare'] != 'eq') && ($onefilter['compare'] != 'noeq') && ($onefilter['compare'] != 'hi') && ($onefilter['compare'] != 'lo') && ($onefilter['compare'] != 'hieq') && ($onefilter['compare'] != 'loeq')) {$errors = true; $onefilter['compareerror'] = 'Неизвестный метод сравнения';}
                    if ((!preg_match("/^(\d?)\d\.\d\d\.\d\d\d\d(( \d\d:\d\d)?)$/ui", $onefilter['value'])) && (!preg_match("/^\-(\d+)$/ui", $onefilter['value']))) {$errors = true; $onefilter['valueerror'] = 'Должна быть указана дата';}
                    if (!preg_match("/^([_A-z0-9]{5,})?$/ui", $onefilter['get'])) {$errors = true; $onefilter['geterror'] = 'Имя параметра должно состоять из не менее 5 букв, цифр или подчёркивания';}
                }
                if (isset($objectparameters[$onefilter['parameter']]['type']) && ($objectparameters[$onefilter['parameter']]['type'] == 'bool'))
                {
                    if (($onefilter['value'] != 1) && ($onefilter['value'] != 0)) {$errors = true; $onefilter['valueerror'] = 'Неизвестный метод сравнения';}
                    if (!preg_match("/^([_A-z0-9]{5,})?$/ui", $onefilter['get'])) {$errors = true; $onefilter['geterror'] = 'Имя параметра должно состоять из не менее 5 букв, цифр или подчёркивания';}
                }
            }
            $selcategories = array();
            $postselcategories = $this->getRequest()->get('selcategories');
            if (is_array($postselcategories)) 
            {
                foreach ($categories as $cat) if (in_array($cat['id'], $postselcategories)) $selcategories[] = $cat['id'];
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 4;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'taxonomyShowCreate', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($tax['avatar']);
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($tax['description'], '');
                $showent = new \Basic\CmsBundle\Entity\TaxonomyShows();
                $em->persist($showent);
                $showent->setCreateDate(new \DateTime('now'));
                $showent->setCreaterId($this->getUser()->getId());
                $showent->setObject($taxonomyobject);
                $showent->setTitle($tax['title']);
                $showent->setDescription($tax['description']);
                $showent->setAvatar($tax['avatar']);
                $showent->setEnabled($tax['enabled']);
                $showent->setMetaDescription($tax['metadescr']);
                $showent->setMetaKeywords($tax['metakey']);
                $showent->setModifyDate(new \DateTime('now'));
                $showent->setPageParameters(serialize($pageparams));
                $showent->setFilterParameters(serialize($filterparams));
                $showent->setTaxonomyIds(implode(',', $selcategories));
                $em->flush();
                if ($page['enable'] != 0)
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $em->persist($pageent);
                    $pageent->setBreadCrumbs('');
                    $pageent->setBreadCrumbsRel('');
                    $pageent->setDescription('Просмотр представления '.$showent->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.taxonomy');
                    $pageent->setContentId($showent->getId());
                    $pageent->setContentAction('viewshow');
                    $showent->setTemplate($page['template']);
                    if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                    $em->flush();
                    if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                }
                foreach ($locales as $locale)
                {
                    if (($taxloc[$locale['shortName']]['title'] != '') ||
                        ($taxloc[$locale['shortName']]['description'] != '') ||
                        ($taxloc[$locale['shortName']]['metadescr'] != '') ||
                        ($taxloc[$locale['shortName']]['metakey'] != ''))
                    {
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($taxloc[$locale['shortName']]['description'], '');
                        $taxlocent = new \Basic\CmsBundle\Entity\TaxonomyShowsLocale();
                        $em->persist($taxlocent);
                        $taxlocent->setShowId($showent->getId());
                        $taxlocent->setLocale($locale['shortName']);
                        if ($taxloc[$locale['shortName']]['title'] == '') $taxlocent->setTitle(null); else $taxlocent->setTitle($taxloc[$locale['shortName']]['title']);
                        if ($taxloc[$locale['shortName']]['description'] == '') $taxlocent->setDescription(null); else $taxlocent->setDescription($taxloc[$locale['shortName']]['description']);
                        if ($taxloc[$locale['shortName']]['metadescr'] == '') $taxlocent->setMetaDescription(null); else $taxlocent->setMetaDescription($taxloc[$locale['shortName']]['metadescr']);
                        if ($taxloc[$locale['shortName']]['metakey'] == '') $taxlocent->setMetaKeywords(null); else $taxlocent->setMetaKeywords($taxloc[$locale['shortName']]['metakey']);
                        $em->flush();
                        unset($taxlocent);
                    }
                }
                $i = 0;
                foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'taxonomyShowCreate', $showent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание нового представления',
                    'message'=>'Представление успешно создано',
                    'paths'=>array('Создать еще одно представление'=>$this->get('router')->generate('basic_cms_taxonomyshow_create'), 'Вернуться к списку представлений'=>$this->get('router')->generate('basic_cms_taxonomy_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'taxonomyShowCreate', 0, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Taxonomy:showCreate.html.twig', array(
            'taxonomyobject' => $taxonomyobject,
            'tax' => $tax,
            'taxerror' => $taxerror,
            'page' => $page,
            'pageerror' => $pageerror,
            'taxloc' => $taxloc,
            'taxlocerror' => $taxlocerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'objectparameters' => $objectparameters,
            'pageparams' => $pageparams,
            'pageparamserror' => $pageparamserror,
            'filterparams' => $filterparams,
            'categories' => $categories,
            'selcategories' => $selcategories,
            'roles' => $roles,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// Редактирование представления
// *******************************************    
    public function taxonomyShowEditAction()
    {
        $this->getRequest()->getSession()->set('basic_cms_taxonomy_list_tab', 1);
        $em = $this->getDoctrine()->getEntityManager();
        if ($this->getUser()->checkAccess('taxonomy_viewshow') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование представления',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $id = $this->getRequest()->get('id');
        $showent = $this->getDoctrine()->getRepository('BasicCmsBundle:TaxonomyShows')->find($id);
        if (empty($showent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование представления',
                'message'=>'Представление не найдено', 
                'error'=>true,
                'paths'=>array('Вернуться к списку представлений'=>$this->get('router')->generate('basic_cms_taxonomy_list'))
            ));
        }
        // Предварительный выбор объекта классификации
        $taxonomyobject = $showent->getObject();
        if (!($this->container->has($taxonomyobject)))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование представления',
                'message'=>'Объект представления не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к списку представлений'=>$this->get('router')->generate('basic_cms_taxonomy_list'))
            ));
        }
        
        $tax = array();
        $texterror = array();
        $tax['title'] = $showent->getTitle();
        $tax['description'] = $showent->getDescription();
        $tax['avatar'] = $showent->getAvatar();
        $tax['enabled'] = $showent->getEnabled();
        $tax['metadescr'] = $showent->getMetaDescription();
        $tax['metakey'] = $showent->getMetaKeywords();
        $taxerror['title'] = '';
        $taxerror['description'] = '';
        $taxerror['avatar'] = '';
        $taxerror['enabled'] = '';
        $taxerror['metadescr'] = '';
        $taxerror['metakey'] = '';
        
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.taxonomy','contentId'=>$id,'contentAction'=>'viewshow'));
        
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
            $page['template'] = $showent->getTemplate();
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
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.taxonomy','viewshow');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.taxonomy.viewshow');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $taxloc = array();
        $taxlocerror = array();
        foreach ($locales as $locale)
        {
            $taxlocent = $this->getDoctrine()->getRepository('BasicCmsBundle:TaxonomyShowsLocale')->findOneBy(array('locale'=>$locale['shortName'],'showId'=>$id));
            if (!empty($taxlocent))
            {
                $taxloc[$locale['shortName']]['title'] = $taxlocent->getTitle();
                $taxloc[$locale['shortName']]['description'] = $taxlocent->getDescription();
                $taxloc[$locale['shortName']]['metadescr'] = $taxlocent->getMetaDescription();
                $taxloc[$locale['shortName']]['metakey'] = $taxlocent->getMetaKeywords();
                unset($taxlocent);
            } else 
            {
                $taxloc[$locale['shortName']]['title'] = '';
                $taxloc[$locale['shortName']]['description'] = '';
                $taxloc[$locale['shortName']]['metadescr'] = '';
                $taxloc[$locale['shortName']]['metakey'] = '';
            }
            $taxlocerror[$locale['shortName']]['title'] = '';
            $taxlocerror[$locale['shortName']]['description'] = '';
            $taxlocerror[$locale['shortName']]['metadescr'] = '';
            $taxlocerror[$locale['shortName']]['metakey'] = '';
        }
        
        $objectparameters = array();
        if ($this->container->has($taxonomyobject)) $objectparameters = $this->container->get($taxonomyobject)->getTaxonomyParameters();
        
        $pageparams = array();
        $pageparamserror = array();
        $pageparams['sortField'] = '';
        $pageparams['sortDirection'] = '';
        $pageparams['sortGetOn'] = '';
        $pageparams['countValue'] = '';
        $pageparams['countGetOn'] = '';
        $pageparams['pageValue'] = '';
        $pageparams['pageGetOn'] = '';
        $pageparams['fields'] = array();
        $pageparams['categoryGetSeo'] = '';
        $pageparamserror['sortField'] = '';
        $pageparamserror['sortDirection'] = '';
        $pageparamserror['sortGetOn'] = '';
        $pageparamserror['countValue'] = '';
        $pageparamserror['countGetOn'] = '';
        $pageparamserror['pageValue'] = '';
        $pageparamserror['pageGetOn'] = '';
        $pageparamserror['fields'] = '';
        $pageparamserror['categoryGetSeo'] = '';
        $pageparamstemp = @unserialize($showent->getPageParameters());
        if (is_array($pageparamstemp))
        {
            if (isset($pageparamstemp['sortField'])) $pageparams['sortField'] = $pageparamstemp['sortField'];
            if (isset($pageparamstemp['sortDirection'])) $pageparams['sortDirection'] = $pageparamstemp['sortDirection'];
            if (isset($pageparamstemp['sortGetOn'])) $pageparams['sortGetOn'] = $pageparamstemp['sortGetOn'];
            if (isset($pageparamstemp['countValue'])) $pageparams['countValue'] = $pageparamstemp['countValue'];
            if (isset($pageparamstemp['countGetOn'])) $pageparams['countGetOn'] = $pageparamstemp['countGetOn'];
            if (isset($pageparamstemp['pageValue'])) $pageparams['pageValue'] = $pageparamstemp['pageValue'];
            if (isset($pageparamstemp['pageGetOn'])) $pageparams['pageGetOn'] = $pageparamstemp['pageGetOn'];
            if (isset($pageparamstemp['fields']) && is_array($pageparamstemp['fields'])) $pageparams['fields'] = $pageparamstemp['fields'];
            if (isset($pageparamstemp['categoryGetSeo'])) $pageparams['categoryGetSeo'] = $pageparamstemp['categoryGetSeo'];
        }
        unset($pageparamstemp);
        
        $filterparams = array();
        $filterparamstemp = @unserialize($showent->getFilterParameters());
        if (is_array($filterparamstemp))
        {
            foreach ($filterparamstemp as $onefilter)
            {
                if (isset($objectparameters[$onefilter['parameter']]) && isset($onefilter['compare']) && isset($onefilter['value']) && isset($onefilter['get']))
                {
                    if (isset($onefilter['getoff'])) $getoff = intval($onefilter['getoff']); else $getoff = 0;
                    $filterparams[] = array('parameter'=>$onefilter['parameter'], 'compare'=>$onefilter['compare'], 'value'=>$onefilter['value'], 'get'=>$onefilter['get'], 'getoff'=>$getoff);
                }
            }
        }
        unset($filterparamstemp);
        
        $categories = array();
        $query = $em->createQuery('SELECT t.id, t.title, t.enabled, t.parent FROM BasicCmsBundle:Taxonomies t '.
                                  'WHERE t.object = :object '.
                                  'ORDER BY t.ordering')->setParameter('object', $taxonomyobject);
        $tree = $query->getResult();
        $this->taxonomyGetTree($tree, null, 0, $categories);
        $selcategories = explode(',', $showent->getTaxonomyIds());
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            if ($this->getUser()->checkAccess('taxonomy_editshow') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование представления',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            // Проверка основных данных
            $posttax = $this->getRequest()->get('tax');
            if (isset($posttax['title'])) $tax['title'] = $posttax['title'];
            if (isset($posttax['description'])) $tax['description'] = $posttax['description'];
            if (isset($posttax['avatar'])) $tax['avatar'] = $posttax['avatar'];
            if (isset($posttax['enabled'])) $tax['enabled'] = intval($posttax['enabled']); else $tax['enabled'] = 0;
            if (isset($posttax['metadescr'])) $tax['metadescr'] = $posttax['metadescr'];
            if (isset($posttax['metakey'])) $tax['metakey'] = $posttax['metakey'];
            unset($posttax);
            if (!preg_match("/^.{3,}$/ui", $tax['title'])) {$errors = true; $taxerror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (($tax['avatar'] != '') && (!file_exists('..'.$tax['avatar']))) {$errors = true; $taxerror['avatar'] = 'Файл не найден';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $tax['metadescr'])) {$errors = true; $taxerror['metadescr'] = 'Использованы недопустимые символы';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $tax['metakey'])) {$errors = true; $taxerror['metakey'] = 'Использованы недопустимые символы';}
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
            $posttaxloc = $this->getRequest()->get('taxloc');
            foreach ($locales as $locale)
            {
                if (isset($posttaxloc[$locale['shortName']]['title'])) $taxloc[$locale['shortName']]['title'] = $posttaxloc[$locale['shortName']]['title'];
                if (isset($posttaxloc[$locale['shortName']]['description'])) $taxloc[$locale['shortName']]['description'] = $posttaxloc[$locale['shortName']]['description'];
                if (isset($posttaxloc[$locale['shortName']]['metadescr'])) $taxloc[$locale['shortName']]['metadescr'] = $posttaxloc[$locale['shortName']]['metadescr'];
                if (isset($posttaxloc[$locale['shortName']]['metakey'])) $taxloc[$locale['shortName']]['metakey'] = $posttaxloc[$locale['shortName']]['metakey'];
                if (($taxloc[$locale['shortName']]['title'] != '') && (!preg_match("/^.{3,}$/ui", $taxloc[$locale['shortName']]['title']))) {$errors = true; $taxlocerror[$locale['shortName']]['title'] = 'Заголовок должен содержать более 3 символов';}
                if (($taxloc[$locale['shortName']]['metadescr'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $taxloc[$locale['shortName']]['metadescr']))) {$errors = true; $taxlocerror[$locale['shortName']]['metadescr'] = 'Использованы недопустимые символы';}
                if (($taxloc[$locale['shortName']]['metakey'] != '') && (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $taxloc[$locale['shortName']]['metakey']))) {$errors = true; $taxlocerror[$locale['shortName']]['metakey'] = 'Использованы недопустимые символы';}
            }
            unset($posttaxloc);
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Валидация данныx о выводе
            $postpageparams = $this->getRequest()->get('pageparams');
            if (is_array($postpageparams))
            {
                if (isset($postpageparams['sortField'])) $pageparams['sortField'] = $postpageparams['sortField'];
                if (isset($postpageparams['sortDirection'])) $pageparams['sortDirection'] = intval($postpageparams['sortDirection']); else $pageparams['sortDirection'] = 0;
                if (isset($postpageparams['sortGetOn'])) $pageparams['sortGetOn'] = intval($postpageparams['sortGetOn']); else $pageparams['sortGetOn'] = 0;
                if (isset($postpageparams['countValue'])) $pageparams['countValue'] = $postpageparams['countValue'];
                if (isset($postpageparams['countGetOn'])) $pageparams['countGetOn'] = intval($postpageparams['countGetOn']); else $pageparams['countGetOn'] = 0;
                if (isset($postpageparams['pageValue'])) $pageparams['pageValue'] = $postpageparams['pageValue'];
                if (isset($postpageparams['pageGetOn'])) $pageparams['pageGetOn'] = intval($postpageparams['pageGetOn']); else $pageparams['pageGetOn'] = 0;
                if (isset($postpageparams['fields']) && is_array($postpageparams['fields'])) $pageparams['fields'] = $postpageparams['fields'];
                if (isset($postpageparams['categoryGetSeo'])) $pageparams['categoryGetSeo'] = intval($postpageparams['categoryGetSeo']); else $pageparams['categoryGetSeo'] = 0;
            }
            unset($postpageparams);
            if (($pageparams['sortField'] != '') && (!isset($objectparameters[$pageparams['sortField']]))) {$errors = true; $pageparamserror['sortField'] = 'Указанное поле не найдено';}
            if ((!preg_match("/^\d+$/ui", $pageparams['countValue'])) || ($pageparams['countValue'] < 1) || ($pageparams['countValue'] > 1000)) {$errors = true; $pageparamserror['countValue'] = 'Должно быть указано число от 1 до 1000';}
            if ((!preg_match("/^\d+$/ui", $pageparams['pageValue'])) || ($pageparams['pageValue'] < 0)) {$errors = true; $pageparamserror['pageValue'] = 'Должно быть указано положительное число';}
            // Валидация фильтров
            $postfilterparams = $this->getRequest()->get('filterparams');
            $filterparams = array();
            if (is_array($postfilterparams))
            {
                foreach ($postfilterparams as $onefilter)
                {
                    if (isset($objectparameters[$onefilter['parameter']]) && isset($onefilter['compare']) && isset($onefilter['value']) && isset($onefilter['get']))
                    {
                        if (isset($onefilter['getoff'])) $getoff = intval($onefilter['getoff']); else $getoff = 0;
                        $filterparams[] = array('parameter'=>$onefilter['parameter'], 'compare'=>$onefilter['compare'], 'value'=>$onefilter['value'], 'get'=>$onefilter['get'], 'getoff'=>$getoff);
                    }
                }
            }
            unset($postfilterparams);
            foreach ($filterparams as &$onefilter)
            {
                if (isset($objectparameters[$onefilter['parameter']]['type']) && ($objectparameters[$onefilter['parameter']]['type'] == 'text'))
                {
                    if (($onefilter['compare'] != 'eq') && ($onefilter['compare'] != 'noeq') && ($onefilter['compare'] != 'like')) {$errors = true; $onefilter['compareerror'] = 'Неизвестный метод сравнения';}
                    if (!preg_match("/^([_A-z0-9]{5,})?$/ui", $onefilter['get'])) {$errors = true; $onefilter['geterror'] = 'Имя параметра должно состоять из не менее 5 букв, цифр или подчёркивания';}
                }
                if (isset($objectparameters[$onefilter['parameter']]['type']) && ($objectparameters[$onefilter['parameter']]['type'] == 'int'))
                {
                    if (($onefilter['compare'] != 'eq') && ($onefilter['compare'] != 'noeq') && ($onefilter['compare'] != 'hi') && ($onefilter['compare'] != 'lo') && ($onefilter['compare'] != 'hieq') && ($onefilter['compare'] != 'loeq')) {$errors = true; $onefilter['compareerror'] = 'Неизвестный метод сравнения';}
                    if (!preg_match("/^(\-)?(\d+)(\.(\d+))?$/ui", $onefilter['value'])) {$errors = true; $onefilter['valueerror'] = 'Должно быть указано число';}
                    if (!preg_match("/^([_A-z0-9]{5,})?$/ui", $onefilter['get'])) {$errors = true; $onefilter['geterror'] = 'Имя параметра должно состоять из не менее 5 букв, цифр или подчёркивания';}
                }
                if (isset($objectparameters[$onefilter['parameter']]['type']) && ($objectparameters[$onefilter['parameter']]['type'] == 'date'))
                {
                    if (($onefilter['compare'] != 'eq') && ($onefilter['compare'] != 'noeq') && ($onefilter['compare'] != 'hi') && ($onefilter['compare'] != 'lo') && ($onefilter['compare'] != 'hieq') && ($onefilter['compare'] != 'loeq')) {$errors = true; $onefilter['compareerror'] = 'Неизвестный метод сравнения';}
                    if ((!preg_match("/^(\d?)\d\.\d\d\.\d\d\d\d(( \d\d:\d\d)?)$/ui", $onefilter['value'])) && (!preg_match("/^\-(\d+)$/ui", $onefilter['value']))) {$errors = true; $onefilter['valueerror'] = 'Должна быть указана дата';}
                    if (!preg_match("/^([_A-z0-9]{5,})?$/ui", $onefilter['get'])) {$errors = true; $onefilter['geterror'] = 'Имя параметра должно состоять из не менее 5 букв, цифр или подчёркивания';}
                }
                if (isset($objectparameters[$onefilter['parameter']]['type']) && ($objectparameters[$onefilter['parameter']]['type'] == 'bool'))
                {
                    if (($onefilter['value'] != 1) && ($onefilter['value'] != 0)) {$errors = true; $onefilter['valueerror'] = 'Неизвестный метод сравнения';}
                    if (!preg_match("/^([_A-z0-9]{5,})?$/ui", $onefilter['get'])) {$errors = true; $onefilter['geterror'] = 'Имя параметра должно состоять из не менее 5 букв, цифр или подчёркивания';}
                }
            }
            $selcategories = array();
            $postselcategories = $this->getRequest()->get('selcategories');
            if (is_array($postselcategories)) 
            {
                foreach ($categories as $cat) if (in_array($cat['id'], $postselcategories)) $selcategories[] = $cat['id'];
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 4;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'taxonomyShowEdit', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                if (($showent->getAvatar() != '') && ($showent->getAvatar() != $tax['avatar'])) @unlink('..'.$showent->getAvatar());
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($tax['avatar']);
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($tax['description'], $showent->getDescription());
                $showent->setTitle($tax['title']);
                $showent->setDescription($tax['description']);
                $showent->setAvatar($tax['avatar']);
                $showent->setEnabled($tax['enabled']);
                $showent->setMetaDescription($tax['metadescr']);
                $showent->setMetaKeywords($tax['metakey']);
                $showent->setModifyDate(new \DateTime('now'));
                $showent->setPageParameters(serialize($pageparams));
                $showent->setFilterParameters(serialize($filterparams));
                $showent->setTaxonomyIds(implode(',', $selcategories));
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
                    $pageent->setDescription('Просмотр представления '.$showent->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.taxonomy');
                    $pageent->setContentId($showent->getId());
                    $pageent->setContentAction('viewshow');
                    $showent->setTemplate($page['template']);
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
                    $taxlocent = $this->getDoctrine()->getRepository('BasicCmsBundle:TaxonomyShowsLocale')->findOneBy(array('locale'=>$locale['shortName'],'showId'=>$id));
                    if (($taxloc[$locale['shortName']]['title'] != '') ||
                        ($taxloc[$locale['shortName']]['description'] != '') ||
                        ($taxloc[$locale['shortName']]['metadescr'] != '') ||
                        ($taxloc[$locale['shortName']]['metakey'] != ''))
                    {
                        if (empty($taxlocent))
                        {
                            $taxlocent = new \Basic\CmsBundle\Entity\TaxonomyShowsLocale();
                            $em->persist($taxlocent);
                        }
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($taxloc[$locale['shortName']]['description'], $taxlocent->getDescription());
                        $taxlocent->setShowId($showent->getId());
                        $taxlocent->setLocale($locale['shortName']);
                        if ($taxloc[$locale['shortName']]['title'] == '') $taxlocent->setTitle(null); else $taxlocent->setTitle($taxloc[$locale['shortName']]['title']);
                        if ($taxloc[$locale['shortName']]['description'] == '') $taxlocent->setDescription(null); else $taxlocent->setDescription($taxloc[$locale['shortName']]['description']);
                        if ($taxloc[$locale['shortName']]['metadescr'] == '') $taxlocent->setMetaDescription(null); else $taxlocent->setMetaDescription($taxloc[$locale['shortName']]['metadescr']);
                        if ($taxloc[$locale['shortName']]['metakey'] == '') $taxlocent->setMetaKeywords(null); else $taxlocent->setMetaKeywords($taxloc[$locale['shortName']]['metakey']);
                        $em->flush();
                    } else
                    {
                        if (!empty($taxlocent))
                        {
                            $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $taxlocent->getDescription());
                            $em->remove($taxlocent);
                            $em->flush();
                        }
                    }
                    unset($taxlocent);
                }
                $i = 0;
                foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'taxonomyShowEdit', $showent->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование представления',
                    'message'=>'Представление успешно изменено',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('basic_cms_taxonomyshow_edit').'?id='.$id, 'Вернуться к списку представлений'=>$this->get('router')->generate('basic_cms_taxonomy_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'taxonomyShowEdit', $id, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('BasicCmsBundle:Taxonomy:showEdit.html.twig', array(
            'id' => $id,
            'taxonomyobject' => $taxonomyobject,
            'tax' => $tax,
            'taxerror' => $taxerror,
            'page' => $page,
            'pageerror' => $pageerror,
            'taxloc' => $taxloc,
            'taxlocerror' => $taxlocerror,
            'locales' => $locales,
            'templates' => $templates,
            'modules' => $modules,
            'layouts' => $layouts,
            'objectparameters' => $objectparameters,
            'pageparams' => $pageparams,
            'pageparamserror' => $pageparamserror,
            'filterparams' => $filterparams,
            'categories' => $categories,
            'selcategories' => $selcategories,
            'roles' => $roles,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
    }
    
// *******************************************
// AJAX-контроллер
// *******************************************    
    
    public function taxonomyAjaxAction()
    {
        $tab = intval($this->getRequest()->get('tab'));
        $taxonomyobject = $this->getRequest()->get('object');
        if ($taxonomyobject === null) $taxonomyobject = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_object');
                                 else $this->getRequest()->getSession()->set('basic_cms_taxonomy_list_object', $taxonomyobject);
        $taxonomydescription = '';
        $taxonomyobjectsql = '';
        if ($taxonomyobject != '')
        {
            if ((strpos($taxonomyobject,'object.') === 0) && ($this->container->has($taxonomyobject)) && ($this->container->get($taxonomyobject)->getTaxonomyType() == true))
            {
                $taxonomydescription = $this->container->get($taxonomyobject)->getDescription();
                $taxonomyobjectsql = 'AND t.object = \''.$taxonomyobject.'\' ';
            } else
            {
                $taxonomyobject = '';
            }
        }
        
        if ((($tab == 0) && ($this->getUser()->checkAccess('taxonomy_list') == 0)) || (($tab != 0) && ($this->getUser()->checkAccess('taxonomy_listshow') == 0)))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Категории',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->getRequest()->get('action');
        $errors = array();
        if ($action == 'deletetaxonomy')
        {
            if ($this->getUser()->checkAccess('taxonomy_edit') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Категории',
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
                    $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.taxonomy\' AND p.contentAction = \'view\' AND p.contentId IN (SELECT t.id FROM BasicCmsBundle:Taxonomies t WHERE t.firstParent = :id)')->setParameter('id', $key);
                    $query->execute();
                    $query = $em->createQuery('DELETE FROM BasicCmsBundle:TaxonomiesLinks tl WHERE tl.taxonomyId IN (SELECT t.id FROM BasicCmsBundle:Taxonomies t WHERE t.firstParent = :id)')->setParameter('id', $key);
                    $query->execute();
                    $query = $em->createQuery('SELECT tl.description FROM BasicCmsBundle:TaxonomiesLocale tl WHERE tl.taxonomyId IN (SELECT t.id FROM BasicCmsBundle:Taxonomies t WHERE t.firstParent = :id)')->setParameter('id', $key);
                    $deltaxes = $query->getResult();
                    if (is_array($deltaxes))
                    {
                        foreach ($deltaxes as $deltax)
                        {
                            if ($deltax['description'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $deltax['description']);
                        }
                    }
                    $query = $em->createQuery('DELETE FROM BasicCmsBundle:TaxonomiesLocale tl WHERE tl.taxonomyId IN (SELECT t.id FROM BasicCmsBundle:Taxonomies t WHERE t.firstParent = :id)')->setParameter('id', $key);
                    $query->execute();
                    $query = $em->createQuery('SELECT t.avatar, t.description FROM BasicCmsBundle:Taxonomies t WHERE t.firstParent = :id')->setParameter('id', $key);
                    $deltaxes = $query->getResult();
                    if (is_array($deltaxes))
                    {
                        foreach ($deltaxes as $deltax)
                        {
                            if ($deltax['avatar'] != '') @unlink('..'.$deltax['avatar']);
                            if ($deltax['description'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $deltax['description']);
                        }
                    }
                    $query = $em->createQuery('DELETE FROM BasicCmsBundle:Taxonomies t WHERE t.firstParent = :id')->setParameter('id', $key);
                    $query->execute();
                    foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
                    {
                        $serv = $this->container->get($item);
                        $serv->getAdminController($this->getRequest(), 'taxonomyDelete', $key, 'save');
                    }       
                }
        }
        if ($action == 'deleteshow')
        {
            if ($this->getUser()->checkAccess('taxonomy_editshow') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Категории',
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
                    $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.taxonomy\' AND p.contentAction = \'viewshow\' AND p.contentId = :id')->setParameter('id', $key);
                    $query->execute();
                    $query = $em->createQuery('SELECT tl.description FROM BasicCmsBundle:TaxonomyShowsLocale tl WHERE tl.showId = :id')->setParameter('id', $key);
                    $deltaxes = $query->getResult();
                    if (is_array($deltaxes))
                    {
                        foreach ($deltaxes as $deltax)
                        {
                            if ($deltax['description'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $deltax['description']);
                        }
                    }
                    $query = $em->createQuery('DELETE FROM BasicCmsBundle:TaxonomyShowsLocale tl WHERE tl.showId = :id')->setParameter('id', $key);
                    $query->execute();
                    $query = $em->createQuery('SELECT t.avatar, t.description FROM BasicCmsBundle:TaxonomyShows t WHERE t.id = :id')->setParameter('id', $key);
                    $deltaxes = $query->getResult();
                    if (is_array($deltaxes))
                    {
                        foreach ($deltaxes as $deltax)
                        {
                            if ($deltax['avatar'] != '') @unlink('..'.$deltax['avatar']);
                            if ($deltax['description'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $deltax['description']);
                        }
                    }
                    $query = $em->createQuery('DELETE FROM BasicCmsBundle:TaxonomyShows t WHERE t.id = :id')->setParameter('id', $key);
                    $query->execute();
                    foreach ($cmsservices as $item) if (strpos($item,'addone.taxonomy.') === 0) 
                    {
                        $serv = $this->container->get($item);
                        $serv->getAdminController($this->getRequest(), 'taxonomyShowDelete', $key, 'save');
                    }       
                }
        }
        if ($action == 'blockedshow')
        {
            if ($this->getUser()->checkAccess('taxonomy_editshow') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Категории',
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
                    $showent = $this->getDoctrine()->getRepository('BasicCmsBundle:TaxonomyShows')->find($key);
                    if (!empty($showent))
                    {
                        $showent->setEnabled(0);
                        $em->flush();
                        unset($showent);    
                    }
                }
        }
        if ($action == 'unblockedshow')
        {
            if ($this->getUser()->checkAccess('taxonomy_editshow') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Категории',
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
                    $showent = $this->getDoctrine()->getRepository('BasicCmsBundle:TaxonomyShows')->find($key);
                    if (!empty($showent))
                    {
                        $showent->setEnabled(1);
                        $em->flush();
                        unset($showent);    
                    }
                }
        }

        
        if ($tab == 0)
        {
            // Таб 1
            $page0 = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_page0');
            $sort0 = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_sort0');
            $search0 = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_search0');
            $page0 = intval($page0);
            $sort0 = intval($sort0);
            $search0 = trim($search0);
            // Поиск контента для 1 таба (тексоны)
            $query = $em->createQuery('SELECT count(t.id) as taxcount FROM BasicCmsBundle:Taxonomies t '.
                                      'WHERE t.parent is null AND t.title like :search '.$taxonomyobjectsql)->setParameter('search', '%'.$search0.'%');
            $taxcount = $query->getResult();
            if (!empty($taxcount)) $taxcount = $taxcount[0]['taxcount']; else $taxcount = 0;
            $pagecount0 = ceil($taxcount / 20);
            if ($pagecount0 < 1) $pagecount0 = 1;
            if ($page0 < 0) $page0 = 0;
            if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
            $sortsql = 'ORDER BY t.title ASC';
            if ($sort0 == 1) $sortsql = 'ORDER BY t.title DESC';
            if ($sort0 == 2) $sortsql = 'ORDER BY t.object ASC';
            if ($sort0 == 3) $sortsql = 'ORDER BY t.object DESC';
            if ($sort0 == 4) $sortsql = 'ORDER BY t.createDate ASC';
            if ($sort0 == 5) $sortsql = 'ORDER BY t.createDate DESC';
            if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
            if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
            $start = $page0 * 20;
            $query = $em->createQuery('SELECT t.id, t.title, t.object, t.createDate, t.modifyDate, u.fullName FROM BasicCmsBundle:Taxonomies t '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.
                                      'WHERE t.parent is null AND t.title like :search '.$taxonomyobjectsql.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
            $taxons0 = $query->getResult();
            foreach ($taxons0 as &$taxon)
            {
                if ($this->container->has($taxon['object'])) $taxon['objectName'] = $this->container->get($taxon['object'])->getDescription(); else $taxon['objectName'] = '';
            }
            return $this->render('BasicCmsBundle:Taxonomy:taxonomyListTab1.html.twig', array(
                'taxonomyobject' => $taxonomyobject,
                'taxonomydescription' => $taxonomydescription,
                'taxons0' => $taxons0,
                'search0' => $search0,
                'page0' => $page0,
                'sort0' => $sort0,
                'pagecount0' => $pagecount0,
                'tab' => $tab,
                'errors0' => $errors
            ));
            
        }
        if ($tab == 1)
        {
            // Таб 2
            $page1 = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_page1');
            $sort1 = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_sort1');
            $search1 = $this->getRequest()->getSession()->get('basic_cms_taxonomy_list_search1');
            $page1 = intval($page1);
            $sort1 = intval($sort1);
            $search1 = trim($search1);
            // Поиск контента для 2 таба (текстовые страницы)
            $query = $em->createQuery('SELECT count(t.id) as showcount FROM BasicCmsBundle:TaxonomyShows t '.
                                      'WHERE t.title like :search '.$taxonomyobjectsql)->setParameter('search', '%'.$search1.'%');
            $showcount = $query->getResult();
            if (!empty($showcount)) $showcount = $showcount[0]['showcount']; else $showcount = 0;
            $pagecount1 = ceil($showcount / 20);
            if ($pagecount1 < 1) $pagecount1 = 1;
            if ($page1 < 0) $page1 = 0;
            if ($page1 >= $pagecount1) $page1 = $pagecount1 - 1;
            $sortsql = 'ORDER BY t.title ASC';
            if ($sort1 == 1) $sortsql = 'ORDER BY t.title DESC';
            if ($sort1 == 2) $sortsql = 'ORDER BY t.object ASC';
            if ($sort1 == 3) $sortsql = 'ORDER BY t.object DESC';
            if ($sort1 == 4) $sortsql = 'ORDER BY t.createDate ASC';
            if ($sort1 == 5) $sortsql = 'ORDER BY t.createDate DESC';
            if ($sort1 == 6) $sortsql = 'ORDER BY u.fullName ASC';
            if ($sort1 == 7) $sortsql = 'ORDER BY u.fullName DESC';
            $start = $page1 * 20;
            $query = $em->createQuery('SELECT t.id, t.title, t.enabled, t.object, t.createDate, t.modifyDate, u.fullName FROM BasicCmsBundle:TaxonomyShows t '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = t.createrId '.
                                      'WHERE t.title like :search '.$taxonomyobjectsql.$sortsql)->setParameter('search', '%'.$search1.'%')->setFirstResult($start)->setMaxResults(20);
            $shows1 = $query->getResult();
            foreach ($shows1 as &$show)
            {
                if ($this->container->has($show['object'])) $show['objectName'] = $this->container->get($show['object'])->getDescription(); else $show['objectName'] = '';
            }
            return $this->render('BasicCmsBundle:Taxonomy:taxonomyListTab2.html.twig', array(
                'taxonomyobject' => $taxonomyobject,
                'taxonomydescription' => $taxonomydescription,
                'shows1' => $shows1,
                'search1' => $search1,
                'page1' => $page1,
                'sort1' => $sort1,
                'pagecount1' => $pagecount1,
                'tab' => $tab,
                'errors1' => $errors
            ));
            
        }
    }
    
}
