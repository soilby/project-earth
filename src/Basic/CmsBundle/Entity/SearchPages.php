<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SearchPages
 *
 * @ORM\Table(name="searchpages")
 * @ORM\Entity
 */
class SearchPages
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
     * @ORM\Column(name="template", type="string", length=99, nullable=true)
     */
    private $template;

    /**
     * @var string
     *
     * @ORM\Column(name="object_options", type="text")
     */
    private $objectOptions;

    /**
     * @var integer
     *
     * @ORM\Column(name="count_in_page", type="integer")
     */
    private $countInPage;

    /**
     * @var integer
     *
     * @ORM\Column(name="get_count_in_page", type="integer")
     */
    private $getCountInPage;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort_direction", type="integer")
     */
    private $sortDirection;


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
     * @return SearchPages
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
     * @return SearchPages
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
     * @return SearchPages
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
     * Set objectOptions
     *
     * @param string $objectOptions
     * @return SearchPages
     */
    public function setObjectOptions($objectOptions)
    {
        $this->objectOptions = $objectOptions;
    
        return $this;
    }

    /**
     * Get objectOptions
     *
     * @return string 
     */
    public function getObjectOptions()
    {
        return $this->objectOptions;
    }

    /**
     * Set countInPage
     *
     * @param integer $countInPage
     * @return SearchPages
     */
    public function setCountInPage($countInPage)
    {
        $this->countInPage = $countInPage;
    
        return $this;
    }

    /**
     * Get countInPage
     *
     * @return integer 
     */
    public function getCountInPage()
    {
        return $this->countInPage;
    }

    /**
     * Set getCountInPage
     *
     * @param integer $getCountInPage
     * @return SearchPages
     */
    public function setGetCountInPage($getCountInPage)
    {
        $this->getCountInPage = $getCountInPage;
    
        return $this;
    }

    /**
     * Get getCountInPage
     *
     * @return integer 
     */
    public function getGetCountInPage()
    {
        return $this->getCountInPage;
    }

    /**
     * Set sortDirection
     *
     * @param integer $sortDirection
     * @return SearchPages
     */
    public function setSortDirection($sortDirection)
    {
        $this->sortDirection = $sortDirection;
    
        return $this;
    }

    /**
     * Get sortDirection
     *
     * @return integer 
     */
    public function getSortDirection()
    {
        return $this->sortDirection;
    }
}