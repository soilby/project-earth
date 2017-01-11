<?php

namespace Form\FormBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FormFields
 *
 * @ORM\Table(name="formfields")
 * @ORM\Entity
 */
class FormFields
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
     * @ORM\Column(name="name", type="string", length=4096)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=99)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="parameters", type="text")
     */
    private $parameters;

    /**
     * @var integer
     *
     * @ORM\Column(name="form_id", type="integer")
     */
    private $formId;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordering", type="integer")
     */
    private $ordering;

    /**
     * @var integer
     *
     * @ORM\Column(name="nesting", type="integer")
     */
    private $nesting;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="enabled", type="integer")
     */
    private $enabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="required", type="integer")
     */
    private $required;
    

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
     * @return FormFields
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
     * Set type
     *
     * @param string $type
     * @return FormFields
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set parameters
     *
     * @param string $parameters
     * @return FormFields
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
     * Set formId
     *
     * @param integer $formId
     * @return FormFields
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

    /**
     * Set ordering
     *
     * @param integer $ordering
     * @return FormFields
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
     * Set enabled
     *
     * @param integer $enabled
     * @return FormFields
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
     * Set nesting
     *
     * @param integer $nesting
     * @return FormFields
     */
    public function setNesting($nesting)
    {
        $this->nesting = $nesting;
    
        return $this;
    }

    /**
     * Get nesting
     *
     * @return integer 
     */
    public function getNesting()
    {
        return $this->nesting;
    }

    /**
     * Set required
     *
     * @param integer $required
     * @return FormFields
     */
    public function setRequired($required)
    {
        $this->required = $required;
    
        return $this;
    }

    /**
     * Get required
     *
     * @return integer 
     */
    public function getRequired()
    {
        return $this->required;
    }
}