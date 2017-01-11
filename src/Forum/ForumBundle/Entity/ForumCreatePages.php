<?php

namespace Forum\ForumBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ForumCreatePages
 *
 * @ORM\Table(name="forumcreatepages")
 * @ORM\Entity
 */
class ForumCreatePages
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
     * @var integer
     *
     * @ORM\Column(name="captcha_enabled", type="integer")
     */
    private $captchaEnabled;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=99, nullable=true)
     */
    private $template;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_enabled", type="integer")
     */
    private $forumEnabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_is_closed", type="integer")
     */
    private $forumIsClosed;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_is_important", type="integer")
     */
    private $forumIsImportant;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_is_visible", type="integer")
     */
    private $forumIsVisible;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_captcha_enabled", type="integer")
     */
    private $forumCaptchaEnabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_only_autorized_view", type="integer")
     */
    private $forumOnlyAutorizedView;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_only_autorized_post", type="integer")
     */
    private $forumOnlyAutorizedPost;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_only_autorized_download", type="integer")
     */
    private $forumOnlyAutorizedDownload;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_only_autorized_image", type="integer")
     */
    private $forumOnlyAutorizedImage;

    /**
     * @var string
     *
     * @ORM\Column(name="forum_allow_tags", type="text")
     */
    private $forumAllowTags;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_allow_style_prop", type="integer")
     */
    private $forumAllowStyleProp;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_replace_urls", type="integer")
     */
    private $forumReplaceUrls;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_message_in_page", type="integer")
     */
    private $forumMessageInPage;

    /**
     * @var string
     *
     * @ORM\Column(name="forum_moderators", type="text")
     */
    private $forumModerators;

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
     * @ORM\Column(name="forum_template", type="string", length=99)
     */
    private $forumTemplate;


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
     * @return ForumCreatePages
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
     * @return ForumCreatePages
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
     * @return ForumCreatePages
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
     * Set template
     *
     * @param string $template
     * @return ForumCreatePages
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
     * Set forumEnabled
     *
     * @param integer $forumEnabled
     * @return ForumCreatePages
     */
    public function setForumEnabled($forumEnabled)
    {
        $this->forumEnabled = $forumEnabled;
    
        return $this;
    }

    /**
     * Get forumEnabled
     *
     * @return integer 
     */
    public function getForumEnabled()
    {
        return $this->forumEnabled;
    }

    /**
     * Set forumIsClosed
     *
     * @param integer $forumIsClosed
     * @return ForumCreatePages
     */
    public function setForumIsClosed($forumIsClosed)
    {
        $this->forumIsClosed = $forumIsClosed;
    
        return $this;
    }

    /**
     * Get forumIsClosed
     *
     * @return integer 
     */
    public function getForumIsClosed()
    {
        return $this->forumIsClosed;
    }

    /**
     * Set forumIsImportant
     *
     * @param integer $forumIsImportant
     * @return ForumCreatePages
     */
    public function setForumIsImportant($forumIsImportant)
    {
        $this->forumIsImportant = $forumIsImportant;
    
        return $this;
    }

    /**
     * Get forumIsImportant
     *
     * @return integer 
     */
    public function getForumIsImportant()
    {
        return $this->forumIsImportant;
    }

    /**
     * Set forumIsVisible
     *
     * @param integer $forumIsVisible
     * @return ForumCreatePages
     */
    public function setForumIsVisible($forumIsVisible)
    {
        $this->forumIsVisible = $forumIsVisible;
    
        return $this;
    }

    /**
     * Get forumIsVisible
     *
     * @return integer 
     */
    public function getForumIsVisible()
    {
        return $this->forumIsVisible;
    }

    /**
     * Set forumCaptchaEnabled
     *
     * @param integer $forumCaptchaEnabled
     * @return ForumCreatePages
     */
    public function setForumCaptchaEnabled($forumCaptchaEnabled)
    {
        $this->forumCaptchaEnabled = $forumCaptchaEnabled;
    
        return $this;
    }

    /**
     * Get forumCaptchaEnabled
     *
     * @return integer 
     */
    public function getForumCaptchaEnabled()
    {
        return $this->forumCaptchaEnabled;
    }

    /**
     * Set forumOnlyAutorizedView
     *
     * @param integer $forumOnlyAutorizedView
     * @return ForumCreatePages
     */
    public function setForumOnlyAutorizedView($forumOnlyAutorizedView)
    {
        $this->forumOnlyAutorizedView = $forumOnlyAutorizedView;
    
        return $this;
    }

    /**
     * Get forumOnlyAutorizedView
     *
     * @return integer 
     */
    public function getForumOnlyAutorizedView()
    {
        return $this->forumOnlyAutorizedView;
    }

    /**
     * Set forumOnlyAutorizedPost
     *
     * @param integer $forumOnlyAutorizedPost
     * @return ForumCreatePages
     */
    public function setForumOnlyAutorizedPost($forumOnlyAutorizedPost)
    {
        $this->forumOnlyAutorizedPost = $forumOnlyAutorizedPost;
    
        return $this;
    }

    /**
     * Get forumOnlyAutorizedPost
     *
     * @return integer 
     */
    public function getForumOnlyAutorizedPost()
    {
        return $this->forumOnlyAutorizedPost;
    }

    /**
     * Set forumOnlyAutorizedDownload
     *
     * @param integer $forumOnlyAutorizedDownload
     * @return ForumCreatePages
     */
    public function setForumOnlyAutorizedDownload($forumOnlyAutorizedDownload)
    {
        $this->forumOnlyAutorizedDownload = $forumOnlyAutorizedDownload;
    
        return $this;
    }

    /**
     * Get forumOnlyAutorizedDownload
     *
     * @return integer 
     */
    public function getForumOnlyAutorizedDownload()
    {
        return $this->forumOnlyAutorizedDownload;
    }

    /**
     * Set forumOnlyAutorizedImage
     *
     * @param integer $forumOnlyAutorizedImage
     * @return ForumCreatePages
     */
    public function setForumOnlyAutorizedImage($forumOnlyAutorizedImage)
    {
        $this->forumOnlyAutorizedImage = $forumOnlyAutorizedImage;
    
        return $this;
    }

    /**
     * Get forumOnlyAutorizedImage
     *
     * @return integer 
     */
    public function getForumOnlyAutorizedImage()
    {
        return $this->forumOnlyAutorizedImage;
    }

    /**
     * Set forumAllowTags
     *
     * @param string $forumAllowTags
     * @return ForumCreatePages
     */
    public function setForumAllowTags($forumAllowTags)
    {
        $this->forumAllowTags = $forumAllowTags;
    
        return $this;
    }

    /**
     * Get forumAllowTags
     *
     * @return string 
     */
    public function getForumAllowTags()
    {
        return $this->forumAllowTags;
    }

    /**
     * Set forumAllowStyleProp
     *
     * @param integer $forumAllowStyleProp
     * @return ForumCreatePages
     */
    public function setForumAllowStyleProp($forumAllowStyleProp)
    {
        $this->forumAllowStyleProp = $forumAllowStyleProp;
    
        return $this;
    }

    /**
     * Get forumAllowStyleProp
     *
     * @return integer 
     */
    public function getForumAllowStyleProp()
    {
        return $this->forumAllowStyleProp;
    }

    /**
     * Set forumReplaceUrls
     *
     * @param integer $forumReplaceUrls
     * @return ForumCreatePages
     */
    public function setForumReplaceUrls($forumReplaceUrls)
    {
        $this->forumReplaceUrls = $forumReplaceUrls;
    
        return $this;
    }

    /**
     * Get forumReplaceUrls
     *
     * @return integer 
     */
    public function getForumReplaceUrls()
    {
        return $this->forumReplaceUrls;
    }

    /**
     * Set forumMessageInPage
     *
     * @param integer $forumMessageInPage
     * @return ForumCreatePages
     */
    public function setForumMessageInPage($forumMessageInPage)
    {
        $this->forumMessageInPage = $forumMessageInPage;
    
        return $this;
    }

    /**
     * Get forumMessageInPage
     *
     * @return integer 
     */
    public function getForumMessageInPage()
    {
        return $this->forumMessageInPage;
    }

    /**
     * Set forumModerators
     *
     * @param string $forumModerators
     * @return ForumCreatePages
     */
    public function setForumModerators($forumModerators)
    {
        $this->forumModerators = $forumModerators;
    
        return $this;
    }

    /**
     * Get forumModerators
     *
     * @return string 
     */
    public function getForumModerators()
    {
        return $this->forumModerators;
    }

    /**
     * Set seopageEnabled
     *
     * @param integer $seopageEnabled
     * @return ForumCreatePages
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
     * @return ForumCreatePages
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
     * @return ForumCreatePages
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
     * @return ForumCreatePages
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
     * @return ForumCreatePages
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
     * @return ForumCreatePages
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
     * Set forumTemplate
     *
     * @param string $forumTemplate
     * @return ForumCreatePages
     */
    public function setForumTemplate($forumTemplate)
    {
        $this->forumTemplate = $forumTemplate;
    
        return $this;
    }

    /**
     * Get forumTemplate
     *
     * @return string 
     */
    public function getForumTemplate()
    {
        return $this->forumTemplate;
    }

    /**
     * Set onlyAutorized
     *
     * @param integer $onlyAutorized
     * @return ForumCreatePages
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
     * Set categoryMode
     *
     * @param integer $categoryMode
     * @return ForumCreatePages
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
     * @return ForumCreatePages
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
}