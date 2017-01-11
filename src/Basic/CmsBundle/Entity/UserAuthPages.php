<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserAuthPages
 *
 * @ORM\Table(name="userauthpages")
 * @ORM\Entity
 */
class UserAuthPages
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
     * @ORM\Column(name="is_default", type="integer", nullable=true)
     */
    private $isDefault;

    /**
     * @var string
     *
     * @ORM\Column(name="redirect_path", type="string", length=255)
     */
    private $redirectPath;

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
     * @return UserAuthPages
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
     * @return UserAuthPages
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
     * Set isDefault
     *
     * @param integer $isDefault
     * @return UserAuthPages
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;
    
        return $this;
    }

    /**
     * Get isDefault
     *
     * @return integer 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * Set redirectPath
     *
     * @param string $redirectPath
     * @return UserAuthPages
     */
    public function setRedirectPath($redirectPath)
    {
        $this->redirectPath = $redirectPath;
    
        return $this;
    }

    /**
     * Get redirectPath
     *
     * @return string 
     */
    public function getRedirectPath()
    {
        return $this->redirectPath;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return UserAuthPages
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