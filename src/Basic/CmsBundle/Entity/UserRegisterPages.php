<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserRegisterPages
 *
 * @ORM\Table(name="userregisterpages")
 * @ORM\Entity
 */
class UserRegisterPages
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
     * @var integer
     *
     * @ORM\Column(name="activation_enabled", type="integer")
     */
    private $activationEnabled;

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
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=99, nullable = true)
     */
    private $template;

    /**
     * @var string
     *
     * @ORM\Column(name="user_type", type="string", length=99)
     */
    private $userType;

    /**
     * @var string
     *
     * @ORM\Column(name="user_template", type="string", length=99)
     */
    private $userTemplate;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="role", type="integer")
     */
    private $role;

    /**
     * @var integer
     *
     * @ORM\Column(name="seopage_enabled", type="integer")
     */
    private $seopageEnabled;

    /**
     * @var string
     *
     * @ORM\Column(name="seopage_url", type="string", length=255)
     */
    private $seopageUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="seopage_template", type="string", length=99)
     */
    private $seopageTemplate;

    /**
     * @var string
     *
     * @ORM\Column(name="seopage_modules", type="text")
     */
    private $seopageModules;

    /**
     * @var string
     *
     * @ORM\Column(name="seopage_locale", type="string", length=5)
     */
    private $seopageLocale;

    /**
     * @var string
     *
     * @ORM\Column(name="seopage_access", type="text")
     */
    private $seopageAccess;


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
     * @return UserRegisterPages
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
     * @return UserRegisterPages
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
     * Set activationEnabled
     *
     * @param integer $activationEnabled
     * @return UserRegisterPages
     */
    public function setActivationEnabled($activationEnabled)
    {
        $this->activationEnabled = $activationEnabled;
    
        return $this;
    }

    /**
     * Get activationEnabled
     *
     * @return integer 
     */
    public function getActivationEnabled()
    {
        return $this->activationEnabled;
    }

    /**
     * Set letterText
     *
     * @param string $letterText
     * @return UserRegisterPages
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
     * @return UserRegisterPages
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
     * Set template
     *
     * @param string $template
     * @return UserRegisterPages
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
     * Set userType
     *
     * @param string $userType
     * @return UserRegisterPages
     */
    public function setUserType($userType)
    {
        $this->userType = $userType;
    
        return $this;
    }

    /**
     * Get userType
     *
     * @return string 
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * Set role
     *
     * @param integer $role
     * @return UserRegisterPages
     */
    public function setRole($role)
    {
        $this->role = $role;
    
        return $this;
    }

    /**
     * Get role
     *
     * @return integer 
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set seopageEnabled
     *
     * @param integer $seopageEnabled
     * @return UserRegisterPages
     */
    public function setSeopageEnabled($seopageEnabled)
    {
        $this->seopageEnabled = $seopageEnabled;
    
        return $this;
    }

    /**
     * Get seopageEnabled
     *
     * @return integer 
     */
    public function getSeopageEnabled()
    {
        return $this->seopageEnabled;
    }

    /**
     * Set seopageUrl
     *
     * @param string $seopageUrl
     * @return UserRegisterPages
     */
    public function setSeopageUrl($seopageUrl)
    {
        $this->seopageUrl = $seopageUrl;
    
        return $this;
    }

    /**
     * Get seopageUrl
     *
     * @return string 
     */
    public function getSeopageUrl()
    {
        return $this->seopageUrl;
    }

    /**
     * Set seopageTemplate
     *
     * @param string $seopageTemplate
     * @return UserRegisterPages
     */
    public function setSeopageTemplate($seopageTemplate)
    {
        $this->seopageTemplate = $seopageTemplate;
    
        return $this;
    }

    /**
     * Get seopageTemplate
     *
     * @return string 
     */
    public function getSeopageTemplate()
    {
        return $this->seopageTemplate;
    }

    /**
     * Set seopageModules
     *
     * @param string $seopageModules
     * @return UserRegisterPages
     */
    public function setSeopageModules($seopageModules)
    {
        $this->seopageModules = $seopageModules;
    
        return $this;
    }

    /**
     * Get seopageModules
     *
     * @return string 
     */
    public function getSeopageModules()
    {
        return $this->seopageModules;
    }

    /**
     * Set seopageLocale
     *
     * @param string $seopageLocale
     * @return UserRegisterPages
     */
    public function setSeopageLocale($seopageLocale)
    {
        $this->seopageLocale = $seopageLocale;
    
        return $this;
    }

    /**
     * Get seopageLocale
     *
     * @return string 
     */
    public function getSeopageLocale()
    {
        return $this->seopageLocale;
    }

    /**
     * Set seopageAccess
     *
     * @param string $seopageAccess
     * @return UserRegisterPages
     */
    public function setSeopageAccess($seopageAccess)
    {
        $this->seopageAccess = $seopageAccess;
    
        return $this;
    }

    /**
     * Get seopageAccess
     *
     * @return string 
     */
    public function getSeopageAccess()
    {
        return $this->seopageAccess;
    }

    /**
     * Set userTemplate
     *
     * @param string $userTemplate
     * @return UserRegisterPages
     */
    public function setUserTemplate($userTemplate)
    {
        $this->userTemplate = $userTemplate;
    
        return $this;
    }

    /**
     * Get userTemplate
     *
     * @return string 
     */
    public function getUserTemplate()
    {
        return $this->userTemplate;
    }
}