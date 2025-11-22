<?php
declare(strict_types=1);

/**
 * .htaccess File Editor Component
 * Provides table view and text editor mode for managing .htaccess file
 */
?>

<div id="htaccess-editor-container" style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
    <!-- Loading State -->
    <div id="htaccess-loading" style="text-align: center; padding: 3rem 1rem;">
        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 1rem; display: block;"></i>
        <p style="color: var(--text-secondary); opacity: 0.8;">Loading .htaccess file...</p>
    </div>
    
    <!-- Error State -->
    <div id="htaccess-error" style="display: none; padding: 1rem; background: rgba(239, 68, 68, 0.1); border-radius: 0.5rem; border: 1px solid rgba(239, 68, 68, 0.3); margin-bottom: 1rem;">
        <div style="color: #ef4444; font-weight: 600; margin-bottom: 0.5rem;">
            <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i>
            Error loading .htaccess file
        </div>
        <div id="htaccess-error-message" style="color: var(--text-secondary); font-size: 0.875rem;"></div>
        <button onclick="loadHtaccessData()" style="margin-top: 0.75rem; padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 0.5rem; cursor: pointer;">
            <i class="fas fa-redo" style="margin-right: 0.5rem;"></i>Retry
        </button>
    </div>
    
    <!-- Success/Error Messages -->
    <div id="htaccess-message" style="display: none; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
        <div id="htaccess-message-content" style="font-weight: 600;"></div>
    </div>
    
    <!-- Content Container -->
    <div id="htaccess-content" style="display: none;">
        <!-- Mode Toggle -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid rgba(148, 163, 184, 0.2);">
            <div>
                <h3 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
                    .htaccess File Editor
                </h3>
                <p style="color: var(--text-secondary); opacity: 0.8; font-size: 0.875rem;">
                    Manage your Apache configuration
                </p>
            </div>
            <button id="htaccess-mode-toggle" onclick="toggleHtaccessMode()" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
                <i class="fas fa-table" id="htaccess-mode-icon" style="margin-right: 0.5rem;"></i>
                <span id="htaccess-mode-text">Switch to Text Editor</span>
            </button>
        </div>
        
        <!-- Table View -->
        <div id="htaccess-table-view">
            <div style="margin-bottom: 1rem; display: flex; justify-content: flex-end; gap: 1rem;">
                <button onclick="saveHtaccessTable()" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                    <i class="fas fa-save" style="margin-right: 0.5rem;"></i>Save Changes
                </button>
            </div>
            
            <div style="overflow-x: auto; border-radius: 0.5rem; border: 1px solid var(--color-border);">
                <table id="htaccess-table" style="width: 100%; border-collapse: collapse; background: var(--color-bg-secondary);">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--color-border); background: rgba(14, 165, 233, 0.1);">
                            <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: var(--text-primary); width: 8%;">LINE</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: var(--text-primary); width: 60%;">CONTENT</th>
                            <th style="padding: 1rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: var(--text-primary); width: 15%;">MASK VALUE</th>
                            <th style="padding: 1rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: var(--text-primary); width: 15%;">EDITABLE</th>
                        </tr>
                    </thead>
                    <tbody id="htaccess-table-body">
                        <!-- Table rows will be inserted here -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Text Editor View -->
        <div id="htaccess-editor-view" style="display: none;">
            <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" id="htaccess-line-numbers" checked onchange="toggleHtaccessLineNumbers()" style="margin: 0; cursor: pointer;">
                        <span style="font-size: 0.875rem;">Show line numbers</span>
                    </label>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button onclick="loadHtaccessData()" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
                        <i class="fas fa-redo" style="margin-right: 0.5rem;"></i>Reload
                    </button>
                    <button onclick="saveHtaccessEditor()" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                        <i class="fas fa-save" style="margin-right: 0.5rem;"></i>Save Changes
                    </button>
                </div>
            </div>
            
            <div style="position: relative; border-radius: 0.5rem; border: 1px solid var(--color-border); overflow: hidden;">
                <div id="htaccess-editor-wrapper" style="position: relative; background: #0f172a;">
                    <div id="htaccess-line-numbers-display" style="position: absolute; left: 0; top: 0; padding: 1rem; font-family: 'Courier New', monospace; font-size: 0.875rem; line-height: 1.6; color: #64748b; pointer-events: none; user-select: none; background: #1e293b; border-right: 1px solid rgba(148, 163, 184, 0.2); min-width: 50px; text-align: right; z-index: 1;"></div>
                    <textarea 
                        id="htaccess-text-editor" 
                        style="width: 100%; min-height: 500px; padding: 1rem; padding-left: 60px; font-family: 'Courier New', monospace; font-size: 0.875rem; line-height: 1.6; background: #0f172a; color: #e2e8f0; border: none; resize: vertical; outline: none; tab-size: 2; position: relative; z-index: 2;"
                        spellcheck="false"
                        onscroll="syncHtaccessLineNumbersScroll()"
                        oninput="updateHtaccessLineNumbers()"
                    ></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#htaccess-editor-container {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

