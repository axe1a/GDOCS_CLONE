<?php  
require_once 'dbConfig.php';
require_once 'models.php';

// User Management
if (isset($_POST['insertNewUserBtn'])) {
	$username = trim($_POST['username']);
	$first_name = trim($_POST['first_name']);
	$last_name = trim($_POST['last_name']);
	$password = trim($_POST['password']);
	$confirm_password = trim($_POST['confirm_password']);

	if (!empty($username) && !empty($first_name) && !empty($last_name) && !empty($password) && !empty($confirm_password)) {
		if ($password == $confirm_password) {
			$insertQuery = insertNewUser($pdo, $username, password_hash($password, PASSWORD_DEFAULT), $first_name, $last_name, false); // Regular users
			$_SESSION['message'] = $insertQuery['message'];

			if ($insertQuery['status'] == '200') {
				$_SESSION['message'] = $insertQuery['message'];
				$_SESSION['status'] = $insertQuery['status'];
				header("Location: ../login.php");
			} else {
				$_SESSION['message'] = $insertQuery['message'];
				$_SESSION['status'] = $insertQuery['status'];
				header("Location: ../register.php");
			}
		} else {
			$_SESSION['message'] = "Please make sure both passwords are equal";
			$_SESSION['status'] = '400';
			header("Location: ../register.php");
		}
	} else {
		$_SESSION['message'] = "Please make sure there are no empty input fields";
		$_SESSION['status'] = '400';
		header("Location: ../register.php");
	}
}

if (isset($_POST['loginUserBtn'])) {
	$username = trim($_POST['username']);
	$password = trim($_POST['password']);

	if (!empty($username) && !empty($password)) {
		$loginQuery = checkIfUserExists($pdo, $username);
		
		if ($loginQuery['result']) {
			$userInfo = $loginQuery['userInfoArray'];
			
			// Check if user is suspended
			if (checkIfUserSuspended($pdo, $userInfo['user_id'])) {
				$_SESSION['message'] = "Your account has been suspended";
				$_SESSION['status'] = "400";
				header("Location: ../login.php");
				exit();
			}

			if (password_verify($password, $userInfo['password'])) {
				// Clear any existing session data
				session_unset();
				
				// Set new session variables
				$_SESSION['user_id'] = $userInfo['user_id'];
				$_SESSION['username'] = $userInfo['username'];
				$_SESSION['is_admin'] = $userInfo['is_admin'];
				
				// Redirect based on user type
				if ($userInfo['is_admin'] == 1) {
					header("Location: ../index.php");
				} else {
					header("Location: ../../user/index.php");
				}
				exit();
			} else {
				$_SESSION['message'] = "Username/password invalid";
				$_SESSION['status'] = "400";
				header("Location: ../login.php");
				exit();
			}
		} else {
			$_SESSION['message'] = "Username/password invalid";
			$_SESSION['status'] = "400";
			header("Location: ../login.php");
			exit();
		}
	} else {
		$_SESSION['message'] = "Please make sure there are no empty input fields";
		$_SESSION['status'] = '400';
		header("Location: ../login.php");
		exit();
	}
}

if (isset($_GET['logoutUserBtn'])) {
	unset($_SESSION['username']);
	unset($_SESSION['user_id']);
	unset($_SESSION['is_admin']);
	header("Location: ../login.php");
}

// Document Management
if (isset($_POST['createDocument'])) {
	if (isset($_SESSION['user_id'])) {
		$title = trim($_POST['title']);
		$content = trim($_POST['content']);
		
		if (!empty($title)) {
			if (createDocument($pdo, $title, $content, $_SESSION['user_id'])) {
				$_SESSION['message'] = "Document created successfully";
				$_SESSION['status'] = "200";
			} else {
				$_SESSION['message'] = "Failed to create document";
				$_SESSION['status'] = "400";
			}
		} else {
			$_SESSION['message'] = "Title is required";
			$_SESSION['status'] = "400";
		}
		header("Location: ../documents.php");
	}
}

