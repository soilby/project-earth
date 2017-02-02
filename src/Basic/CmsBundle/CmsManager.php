<?php

namespace Basic\CmsBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class CmsManager
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }
    // Меню админки
    private $adminMenu = array();
    // Список ролей пользователей
    private $roleList = array();
    
    public function addAdminMenu($item, $url, $ordering, $access, $group = null)
    {
        if ($group !== null)
        {
            $newitem = array();
            $newitem['name'] = $item;
            $newitem['url'] = $url;
            $newitem['ordering'] = $ordering;
            $newitem['enabled'] = 1;
            $newitem['access'] = $access;
            $needkey = null;
            foreach ($this->adminMenu as $key=>$val)
            {
                if ($val['name'] == $group) {$needkey = $key; break;}
            }
            if ($needkey === null)
            {
                $groupitem = array();
                $groupitem['name'] = $group;
                $groupitem['url'] = '';
                $groupitem['ordering'] = 0;
                $groupitem['enabled'] = 0;
                $groupitem['access'] = 0;
                $groupitem['items'] = array($newitem);
                $this->adminMenu[] = $groupitem;
            } else
            {
                $this->adminMenu[$needkey]['items'][] = $newitem;
            }
        } else
        {
            $needkey = null;
            foreach ($this->adminMenu as $key=>$val)
            {
                if ($val['name'] == $item) {$needkey = $key; break;}
            }
            if ($needkey === null)
            {
                $newitem = array();
                $newitem['name'] = $item;
                $newitem['url'] = $url;
                $newitem['ordering'] = $ordering;
                $newitem['enabled'] = 1;
                $newitem['access'] = $access;
                $this->adminMenu[] = $newitem;
            } else
            {
                $this->adminMenu[$needkey]['name'] = $item;
                $this->adminMenu[$needkey]['url'] = $url;
                $this->adminMenu[$needkey]['ordering'] = $ordering;
                $this->adminMenu[$needkey]['enabled'] = 1;
                $this->adminMenu[$needkey]['access'] = $access;
            }
        }
    }

    private function cmp_array_menu($a, $b)
    {
        $i = 0;
        if ($a['ordering'] != $b['ordering']) $i = ($a['ordering'] < $b['ordering']) ? -1 : 1;
        return $i;
    }
    
    public function getAdminMenu()
    {
        $cmsservices = $this->container->getServiceIds();
        $this->adminMenu = array();
        foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) $this->container->get($item)->registerMenu();
        foreach ($cmsservices as $item) if (strpos($item,'template.') === 0) $this->container->get($item)->registerMenu();
        $menu = $this->adminMenu;
        usort($menu, array($this,'cmp_array_menu'));
        foreach ($menu as &$val)
        {
            if (isset($val['items'])) usort($val['items'], array($this,'cmp_array_menu'));
        }
        return $menu;
    }
    
    public function addRole($rolename, $descr)
    {
        $this->roleList[$rolename] = $descr;
    }
    
    public function getRoleList()
    {
        $cmsservices = $this->container->getServiceIds();
        $this->roleList = array();
        foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) $this->container->get($item)->registerRoles();
        foreach ($cmsservices as $item) if (strpos($item,'template.') === 0) $this->container->get($item)->registerRoles();
        return $this->roleList;
    }

    public function getRoleListByObjects()
    {
        $roles = array();
        $cmsservices = $this->container->getServiceIds();
        $this->roleList = array();
        foreach ($cmsservices as $item) if (strpos($item,'object.') === 0) 
        {
            $rolesitem = array();
            $rolesitem['name'] = $item;
            $rolesitem['description'] = $this->container->get($item)->getDescription();
            $this->roleList = array();
            $this->container->get($item)->registerRoles();
            $rolesitem['roles'] = $this->roleList;
            $roles[] = $rolesitem;
        }
        foreach ($cmsservices as $item) if (strpos($item,'template.') === 0) 
        {
            $rolesitem = array();
            $rolesitem['name'] = $item;
            $rolesitem['description'] = $this->container->get($item)->getDescription();
            $this->roleList = array();
            $this->container->get($item)->registerRoles();
            $rolesitem['roles'] = $this->roleList;
            $roles[] = $rolesitem;
        }
        $this->roleList = array();
        return $roles;
    }
    
    public function getLayoutList()
    {
        $layouts = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'template.') === 0) $this->container->get($item)->registerLayout($layouts);
        return $layouts;
    }
    
    public function getTemplateList($target, $contentType)
    {
        $templates = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'template.') === 0) $this->container->get($item)->registerTemplate($target, $contentType, $templates);
        return $templates;
    }

    public function getErrorTemplateList()
    {
        $templates = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'template.') === 0) $this->container->get($item)->registerErrorTemplate($templates);
        return $templates;
    }
    
    public function getLayoutTwig($layout, $locale)
    {
        $twig = null;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'template.') === 0) 
        {
            $twig = $this->container->get($item)->getLayoutTwig($layout, $locale);
            if ($twig != null) break;
        }
        if ($twig == null) $twig = 'BasicCmsBundle:Front:layout.html.twig';
        return $twig;
    }
    public function getTemplateTwig($target, $contentType, $template, $locale)
    {
        $twig = null;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'template.') === 0) 
        {
            $twig = $this->container->get($item)->getTemplateTwig($target, $contentType, $template, $locale);
            if ($twig != null) break;
        }
        if ($twig == null) 
        {
            $twig = $this->container->get($target)->getTemplateTwig($contentType, $locale);
        }
        return $twig;
    }
    public function getErrorTemplateTwig($template, $locale)
    {
        $twig = null;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'template.') === 0) 
        {
            $twig = $this->container->get($item)->getErrorTemplateTwig($template, $locale);
            if ($twig != null) break;
        }
        if ($twig == null) $twig = 'BasicCmsBundle:Front:errorpage.html.twig';
        return $twig;
    }
    
    public function getPositionList($layout)
    {
        $positions = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'template.') === 0) $this->container->get($item)->registerPosition($layout, $positions);
        return $positions;
    }
    
    public function getModuleTemplateList($target, $contentType)
    {
        $templates = array();
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'template.') === 0) $this->container->get($item)->registerModuleTemplate($target, $contentType, $templates);
        return $templates;
    }
    
    public function getModuleTemplateTwig($target, $contentType, $template, $locale)
    {
        $twig = null;
        $cmsservices = $this->container->getServiceIds();
        foreach ($cmsservices as $item) if (strpos($item,'template.') === 0) 
        {
            $twig = $this->container->get($item)->getModuleTemplateTwig($target, $contentType, $template, $locale);
            if ($twig != null) break;
        }
        if ($twig == null) 
        {
            $twig = $this->container->get($target)->getModuleTemplateTwig($contentType, $locale);
        }
        return $twig;
    }
    
    public function decodeLocalString($string, $locale, $useDefault = true)
    {
        $var = @unserialize($string);
        if (is_array($var))
        {
            if (isset($var[$locale])) return $var[$locale];
            if (($useDefault == true) && (isset($var['default']))) return $var['default'];
            return '';
        }
        if (($useDefault == true) || ($locale == 'default')) return $string;
        return '';
    }
    
    public function encodeLocalString($var)
    {
        if (is_array($var)) 
        {
            foreach ($var as $key=>$item)
            {
                if (($item == '') && ($key != 'default')) unset($var[$key]);
            }
            $string = serialize($var); 
        } elseif (is_string($var)) $string = $var; else $string = '';
        return $string;        
    }
    
    public function getBreadCrumbs($seoPage, $locale)
    {
        $path = @unserialize($seoPage->getBreadCrumbs());
        if (($seoPage->getBreadCrumbs() == '') || (!is_array($path)))
        {
            $em = $this->container->get('doctrine')->getEntityManager();
            $path = array();
            if ($this->container->has($seoPage->getContentType())) $path[] = array('id'=>$seoPage->getId(), 'locale'=>$seoPage->getLocale(), 'url'=>$seoPage->getUrl(), 'title'=>$this->container->get($seoPage->getContentType())->getInfoTitle($seoPage->getContentAction(), $seoPage->getContentId()));
            // если объект таксономируемый
            if (($seoPage->getContentType() != 'object.taxonomy') && ($this->container->has($seoPage->getContentType())) && ($this->container->has('object.taxonomy')) && ($this->container->get($seoPage->getContentType())->getTaxonomyType() == true) && (($seoPage->getContentAction() == 'view') || ($seoPage->getContentAction() == 'edit')))
            {
                $query = $em->createQuery('SELECT t.id, t.firstParent FROM BasicCmsBundle:TaxonomiesLinks tl '.
                                          'LEFT JOIN BasicCmsBundle:Taxonomies t WITH t.id = tl.taxonomyId '.
                                          'WHERE t.object = :object AND tl.itemId = :id')->setParameter('object', $seoPage->getContentType())->setParameter('id', $seoPage->getContentId());
                $taxcat = $query->getResult();
                if (isset($taxcat[0]['id']))
                {
                    $query = $em->createQuery('SELECT t.id, t.parent, t.enabled, s.id as seoid, s.url as seourl, s.locale as seolocale FROM BasicCmsBundle:Taxonomies t '.
                                              'LEFT JOIN BasicCmsBundle:SeoPage s WITH s.contentType = \'object.taxonomy\' AND s.contentAction = \'view\' AND s.contentId = t.id '.
                                              'WHERE t.firstParent = :parent')->setParameter('parent', $taxcat[0]['firstParent']);
                    $taxcats = $query->getResult();
                    if (is_array($taxcats))
                    {
                        $findid = $taxcat[0]['id'];
                        while ($findid != null)
                        {
                            $find = false;
                            foreach ($taxcats as $cat)
                            {
                                if ($cat['id'] == $findid)
                                {
                                    $find = true;
                                    $findid = $cat['parent'];
                                    if (($cat['enabled'] != 0) && ($cat['seoid'] != null)) $path[] = array('id'=>$cat['seoid'], 'locale'=>$cat['seolocale'], 'url'=>$cat['seourl'], 'title'=>$this->container->get('object.taxonomy')->getInfoTitle('view', $cat['id']));
                                }
                            }
                            if ($find == false) $findid = null;
                        }
                    }
                }
            }
            // если объект - категория таксономии
            if (($seoPage->getContentType() == 'object.taxonomy') && ($this->container->has('object.taxonomy')) && (($seoPage->getContentAction() == 'view') || ($seoPage->getContentAction() == 'edit')))
            {
                $query = $em->createQuery('SELECT t.id, t.firstParent, t.parent FROM BasicCmsBundle:Taxonomies t '.
                                          'WHERE t.id = :id')->setParameter('id', $seoPage->getContentId());
                $taxcat = $query->getResult();
                if (isset($taxcat[0]['id']))
                {
                    $query = $em->createQuery('SELECT t.id, t.parent, t.enabled, s.id as seoid, s.url as seourl, s.locale as seolocale FROM BasicCmsBundle:Taxonomies t '.
                                              'LEFT JOIN BasicCmsBundle:SeoPage s WITH s.contentType = \'object.taxonomy\' AND s.contentAction = \'view\' AND s.contentId = t.id '.
                                              'WHERE t.firstParent = :parent')->setParameter('parent', $taxcat[0]['firstParent']);
                    $taxcats = $query->getResult();
                    if (is_array($taxcats))
                    {
                        $findid = $taxcat[0]['parent'];
                        while ($findid != null)
                        {
                            $find = false;
                            foreach ($taxcats as $cat)
                            {
                                if ($cat['id'] == $findid)
                                {
                                    $find = true;
                                    $findid = $cat['parent'];
                                    if (($cat['enabled'] != 0) && ($cat['seoid'] != null)) $path[] = array('id'=>$cat['seoid'], 'locale'=>$cat['seolocale'], 'url'=>$cat['seourl'], 'title'=>$this->container->get('object.taxonomy')->getInfoTitle('view', $cat['id']));
                                }
                            }
                            if ($find == false) $findid = null;
                        }
                    }
                }
            }
            // заменяем заданные пути
            $seoids = array();
            foreach ($path as $pathitem) $seoids[] = $pathitem['id'];
            if (count($seoids) > 0)
            {
                $query = $em->createQuery('SELECT p.seoPageId, p.path FROM BasicCmsBundle:BreadCrumbPaths p WHERE p.seoPageId IN (:ids)')->setParameter('ids', $seoids);
                $replacepaths = $query->getResult();
            } else $replacepaths = array();
            if (is_array($replacepaths) && (count($replacepaths) > 0))
            {
                foreach ($seoids as $seoid)
                    foreach ($replacepaths as $rp)
                        if ($rp['seoPageId'] == $seoid)
                        {
                            $oldpath = $path;
                            $path = array();
                            foreach ($oldpath as $pathitem) 
                            {
                                $path[] = $pathitem;
                                if ($pathitem['id'] == $seoid) break;
                            }
                            $replacepath = explode(',', $rp['path']);
                            if (!is_array($replacepath)) $replacepath = array($replacepath);
                            $replacepath = array_reverse ($replacepath);
                            $query = $em->createQuery('SELECT s.id, s.contentType, s.contentAction, s.contentId, s.url, s.locale FROM BasicCmsBundle:SeoPage s WHERE s.id IN (:ids)')->setParameter('ids', $replacepath);
                            $seopages = $query->getResult();
                            if (is_array($seopages))
                            {
                                foreach ($replacepath as $repitem)
                                    foreach ($seopages as $page)
                                        if ($page['id'] == $repitem)
                                        {
                                            if ($this->container->has($page['contentType'])) $path[] = array('id'=>$page['id'], 'locale'=>$page['locale'], 'url'=>$page['url'], 'title'=>$this->container->get($page['contentType'])->getInfoTitle($page['contentAction'], $page['contentId']));
                                        }
                            }
                            break 2;
                        }
            }
            // сохраняем инфу
            $seoids = array();
            foreach ($path as $pathitem) $seoids[] = $pathitem['id'];
            $seoPage->setBreadCrumbs(serialize($path));
            $seoPage->setBreadCrumbsRel(implode(',', $seoids));
            if ($seoPage->getUrl() != '') $em->flush();
        }
        $newpath = array();
        foreach ($path as $pathitem) 
            if (($pathitem['locale'] == '') || ($locale == $pathitem['locale']))
            {
                $pathitem['title'] = $this->container->get('cms.cmsManager')->decodeLocalString($pathitem['title'], $locale, true);
                if (count($newpath) == 0) $pathitem['active'] = 1; else $pathitem['active'] = 0;
                $newpath[] = $pathitem;
            }
        $newpath = array_reverse ($newpath);
        return $newpath;
    }
    
    public function updateBreadCrumbs($seoId)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        $query = $em->createQuery('UPDATE BasicCmsBundle:SeoPage s SET s.breadCrumbs = \'\', s.breadCrumbsRel = \'\' WHERE LOCATE(:pageid,CONCAT(\',\',CONCAT(s.breadCrumbsRel,\',\'))) != 0')->setParameter('pageid', $seoId);
        $query->execute();
    }
    
     // ***************************************
     // Функции настроек
     // ***************************************
    
    private $valuesTemp = array();
    
    public function getConfigValue($name)
    {
        if (isset($this->valuesTemp[$name])) return $this->valuesTemp[$name];
        $em = $this->container->get('doctrine')->getEntityManager();
        $query = $em->createQuery('SELECT c.value, c.serialized FROM BasicCmsBundle:Configs c WHERE c.name = :name')->setParameter('name', $name);
        $value = $query->getResult();
        if (!isset($value[0]['value'])) return null;
        if (strlen($value[0]['value']) < 65536) $saveValue = 1; else $saveValue = 0; 
        if (isset($value[0]['serialized']) && ($value[0]['serialized'] != 0)) 
        {
            $value[0]['value'] = @unserialize($value[0]['value']);
        }
        if ($saveValue != 0) $this->valuesTemp[$name] = $value[0]['value'];
        return $value[0]['value'];
    }
    
    public function setConfigValue($name, $value)
    {
        $em = $this->container->get('doctrine')->getEntityManager();
        $cfg = $em->getRepository('BasicCmsBundle:Configs')->findOneBy(array('name'=>$name));
        if (empty($cfg))
        {
            $cfg = new \Basic\CmsBundle\Entity\Configs();
            $em->persist($cfg);
            $cfg->setName($name);
        }
        if (is_array($value))
        {
            $cfg->setSerialized(1);
            $cfg->setValue(serialize($value));
        } else
        {
            $cfg->setSerialized(0);
            $cfg->setValue($value);
        }
        if (isset($this->valuesTemp[$name]) || (strlen($cfg->getValue()) < 65536)) $this->valuesTemp[$name] = $value;
        $em->flush();
    }
    
     // ***************************************
     // Функции передачи ошибки на страницу ошибки
     // ***************************************
    
    private $errorPageNumber = 0; //0 - not found, 1 - access denied
    
    public function setErrorPageNumber($numb)
    {
        $this->errorPageNumber = $numb;
    }
    
    public function getErrorPageNumber()
    {
        return $this->errorPageNumber;
    }
    
     // ***************************************
     // Функции авторизации
     // ***************************************
    
    private $currentUser = null;
    
    public function setCurrentUser(\Basic\CmsBundle\Entity\Users $user = null)
    {
        $this->currentUser = $user;
    }
    
    public function getCurrentUser()
    {
        return $this->currentUser;
    }
    
     // ***************************************
     // Управление куками
     // ***************************************
    
    private $cookies = array();
    
    
    public function addCookie(\Symfony\Component\HttpFoundation\Cookie $cookie)
    {
        $this->cookies[] = $cookie;
    }
    
    public function getCookies()
    {
        return $this->cookies;
    }
    
     // ***************************************
     // Функция генерации имени страницы
     // ***************************************
    
    public function getUniqueUrl($pattern, $parameters, $realId = null)
    {
        if ($pattern == '') return '';
        foreach ($parameters as &$val) if (strlen($val) > 100)  $val = substr($val, 0, 100);
        unset($val);
        if (is_array($parameters)) foreach ($parameters as $key => $val) $pattern = str_replace ('//'.$key.'//', $val, $pattern);
        $converter = array(
        'а' => 'a',   'б' => 'b',   'в' => 'v',
        'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
        'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',
        'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',
        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
        'ь' => '',  'ы' => 'y',   'ъ' => '',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
        'А' => 'A',   'Б' => 'B',   'В' => 'V',
        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
        'И' => 'I',   'Й' => 'Y',   'К' => 'K',
        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
        'О' => 'O',   'П' => 'P',   'Р' => 'R',
        'С' => 'S',   'Т' => 'T',   'У' => 'U',
        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
        'Ь' => '',  'Ы' => 'Y',   'Ъ' => '',
        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya');
        $pattern = strtr($pattern, $converter);
        $pattern = strtolower($pattern);
        $pattern = preg_replace('~[^-a-z0-9_\.]+~u', '-', $pattern);
        $pattern = trim($pattern, "-");
        $em = $this->container->get('doctrine')->getEntityManager();
        if ($realId === null) $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url')->setParameter('url', $pattern);
                         else $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url AND s.id != :id')->setParameter('id', $realId)->setParameter('url', $pattern);
        $checkurl = $query->getResult();
        if ($checkurl[0]['urlcount'] == 0) return $pattern;
        $index = 1;
        while (1)
        {
            if (strpos($pattern, '.') !== false) $patternnew = substr_replace($pattern, $index, strpos($pattern, '.'), 0); else $patternnew = $pattern.$index;
            if ($realId === null) $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url')->setParameter('url', $patternnew);
                             else $query = $em->createQuery('SELECT count(s.id) as urlcount FROM BasicCmsBundle:SeoPage s WHERE s.url=:url AND s.id != :id')->setParameter('id', $realId)->setParameter('url', $patternnew);
            $checkurl = $query->getResult();
            if ($checkurl[0]['urlcount'] == 0) return $patternnew;
            $index++;
        }
    }
    
     // ***************************************
     // Функция очистки HTML кода
     // ***************************************
    
     private $allowStyleProp = 0;
     private $replaceUrls = 0;
     
     private function clearHtmlPropertiy($matches)
     {
         if (!isset($matches[1]) || !isset($matches[2])) return '';
         if ($matches[1] == 'href')
         {
             if ($this->replaceUrls != 0) return 'href="#" onclick="window.open(\''.$matches[2].'\');"';
             return 'href="'.$matches[2].'"';
         } elseif ($matches[1] == 'style')
         {
             if ($this->allowStyleProp != 0) return 'style="'.$matches[2].'"';
             return '';
         }
         return '';
     }
     
     private function clearHtmlProperties($matches)
     {
         if (!isset($matches[1]) || !isset($matches[2])) return '';
         return '<'.$matches[1].' '.preg_replace_callback('/([A-z][A-z0-9]*)[\s]*\=[\s]*"([^"]*)"/i', array($this, 'clearHtmlPropertiy') , $matches[2]).'>';
     }
    
     
     
     public function clearHtmlCode($text, $allowTagsList = 'p', $allowStyleProperty = 0, $replaceUrls = 1)
     {
         $allowtags = explode(',', $allowTagsList);
         $allowtagsstr = '';
         foreach ($allowtags as $tag) $allowtagsstr .= '<'.trim($tag).'>';
         $text = strip_tags($text, $allowtagsstr);
         // Удаление свойств onclick и т.д. и изменение урлов на редиректы
         $this->allowStyleProp = $allowStyleProperty;
         $this->replaceUrls = $replaceUrls;
         $text = preg_replace_callback('/<([A-z][A-z0-9]*)[\s]+([^>]*)>/i', array($this, 'clearHtmlProperties') , $text);
         return $text;
     }
     
     public function unclearHtmlCode($text)
     {
         $text = preg_replace('/\shref="#" onclick="window\.open\(\'([^"\']*)\'\);"/ui', ' href="$1"', $text);
         return $text;
     }
     
     // ***************************************
     // Функции для временных файлов
     // ***************************************
     
     public function registerTemporaryFile($filename, $originalfilename)
     {
         $em = $this->container->get('doctrine')->getEntityManager();
         $tempent = new \Basic\CmsBundle\Entity\TemporaryFiles();
         $tempent->setFileName($filename);
         $tempent->setOriginalName($originalfilename);
         $tempent->setUploadDate(new \DateTime('now'));
         $em->persist($tempent);
         $em->flush();
         $query = $em->createQuery('SELECT t.fileName, t.id FROM BasicCmsBundle:TemporaryFiles t WHERE t.uploadDate < :date')->setParameter('date', new \DateTime('1 days ago'))->setMaxResults(10);
         $files = $query->getResult();
         if (is_array($files))
         {
             $removeids = array();
             foreach ($files as $file)
             {
                 @unlink('.'.$file['fileName']);
                 $removeids[] = $file['id'];
             }
             if (count($removeids) > 0)
             {
                 $query = $em->createQuery('DELETE FROM BasicCmsBundle:TemporaryFiles t WHERE t.id IN (:ids)')->setParameter('ids', $removeids);
                 $query->execute();
             }
         }
         unset($files);
         return $tempent->getId();
     }

     public function unlockTemporaryFile($filename)
     {
         $em = $this->container->get('doctrine')->getEntityManager();
         $query = $em->createQuery('DELETE FROM BasicCmsBundle:TemporaryFiles t WHERE t.fileName = :name')->setParameter('name', $filename);
         $query->execute();
     }
     
     public function unlockTemporaryEditor($text, $textold)
     {
         /*preg_match_all('/<(img|IMG)\s+src=("|\')([^"\']*)/ui', $textold, $matches);
         if (isset($matches[3]) && (is_array($matches[3]))) $oldfiles = $matches[3]; else $oldfiles = array();
         preg_match_all('/<(img|IMG)\s+src=("|\')([^"\']*)/ui', $text, $matches);
         if (isset($matches[3]) && (is_array($matches[3]))) $newfiles = $matches[3]; else $newfiles = array();*/
         preg_match_all('/<[iI][mM][gG]\s+([^>]*)/ui', $textold, $matches);
         preg_match_all('/[sS][rR][cC]=["\']([^"\']*)/ui', implode(' ', $matches[1]), $matches);
         $oldfiles = $matches[1];
         preg_match_all('/<[iI][mM][gG]\s+([^>]*)/ui', $text, $matches);
         preg_match_all('/[sS][rR][cC]=["\']([^"\']*)/ui', implode(' ', $matches[1]), $matches);
         $newfiles = $matches[1];
         foreach ($oldfiles as $file) if ((strpos($file, '/images/editor/') === 0) && (!in_array($file, $newfiles))) @unlink('.'.$file);
         foreach ($newfiles as $file) if (strpos($file, '/images/editor/') === 0) $this->unlockTemporaryFile ($file);
     }
     
     // ***************************************
     // Функции ведения лога
     // ***************************************

     public function logPushFront($sourceId, $actionType, $text, $url)
     {
         if ($sourceId == 0)
         {
             if ($this->currentUser != null) $sourceId = $this->currentUser->getId();
         }
         $em = $this->container->get('doctrine')->getEntityManager();
         $logent = new \Basic\CmsBundle\Entity\LogFront();
         $logent->setActionType($actionType);
         $logent->setLogDate(new \DateTime('now'));
         $logent->setSourceId($sourceId);
         $logent->setText($text);
         $logent->setUrl($url);
         $logent->setViewed(0);
         $em->persist($logent);
         $em->flush();
                     $mailer = $this->container->get('mailer');
                     $messages = \Swift_Message::newInstance()
                             ->setSubject('Новый событие на сайте '.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost())
                             ->setFrom('loglist@'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost())
                             ->setTo('admin@project-earth.community')
                             ->setContentType('text/html')
                             ->setBody($this->container->get('templating')->render('BasicCmsBundle:Default:emaillog.html.twig', array(
                                 'host' => $this->container->get('request_stack')->getCurrentRequest()->getHttpHost(),
                                 'text' => $text,
                                 'referer' => parse_url($this->container->get('request_stack')->getCurrentRequest()->headers->get('referer'), PHP_URL_PATH),
                                 'userurl' => ($this->getCurrentUser() != null ? 'http://'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost().$this->container->get('router')->generate('basic_cms_user_edit').'?id='.$this->getCurrentUser()->getId() : ''),
                                 'url' => 'http://'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost().$this->container->get('router')->generate('basic_cms_logs_view').'?id='.$logent->getId())));
                     $mailer->send($messages);
         $subscribes = $this->getConfigValue('object.user.logsubscribe');
         if (!is_array($subscribes)) $subscribes = array();
         foreach ($subscribes as $sbitem)
         {
             if (isset($sbitem['email']) && isset($sbitem['actions']))
             {
                 $emailchecked = false;
                 $sbitem['actions'] = explode(',', $sbitem['actions']);
                 foreach ($sbitem['actions'] as $needle) if ($needle == $actionType) $emailchecked = true;
                 if ($emailchecked == true)
                 {
                     $mailer = $this->container->get('mailer');
                     $messages = \Swift_Message::newInstance()
                             ->setSubject('Новый событие на сайте '.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost())
                             ->setFrom('loglist@'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost())
                             ->setTo($sbitem['email'])
                             ->setContentType('text/html')
                             ->setBody($this->container->get('templating')->render('BasicCmsBundle:Default:emaillog.html.twig', array(
                                 'host' => $this->container->get('request_stack')->getCurrentRequest()->getHttpHost(),
                                 'text' => $text,
                                 'referer' => parse_url($this->container->get('request_stack')->getCurrentRequest()->headers->get('referer'), PHP_URL_PATH),
                                 'userurl' => ($this->getCurrentUser() != null ? 'http://'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost().$this->container->get('router')->generate('basic_cms_user_edit').'?id='.$this->getCurrentUser()->getId() : ''),
                                 'url' => 'http://'.$this->container->get('request_stack')->getCurrentRequest()->getHttpHost().$this->container->get('router')->generate('basic_cms_logs_view').'?id='.$logent->getId())));
                     $mailer->send($messages);
                 }
             }
         }
     }
}
