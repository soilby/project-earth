<?php

namespace Extended\ShopImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductImport
 *
 * @ORM\Table(name="productimport")
 * @ORM\Entity
 */
class ProductImport
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
     * @ORM\Column(name="import_type", type="string", length=99)
     */
    private $importType;

    /**
     * @var integer
     *
     * @ORM\Column(name="import_id", type="integer")
     */
    private $importId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="import_date", type="datetime")
     */
    private $importDate;


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
     * @return ProductImport
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
     * Set importType
     *
     * @param string $importType
     * @return ProductImport
     */
    public function setImportType($importType)
    {
        $this->importType = $importType;
    
        return $this;
    }

    /**
     * Get importType
     *
     * @return string 
     */
    public function getImportType()
    {
        return $this->importType;
    }

    /**
     * Set importId
     *
     * @param integer $importId
     * @return ProductImport
     */
    public function setImportId($importId)
    {
        $this->importId = $importId;
    
        return $this;
    }

    /**
     * Get importId
     *
     * @return integer 
     */
    public function getImportId()
    {
        return $this->importId;
    }

    /**
     * Set importDate
     *
     * @param \DateTime $importDate
     * @return ProductImport
     */
    public function setImportDate($importDate)
    {
        $this->importDate = $importDate;
    
        return $this;
    }

    /**
     * Get importDate
     *
     * @return \DateTime 
     */
    public function getImportDate()
    {
        return $this->importDate;
    }
}
