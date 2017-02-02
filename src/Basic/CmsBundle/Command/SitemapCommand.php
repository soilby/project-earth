<?php

namespace Basic\CmsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class SitemapCommand extends ContainerAwareCommand {

    public function configure() 
    {
        $this->setDefinition(array())->setDescription('Update sitemap')->setName('cms:sitemap:update')->addArgument('host', InputArgument::REQUIRED, 'HTTP Host name');
    } 
    
    protected function execute(InputInterface $input, OutputInterface $output) 
    {
        $hostName = $input->getArgument('host');
        $output->write('Sitemap generation...');
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $logger = $this->getContainer()->get('logger'); 
        $query = $em->createQuery('SELECT l.shortName FROM BasicCmsBundle:Locales l');
        $locales = $query->getResult();
        if (!is_array($locales)) $locales = array();
        $file = @fopen('sitemap.tmp', "w"); 
        fwrite($file,
            '<?xml version="1.0" encoding="UTF-8"?>'."\r\n".
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\r\n");
        if ($file === false) 
        {
            $logger->info('Error: can`t open sitemap.tmp');
            $output->writeln('error: can`t open sitemap.tmp');
            return false;
        }
        //Найти страницы 100 штук
        $id = 0;
        $count = 0;
        do 
        {
            $query = $em->createQuery('SELECT p.id, p.locale, p.url, p.contentType, p.contentAction, p.contentId, p.access FROM BasicCmsBundle:SeoPage p WHERE p.id > :id')->setParameter('id',$id)->setMaxResults(1000);
            $pages = $query->getResult();
            // Обработать
            if ((is_array($pages)) && (count($pages) > 0))
            {
                foreach ($pages as $page)
                if (($page['access'] == '') || (!is_array(explode(',', $page['access']))))
                {
                    $id = $page['id'];
                    $objectinfo = null;
                    if ($this->getContainer()->has($page['contentType'])) $objectinfo = $this->getContainer()->get($page['contentType'])->getInfoSitemap($page['contentAction'], $page['contentId']);
                    if ($objectinfo != null)
                    {
                        $urls = array();
                        if ($page['locale'] == '')
                        {
                            $urls[] = 'http://'.$hostName.'/'.($page['url'] != 'index.html' ? $page['url'] : '');
                            foreach ($locales as $locale) $urls[] = 'http://'.$hostName.'/'.$locale['shortName'].'/'.$page['url'];
                        } else
                        {
                            foreach ($locales as $locale) if ($item['locale'] == $locales['shortName']) $urls[] = 'http://'.$hostName.'/'.$locale['shortName'].'/'.$page['url'];
                        }
                        if (count($urls) > 0)
                        {
                            foreach ($urls as $url)
                            {
                                $info = '<url>'."\r\n".
                                        '<loc>'.$url.'</loc>'."\r\n";
                                if (isset($objectinfo['avatar']) && ($objectinfo['avatar'] != ''))
                                {
                                    $info = $info.'<image:image>'."\r\n".
                                                  '<image:loc>http://'.$hostName.$objectinfo['avatar'].'</image:loc>'."\r\n".
                                                  '</image:image>'."\r\n";
                                }
                                if (isset($objectinfo['modifyDate']) && ($objectinfo['modifyDate'] != null))
                                {
                                    $info = $info.'<lastmod>'.$objectinfo['modifyDate']->format('Y-m-d').'</lastmod>'."\r\n";
                                }
                                if (isset($objectinfo['priority']) && ($objectinfo['priority'] != null))
                                {
                                    $info = $info.'<priority>'.$objectinfo['priority'].'</priority>'."\r\n";
                                }
                                $info = $info.'</url>'."\r\n";
                                fwrite($file, $info);
                                $count++;
                            }
                        }
                    }
                }
            } else break;
        } while (count($pages) > 0);
        fwrite($file, '</urlset>'."\r\n");
        fclose($file);
        // переместить файл в sitemap.xml
        @unlink('sitemap.xml');
        rename('sitemap.tmp', 'sitemap.xml');
        // записать robots.txt
        $filer = @fopen('robots.txt', "w"); 
        fwrite($filer,
                'User-agent: *'."\r\n".
                'Disallow: /admin/'."\r\n".
                'Disallow: /system/'."\r\n".
                'Host: '.$hostName."\r\n".
                'Sitemap: http://'.$hostName.'/sitemap.xml'."\r\n");
        fclose($filer);
        // Вывести результат
        $logger->info('Sitemap.xml generate successful ('.$count.' pages)');
        $output->writeln('done ('.$count.' pages)');
    } 
}
// 0 0 * * *
// cd /home/username/site.ru && php app/console cms:sitemap:update [domain] --no-debug
// cd /home/username/site.ru && /usr/bin/php app/console cms:sitemap:update [domain] --no-debug
