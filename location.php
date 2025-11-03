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
            $locationId = $_GET["id"];
            getLocationById($con, $locationId);
        }else{
            getAllLocations($con);
        }
        break;
    case "POST":
        $data = json_decode(file_get_contents('php://input'), true);
        insertLocation($con, $data["locationName"], $data["hourlyRate"]);
        break;
    case "PUT":
        $locationId = $_GET["id"];
        $data = json_decode(file_get_contents('php://input'), true);
        updateLocation($con, $locationId, $data["locationName"], $data["hourlyRate"]);
        break;
    case "DELETE":
        $locationId = $_GET["id"];
        deleteLocation($con, $locationId);
        break;
    default:
        header("HTTP/1.0 405 Method Not Implemented");
        break;
}

function getLocationById($con, $LocationId){
    $query = $con->prepare("SELECT * FROM Location WHERE locationId=:id");
    $query->bindValue(":id", $LocationId);
    $query->execute();

    $response = $query->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($response);
}

function getAllLocations($con){
    $query = $con->prepare("SELECT * FROM Location");
    $query->execute();

    $response = array();

    if($query->rowCount() > 0){

        while($row = $query->fetch(PDO::FETCH_ASSOC)){

            $row["hourlyRate"] = (float)$row["hourlyRate"];
            $row["hourlyRate"] = number_format($row["hourlyRate"], 2, '.', '');

            array_push($response, $row);
        }

    }

    header('Content-Type: application/json');
    echo json_encode($response);
}

function insertLocation($con, $locationName, $hourlyRate){

    global $errorArray;

    verifyCurrencyValue($hourlyRate);

    if(empty($errorArray)){
        $query = $con->prepare("INSERT INTO Location (locationName, hourlyRate) VALUES (:ln, :hr)");
        $query->bindValue(":ln", $locationName);
        $query->bindValue(":hr", $hourlyRate);

        if($query->execute()){
            //Success
            header('HTTP/1.0 201');
            $response = array(
                'status' => 1,
                'status_message' => 'Location added successfully'
            );
        }else{
            //Failed
            header('HTTP/1.0 400');
            $response = array(
                'status' => 0,
                'status_message' => 'Location could not be added'
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

function updateLocation($con, $locationId, $locationName, $hourlyRate){

    global $errorArray;

    verifyCurrencyValue($hourlyRate);

    if(empty($errorArray)){
        $query = $con->prepare("UPDATE Location SET locationName=:ln, hourlyRate=:hr WHERE locationId=:id");
        $query->bindValue(":ln", $locationName);
        $query->bindValue(":hr", $hourlyRate);
        $query->bindValue(":id", $locationId);

        if($query->execute()){
            //Success
            header('HTTP/1.0 200');
            $response = array(
                'status' => 1,
                'status_message' => 'Location updated successfully'
            );
        }else{
            //Failed
            header('HTTP/1.0 400');
            $response = array(
                'status' => 0,
                'status_message' => 'Location could not be updated'
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

function deleteLocation($con, $locationId){

    $query = $con->prepare("DELETE FROM Location WHERE locationId=:id");
    $query->bindValue(":id", $locationId);

    if($query->execute()){
        //Success
        header('HTTP/1.0 200');
        $response = array(
            'status' => 1,
            'status_message' => 'Location deleted successfully'
        );
    }else{
        //Failed
        header('HTTP/1.0 400');
        $response = array(
            'status' => 0,
            'status_message' => 'Location could not be deleted'
        );
    }

    header('Content-Type: application/json');
    echo json_encode($response);

}

function verifyCurrencyValue($price){

    global $errorArray;

    $pattern = "/^([0-9]+[\.]?[0-9]*)$/";

    if($price == "" || $price == null){
        $errorMessage = "Location amount cannot be empty";
        array_push($errorArray, $errorMessage);
    }elseif(!preg_match($pattern, $price)){
        $errorMessage = "Invalid amount";
        array_push($errorArray, $errorMessage);
    }elseif(strlen($price) > 20){
        $errorMessage = "Amount cannot be more than 20 characters";
        array_push($errorArray, $errorMessage);
    }else{
        return;
    }

}

?>