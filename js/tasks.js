/**
 * Task-specific JavaScript functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Handle task deletion
    initTaskDeletion();
    
    // Handle task status changes
    initTaskStatusChange();
    
    // Handle invitation responses
    initInvitationResponses();
});

/**
 * Initialize task deletion functionality
 */
function initTaskDeletion() {
    const deleteTaskModal = document.getElementById('deleteTaskModal');
    if (!deleteTaskModal) return;
    
    // Delete task buttons
    const deleteButtons = document.querySelectorAll('.delete-task, #deleteTaskBtn');
    const cancelDeleteButton = document.getElementById('cancelDelete');
    const confirmDeleteButton = document.getElementById('confirmDelete');
    
    let currentTaskId = null;
    
    // Show modal when delete button is clicked
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            currentTaskId = this.getAttribute('data-task-id');
            
            // Update modal with task name
            const taskTitle = this.getAttribute('data-task-title');
            const taskNameElement = document.getElementById('deleteTaskName');
            if (taskNameElement) {
                taskNameElement.textContent = taskTitle;
            }
            
            // Show modal
            deleteTaskModal.classList.remove('hidden');
        });
    });
    
    // Hide modal when cancel button is clicked
    if (cancelDeleteButton) {
        cancelDeleteButton.addEventListener('click', function() {
            deleteTaskModal.classList.add('hidden');
            currentTaskId = null;
        });
    }
    
    // Process deletion when confirm button is clicked
    if (confirmDeleteButton) {
        confirmDeleteButton.addEventListener('click', function() {
            if (!currentTaskId) return;
            
            // Send delete request
            fetch(`${APP_URL}/api/tasks.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&task_id=${currentTaskId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to tasks list
                    window.location.href = `${APP_URL}/dashboard/tasks.php`;
                } else {
                    alert('Failed to delete task: ' + (data.message || 'Unknown error'));
                    deleteTaskModal.classList.add('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while trying to delete the task.');
                deleteTaskModal.classList.add('hidden');
            });
        });
    }
    
    // Close modal when clicking outside
    deleteTaskModal.addEventListener('click', function(event) {
        if (event.target === deleteTaskModal) {
            deleteTaskModal.classList.add('hidden');
            currentTaskId = null;
        }
    });
}

/**
 * Initialize task status change functionality
 */
function initTaskStatusChange() {
    const statusSelects = document.querySelectorAll('.task-status-select');
    
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const taskId = this.getAttribute('data-task-id');
            const newStatus = this.value;
            
            // Update task status
            fetch(`${APP_URL}/api/tasks.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_status&task_id=${taskId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    const statusBadge = document.querySelector(`[data-task-id="${taskId}"] .task-status`);
                    if (statusBadge) {
                        // Remove existing status classes
                        statusBadge.classList.remove('bg-blue-500/20', 'text-blue-500', 'bg-yellow-500/20', 'text-yellow-500', 'bg-green-500/20', 'text-green-500');
                        
                        // Add new status classes
                        if (newStatus === 'To-Do') {
                            statusBadge.classList.add('bg-blue-500/20', 'text-blue-500');
                        } else if (newStatus === 'In Progress') {
                            statusBadge.classList.add('bg-yellow-500/20', 'text-yellow-500');
                        } else if (newStatus === 'Done') {
                            statusBadge.classList.add('bg-green-500/20', 'text-green-500');
                        }
                        
                        statusBadge.textContent = newStatus;
                    }
                } else {
                    alert('Failed to update task status: ' + (data.message || 'Unknown error'));
                    // Reset select to previous value
                    this.value = this.getAttribute('data-original-status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the task status.');
                // Reset select to previous value
                this.value = this.getAttribute('data-original-status');
            });
        });
        
        // Store original status for potential rollback
        select.setAttribute('data-original-status', select.value);
    });
}

/**
 * Initialize invitation response functionality
 */
function initInvitationResponses() {
    const responseButtons = document.querySelectorAll('.respond-invite');
    
    responseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const invitationId = this.getAttribute('data-invitation-id');
            const action = this.getAttribute('data-action');
            
            // Send response
            fetch(`${APP_URL}/api/invites.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&invitation_id=${invitationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to reflect changes
                    window.location.reload();
                } else {
                    alert('Failed to process invitation: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing the invitation.');
            });
        });
    });
}
