<?php

	class AuthHelper {

		/** Construct a new Auth helper */
		public function __construct($controller) {
			$this->controller = $controller;
		}

		/** Attempt to resume a previously logged in session if one exists */
		public function resume() {
			$f3=Base::instance();				

			//Ignore if already running session	
			if($f3->exists('SESSION.user.id')) return;

			//Log user back in from cookie
			if($f3->exists('COOKIE.RobPress_User')) {
				// Load the models and find the user with the token provided						
				$model = $this->controller->Model;
				$user = $model->Users->fetch(array('token' => $f3->get('COOKIE.RobPress_User')));

				// If there is a user and the token has not expired, log them in
				if (!empty($user) && strtotime($user->tokenexpiry) > time()) {
					return $this->forceLogin($user->cast());
				}

				// The cookie was not valid so clear it
				setcookie('RobPress_User', '', time()-3600, '/');
			}
		}		

		/** Look up user by username and password and log them in */
		public function login($username,$password) {
			$f3=Base::instance();

			// Load the bcrypt library
			$crypt = \Bcrypt::instance();

			// Load the models and find the user with the username provided						
			$model = $this->controller->Model;
			$user = $model->Users->fetch(array('username' => $username));

			// If a user exists and the password is correct, setup the session and log them in, otherwise abort and return false
			if (!empty($user) && $crypt->verify($password, $user->password)) {		
				$this->setupSession($user);
				return $this->forceLogin($user->cast());
			} 
			return false;
		}

		/** Log user out of system */
		public function logout() {
			$f3=Base::instance();							

			//Kill the session and clear the cookie
			session_destroy();
			setcookie(session_name(),'',time()-3600,'/');

			//Kill the cookie
			setcookie('RobPress_User','',time()-3600,'/');

			//Start a fresh session
			session_start();
		}

		/** Set up the session for the current user */
		public function setupSession($user) {
			// Remove previous session and clear the cookie
			session_destroy();
			setcookie(session_name(),'',time()-3600,'/');

			// Generate a random 256bit string to use as cookie for relogging in
			$token = bin2hex(openssl_random_pseudo_bytes(32));
			$tokenExpiry = time() + 3600 * 24 * 30;

			// Store the new token
			$user->token = $token;
			$user->tokenexpiry = mydate($tokenExpiry);
			$user->save();

			// Set the cookie
			setcookie('RobPress_User', $token, $tokenExpiry, '/');

			//And begin!
			session_start();
		}

		/** Not used anywhere in the code, for debugging only */
		public function specialLogin($username) {
			//YOU ARE NOT ALLOWED TO CHANGE THIS FUNCTION
			$f3 = Base::instance();
			$user = $this->controller->Model->Users->fetch(array('username' => $username));
			$array = $user->cast();
			return $this->forceLogin($array);
		}

		/** Force a user to log in and set up their details */
		public function forceLogin($user) {
			//YOU ARE NOT ALLOWED TO CHANGE THIS FUNCTION
			$f3=Base::instance();						
			$f3->set('SESSION.user',$user);
			return $user;
		}

		/** Get information about the current user */
		public function user($element=null) {
			$f3=Base::instance();
			if(!$f3->exists('SESSION.user')) { return false; }
			if(empty($element)) { return $f3->get('SESSION.user'); }
			else { return $f3->get('SESSION.user.'.$element); }
		}

	}

?>
