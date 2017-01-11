<?php

namespace Extended\ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectFileVersion
 *
 * @ORM\Table(name="projectfileversion")
 * @ORM\Entity
 */
class ProjectFileVersion
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
     * @ORM\Column(name="content_file", type="string", length=99)
     */
    private $contentFile;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime")
     */
    private $createDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="creater_id", type="integer")
     */
    private $createrId;

    /**
     * @var integer
     *
     * @ORM\Column(name="file_id", type="integer")
     */
    private $fileId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=5)
     */
    private $locale;


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
     * Set contentFile
     *
     * @param string $contentFile
     * @return ProjectFileVersion
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
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return ProjectFileVersion
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
     * Set createrId
     *
     * @param integer $createrId
     * @return ProjectFileVersion
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
     * Set fileId
     *
     * @param integer $fileId
     * @return ProjectFileVersion
     */
    public function setFileId($fileId)
    {
        $this->fileId = $fileId;
    
        return $this;
    }

    /**
     * Get fileId
     *
     * @return integer 
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return ProjectFileVersion
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    
        return $this;
    }

    /**
     * Get locale
     *
     * @return string 
     */
    public function getLocale()
    {
        return $this->locale;
    }
}