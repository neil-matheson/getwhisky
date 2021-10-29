<?php
// The User class, responsible for user data, checks that the altered user data is valid before changing the 
// data in the temporary instance of that user. It then sends that data to the UserCRUD class.
require_once("util.class.php");
require_once("usercrud.class.php");
require_once("userhash.class.php");
require_once("dob.class.php");

class User {
    private $userid;
	private $username;
	private $userhash; 
	private $firstname;
    private $surname; 
	private $lastsession; 
	private $email; 
	private $dob; 
	private $usertype;
    private $vKey;
    private $verified;
	
	public function __construct() {
		$this->userid=-1;
		$this->username="Anon";
		$this->usertype=0;
        $this->verified=false;
		$this->dob = new DOB();
		$this->userhash=new UserHash(); // when user is constructed an instance of userHash is also
										// constructed and assigned to the userhash field
	}
	
	private function setUserid($userid) {
		$this->userid=$userid;
	}

	public function setGuestUserid() {
		if ($_SESSION['guest'] == true) {
			$this->setUserid($_SESSION['userid']);
		}
	}
	
	private function setUsername($username) {
		$message="";
		if (util::valUName($username)) {
			$this->username=$username;
		} else {$message="Invalid username<br />";}
		return $message;
	}
	
	// checks for validation via the util class and sends an error message if username
	// does not meet criteria
	private function setFirstname($firstname) {
		$message="";
		if(util::valStr($firstname)) {
			$this->firstname=$firstname;
		} else {$message="Invalid Firstname<br />";}
		return $message;
	}

	
	private function setSurname($surname) {
		$message="";
		if(util::valStr($surname)) {
			$this->surname=$surname;
		} else { $message="Invalid Surname<br />";}
		return $message;
		
	}
	
	private function setEmail($email) {
		$message="";
		if (util::valEmail($email)) {
			$this->email=$email;
		} else {$message="Invalid Email Address<br />";}
		return $message;
		
	}
	
	private function setDOB($dob) {
		$message="";
		if(!$this->dob->setDOB($dob))
		{ $message.="Date is not correct<br />"; }
		if($this->dob->getAge()<16)
		{ $message.= "User must be 16 years or older<br />";}	
		return $message;
	}

	
	private function setSession($session) {
		$this->lastsession=$session;
	}
	
	private function setUsertype($usertype) {
		$this->usertype=$usertype;
	}

    private function setVerified($verified) {
        $this->verified = $verified;
    }

	private function setVerificatinKey($vKey) {
		$this->vKey = $vKey;
	}
	
	public function getUserid() { return $this->userid; }
	public function getUsername() { return $this->username; }
	public function getFirstname() { return $this->firstname; }
	public function getSurname() { return $this->surname; }
	public function getEmail() { return $this->email; }
	public function getDOB($format="Y-m-d") { return $this->dob->format($format); }
	public function getSession() { return $this->lastsession; }
	public function getUsertype() { return $this->usertype; }
    public function getVerifiedStatus() { return $this->verified; }
	public function getVerificationKey() { return $this->vKey; }

        /*  
            UserCRUD class is used to get the data. It is checked that a single record is returned
            method should return either 0 records, if the username is not found, or 1 record if the
            username is found.
            If a single record is returned the setters are used to set the values of the different 
            fields for the class.
        */
        public function getUserByName($userid) {
            $haveuser=false;
            $source=new UserCRUD();
            $data=$source->getUserByName($userid);
            if(count($data)==1) {
                $user=$data[0];
                $this->setUserid($user["userid"]);
                $this->setUsername($user["username"]);
                $this->setFirstname($user["firstname"]);
                $this->setSurname($user["surname"]);
                $this->setSession($user["lastsession"]);
                $this->setEmail($user["email"]);
                $this->setDOB($user["dob"]);
				$this->setUsertype($user["usertype"]);
                $this->setVerified($user["verified"]);
				$this->setVerificatinKey($user["vkey"]);
				$this->userhash->initHash($user["userpass"]);
                $haveuser=true;
            } 
            return $haveuser;
		}
		
		// takes the details submitted by a user compares it to the details stored in the database
		public function authNamePass($username, $userpass) {
			$authenticated=$this->getUserByName($username);
			if($authenticated) {
				$authenticated=$this->userhash->testPass($userpass);
			}
			return $authenticated;
		}

