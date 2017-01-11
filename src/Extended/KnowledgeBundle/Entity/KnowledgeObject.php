<?php

namespace Extended\KnowledgeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KnowledgeObject
 *
 * @ORM\Table(name="knowledgeobject")
 * @ORM\Entity
 */
class KnowledgeObject
{
    const KO_TYPE_DOCUMENT = 0;
    const KO_TYPE_FILE = 1;
    
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
     * @ORM\Column(name="title", type="text")
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="default_title", type="text")
     */
    private $defaultTitle;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="object_type", type="integer")
     */
    private $objectType;

    /**
     * @var integer
     *
     * @ORM\Column(name="object_id", type="integer")
     */
    private $objectId;

    /**
     * @var integer
     *
     * @ORM\Column(name="object_text_id", type="string", length=99, nullable=true)
     */
    private $objectTextId;
    
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
     * @var integer
     *
     * @ORM\Column(name="rating_positive", type="integer")
     */
    private $ratingPositive;

    /**
     * @var integer
     *
     * @ORM\Column(name="rating_negative", type="integer")
     */
    private $ratingNegative;


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
     * @return KnowledgeObject
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
     * Set objectType
     *
     * @param integer $objectType
     * @return KnowledgeObject
     */
    public function setObjectType($objectType)
    {
        $this->objectType = $objectType;
    
        return $this;
    }

    /**
     * Get objectType
     *
     * @return integer 
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * Set objectId
     *
     * @param integer $objectId
     * @return KnowledgeObject
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
    
        return $this;
    }

    /**
     * Get objectId
     *
     * @return integer 
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Set createrId
     *
     * @param integer $createrId
     * @return KnowledgeObject
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
     * @return KnowledgeObject
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
     * Set ratingPositive
     *
     * @param integer $ratingPositive
     * @return KnowledgeObject
     */
    public function setRatingPositive($ratingPositive)
    {
        $this->ratingPositive = $ratingPositive;
    
        return $this;
    }

    /**
     * Get ratingPositive
     *
     * @return integer 
     */
    public function getRatingPositive()
    {
        return $this->ratingPositive;
    }

    /**
     * Set ratingNegative
     *
     * @param integer $ratingNegative
     * @return KnowledgeObject
     */
    public function setRatingNegative($ratingNegative)
    {
        $this->ratingNegative = $ratingNegative;
    
        return $this;
    }

    /**
     * Get ratingNegative
     *
     * @return integer 
     */
    public function getRatingNegative()
    {
        return $this->ratingNegative;
    }

    /**
     * Set defaultTitle
     *
     * @param string $defaultTitle
     * @return KnowledgeObject
     */
    public function setDefaultTitle($defaultTitle)
    {
        $this->defaultTitle = $defaultTitle;
    
        return $this;
    }

    /**
     * Get defaultTitle
     *
     * @return string 
     */
    public function getDefaultTitle()
    {
        return $this->defaultTitle;
    }

    /**
     * Set objectTextId
     *
     * @param string $objectTextId
     * @return KnowledgeObject
     */
    public function setObjectTextId($objectTextId)
    {
        $this->objectTextId = $objectTextId;
    
        return $this;
    }

    /**
     * Get objectTextId
     *
     * @return string 
     */
    public function getObjectTextId()
    {
        return $this->objectTextId;
    }
}