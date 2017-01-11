<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TaxonomyShows
 *
 * @ORM\Table(name="taxonomyshows")
 * @ORM\Entity
 */
class TaxonomyShows
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
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

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
     * @var string
     *
     * @ORM\Column(name="taxonomy_ids", type="text", nullable=true)
     */
    private $taxonomyIds;


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
     * @return TaxonomyShows
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
     * @return TaxonomyShows
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
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return TaxonomyShows
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
     * @return TaxonomyShows
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
     * @return TaxonomyShows
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
     * Set template
     *
     * @param string $template
     * @return TaxonomyShows
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
     * @return TaxonomyShows
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
     * @return TaxonomyShows
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
     * Set object
     *
     * @param string $object
     * @return TaxonomyShows
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
     * @return TaxonomyShows
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
     * @return TaxonomyShows
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
     * Set pageParameters
     *
     * @param string $pageParameters
     * @return TaxonomyShows
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
     * @return TaxonomyShows
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
     * Set taxonomyIds
     *
     * @param string $taxonomyIds
     * @return TaxonomyShows
     */
    public function setTaxonomyIds($taxonomyIds)
    {
        $this->taxonomyIds = $taxonomyIds;
    
        return $this;
    }

    /**
     * Get taxonomyIds
     *
     * @return string 
     */
    public function getTaxonomyIds()
    {
        return $this->taxonomyIds;
    }
}