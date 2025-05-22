<?php
// Initialize SQLite database
require_once 'includes/config.php';

// Create new SQLite database
$dbFile = __DIR__ . '/database.sqlite';

// Check if database already exists
$dbExists = file_exists($dbFile);

if (!$dbExists) {
    // Create the database file
    touch($dbFile);
    chmod($dbFile, 0666); // Set permissions
    
    // Load and execute the schema
    try {
        $pdo = new PDO("sqlite:$dbFile");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Load schema SQL
        $sql = file_get_contents(__DIR__ . '/sql/sqlite_schema.sql');
        
        // Execute the SQL statements
        $pdo->exec($sql);
        
        echo "Database initialized successfully with sample data.\n";
    } catch (PDOException $e) {
        die("Database initialization failed: " . $e->getMessage() . "\n");
    }
} else {
    echo "Database already exists.\n";
}