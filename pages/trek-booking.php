<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token for form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$trek_id = isset($_GET['trek_id']) ? intval($_GET['trek_id']) : 0;
$trek_details = null;
$popular_treks = [];
$error_message = '';

// Get database connection
$conn = $db->getConnection();

// Check if it's a specific trek booking
if ($trek_id > 0) {
    $stmt = $conn->prepare("SELECT id, name, price, duration, difficulty_level, max_altitude, featured_image, description FROM treks WHERE id = ? AND status = 1");
    if ($stmt) {
        $stmt->bind_param("i", $trek_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $trek_details = $result->fetch_assoc();
        }
        $stmt->close();
    } else {
        error_log("Error preparing trek details query: " . $conn->error);
    }
}

// Get popular treks for dropdown (if no specific trek selected)
$popular_query = "SELECT id, name, duration, difficulty_level, price FROM treks WHERE status = 1 ORDER BY id DESC LIMIT 5";
$popular_result = $conn->query($popular_query);

if ($popular_result) {
    while ($row = $popular_result->fetch_assoc()) {
        $popular_treks[] = $row;
    }
} else {
    error_log("Error in popular treks query: " . $conn->error);
}

// Set page metadata
$page_title = $trek_details ? "Book " . htmlspecialchars($trek_details['name']) . " Trek - Newa Yatra" : "Book Your Trek - Newa Yatra";
$meta_description = $trek_details ? "Book " . htmlspecialchars($trek_details['name']) . " trek with Newa Yatra. " . strip_tags(substr($trek_details['description'], 0, 150)) : "Book your trekking adventure with Newa Yatra. Choose from various Himalayan treks and customize your journey.";
$meta_keywords = "book trek, himalayan trek booking, trek reservation, adventure booking, nepal trek booking";

// Helper function to create slug if not exists in functions.php
if (!function_exists('createSlug')) {
    function createSlug($string) {
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9-]/', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        return trim($string, '-');
    }
}

ob_start();
?>

<?php include '../includes/header.php'; ?>

<!-- Add SweetAlert2 if not already included -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Breadcrumb -->
<section class="breadcrumb">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb-list">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/pages/trekking.php">Trekking</a></li>
                <?php if ($trek_details): ?>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/pages/trek-detail.php?id=<?php echo $trek_details['id']; ?>&slug=<?php echo createSlug($trek_details['name']); ?>"><?php echo htmlspecialchars($trek_details['name']); ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page">Trek Booking</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Booking Form -->