		public function getUserById($userid) {
			$haveuser=false;
			$source=new UserCRUD();
			$data=$source->getUserById($userid);
			if(count($data)==1) {
				$user=$data[0];
				$this->setUserid($user["userid"]);
				$this->setUsername($user["username"]);
				$this->setFirstname($user["firstname"]);
				$this->setSurname($user["surname"]);
				$this->setSession($user["lastsession"]);
				$this->setEmail($user["email"]);
				$this->setDOB($user["dob"]);
				$this->setUsertype($user["usertype"]);
                $this->setVerified($user["verified"]);
				$this->setVerificatinKey($user["vkey"]);
                $this->userhash->initHash($user["userpass"]);
				$haveuser=true;
			} 
			return $haveuser;
		}
	
		// gets the session sends it to the userCRUD class which sends the session to
		// the lastSession attribute in the user table
		public function storeSession($userid, $session="") {
			$result=0;
			$target=new UserCRUD();
			$result=$target->storeSession($userid, $session);
			if($result) {$this->setSession($session);}
			return $result;
		}
		
		// returns false if the new session is not the same as the user table session
		// This is what sets the values of the user
		public function authIdSession($id, $session) {
			$authenticated=false;
			$authenticated=$this->getUserById($id);
			if($authenticated) {
				if($this->getSession()!=$session) { $authenticated=false; }
			}
			return $authenticated;
		}
		
		// password setter
		// set up the instance of the UserHash class with a hash of a new plaintext password
		// returns an error message if the password is too weak
		private function setPass($password) {
			$message="";
			if($this->userhash->checkRules($password)) {
				$this->userhash->newHash($password);
			} else {
				$message="Password did not meet complexity standards<br />";
				$message.="Please enter a password between 8 and 72 characters<br />";
			}
			return $message;
		}
	
		
		// takes the number of parameters which comes from the form and after using setters for the
		// user, passes the parameters to a store method in the userCRUD class.
		// messages variable will be used to notify the user of any issues.
        // sends a verification key to the database but doesn't store that key as unnecessary.
		public function registerUser($userid,$username,$password, $firstname,$surname, $email, $dob, $vKey) {
			$insert=0;
			$messages="";
			$target=new UserCRUD();
		
			$messages.=$this->setUserid($userid);
			$messages.=$this->setUsername($username);
			$messages.=$this->setFirstname($firstname);
			$messages.=$this->setSurname($surname);
			$messages.=$this->setPass($password); // adds error message if pass doesn't meet standards
			$messages.=$this->setEmail($email);
			$messages.=$this->setDOB($dob);
			if($messages=="") {
				$insert=$target->storeNewUser($this->getUserid(), $this->getUsername(),$this->getFirstname(),$this->getSurname(),$this->userhash->getHash(),$this->getEmail(), $this->getDOB(), $vKey);
				if($insert!=1) { $messages.=$insert;$insert=0; }
			}
			$result=['insert' => $insert,'messages' => $messages];
			return $result;
		}

		// allows a user to update their details by calling the update method in the userCRUD class
		// also uses the util class to validate
		public function updateUser($username,$firstname,$surname,$password,$email,$dob,$usertype, $userid) {		
			$update=0;
			$messages="";
			$found=$this->getUserById($userid);
			$target=new UserCRUD();
			if($found) {
				if(util::posted($username)){$messages.=$this->setUsername($username);}
				if(util::posted($firstname)){$messages.=$this->setFirstname($firstname);}
				if(util::posted($surname)){$messages.=$this->setSurname($surname);}
				if(util::posted($password)){$messages.=$this->setPass($password);}
				if(util::posted($email)){$messages.=$this->setEmail($email);}
				if(util::posted($dob)){$messages.=$this->setDOB($dob);}
				if(util::posted($usertype)){$messages.=$this->setUsertype($usertype);}
				if($messages=="") {
					$update=$target->updateUser($this->getUsername(), $this->getFirstname(), $this->getSurname(), $this->userhash->getHash(), $this->getEmail(), $this->getDOB(),$this->getUsertype(), $userid);
					if($update!=1) {$messages=$update;$update=0;}
				}			
			}
			$result=['update' => $update, 'messages' => $messages];	
			return $result;
		}
	
		// sends user menu data to the $ouput variable via the getters
		public function __toString() {
			$output="";
			$output.="<p>User : ".$this->getusername()."</p>";
			$output.="<p>Name : ".$this->getFirstname()." ".$this->getSurname()."</p>";
			$output.="<p>Email : ".$this->getEmail()."</p>";
			$output.="<p>DOB : ".$this->getDOB()."</p>";
			$typeUser = "Anonymous";
			switch($this->getUsertype()) {
				case 1: $typeUser = "Suspended";
			break;
				case 2: $typeUser = "User";
			break;
				case 3: $typeUser = "Admin"; 
			}
			$output.="<p>Account type : ".$typeUser."</p>";
            $output.="<p>Verified? : ".$this->getVerifiedStatus()."</p>";
			return $output;
		}
	
    }
    
?>