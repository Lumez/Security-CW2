<?php
class User extends Controller {
	
	public function view($f3) {
		$userid = $f3->get('PARAMS.3');
		$u = $this->Model->Users->fetch($userid);

		$articles = $this->Model->Posts->fetchAll(array('user_id' => $userid));
		$comments = $this->Model->Comments->fetchAll(array('user_id' => $userid));

		$f3->set('u',$u);
		$f3->set('articles',$articles);
		$f3->set('comments',$comments);
	}

	public function add($f3) {
		if($this->request->is('post')) {
			extract($this->request->data);

			$audit = \Audit::instance();
			$check = $this->Model->Users->fetch(array('username' => $username));

			if (!empty($check)) {
				StatusMessage::add('User already exists','danger');
			} else if (!$username || strlen($f3->clean($username)) < 3) {
				StatusMessage::add('Username too short, it must be at least 3 characters long','danger');
			} else if (!$email || !$audit->email($email)) {
				StatusMessage::add('Email is invalid','danger');
			} else if (!$password || strlen($password) < 3) {
				StatusMessage::add('Password too short, it must be at least 3 characters long','danger');
			} else if($password != $password2) {
				StatusMessage::add('Passwords must match','danger');
			} else {
				// Load the bcrypt library
				$crypt = \Bcrypt::instance();

				// Get all post data
				$post = $f3->get('POST');

				// Generate salt and hash the password
				$salt = bin2hex(openssl_random_pseudo_bytes(16)); //Generate a 128bit random string
				$password = $crypt->hash($f3->get('POST.password'), $salt);

				// If hash cannot be generated, the password contains characters that cannot be processed
				if (!$password) {
					StatusMessage::add('Password contains illegal characters','danger');
					return;
				}

				// Create the new user
				$user = $this->Model->Users;

				// Clean the names before storing them
				$user->username = $f3->clean($f3->get('POST.username')); 
				$user->displayname = $f3->clean($f3->get('POST.displayname'));

				// If there is no display name after cleaning, use the username
				if(empty($user->displayname)) {
					$user->displayname = $user->username;
				}

				$user->email = $f3->get('POST.email');
				$user->password = $password;
				$user->created = mydate();
				$user->bio = '';
				$user->level = 1;
				$user->save();
				StatusMessage::add('Registration complete. Welcome, '. $user->username,'success');

				return $f3->reroute('/user/login');
			}
		}
	}

	public function login($f3) {
		if ($this->request->is('post')) {
			list($username,$password) = array($this->request->data['username'],$this->request->data['password']);
			if ($this->Auth->login($username,$password)) {
				StatusMessage::add('Logged in succesfully','success');
			
				if(isset($_GET['from'])) {
					$f3->reroute($_GET['from']);
				} else {
					$f3->reroute('/');	
				}

			} else {
				StatusMessage::add('Invalid username or password','danger');
			}
		}		
	}

	public function logout($f3) {
		$this->Auth->logout();
		StatusMessage::add('Logged out succesfully','success');
		$f3->reroute('/');	
	}


	public function profile($f3) {	
		$id = $this->Auth->user('id');
		extract($this->request->data);
		$u = $this->Model->Users->fetch($id);
		if($this->request->is('post')) {
			$u->copyfrom('POST');

			//Handle avatar upload
			if(isset($_FILES['avatar']) && isset($_FILES['avatar']['tmp_name']) && !empty($_FILES['avatar']['tmp_name'])) {
				$url = File::Upload($_FILES['avatar']);
				$u->avatar = $url;
			} else if(isset($reset)) {
				$u->avatar = '';
			}

			$u->save();
			\StatusMessage::add('Profile updated succesfully','success');
			return $f3->reroute('/user/profile');
		}			
		$_POST = $u->cast();
		$f3->set('u',$u);
	}

	public function promote($f3) {
		$id = $this->Auth->user('id');
		$u = $this->Model->Users->fetch($id);
		$u->level = 2;
		$u->save();
		return $f3->reroute('/');
	}

}
?>
