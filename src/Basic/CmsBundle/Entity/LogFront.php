<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LogFront
 *
 * @ORM\Table(name="logfront")
 * @ORM\Entity
 */
class LogFront
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
     * @var \DateTime
     *
     * @ORM\Column(name="log_date", type="datetime")
     */
    private $logDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="source_id", type="integer")
     */
    private $sourceId;

    /**
     * @var string
     *
     * @ORM\Column(name="action_type", type="string", length=99)
     */
    private $actionType;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="text")
     */
    private $text;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255)
     */
    private $url;

    /**
     * @var integer
     *
     * @ORM\Column(name="viewed", type="integer", nullable=true)
     */
    private $viewed;

    /**
     * @var integer
     *
     * @ORM\Column(name="viewed_user_id", type="integer", nullable=true)
     */
    private $viewedUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="viewed_date", type="datetime", nullable=true)
     */
    private $viewedDate;


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
     * Set actionType
     *
     * @param string $actionType
     * @return LogFront
     */
    public function setActionType($actionType)
    {
        $this->actionType = $actionType;
    
        return $this;
    }

    /**
     * Get actionType
     *
     * @return string 
     */
    public function getActionType()
    {
        return $this->actionType;
    }

    /**
     * Set text
     *
     * @param string $text
     * @return LogFront
     */
    public function setText($text)
    {
        $this->text = $text;
    
        return $this;
    }

    /**
     * Get text
     *
     * @return string 
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return LogFront
     */
    public function setUrl($url)
    {
        $this->url = $url;
    
        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set viewed
     *
     * @param integer $viewed
     * @return LogFront
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
     * Set viewedUserId
     *
     * @param integer $viewedUserId
     * @return LogFront
     */
    public function setViewedUserId($viewedUserId)
    {
        $this->viewedUserId = $viewedUserId;
    
        return $this;
    }

    /**
     * Get viewedUserId
     *
     * @return integer 
     */
    public function getViewedUserId()
    {
        return $this->viewedUserId;
    }

    /**
     * Set viewedDate
     *
     * @param \DateTime $viewedDate
     * @return LogFront
     */
    public function setViewedDate($viewedDate)
    {
        $this->viewedDate = $viewedDate;
    
        return $this;
    }

    /**
     * Get viewedDate
     *
     * @return \DateTime 
     */
    public function getViewedDate()
    {
        return $this->viewedDate;
    }

    /**
     * Set logDate
     *
     * @param \DateTime $logDate
     * @return LogFront
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
    
        return $this;
    }

    /**
     * Get logDate
     *
     * @return \DateTime 
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * Set sourceId
     *
     * @param integer $sourceId
     * @return LogFront
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;
    
        return $this;
    }

    /**
     * Get sourceId
     *
     * @return integer 
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }
}