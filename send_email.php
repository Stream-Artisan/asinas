<?php
// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuration constants
define('ADMIN_EMAIL', 'noreply@asinas.edu.pk');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);
define('NEWSLETTER_FILE', 'newsletter_subscribers.txt');

// Helper function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Helper function to format POST data as HTML table
function format_post_data($data) {
    $html = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
    foreach ($data as $key => $value) {
        if ($key === 'form_type') continue;
        $html .= '<tr>';
        $html .= '<td style="background-color: #f2f2f2; font-weight: bold;">' . ucwords(str_replace('_', ' ', $key)) . '</td>';
        $html .= '<td>' . (is_array($value) ? htmlspecialchars(json_encode($value)) : sanitize_input($value)) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    return $html;
}

// Helper function to send emails (with or without attachments)
function send_email($to, $subject, $htmlBody, $from, $replyTo, $files = []) {
    // Prevent email header injection
    if (preg_match("/[\r\n]/", $to . $from . $replyTo)) {
        error_log("Email header injection attempt detected: to=$to, from=$from, replyTo=$replyTo");
        return false;
    }

    $boundary = md5(uniqid(time()));
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $replyTo\r\n";
    $headers .= "MIME-Version: 1.0\r\n";

    if (empty($files)) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body = $htmlBody;
    } else {
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        $body = "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $htmlBody . "\r\n";

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $maxFiles = 5; // Limit to prevent resource exhaustion
        $fileCount = 0;

        foreach ($files as $file) {
            if ($fileCount >= $maxFiles) {
                error_log("Maximum file limit ($maxFiles) reached.");
                break;
            }
            if (!empty($file['tmp_name']) && is_uploaded_file($file['tmp_name']) && $file['error'] === UPLOAD_ERR_OK && $file['size'] <= MAX_FILE_SIZE) {
                $filetype = finfo_file($finfo, $file['tmp_name']);
                if (in_array($filetype, ALLOWED_FILE_TYPES)) {
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $extension;
                    $filedata = file_get_contents($file['tmp_name']);
                    $body .= "--$boundary\r\n";
                    $body .= "Content-Type: $filetype; name=\"$filename\"\r\n";
                    $body .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
                    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                    $body .= chunk_split(base64_encode($filedata)) . "\r\n";
                    $fileCount++;
                } else {
                    error_log("Invalid file type for file: " . json_encode($file));
                }
            } else {
                error_log("Invalid file: " . json_encode($file));
            }
        }
        finfo_close($finfo);
        $body .= "--$boundary--";
    }

    $result = mail($to, $subject, $body, $headers);
    if (!$result) {
        error_log("Failed to send email to $to with subject: $subject");
    }
    return $result;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$formType = isset($_POST['form_type']) ? sanitize_input($_POST['form_type']) : '';
$validFormTypes = ['admission', 'contact', 'newsletter'];
if (!in_array($formType, $validFormTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid form type.']);
    exit;
}

$to = ADMIN_EMAIL;
$studentEmail = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
$from = ADMIN_EMAIL;
$replyTo = filter_var($studentEmail, FILTER_VALIDATE_EMAIL) ? $studentEmail : $from;

if ($formType === 'admission') {
    if (empty($studentEmail) || !filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }

    $subject = 'Admission Form Submission';
    $htmlBody = '<html><body>';
    $htmlBody .= '<h2>Admission Form Submission</h2>';
    $htmlBody .= format_post_data($_POST);
    $htmlBody .= '</body></html>';

    // Collect uploaded files
    $files = [];
    foreach ($_FILES as $file) {
        if (is_array($file['name'])) {
            for ($i = 0; $i < count($file['name']); $i++) {
                if (!empty($file['name'][$i]) && $file['error'][$i] === UPLOAD_ERR_OK) {
                    $files[] = [
                        'name' => $file['name'][$i],
                        'type' => $file['type'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error' => $file['error'][$i],
                        'size' => $file['size'][$i]
                    ];
                } elseif ($file['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds server size limit (max 5MB).',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit (max 5MB).',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded.'
                    ];
                    echo json_encode(['success' => false, 'message' => $errorMessages[$file['error'][$i]] ?? 'File upload error.']);
                    exit;
                }
            }
        } else {
            if (!empty($file['name']) && $file['error'] === UPLOAD_ERR_OK) {
                $files[] = $file;
            } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds server size limit (max 5MB).',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit (max 5MB).',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded.'
                ];
                echo json_encode(['success' => false, 'message' => $errorMessages[$file['error']] ?? 'File upload error.']);
                exit;
            }
        }
    }

    $adminMailSent = send_email($to, $subject, $htmlBody, $from, $replyTo, $files);

    // Confirmation to student
    $studentMailSent = false;
    if (filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
        $studentSubject = 'Thank You for Your Submission - All Saints’ Institute';
        $studentMessage = '<html><body>';
        $studentMessage .= '<p>Dear Student,</p>';
        $studentMessage .= '<p>Thank you for submitting your application to <strong>All Saints’ Institute of Nursing & Allied Sciences</strong>.</p>';
        $studentMessage .= '<p>We have received your details and will get back to you shortly.</p>';
        $studentMessage .= '<br><p>Regards,<br>Admissions Office<br>All Saints’ Institute</p>';
        $studentMessage .= '</body></html>';
        $studentMailSent = send_email($studentEmail, $studentSubject, $studentMessage, $from, $from);
        if (!$studentMailSent) {
            error_log("Failed to send confirmation email to $studentEmail for admission form: " . json_encode($_POST));
        }
    }

    echo json_encode([
        'success' => $adminMailSent,
        'message' => $adminMailSent ? 'Form submitted successfully. Confirmation email sent to student: ' . ($studentMailSent ? 'Yes' : 'No') : 'Failed to send admin email. Please try again later.'
    ]);
    exit;
}

if ($formType === 'contact') {
    if (empty($studentEmail) || !filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $number = isset($_POST['number']) ? sanitize_input($_POST['number']) : '';
    $message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';

    if (empty($name) || empty($number) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields (name, phone number, message).']);
        exit;
    }

    if (!preg_match('/^\+92[0-9]{10}$/', $number)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format. Please use +923001234567.']);
        exit;
    }

    $subject = 'Contact Form Submission';
    $htmlBody = '<html><body>';
    $htmlBody .= '<h2>Contact Form Submission</h2>';
    $htmlBody .= format_post_data($_POST);
    $htmlBody .= '</body></html>';

    $adminMailSent = send_email($to, $subject, $htmlBody, $from, $replyTo);

    // Confirmation to student
    $studentMailSent = false;
    if (filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
        $studentSubject = 'Thank You for Contacting All Saints’ Institute';
        $studentMessage = '<html><body>';
        $studentMessage .= '<p>Dear ' . htmlspecialchars($name) . ',</p>';
        $studentMessage .= '<p>Thank you for reaching out to <strong>All Saints’ Institute of Nursing & Allied Sciences</strong>.</p>';
        $studentMessage .= '<p>We have received your message and will get back to you soon.</p>';
        $studentMessage .= '<br><p>Regards,<br>Admissions Office<br>All Saints’ Institute</p>';
        $studentMessage .= '</body></html>';
        $studentMailSent = send_email($studentEmail, $studentSubject, $studentMessage, $from, $from);
        if (!$studentMailSent) {
            error_log("Failed to send confirmation email to $studentEmail for contact form: " . json_encode($_POST));
        }
    }

    echo json_encode([
        'success' => $adminMailSent,
        'message' => $adminMailSent ? 'Message sent successfully. Confirmation email sent to student: ' . ($studentMailSent ? 'Yes' : 'No') : 'Failed to send admin email. Please try again later.'
    ]);
    exit;
}

if ($formType === 'newsletter') {
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }

    // Check for duplicate subscription
    if (file_exists(NEWSLETTER_FILE)) {
        $existing = file_get_contents(NEWSLETTER_FILE);
        if (strpos($existing, $email . ',') !== false) {
            echo json_encode(['success' => false, 'message' => 'This email is already subscribed to the newsletter.']);
            exit;
        }
    }

    // Store email in file
    $emailEntry = $email . ',' . date('Y-m-d H:i:s') . "\n";
    file_put_contents(NEWSLETTER_FILE, $emailEntry, FILE_APPEND | LOCK_EX);

    // Send notification to admin
    $subject = 'New Newsletter Subscription';
    $htmlBody = '<html><body>';
    $htmlBody .= '<h2>New Newsletter Subscription</h2>';
    $htmlBody .= '<p>Email: ' . htmlspecialchars($email) . '</p>';
    $htmlBody .= '<p>Subscribed on: ' . date('Y-m-d H:i:s') . '</p>';
    $htmlBody .= '</body></html>';

    $adminMailSent = send_email($to, $subject, $htmlBody, $from, $from);

    // Confirmation to subscriber
    $subscriberMailSent = false;
    $subscriberSubject = 'Welcome to All Saints’ Institute Newsletter';
    $subscriberMessage = '<html><body>';
    $subscriberMessage .= '<p>Dear Subscriber,</p>';
    $subscriberMessage .= '<p>Thank you for subscribing to the <strong>All Saints’ Institute of Nursing & Allied Sciences</strong> newsletter.</p>';
    $subscriberMessage .= '<p>You will receive updates on our programs, admissions, and more.</p>';
    $subscriberMessage .= '<br><p>Regards,<br>All Saints’ Institute</p>';
    $subscriberMessage .= '</body></html>';
    $subscriberMailSent = send_email($email, $subscriberSubject, $subscriberMessage, $from, $from);
    if (!$subscriberMailSent) {
        error_log("Failed to send confirmation email to $email for newsletter: " . json_encode($_POST));
    }

    echo json_encode([
        'success' => $adminMailSent && $subscriberMailSent,
        'message' => $adminMailSent && $subscriberMailSent ? 'Thank you for subscribing to our newsletter!' : 'Subscription recorded, but failed to send confirmation email.'
    ]);
    exit;
}
?>