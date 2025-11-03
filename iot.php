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
            $gateId = $_GET["id"];
            getGateStatus($con, $gateId);
        }
        break;
    case "POST":
        $data = json_decode(file_get_contents('php://input'), true);
        insertGate($con, $data["locationId"], $data["gateType"]);
        break;
    case "PUT":
        $gateId = $_GET["id"];
        $data = json_decode(file_get_contents('php://input'), true);
        if(isset($_GET["close"])){
            if($_GET["close"] == "true"){
                setGateClosed($con, $gateId);
            }
        }elseif(isset($_GET["open"])){
            if($_GET["open"] == "true"){
                setGateOpen($con, $gateId);
            }
        }else{
            updateGate($con, $gateId, $data["locationId"], $data["gateType"]);
        }
        break;
    case "DELETE":
        $gateId = $_GET["id"];
        deleteGate($con, $gateId);
        break;
    default:
        header("HTTP/1.0 405 Method Not Implemented");
        break;
}

function getGateStatus($con, $gateId){
    $query = $con->prepare("SELECT status FROM Gates WHERE gateId=:id");
    $query->bindValue(":id", $gateId);
    $query->execute();

    $response = $query->fetch(PDO::FETCH_ASSOC);

    if($response["status"] == 1){
        $response = array("status" => 1);
    }elseif($response["status"] == 0){
        $response = array("status" => 0);
    }

    header('Content-Type: application/json');
    echo json_encode($response);
}

function insertGate($con, $locationId, $gateType){

    $query = $con->prepare("INSERT INTO Gates (locationId, gateType) VALUES (:li, :gt)");
    $query->bindValue(":li", $locationId);
    $query->bindValue(":gt", $gateType);

    if($query->execute()){
        //Success
        header('HTTP/1.0 201');
        $response = array(
            'status' => 1,
            'status_message' => 'Gate added successfully'
        );
    }else{
        //Failed
        header('HTTP/1.0 400');
        $response = array(
            'status' => 0,
            'status_message' => 'Gate could not be added'
        );
    }

    header('Content-Type: application/json');
    echo json_encode($response);

}

function setGateClosed($con, $gateId){

    $query = $con->prepare("UPDATE Gates SET status=:st WHERE gateId=:id");
    $query->bindValue(":st", 0);
    $query->bindValue(":id", $gateId);

    if($query->execute()){
        //Success
        header('HTTP/1.0 200');
        $response = array(
            'status' => 1,
            'status_message' => 'Gate set as closed'
        );
    }else{
        //Failed
        header('HTTP/1.0 400');
        $response = array(
            'status' => 0,
            'status_message' => 'Gate could not be closed'
        );
    }

    header('Content-Type: application/json');
    echo json_encode($response);

}

function setGateOpen($con, $gateId){

    $status = 1;

    $query = $con->prepare("UPDATE Gates SET status=:st WHERE gateId=:id");
    $query->bindValue(":st", $status);
    $query->bindValue(":id", $gateId);

    if($query->execute()){
        //Success
        header('HTTP/1.0 200');
        $response = array(
            'status' => 1,
            'status_message' => 'Gate set as open'
        );
    }else{
        //Failed
        header('HTTP/1.0 400');
        $response = array(
            'status' => 0,
            'status_message' => 'Gate could not be opened'
        );
    }

    header('Content-Type: application/json');
    echo json_encode($response);

}

function updateGate($con, $gateId, $locationId, $gateType){

    $query = $con->prepare("UPDATE Gates SET locationId=:li, gateType=:gt WHERE gateId=:id");
    $query->bindValue(":li", $locationId);
    $query->bindValue(":gt", $gateType);
    $query->bindValue(":id", $gateId);

    if($query->execute()){
        //Success
        header('HTTP/1.0 200');
        $response = array(
            'status' => 1,
            'status_message' => 'Gate updated successfully'
        );
    }else{
        //Failed
        header('HTTP/1.0 400');
        $response = array(
            'status' => 0,
            'status_message' => 'Gate could not be updated'
        );
    }

    header('Content-Type: application/json');
    echo json_encode($response);

}

function deleteGate($con, $gateId){

    $query = $con->prepare("DELETE FROM Gates WHERE gateId=:id");
    $query->bindValue(":id", $gateId);

    if($query->execute()){
        //Success
        header('HTTP/1.0 200');
        $response = array(
            'status' => 1,
            'status_message' => 'Gate deleted successfully'
        );
    }else{
        //Failed
        header('HTTP/1.0 400');
        $response = array(
            'status' => 0,
            'status_message' => 'Gate could not be deleted'
        );
    }

    header('Content-Type: application/json');
    echo json_encode($response);

}

?>