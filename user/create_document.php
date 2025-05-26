<?php 
require_once 'core/dbConfig.php'; 
require_once 'core/models.php'; 

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
}

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    header("Location: ../admin/index.php");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['title']) && isset($_POST['content'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        
        if (!empty($title)) {
            // Check if we're updating an existing draft or creating a new one
            if (isset($_POST['draft_id']) && !empty($_POST['draft_id'])) {
                $document_id = $_POST['draft_id'];
                if (updateDocument($pdo, $document_id, $content) && updateDocumentTitle($pdo, $document_id, $title)) {
                    logActivity($pdo, $document_id, $_SESSION['user_id'], 'Updated draft');
                    if (isset($_POST['finalSave']) && $_POST['finalSave']) {
                        echo json_encode(['status' => 'success', 'document_id' => $document_id, 'redirect' => true]);
                    } else {
                        echo json_encode(['status' => 'success', 'document_id' => $document_id, 'redirect' => false]);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update draft']);
                }
            } else {
                $document_id = createDocument($pdo, $title, $content, $_SESSION['user_id']);
                if ($document_id) {
                    logActivity($pdo, $document_id, $_SESSION['user_id'], 'Created document');
                    if (isset($_POST['finalSave']) && $_POST['finalSave']) {
                        echo json_encode(['status' => 'success', 'document_id' => $document_id, 'redirect' => true]);
                    } else {
                        echo json_encode(['status' => 'success', 'document_id' => $document_id, 'redirect' => false]);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to create document']);
                }
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Title is required']);
        }
        exit();
    }
}

// Get recent activity logs for the user
$activityLogs = getUserActivityLogs($pdo, $_SESSION['user_id']);
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
        border: none;
        width: 100%;
        margin-bottom: 1rem;
        padding: 0.5rem;
        background: transparent;
      }
      .document-title:focus {
        outline: none;
        background: white;
        border-radius: 4px;
      }
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
    </style>
    <title>Create New Document</title>
  </head>
  <body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
      <div class="row">
        <!-- Main Content -->
        <div class="col-md-9">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <input type="text" id="documentTitle" class="document-title" placeholder="Untitled Document" required>
            <button type="button" class="btn btn-save" id="saveBtn">Save</button>
          </div>

          <div class="editor-container">
            <div class="toolbar">
              <button onclick="document.execCommand('bold')"><i class="fas fa-bold"></i></button>
              <button onclick="document.execCommand('italic')"><i class="fas fa-italic"></i></button>
              <button onclick="document.execCommand('underline')"><i class="fas fa-underline"></i></button>
              <button onclick="document.execCommand('insertUnorderedList')"><i class="fas fa-list-ul"></i></button>
              <button onclick="document.execCommand('insertOrderedList')"><i class="fas fa-list-ol"></i></button>
              <button onclick="$('#imageUpload').click()"><i class="fas fa-image"></i></button>
              <input type="file" id="imageUpload" accept="image/*">
            </div>
            <div id="editor" class="form-control" contenteditable="true"></div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-3">
          <div class="sidebar">
            <h5 class="mb-3">Recent Activity</h5>
            <div class="activity-container">
              <?php if ($activityLogs && count($activityLogs) > 0): ?>
                <?php foreach ($activityLogs as $log): ?>
                  <div class="activity-log">
                    <small class="text-muted">
                      <?php echo date('M j, g:i a', strtotime($log['timestamp'])); ?>
                    </small>
                    <br>
                    <strong><?php echo htmlspecialchars($log['username']); ?></strong>
                    <?php echo htmlspecialchars($log['action']); ?>
                    in <em><?php echo htmlspecialchars($log['document_title']); ?></em>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="text-muted">No recent activity</p>
              <?php endif; ?>
            </div>

            <hr>

            <div class="tips-section">
              <h5 class="mb-3">Tips</h5>
              <ul class="small">
                <li>Use the toolbar above to format your text</li>
                <li>Click the image icon to insert images</li>
                <li>Your document is saved automatically every 30 seconds</li>
                <li>Click "Save Document" to save manually</li>
              </ul>
            </div>

            <hr>

            <!-- Temporary Images Section -->
            <h5 class="mb-3">Uploaded Images</h5>
            <div class="image-grid" id="tempImageGrid"></div>
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
        let currentDocumentId = null;
        let tempImages = [];
        let lastAutoSaveTime = null;
        
        // Track changes
        $('#editor, #documentTitle').on('input', function() {
          contentChanged = true;
        });

        // Auto-save every 30 seconds if there are changes
        setInterval(function() {
          if (contentChanged) {
            saveDocument(false);
          }
        }, 30000);

        // Save button click
        $('#saveBtn').click(function() {
          saveDocument(true);
        });

        function saveDocument(isFinalSave = false) {
          const title = $('#documentTitle').val().trim();
          const content = $('#editor').html();

          if (!title) {
            alert('Please enter a document title');
            return;
          }

          // Show saving indicator
          const $btn = $('#saveBtn');
          const originalHtml = $btn.html();
          $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

          $.ajax({
            type: 'POST',
            url: 'create_document.php',
            data: {
              title: title,
              content: content,
              draft_id: currentDocumentId,
              finalSave: isFinalSave ? 1 : 0
            },
            success: function(response) {
              const data = JSON.parse(response);
              if (data.status === 'success') {
                contentChanged = false;
                currentDocumentId = data.document_id;
                lastAutoSaveTime = new Date();

                if (data.redirect) {
                  // Only redirect if it's the final save
                  window.location.href = 'document.php?id=' + data.document_id;
                } else {
                  // Update save button with success message
                  $btn.html('<i class="fas fa-check"></i> ' + (isFinalSave ? 'Saved' : 'Auto-saved'))
                      .addClass('btn-success')
                      .removeClass('btn-save');
                  
                  // Update the window title to show it's saved
                  document.title = title + ' (Saved) - Document';
                  
                  setTimeout(() => {
                    $btn.html(originalHtml)
                        .removeClass('btn-success')
                        .addClass('btn-save')
                        .prop('disabled', false);
                  }, 2000);
                }
              } else {
                alert(data.message || 'Error saving document');
                $btn.html(originalHtml).prop('disabled', false);
              }
            },
            error: function() {
              alert('Error saving document. Please try again.');
              $btn.html(originalHtml).prop('disabled', false);
            }
          });
        }

        // Add save status to window title when changes are made
        $('#editor, #documentTitle').on('input', function() {
          const title = $('#documentTitle').val().trim() || 'Untitled Document';
          document.title = title + '* - Document';
        });

        // Prevent accidental navigation if there are unsaved changes
        window.onbeforeunload = function() {
          if (contentChanged) {
            return "You have unsaved changes. Are you sure you want to leave?";
          }
        };

        // Handle image upload
        $('#imageUpload').change(function() {
          const file = this.files[0];
          if (file) {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('uploadImage', 1);

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

                  // Add to temporary images grid
                  tempImages.push(data.url);
                  updateTempImageGrid();
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

        function updateTempImageGrid() {
          const grid = $('#tempImageGrid');
          grid.empty();
          
          tempImages.forEach(url => {
            const item = $('<div class="image-item">');
            const img = $('<img>')
              .attr('src', url)
              .attr('alt', 'Uploaded image')
              .click(() => previewImage(url));
            
            item.append(img);
            grid.append(item);
          });
        }
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
  </body>
</html> 