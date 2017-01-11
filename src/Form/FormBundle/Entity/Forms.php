<?php

namespace Form\FormBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Forms
 *
 * @ORM\Table(name="forms")
 * @ORM\Entity
 */
class Forms
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
     * @ORM\Column(name="captcha_enabled", type="integer")
     */
    private $captchaEnabled;
    
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
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=4096)
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(name="enabled", type="integer")
     */
    private $enabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="creater_id", type="integer")
     */
    private $createrId;
    
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
     * Set sendEmail
     *
     * @param integer $sendEmail
     * @return Forms
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
     * @return Forms
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

    /**
     * Set title
     *
     * @param string $title
     * @return Forms
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return Forms
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
     * @return Forms
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
     * Set createrId
     *
     * @param integer $createrId
     * @return Forms
     */
    public function setCreaterId($createrId)
    {
        $this->createrId = $createrId;
    
        return $this;
    }

    /**
     * Get createrId
     *
     * @return integer 
     */
    public function getCreaterId()
    {
        return $this->createrId;
    }

    /**
     * Set captchaEnabled
     *
     * @param integer $captchaEnabled
     * @return Forms
     */
    public function setCaptchaEnabled($captchaEnabled)
    {
        $this->captchaEnabled = $captchaEnabled;
    
        return $this;
    }

    /**
     * Get captchaEnabled
     *
     * @return integer 
     */
    public function getCaptchaEnabled()
    {
        return $this->captchaEnabled;
    }
}