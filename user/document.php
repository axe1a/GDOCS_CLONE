<?php 
require_once 'core/dbConfig.php'; 
require_once 'core/models.php'; 

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
}

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    header("Location: ../admin/index.php");
}

// Check if document ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$document_id = $_GET['id'];
$document = getDocumentById($pdo, $document_id);

// Check if document exists and user has access
$hasAccess = false;
$canEdit = false;

if ($document) {
    if ($document['author_id'] == $_SESSION['user_id']) {
        $hasAccess = true;
        $canEdit = true;
    } else {
        // Check permissions
        $sql = "SELECT can_edit FROM document_permissions 
                WHERE document_id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$document_id, $_SESSION['user_id']]);
        $permission = $stmt->fetch();
        
        if ($permission) {
            $hasAccess = true;
            $canEdit = $permission['can_edit'];
        }
    }
}

if (!$hasAccess) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['content']) && $canEdit) {
        $content = trim($_POST['content']);
        if (updateDocument($pdo, $document_id, $content)) {
            logActivity($pdo, $document_id, $_SESSION['user_id'], 'Updated document content');
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update document']);
        }
        exit();
    }
    
    if (isset($_POST['title']) && $canEdit) {
        $title = trim($_POST['title']);
        if (!empty($title)) {
            if (updateDocumentTitle($pdo, $document_id, $title)) {
                logActivity($pdo, $document_id, $_SESSION['user_id'], 'Updated document title');
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update title']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Title cannot be empty']);
        }
        exit();
    }
}

// Get document messages
$messages = getDocumentMessages($pdo, $document_id);

// Get document activity logs
$activityLogs = getDocumentActivityLogs($pdo, $document_id);

// Get document images
$documentImages = getDocumentImages($pdo, $document_id);