#htaccess-table tbody tr:hover {
    background: rgba(14, 165, 233, 0.05);
}

#htaccess-table tbody tr td {
    vertical-align: middle;
}

.htaccess-content-input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--color-border);
    border-radius: 0.25rem;
    background: var(--color-bg-secondary);
    color: var(--color-text);
    font-size: 0.875rem;
    font-family: 'Courier New', monospace;
}

.htaccess-content-input:focus {
    outline: 2px solid rgba(14, 165, 233, 0.5);
    outline-offset: 2px;
    border-color: rgba(14, 165, 233, 0.5);
}

.htaccess-content-input:disabled {
    background: transparent;
    border: none;
    cursor: default;
}

.htaccess-masked {
    font-family: 'Courier New', monospace;
    letter-spacing: 0.2em;
    color: var(--text-secondary);
}

.htaccess-checkbox {
    width: 1.25rem;
    height: 1.25rem;
    cursor: pointer;
    accent-color: #3b82f6;
}

.htaccess-comment-row {
    background: rgba(148, 163, 184, 0.05);
    font-style: italic;
}

.htaccess-empty-row {
    background: rgba(148, 163, 184, 0.02);
}

.env-line-numbers-hidden #htaccess-line-numbers-display {
    display: none;
}

.env-line-numbers-hidden #htaccess-text-editor {
    padding-left: 1rem !important;
}
</style>

<script>
let htaccessData = [];
let htaccessMode = 'table'; // 'table' or 'editor'
let htaccessOriginalContent = '';

// Load .htaccess data
function loadHtaccessData() {
    const loadingEl = document.getElementById('htaccess-loading');
    const errorEl = document.getElementById('htaccess-error');
    const contentEl = document.getElementById('htaccess-content');
    const messageEl = document.getElementById('htaccess-message');
    
    // Show loading, hide error and content
    loadingEl.style.display = 'block';
    errorEl.style.display = 'none';
    contentEl.style.display = 'none';
    messageEl.style.display = 'none';
    
    fetch('/admin/environment/htaccess.php?action=read')
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                throw new Error(result.error || 'Unknown error');
            }
            
            htaccessData = result.data || [];
            
            // Hide loading, show content
            loadingEl.style.display = 'none';
            contentEl.style.display = 'block';
            
            // Render based on current mode
            if (htaccessMode === 'table') {
                renderHtaccessTable();
            } else {
                renderHtaccessEditor();
            }
        })
        .catch(error => {
            console.error('Error loading .htaccess data:', error);
            loadingEl.style.display = 'none';
            errorEl.style.display = 'block';
            document.getElementById('htaccess-error-message').textContent = error.message || 'Failed to load .htaccess file';
        });
}

