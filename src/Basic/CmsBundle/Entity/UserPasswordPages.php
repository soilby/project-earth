<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserPasswordPages
 *
 * @ORM\Table(name="userpasswordpages")
 * @ORM\Entity
 */
class UserPasswordPages
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
     * @var string
     *
     * @ORM\Column(name="letter_text", type="text")
     */
    private $letterText;

    /**
     * @var integer
     *
     * @ORM\Column(name="captcha_enabled", type="integer")
     */
    private $captchaEnabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_password_type", type="integer")
     */
    private $allowPasswordType;

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
     * Set title
     *
     * @param string $title
     * @return UserPasswordPage
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
     * Set enabled
     *
     * @param integer $enabled
     * @return UserPasswordPage
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
     * Set letterText
     *
     * @param string $letterText
     * @return UserPasswordPage
     */
    public function setLetterText($letterText)
    {
        $this->letterText = $letterText;
    
        return $this;
    }

    /**
     * Get letterText
     *
     * @return string 
     */
    public function getLetterText()
    {
        return $this->letterText;
    }

    /**
     * Set captchaEnabled
     *
     * @param integer $captchaEnabled
     * @return UserPasswordPage
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

    /**
     * Set allowPasswordType
     *
     * @param integer $allowPasswordType
     * @return UserPasswordPage
     */
    public function setAllowPasswordType($allowPasswordType)
    {
        $this->allowPasswordType = $allowPasswordType;
    
        return $this;
    }

    /**
     * Get allowPasswordType
     *
     * @return integer 
     */
    public function getAllowPasswordType()
    {
        return $this->allowPasswordType;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return UserPasswordPage
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
}