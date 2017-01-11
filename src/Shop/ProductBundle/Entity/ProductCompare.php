<?php

namespace Shop\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductCompare
 *
 * @ORM\Table(name="productcompare")
 * @ORM\Entity
 */
class ProductCompare
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
     * @ORM\Column(name="name", type="string", length=4096)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="product_limit", type="integer")
     */
    private $productLimit;

    /**
     * @var integer
     *
     * @ORM\Column(name="enabled", type="integer")
     */
    private $enabled;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=99, nullable=true)
     */
    private $template;


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
     * @return ProductCompare
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
     * Set productLimit
     *
     * @param integer $productLimit
     * @return ProductCompare
     */
    public function setProductLimit($productLimit)
    {
        $this->productLimit = $productLimit;
    
        return $this;
    }

    /**
     * Get productLimit
     *
     * @return integer 
     */
    public function getProductLimit()
    {
        return $this->productLimit;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return ProductCompare
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
     * @return ProductCompare
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
}