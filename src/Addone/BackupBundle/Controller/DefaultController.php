<?php

namespace Addone\BackupBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        if ($this->getUser()->checkAccess('backup_create') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание резервных копий',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        return $this->render('AddoneBackupBundle:Default:index.html.twig');
    }
    
    private function backupTables($filename, $tables = '*')
    {
        $file = fopen($filename, 'w');
        if (!$file) return false;
        fwrite($file, '-- Embedded.CMS SQL Dump'."\n");
        fwrite($file, '-- Version 1.0'."\n");
        fwrite($file, '-- Timestamp: '.date('d.m.Y H:i:s')."\n\n\n");
        fwrite($file, 'SET FOREIGN_KEY_CHECKS=0;'."\n");
        
        $conn = $em = $this->get('doctrine')->getEntityManager()->getConnection();
        
	if ($tables == '*')
	{
            $tables = array();
            $result = $conn->query('SHOW TABLES');
            
            while ($row = $result->fetch()) 
            {
                $row = array_values($row);
                if (isset($row[0])) $tables[] = $row[0];
            }
	} else
	{
            $tables = is_array($tables) ? $tables : explode(',', $tables);
	}
	
	foreach ($tables as $table)
	{
            fwrite($file, '-- Structure of table '.$table."\n");
            
            $row = $conn->fetchArray('SHOW CREATE TABLE '.$table);
            if (!isset($row[1])) continue;
            fwrite($file, 'DROP TABLE IF EXISTS '.$table.';'."\n\n");
            fwrite($file, $row[1].';'."\n\n");
            
            $result = $conn->query('SELECT * FROM '.$table);
            while ($row = $result->fetch()) 
            {
                $colums = '';
                $values = '';
                foreach ($row as $filedname => $fieldval)
                {
                    if ($colums != '') $colums .= ', ';
                    if ($values != '') $values .= ', ';
                    $colums .= $conn->quoteIdentifier($filedname);
                    if ($fieldval === null) $values .= 'NULL';
                    else $values .= $conn->quote($fieldval);
                }
                fwrite($file, 'INSERT INTO '.$table.' ('.$colums.') VALUES ('.$values.');'."\n");
            }
            fwrite($file, "\n\n\n");
        }
        fclose($file);
        return true;
    }
    
    private function addToZip($zip, $source, $basepath, $filter = null)
    {
        if (!file_exists($source)) return false;

        $source = str_replace('\\', '/', realpath($source));
        if (is_dir($basepath)) $basepath = str_replace('\\', '/', realpath($basepath)); else $basepath = '';

        if (is_dir($source) === true)
        {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $file)
            {
                $file = str_replace('\\', '/', $file);
                if (in_array(substr($file, strrpos($file, '/')+1), array('.', '..'))) continue;
                $file = str_replace('\\', '/', realpath($file));

                if (is_array($filter))
                {
                    foreach ($filter as $filterstr) if (preg_match($filterstr, $file)) continue 2;
                }

                if (is_file($file) === true)
                {
                    $zip->add_file_from_path(str_replace($basepath . '/', '', $file), $file);
                }
            }
        }
        else if (is_file($source) === true)
        {
            $zip->add_file_from_path(str_replace($basepath . '/', '', $source), $source);
        }
    }
    
    
    public function makeBackupAction() 
    {
        if ($this->getUser()->checkAccess('backup_create') == 0)
        {
            return $this->render('BasicCmsBundle:Default:message.html.twig', array(
                'title'=>'Создание резервных копий',
                'message'=>'Доступ запрещён', 
                'error'=>true,
                'paths'=>array()
            ));
        }
        set_time_limit(0);
        $this->backupTables('../secured/backup.sql');
        $zip = new \Addone\BackupBundle\Classes\ZipStream('backup.zip');
        
        $this->addToZip($zip, '../', '../', array('/\/app\/cache\//ui', '/\/app\/logs\//ui', '/\/helper\/imageresize\//ui'));
        $zip->add_file('app/cache/.htaccess', "Deny from all\r\n");
        $zip->add_file('app/logs/.htaccess', "Deny from all\r\n");
        $zip->add_file('helper/imageresize/.htaccess', "");
        
        $zip->add_file('restore.php', $this->render('AddoneBackupBundle:Default:restore.php.twig')->getContent());
        
        $zip->finish();
        flush();
        die;
    }
    
    
}
