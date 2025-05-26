<style>
  .navbar-nav {
    width: 100%;
    display: flex;
    justify-content: space-between;
  }
  .nav-left {
    display: flex;
    align-items: center;
    flex-grow: 1;
    margin-left: 20px;
  }
  .nav-right {
    display: flex;
  }
  .search-container {
    position: relative;
    width: 100%;
    max-width: 600px;
  }
  .search-input {
    width: 100%;
    padding: 8px 15px;
    border: 1px solid #dfe1e5;
    border-radius: 24px;
    font-size: 14px;
    outline: none;
    transition: box-shadow 0.2s;
  }
  .search-input:focus {
    box-shadow: 0 1px 6px rgba(32,33,36,.28);
    border-color: rgba(223,225,229,0);
  }
  .search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-top: 5px;
    display: none;
    z-index: 1000;
    max-height: 400px;
    overflow-y: auto;
  }
  .search-result-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f5f5f5;
  }
  .search-result-item:hover {
    background: #f8f9fa;
  }
  .search-result-item:last-child {
    border-bottom: none;
  }
</style>    

<nav class="navbar navbar-expand-lg navbar-dark p-4" style="background-color: #ffffff; color: #27292b;">
  <a class="navbar-brand" style="color: #ea4335; font-weight: 500; font-size: 20px;" href="index.php">Google Docs Clone - Admin</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarNav">
    <div class="navbar-nav">
      <div class="nav-left">  

      </div>
      
      <div class="nav-right">
        <div class="nav-item mt-3">
          <p> Hello,<button style="color: #ea4335; font-size: 18px; background-color: #ffffff; border: none;">  <?php echo $_SESSION['username']; ?></button></p>
        </div>
        <button class="nav-item" style="background-color: #ffffff; border: none;">
          <a class="nav-link" href="core/handleForms.php?logoutUserBtn=1" style="color: #27292b; font-size: 18px;">Logout</a>
        </button>
      </div>
    </div>
  </div>
</nav>

<script>
$(document).ready(function() {
  let searchTimeout;
  
  $('#searchInput').on('input', function() {
    clearTimeout(searchTimeout);
    const query = $(this).val().trim();
    
    if (query.length > 0) {
      searchTimeout = setTimeout(function() {
        $.ajax({
          url: 'core/handleForms.php',
          method: 'POST',
          data: {
            searchDocuments: 1,
            query: query
          },
          success: function(response) {
            const data = JSON.parse(response);
            const resultsDiv = $('#searchResults');
            resultsDiv.empty();
            
            if (data.status === 'success' && data.results.length > 0) {
              data.results.forEach(function(doc) {
                resultsDiv.append(`
                  <div class="search-result-item" onclick="window.location.href='document.php?id=${doc.document_id}'">
                    <div class="font-weight-bold">${doc.title}</div>
                    <small class="text-muted">Last updated: ${new Date(doc.last_updated).toLocaleDateString()}</small>
                  </div>
                `);
              });
              resultsDiv.show();
            } else {
              resultsDiv.append(`
                <div class="search-result-item">No documents found</div>
              `);
              resultsDiv.show();
            }
          }
        });
      }, 300);
    } else {
      $('#searchResults').hide();
    }
  });
  
  // Hide search results when clicking outside
  $(document).on('click', function(e) {
    if (!$(e.target).closest('.search-container').length) {
      $('#searchResults').hide();
    }
  });
});
</script>