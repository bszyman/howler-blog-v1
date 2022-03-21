<?php

class Paginator {
	private ArrayObject $page_listing;
	private int $current_page;
	private int $max_page;
	
	public function __construct() {
		$this->page_listing = new ArrayObject();
        $this->current_page = 1;
        $this->max_page = 1;
	}
	
	public function setCurrentPage(int $page_number): void {
        if ($page_number > 0) {
            $this->current_page = $page_number;
        }
	} 
	
	public function setMaxPage(int $page_number): void {
        if ($page_number > 0) {
            $this->max_page = $page_number;
        }
	}
	
	// Returns an array of numbers (n), from n = current_page to max_page
	public final function getPageRange(): ArrayObject {
		if ($this->max_page == 1) {
			$this->page_listing->append(1);
		} else if ($this->max_page < 8) {
			for ($x = 1; $x <= $this->max_page; $x++)
				$this->page_listing->append($x);
			
		} else {
			$max_page_tracker = 6;
			$lower_bound = $this->current_page - 3;
			
			if ($lower_bound < 1)
				$lower_bound = 1;
			
			$max_page_tracker -= ($this->current_page - $lower_bound);
			
			$upper_bound = $this->current_page + $max_page_tracker;
			
			if ($upper_bound > $this->max_page)
				$upper_bound = $this->max_page;
			
			
			for ($x = $lower_bound; $x <= $upper_bound; $x++)
				$this->page_listing->append($x);
		}
		
		return $this->page_listing;
	}
}
