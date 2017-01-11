<?php

namespace Forum\PrivateBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ForumPrivateAttachments
 *
 * @ORM\Table(name="forumprivateattachments")
 * @ORM\Entity
 */
class ForumPrivateAttachments
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
     * @ORM\Column(name="message_id", type="integer")
     */
    private $messageId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="download_date", type="datetime")
     */
    private $downloadDate;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", length=255)
     */
    private $fileName;

    /**
     * @var string
     *
     * @ORM\Column(name="content_file", type="string", length=255)
     */
    private $contentFile;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_image", type="integer")
     */
    private $isImage;


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
     * Set messageId
     *
     * @param integer $messageId
     * @return ForumPrivateAttachments
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;
    
        return $this;
    }

    /**
     * Get messageId
     *
     * @return integer 
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Set downloadDate
     *
     * @param \DateTime $downloadDate
     * @return ForumPrivateAttachments
     */
    public function setDownloadDate($downloadDate)
    {
        $this->downloadDate = $downloadDate;
    
        return $this;
    }

    /**
     * Get downloadDate
     *
     * @return \DateTime 
     */
    public function getDownloadDate()
    {
        return $this->downloadDate;
    }

    /**
     * Set fileName
     *
     * @param string $fileName
     * @return ForumPrivateAttachments
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    
        return $this;
    }

    /**
     * Get fileName
     *
     * @return string 
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set contentFile
     *
     * @param string $contentFile
     * @return ForumPrivateAttachments
     */
    public function setContentFile($contentFile)
    {
        $this->contentFile = $contentFile;
    
        return $this;
    }

    /**
     * Get contentFile
     *
     * @return string 
     */
    public function getContentFile()
    {
        return $this->contentFile;
    }

    /**
     * Set isImage
     *
     * @param integer $isImage
     * @return ForumPrivateAttachments
     */
    public function setIsImage($isImage)
    {
        $this->isImage = $isImage;
    
        return $this;
    }

    /**
     * Get isImage
     *
     * @return integer 
     */
    public function getIsImage()
    {
        return $this->isImage;
    }
}