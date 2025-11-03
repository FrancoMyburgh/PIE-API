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
            $id = $_GET["id"];
            getProductById($con, $id);
        }else{
            getAllProducts($con);
        }
        break;
    case "POST":
        $data = json_decode(file_get_contents('php://input'), true);
        insertProduct($con, $data["name"], $data["description"], $data["size"], $data["price"]);
        break;
    case "PUT":
        $id = $_GET["id"];
        $data = json_decode(file_get_contents('php://input'), true);
        updateProduct($con, $id, $data["name"], $data["description"], $data["size"], $data["price"]);
        break;
    case "DELETE":
        $id = $_GET["id"];
        deleteProduct($con, $id);
        break;
    default:
        header("HTTP/1.0 405 Method Not Implemented");
        break;
}

function getProductById($con, $id){
    $query = $con->prepare("SELECT * FROM products WHERE productId=:id");
    $query->bindValue(":id", $id);
    $query->execute();

    $response = $query->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($response);
}

function getAllProducts($con){
    $query = $con->prepare("SELECT * FROM products");
    $query->execute();

    $response = array();

    if($query->rowCount() > 0){

        while($row = $query->fetch(PDO::FETCH_ASSOC)){

            $row["price"] = (float)$row["price"];
            $row["price"] = number_format($row["price"], 2, '.', '');

            array_push($response, $row);
        }

    }

    header('Content-Type: application/json');
    echo json_encode($response);
}

function insertProduct($con, $name, $description, $size, $price){

    global $errorArray;

    verifyProductName($con, $name);
    verifyProductDescription($description);
    verifyProductSize($size);
    verifyProductPrice($price);

    if(empty($errorArray)){
        $query = $con->prepare("INSERT INTO products (name, description, size, price) VALUES (:nm, :dc, :sz, :pr)");
        $query->bindValue(":nm", $name);
        $query->bindValue(":dc", $description);
        $query->bindValue(":sz", $size);
        $query->bindValue(":pr", $price);

        if($query->execute()){
            //Success
            header('HTTP/1.0 201');
            $response = array(
                'status' => 1,
                'status_message' => 'Product added successfully'
            );
        }else{
            //Failed
            header('HTTP/1.0 400');
            $response = array(
                'status' => 0,
                'status_message' => 'Product could not be added'
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

function updateProduct($con, $id, $name, $description, $size, $price){

    global $errorArray;

    verifyProductName($con, $name, 1);
    verifyProductDescription($description);
    verifyProductSize($size);
    verifyProductPrice($price);

    if(empty($errorArray)){
        $query = $con->prepare("UPDATE products SET name=:nm, description=:dc, size=:sz, price=:pr WHERE productId=:id");
        $query->bindValue(":nm", $name);
        $query->bindValue(":dc", $description);
        $query->bindValue(":sz", $size);
        $query->bindValue(":pr", $price);
        $query->bindValue(":id", $id);

        if($query->execute()){
            //Success
            header('HTTP/1.0 200');
            $response = array(
                'status' => 1,
                'status_message' => 'Product updated successfully'
            );
        }else{
            //Failed
            header('HTTP/1.0 400');
            $response = array(
                'status' => 0,
                'status_message' => 'Product could not be updated'
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

function deleteProduct($con, $id){

    $query = $con->prepare("DELETE FROM products WHERE productId=:id");
    $query->bindValue(":id", $id);

    if($query->execute()){
        //Success
        header('HTTP/1.0 200');
        $response = array(
            'status' => 1,
            'status_message' => 'Product deleted successfully'
        );
    }else{
        //Failed
        header('HTTP/1.0 400');
        $response = array(
            'status' => 0,
            'status_message' => 'Product could not be deleted'
        );
    }

    header('Content-Type: application/json');
    echo json_encode($response);

}

function verifyProductName($con, $name, $update=null){

    global $errorArray;

    $pattern = "/^[A-z0-9\-\. ]*$/";

    $query = $con->prepare("SELECT * FROM products WHERE name=:nm");
    $query->bindValue(":nm", $name);
    $query->execute();

    if($update == null && $query->rowCount() !== 0){
        $errorMessage = "Product allready exists";
        array_push($errorArray, $errorMessage);
    }elseif($name == "" || $name == null){
        $errorMessage = "Product name cannot be empty";
        array_push($errorArray, $errorMessage);
    }elseif(!preg_match($pattern, $name)){
        $errorMessage = "Invalid name";
        array_push($errorArray, $errorMessage);
    }elseif(strlen($name) > 25){
        $errorMessage = "Name cannot be more than 25 characters";
        array_push($errorArray, $errorMessage);
    }else{
        return;
    }

}

function verifyProductDescription($description){

    global $errorArray;

    $pattern = "/^[A-z0-9\-\.\, ]*$/";

    if($description == "" || $description == null){
        $errorMessage = "Product description cannot be empty";
        array_push($errorArray, $errorMessage);
    }elseif(!preg_match($pattern, $description)){
        $errorMessage = "Invalid description";
        array_push($errorArray, $errorMessage);
    }elseif(strlen($description) > 255){
        $errorMessage = "Description cannot be more than 255 characters";
        array_push($errorArray, $errorMessage);
    }else{
        return;
    }

}

function verifyProductSize($size){

    global $errorArray;

    $pattern = "/^[0-9]*$/";

    if($size == "" || $size == null){
        $errorMessage = "Product size cannot be empty";
        array_push($errorArray, $errorMessage);
    }elseif(!preg_match($pattern, $size)){
        $errorMessage = "Invalid size";
        array_push($errorArray, $errorMessage);
    }elseif(strlen($size) > 2){
        $errorMessage = "Size cannot be more than 2 characters";
        array_push($errorArray, $errorMessage);
    }else{
        return;
    }

}

function verifyProductPrice($price){

    global $errorArray;

    $pattern = "/^([0-9]+[\.]?[0-9]*)$/";

    if($price == "" || $price == null){
        $errorMessage = "Product price cannot be empty";
        array_push($errorArray, $errorMessage);
    }elseif(!preg_match($pattern, $price)){
        $errorMessage = "Invalid price";
        array_push($errorArray, $errorMessage);
    }elseif(strlen($price) > 20){
        $errorMessage = "Price cannot be more than 20 characters";
        array_push($errorArray, $errorMessage);
    }else{
        return;
    }

}


?>