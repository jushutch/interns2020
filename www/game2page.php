<?php session_start(); ?>
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <meta http-equiv="refresh" content="5">
</head>
<style>
    <?php include 'style.css';?>
</style>
<body class="game">
<div class="page-wrap">
<!--    <form method="post">-->
<!--        <input id="addDeck" type="submit" name="addDeck" value = "Add Deck">-->
<!--    </form>-->

    <div class="buttons-box">
        <!-- Main player -->
        <div class="player">
            <div class="card-box">
                <?php
                include "helper.php";
                include "deck.php";
                include "player.php";

                getSessionHandID();
                getSessionDeckID();

                // Keep track of player info within session
                if (!isset($_SESSION['sessionPlayer'])) {
                    // $player = new Player("name");
                    $username = $_SESSION['login_user'];
                    $player = new Player ($username);
                    $_SESSION['sessionPlayer'] = serialize($player);
                }

                // POST request behavior here so session/db would get update before interfacing
                if (isset($_POST['reset'])) {
                    resetGame();
                }

                if (isset($_POST['hit'])) {
                    hit();
                }

                // Prints hand
                $player = unserialize($_SESSION['sessionPlayer']);
                printHand($player);

                // if (!$player->isTurn() or $player->isBust()) {
                if ($player->isBust()) {
                    echo "Bust you're out";
                    echo "<script type='text/javascript'>
                  $(document).ready(function()
                  {
                    $('#hitBtn').prop('disabled', true);
                    $('#stayBtn').prop('disabled', true);
                  });
                </script>";
                }
                ?>
            </div>

            <?php
                $username =  $_SESSION['login_user'];
                echo "<div class='card'>$username</div>";
            ?>
            
        </div>

        <!-- Other players -->
        <?php
        // Get all other players in room 1
        $conn = makeConnection();
        $currPlayerHandID = $_SESSION["sessionHandID"];
        $sql = "SELECT * FROM online_user WHERE gameID = 1 AND handID <> '$currPlayerHandID'";
        $result = $conn->query($sql);

        // Divs for each player
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<div class='player'><div class='card-box'>";
                // Get the player's hand
                $handID = $row["handID"];
                $sql = "SELECT * from card_hand WHERE handID = '$handID'";
                $cards_query = $conn->query($sql);

                if ($cards_query->num_rows > 0) {
                    while($card_row = $cards_query->fetch_assoc()) {
                        $cardValMap = deckArray();
                        $card = $cardValMap[$card_row['cardID']];
                        $cardValue = $card['Value'];
                        echo "<div class='card'>$cardValue</div>";
                    }
                }
                $username = $row["username"];
                echo "</div><div class='card'>$username</div></div>";
            }
        }
//        for ($i = 0; $i < $numPlayers - 1; $i++) {
//            $playerIdx = $i + 2;
//
//            // Get the player's hand
//            $sql = "SELECT cardsHand.* FROM onlineUsers
//                    INNER JOIN cardsHand on onlineUsers.handID = cardsHand.handID
//                    WHERE onlineUsers.gameID = 1";
//            $result = $conn->query($sql);
//
//            echo "
//            <div class='player'>
//                <div class='card-box'></div>
//                <div class='card'>Player $playerIdx</div>
//            </div>
//            ";
//        }

        $conn->close();
        ?>
    </div>
    <div class="buttons-box">
        <div class="card-box">
            <form method="post">
                <input id="hitBtn" type="submit" name="hit" value="Hit">
                <input id="stayBtn" type="submit" name="stay" value="Stay">
                <input type="submit" name="reset" value="Reset">
            </form>
        </div>
    </div>
</div>

