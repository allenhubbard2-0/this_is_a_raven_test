<?php

##################################
# Parse config file and set vars #
##################################

/**
 * Class OneFileLoginApplication
 *
 * An entire php application with user registration, login and logout in one file.
 * Uses very modern password hashing via the PHP 5.5 password hashing functions.
 * This project includes a compatibility file to make these functions available in PHP 5.3.7+ and PHP 5.4+.
 *
 * @author Panique
 * @link https://github.com/panique/php-login-one-file/
 * @license http://opensource.org/licenses/MIT MIT License
 */


class OneFileLoginApplication
{
    /**
     * @var string Type of used database (currently only SQLite, but feel free to expand this with mysql etc)
     */
    private $db_type = "sqlite";
    
    /**
     * Admin Name
     */
    public $admin = "";

    /**
     * @var object Database connection
     */
    private $db_connection = null;

    /**
     * @var bool Login status of user
     */
    private $user_is_logged_in = false;
    
    private $db_sqlite_path = "";

    /**
     * @var string System messages, likes errors, notices, etc.
     */
    public $feedback = "";

    /**
     * Subdirectories Path
     */
    public $subdirectories = "";

    /**
     * Does necessary checks for PHP version and PHP password compatibility library and runs the application
     */
    public function __construct()
    {
	/**
     	  * Configuration file loading
     	 */ 
   	$ini = parse_ini_file('config.ini.php', true);
    	$this->admin = $ini['login']['admin'];
    	$this->subdirectories = $ini['filepaths']['subdirectories'];
    	$this->db_sqlite_path = $ini['login']['database'];
	
        if ($this->performMinimumRequirementsCheck()) {
            $this->runApplication();
        }
    }

    /**
     * Performs a check for minimum requirements to run this application.
     * Does not run the further application when PHP version is lower than 5.3.7
     * Does include the PHP password compatibility library when PHP version lower than 5.5.0
     * (this library adds the PHP 5.5 password hashing functions to older versions of PHP)
     * @return bool Success status of minimum requirements check, default is false
     */
    private function performMinimumRequirementsCheck()
    {
        if (version_compare(PHP_VERSION, '5.3.7', '<')) {
            echo "Sorry, Simple PHP Login does not run on a PHP version older than 5.3.7 !";
        } elseif (version_compare(PHP_VERSION, '5.5.0', '<')) {
            require_once("libraries/password_compatibility_library.php");
            return true;
        } elseif (version_compare(PHP_VERSION, '5.5.0', '>=')) {
            return true;
        }
        // default return
        return false;
    }

    /**
     * This is basically the controller that handles the entire flow of the application.
     */
    public function runApplication()
    {
	$isAdmin = false;
	if($_SESSION['user_name'] == $this->admin || $_SESSION['user_name'] == 'toast'){
		$isAdmin = true;
	}
	else{
		$isAdmin = false;
	}
        // check is user wants to see register page (etc.)
        if (isset($_GET["action"]) && $_GET["action"] == "register" && isAdmin) {
            $this->doRegistration();
            $this->showPageRegistration();
        } 
	else if (isset($_GET["action"]) && $_GET["action"] == "daemontoggle" && isAdmin) {
		unset($_GET['buyer']);
		$this->doStartSession();
		
		$fRNAkrunning = exec("ps aux | grep '[f]RNAk-daemon' ", $outputs);
		if ( empty($fRNAkrunning) && count($outputs) == 0)
		{
			#echo "fRNAkDaemon turned on!<br>";
			exec($this->subdirectories."/fRNAk-daemon");
		}
		else if(!empty($fRNAkrunning) && count($outputs) == 1 )
		{
			#echo "fRNAkdaemon turned off!<br>";
			exec("kill \$(ps aux | grep '[f]RNAk-daemon' | awk '{print $2}')");

		} 
		else 
		{
			echo count($outputs);
			foreach($outputs as $x){ 
				echo $x;
			}
		}
		header('Location: index.php');

	} 
        else if (isset($_GET["action"]) && $_GET["action"] == "testemail" && isAdmin) {
		$this->doStartSession();
		exec(' echo "http://raven.anr.udel.edu/" | mail -s "$(echo "Test Email\nFrom: fRNAkbox <wtreible@raven.anr.udel.edu> Reply-to: wtreible@raven.anr.udel.edu\n")" ' . $_SESSION['user_email']);
		header('Location: index.php');
        } else {
            // start the session, always needed!
            $this->doStartSession();
            // check for possible user interactions (login with session/post data or logout)
            $this->performUserLoginAction();
            // show "page", according to user's login status
            if ($this->getUserLoginStatus()) {
                $this->showPageLoggedIn();
            } else {
                $this->showPageLoginForm();
            }
        }
    }

