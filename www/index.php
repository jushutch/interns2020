<style>
<?php include 'style.css';?>
</style>
<body>
    <div class="page-wrap">
        <div class="header">Login </div>
        <form method="get" class="form" id="loginForm" action="/index.php">
            <label for="uname">Username: </label>
            <input type="text" id = "uname" name="uname"><br><br>
            <label for="password">Password: </label>
            <input type="text" id = "password" name="password"><br><br>
            <input type="submit" name="click" value = "Login">
        </form>
        <a href = "account.php"> Create New Account</a>
        <?php
        function login() {
            echo "in";
        $username = $_GET["uname"];
        $password = $_GET["password"];

        $servername = "localhost";
        $usernameServer = "root";
        $passwordServer = "#Awesome1AZ";

        // Create connection
        $conn = new mysqli($servername, $usernameServer, $passwordServer);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT username FROM users WHERE username = $username";
        $result = $conn->query($sql);

        if ($result->num_rows <= 0) {
            echo "Account not found";
        } else {
            $sql = "SELECT password FROM users WHERE password = $password";
            $result = $conn->query($sql);
            while($row = mysqli_fetch_assoc($result)) {
                if(password_verify($password, $row["password"])) {
                    $sql = "INSERT INTO onlineUsers (username) VALUES ($username)";

                    if ($conn->query($sql) === TRUE) {
                        $_SESSION['loggedin'] = True;
                        $_SESSION['login_user'] = $username;


                    } else {
                        echo "Error: " . $sql . "<br>" . $conn->error;
                    }
                }
                else {
                    echo "Wrong Password";
                }
            }

        }
        $conn->close();
        }
        if (isset($_POST['click'])){
            login();
        }
        ?>


    </div>
    <script type="text/javascript" src="deck.js"></script>
    <script type="text/javascript" src="player.js"></script>
</body>
