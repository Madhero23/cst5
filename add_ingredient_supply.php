<?php
require_once 'db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        $ingredient_id = $_POST['ingredient_id'];
        $supplier_id = $_POST['supplier_id'];
        $quantity = $_POST['quantity'];
        $employee_id = $_SESSION['user_id'] ?? 1;

        // Verify supplier is authorized for this ingredient
        $check_supplier = $conn->prepare("
            SELECT SupplierIngredientID 
            FROM supplieringredient 
            WHERE SupplierID = ? AND IngredientID = ?
        ");
        $check_supplier->bind_param("ii", $supplier_id, $ingredient_id);
        $check_supplier->execute();
        
        if ($check_supplier->get_result()->num_rows === 0) {
            throw new Exception("This supplier is not authorized for this ingredient");
        }

        // Add supply record
        $supply_stmt = $conn->prepare("
            INSERT INTO supplies (
                IngredientID,
                SupplierID,
                SuppliedQuantity,
                EmployeeID,
                SuppliedDate
            ) VALUES (?, ?, ?, ?, NOW())
        ");
        $supply_stmt->bind_param("iiii", $ingredient_id, $supplier_id, $quantity, $employee_id);
        $supply_stmt->execute();

        // Update ingredient stock
        $update_stock = $conn->prepare("
            UPDATE ingredients 
            SET ItemStockQuantity = ItemStockQuantity + ?,
                IngredientExpirationDate = DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)
            WHERE IngredientID = ?
        ");
        $update_stock->bind_param("ii", $quantity, $ingredient_id);
        $update_stock->execute();

        $conn->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
