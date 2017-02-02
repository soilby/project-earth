<?php

namespace Extended\ProjectBundle\FS;
use Sabre\DAV;

class Directory extends Node implements DAV\ICollection, DAV\IQuota {

    public function createFile($name, $data = null) 
    {
        throw new DAV\Exception\MethodNotAllowed('Method not allowed');
    }

    public function createDirectory($name) 
    {
        throw new DAV\Exception\MethodNotAllowed('Method not allowed');
    }

    public function getChild($name) 
    {
        return new File($this->path.$name, $this->entityManager);
    }

    public function getChildren() 
    {
        return array();
    }

    public function childExists($name) 
    {
        $name = $this->path.$name;
        $count = $this->entityManager->createQuery('SELECT count(t.id) FROM ExtendedProjectBundle:ProjectFileToken t WHERE t.token = :token AND t.expired > :now')->setParameters(array('token' => $name, 'now' => new \DateTime('now')))->getSingleScalarResult();
        return ($count != 0 ? true : false);
    }

    public function delete() 
    {
        throw new DAV\Exception\MethodNotAllowed('Method not allowed');
    }

    public function getQuotaInfo() 
    {
        return array(
            disk_total_space(''.Node::FOLDER)-disk_free_space(''.Node::FOLDER),
            disk_free_space(''.Node::FOLDER)
        );
    }

}

