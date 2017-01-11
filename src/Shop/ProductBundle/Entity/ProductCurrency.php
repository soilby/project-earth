<?php

namespace Shop\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductCurrency
 *
 * @ORM\Table(name="productcurrency")
 * @ORM\Entity
 */
class ProductCurrency
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="prefix", type="string", length=99)
     */
    private $prefix;

    /**
     * @var string
     *
     * @ORM\Column(name="postfix", type="string", length=99)
     */
    private $postfix;

    /**
     * @var integer
     *
     * @ORM\Column(name="decimal_signs", type="integer")
     */
    private $decimalSigns;

    /**
     * @var string
     *
     * @ORM\Column(name="decimal_sep", type="string", length=5)
     */
    private $decimalSep;

    /**
     * @var string
     *
     * @ORM\Column(name="thousand_sep", type="string", length=5)
     */
    private $thousandSep;

    /**
     * @var integer
     *
     * @ORM\Column(name="main", type="integer")
     */
    private $main;

    /**
     * @var integer
     *
     * @ORM\Column(name="enabled", type="integer")
     */
    private $enabled;
    
    /**
     * @var float
     *
     * @ORM\Column(name="min_note", type="decimal", precision=17, scale=3)
     */
    private $minNote;
    
    /**
     * @var float
     *
     * @ORM\Column(name="exchange_index", type="float")
     */
    private $exchangeIndex;

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
     * @return ProductCurrency
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
     * Set prefix
     *
     * @param string $prefix
     * @return ProductCurrency
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    
        return $this;
    }

    /**
     * Get prefix
     *
     * @return string 
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set postfix
     *
     * @param string $postfix
     * @return ProductCurrency
     */
    public function setPostfix($postfix)
    {
        $this->postfix = $postfix;
    
        return $this;
    }

    /**
     * Get postfix
     *
     * @return string 
     */
    public function getPostfix()
    {
        return $this->postfix;
    }

    /**
     * Set decimalSigns
     *
     * @param integer $decimalSigns
     * @return ProductCurrency
     */
    public function setDecimalSigns($decimalSigns)
    {
        $this->decimalSigns = $decimalSigns;
    
        return $this;
    }

    /**
     * Get decimalSigns
     *
     * @return integer 
     */
    public function getDecimalSigns()
    {
        return $this->decimalSigns;
    }

    /**
     * Set decimalSep
     *
     * @param string $decimalSep
     * @return ProductCurrency
     */
    public function setDecimalSep($decimalSep)
    {
        $this->decimalSep = $decimalSep;
    
        return $this;
    }

    /**
     * Get decimalSep
     *
     * @return string 
     */
    public function getDecimalSep()
    {
        return $this->decimalSep;
    }


    /**
     * Set thousandSep
     *
     * @param string $thousandSep
     * @return ProductCurrency
     */
    public function setThousandSep($thousandSep)
    {
        $this->thousandSep = $thousandSep;
    
        return $this;
    }

    /**
     * Get thousandSep
     *
     * @return string 
     */
    public function getThousandSep()
    {
        return $this->thousandSep;
    }

    /**
     * Set main
     *
     * @param integer $main
     * @return ProductCurrency
     */
    public function setMain($main)
    {
        $this->main = $main;
    
        return $this;
    }

    /**
     * Get main
     *
     * @return integer 
     */
    public function getMain()
    {
        return $this->main;
    }

    /**
     * Set enabled
     *
     * @param integer $enabled
     * @return ProductCurrency
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
     * Set minNote
     *
     * @param float $minNote
     * @return ProductCurrency
     */
    public function setMinNote($minNote)
    {
        $this->minNote = $minNote;
    
        return $this;
    }

    /**
     * Get minNote
     *
     * @return float 
     */
    public function getMinNote()
    {
        return $this->minNote;
    }

    /**
     * Set exchangeIndex
     *
     * @param float $exchangeIndex
     * @return ProductCurrency
     */
    public function setExchangeIndex($exchangeIndex)
    {
        $this->exchangeIndex = $exchangeIndex;
    
        return $this;
    }

    /**
     * Get exchangeIndex
     *
     * @return float 
     */
    public function getExchangeIndex()
    {
        return $this->exchangeIndex;
    }
}