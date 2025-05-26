<?php  

require_once 'dbConfig.php';


// User entity

function checkIfUserExists($pdo, $username) {
	$response = array();
	$sql = "SELECT * FROM users WHERE username = ?";
	$stmt = $pdo->prepare($sql);

	if ($stmt->execute([$username])) {

		$userInfoArray = $stmt->fetch();

		if ($stmt->rowCount() > 0) {
			$response = array(
				"result"=> true,
				"status" => "200",
				"userInfoArray" => $userInfoArray
			);
		}

		else {
			$response = array(
				"result"=> false,
				"status" => "400",
				"message"=> "User doesn't exist in the database"
			);
		}
	}

	return $response;

}

function checkIfUserSuspended($pdo, $user_id) {
	$sql = "SELECT is_suspended FROM users WHERE user_id = ?";
	$stmt = $pdo->prepare($sql);
	
	if ($stmt->execute([$user_id])) {
		$result = $stmt->fetch();
		return $result['is_suspended'] ?? false;
	}
	
	return false;
}

function insertNewUser($pdo, $username, $password, $first_name, $last_name, $is_admin = false) {
	$response = array();
	$checkIfUserExists = checkIfUserExists($pdo, $username); 

	if (!$checkIfUserExists['result']) {

		$sql = "INSERT INTO users (username, password, first_name, last_name, is_admin, is_suspended) 
		VALUES (?,?,?,?,?, false)";

		$stmt = $pdo->prepare($sql);

		if ($stmt->execute([$username, $password, $first_name, $last_name, $is_admin])) {
			$response = array(
				"status" => "200",
				"message" => "User successfully inserted!"
			);
		}

		else {
			$response = array(
				"status" => "400",
				"message" => "An error occurred with the query!"
			);
		}
	}

	else {
		$response = array(
			"status" => "400",
			"message" => "User already exists!"
		);
	}

	return $response;
}

function getAllUsers($pdo) {
	$sql = "SELECT * FROM fiverr_users";
	$stmt = $pdo->prepare($sql);
	$executeQuery = $stmt->execute();

	if ($executeQuery) {
		return $stmt->fetchAll();
	}
}

// Document Management Functions
function createDocument($pdo, $title, $content, $author_id) {
	$sql = "INSERT INTO documents (title, content, author_id) VALUES (?, ?, ?)";
	$stmt = $pdo->prepare($sql);
	return $stmt->execute([$title, $content, $author_id]);
}

function updateDocument($pdo, $document_id, $content) {
	try {
		$sql = "UPDATE documents SET content = ? WHERE document_id = ?";
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute([$content, $document_id]);
		
		if ($result) {
			return true;
		} else {
			error_log("Failed to update document: " . implode(", ", $stmt->errorInfo()));
			return false;
		}
	} catch (PDOException $e) {
		error_log("Database error in updateDocument: " . $e->getMessage());
		return false;
	}
}

function updateDocumentTitle($pdo, $document_id, $title) {
	try {
		$sql = "UPDATE documents SET title = ? WHERE document_id = ?";
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute([$title, $document_id]);
		
		if ($result) {
			return true;
		} else {
			error_log("Failed to update document title: " . implode(", ", $stmt->errorInfo()));
			return false;
		}
	} catch (PDOException $e) {
		error_log("Database error in updateDocumentTitle: " . $e->getMessage());
		return false;
	}
}

function getDocumentById($pdo, $document_id) {
	$sql = "SELECT * FROM documents WHERE document_id = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$document_id]);
	return $stmt->fetch();
}

function getUserDocuments($pdo, $user_id) {
	$sql = "SELECT d.* FROM documents d 
			LEFT JOIN document_permissions dp ON d.document_id = dp.document_id 
			WHERE d.author_id = ? OR dp.user_id = ?
			ORDER BY d.last_updated DESC";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$user_id, $user_id]);
	return $stmt->fetchAll();
}

// Document Permissions Functions
function grantDocumentAccess($pdo, $document_id, $user_id, $can_edit = false) {
	$sql = "INSERT INTO document_permissions (document_id, user_id, can_edit) VALUES (?, ?, ?)";
	$stmt = $pdo->prepare($sql);
	return $stmt->execute([$document_id, $user_id, $can_edit]);
}

