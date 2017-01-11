<?php

namespace Extended\ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectFileToken
 *
 * @ORM\Table(name="projectfiletoken")
 * @ORM\Entity
 */
class ProjectFileToken
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=64)
     */
    private $token;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="version_id", type="integer", nullable=true)
     */
    private $versionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="file_id", type="integer")
     */
    private $fileId;

    /**
     * @var integer
     *
     * @ORM\Column(name="permission", type="integer")
     */
    private $permission;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expired", type="datetime")
     */
    private $expired;
    
    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=5)
     */
    private $locale;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return ProjectFileToken
     */
    public function setToken($token)
    {
        $this->token = $token;
    
        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return ProjectFileToken
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    
        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set versionId
     *
     * @param integer $versionId
     * @return ProjectFileToken
     */
    public function setVersionId($versionId)
    {
        $this->versionId = $versionId;
    
        return $this;
    }

    /**
     * Get versionId
     *
     * @return integer 
     */
    public function getVersionId()
    {
        return $this->versionId;
    }

    /**
     * Set permission
     *
     * @param integer $permission
     * @return ProjectFileToken
     */
    public function setPermission($permission)
    {
        $this->permission = $permission;
    
        return $this;
    }

    /**
     * Get permission
     *
     * @return integer 
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * Set expired
     *
     * @param \DateTime $expired
     * @return ProjectFileToken
     */
    public function setExpired($expired)
    {
        $this->expired = $expired;
    
        return $this;
    }

    /**
     * Get expired
     *
     * @return \DateTime 
     */
    public function getExpired()
    {
        return $this->expired;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return ProjectFileToken
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    
        return $this;
    }

    /**
     * Get locale
     *
     * @return string 
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set fileId
     *
     * @param integer $fileId
     * @return ProjectFileToken
     */
    public function setFileId($fileId)
    {
        $this->fileId = $fileId;
    
        return $this;
    }

    /**
     * Get fileId
     *
     * @return integer 
     */
    public function getFileId()
    {
        return $this->fileId;
    }
}