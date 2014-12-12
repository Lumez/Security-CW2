<?php

class PagesModel {

	/** Create a new page */
	public function create($pagename) {
		$pagedir = getcwd() . "/pages/";
		touch($pagedir . $pagename . ".html");
	}

	/** Load the contents of a page */
	public function delete($pagename) {
		$pagedir = getcwd() . "/pages/";
		$file = $pagedir . $pagename;
		if(!file_exists($file)) {
			$file .= ".html";
		}
		if(!file_exists($file)) { return false; }
		unlink($file);
	}

	/** Get all available pages */
	public function fetchAll() {
		$pagedir = getcwd() . "/pages/";
		$pages = array();
		if ($handle = opendir($pagedir)) {
			while (false !== ($file = readdir($handle))) {
				if (!preg_match('![.]html!sim',$file)) continue;
				$title = ucfirst(str_ireplace("_"," ",str_ireplace(".html","",$file)));
				$pages[$title] = $file;
			}
			closedir($handle);
		}
		return $pages;
	}

	/** Load the contents of a page */
	public function fetch($pagename) {
		// Load the possible pages
		$pages = $this->fetchAll();

		// Convert the name into the title format so it can be seached for
		$pagetitle = ucfirst(str_replace("_"," ",str_replace(".html","",$pagename)));
		
		// If the page requested is one that can be retrieved, get it
		if(isset($pages[$pagetitle])) { 
			$pagedir = getcwd() . "/pages/";
			return file_get_contents($pagedir . $pages[$pagetitle]);
		} else {
			return false; 
		}
	}

	/** Save contents of the page based on title and content field to file */
	public function save() {
		$pagedir = getcwd() . "/pages/";
		$file = $pagedir . $this->title;
		if(!file_exists($file)) {
			$file .= ".html";
		}
		if(!file_exists($file)) { return false; }
		if(!isset($this->content)) { return false; } 

		return file_put_contents($file,$this->content);
	}

}

?>
