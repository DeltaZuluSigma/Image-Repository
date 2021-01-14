<html>
    <head><title>Debug Mode</title></head>
    <?php
		// Start Session
		session_start();
		// MySQL User Login & Connection
        require_once 'login.php';
        $conn = new mysqli($hn, $un, $pw, $db);
        if (mysqli_connect_errno()) { echo "Failed to connect to MySQL: " . mysqli_connect_error(); }
        
		// Operation POST Request Check
        if (isset($_POST['operation']) && !empty($_POST['operation'])) {
			$opt = $_POST['operation'];
            
			// Parts POST Request Check
			if (isset($_POST['part']) && !empty($_POST['part'])) {
				$param = $_POST['part'];
				
				// Operation Action Switch Statement
				switch ($_POST['operation']) {
					case "login": login($param, $conn); break;
					case "display":
						if ($_SESSION['user']) { display(explode(" ", $param)[0], $conn); }
                        else { echo "Please Login to Perform This Action."; }
						break;
					case "view":
						if ($_SESSION['user']) { view(explode(" ", $param)[0], $conn); }
                        else { echo "Please Login to Perform This Action."; }
						break;
					case "del":
						if ($_SESSION['user']) { del(explode(" ", $param)[0], $conn); }
                        else { echo "Please Login to Perform This Action."; }
						break;
					default: echo "Invalid Command (POST)."; break;
				}
			}
			// Logout No Parts POST Request Check
			else if (($opt == "logout") && $_SESSION['user']) {
				// Temporarily Copy User Session Variable
				$temp = $_SESSION['user'];
				// Clear Session Data
				session_unset();
				session_destroy();
				
				echo "" . $temp . " Has Logged Out.";
			}
			else { echo "Invalid Options."; }
		}
		// Add Operation GET Request Check
		else if (isset($_GET['op']) && !empty($_GET['op']) && $_SESSION['user']) {
			// Received File Check
			if ($_FILES) {
				// Check/Create User Folder
				checkUFolder();
				
                if (isset($_GET['name']) && !empty($_GET['name'])) {
                    // Verify File Type
                    switch($_FILES['file']['type']) {
                        case 'image/jpeg': $ext = 'jpg'; break;
                        case 'image/gif':  $ext = 'gif'; break;
                        case 'image/png':  $ext = 'png'; break;
                        case 'image/tiff': $ext = 'tif'; break;
                        default: $ext = ''; break;
                    }
                    if ($ext) {
                        // Appropriately Upload File
                        $fn = "" . $_GET['name'] . "_" . $_SESSION['user'] . "." . $ext;
                        if (imageData($fn, $conn)) {
                            move_uploaded_file($_FILES['file']['tmp_name'], "../reporoot/" . $_SESSION['user'] . "/" . $fn);
                            echo "" . $fn . " Was Uploaded.";
                        }
                    }
                    else { echo "Invalid File Type."; }
                }
                else { echo "No File Name."; }
			}
			else {
				echo "No File Provided.";
			}
		}
        else if (isset($_GET['op']) && !empty($_GET['op']) && !$_SESSION['user']) {
        	echo "Please Login to Perform This Action.";
        }
		else {
			echo "Invalid Command (PHP).";
		}
		
		/*******FUNCTIONS*******/
		
		/***** login Function
		   - Checks for existing users to "log in" with associate password
		   - Creates user with associate password, otherwise
		*/
		function login($credits, $conn) {
			// Extract U/P Pair
			$pair = explode(" ", $credits);
            
			// Database Search Query
			$stmt = $conn->prepare('SELECT password FROM users WHERE username = ?');
        	$stmt->bind_param('s', $user);
            // Code Stripped Variables
        	$user = get_full_strip($conn, $pair[0]);
        	$pwd = get_full_strip($conn, $pair[1]);
            // Search Query Store
        	$stmt->execute();
        	$stmt->store_result();
            
			// Existing User Check
			if ($stmt->num_rows) {
                $stmt->bind_result($prop);
            	$stmt->fetch();
                
				if ($prop == $pwd) { $_SESSION['user'] = $user; echo "". $user ." Logged In."; }
				else { echo "Incorrent Password."; }
			}
			// New User Case
			else if (!empty($user) && !empty($pwd)) {
				// Database Insertion Query
				$stmt = $conn->prepare("INSERT INTO users VALUES(NULL,?,?)");
				$stmt->bind_param("ss",$user, $pwd);
				
				// Query Success Check
				if (!($state = $stmt->execute())) { echo "Query Error (Login)."; }
				else if ($state) { $_SESSION['user'] = $user; echo "" . $user . " User Created & Logged In."; }
			}
            else {
            	echo "A Field Was Not Provided.";
            }
		}
		
		/***** display Function
		   - Displays names of images of that permission
		   > public - All public images
		   > private - All user private images
		   > * - All public and user private images
		*/
		function display($perm, $conn) {
        	// Request Response Holder
			$txt = "";
			
			// Displaying Options
			switch ($perm) {
				case "*":					// All Images Displayed
				case "public":				// Public Images Displayed
					// Database Search Query
					$query  = "SELECT name,uid,permission FROM images WHERE permission = 'public'";
                    $result = $conn->query($query);
                    if (!$result) die ("Database access failed: " . $conn->error);
                    $rows = $result->num_rows;
					
                    $txt .= "public ";
                    
					// Database Data Seeking Loop
					for ($i = 0; $i < $rows; $i++) {
						$result->data_seek($j);
						$row = $result->fetch_array(MYSQLI_NUM);
						
                        $txt .= $row[0] . " ";
					}
					
					// Public Specific Breaker
					if ($perm == "public") {
                    	echo $txt;
                        break;
                    }
				case "private":				// Private Images Displayed
					// Database Search Query
                    $query  = "SELECT name,username,permission FROM images,users WHERE images.uid = users.uid AND username = '" . $_SESSION['user'] . "' AND permission = 'private'";
                    $result = $conn->query($query);
                    if (!$result) die ("Database access failed: " . $conn->error);
                    $rows = $result->num_rows;
					
                    $txt .= "private ";
                    
					// Database Data Seeking Loop
					for ($i = 0; $i < $rows; $i++) {
						$result->data_seek($j);
						$row = $result->fetch_array(MYSQLI_NUM);
						
						$txt .= $row[0] . " ";
					}
					
					echo $txt;
					break;
				default: echo "Invalid Display Option.";
			}
		}
		
		/***** view Function
		   - Opens the requested image given ownership/permission
		*/
		function view($img, $conn) {
			// Database Search Query
            $stmt = $conn->prepare('SELECT name FROM images,users WHERE images.uid = users.uid AND name = ? AND username = ? OR permission = "public"');
			$stmt->bind_param('ss', $img, $_SESSION['user']);
			$stmt->execute();
			$stmt->store_result();
			
			// Database Search Result
			if ($stmt->num_rows) {
            	$stmt->bind_result($nimg);
            	$stmt->fetch();
                
            	$path = "../reporoot/" . $_SESSION['user'] . "/" . $nimg;
            	list($width, $height) = getimagesize($path);
                
            	echo "s " . $path . " " . $height . " " . $width;
            }
			else { echo "No Such Image."; }
		}
		
		/***** del Function
		   - Deletes the requested image given ownership
		*/
		function del($img, $conn) {
			// Database Deletion Query
			$stmt = $conn->prepare("DELETE FROM images WHERE name = ?");
			$stmt->bind_param("s", $img);
			
			// Query Success Check
			if ($stmt->execute()) {
				if(unlink("../reporoot/" . $_SESSION['user'] . "/" . $img)) { echo "" . $img . " Was Deleted."; }
				else { echo "Unlinking Error."; }
			}
			else { echo "Query Error (Delete)."; }
		}
		
		/***** checkUFolder Function
		   - Checks for the user's folder if it exists
		   > If it does, allows the file to be created in the folder
		   > If it doesn't, creates a folder for the file to go in
		*/
		function checkUFolder() {
			// Initialize Variables
			$ufolders = scandir('../reporoot');
			$exists = false;
			
			// Scan Directors Loop & Condition
			foreach ($ufolders as $value) {
				if ($value == $_SESSION['user']) { $exists = true; break; }
			}
			if (!$exists) { mkdir("../reporoot/" . $_SESSION['user']); }
		}
		
		/***** imageData Function
		   - Logs the image permission in the database
		*/
		function imageData($fn, $conn) {
			// Permission GET Request Check
			if (isset($_GET['perm']) && !empty($_GET['perm'])) {
				$perm = $_GET['perm'];								// Initialize Perm
				
				// Appropriate Options Check
				if (($perm == "public") || ($perm == "private")) {
					// Database Search Query
					$stmt = $conn->prepare('SELECT uid FROM users WHERE username = ?');
					$stmt->bind_param('s', $_SESSION['user']);
					$stmt->execute();
					$stmt->store_result();
                    
					// Database Search Result Check
					if ($stmt->num_rows) {
						$stmt->bind_result($uid);
            			$stmt->fetch();
						
						// Database Insertion Query
						$stmt = $conn->prepare("INSERT INTO images VALUES(NULL,?,?,?)");
						$stmt->bind_param("sis",$fn, $uid, $perm);
						
						// Query Success Check
						if (!$stmt->execute()) { echo "Query Error (Login)."; return false; }
                        else { return true; }
					}
					else { echo "Invalid User (id_user_search)."; }
				}
				else { echo "Invalid Image Permission Option."; }
			}
			else { echo "Missing Image Permission."; }
		}
		
		/***** get_full_strip Function
		   - Fully santizes inputs
		*/
        function get_full_strip($conn, $var) {
            $var = stripslashes($var);
            $var = strip_tags($var);
            $var = $conn->real_escape_string($var);
            return htmlentities($var);
        }
    ?>
</html>