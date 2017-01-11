<?php

namespace Forum\ForumBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Forums
 *
 * @ORM\Table(name="forums")
 * @ORM\Entity
 */
class Forums
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
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="avatar", type="string", length=255)
     */
    private $avatar;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=99, nullable=true)
     */
    private $template;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime")
     */
    private $createDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modify_date", type="datetime")
     */
    private $modifyDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="creater_id", type="integer")
     */
    private $createrId;

    /**
     * @var integer
     *
     * @ORM\Column(name="enabled", type="integer")
     */
    private $enabled;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_message_date", type="datetime")
     */
    private $lastMessageDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_closed", type="integer")
     */
    private $isClosed;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_important", type="integer")
     */
    private $isImportant;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_visible", type="integer")
     */
    private $isVisible;

    /**
     * @var integer
     *
     * @ORM\Column(name="moderator_id", type="integer", nullable=true)
     */
    private $moderatorId;

    /**
     * @var string
     *
     * @ORM\Column(name="moderator_message", type="text", nullable=true)
     */
    private $moderatorMessage;

    /**
     * @var integer
     *
     * @ORM\Column(name="captcha_enabled", type="integer")
     */
    private $captchaEnabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="only_autorized_view", type="integer")
     */
    private $onlyAutorizedView;

    /**
     * @var integer
     *
     * @ORM\Column(name="only_autorized_post", type="integer")
     */
    private $onlyAutorizedPost;

    /**
     * @var integer
     *
     * @ORM\Column(name="only_autorized_download", type="integer")
     */
    private $onlyAutorizedDownload;

    /**
     * @var integer
     *
     * @ORM\Column(name="only_autorized_image", type="integer")
     */
    private $onlyAutorizedImage;

    /**
     * @var string
     *
     * @ORM\Column(name="allow_tags", type="text")
     */
    private $allowTags;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_style_prop", type="integer")
     */
    private $allowStyleProp;

    /**
     * @var integer
     *
     * @ORM\Column(name="replace_urls", type="integer")
     */
    private $replaceUrls;

    /**
     * @var integer
     *
     * @ORM\Column(name="message_in_page", type="integer")
     */
    private $messageInPage;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_message_id", type="integer", nullable=true)
     */
    private $lastMessageId;
    
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
     * @return Forums
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
     * Set description
     *
     * @param string $description
     * @return Forums
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
     * Set avatar
     *
     * @param string $avatar
     * @return Forums
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    
        return $this;
    }

    /**
     * Get avatar
     *
     * @return string 
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return Forums
     */
    public function setContent($content)
    {
        $this->content = $content;
    
        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return Forums
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
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return Forums
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;
    
        return $this;
    }

    /**
     * Get createDate
     *
     * @return \DateTime 
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * Set modifyDate
     *
     * @param \DateTime $modifyDate
     * @return Forums
     */
    public function setModifyDate($modifyDate)
    {
        $this->modifyDate = $modifyDate;
    
        return $this;
    }

    /**
     * Get modifyDate
     *
     * @return \DateTime 
     */
    public function getModifyDate()
    {
        return $this->modifyDate;
    }

    /**
     * Set createrId
     *
     * @param integer $createrId
     * @return Forums
     */
    public function setCreaterId($createrId)
    {
        $this->createrId = $createrId;
    
        return $this;
    }

    /**
     * Get createrId
     *
     * @return integer 
     */
    public function getCreaterId()
    {
        return $this->createrId;
    }

    /**
     * Set enabled
     *
     * @param integer $enabled
     * @return Forums
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
     * Set lastMessageDate
     *
     * @param \DateTime $lastMessageDate
     * @return Forums
     */
    public function setLastMessageDate($lastMessageDate)
    {
        $this->lastMessageDate = $lastMessageDate;
    
        return $this;
    }

    /**
     * Get lastMessageDate
     *
     * @return \DateTime 
     */
    public function getLastMessageDate()
    {
        return $this->lastMessageDate;
    }

    /**
     * Set isClosed
     *
     * @param integer $isClosed
     * @return Forums
     */
    public function setIsClosed($isClosed)
    {
        $this->isClosed = $isClosed;
    
        return $this;
    }

    /**
     * Get isClosed
     *
     * @return integer 
     */
    public function getIsClosed()
    {
        return $this->isClosed;
    }

    /**
     * Set isImportant
     *
     * @param integer $isImportant
     * @return Forums
     */
    public function setIsImportant($isImportant)
    {
        $this->isImportant = $isImportant;
    
        return $this;
    }

    /**
     * Get isImportant
     *
     * @return integer 
     */
    public function getIsImportant()
    {
        return $this->isImportant;
    }

    /**
     * Set isVisible
     *
     * @param integer $isVisible
     * @return Forums
     */
    public function setIsVisible($isVisible)
    {
        $this->isVisible = $isVisible;
    
        return $this;
    }

    /**
     * Get isVisible
     *
     * @return integer 
     */
    public function getIsVisible()
    {
        return $this->isVisible;
    }

    /**
     * Set moderatorId
     *
     * @param integer $moderatorId
     * @return Forums
     */
    public function setModeratorId($moderatorId)
    {
        $this->moderatorId = $moderatorId;
    
        return $this;
    }

    /**
     * Get moderatorId
     *
     * @return integer 
     */
    public function getModeratorId()
    {
        return $this->moderatorId;
    }

    /**
     * Set moderatorMessage
     *
     * @param string $moderatorMessage
     * @return Forums
     */
    public function setModeratorMessage($moderatorMessage)
    {
        $this->moderatorMessage = $moderatorMessage;
    
        return $this;
    }

    /**
     * Get moderatorMessage
     *
     * @return string 
     */
    public function getModeratorMessage()
    {
        return $this->moderatorMessage;
    }

    /**
     * Set captchaEnabled
     *
     * @param integer $captchaEnabled
     * @return Forums
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
     * Set onlyAutorizedView
     *
     * @param integer $onlyAutorizedView
     * @return Forums
     */
    public function setOnlyAutorizedView($onlyAutorizedView)
    {
        $this->onlyAutorizedView = $onlyAutorizedView;
    
        return $this;
    }

    /**
     * Get onlyAutorizedView
     *
     * @return integer 
     */
    public function getOnlyAutorizedView()
    {
        return $this->onlyAutorizedView;
    }

    /**
     * Set onlyAutorizedPost
     *
     * @param integer $onlyAutorizedPost
     * @return Forums
     */
    public function setOnlyAutorizedPost($onlyAutorizedPost)
    {
        $this->onlyAutorizedPost = $onlyAutorizedPost;
    
        return $this;
    }

    /**
     * Get onlyAutorizedPost
     *
     * @return integer 
     */
    public function getOnlyAutorizedPost()
    {
        return $this->onlyAutorizedPost;
    }

    /**
     * Set onlyAutorizedDownload
     *
     * @param integer $onlyAutorizedDownload
     * @return Forums
     */
    public function setOnlyAutorizedDownload($onlyAutorizedDownload)
    {
        $this->onlyAutorizedDownload = $onlyAutorizedDownload;
    
        return $this;
    }

    /**
     * Get onlyAutorizedDownload
     *
     * @return integer 
     */
    public function getOnlyAutorizedDownload()
    {
        return $this->onlyAutorizedDownload;
    }

    /**
     * Set onlyAutorizedImage
     *
     * @param integer $onlyAutorizedImage
     * @return Forums
     */
    public function setOnlyAutorizedImage($onlyAutorizedImage)
    {
        $this->onlyAutorizedImage = $onlyAutorizedImage;
    
        return $this;
    }

    /**
     * Get onlyAutorizedImage
     *
     * @return integer 
     */
    public function getOnlyAutorizedImage()
    {
        return $this->onlyAutorizedImage;
    }

    /**
     * Set allowTags
     *
     * @param string $allowTags
     * @return Forums
     */
    public function setAllowTags($allowTags)
    {
        $this->allowTags = $allowTags;
    
        return $this;
    }

    /**
     * Get allowTags
     *
     * @return string 
     */
    public function getAllowTags()
    {
        return $this->allowTags;
    }

    /**
     * Set allowStyleProp
     *
     * @param integer $allowStyleProp
     * @return Forums
     */
    public function setAllowStyleProp($allowStyleProp)
    {
        $this->allowStyleProp = $allowStyleProp;
    
        return $this;
    }

    /**
     * Get allowStyleProp
     *
     * @return integer 
     */
    public function getAllowStyleProp()
    {
        return $this->allowStyleProp;
    }

    /**
     * Set replaceUrls
     *
     * @param integer $replaceUrls
     * @return Forums
     */
    public function setReplaceUrls($replaceUrls)
    {
        $this->replaceUrls = $replaceUrls;
    
        return $this;
    }

    /**
     * Get replaceUrls
     *
     * @return integer 
     */
    public function getReplaceUrls()
    {
        return $this->replaceUrls;
    }

    /**
     * Set messageInPage
     *
     * @param integer $messageInPage
     * @return Forums
     */
    public function setMessageInPage($messageInPage)
    {
        $this->messageInPage = $messageInPage;
    
        return $this;
    }

    /**
     * Get messageInPage
     *
     * @return integer 
     */
    public function getMessageInPage()
    {
        return $this->messageInPage;
    }

    /**
     * Set lastMessageId
     *
     * @param integer $lastMessageId
     * @return Forums
     */
    public function setLastMessageId($lastMessageId)
    {
        $this->lastMessageId = $lastMessageId;
    
        return $this;
    }

    /**
     * Get lastMessageId
     *
     * @return integer 
     */
    public function getLastMessageId()
    {
        return $this->lastMessageId;
    }
}