// Render table view
function renderHtaccessTable() {
    const tbody = document.getElementById('htaccess-table-body');
    if (!tbody) return;
    
    let html = '';
    
    htaccessData.forEach((item, index) => {
        const isComment = item.is_comment || false;
        const isEmpty = item.is_empty || false;
        const content = item.content || '';
        const original = item.original || '';
        const lineNumber = item.line_number || (index + 1);
        
        let rowClass = '';
        if (isComment) rowClass = 'htaccess-comment-row';
        else if (isEmpty) rowClass = 'htaccess-empty-row';
        
        html += `<tr class="${rowClass}" data-index="${index}" data-line-number="${lineNumber}">`;
        
        html += `
            <td style="padding: 1rem; font-size: 0.875rem; font-weight: 600; color: var(--text-secondary); text-align: right;">
                ${lineNumber}
            </td>
            <td style="padding: 1rem; font-size: 0.875rem;">
            `;
        
        if (isComment || isEmpty) {
            // Comment or empty row - show as read-only
            html += `<span style="font-family: 'Courier New', monospace; color: var(--text-secondary);">${escapeHtml(original)}</span>`;
        } else {
            // Regular content row
            const rowId = `htaccess-row-${index}`;
            html += `
                <input 
                    type="text" 
                    id="${rowId}-content" 
                    class="htaccess-content-input" 
                    value="${escapeHtml(content)}" 
                    data-line-number="${lineNumber}"
                    data-original-content="${escapeHtml(content)}"
                    disabled
                    onchange="markHtaccessRowChanged(${index})"
                >
            `;
        }
        
        html += `</td>`;
        
        if (!isComment && !isEmpty) {
            // Add checkboxes for non-comment, non-empty rows
            const rowId = `htaccess-row-${index}`;
            html += `
                <td style="padding: 1rem; text-align: center;">
                    <input 
                        type="checkbox" 
                        id="${rowId}-mask" 
                        class="htaccess-checkbox" 
                        checked
                        onchange="toggleHtaccessMask(${index})"
                    >
                </td>
                <td style="padding: 1rem; text-align: center;">
                    <input 
                        type="checkbox" 
                        id="${rowId}-editable" 
                        class="htaccess-checkbox" 
                        onchange="toggleHtaccessEditable(${index})"
                    >
                </td>
            `;
        } else {
            html += `<td colspan="2"></td>`;
        }
        
        html += '</tr>';
    });
    
    tbody.innerHTML = html;
    
    // Apply initial mask state
    htaccessData.forEach((item, index) => {
        if (!item.is_comment && !item.is_empty) {
            toggleHtaccessMask(index);
        }
    });
}

// Toggle mask for a row
function toggleHtaccessMask(index) {
    const row = document.querySelector(`tr[data-index="${index}"]`);
    if (!row) return;
    
    const maskCheckbox = document.getElementById(`htaccess-row-${index}-mask`);
    const contentInput = document.getElementById(`htaccess-row-${index}-content`);
    
    if (!maskCheckbox || !contentInput) return;
    
    if (maskCheckbox.checked) {
        // Mask the content
        const originalContent = contentInput.getAttribute('data-original-content') || contentInput.value;
        contentInput.setAttribute('data-masked', 'true');
        contentInput.value = '****';
        contentInput.classList.add('htaccess-masked');
    } else {
        // Show real content
        const originalContent = contentInput.getAttribute('data-original-content') || '';
        contentInput.removeAttribute('data-masked');
        contentInput.value = originalContent;
        contentInput.classList.remove('htaccess-masked');
    }
}

// Toggle editable for a row
function toggleHtaccessEditable(index) {
    const row = document.querySelector(`tr[data-index="${index}"]`);
    if (!row) return;
    
    const editableCheckbox = document.getElementById(`htaccess-row-${index}-editable`);
    const maskCheckbox = document.getElementById(`htaccess-row-${index}-mask`);
    const contentInput = document.getElementById(`htaccess-row-${index}-content`);
    
    if (!editableCheckbox || !maskCheckbox || !contentInput) return;
    
    if (editableCheckbox.checked) {
        // Enable editing
        contentInput.disabled = false;
        // Unmask automatically
        maskCheckbox.checked = false;
        toggleHtaccessMask(index);
    } else {
        // Disable editing
        contentInput.disabled = true;
        // Restore original content
        const originalContent = contentInput.getAttribute('data-original-content') || '';
        contentInput.value = originalContent;
        contentInput.setAttribute('data-masked', 'false');
        contentInput.classList.remove('htaccess-masked');
    }
}

