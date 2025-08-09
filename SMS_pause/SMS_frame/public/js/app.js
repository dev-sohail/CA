// SMS Framework - Main JavaScript File

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Copy to clipboard functionality
    $('.copy-notice-btn').click(function() {
        var noticeText = $(this).data('notice');
        navigator.clipboard.writeText(noticeText).then(function() {
            // Show success message
            var btn = $(this);
            var originalText = btn.html();
            btn.html('<i class="fas fa-check"></i> Copied!');
            setTimeout(function() {
                btn.html(originalText);
            }, 2000);
        }.bind(this));
    });

    // Attendance chart functionality
    if ($('#MyAttendanceChart').length) {
        initializeAttendanceChart();
    }

    // Form validation
    $('form').on('submit', function() {
        var isValid = true;
        
        // Check required fields
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // Password confirmation validation
        var password = $('#password');
        var confirmPassword = $('#confirm_password');
        if (password.length && confirmPassword.length) {
            if (password.val() !== confirmPassword.val()) {
                confirmPassword.addClass('is-invalid');
                isValid = false;
            } else {
                confirmPassword.removeClass('is-invalid');
            }
        }
        
        return isValid;
    });

    // Remove validation classes on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
    });
});

// Initialize attendance chart
function initializeAttendanceChart() {
    var ctx = document.getElementById('MyAttendanceChart').getContext('2d');
    
    // Get data from PHP variables (these should be set in the view)
    var months = window.attendanceMonths || [];
    var presentDays = window.attendancePresent || [];
    var absentDays = window.attendanceAbsent || [];
    var lateDays = window.attendanceLate || [];
    
    var chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Present Days',
                data: presentDays,
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }, {
                label: 'Absent Days',
                data: absentDays,
                backgroundColor: 'rgba(220, 53, 69, 0.8)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 1
            }, {
                label: 'Late Days',
                data: lateDays,
                backgroundColor: 'rgba(255, 193, 7, 0.8)',
                borderColor: 'rgba(255, 193, 7, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Monthly Attendance Overview'
                }
            }
        }
    });
}

// AJAX helper functions
function makeAjaxRequest(url, method, data, successCallback, errorCallback) {
    $.ajax({
        url: url,
        method: method,
        data: data,
        dataType: 'json',
        success: function(response) {
            if (successCallback) {
                successCallback(response);
            }
        },
        error: function(xhr, status, error) {
            if (errorCallback) {
                errorCallback(xhr, status, error);
            } else {
                showAlert('An error occurred. Please try again.', 'danger');
            }
        }
    });
}

// Show alert message
function showAlert(message, type) {
    var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                    message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>';
    
    $('.container').first().prepend(alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
}

// Loading spinner
function showLoading() {
    $('body').append('<div id="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; justify-content: center; align-items: center;"><div class="spinner"></div></div>');
}

function hideLoading() {
    $('#loading-overlay').remove();
}

// Utility functions
function formatDate(date) {
    return new Date(date).toLocaleDateString();
}

function formatDateTime(dateTime) {
    return new Date(dateTime).toLocaleString();
}

// Export functions
window.SMSFramework = {
    showAlert: showAlert,
    showLoading: showLoading,
    hideLoading: hideLoading,
    makeAjaxRequest: makeAjaxRequest,
    formatDate: formatDate,
    formatDateTime: formatDateTime
}; 