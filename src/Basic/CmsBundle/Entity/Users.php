<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Users
 *
 * @ORM\Table(name="users")
 * @ORM\Entity
 */
class Users implements UserInterface {

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
     * @ORM\Column(name="login", type="string", length=99, nullable=true)
     */
    private $login;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=99, nullable=true)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=99, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="salt", type="string", length=255)
     */
    private $salt;

    /**
     * @var string
     *
     * @ORM\Column(name="full_name", type="string", length=99, nullable=true)
     */
    
    private $fullName;

    /**
     * @var string
     *
     * @ORM\Column(name="user_type", type="string", length=99)
     */
    private $userType;

    /**
     * @var integer
     *
     * @ORM\Column(name="blocked", type="integer")
     */
    private $blocked;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="reg_date", type="datetime")
     */
    private $regDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="visit_date", type="datetime", nullable=true)
     */
    private $visitDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="prev_visit_date", type="datetime", nullable=true)
     */
    private $prevVisitDate;
    
    /**
     * @ORM\ManyToOne(targetEntity="Roles")
     * @ORM\JoinColumn(name="role_id", referencedColumnName="id", nullable=true)
     */
    protected $role;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=99, nullable=true)
     */
    private $template;

    /**
     * @var string
     *
     * @ORM\Column(name="avatar", type="string", length=255)
     */
    private $avatar;
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set login
     *
     * @param string $login
     * @return Users
     */
    public function setLogin($login) {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login
     *
     * @return string 
     */
    public function getLogin() {
        return $this->login;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return Users
     */
    public function setPassword($password) {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Users
     */
    public function setEmail($email) {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Set salt
     *
     * @param string $salt
     * @return Users
     */
    public function setSalt($salt) {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt
     *
     * @return string 
     */
    public function getSalt() {
        return $this->salt;
    }

    /**
     * Set fullname
     *
     * @param string $fullname
     * @return Users
     */
    public function setFullName($fullname) {
        $this->fullName = $fullname;

        return $this;
    }

    /**
     * Get fullname
     *
     * @return string 
     */
    public function getFullName() {
        return $this->fullName;
    }
    
    
    /**
     * Set userType
     *
     * @param string $userType
     * @return Users
     */
    public function setUserType($userType) {
        $this->userType = $userType;

        return $this;
    }

    /**
     * Get userType
     *
     * @return string 
     */
    public function getUserType() {
        return $this->userType;
    }

    /**
     * Set regDate
     *
     * @param \DateTime $regDate
     * @return Users
     */
    public function setRegDate($regDate) {
        $this->regDate = $regDate;

        return $this;
    }

    /**
     * Get regDate
     *
     * @return \DateTime 
     */
    public function getRegDate() {
        return $this->regDate;
    }

    /**
     * Get roles
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRoles() {
        if ($this->blocked == 2)
        {
            return array('ROLE_'.$this->userType);
        } elseif ($this->blocked == 1)
        {
            return array('ROLE_BLOCKED');
        }
        return array('ROLE_NOQUERED');
    }

    public function eraseCredentials() {
        
    }

    public function getUsername() {
        return $this->login;
    }

    /**
     * Set role
     *
     * @param \Basic\CmsBundle\Entity\Roles $role
     * @return Users
     */
    public function setRole(\Basic\CmsBundle\Entity\Roles $role = null)
    {
        $this->role = $role;
    
        return $this;
    }

    /**
     * Get role
     *
     * @return \Basic\CmsBundle\Entity\Roles 
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set blocked
     *
     * @param integer $blocked
     * @return Users
     */
    public function setBlocked($blocked)
    {
        $this->blocked = $blocked;
    
        return $this;
    }

    /**
     * Get blocked
     *
     * @return integer 
     */
    public function getBlocked()
    {
        return $this->blocked;
    }
    
    public function checkAccess($str) 
    {
        if ($this->blocked != 2) return 0;
        if ($this->userType == 'SUPERADMIN') return 1;
        if ($this->getRole() == null) return 0;
        if (strpos($this->getRole()->getAccess(),' '.$str.' ') !== false) return 1;
        return 0;
    }
    

    /**
     * Set template
     *
     * @param string $template
     * @return Users
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
     * Set visitDate
     *
     * @param \DateTime $visitDate
     * @return Users
     */
    public function setVisitDate($visitDate)
    {
        $this->visitDate = $visitDate;
    
        return $this;
    }

    /**
     * Get visitDate
     *
     * @return \DateTime 
     */
    public function getVisitDate()
    {
        return $this->visitDate;
    }

    /**
     * Set avatar
     *
     * @param string $avatar
     * @return Users
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
     * Set prevVisitDate
     *
     * @param \DateTime $prevVisitDate
     * @return Users
     */
    public function setPrevVisitDate($prevVisitDate)
    {
        $this->prevVisitDate = $prevVisitDate;
    
        return $this;
    }

    /**
     * Get prevVisitDate
     *
     * @return \DateTime 
     */
    public function getPrevVisitDate()
    {
        return $this->prevVisitDate;
    }
}