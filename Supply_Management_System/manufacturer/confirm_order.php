<?php
require("../includes/config.php");
session_start();
if(isset($_SESSION['manufacturer_login'])) {
    $orderId = $_GET['id'];
    $availableProducts = array();
    $orderedProducts = array();

    $queryAvailableQuantity = "SELECT products.pro_id AS pro_id, products.quantity AS quantity FROM order_items, products WHERE products.pro_id = order_items.pro_id AND order_items.order_id = ?";
    $stmtAvailableQuantity = mysqli_prepare($con, $queryAvailableQuantity);
    mysqli_stmt_bind_param($stmtAvailableQuantity, "i", $orderId);
    mysqli_stmt_execute($stmtAvailableQuantity);
    $resultAvailableQuantity = mysqli_stmt_get_result($stmtAvailableQuantity);

    $queryOrderQuantity = "SELECT quantity AS q, pro_id AS p FROM order_items WHERE order_id = ?";
    $stmtOrderQuantity = mysqli_prepare($con, $queryOrderQuantity);
    mysqli_stmt_bind_param($stmtOrderQuantity, "i", $orderId);
    mysqli_stmt_execute($stmtOrderQuantity);
    $resultOrderQuantity = mysqli_stmt_get_result($stmtOrderQuantity);

    while($rowAvailableQuantity = mysqli_fetch_array($resultAvailableQuantity)){
        $availableProducts[$rowAvailableQuantity['pro_id']] = $rowAvailableQuantity['quantity'];
    }

    while($rowOrderQuantity = mysqli_fetch_array($resultOrderQuantity)){
        $orderedProducts[$rowOrderQuantity['p']] = $rowOrderQuantity['q'];
    }

    foreach($orderedProducts as $productId => $orderQuantity) {
        if(isset($availableProducts[$productId])) {
            $availableQuantity = $availableProducts[$productId];
            $total = $availableQuantity - $orderQuantity;
            if($total >= 0 ) {
                $queryUpdateQuantity = "UPDATE products SET quantity = ? WHERE pro_id = ?";
                $stmtUpdateQuantity = mysqli_prepare($con, $queryUpdateQuantity);
                mysqli_stmt_bind_param($stmtUpdateQuantity, "ii", $total, $productId);
                mysqli_stmt_execute($stmtUpdateQuantity);
                if(mysqli_stmt_affected_rows($stmtUpdateQuantity) < 1) {
                    mysqli_stmt_close($stmtUpdateQuantity);
                    mysqli_close($con);
                    echo "<script> alert(\"You don't have enough stock to approve this order\"); </script>";
                    header("refresh:0;url=view_orders.php");
                    exit;
                }
                mysqli_stmt_close($stmtUpdateQuantity);
            }
        }
    }

    $queryConfirm = "UPDATE orders SET approved = 1, status = 1 WHERE order_id = ?";
    $stmtConfirm = mysqli_prepare($con, $queryConfirm);
    mysqli_stmt_bind_param($stmtConfirm, "i", $orderId);
    if(mysqli_stmt_execute($stmtConfirm)) {
        mysqli_stmt_close($stmtConfirm);
        mysqli_close($con);
        echo "<script> alert(\"Order has been confirmed\"); </script>";
        header( "refresh:0;url=view_orders.php" );
        exit;
    } else {
        mysqli_stmt_close($stmtConfirm);
        mysqli_close($con);
        echo "<script> alert(\"There was some issue in approving order.\"); </script>";
        header( "refresh:0;url=view_orders.php" );
        exit;
    }
} else {
    header('Location:../index.php');
    exit;
	}
?>