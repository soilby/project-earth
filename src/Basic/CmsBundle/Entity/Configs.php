<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Configs
 *
 * @ORM\Table(name="configs")
 * @ORM\Entity
 */
class Configs
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
     * @ORM\Column(name="value", type="text")
     */
    private $value;

    /**
     * @var integer
     *
     * @ORM\Column(name="serialized", type="integer")
     */
    private $serialized;

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
     * @return Configs
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
     * Set value
     *
     * @param string $value
     * @return Configs
     */
    public function setValue($value)
    {
        $this->value = $value;
    
        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set serialized
     *
     * @param integer $serialized
     * @return Configs
     */
    public function setSerialized($serialized)
    {
        $this->serialized = $serialized;
    
        return $this;
    }

    /**
     * Get serialized
     *
     * @return integer 
     */
    public function getSerialized()
    {
        return $this->serialized;
    }
}