<?php
    if (isset($_POST['addDeck'])) {
        addDeck();
    }

    function resetGame() {
        // Clear database
        $conn = makeConnection();
        $sql = "DELETE FROM card_hand";
        $conn->query($sql);
        $sql = "DELETE FROM deck_hand"; //typo?
        $conn->query($sql);
        $sql = "DELETE FROM hand";
        $conn->query($sql);
        $sql = "DELETE FROM deck";
        $conn->query($sql);
        $sql = "DELETE FROM game";
        $conn->query($sql);
        $sql = "INSERT INTO game (gameID, deckID, discardID, playerTurn, numPlayers) VALUES (1, NULL, NULL, NULL, 0)";
        $conn->query($sql);
        $conn->close();

        // Clear player hand in current session
        $player = unserialize($_SESSION['sessionPlayer']);
        $player->emptyHand();
        $_SESSION['sessionPlayer'] = serialize($player);
        echo "reset";
    }

    function getSessionHandID() {
        // Get the player hands id
        if (!isset($_SESSION['sessionHandID'])) {
            $conn = makeConnection();
            $sql = "SELECT * FROM hand ORDER BY handID DESC LIMIT 1";
            $result = $conn->query($sql);
            $_SESSION['sessionHandID'] = 0;
            $handID = 0;

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $_SESSION['sessionHandID'] = $row["handID"] + 1;
                $handID = $_SESSION['sessionHandID'];
            }

            // insert into hand db
            $sql = "INSERT INTO hand (handID) VALUES ('$handID')";
            $conn->query($sql);

            // Insert into onlineUsers table
            // TODO: name below is just for testing currently. Replace name with appropriate username
            // $names = ['', 'name'];
            // $name = $names[$handID];
            $username = $_SESSION['login_user'];
            $sql = "UPDATE online_user SET gameID = 1 WHERE username = '$username'";
            $conn->query($sql);
            $sql = "UPDATE online_user SET handID = '$handID' WHERE username = '$username'";
            $conn->query($sql);
            $sql = "UPDATE online_user SET money = 100 WHERE username = '$username'";
            $conn->query($sql);

            // Update number of player in game
            $sql = "SELECT * FROM game WHERE gameID = 1";
            $result = $conn->query($sql);
            $result = $result->fetch_assoc();
            $newNumPlayers = $result["numPlayers"] + 1;
            $sql = "UPDATE game SET numPlayers = '$newNumPlayers' WHERE gameID = 1";
            $conn->query($sql);

            $conn->close();
        }
    }

    function hit() {
        // Draw card
        $newCardID = getTopCardDB();

        // Add card to hand db
        $conn = makeConnection();
        $handID = $_SESSION['sessionHandID'];
        $sql = "INSERT INTO card_hand (handID, cardID) VALUES ('$handID', '$newCardID')";
        $conn->query($sql);
        $conn->close();

        // Insert card into hand of current player session
        // Update if player bust
        $cardValMap = deckArray();
        $card = $cardValMap[$newCardID];
        $player = unserialize($_SESSION["sessionPlayer"]);
        $player->addCard($card);
        $player->checkBust();
        $_SESSION["sessionPlayer"] = serialize($player);

    //    $cardValue = $card["Value"];
    //    echo "<div class='card'>$cardValue</div>";
        //  "<div class='card'>'$cardValue'</div>";
    }

    function printHand($player) {
        $hand = $player->getHand();
        for ($i = 0; $i < count($hand); $i++) {
            $cardValue = $hand[$i]['Value'];
            echo "<div class='card'>$cardValue</div>";
        }
        $score = $player->calcHand();
        echo "<div>Score: $score</div>";
    }


    function addDeck() {
        $deck = new Deck();
        $deck->newFillDeck();
        $deck->pushToDb();
        $deckID = $deck->getDeckID();
        $_SESSION['sessionDeckID'] = $deckID;

        // Add deckID to games
        $conn = makeConnection();
        $sql = "UPDATE game SET deckID = '$deckID' WHERE gameID = 1";
        $conn->query($sql);

    //    if (!isset($_SESSION['sessionDeckID'])) {
    //        $_SESSION['sessionDeckID'] = $deck->getDeckID();
    //    }

        echo "new deck added";
    }

    // Updates sessionDeckID
    function getSessionDeckID() {
        if (!isset($_SESSION['sessionDeckID'])) {
            $conn = makeConnection();
            $sql = "SELECT * FROM game WHERE gameID = 1";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();

            // Create new deck if current game doesn't have a deck
            if (is_null($row["deckID"])) {
                addDeck();
            }
            // Update session deckID if deck already exists
            else {
                $_SESSION['sessionDeckID'] = $row["deckID"];
            }
        }
    }

    function getTopCardDB() {
        $conn = makeConnection();
        $deckID = $_SESSION['sessionDeckID'];
        $sql = "SELECT * FROM card_deck WHERE deckID = '$deckID' ORDER BY cardOrder ASC LIMIT 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $cardID = $row['cardID'];
            $sql = "DELETE FROM card_deck WHERE deckID = '$deckID' AND cardID = '$cardID'";
            $conn->query($sql);

            return $cardID;
        }
    }
?>
</body>

function isLoginSessionExpired() {
    //giving them 30 seconds when it is
	$login_session_duration = 30; 
	$current_time = time(); 
    <script type = 'text/javascript'>
    var is_their_turn = document.getElementbyId('#hitBtn').enable;
    </script>
	if($is_their_turnand isset($_SESSION['loggedin_time']) and isset($_SESSION["user_id"])){  
		if(((time() - $_SESSION['loggedin_time']) > $login_session_duration)){ 
			return true; 
		} 
	}
	return false;
}

session_start();
unset($_SESSION["user_id"]);
unset($_SESSION["user_name"]);
$url = "index.php";
if(isset($_GET["session_expired"])) {
	$url .= "?session_expired=" . $_GET["session_expired"];
}
header("Location:$url");

