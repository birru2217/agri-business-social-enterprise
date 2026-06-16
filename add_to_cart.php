<?php
// add_to_cart.php
session_start();

if (isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    
    // Yoo cart hin uumamne ta'e uumi
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Meeshaa itti dabali (Quantity = 1)
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]++;
    } else {
        $_SESSION['cart'][$product_id] = 1;
    }
    
    // Gara cart.php tti deebisi
    header("Location: cart.php");
    exit();
}