<?php
declare(strict_types=1);

/**
 * .env File Editor Component
 * Provides table view and text editor mode for managing .env file
 */
?>

<div id="env-editor-container" style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
    <!-- Loading State -->
    <div id="env-loading" style="text-align: center; padding: 3rem 1rem;">
        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 1rem; display: block;"></i>
        <p style="color: var(--text-secondary); opacity: 0.8;">Loading .env file...</p>
    </div>
    
    <!-- Error State -->
    <div id="env-error" style="display: none; padding: 1rem; background: rgba(239, 68, 68, 0.1); border-radius: 0.5rem; border: 1px solid rgba(239, 68, 68, 0.3); margin-bottom: 1rem;">
        <div style="color: #ef4444; font-weight: 600; margin-bottom: 0.5rem;">
            <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i>
            Error loading .env file
        </div>
        <div id="env-error-message" style="color: var(--text-secondary); font-size: 0.875rem;"></div>
        <button onclick="loadEnvData()" style="margin-top: 0.75rem; padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 0.5rem; cursor: pointer;">
            <i class="fas fa-redo" style="margin-right: 0.5rem;"></i>Retry
        </button>
    </div>
    
    <!-- Success/Error Messages -->
    <div id="env-message" style="display: none; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
        <div id="env-message-content" style="font-weight: 600;"></div>
    </div>
    
    <!-- Content Container -->
    <div id="env-content" style="display: none;">
        <!-- Mode Toggle -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid rgba(148, 163, 184, 0.2);">
            <div>
                <h3 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
                    .env File Editor
                </h3>
                <p style="color: var(--text-secondary); opacity: 0.8; font-size: 0.875rem;">
                    Manage your environment variables
                </p>
            </div>
            <button id="env-mode-toggle" onclick="toggleEnvMode()" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
                <i class="fas fa-table" id="env-mode-icon" style="margin-right: 0.5rem;"></i>
                <span id="env-mode-text">Switch to Text Editor</span>
            </button>
        </div>
        
        <!-- Table View -->
        <div id="env-table-view">
            <div style="margin-bottom: 1rem; display: flex; justify-content: flex-end; gap: 1rem;">
                <button onclick="saveEnvTable()" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                    <i class="fas fa-save" style="margin-right: 0.5rem;"></i>Save Changes
                </button>
            </div>
            
            <div style="overflow-x: auto; border-radius: 0.5rem; border: 1px solid var(--color-border);">
                <table id="env-table" style="width: 100%; border-collapse: collapse; background: var(--color-bg-secondary);">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--color-border); background: rgba(14, 165, 233, 0.1);">
                            <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: var(--text-primary); width: 25%;">KEY</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: var(--text-primary); width: 40%;">VALUE</th>
                            <th style="padding: 1rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: var(--text-primary); width: 15%;">MASK VALUE</th>
                            <th style="padding: 1rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: var(--text-primary); width: 15%;">EDITABLE</th>
                        </tr>
                    </thead>
                    <tbody id="env-table-body">
                        <!-- Table rows will be inserted here -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Text Editor View -->
        <div id="env-editor-view" style="display: none;">
            <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" id="env-line-numbers" checked onchange="toggleLineNumbers()" style="margin: 0; cursor: pointer;">
                        <span style="font-size: 0.875rem;">Show line numbers</span>
                    </label>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button onclick="loadEnvData()" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
                        <i class="fas fa-redo" style="margin-right: 0.5rem;"></i>Reload
                    </button>
                    <button onclick="saveEnvEditor()" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                        <i class="fas fa-save" style="margin-right: 0.5rem;"></i>Save Changes
                    </button>
                </div>
            </div>
            
            <div style="position: relative; border-radius: 0.5rem; border: 1px solid var(--color-border); overflow: hidden;">
                <div id="env-editor-wrapper" style="position: relative; background: #0f172a;">
                    <div id="env-line-numbers-display" style="position: absolute; left: 0; top: 0; padding: 1rem; font-family: 'Courier New', monospace; font-size: 0.875rem; line-height: 1.6; color: #64748b; pointer-events: none; user-select: none; background: #1e293b; border-right: 1px solid rgba(148, 163, 184, 0.2); min-width: 50px; text-align: right; z-index: 1;"></div>
                    <textarea 
                        id="env-text-editor" 
                        style="width: 100%; min-height: 500px; padding: 1rem; padding-left: 60px; font-family: 'Courier New', monospace; font-size: 0.875rem; line-height: 1.6; background: #0f172a; color: #e2e8f0; border: none; resize: vertical; outline: none; tab-size: 2; position: relative; z-index: 2;"
                        spellcheck="false"
                        onscroll="syncLineNumbersScroll()"
                        oninput="updateLineNumbers()"
                    ></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#env-editor-container {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

