<?php
class SupplierController {
    private $supplierModel;

    public function __construct() {
        $this->supplierModel = new Supplier();
    }

    public function getPendingSuppliers() {
        AuthMiddleware::isAuthenticated();
        $suppliers = $this->supplierModel->getPending();
        Response::success($suppliers);
    }

    public function updateDecision($id) {
        AuthMiddleware::isAuthenticated();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $success = $this->supplierModel->updateStatus(
            $id,
            $data['decision'],
            $_SESSION['user']['fullName'],
            $data['rejectionReason'] ?? null
        );

        if ($success) {
            Response::success();
        }
        Response::error(500, 'Failed to update supplier');
    }
} 