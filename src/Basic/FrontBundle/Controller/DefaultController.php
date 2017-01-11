<?php

namespace Basic\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    
    private function get_timezone_offset($remote_tz, $origin_tz = null) 
    {
        if($origin_tz === null) 
        {
            if(!is_string($origin_tz = date_default_timezone_get())) 
            {
                return false;
            }
        }
        $origin_dtz = new \DateTimeZone($origin_tz);
        $remote_dtz = new \DateTimeZone($remote_tz);
        $origin_dt = new \DateTime("now", $origin_dtz);
        $remote_dt = new \DateTime("now", $remote_dtz);
        $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
        return $offset;
    }    
    
    public function notFoundAction(Request $request, $locale, $result)
    {
        return $this->errorAction($request, $locale, 'Not found', $result);
    }

    public function errorAction(Request $request, $locale, $text, $result, $reqestSeoPage = null)
    {
        $cmsManager = $this->get('cms.cmsManager');
        $errorNumber = $cmsManager->getErrorPageNumber();
        // Считываем настроки страницы ошибок
        $layoutcfg = $cmsManager->getConfigValue('system.errorpage.layout');
        $templatecfg = $cmsManager->getConfigValue('system.errorpage.template');
        $modulescfg = $cmsManager->getConfigValue('system.errorpage.modules');
        // Подготовка переменных
        $seoPage = new \Basic\CmsBundle\Entity\SeoPage();
        $seoPage->setBreadCrumbs(serialize(array(array('id'=>0,'locale'=>'','url'=>'#','title'=>'Error'))));
        $seoPage->setContentAction('');
        $seoPage->setContentType('');
        $seoPage->setDescription('');
        $seoPage->setLocale('');
        $seoPage->setContentId(0);
        $seoPage->setModules('');
        $seoPage->setTemplate('');
        $seoPage->setUrl('');
        if ($reqestSeoPage != null)
        {
            $seoPage->setBreadCrumbs($reqestSeoPage->getBreadCrumbs());
        }
        // Заполнение контента позиций модулями
        $positions = array();
        $modulesid = @explode(',', $modulescfg);
        if (is_array($modulesid) && (count($modulesid) > 0))
        {
            $modules = $this->getDoctrine()->getEntityManager()->createQuery('SELECT m.id, m.objectName, m.moduleType, m.parameters, m.position, m.template FROM BasicCmsBundle:Modules m '.
                       'WHERE m.id in (:modulesid) AND m.layout = :layout AND m.enabled != 0 AND ((m.locale = \'\') OR (m.locale = :locale)) ORDER BY m.position, m.ordering')->
                       setParameter('modulesid', $modulesid)->setParameter('layout', $layoutcfg)->setParameter('locale', $locale)->getResult();
            foreach ($modules as $module)
            {
                $module['parameters'] = @unserialize($module['parameters']);
                $moduleobject = null;
                if ($this->has($module['objectName'])) $moduleobject = $this->get($module['objectName']);
                if ($moduleobject != null) $moduleparameters = $moduleobject->getFrontModule($locale, $seoPage, $module['moduleType'], $module['parameters'], $result);
                if ($moduleparameters != null) 
                {
                    $moduleparameters['actionResult'] = null;
                    $moduleparameters['currentUser'] = $cmsManager->getCurrentUser();
                    $moduleparameters['currentLocale'] = $locale;
                    $moduleparameters['currentSeoPage'] = $seoPage;
                    $moduleparameters['cmsManager'] = $cmsManager;
                    $modulecontent = $this->render($cmsManager->getModuleTemplateTwig($module['objectName'], $module['moduleType'], $module['template'], $locale),$moduleparameters);
                    if (!isset($positions[$module['position']])) $positions[$module['position']] = '';
                    $positions[$module['position']] .= $modulecontent->getContent();
                    unset($modulecontent);
                }
                unset($moduleobject);
                unset($moduleparameters);
            }
            unset($modules);
        }
        unset($modulesid);
        // Заполнение основного контента
        $parameters = array();
        $parameters['errorNumber'] = $errorNumber;
        $parameters['locale'] = $locale;
        $parameters['currentUser'] = $cmsManager->getCurrentUser();
        $parameters['currentLocale'] = $locale;
        $parameters['currentSeoPage'] = $seoPage;
        $parameters['requestSeoPage'] = $reqestSeoPage;
        $parameters['doctrine'] = $this->getDoctrine();
        $parameters['cmsManager'] = $cmsManager;
        $content = $this->render($cmsManager->getErrorTemplateTwig($templatecfg, $locale),array_merge($parameters, array('positions' => $positions))); 
        $positions['content'] = $content->getContent();
        $positions['meta'] = array('title'=>'Error', 'keywords'=>'', 'description'=>'','robotindex'=>false);
        $positions['currentUser'] = $cmsManager->getCurrentUser();
        $positions['currentLocale'] = $locale;
        $positions['currentSeoPage'] = $seoPage;
        $positions['cmsManager'] = $cmsManager;
        // Вывод в поток
        if ($request->get('ajax') == 'position')
        {
            if (($request->get('ajaxposition') != null) && isset($positions[$request->get('ajaxposition')])) $response = new \Symfony\Component\HttpFoundation\Response($positions[$request->get('ajaxposition')]); 
            else $response = new \Symfony\Component\HttpFoundation\Response('');
            $cookies = $cmsManager->getCookies();
            foreach ($cookies as $cookie) $response->headers->setCookie($cookie);
            return $response;
        }
        $response = $this->render($cmsManager->getLayoutTwig($layoutcfg, $locale), $positions);
        $cookies = $cmsManager->getCookies();
        foreach ($cookies as $cookie) $response->headers->setCookie($cookie);
        return $response;
}
    
    public function indexAction(Request $request, $url = 'index.html', $locale = '')
    {
        if ($locale == '')
        {
            $locales = $this->getDoctrine()->getEntityManager()->createQuery('SELECT l.shortName FROM BasicCmsBundle:Locales l')->getResult();
            if (is_array($locales))
            {
                $localefinaly = false;
                if (($request->get('actionobject') == 'module') && ($request->get('action') == 'changelocale'))
                {
                    if ($request->get('actionlocale') == 'default') {$locale = ''; $localefinaly = true;} else
                    {
                        foreach ($locales as $localitem)
                        {
                            if ($request->get('actionlocale') == $localitem['shortName']) {$locale = $localitem['shortName']; $localefinaly = true; break;}
                        }
                    }
                }
                foreach ($locales as $localitem)
                {
                    if (strpos($url, $localitem['shortName'].'/') === 0) {if ($localefinaly == false) $locale = $localitem['shortName']; $localefinaly = true; $url = substr($url, strlen($localitem['shortName']) + 1); break;}
                }
                if ($localefinaly == false)
                {
                    if ($request->cookies->get('front_locale_current') == 'default') {$locale = ''; $localefinaly = true;} else
                    {
                        foreach ($locales as $localitem)
                        {
                            if ($localitem['shortName'] === $request->cookies->get('front_locale_current')) {$locale = $localitem['shortName']; break;}
                        }
                    }
                }
                if ($localefinaly == false)
                {
                    foreach ($locales as $localitem)
                    {
                        if ($localitem['shortName'] === substr($request->headers->get('Accept-Language'), 0, 2)) {$locale = $localitem['shortName']; $localefinaly = true; break;}
                    }
                }
            }
        }
        $cmsManager = $this->get('cms.cmsManager');
        // Загрузить текущего пользователя
        $userid = $request->getSession()->get('front_system_autorized');
        $userEntity = null;
        if ($userid != null) $userEntity = $this->getDoctrine()->getRepository('BasicCmsBundle:Users')->find($userid);
        if (empty($userEntity))
        {
            $userid = $request->cookies->get('front_autorization_keytwo');
            $userpass = $request->cookies->get('front_autorization_keyone');
            $query = $this->getDoctrine()->getEntityManager()->createQuery('SELECT u FROM BasicCmsBundle:Users u WHERE MD5(CONCAT(u.id,\'Embedded.CMS\')) = :userid AND MD5(CONCAT(u.password, u.salt)) = :userpass')->setParameter('userid', $userid)->setParameter('userpass', $userpass);
            $userEntity = $query->getResult();
            if ((count($userEntity) == 1) && (isset($userEntity[0])) && (!empty($userEntity[0]))) $userEntity = $userEntity[0];
        }
        if (!empty($userEntity))
        {
            $nowtime = new \DateTime('now');
            if ($userEntity->getVisitDate() == null) $userEntity->setVisitDate($nowtime); 
            if ($userEntity->getPrevVisitDate() == null) $userEntity->setPrevVisitDate($nowtime);
            if (($nowtime->getTimestamp() - $userEntity->getVisitDate()->getTimestamp()) > 15*60) $userEntity->setPrevVisitDate($userEntity->getVisitDate());
            $userEntity->setVisitDate($nowtime);
            $this->getDoctrine()->getEntityManager()->flush();
            $cmsManager->setCurrentUser($userEntity);
            $request->getSession()->set('front_system_autorized', $userEntity->getId());
            unset($nowtime);
        }
        // Найти страницу по url
        $seoPage = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoPage')->findOneBy(array('url'=>$url));
        if (empty($seoPage) || (($seoPage->getLocale() != '') && ($seoPage->getLocale() != $locale))) return $this->notFoundAction ($locale, null);
        // Опросить экшены модулей (если экшен установлен)
        $result = null;
        if (($request->get('actionobject') != null) && ($request->get('action') != null))
        {
            if ($this->has('object.'.$request->get('actionobject')))
            {
                $result = $this->get('object.'.$request->get('actionobject'))->setFrontAction($locale, $seoPage, $request->get('action'));
            }
        }
        $userEntity = $cmsManager->getCurrentUser();
        // Проверка доступа к странице
        if (($seoPage->getAccess() != '') && (is_array(explode(',', $seoPage->getAccess()))) && ((empty($userEntity)) || ($userEntity->getBlocked() != 2) || ($userEntity->getRole() != null) || (!in_array($userEntity->getRole()->getId(), explode(',', $seoPage->getAccess())))))
        {
            $cmsManager->setErrorPageNumber(1);
            return $this->errorAction ($locale, 'Access denied', $result, $seoPage);
        }
        // Заполнение расширенной статистики
        if (($request->getSession()->get('front_system_visitflash') != 1) && (strpos(strtolower($request->headers->get('User-Agent')), 'bot') === false))
        {
            $request->getSession()->set('front_system_visitflash', 1);
            $seoStatUniq = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoStatistic')->findOneBy(array('pageId'=>0));
            if (empty($seoStatUniq))
            {
                $seoStatUniq = new \Basic\CmsBundle\Entity\SeoStatistic();
                $seoStatUniq->setAllVisits(0);
                $seoStatUniq->setDay1(0);$seoStatUniq->setDay2(0);$seoStatUniq->setDay3(0);$seoStatUniq->setDay4(0);$seoStatUniq->setDay5(0);$seoStatUniq->setDay6(0);
                $seoStatUniq->setDay7(0);$seoStatUniq->setDay8(0);$seoStatUniq->setDay9(0);$seoStatUniq->setDay10(0);$seoStatUniq->setDay11(0);$seoStatUniq->setDay12(0);
                $seoStatUniq->setMonth1(0);$seoStatUniq->setMonth2(0);$seoStatUniq->setMonth3(0);$seoStatUniq->setMonth4(0);$seoStatUniq->setMonth5(0);$seoStatUniq->setMonth6(0);
                $seoStatUniq->setMonth7(0);$seoStatUniq->setMonth8(0);$seoStatUniq->setMonth9(0);$seoStatUniq->setMonth10(0);$seoStatUniq->setMonth11(0);$seoStatUniq->setMonth12(0);
                $seoStatUniq->setWeek1(0);$seoStatUniq->setWeek2(0);$seoStatUniq->setWeek3(0);$seoStatUniq->setWeek4(0);$seoStatUniq->setWeek5(0);$seoStatUniq->setWeek6(0);
                $seoStatUniq->setWeek7(0);$seoStatUniq->setWeek8(0);$seoStatUniq->setWeek9(0);$seoStatUniq->setWeek10(0);$seoStatUniq->setWeek11(0);$seoStatUniq->setWeek12(0);
                $seoStatUniq->setVisitDate(new \DateTime('now'));
                $seoStatUniq->setPageId(0);
                $this->getDoctrine()->getEntityManager()->persist($seoStatUniq);
            }
            $statDate = $seoStatUniq->getVisitDate();
            $currentDate = new \DateTime('now');
            $offset = $this->get_timezone_offset('UTC');
            // Количество дней
            $count = intval(floor(($currentDate->getTimestamp() + $offset) / 86400) - floor(($statDate->getTimestamp() + $offset) / 86400));
            if ($count > 0)
            {
                for ($i = 0; $i < 12; $i++)
                {
                    $setMethod = 'setDay'.(12 - $i);
                    if ((12 - $count - $i) > 0) $getMethod = 'getDay'.(12 - $count - $i); else $getMethod = null;
                    if (method_exists($seoStatUniq, $setMethod)) $seoStatUniq->$setMethod((method_exists($seoStatUniq, $getMethod) ?  $seoStatUniq->$getMethod() : 0));
                }
            }
            // Количество недель
            $count = intval(floor(($currentDate->getTimestamp() + $offset + 3 * 86400) / (7 * 86400)) - floor(($statDate->getTimestamp() + $offset + 3 * 86400) / (7 * 86400)));
            if ($count > 0)
            {
                for ($i = 0; $i < 12; $i++)
                {
                    $setMethod = 'setWeek'.(12 - $i);
                    if ((12 - $count - $i) > 0) $getMethod = 'getWeek'.(12 - $count - $i); else $getMethod = null;
                    if (method_exists($seoStatUniq, $setMethod)) $seoStatUniq->$setMethod((method_exists($seoStatUniq, $getMethod) ?  $seoStatUniq->$getMethod() : 0));
                }
            }
            // Количество месяцев
            $count = (intval($currentDate->format('Y')) * 12 + intval($currentDate->format('m'))) - (intval($statDate->format('Y')) * 12 + intval($statDate->format('m')));
            if ($count > 0)
            {
                for ($i = 0; $i < 12; $i++)
                {
                    $setMethod = 'setMonth'.(12 - $i);
                    if ((12 - $count - $i) > 0) $getMethod = 'getMonth'.(12 - $count - $i); else $getMethod = null;
                    if (method_exists($seoStatUniq, $setMethod)) $seoStatUniq->$setMethod((method_exists($seoStatUniq, $getMethod) ?  $seoStatUniq->$getMethod() : 0));
                }
            }
            $seoStatUniq->setAllVisits($seoStatUniq->getAllVisits() + 1);
            $seoStatUniq->setDay1($seoStatUniq->getDay1() + 1);
            $seoStatUniq->setWeek1($seoStatUniq->getWeek1() + 1);
            $seoStatUniq->setMonth1($seoStatUniq->getMonth1() + 1);
            $seoStatUniq->setVisitDate($currentDate);
            $this->getDoctrine()->getEntityManager()->flush();
            unset($seoStatUniq);
        }
        $seoStat = $this->getDoctrine()->getRepository('BasicCmsBundle:SeoStatistic')->findOneBy(array('pageId'=>$seoPage->getId()));
        if (empty($seoStat))
        {
            $seoStat = new \Basic\CmsBundle\Entity\SeoStatistic();
            $seoStat->setAllVisits(0);
            $seoStat->setDay1(0);$seoStat->setDay2(0);$seoStat->setDay3(0);$seoStat->setDay4(0);$seoStat->setDay5(0);$seoStat->setDay6(0);
            $seoStat->setDay7(0);$seoStat->setDay8(0);$seoStat->setDay9(0);$seoStat->setDay10(0);$seoStat->setDay11(0);$seoStat->setDay12(0);
            $seoStat->setMonth1(0);$seoStat->setMonth2(0);$seoStat->setMonth3(0);$seoStat->setMonth4(0);$seoStat->setMonth5(0);$seoStat->setMonth6(0);
            $seoStat->setMonth7(0);$seoStat->setMonth8(0);$seoStat->setMonth9(0);$seoStat->setMonth10(0);$seoStat->setMonth11(0);$seoStat->setMonth12(0);
            $seoStat->setWeek1(0);$seoStat->setWeek2(0);$seoStat->setWeek3(0);$seoStat->setWeek4(0);$seoStat->setWeek5(0);$seoStat->setWeek6(0);
            $seoStat->setWeek7(0);$seoStat->setWeek8(0);$seoStat->setWeek9(0);$seoStat->setWeek10(0);$seoStat->setWeek11(0);$seoStat->setWeek12(0);
            $seoStat->setVisitDate(new \DateTime('now'));
            $seoStat->setPageId($seoPage->getId());
            $this->getDoctrine()->getEntityManager()->persist($seoStat);
        }
        $statDate = $seoStat->getVisitDate();
        $currentDate = new \DateTime('now');
        $offset = $this->get_timezone_offset('UTC');
        // Количество дней
        $count = intval(floor(($currentDate->getTimestamp() + $offset) / 86400) - floor(($statDate->getTimestamp() + $offset) / 86400));
        if ($count > 0)
        {
            for ($i = 0; $i < 12; $i++)
            {
                $setMethod = 'setDay'.(12 - $i);
                if ((12 - $count - $i) > 0) $getMethod = 'getDay'.(12 - $count - $i); else $getMethod = null;
                if (method_exists($seoStat, $setMethod)) $seoStat->$setMethod((method_exists($seoStat, $getMethod) ?  $seoStat->$getMethod() : 0));
            }
        }
        // Количество недель
        $count = intval(floor(($currentDate->getTimestamp() + $offset + 3 * 86400) / (7 * 86400)) - floor(($statDate->getTimestamp() + $offset + 3 * 86400) / (7 * 86400)));
        if ($count > 0)
        {
            for ($i = 0; $i < 12; $i++)
            {
                $setMethod = 'setWeek'.(12 - $i);
                if ((12 - $count - $i) > 0) $getMethod = 'getWeek'.(12 - $count - $i); else $getMethod = null;
                if (method_exists($seoStat, $setMethod)) $seoStat->$setMethod((method_exists($seoStat, $getMethod) ?  $seoStat->$getMethod() : 0));
            }
        }
        // Количество месяцев
        $count = (intval($currentDate->format('Y')) * 12 + intval($currentDate->format('m'))) - (intval($statDate->format('Y')) * 12 + intval($statDate->format('m')));
        if ($count > 0)
        {
            for ($i = 0; $i < 12; $i++)
            {
                $setMethod = 'setMonth'.(12 - $i);
                if ((12 - $count - $i) > 0) $getMethod = 'getMonth'.(12 - $count - $i); else $getMethod = null;
                if (method_exists($seoStat, $setMethod)) $seoStat->$setMethod((method_exists($seoStat, $getMethod) ?  $seoStat->$getMethod() : 0));
            }
        }
        $seoStat->setAllVisits($seoStat->getAllVisits() + 1);
        $seoStat->setDay1($seoStat->getDay1() + 1);
        $seoStat->setWeek1($seoStat->getWeek1() + 1);
        $seoStat->setMonth1($seoStat->getMonth1() + 1);
        $seoStat->setVisitDate($currentDate);
        $this->getDoctrine()->getEntityManager()->flush();
        unset($seoStat);
        // Обработка Ajax загрузки
        if ($request->get('ajax') == 'result')
        {
            $response = new \Symfony\Component\HttpFoundation\Response(json_encode($result), 200, array('Content-type' => 'application-json; charset=utf8'));
            $cookies = $cmsManager->getCookies();
            foreach ($cookies as $cookie) $response->headers->setCookie($cookie);
            return $response;
        }
        // Заполнение контента позиций
        $positions = array();
        // Взять данные модулей и пропустить через шаблон в позиции
        $modulesid = @explode(',', $seoPage->getModules());
        if (is_array($modulesid) && (count($modulesid) > 0))
        {
            $modules = $this->getDoctrine()->getEntityManager()->createQuery('SELECT m.id, m.objectName, m.moduleType, m.parameters, m.position, m.template, m.access FROM BasicCmsBundle:Modules m '.
                       'WHERE m.id in (:modulesid) AND m.layout = :layout AND m.enabled != 0 AND ((m.locale = \'\') OR (m.locale = :locale)) ORDER BY m.position, m.ordering')->
                       setParameter('modulesid', $modulesid)->setParameter('layout', $seoPage->getTemplate())->setParameter('locale', $locale)->getResult();
            foreach ($modules as $module)
            {
                if (($module['access'] == '') || (!is_array(explode(',', $module['access']))) || ((!empty($userEntity)) && ($userEntity->getBlocked() == 2) && ($userEntity->getRole() != null) && (in_array($userEntity->getRole()->getId(), explode(',', $module['access'])))))
                {
                    $module['parameters'] = @unserialize($module['parameters']);
                    $moduleobject = null;
                    if ($this->has($module['objectName'])) $moduleobject = $this->get($module['objectName']);
                    if ($moduleobject != null) $moduleparameters = $moduleobject->getFrontModule($locale, $seoPage, $module['moduleType'], $module['parameters'], $result);
                    if ($moduleparameters != null) 
                    {
                        $moduleparameters['actionResult'] = $result;
                        $moduleparameters['currentUser'] = $userEntity;
                        $moduleparameters['currentLocale'] = $locale;
                        $moduleparameters['currentSeoPage'] = $seoPage;
                        $moduleparameters['cmsManager'] = $cmsManager;
                        $modulecontent = $this->render($cmsManager->getModuleTemplateTwig($module['objectName'], $module['moduleType'], $module['template'], $locale),$moduleparameters);
                        if (!isset($positions[$module['position']])) $positions[$module['position']] = '';
                        $positions[$module['position']] .= $modulecontent->getContent();
                        unset($modulecontent);
                    }
                    unset($moduleobject);
                    unset($moduleparameters);
                }
            }
            unset($modules);
        }
        unset($modulesid);
        // Взять данные контента и пропустить через шаблон
        $object = null;
        if ($this->has($seoPage->getContentType())) $object = $this->get($seoPage->getContentType());
        if ($object !== null) $parameters = $object->getFrontContent($locale, $seoPage->getContentAction(), $seoPage->getContentId(), $result); else return $this->errorAction ($locale, 'Обработчик контента не найден', $result, $seoPage);
        if ($parameters == null) return $this->errorAction ($locale, 'Контент не найден', $result, $seoPage);
        $parameters['actionResult'] = $result;
        $parameters['currentUser'] = $userEntity;
        $parameters['currentLocale'] = $locale;
        $parameters['currentSeoPage'] = $seoPage;
        $parameters['cmsManager'] = $cmsManager;
        if (isset($parameters['template'])) $content = $this->render($cmsManager->getTemplateTwig($seoPage->getContentType(), $seoPage->getContentAction(), $parameters['template'], $locale),array_merge($parameters, array('positions' => $positions))); 
                                       elseif (isset($parameters['rawContent'])) $content = $parameters['rawContent']; 
                                       else return $this->errorAction ($locale, 'Контент не найден', $result, $seoPage);
        $positions['content'] = (is_object($content) ? $content->getContent() : $content);
        $positions['meta'] = $parameters['meta'];
        $positions['currentUser'] = $userEntity;
        $positions['currentLocale'] = $locale;
        $positions['currentSeoPage'] = $seoPage;
        $positions['cmsManager'] = $cmsManager;
        unset($content);
        unset($parameters);
        // Вывести в лэйаут
        if ($request->get('ajax') == 'position')
        {
            if (($request->get('ajaxposition') != null) && isset($positions[$request->get('ajaxposition')])) $response = new \Symfony\Component\HttpFoundation\Response($positions[$request->get('ajaxposition')]); else $response = new \Symfony\Component\HttpFoundation\Response('');
            $cookies = $cmsManager->getCookies();
            foreach ($cookies as $cookie) $response->headers->setCookie($cookie);
            return $response;
        }
        $response = $this->render($cmsManager->getLayoutTwig($seoPage->getTemplate(), $locale), $positions);
        $cookies = $cmsManager->getCookies();
        foreach ($cookies as $cookie) $response->headers->setCookie($cookie);
        return $response;
    }
}
