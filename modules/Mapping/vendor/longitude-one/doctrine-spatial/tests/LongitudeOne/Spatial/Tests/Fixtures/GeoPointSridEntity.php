<?php
/**
 * This file is part of the doctrine spatial extension.
 *
 * PHP 7.4 | 8.0 | 8.1
 *
 * (c) Alexandre Tranchant <alexandre.tranchant@gmail.com> 2017 - 2022
 * (c) Longitude One 2020 - 2022
 * (c) 2015 Derek J. Lambert
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace LongitudeOne\Spatial\Tests\Fixtures;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;

/**
 * Geography point entity specifying SRID.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license https://dlambert.mit-license.org MIT
 *
 * @Entity
 *
 * @internal
 */
class GeoPointSridEntity
{
    /**
     * @var int
     *
     * @Id
     * @GeneratedValue(strategy="AUTO")
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @var Point
     *
     * @Column(type="geopoint", nullable=true, options={"srid": "4326"})
     */
    protected $point;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get geography.
     *
     * @return Point
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * Set geography.
     *
     * @param Point $point point to set
     *
     * @return self
     */
    public function setPoint(Point $point)
    {
        $this->point = $point;

        return $this;
    }
}