<section class="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-5">
                            <h1 class="h2 mb-3">Book Your Trek</h1>
                            <p class="text-muted">Fill in the details below to book your trekking adventure</p>
                            
                            <?php if ($trek_details): ?>
                                <div class="alert alert-info d-inline-block">
                                    <i class="fas fa-info-circle me-2"></i>
                                    You're booking: <strong><?php echo htmlspecialchars($trek_details['name']); ?></strong>
                                    (<?php echo $trek_details['duration']; ?> Days • <?php echo $trek_details['difficulty_level']; ?>)
                                </div>
                            <?php elseif (empty($popular_treks)): ?>
                                <div class="alert alert-danger d-inline-block">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    No treks available for booking at the moment. Please try again later.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning d-inline-block">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    No trek selected. Please select a trek from the dropdown below.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <form id="trekBookingForm" method="POST" action="<?php echo SITE_URL; ?>/process/trek-booking.php">
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="row">
                                <!-- Personal Details -->
                                <div class="col-lg-6">
                                    <div class="card mb-4 border-primary">
                                        <div class="card-header bg-primary text-white py-3">
                                            <h3 class="h5 mb-0"><i class="fas fa-user me-2"></i>Personal Details</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="name" name="name" required 
                                                       placeholder="Enter your full name as per ID proof"
                                                       value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" id="email" name="email" required 
                                                       placeholder="Enter your email address for confirmation"
                                                       value="<?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                                <input type="tel" class="form-control" id="phone" name="phone" required 
                                                       placeholder="Enter your contact number with country code"
                                                       value="<?php echo isset($_SESSION['user_phone']) ? htmlspecialchars($_SESSION['user_phone']) : ''; ?>">
                                                <small class="text-muted">We'll contact you on this number for trek updates</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="address" class="form-label">Address</label>
                                                <textarea class="form-control" id="address" name="address" rows="2" 
                                                          placeholder="Enter your full address"><?php echo isset($_SESSION['user_address']) ? htmlspecialchars($_SESSION['user_address']) : ''; ?></textarea>
                                                <small class="text-muted">Required for emergency contact and permits</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Trek Details -->
                                <div class="col-lg-6">
                                    <div class="card mb-4 border-primary">
                                        <div class="card-header bg-primary text-white py-3">
                                            <h3 class="h5 mb-0"><i class="fas fa-hiking me-2"></i>Trek Details</h3>
                                        </div>
                                        <div class="card-body">
                                            <?php if (!$trek_details && !empty($popular_treks)): ?>
                                            <div class="mb-3">
                                                <label for="trek_id" class="form-label">Select Trek <span class="text-danger">*</span></label>
                                                <select class="form-select" id="trek_id" name="trek_id" required>
                                                    <option value="">-- Select a Trek --</option>
                                                    <?php foreach ($popular_treks as $trek): ?>
                                                    <option value="<?php echo $trek['id']; ?>" 
                                                            data-price="<?php echo $trek['price']; ?>"
                                                            data-duration="<?php echo $trek['duration']; ?>"
                                                            data-difficulty="<?php echo $trek['difficulty_level']; ?>">
                                                        <?php echo htmlspecialchars($trek['name']); ?> - <?php echo $trek['duration']; ?> Days (₹<?php echo number_format($trek['price']); ?>)
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">Select the trek you want to book</small>
                                            </div>
                                            <?php elseif ($trek_details): ?>
                                                <input type="hidden" name="trek_id" value="<?php echo $trek_details['id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Selected Trek</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($trek_details['name']); ?> - <?php echo $trek_details['duration']; ?> Days" readonly disabled>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mb-3">
                                                <label for="travel_date" class="form-label">Preferred Start Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="travel_date" name="travel_date" required 
                                                       min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>"
                                                       value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>">
                                                <small class="text-muted">Select your preferred trek start date (minimum 7 days in advance)</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="number_of_trekkers" class="form-label">Number of Trekkers <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="number_of_trekkers" name="number_of_trekkers" 
                                                       min="1" max="20" value="<?php echo isset($_GET['trekkers']) ? intval($_GET['trekkers']) : 1; ?>" required>
                                                <small class="text-muted">Minimum 1 person, maximum 20 people per booking</small>
                                            </div>
                                            
                                            <!-- Dynamic Price Summary -->
                                            <div class="card bg-light mb-3" id="priceSummary" style="<?php echo (!$trek_details && empty($popular_treks)) ? 'display: none;' : ''; ?>">
                                                <div class="card-body">
                                                    <h6 class="mb-3">Price Summary</h6>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Price per person:</span>
                                                        <span class="fw-bold" id="pricePerPerson">
                                                            <?php if ($trek_details): ?>
                                                                ₹<?php echo number_format($trek_details['price'], 2); ?>
                                                            <?php else: ?>
                                                                ₹0.00
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Number of trekkers:</span>
                                                        <span class="fw-bold" id="trekkersCount"><?php echo isset($_GET['trekkers']) ? intval($_GET['trekkers']) : 1; ?></span>
                                                    </div>
                                                    <hr>
                                                    <div class="d-flex justify-content-between">
                                                        <span class="h6">Estimated Total:</span>
                                                        <span class="h5 text-primary" id="estimatedTotal">
                                                            <?php if ($trek_details): ?>
                                                                ₹<?php echo number_format($trek_details['price'] * (isset($_GET['trekkers']) ? intval($_GET['trekkers']) : 1), 2); ?>
                                                            <?php else: ?>
                                                                ₹0.00
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <small class="text-muted d-block mt-2">
                                                        *Final price may vary based on group size, season, and additional services
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Additional Information -->
                                <div class="col-12">
                                    <div class="card mb-4 border-primary">
                                        <div class="card-header bg-primary text-white py-3">
                                            <h3 class="h5 mb-0"><i class="fas fa-clipboard-list me-2"></i>Additional Information</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="special_requirements" class="form-label">Special Requirements</label>
                                                <textarea class="form-control" id="special_requirements" name="special_requirements" 
                                                          rows="4" placeholder="Please mention any special requirements such as:&#10;• Dietary restrictions (vegetarian, vegan, allergies)&#10;• Medical conditions (altitude sickness history, medications)&#10;• Accommodation preferences (single room, shared)&#10;• Any specific requests or questions"><?php echo isset($_GET['requirements']) ? htmlspecialchars($_GET['requirements']) : ''; ?></textarea>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="need_guide" name="need_guide" value="1" <?php echo isset($_GET['guide']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="need_guide">
                                                            Need a Guide <span class="text-muted">(Included in package)</span>
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="need_porter" name="need_porter" value="1" <?php echo isset($_GET['porter']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="need_porter">
                                                            Need Porter Service <span class="text-muted">(Additional cost)</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="need_gear" name="need_gear" value="1" <?php echo isset($_GET['gear']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="need_gear">
                                                            Need Trekking Gear Rental <span class="text-muted">(Sleeping bag, jacket, etc.)</span>
                                                        </label>
                                                    </div>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="need_pickup" name="need_pickup" value="1" <?php echo isset($_GET['pickup']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="need_pickup">
                                                            Need Airport/Hotel Pickup <span class="text-muted">(Free for groups of 4+)</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="insurance" name="insurance" value="1" <?php echo isset($_GET['insurance']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="insurance">
                                                    I have travel insurance that covers trekking and high altitude activities
                                                </label>
                                            </div>
                                            
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" checked>
                                                <label class="form-check-label" for="newsletter">
                                                    Send me trekking tips, deals, and updates from Newa Yatra
                                                </label>
                                            </div>
                                            
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                                <label class="form-check-label" for="terms">
                                                    I agree to the <a href="<?php echo SITE_URL; ?>/pages/terms.php" target="_blank">Terms & Conditions</a> and 
                                                    <a href="<?php echo SITE_URL; ?>/pages/privacy-policy.php" target="_blank">Privacy Policy</a>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Submit Section -->
                                <div class="col-12">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body text-center p-4">
                                            <div class="mb-4">
                                                <h4 class="mb-3">Ready for the Adventure?</h4>
                                                <p class="text-muted mb-0">Submit your trek booking request and our team will contact you within 24 hours</p>
                                            </div>
                                            
                                            <div class="d-flex flex-wrap gap-3 justify-content-center">
                                                <button type="submit" class="btn btn-primary btn-lg px-5" id="submitBtn" <?php echo (empty($trek_details) && empty($popular_treks)) ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-paper-plane me-2"></i>Submit Trek Booking
                                                </button>
                                                
                                                <button type="reset" class="btn btn-outline-primary btn-lg" id="resetBtn">
                                                    <i class="fas fa-redo me-2"></i>Reset Form
                                                </button>
                                            </div>
                                            
                                            <div class="mt-4">
                                                <p class="text-muted small mb-0">
                                                    <i class="fas fa-shield-alt me-1 text-primary"></i>
                                                    Your information is secure and will not be shared with third parties
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Support Information (keep your existing code) -->
                <!-- ... -->
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

<!-- Add this debugging script -->
<script>
// Debug function to check form submission
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, form exists:', document.getElementById('trekBookingForm') !== null);
    
    // Test if fetch is working
    console.log('Fetch API available:', typeof fetch !== 'undefined');
    
    // Log the form action URL
    const form = document.getElementById('trekBookingForm');
    if (form) {
        console.log('Form action URL:', form.action);
        console.log('Form method:', form.method);
    }
});

// Updated form submission with better error handling
document.getElementById('trekBookingForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Form submission started');
    
    // Basic validation
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const trekIdInput = document.getElementById('trek_id');
    const trekId = trekIdInput ? trekIdInput.value : '<?php echo $trek_id; ?>';
    const travelDate = document.getElementById('travel_date').value;
    const trekkers = document.getElementById('number_of_trekkers').value;
    const terms = document.getElementById('terms').checked;
    
    console.log('Form data:', { name, email, phone, trekId, travelDate, trekkers, terms });
    
    let errors = [];
    
    if (!name) errors.push('Full name is required');
    if (name.length < 3) errors.push('Name must be at least 3 characters long');
    
    if (!email) {
        errors.push('Email address is required');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errors.push('Please enter a valid email address');
    }
    
    if (!phone) {
        errors.push('Phone number is required');
    } else if (!/^[\d\+\-\(\)\s]{8,20}$/.test(phone)) {
        errors.push('Please enter a valid phone number (8-20 digits)');
    }
    
    if (!trekId || trekId === '') {
        errors.push('Please select a trek');
    }
    
    if (!travelDate) {
        errors.push('Preferred trek start date is required');
    } else {
        const selectedDate = new Date(travelDate);
        const minDate = new Date();
        minDate.setDate(minDate.getDate() + 7);
        minDate.setHours(0, 0, 0, 0);
        
        if (selectedDate < minDate) {
            errors.push('Trek start date must be at least 7 days from today');
        }
    }
    
    if (trekkers < 1) errors.push('At least 1 trekker is required');
    if (trekkers > 20) errors.push('Maximum 20 trekkers allowed for safety');
    
    if (!terms) errors.push('You must agree to the Terms & Conditions');
    
    // If there are errors, show them
    if (errors.length > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: errors.join('<br>'),
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff6b35'
        });
        return;
    }
    
    // Confirm booking
    Swal.fire({
        title: 'Confirm Booking',
        text: 'Are you sure you want to submit this trek booking request?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ff6b35',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, submit it!'
    }).then((result) => {
        if (result.isConfirmed) {
            submitForm();
        }
    });
});

