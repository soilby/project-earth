<?php

namespace Extended\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ChatUser
 *
 * @ORM\Table(name="chatuser")
 * @ORM\Entity
 */
class ChatUser
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
     * @ORM\Column(name="chat_id", type="integer")
     */
    private $chatId;

    /**
     * @var integer
     *
     * @ORM\Column(name="chat_type", type="integer")
     */
    private $chatType;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="visit_date", type="datetime")
     */
    private $visitDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_seen_message", type="integer")
     */
    private $lastSeenMessage;
    
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
     * @return ChatUser
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
     * Set chatId
     *
     * @param integer $chatId
     * @return ChatUser
     */
    public function setChatId($chatId)
    {
        $this->chatId = $chatId;
    
        return $this;
    }

    /**
     * Get chatId
     *
     * @return integer 
     */
    public function getChatId()
    {
        return $this->chatId;
    }

    /**
     * Set visitDate
     *
     * @param \DateTime $visitDate
     * @return ChatUser
     */
    public function setVisitDate($visitDate)
    {
        $this->visitDate = $visitDate;
    
        return $this;
    }

    /**
     * Get visitDate
     *
     * @return \DateTime 
     */
    public function getVisitDate()
    {
        return $this->visitDate;
    }

    /**
     * Set lastSeenMessage
     *
     * @param integer $lastSeenMessage
     * @return ChatUser
     */
    public function setLastSeenMessage($lastSeenMessage)
    {
        $this->lastSeenMessage = $lastSeenMessage;
    
        return $this;
    }

    /**
     * Get lastSeenMessage
     *
     * @return integer 
     */
    public function getLastSeenMessage()
    {
        return $this->lastSeenMessage;
    }

    /**
     * Set chatType
     *
     * @param integer $chatType
     * @return ChatUser
     */
    public function setChatType($chatType)
    {
        $this->chatType = $chatType;
    
        return $this;
    }

    /**
     * Get chatType
     *
     * @return integer 
     */
    public function getChatType()
    {
        return $this->chatType;
    }
}