<?php

namespace Extended\ProjectBundle\FS;

use Sabre\DAV;

class File extends Node implements DAV\IFile {

    public function put($data)
    {
        $version = $this->findVersion();
        if (empty($version) || ($this->token->getPermission() == 0)) {
            throw new DAV\Exception\MethodNotAllowed('Method not allowed');
        }
        do {
            $contentFile = md5(time().rand());
        } while (file_exists('../'.Node::FOLDER.$contentFile));
        file_put_contents('../'.Node::FOLDER.$contentFile, $data);
        
        $newVersion = new \Extended\ProjectBundle\Entity\ProjectFileVersion();
        $newVersion
                ->setCreateDate(new \DateTime('now'))
                ->setCreaterId($this->token->getUserId())
                ->setFileId($version->getFileId())
                ->setLocale($this->token->getLocale())
                ->setContentFile($contentFile);
        $this->entityManager->persist($newVersion);
        $file = $this->findFile();
        $file->setModifyDate(new \DateTime('now'));
        $this->entityManager->flush();
    }

    public function get() 
    {
        $version = $this->findVersion();
        if (empty($version)) {
            throw new DAV\Exception\NotFound('Not found');
        }
        return fopen('../'.Node::FOLDER.$version->getContentFile(), 'r');
    }

    public function delete() 
    {
        throw new DAV\Exception\MethodNotAllowed('Method not allowed');
    }

    public function getSize() 
    {
        $version = $this->findVersion();
        if (empty($version)) {
            return 0;
        }
        return filesize('../'.Node::FOLDER.$version->getContentFile());
    }

    public function getETag() 
    {
        return null;
    }

    public function getContentType() 
    {
        $file = $this->findFile();
        if (empty($file)) {
            return null;
        }
        return $file->getMimeType();
    }

}

