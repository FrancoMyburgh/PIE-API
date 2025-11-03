<?php

require_once("config.php");
require_once("auth.php");

$auth = new Auth();
$authorized = $auth->authenticate();

if(!$authorized){
    header("HTTP/1.0 403");
    exit();
}

$errorArray = array();

$requestMethod = $_SERVER["REQUEST_METHOD"];

switch($requestMethod){
    case "GET":
        if(isset($_GET["id"])){
            $sessionId = $_GET["id"];
            getSessionById($con, $sessionId);
        }else{
            getAllSessions($con);
        }
        break;
    case "POST":
        $data = json_decode(file_get_contents('php://input'), true);
        insertSession($con, $data["userId"], $data["locationId"]);
        break;
    case "PUT":
        $sessionId = $_GET["id"];
        if(isset($_GET["paid"])){
            if($_GET["paid"] == "true"){
                $data = json_decode(file_get_contents('php://input'), true);
                updateSessionPaid($con, $sessionId, $data["timePaid"]);
            }
        }elseif(isset($_GET["exit"])){
            if($_GET["exit"] == "true"){
                $data = json_decode(file_get_contents('php://input'), true);
                updateSessionExit($con, $sessionId, $data["timeOut"]);
            }
        }else{
            header("HTTP/1.0 405 Missing GET Variables");
        }
        break;
    case "DELETE":
        $sessionId = $_GET["id"];
        deleteSession($con, $sessionId);
        break;
    default:
        header("HTTP/1.0 405 Method Not Implemented");
        break;
}

function getSessionById($con, $SessionId){
    $query = $con->prepare("SELECT * FROM Session WHERE sessionId=:id");
    $query->bindValue(":id", $SessionId);
    $query->execute();

    $response = $query->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($response);
}

function getAllSessions($con){
    $query = $con->prepare("SELECT * FROM Session");
    $query->execute();

    $response = array();

    if($query->rowCount() > 0){

        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            array_push($response, $row);
        }

    }

    header('Content-Type: application/json');
    echo json_encode($response);
}

function insertSession($con, $userId, $locationId){

    global $errorArray;

    verifyId($userId);
    verifyId($locationId);

    if(empty($errorArray)){
        $query = $con->prepare("INSERT INTO Session (userId, locationId) VALUES (:ui, :li)");
        $query->bindValue(":ui", $userId);
        $query->bindValue(":li", $locationId);

        if($query->execute()){
            //Success
            $query = $con->prepare("SELECT sessionId FROM Session WHERE userId=:ui AND locationId=:li AND timePaid IS NULL");
            $query->bindValue(":ui", $userId);
            $query->bindValue(":li", $locationId);
            $query->execute();

            $result = $query->fetch()[0];

            header('HTTP/1.0 201');
            $response = array(
                'status' => 1,
                'status_message' => 'Session added successfully',
                'sessionId' => $result
            );
        }else{
            //Failed
            header('HTTP/1.0 400');
            $response = array(
                'status' => 0,
                'status_message' => 'Session could not be added'
            );
        }
    }else{
        //Failed
        header('HTTP/1.0 400');
        $response = array(
            'status' => 0,
            'status_message' => $errorArray[0]
        );
    }

    header('Content-Type: application/json');
    echo json_encode($response);

}

function updateSessionPaid($con, $sessionId, $timePaid){

    global $errorArray;

    verifyId($sessionId);
    verifyTime($timePaid);

    if(empty($errorArray)){
        $query = $con->prepare("UPDATE Session SET timePaid=:tp WHERE sessionId=:id");
        $query->bindValue(":tp", $timePaid);
        $query->bindValue(":id", $sessionId);

        if($query->execute()){
            //Success
            header('HTTP/1.0 200');
            $response = array(
                'status' => 1,
                'status_message' => 'Session updated successfully'
            );
        }else{
            //Failed
            header('HTTP/1.0 400');
            $response = array(
                'status' => 0,
                'status_message' => 'Session could not be updated'
            );
        }
    }else{
        //Failed
        header('HTTP/1.0 400');
        $response = array(
            'status' => 0,
            'status_message' => $errorArray[0]
        );
    }

    header('Content-Type: application/json');
    echo json_encode($response);

}

function updateSessionExit($con, $sessionId, $timeOut){

    global $errorArray;

    verifyId($sessionId);
    verifyTime($timeOut);

    if(empty($errorArray)){
        $query = $con->prepare("UPDATE Session SET timeOut=:to WHERE sessionId=:id");
        $query->bindValue(":to", $timeOut);
        $query->bindValue(":id", $sessionId);

        if($query->execute()){
            //Success
            header('HTTP/1.0 200');
            $response = array(
                'status' => 1,
                'status_message' => 'Session updated successfully'
            );
        }else{
            //Failed
            header('HTTP/1.0 400');
            $response = array(
                'status' => 0,
                'status_message' => 'Session could not be updated'
            );
        }
    }else{
        //Failed
        header('HTTP/1.0 400');
        $response = array(
            'status' => 0,
            'status_message' => $errorArray[0]
        );
    }

    header('Content-Type: application/json');
    echo json_encode($response);

}

function deleteSession($con, $sessionId){

    $query = $con->prepare("DELETE FROM Session WHERE sessionId=:id");
    $query->bindValue(":id", $sessionId);

    if($query->execute()){
        //Success
        header('HTTP/1.0 200');
        $response = array(
            'status' => 1,
            'status_message' => 'Session deleted successfully'
        );
    }else{
        //Failed
        header('HTTP/1.0 400');
        $response = array(
            'status' => 0,
            'status_message' => 'Session could not be deleted'
        );
    }

    header('Content-Type: application/json');
    echo json_encode($response);

}

function verifyId($id){

    global $errorArray;

    $pattern = "/^[0-9]*$/";

    if($id == "" || $id == null){
        $errorMessage = "ID cannot be empty";
        array_push($errorArray, $errorMessage);
    }elseif(!preg_match($pattern, $id)){
        $errorMessage = "Invalid ID";
        array_push($errorArray, $errorMessage);
    }elseif(strlen($id) > 100){
        $errorMessage = "ID cannot be more than 100 characters";
        array_push($errorArray, $errorMessage);
    }else{
        return;
    }

}

function verifyTime($time){

    global $errorArray;

    $pattern = "/^([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})$/";

    if($time == "" || $time == null){
        $errorMessage = "Time cannot be empty";
        array_push($errorArray, $errorMessage);
    }elseif(!preg_match($pattern, $time)){
        $errorMessage = "Invalid Time, Format: YYYY-MM-DD HH:MM:SS";
        array_push($errorArray, $errorMessage);
    }elseif(strlen($time) > 19){
        $errorMessage = "Time cannot be more than 19 characters";
        array_push($errorArray, $errorMessage);
    }else{
        return;
    }

}

?>