#env-table tbody tr:hover {
    background: rgba(14, 165, 233, 0.05);
}

#env-table tbody tr td {
    vertical-align: middle;
}

.env-value-input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--color-border);
    border-radius: 0.25rem;
    background: var(--color-bg-secondary);
    color: var(--color-text);
    font-size: 0.875rem;
    font-family: 'Courier New', monospace;
}

.env-value-input:focus {
    outline: 2px solid rgba(14, 165, 233, 0.5);
    outline-offset: 2px;
    border-color: rgba(14, 165, 233, 0.5);
}

.env-value-input:disabled {
    background: transparent;
    border: none;
    cursor: default;
}

.env-masked {
    font-family: 'Courier New', monospace;
    letter-spacing: 0.2em;
    color: var(--text-secondary);
}

.env-checkbox {
    width: 1.25rem;
    height: 1.25rem;
    cursor: pointer;
    accent-color: #3b82f6;
}

.env-comment-row {
    background: rgba(148, 163, 184, 0.05);
    font-style: italic;
}

.env-empty-row {
    background: rgba(148, 163, 184, 0.02);
}

.env-invalid-row {
    background: rgba(239, 68, 68, 0.1);
}

#env-text-editor {
    padding-left: 60px !important;
}

#env-line-numbers-display {
    display: block;
}

.env-line-numbers-hidden #env-line-numbers-display {
    display: none;
}

.env-line-numbers-hidden #env-text-editor {
    padding-left: 1rem !important;
}
</style>

<script>
let envData = [];
let envMode = 'table'; // 'table' or 'editor'
let envOriginalContent = '';

// Load .env data
function loadEnvData() {
    const loadingEl = document.getElementById('env-loading');
    const errorEl = document.getElementById('env-error');
    const contentEl = document.getElementById('env-content');
    const messageEl = document.getElementById('env-message');
    
    // Show loading, hide error and content
    loadingEl.style.display = 'block';
    errorEl.style.display = 'none';
    contentEl.style.display = 'none';
    messageEl.style.display = 'none';
    
    fetch('/admin/environment/env.php?action=read')
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                throw new Error(result.error || 'Unknown error');
            }
            
            envData = result.data || [];
            
            // Hide loading, show content
            loadingEl.style.display = 'none';
            contentEl.style.display = 'block';
            
            // Render based on current mode
            if (envMode === 'table') {
                renderEnvTable();
            } else {
                renderEnvEditor();
            }
        })
        .catch(error => {
            console.error('Error loading .env data:', error);
            loadingEl.style.display = 'none';
            errorEl.style.display = 'block';
            document.getElementById('env-error-message').textContent = error.message || 'Failed to load .env file';
        });
}

