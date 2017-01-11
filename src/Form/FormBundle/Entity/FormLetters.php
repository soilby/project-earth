<?php

namespace Form\FormBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FormLetters
 *
 * @ORM\Table(name="formletters")
 * @ORM\Entity
 */
class FormLetters
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
     * @ORM\Column(name="seo_page_object", type="string", length=99)
     */
    private $seoPageObject;

    /**
     * @var string
     *
     * @ORM\Column(name="seo_page_action", type="string", length=99)
     */
    private $seoPageAction;

    /**
     * @var integer
     *
     * @ORM\Column(name="seo_page_id", type="integer")
     */
    private $seoPageId;

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
     * @ORM\Column(name="viewed", type="integer")
     */
    private $viewed;

    /**
     * @var integer
     *
     * @ORM\Column(name="view_user_id", type="integer", nullable=true)
     */
    private $viewUserId;

    /**
     * @var integer
     *
     * @ORM\Column(name="form_id", type="integer")
     */
    private $formId;
    
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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set seoPageObject
     *
     * @param string $seoPageObject
     * @return FormLetters
     */
    public function setSeoPageObject($seoPageObject)
    {
        $this->seoPageObject = $seoPageObject;
    
        return $this;
    }

    /**
     * Get seoPageObject
     *
     * @return string 
     */
    public function getSeoPageObject()
    {
        return $this->seoPageObject;
    }

    /**
     * Set seoPageAction
     *
     * @param string $seoPageAction
     * @return FormLetters
     */
    public function setSeoPageAction($seoPageAction)
    {
        $this->seoPageAction = $seoPageAction;
    
        return $this;
    }

    /**
     * Get seoPageAction
     *
     * @return string 
     */
    public function getSeoPageAction()
    {
        return $this->seoPageAction;
    }

    /**
     * Set seoPageId
     *
     * @param integer $seoPageId
     * @return FormLetters
     */
    public function setSeoPageId($seoPageId)
    {
        $this->seoPageId = $seoPageId;
    
        return $this;
    }

    /**
     * Get seoPageId
     *
     * @return integer 
     */
    public function getSeoPageId()
    {
        return $this->seoPageId;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return FormLetters
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
     * @return FormLetters
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
     * Set viewed
     *
     * @param integer $viewed
     * @return FormLetters
     */
    public function setViewed($viewed)
    {
        $this->viewed = $viewed;
    
        return $this;
    }

    /**
     * Get viewed
     *
     * @return integer 
     */
    public function getViewed()
    {
        return $this->viewed;
    }

    /**
     * Set viewUserId
     *
     * @param integer $viewUserId
     * @return FormLetters
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
     * @return FormLetters
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
     * @return FormLetters
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
     * @return FormLetters
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
     * Set formId
     *
     * @param integer $formId
     * @return FormLetters
     */
    public function setFormId($formId)
    {
        $this->formId = $formId;
    
        return $this;
    }

    /**
     * Get formId
     *
     * @return integer 
     */
    public function getFormId()
    {
        return $this->formId;
    }
}