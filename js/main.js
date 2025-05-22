/**
 * Main JavaScript functionality for Task Manager application
 */

// Define constants
const APP_URL = document.querySelector('meta[name="app-url"]')?.content || '';

// DOM ready event
document.addEventListener('DOMContentLoaded', function() {
    // Add fadeIn effect to main content
    const mainContent = document.querySelector('main');
    if (mainContent) {
        mainContent.classList.add('fade-in');
    }

    // Initialize tooltips
    initTooltips();
    
    // Set up AJAX polling for updates
    initPolling();
});

/**
 * Initialize tooltip functionality
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-tooltip');
            
            // Create tooltip element
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip bg-gray-800 text-white text-xs rounded py-1 px-2 absolute z-10';
            tooltip.textContent = tooltipText;
            
            // Position tooltip
            document.body.appendChild(tooltip);
            const rect = this.getBoundingClientRect();
            tooltip.style.top = `${rect.top - tooltip.offsetHeight - 5}px`;
            tooltip.style.left = `${rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)}px`;
            
            // Store reference to tooltip
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                document.body.removeChild(this._tooltip);
                this._tooltip = null;
            }
        });
    });
}

/**
 * Initialize polling for updates
 * This simulates real-time updates by periodically checking for changes
 */
function initPolling() {
    // Only poll on task detail pages or dashboard
    const taskElement = document.querySelector('[data-task-id]');
    const isDashboard = document.querySelector('[data-poll-dashboard]');
    
    if (!taskElement && !isDashboard) return;
    
    let taskIds = [];
    let lastUpdate = new Date().toISOString();
    
    // Collect task IDs to monitor
    if (taskElement) {
        // Single task view
        taskIds.push(taskElement.getAttribute('data-task-id'));
    } else if (isDashboard) {
        // Dashboard view with multiple tasks
        document.querySelectorAll('[data-task-id]').forEach(el => {
            taskIds.push(el.getAttribute('data-task-id'));
        });
    }
    
    if (taskIds.length === 0) return;
    
    // Set up polling interval (every 10 seconds)
    setInterval(() => {
        fetch(`${APP_URL}/api/tasks.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=poll_updates&task_ids=${JSON.stringify(taskIds)}&last_update=${lastUpdate}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.tasks && data.tasks.length > 0) {
                // Update UI with new task information
                data.tasks.forEach(task => {
                    updateTaskUI(task);
                });
                
                // Show notification
                showNotification('Task updated', 'Task information has been updated.');
            }
            
            // Update timestamp for next poll
            if (data.timestamp) {
                lastUpdate = data.timestamp;
            }
        })
        .catch(error => {
            console.error('Polling error:', error);
        });
    }, 10000); // Poll every 10 seconds
}

/**
 * Update task UI with new data
 * @param {Object} task - Updated task data
 */
function updateTaskUI(task) {
    const taskElements = document.querySelectorAll(`[data-task-id="${task.id}"]`);
    
    taskElements.forEach(element => {
        // Update task title
        const titleEl = element.querySelector('.task-title');
        if (titleEl) titleEl.textContent = task.title;
        
        // Update task description
        const descEl = element.querySelector('.task-description');
        if (descEl) descEl.textContent = task.description;
        
        // Update task status
        const statusEl = element.querySelector('.task-status');
        if (statusEl) {
            // Remove existing status classes
            statusEl.classList.remove('bg-blue-500/20', 'text-blue-500', 'bg-yellow-500/20', 'text-yellow-500', 'bg-green-500/20', 'text-green-500');
            
            // Add new status classes
            if (task.status === 'To-Do') {
                statusEl.classList.add('bg-blue-500/20', 'text-blue-500');
            } else if (task.status === 'In Progress') {
                statusEl.classList.add('bg-yellow-500/20', 'text-yellow-500');
            } else if (task.status === 'Done') {
                statusEl.classList.add('bg-green-500/20', 'text-green-500');
            }
            
            statusEl.textContent = task.status;
        }
        
        // Update task due date
        const dueDateEl = element.querySelector('.task-due-date');
        if (dueDateEl) {
            const date = new Date(task.due_date);
            dueDateEl.textContent = date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric' 
            });
        }
    });
}

/**
 * Show a notification
 * @param {string} title - Notification title
 * @param {string} message - Notification message
 */
function showNotification(title, message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-white dark:bg-gray-800 shadow-lg rounded-2xl p-4 z-50 transform transition-transform duration-300 translate-x-full';
    notification.innerHTML = `
        <div class="flex items-start">
            <div class="flex-shrink-0 pt-0.5">
                <i class="fas fa-info-circle text-primary text-lg"></i>
            </div>
            <div class="ml-3 w-0 flex-1">
                <p class="text-sm font-medium">${title}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">${message}</p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
                <button class="bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                    <span class="sr-only">Close</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    
    // Add to DOM
    document.body.appendChild(notification);
    
    // Show notification with animation
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 10);
    
    // Set up close button
    const closeButton = notification.querySelector('button');
    closeButton.addEventListener('click', () => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    });
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (document.body.contains(notification)) {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }
    }, 5000);
}
