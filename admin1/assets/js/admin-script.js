// Admin Panel JavaScript

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all admin features
    
    // Toggle password visibility in login forms
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');
    togglePasswordBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Auto-refresh dashboard data
    if (document.querySelector('.dashboard-content')) {
        setInterval(refreshDashboard, 30000); // Every 30 seconds
    }
    
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[title]');
    tooltips.forEach(el => {
        el.addEventListener('mouseenter', showTooltip);
        el.addEventListener('mouseleave', hideTooltip);
    });
    
    // Logout confirmation
    const logoutLinks = document.querySelectorAll('a[href*="logout"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to logout?')) {
                e.preventDefault();
            }
        });
    });
    
    // Table row click for details
    const tableRows = document.querySelectorAll('.table tbody tr[data-id]');
    tableRows.forEach(row => {
        row.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const type = this.getAttribute('data-type');
            if (id && type) {
                window.location.href = `edit-${type}.php?id=${id}`;
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', validateForm);
    });
});

// Dashboard auto-refresh
function refreshDashboard() {
    // You can implement AJAX refresh here
    console.log('Dashboard auto-refresh');
    
    // Example: Refresh stats
    fetch('api/dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            updateDashboardStats(data);
        })
        .catch(error => console.error('Refresh error:', error));
}

function updateDashboardStats(data) {
    // Update stats cards with new data
    const statElements = {
        'total-tours': '.stat-tours .stat-number',
        'total-bookings': '.stat-bookings .stat-number',
        'pending-bookings': '.stat-pending .stat-number',
        'total-messages': '.stat-messages .stat-number'
    };
    
    Object.keys(statElements).forEach(key => {
        const element = document.querySelector(statElements[key]);
        if (element && data[key]) {
            element.textContent = data[key];
        }
    });
}

// Tooltip functionality
function showTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.className = 'admin-tooltip';
    tooltip.textContent = this.getAttribute('title');
    this.appendChild(tooltip);
    
    // Position tooltip
    const rect = this.getBoundingClientRect();
    tooltip.style.left = '50%';
    tooltip.style.top = (rect.height + 5) + 'px';
    tooltip.style.transform = 'translateX(-50%)';
}

function hideTooltip() {
    const tooltip = this.querySelector('.admin-tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

// Form validation
function validateForm(e) {
    const form = e.target;
    let isValid = true;
    
    // Check required fields
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            markInvalid(field);
            isValid = false;
        } else {
            markValid(field);
        }
    });
    
    // Email validation
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            markInvalid(field, 'Please enter a valid email address');
            isValid = false;
        }
    });
    
    // Number validation
    const numberFields = form.querySelectorAll('input[type="number"]');
    numberFields.forEach(field => {
        const min = parseFloat(field.getAttribute('min'));
        const max = parseFloat(field.getAttribute('max'));
        
        if (!isNaN(min) && field.value && parseFloat(field.value) < min) {
            markInvalid(field, `Minimum value is ${min}`);
            isValid = false;
        }
        
        if (!isNaN(max) && field.value && parseFloat(field.value) > max) {
            markInvalid(field, `Maximum value is ${max}`);
            isValid = false;
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        showFormError('Please fix the errors in the form.');
    }
    
    return isValid;
}

function markInvalid(field, message) {
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');
    
    // Add error message
    let errorDiv = field.parentElement.querySelector('.invalid-feedback');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        field.parentElement.appendChild(errorDiv);
    }
    errorDiv.textContent = message || 'This field is required';
}

function markValid(field) {
    field.classList.remove('is-invalid');
    field.classList.add('is-valid');
    
    // Remove error message
    const errorDiv = field.parentElement.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function isValidEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

function showFormError(message) {
    // Create or show error alert
    let alertDiv = document.querySelector('.form-error-alert');
    if (!alertDiv) {
        alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger form-error-alert';
        document.querySelector('form').prepend(alertDiv);
    }
    alertDiv.textContent = message;
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// File upload preview
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px;">`;
        }
        
        reader.readAsDataURL(file);
    }
}

