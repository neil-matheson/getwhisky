<?php
//The Page class, responsible for the application state, checks that the user is authorised 
//to carry out the update action and if so passes the data to a temporary instance of the User class.
require_once("user.class.php");
require_once("menu.class.php");

class Page {
	private $user, $pagetype, $isauthenticated, $menu;
	
	public function __construct($pagetype=0){
		session_start();
		$this->setPagetype($pagetype);
		$this->user = new User();
		$this->setStatus(false);
        $this->checkUser();
        $this->menuObj = new Menu($this->getUser()->getUsertype()); // sends usertype to the menu class to create a menu depending on
                                                                  // the user type
		$this->menu = $this->menuObj->getMenuItems();
	}
	
	public function getPagetype() { return $this->pagetype;}
	public function getStatus() { return $this->isauthenticated;}
	public function getUser() {return $this->user;}
    public function getMenu() {return $this->menu; }
    
	private function setPagetype($pagetype) {$this->pagetype=(int)$pagetype;}
	private function setStatus($status) {$this->isauthenticated=(bool)$status;}
	public function setMenu() { $this->menu = new Menu($this->getUser()->getUsertype()); }
	
	// Checks for a user in the $_SESSION
	// if session is found set status is set to true and
	// the authIdSession retrieves the user details and stores them
	// in the user class
	public function checkUser() {
		// Establish guest session
		if (!isset($_SESSION['userid'])) {
			$_SESSION['userid'] = md5(time(). bin2hex(random_bytes(10)));
			$_SESSION['guest'] = true;
		}

		if(isset($_SESSION['userid']) && $_SESSION['userid']!="") {
			// Guest logged in
			if (isset($_SESSION['guest']) && $_SESSION['guest'] == true) {
				$this->getUser()->setGuestUserid($_SESSION['userid']);
			} 
			
			// User logged in
			if (isset($_SESSION['guest']) && $_SESSION['guest'] == false) {
				$this->setStatus($this->getUser()->authIdSession($_SESSION['userid'],session_id()));
				$this->checkInactivityLength();
			}
		}
		
		// a catch for both guests and registered users to check if they're trying to access restricted content
		if((!$this->getStatus() && $this->getPagetype()>0) || ($this->getStatus() && $this->getUser()->getUsertype()<$this->getPagetype())) {
			$this->logout();
		}
	}
	
	private function checkInactivityLength() {
		if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
			$this->logout();
		}
		$_SESSION['last_activity'] = time();
	}
	/*******************
	 * Checks for user credentials with the authNamePass method
	 * if the hash of the passed in password matches the hash in the database
	 * the user is logged in and a session is stored in the database
	 **************************************************/
	public function login($username, $userpass) {
		session_regenerate_id();
		if($this->getUser()->authNamePass($username,$userpass)) {
			$_SESSION['guest'] = false;
			$this->getUser()->storeSession($this->getUser()->getUserid(),session_id());
			$_SESSION['userid']=$this->getUser()->getUserid();
			$_SESSION['last_activity'] = time(); // init inactivity timer

			// userlevel logic here
			switch($this->getUser()->getUsertype()) {
				case 1:
					header("location: suspended.php");
					break;
				case 2:
					header("location: user.php");
					break;
				case 3:
					header("location: admin.php");
					break;
			}
			exit();
			
		} else {
			echo "<br />Authentication failed";
		}
	}

	/****************
	 * A single use function that logs the user in on the backend
	 * during the registration process
	 **************************************************/
	public function loginDiscreet($username, $userpass) {
		session_regenerate_id();
		if($this->getUser()->authNamePass($username,$userpass)) {
			$this->getUser()->storeSession($this->getUser()->getUserid(),session_id());
			$_SESSION['userid']=$this->getUser()->getUserid();
			$_SESSION['guest'] = false;
		} else {
			echo "<br />Authentication failed";
		}
	}
	
	public function logout() {
		if(isset($_SESSION['userid']) && $_SESSION['userid']!="") {
			$this->getUser()->storeSession($_SESSION['userid']);
		}
		session_regenerate_id();
		session_unset();
		session_destroy();
		header("location: login.php");
		exit();
	}

	/*
		checks that the user is authorised to carry out the update action and if so passes the data to a 
		temporary instance of the User class.
	*/
	public function updateUser($username,$firstname,$surname,$password,$email,$dob,$userid, $usertype) {
		if($this->getUser()->getUsertype()==3 || $this->getUser()->getUserid()==$userid) {
			$usertoupdate=new User();
			$usertoupdate->getUserById($userid);
			if($this->getUser()->getUsertype()!=3) {
				$usertype="";
			}
			$result=$usertoupdate->updateUser($username,$firstname,$surname,$password,$email,$dob,$usertype, $userid);
			return $result;
			
		}
	}

    public function displayHead() {
		$html= "
				<meta charset='UTF-8'>
				<meta http-equiv='X-UA-Compatible' content='IE=edge'>
				<meta name='viewport' content='width=device-width, initial-scale=1.0'>
				<!-- JQuery, FontAwesome -->
				<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>
				<script src='https://kit.fontawesome.com/1942d39d14.js' crossorigin='anonymous'></script>
				<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
				<!-- GoogleFonts -->
				<link rel='preconnect' href='https://fonts.googleapis.com'>
				<link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
				<link href='https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap' rel='stylesheet'>
				<link href='https://fonts.googleapis.com/css2?family=Courier+Prime&display=swap' rel='stylesheet'>
				<!-- CSS -->
				<link rel='stylesheet' href='style/css/reset.css'>
				<link rel='stylesheet' href='style/css/style.css'>
				<link rel='stylesheet' href='style/css/alerts.css'>
				";
		return $html;
	}

	public function displayHeader() {
		$html="";
		$html.="<div class='header-content'>";
			$html.="<a href='/index.php'><img class='header-logo' src='assets/getwhisky-logo-small.png' alt=''></a>";
			$html.="<nav class='header-menu'>";
				$html.="<ul>";
					$html.="<li><a href='/cart.php'><i class='header-nav-icon fas fa-shopping-basket'><span class='cart-count'>0</span></a></i><a class='header-nav-link' href='/cart.php'>basket</a></li>";
					foreach ($this->getMenu() as $menuItem) {
						$html.="<li><a class='header-nav-link' href='".$menuItem['url']."'>".$menuItem['pagename']."</a></li>";
					}
				$html.="</ul>";
			$html.="</nav>";
		$html.="</div>";
	
	return $html;
	}
}
?>
