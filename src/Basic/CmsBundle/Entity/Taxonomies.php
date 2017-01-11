<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Taxonomies
 *
 * @ORM\Table(name="taxonomies")
 * @ORM\Entity
 */
class Taxonomies
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
     * @ORM\Column(name="avatar", type="string", length=255)
     */
    private $avatar;

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
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=99, nullable=true)
     */
    private $template;

    /**
     * @var integer
     *
     * @ORM\Column(name="enabled", type="integer")
     */
    private $enabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="enable_add", type="integer")
     */
    private $enableAdd;
    
    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent", type="integer", nullable=true)
     */
    private $parent;

    /**
     * @var integer
     *
     * @ORM\Column(name="first_parent", type="integer", nullable=true)
     */
    private $firstParent;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordering", type="integer")
     */
    private $ordering;

    /**
     * @var string
     *
     * @ORM\Column(name="object", type="string", length=99)
     */
    private $object;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_description", type="text")
     */
    private $metaDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_keywords", type="text")
     */
    private $metaKeywords;

    /**
     * @var string
     *
     * @ORM\Column(name="page_parameters", type="text", nullable=true)
     */
    private $pageParameters;

    /**
     * @var string
     *
     * @ORM\Column(name="filter_parameters", type="text", nullable=true)
     */
    private $filterParameters;
    
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
     * @return Taxonomies
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
     * Set avatar
     *
     * @param string $avatar
     * @return Taxonomies
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    
        return $this;
    }

    /**
     * Get avatar
     *
     * @return string 
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return Taxonomies
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    
        return $this;
    }

    /**
     * Get template
     *
     * @return string 
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set enabled
     *
     * @param integer $enabled
     * @return Taxonomies
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
     * Set description
     *
     * @param string $description
     * @return Taxonomies
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set parent
     *
     * @param integer $parent
     * @return Taxonomies
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    
        return $this;
    }

    /**
     * Get parent
     *
     * @return integer 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set firstParent
     *
     * @param integer $firstParent
     * @return Taxonomies
     */
    public function setFirstParent($firstParent)
    {
        $this->firstParent = $firstParent;
    
        return $this;
    }

    /**
     * Get firstParent
     *
     * @return integer 
     */
    public function getFirstParent()
    {
        return $this->firstParent;
    }

    /**
     * Set ordering
     *
     * @param integer $ordering
     * @return Taxonomies
     */
    public function setOrdering($ordering)
    {
        $this->ordering = $ordering;
    
        return $this;
    }

    /**
     * Get ordering
     *
     * @return integer 
     */
    public function getOrdering()
    {
        return $this->ordering;
    }

    /**
     * Set object
     *
     * @param string $object
     * @return Taxonomies
     */
    public function setObject($object)
    {
        $this->object = $object;
    
        return $this;
    }

    /**
     * Get object
     *
     * @return string 
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Set metaDescription
     *
     * @param string $metaDescription
     * @return Taxonomies
     */
    public function setMetaDescription($metaDescription)
    {
        $this->metaDescription = $metaDescription;
    
        return $this;
    }

    /**
     * Get metaDescription
     *
     * @return string 
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * Set metaKeywords
     *
     * @param string $metaKeywords
     * @return Taxonomies
     */
    public function setMetaKeywords($metaKeywords)
    {
        $this->metaKeywords = $metaKeywords;
    
        return $this;
    }

    /**
     * Get metaKeywords
     *
     * @return string 
     */
    public function getMetaKeywords()
    {
        return $this->metaKeywords;
    }

    /**
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return Taxonomies
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
     * @return Taxonomies
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
     * @return Taxonomies
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
     * Set pageParameters
     *
     * @param string $pageParameters
     * @return Taxonomies
     */
    public function setPageParameters($pageParameters)
    {
        $this->pageParameters = $pageParameters;
    
        return $this;
    }

    /**
     * Get pageParameters
     *
     * @return string 
     */
    public function getPageParameters()
    {
        return $this->pageParameters;
    }

    /**
     * Set filterParameters
     *
     * @param string $filterParameters
     * @return Taxonomies
     */
    public function setFilterParameters($filterParameters)
    {
        $this->filterParameters = $filterParameters;
    
        return $this;
    }

    /**
     * Get filterParameters
     *
     * @return string 
     */
    public function getFilterParameters()
    {
        return $this->filterParameters;
    }

    /**
     * Set enableAdd
     *
     * @param integer $enableAdd
     * @return Taxonomies
     */
    public function setEnableAdd($enableAdd)
    {
        $this->enableAdd = $enableAdd;
    
        return $this;
    }

    /**
     * Get enableAdd
     *
     * @return integer 
     */
    public function getEnableAdd()
    {
        return $this->enableAdd;
    }
}