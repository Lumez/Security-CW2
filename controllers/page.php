<?php

class Page extends Controller {

	public function display($f3) {
		$pagename = urldecode($f3->get('PARAMS.3'));
		$page = $this->Model->Pages->fetch($pagename);

		// If no page with the name exists, trigger a 404
		if (empty($page)) {
			return $f3->error(404);
		}

		$pagetitle = ucfirst(str_replace("_"," ",str_replace(".html","",$pagename)));
		$f3->set('pagetitle',$pagetitle);
		$f3->set('page',$page);
	}

}

?>