// Mark row as changed
function markHtaccessRowChanged(index) {
    const contentInput = document.getElementById(`htaccess-row-${index}-content`);
    if (contentInput) {
        contentInput.style.borderColor = '#3b82f6';
    }
}

// Save from table mode
function saveHtaccessTable() {
    const updates = [];
    
    htaccessData.forEach((item, index) => {
        if (item.is_comment || item.is_empty) {
            return;
        }
        
        const contentInput = document.getElementById(`htaccess-row-${index}-content`);
        if (!contentInput) return;
        
        const lineNumber = parseInt(contentInput.getAttribute('data-line-number') || '0');
        const originalContent = contentInput.getAttribute('data-original-content') || '';
        const currentContent = contentInput.value;
        
        // Only include if content changed
        if (currentContent !== originalContent) {
            updates.push({
                line_number: lineNumber,
                content: currentContent
            });
        }
    });
    
    if (updates.length === 0) {
        showHtaccessMessage('No changes to save', 'info');
        return;
    }
    
    // Show loading
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i>Saving...';
    
    const formData = new FormData();
    formData.append('action', 'save_table');
    formData.append('updates', JSON.stringify(updates));
    
    fetch('/admin/environment/htaccess.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                throw new Error(result.error || 'Failed to save');
            }
            
            showHtaccessMessage(`Saved successfully! Backup created: ${result.backup}`, 'success');
            
            // Reload data
            setTimeout(() => {
                loadHtaccessData();
            }, 1000);
        })
        .catch(error => {
            console.error('Error saving .htaccess:', error);
            showHtaccessMessage('Error saving file: ' + error.message, 'error');
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        });
}

// Render editor view
function renderHtaccessEditor() {
    const editor = document.getElementById('htaccess-text-editor');
    if (!editor) return;
    
    // Reconstruct original content
    let content = '';
    htaccessData.forEach(item => {
        content += item.original + '\n';
    });
    
    htaccessOriginalContent = content.trim();
    editor.value = htaccessOriginalContent;
    
    // Update line numbers
    updateHtaccessLineNumbers();
}

// Sync line numbers scroll
function syncHtaccessLineNumbersScroll() {
    const editor = document.getElementById('htaccess-text-editor');
    const lineNumbersEl = document.getElementById('htaccess-line-numbers-display');
    if (editor && lineNumbersEl) {
        lineNumbersEl.scrollTop = editor.scrollTop;
    }
}

// Update line numbers display
function updateHtaccessLineNumbers() {
    const editor = document.getElementById('htaccess-text-editor');
    const lineNumbersEl = document.getElementById('htaccess-line-numbers-display');
    const wrapper = document.getElementById('htaccess-editor-wrapper');
    const showLineNumbers = document.getElementById('htaccess-line-numbers').checked;
    
    if (!editor || !lineNumbersEl || !wrapper) return;
    
    if (!showLineNumbers) {
        wrapper.classList.add('env-line-numbers-hidden');
        return;
    }
    
    wrapper.classList.remove('env-line-numbers-hidden');
    
    const lines = editor.value.split('\n');
    let lineNumbersHtml = '';
    lines.forEach((_, index) => {
        lineNumbersHtml += `${index + 1}<br>`;
    });
    lineNumbersEl.innerHTML = lineNumbersHtml;
    
    // Sync scroll position
    syncHtaccessLineNumbersScroll();
}

// Toggle line numbers
function toggleHtaccessLineNumbers() {
    updateHtaccessLineNumbers();
}

// Save from editor mode
function saveHtaccessEditor() {
    const editor = document.getElementById('htaccess-text-editor');
    if (!editor) return;
    
    const content = editor.value.trim();
    
    if (content === htaccessOriginalContent) {
        showHtaccessMessage('No changes to save', 'info');
        return;
    }
    
    // Show loading
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i>Saving...';
    
    const formData = new FormData();
    formData.append('action', 'save_editor');
    formData.append('content', content);
    
    fetch('/admin/environment/htaccess.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                throw new Error(result.error || 'Failed to save');
            }
            
            showHtaccessMessage(`Saved successfully! Backup created: ${result.backup}`, 'success');
            htaccessOriginalContent = content;
            
            // Reload data
            setTimeout(() => {
                loadHtaccessData();
            }, 1000);
        })
        .catch(error => {
            console.error('Error saving .htaccess:', error);
            showHtaccessMessage('Error saving file: ' + error.message, 'error');
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        });
}

