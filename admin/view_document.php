<?php 
require_once 'core/dbConfig.php'; 
require_once 'core/models.php'; 

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../user/index.php");
    exit();
}

// Get document ID
$document_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$document_id) {
    header("Location: index.php");
    exit();
}

// Get document details
$document = getDocumentById($pdo, $document_id);
if (!$document) {
    header("Location: index.php");
    exit();
}

// Get document messages
$messages = getDocumentMessages($pdo, $document_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>View Document - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.cdnfonts.com/css/product-sans" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Product Sans', sans-serif;
            background-color: #f5f5f5;
        }
        .document-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        .chat-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: calc(100vh - 100px);
            overflow-y: auto;
        }
        .chat-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            background-color: white;
            border-radius: 10px 10px 0 0;
        }
        .chat-messages {
            padding: 20px;
            overflow-y: auto;
            max-height: calc(100vh - 200px);
        }
        .chat-message {
            padding: 10px 15px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        .document-content {
            font-size: 16px;
            line-height: 1.6;
        }
        .back-button {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="back-button">
            <a href="index.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="row">
            <!-- Document Content -->
            <div class="col-md-8">
                <div class="document-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-0"><?php echo htmlspecialchars($document['title']); ?></h2>
                            <small class="text-muted">Last updated: <?php echo date('M j, Y g:i A', strtotime($document['last_updated'])); ?></small>
                        </div>
                    </div>
                    <div class="document-content">
                        <?php echo $document['content']; ?>
                    </div>
                </div>
            </div>

            <!-- Chat History -->
            <div class="col-md-4">
                <div class="chat-container">
                    <div class="chat-header">
                        <h5 class="mb-0">Chat History</h5>
                    </div>
                    <div class="chat-messages">
                        <?php if (!empty($messages)): ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="chat-message">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <strong><?php echo htmlspecialchars($message['username']); ?></strong>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y g:i A', strtotime($message['timestamp'])); ?>
                                        </small>
                                    </div>
                                    <div class="mt-1">
                                        <?php echo htmlspecialchars($message['message']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted mt-4">
                                <p>No chat messages for this document</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 