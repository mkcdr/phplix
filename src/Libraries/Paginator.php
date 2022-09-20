<?php

namespace App\Libraries;

class Paginator
{
    /**
     * @var int $current Current page index
     */
    private $current;

    /**
     * @var int $total Total number of results
     */
    private $total;

    /**
     * @var int $limit Number of results per page
     */
    private $limit;

    /**
     * @var int $adjacents Maximum number of pages to show
     */
    private $adjacents;

    /**
     * @var int $pages_num Number of pages
     */
    private $pages_num;

    /**
     * @var string $link Link for pagination
     */

    /**
     * @param int $current
     * @param int $total
     * @param int $limit
     */
    public function __construct(string $link, int $current, int $total, int $limit, int $adjacents=3) 
    {
        $this->link     = $link;
        $this->current  = $current;
        $this->total    = $total;
        $this->limit    = $limit;
        $this->adjacents = $adjacents;
        $this->pages_num = $this->limit == 0 ? 0 : ceil($this->total/$this->limit);
    }

    /**
     * Get link
     * 
     * @return string
     */
    public function getLink(int $page=0) : string
    {
        return str_replace('[:page:]', $page, $this->link);
    }

    /**
     * Get current page
     * 
     * @return int
     */
    public function getCurrent() : int
    {
        return $this->current;
    }

    /**
     * Get the number of pages
     * 
     * @return int
     */
    public function getPagesNum() : int
    {
        return $this->pages_num;
    }

    /**
     * Check if current page has a previous page
     * 
     * @return bool
     */
    public function hasPrev() : bool
    {
        return $this->current > 1;
    }

    /**
     * Check if current page has a next page
     * 
     * @return bool
     */
    public function hasNext() : bool
    {
        return $this->current < $this->pages_num;
    }

    /**
     * Get previous page
     * 
     * @return int
     */
    public function getPrev()
    {
        return $this->current - 1;
    }

    /**
     * Get next page
     * 
     * @return int
     */
    public function getNext()
    {
        return $this->current + 1;
    }

    /**
     * Check if current page is the first one
     * 
     * @return bool
     */
    public function isFirst() : bool
    {
        return $this->current == 1;
    }

    /**
     * Check if current page is the last one
     * 
     * @return bool
     */
    public function isLast() : bool
    {
        return $this->current == $this->pages_num;
    }

    /**
     * Get pages array
     * 
     * @return array
     */
    public function getPages() : array
    {
        $pages = [];
        
        if ($this->adjacents * 2 + 3 < $this->pages_num)
        {
            if ($this->current < $this->adjacents * 2 - 1)
            {
                $start = 1;
                $end = $this->adjacents * 2;
            }
            elseif ($this->current > $this->pages_num - $this->adjacents * 2 + 2)
            {
                $start = $this->pages_num - $this->adjacents * 2 + 1;
                $end = $this->pages_num;
            }
            else
            {
                $start = $this->current - $this->adjacents;
                $end = $this->current + $this->adjacents;
            }

            if ($start > 1)
            {
                $pages[] = 1;
                $pages[] = '&hellip;';
            }
    
            for ($i=$start; $i <= $end; $i++) 
            { 
                $pages[] = $i;
            }
    
            if ($end < $this->pages_num)
            {
                $pages[] = '&hellip;';
                $pages[] = $this->pages_num;
            }
        }
        else
        {
            for ($i=1; $i <= $this->pages_num; $i++) 
            { 
                $pages[] = $i;
            }
        }

        return $pages;
    }
}