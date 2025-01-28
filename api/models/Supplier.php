<?php
class Supplier {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getPending() {
        $result = $this->db->query('
            SELECT * FROM suppliers 
            WHERE status = "pending" 
            ORDER BY created_at DESC
        ');
        
        $suppliers = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $suppliers[] = $row;
        }
        return $suppliers;
    }

    public function updateStatus($id, $status, $reviewedBy, $rejectionReason = null) {
        $stmt = $this->db->prepare('
            UPDATE suppliers 
            SET status = :status, 
                reviewed_by = :reviewed_by,
                rejection_reason = :rejection_reason
            WHERE id = :id
        ');
        
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':reviewed_by', $reviewedBy);
        $stmt->bindValue(':rejection_reason', $rejectionReason);
        $stmt->bindValue(':id', $id);
        
        return $stmt->execute();
    }
} 