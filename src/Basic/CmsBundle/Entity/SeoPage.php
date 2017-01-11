<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SeoPage
 *
 * @ORM\Table(name="seopage")
 * @ORM\Entity
 */
class SeoPage
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
     * @ORM\Column(name="url", type="string", length=255)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    private $description;
    
    /**
     * @var string
     *
     * @ORM\Column(name="content_type", type="string", length=99)
     */
    private $contentType;

    /**
     * @var string
     *
     * @ORM\Column(name="content_action", type="string", length=99)
     */
    private $contentAction;

    /**
     * @var integer
     *
     * @ORM\Column(name="content_id", type="integer")
     */
    private $contentId;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=5)
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="modules", type="text")
     */
    private $modules;

    /**
     * @var string
     *
     * @ORM\Column(name="bread_crumbs", type="text")
     */
    private $breadCrumbs;

    /**
     * @var string
     *
     * @ORM\Column(name="bread_crumbs_rel", type="text")
     */
    private $breadCrumbsRel;
    
    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=99)
     */
    private $template;

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
     * Set url
     *
     * @param string $url
     * @return SeoPage
     */
    public function setUrl($url)
    {
        $this->url = $url;
    
        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set contentType
     *
     * @param string $contentType
     * @return SeoPage
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    
        return $this;
    }

    /**
     * Get contentType
     *
     * @return string 
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Set contentAction
     *
     * @param string $contentAction
     * @return SeoPage
     */
    public function setContentAction($contentAction)
    {
        $this->contentAction = $contentAction;
    
        return $this;
    }

    /**
     * Get contentAction
     *
     * @return string 
     */
    public function getContentAction()
    {
        return $this->contentAction;
    }

    /**
     * Set contentId
     *
     * @param integer $contentId
     * @return SeoPage
     */
    public function setContentId($contentId)
    {
        $this->contentId = $contentId;
    
        return $this;
    }

    /**
     * Get contentId
     *
     * @return integer 
     */
    public function getContentId()
    {
        return $this->contentId;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return SeoPage
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
     * Set modules
     *
     * @param string $modules
     * @return SeoPage
     */
    public function setModules($modules)
    {
        $this->modules = $modules;
    
        return $this;
    }

    /**
     * Get modules
     *
     * @return string 
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * Set breadCrumbs
     *
     * @param string $breadCrumbs
     * @return SeoPage
     */
    public function setBreadCrumbs($breadCrumbs)
    {
        $this->breadCrumbs = $breadCrumbs;
    
        return $this;
    }

    /**
     * Get breadCrumbs
     *
     * @return string 
     */
    public function getBreadCrumbs()
    {
        return $this->breadCrumbs;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return SeoPage
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
     * Set description
     *
     * @param string $description
     * @return SeoPage
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set breadCrumbsRel
     *
     * @param string $breadCrumbsRel
     * @return SeoPage
     */
    public function setBreadCrumbsRel($breadCrumbsRel)
    {
        $this->breadCrumbsRel = $breadCrumbsRel;
    
        return $this;
    }

    /**
     * Get breadCrumbsRel
     *
     * @return string 
     */
    public function getBreadCrumbsRel()
    {
        return $this->breadCrumbsRel;
    }

    /**
     * Set access
     *
     * @param string $access
     * @return SeoPage
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