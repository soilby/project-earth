<?php

namespace Shop\ParameterBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function parameterListAction()
    {
        if ($this->getUser()->checkAccess('product_parameter') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Настройка полей параметров продукции',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $page0 = $this->get('request_stack')->getMasterRequest()->get('page0');
        $search0 = $this->get('request_stack')->getMasterRequest()->get('search0');
        
        if ($page0 === null) $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('shop_parameter_list_page0');
                        else $this->get('request_stack')->getMasterRequest()->getSession()->set('shop_parameter_list_page0', $page0);
        if ($search0 === null) $search0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('shop_parameter_list_search0');
                          else $this->get('request_stack')->getMasterRequest()->getSession()->set('shop_parameter_list_search0', $search0);
        $page0 = intval($page0);
        $search0 = trim($search0);
                          
        $query = $em->createQuery('SELECT count(p.id) as paramcount FROM ShopParameterBundle:ProductParameters p '.
                                  'WHERE p.name like :search OR p.description like :search')->setParameter('search', '%'.$search0.'%');
        $paramcount = $query->getResult();
        if (!empty($paramcount)) $paramcount = $paramcount[0]['paramcount']; else $paramcount = 0;
        $pagecount0 = ceil($paramcount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT p.id, p.type, p.name, p.description, p.ordering, p.regularExp FROM ShopParameterBundle:ProductParameters p '.
                                  'WHERE p.name like :search OR p.description like :search ORDER BY p.ordering')->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $parameters = $query->getResult();
        foreach ($parameters as &$item) $item['description'] = $this->get('cms.cmsManager')->decodeLocalString($item['description'], 'default');
        return $this->render('ShopParameterBundle:Default:parameterList.html.twig', array(
            'page0' => $page0,
            'search0' => $search0,
            'pagecount0' => $pagecount0,
            'parameters' => $parameters
            ));
    }
    
    public function parameterCreateAction()
    {
        if ($this->getUser()->checkAccess('product_parameter') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание нового поля параметров продукции',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        
        $param = array();
        $paramerror = array();
        $param['name'] = '';
        $param['type'] = '';
        $param['description'] = array();
        $param['description']['default'] = '';
        foreach ($locales as $local) $param['description'][$local['shortName']] = '';
        $param['regularExp'] = '';
        $param['items'] = '';
        $paramerror['name'] = '';
        $paramerror['type'] = '';
        $paramerror['description'] = array();
        $paramerror['description']['default'] = '';
        foreach ($locales as $local) $paramerror['description'][$local['shortName']] = '';
        $paramerror['regularExp'] = '';
        $paramerror['items'] = '';
        // Валидация
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == 'POST')
        {
            $errors = false;
            $postparam = $this->get('request_stack')->getMasterRequest()->get('param');
            if (isset($postparam['type'])) $param['type'] = $postparam['type'];
            if (isset($postparam['name'])) $param['name'] = $postparam['name'];
            if (isset($postparam['description']['default'])) $param['description']['default'] = $postparam['description']['default'];
            foreach ($locales as $local) if (isset($postparam['description'][$local['shortName']])) $param['description'][$local['shortName']] = $postparam['description'][$local['shortName']];
            if (isset($postparam['regularExp'])) $param['regularExp'] = $postparam['regularExp'];
            if (isset($postparam['items'])) $param['items'] = $postparam['items'];
            unset($postparam);
            if (!preg_match("/^[_A-Za-z0-9\-]{3,90}$/ui", $param['name'])) {$errors = true; $paramerror['name'] = 'Имя долно содержать от 3 до 90 латинских букв, цифр, дефисов или знаков подчёркивания';} 
            else
            {
                $query = $em->createQuery('SELECT count(p.id) as checkcount FROM ShopParameterBundle:ProductParameters p WHERE p.name = :name')->setParameter('name', $param['name']);
                $checkcount = $query->getResult();
                if (isset($checkcount[0]['checkcount'])) $checkcount = $checkcount[0]['checkcount']; else $checkcount = 0;
                if ($checkcount != 0) {$errors = true; $paramerror['name'] = 'Поле с таким именем уже существует';}
            }
            if (!preg_match("/^.{1,1000}$/ui", $param['description']['default'])) {$errors = true; $paramerror['description']['default'] = 'Поле должно содержать от 1 до 1000 символов';}
            foreach ($locales as $local) if (!preg_match("/^.{0,1000}$/ui", $param['description'][$local['shortName']])) {$errors = true; $paramerror['description'][$local['shortName']] = 'Поле должно содержать до 1000 символов';}
            if (($param['type'] == 'text') || ($param['type'] == 'textarea') || ($param['type'] == 'html'))
            {
                if (!preg_match("/^.{1,1000}$/ui", $param['regularExp'])) {$errors = true; $paramerror['regularExp'] = 'Поле должно содержать от 1 до 1000 символов';}
                elseif (@preg_match('/^'.$param['regularExp'].'$/ui','') === false) {$errors = true; $paramerror['regularExp'] = 'Регулярное выражение некорректно';} 
            } elseif ($param['type'] == 'select')
            {
                if (!preg_match("/^([- _A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\,]{3,250}([\r\n]+)?)+$/ui", $param['items'])) {$errors = true; $paramerror['items'] = 'Ошибка в поле пунктов';}
            } elseif ($param['type'] != 'checkbox') {$errors = true; $paramerror['type'] = 'Неизвестный тип';}
            if ($errors == false)
            {
                $query = $em->createQuery('SELECT MAX(p.ordering) as maxordering FROM ShopParameterBundle:ProductParameters p');
                $maxordering = $query->getResult();
                if (isset($maxordering[0]['maxordering'])) $maxordering = $maxordering[0]['maxordering'] + 1; else $maxordering = 1;
                // Сохранение
                $parament = new \Shop\ParameterBundle\Entity\ProductParameters();
                $parament->setType($param['type']);
                $parament->setName($param['name']);
                $parament->setDescription($this->get('cms.cmsManager')->encodeLocalString($param['description']));
                $parament->setRegularExp($param['regularExp']);
                $parament->setOrdering($maxordering);
                $parament->setItems($param['items']);
                $em->persist($parament);
                $em->flush();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Создание нового поля параметров продукции',
                    'message'=>'Поле профиля успешно создано',
                    'paths'=>array('Создать еще одно поле профиля'=>$this->get('router')->generate('shop_parameter_create'),'Вернуться к списку полей'=>$this->get('router')->generate('shop_parameter_list'))
                ));
            }
        }
        // Вывод шаблона
        return $this->render('ShopParameterBundle:Default:parameterCreate.html.twig', array(
            'param' => $param,
            'paramerror' => $paramerror,
            'locales' => $locales
            ));
    }

    public function parameterEditAction()
    {
        if ($this->getUser()->checkAccess('product_parameter') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование поля параметров продукции',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        
        $id = intval($this->get('request_stack')->getMasterRequest()->get('id'));
        $parament = $this->getDoctrine()->getRepository('ShopParameterBundle:ProductParameters')->find($id);
        if (empty($parament))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Редактирование поля параметров продукции',
                'message'=>'Поле параметров не найдено', 
                'error'=>true,
                'paths'=>array('Вернуться к списку полей'=>$this->get('router')->generate('shop_parameter_list'))
            ));
            
        }
        $param = array();
        $paramerror = array();
        $param['type'] = $parament->getType();
        $param['name'] = $parament->getName();
        $param['description'] = array();
        $param['description']['default'] = $this->get('cms.cmsManager')->decodeLocalString($parament->getDescription(),'default',false);
        foreach ($locales as $local) $param['description'][$local['shortName']] = $this->get('cms.cmsManager')->decodeLocalString($parament->getDescription(),$local['shortName'],false);
        $param['regularExp'] = $parament->getRegularExp();
        $param['items'] = $parament->getItems();
        $paramerror['type'] = '';
        $paramerror['name'] = '';
        $paramerror['description'] = array();
        $paramerror['description']['default'] = '';
        foreach ($locales as $local) $paramerror['description'][$local['shortName']] = '';
        $paramerror['regularExp'] = '';
        $paramerror['items'] = '';
        // Валидация
        if ($this->get('request_stack')->getMasterRequest()->getMethod() == 'POST')
        {
            $errors = false;
            $postparam = $this->get('request_stack')->getMasterRequest()->get('param');
            if (isset($postparam['type'])) $param['type'] = $postparam['type'];
            if (isset($postparam['name'])) $param['name'] = $postparam['name'];
            if (isset($postparam['description']['default'])) $param['description']['default'] = $postparam['description']['default'];
            foreach ($locales as $local) if (isset($postparam['description'][$local['shortName']])) $param['description'][$local['shortName']] = $postparam['description'][$local['shortName']];
            if (isset($postparam['regularExp'])) $param['regularExp'] = $postparam['regularExp'];
            if (isset($postparam['items'])) $param['items'] = $postparam['items'];
            unset($postparam);
            if (!preg_match("/^[_A-Za-z0-9\-]{3,90}$/ui", $param['name'])) {$errors = true; $paramerror['name'] = 'Имя долно содержать от 3 до 90 латинских букв, цифр, дефисов или знаков подчёркивания';}
            else
            {
                $query = $em->createQuery('SELECT count(p.id) as checkcount FROM ShopParameterBundle:ProductParameters p WHERE p.name = :name AND p.id != :id')->setParameter('name', $param['name'])->setParameter('id', $id);
                $checkcount = $query->getResult();
                if (isset($checkcount[0]['checkcount'])) $checkcount = $checkcount[0]['checkcount']; else $checkcount = 0;
                if ($checkcount != 0) {$errors = true; $paramerror['name'] = 'Поле с таким именем уже существует';}
            }
            if (!preg_match("/^.{1,1000}$/ui", $param['description']['default'])) {$errors = true; $paramerror['description']['default'] = 'Поле должно содержать от 1 до 1000 символов';}
            foreach ($locales as $local) if (!preg_match("/^.{0,1000}$/ui", $param['description'][$local['shortName']])) {$errors = true; $paramerror['description'][$local['shortName']] = 'Поле должно содержать до 1000 символов';}
            /*if (!preg_match("/^.{1,1000}$/ui", $param['regularExp'])) {$errors = true; $paramerror['regularExp'] = 'Поле должно содержать от 1 до 1000 символов';}
            elseif (@preg_match('/^'.$param['regularExp'].'$/ui','') === false) {$errors = true; $paramerror['regularExp'] = 'Регулярное выражение некорректно';} */
            if (($param['type'] == 'text') || ($param['type'] == 'textarea') || ($param['type'] == 'html'))
            {
                if (!preg_match("/^.{1,1000}$/ui", $param['regularExp'])) {$errors = true; $paramerror['regularExp'] = 'Поле должно содержать от 1 до 1000 символов';}
                elseif (@preg_match('/^'.$param['regularExp'].'$/ui','') === false) {$errors = true; $paramerror['regularExp'] = 'Регулярное выражение некорректно';} 
            } elseif ($param['type'] == 'select')
            {
                if (!preg_match("/^([- _A-zА-яЁё0-9\'\"\(\)\*\?\:\;\+\=\!\@\#\$\%\^\&\`\~\.\,]{3,250}([\r\n]+)?)+$/ui", $param['items'])) {$errors = true; $paramerror['items'] = 'Ошибка в поле пунктов';}
            } elseif ($param['type'] != 'checkbox') {$errors = true; $paramerror['type'] = 'Неизвестный тип';}
            if ($errors == false)
            {
                // Сохранение
                $parament->setType($param['type']);
                $parament->setName($param['name']);
                $parament->setDescription($this->get('cms.cmsManager')->encodeLocalString($param['description']));
                $parament->setRegularExp($param['regularExp']);
                $parament->setItems($param['items']);
                $em->flush();
                return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                    'title'=>'Редактирование поля параметров продукции',
                    'message'=>'Поле параметров успешно сохранено',
                    'paths'=>array('Продолжить редактирование'=>$this->get('router')->generate('shop_parameter_edit').'?id='.$id, 'Вернуться к списку полей'=>$this->get('router')->generate('shop_parameter_list'))
                ));
            }
        }
        // Вывод шаблона
        return $this->render('ShopParameterBundle:Default:parameterEdit.html.twig', array(
            'id' => $id,
            'param' => $param,
            'paramerror' => $paramerror,
            'locales' => $locales
            ));
    }

    public function parameterAjaxAction()
    {
        if ($this->getUser()->checkAccess('product_parameter') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Настройка полей параметров продукции',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        $em = $this->getDoctrine()->getEntityManager();
        $action = $this->get('request_stack')->getMasterRequest()->get('action');
        
        if ($action == 'delete')
        {
            $check = $this->get('request_stack')->getMasterRequest()->get('check');
            if ($check != null)
            foreach ($check as $key=>$val)
                if ($val == 1)
                {
                    $query = $em->createQuery('DELETE FROM ShopParameterBundle:ProductParameters p '.
                                              'WHERE p.id = :id')->setParameter('id', $key);
                    $query->execute();
                    $query = $em->createQuery('DELETE FROM ShopParameterBundle:ProductValues v '.
                                              'WHERE v.parameterId = :id')->setParameter('id', $key);
                    $query->execute();
                }
        }
        
        if ($action == 'ordering')
        {
            $ordering = $this->get('request_stack')->getMasterRequest()->get('ordering');
            $errors = false;
            $errortext = array();
            $ids = array();
            foreach ($ordering as $key=>$val) $ids[] = $key;
            foreach ($ordering as $key=>$val)
            {
                if (!preg_match("/^\d{1,20}$/ui", $val)) {$errors = true; $errortext[$key] = 'Введено не число';} else
                {
                    foreach ($ordering as $key2=>$val2) if (($key != $key2) && ($val == $val2)) {$errors = true; $errortext[$key] = 'Введено два одинаковых числа';} else
                    {
                        $query = $em->createQuery('SELECT count(p.id) as checkid FROM ShopParameterBundle:ProductParameters p '.
                                                  'WHERE p.ordering = :order AND p.id NOT IN (:ids)')->setParameter('ids', $ids)->setParameter('order', $val);
                        $checkid = $query->getResult();
                        if (isset($checkid[0]['checkid'])) $checkid = $checkid[0]['checkid']; else $checkid = 0;
                        if ($checkid != 0) {$errors = true; $errortext[$key] = 'Число уже используется';}
                    }
                }
            }
            // изменить порядок
            if ($errors == true)
            {
                $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('shop_parameter_list_page0');
                $search0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('shop_parameter_list_search0');

                $query = $em->createQuery('SELECT count(p.id) as paramcount FROM ShopParameterBundle:ProductParameters p '.
                                          'WHERE p.name like :search OR p.description like :search')->setParameter('search', '%'.$search0.'%');
                $paramcount = $query->getResult();
                if (!empty($paramcount)) $paramcount = $paramcount[0]['paramcount']; else $paramcount = 0;
                $pagecount0 = ceil($paramcount / 20);
                if ($pagecount0 < 1) $pagecount0 = 1;
                if ($page0 < 0) $page0 = 0;
                if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
                $start = $page0 * 20;
                $parameters = array();
                foreach ($ordering as $key=>$val)
                {
                    $query = $em->createQuery('SELECT p.id, p.name, p.description, p.ordering, p.regularExp FROM ShopParameterBundle:ProductParameters p '.
                                              'WHERE p.id = :id')->setParameter('id', $key);
                    $param = $query->getSingleResult();
                    $param['ordering'] = $val;
                    $parameters[] = $param;
                }
                foreach ($parameters as &$item) $item['description'] = $this->get('cms.cmsManager')->decodeLocalString($item['description'], 'default');
                return $this->render('ShopParameterBundle:Default:parameterListTab1.html.twig', array(
                    'page0' => $page0,
                    'search0' => $search0,
                    'pagecount0' => $pagecount0,
                    'parameters' => $parameters,
                    'errortext' => $errortext
                    ));
            }
            foreach ($ordering as $key=>$val)
            {
                $parament = $this->getDoctrine()->getRepository('ShopParameterBundle:ProductParameters')->find($key);
                $parament->setOrdering($val);
            }
            $em->flush();
        }
        
        $page0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('shop_parameter_list_page0');
        $search0 = $this->get('request_stack')->getMasterRequest()->getSession()->get('shop_parameter_list_search0');
        $page0 = intval($page0);
        $search0 = trim($search0);
        
        $query = $em->createQuery('SELECT count(p.id) as paramcount FROM ShopParameterBundle:ProductParameters p '.
                                  'WHERE p.name like :search OR p.description like :search')->setParameter('search', '%'.$search0.'%');
        $paramcount = $query->getResult();
        if (!empty($paramcount)) $paramcount = $paramcount[0]['paramcount']; else $paramcount = 0;
        $pagecount0 = ceil($paramcount / 20);
        if ($pagecount0 < 1) $pagecount0 = 1;
        if ($page0 < 0) $page0 = 0;
        if ($page0 >= $pagecount0) $page0 = $pagecount0 - 1;
        $start = $page0 * 20;
        $query = $em->createQuery('SELECT p.id, p.type, p.name, p.description, p.ordering, p.regularExp FROM ShopParameterBundle:ProductParameters p '.
                                  'WHERE p.name like :search OR p.description like :search ORDER BY p.ordering')->setParameter('search', '%'.$search0.'%')->setFirstResult($start)->setMaxResults(20);
        $parameters = $query->getResult();
        foreach ($parameters as &$item) $item['description'] = $this->get('cms.cmsManager')->decodeLocalString($item['description'], 'default');
        
        return $this->render('ShopParameterBundle:Default:parameterListTab1.html.twig', array(
            'page0' => $page0,
            'search0' => $search0,
            'pagecount0' => $pagecount0,
            'parameters' => $parameters
            ));
    }
}
