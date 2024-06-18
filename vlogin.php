<?php
global $con;
// Include config file
require_once "config.php";

// Initialize the session
session_set_cookie_params(0, '/', COOKIE_DOMAIN);
session_start();

// Check if the user is already logged in, if yes then redirect him to welcome page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if (isset($_SESSION["admin"]) && $_SESSION["admin"] === true) {
        header("location: vadmin.php");
        exit;
    }
    header("location: vregistration.php");
    exit;
}

$users_table = 'users';
$admin_users_table = 'admin_users';

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";

function loginUser($table, $redirect_page, $callback) {
    global $con, $username, $password, $username_err, $password_err;
    // Prepare a select statement
    $sql = "SELECT id, username, password FROM " . $table . " WHERE username = ?";

    if ($stmt = mysqli_prepare($con, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $param_username);

        // Set parameters
        $param_username = $username;

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            // Store result
            mysqli_stmt_store_result($stmt);

            // Check if username exists, if yes then verify password
            if (mysqli_stmt_num_rows($stmt) == 1) {
                // Bind result variables
                mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password)) {
                        // Password is correct, so start a new session
                        session_destroy();
                        session_start();
                        
                        $callback($id, $username);

                        // Redirect user to welcome page
                        header("location: " .  $redirect_page);
                    } else {
                        // Display an error message if password is not valid
                        $password_err = "The password you entered was not valid.";
                    }
                }
            } else {
                // Display an error message if username doesn't exist
                $username_err = "No account found with that username.";
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }
}

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        loginUser($users_table, 'vregistration.php', function($id, $username){
            // Store data in session variables
            $_SESSION["loggedin"] = true;
            $_SESSION["user"] = true;
            $_SESSION["id"] = $id;
            $_SESSION["username"] = $username;
        });
        loginUser($admin_users_table, 'vadmin.php', function($id, $username){
            // Store data in session variables
            $_SESSION["loggedin"] = true;
            $_SESSION["admin"] = true;
            $_SESSION["id"] = $id;
            $_SESSION["username"] = $username;
        });
    }

    // Close connection
    mysqli_close($con);
}
?>
<!DOCTYPE html>
<html lang="en" dir="rtl">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">

    <title>نظام التصويت الإلكتروني</title>

    <!-- Main Styles -->
    <link rel="stylesheet" href="assets/styles/style-horizontal.min.css">

    <!-- Material Design Icon -->
    <link rel="stylesheet" href="assets/fonts/material-design/css/materialdesignicons.css">

    <!-- mCustomScrollbar -->
    <link rel="stylesheet" href="assets/plugin/mCustomScrollbar/jquery.mCustomScrollbar.min.css">

    <!-- Waves Effect -->
    <link rel="stylesheet" href="assets/plugin/waves/waves.min.css">

    <!-- Sweet Alert -->
    <link rel="stylesheet" href="assets/plugin/sweet-alert/sweetalert.css">

    <!-- RTL -->
    <link rel="stylesheet" href="assets/styles/style-rtl.min.css">

</head>

<body>
    <header class="fixed-header">
        <div class="header-top" style="height: 20px;">
            <div class="container">
                <div class="pull-right">
                    <a class="logo"></a>
                </div>
                <!-- /.pull-right -->

            </div>
            <!-- /.container -->
        </div>
    </header>
    <!-- /.fixed-header -->



    <div id="wrapper">
        <div class="main-content container">

            <div class="container">

                <center style="margin-top: -20px;">
                    <img src="logo.png" alt="logo" width="180" />
                    <h3>تسجيل الدخول</h3>
                    <hr />

                </center>

                <div class="row">
                    <div class="form-box">
                        <h4>يرجى تسجيل الدخول لإستخدام النظام</h4>
                        <br />
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                                <label>إسم المستخدم</label>
                                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                                <span class="help-block"><?php echo $username_err; ?></span>
                            </div>
                            <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                                <label>الرقم السري</label>
                                <input type="password" name="password" class="form-control">
                                <span class="help-block"><?php echo $password_err; ?></span>
                            </div>
                            <div class="form-group">
                                <input type="submit" class="btn btn-success" value="تسجيل الدخول">
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
        <!-- /.main-content -->
    </div>
    <!--/#wrapper -->
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
		<script src="assets/script/html5shiv.min.js"></script>
		<script src="assets/script/respond.min.js"></script>
	<![endif]-->
    <!--
	================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="assets/scripts/jquery.min.js"></script>
    <script src="assets/scripts/modernizr.min.js"></script>
    <script src="assets/plugin/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/plugin/mCustomScrollbar/jquery.mCustomScrollbar.concat.min.js"></script>
    <script src="assets/plugin/nprogress/nprogress.js"></script>
    <script src="assets/plugin/sweet-alert/sweetalert.min.js"></script>
    <script src="assets/plugin/waves/waves.min.js"></script>
    <script src="assets/scripts/main.min.js"></script>
</body>

</html>