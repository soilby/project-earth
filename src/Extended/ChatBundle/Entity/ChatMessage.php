<?php

namespace Extended\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ChatMessage
 *
 * @ORM\Table(name="chatmessage")
 * @ORM\Entity
 */
class ChatMessage
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
     * @ORM\Column(name="creater_id", type="integer")
     */
    private $createrId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime")
     */
    private $createDate;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text")
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(name="attachment", type="string", length=255)
     */
    private $attachment;

    /**
     * @var string
     *
     * @ORM\Column(name="attachment_filename", type="string", length=255)
     */
    private $attachmentFilename;

    /**
     * @var string
     *
     * @ORM\Column(name="attachment_mimetype", type="string", length=99)
     */
    private $attachmentMimetype;
    
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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createrId
     *
     * @param integer $createrId
     * @return ChatMessage
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
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return ChatMessage
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
     * Set message
     *
     * @param string $message
     * @return ChatMessage
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
     * Set attachmentFilename
     *
     * @param string $attachmentFilename
     * @return ChatMessage
     */
    public function setAttachmentFilename($attachmentFilename)
    {
        $this->attachmentFilename = $attachmentFilename;
    
        return $this;
    }

    /**
     * Get attachmentFilename
     *
     * @return string 
     */
    public function getAttachmentFilename()
    {
        return $this->attachmentFilename;
    }

    /**
     * Set attachmentMimetype
     *
     * @param string $attachmentMimetype
     * @return ChatMessage
     */
    public function setAttachmentMimetype($attachmentMimetype)
    {
        $this->attachmentMimetype = $attachmentMimetype;
    
        return $this;
    }

    /**
     * Get attachmentMimetype
     *
     * @return string 
     */
    public function getAttachmentMimetype()
    {
        return $this->attachmentMimetype;
    }

    /**
     * Set chatId
     *
     * @param integer $chatId
     * @return ChatMessage
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
     * Set attachment
     *
     * @param string $attachment
     * @return ChatMessage
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
    
        return $this;
    }

    /**
     * Get attachment
     *
     * @return string 
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * Set chatType
     *
     * @param integer $chatType
     * @return ChatMessage
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