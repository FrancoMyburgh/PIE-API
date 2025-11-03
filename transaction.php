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
            $transactionId = $_GET["id"];
            getTransactionById($con, $transactionId);
        }elseif(isset($_GET["userid"])){
            $userId = $_GET["userid"];
            getTransactionsByUserId($con, $userId);
        }else{
            getAllTransactions($con);
        }
        break;
    case "POST":
        $data = json_decode(file_get_contents('php://input'), true);
        if(isset($_GET["payment"]) && isset($_GET["id"])){
            $transactionId = $_GET["id"];
            if($_GET["payment"] == "complete"){
                completeTransaction($con, $transactionId, $data["gatewayId"]);
            }
        }else{
            insertTransaction($con, $data["gatewayId"], $data["amount"], $data["sessionId"], $data["userId"], $data["locationId"]);
        }
        break;
    case "PUT":
        $transactionId = $_GET["id"];
        $data = json_decode(file_get_contents('php://input'), true);
        updateTransaction($con, $transactionId, $data["gatewayId"], $data["amount"], $data["sessionId"], $data["userId"], $data["locationId"]);
        break;
    case "DELETE":
        $transactionId = $_GET["id"];
        deleteTransaction($con, $transactionId);
        break;
    default:
        header("HTTP/1.0 405 Method Not Implemented");
        break;
}

function getTransactionById($con, $transactionId){

    global $errorArray;

    verifyId($transactionId);

    if(empty($errorArray)){
        $query = $con->prepare("SELECT * FROM Transactions WHERE transactionId=:id");
        $query->bindValue(":id", $transactionId);
        $query->execute();

        $response = $query->fetch(PDO::FETCH_ASSOC);
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

function getTransactionsByUserId($con, $userId){

    global $errorArray;

    verifyId($userId);

    if(empty($errorArray)){
        $query = $con->prepare("SELECT Transactions.transactionId as transactionId, Location.locationName as locationName, Transactions.datePaid as datePaid, Transactions.amount as amount FROM Transactions INNER JOIN Location ON Transactions.locationId=Location.locationId WHERE Transactions.userId=:id");
        $query->bindValue(":id", $userId);
        $query->execute();

        $response = array();

        if($query->rowCount() > 0){

            while($row = $query->fetch(PDO::FETCH_ASSOC)){

                $row["amount"] = (float)$row["amount"];
                $row["amount"] = number_format($row["amount"], 2, '.', '');

                array_push($response, $row);
            }

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

function getAllTransactions($con){
    $query = $con->prepare("SELECT * FROM Transactions");
    $query->execute();

    $response = array();

    if($query->rowCount() > 0){

        while($row = $query->fetch(PDO::FETCH_ASSOC)){

            $row["amount"] = (float)$row["amount"];
            $row["amount"] = number_format($row["amount"], 2, '.', '');

            array_push($response, $row);
        }

    }

    header('Content-Type: application/json');
    echo json_encode($response);
}

function insertTransaction($con, $gatewayId, $amount, $sessionId, $userId, $locationId){

    global $errorArray;

    verifyAmount($amount);
    verifyId($sessionId);
    verifyId($userId);
    verifyId($locationId);

    if(empty($errorArray)){
        $query = $con->prepare("INSERT INTO Transactions (gatewayId, amount, sessionId, userId, locationId) VALUES (:gi, :am, :si, :ui, :li)");
        $query->bindValue(":gi", $gatewayId);
        $query->bindValue(":am", $amount);
        $query->bindValue(":si", $sessionId);
        $query->bindValue(":ui", $userId);
        $query->bindValue(":li", $locationId);

        if($query->execute()){
            //Success
            $query = $con->prepare("SELECT transactionId FROM Transactions ORDER BY transactionId DESC LIMIT 1");
            $query->execute();

            $result = $query->fetch(PDO::FETCH_ASSOC);

            header('HTTP/1.0 201');
            $response = array(
                'status' => 1,
                'status_message' => 'Transaction added successfully',
                'transactionId' => $result['transactionId']
            );
        }else{
            //Failed
            header('HTTP/1.0 400');
            $response = array(
                'status' => 0,
                'status_message' => 'Transaction could not be added'
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

function completeTransaction($con, $transactionId, $gatewayId){

    global $errorArray;

    verifyId($transactionId);

    if(empty($errorArray)){
        $query = $con->prepare("UPDATE Transactions SET gatewayId=:gi WHERE transactionId=:id");
        $query->bindValue(":gi", $gatewayId);
        $query->bindValue(":id", $transactionId);

        if($query->execute()){
            //Success
            header('HTTP/1.0 200');
            $response = array(
                'status' => 1,
                'status_message' => 'Transaction updated successfully'
            );
        }else{
            //Failed
            header('HTTP/1.0 400');
            $response = array(
                'status' => 0,
                'status_message' => 'Transaction could not be updated'
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

function updateTransaction($con, $transactionId, $gatewayId, $amount, $sessionId, $userId, $locationId){

    global $errorArray;

    verifyId($transactionId);
    verifyAmount($amount);
    verifyId($sessionId);
    verifyId($userId);
    verifyId($locationId);

    if(empty($errorArray)){
        $query = $con->prepare("UPDATE Transactions SET gatewayId=:gi, amount=:am, sessionId=:si, userId=:ui, locationId=:li WHERE transactionId=:id");
        $query->bindValue(":gi", $gatewayId);
        $query->bindValue(":am", $amount);
        $query->bindValue(":si", $sessionId);
        $query->bindValue(":ui", $userId);
        $query->bindValue(":li", $locationId);
        $query->bindValue(":id", $transactionId);

        if($query->execute()){
            //Success
            header('HTTP/1.0 200');
            $response = array(
                'status' => 1,
                'status_message' => 'Transaction updated successfully'
            );
        }else{
            //Failed
            header('HTTP/1.0 400');
            $response = array(
                'status' => 0,
                'status_message' => 'Transaction could not be updated'
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

function deleteTransaction($con, $transactionId){

    global $errorArray;

    verifyId($transactionId);

    if(empty($errorArray)){
        $query = $con->prepare("DELETE FROM Transactions WHERE transactionId=:id");
        $query->bindValue(":id", $transactionId);

        if($query->execute()){
            //Success
            header('HTTP/1.0 200');
            $response = array(
                'status' => 1,
                'status_message' => 'Transaction deleted successfully'
            );
        }else{
            //Failed
            header('HTTP/1.0 400');
            $response = array(
                'status' => 0,
                'status_message' => 'Transaction could not be deleted'
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

function verifyAmount($amount){

    global $errorArray;

    $pattern = "/^([0-9\.,]*)$/";

    if($amount == "" || $amount == null){
        $errorMessage = "Transaction amount cannot be empty";
        array_push($errorArray, $errorMessage);
    }elseif(!preg_match($pattern, $amount)){
        $errorMessage = "Invalid amount";
        array_push($errorArray, $errorMessage);
    }elseif(strlen($amount) > 20){
        $errorMessage = "Amount cannot be more than 20 characters";
        array_push($errorArray, $errorMessage);
    }else{
        return;
    }

}


?>