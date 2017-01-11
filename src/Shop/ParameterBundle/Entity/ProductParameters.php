<?php

namespace Shop\ParameterBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductParameters
 *
 * @ORM\Table(name="productparameters")
 * @ORM\Entity
 */
class ProductParameters
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
     * @ORM\Column(name="type", type="string", length=99)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=99)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="regular_exp", type="text")
     */
    private $regularExp;

    /**
     * @var string
     *
     * @ORM\Column(name="items", type="text")
     */
    private $items;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="ordering", type="integer")
     */
    private $ordering;


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
     * @return ProductParameters
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
     * Set description
     *
     * @param string $description
     * @return ProductParameters
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
     * Set regularExp
     *
     * @param string $regularExp
     * @return ProductParameters
     */
    public function setRegularExp($regularExp)
    {
        $this->regularExp = $regularExp;
    
        return $this;
    }

    /**
     * Get regularExp
     *
     * @return string 
     */
    public function getRegularExp()
    {
        return $this->regularExp;
    }

    /**
     * Set ordering
     *
     * @param integer $ordering
     * @return ProductParameters
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
     * Set type
     *
     * @param string $type
     * @return ProductParameters
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
     * Set items
     *
     * @param string $items
     * @return ProductParameters
     */
    public function setItems($items)
    {
        $this->items = $items;
    
        return $this;
    }

    /**
     * Get items
     *
     * @return string 
     */
    public function getItems()
    {
        return $this->items;
    }
}