<?php

namespace Files\FilesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FileEditPages
 *
 * @ORM\Table(name="fileeditpages")
 * @ORM\Entity
 */
class FileEditPages
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
     * @ORM\Column(name="only_own", type="integer")
     */
    private $onlyOwn;

    /**
     * @var integer
     *
     * @ORM\Column(name="reset_file_enabled", type="integer")
     */
    private $resetFileEnabled;

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
     * @return FileEditPages
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
     * @return FileEditPages
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
     * @return FileEditPages
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
     * Set onlyAutorized
     *
     * @param integer $onlyAutorized
     * @return FileEditPages
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

    /**
     * Set onlyOwn
     *
     * @param integer $onlyOwn
     * @return FileEditPages
     */
    public function setOnlyOwn($onlyOwn)
    {
        $this->onlyOwn = $onlyOwn;
    
        return $this;
    }

    /**
     * Get onlyOwn
     *
     * @return integer 
     */
    public function getOnlyOwn()
    {
        return $this->onlyOwn;
    }

    /**
     * Set resetFileEnabled
     *
     * @param integer $resetFileEnabled
     * @return FileEditPages
     */
    public function setResetFileEnabled($resetFileEnabled)
    {
        $this->resetFileEnabled = $resetFileEnabled;
    
        return $this;
    }

    /**
     * Get resetFileEnabled
     *
     * @return integer 
     */
    public function getResetFileEnabled()
    {
        return $this->resetFileEnabled;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return FileEditPages
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
