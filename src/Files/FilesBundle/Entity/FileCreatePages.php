<?php

namespace Files\FilesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FileCreatePages
 *
 * @ORM\Table(name="filecreatepages")
 * @ORM\Entity
 */
class FileCreatePages
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
     * @ORM\Column(name="captcha_enabled", type="integer")
     */
    private $captchaEnabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="only_autorized", type="integer")
     */
    private $onlyAutorized;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="category_mode", type="integer")
     */
    private $categoryMode;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer")
     */
    private $categoryId;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=99, nullable=true)
     */
    private $template;

    /**
     * @var integer
     *
     * @ORM\Column(name="file_enabled", type="integer")
     */
    private $fileEnabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="file_only_autorized", type="integer")
     */
    private $fileOnlyAutorized;

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
     * @var string
     *
     * @ORM\Column(name="file_template", type="string", length=99)
     */
    private $fileTemplate;


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
     * @return FileCreatePages
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
     * @return FileCreatePages
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
     * Set captchaEnabled
     *
     * @param integer $captchaEnabled
     * @return FileCreatePages
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
     * Set categoryMode
     *
     * @param integer $categoryMode
     * @return FileCreatePages
     */
    public function setCategoryMode($categoryMode)
    {
        $this->categoryMode = $categoryMode;
    
        return $this;
    }

    /**
     * Get categoryMode
     *
     * @return integer 
     */
    public function getCategoryMode()
    {
        return $this->categoryMode;
    }

    /**
     * Set categoryId
     *
     * @param integer $categoryId
     * @return FileCreatePages
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    
        return $this;
    }

    /**
     * Get categoryId
     *
     * @return integer 
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return FileCreatePages
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
     * Set fileEnabled
     *
     * @param integer $fileEnabled
     * @return FileCreatePages
     */
    public function setFileEnabled($fileEnabled)
    {
        $this->fileEnabled = $fileEnabled;
    
        return $this;
    }

    /**
     * Get fileEnabled
     *
     * @return integer 
     */
    public function getFileEnabled()
    {
        return $this->fileEnabled;
    }

    /**
     * Set fileOnlyAutorized
     *
     * @param integer $fileOnlyAutorized
     * @return FileCreatePages
     */
    public function setFileOnlyAutorized($fileOnlyAutorized)
    {
        $this->fileOnlyAutorized = $fileOnlyAutorized;
    
        return $this;
    }

    /**
     * Get fileOnlyAutorized
     *
     * @return integer 
     */
    public function getFileOnlyAutorized()
    {
        return $this->fileOnlyAutorized;
    }

    /**
     * Set seopageEnabled
     *
     * @param integer $seopageEnabled
     * @return FileCreatePages
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
     * @return FileCreatePages
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
     * @return FileCreatePages
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
     * @return FileCreatePages
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
     * @return FileCreatePages
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
     * @return FileCreatePages
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
     * Set fileTemplate
     *
     * @param string $fileTemplate
     * @return FileCreatePages
     */
    public function setFileTemplate($fileTemplate)
    {
        $this->fileTemplate = $fileTemplate;
    
        return $this;
    }

    /**
     * Get fileTemplate
     *
     * @return string 
     */
    public function getFileTemplate()
    {
        return $this->fileTemplate;
    }

    /**
     * Set onlyAutorized
     *
     * @param integer $onlyAutorized
     * @return FileCreatePages
     */
    public function setOnlyAutorized($onlyAutorized)
    {
        $this->onlyAutorized = $onlyAutorized;
    
        return $this;
    }

    /**
     * Get onlyAutorized
     *
     * @return integer 
     */
    public function getOnlyAutorized()
    {
        return $this->onlyAutorized;
    }
}