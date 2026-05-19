<?php

namespace sdo\Services;

class PaginationService
{
    public int $totalItems;
    public int $itemsPerPage;
    public int $totalPages;
    public int $currentPage;
    public string $baseUrl;

    public function __construct(int $totalItems, int $currentPage = 1, int $itemsPerPage = 10, string $baseUrl = '')
    {
        $this->totalItems = $totalItems;
        $this->itemsPerPage = $itemsPerPage > 0 ? $itemsPerPage : ($totalItems > 0 ? $totalItems : 1);

        // This is the core fix: totalPages must always be at least 1.
        if ($this->totalItems === 0) {
            $this->totalPages = 1;
        } else {
            $this->totalPages = (int)ceil($this->totalItems / $this->itemsPerPage);
        }
        
        // This ensures currentPage is always a valid, positive number within the bounds of the total pages.
        $this->currentPage = min(max(1, $currentPage), $this->totalPages);
        $this->baseUrl = $baseUrl;
    }

    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }

    public function getLimit(): int
    {
        return $this->itemsPerPage;
    }
}
