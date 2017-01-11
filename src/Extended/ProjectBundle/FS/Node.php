<?php

namespace Extended\ProjectBundle\FS;

use Sabre\DAV;

abstract class Node implements DAV\INode 
{

    const FOLDER = 'secured/projectfiles/';
    
    protected $path;
    protected $entityManager;
    protected $token;

    public function __construct($path, $entityManager) 
    {
        $this->path = $path;
        $this->entityManager = $entityManager;
        $this->findToken();
    }

    public function getName() 
    {
        if (empty($this->token)) {
            return '';
        }
        return $this->token->getToken();
    }

    public function setName($name) 
    {
        throw new DAV\Exception\MethodNotAllowed('Method not allowed');
    }

    public function getLastModified() 
    {
        $file = $this->findFile();
        if (empty($file)) {
            return 0;
        }
        return $file->getModifyDate()->getTimestamp();
    }

    protected function findToken()
    {
        $this->token = $this->entityManager->createQuery('SELECT t FROM ExtendedProjectBundle:ProjectFileToken t WHERE t.token = :token AND t.expired > :now')->setParameters(array('token' => $this->path, 'now' => new \DateTime('now')))->getOneOrNullResult();
    }
    
    protected function findFile()
    {
        if (empty($this->token)) {
            return null;
        }
        $file = $this->entityManager->getRepository('ExtendedProjectBundle:ProjectFile')->find($this->token->getFileId());
        if (empty($file)) {
            return null;
        }
        return $file;
    }
    
    protected function findVersion()
    {
        if (empty($this->token)) {
            return null;
        }
        if ($this->token->getVersionId() != null) {
            $version = $this->entityManager->getRepository('ExtendedProjectBundle:ProjectFileVersion')->find($this->token->getVersionId());
        }
        if (empty($version)) {
            $version = $this->entityManager->createQuery('SELECT v FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid AND v.locale = :locale ORDER BY v.id DESC')->setParameters(array(
                'fileid' => $this->token->getFileId(),
                'locale' => $this->token->getLocale(),
            ))->setMaxResults(1)->getOneOrNullResult();
        }
        if (empty($version)) {
            $version = $this->entityManager->createQuery('SELECT v FROM ExtendedProjectBundle:ProjectFileVersion v WHERE v.fileId = :fileid ORDER BY v.id DESC')->setParameters(array(
                'fileid' => $this->token->getFileId(),
            ))->setMaxResults(1)->getOneOrNullResult();
        }
        if (empty($version)) {
            return null;
        }
        return $version;
    }
    
}

