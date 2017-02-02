<?php

namespace Template\TestBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class TemplateTest
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'template.test';
    }
    
    public function getDescription()
    {
        return 'Тестовая тема';
    }

    public function registerMenu()
    {
        //$manager = $this->container->get('cms.cmsManager');
        //$manager->addAdminMenu('Текстовые страницы', $this->container->get('router')->generate('basic_cms_textpage_list'), 0, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('textpage_list') | $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('textpage_listsite'));
    }
    
    public function registerRoles()
    {
        //$manager = $this->container->get('cms.cmsManager');
        //$manager->addRole('textpage_list','Просмотр списка текстовых материалов');
    }
    
    public function registerLayout(&$layout)
    {
        $layout['test'] = 'Шаблон тестовой темы';
    }
    
    public function getLayoutTwig($layout, $locale)
    {
        if ($layout == 'test') return 'TemplateTestBundle:Front:layout.html.twig';
        return null;
    }

    public function registerPosition($layout, &$positions)
    {
        if ($layout == 'test')
        {
            $positions['top'] = 'Верхняя позиция';
        }
    }
    
    public function registerTemplate($target, $contentType, &$templates)
    {
        if ($target == 'object.user')
        {
            if ($contentType == 'view')
            {
                $templates['test_user_view1'] = 'Шаблон пользователя 1';
            }
            if ($contentType == 'auth')
            {
                $templates['test_user_auth1'] = 'Шаблон авторизации 1';
            }
            if ($contentType == 'password')
            {
                $templates['test_user_password1'] = 'Шаблон восстановления пароля 1';
            }
            if ($contentType == 'register')
            {
                $templates['test_user_register1'] = 'Шаблон регистрации пользователя 1';
            }
            if ($contentType == 'profile')
            {
                $templates['test_user_profile1'] = 'Шаблон изменения профиля 1';
            }
            if ($contentType == 'search')
            {
                $templates['test_pages_search1'] = 'Шаблон поиска по сайту 1';
            }
        }
        if ($target == 'object.textpage')
        {
            if ($contentType == 'view')
            {
                $templates['test_textpage_view1'] = 'Шаблон текстовой страницы 1';
            }
        }
        if ($target == 'object.taxonomy')
        {
            if ($contentType == 'view')
            {
                $templates['test_taxonomy_view1'] = 'Шаблон категории 1';
            }
            if ($contentType == 'viewshow')
            {
                $templates['test_taxonomy_viewshow1'] = 'Шаблон представления 1';
            }
        }
        if ($target == 'object.product')
        {
            if ($contentType == 'view')
            {
                $templates['test_product_view1'] = 'Шаблон продукции 1';
            }
            if ($contentType == 'compare')
            {
                $templates['test_product_compare1'] = 'Шаблон сравнения продукции 1';
            }
        }
        if ($target == 'object.form')
        {
            if ($contentType == 'view')
            {
                $templates['test_form_view1'] = 'Шаблон формы 1';
            }
        }
        if ($target == 'object.image')
        {
            if ($contentType == 'view')
            {
                $templates['test_image_view1'] = 'Шаблон изображения 1';
            }
        }
        if ($target == 'object.file')
        {
            if ($contentType == 'view')
            {
                $templates['test_file_view1'] = 'Шаблон файла 1';
            }
            if ($contentType == 'create')
            {
                $templates['test_file_create1'] = 'Шаблон создания файла 1';
            }
            if ($contentType == 'editpage')
            {
                $templates['test_file_editpage1'] = 'Шаблон редактирования файла 1';
            }
        }
        if ($target == 'object.forum')
        {
            if ($contentType == 'view')
            {
                $templates['test_forum_view1'] = 'Шаблон просмотра форума 1';
            }
            if ($contentType == 'create')
            {
                $templates['test_forum_create1'] = 'Шаблон создания форума 1';
            }
            if ($contentType == 'private')
            {
                $templates['test_forum_private1'] = 'Шаблон личных сообщений 1';
            }
        }
    }
    
    public function getTemplateTwig($target, $contentType, $template, $locale)
    {
        if ($target == 'object.user')
        {
            if ($contentType == 'view')
            {
                if ($template == 'test_user_view1') return 'TemplateTestBundle:User:view1.html.twig';
            }
            if ($contentType == 'auth')
            {
                if ($template == 'test_user_auth1') return 'TemplateTestBundle:User:auth1.html.twig';
            }
            if ($contentType == 'password')
            {
                if ($template == 'test_user_password1') return 'TemplateTestBundle:User:password1.html.twig';
            }
            if ($contentType == 'register')
            {
                if ($template == 'test_user_register1') return 'TemplateTestBundle:User:register1.html.twig';
            }
            if ($contentType == 'profile')
            {
                if ($template == 'test_user_profile1') return 'TemplateTestBundle:User:profile1.html.twig';
            }
            if ($contentType == 'search')
            {
                if ($template == 'test_pages_search1') return 'TemplateTestBundle:Front:search1.html.twig';
            }
        }
        if ($target == 'object.textpage')
        {
            if ($contentType == 'view')
            {
                if ($template == 'test_textpage_view1') return 'TemplateTestBundle:TextPage:view1.html.twig';
            }
        }
        if ($target == 'object.taxonomy')
        {
            if ($contentType == 'view')
            {
                if ($template == 'test_taxonomy_view1') return 'TemplateTestBundle:Taxonomy:view1.html.twig';
            }
            if ($contentType == 'viewshow')
            {
                if ($template == 'test_taxonomy_view1') return 'TemplateTestBundle:Taxonomy:viewshow1.html.twig';
            }
        }
        if ($target == 'object.product')
        {
            if ($contentType == 'view')
            {
                if ($template == 'test_product_view1') return 'TemplateTestBundle:Product:view1.html.twig';
            }
            if ($contentType == 'compare')
            {
                if ($template == 'test_product_compare1') return 'TemplateTestBundle:Product:compare1.html.twig';
            }
        }
        if ($target == 'object.form')
        {
            if ($contentType == 'view')
            {
                if ($template == 'test_form_view1') return 'TemplateTestBundle:Form:view1.html.twig';
            }
        }
        if ($target == 'object.image')
        {
            if ($contentType == 'view')
            {
                if ($template == 'test_image_view1') return 'TemplateTestBundle:Gallery:view1.html.twig';
            }
        }
        if ($target == 'object.file')
        {
            if ($contentType == 'view')
            {
                if ($template == 'test_file_view1') return 'TemplateTestBundle:Files:view1.html.twig';
            }
            if ($contentType == 'create')
            {
                if ($template == 'test_file_create1') return 'TemplateTestBundle:Files:create1.html.twig';
            }
            if ($contentType == 'editpage')
            {
                if ($template == 'test_file_editpage1') return 'TemplateTestBundle:Files:editpage1.html.twig';
            }
        }
        if ($target == 'object.forum')
        {
            if ($contentType == 'view')
            {
                if ($template == 'test_forum_view1') return 'TemplateTestBundle:Forum:view1.html.twig';
            }
            if ($contentType == 'create')
            {
                if ($template == 'test_forum_create1') return 'TemplateTestBundle:Forum:create1.html.twig';
            }
            if ($contentType == 'private')
            {
                if ($template == 'test_forum_private1') return 'TemplateTestBundle:Forum:private1.html.twig';
            }
        }
        return null;
    }

    public function registerErrorTemplate(&$templates)
    {
        $templates['test_error_1'] = 'Шаблон ошибки 1';
    }
    
    public function getErrorTemplateTwig($template, $locale)
    {
        if ($template == 'test_error_1') return 'TemplateTestBundle:Front:error1.html.twig';
    }

    public function registerModuleTemplate($target, $contentType, &$templates)
    {
        if ($target == 'object.user')
        {
            if ($contentType == 'autorization')
            {
                $templates['test_module_autorization1'] = 'Шаблон авторизации 1';
            }
            if ($contentType == 'useronline')
            {
                $templates['test_module_useronline1'] = 'Шаблон пользователей онлайн 1';
            }
        }
        if ($target == 'object.module')
        {
            if ($contentType == 'custom_html')
            {
                $templates['test_module_html1'] = 'Шаблон пользовательской вёрски 1';
            }
            if ($contentType == 'menu')
            {
                $templates['test_module_menu1'] = 'Шаблон меню 1';
            }
            if ($contentType == 'breadcrumbs')
            {
                $templates['test_module_breadcrumbs1'] = 'Шаблон хлебных крошек 1';
            }
            if ($contentType == 'banner')
            {
                $templates['test_module_banner1'] = 'Шаблон баннеров 1';
            }
        }
        if ($target == 'object.taxonomy')
        {
            if ($contentType == 'taxonomy_show')
            {
                $templates['test_module_taxonomy_show1'] = 'Шаблон представления таксономии 1';
            }
            if ($contentType == 'taxonomy_menu')
            {
                $templates['test_module_taxonomy_menu1'] = 'Шаблон представления меню категорий 1';
            }
        }
        if ($target == 'object.product')
        {
            if ($contentType == 'compare')
            {
                $templates['test_module_product_compare1'] = 'Шаблон модуля сравнения 1';
            }
            if ($contentType == 'visited')
            {
                $templates['test_module_product_visited1'] = 'Шаблон модуля посещенных продуктов 1';
            }
        }
        if ($target == 'object.form')
        {
            if ($contentType == 'moduleform')
            {
                $templates['test_module_form_moduleform1'] = 'Шаблон модуля формы 1';
            }
        }
        if ($target == 'object.forum')
        {
            if ($contentType == 'comments')
            {
                $templates['test_module_forum_comments1'] = 'Шаблон модуля комментариев к странице 1';
            }
            if ($contentType == 'private')
            {
                $templates['test_module_forum_private1'] = 'Шаблон модуля личных сообщений 1';
            }
        }
    }
    
    public function getModuleTemplateTwig($target, $contentType, $template, $locale)
    {
        if ($target == 'object.user')
        {
            if ($contentType == 'autorization')
            {
                if ($template == 'test_module_autorization1') return 'TemplateTestBundle:Modules:autorization1.html.twig';
            }
            if ($contentType == 'useronline')
            {
                if ($template == 'test_module_useronline1') return 'TemplateTestBundle:Modules:useronline1.html.twig';
            }
        }
        if ($target == 'object.module')
        {
            if ($contentType == 'custom_html')
            {
                if ($template == 'test_module_html1') return 'TemplateTestBundle:Modules:html1.html.twig';
            }
            if ($contentType == 'menu')
            {
                if ($template == 'test_module_menu1') return 'TemplateTestBundle:Modules:menu1.html.twig';
            }
            if ($contentType == 'breadcrumbs')
            {
                if ($template == 'test_module_breadcrumbs1') return 'TemplateTestBundle:Modules:breadcrumbs1.html.twig';
            }
            if ($contentType == 'banner')
            {
                if ($template == 'test_module_banner1') return 'TemplateTestBundle:Modules:banner1.html.twig';
            }
        }
        if ($target == 'object.taxonomy')
        {
            if ($contentType == 'taxonomy_show')
            {
                if ($template == 'test_module_taxonomy_show1') return 'TemplateTestBundle:Modules:taxonomyshow1.html.twig';
            }
            if ($contentType == 'taxonomy_menu')
            {
                if ($template == 'test_module_taxonomy_menu1') return 'TemplateTestBundle:Modules:taxonomymenu1.html.twig';
            }
        }
        if ($target == 'object.product')
        {
            if ($contentType == 'compare')
            {
                if ($template == 'test_module_product_compare1') return 'TemplateTestBundle:Modules:productcompare1.html.twig';
            }
            if ($contentType == 'visited')
            {
                if ($template == 'test_module_product_visited1') return 'TemplateTestBundle:Modules:productvisited1.html.twig';
            }
        }
        if ($target == 'object.form')
        {
            if ($contentType == 'moduleform')
            {
                if ($template == 'test_module_form_moduleform1') return 'TemplateTestBundle:Modules:formmodule1.html.twig';
            }
        }
        if ($target == 'object.forum')
        {
            if ($contentType == 'comments')
            {
                if ($template == 'test_module_forum_comments1') return 'TemplateTestBundle:Modules:comments1.html.twig';
            }
            if ($contentType == 'private')
            {
                if ($template == 'test_module_forum_private1') return 'TemplateTestBundle:Modules:private1.html.twig';
            }
        }
        return null;
    }
    
}
