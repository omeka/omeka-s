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
use Doctrine\ORM\Mapping\Table;
use LongitudeOne\Spatial\PHP\Types\Geometry\MultiPolygon;

/**
 * Multi Polygon entity.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license https://dlambert.mit-license.org MIT
 *
 * @Entity
 * @Table
 *
 * @internal
 */
class MultiPolygonEntity
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
     * @var MultiPolygon
     *
     * @Column(type="multipolygon", nullable=true)
     */
    protected $multiPolygon;

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
     * Get multipolygon.
     *
     * @return MultiPolygon
     */
    public function getMultiPolygon()
    {
        return $this->multiPolygon;
    }

    /**
     * Set multipolygon.
     *
     * @param MultiPolygon $multiPolygon multipolygon to set
     *
     * @return self
     */
    public function setMultiPolygon(MultiPolygon $multiPolygon)
    {
        $this->multiPolygon = $multiPolygon;

        return $this;
    }
}
