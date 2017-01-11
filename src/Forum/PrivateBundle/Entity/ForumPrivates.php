<?php

namespace Forum\PrivateBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ForumPrivates
 *
 * @ORM\Table(name="forumprivates")
 * @ORM\Entity
 */
class ForumPrivates
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
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;

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
     * @ORM\Column(name="creater_id", type="integer")
     */
    private $createrId;

    /**
     * @var integer
     *
     * @ORM\Column(name="target_id", type="integer")
     */
    private $targetId;

    /**
     * @var integer
     *
     * @ORM\Column(name="viewed", type="integer")
     */
    private $viewed;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="view_date", type="datetime", nullable=true)
     */
    private $viewDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="chain_id", type="integer")
     */
    private $chainId;

    /**
     * @var integer
     *
     * @ORM\Column(name="removed_creater", type="integer")
     */
    private $removedCreater;

    /**
     * @var integer
     *
     * @ORM\Column(name="removed_target", type="integer")
     */
    private $removedTarget;
    
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
     * Set title
     *
     * @param string $title
     * @return ForumPrivates
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return ForumPrivates
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
     * @return ForumPrivates
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
     * @return ForumPrivates
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
     * @return ForumPrivates
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
     * Set targetId
     *
     * @param integer $targetId
     * @return ForumPrivates
     */
    public function setTargetId($targetId)
    {
        $this->targetId = $targetId;
    
        return $this;
    }

    /**
     * Get targetId
     *
     * @return integer 
     */
    public function getTargetId()
    {
        return $this->targetId;
    }

    /**
     * Set viewed
     *
     * @param integer $viewed
     * @return ForumPrivates
     */
    public function setViewed($viewed)
    {
        $this->viewed = $viewed;
    
        return $this;
    }

    /**
     * Get viewed
     *
     * @return integer 
     */
    public function getViewed()
    {
        return $this->viewed;
    }

    /**
     * Set viewDate
     *
     * @param \DateTime $viewDate
     * @return ForumPrivates
     */
    public function setViewDate($viewDate)
    {
        $this->viewDate = $viewDate;
    
        return $this;
    }

    /**
     * Get viewDate
     *
     * @return \DateTime 
     */
    public function getViewDate()
    {
        return $this->viewDate;
    }

    /**
     * Set chainId
     *
     * @param integer $chainId
     * @return ForumPrivates
     */
    public function setChainId($chainId)
    {
        $this->chainId = $chainId;
    
        return $this;
    }

    /**
     * Get chainId
     *
     * @return integer 
     */
    public function getChainId()
    {
        return $this->chainId;
    }

    /**
     * Set removedCreater
     *
     * @param integer $removedCreater
     * @return ForumPrivates
     */
    public function setRemovedCreater($removedCreater)
    {
        $this->removedCreater = $removedCreater;
    
        return $this;
    }

    /**
     * Get removedCreater
     *
     * @return integer 
     */
    public function getRemovedCreater()
    {
        return $this->removedCreater;
    }

    /**
     * Set removedTarget
     *
     * @param integer $removedTarget
     * @return ForumPrivates
     */
    public function setRemovedTarget($removedTarget)
    {
        $this->removedTarget = $removedTarget;
    
        return $this;
    }

    /**
     * Get removedTarget
     *
     * @return integer 
     */
    public function getRemovedTarget()
    {
        return $this->removedTarget;
    }
}