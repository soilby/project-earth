<?php

namespace Extended\KnowledgeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Basic\CmsBundle\Entity\Users;
use Basic\CmsBundle\Entity\Roles;

class TypesController extends Controller
{
    
// *******************************************
// Редактирование классификации и AJAX-функции
// *******************************************    
    private function typesGetTree($tree, $id, $nesting, &$cats)
    {
        foreach ($tree as $cat)
        {
            if ($cat['parent'] == $id)
            {
                $cats[] = array('name' => $cat['title'], 'id' => $cat['id'], 'enabled' => $cat['enabled'], 'nesting' => $nesting, 'error' => '');
                $this->typesGetTree($tree, $cat['id'], $nesting + 1, $cats);
            }
        }
    }
    
    public function listAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ($this->getUser()->checkAccess('knowledge_typelist') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование дерева объектов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $categories = array();
        $query = $em->createQuery('SELECT t.id, t.title, t.enabled, t.parent FROM ExtendedKnowledgeBundle:KnowledgeType t '.
                                  'ORDER BY t.ordering');
        $tree = $query->getResult();
        $this->typesGetTree($tree, null, 0, $categories);
        $activetab = 0;
        $tabs = array();
        $errors = false;
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == 'POST')
        {
            $categoriespost = $this->get('request_stack')->getMasterRequest()->get('categories');
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
            foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'objectTypeEdit', null, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                $i++;
            }     
            //Сохранение
            if ($errors == false)
            {
                for ($i = 0; $i < count($categories); $i++)
                {
                    $parentid = null;
                    $j = $i - 1;
                    $ordering = 1;
                    while ($j >= 0)
                    {
                        if (($categories[$j]['nesting'] < $categories[$i]['nesting']) && isset($categories[$j]['id'])) {$parentid = $categories[$j]['id'];break;}
                        if ($categories[$j]['nesting'] == $categories[$i]['nesting']) $ordering++;
                        $j--;
                    }
                    $cattaxent = $em->getRepository('ExtendedKnowledgeBundle:KnowledgeType')->find($categories[$i]['id']);
                    if (!empty($cattaxent))
                    {
                        $cattaxent->setModifyDate(new \DateTime('now'));
                        $cattaxent->setOrdering($ordering);
                        $cattaxent->setParent($parentid);
                        $em->flush();
                        $seopageent = $em->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.knowledge', 'contentAction'=>'type','contentId'=>$categories[$i]['id']));
                        if (!empty($seopage)) $this->get('cms.cmsManager')->updateBreadCrumbs($seopageent->getId());
                        unset($seopageent);
                    }
                    unset($cattaxent);
                }
                // Другие табы
                $i = 0;
                foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'objectTypeEdit', null, 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 3;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование дерева объектов',
                    'message'=>'Иерархия дерева успешно отредактирована',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('extended_knowledge_typelist'))
                ));
                
            }
        }
        // Другие табы
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'objectTypeEdit', null, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('ExtendedKnowledgeBundle:Types:list.html.twig', array(
            'categories' => $categories,
            'tabs'  => $tabs,
            'activetab' => $activetab
        ));
    }
    
    public function ajaxDeleteAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $taxent = $this->getDoctrine()->getRepository('ExtendedKnowledgeBundle:KnowledgeType')->find($id);
        if (empty($taxent)) return new Response(json_encode(array('result'=>'error','message'=>'Категория не найдена')));
        if ($this->getUser()->checkAccess('knowledge_typelist') == 0) return new Response(json_encode(array('result'=>'error','message'=>'Доступ запрещён')));
        $categories = array();
        $query = $em->createQuery('SELECT t.id, t.title, t.enabled, t.parent FROM ExtendedKnowledgeBundle:KnowledgeType t '.
                                  'ORDER BY t.ordering');
        $tree = $query->getResult();
        $this->typesGetTree($tree, $id, 0, $categories);
        $ids = array($id);
        foreach ($categories as $category) $ids[] = $category['id'];
        $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.knowledge\' AND p.contentAction = \'type\' AND p.contentId IN (:ids)')->setParameter('ids', $ids);
        $query->execute();
        $query = $em->createQuery('DELETE FROM ExtendedKnowledgeBundle:KnowledgeTypeLink tl WHERE tl.typeId IN (:ids)')->setParameter('ids', $ids);
        $query->execute();
        $query = $em->createQuery('SELECT tl.description FROM ExtendedKnowledgeBundle:KnowledgeTypeLocale tl WHERE tl.typeId IN (:ids)')->setParameter('ids', $ids);
        $deltaxes = $query->getResult();
        if (is_array($deltaxes))
        {
            foreach ($deltaxes as $deltax)
            {
                if ($deltax['description'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $deltax['description']);
            }
        }
        $query = $em->createQuery('DELETE FROM ExtendedKnowledgeBundle:KnowledgeTypeLocale tl WHERE tl.typeId IN (:ids)')->setParameter('ids', $ids);
        $query->execute();
        $query = $em->createQuery('SELECT t.avatar, t.description FROM ExtendedKnowledgeBundle:KnowledgeType t WHERE t.id IN (:ids)')->setParameter('ids', $ids);
        $deltaxes = $query->getResult();
        if (is_array($deltaxes))
        {
            foreach ($deltaxes as $deltax)
            {
                if ($deltax['avatar'] != '') @unlink('.'.$deltax['avatar']);
                if ($deltax['description'] != '') $this->container->get('cms.cmsManager')->unlockTemporaryEditor('', $deltax['description']);
            }
        }
        $query = $em->createQuery('DELETE FROM ExtendedKnowledgeBundle:KnowledgeType t WHERE t.id IN (:ids)')->setParameter('ids', $ids);
        $query->execute();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) 
        {
            $serv = $this->container->get($item);
            $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'tobjectTypeEditDelete', $id, 'save');
        }       
        return new Response(json_encode(array('result'=>'OK','message'=>'')));
    }

    public function ajaxAddAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $name = $this->get('request_stack')->getMasterRequest()->get('name');
        if ($this->getUser()->checkAccess('knowledge_typelist') == 0) return new Response(json_encode(array('result'=>'error','message'=>'Доступ запрещён')));
        if (!preg_match("/^.{3,}$/ui", $name)) return new Response(json_encode(array('result'=>'error','message'=>'Заголовок должен содержать более 3 символов')));
        $query = $em->createQuery('SELECT MAX(t.ordering) as maxordering FROM ExtendedKnowledgeBundle:KnowledgeType t');
        $ordering = $query->getResult();
        if (isset($ordering[0]['maxordering'])) $ordering = $ordering[0]['maxordering'] + 1; else $ordering = 1;
        $query = $em->createQuery('SELECT t.id, t.template, sp.locale, sp.modules, sp.template as layout, sp.access, sp.id as seoPageId, t.parent FROM ExtendedKnowledgeBundle:KnowledgeType t '.
                                  'LEFT JOIN BasicCmsBundle:SeoPage sp WITH sp.contentType = \'object.knowledge\' AND sp.contentAction = \'type\' AND sp.contentId = t.id ');
        $defparameterssql = $query->getResult();
        $defparameters = null;
        if (is_array($defparameterssql))
        {
            $defparametersmain = null;
            $defparametersord = null;
            $defparameterssame = true;
            foreach ($defparameterssql as $defparam)
            {
                if (($defparam['parent'] == null) && ($defparametersmain == null)) $defparametersmain = $defparam;
                if ($defparam['parent'] != null) 
                {
                    if ($defparametersord == null) $defparametersord = $defparam; else
                    {
                        if (($defparametersord['template'] != $defparametersord['template']) ||
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
        $cattaxent = new \Extended\KnowledgeBundle\Entity\KnowledgeType();
        $cattaxent->setAvatar('');
        $cattaxent->setCreateDate(new \DateTime('now'));
        $cattaxent->setCreaterId($this->getUser()->getId());
        $cattaxent->setDescription('');
        $cattaxent->setEnabled(1);
        $cattaxent->setMetaDescription('');
        $cattaxent->setMetaKeywords('');
        $cattaxent->setModifyDate(new \DateTime('now'));
        $cattaxent->setOrdering($ordering);
        $cattaxent->setTemplate('');
        $cattaxent->setTitle($name);
        $cattaxent->setParent(null);
        if ($defparameters != null)
        {
            $cattaxent->setTemplate($defparameters['template']);
        }
        $em->persist($cattaxent);
        $em->flush();
        if (($defparameters != null) && ($defparameters['seoPageId'] != null))
        {
            $pageent = new \Basic\CmsBundle\Entity\SeoPage();
            $pageent->setBreadCrumbs('');
            $pageent->setBreadCrumbsRel('');
            $pageent->setDescription('Просмотр типа базы знаний '.$cattaxent->getTitle());
            $pageent->setUrl('');
            $pageent->setTemplate($defparameters['layout']);
            $pageent->setModules($defparameters['modules']);
            $pageent->setLocale($defparameters['locale']);
            $pageent->setContentType('object.knowledge');
            $pageent->setContentId($cattaxent->getId());
            $pageent->setContentAction('type');
            $pageent->setAccess($defparameters['access']);
            $em->persist($pageent);
            $em->flush();
            $pageent->setUrl($pageent->getId());
            $em->flush();
        }
        return new Response(json_encode(array('result'=>'OK','message'=>'','id'=>$cattaxent->getId())));
    }
    

    public function ajaxSaveAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ($this->getUser()->checkAccess('knowledge_typelist') == 0) return new Response(json_encode(array('result'=>'error','message'=>'Доступ запрещён')));

        $query = $em->createQuery('SELECT t.id, t.title, t.enabled, t.parent FROM ExtendedKnowledgeBundle:KnowledgeType t '.
                                  'ORDER BY t.ordering');
        $tree = $query->getResult();
        $errors = false;
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == 'POST')
        {
            $categoriespost = $this->get('request_stack')->getMasterRequest()->get('categories');
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
            foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'objectTypeEdit', null, 'validate');
                if ($localerror == true) $errors = true;
                $i++;
            }     
            //Сохранение
            if ($errors == false)
            {
                for ($i = 0; $i < count($categories); $i++)
                {
                    $parentid = null;
                    $j = $i - 1;
                    $ordering = 1;
                    while ($j >= 0)
                    {
                        if (($categories[$j]['nesting'] < $categories[$i]['nesting']) && isset($categories[$j]['id'])) {$parentid = $categories[$j]['id'];break;}
                        if ($categories[$j]['nesting'] == $categories[$i]['nesting']) $ordering++;
                        $j--;
                    }
                    $cattaxent = $em->getRepository('ExtendedKnowledgeBundle:KnowledgeType')->find($categories[$i]['id']);
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
                foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'objectTypeEdit', null, 'save');
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

    public function editAjaxAvatarAction() 
    {
        $userId = $this->getUser()->getId();
        $file = $this->get('request_stack')->getMasterRequest()->files->get('avatar');
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
            $basepath = '/images/knowledge/';
            $name = $this->getUser()->getId().'_'.md5($tmpfile.time()).'.'.$imageTypeArray[$params[2]];
            if (move_uploaded_file($tmpfile, '.'.$basepath.$name)) 
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
    public function editAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $taxonomyent = $this->getDoctrine()->getRepository('ExtendedKnowledgeBundle:KnowledgeType')->find($id);
        if (empty($taxonomyent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование дерева объекта',
                'message'=>'Тип объектов не найден', 
                'error'=>true,
                'paths'=>array('Вернуться к базе знаний'=>$this->get('router')->generate('extended_knowledge_list'))
            ));
        }
        if ($this->getUser()->checkAccess('knowledge_typelist') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование дерева объекта',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.knowledge','contentId'=>$id,'contentAction'=>'type'));
        
        $tax = array();
        $texterror = array();
        
        $tax['title'] = $taxonomyent->getTitle();
        $tax['description'] = $taxonomyent->getDescription();
        $tax['avatar'] = $taxonomyent->getAvatar();
        $tax['enabled'] = $taxonomyent->getEnabled();
        $tax['metadescr'] = $taxonomyent->getMetaDescription();
        $tax['metakey'] = $taxonomyent->getMetaKeywords();
        $taxerror['title'] = '';
        $taxerror['description'] = '';
        $taxerror['avatar'] = '';
        $taxerror['enabled'] = '';
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
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.knowledge','type');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.knowledge.type');
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
            $taxlocent = $this->getDoctrine()->getRepository('ExtendedKnowledgeBundle:KnowledgeTypeLocale')->findOneBy(array('locale'=>$locale['shortName'],'typeId'=>$id));
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
        
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == "POST")
        {
            $changeCategoryChilds = intval($this->get('request_stack')->getMasterRequest()->get('changecategorychilds'));
            // Проверка основных данных
            $posttax = $this->get('request_stack')->getMasterRequest()->get('tax');
            if (isset($posttax['title'])) $tax['title'] = $posttax['title'];
            if (isset($posttax['description'])) $tax['description'] = $posttax['description'];
            if (isset($posttax['avatar'])) $tax['avatar'] = $posttax['avatar'];
            if (isset($posttax['enabled'])) $tax['enabled'] = intval($posttax['enabled']); else $tax['enabled'] = 0;
            if (isset($posttax['metadescr'])) $tax['metadescr'] = $posttax['metadescr'];
            if (isset($posttax['metakey'])) $tax['metakey'] = $posttax['metakey'];
            unset($posttax);
            if (!preg_match("/^.{3,}$/ui", $tax['title'])) {$errors = true; $taxerror['title'] = 'Заголовок должен содержать более 3 символов';}
            if (($tax['avatar'] != '') && (!file_exists('.'.$tax['avatar']))) {$errors = true; $taxerror['avatar'] = 'Файл не найден';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $tax['metadescr'])) {$errors = true; $taxerror['metadescr'] = 'Использованы недопустимые символы';}
            if (!preg_match("/^[0-9A-zА-яЁё\s\,\.\-\`\!\@\#\$\%\^\&\*\(\)\_\+\=\?\/\\\:\;]*$/ui", $tax['metakey'])) {$errors = true; $taxerror['metakey'] = 'Использованы недопустимые символы';}
            if (($errors == true) && ($activetab == 0)) $activetab = 1;
            // Проверка данных о странице
            $postpage = $this->get('request_stack')->getMasterRequest()->get('page');
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
            $posttaxloc = $this->get('request_stack')->getMasterRequest()->get('taxloc');
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
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'objectTypeEdit', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 5;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                if (($taxonomyent->getAvatar() != '') && ($taxonomyent->getAvatar() != $tax['avatar'])) @unlink('.'.$taxonomyent->getAvatar());
                $this->container->get('cms.cmsManager')->unlockTemporaryFile($tax['avatar']);
                $this->container->get('cms.cmsManager')->unlockTemporaryEditor($tax['description'], $taxonomyent->getDescription());
                $taxonomyent->setTitle($tax['title']);
                $taxonomyent->setDescription($tax['description']);
                $taxonomyent->setAvatar($tax['avatar']);
                $taxonomyent->setEnabled($tax['enabled']);
                $taxonomyent->setMetaDescription($tax['metadescr']);
                $taxonomyent->setMetaKeywords($tax['metakey']);
                $taxonomyent->setModifyDate(new \DateTime('now'));
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
                    $pageent->setDescription('Просмотр типа базы знаний '.$taxonomyent->getTitle());
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.knowledge');
                    $pageent->setContentId($taxonomyent->getId());
                    $pageent->setContentAction('type');
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
                    $taxlocent = $this->getDoctrine()->getRepository('ExtendedKnowledgeBundle:KnowledgeTypeLocale')->findOneBy(array('locale'=>$locale['shortName'],'typeId'=>$id));
                    if (($taxloc[$locale['shortName']]['title'] != '') ||
                        ($taxloc[$locale['shortName']]['description'] != '') ||
                        ($taxloc[$locale['shortName']]['metadescr'] != '') ||
                        ($taxloc[$locale['shortName']]['metakey'] != ''))
                    {
                        if (empty($taxlocent))
                        {
                            $taxlocent = new \Extended\KnowledgeBundle\Entity\KnowledgeTypeLocale();
                            $em->persist($taxlocent);
                        }
                        $this->container->get('cms.cmsManager')->unlockTemporaryEditor($taxloc[$locale['shortName']]['description'], $taxlocent->getDescription());
                        $taxlocent->setTypeId($taxonomyent->getId());
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
                foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'objectTypeEdit', $taxonomyent->getId(), 'save');
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
                    $query = $em->createQuery('SELECT t.id, t.parent FROM ExtendedKnowledgeBundle:KnowledgeType t '.
                                              'ORDER BY t.ordering');
                    $tree = $query->getResult();
                    do
                    {
                        $count = 0;
                        foreach ($tree as $cat) if ((in_array($cat['parent'], $categoryIds)) && (!in_array($cat['id'], $categoryIds))) {$categoryIds[] = $cat['id'];$count++;}
                    } while ($count > 0);
                    // Изменяем категории
                    foreach ($categoryIds as $categoryId) if ($categoryId != $id)
                    {
                        $taxonomychildent = $this->getDoctrine()->getRepository('ExtendedKnowledgeBundle:KnowledgeType')->find($categoryId);
                        $pagechildent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.knowledge','contentId'=>$categoryId,'contentAction'=>'type'));
                        if (!empty($taxonomychildent))
                        {
                            $taxonomychildent->setModifyDate(new \DateTime('now'));
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
                                $pagechildent->setDescription('Просмотр типа базы знаний '.$taxonomychildent->getTitle());
                                $pagechildent->setUrl($pageurl);
                                $pagechildent->setTemplate($page['layout']);
                                $pagechildent->setModules(implode(',', $page['modules']));
                                $pagechildent->setLocale($page['locale']);
                                $pagechildent->setContentType('object.knowledge');
                                $pagechildent->setContentId($taxonomychildent->getId());
                                $pagechildent->setContentAction('type');
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
                            foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) 
                            {
                                $serv = $this->container->get($item);
                                $localerror = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'objectTypeEdit', $taxonomychildent->getId(), 'saveChilds');
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
                        'title'=>'Редактирование дерева объекта',
                        'message'=>'Тип и его потомки ('.$childsCount.') успешно изменены',
                        'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('extended_knowledge_typelist_edit').'?id='.$id, 'Вернуться к базе знений'=>$this->get('router')->generate('extended_knowledge_typelist'))
                    ));
                }
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование дерева объекта',
                    'message'=>'Тип успешно изменен',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('extended_knowledge_typelist_edit').'?id='.$id, 'Вернуться к базе знений'=>$this->get('router')->generate('extended_knowledge_typelist'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.knowledge.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->get('request_stack')->getMasterRequest(), 'objectTypeEdit', $id, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('ExtendedKnowledgeBundle:Types:edit.html.twig', array(
            'id' => $id,
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
            'roles' => $roles,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
    }
}
