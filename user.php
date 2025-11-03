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
            $userId = $_GET["id"];
            getUserById($con, $userId);
        }else{
            getAllUsers($con);
        }
        break;
    case "POST":
        $data = json_decode(file_get_contents('php://input'), true);
        if(isset($_GET["login"])){
            loginUser($con, $data["username"], $data["password"]);
        }else{
            insertUser($con, $data["firstname"], $data["lastname"], $data["username"], $data["email"], $data["password"]);
        }
        break;
    case "PUT":
        $userId = $_GET["id"];
        $data = json_decode(file_get_contents('php://input'), true);
        updateUser($con, $userId, $data["firstname"], $data["lastname"], $data["username"], $data["email"], $data["password"]);
        break;
    case "DELETE":
        $userId = $_GET["id"];
        deleteUser($con, $userId);
        break;
    default:
        header("HTTP/1.0 405 Method Not Implemented");
        break;
}

function getUserById($con, $userId){

    global $errorArray;

    verifyId($userId);

    if(empty($errorArray)){
        $query = $con->prepare("SELECT * FROM Users WHERE userId=:id");
        $query->bindValue(":id", $userId);
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

function getAllUsers($con){
    $query = $con->prepare("SELECT * FROM Users");
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

function insertUser($con, $firstname, $lastname, $username, $email, $password){

    global $errorArray;

    verifyFirstname($firstname);
    verifyLastname($lastname);
    verifyUsername($username);
    verifyEmail($email);
    verifyPassword($password);

    if(empty($errorArray)){
        $query = $con->prepare("INSERT INTO Users (firstname lastname username, email, password) VALUES (:fn, :ln, :un, :em, :pw)");
        $query->bindValue(":fn", $firstname);
        $query->bindValue(":ln", $lastname);
        $query->bindValue(":un", $username);
        $query->bindValue(":em", $email);
        $query->bindValue(":pw", $password);

        if($query->execute()){
            //Success
            $query = $con->prepare("SELECT userId FROM Users WHERE username=:un AND password=:pw");
            $query->bindValue(":un", $username);
            $query->bindValue(":pw", $password);
            $query->execute();

            $userId = $query->fetch(PDO::FETCH_ASSOC);

            header('HTTP/1.0 201');
            $response = array(
                'status' => 1,
                'status_message' => 'User added successfully',
                'userId' => $userId["userId"]
            );
        }else{
            //Failed
            header('HTTP/1.0 400');
            $response = array(
                'status' => 0,
                'status_message' => 'User could not be added'
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

function loginUser($con, $username, $password){

    global $errorArray;

    verifyUsername($username);
    verifyPassword($password);

    if(empty($errorArray)){

        $query = $con->prepare("SELECT userId FROM Users WHERE username=:un AND password=:pw");
        $query->bindValue(":un", $username);
        $query->bindValue(":pw", $password);
        $query->execute();

        if($query->rowCount() == 1){

            $response = $query->fetch(PDO::FETCH_ASSOC);

        }else{
            $response = array("userId"=> -1);
        }

        header('Content-Type: application/json');
        echo json_encode($response);

    }

}

function updateUser($con, $userId, $firstname, $lastname, $username, $email, $password){

    global $errorArray;

    verifyId($userId);
    verifyFirstname($firstname);
    verifyLastname($lastname);
    verifyUsername($username);
    verifyEmail($email);
    verifyPassword($password);

    if(empty($errorArray)){
        $query = $con->prepare("UPDATE Users SET firstname=:fn, lastname=:ln, username=:un, email=:em, password=:pw WHERE userId=:id");
        $query->bindValue(":fn", $firstname);
        $query->bindValue(":ln", $lastname);
        $query->bindValue(":un", $username);
        $query->bindValue(":em", $email);
        $query->bindValue(":pw", $password);
        $query->bindValue(":id", $userId);

        if($query->execute()){
            //Success
            header('HTTP/1.0 200');
            $response = array(
                'status' => 1,
                'status_message' => 'User updated successfully'
            );
        }else{
            //Failed
            header('HTTP/1.0 400');
            $response = array(
                'status' => 0,
                'status_message' => 'User could not be updated'
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

function deleteUser($con, $userId){

    global $errorArray;

    verifyId($userId);

    if(empty($errorArray)){
        $query = $con->prepare("DELETE FROM Users WHERE userId=:id");
        $query->bindValue(":id", $userId);

        if($query->execute()){
            //Success
            header('HTTP/1.0 200');
            $response = array(
                'status' => 1,
                'status_message' => 'User deleted successfully'
            );
        }else{
            //Failed
            header('HTTP/1.0 400');
            $response = array(
                'status' => 0,
                'status_message' => 'User could not be deleted'
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

function verifyFirstname($firstname){

    global $errorArray;

    $pattern = "/^[A-z]*$/";

    if($firstname == "" || $firstname == null){
        $errorMessage = "First name cannot be empty";
        array_push($errorArray, $errorMessage);
    }elseif(!preg_match($pattern, $firstname)){
        $errorMessage = "Invalid first name";
        array_push($errorArray, $errorMessage);
    }elseif(strlen($firstname) > 25){
        $errorMessage = "First name cannot be more than 25 characters";
        array_push($errorArray, $errorMessage);
    }else{
        return;
    }

}

function verifyLastname($lastname){

    global $errorArray;

    $pattern = "/^[A-z]*$/";

    if($lastname == "" || $lastname == null){
        $errorMessage = "Last name cannot be empty";
        array_push($errorArray, $errorMessage);
    }elseif(!preg_match($pattern, $lastname)){
        $errorMessage = "Invalid last name";
        array_push($errorArray, $errorMessage);
    }elseif(strlen($lastname) > 25){
        $errorMessage = "Last name cannot be more than 25 characters";
        array_push($errorArray, $errorMessage);
    }else{
        return;
    }

}

function verifyUsername($username){

    global $errorArray;

    //Alphanumeric, "-" and "."
    $pattern = "/^[A-z0-9\-\.]*$/";

    if($username == "" || $username == null){
        $errorMessage = "Username cannot be empty";
        array_push($errorArray, $errorMessage);
    }elseif(!preg_match($pattern, $username)){
        $errorMessage = "Invalid username";
        array_push($errorArray, $errorMessage);
    }elseif(strlen($username) > 25){
        $errorMessage = "Username cannot be more than 25 characters";
        array_push($errorArray, $errorMessage);
    }else{
        return;
    }

}

function verifyEmail($email){

    global $errorArray;

    if($email == "" || $email == null){
        $errorMessage = "User email cannot be empty";
        array_push($errorArray, $errorMessage);
    }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errorMessage = "Email invalid";
        array_push($errorArray, $errorMessage);
        return;
    }elseif(strlen($email) > 50){
        $errorMessage = "Email cannot be more than 50 characters";
        array_push($errorArray, $errorMessage);
    }else{
        return;
    }

}

function verifyPassword($password){

    global $errorArray;

    //Hexadecimal
    $pattern = "/^[A-z0-9]*$/";

    if($password == "" || $password == null){
        $errorMessage = "User password cannot be empty";
        array_push($errorArray, $errorMessage);
    }elseif(!preg_match($pattern, $password)){
        $errorMessage = "Password invalid";
        array_push($errorArray, $errorMessage);
        return;
    }elseif(strlen($password) > 255){
        $errorMessage = "Password contains too many characters";
        array_push($errorArray, $errorMessage);
    }else{
        return;
    }

}

?>