function submitForm() {
    console.log('submitForm function called');
    
    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    submitBtn.disabled = true;
    
    // Submit form via AJAX
    const formData = new FormData(document.getElementById('trekBookingForm'));
    
    // Log form data for debugging
    console.log('Submitting form data:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    const url = '<?php echo SITE_URL; ?>/process/trek-booking.php';
    console.log('Fetch URL:', url);
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Check if response is ok
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.text().then(text => {
            try {
                // Try to parse as JSON
                const data = JSON.parse(text);
                console.log('Parsed JSON response:', data);
                return data;
            } catch (e) {
                // If not JSON, show the raw response for debugging
                console.error('Response is not JSON:', text);
                throw new Error('Invalid JSON response from server');
            }
        });
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Trek Booking Submitted!',
                html: `
                    <p>${data.message || 'Your booking has been submitted successfully!'}</p>
                    <div class="alert alert-info mt-3">
                        <p class="h5 mb-2">Booking Reference: <strong>${data.booking_ref || 'N/A'}</strong></p>
                        <p class="small mb-0">Please save this reference for future correspondence</p>
                    </div>
                    <p class="mt-3">You will receive a confirmation email shortly.</p>
                `,
                confirmButtonText: 'View Booking Details',
                confirmButtonColor: '#ff6b35'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?php echo SITE_URL; ?>/pages/trek-booking-confirmation.php?ref=' + data.booking_ref;
                } else {
                    // Reset form on success
                    document.getElementById('trekBookingForm').reset();
                }
            });
            
            // Clear saved data from localStorage
            localStorage.removeItem('trekBookingForm');
            
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Booking Failed',
                text: data.message || 'An error occurred. Please try again.',
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#ff6b35'
            });
        }
    })
    .catch(error => {
        console.error('Error details:', error);
        Swal.fire({
            icon: 'error',
            title: 'Submission Error',
            html: `
                <p>${error.message}</p>
                <p class="mt-3">Please check the console for more details and contact support.</p>
            `,
            confirmButtonText: 'OK',
            confirmButtonColor: '#ff6b35'
        });
    })
    .finally(() => {
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Rest of your existing JavaScript code...
// (Keep all your existing code for price calculation, auto-save, etc.)
</script>

<?php
$content = ob_get_clean();
echo $content;
?>