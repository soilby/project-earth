<?php

namespace Shop\ParameterBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductValues
 *
 * @ORM\Table(name="productvalues")
 * @ORM\Entity
 */
class ProductValues
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
     * @ORM\Column(name="parameter_id", type="integer")
     */
    private $parameterId;

    /**
     * @var integer
     *
     * @ORM\Column(name="product_id", type="integer")
     */
    private $productId;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text")
     */
    private $value;


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
     * Set parameterId
     *
     * @param integer $parameterId
     * @return ProductValues
     */
    public function setParameterId($parameterId)
    {
        $this->parameterId = $parameterId;
    
        return $this;
    }

    /**
     * Get parameterId
     *
     * @return integer 
     */
    public function getParameterId()
    {
        return $this->parameterId;
    }

    /**
     * Set productId
     *
     * @param integer $productId
     * @return ProductValues
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
    
        return $this;
    }

    /**
     * Get productId
     *
     * @return integer 
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return ProductValues
     */
    public function setValue($value)
    {
        $this->value = $value;
    
        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }
}