// Render table view
function renderEnvTable() {
    const tbody = document.getElementById('env-table-body');
    if (!tbody) return;
    
    let html = '';
    
    envData.forEach((item, index) => {
        const isComment = item.is_comment || false;
        const isEmpty = item.is_empty || false;
        const isInvalid = item.is_invalid || false;
        const key = item.key || '';
        const value = item.value || '';
        const original = item.original || '';
        
        let rowClass = '';
        if (isComment) rowClass = 'env-comment-row';
        else if (isEmpty) rowClass = 'env-empty-row';
        else if (isInvalid) rowClass = 'env-invalid-row';
        
        html += `<tr class="${rowClass}" data-index="${index}">`;
        
        if (isComment || isEmpty || isInvalid) {
            // Comment, empty, or invalid row
            html += `<td colspan="4" style="padding: 1rem; font-size: 0.875rem; color: var(--text-secondary); font-family: 'Courier New', monospace;">${escapeHtml(original)}</td>`;
        } else {
            // Regular key-value row
            const rowId = `env-row-${index}`;
            html += `
                <td style="padding: 1rem; font-size: 0.875rem; font-family: 'Courier New', monospace; font-weight: 600; color: var(--text-primary);">
                    ${escapeHtml(key)}
                </td>
                <td style="padding: 1rem; font-size: 0.875rem;">
                    <input 
                        type="text" 
                        id="${rowId}-value" 
                        class="env-value-input" 
                        value="${escapeHtml(value)}" 
                        data-key="${escapeHtml(key)}"
                        data-original-value="${escapeHtml(value)}"
                        disabled
                        onchange="markEnvRowChanged(${index})"
                    >
                </td>
                <td style="padding: 1rem; text-align: center;">
                    <input 
                        type="checkbox" 
                        id="${rowId}-mask" 
                        class="env-checkbox" 
                        checked
                        onchange="toggleEnvMask(${index})"
                    >
                </td>
                <td style="padding: 1rem; text-align: center;">
                    <input 
                        type="checkbox" 
                        id="${rowId}-editable" 
                        class="env-checkbox" 
                        onchange="toggleEnvEditable(${index})"
                    >
                </td>
            `;
        }
        
        html += '</tr>';
    });
    
    tbody.innerHTML = html;
    
    // Apply initial mask state
    envData.forEach((item, index) => {
        if (!item.is_comment && !item.is_empty && !item.is_invalid) {
            toggleEnvMask(index);
        }
    });
}

// Toggle mask for a row
function toggleEnvMask(index) {
    const row = document.querySelector(`tr[data-index="${index}"]`);
    if (!row) return;
    
    const maskCheckbox = document.getElementById(`env-row-${index}-mask`);
    const valueInput = document.getElementById(`env-row-${index}-value`);
    
    if (!maskCheckbox || !valueInput) return;
    
    if (maskCheckbox.checked) {
        // Mask the value
        const originalValue = valueInput.getAttribute('data-original-value') || valueInput.value;
        valueInput.setAttribute('data-masked', 'true');
        valueInput.value = '****';
        valueInput.classList.add('env-masked');
    } else {
        // Show real value
        const originalValue = valueInput.getAttribute('data-original-value') || '';
        valueInput.removeAttribute('data-masked');
        valueInput.value = originalValue;
        valueInput.classList.remove('env-masked');
    }
}

// Toggle editable for a row
function toggleEnvEditable(index) {
    const row = document.querySelector(`tr[data-index="${index}"]`);
    if (!row) return;
    
    const editableCheckbox = document.getElementById(`env-row-${index}-editable`);
    const maskCheckbox = document.getElementById(`env-row-${index}-mask`);
    const valueInput = document.getElementById(`env-row-${index}-value`);
    
    if (!editableCheckbox || !maskCheckbox || !valueInput) return;
    
    if (editableCheckbox.checked) {
        // Enable editing
        valueInput.disabled = false;
        // Unmask automatically
        maskCheckbox.checked = false;
        toggleEnvMask(index);
    } else {
        // Disable editing
        valueInput.disabled = true;
        // Restore original value
        const originalValue = valueInput.getAttribute('data-original-value') || '';
        valueInput.value = originalValue;
        valueInput.setAttribute('data-masked', 'false');
        valueInput.classList.remove('env-masked');
    }
}

// Mark row as changed
function markEnvRowChanged(index) {
    const valueInput = document.getElementById(`env-row-${index}-value`);
    if (valueInput) {
        valueInput.style.borderColor = '#3b82f6';
    }
}

// Save from table mode
function saveEnvTable() {
    const updates = [];
    
    envData.forEach((item, index) => {
        if (item.is_comment || item.is_empty || item.is_invalid) {
            return;
        }
        
        const valueInput = document.getElementById(`env-row-${index}-value`);
        if (!valueInput) return;
        
        const key = valueInput.getAttribute('data-key');
        const originalValue = valueInput.getAttribute('data-original-value') || '';
        const currentValue = valueInput.value;
        
        // Only include if value changed
        if (currentValue !== originalValue) {
            updates.push({
                key: key,
                value: currentValue
            });
        }
    });
    
    if (updates.length === 0) {
        showEnvMessage('No changes to save', 'info');
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
    
    fetch('/admin/environment/env.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                throw new Error(result.error || 'Failed to save');
            }
            
            showEnvMessage(`Saved successfully! Backup created: ${result.backup}`, 'success');
            
            // Reload data
            setTimeout(() => {
                loadEnvData();
            }, 1000);
        })
        .catch(error => {
            console.error('Error saving .env:', error);
            showEnvMessage('Error saving file: ' + error.message, 'error');
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        });
}

