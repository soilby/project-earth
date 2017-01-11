<?php

namespace Forum\PrivateBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ForumPrivatePages
 *
 * @ORM\Table(name="forumprivatepages")
 * @ORM\Entity
 */
class ForumPrivatePages
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
     * @var string
     *
     * @ORM\Column(name="message_allow_tags", type="text")
     */
    private $messageAllowTags;

    /**
     * @var integer
     *
     * @ORM\Column(name="message_allow_style_prop", type="integer")
     */
    private $messageAllowStyleProp;

    /**
     * @var integer
     *
     * @ORM\Column(name="message_replace_urls", type="integer")
     */
    private $messageReplaceUrls;

    /**
     * @var integer
     *
     * @ORM\Column(name="messages_in_page", type="integer")
     */
    private $messagesInPage;

    /**
     * @var integer
     *
     * @ORM\Column(name="message_mode", type="integer")
     */
    private $messageMode;

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
     * @return ForumPrivatePages
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
     * @return ForumPrivatePages
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
     * @return ForumPrivatePages
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
     * @return ForumPrivatePages
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
     * Set messageAllowTags
     *
     * @param string $messageAllowTags
     * @return ForumPrivatePages
     */
    public function setMessageAllowTags($messageAllowTags)
    {
        $this->messageAllowTags = $messageAllowTags;
    
        return $this;
    }

    /**
     * Get messageAllowTags
     *
     * @return string 
     */
    public function getMessageAllowTags()
    {
        return $this->messageAllowTags;
    }

    /**
     * Set messageAllowStyleProp
     *
     * @param integer $messageAllowStyleProp
     * @return ForumPrivatePages
     */
    public function setMessageAllowStyleProp($messageAllowStyleProp)
    {
        $this->messageAllowStyleProp = $messageAllowStyleProp;
    
        return $this;
    }

    /**
     * Get messageAllowStyleProp
     *
     * @return integer 
     */
    public function getMessageAllowStyleProp()
    {
        return $this->messageAllowStyleProp;
    }

    /**
     * Set messageReplaceUrls
     *
     * @param integer $messageReplaceUrls
     * @return ForumPrivatePages
     */
    public function setMessageReplaceUrls($messageReplaceUrls)
    {
        $this->messageReplaceUrls = $messageReplaceUrls;
    
        return $this;
    }

    /**
     * Get messageReplaceUrls
     *
     * @return integer 
     */
    public function getMessageReplaceUrls()
    {
        return $this->messageReplaceUrls;
    }

    /**
     * Set messagesInPage
     *
     * @param integer $messagesInPage
     * @return ForumPrivatePages
     */
    public function setMessagesInPage($messagesInPage)
    {
        $this->messagesInPage = $messagesInPage;
    
        return $this;
    }

    /**
     * Get messagesInPage
     *
     * @return integer 
     */
    public function getMessagesInPage()
    {
        return $this->messagesInPage;
    }

    /**
     * Set messageMode
     *
     * @param integer $messageMode
     * @return ForumPrivatePages
     */
    public function setMessageMode($messageMode)
    {
        $this->messageMode = $messageMode;
    
        return $this;
    }

    /**
     * Get messageMode
     *
     * @return integer 
     */
    public function getMessageMode()
    {
        return $this->messageMode;
    }
}