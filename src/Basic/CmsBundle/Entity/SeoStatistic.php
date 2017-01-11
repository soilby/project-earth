<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SeoStatistic
 *
 * @ORM\Table(name="seostatistic")
 * @ORM\Entity
 */
class SeoStatistic
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
     * @ORM\Column(name="page_id", type="integer")
     */
    private $pageId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="visit_date", type="datetime")
     */
    private $visitDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="all_visits", type="integer")
     */
    private $allVisits;

    /**
     * @var integer
     *
     * @ORM\Column(name="month1", type="integer")
     */
    private $month1;

    /**
     * @var integer
     *
     * @ORM\Column(name="month2", type="integer")
     */
    private $month2;

    /**
     * @var integer
     *
     * @ORM\Column(name="month3", type="integer")
     */
    private $month3;

    /**
     * @var integer
     *
     * @ORM\Column(name="month4", type="integer")
     */
    private $month4;

    /**
     * @var integer
     *
     * @ORM\Column(name="month5", type="integer")
     */
    private $month5;

    /**
     * @var integer
     *
     * @ORM\Column(name="month6", type="integer")
     */
    private $month6;

    /**
     * @var integer
     *
     * @ORM\Column(name="month7", type="integer")
     */
    private $month7;

    /**
     * @var integer
     *
     * @ORM\Column(name="month8", type="integer")
     */
    private $month8;

    /**
     * @var integer
     *
     * @ORM\Column(name="month9", type="integer")
     */
    private $month9;

    /**
     * @var integer
     *
     * @ORM\Column(name="month10", type="integer")
     */
    private $month10;

    /**
     * @var integer
     *
     * @ORM\Column(name="month11", type="integer")
     */
    private $month11;

    /**
     * @var integer
     *
     * @ORM\Column(name="month12", type="integer")
     */
    private $month12;

    /**
     * @var integer
     *
     * @ORM\Column(name="week1", type="integer")
     */
    private $week1;

    /**
     * @var integer
     *
     * @ORM\Column(name="week2", type="integer")
     */
    private $week2;

    /**
     * @var integer
     *
     * @ORM\Column(name="week3", type="integer")
     */
    private $week3;

    /**
     * @var integer
     *
     * @ORM\Column(name="week4", type="integer")
     */
    private $week4;

    /**
     * @var integer
     *
     * @ORM\Column(name="week5", type="integer")
     */
    private $week5;

    /**
     * @var integer
     *
     * @ORM\Column(name="week6", type="integer")
     */
    private $week6;

    /**
     * @var integer
     *
     * @ORM\Column(name="week7", type="integer")
     */
    private $week7;

    /**
     * @var integer
     *
     * @ORM\Column(name="week8", type="integer")
     */
    private $week8;

    /**
     * @var integer
     *
     * @ORM\Column(name="week9", type="integer")
     */
    private $week9;

    /**
     * @var integer
     *
     * @ORM\Column(name="week10", type="integer")
     */
    private $week10;

    /**
     * @var integer
     *
     * @ORM\Column(name="week11", type="integer")
     */
    private $week11;

    /**
     * @var integer
     *
     * @ORM\Column(name="week12", type="integer")
     */
    private $week12;

    /**
     * @var integer
     *
     * @ORM\Column(name="day1", type="integer")
     */
    private $day1;

    /**
     * @var integer
     *
     * @ORM\Column(name="day2", type="integer")
     */
    private $day2;

    /**
     * @var integer
     *
     * @ORM\Column(name="day3", type="integer")
     */
    private $day3;

    /**
     * @var integer
     *
     * @ORM\Column(name="day4", type="integer")
     */
    private $day4;

    /**
     * @var integer
     *
     * @ORM\Column(name="day5", type="integer")
     */
    private $day5;

    /**
     * @var integer
     *
     * @ORM\Column(name="day6", type="integer")
     */
    private $day6;

    /**
     * @var integer
     *
     * @ORM\Column(name="day7", type="integer")
     */
    private $day7;

    /**
     * @var integer
     *
     * @ORM\Column(name="day8", type="integer")
     */
    private $day8;

    /**
     * @var integer
     *
     * @ORM\Column(name="day9", type="integer")
     */
    private $day9;

    /**
     * @var integer
     *
     * @ORM\Column(name="day10", type="integer")
     */
    private $day10;

    /**
     * @var integer
     *
     * @ORM\Column(name="day11", type="integer")
     */
    private $day11;

    /**
     * @var integer
     *
     * @ORM\Column(name="day12", type="integer")
     */
    private $day12;


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
     * Set pageId
     *
     * @param integer $pageId
     * @return SeoStatistic
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;
    
        return $this;
    }

    /**
     * Get pageId
     *
     * @return integer 
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * Set visitDate
     *
     * @param \DateTime $visitDate
     * @return SeoStatistic
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
     * Set allVisits
     *
     * @param integer $allVisits
     * @return SeoStatistic
     */
    public function setAllVisits($allVisits)
    {
        $this->allVisits = $allVisits;
    
        return $this;
    }

    /**
     * Get allVisits
     *
     * @return integer 
     */
    public function getAllVisits()
    {
        return $this->allVisits;
    }

    /**
     * Set month1
     *
     * @param integer $month1
     * @return SeoStatistic
     */
    public function setMonth1($month1)
    {
        $this->month1 = $month1;
    
        return $this;
    }

    /**
     * Get month1
     *
     * @return integer 
     */
    public function getMonth1()
    {
        return $this->month1;
    }

    /**
     * Set month2
     *
     * @param integer $month2
     * @return SeoStatistic
     */
    public function setMonth2($month2)
    {
        $this->month2 = $month2;
    
        return $this;
    }

    /**
     * Get month2
     *
     * @return integer 
     */
    public function getMonth2()
    {
        return $this->month2;
    }

    /**
     * Set month3
     *
     * @param integer $month3
     * @return SeoStatistic
     */
    public function setMonth3($month3)
    {
        $this->month3 = $month3;
    
        return $this;
    }

    /**
     * Get month3
     *
     * @return integer 
     */
    public function getMonth3()
    {
        return $this->month3;
    }

    /**
     * Set month4
     *
     * @param integer $month4
     * @return SeoStatistic
     */
    public function setMonth4($month4)
    {
        $this->month4 = $month4;
    
        return $this;
    }

    /**
     * Get month4
     *
     * @return integer 
     */
    public function getMonth4()
    {
        return $this->month4;
    }

    /**
     * Set month5
     *
     * @param integer $month5
     * @return SeoStatistic
     */
    public function setMonth5($month5)
    {
        $this->month5 = $month5;
    
        return $this;
    }

    /**
     * Get month5
     *
     * @return integer 
     */
    public function getMonth5()
    {
        return $this->month5;
    }

    /**
     * Set month6
     *
     * @param integer $month6
     * @return SeoStatistic
     */
    public function setMonth6($month6)
    {
        $this->month6 = $month6;
    
        return $this;
    }

    /**
     * Get month6
     *
     * @return integer 
     */
    public function getMonth6()
    {
        return $this->month6;
    }

    /**
     * Set month7
     *
     * @param integer $month7
     * @return SeoStatistic
     */
    public function setMonth7($month7)
    {
        $this->month7 = $month7;
    
        return $this;
    }

    /**
     * Get month7
     *
     * @return integer 
     */
    public function getMonth7()
    {
        return $this->month7;
    }

    /**
     * Set month8
     *
     * @param integer $month8
     * @return SeoStatistic
     */
    public function setMonth8($month8)
    {
        $this->month8 = $month8;
    
        return $this;
    }

    /**
     * Get month8
     *
     * @return integer 
     */
    public function getMonth8()
    {
        return $this->month8;
    }

    /**
     * Set month9
     *
     * @param integer $month9
     * @return SeoStatistic
     */
    public function setMonth9($month9)
    {
        $this->month9 = $month9;
    
        return $this;
    }

    /**
     * Get month9
     *
     * @return integer 
     */
    public function getMonth9()
    {
        return $this->month9;
    }

    /**
     * Set month10
     *
     * @param integer $month10
     * @return SeoStatistic
     */
    public function setMonth10($month10)
    {
        $this->month10 = $month10;
    
        return $this;
    }

    /**
     * Get month10
     *
     * @return integer 
     */
    public function getMonth10()
    {
        return $this->month10;
    }

    /**
     * Set month11
     *
     * @param integer $month11
     * @return SeoStatistic
     */
    public function setMonth11($month11)
    {
        $this->month11 = $month11;
    
        return $this;
    }

    /**
     * Get month11
     *
     * @return integer 
     */
    public function getMonth11()
    {
        return $this->month11;
    }

    /**
     * Set month12
     *
     * @param integer $month12
     * @return SeoStatistic
     */
    public function setMonth12($month12)
    {
        $this->month12 = $month12;
    
        return $this;
    }

    /**
     * Get month12
     *
     * @return integer 
     */
    public function getMonth12()
    {
        return $this->month12;
    }

    /**
     * Set week1
     *
     * @param integer $week1
     * @return SeoStatistic
     */
    public function setWeek1($week1)
    {
        $this->week1 = $week1;
    
        return $this;
    }

    /**
     * Get week1
     *
     * @return integer 
     */
    public function getWeek1()
    {
        return $this->week1;
    }

    /**
     * Set week2
     *
     * @param integer $week2
     * @return SeoStatistic
     */
    public function setWeek2($week2)
    {
        $this->week2 = $week2;
    
        return $this;
    }

    /**
     * Get week2
     *
     * @return integer 
     */
    public function getWeek2()
    {
        return $this->week2;
    }

    /**
     * Set week3
     *
     * @param integer $week3
     * @return SeoStatistic
     */
    public function setWeek3($week3)
    {
        $this->week3 = $week3;
    
        return $this;
    }

    /**
     * Get week3
     *
     * @return integer 
     */
    public function getWeek3()
    {
        return $this->week3;
    }

    /**
     * Set week4
     *
     * @param integer $week4
     * @return SeoStatistic
     */
    public function setWeek4($week4)
    {
        $this->week4 = $week4;
    
        return $this;
    }

    /**
     * Get week4
     *
     * @return integer 
     */
    public function getWeek4()
    {
        return $this->week4;
    }

    /**
     * Set week5
     *
     * @param integer $week5
     * @return SeoStatistic
     */
    public function setWeek5($week5)
    {
        $this->week5 = $week5;
    
        return $this;
    }

    /**
     * Get week5
     *
     * @return integer 
     */
    public function getWeek5()
    {
        return $this->week5;
    }

    /**
     * Set week6
     *
     * @param integer $week6
     * @return SeoStatistic
     */
    public function setWeek6($week6)
    {
        $this->week6 = $week6;
    
        return $this;
    }

    /**
     * Get week6
     *
     * @return integer 
     */
    public function getWeek6()
    {
        return $this->week6;
    }

    /**
     * Set week7
     *
     * @param integer $week7
     * @return SeoStatistic
     */
    public function setWeek7($week7)
    {
        $this->week7 = $week7;
    
        return $this;
    }

    /**
     * Get week7
     *
     * @return integer 
     */
    public function getWeek7()
    {
        return $this->week7;
    }

    /**
     * Set week8
     *
     * @param integer $week8
     * @return SeoStatistic
     */
    public function setWeek8($week8)
    {
        $this->week8 = $week8;
    
        return $this;
    }

    /**
     * Get week8
     *
     * @return integer 
     */
    public function getWeek8()
    {
        return $this->week8;
    }

    /**
     * Set week9
     *
     * @param integer $week9
     * @return SeoStatistic
     */
    public function setWeek9($week9)
    {
        $this->week9 = $week9;
    
        return $this;
    }

    /**
     * Get week9
     *
     * @return integer 
     */
    public function getWeek9()
    {
        return $this->week9;
    }

    /**
     * Set week10
     *
     * @param integer $week10
     * @return SeoStatistic
     */
    public function setWeek10($week10)
    {
        $this->week10 = $week10;
    
        return $this;
    }

    /**
     * Get week10
     *
     * @return integer 
     */
    public function getWeek10()
    {
        return $this->week10;
    }

    /**
     * Set week11
     *
     * @param integer $week11
     * @return SeoStatistic
     */
    public function setWeek11($week11)
    {
        $this->week11 = $week11;
    
        return $this;
    }

    /**
     * Get week11
     *
     * @return integer 
     */
    public function getWeek11()
    {
        return $this->week11;
    }

    /**
     * Set week12
     *
     * @param integer $week12
     * @return SeoStatistic
     */
    public function setWeek12($week12)
    {
        $this->week12 = $week12;
    
        return $this;
    }

    /**
     * Get week12
     *
     * @return integer 
     */
    public function getWeek12()
    {
        return $this->week12;
    }

    /**
     * Set day1
     *
     * @param integer $day1
     * @return SeoStatistic
     */
    public function setDay1($day1)
    {
        $this->day1 = $day1;
    
        return $this;
    }

    /**
     * Get day1
     *
     * @return integer 
     */
    public function getDay1()
    {
        return $this->day1;
    }

    /**
     * Set day2
     *
     * @param integer $day2
     * @return SeoStatistic
     */
    public function setDay2($day2)
    {
        $this->day2 = $day2;
    
        return $this;
    }

    /**
     * Get day2
     *
     * @return integer 
     */
    public function getDay2()
    {
        return $this->day2;
    }

    /**
     * Set day3
     *
     * @param integer $day3
     * @return SeoStatistic
     */
    public function setDay3($day3)
    {
        $this->day3 = $day3;
    
        return $this;
    }

    /**
     * Get day3
     *
     * @return integer 
     */
    public function getDay3()
    {
        return $this->day3;
    }

    /**
     * Set day4
     *
     * @param integer $day4
     * @return SeoStatistic
     */
    public function setDay4($day4)
    {
        $this->day4 = $day4;
    
        return $this;
    }

    /**
     * Get day4
     *
     * @return integer 
     */
    public function getDay4()
    {
        return $this->day4;
    }

    /**
     * Set day5
     *
     * @param integer $day5
     * @return SeoStatistic
     */
    public function setDay5($day5)
    {
        $this->day5 = $day5;
    
        return $this;
    }

    /**
     * Get day5
     *
     * @return integer 
     */
    public function getDay5()
    {
        return $this->day5;
    }

    /**
     * Set day6
     *
     * @param integer $day6
     * @return SeoStatistic
     */
    public function setDay6($day6)
    {
        $this->day6 = $day6;
    
        return $this;
    }

    /**
     * Get day6
     *
     * @return integer 
     */
    public function getDay6()
    {
        return $this->day6;
    }

    /**
     * Set day7
     *
     * @param integer $day7
     * @return SeoStatistic
     */
    public function setDay7($day7)
    {
        $this->day7 = $day7;
    
        return $this;
    }

    /**
     * Get day7
     *
     * @return integer 
     */
    public function getDay7()
    {
        return $this->day7;
    }

    /**
     * Set day8
     *
     * @param integer $day8
     * @return SeoStatistic
     */
    public function setDay8($day8)
    {
        $this->day8 = $day8;
    
        return $this;
    }

    /**
     * Get day8
     *
     * @return integer 
     */
    public function getDay8()
    {
        return $this->day8;
    }

    /**
     * Set day9
     *
     * @param integer $day9
     * @return SeoStatistic
     */
    public function setDay9($day9)
    {
        $this->day9 = $day9;
    
        return $this;
    }

    /**
     * Get day9
     *
     * @return integer 
     */
    public function getDay9()
    {
        return $this->day9;
    }

    /**
     * Set day10
     *
     * @param integer $day10
     * @return SeoStatistic
     */
    public function setDay10($day10)
    {
        $this->day10 = $day10;
    
        return $this;
    }

    /**
     * Get day10
     *
     * @return integer 
     */
    public function getDay10()
    {
        return $this->day10;
    }

    /**
     * Set day11
     *
     * @param integer $day11
     * @return SeoStatistic
     */
    public function setDay11($day11)
    {
        $this->day11 = $day11;
    
        return $this;
    }

    /**
     * Get day11
     *
     * @return integer 
     */
    public function getDay11()
    {
        return $this->day11;
    }

    /**
     * Set day12
     *
     * @param integer $day12
     * @return SeoStatistic
     */
    public function setDay12($day12)
    {
        $this->day12 = $day12;
    
        return $this;
    }

    /**
     * Get day12
     *
     * @return integer 
     */
    public function getDay12()
    {
        return $this->day12;
    }
}