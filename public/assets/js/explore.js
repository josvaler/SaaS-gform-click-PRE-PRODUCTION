// Explore Links Page JavaScript
(function() {
    'use strict';

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Get CSRF token from window
    const csrfToken = window.csrfToken || '';

    // Search input debounced handler
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        const performSearch = debounce(() => {
            const form = document.getElementById('filter-form');
            if (form) {
                form.submit();
            }
        }, 300);

        searchInput.addEventListener('input', performSearch);
    }

    // Status filter instant handler
    const statusFilter = document.getElementById('status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', () => {
            const form = document.getElementById('filter-form');
            if (form) {
                form.submit();
            }
        });
    }

    // Date filters instant handler
    const dateFrom = document.getElementById('date-from');
    const dateTo = document.getElementById('date-to');
    
    if (dateFrom) {
        dateFrom.addEventListener('change', () => {
            const form = document.getElementById('filter-form');
            if (form) {
                form.submit();
            }
        });
    }
    
    if (dateTo) {
        dateTo.addEventListener('change', () => {
            const form = document.getElementById('filter-form');
            if (form) {
                form.submit();
            }
        });
    }

    // Toggle link active/inactive
    function handleToggle(event) {
        const button = event.currentTarget;
        const linkId = button.getAttribute('data-link-id');
        const currentStatus = button.getAttribute('data-current-status');
        
        if (!linkId) return;

        // Disable button during request
        button.disabled = true;
        button.style.opacity = '0.6';
        button.style.cursor = 'wait';

        const formData = new FormData();
        formData.append('link_id', linkId);
        formData.append('csrf_token', csrfToken);

        fetch('/api/link/toggle.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update button state
                const newStatus = data.is_active;
                button.setAttribute('data-current-status', newStatus);
                
                // Update icon
                const icon = button.querySelector('i');
                if (icon) {
                    icon.className = newStatus == 1 ? 'fas fa-toggle-on' : 'fas fa-toggle-off';
                }
                
                // Update status badge in same row
                const row = button.closest('tr');
                if (row) {
                    const badge = row.querySelector('.badge');
                    if (badge) {
                        const isExpired = badge.getAttribute('data-status') === 'expired';
                        if (!isExpired) {
                            // Get status text from data attributes
                            const activeText = badge.getAttribute('data-active-text') || 'Active';
                            const inactiveText = badge.getAttribute('data-inactive-text') || 'Inactive';
                            
                            if (newStatus == 1) {
                                badge.className = 'badge premium-badge';
                                badge.setAttribute('data-status', 'active');
                                badge.textContent = activeText;
                            } else {
                                badge.className = 'badge free-badge';
                                badge.setAttribute('data-status', 'inactive');
                                badge.textContent = inactiveText;
                            }
                        }
                    }
                }
                
                // Show success feedback
                showNotification('success', data.message || 'Status updated');
            } else {
                showNotification('error', data.message || 'Error updating status');
            }
        })
        .catch(error => {
            console.error('Toggle error:', error);
            showNotification('error', 'Network error. Please try again.');
        })
        .finally(() => {
            button.disabled = false;
            button.style.opacity = '1';
            button.style.cursor = 'pointer';
        });
    }

    // Delete link handler
    let deleteLinkId = null;
    const deleteModal = document.getElementById('delete-modal');
    const deleteLinkInfo = document.getElementById('delete-link-info');
    const confirmDeleteBtn = document.getElementById('confirm-delete');
    const cancelDeleteBtn = document.getElementById('cancel-delete');
    const closeDeleteModalBtn = document.getElementById('close-delete-modal');

    function handleDeleteClick(event) {
        const button = event.currentTarget;
        const linkId = button.getAttribute('data-link-id');
        const linkCode = button.getAttribute('data-link-code');
        
        if (!linkId) return;

        deleteLinkId = linkId;
        
        // Update modal info
        if (deleteLinkInfo) {
            deleteLinkInfo.textContent = linkCode ? `Link: ${linkCode}` : `Link ID: ${linkId}`;
        }
        
        // Show modal
        if (deleteModal) {
            deleteModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeDeleteModal() {
        if (deleteModal) {
            deleteModal.style.display = 'none';
            document.body.style.overflow = '';
        }
        deleteLinkId = null;
    }

    function confirmDelete() {
        if (!deleteLinkId) return;

        // Disable button during request
        if (confirmDeleteBtn) {
            confirmDeleteBtn.disabled = true;
            confirmDeleteBtn.style.opacity = '0.6';
            confirmDeleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i>Deleting...';
        }

        const formData = new FormData();
        formData.append('link_id', deleteLinkId);
        formData.append('csrf_token', csrfToken);

        fetch('/api/link/delete.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove row from table
                const row = document.querySelector(`tr[data-link-id="${deleteLinkId}"]`);
                if (row) {
                    row.style.transition = 'opacity 0.3s ease';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        
                        // Check if table is empty
                        const tbody = document.getElementById('links-table-body');
                        if (tbody && tbody.children.length === 0) {
                            location.reload();
                        }
                    }, 300);
                }
                
                closeDeleteModal();
                showNotification('success', data.message || 'Link deleted successfully');
            } else {
                showNotification('error', data.message || 'Error deleting link');
                if (confirmDeleteBtn) {
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.style.opacity = '1';
                    confirmDeleteBtn.innerHTML = '<i class="fas fa-trash" style="margin-right: 0.5rem;"></i>Delete Permanently';
                }
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            showNotification('error', 'Network error. Please try again.');
            if (confirmDeleteBtn) {
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.style.opacity = '1';
                confirmDeleteBtn.innerHTML = '<i class="fas fa-trash" style="margin-right: 0.5rem;"></i>Delete Permanently';
            }
        });
    }

    // Notification system
    function showNotification(type, message) {
        // Remove existing notifications
        const existing = document.querySelector('.explore-notification');
        if (existing) {
            existing.remove();
        }

        const notification = document.createElement('div');
        notification.className = `explore-notification alert alert-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 10002;
            min-width: 300px;
            max-width: 500px;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            animation: slideInRight 0.3s ease;
        `;
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}" style="font-size: 1.2rem;"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(notification);

        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Add CSS animations
    if (!document.getElementById('explore-animations')) {
        const style = document.createElement('style');
        style.id = 'explore-animations';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }

    // Attach event listeners
    document.addEventListener('DOMContentLoaded', () => {
        // Toggle buttons
        const toggleButtons = document.querySelectorAll('.toggle-link-btn');
        toggleButtons.forEach(button => {
            button.addEventListener('click', handleToggle);
        });

        // Delete buttons
        const deleteButtons = document.querySelectorAll('.delete-link-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', handleDeleteClick);
        });

        // Modal close handlers
        if (cancelDeleteBtn) {
            cancelDeleteBtn.addEventListener('click', closeDeleteModal);
        }
        if (closeDeleteModalBtn) {
            closeDeleteModalBtn.addEventListener('click', closeDeleteModal);
        }
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', confirmDelete);
        }

        // Close modal on overlay click
        if (deleteModal) {
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    closeDeleteModal();
                }
            });
        }

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && deleteModal && deleteModal.style.display === 'flex') {
                closeDeleteModal();
            }
        });
    });
})();