function updateDocumentPermission($pdo, $document_id, $user_id, $can_edit) {
	$sql = "UPDATE document_permissions SET can_edit = ? WHERE document_id = ? AND user_id = ?";
	$stmt = $pdo->prepare($sql);
	return $stmt->execute([$can_edit, $document_id, $user_id]);
}

// Activity Logging Functions
function logActivity($pdo, $document_id, $user_id, $action) {
	$sql = "INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, ?)";
	$stmt = $pdo->prepare($sql);
	return $stmt->execute([$document_id, $user_id, $action]);
}

function getDocumentActivityLogs($pdo, $document_id) {
	$sql = "SELECT al.*, u.username 
			FROM activity_logs al 
			JOIN users u ON al.user_id = u.user_id 
			WHERE al.document_id = ? 
			ORDER BY al.timestamp DESC";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$document_id]);
	return $stmt->fetchAll();
}

// Get user's recent activity logs
function getUserActivityLogs($pdo, $user_id) {
    $sql = "SELECT al.*, u.username, d.title as document_title
            FROM activity_logs al 
            JOIN users u ON al.user_id = u.user_id 
            JOIN documents d ON al.document_id = d.document_id
            WHERE al.user_id = ? 
            ORDER BY al.timestamp DESC 
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Document Messages Functions
function addDocumentMessage($pdo, $document_id, $user_id, $message) {
	$sql = "INSERT INTO document_messages (document_id, user_id, message) VALUES (?, ?, ?)";
	$stmt = $pdo->prepare($sql);
	return $stmt->execute([$document_id, $user_id, $message]);
}

function getDocumentMessages($pdo, $document_id) {
	$sql = "SELECT dm.*, u.username 
			FROM document_messages dm 
			JOIN users u ON dm.user_id = u.user_id 
			WHERE dm.document_id = ? 
			ORDER BY dm.timestamp ASC";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$document_id]);
	return $stmt->fetchAll();
}

// Document Images Functions
function addDocumentImage($pdo, $document_id, $image_url, $added_by) {
	$sql = "INSERT INTO document_images (document_id, image_url, added_by) VALUES (?, ?, ?)";
	$stmt = $pdo->prepare($sql);
	return $stmt->execute([$document_id, $image_url, $added_by]);
}

function getDocumentImages($pdo, $document_id) {
	$sql = "SELECT di.*, u.username 
			FROM document_images di 
			JOIN users u ON di.added_by = u.user_id 
			WHERE di.document_id = ? 
			ORDER BY di.timestamp DESC";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$document_id]);
	return $stmt->fetchAll();
}

// Document Search Function
function searchDocuments($pdo, $user_id, $query) {
    $sql = "SELECT d.* FROM documents d 
            LEFT JOIN document_permissions dp ON d.document_id = dp.document_id 
            WHERE (d.author_id = ? OR dp.user_id = ?) 
            AND d.title LIKE ? 
            ORDER BY d.last_updated DESC 
            LIMIT 10";
    
    $searchQuery = "%" . $query . "%";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id, $searchQuery]);
    return $stmt->fetchAll();
}

// Get all users except the current user and admin users
function getAvailableUsers($pdo, $current_user_id) {
    $sql = "SELECT user_id, username, first_name, last_name 
            FROM users 
            WHERE user_id != ? AND is_admin = 0 AND is_suspended = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$current_user_id]);
    return $stmt->fetchAll();
}

// Get document permissions for all users
function getDocumentPermissions($pdo, $document_id) {
    $sql = "SELECT dp.*, u.username, u.first_name, u.last_name 
            FROM document_permissions dp 
            JOIN users u ON dp.user_id = u.user_id 
            WHERE dp.document_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$document_id]);
    return $stmt->fetchAll();
}

// Remove document permission
function removeDocumentPermission($pdo, $document_id, $user_id) {
    $sql = "DELETE FROM document_permissions WHERE document_id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$document_id, $user_id]);
}