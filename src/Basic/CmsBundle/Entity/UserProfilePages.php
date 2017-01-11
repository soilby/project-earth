<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserProfilePages
 *
 * @ORM\Table(name="userprofilepage")
 * @ORM\Entity
 */
class UserProfilePages
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
     * @ORM\Column(name="change_email", type="integer")
     */
    private $changeEmail;

    /**
     * @var integer
     *
     * @ORM\Column(name="change_password", type="integer")
     */
    private $changePassword;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=99, nullable=true)
     */
    private $template;


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
     * @return UserProfilePages
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
     * @return UserProfilePages
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
     * Set changeEmail
     *
     * @param integer $changeEmail
     * @return UserProfilePages
     */
    public function setChangeEmail($changeEmail)
    {
        $this->changeEmail = $changeEmail;
    
        return $this;
    }

    /**
     * Get changeEmail
     *
     * @return integer 
     */
    public function getChangeEmail()
    {
        return $this->changeEmail;
    }

    /**
     * Set changePassword
     *
     * @param integer $changePassword
     * @return UserProfilePages
     */
    public function setChangePassword($changePassword)
    {
        $this->changePassword = $changePassword;
    
        return $this;
    }

    /**
     * Get changePassword
     *
     * @return integer 
     */
    public function getChangePassword()
    {
        return $this->changePassword;
    }

    /**
     * Set template
     *
     * @param string $template
     * @return UserProfilePages
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
}