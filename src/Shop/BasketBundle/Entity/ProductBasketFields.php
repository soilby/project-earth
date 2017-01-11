<?php

namespace Shop\BasketBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductBasketFields
 *
 * @ORM\Table(name="productbasketfields")
 * @ORM\Entity
 */
class ProductBasketFields
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
     * @ORM\Column(name="tech_name", type="string", length=99)
     */
    private $techName;
    
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=4096)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=99)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="parameters", type="text")
     */
    private $parameters;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordering", type="integer")
     */
    private $ordering;

    /**
     * @var integer
     *
     * @ORM\Column(name="enabled", type="integer")
     */
    private $enabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="default_enable", type="integer")
     */
    private $defaultEnable;

    /**
     * @var string
     *
     * @ORM\Column(name="default_value", type="text")
     */
    private $defaultValue;


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
     * Set name
     *
     * @param string $name
     * @return ProductBasketFields
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return ProductBasketFields
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set parameters
     *
     * @param string $parameters
     * @return ProductBasketFields
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    
        return $this;
    }

    /**
     * Get parameters
     *
     * @return string 
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set ordering
     *
     * @param integer $ordering
     * @return ProductBasketFields
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
     * Set enabled
     *
     * @param integer $enabled
     * @return ProductBasketFields
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
     * Set defaultEnable
     *
     * @param integer $defaultEnable
     * @return ProductBasketFields
     */
    public function setDefaultEnable($defaultEnable)
    {
        $this->defaultEnable = $defaultEnable;
    
        return $this;
    }

    /**
     * Get defaultEnable
     *
     * @return integer 
     */
    public function getDefaultEnable()
    {
        return $this->defaultEnable;
    }

    /**
     * Set defaultValue
     *
     * @param string $defaultValue
     * @return ProductBasketFields
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    
        return $this;
    }

    /**
     * Get defaultValue
     *
     * @return string 
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Set techName
     *
     * @param string $techName
     * @return ProductBasketFields
     */
    public function setTechName($techName)
    {
        $this->techName = $techName;
    
        return $this;
    }

    /**
     * Get techName
     *
     * @return string 
     */
    public function getTechName()
    {
        return $this->techName;
    }
}