if (isset($_POST['updateDocument'])) {
	if (isset($_SESSION['user_id'])) {
		$document_id = $_POST['document_id'];
		$content = $_POST['content'];
		
		// Check if user has edit permission
		$sql = "SELECT 1 FROM document_permissions 
				WHERE document_id = ? AND user_id = ? AND can_edit = true
				UNION
				SELECT 1 FROM documents 
				WHERE document_id = ? AND author_id = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$document_id, $_SESSION['user_id'], $document_id, $_SESSION['user_id']]);
		
		if ($stmt->rowCount() > 0) {
			if (updateDocument($pdo, $document_id, $content)) {
				// Log the activity
				logActivity($pdo, $document_id, $_SESSION['user_id'], 'Updated document content');
				echo json_encode(['status' => 'success']);
			} else {
				echo json_encode(['status' => 'error', 'message' => 'Failed to update document']);
			}
		} else {
			echo json_encode(['status' => 'error', 'message' => 'You do not have permission to edit this document']);
		}
	}
}

// Document Messages
if (isset($_POST['addMessage'])) {
	if (isset($_SESSION['user_id'])) {
		$document_id = $_POST['document_id'];
		$message = trim($_POST['message']);
		
		// Check if user has access to the document
		$sql = "SELECT 1 FROM document_permissions 
				WHERE document_id = ? AND user_id = ?
				UNION
				SELECT 1 FROM documents 
				WHERE document_id = ? AND author_id = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$document_id, $_SESSION['user_id'], $document_id, $_SESSION['user_id']]);
		
		if ($stmt->rowCount() > 0 && !empty($message)) {
			if (addDocumentMessage($pdo, $document_id, $_SESSION['user_id'], $message)) {
				echo json_encode(['status' => 'success']);
			} else {
				echo json_encode(['status' => 'error', 'message' => 'Failed to add message']);
			}
		} else {
			echo json_encode(['status' => 'error', 'message' => 'You do not have permission to add messages to this document']);
		}
	}
}

// Document Images
if (isset($_POST['addImage'])) {
	if (isset($_SESSION['user_id'])) {
		$document_id = $_POST['document_id'];
		$image_url = $_POST['image_url'];
		
		// Check if user has edit permission
		$sql = "SELECT 1 FROM document_permissions 
				WHERE document_id = ? AND user_id = ? AND can_edit = true
				UNION
				SELECT 1 FROM documents 
				WHERE document_id = ? AND author_id = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$document_id, $_SESSION['user_id'], $document_id, $_SESSION['user_id']]);
		
		if ($stmt->rowCount() > 0 && !empty($image_url)) {
			if (addDocumentImage($pdo, $document_id, $image_url, $_SESSION['user_id'])) {
				logActivity($pdo, $document_id, $_SESSION['user_id'], 'Added image to document');
				echo json_encode(['status' => 'success']);
			} else {
				echo json_encode(['status' => 'error', 'message' => 'Failed to add image']);
			}
		} else {
			echo json_encode(['status' => 'error', 'message' => 'You do not have permission to add images to this document']);
		}
	}
}

if (isset($_POST['newGigProposal'])) {
	echo insertNewGigProposal(	$pdo, 
								$_POST['gig_proposal_description'], 
								$_POST['gig_id'], 
								$_SESSION['user_id']
							);
}

if (isset($_POST['updateInterviewStatus'])) {
	echo updateInterviewStatus(	$pdo, 
							$_POST['status'], 
							$_POST['gig_interview_id']
						 );
}

if (isset($_POST['deleteGigProposal'])) {
	echo deleteGigProposal($pdo, $_POST['gig_proposal_id']);
}

// Handle Image Upload
if (isset($_POST['uploadImage'])) {
    if (isset($_SESSION['user_id'])) {
        $document_id = $_POST['document_id'] ?? null;
        $response = ['status' => 'error', 'message' => 'Invalid request'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $fileName = $file['name'];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Validate file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileType, $allowedTypes)) {
                // Generate unique filename
                $newFileName = uniqid() . '.' . $fileType;
                $uploadPath = '../uploads/' . $newFileName;
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $imageUrl = 'uploads/' . $newFileName;
                    
                    if ($document_id) {
                        // Add image to document_images table
                        if (addDocumentImage($pdo, $document_id, $imageUrl, $_SESSION['user_id'])) {
                            logActivity($pdo, $document_id, $_SESSION['user_id'], 'Added image to document');
                            $response = [
                                'status' => 'success',
                                'url' => $imageUrl,
                                'message' => 'Image uploaded successfully'
                            ];
                        } else {
                            $response = ['status' => 'error', 'message' => 'Failed to save image record'];
                        }
                    } else {
                        // For new documents that don't have an ID yet
                        $response = [
                            'status' => 'success',
                            'url' => $imageUrl,
                            'message' => 'Image uploaded successfully'
                        ];
                    }
                } else {
                    $response = ['status' => 'error', 'message' => 'Failed to move uploaded file'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes)];
            }
        }
        
        echo json_encode($response);
        exit();
    }
}

