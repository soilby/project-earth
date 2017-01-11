<?php

namespace Extended\ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectBlogLink
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ProjectBlogLink
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
     * @ORM\Column(name="projectId", type="integer")
     */
    private $projectId;

    /**
     * @var integer
     *
     * @ORM\Column(name="itemId", type="integer")
     */
    private $itemId;
    

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
     * @return ProjectBlogLink
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
     * Set itemId
     *
     * @param integer $itemId
     * @return ProjectBlogLink
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;
    
        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer 
     */
    public function getItemId()
    {
        return $this->itemId;
    }
}