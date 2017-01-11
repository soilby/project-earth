<?php

namespace Forum\ForumBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ForumModerators
 *
 * @ORM\Table(name="forummoderators")
 * @ORM\Entity
 */
class ForumModerators
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
     * @ORM\Column(name="category_id", type="integer")
     */
    private $categoryId;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_id", type="integer")
     */
    private $forumId;

    /**
     * @var integer
     *
     * @ORM\Column(name="moderator_id", type="integer")
     */
    private $moderatorId;


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
     * Set forumId
     *
     * @param integer $forumId
     * @return ForumModerators
     */
    public function setForumId($forumId)
    {
        $this->forumId = $forumId;
    
        return $this;
    }

    /**
     * Get forumId
     *
     * @return integer 
     */
    public function getForumId()
    {
        return $this->forumId;
    }

    /**
     * Set moderatorId
     *
     * @param integer $moderatorId
     * @return ForumModerators
     */
    public function setModeratorId($moderatorId)
    {
        $this->moderatorId = $moderatorId;
    
        return $this;
    }

    /**
     * Get moderatorId
     *
     * @return integer 
     */
    public function getModeratorId()
    {
        return $this->moderatorId;
    }

    /**
     * Set categoryId
     *
     * @param integer $categoryId
     * @return ForumModerators
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    
        return $this;
    }

    /**
     * Get categoryId
     *
     * @return integer 
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }
}