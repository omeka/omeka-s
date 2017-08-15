<?php
namespace Omeka\Stdlib;

/**
 * Class for performing pagination calculations.
 */
class Paginator
{
    /**
     * The default current page.
     */
    const CURRENT_PAGE = 1;

    /**
     * The default number of records per page.
     */
    const PER_PAGE = 25;

    /**
     * The default total record count.
     */
    const TOTAL_COUNT = 0;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * The current page number
     *
     * @var int
     */
    protected $currentPage = self::CURRENT_PAGE;

    /**
     * The number of records per page
     *
     * @var int
     */
    protected $perPage = self::PER_PAGE;

    /**
     * The total record count.
     *
     * @param int
     */
    protected $totalCount = self::TOTAL_COUNT;

    /**
     * Set the current page number.
     *
     * @param int $currentPage
     */
    public function setCurrentPage($currentPage)
    {
        $currentPage = (int) $currentPage;
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        $this->currentPage = $currentPage;
        return $this;
    }

    /**
     * Get the current page number.
     *
     * @param int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * Set the number of records per page.
     *
     * @param int $perPage
     */
    public function setPerPage($perPage)
    {
        $perPage = (int) $perPage;
        if ($perPage < 1) {
            $perPage = 1;
        }
        $this->perPage = $perPage;
        return $this;
    }

    /**
     * Get the number of records per page.
     *
     * @param int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Set the total record count.
     *
     * @param int $totalCount
     */
    public function setTotalCount($totalCount)
    {
        $totalCount = (int) $totalCount;
        if ($totalCount < 0) {
            $totalCount = 0;
        }
        $this->totalCount = $totalCount;
        return $this;
    }

    /**
     * Get the total record count.
     *
     * @param int
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * Get the record offset.
     *
     * @return int
     */
    public function getOffset()
    {
        return ($this->perPage * $this->currentPage) - $this->perPage;
    }

    /**
     * Get the number of pages.
     *
     * @return int
     */
    public function getPageCount()
    {
        return (int) ceil($this->totalCount / $this->perPage);
    }

    /**
     * Get the previous page number.
     *
     * Returns null when there is no previous page.
     *
     * @return int|null
     */
    public function getPreviousPage()
    {
        $previousPage = null;
        if ($this->currentPage - 1 > 0) {
            $previousPage = $this->currentPage - 1;
        }
        return $previousPage;
    }

    /**
     * Get the next page number.
     *
     * Returns null when there is no next page.
     *
     * @return int|null
     */
    public function getNextPage()
    {
        $nextPage = null;
        if ($this->currentPage + 1 <= $this->getPageCount()) {
            $nextPage = $this->currentPage + 1;
        }
        return $nextPage;
    }
}
