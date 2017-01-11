<?php

namespace Form\FormBundle\Controller;

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
// Список форм
// *******************************************    
    public function formListAction()
    {
        if (($this->getUser()->checkAccess('form_list') == 0) && ($this->getUser()->checkAccess('form_letterlist') == 0))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Список форм',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $tab = $this->getRequest()->get('tab');
        if ($tab === null) $tab = $this->getRequest()->getSession()->get('form_form_list_tab');
                      else $this->getRequest()->getSession()->set('form_form_list_tab', $tab);
        if ($tab < 0) $tab = 0;
        if ($tab > 1) $tab = 1;
        if ($this->getUser()->checkAccess('form_letterlist') == 0) $tab = 1;
        if ($this->getUser()->checkAccess('form_list') == 0) $tab = 0;
        // Таб 1
        $page0 = $this->getRequest()->get('page0');
        $sort0 = $this->getRequest()->get('sort0');
        $search0 = $this->getRequest()->get('search0');
        if ($page0 === null) $page0 = $this->getRequest()->getSession()->get('form_form_list_page0');
                        else $this->getRequest()->getSession()->set('form_form_list_page0', $page0);
        if ($sort0 === null) $sort0 = $this->getRequest()->getSession()->get('form_form_list_sort0');
                        else $this->getRequest()->getSession()->set('form_form_list_sort0', $sort0);
        if ($search0 === null) $search0 = $this->getRequest()->getSession()->get('form_form_list_search0');
                          else $this->getRequest()->getSession()->set('form_form_list_search0', $search0);
        $page0 = intval($page0);
        $sort0 = intval($sort0);
        $search0 = trim($search0);
        // Поиск контента для 1 таба (письма)
        $query = $em->createQuery('SELECT count(l.id) as lettercount FROM FormFormBundle:FormLetters l '.
                                  'LEFT JOIN FormFormBundle:Forms f WITH f.id = l.formId '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = l.viewUserId '.
                                  'WHERE u.fullName like :search OR f.title like :search')->setParameter('search', '%'.$search0.'%');
        $lettercount = $query->getResult();
        if (!empty($lettercount)) $lettercount = $lettercount[0]['lettercount']; else $lettercount = 0;
        $pagecount0 = ceil($lettercount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $sortsql = 'ORDER BY l.createDate DESC';
        if ($sort0 == 1) $sortsql = 'ORDER BY l.createDate ASC';
        if ($sort0 == 2) $sortsql = 'ORDER BY l.formId ASC';
        if ($sort0 == 3) $sortsql = 'ORDER BY l.formId DESC';
        if ($sort0 == 4) $sortsql = 'ORDER BY l.viewed ASC';
        if ($sort0 == 5) $sortsql = 'ORDER BY l.viewed DESC';
        if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT l.id, l.viewed, u.fullName, l.createDate, l.viewDate, l.formId, f.title FROM FormFormBundle:FormLetters l '.
                                  'LEFT JOIN FormFormBundle:Forms f WITH f.id = l.formId '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = l.viewUserId '.
                                  'WHERE u.fullName like :search OR f.title like :search '.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $letters0 = $query->getResult();
        foreach ($letters0 as &$lett) $lett['title'] = $this->get('cms.cmsManager')->decodeLocalString($lett['title'], 'default');
        // Таб 2
        $page1 = $this->getRequest()->get('page1');
        $sort1 = $this->getRequest()->get('sort1');
        $search1 = $this->getRequest()->get('search1');
        if ($page1 === null) $page1 = $this->getRequest()->getSession()->get('form_form_list_page1');
                        else $this->getRequest()->getSession()->set('form_form_list_page1', $page1);
        if ($sort1 === null) $sort1 = $this->getRequest()->getSession()->get('form_form_list_sort1');
                        else $this->getRequest()->getSession()->set('form_form_list_sort1', $sort1);
        if ($search1 === null) $search1 = $this->getRequest()->getSession()->get('form_form_list_search1');
                          else $this->getRequest()->getSession()->set('form_form_list_search1', $search1);
        $page1 = intval($page1);
        $sort1 = intval($sort1);
        $search1 = trim($search1);
        // Поиск контента для 2 таба (формы)
        $query = $em->createQuery('SELECT count(f.id) as formcount FROM FormFormBundle:Forms f '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.
                                  'WHERE u.fullName like :search OR f.title like :search')->setParameter('search', '%'.$search1.'%');
        $formcount = $query->getResult();
        if (!empty($formcount)) $formcount = $formcount[0]['formcount']; else $formcount = 0;
        $pagecount1 = ceil($formcount / 20);
        if ($pagecount1 < 1) $pagecount1 = 1;
        if ($page1 < 0) $page1 = 0;
        if ($page1 >= $pagecount1) $page1 = $pagecount1 - 1;
        $sortsql = 'ORDER BY f.title ASC';
        if ($sort1 == 1) $sortsql = 'ORDER BY f.title DESC';
        if ($sort1 == 2) $sortsql = 'ORDER BY f.enabled ASC';
        if ($sort1 == 3) $sortsql = 'ORDER BY f.enabled DESC';
        if ($sort1 == 4) $sortsql = 'ORDER BY f.email ASC';
        if ($sort1 == 5) $sortsql = 'ORDER BY f.email DESC';
        if ($sort1 == 6) $sortsql = 'ORDER BY u.fullName ASC';
        if ($sort1 == 7) $sortsql = 'ORDER BY u.fullName DESC';
        $start = $page1 * 20;
        $query = $em->createQuery('SELECT f.id, f.title, f.email, f.sendEmail, f.enabled, u.fullName FROM FormFormBundle:Forms f '.
                                  'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.
                                  'WHERE u.fullName like :search OR f.title like :search '.$sortsql)->setParameter('search', '%'.$search1.'%')->setFirstResult($start)->setMaxResults(20);
        $forms1 = $query->getResult();
        foreach ($forms1 as &$formitem) $formitem['title'] = $this->get('cms.cmsManager')->decodeLocalString($formitem['title'], 'default');
        return $this->render('FormFormBundle:Default:formList.html.twig', array(
            'pagecount0' => $pagecount0,
            'search0' => $search0,
            'page0' => $page0,
            'sort0' => $sort0,
            'letters0' => $letters0,
            'pagecount1' => $pagecount1,
            'search1' => $search1,
            'page1' => $page1,
            'sort1' => $sort1,
            'forms1' => $forms1,
            'tab' => $tab
        ));
        
    }
    
// *******************************************
// Создание новой формы
// *******************************************    
    public function formCreateAction()
    {
        $this->getRequest()->getSession()->set('form_form_list_tab', 1);
        if ($this->getUser()->checkAccess('form_new') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание новой формы',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $form = array();
        $formerror = array();
        
        $form['title'] = array();
        $form['enabled'] = 1;
        $form['sendEmail'] = '';
        $form['email'] = '';
        $form['captchaEnabled'] = '';
        $formerror['title'] = array();
        $formerror['enabled'] = '';
        $formerror['sendEmail'] = '';
        $formerror['email'] = '';
        $formerror['captchaEnabled'] = '';
        $form['title']['default'] = '';
        $formerror['title']['default'] = '';
        foreach ($locales as $locale)
        {
            $form['title'][$locale['shortName']] = '';
            $formerror['title'][$locale['shortName']] = '';
        }
        
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
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.form','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        if (count($templates) > 0) {reset($templates); $page['template'] = key($templates);}
        if (count($layouts) > 0) {reset($layouts); $page['layout'] = key($layouts);}
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.form.view');
        $modules = $query->getResult();
        foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        
        $formfields = array();
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postform = $this->getRequest()->get('form');
            if (isset($postform['title']['default'])) $form['title']['default'] = $postform['title']['default'];
            foreach ($locales as $locale)
            {
                if (isset($postform['title'][$locale['shortName']])) $form['title'][$locale['shortName']] = $postform['title'][$locale['shortName']];
            }            
            if (isset($postform['enabled'])) $form['enabled'] = intval($postform['enabled']); else $form['enabled'] = 0;
            if (isset($postform['sendEmail'])) $form['sendEmail'] = intval($postform['sendEmail']); else $form['sendEmail'] = 0;
            if (isset($postform['email'])) $form['email'] = $postform['email'];
            if (isset($postform['captchaEnabled'])) $form['captchaEnabled'] = intval($postform['captchaEnabled']); else $form['captchaEnabled'] = 0;
            unset($postform);
            if (!preg_match("/^.{3,255}$/ui", $form['title']['default'])) {$errors = true; $formerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale)
            {
                if (!preg_match("/^(.{3,255})?$/ui", $form['title'][$locale['shortName']])) {$errors = true; $formerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов либо отсутствовать';}
            }            
            if (($form['sendEmail'] != 0) && (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,24})$/ui", $form['email']))) {$errors = true; $formerror['email'] = 'Введен некорректный E-Mail';}
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
            $postformfields = $this->getRequest()->get('formfields');
            if (is_array($postformfields))
            {
                foreach ($postformfields as $field)
                {
                    $allfields = true;
                    $newfield = array();
                    if (isset($field['enabled'])) $newfield['enabled'] = intval($field['enabled']); else $allfields = false;
                    if (isset($field['required'])) $newfield['required'] = intval($field['required']); else $allfields = false;
                    if (isset($field['type'])) $newfield['type'] = $field['type']; else $allfields = false;
                    if (isset($field['ordering'])) $newfield['ordering'] = intval($field['ordering']); else $allfields = false;
                    if (isset($field['nesting'])) $newfield['nesting'] = intval($field['nesting']); else $allfields = false;
                    if (isset($field['name']['default'])) $newfield['name']['default'] = $field['name']['default']; else $allfields = false;
                    if (isset($field['parameter']['default'])) $newfield['parameter']['default'] = $field['parameter']['default']; else $allfields = false;
                    foreach ($locales as $locale)
                    {
                        if (isset($field['name'][$locale['shortName']])) $newfield['name'][$locale['shortName']] = $field['name'][$locale['shortName']]; else $allfields = false;
                        if (isset($field['parameter'][$locale['shortName']])) $newfield['parameter'][$locale['shortName']] = $field['parameter'][$locale['shortName']]; else $allfields = false;
                    }                    
                    if ($allfields == true) 
                    {
                        if (!preg_match("/^.{3,255}$/ui", $newfield['name']['default'])) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Заголовок должен содержать более 3 символов';}
                        foreach ($locales as $locale)
                        {
                            if (!preg_match("/^(.{3,255})?$/ui", $newfield['name'][$locale['shortName']])) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Заголовок должен содержать более 3 символов';}
                        }
                        if (($newfield['type'] == 'text') || ($newfield['type'] == 'textarea'))
                        {
                            if (!preg_match("/^.{1,1000}$/ui", $newfield['parameter']['default'])) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Регулярное выражение должно содержать от 1 до 1000 символов';}
                            elseif (@preg_match('/^'.$newfield['parameter']['default'].'$/ui','') === false) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Регулярное выражение некорректно';} 
                            foreach ($locales as $locale)
                            {
                                if (!preg_match("/^(.{1,1000})?$/ui", $newfield['parameter'][$locale['shortName']])) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Регулярное выражение должно содержать от 1 до 1000 символов';}
                                elseif (($newfield['parameter'][$locale['shortName']] != '') && (@preg_match('/^'.$newfield['parameter'][$locale['shortName']].'$/ui','') === false)) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Регулярное выражение некорректно';} 
                            }
                        } else
                        if (($newfield['type'] == 'radio') || ($newfield['type'] == 'select'))
                        {
                            if (!preg_match("/^([- _A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\,]{3,250}([\r\n]+)?)+$/ui", $newfield['parameter']['default'])) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Ошибка в поле пунктов';}
                            foreach ($locales as $locale)
                            {
                                if (!preg_match("/^(([- _A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\,]{3,250}([\r\n]+)?)+)?$/ui", $newfield['parameter'][$locale['shortName']])) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Ошибка в поле пунктов';}
                            }
                        } else if (($newfield['type'] != 'checkbox') && ($newfield['type'] != 'group')) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Неизвестный тип';}
                        $formfields[$newfield['ordering']] = $newfield;
                    }
                }
                ksort($formfields);
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'formCreate', 0, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $forment = new \Form\FormBundle\Entity\Forms();
                $forment->setCreaterId($this->getUser()->getId());
                $forment->setTitle($this->get('cms.cmsManager')->encodeLocalString($form['title']));
                $forment->setEmail($form['email']);
                $forment->setEnabled($form['enabled']);
                $forment->setSendEmail($form['sendEmail']);
                $forment->setCaptchaEnabled($form['captchaEnabled']);
                $em->persist($forment);
                $em->flush();
                if ($page['enable'] != 0)
                {
                    $pageent = new \Basic\CmsBundle\Entity\SeoPage();
                    $pageent->setBreadCrumbs('');
                    $pageent->setBreadCrumbsRel('');
                    $pageent->setDescription('Просмотр формы '.$this->get('cms.cmsManager')->decodeLocalString($forment->getTitle(),'default',false));
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.form');
                    $pageent->setContentId($forment->getId());
                    $pageent->setContentAction('view');
                    $forment->setTemplate($page['template']);
                    if ($page['accessOn'] != 0) $pageent->setAccess(implode(',', $page['access'])); else $pageent->setAccess('');
                    $em->persist($pageent);
                    $em->flush();
                    if ($page['url'] == '') {$pageent->setUrl($pageent->getId());$em->flush();}
                }
                foreach ($formfields as $field)
                {
                    $fieldent = new \Form\FormBundle\Entity\FormFields();
                    $fieldent->setEnabled($field['enabled']);
                    $fieldent->setRequired($field['required']);
                    $fieldent->setFormId($forment->getId());
                    $fieldent->setName($this->get('cms.cmsManager')->encodeLocalString($field['name']));
                    $fieldent->setOrdering($field['ordering']);
                    $fieldent->setNesting($field['nesting']);
                    $fieldent->setParameters($this->get('cms.cmsManager')->encodeLocalString($field['parameter']));
                    $fieldent->setType($field['type']);
                    $em->persist($fieldent);
                    $em->flush();
                    unset($fieldent);
                }
                $i = 0;
                foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'formCreate', $forment->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание новой формы',
                    'message'=>'Форма успешно создана',
                    'paths'=>array('Создать еще одну форму'=>$this->get('router')->generate('form_form_create'),'Вернуться к списку форм'=>$this->get('router')->generate('form_form_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'formCreate', 0, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('FormFormBundle:Default:formCreate.html.twig', array(
            'form' => $form,
            'formerror' => $formerror,
            'page' => $page,
            'pageerror' => $pageerror,
            'formfields' => $formfields,
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
// Редактирование формы
// *******************************************    
    public function formEditAction()
    {
        $this->getRequest()->getSession()->set('form_form_list_tab', 1);
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $forment = $this->getDoctrine()->getRepository('FormFormBundle:Forms')->find($id);
        if (empty($forment))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование формы',
                'message'=>'Форма не найдена', 
                'error'=>true,
                'paths'=>array('Вернуться к списку форм'=>$this->get('router')->generate('form_form_list'))
            ));
        }
        if (($this->getUser()->checkAccess('form_viewall') == 0) && ($this->getUser()->getId() != $forment->getCreaterId()))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование формы',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        if (($this->getUser()->checkAccess('form_viewown') == 0) && ($this->getUser()->getId() == $forment->getCreaterId()))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование формы',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>'object.form','contentId'=>$id,'contentAction'=>'view'));
        
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        
        $form = array();
        $formerror = array();
        
        $form['title'] = array();
        $form['enabled'] = $forment->getEnabled();
        $form['sendEmail'] = $forment->getSendEmail();
        $form['email'] = $forment->getEmail();
        $form['captchaEnabled'] = $forment->getCaptchaEnabled();
        $formerror['title'] = array();
        $formerror['enabled'] = '';
        $formerror['sendEmail'] = '';
        $formerror['email'] = '';
        $formerror['captchaEnabled'] = '';
        $form['title']['default'] = $this->get('cms.cmsManager')->decodeLocalString($forment->getTitle(),'default',false);
        $formerror['title']['default'] = '';
        foreach ($locales as $locale)
        {
            $form['title'][$locale['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($forment->getTitle(),$locale['shortName'],false);
            $formerror['title'][$locale['shortName']] = '';
        }
        
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
            $page['template'] = $forment->getTemplate();
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
        
        $templates = $this->get('cms.cmsManager')->getTemplateList('object.form','view');
        $layouts = $this->get('cms.cmsManager')->getLayoutList();
        $query = $em->createQuery('SELECT m.id, m.name, LOCATE(:moduleid,CONCAT(\',\',CONCAT(m.autoEnable,\',\'))) as moduleDefOn FROM BasicCmsBundle:Modules m')->setParameter('moduleid','object.form.view');
        $modules = $query->getResult();
        if (empty($pageent))
        {
            foreach ($modules as $module) if ($module['moduleDefOn'] != 0) $page['modules'][] = $module['id'];
        }
        
        $query = $em->createQuery('SELECT f.id, f.name, f.type, f.enabled, f.ordering, f.parameters as parameter, f.nesting, f.required FROM FormFormBundle:FormFields f WHERE f.formId = :id ORDER BY f.ordering')->setParameter('id', $id);
        $formfields = $query->getResult();
        if (!is_array($formfields)) $formfields = array();
        foreach ($formfields as &$fieldtmp)
        {
            $nametmp = $fieldtmp['name'];
            $parametertmp = $fieldtmp['parameter'];
            $fieldtmp['name'] = array();
            $fieldtmp['parameter'] = array();
            $fieldtmp['name']['default'] = $this->get('cms.cmsManager')->decodeLocalString($nametmp,'default',false);
            $fieldtmp['parameter']['default'] = $this->get('cms.cmsManager')->decodeLocalString($parametertmp,'default',false);
            foreach ($locales as $locale)
            {
                $fieldtmp['name'][$locale['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($nametmp,$locale['shortName'],false);
                $fieldtmp['parameter'][$locale['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($parametertmp,$locale['shortName'],false);
            }
        }
        // Валидация
        $activetab = 0;
        $errors = false;
        $tabs = array();
        if ($this->getRequest()->getMethod() == "POST")
        {
            // Проверка основных данных
            $postform = $this->getRequest()->get('form');
            if (isset($postform['title']['default'])) $form['title']['default'] = $postform['title']['default'];
            foreach ($locales as $locale)
            {
                if (isset($postform['title'][$locale['shortName']])) $form['title'][$locale['shortName']] = $postform['title'][$locale['shortName']];
            }            
            if (isset($postform['enabled'])) $form['enabled'] = intval($postform['enabled']); else $form['enabled'] = 0;
            if (isset($postform['sendEmail'])) $form['sendEmail'] = intval($postform['sendEmail']); else $form['sendEmail'] = 0;
            if (isset($postform['email'])) $form['email'] = $postform['email'];
            if (isset($postform['captchaEnabled'])) $form['captchaEnabled'] = intval($postform['captchaEnabled']); else $form['captchaEnabled'] = 0;
            unset($postform);
            if (!preg_match("/^.{3,255}$/ui", $form['title']['default'])) {$errors = true; $formerror['title']['default'] = 'Заголовок должен содержать более 3 символов';}
            foreach ($locales as $locale)
            {
                if (!preg_match("/^(.{3,255})?$/ui", $form['title'][$locale['shortName']])) {$errors = true; $formerror['title'][$locale['shortName']] = 'Заголовок должен содержать более 3 символов либо отсутствовать';}
            }            
            if (($form['sendEmail'] != 0) && (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,24})$/ui", $form['email']))) {$errors = true; $formerror['email'] = 'Введен некорректный E-Mail';}
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
            $postformfields = $this->getRequest()->get('formfields');
            if (is_array($postformfields))
            {
                $formfields = array();
                foreach ($postformfields as $field)
                {
                    $allfields = true;
                    $newfield = array();
                    if (isset($field['id'])) $newfield['id'] = $field['id'];
                    if (isset($field['enabled'])) $newfield['enabled'] = intval($field['enabled']); else $allfields = false;
                    if (isset($field['required'])) $newfield['required'] = intval($field['required']); else $allfields = false;
                    if (isset($field['type'])) $newfield['type'] = $field['type']; else $allfields = false;
                    if (isset($field['ordering'])) $newfield['ordering'] = intval($field['ordering']); else $allfields = false;
                    if (isset($field['nesting'])) $newfield['nesting'] = intval($field['nesting']); else $allfields = false;
                    if (isset($field['name']['default'])) $newfield['name']['default'] = $field['name']['default']; else $allfields = false;
                    if (isset($field['parameter']['default'])) $newfield['parameter']['default'] = $field['parameter']['default']; else $allfields = false;
                    foreach ($locales as $locale)
                    {
                        if (isset($field['name'][$locale['shortName']])) $newfield['name'][$locale['shortName']] = $field['name'][$locale['shortName']]; else $allfields = false;
                        if (isset($field['parameter'][$locale['shortName']])) $newfield['parameter'][$locale['shortName']] = $field['parameter'][$locale['shortName']]; else $allfields = false;
                    }                    
                    if ($allfields == true) 
                    {
                        if (!preg_match("/^.{3,255}$/ui", $newfield['name']['default'])) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Заголовок должен содержать более 3 символов';}
                        foreach ($locales as $locale)
                        {
                            if (!preg_match("/^(.{3,255})?$/ui", $newfield['name'][$locale['shortName']])) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Заголовок должен содержать более 3 символов';}
                        }
                        if (($newfield['type'] == 'text') || ($newfield['type'] == 'textarea'))
                        {
                            if (!preg_match("/^.{1,255}$/ui", $newfield['parameter']['default'])) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Ошибка в поле регулярного выражения';}
                            foreach ($locales as $locale)
                            {
                                if (!preg_match("/^(.{1,255})?$/ui", $newfield['parameter'][$locale['shortName']])) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Ошибка в поле регулярного выражения';}
                            }
                        } else
                        if (($newfield['type'] == 'radio') || ($newfield['type'] == 'select'))
                        {
                            if (!preg_match("/^([- _A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\,]{3,250}([\r\n]+)?)+$/ui", $newfield['parameter']['default'])) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Ошибка в поле пунктов';}
                            foreach ($locales as $locale)
                            {
                                if (!preg_match("/^(([- _A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\,]{3,250}([\r\n]+)?)+)?$/ui", $newfield['parameter'][$locale['shortName']])) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Ошибка в поле пунктов';}
                            }
                        } else if (($newfield['type'] != 'checkbox') && ($newfield['type'] != 'group')) {$errors = true; if (!isset($newfield['error'])) $newfield['error'] = 'Неизвестный тип';}
                        $formfields[$newfield['ordering']] = $newfield;
                    }
                }
                ksort($formfields);
            }
            if (($errors == true) && ($activetab == 0)) $activetab = 3;
            // Вылидация других табов и считывание контента
            $cmsservices = $this->container->getServiceIds();
            $i = 0;
            foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) 
            {
                $serv = $this->container->get($item);
                $localerror = $serv->getAdminController($this->getRequest(), 'formEdit', $id, 'validate');
                if ($localerror == true) $errors = true;
                if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                $i++;
            }     
            // Если нет ошибок - сохранение
            if ($errors == false)
            {
                $forment->setTitle($this->get('cms.cmsManager')->encodeLocalString($form['title']));
                $forment->setEmail($form['email']);
                $forment->setEnabled($form['enabled']);
                $forment->setSendEmail($form['sendEmail']);
                $forment->setCaptchaEnabled($form['captchaEnabled']);
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
                    $pageent->setDescription('Просмотр формы '.$this->get('cms.cmsManager')->decodeLocalString($forment->getTitle(),'default',false));
                    $pageent->setUrl($page['url']);
                    $pageent->setTemplate($page['layout']);
                    $pageent->setModules(implode(',', $page['modules']));
                    $pageent->setLocale($page['locale']);
                    $pageent->setContentType('object.form');
                    $pageent->setContentId($forment->getId());
                    $pageent->setContentAction('view');
                    $forment->setTemplate($page['template']);
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
                $tmpids = array();
                foreach ($formfields as $field)
                {
                    if (isset($field['id'])) $tmpids[] = $field['id'];
                }
                if (count($tmpids) > 0) 
                {
                    $query = $em->createQuery('DELETE FROM FormFormBundle:FormFields f WHERE f.formId = :id AND f.id not in (:ids)')->setParameter('id', $id)->setParameter('ids', $tmpids);
                    $query->execute();
                }
                foreach ($formfields as $field)
                {
                    if (isset($field['id'])) 
                    {
                        $fieldent = $em->getRepository('FormFormBundle:FormFields')->find($field['id']);
                        if (empty($fieldent))
                        {
                            $fieldent = new \Form\FormBundle\Entity\FormFields();
                            $em->persist($fieldent);
                        }
                    } else 
                    {
                        $fieldent = new \Form\FormBundle\Entity\FormFields();
                        $em->persist($fieldent);
                    }
                    $fieldent->setEnabled($field['enabled']);
                    $fieldent->setRequired($field['required']);
                    $fieldent->setFormId($forment->getId());
                    $fieldent->setName($this->get('cms.cmsManager')->encodeLocalString($field['name']));
                    $fieldent->setOrdering($field['ordering']);
                    $fieldent->setNesting($field['nesting']);
                    $fieldent->setParameters($this->get('cms.cmsManager')->encodeLocalString($field['parameter']));
                    $fieldent->setType($field['type']);
                    $em->flush();
                    unset($fieldent);
                }
                $i = 0;
                foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) 
                {
                    $serv = $this->container->get($item);
                    $localerror = $serv->getAdminController($this->getRequest(), 'formEdit', $forment->getId(), 'save');
                    if ($localerror == true) $errors = true;
                    if (($errors == true) && ($activetab == 0)) $activetab = $i + 4;
                    $i++;
                }       
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование формы',
                    'message'=>'Данные форма успешно отредактированы',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('form_form_edit').'?id='.$id, 'Вернуться к списку форм'=>$this->get('router')->generate('form_form_list'))
                ));
            }
        }
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'formEdit', $id, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        if ($activetab == 0) $activetab = 1;
        return $this->render('FormFormBundle:Default:formEdit.html.twig', array(
            'id' => $id,
            'createrId' => $forment->getCreaterId(),
            'form' => $form,
            'formerror' => $formerror,
            'page' => $page,
            'pageerror' => $pageerror,
            'formfields' => $formfields,
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
    
    public function formAjaxAction()
    {
        $tab = intval($this->getRequest()->get('tab'));
        if ((($this->getUser()->checkAccess('form_letterlist') == 0) && ($tab == 0)) || (($this->getUser()->checkAccess('form_list') == 0) && ($tab != 0)))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Список форм',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->getRequest()->get('action');
        $errors = array();
        $errorsorder = array();
        if ($action == 'deleteletters')
        {
            if ($this->getUser()->checkAccess('form_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Список форм',
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
                    $query = $em->createQuery('DELETE FROM FormFormBundle:FormLetters l WHERE l.formId = :id')->setParameter('id', $key);
                    $query->execute();
                    foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
                    {
                        $serv = $this->container->get($item);
                        $serv->getAdminController($this->getRequest(), 'formDeleteLetters', $key, 'save');
                    }       
                }
        }
        if ($action == 'delete')
        {
            if ($this->getUser()->checkAccess('form_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Список форм',
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
                    $query = $em->createQuery('DELETE FROM FormFormBundle:FormLetters l WHERE l.formId = :id')->setParameter('id', $key);
                    $query->execute();
                    $query = $em->createQuery('DELETE FROM BasicCmsBundle:SeoPage p WHERE p.contentType = \'object.form\' AND p.contentAction = \'view\' AND p.contentId = :id')->setParameter('id', $key);
                    $query->execute();
                    $forment = $this->getDoctrine()->getRepository('FormFormBundle:Forms')->find($key);
                    if (!empty($forment))
                    {
                        $em->remove($forment);
                        $em->flush();
                        foreach ($cmsservices as $item) if (strpos($item,'addone.product.') === 0) 
                        {
                            $serv = $this->container->get($item);
                            $serv->getAdminController($this->getRequest(), 'formDelete', $key, 'save');
                        }       
                    }
                    unset($forment);
                }
        }
        if ($action == 'blocked')
        {
            if ($this->getUser()->checkAccess('form_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Список форм',
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
                    $forment = $this->getDoctrine()->getRepository('FormFormBundle:Forms')->find($key);
                    if (!empty($forment))
                    {
                        $forment->setEnabled(0);
                        $em->flush();
                    }
                    unset($forment);
                }
        }
        if ($action == 'unblocked')
        {
            if ($this->getUser()->checkAccess('form_editall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Список форм',
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
                    $forment = $this->getDoctrine()->getRepository('FormFormBundle:Forms')->find($key);
                    if (!empty($forment))
                    {
                        $forment->setEnabled(1);
                        $em->flush();
                    }
                    unset($forment);
                }
        }
        if ($action == 'viewed')
        {
            if ($this->getUser()->checkAccess('form_letterviewall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Список форм',
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
                    $letterent = $this->getDoctrine()->getRepository('FormFormBundle:FormLetters')->find($key);
                    if (!empty($letterent))
                    {
                        $letterent->setViewed(1);
                        $letterent->setViewDate(new \DateTime('now'));
                        $letterent->setViewUserId($this->getUser()->getId());
                        $em->flush();
                    }
                    unset($letterent);
                }
        }
        if ($tab == 0)
        {
            $page0 = $this->getRequest()->getSession()->get('form_form_list_page0');
            $sort0 = $this->getRequest()->getSession()->get('form_form_list_sort0');
            $search0 = $this->getRequest()->getSession()->get('form_form_list_search0');
            $page0 = intval($page0);
            $sort0 = intval($sort0);
            $search0 = trim($search0);
            // Поиск контента для 1 таба (письма)
            $query = $em->createQuery('SELECT count(l.id) as lettercount FROM FormFormBundle:FormLetters l '.
                                      'LEFT JOIN FormFormBundle:Forms f WITH f.id = l.formId '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = l.viewUserId '.
                                      'WHERE u.fullName like :search OR f.title like :search')->setParameter('search', '%'.$search0.'%');
            $lettercount = $query->getResult();
            if (!empty($lettercount)) $lettercount = $lettercount[0]['lettercount']; else $lettercount = 0;
            $pagecount0 = ceil($lettercount / 20);
            if ($pagecount0 < 1) $pagecount0 = 1;
            if ($page0 < 0) $page0 = 0;
            if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
            $sortsql = 'ORDER BY l.createDate DESC';
            if ($sort0 == 1) $sortsql = 'ORDER BY l.createDate ASC';
            if ($sort0 == 2) $sortsql = 'ORDER BY l.formId ASC';
            if ($sort0 == 3) $sortsql = 'ORDER BY l.formId DESC';
            if ($sort0 == 4) $sortsql = 'ORDER BY l.viewed ASC';
            if ($sort0 == 5) $sortsql = 'ORDER BY l.viewed DESC';
            if ($sort0 == 6) $sortsql = 'ORDER BY u.fullName ASC';
            if ($sort0 == 7) $sortsql = 'ORDER BY u.fullName DESC';
            $start = $page0 * 20;
            $query = $em->createQuery('SELECT l.id, l.viewed, u.fullName, l.createDate, l.viewDate, l.formId, f.title FROM FormFormBundle:FormLetters l '.
                                      'LEFT JOIN FormFormBundle:Forms f WITH f.id = l.formId '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = l.viewUserId '.
                                      'WHERE u.fullName like :search OR f.title like :search '.$sortsql)->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
            $letters0 = $query->getResult();
            foreach ($letters0 as &$lett) $lett['title'] = $this->get('cms.cmsManager')->decodeLocalString($lett['title'], 'default');
            return $this->render('FormFormBundle:Default:formListTab1.html.twig', array(
                'pagecount0' => $pagecount0,
                'search0' => $search0,
                'page0' => $page0,
                'sort0' => $sort0,
                'letters0' => $letters0,
                'tab' => $tab
            ));
            
        }
        if ($tab == 1)
        {
            // Таб 2
            $page1 = $this->getRequest()->getSession()->get('form_form_list_page1');
            $sort1 = $this->getRequest()->getSession()->get('form_form_list_sort1');
            $search1 = $this->getRequest()->getSession()->get('form_form_list_search1');
            $page1 = intval($page1);
            $sort1 = intval($sort1);
            $search1 = trim($search1);
            // Поиск контента для 2 таба (формы)
            $query = $em->createQuery('SELECT count(f.id) as formcount FROM FormFormBundle:Forms f '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.
                                      'WHERE u.fullName like :search OR f.title like :search')->setParameter('search', '%'.$search1.'%');
            $formcount = $query->getResult();
            if (!empty($formcount)) $formcount = $formcount[0]['formcount']; else $formcount = 0;
            $pagecount1 = ceil($formcount / 20);
            if ($pagecount1 < 1) $pagecount1 = 1;
            if ($page1 < 0) $page1 = 0;
            if ($page1 >= $pagecount1) $page1 = $pagecount1 - 1;
            $sortsql = 'ORDER BY f.title ASC';
            if ($sort1 == 1) $sortsql = 'ORDER BY f.title DESC';
            if ($sort1 == 2) $sortsql = 'ORDER BY f.enabled ASC';
            if ($sort1 == 3) $sortsql = 'ORDER BY f.enabled DESC';
            if ($sort1 == 4) $sortsql = 'ORDER BY f.email ASC';
            if ($sort1 == 5) $sortsql = 'ORDER BY f.email DESC';
            if ($sort1 == 6) $sortsql = 'ORDER BY u.fullName ASC';
            if ($sort1 == 7) $sortsql = 'ORDER BY u.fullName DESC';
            $start = $page1 * 20;
            $query = $em->createQuery('SELECT f.id, f.title, f.email, f.sendEmail, f.enabled, u.fullName FROM FormFormBundle:Forms f '.
                                      'LEFT JOIN BasicCmsBundle:Users u WITH u.id = f.createrId '.
                                      'WHERE u.fullName like :search OR f.title like :search '.$sortsql)->setParameter('search', '%'.$search1.'%')->setFirstResult($start)->setMaxResults(20);
            $forms1 = $query->getResult();
            foreach ($forms1 as &$formitem) $formitem['title'] = $this->get('cms.cmsManager')->decodeLocalString($formitem['title'], 'default');
            return $this->render('FormFormBundle:Default:formListTab2.html.twig', array(
                'pagecount1' => $pagecount1,
                'search1' => $search1,
                'page1' => $page1,
                'sort1' => $sort1,
                'forms1' => $forms1,
                'tab' => $tab
            ));
            
        }
    }
    
// *******************************************
// Просмотр письма
// *******************************************    
    public function formLetterViewAction()
    {
        $this->getRequest()->getSession()->set('form_form_list_tab', 0);
        $em = $this->getDoctrine()->getEntityManager();
        $id = intval($this->getRequest()->get('id'));
        $letterent = $this->getDoctrine()->getRepository('FormFormBundle:FormLetters')->find($id);
        if (empty($letterent))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Просмотр письма формы',
                'message'=>'Письмо не найдено', 
                'error'=>true,
                'paths'=>array('Вернуться к списку форм'=>$this->get('router')->generate('form_form_list'))
            ));
        }
        $forment = $this->getDoctrine()->getRepository('FormFormBundle:Forms')->find($letterent->getFormId());
        if (!empty($forment))
        {
            if (($this->getUser()->checkAccess('form_viewall') == 0) && ($this->getUser()->getId() != $forment->getCreaterId()))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Просмотр письма формы',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
            if (($this->getUser()->checkAccess('form_viewown') == 0) && ($this->getUser()->getId() == $forment->getCreaterId()))
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Просмотр письма формы',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
        } else
        {
            if ($this->getUser()->checkAccess('form_viewall') == 0)
            {
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Просмотр письма формы',
                    'message'=>'Доступ запрещён', 
                    'error'=>true,
                    'paths'=>array()
                ));
            }
        }
        $pageent = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('contentType'=>$letterent->getSeoPageObject(),'contentId'=>$letterent->getSeoPageId(),'contentAction'=>$letterent->getSeoPageAction()));
        $locale = $this->getDoctrine()->getRepository('BasicCmsBundle:Locales')->findOneBy(array('shortName'=>$letterent->getLocale()));
        if ($letterent->getViewUserId() != null) $user = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($letterent->getViewUserId());
        $form = array();
        if (empty($pageent))
        {
            $form['url'] = '';
            $form['urlDescription'] = '';
        } else
        {
            $form['url'] = $pageent->getUrl();
            $form['urlDescription'] = $pageent->getDescription();
        }
        if (empty($locale))
        {
            $form['locale'] = 'По умолчанию';
        } else
        {
            $form['locale'] = $locale->getFullName();
        }
        $form['ipAddress'] = $letterent->getIpAddress();
        $form['createDate'] = $letterent->getCreateDate();
        if (empty($forment))
        {
            $form['formName'] = 'Форма не найдена';
            $form['formEmail'] = 'Выключена';
        } else
        {
            $form['formName'] = $this->get('cms.cmsManager')->decodeLocalString($forment->getTitle(), 'default');
            $form['formEmail'] = ($forment->getSendEmail() != 0 ? $forment->getEmail() : 'Выключена');
        }
        $form['viewed'] = $letterent->getViewed();
        $form['viewDate'] = $letterent->getViewDate();
        if (empty($user))
        {
            $form['viewUser'] = 'Пользователь не найден';
        } else
        {
            $form['viewUser'] = $user->getFullName();
        }
        $fields = @unserialize($letterent->getFields());
        if (!is_array($fields)) $fields = array();
        $tabs = array();
        $activetab = 1;
        // Другие табы
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'addone.form.') === 0) 
        {
            $serv = $this->container->get($item);
            $content = $serv->getAdminController($this->getRequest(), 'formLetterView', $id, 'tab');
            $tabs[] = array('name'=>$serv->getDescription(),'content'=>$content);
        }       
        // Записать данные о просмотре
        $letterent->setViewed(1);
        $letterent->setViewDate(new \DateTime('now'));
        $letterent->setViewUserId($this->getUser()->getId());
        $em->flush();
        return $this->render('FormFormBundle:Default:letterView.html.twig', array(
            'form' => $form,
            'fields' => $fields,
            'tabs' => $tabs,
            'activetab' => $activetab
        ));
        
    }
    
}