// Toggle between table and editor mode
function toggleHtaccessMode() {
    const tableView = document.getElementById('htaccess-table-view');
    const editorView = document.getElementById('htaccess-editor-view');
    const modeIcon = document.getElementById('htaccess-mode-icon');
    const modeText = document.getElementById('htaccess-mode-text');
    
    if (htaccessMode === 'table') {
        // Switch to editor
        htaccessMode = 'editor';
        tableView.style.display = 'none';
        editorView.style.display = 'block';
        modeIcon.className = 'fas fa-code';
        modeText.textContent = 'Switch to Table';
        renderHtaccessEditor();
    } else {
        // Switch to table
        htaccessMode = 'table';
        tableView.style.display = 'block';
        editorView.style.display = 'none';
        modeIcon.className = 'fas fa-table';
        modeText.textContent = 'Switch to Text Editor';
        renderHtaccessTable();
    }
}

// Show message
function showHtaccessMessage(message, type) {
    const messageEl = document.getElementById('htaccess-message');
    const contentEl = document.getElementById('htaccess-message-content');
    
    if (!messageEl || !contentEl) return;
    
    messageEl.style.display = 'block';
    
    const colors = {
        'success': { bg: 'rgba(34, 197, 94, 0.1)', border: 'rgba(34, 197, 94, 0.3)', text: '#22c55e' },
        'error': { bg: 'rgba(239, 68, 68, 0.1)', border: 'rgba(239, 68, 68, 0.3)', text: '#ef4444' },
        'info': { bg: 'rgba(59, 130, 246, 0.1)', border: 'rgba(59, 130, 246, 0.3)', text: '#3b82f6' }
    };
    
    const color = colors[type] || colors.info;
    messageEl.style.background = color.bg;
    messageEl.style.border = `1px solid ${color.border}`;
    contentEl.style.color = color.text;
    contentEl.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}" style="margin-right: 0.5rem;"></i>${escapeHtml(message)}`;
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageEl.style.display = 'none';
    }, 5000);
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Auto-load when component is visible
if (typeof window !== 'undefined') {
    window.loadHtaccessData = loadHtaccessData;
    window.toggleHtaccessMode = toggleHtaccessMode;
    window.saveHtaccessTable = saveHtaccessTable;
    window.saveHtaccessEditor = saveHtaccessEditor;
    window.toggleHtaccessMask = toggleHtaccessMask;
    window.toggleHtaccessEditable = toggleHtaccessEditable;
    window.markHtaccessRowChanged = markHtaccessRowChanged;
    window.toggleHtaccessLineNumbers = toggleHtaccessLineNumbers;
    window.syncHtaccessLineNumbersScroll = syncHtaccessLineNumbersScroll;
    window.updateHtaccessLineNumbers = updateHtaccessLineNumbers;
    
    // Load data when accordion opens
    document.addEventListener('DOMContentLoaded', function() {
        // Check if this component is in an accordion
        const accordionHeader = document.getElementById('env-accordion-header-htaccess');
        if (accordionHeader) {
            // Use event delegation for accordion clicks
            const checkAndLoad = () => {
                setTimeout(() => {
                    const accordionContent = document.getElementById('env-accordion-htaccess');
                    if (accordionContent && accordionContent.getAttribute('aria-hidden') === 'false') {
                        const contentDiv = document.getElementById('htaccess-content');
                        if (contentDiv && contentDiv.style.display === 'none' && htaccessData.length === 0) {
                            loadHtaccessData();
                        }
                    }
                }, 150);
            };
            
            accordionHeader.addEventListener('click', checkAndLoad);
            
            // Also check on initial load if accordion is already open
            checkAndLoad();
        }
    });
}

</script>

