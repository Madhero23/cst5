<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        $employee_id = $_POST['employee_id'];
        $roles = $_POST['roles'] ?? [];
        $entity_types = $_POST['entity_types'] ?? [];
        $entity_ids = $_POST['entity_ids'] ?? [];

        // Remove existing roles
        $stmt = $conn->prepare("DELETE FROM employeemanagement WHERE EmployeeID = ?");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();

        // Add new roles with entity types
        if (!empty($roles)) {
            $stmt = $conn->prepare("
                INSERT INTO employeemanagement 
                (EmployeeID, Role, ManagedEntityType, ManagedEntityID) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($roles as $index => $role) {
                $entity_type = $entity_types[$index] ?? 'Default';
                $entity_id = $entity_ids[$index] ?? 1;
                $stmt->bind_param("issi", $employee_id, $role, $entity_type, $entity_id);
                $stmt->execute();
            }
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>