<?php

namespace Extended\ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectFile
 *
 * @ORM\Table(name="projectfile")
 * @ORM\Entity
 */
class ProjectFile
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
     * @ORM\Column(name="is_collection", type="integer")
     */
    private $isCollection;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=99)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="title_ru", type="string", length=99)
     */
    private $titleRu;

    /**
     * @var string
     *
     * @ORM\Column(name="mime_type", type="string", length=99, nullable=true)
     */
    private $mimeType;

    /**
     * @var string
     *
     * @ORM\Column(name="extension", type="string", length=16, nullable=true)
     */
    private $extension;

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
     * @ORM\Column(name="parent_id", type="integer", nullable=true)
     */
    private $parentId;

    /**
     * @var integer
     *
     * @ORM\Column(name="project_id", type="integer")
     */
    private $projectId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="access_control", type="integer")
     */
    private $accessControl = 0;


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
     * Set isCollection
     *
     * @param integer $isCollection
     * @return ProjectFile
     */
    public function setIsCollection($isCollection)
    {
        $this->isCollection = $isCollection;
    
        return $this;
    }

    /**
     * Get isCollection
     *
     * @return integer 
     */
    public function getIsCollection()
    {
        return $this->isCollection;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return ProjectFile
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
     * Set titleRu
     *
     * @param string $titleRu
     * @return ProjectFile
     */
    public function setTitleRu($titleRu)
    {
        $this->titleRu = $titleRu;
    
        return $this;
    }

    /**
     * Get titleRu
     *
     * @return string 
     */
    public function getTitleRu()
    {
        return $this->titleRu;
    }

    /**
     * Set mimeType
     *
     * @param string $mimeType
     * @return ProjectFile
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
    
        return $this;
    }

    /**
     * Get mimeType
     *
     * @return string 
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Set extension
     *
     * @param string $extension
     * @return ProjectFile
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
    
        return $this;
    }

    /**
     * Get extension
     *
     * @return string 
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return ProjectFile
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
     * @return ProjectFile
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
     * Set parentId
     *
     * @param integer $parentId
     * @return ProjectFile
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    
        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer 
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set projectId
     *
     * @param integer $projectId
     * @return ProjectFile
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    
        return $this;
    }

    /**
     * Get projectId
     *
     * @return integer 
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * Set accessControl
     *
     * @param integer $accessControl
     * @return ProjectFile
     */
    public function setAccessControl($accessControl)
    {
        $this->accessControl = $accessControl;
    
        return $this;
    }

    /**
     * Get accessControl
     *
     * @return integer 
     */
    public function getAccessControl()
    {
        return $this->accessControl;
    }
}