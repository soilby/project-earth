<?php

namespace Template\MirclubBundle;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class TemplateMirclub
{
    private $container;

    public function __construct(Container $container) 
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'template.mirclub';
    }
    
    public function getDescription()
    {
        return 'Тема клуба МиР';
    }
    
    public function registerMenu()
    {
        $manager = $this->container->get('cms.cmsManager');
        $manager->addAdminMenu('Настройка цветов и фона', $this->container->get('router')->generate('template_mirclub_index'), 70, $this->container->get('security.token_storage')->getToken()->getUser()->checkAccess('template_mirclub'), 'Администрирование');
    }
    
    public function registerRoles()
    {
        $manager = $this->container->get('cms.cmsManager');
        $manager->addRole('template_mirclub', 'Настройка цветового оформления и фона МиР');
    }
    
    public function registerLayout(&$layout)
    {
        $layout['main'] = 'Основной шаблон темы';
    }
    
    public function getLayoutTwig($layout, $locale)
    {
        if ($layout == 'main') return 'TemplateMirclubBundle:Front:layout.html.twig';
        return null;
    }
    
    public function registerTemplate($target, $contentType, &$templates)
    {
        if ($target == 'object.textpage')
        {
            if ($contentType == 'view')
            {
                $templates['mir_textpage_viewknowladge'] = 'Шаблон базы знаний';
                $templates['mir_textpage_viewraw'] = 'Шаблон просмотра готовой вёрстки';
            }
        }
        if ($target == 'object.user')
        {
            if ($contentType == 'password')
            {
                $templates['mir_user_password'] = 'Шаблон восстановления пароля';
            }
            if ($contentType == 'register')
            {
                $templates['mir_user_register'] = 'Шаблон регистрации пользователя';
            }
            if ($contentType == 'profile')
            {
                $templates['mir_user_profile'] = 'Шаблон изменения профиля';
            }
            if ($contentType == 'searchpage')
            {
                $templates['mir_pages_searchforum'] = 'Шаблон поиска по форуму';
            }
        }
        if ($target == 'object.taxonomy')
        {
            if ($contentType == 'view')
            {
                $templates['mir_taxonomy_viewcatalog'] = 'Шаблон ярмарки';
                $templates['mir_taxonomy_viewknowladge'] = 'Шаблон базы знаний';
                $templates['mir_taxonomy_viewvillage'] = 'Шаблон поселений';
                $templates['mir_taxonomy_viewvillagehouse'] = 'Шаблон поместья поселения';
                $templates['mir_taxonomy_viewforum'] = 'Шаблон форума';
                $templates['mir_taxonomy_viewproject'] = 'Шаблон проектов';
                $templates['mir_taxonomy_viewlibrary'] = 'Шаблон библиотеки';
            }
            if ($contentType == 'viewshow')
            {
                $templates['mir_taxonomy_viewshowproject'] = 'Шаблон представления проектов';
                $templates['mir_taxonomy_viewshowmyfiles'] = 'Шаблон представления моих файлов';
            }
        }
        if ($target == 'object.product')
        {
            if ($contentType == 'view')
            {
                $templates['mir_product_view'] = 'Шаблон продукции';
            }
        }
        if ($target == 'object.forum')
        {
            if ($contentType == 'view')
            {
                $templates['mir_forum_view'] = 'Шаблон просмотра форума';
            }
            if ($contentType == 'create')
            {
                $templates['mir_forum_create'] = 'Шаблон создания форума';
            }
        }
        if ($target == 'object.project')
        {
            if ($contentType == 'view')
            {
                $templates['mir_project_view'] = 'Шаблон просмотра проекта';
            }
            if ($contentType == 'edit')
            {
                $templates['mir_project_edit'] = 'Шаблон редактирования проекта';
            }
        }
        if ($target == 'object.file')
        {
            if ($contentType == 'create')
            {
                $templates['mir_file_create'] = 'Шаблон создания файла';
            }
            if ($contentType == 'editpage')
            {
                $templates['mir_file_editpage'] = 'Шаблон редактирования файла';
            }
        }
        /*
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
        }*/
    }
    
    public function getTemplateTwig($target, $contentType, $template, $locale)
    {
        if ($target == 'object.textpage')
        {
            if ($contentType == 'view')
            {
                if ($template == 'mir_textpage_viewknowladge') return 'TemplateMirclubBundle:TextPage:viewknowladge.html.twig';
                if ($template == 'mir_textpage_viewraw') return 'TemplateMirclubBundle:TextPage:viewraw.html.twig';
            }
        }
        if ($target == 'object.user')
        {
            if ($contentType == 'password')
            {
                if ($template == 'mir_user_password') return 'TemplateMirclubBundle:User:password.html.twig';
            }
            if ($contentType == 'register')
            {
                if ($template == 'mir_user_register') return 'TemplateMirclubBundle:User:register.html.twig';
            }
            if ($contentType == 'profile')
            {
                if ($template == 'mir_user_profile') return 'TemplateMirclubBundle:User:profile.html.twig';
            }
            if ($contentType == 'searchpage')
            {
                if ($template == 'mir_pages_searchforum') return 'TemplateMirclubBundle:Front:searchforum.html.twig';
            }
        }
        if ($target == 'object.taxonomy')
        {
            if ($contentType == 'view')
            {
                if ($template == 'mir_taxonomy_viewcatalog') return 'TemplateMirclubBundle:Taxonomy:viewcatalog.html.twig';
                if ($template == 'mir_taxonomy_viewknowladge') return 'TemplateMirclubBundle:Taxonomy:viewknowladge.html.twig';
                if ($template == 'mir_taxonomy_viewvillage') return 'TemplateMirclubBundle:Taxonomy:viewvillage.html.twig';
                if ($template == 'mir_taxonomy_viewvillagehouse') return 'TemplateMirclubBundle:Taxonomy:viewvillage.html.twig';
                if ($template == 'mir_taxonomy_viewforum') return 'TemplateMirclubBundle:Taxonomy:viewforum.html.twig';
                if ($template == 'mir_taxonomy_viewproject') return 'TemplateMirclubBundle:Taxonomy:viewprojects.html.twig';
                if ($template == 'mir_taxonomy_viewlibrary') return 'TemplateMirclubBundle:Taxonomy:viewlibrary.html.twig';
            }
            if ($contentType == 'viewshow')
            {
                if ($template == 'mir_taxonomy_viewshowproject') return 'TemplateMirclubBundle:Taxonomy:viewshowprojects.html.twig';
                if ($template == 'mir_taxonomy_viewshowmyfiles') return 'TemplateMirclubBundle:Taxonomy:viewshowmyfiles.html.twig';
            }
        }
        if ($target == 'object.product')
        {
            if ($contentType == 'view')
            {
                if ($template == 'mir_product_view') return 'TemplateMirclubBundle:Product:view.html.twig';
            }
        }
        if ($target == 'object.forum')
        {
            if ($contentType == 'view')
            {
                if ($template == 'mir_forum_view') return 'TemplateMirclubBundle:Forum:view.html.twig';
            }
            if ($contentType == 'create')
            {
                if ($template == 'mir_forum_create') return 'TemplateMirclubBundle:Forum:create.html.twig';
            }
        }
        if ($target == 'object.project')
        {
            if ($contentType == 'view')
            {
                if ($template == 'mir_project_view') return ($locale == 'en' ? 'TemplateMirclubBundle:Project:view.en.html.twig' : 'TemplateMirclubBundle:Project:view.html.twig');
            }
            if ($contentType == 'edit')
            {
                if ($template == 'mir_project_edit') return ($locale == 'en' ? 'TemplateMirclubBundle:Project:edit.en.html.twig' : 'TemplateMirclubBundle:Project:edit.html.twig');
            }
        }
        if ($target == 'object.file')
        {
            if ($contentType == 'create')
            {
                if ($template == 'mir_file_create') return 'TemplateMirclubBundle:Files:create.html.twig';
            }
            if ($contentType == 'editpage')
            {
                if ($template == 'mir_file_editpage') return 'TemplateMirclubBundle:Files:editpage.html.twig';
            }
        }
        if ($target == 'object.knowledge')
        {
            if ($contentType == 'viewvilagesprofile')
            {
                return 'TemplateMirclubBundle:Temp:vilages.html.twig';
            }
            if ($contentType == 'viewblogsprofile')
            {
                return 'TemplateMirclubBundle:Temp:blogs.html.twig';
            }
        }
        /*
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
        }*/
        return null;
    }

    public function registerErrorTemplate(&$templates)
    {
        $templates['mir_error'] = 'Шаблон ошибки';
    }
    
    public function getErrorTemplateTwig($template, $locale)
    {
        if ($template == 'mir_error') return 'TemplateMirclubBundle:Front:error.html.twig';
    }

    public function registerPosition($layout, &$positions)
    {
        if ($layout == 'main')
        {
            $positions['leftSide'] = 'Левая сторона';
            $positions['preContent'] = 'Модули перед контентом';
            $positions['postContent'] = 'Модули после контента';
            $positions['projectAjaxMenu'] = 'Загрузка подпроектов';
            $positions['headerRightSide'] = 'Правая сторона шапки';
            $positions['preHeader'] = 'Модули перед шапкой';
        }
    }
    
    public function registerModuleTemplate($target, $contentType, &$templates)
    {
        if ($target == 'object.module')
        {
            if ($contentType == 'menu')
            {
                $templates['mir_module_mainmenu'] = 'Шаблон главного меню';
            }
            if ($contentType == 'breadcrumbs')
            {
                $templates['mir_module_breadcrumbs'] = 'Шаблон хлебных крошек';
            }
            if ($contentType == 'locale')
            {
                $templates['mir_module_locale'] = 'Шаблон смены языка';
            }
        }
        if ($target == 'object.user')
        {
            if ($contentType == 'autorization')
            {
                $templates['mir_module_autorization'] = 'Шаблон авторизации';
            }
            if ($contentType == 'useronline')
            {
                $templates['mir_module_useronline'] = 'Шаблон пользователей онлайн';
            }
        }
        if ($target == 'object.taxonomy')
        {
            if ($contentType == 'taxonomy_show')
            {
                $templates['mir_module_taxonomy_shownews'] = 'Шаблон представления таксономии статей';
            }
            if ($contentType == 'taxonomy_menu')
            {
                $templates['mir_module_taxonomy_menuajax'] = 'Шаблон загрузки подпроектов';
            }
        }
        if ($target == 'object.forum')
        {
            if ($contentType == 'comments')
            {
                $templates['mir_module_forum_comments'] = 'Шаблон модуля комментариев к странице';
            }
        }
        if ($target == 'object.form')
        {
            if ($contentType == 'moduleform')
            {
                $templates['mir_module_form_moduleform'] = 'Шаблон модуля формы';
                $templates['mir_module_form_moduleformhide'] = 'Шаблон модуля скрытой формы';
            }
        }
        /*
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
        }*/
    }
    
    public function getModuleTemplateTwig($target, $contentType, $template, $locale)
    {
        if ($target == 'object.module')
        {
            if ($contentType == 'menu')
            {
                if ($template == 'mir_module_mainmenu') return 'TemplateMirclubBundle:Modules:menumain.html.twig';
            }
            if ($contentType == 'breadcrumbs')
            {
                if (($template == 'mir_module_breadcrumbs') && ($locale == 'en')) return 'TemplateMirclubBundle:Modules:breadcrumbs.en.html.twig';
                if (($template == 'mir_module_breadcrumbs') && ($locale != 'en')) return 'TemplateMirclubBundle:Modules:breadcrumbs.html.twig';
            }
            if ($contentType == 'locale')
            {
                if ($template == 'mir_module_locale') return 'TemplateMirclubBundle:Modules:locale.html.twig';
            }
        }
        if ($target == 'object.user')
        {
            if ($contentType == 'autorization')
            {
                if ($template == 'mir_module_autorization') return 'TemplateMirclubBundle:Modules:autorization.html.twig';
            }
            if ($contentType == 'useronline')
            {
                if ($template == 'mir_module_useronline') return 'TemplateMirclubBundle:Modules:useronline.html.twig';
            }
        }
        if ($target == 'object.taxonomy')
        {
            if ($contentType == 'taxonomy_show')
            {
                if ($template == 'mir_module_taxonomy_shownews') return 'TemplateMirclubBundle:Modules:taxonomyshownews.html.twig';
            }
            if ($contentType == 'taxonomy_menu')
            {
                if ($template == 'mir_module_taxonomy_menuajax') return 'TemplateMirclubBundle:Modules:taxonomymenuajax.html.twig';
            }
        }
        if ($target == 'object.forum')
        {
            if ($contentType == 'comments')
            {
                if ($template == 'mir_module_forum_comments') return ($locale == 'en' ? 'TemplateMirclubBundle:Modules:comments.en.html.twig' : 'TemplateMirclubBundle:Modules:comments.html.twig');
            }
        }
        if ($target == 'object.form')
        {
            if ($contentType == 'moduleform')
            {
                if ($template == 'mir_module_form_moduleform') return 'TemplateMirclubBundle:Modules:formmodule.html.twig';
                if ($template == 'mir_module_form_moduleformhide') return 'TemplateMirclubBundle:Modules:formmodulehide.html.twig';
            }
        }
        /*
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
        }*/
        return null;
    }
    
}
