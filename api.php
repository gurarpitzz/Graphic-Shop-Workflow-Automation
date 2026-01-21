<?php
// server/api.php v3.2
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

function jsonResponse($success, $message = '', $data = []) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

try {
    require 'db_connect.php';
    $action = $_REQUEST['action'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if ($action === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            jsonResponse(true, 'Login Success', ['user' => $user]);
        } else {
            jsonResponse(false, 'Invalid Credentials');
        }
    }
    
    if ($action === 'create_order' || $action === 'update_order') {
        $customerName = $_POST['customer_name'] ?? '';
        $mobile = $_POST['mobile_number'] ?? '';
        $type = $_POST['project_type'] ?? '';
        $source = $_POST['source'] ?? 'In-Person';
        $desc = $_POST['design_description'] ?? '';
        $wa = $_POST['whatsapp_link'] ?? '';
        $tg = $_POST['telegram_link'] ?? '';
        $mail = $_POST['mail_link'] ?? '';
        $mat = $_POST['material_used'] ?? '';
        $sz = $_POST['sizes'] ?? '';
        $total = $_POST['total_amount'] ?? 0;
        $advance = $_POST['advance_paid'] ?? 0;
        $billNo = $_POST['bill_number'] ?? '';
        $id = $_POST['id'] ?? null;
        $isQuote = ($total <= 0); // If no amount, it's a rate request (noise potential)

        // Customer Logic
        $stmtC = $pdo->prepare("SELECT id FROM customers WHERE mobile_number = ?");
        $stmtC->execute([$mobile]);
        $custId = $stmtC->fetchColumn();

        if (!$custId && !empty($mobile)) {
            $code = "CUST-" . date('Y') . "-" . rand(1000, 9999);
            $stmtNewC = $pdo->prepare("INSERT INTO customers (cust_id, name, mobile_number) VALUES (?, ?, ?)");
            $stmtNewC->execute([$code, $customerName, $mobile]);
            $custId = $pdo->lastInsertId();
        }

        if ($custId) {
            if ($isQuote) {
                $pdo->prepare("UPDATE customers SET rate_request_count = rate_request_count + 1 WHERE id = ?")->execute([$custId]);
            } else {
                $pdo->prepare("UPDATE customers SET actual_order_count = actual_order_count + 1 WHERE id = ?")->execute([$custId]);
            }
        }

        // Image/Media Logic
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $mainThumb = null;
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
            $filename = uniqid() . '_' . $_FILES['thumbnail']['name'];
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadDir . $filename)) {
                $mainThumb = 'uploads/' . $filename;
            }
        }

        if ($action === 'create_order') {
            $orderCode = "ORD-" . date('Y') . "-" . rand(100, 999);
            $stmt = $pdo->prepare("INSERT INTO orders (customer_id, order_id_code, source, project_type, design_description, whatsapp_link, telegram_link, mail_link, material_used, sizes, total_amount, advance_paid, bill_number, thumbnail_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$custId, $orderCode, $source, $type, $desc, $wa, $tg, $mail, $mat, $sz, $total, $advance, $billNo, $mainThumb]);
            $orderId = $pdo->lastInsertId();
            
            $milestones = [['Order Initiation',null],['Site Inspection','Installation'],['Designing','Designing'],['Client Approval','Designing'],['Execution: Printing','Printing'],['Execution: Fabrication','Fabrication'],['Assembly','Fabrication'],['Installation','Installation'],['Billing','Accountant'],['Closed',null]];
            $stMil = $pdo->prepare("INSERT INTO order_stages (order_id, stage_name, stage_index) VALUES (?, ?, ?)");
            foreach ($milestones as $idx => $m) { $stMil->execute([$orderId, $m[0], $idx + 1]); }
            jsonResponse(true, 'Order Created', ['order_id' => $orderId, 'code' => $orderCode]);
        } else {
            $sql = "UPDATE orders SET customer_id=?, project_type=?, design_description=?, whatsapp_link=?, telegram_link=?, mail_link=?, material_used=?, sizes=?, total_amount=?, advance_paid=?, bill_number=? ";
            $par = [$custId, $type, $desc, $wa, $tg, $mail, $mat, $sz, $total, $advance, $billNo];
            if ($mainThumb) { $sql .= ", thumbnail_path=? "; $par[] = $mainThumb; }
            $sql .= " WHERE id=?"; $par[] = $id;
            $pdo->prepare($sql)->execute($par);
            
            // Multiple Media Upload
            if (isset($_FILES['extra_media'])) {
                foreach ($_FILES['extra_media']['tmp_name'] as $k => $tmpName) {
                    if ($_FILES['extra_media']['error'][$k] === 0) {
                        $fname = uniqid() . '_' . $_FILES['extra_media']['name'][$k];
                        if (move_uploaded_file($tmpName, $uploadDir . $fname)) {
                            $pdo->prepare("INSERT INTO order_media (order_id, file_path, file_type) VALUES (?, ?, 'Other')")->execute([$id, 'uploads/' . $fname]);
                        }
                    }
                }
            }
            jsonResponse(true, 'Order Updated');
        }
    }

    if ($action === 'update_stage') {
        $stageId = $_POST['stage_id'] ?? 0;
        $completed = $_POST['completed'] ?? 0;
        $userId = $_POST['user_id'] ?? 0;
        $pdo->prepare("UPDATE order_stages SET is_completed = ?, completed_by = ?, completed_at = NOW() WHERE id = ?")->execute([$completed, $userId, $stageId]);
        
        $sth = $pdo->prepare("SELECT order_id FROM order_stages WHERE id = ?"); $sth->execute([$stageId]); $orderId = $sth->fetchColumn();
        $nextStage = $pdo->prepare("SELECT stage_name FROM order_stages WHERE order_id = ? AND is_completed = 0 ORDER BY stage_index ASC LIMIT 1");
        $nextStage->execute([$orderId]);
        $status = $nextStage->fetchColumn() ?: 'Order Closed';
        $pdo->prepare("UPDATE orders SET current_stage_name = ?, last_updated_by = ?, is_closed = ? WHERE id = ?")->execute([$status, $userId, ($status === 'Order Closed' ? 1 : 0), $orderId]);
        jsonResponse(true, 'Stage Updated', ['status' => $status]);
    }

    if ($action === 'archive_order') {
        $id = $_POST['id'] ?? 0;
        $pdo->prepare("UPDATE orders SET is_archived = 1 WHERE id = ?")->execute([$id]);
        jsonResponse(true, 'Order Archived');
    }

    if ($action === 'delete_order') {
        $id = $_POST['id'] ?? 0;
        $userId = $_POST['user_id'] ?? 0;

        // Verify User Role
        $stmtUser = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch();

        if (!$user || !in_array($user['role'], ['admin', 'accountant'])) {
            jsonResponse(false, 'Unauthorized: Only Admin or Accountant can delete orders.');
        }

        // Deletion (Cascading handles order_media and order_stages)
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        
        jsonResponse(true, 'Order permanently deleted.');
    }

    if ($action === 'add_user') {
        $username = strtolower($_POST['username'] ?? '');
        $role = $_POST['role'] ?? 'worker';
        $domain = $_POST['domain'] ?? '';
        $adminId = $_POST['admin_id'] ?? 0;

        // Admin Only
        $st = $pdo->prepare("SELECT role FROM users WHERE id=?"); $st->execute([$adminId]);
        if ($st->fetchColumn() !== 'admin') jsonResponse(false, 'Unauthorized');

        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, domain) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $role, $domain]);
        jsonResponse(true, 'User Created. Default password: password123');
    }

    if ($action === 'delete_user') {
        $targetId = $_POST['target_id'] ?? 0;
        $adminId = $_POST['admin_id'] ?? 0;
        if ($targetId == $adminId) jsonResponse(false, "Cannot delete yourself");

        $st = $pdo->prepare("SELECT role FROM users WHERE id=?"); $st->execute([$adminId]);
        if ($st->fetchColumn() !== 'admin') jsonResponse(false, 'Unauthorized');

        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
        jsonResponse(true, 'User Removed');
    }

    if ($action === 'assign_task') {
        $stageId = $_POST['stage_id'] ?? 0;
        $workerId = $_POST['worker_id'] ?? null;
        $adminId = $_POST['admin_id'] ?? 0;

        $st = $pdo->prepare("SELECT role FROM users WHERE id=?"); $st->execute([$adminId]);
        if ($st->fetchColumn() !== 'admin') jsonResponse(false, 'Unauthorized');

        $pdo->prepare("UPDATE order_stages SET responsible_person_id = ? WHERE id = ?")->execute([($workerId == 'null' ? null : $workerId), $stageId]);
        jsonResponse(true, 'Responsibility Assigned');
    }

} else {
    if ($action === 'fetch_all_users') {
        $stmt = $pdo->query("SELECT id, username, role, domain FROM users ORDER BY role DESC, username ASC");
        jsonResponse(true, '', ['users' => $stmt->fetchAll()]);
    }
    if ($action === 'fetch_orders') {
        $archived = $_GET['archived'] ?? 0;
        $stmt = $pdo->prepare("SELECT o.*, c.name as customer_name, c.mobile_number, c.cust_id as customer_code,
                               (SELECT s.responsible_person_id FROM order_stages s WHERE s.order_id = o.id AND s.is_completed = 0 ORDER BY s.stage_index ASC LIMIT 1) as current_responsible_id
                               FROM orders o LEFT JOIN customers c ON o.customer_id = c.id 
                               WHERE o.is_archived = ? ORDER BY o.created_at DESC");
        $stmt->execute([$archived]);
        jsonResponse(true, '', ['orders' => $stmt->fetchAll()]);
    }
    if ($action === 'fetch_stages') {
        $orderId = $_GET['order_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT s.*, u.username as completed_by_name FROM order_stages s LEFT JOIN users u ON s.completed_by = u.id WHERE s.order_id = ? ORDER BY s.stage_index ASC");
        $stmt->execute([$orderId]);
        jsonResponse(true, '', ['stages' => $stmt->fetchAll()]);
    }
    if ($action === 'fetch_media') {
        $orderId = $_GET['order_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM order_media WHERE order_id = ?");
        $stmt->execute([$orderId]);
        jsonResponse(true, '', ['media' => $stmt->fetchAll()]);
    }
    if ($action === 'fetch_customers') {
        $stmt = $pdo->query("SELECT * FROM customers ORDER BY actual_order_count DESC");
        jsonResponse(true, '', ['customers' => $stmt->fetchAll()]);
    }
    
    // Explicit Fallback
    jsonResponse(false, 'Invalid or missing action: ' . $action);
    }
} catch (Throwable $e) {
    $dbName = 'Unknown';
    try { $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn(); } catch(Exception $ex) {}
    jsonResponse(false, "Server Error [DB:$dbName]: " . $e->getMessage() . " on line " . $e->getLine());
}
