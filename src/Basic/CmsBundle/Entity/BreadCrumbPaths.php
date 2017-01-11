<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BreadCrumbPaths
 *
 * @ORM\Table(name="breadcrumbpaths")
 * @ORM\Entity
 */
class BreadCrumbPaths
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
     * @ORM\Column(name="path", type="text")
     */
    private $path;

    /**
     * @var integer
     *
     * @ORM\Column(name="seo_page_id", type="integer")
     */
    private $seoPageId;


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
     * Set path
     *
     * @param string $path
     * @return BreadCrubmPaths
     */
    public function setPath($path)
    {
        $this->path = $path;
    
        return $this;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set seoPageId
     *
     * @param integer $seoPageId
     * @return BreadCrubmPaths
     */
    public function setSeoPageId($seoPageId)
    {
        $this->seoPageId = $seoPageId;
    
        return $this;
    }

    /**
     * Get seoPageId
     *
     * @return integer 
     */
    public function getSeoPageId()
    {
        return $this->seoPageId;
    }
}