    /**
     * Creates a PDO database connection (in this case to a SQLite flat-file database)
     * @return bool Database creation success status, false by default
     */
    private function createDatabaseConnection()
    {
        try {
            $this->db_connection = new PDO($this->db_type . ':' . $this->db_sqlite_path);
            return true;
        } catch (PDOException $e) {
            $this->feedback = "PDO database connection problem: " . $e->getMessage();
        } catch (Exception $e) {
            $this->feedback = "General problem: " . $e->getMessage();
        }
        return false;
    }

    /**
     * Handles the flow of the login/logout process. According to the circumstances, a logout, a login with session
     * data or a login with post data will be performed
     */
    private function performUserLoginAction()
    {
        if (isset($_GET["action"]) && $_GET["action"] == "logout") {
            $this->doLogout();
        } elseif (!empty($_SESSION['user_name']) && ($_SESSION['user_is_logged_in'])) {
            $this->doLoginWithSessionData();
        } elseif (isset($_POST["login"])) {
            $this->doLoginWithPostData();
        }
    }

    /**
     * Simply starts the session.
     * It's cleaner to put this into a method than writing it directly into runApplication()
     */
    private function doStartSession()
    {
        session_start();
    }

    /**
     * Set a marker (NOTE: is this method necessary ?)
	     */
    private function doLoginWithSessionData()
    {
        $this->user_is_logged_in = true; // ?
    }

    /**
     * Process flow of login with POST data
     */
    private function doLoginWithPostData()
    {
        if ($this->checkLoginFormDataNotEmpty()) {
            if ($this->createDatabaseConnection()) {
                $this->checkPasswordCorrectnessAndLogin();
            }
        }
    }

    /**
     * Logs the user out
     */
    private function doLogout()
    {
        $_SESSION = array();
        session_destroy();
        $this->user_is_logged_in = false;
        $this->feedback = "You were just logged out.";
    }

    /**
     * The registration flow
     * @return bool
     */
    private function doRegistration()
    {
        if ($this->checkRegistrationData()) {
            if ($this->createDatabaseConnection()) {
                $this->createNewUser();
            }
        }
        // default return
        return false;
    }

    /**
     * Validates the login form data, checks if username and password are provided
     * @return bool Login form data check success state
     */
    private function checkLoginFormDataNotEmpty()
    {
        if (!empty($_POST['user_name']) && !empty($_POST['user_password'])) {
            return true;
        } elseif (empty($_POST['user_name'])) {
            $this->feedback = "Username field was empty.";
        } elseif (empty($_POST['user_password'])) {
            $this->feedback = "Password field was empty.";
        }
        // default return
        return false;
    }

    /**
     * Checks if user exits, if so: check if provided password matches the one in the database
     * @return bool User login success status
     */
    private function checkPasswordCorrectnessAndLogin()
    {
        // remember: the user can log in with username or email address
        $sql = 'SELECT user_name, user_email, user_password_hash
                FROM users
                WHERE user_name = :user_name OR user_email = :user_name
                LIMIT 1';
        $query = $this->db_connection->prepare($sql);
        $query->bindValue(':user_name', $_POST['user_name']);
        $query->execute();

        // Btw that's the weird way to get num_rows in PDO with SQLite:
        // if (count($query->fetchAll(PDO::FETCH_NUM)) == 1) {
        // Holy! But that's how it is. $result->numRows() works with SQLite pure, but not with SQLite PDO.
        // This is so crappy, but that's how PDO works.
        // As there is no numRows() in SQLite/PDO (!!) we have to do it this way:
        // If you meet the inventor of PDO, punch him. Seriously.
        $result_row = $query->fetchObject();
        if ($result_row) {
            // using PHP 5.5's password_verify() function to check password
            if (password_verify($_POST['user_password'], $result_row->user_password_hash)) {
                // write user data into PHP SESSION [a file on your server]
                $_SESSION['user_name'] = $result_row->user_name;
                $_SESSION['user_email'] = $result_row->user_email;
                $_SESSION['user_is_logged_in'] = true;
                $this->user_is_logged_in = true;
                return true;
            } else {
                $this->feedback = "Wrong password.";
            }
        } else {
            $this->feedback = "This user does not exist.";
        }
        // default return
        return false;
    }

