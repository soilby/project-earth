<?php

namespace Basic\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TaxonomiesLinks
 *
 * @ORM\Table(name="taxonomieslinks")
 * @ORM\Entity
 */
class TaxonomiesLinks
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
     * @ORM\Column(name="taxonomy_id", type="integer")
     */
    private $taxonomyId;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer")
     */
    private $itemId;


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
     * Set taxonomyId
     *
     * @param integer $taxonomyId
     * @return TaxonomiesLinks
     */
    public function setTaxonomyId($taxonomyId)
    {
        $this->taxonomyId = $taxonomyId;
    
        return $this;
    }

    /**
     * Get taxonomyId
     *
     * @return integer 
     */
    public function getTaxonomyId()
    {
        return $this->taxonomyId;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     * @return TaxonomiesLinks
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;
    
        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer 
     */
    public function getItemId()
    {
        return $this->itemId;
    }
}