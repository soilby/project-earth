<?php

namespace Extended\ShopExportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductExportPages
 *
 * @ORM\Table(name="productexportpages")
 * @ORM\Entity
 */
class ProductExportPages
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
     * @ORM\Column(name="file_name", type="string", length=99)
     */
    private $fileName;

    /**
     * @var integer
     *
     * @ORM\Column(name="currency_id", type="integer")
     */
    private $currencyId;

    /**
     * @var integer
     *
     * @ORM\Column(name="only_on_stock", type="integer")
     */
    private $onlyOnStock;

    /**
     * @var string
     *
     * @ORM\Column(name="categories", type="text")
     */
    private $categories;


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
     * Set fileName
     *
     * @param string $fileName
     * @return ProductExportPages
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    
        return $this;
    }

    /**
     * Get fileName
     *
     * @return string 
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set currencyId
     *
     * @param integer $currencyId
     * @return ProductExportPages
     */
    public function setCurrencyId($currencyId)
    {
        $this->currencyId = $currencyId;
    
        return $this;
    }

    /**
     * Get currencyId
     *
     * @return integer 
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    /**
     * Set onlyOnStock
     *
     * @param integer $onlyOnStock
     * @return ProductExportPages
     */
    public function setOnlyOnStock($onlyOnStock)
    {
        $this->onlyOnStock = $onlyOnStock;
    
        return $this;
    }

    /**
     * Get onlyOnStock
     *
     * @return integer 
     */
    public function getOnlyOnStock()
    {
        return $this->onlyOnStock;
    }

    /**
     * Set categories
     *
     * @param string $categories
     * @return ProductExportPages
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    
        return $this;
    }

    /**
     * Get categories
     *
     * @return string 
     */
    public function getCategories()
    {
        return $this->categories;
    }
}