// Get document permissions if user is owner
$documentPermissions = [];
$availableUsers = [];
if ($document['author_id'] == $_SESSION['user_id']) {
    $documentPermissions = getDocumentPermissions($pdo, $document_id);
    $availableUsers = getAvailableUsers($pdo, $_SESSION['user_id']);
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.cdnfonts.com/css/product-sans" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
      body {
        font-family: 'Product Sans', sans-serif;
        background-color: #f5f5f5;
      }
      .document-title {
        font-size: 2rem;
        font-weight: 500;
        margin-bottom: 1rem;
      }
      <?php if ($canEdit): ?>
      .document-title:hover {
        background: rgba(66, 133, 244, 0.1);
        border-radius: 4px;
        cursor: text;
      }
      .title-input {
        font-size: 2rem;
        font-weight: 500;
        margin-bottom: 1rem;
        width: 100%;
        border: none;
        border-radius: 4px;
        padding: 0.25rem 0.5rem;
        background: white;
        box-shadow: 0 0 0 2px #4285f4;
      }
      .title-input:focus {
        outline: none;
      }
      <?php endif; ?>
      .editor-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px;
        min-height: calc(100vh - 250px);
      }
      .sidebar {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px;
        height: calc(100vh - 100px);
        position: sticky;
        top: 20px;
        overflow-y: auto;
      }
      .message-container {
        max-height: 300px;
        overflow-y: auto;
      }
      .message {
        border-left: 3px solid #4285f4;
        padding: 10px;
        margin-bottom: 10px;
        background: #f8f9fa;
      }
      .image-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 20px;
      }
      .image-item {
        position: relative;
        padding-top: 100%;
      }
      .image-item img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 4px;
        cursor: pointer;
      }
      .image-preview {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.9);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
      }
      .image-preview img {
        max-width: 90%;
        max-height: 90vh;
      }
      .image-preview .close {
        position: absolute;
        top: 20px;
        right: 20px;
        color: white;
        font-size: 30px;
        cursor: pointer;
      }
      .activity-log {
        font-size: 0.9rem;
        padding: 5px 0;
        border-bottom: 1px solid #eee;
      }
      .btn-save {
        background-color: #4285f4;
        color: white;
        border-radius: 30px;
        padding: 8px 20px;
        font-weight: 500;
      }
      .btn-save:hover {
        background-color: #2b76f5;
        color: white;
      }
      .toolbar {
        padding: 10px;
        background: #f8f9fa;
        border-radius: 4px;
        margin-bottom: 10px;
      }
      .toolbar button {
        margin-right: 5px;
        padding: 5px 10px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
      }
      .toolbar button:hover {
        background: #e9ecef;
      }
      #editor {
        min-height: 500px;
        padding: 15px;
        overflow-y: auto;
      }
      #imageUpload {
        display: none;
      }
      .permissions-section {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
      }
      .permission-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px;
        margin-bottom: 8px;
        background: #f8f9fa;
        border-radius: 4px;
      }
      .permission-item:hover {
        background: #e9ecef;
      }
      .user-select {
        width: 100%;
        margin-bottom: 10px;
      }
      .permission-controls {
        display: flex;
        gap: 8px;
      }
    </style>
    <title><?php echo htmlspecialchars($document['title']); ?></title>
  </head>
  <body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
      <div class="row">
        <!-- Main Content -->
        <div class="col-md-9">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <?php if ($canEdit): ?>
              <h1 class="document-title" id="documentTitle" ondblclick="makeEditable(this)"><?php echo htmlspecialchars($document['title']); ?></h1>
            <?php else: ?>
              <h1 class="document-title"><?php echo htmlspecialchars($document['title']); ?></h1>
            <?php endif; ?>
            <?php if ($canEdit): ?>
              <button type="button" class="btn btn-save" id="saveBtn">Save Changes</button>
            <?php endif; ?>
          </div>

          <div class="editor-container">
            <div class="toolbar">
              <button onclick="document.execCommand('bold')" <?php echo !$canEdit ? 'disabled' : ''; ?>><i class="fas fa-bold"></i></button>
              <button onclick="document.execCommand('italic')" <?php echo !$canEdit ? 'disabled' : ''; ?>><i class="fas fa-italic"></i></button>
              <button onclick="document.execCommand('underline')" <?php echo !$canEdit ? 'disabled' : ''; ?>><i class="fas fa-underline"></i></button>
              <button onclick="document.execCommand('insertUnorderedList')" <?php echo !$canEdit ? 'disabled' : ''; ?>><i class="fas fa-list-ul"></i></button>
              <button onclick="document.execCommand('insertOrderedList')" <?php echo !$canEdit ? 'disabled' : ''; ?>><i class="fas fa-list-ol"></i></button>
              <?php if ($canEdit): ?>
                <button onclick="$('#imageUpload').click()"><i class="fas fa-image"></i></button>
                <input type="file" id="imageUpload" accept="image/*">
              <?php endif; ?>
            </div>
            <div id="editor" class="form-control" <?php echo $canEdit ? 'contenteditable="true"' : 'readonly'; ?>>
              <?php echo $document['content']; ?>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-3">
          <div class="sidebar">
            <!-- Images Section -->
            <h5 class="mb-3">Document Images</h5>
            <div class="image-grid mb-4">
              <?php foreach ($documentImages as $image): ?>
                <div class="image-item">
                  <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                       alt="Document image" 
                       onclick="previewImage('<?php echo htmlspecialchars($image['image_url']); ?>')">
                </div>
              <?php endforeach; ?>
            </div>

            <?php if ($document['author_id'] == $_SESSION['user_id']): ?>
            <!-- Permissions Section -->
            <div class="permissions-section">
              <h5 class="mb-3">Share with Users</h5>
              
              <!-- Add new user permission -->
              <div class="mb-4">
                <select class="form-control user-select" id="userSelect">
                  <option value="">Select a user...</option>
                  <?php foreach ($availableUsers as $user): ?>
                    <?php 
                    // Skip users who already have permissions
                    $hasPermission = false;
                    foreach ($documentPermissions as $perm) {
                        if ($perm['user_id'] == $user['user_id']) {
                            $hasPermission = true;
                            break;
                        }
                    }
                    if (!$hasPermission):
                    ?>
                    <option value="<?php echo $user['user_id']; ?>">
                      <?php echo htmlspecialchars($user['username']); ?> 
                      (<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>)
                    </option>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </select>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" id="canEdit">
                  <label class="form-check-label" for="canEdit">
                    Can edit document
                  </label>
                </div>
                <button class="btn btn-primary btn-sm" id="addPermission">Add User</button>
              </div>

              <!-- Current permissions -->
              <h6 class="mb-2">Current Access</h6>
              <div id="permissionsList">
                <?php foreach ($documentPermissions as $permission): ?>
                  <div class="permission-item" data-user-id="<?php echo $permission['user_id']; ?>">
                    <div>
                      <strong><?php echo htmlspecialchars($permission['username']); ?></strong>
                      <br>
                      <small class="text-muted">
                        <?php echo $permission['can_edit'] ? 'Can edit' : 'Can view'; ?>
                      </small>
                    </div>
                    <div class="permission-controls">
                      <button class="btn btn-outline-danger btn-sm remove-permission">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Messages Section -->
            <h5 class="mb-3">Messages</h5>
            <div class="message-container mb-4">
              <?php foreach ($messages as $message): ?>
                <div class="message">
                  <strong><?php echo htmlspecialchars($message['username']); ?></strong>
                  <small class="text-muted float-right">
                    <?php echo date('M j, g:i a', strtotime($message['timestamp'])); ?>
                  </small>
                  <p class="mb-0"><?php echo htmlspecialchars($message['message']); ?></p>
                </div>
              <?php endforeach; ?>
            </div>

            <form id="messageForm" class="mb-4">
              <div class="form-group">
                <textarea class="form-control" id="newMessage" rows="2" placeholder="Type a message..."></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-sm float-right">Send</button>
            </form>

            <hr>

            <!-- Activity Log Section -->
            <h5 class="mb-3">Activity Log</h5>
            <div class="activity-container">
              <?php foreach ($activityLogs as $log): ?>
                <div class="activity-log">
                  <small class="text-muted">
                    <?php echo date('M j, g:i a', strtotime($log['timestamp'])); ?>
                  </small>
                  <br>
                  <strong><?php echo htmlspecialchars($log['username']); ?></strong>
                  <?php echo htmlspecialchars($log['action']); ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Image Preview Modal -->
    <div class="image-preview" id="imagePreview">
      <span class="close" onclick="closePreview()">&times;</span>
      <img id="previewImg" src="" alt="Preview">
    </div>

    <!-- Required JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/your-kit-code.js"></script>

    <script>
      $(document).ready(function() {
        let contentChanged = false;
        
        // Make content editable if user has permission
        <?php if ($canEdit): ?>
          $('#editor').attr('contenteditable', 'true');
          
          // Track changes
          $('#editor').on('input', function() {
            contentChanged = true;
          });

          // Auto-save every 30 seconds if there are changes
          setInterval(function() {
            if (contentChanged) {
              saveDocument();
            }
          }, 30000);

          // Save button click
          $('#saveBtn').click(function() {
            saveDocument();
          });

          function saveDocument() {
            $.ajax({
              type: 'POST',
              url: 'document.php?id=<?php echo $document_id; ?>',
              data: {
                content: $('#editor').html()
              },
              success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                  contentChanged = false;
                  // Show success feedback
                  const $btn = $('#saveBtn');
                  const originalHtml = $btn.html();
                  $btn.html('<i class="fas fa-check"></i> Saved').addClass('btn-success').removeClass('btn-save');
                  setTimeout(() => {
                    $btn.html(originalHtml).removeClass('btn-success').addClass('btn-save');
                  }, 2000);
                } else {
                  alert(data.message || 'Error saving document');
                }
              },
              error: function() {
                alert('Error saving document. Please try again.');
              }
            });
          }

          // Title editing functions
          window.makeEditable = function(element) {
            const currentTitle = element.textContent;
            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentTitle;
            input.className = 'title-input';
            element.replaceWith(input);
            input.focus();
            
            // Save on enter
            input.addEventListener('keypress', function(e) {
              if (e.key === 'Enter') {
                saveTitle(input);
              }
            });
            
            // Save on blur
            input.addEventListener('blur', function() {
              saveTitle(input);
            });
          }

          function saveTitle(input) {
            const newTitle = input.value.trim();
            if (!newTitle) {
              alert('Title cannot be empty');
              input.focus();
              return;
            }

            $.ajax({
              type: 'POST',
              url: 'document.php?id=<?php echo $document_id; ?>',
              data: {
                title: newTitle
              },
              success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                  const h1 = document.createElement('h1');
                  h1.className = 'document-title';
                  h1.id = 'documentTitle';
                  h1.setAttribute('ondblclick', 'makeEditable(this)');
                  h1.textContent = newTitle;
                  input.replaceWith(h1);
                  // Update page title
                  document.title = newTitle;
                } else {
                  alert(data.message || 'Error saving title');
                }
              },
              error: function() {
                alert('Error saving title. Please try again.');
              }
            });
          }

          // Handle image upload
          $('#imageUpload').change(function() {
            const file = this.files[0];
            if (file) {
              const formData = new FormData();
              formData.append('image', file);
              formData.append('uploadImage', 1);
              formData.append('document_id', <?php echo $document_id; ?>);

              // Show loading state
              const $btn = $(this).prev('button');
              const originalHtml = $btn.html();
              $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

              $.ajax({
                type: 'POST',
                url: 'core/handleForms.php',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                  const data = JSON.parse(response);
                  if (data.status === 'success') {
                    // Insert image into editor
                    const img = document.createElement('img');
                    img.src = data.url;
                    img.style.maxWidth = '100%';
                    document.execCommand('insertHTML', false, img.outerHTML);
                    contentChanged = true;
                    
                    // Refresh the page to update the image grid
                    location.reload();
                  } else {
                    alert(data.message || 'Error uploading image');
                  }
                },
                error: function() {
                  alert('Error uploading image');
                },
                complete: function() {
                  // Restore button state
                  $btn.prop('disabled', false).html(originalHtml);
                }
              });
            }
          });
        <?php endif; ?>

        // Handle messages
        $('#messageForm').submit(function(e) {
          e.preventDefault();
          const message = $('#newMessage').val().trim();
          
          if (message) {
            $.ajax({
              type: 'POST',
              url: 'core/handleForms.php',
              data: {
                addMessage: 1,
                document_id: <?php echo $document_id; ?>,
                message: message
              },
              success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                  location.reload();
                }
              }
            });
          }
        });

        // Scroll message container to bottom
        const messageContainer = $('.message-container');
        messageContainer.scrollTop(messageContainer.prop('scrollHeight'));

        // Handle adding new permission
        $('#addPermission').click(function() {
            const userId = $('#userSelect').val();
            const canEdit = $('#canEdit').is(':checked');
            
            if (!userId) {
                alert('Please select a user');
                return;
            }
            
            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');
            
            $.ajax({
                type: 'POST',
                url: 'core/handleForms.php',
                data: {
                    updatePermissions: 1,
                    document_id: <?php echo $document_id; ?>,
                    user_id: userId,
                    can_edit: canEdit ? 1 : 0
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            // Add the new permission item to the list
                            const newItem = `
                                <div class="permission-item" data-user-id="${userId}">
                                    <div>
                                        <strong>${$('#userSelect option:selected').text().split('(')[0].trim()}</strong>
                                        <br>
                                        <small class="text-muted">${canEdit ? 'Can edit' : 'Can view'}</small>
                                    </div>
                                    <div class="permission-controls">
                                        <button class="btn btn-outline-danger btn-sm remove-permission">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                            
                            $('#permissionsList').append(newItem);
                            
                            // Remove the user from the select options
                            $(`#userSelect option[value="${userId}"]`).remove();
                            
                            // Reset the form
                            $('#userSelect').val('');
                            $('#canEdit').prop('checked', false);
                            
                            // Show success message
                            const $successMsg = $('<div class="alert alert-success mt-2" role="alert">User added successfully!</div>');
                            $('#addPermission').after($successMsg);
                            setTimeout(() => $successMsg.fadeOut(400, function() { $(this).remove(); }), 2000);
                            
                            // Restore button
                            $btn.prop('disabled', false).html(originalHtml);
                            
                            // Bind remove handler to new item
                            bindRemoveHandler();
                        } else {
                            throw new Error(data.message || 'Error updating permissions');
                        }
                    } catch (error) {
                        alert(error.message);
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function() {
                    alert('Error updating permissions. Please try again.');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        });
        
        // Function to bind remove handler
        function bindRemoveHandler() {
            $('.remove-permission').off('click').on('click', function() {
                if (!confirm('Are you sure you want to remove access for this user?')) {
                    return;
                }
                
                const $item = $(this).closest('.permission-item');
                const userId = $item.data('user-id');
                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                
                $.ajax({
                    type: 'POST',
                    url: 'core/handleForms.php',
                    data: {
                        removePermission: 1,
                        document_id: <?php echo $document_id; ?>,
                        user_id: userId
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            $item.fadeOut(400, function() {
                                $(this).remove();
                                // If no more permissions, update the list
                                if ($('.permission-item').length === 0) {
                                    $('#permissionsList').html('<p class="text-muted">No users have access</p>');
                                }
                            });
                        } else {
                            alert(data.message || 'Error removing permission');
                            $btn.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function() {
                        alert('Error removing permission. Please try again.');
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        }
        
        // Initial bind of remove handlers
        bindRemoveHandler();
      });

      // Image preview functions
      function previewImage(url) {
        $('#previewImg').attr('src', url);
        $('#imagePreview').css('display', 'flex');
      }

      function closePreview() {
        $('#imagePreview').hide();
      }

      // Close preview on escape key
      $(document).keydown(function(e) {
        if (e.key === "Escape") {
          closePreview();
        }
      });
    </script>
  </body>
</html> 