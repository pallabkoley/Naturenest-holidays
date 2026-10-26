<?php
/* =========================================================
   BOOKING SUBMIT HANDLER — Nature Nest Holidays
   Receives POST from pages/booking.php, saves to DB, returns JSON.
   ========================================================= */

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

/* Only POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

/* CSRF (soft check — session already started in config.php) */
$token = $_POST['csrf_token'] ?? '';
if (!empty($_SESSION['csrf_token']) && $token !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please reload the page.']);
    exit;
}

/* ---- Collect inputs ---- */
$name           = trim($_POST['name'] ?? '');
$email          = trim($_POST['email'] ?? '');
$phone          = trim($_POST['phone'] ?? '');
$journey_type   = trim($_POST['journey_type'] ?? '');
$travel_date    = trim($_POST['travel_date'] ?? '');
$return_date    = trim($_POST['return_date'] ?? '');
$destination    = trim($_POST['destination'] ?? '');
$adults         = max(1, (int)($_POST['adults'] ?? 1));
$children       = max(0, (int)($_POST['children'] ?? 0));
$custom_details = trim($_POST['custom_tour_details'] ?? '');
$special_req    = trim($_POST['special_requests'] ?? '');
$newsletter     = isset($_POST['newsletter']) ? 1 : 0;
$package_title  = trim($_POST['package_title'] ?? '');
$tour_id_in     = (int)($_POST['tour_package_id'] ?? 0);

/* ---- Validation ---- */
$errors = [];
if ($name === '')         $errors[] = 'Name is required.';
if ($phone === '')        $errors[] = 'Phone number is required.';
if ($travel_date === '')  $errors[] = 'Travel date is required.';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid email address.';
}
if (!preg_match('/^\d{10,15}$/', preg_replace('/\D/', '', $phone))) {
    $errors[] = 'Phone must be 10–15 digits.';
}
if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

/* ---- Normalize dates ---- */
$travel_date_sql = date('Y-m-d', strtotime($travel_date));
$return_date_sql = ($return_date !== '' && strtotime($return_date))
                   ? date('Y-m-d', strtotime($return_date))
                   : null;

/* ---- Get PDO (already created by config.php) ---- */
if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo json_encode(['success' => false, 'message' => 'Server error (no DB). Please try again.']);
    exit;
}

/* ---- Resolve tour_package_id — MUST match tour_packages.id (FK) ---- */
$tour_package_id = null;
try {
    /* 1. If explicit tour_id was given */
    if ($tour_id_in > 0) {
        $stmt = $pdo->prepare("SELECT id FROM tour_packages WHERE id = ? LIMIT 1");
        $stmt->execute([$tour_id_in]);
        if ($stmt->fetch()) $tour_package_id = $tour_id_in;
    }

    /* 2. Try by package_title in tour_packages */
    if ($tour_package_id === null && $package_title !== '') {
        $stmt = $pdo->prepare("SELECT id FROM tour_packages WHERE (title = ? OR slug = ?) LIMIT 1");
        $stmt->execute([$package_title, $package_title]);
        $row = $stmt->fetch();
        if ($row) {
            $tour_package_id = (int)$row['id'];
        } else {
            /* 3. Try tour_package_lists → get parent_slug → parent id in tour_packages */
            $stmt = $pdo->prepare("SELECT parent_slug FROM tour_package_lists WHERE (title = ? OR slug = ?) LIMIT 1");
            $stmt->execute([$package_title, $package_title]);
            $sub = $stmt->fetch();
            if ($sub && !empty($sub['parent_slug'])) {
                $stmt = $pdo->prepare("SELECT id FROM tour_packages WHERE slug = ? LIMIT 1");
                $stmt->execute([$sub['parent_slug']]);
                $parent = $stmt->fetch();
                if ($parent) $tour_package_id = (int)$parent['id'];
            }
        }
    }
} catch (Throwable $e) {
    error_log('booking-submit lookup: ' . $e->getMessage());
}

/* ---- Generate unique booking reference ---- */
function nn_generate_ref(PDO $pdo): string {
    for ($i = 0; $i < 8; $i++) {
        $ref = 'NN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE booking_reference = ? LIMIT 1");
        $stmt->execute([$ref]);
        if (!$stmt->fetch()) return $ref;
    }
    return 'NN-' . time();
}
$booking_ref = nn_generate_ref($pdo);

/* ---- Build notes ---- */
$notes_parts = [];
if ($custom_details !== '') $notes_parts[] = 'Custom details: ' . $custom_details;
if ($newsletter)            $notes_parts[] = 'Newsletter opt-in: yes';
$notes = implode("\n", $notes_parts);

/* ---- Insert ---- */
try {
    $sql = "INSERT INTO bookings
            (booking_reference, tour_package_id, custom_tour_details, name, email, phone,
             travel_date, return_date, destination, journey_type, adults, children,
             special_requests, status, payment_status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $booking_ref,
        $tour_package_id,
        $custom_details,
        $name,
        $email,              /* schema says NOT NULL — '' is fine */
        $phone,
        $travel_date_sql,
        $return_date_sql,
        $destination,
        $journey_type,
        $adults,
        $children,
        $special_req,
        $notes
    ]);

    /* Remember for confirmation page */
    $_SESSION['last_booking_ref'] = $booking_ref;

    echo json_encode([
        'success'     => true,
        'message'     => 'Thank you! Your booking request has been received. Our team will contact you within 24 hours.',
        'booking_ref' => $booking_ref
    ]);
} catch (Throwable $e) {
    error_log('booking-submit insert: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not save your booking. Please try again.']);
}