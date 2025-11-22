<?php
declare(strict_types=1);

/**
 * Reports Generator Component
 * Provides 5 different report types with CSV export and pagination
 */
?>

<div style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.6;">
    <!-- Header -->
    <div style="margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid rgba(148, 163, 184, 0.2);">
        <h3 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
            <?= t('admin.reports.title') ?>
        </h3>
        <p style="color: var(--text-secondary); opacity: 0.8; font-size: 0.875rem;">
            <?= t('admin.reports.subtitle') ?>
        </p>
    </div>

    <!-- Report Type Selection -->
    <div style="margin-bottom: 2rem;">
        <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">
            Selecciona un Tipo de Reporte / Select Report Type
        </h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
            <!-- Report A -->
            <div class="report-type-card" data-type="user_subscription" style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 2px solid rgba(59, 130, 246, 0.3); cursor: pointer; transition: all 0.3s;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div style="width: 48px; height: 48px; background: rgba(59, 130, 246, 0.2); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-users" style="font-size: 1.5rem; color: #3b82f6;"></i>
                    </div>
                    <div>
                        <div style="color: var(--text-primary); font-weight: 600; font-size: 1rem;">
                            <?= t('admin.reports.type_a') ?>
                        </div>
                        <div style="color: var(--text-secondary); font-size: 0.75rem; opacity: 0.8;">
                            Report A
                        </div>
                    </div>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.875rem; margin: 0;">
                    <?= t('admin.reports.type_a_desc') ?>
                </p>
            </div>

            <!-- Report B -->
            <div class="report-type-card" data-type="stripe_sync" style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 2px solid rgba(16, 185, 129, 0.3); cursor: pointer; transition: all 0.3s;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.2); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-sync-alt" style="font-size: 1.5rem; color: #10b981;"></i>
                    </div>
                    <div>
                        <div style="color: var(--text-primary); font-weight: 600; font-size: 1rem;">
                            <?= t('admin.reports.type_b') ?>
                        </div>
                        <div style="color: var(--text-secondary); font-size: 0.75rem; opacity: 0.8;">
                            Report B
                        </div>
                    </div>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.875rem; margin: 0;">
                    <?= t('admin.reports.type_b_desc') ?>
                </p>
            </div>

            <!-- Report C -->
            <div class="report-type-card" data-type="general_users" style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 2px solid rgba(139, 92, 246, 0.3); cursor: pointer; transition: all 0.3s;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div style="width: 48px; height: 48px; background: rgba(139, 92, 246, 0.2); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-database" style="font-size: 1.5rem; color: #8b5cf6;"></i>
                    </div>
                    <div>
                        <div style="color: var(--text-primary); font-weight: 600; font-size: 1rem;">
                            <?= t('admin.reports.type_c') ?>
                        </div>
                        <div style="color: var(--text-secondary); font-size: 0.75rem; opacity: 0.8;">
                            Report C
                        </div>
                    </div>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.875rem; margin: 0;">
                    <?= t('admin.reports.type_c_desc') ?>
                </p>
            </div>

            <!-- Report D -->
            <div class="report-type-card" data-type="custom_gmail" style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 2px solid rgba(245, 158, 11, 0.3); cursor: pointer; transition: all 0.3s;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div style="width: 48px; height: 48px; background: rgba(245, 158, 11, 0.2); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-search" style="font-size: 1.5rem; color: #f59e0b;"></i>
                    </div>
                    <div>
                        <div style="color: var(--text-primary); font-weight: 600; font-size: 1rem;">
                            <?= t('admin.reports.type_d') ?>
                        </div>
                        <div style="color: var(--text-secondary); font-size: 0.75rem; opacity: 0.8;">
                            Report D
                        </div>
                    </div>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.875rem; margin: 0;">
                    <?= t('admin.reports.type_d_desc') ?>
                </p>
            </div>

            <!-- Report E -->
            <div class="report-type-card" data-type="stripe_sanity" style="background: rgba(17, 24, 39, 0.3); padding: 1.25rem; border-radius: 0.5rem; border: 2px solid rgba(239, 68, 68, 0.3); cursor: pointer; transition: all 0.3s;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div style="width: 48px; height: 48px; background: rgba(239, 68, 68, 0.2); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-heartbeat" style="font-size: 1.5rem; color: #ef4444;"></i>
                    </div>
                    <div>
                        <div style="color: var(--text-primary); font-weight: 600; font-size: 1rem;">
                            <?= t('admin.reports.type_e') ?>
                        </div>
                        <div style="color: var(--text-secondary); font-size: 0.75rem; opacity: 0.8;">
                            Report E
                        </div>
                    </div>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.875rem; margin: 0;">
                    <?= t('admin.reports.type_e_desc') ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Report Generation Form (Hidden by default) -->
    <div id="report-form-container" style="display: none; margin-bottom: 2rem;">
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.5rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <h4 id="report-form-title" style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin-bottom: 1rem;"></h4>
            <form id="report-form">
                <input type="hidden" id="report-type" name="report_type" value="">
                
                <!-- Custom Gmail Form Fields (only for Report D) -->
                <div id="custom-gmail-fields" style="display: none; margin-bottom: 1rem;">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">
                            <?= t('admin.reports.gmail_input_type') ?>
                        </label>
                        <select id="gmail-input-type" name="input_type" style="width: 100%; padding: 0.5rem; background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 0.5rem; color: var(--text-primary);">
                            <option value="gmail_id"><?= t('admin.reports.gmail_id') ?></option>
                            <option value="gmail_account"><?= t('admin.reports.gmail_account') ?></option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">
                            <?= t('admin.reports.gmail_values') ?>
                        </label>
                        <input type="text" id="gmail-values" name="values" placeholder="<?= t('admin.reports.gmail_placeholder') ?>" style="width: 100%; padding: 0.5rem; background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 0.5rem; color: var(--text-primary);">
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" id="generate-btn" style="flex: 1; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">
                        <?= t('admin.reports.generate') ?>
                    </button>
                    <button type="button" id="cancel-btn" style="padding: 0.75rem 1.5rem; background: rgba(148, 163, 184, 0.2); color: var(--text-primary); border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Preview (Hidden by default) -->
    <div id="report-preview-container" style="display: none; margin-bottom: 2rem;">
        <div style="background: rgba(17, 24, 39, 0.3); padding: 1.5rem; border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600;">
                    <?= t('admin.reports.preview') ?>
                </h4>
                <a id="download-link" href="#" style="padding: 0.5rem 1rem; background: #10b981; color: white; border-radius: 0.5rem; text-decoration: none; font-weight: 600; font-size: 0.875rem;">
                    <i class="fas fa-download" style="margin-right: 0.5rem;"></i>
                    <?= t('admin.reports.download') ?>
                </a>
            </div>
            <div id="report-preview-table" style="overflow-x: auto;"></div>
            <div id="report-pagination" style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;"></div>
        </div>
    </div>

    <!-- Recent Reports -->
    <div style="margin-top: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h4 style="color: var(--text-primary); font-size: 1rem; font-weight: 600; margin: 0;">
                <?= t('admin.reports.recent_reports') ?>
            </h4>
            <button id="delete-all-btn" onclick="deleteAllReports()" style="padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-trash-alt"></i>
                <?= t('admin.reports.delete_all') ?>
            </button>
        </div>
        <div id="recent-reports-list" style="display: grid; gap: 0.75rem;">
            <!-- Reports will be loaded here -->
        </div>
    </div>
