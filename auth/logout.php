<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Destroy the session
session_destroy();

// Redirect to home page
redirect(APP_URL);
