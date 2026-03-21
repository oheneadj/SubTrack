<?php

namespace App\Traits;

trait WithSorting
{
    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function applySorting($query)
    {
        // Handle sorting through relationships if needed, e.g. 'client.name'
        if (str_contains($this->sortColumn, '.')) {
            // For simple cases, we might rely on the main query if it's a joined column,
            // or we might need specific logic in the component. 
            // By default, assuming it's a direct column or a scope handles it.
            return $query->orderBy($this->sortColumn, $this->sortDirection);
        }

        return $query->orderBy($this->sortColumn, $this->sortDirection);
    }
}
