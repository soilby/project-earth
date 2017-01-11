<?php

namespace Extended\ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectUsers
 *
 * @ORM\Table(name="projectusers")
 * @ORM\Entity
 */
class ProjectUsers
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
     * @var integer
     *
     * @ORM\Column(name="project_id", type="integer")
     */
    private $projectId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="read_only", type="integer")
     */
    private $readOnly;

    /**
     * @var integer
     *
     * @ORM\Column(name="project_role", type="integer")
     */
    private $projectRole;

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
     * Set projectId
     *
     * @param integer $projectId
     * @return ProjectUsers
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    
        return $this;
    }

    /**
     * Get projectId
     *
     * @return integer 
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return ProjectUsers
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
     * Set readOnly
     *
     * @param integer $readOnly
     * @return ProjectUsers
     */
    public function setReadOnly($readOnly)
    {
        $this->readOnly = $readOnly;
    
        return $this;
    }

    /**
     * Get readOnly
     *
     * @return integer 
     */
    public function getReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * Set projectRole
     *
     * @param integer $projectRole
     * @return ProjectUsers
     */
    public function setProjectRole($projectRole)
    {
        $this->projectRole = $projectRole;
    
        return $this;
    }

    /**
     * Get projectRole
     *
     * @return integer 
     */
    public function getProjectRole()
    {
        return $this->projectRole;
    }
}