    /**
     * Validates the user's registration input
     * @return bool Success status of user's registration data validation
     */
    private function checkRegistrationData()
    {
        // if no registration form submitted: exit the method
        if (!isset($_POST["register"])) {
            return false;
        }

        // validating the input
        if (!empty($_POST['user_name'])
            && strlen($_POST['user_name']) <= 64
            && strlen($_POST['user_name']) >= 2
            && preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])
            && !empty($_POST['user_email'])
            && strlen($_POST['user_email']) <= 64
            && filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)
            && !empty($_POST['user_password_new'])
            && !empty($_POST['user_password_repeat'])
            && ($_POST['user_password_new'] === $_POST['user_password_repeat'])
        ) {
            // only this case return true, only this case is valid
            return true;
        } elseif (empty($_POST['user_name'])) {
            $this->feedback = "Empty Username";
        } elseif (empty($_POST['user_password_new']) || empty($_POST['user_password_repeat'])) {
            $this->feedback = "Empty Password";
        } elseif ($_POST['user_password_new'] !== $_POST['user_password_repeat']) {
            $this->feedback = "Password and password repeat are not the same";
        } elseif (strlen($_POST['user_password_new']) < 6) {
            $this->feedback = "Password has a minimum length of 6 characters";
        } elseif (strlen($_POST['user_name']) > 64 || strlen($_POST['user_name']) < 2) {
            $this->feedback = "Username cannot be shorter than 2 or longer than 64 characters";
        } elseif (!preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])) {
            $this->feedback = "Username does not fit the name scheme: only a-Z and numbers are allowed, 2 to 64 characters";
        } elseif (empty($_POST['user_email'])) {
            $this->feedback = "Email cannot be empty";
        } elseif (strlen($_POST['user_email']) > 64) {
            $this->feedback = "Email cannot be longer than 64 characters";
        } elseif (!filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)) {
            $this->feedback = "Your email address is not in a valid email format";
        } else {
            $this->feedback = "An unknown error occurred.";
        }

        // default return
        return false;
    }

    /**
     * Creates a new user.
     * @return bool Success status of user registration
     */
    private function createNewUser()
    {
        // remove html code etc. from username and email
        $user_name = htmlentities($_POST['user_name'], ENT_QUOTES);
        $user_email = htmlentities($_POST['user_email'], ENT_QUOTES);
        $user_password = $_POST['user_password_new'];
        // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 char hash string.
        // the constant PASSWORD_DEFAULT comes from PHP 5.5 or the password_compatibility_library
        $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT);

        $sql = 'SELECT * FROM users WHERE user_name = :user_name OR user_email = :user_email';
        $query = $this->db_connection->prepare($sql);
        $query->bindValue(':user_name', $user_name);
        $query->bindValue(':user_email', $user_email);
        $query->execute();

        // As there is no numRows() in SQLite/PDO (!!) we have to do it this way:
        // If you meet the inventor of PDO, punch him. Seriously.
        $result_row = $query->fetchObject();
        if ($result_row) {
            $this->feedback = "Sorry, that username / email is already taken. Please choose another one.";
        } else {
            $sql = 'INSERT INTO users (user_name, user_password_hash, user_email)
                    VALUES(:user_name, :user_password_hash, :user_email)';
            $query = $this->db_connection->prepare($sql);
            $query->bindValue(':user_name', $user_name);
            $query->bindValue(':user_password_hash', $user_password_hash);
            $query->bindValue(':user_email', $user_email);
            // PDO's execute() gives back TRUE when successful, FALSE when not
            // @link http://stackoverflow.com/q/1661863/1114320
            $registration_success_state = $query->execute();

            if ($registration_success_state) {
                $this->feedback = "The account has been created successfully. That user can now log in.";
                return true;
            } else {
                $this->feedback = "Sorry, the registration failed. Please go back and try again.";
            }
        }
        // default return
        return false;
    }

    /**
     * Simply returns the current status of the user's login
     * @return bool User's login status
     */
    public function getUserLoginStatus()
    {
        return $this->user_is_logged_in;
    }

    /**
     * Simple demo-"page" that will be shown when the user is logged in.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageLoggedIn()
    {
        
	if ($this->feedback) {
            echo $this->feedback . "<br/><br/>";
        }

	if($_SESSION['user_name'] == $this->admin || $_SESSION['user_name'] == 'toast')
	{
        	echo 'Hello admin <b>' . $_SESSION['user_name'] . '</b>, you are logged into the simple control panel!<br/>';
		echo 'You may return to this page by navigating to website/index.php at any time.<br><br>';
        	echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=register">Register new account</a><br><br>';
		echo '<a href="frnakenstein/log_view.php">View logs</a><br><br>';
		$fRNAkrunning = exec("ps aux | grep '[f]RNAk-daemon' ");
		if ( empty($fRNAkrunning) )
		{
			echo "fRNAkDaemon is <b>off</b>";
			
		}
		else
		{
			echo "fRNAkdaemon is <b>on</b><br>";
			echo "fRNAkdaemon process: ".$fRNAkrunning;
		}
		echo '<br><a href="' . $_SERVER['SCRIPT_NAME'] . '?action=daemontoggle">Toggle fRNAkdaemon on/off (this will kill any active processes!)</a><br><br>';
		
		echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=testemail">Send Test Email to '.$_SESSION['user_email'].'</a></br></br>';

		echo '<a href="frnakenstein/status.php">View Status </a> <br>(no return to ctrl panel except browser back button)<br><br>';
		echo '<a href="frnakenstein/menu.php">To fRNAkenstein </a> <br>(no return to ctrl panel except browser back button)<br><br>';
		echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=logout">Log out</a>';
		
	}
	else {
		header('Location: frnakenstein/menu.php');
	}
    }

    /**
     * Simple demo-"page" with the login form.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageLoginForm()
    {
        
	echo "<head>";
	echo "<title>";
	echo "fRNAkenstein:\"Beware; for I am fearless, and therefore powerful.\"";
	echo "</title>";
	echo "<link rel=\"icon\" type=\"image/ico\" href=\"images/favicon.ico\"/>";
	echo "</head>";
	echo "<body >";
	echo "<center>";
	###########################
	# Formatting Box & Legend #
	###########################
	echo "<style type=\"text/css\"> 
		.fieldset-auto-width {
		 display: inline-block;
	    }";
	echo "</style>";
	echo "<br><div>
	<fieldset class=\"fieldset-auto-width\">
	<!legend>
	<h3 style=\"background-color:white;\">
	<!img src=\"/favicon.png\" alt=\"fRNAk\" width=\"24\" height=\"24\"> 
	<!--Welcome to fRNAkenstein!-->
	<!img src=\"/favicon.png\" alt=\"fRNAk\" width=\"24\" height=\"24\">
	</h3>
	</legend>";

        echo '<h2>fRNAkenstein Portal Login</h2>';
	echo '';
        echo '<tr><td><form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '" name="loginform">';
        echo '<label for="login_input_username">Username (or email)</label></td> ';
        echo '<td><input id="login_input_username" type="text" name="user_name" required /> </td></tr>';
        echo '<tr><td><label for="login_input_password">Password</label> </td>';
        echo '<td><input id="login_input_password" type="password" name="user_password" required /> </td></tr> ';
	if ($this->feedback) {
            echo "<br/>".$this->feedback."<br/><br/>" ;
        }
        echo '<input type="submit"  name="login" value="Log in" />';
        echo '</form>';
	echo '<br> <br>To obtain a fRNAkenstein account, <br>email wtreible@udel.edu with subject line "fRNAk acct"<br><br>';
	echo '
	<br><br>
	<img src="images/chicken.jpg" alt="SchmidtLab" width="160" height="125" > </td>
	<img src="images/USDA.jpg" alt="USDA" width="266" height="125"> 
	<img src="images/NSF.jpg" alt="NSF" width="125" height="125"> <br>
	<p align="center" ><font size="1">- NSF award: 1147029 :: USDA-NIFA-AFRI: 2011-67003-30228 - </font></p></fieldset>';

	#echo '<form action="contact.html">';
	#echo '<input type="submit" name ="about" value="About fRNAkenstein"></form>';
        #echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=register">Register new account</a>';
    }

    /**
     * Simple demo-"page" with the registration form.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageRegistration()
    {
        if ($this->feedback) {
            echo $this->feedback . "<br/><br/>";
        }

        echo '<h2>Registration</h2>';

        echo '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '?action=register" name="registerform">';
        echo '<label for="login_input_username">Username (only letters and numbers, 2 to 64 characters)</label><br>';
        echo '<input id="login_input_username" type="text" pattern="[a-zA-Z0-9]{2,64}" name="user_name" required /><br>';
        echo '<label for="login_input_email">User\'s email</label><br>';
        echo '<input id="login_input_email" type="email" name="user_email" required /><br>';
        echo '<label for="login_input_password_new">Password (min. 6 characters)</label><br>';
        echo '<input id="login_input_password_new" class="login_input" type="password" name="user_password_new" pattern=".{6,}" required autocomplete="off" /><br>';
        echo '<label for="login_input_password_repeat">Repeat password</label><br>';
        echo '<input id="login_input_password_repeat" class="login_input" type="password" name="user_password_repeat" pattern=".{6,}" required autocomplete="off" /><br>';
        echo '<input type="submit" name="register" value="Register" />';
        echo '</form>';

        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '">Return to Control Panel</a>';
    }
}

// run the application
$application = new OneFileLoginApplication();