</div>

<style>
.report-type-card:hover {
    transform: translateY(-2px);
    border-color: currentColor !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

#report-preview-table table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

#report-preview-table th {
    background: rgba(0, 0, 0, 0.3);
    padding: 0.75rem;
    text-align: left;
    color: var(--text-primary);
    font-weight: 600;
    border-bottom: 2px solid rgba(148, 163, 184, 0.2);
}

#report-preview-table td {
    padding: 0.75rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.1);
    color: var(--text-secondary);
}

#report-preview-table tr:hover {
    background: rgba(0, 0, 0, 0.1);
}
</style>

<script>
(function() {
    let currentReportData = [];
    let currentPage = 1;
    const rowsPerPage = 50;
    let currentFilename = '';

    // Make functions globally accessible for onclick handlers
    window.deleteReport = function(filename, reportType) {
        if (!confirm('<?= t('admin.reports.delete_confirm') ?>')) {
            return;
        }
        
        fetch('/admin/reports/delete.php?file=' + encodeURIComponent(filename))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('<?= t('admin.reports.delete_success') ?>');
                    // Reload reports list
                    loadRecentReports();
                } else {
                    alert('<?= t('admin.reports.delete_error') ?>: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error deleting report:', error);
                alert('<?= t('admin.reports.delete_error') ?>');
            });
    };

    window.deleteAllReports = function() {
        if (!confirm('<?= t('admin.reports.delete_all_confirm') ?>')) {
            return;
        }
        
        const deleteBtn = document.getElementById('delete-all-btn');
        const originalText = deleteBtn.innerHTML;
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?= t('admin.reports.deleting') ?>';
        
        fetch('/admin/reports/delete_all.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('<?= t('admin.reports.delete_all_success') ?> (' + data.deleted_count + ' reports)');
                    // Reload reports list
                    loadRecentReports();
                } else {
                    alert('<?= t('admin.reports.delete_error') ?>: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error deleting all reports:', error);
                alert('<?= t('admin.reports.delete_error') ?>');
            })
            .finally(() => {
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = originalText;
            });
    };

    // Initialize all event handlers
    function initEventHandlers() {
        // Report type cards click handler
        document.querySelectorAll('.report-type-card').forEach(card => {
            // Only add listener if not already attached
            if (!card.dataset.listenerAttached) {
                card.addEventListener('click', function() {
                    const reportType = this.dataset.type;
                    showReportForm(reportType);
                });
                card.dataset.listenerAttached = 'true';
            }
        });

        // Cancel button
        const cancelBtn = document.getElementById('cancel-btn');
        if (cancelBtn && !cancelBtn.dataset.listenerAttached) {
            cancelBtn.addEventListener('click', function() {
                hideReportForm();
            });
            cancelBtn.dataset.listenerAttached = 'true';
        }

        // Form submit
        const reportForm = document.getElementById('report-form');
        if (reportForm && !reportForm.dataset.listenerAttached) {
            reportForm.addEventListener('submit', function(e) {
                e.preventDefault();
                generateReport();
            });
            reportForm.dataset.listenerAttached = 'true';
        }

        // Load recent reports
        loadRecentReports();
    }

    // Track if handlers are initialized to avoid duplicate listeners
    let handlersInitialized = false;
    
    function initializeReports() {
        if (handlersInitialized) return;
        
        // Check if elements exist
        const hasCards = document.querySelectorAll('.report-type-card').length > 0;
        const hasForm = document.getElementById('report-form');
        
        if (hasCards || hasForm) {
            initEventHandlers();
            handlersInitialized = true;
        }
    }
    
    // Initialize when DOM is ready
    function initOnReady() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initializeReports, 200);
            });
        } else {
            setTimeout(initializeReports, 200);
        }
    }
    
    // Also initialize when accordion opens (in case script runs before accordion is visible)
    function setupAccordionObserver() {
        const miscAccordion = document.getElementById('accordion-misc');
        if (miscAccordion) {
            const observer = new MutationObserver(function(mutations) {
                if (miscAccordion.getAttribute('aria-hidden') === 'false') {
                    // Accordion opened, ensure handlers are initialized
                    setTimeout(initializeReports, 100);
                }
            });
            observer.observe(miscAccordion, { attributes: true, attributeFilter: ['aria-hidden'] });
            
            // Also check immediately if accordion is already open
            if (miscAccordion.getAttribute('aria-hidden') === 'false') {
                setTimeout(initializeReports, 100);
            }
        }
    }
    
    // Start initialization
    initOnReady();
    setupAccordionObserver();
    
    // Also try to initialize immediately (in case everything is already loaded)
    setTimeout(initializeReports, 300);

    function showReportForm(reportType) {
        const formContainer = document.getElementById('report-form-container');
        const formTitle = document.getElementById('report-form-title');
        const reportTypeInput = document.getElementById('report-type');
        const customGmailFields = document.getElementById('custom-gmail-fields');
        
        reportTypeInput.value = reportType;
        
        // Set title based on report type
        const titles = {
            'user_subscription': '<?= t('admin.reports.type_a') ?>',
            'stripe_sync': '<?= t('admin.reports.type_b') ?>',
            'general_users': '<?= t('admin.reports.type_c') ?>',
            'custom_gmail': '<?= t('admin.reports.type_d') ?>',
            'stripe_sanity': '<?= t('admin.reports.type_e') ?>'
        };
        
        formTitle.textContent = titles[reportType] || 'Generate Report';
        
        // Show custom Gmail fields only for Report D
        if (reportType === 'custom_gmail') {
            customGmailFields.style.display = 'block';
            document.getElementById('gmail-values').required = true;
        } else {
            customGmailFields.style.display = 'none';
            document.getElementById('gmail-values').required = false;
        }
        
        formContainer.style.display = 'block';
        formContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideReportForm() {
        document.getElementById('report-form-container').style.display = 'none';
        document.getElementById('report-form').reset();
    }

    function generateReport() {
        const form = document.getElementById('report-form');
        const formData = new FormData(form);
        const reportType = formData.get('report_type');
        
        const params = {};
        if (reportType === 'custom_gmail') {
            params.input_type = formData.get('input_type');
            params.values = formData.get('values');
        }
        
        const generateBtn = document.getElementById('generate-btn');
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i><?= t('admin.reports.generating') ?>';
        
        fetch('/admin/reports/generate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                report_type: reportType,
                params: JSON.stringify(params)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentFilename = data.filename;
                loadReportPreview(data.filename);
                hideReportForm();
                loadRecentReports();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error generating report');
        })
        .finally(() => {
            generateBtn.disabled = false;
            generateBtn.textContent = '<?= t('admin.reports.generate') ?>';
        });
    }

    function loadReportPreview(filename) {
        // For now, we'll show a message that the report was generated
        // In a full implementation, you'd fetch the CSV and parse it for preview
        const previewContainer = document.getElementById('report-preview-container');
        const downloadLink = document.getElementById('download-link');
        
        downloadLink.href = '/admin/reports/download.php?file=' + encodeURIComponent(filename);
        previewContainer.style.display = 'block';
        
        // Show success message
        document.getElementById('report-preview-table').innerHTML = 
            '<div style="padding: 2rem; text-align: center; color: var(--text-secondary);">' +
            '<i class="fas fa-check-circle" style="color: #10b981; font-size: 3rem; margin-bottom: 1rem;"></i>' +
            '<p style="font-size: 1rem; margin-bottom: 0.5rem;"><?= t('admin.reports.success') ?></p>' +
            '<p style="font-size: 0.875rem; opacity: 0.8;">Click "Download CSV" to get the full report</p>' +
            '</div>';
    }

    function loadRecentReports() {
        fetch('/admin/reports/list.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayRecentReports(data.reports);
                }
            })
            .catch(error => {
                console.error('Error loading reports:', error);
            });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function displayRecentReports(reports) {
        const container = document.getElementById('recent-reports-list');
        
        if (reports.length === 0) {
            container.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--text-secondary); opacity: 0.8;"><?= t('admin.reports.no_reports') ?></div>';
            return;
        }
        
        const icons = {
            'user_subscription': 'fa-users',
            'stripe_sync': 'fa-sync-alt',
            'general_users': 'fa-database',
            'custom_gmail': 'fa-search',
            'stripe_sanity': 'fa-heartbeat'
        };
        
        const colors = {
            'user_subscription': '#3b82f6',
            'stripe_sync': '#10b981',
            'general_users': '#8b5cf6',
            'custom_gmail': '#f59e0b',
            'stripe_sanity': '#ef4444'
        };
        
        container.innerHTML = reports.map(report => {
            const icon = icons[report.type] || 'fa-file';
            const color = colors[report.type] || '#6b7280';
            
            return `
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; background: rgba(17, 24, 39, 0.3); border-radius: 0.5rem; border: 1px solid rgba(148, 163, 184, 0.1);">
                    <div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
                        <div style="width: 40px; height: 40px; background: rgba(${color.replace('#', '')}, 0.2); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                            <i class="fas ${icon}" style="color: ${color};"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="color: var(--text-primary); font-weight: 600; margin-bottom: 0.25rem;">
                                ${report.type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.75rem;">
                                ${report.created} • ${report.size_formatted}
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <a href="/admin/reports/download.php?file=${encodeURIComponent(report.filename)}" style="padding: 0.5rem 1rem; background: #3b82f6; color: white; border-radius: 0.5rem; text-decoration: none; font-weight: 600; font-size: 0.875rem;">
                            <i class="fas fa-download" style="margin-right: 0.5rem;"></i>
                            <?= t('admin.reports.download_previous') ?>
                        </a>
                        <button onclick="deleteReport('${escapeHtml(report.filename)}', '${escapeHtml(report.type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()))}')" style="padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; cursor: pointer;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }
})();
</script>

