<?php

namespace Extended\ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectDocuments
 *
 * @ORM\Table(name="projectdocuments")
 * @ORM\Entity
 */
class ProjectDocuments
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
     * @ORM\Column(name="enabled", type="integer")
     */
    private $enabled;

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
     * @var integer
     *
     * @ORM\Column(name="creater_id", type="integer")
     */
    private $createrId;

    /**
     * @var integer
     *
     * @ORM\Column(name="read_only", type="integer")
     */
    private $readOnly;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_public", type="integer")
     */
    private $isPublic;

    /**
     * @var integer
     *
     * @ORM\Column(name="project_id", type="integer")
     */
    private $projectId;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer")
     */
    private $parentId;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_master", type="integer", nullable=true)
     */
    private $isMaster;
    
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
     * Set enabled
     *
     * @param integer $enabled
     * @return ProjectDocuments
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    
        return $this;
    }

    /**
     * Get enabled
     *
     * @return integer 
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return ProjectDocuments
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
     * @return ProjectDocuments
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
     * @return ProjectDocuments
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
     * @return ProjectDocuments
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
     * Set modifyDate
     *
     * @param \DateTime $modifyDate
     * @return ProjectDocuments
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
     * Set modifierId
     *
     * @param integer $modifierId
     * @return ProjectDocuments
     */
    public function setModifierId($modifierId)
    {
        $this->modifierId = $modifierId;
    
        return $this;
    }

    /**
     * Get modifierId
     *
     * @return integer 
     */
    public function getModifierId()
    {
        return $this->modifierId;
    }

    /**
     * Set readOnly
     *
     * @param integer $readOnly
     * @return ProjectDocuments
     */
    public function setReadOnly($readOnly)
    {
        $this->readOnly = $readOnly;
    
        return $this;
    }

    /**
     * Get readOnly
     *
     * @return integer 
     */
    public function getReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * Set isPublic
     *
     * @param integer $isPublic
     * @return ProjectDocuments
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;
    
        return $this;
    }

    /**
     * Get isPublic
     *
     * @return integer 
     */
    public function getIsPublic()
    {
        return $this->isPublic;
    }

    /**
     * Set projectId
     *
     * @param integer $projectId
     * @return ProjectDocuments
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
     * Set parentId
     *
     * @param integer $parentId
     * @return ProjectDocuments
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
     * Set isMaster
     *
     * @param integer $isMaster
     * @return ProjectDocuments
     */
    public function setIsMaster($isMaster)
    {
        $this->isMaster = $isMaster;
    
        return $this;
    }

    /**
     * Get isMaster
     *
     * @return integer 
     */
    public function getIsMaster()
    {
        return $this->isMaster;
    }
}