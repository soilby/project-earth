<?php

namespace Extended\AlertBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserAlerts
 *
 * @ORM\Table(name="useralerts")
 * @ORM\Entity
 */
class UserAlerts
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
     * @ORM\Column(name="userId", type="integer")
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="subscribe", type="text")
     */
    private $subscribe;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=5)
     */
    private $locale;

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
     * Set userId
     *
     * @param integer $userId
     * @return UserAlerts
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    
        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set subscribe
     *
     * @param string $subscribe
     * @return UserAlerts
     */
    public function setSubscribe($subscribe)
    {
        $this->subscribe = $subscribe;
    
        return $this;
    }

    /**
     * Get subscribe
     *
     * @return string 
     */
    public function getSubscribe()
    {
        return $this->subscribe;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return UserAlerts
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
}