// Render editor view
function renderEnvEditor() {
    const editor = document.getElementById('env-text-editor');
    if (!editor) return;
    
    // Reconstruct original content
    let content = '';
    envData.forEach(item => {
        content += item.original + '\n';
    });
    
    envOriginalContent = content.trim();
    editor.value = envOriginalContent;
    
    // Update line numbers
    updateLineNumbers();
}

// Sync line numbers scroll
function syncLineNumbersScroll() {
    const editor = document.getElementById('env-text-editor');
    const lineNumbersEl = document.getElementById('env-line-numbers-display');
    if (editor && lineNumbersEl) {
        lineNumbersEl.scrollTop = editor.scrollTop;
    }
}

// Update line numbers display
function updateLineNumbers() {
    const editor = document.getElementById('env-text-editor');
    const lineNumbersEl = document.getElementById('env-line-numbers-display');
    const wrapper = document.getElementById('env-editor-wrapper');
    const showLineNumbers = document.getElementById('env-line-numbers').checked;
    
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
    syncLineNumbersScroll();
}

// Toggle line numbers
function toggleLineNumbers() {
    updateLineNumbers();
}

// Save from editor mode
function saveEnvEditor() {
    const editor = document.getElementById('env-text-editor');
    if (!editor) return;
    
    const content = editor.value.trim();
    
    if (content === envOriginalContent) {
        showEnvMessage('No changes to save', 'info');
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
    
    fetch('/admin/environment/env.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                throw new Error(result.error || 'Failed to save');
            }
            
            showEnvMessage(`Saved successfully! Backup created: ${result.backup}`, 'success');
            envOriginalContent = content;
            
            // Reload data
            setTimeout(() => {
                loadEnvData();
            }, 1000);
        })
        .catch(error => {
            console.error('Error saving .env:', error);
            showEnvMessage('Error saving file: ' + error.message, 'error');
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        });
}

// Toggle between table and editor mode
function toggleEnvMode() {
    const tableView = document.getElementById('env-table-view');
    const editorView = document.getElementById('env-editor-view');
    const modeIcon = document.getElementById('env-mode-icon');
    const modeText = document.getElementById('env-mode-text');
    
    if (envMode === 'table') {
        // Switch to editor
        envMode = 'editor';
        tableView.style.display = 'none';
        editorView.style.display = 'block';
        modeIcon.className = 'fas fa-code';
        modeText.textContent = 'Switch to Table';
        renderEnvEditor();
    } else {
        // Switch to table
        envMode = 'table';
        tableView.style.display = 'block';
        editorView.style.display = 'none';
        modeIcon.className = 'fas fa-table';
        modeText.textContent = 'Switch to Text Editor';
        renderEnvTable();
    }
}

// Show message
function showEnvMessage(message, type) {
    const messageEl = document.getElementById('env-message');
    const contentEl = document.getElementById('env-message-content');
    
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
    window.loadEnvData = loadEnvData;
    window.toggleEnvMode = toggleEnvMode;
    window.saveEnvTable = saveEnvTable;
    window.saveEnvEditor = saveEnvEditor;
    window.toggleEnvMask = toggleEnvMask;
    window.toggleEnvEditable = toggleEnvEditable;
    window.markEnvRowChanged = markEnvRowChanged;
    window.toggleLineNumbers = toggleLineNumbers;
    window.syncLineNumbersScroll = syncLineNumbersScroll;
    window.updateLineNumbers = updateLineNumbers;
    
    // Load data when accordion opens
    document.addEventListener('DOMContentLoaded', function() {
        // Check if this component is in an accordion
        const accordionHeader = document.getElementById('env-accordion-header-env');
        if (accordionHeader) {
            // Use event delegation for accordion clicks
            const checkAndLoad = () => {
                setTimeout(() => {
                    const accordionContent = document.getElementById('env-accordion-env');
                    if (accordionContent && accordionContent.getAttribute('aria-hidden') === 'false') {
                        const contentDiv = document.getElementById('env-content');
                        if (contentDiv && contentDiv.style.display === 'none' && envData.length === 0) {
                            loadEnvData();
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

