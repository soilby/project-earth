<?php

namespace Template\MirclubBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TemplateMirclubLink
 *
 * @ORM\Table(name="templatemirclublink")
 * @ORM\Entity
 */
class TemplateMirclubLink
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
     * @var integer
     *
     * @ORM\Column(name="image_id", type="integer")
     */
    private $imageId;

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
     * Set imageId
     *
     * @param integer $imageId
     * @return TemplateMirclubLink
     */
    public function setImageId($imageId)
    {
        $this->imageId = $imageId;
    
        return $this;
    }

    /**
     * Get imageId
     *
     * @return integer 
     */
    public function getImageId()
    {
        return $this->imageId;
    }

    /**
     * Set seoPageId
     *
     * @param integer $seoPageId
     * @return TemplateMirclubLink
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
