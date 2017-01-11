<?php

namespace Shop\GiftBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductGift
 *
 * @ORM\Table(name="productgift")
 * @ORM\Entity
 */
class ProductGift
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
     * @var integer
     *
     * @ORM\Column(name="gift_id", type="integer")
     */
    private $giftId;

    /**
     * @var integer
     *
     * @ORM\Column(name="enabled", type="integer")
     */
    private $enabled;

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
     * Set productId
     *
     * @param integer $productId
     * @return ProductGift
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
     * Set giftId
     *
     * @param integer $giftId
     * @return ProductGift
     */
    public function setGiftId($giftId)
    {
        $this->giftId = $giftId;
    
        return $this;
    }

    /**
     * Get giftId
     *
     * @return integer 
     */
    public function getGiftId()
    {
        return $this->giftId;
    }

    /**
     * Set enabled
     *
     * @param integer $enabled
     * @return ProductGift
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
     * Set ordering
     *
     * @param integer $ordering
     * @return ProductGift
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
}