// Confirm delete actions
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Toggle sidebar on mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (window.innerWidth <= 768) {
        if (sidebar.style.display === 'none') {
            sidebar.style.display = 'block';
            mainContent.style.marginLeft = '250px';
        } else {
            sidebar.style.display = 'none';
            mainContent.style.marginLeft = '0';
        }
    }
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!');
    }).catch(err => {
        console.error('Copy failed:', err);
    });
}

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'admin-toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

// Search functionality
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    input.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// Initialize date pickers
function initDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.min) {
            const today = new Date().toISOString().split('T')[0];
            input.min = today;
        }
    });
}

// Export data
function exportTable(tableId, filename = 'export') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        
        cols.forEach(col => {
            rowData.push(col.textContent.trim());
        });
        
        csv.push(rowData.join(','));
    });
    
    const csvString = csv.join('\n');
    const blob = new Blob([csvString], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = `${filename}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}


$(document).ready(function() {
    // Initialize Select2
    $('#selected_tours').select2({
        placeholder: 'Select individual tours...',
        allowClear: true,
        width: '100%'
    });
    
    // Initialize Summernote
    $('.summernote').summernote({
        height: 200,
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['height', ['height']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });
    
    // Featured image preview - FIXED
    document.getElementById('featured_image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('featured_preview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });
    
    // Gallery images preview - FIXED
    document.getElementById('gallery_images').addEventListener('change', function(e) {
        const files = e.target.files;
        const previewContainer = document.getElementById('new_gallery_preview');
        previewContainer.innerHTML = '';
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const reader = new FileReader();
            
            reader.onload = (function(file, index) {
                return function(e) {
                    const div = document.createElement('div');
                    div.className = 'gallery-item';
                    div.style.position = 'relative';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'gallery-image';
                    img.style.width = '100px';
                    img.style.height = '80px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '6px';
                    img.style.border = '2px solid #e2e8f0';
                    
                    // Add remove button for each image
                    const removeBtn = document.createElement('span');
                    removeBtn.className = 'delete-gallery-image';
                    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    removeBtn.style.position = 'absolute';
                    removeBtn.style.top = '-5px';
                    removeBtn.style.right = '-5px';
                    removeBtn.style.background = '#e53e3e';
                    removeBtn.style.color = 'white';
                    removeBtn.style.width = '20px';
                    removeBtn.style.height = '20px';
                    removeBtn.style.borderRadius = '50%';
                    removeBtn.style.display = 'flex';
                    removeBtn.style.alignItems = 'center';
                    removeBtn.style.justifyContent = 'center';
                    removeBtn.style.cursor = 'pointer';
                    removeBtn.style.fontSize = '10px';
                    
                    removeBtn.onclick = function() {
                        removeGalleryImage(index);
                    };
                    
                    div.appendChild(img);
                    div.appendChild(removeBtn);
                    previewContainer.appendChild(div);
                };
            })(file, i);
            
            reader.readAsDataURL(file);
        }
    });
    
    // Function to remove gallery image from preview
    function removeGalleryImage(index) {
        const input = document.getElementById('gallery_images');
        const dt = new DataTransfer();
        const files = Array.from(input.files);
        
        // Remove file at index
        files.splice(index, 1);
        
        // Update file input
        files.forEach(file => dt.items.add(file));
        input.files = dt.files;
        
        // Refresh preview
        const event = new Event('change');
        input.dispatchEvent(event);
    }
    
    // FAQ management - FIXED
    let faqCount = 0;
    
    document.getElementById('add_faq').addEventListener('click', function() {
        faqCount++;
        const faqHtml = `
            <div class="faq-item" id="faq_${faqCount}">
                <span class="remove-faq" onclick="removeFaq(${faqCount})">
                    <i class="fas fa-trash"></i>
                </span>
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-question"></i>Question
                    </label>
                    <input type="text" name="faq_question[]" class="form-control" placeholder="Enter question" required>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-comment"></i>Answer
                    </label>
                    <textarea name="faq_answer[]" class="form-control" rows="3" placeholder="Enter answer" required></textarea>
                </div>
            </div>
        `;
        document.getElementById('faqs_container').insertAdjacentHTML('beforeend', faqHtml);
    });
    
    // Selected tours preview - FIXED
    $('#selected_tours').on('change', function() {
        const selected = $(this).val();
        const list = $('#selected_tours_list');
        list.empty();
        
        if (selected && selected.length > 0) {
            selected.forEach(function(tourId) {
                const optionText = $('#selected_tours option[value="' + tourId + '"]').text();
                list.append('<span class="tour-badge">' + optionText + '</span>');
            });
        } else {
            list.html('<p class="text-muted">No tours selected yet</p>');
        }
    });
    
    // Auto-generate slug and meta data from title
    $('#title').on('blur', function() {
        const title = $(this).val();
        if (title) {
            // Simple slug generation
            let slug = title.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            
            // Update meta title if empty
            if (!$('#meta_title').val()) {
                $('#meta_title').val(title + ' - Tour Package');
            }
            
            // Update meta description if empty
            if (!$('#meta_description').val()) {
                $('#meta_description').val(`Book ${title} - Best tour package with amazing experiences. Includes accommodation, transport, and guide services.`);
            }
            
            // Update meta keywords if empty
            if (!$('#meta_keywords').val()) {
                const keywords = title.toLowerCase().split(' ').filter(word => word.length > 3);
                keywords.push('tour package', 'travel', 'holiday', 'vacation');
                $('#meta_keywords').val(keywords.join(', '));
            }
        }
    });
    
    // Form validation
    $('#packageForm').submit(function(e) {
        const title = $('#title').val().trim();
        const durationDays = $('#duration_days').val();
        const price = $('#price').val();
        const selectedTours = $('#selected_tours').val();
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.error-message').remove();
        
        let hasError = false;
        
        if (!title) {
            $('#title').addClass('is-invalid').after('<div class="error-message text-danger mt-1">Please enter a package title</div>');
            hasError = true;
        }
        
        if (!durationDays || durationDays < 1) {
            $('#duration_days').addClass('is-invalid').after('<div class="error-message text-danger mt-1">Please enter valid duration (at least 1 day)</div>');
            hasError = true;
        }
        
        if (!price || price < 0) {
            $('#price').addClass('is-invalid').after('<div class="error-message text-danger mt-1">Please enter a valid price</div>');
            hasError = true;
        }
        
        if (!selectedTours || selectedTours.length === 0) {
            const select2Container = $('#selected_tours').closest('.form-group').find('.select2-container');
            select2Container.addClass('is-invalid');
            select2Container.css('border-color', '#e53e3e');
            $('#selected_tours').closest('.form-group').append('<div class="error-message text-danger mt-1">Please select at least one tour</div>');
            hasError = true;
        }
        
        if (hasError) {
            e.preventDefault();
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $('.is-invalid').first().offset().top - 100
            }, 500);
            return false;
        }
        
        // Show loading indicator
        $(this).find('.btn-primary').html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        
        return true;
    });
    
    // Initialize Select2 properly after page load
    setTimeout(function() {
        $('#selected_tours').trigger('change.select2');
    }, 100);
});

// Function to remove FAQ - MUST be global
function removeFaq(id) {
    const element = document.getElementById('faq_' + id);
    if (element) {
        element.remove();
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        $('#packageForm').submit();
    }
    
    // Escape to cancel
    if (e.key === 'Escape') {
        if (confirm('Are you sure you want to cancel?')) {
            window.location.href = 'manage-tour-lists.php';
        }
    }
});
