<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Modules
 *
 * @ORM\Table(name="modules")
 * @ORM\Entity
 */
class Modules
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
     * @ORM\Column(name="name", type="string", length=99)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="object_name", type="string", length=99)
     */
    private $objectName;

    /**
     * @var string
     *
     * @ORM\Column(name="module_type", type="string", length=99)
     */
    private $moduleType;

    /**
     * @var string
     *
     * @ORM\Column(name="parameters", type="text")
     */
    private $parameters;

    /**
     * @var string
     *
     * @ORM\Column(name="position", type="string", length=99)
     */
    private $position;

    /**
     * @var string
     *
     * @ORM\Column(name="layout", type="string", length=99)
     */
    private $layout;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="enabled", type="integer")
     */
    private $enabled;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="ordering", type="integer")
     */
    private $ordering;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=99)
     */
    private $template;

    /**
     * @var string
     *
     * @ORM\Column(name="auto_enable", type="text")
     */
    private $autoEnable;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=5)
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="access", type="text", nullable=true)
     */
    private $access;
    
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
     * @return Modules
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
     * Set objectName
     *
     * @param string $objectName
     * @return Modules
     */
    public function setObjectName($objectName)
    {
        $this->objectName = $objectName;
    
        return $this;
    }

    /**
     * Get objectName
     *
     * @return string 
     */
    public function getObjectName()
    {
        return $this->objectName;
    }

    /**
     * Set moduleType
     *
     * @param string $moduleType
     * @return Modules
     */
    public function setModuleType($moduleType)
    {
        $this->moduleType = $moduleType;
    
        return $this;
    }

    /**
     * Get moduleType
     *
     * @return string 
     */
    public function getModuleType()
    {
        return $this->moduleType;
    }

    /**
     * Set parameters
     *
     * @param string $parameters
     * @return Modules
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    
        return $this;
    }

    /**
     * Get parameters
     *
     * @return string 
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set position
     *
     * @param string $position
     * @return Modules
     */
    public function setPosition($position)
    {
        $this->position = $position;
    
        return $this;
    }

    /**
     * Get position
     *
     * @return string 
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set enabled
     *
     * @param integer $enabled
     * @return Modules
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
     * Set template
     *
     * @param string $template
     * @return Modules
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
     * Set ordering
     *
     * @param integer $ordering
     * @return Modules
     */
    public function setOrdering($ordering)
    {
        $this->ordering = $ordering;
    
        return $this;
    }

    /**
     * Get ordering
     *
     * @return integer 
     */
    public function getOrdering()
    {
        return $this->ordering;
    }

    /**
     * Set layout
     *
     * @param string $layout
     * @return Modules
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    
        return $this;
    }

    /**
     * Get layout
     *
     * @return string 
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Set autoEnable
     *
     * @param string $autoEnable
     * @return Modules
     */
    public function setAutoEnable($autoEnable)
    {
        $this->autoEnable = $autoEnable;
    
        return $this;
    }

    /**
     * Get autoEnable
     *
     * @return string 
     */
    public function getAutoEnable()
    {
        return $this->autoEnable;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return Modules
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
     * Set access
     *
     * @param string $access
     * @return Modules
     */
    public function setAccess($access)
    {
        $this->access = $access;
    
        return $this;
    }

    /**
     * Get access
     *
     * @return string 
     */
    public function getAccess()
    {
        return $this->access;
    }
}