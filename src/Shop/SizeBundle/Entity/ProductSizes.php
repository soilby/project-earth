<?php

namespace Shop\SizeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductSizes
 *
 * @ORM\Table(name="productsizes")
 * @ORM\Entity
 */
class ProductSizes
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
     * @ORM\Column(name="product_id", type="integer")
     */
    private $productId;

    /**
     * @var string
     *
     * @ORM\Column(name="sizes", type="text")
     */
    private $sizes;


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
     * Set productId
     *
     * @param integer $productId
     * @return ProductSizes
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
     * Set sizes
     *
     * @param string $sizes
     * @return ProductSizes
     */
    public function setSizes($sizes)
    {
        $this->sizes = $sizes;
    
        return $this;
    }

    /**
     * Get sizes
     *
     * @return string 
     */
    public function getSizes()
    {
        return $this->sizes;
    }
}
