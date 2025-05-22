/**
 * Collaborator-specific JavaScript functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Handle collaborator removal
    initCollaboratorRemoval();
    
    // Handle role updates
    initRoleUpdates();
});

/**
 * Initialize collaborator removal functionality
 */
function initCollaboratorRemoval() {
    const removeCollaboratorModal = document.getElementById('removeCollaboratorModal');
    if (!removeCollaboratorModal) return;
    
    const removeButtons = document.querySelectorAll('.remove-collaborator');
    const cancelRemoveButton = document.getElementById('cancelRemove');
    const confirmRemoveButton = document.getElementById('confirmRemove');
    
    let currentCollaboratorId = null;
    
    // Show modal when remove button is clicked
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            currentCollaboratorId = this.getAttribute('data-collaborator-id');
            
            // Update modal with collaborator name
            const collaboratorName = this.getAttribute('data-collaborator-name');
            const nameElement = document.getElementById('removeCollaboratorName');
            if (nameElement) {
                nameElement.textContent = collaboratorName;
            }
            
            // Show modal
            removeCollaboratorModal.classList.remove('hidden');
        });
    });
    
    // Hide modal when cancel button is clicked
    if (cancelRemoveButton) {
        cancelRemoveButton.addEventListener('click', function() {
            removeCollaboratorModal.classList.add('hidden');
            currentCollaboratorId = null;
        });
    }
    
    // Process removal when confirm button is clicked
    if (confirmRemoveButton) {
        confirmRemoveButton.addEventListener('click', function() {
            if (!currentCollaboratorId) return;
            
            // Send delete request
            fetch(`${APP_URL}/api/collaborators.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove&collaborator_id=${currentCollaboratorId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show updated collaborator list
                    window.location.reload();
                } else {
                    alert('Failed to remove collaborator: ' + (data.message || 'Unknown error'));
                    removeCollaboratorModal.classList.add('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while trying to remove the collaborator.');
                removeCollaboratorModal.classList.add('hidden');
            });
        });
    }
    
    // Close modal when clicking outside
    removeCollaboratorModal.addEventListener('click', function(event) {
        if (event.target === removeCollaboratorModal) {
            removeCollaboratorModal.classList.add('hidden');
            currentCollaboratorId = null;
        }
    });
}

/**
 * Initialize role update functionality
 */
function initRoleUpdates() {
    const roleSelects = document.querySelectorAll('.collaborator-role-select');
    
    roleSelects.forEach(select => {
        select.addEventListener('change', function() {
            const collaboratorId = this.getAttribute('data-collaborator-id');
            const newRole = this.value;
            
            // Update collaborator role
            fetch(`${APP_URL}/api/collaborators.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_role&collaborator_id=${collaboratorId}&role=${newRole}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    const roleBadge = document.querySelector(`[data-collaborator-id="${collaboratorId}"] .collaborator-role`);
                    if (roleBadge) {
                        // Remove existing role classes
                        roleBadge.classList.remove('bg-purple-500/20', 'text-purple-500', 'bg-blue-500/20', 'text-blue-500', 'bg-gray-500/20', 'text-gray-500');
                        
                        // Add new role classes
                        if (newRole === 'Owner') {
                            roleBadge.classList.add('bg-purple-500/20', 'text-purple-500');
                        } else if (newRole === 'Editor') {
                            roleBadge.classList.add('bg-blue-500/20', 'text-blue-500');
                        } else {
                            roleBadge.classList.add('bg-gray-500/20', 'text-gray-500');
                        }
                        
                        roleBadge.textContent = newRole;
                    }
                } else {
                    alert('Failed to update collaborator role: ' + (data.message || 'Unknown error'));
                    // Reset select to previous value
                    this.value = this.getAttribute('data-original-role');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the collaborator role.');
                // Reset select to previous value
                this.value = this.getAttribute('data-original-role');
            });
        });
        
        // Store original role for potential rollback
        select.setAttribute('data-original-role', select.value);
    });
}
