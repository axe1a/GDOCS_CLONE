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

// Get activity logs
$activityLogs = getActivityLogs($pdo);
// Get all documents with messages
$allDocuments = getAllDocumentsWithMessages($pdo);
// Get all users with their status
$users = getAllUsers($pdo);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <link href="https://fonts.cdnfonts.com/css/product-sans" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
      body {
        font-family: 'Product Sans', sans-serif;
        background-color: #f5f5f5;
      }
      .card {
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
      }
      .card-header {
        background-color: #fff;
        border-bottom: 1px solid #eee;
        padding: 15px 20px;
      }
      .activity-log {
        max-height: 400px;
        overflow-y: auto;
      }
      .activity-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
      }
      .activity-item:last-child {
        border-bottom: none;
      }
      .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
      }
      .status-active {
        background-color: #28a745;
        color: white;
      }
      .status-suspended {
        background-color: #dc3545;
        color: white;
      }
      .chat-messages {
        max-height: 200px;
        overflow-y: auto;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 5px;
      }
      .chat-message {
        padding: 8px;
        margin-bottom: 8px;
        border-radius: 5px;
        background-color: white;
      }
      .document-section {
        margin-bottom: 30px;
        border-bottom: 1px solid #eee;
        padding-bottom: 20px;
      }
      .document-section:last-child {
        border-bottom: none;
      }
    </style>
    <title>Admin Dashboard - Google Docs Clone</title>
  </head>
  <body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid py-4">
      <div class="row">
        <!-- Activity Logs Section -->
        <div class="col-md-3">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Activity Logs</h5>
              <button class="btn btn-sm btn-outline-primary" onclick="refreshActivityLogs()">
                <i class="fas fa-sync-alt"></i>
              </button>
            </div>
            <div class="card-body activity-log" id="activityLogContainer">
              <?php foreach ($activityLogs as $log): ?>
                <div class="activity-item">
                  <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($log['timestamp'])); ?></small>
                  <p class="mb-0">
                    <strong><?php echo htmlspecialchars($log['username']); ?></strong>
                    <?php echo htmlspecialchars($log['action']); ?>
                  </p>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Documents Section -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">All Documents</h5>
              <div class="form-inline">
                <select class="form-control form-control-sm" id="userFilter" onchange="filterDocuments()">
                  <option value="">All Users</option>
                  <?php
                    $uniqueUsers = array();
                    foreach ($allDocuments as $doc) {
                      if (!in_array($doc['username'], $uniqueUsers)) {
                        $uniqueUsers[] = $doc['username'];
                        echo '<option value="' . htmlspecialchars($doc['username']) . '">' . 
                             htmlspecialchars($doc['username']) . '</option>';
                      }
                    }
                  ?>
                </select>
              </div>
            </div>
            <div class="card-body">
                <?php foreach ($allDocuments as $doc): ?>
                  <div class="document-section">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <h5 class="mb-0"><?php echo htmlspecialchars($doc['title']); ?></h5>
                        <small class="text-muted">
                          By: <?php echo htmlspecialchars($doc['username']); ?> | 
                          Last updated: <?php echo date('M j, Y g:i A', strtotime($doc['last_updated'])); ?>
                        </small>
                      </div>
                      <a href="view_document.php?id=<?php echo $doc['document_id']; ?>" class="btn btn-primary">
                        <i class="fas fa-eye"></i> View
                      </a>
                    </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- User Management Section -->
        <div class="col-md-3">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">User Management</h5>
            </div>
            <div class="card-body">
              <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                  <a class="nav-link active" id="active-tab" data-toggle="tab" href="#active">Active</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="suspended-tab" data-toggle="tab" href="#suspended">Suspended</a>
                </li>
              </ul>
              <div class="tab-content">
                <div class="tab-pane fade show active" id="active">
                  <?php foreach ($users as $user): ?>
                    <?php if (!$user['is_suspended']): ?>
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                          <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                          <br>
                          <small class="text-muted"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></small>
                        </div>
                        <button class="btn btn-sm btn-danger" onclick="toggleUserStatus(<?php echo $user['user_id']; ?>, 1)">
                          Suspend
                        </button>
                      </div>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
                <div class="tab-pane fade" id="suspended">
                  <?php foreach ($users as $user): ?>
                    <?php if ($user['is_suspended']): ?>
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                          <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                          <br>
                          <small class="text-muted"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></small>
                        </div>
                        <button class="btn btn-sm btn-success" onclick="toggleUserStatus(<?php echo $user['user_id']; ?>, 0)">
                          Activate
                        </button>
                      </div>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      function refreshActivityLogs() {
        $.ajax({
          url: 'core/handleForms.php',
          type: 'POST',
          data: { getActivityLogs: 1 },
          success: function(response) {
            try {
              const data = JSON.parse(response);
              if (data.status === 'success') {
                let html = '';
                data.logs.forEach(log => {
                  html += `
                    <div class="activity-item">
                      <small class="text-muted">${new Date(log.timestamp).toLocaleString()}</small>
                      <p class="mb-0">
                        <strong>${log.username}</strong>
                        ${log.action}
                      </p>
                    </div>
                  `;
                });
                $('#activityLogContainer').html(html);
              }
            } catch (e) {
              console.error('Error parsing response:', e);
            }
          }
        });
      }

      function toggleUserStatus(userId, suspend) {
        if (confirm('Are you sure you want to ' + (suspend ? 'suspend' : 'activate') + ' this user?')) {
          $.ajax({
            url: 'core/handleForms.php',
            type: 'POST',
            data: {
              toggleUserStatus: 1,
              user_id: userId,
              suspend: suspend
            },
            success: function(response) {
              try {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                  location.reload();
                } else {
                  alert(data.message || 'Error updating user status');
                }
              } catch (e) {
                console.error('Error parsing response:', e);
              }
            }
          });
        }
      }

      // Refresh activity logs every 30 seconds
      setInterval(refreshActivityLogs, 30000);

      function filterDocuments() {
        const selectedUser = document.getElementById('userFilter').value;
        const documentSections = document.querySelectorAll('.document-section');
        
        documentSections.forEach(section => {
          const username = section.querySelector('.text-muted').textContent.split('By: ')[1].split(' |')[0].trim();
          if (selectedUser === '' || username === selectedUser) {
            section.style.display = 'block';
          } else {
            section.style.display = 'none';
          }
        });
      }
    </script>
  </body>
</html>