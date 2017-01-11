<?php

namespace Forum\ForumBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ForumMessages
 *
 * @ORM\Table(name="forummessages")
 * @ORM\Entity
 */
class ForumMessages
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
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_id", type="integer")
     */
    private $forumId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime")
     */
    private $createDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modify_date", type="datetime")
     */
    private $modifyDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="creater_id", type="integer", nullable=true)
     */
    private $createrId;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_visible", type="integer")
     */
    private $isVisible;

    /**
     * @var integer
     *
     * @ORM\Column(name="moderator_id", type="integer", nullable=true)
     */
    private $moderatorId;

    /**
     * @var string
     *
     * @ORM\Column(name="moderator_message", type="text", nullable=true)
     */
    private $moderatorMessage;


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
     * Set content
     *
     * @param string $content
     * @return ForumMessages
     */
    public function setContent($content)
    {
        $this->content = $content;
    
        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return ForumMessages
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;
    
        return $this;
    }

    /**
     * Get createDate
     *
     * @return \DateTime 
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * Set modifyDate
     *
     * @param \DateTime $modifyDate
     * @return ForumMessages
     */
    public function setModifyDate($modifyDate)
    {
        $this->modifyDate = $modifyDate;
    
        return $this;
    }

    /**
     * Get modifyDate
     *
     * @return \DateTime 
     */
    public function getModifyDate()
    {
        return $this->modifyDate;
    }

    /**
     * Set createrId
     *
     * @param integer $createrId
     * @return ForumMessages
     */
    public function setCreaterId($createrId)
    {
        $this->createrId = $createrId;
    
        return $this;
    }

    /**
     * Get createrId
     *
     * @return integer 
     */
    public function getCreaterId()
    {
        return $this->createrId;
    }

    /**
     * Set isVisible
     *
     * @param integer $isVisible
     * @return ForumMessages
     */
    public function setIsVisible($isVisible)
    {
        $this->isVisible = $isVisible;
    
        return $this;
    }

    /**
     * Get isVisible
     *
     * @return integer 
     */
    public function getIsVisible()
    {
        return $this->isVisible;
    }

    /**
     * Set moderatorId
     *
     * @param integer $moderatorId
     * @return ForumMessages
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
     * Set moderatorMessage
     *
     * @param string $moderatorMessage
     * @return ForumMessages
     */
    public function setModeratorMessage($moderatorMessage)
    {
        $this->moderatorMessage = $moderatorMessage;
    
        return $this;
    }

    /**
     * Get moderatorMessage
     *
     * @return string 
     */
    public function getModeratorMessage()
    {
        return $this->moderatorMessage;
    }

    /**
     * Set forumId
     *
     * @param integer $forumId
     * @return ForumMessages
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
}