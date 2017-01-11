<?php

namespace Shop\BasketBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductBaskets
 *
 * @ORM\Table(name="productbaskets")
 * @ORM\Entity
 */
class ProductBaskets
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
     * @ORM\Column(name="names", type="text")
     */
    private $names;

    /**
     * @var integer
     *
     * @ORM\Column(name="enabled", type="integer")
     */
    private $enabled;

    /**
     * @var string
     *
     * @ORM\Column(name="templates", type="text", nullable=true)
     */
    private $templates;

    /**
     * @var integer
     *
     * @ORM\Column(name="step_count", type="integer")
     */
    private $stepCount;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="send_email", type="integer")
     */
    private $sendEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

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
     * Set enabled
     *
     * @param integer $enabled
     * @return ProductBaskets
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
     * Set names
     *
     * @param string $names
     * @return ProductBaskets
     */
    public function setNames($names)
    {
        $this->names = $names;
    
        return $this;
    }

    /**
     * Get names
     *
     * @return string 
     */
    public function getNames()
    {
        return $this->names;
    }

    /**
     * Set templates
     *
     * @param string $templates
     * @return ProductBaskets
     */
    public function setTemplates($templates)
    {
        $this->templates = $templates;
    
        return $this;
    }

    /**
     * Get templates
     *
     * @return string 
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * Set stepCount
     *
     * @param integer $stepCount
     * @return ProductBaskets
     */
    public function setStepCount($stepCount)
    {
        $this->stepCount = $stepCount;
    
        return $this;
    }

    /**
     * Get stepCount
     *
     * @return integer 
     */
    public function getStepCount()
    {
        return $this->stepCount;
    }

    /**
     * Set sendEmail
     *
     * @param integer $sendEmail
     * @return ProductBaskets
     */
    public function setSendEmail($sendEmail)
    {
        $this->sendEmail = $sendEmail;
    
        return $this;
    }

    /**
     * Get sendEmail
     *
     * @return integer 
     */
    public function getSendEmail()
    {
        return $this->sendEmail;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return ProductBaskets
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }
}