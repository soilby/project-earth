<?php

namespace Extended\KnowledgeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KnowledgeChanges
 *
 * @ORM\Table(name="knowledgechanges")
 * @ORM\Entity
 */
class KnowledgeChanges
{
    const KC_CT_CONTENT = 0;
    
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
     * @ORM\Column(name="object_id", type="integer")
     */
    private $objectId;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

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
     * @ORM\Column(name="change_type", type="integer")
     */
    private $changeType;

    /**
     * @var integer
     *
     * @ORM\Column(name="change_value", type="integer")
     */
    private $changeValue;


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
     * Set objectId
     *
     * @param integer $objectId
     * @return KnowledgeChanges
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
     * Set description
     *
     * @param string $description
     * @return KnowledgeChanges
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
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return KnowledgeChanges
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
     * @return KnowledgeChanges
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
     * Set changeType
     *
     * @param integer $changeType
     * @return KnowledgeChanges
     */
    public function setChangeType($changeType)
    {
        $this->changeType = $changeType;
    
        return $this;
    }

    /**
     * Get changeType
     *
     * @return integer 
     */
    public function getChangeType()
    {
        return $this->changeType;
    }

    /**
     * Set changeValue
     *
     * @param integer $changeValue
     * @return KnowledgeChanges
     */
    public function setChangeValue($changeValue)
    {
        $this->changeValue = $changeValue;
    
        return $this;
    }

    /**
     * Get changeValue
     *
     * @return integer 
     */
    public function getChangeValue()
    {
        return $this->changeValue;
    }
}