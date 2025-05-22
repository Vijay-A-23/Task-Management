<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$response = ['success' => false];

// Handle different actions based on request method and parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'accept' || $action === 'decline') {
        // Accept or decline invitation
        $invitationId = isset($_POST['invitation_id']) ? (int)$_POST['invitation_id'] : 0;
        
        // Get invitation details
        $invitation = $db->getRow(
            "SELECT * FROM invitations WHERE id = ? AND status = 'pending'",
            [$invitationId]
        );
        
        if (!$invitation) {
            $response = ['success' => false, 'message' => 'Invitation not found or already processed'];
        } else {
            // Get user email
            $userEmail = $db->getRow("SELECT email FROM users WHERE id = ?", [$userId]);
            
            if (!$userEmail || $userEmail['email'] !== $invitation['invited_email']) {
                $response = ['success' => false, 'message' => 'This invitation is not for your account'];
            } else {
                if ($action === 'accept') {
                    // Add user as collaborator
                    $collaboratorResult = $db->query(
                        "INSERT INTO task_collaborators (task_id, user_id, role, created_at) 
                         VALUES (?, ?, ?, NOW())",
                        [$invitation['task_id'], $userId, $invitation['role']]
                    );
                    
                    if (!$collaboratorResult) {
                        $response = ['success' => false, 'message' => 'Failed to add you as a collaborator'];
                    }
                }
                
                // Update invitation status
                $statusUpdateResult = $db->query(
                    "UPDATE invitations SET status = ? WHERE id = ?",
                    [$action === 'accept' ? 'accepted' : 'declined', $invitationId]
                );
                
                if ($statusUpdateResult) {
                    $response = [
                        'success' => true,
                        'message' => $action === 'accept' ? 'Invitation accepted successfully' : 'Invitation declined'
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update invitation status'];
                }
            }
        }
    } elseif ($action === 'cancel') {
        // Cancel an invitation (by the task owner)
        $invitationId = isset($_POST['invitation_id']) ? (int)$_POST['invitation_id'] : 0;
        
        // Get invitation details
        $invitation = $db->getRow(
            "SELECT i.*, t.created_by FROM invitations i 
             JOIN tasks t ON i.task_id = t.id 
             WHERE i.id = ? AND i.status = 'pending'",
            [$invitationId]
        );
        
        if (!$invitation) {
            $response = ['success' => false, 'message' => 'Invitation not found or already processed'];
        } elseif ($invitation['created_by'] != $userId) {
            $response = ['success' => false, 'message' => 'You do not have permission to cancel this invitation'];
        } else {
            // Delete the invitation
            $result = $db->query("DELETE FROM invitations WHERE id = ?", [$invitationId]);
            
            if ($result) {
                $response = ['success' => true, 'message' => 'Invitation cancelled successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to cancel invitation'];
            }
        }
    } elseif ($action === 'resend') {
        // Resend an invitation
        $invitationId = isset($_POST['invitation_id']) ? (int)$_POST['invitation_id'] : 0;
        
        // Get invitation details
        $invitation = $db->getRow(
            "SELECT i.*, t.title, t.created_by, u.name as inviter_name 
             FROM invitations i 
             JOIN tasks t ON i.task_id = t.id 
             JOIN users u ON t.created_by = u.id
             WHERE i.id = ? AND i.status = 'pending'",
            [$invitationId]
        );
        
        if (!$invitation) {
            $response = ['success' => false, 'message' => 'Invitation not found or already processed'];
        } elseif ($invitation['created_by'] != $userId) {
            $response = ['success' => false, 'message' => 'You do not have permission to resend this invitation'];
        } else {
            // Generate a new token
            $token = generateToken();
            
            // Update the invitation with new token and timestamp
            $result = $db->query(
                "UPDATE invitations SET token = ?, created_at = NOW() WHERE id = ?",
                [$token, $invitationId]
            );
            
            if ($result) {
                // Send invitation email
                if (sendInvitationEmail(
                    $invitation['invited_email'],
                    $invitation['title'],
                    $invitation['inviter_name'],
                    $invitation['role'],
                    $token
                )) {
                    $response = ['success' => true, 'message' => 'Invitation resent successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to send invitation email'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Failed to update invitation'];
            }
        }
    }
} else {
    $response = ['success' => false, 'message' => 'Invalid request method'];
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
