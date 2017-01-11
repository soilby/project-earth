<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TemporaryFiles
 *
 * @ORM\Table(name="temporaryfiles")
 * @ORM\Entity
 */
class TemporaryFiles
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
     * @ORM\Column(name="file_name", type="string", length=255)
     */
    private $fileName;

    /**
     * @var string
     *
     * @ORM\Column(name="original_name", type="string", length=255)
     */
    private $originalName;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="upload_date", type="datetime")
     */
    private $uploadDate;


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
     * Set fileName
     *
     * @param string $fileName
     * @return TemporaryFiles
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
     * Set uploadDate
     *
     * @param \DateTime $uploadDate
     * @return TemporaryFiles
     */
    public function setUploadDate($uploadDate)
    {
        $this->uploadDate = $uploadDate;
    
        return $this;
    }

    /**
     * Get uploadDate
     *
     * @return \DateTime 
     */
    public function getUploadDate()
    {
        return $this->uploadDate;
    }

    /**
     * Set originalName
     *
     * @param string $originalName
     * @return TemporaryFiles
     */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;
    
        return $this;
    }

    /**
     * Get originalName
     *
     * @return string 
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }
}