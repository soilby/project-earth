<?php

namespace Extended\ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectFileGDToken
 *
 * @ORM\Table(name="projectfilegdtoken")
 * @ORM\Entity(repositoryClass="Extended\ProjectBundle\Repository\ProjectFileGDTokenRepository")
 */
class ProjectFileGDToken
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=99)
     */
    private $token;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="file_id", type="integer")
     */
    private $fileId;

    /**
     * @var int
     *
     * @ORM\Column(name="permission", type="integer")
     */
    private $permission;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=5)
     */
    private $locale;
    
    /**
     * @var int
     *
     * @ORM\Column(name="version_id", type="integer", nullable=true)
     */
    private $versionId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expired", type="datetime")
     */
    private $expired;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="text")
     */
    private $link;

    
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set token
     *
     * @param string $token
     *
     * @return ProjectFileGDToken
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
     *
     * @return ProjectFileGDToken
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set fileId
     *
     * @param integer $fileId
     *
     * @return ProjectFileGDToken
     */
    public function setFileId($fileId)
    {
        $this->fileId = $fileId;

        return $this;
    }

    /**
     * Get fileId
     *
     * @return int
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * Set permission
     *
     * @param integer $permission
     *
     * @return ProjectFileGDToken
     */
    public function setPermission($permission)
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * Get permission
     *
     * @return int
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * Set locale
     *
     * @param string $locale
     *
     * @return ProjectFileGDToken
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
     * Set versionId
     *
     * @param integer $versionId
     *
     * @return ProjectFileGDToken
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
     * Set expired
     *
     * @param \DateTime $expired
     *
     * @return ProjectFileGDToken
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
     * Set link
     *
     * @param string $link
     *
     * @return ProjectFileGDToken
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }
}
