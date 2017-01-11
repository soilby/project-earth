<?php

namespace Extended\ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectsRequests
 *
 * @ORM\Table(name="projectsrequests")
 * @ORM\Entity
 */
class ProjectsRequests
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
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="project_id", type="integer")
     */
    private $projectId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="project_role", type="integer")
     */
    private $projectRole;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text")
     */
    private $message;


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
     * Set userId
     *
     * @param integer $userId
     * @return ProjectsRequests
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
     * Set projectRole
     *
     * @param integer $projectRole
     * @return ProjectsRequests
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

    /**
     * Set message
     *
     * @param string $message
     * @return ProjectsRequests
     */
    public function setMessage($message)
    {
        $this->message = $message;
    
        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set projectId
     *
     * @param integer $projectId
     * @return ProjectsRequests
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
}