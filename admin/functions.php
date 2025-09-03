function isAdminLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function logAdminAction($action_type, $target_user_id = null, $description = '') {
    global $conn;
    if (isAdminLoggedIn()) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $stmt = $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, target_user_id, description, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isiss", $_SESSION['user_id'], $action_type, $target_user_id, $description, $ip_address);
        $stmt->execute();
    }
}