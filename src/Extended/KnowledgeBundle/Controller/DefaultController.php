<?php

namespace Extended\KnowledgeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
    
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
        if ($this->getUser()->checkAccess('knowledge_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Браузер объектов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $categories = array(
            array('id' => -1, 'name' => 'База данных', 'nesting' => 0),
            array('id' => \Extended\KnowledgeBundle\Entity\KnowledgeObject::KO_TYPE_DOCUMENT, 'name' => 'Документы мастерских', 'nesting' => 1),
            array('id' => -1, 'name' => 'Файловая система', 'nesting' => 0),
            array('id' => \Extended\KnowledgeBundle\Entity\KnowledgeObject::KO_TYPE_FILE, 'name' => 'Файлы мастерских', 'nesting' => 1),
        );
        
        $typeCounts = $em->createQuery('SELECT o.objectType, count(o.id) as objectsCount FROM ExtendedKnowledgeBundle:KnowledgeObject o GROUP BY o.objectType')->getResult();
        foreach ($categories as &$category) {
            $category['count'] = 0;
            foreach ($typeCounts as $typeCount) {
                if ($category['id'] == $typeCount['objectType']) {
                    $category['count'] = $typeCount['objectsCount'];
                    break;
                }
            }
        }
        

        /*$query = $em->createQuery('SELECT t.id, t.title, t.enabled, t.parent FROM ExtendedKnowledgeBundle:KnowledgeType t '.
                                  'ORDER BY t.ordering');
        $tree = $query->getResult();
        $this->typesGetTree($tree, null, 0, $categories);*/
        
        return $this->render('ExtendedKnowledgeBundle:Default:list.html.twig', array(
            'categories' => $categories,
        ));
    }

    public function listTableAction()
    {
        /*$objects = $this->getDoctrine()->getRepository('ExtendedKnowledgeBundle:KnowledgeObject')->findAll();
        foreach ($objects as $object) {
            $object->setDefaultTitle($this->container->get('cms.cmsManager')->decodeLocalString($object->getTitle(), 'default'));
        }
        $this->getDoctrine()->getEntityManager()->flush();
        
        */
        $em = $this->getDoctrine()->getEntityManager();
        if ($this->getUser()->checkAccess('knowledge_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Браузер объектов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $paths = array(
            \Extended\KnowledgeBundle\Entity\KnowledgeObject::KO_TYPE_DOCUMENT => '/ База данных / Документы мастерских',
            \Extended\KnowledgeBundle\Entity\KnowledgeObject::KO_TYPE_FILE => '/ Файловая система / Файлы мастерских',
        );
        
        return $this->render('ExtendedKnowledgeBundle:Default:listTable.html.twig', array(
            'paths' => json_encode($paths),
        ));
    }
    
    public function loadObjectsAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ($this->getUser()->checkAccess('knowledge_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Браузер объектов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }

        $ids = $this->get('request_stack')->getMasterRequest()->get('type');
        if ($ids != null) {
            $ids = explode(',', $ids);
        }
        $sort = intval($this->get('request_stack')->getMasterRequest()->get('sort'));
        $sortSql = 'o.defaultTitle ASC';
        if ($sort == 1) {
            $sortSql = 'o.defaultTitle DESC';
        } elseif ($sort == 2) {
            $sortSql = 'o.objectType ASC';
        } elseif ($sort == 3) {
            $sortSql = 'o.objectType DESC';
        }
        $page = intval($this->get('request_stack')->getMasterRequest()->get('page'));
        
        if ($ids != null) {
            $count = $em->createQuery('SELECT count(o.id) FROM ExtendedKnowledgeBundle:KnowledgeObject o WHERE o.objectType IN (:ids)')->setParameter('ids', $ids)->getSingleScalarResult();
        } else {
            $count = $em->createQuery('SELECT count(o.id) FROM ExtendedKnowledgeBundle:KnowledgeObject o ORDER BY '.$sortSql)->getSingleScalarResult();
        }
        
        $pageCount = ceil($count / 20);
        $pageCount = max($pageCount, 1);
        
        $page = max($page, 0);
        $page = min($page, $pageCount - 1);
        
        if ($ids != null) {
            $items = $em->createQuery('SELECT o.id, o.defaultTitle as title, o.objectType FROM ExtendedKnowledgeBundle:KnowledgeObject o WHERE o.objectType IN (:ids)')->setParameter('ids', $ids)->setFirstResult($page * 20)->setMaxResults(20)->getResult();
        } else {
            $items = $em->createQuery('SELECT o.id, o.defaultTitle as title, o.objectType FROM ExtendedKnowledgeBundle:KnowledgeObject o ORDER BY '.$sortSql)->setFirstResult($page * 20)->setMaxResults(20)->getResult();
        }
        /*foreach ($items as &$item) {
            $item['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($item['title'],'default');
        }
        unset($item);*/
        
        return new JsonResponse(array(
            'itemCount' => $count,
            'pageCount' => $pageCount,
            'page' => $page,
            'items' => $items,
            'addPermission' => $this->getUser()->checkAccess('knowledge_addobject'),
        ));
    }
    
    public function addObjectAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ($this->getUser()->checkAccess('knowledge_addobject') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Браузер объектов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }

        //$id = $this->get('request_stack')->getMasterRequest()->get('category');
        $url = trim($this->get('request_stack')->getMasterRequest()->get('url'));

        if (strtolower(parse_url($url, PHP_URL_HOST)) != strtolower($this->get('request_stack')->getMasterRequest()->getHttpHost())) {
            return new JsonResponse(array('error' => 'Неправильное имя сайта'));
        }
        
        $path = parse_url($url,  PHP_URL_PATH);
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }
        parse_str(parse_url($url,  PHP_URL_QUERY), $query);
        $locales = $this->getDoctrine()->getEntityManager()->createQuery('SELECT l.shortName FROM BasicCmsBundle:Locales l')->getResult();
        foreach ($locales as $localitem) {
            if (strpos($path, $localitem['shortName'].'/') === 0) {
                $path = substr($path, strlen($localitem['shortName']) + 1); 
                break;
            }
        }
        if ($path == 'system/download/mceprojectfile') {
            // Найти файл документа
            $fileFound = false;
            $projectLink = @unserialize(file_get_contents('secured/project/'.'.projectlink'));
            if (!is_array($projectLink)) {
                $projectLink = array();
            }
            foreach ($projectLink as $projectKey=>$projectVal) {
                if (($projectVal['projectId'] == $query['projectid']) && (($projectVal['path'] != '' ? $projectVal['path'].'/' : '').$projectVal['file'] == $query['file'])) {
                    $fileFound = true;
                    break;
                }
            }
            if ($fileFound == false) {
                return new JsonResponse(array('error' => 'Файл не найден'));
            }
            $prijectId = $query['projectid'];
            $fileId = $query['file'];
            // Проверить нет ли такого
            $count = $em->createQuery('SELECT count(o.id) FROM ExtendedKnowledgeBundle:KnowledgeObject o WHERE o.objectType = :type AND o.objectId = :id AND o.objectTextId = :textid')
                        ->setParameter('type', \Extended\KnowledgeBundle\Entity\KnowledgeObject::KO_TYPE_FILE)
                        ->setParameter('id', $prijectId)
                        ->setParameter('textid', $fileId)
                        ->getSingleScalarResult();
            if ($count != 0) {
                return new JsonResponse(array('error' => 'Файл уже добавлен в реестр'));
            }
            $objectEnt = new \Extended\KnowledgeBundle\Entity\KnowledgeObject();
            $time = new \DateTime();
            $time->setTimestamp(filemtime('secured/project/'.$fileId));
            $objectEnt->setCreateDate($time);
            $objectEnt->setCreaterId($this->getUser()->getId());
            $objectEnt->setObjectId($prijectId);
            $objectEnt->setObjectTextId($fileId);
            $objectEnt->setObjectType(\Extended\KnowledgeBundle\Entity\KnowledgeObject::KO_TYPE_FILE);
            $objectEnt->setRatingNegative(0);
            $objectEnt->setRatingPositive(0);
            $objectEnt->setTitle($this->container->get('cms.cmsManager')->encodeLocalString(array('default' => $fileId)));
            $objectEnt->setDefaultTitle($fileId);
            $em->persist($objectEnt);
            $em->flush();
            return new JsonResponse(array(
                'result' => 'Добавлен файл '.$this->container->get('cms.cmsManager')->decodeLocalString($objectEnt->getTitle(),'default'),
                'type' => 'file',
                'id' => $objectEnt->getId(),
            ));
        }
        $seoPage = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('url' => $path));
        if (empty($seoPage)) {
            return new JsonResponse(array('error' => 'Путь '.$path.' не найден'));
        }
        if (($seoPage->getContentType() == 'object.project') && isset($query['document'])) {
            // Найти первый документа
            $documentId = $em->createQuery('SELECT d.parentId FROM ExtendedProjectBundle:ProjectDocuments d WHERE d.id = :id')->setParameter('id', $query['document'])->getSingleScalarResult();
            // Проверить нет ли такого
            $count = $em->createQuery('SELECT count(o.id) FROM ExtendedKnowledgeBundle:KnowledgeObject o WHERE o.objectType = :type AND o.objectId = :id')
                        ->setParameter('type', \Extended\KnowledgeBundle\Entity\KnowledgeObject::KO_TYPE_DOCUMENT)
                        ->setParameter('id', $documentId)
                        ->getSingleScalarResult();
            if ($count != 0) {
                return new JsonResponse(array('error' => 'Документ уже добавлен в реестр'));
            }
            // Найти всю ветку документов
            $documents = $em->createQuery('SELECT d.id, d.title, d.createDate, d.createrId FROM ExtendedProjectBundle:ProjectDocuments d WHERE d.parentId = :id ORDER BY d.createDate')->setParameter('id', $documentId)->getResult();
            if (count($documents) < 1) {
                return new JsonResponse(array(
                    'error' => 'Не найдено документов',
                ));
            }
            $objectEnt = new \Extended\KnowledgeBundle\Entity\KnowledgeObject();
            $objectEnt->setCreateDate($documents[0]['createDate']);
            $objectEnt->setCreaterId($documents[0]['createrId']);
            $objectEnt->setObjectId($documentId);
            $objectEnt->setObjectType(\Extended\KnowledgeBundle\Entity\KnowledgeObject::KO_TYPE_DOCUMENT);
            $objectEnt->setRatingNegative(0);
            $objectEnt->setRatingPositive(0);
            $objectEnt->setTitle($this->container->get('cms.cmsManager')->encodeLocalString(array('default' => $documents[0]['title'])));
            $objectEnt->setDefaultTitle($documents[0]['title']);
            $em->persist($objectEnt);
            $em->flush();
            /*$linkEnt = new \Extended\KnowledgeBundle\Entity\KnowledgeTypeLink();
            $linkEnt->setItemId($objectEnt->getId());
            $linkEnt->setTypeId($id);
            $em->persist($linkEnt);
            $em->flush();*/
            $first = true;
            foreach ($documents as $document) {
                if ($first) {
                    $first = false;
                    continue;
                }
                $objectEnt->setTitle($this->container->get('cms.cmsManager')->encodeLocalString(array('default' => $document['title'])));
                $changeEnt = new \Extended\KnowledgeBundle\Entity\KnowledgeChanges();
                $changeEnt->setChangeType(\Extended\KnowledgeBundle\Entity\KnowledgeChanges::KC_CT_CONTENT);
                $changeEnt->setChangeValue(0);
                $changeEnt->setCreateDate($document['createDate']);
                $changeEnt->setCreaterId($document['createrId']);
                $changeEnt->setDescription('');
                $changeEnt->setObjectId($objectEnt->getId());
                $em->persist($changeEnt);
                $em->flush();
            }
            return new JsonResponse(array(
                'result' => 'Добавлен документ '.$this->container->get('cms.cmsManager')->decodeLocalString($objectEnt->getTitle(),'default'),
                'type' => 'document',
                'id' => $objectEnt->getId(),
            ));
        }
        
        return new JsonResponse(array(
            'error' => 'Неизвестный тип объекта',
        ));
    }
    
    public function getObjectAction($errors = array())
    {
        $em = $this->getDoctrine()->getEntityManager();
        $locales = $this->getDoctrine()->getEntityManager()->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l')->getResult();
        if ($this->getUser()->checkAccess('knowledge_list') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Браузер объектов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $id = $this->get('request_stack')->getMasterRequest()->get('id');
        
        $object = $em->createQuery('SELECT o.id, o.title, o.objectType, o.objectId, o.createrId, o.createDate, o.ratingPositive, o.ratingNegative, u.login, u.fullName, u.avatar FROM ExtendedKnowledgeBundle:KnowledgeObject o '.
                                   'LEFT JOIN BasicCmsBundle:Users u WITH u.id = o.createrId WHERE o.id = :id')->setParameter('id', $id)->getSingleResult();
        if (empty($object)) {
            return new \Symfony\Component\HttpFoundation\Response('');
        }
        $object['titleLocale'] = array();
        $object['titleLocale']['default'] = $this->container->get('cms.cmsManager')->decodeLocalString($object['title'], 'default', false);
        foreach ($locales as $localitem) {
            $object['titleLocale'][$localitem['shortName']] = $this->container->get('cms.cmsManager')->decodeLocalString($object['title'], $localitem['shortName'], false);
        }
        
        $changes = $em->createQuery('SELECT c.id, c.description, c.createDate, c.createrId, c.changeType, c.changeValue, u.login, u.fullName, u.avatar FROM ExtendedKnowledgeBundle:KnowledgeChanges c '.
                                    'LEFT JOIN BasicCmsBundle:Users u WITH u.id = c.createrId WHERE c.objectId = :id ORDER BY c.createDate DESC')->setParameter('id', $id)->getResult();
        
        $objectTypes = array();
        $objectTypes[\Extended\KnowledgeBundle\Entity\KnowledgeObject::KO_TYPE_DOCUMENT] = 'Документ мастерской';
        $objectTypes[\Extended\KnowledgeBundle\Entity\KnowledgeObject::KO_TYPE_FILE] = 'Файл мастерской';

        $changeTypes = array();
        $changeTypes[\Extended\KnowledgeBundle\Entity\KnowledgeChanges::KC_CT_CONTENT] = 'Изменение содержимого';
        
        $categories = array();
        $query = $em->createQuery('SELECT t.id, t.title, t.enabled, t.parent FROM ExtendedKnowledgeBundle:KnowledgeType t '.
                                  'ORDER BY t.ordering');
        $tree = $query->getResult();
        $this->typesGetTree($tree, null, 0, $categories);
        
        $links = $em->createQuery('SELECT l.typeId FROM ExtendedKnowledgeBundle:KnowledgeTypeLink l WHERE l.itemId = :id')->setParameter('id', $id)->getResult();
        $ids = array();
        foreach ($links as $link) {
            $ids[] = $link['typeId'];
        }
        foreach ($categories as &$category) {
            $category['linked'] = (in_array($category['id'], $ids) ? 1 : 0);
        }
        unset($category);
        
        return $this->render('ExtendedKnowledgeBundle:Default:object.html.twig', array(
            'object' => $object,
            'locales' => $locales,
            'changes' => $changes,
            'objectTypes' => $objectTypes,
            'changeTypes' => $changeTypes,
            'categories' => $categories,
            'errors' => $errors,
            'editPermission' => $this->getUser()->checkAccess('knowledge_editobject'),
        ));
    }
    
    public function editObjectAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $locales = $this->getDoctrine()->getEntityManager()->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l')->getResult();
        if (($this->getUser()->checkAccess('knowledge_list') == 0) || ($this->getUser()->checkAccess('knowledge_editobject') == 0))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Браузер объектов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $id = $this->get('request_stack')->getMasterRequest()->get('id');
        
        $objectEnt = $this->getDoctrine()->getRepository('ExtendedKnowledgeBundle:KnowledgeObject')->find($id);
        
        if (empty($objectEnt)) {
            return new \Symfony\Component\HttpFoundation\Response('');
        }
        
        $title = $this->get('request_stack')->getMasterRequest()->get('title');
        
        $object = array();
        $object['titleLocale'] = array();
        $object['titleLocale']['default'] = (isset($title['default']) ? $title['default'] : '');
        foreach ($locales as $localitem) {
            $object['titleLocale'][$localitem['shortName']] = (isset($title[$localitem['shortName']]) ? $title[$localitem['shortName']] : '');
        }
        $object['ratingPositive'] = $this->get('request_stack')->getMasterRequest()->get('ratingPositive');
        $object['ratingNegative'] = $this->get('request_stack')->getMasterRequest()->get('ratingNegative');
        $object['categories'] = $this->get('request_stack')->getMasterRequest()->get('categories');
        // Валидация
        $errors = array();
        if (!preg_match('/[\s\S]{3,}/ui', $object['titleLocale']['default'])) {
            $errors['title']['default'] = 'Должно быть задано название по умолчанию';
        }
        if (!preg_match('/[\d]{1,9}/ui', $object['ratingPositive'])) {
            $errors['ratingPositive'] = 'Должно быть задано число';
        }
        if (!preg_match('/[\d]{1,9}/ui', $object['ratingNegative'])) {
            $errors['ratingNegative'] = 'Должно быть задано число';
        }
        if (!is_array($object['categories']) || (count($object['categories']) == 0)) {
            $errors['categories'] = 'Должен быть выбран хотя бы один тип';
        } else {
            $count = $em->createQuery('SELECT count(t.id) FROM ExtendedKnowledgeBundle:KnowledgeType t WHERE t.id in (:ids)')->setParameter('ids', $object['categories'])->getSingleScalarResult();
            if ($count == 0) {
                $errors['categories'] = 'Должен быть выбран хотя бы один тип';
            }
        }
        // Сохранение
        if (count($errors) == 0) {
            $objectEnt->setRatingNegative($object['ratingNegative']);
            $objectEnt->setRatingPositive($object['ratingPositive']);
            $objectEnt->setTitle($this->container->get('cms.cmsManager')->encodeLocalString($object['titleLocale']));
            $objectEnt->setDefaultTitle($object['titleLocale']['default']);
            $em->flush();
            $links = $this->getDoctrine()->getRepository('ExtendedKnowledgeBundle:KnowledgeTypeLink')->findBy(array('itemId' => $id));
            $registredIds = array();
            foreach ($links as $link) {
                if (!in_array($link->getTypeId(), $object['categories'])) {
                    $em->remove($link);
                    $em->flush();
                } else {
                    $registredIds[] = $link->getTypeId();
                }
            }
            $newLinks = array_diff($object['categories'], $registredIds);
            foreach ($newLinks as $newId) {
                $linkEnt = new \Extended\KnowledgeBundle\Entity\KnowledgeTypeLink();
                $linkEnt->setItemId($objectEnt->getId());
                $linkEnt->setTypeId($newId);
                $em->persist($linkEnt);
                $em->flush();
            }
        }
        // Вывод
        return $this->getObjectAction($errors);
    }
    
    public function removeObjectAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $locales = $this->getDoctrine()->getEntityManager()->createQuery('SELECT l.shortName, l.fullName FROM BasicCmsBundle:Locales l')->getResult();
        if (($this->getUser()->checkAccess('knowledge_list') == 0) || ($this->getUser()->checkAccess('knowledge_editobject') == 0))
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Браузер объектов',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        
        $id = $this->get('request_stack')->getMasterRequest()->get('id');
        
        $objectEnt = $this->getDoctrine()->getRepository('ExtendedKnowledgeBundle:KnowledgeObject')->find($id);
        
        if (empty($objectEnt)) {
            return new JsonResponse(array('error' => 'Объект не найден'));
        }
        $links = $this->getDoctrine()->getRepository('ExtendedKnowledgeBundle:KnowledgeTypeLink')->findBy(array('itemId' => $id));
        foreach ($links as $link) {
            $em->remove($link);
        }
        $changes = $this->getDoctrine()->getRepository('ExtendedKnowledgeBundle:KnowledgeChanges')->findBy(array('objectId' => $id));
        foreach ($changes as $change) {
            $em->remove($change);
        }
        $em->remove($objectEnt);
        $em->flush();
        return new JsonResponse(array('result' => 'Объект успешно удален'));
    }        
    
    
}
