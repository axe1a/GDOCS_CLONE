CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY, 
    username VARCHAR(255) UNIQUE NOT NULL, 
    password TEXT NOT NULL, 
    first_name VARCHAR(255), 
    last_name VARCHAR(255), 
    is_admin BOOLEAN, 
    is_suspended BOOLEAN, 
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY, 
    title VARCHAR(255) NOT NULL, 
    content LONGTEXT, 
    author_id INT, 
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(user_id)
);

CREATE TABLE document_permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY, 
    document_id INT, 
    user_id INT, 
    can_edit BOOLEAN DEFAULT FALSE, 
    date_granted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(document_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY, 
    document_id INT, 
    user_id INT, 
    action VARCHAR(255), 
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(document_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE document_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY, 
    document_id INT, 
    user_id INT, 
    message TEXT, 
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(document_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE document_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY, 
    document_id INT, 
    image_url TEXT, 
    added_by INT, 
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(document_id),
    FOREIGN KEY (added_by) REFERENCES users(user_id)
);