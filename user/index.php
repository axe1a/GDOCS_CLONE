<?php 
require_once 'core/dbConfig.php'; 
require_once 'core/models.php'; 

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// If user is admin, redirect to admin section
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    header("Location: ../admin/index.php");
    exit();
}
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
    
    <style>
      body {
        font-family: 'Product Sans', sans-serif;
        background-color: #f5f5f5;
      }
      .document-card {
        transition: transform 0.2s;
      }
      .document-card:hover {
        transform: translateY(-5px);
      }
      .create-doc-btn {
        background-color: #4285f4;
        color: white;
        border-radius: 30px;
        padding: 10px 20px;
        font-weight: 500;
      }
      .create-doc-btn:hover {
        background-color: #2b76f5;
        color: white;
        text-decoration: none;
      }
    </style>
    <title>Google Docs Clone</title>
  </head>
  <body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
      <!-- Create new document button -->
      <div class="d-flex justify-content-between align-items-center m-4">
        <p style="font-size: 24px; font-weight: 500;">Recent Documents</p>
        <a href="create_document.php" class="create-doc-btn">
          <i class="fas fa-plus"></i> Create New Document
        </a>
      </div>

      <div class="row justify-content-center">
        <div class="col-md-10">
          <!-- Display user's documents -->
          <?php 
          $userDocuments = getUserDocuments($pdo, $_SESSION['user_id']); 
          if (!empty($userDocuments)) {
            foreach ($userDocuments as $doc) { 
          ?>
            <div class="card shadow-sm mb-4 document-card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <h5 class="card-title"><?php echo htmlspecialchars($doc['title']); ?></h5>
                  <small class="text-muted">Last updated: <?php echo date('M j, Y g:i A', strtotime($doc['last_updated'])); ?></small>
                </div>
                <p class="card-text">
                  <?php 
                    // Show first 200 characters of content
                    echo htmlspecialchars(substr(strip_tags($doc['content']), 0, 200)) . '...'; 
                  ?>
                </p>
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <a href="document.php?id=<?php echo $doc['document_id']; ?>" class="btn btn-primary btn-sm">Open</a>
                    <?php if ($doc['author_id'] == $_SESSION['user_id']) { ?>
                      <button class="btn btn-danger btn-sm delete-doc" data-id="<?php echo $doc['document_id']; ?>">Delete</button>
                    <?php } ?>
                  </div>
                  <div>
                    <?php if ($doc['author_id'] == $_SESSION['user_id']) { ?>
                      <span class="badge badge-success">Owner</span>
                    <?php } else { ?>
                      <span class="badge badge-info">Shared with you</span>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </div>
          <?php 
            }
          } else {
          ?>
            <div class="text-center mt-5">
              <h4>No documents yet</h4>
              <p>Click the "Create New Document" button to get started!</p>
            </div>
          <?php } ?>
        </div>
      </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/your-kit-code.js"></script>

    <script>
      // Handle document deletion
      $('.delete-doc').on('click', function() {
        const $btn = $(this);
        const docId = $btn.data('id');
        const $card = $btn.closest('.document-card');
        
        if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
          // Show loading state
          const originalHtml = $btn.html();
          $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
          
          $.ajax({
            type: "POST",
            url: "core/handleForms.php",
            data: {
              document_id: docId,
              deleteDocument: 1
            },
            success: function(response) {
              try {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                  // Fade out and remove the card
                  $card.fadeOut(400, function() {
                    $(this).remove();
                    // Check if there are no more documents
                    if ($('.document-card').length === 0) {
                      $('.col-md-10').html(`
                        <div class="text-center mt-5">
                          <h4>No documents yet</h4>
                          <p>Click the "Create New Document" button to get started!</p>
                        </div>
                      `);
                    }
                  });
                } else {
                  alert(data.message || 'Error deleting document');
                  // Restore button state
                  $btn.prop('disabled', false).html(originalHtml);
                }
              } catch (e) {
                alert('Error processing server response');
                // Restore button state
                $btn.prop('disabled', false).html(originalHtml);
              }
            },
            error: function() {
              alert('Error deleting document. Please try again.');
              // Restore button state
              $btn.prop('disabled', false).html(originalHtml);
            }
          });
        }
      });
    </script>
  </body>
</html>
