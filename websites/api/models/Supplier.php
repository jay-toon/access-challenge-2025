<?php
class Supplier {
    private $db;
    private $table = 'suppliers';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data, $userId = null) {
        try {
            // Start transaction
            $this->db->beginTransaction();

            // Insert supplier
            $stmt = $this->db->prepare("
                INSERT INTO suppliers (share_code, supplier_data, status) 
                VALUES (:share_code, :supplier_data, 'pending')
            ");

            $stmt->execute([
                ':share_code' => $data['share_code'],
                ':supplier_data' => json_encode($data['supplier_data'])
            ]);

            $supplierId = $this->db->lastInsertId();

            // Create audit log
            $auditStmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, new_value) 
                VALUES (:user_id, 'create', 'supplier', :entity_id, :new_value)
            ");

            $auditStmt->execute([
                ':user_id' => $userId,
                ':entity_id' => $supplierId,
                ':new_value' => json_encode($data)
            ]);

            $this->db->commit();
            return $supplierId;

        } catch(PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateStatus($id, $status, $userId, $rejectionReason = null) {
        try {
            $this->db->beginTransaction();

            // Get old value for audit log
            $oldStmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?");
            $oldStmt->execute([$id]);
            $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

            // Update supplier
            $stmt = $this->db->prepare("
                UPDATE suppliers 
                SET status = :status,
                    reviewed_by = :reviewed_by,
                    rejection_reason = :rejection_reason
                WHERE id = :id
            ");

            $stmt->execute([
                ':status' => $status,
                ':reviewed_by' => $userId,
                ':rejection_reason' => $rejectionReason,
                ':id' => $id
            ]);

            // Get new value for audit log
            $newStmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?");
            $newStmt->execute([$id]);
            $newData = $newStmt->fetch(PDO::FETCH_ASSOC);

            // Create audit log
            $auditStmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_value, new_value) 
                VALUES (:user_id, 'update_status', 'supplier', :entity_id, :old_value, :new_value)
            ");

            $auditStmt->execute([
                ':user_id' => $userId,
                ':entity_id' => $id,
                ':old_value' => json_encode($oldData),
                ':new_value' => json_encode($newData)
            ]);

            $this->db->commit();
            return true;

        } catch(PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getPending() {
        $stmt = $this->db->query("
            SELECT * FROM suppliers 
            WHERE status = 'pending' 
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
} 