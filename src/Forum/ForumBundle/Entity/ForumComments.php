<?php

namespace Forum\ForumBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ForumComments
 *
 * @ORM\Table(name="forumcomments")
 * @ORM\Entity
 */
class ForumComments
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
     * @ORM\Column(name="seo_page_id", type="integer")
     */
    private $seoPageId;

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
     * @return ForumComments
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
     * Set seoPageId
     *
     * @param integer $seoPageId
     * @return ForumComments
     */
    public function setSeoPageId($seoPageId)
    {
        $this->seoPageId = $seoPageId;
    
        return $this;
    }

    /**
     * Get seoPageId
     *
     * @return integer 
     */
    public function getSeoPageId()
    {
        return $this->seoPageId;
    }

    /**
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return ForumComments
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
     * @return ForumComments
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
     * @return ForumComments
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
     * @return ForumComments
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
     * @return ForumComments
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
     * @return ForumComments
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
}
