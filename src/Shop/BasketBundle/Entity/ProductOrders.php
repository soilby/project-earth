<?php

namespace Shop\BasketBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductOrders
 *
 * @ORM\Table(name="productorders")
 * @ORM\Entity
 */
class ProductOrders
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
     * @ORM\Column(name="locale", type="string", length=5)
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="ip_address", type="string", length=99)
     */
    private $ipAddress;

    /**
     * @var integer
     *
     * @ORM\Column(name="view_user_id", type="integer", nullable=true)
     */
    private $viewUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="view_date", type="datetime", nullable=true)
     */
    private $viewDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime")
     */
    private $createDate;

    /**
     * @var string
     *
     * @ORM\Column(name="fields", type="text")
     */
    private $fields;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="pay_status", type="integer")
     */
    private $payStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_system", type="string", length=99)
     */
    private $paySystem;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_info", type="text")
     */
    private $payInfo;

    /**
     * @var string
     *
     * @ORM\Column(name="ship_system", type="string", length=99)
     */
    private $shipSystem;

    /**
     * @var string
     *
     * @ORM\Column(name="products", type="text")
     */
    private $products;

    /**
     * @var string
     *
     * @ORM\Column(name="summ_info", type="text")
     */
    private $summInfo;

    /**
     * @var float
     *
     * @ORM\Column(name="summ", type="decimal", precision=17, scale=3)
     */
    private $summ;

    /**
     * @var string
     *
     * @ORM\Column(name="summ_currencies", type="text")
     */
    private $summCurrencies;
    

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
     * Set locale
     *
     * @param string $locale
     * @return ProductOrders
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    
        return $this;
    }

    /**
     * Get locale
     *
     * @return string 
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return ProductOrders
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    
        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return string 
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set viewUserId
     *
     * @param integer $viewUserId
     * @return ProductOrders
     */
    public function setViewUserId($viewUserId)
    {
        $this->viewUserId = $viewUserId;
    
        return $this;
    }

    /**
     * Get viewUserId
     *
     * @return integer 
     */
    public function getViewUserId()
    {
        return $this->viewUserId;
    }

    /**
     * Set viewDate
     *
     * @param \DateTime $viewDate
     * @return ProductOrders
     */
    public function setViewDate($viewDate)
    {
        $this->viewDate = $viewDate;
    
        return $this;
    }

    /**
     * Get viewDate
     *
     * @return \DateTime 
     */
    public function getViewDate()
    {
        return $this->viewDate;
    }

    /**
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return ProductOrders
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
     * Set fields
     *
     * @param string $fields
     * @return ProductOrders
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    
        return $this;
    }

    /**
     * Get fields
     *
     * @return string 
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return ProductOrders
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set payStatus
     *
     * @param integer $payStatus
     * @return ProductOrders
     */
    public function setPayStatus($payStatus)
    {
        $this->payStatus = $payStatus;
    
        return $this;
    }

    /**
     * Get payStatus
     *
     * @return integer 
     */
    public function getPayStatus()
    {
        return $this->payStatus;
    }

    /**
     * Set paySystem
     *
     * @param string $paySystem
     * @return ProductOrders
     */
    public function setPaySystem($paySystem)
    {
        $this->paySystem = $paySystem;
    
        return $this;
    }

    /**
     * Get paySystem
     *
     * @return string 
     */
    public function getPaySystem()
    {
        return $this->paySystem;
    }

    /**
     * Set payInfo
     *
     * @param string $payInfo
     * @return ProductOrders
     */
    public function setPayInfo($payInfo)
    {
        $this->payInfo = $payInfo;
    
        return $this;
    }

    /**
     * Get payInfo
     *
     * @return string 
     */
    public function getPayInfo()
    {
        return $this->payInfo;
    }


    /**
     * Set products
     *
     * @param string $products
     * @return ProductOrders
     */
    public function setProducts($products)
    {
        $this->products = $products;
    
        return $this;
    }

    /**
     * Get products
     *
     * @return string 
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Set summInfo
     *
     * @param string $summInfo
     * @return ProductOrders
     */
    public function setSummInfo($summInfo)
    {
        $this->summInfo = $summInfo;
    
        return $this;
    }

    /**
     * Get summInfo
     *
     * @return string 
     */
    public function getSummInfo()
    {
        return $this->summInfo;
    }

    /**
     * Set summ
     *
     * @param float $summ
     * @return ProductOrders
     */
    public function setSumm($summ)
    {
        $this->summ = $summ;
    
        return $this;
    }

    /**
     * Get summ
     *
     * @return float 
     */
    public function getSumm()
    {
        return $this->summ;
    }

    /**
     * Set shipSystem
     *
     * @param string $shipSystem
     * @return ProductOrders
     */
    public function setShipSystem($shipSystem)
    {
        $this->shipSystem = $shipSystem;
    
        return $this;
    }

    /**
     * Get shipSystem
     *
     * @return string 
     */
    public function getShipSystem()
    {
        return $this->shipSystem;
    }

    /**
     * Set summCurrencies
     *
     * @param string $summCurrencies
     * @return ProductOrders
     */
    public function setSummCurrencies($summCurrencies)
    {
        $this->summCurrencies = $summCurrencies;
    
        return $this;
    }

    /**
     * Get summCurrencies
     *
     * @return string 
     */
    public function getSummCurrencies()
    {
        return $this->summCurrencies;
    }
}