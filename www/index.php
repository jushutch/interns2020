<?php session_start();
include 'helper.php';?>
<style>
<?php include 'style.css';?>
</style>
<body class="body">

    <div class="page-wrap">
        <div class="header">Welcome to LAZ Blackjack!</div>
        <h1>Log in to play!</h1>
        <p></p>
        <form method="get" class="form" id="loginForm" action="/index.php">
            <label for="uname">Username: </label>
            <input type="text" id = "uname" name="uname" required><br><br>
            <label for="password">Password: </label>
            <input type="password" id = "password" name="password" required><br><br>
            <input type="submit" name="click" value = "Login">
        </form>
        <p>Don't have an account? <a href = "account.php">Sign up</a></p>

        <?php
        if ($_SESSION['loggedin']) {

        }
        function login() {
            //Get Username and password
            $username = $_GET["uname"];
            $password = $_GET["password"];
            //Sanitize
            $username = stripcslashes($username);
            $password = stripcslashes($password);

            $conn = makeConnection();

            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT username FROM internDatabase.users WHERE username = '$username'";
        $result = $conn->query($sql);

        //Checking to see if the account is found using DB
        if ($result->num_rows <= 0) {
            echo "Incorrect username or password";
            return;
        } else { //Matching password to username
            $sql = "SELECT password FROM internDatabase.users WHERE username = '$username'";
            $result = $conn->query($sql);
            while($row = mysqli_fetch_assoc($result)) {
                if(password_verify($password, $row["password"])) { //Password verify function
                    //Updates server to add online User if not already online
                    $sql = "SELECT username FROM internDatabase.onlineUsers WHERE username = '$username'";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {

                    }
                    else {
                        $sql = "INSERT INTO internDatabase.onlineUsers (username) VALUES ('$username')";
                        $conn->query($sql);
                    }
                    $_SESSION['loggedin'] = True;
                    $_SESSION['login_user'] = $username; //Updates session for logged in user
                    echo "<script> document.location.href='/lobby.php'</script>";

                }
                else { //Non-matching password
                    echo "Incorrect username or password";
                    return;
                }
            }

        }
        $conn->close();
        }
        if (isset($_GET['click'])){
            login();
        }
    ?>

    </div>
<script type="text/javascript" src="deck.js"></script>
<script type="text/javascript" src="player.js"></script>
</body>