// Handle document search
if (isset($_POST['searchDocuments']) && isset($_POST['query'])) {
    $query = trim($_POST['query']);
    
    if (!empty($query)) {
        $results = searchDocuments($pdo, $_SESSION['user_id'], $query);
        echo json_encode([
            'status' => 'success',
            'results' => $results
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Search query is empty'
        ]);
    }
    exit();
}

// Handle document deletion
if (isset($_POST['deleteDocument'])) {
    if (isset($_SESSION['user_id'])) {
        $document_id = $_POST['document_id'];
        
        // Check if user is the author of the document
        $sql = "SELECT 1 FROM documents WHERE document_id = ? AND author_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$document_id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            // Delete associated records first
            $pdo->beginTransaction();
            try {
                // Delete document permissions
                $sql = "DELETE FROM document_permissions WHERE document_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$document_id]);
                
                // Delete document messages
                $sql = "DELETE FROM document_messages WHERE document_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$document_id]);
                
                // Delete document images
                $sql = "DELETE FROM document_images WHERE document_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$document_id]);
                
                // Delete activity logs
                $sql = "DELETE FROM activity_logs WHERE document_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$document_id]);
                
                // Finally delete the document
                $sql = "DELETE FROM documents WHERE document_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$document_id]);
                
                $pdo->commit();
                echo json_encode(['status' => 'success', 'message' => 'Document deleted successfully']);
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Error deleting document: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete document']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission to delete this document']);
        }
        exit();
    }
}

// Handle document permissions
if (isset($_POST['updatePermissions'])) {
    if (isset($_SESSION['user_id'])) {
        $document_id = $_POST['document_id'];
        $user_id = $_POST['user_id'];
        $can_edit = isset($_POST['can_edit']) && $_POST['can_edit'] == 1;
        
        // Check if user is the document owner
        $sql = "SELECT 1 FROM documents WHERE document_id = ? AND author_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$document_id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            // Check if permission already exists
            $sql = "SELECT 1 FROM document_permissions WHERE document_id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$document_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                // Update existing permission
                if (updateDocumentPermission($pdo, $document_id, $user_id, $can_edit)) {
                    logActivity($pdo, $document_id, $_SESSION['user_id'], 
                              'Updated permissions for user to ' . ($can_edit ? 'editor' : 'viewer'));
                    echo json_encode(['status' => 'success']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update permissions']);
                }
            } else {
                // Create new permission
                if (grantDocumentAccess($pdo, $document_id, $user_id, $can_edit)) {
                    logActivity($pdo, $document_id, $_SESSION['user_id'], 
                              'Granted ' . ($can_edit ? 'edit' : 'view') . ' access to user');
                    echo json_encode(['status' => 'success']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to grant permissions']);
                }
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission to modify document permissions']);
        }
        exit();
    }
}

// Admin-specific handlers
if (isset($_POST['getActivityLogs'])) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        $logs = getActivityLogs($pdo);
        echo json_encode([
            'status' => 'success',
            'logs' => $logs
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Unauthorized access'
        ]);
    }
    exit();
}

if (isset($_POST['toggleUserStatus'])) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        $userId = $_POST['user_id'];
        $suspend = $_POST['suspend'];
        
        if (toggleUserStatus($pdo, $userId, $suspend)) {
            // Log the action
            $action = $suspend ? 'suspended' : 'activated';
            logActivity($pdo, null, $_SESSION['user_id'], "Admin {$action} user ID: {$userId}");
            
            echo json_encode([
                'status' => 'success',
                'message' => 'User status updated successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update user status'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Unauthorized access'
        ]);
    }
    exit();
}


