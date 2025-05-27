<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Helper function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Helper function to format POST data as HTML table
function format_post_data($data) {
    $html = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
    foreach ($data as $key => $value) {
        if ($key === 'form_type') continue;
        $html .= '<tr>';
        $html .= '<td style="background-color: #f2f2f2; font-weight: bold;">' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</td>';
        if (is_array($value)) {
            $html .= '<td>' . htmlspecialchars(json_encode($value)) . '</td>';
        } else {
            $html .= '<td>' . htmlspecialchars(sanitize_input($value)) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table>';
    return $html;
}

// Helper function to build a multipart email with attachments
function build_mail_with_attachments($to, $subject, $htmlBody, $from, $replyTo, $files = []) {
    $boundary = md5(uniqid(time()));
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $replyTo\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $htmlBody . "\r\n";

    // Attach files with validation
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    foreach ($files as $file) {
        if (!empty($file['tmp_name']) && is_uploaded_file($file['tmp_name']) && $file['error'] === UPLOAD_ERR_OK && $file['size'] <= $maxSize && in_array($file['type'], $allowedTypes)) {
            $filename = preg_replace('/[^A-Za-z0-9\-\_\.]/', '', basename($file['name']));
            $filedata = file_get_contents($file['tmp_name']);
            $filetype = $file['type'] ?: "application/octet-stream";
            $body .= "--$boundary\r\n";
            $body .= "Content-Type: $filetype; name=\"$filename\"\r\n";
            $body .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($filedata)) . "\r\n";
        } else {
            error_log("Invalid file: " . json_encode($file));
        }
    }
    $body .= "--$boundary--";

    $result = mail($to, $subject, $body, $headers);
    if (!$result) {
        error_log("Failed to send email to $to with subject: $subject");
    }
    return $result;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = isset($_POST['form_type']) ? sanitize_input($_POST['form_type']) : '';
    $to = 'noreply@asinas.edu.pk';
    $studentEmail = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $from = 'noreply@asinas.edu.pk';
    $replyTo = filter_var($studentEmail, FILTER_VALIDATE_EMAIL) ? $studentEmail : $from;

    if ($formType === 'admission') {
        // Validate required fields
        if (empty($studentEmail) || !filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or missing email address.'
            ]);
            exit;
        }

        $subject = 'Admission Form Submission';
        $htmlBody = '<html><body>';
        $htmlBody .= '<h2>Admission Form Submission</h2>';
        $htmlBody .= format_post_data($_POST);
        $htmlBody .= '</body></html>';

        // Collect all uploaded files
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
                    }
                }
            } else {
                if (!empty($file['name']) && $file['error'] === UPLOAD_ERR_OK) {
                    $files[] = $file;
                }
            }
        }

        $adminMailSent = build_mail_with_attachments($to, $subject, $htmlBody, $from, $replyTo, $files);

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
            $studentHeaders = "From: noreply@asinas.edu.pk\r\n";
            $studentHeaders .= "Reply-To: noreply@asinas.edu.pk\r\n";
            $studentHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
            $studentMailSent = mail($studentEmail, $studentSubject, $studentMessage, $studentHeaders);
            if (!$studentMailSent) {
                error_log("Failed to send confirmation email to $studentEmail");
            }
        }

        echo json_encode([
            'success' => $adminMailSent,
            'message' => $adminMailSent ? 'Form submitted successfully. Confirmation email sent to student: ' . ($studentMailSent ? 'Yes' : 'No') : 'Failed to send admin email. Please try again later.'
        ]);
        exit;
    }

    if ($formType === 'contact') {
        // Validate required fields
        if (empty($studentEmail) || !filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or missing email address.'
            ]);
            exit;
        }
        $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
        $number = isset($_POST['number']) ? sanitize_input($_POST['number']) : '';
        $message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';

        if (empty($name) || empty($number) || empty($message)) {
            echo json_encode([
                'success' => false,
                'message' => 'All fields are required.'
            ]);
            exit;
        }

        // Validate phone number format (e.g., +923001234567)
        if (!preg_match('/^\+92[0-9]{10}$/', $number)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid phone number format. Use +92 followed by 10 digits.'
            ]);
            exit;
        }

        $subject = 'Contact Form Submission';
        $htmlBody = '<html><body>';
        $htmlBody .= '<h2>Contact Form Submission</h2>';
        $htmlBody .= format_post_data($_POST);
        $htmlBody .= '</body></html>';
        $headers = "From: $from\r\n";
        $headers .= "Reply-To: $replyTo\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $adminMailSent = mail($to, $subject, $htmlBody, $headers);
        if (!$adminMailSent) {
            error_log("Failed to send contact form email to $to");
        }

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
            $studentHeaders = "From: noreply@asinas.edu.pk\r\n";
            $studentHeaders .= "Reply-To: noreply@asinas.edu.pk\r\n";
            $studentHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
            $studentMailSent = mail($studentEmail, $studentSubject, $studentMessage, $studentHeaders);
            if (!$studentMailSent) {
                error_log("Failed to send confirmation email to $studentEmail for contact form");
            }
        }

        echo json_encode([
            'success' => $adminMailSent,
            'message' => $adminMailSent ? 'Message sent successfully. Confirmation email sent to student: ' . ($studentMailSent ? 'Yes' : 'No') : 'Failed to send admin email. Please try again later.'
        ]);
        exit;
    }

    if ($formType === 'newsletter' || (empty($formType) && isset($_POST['email']) && count($_POST) === 2)) {
        // Newsletter subscription
        $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Please enter a valid email address.'
            ]);
            exit;
        }

        // Store email in a file (replace with database in production)
        $newsletterFile = 'newsletter_subscribers.txt';
        $emailEntry = $email . ',' . date('Y-m-d H:i:s') . "\n";
        file_put_contents($newsletterFile, $emailEntry, FILE_APPEND | LOCK_EX);

        // Send notification to admin
        $subject = 'New Newsletter Subscription';
        $htmlBody = '<html><body>';
        $htmlBody .= '<h2>New Newsletter Subscription</h2>';
        $htmlBody .= '<p>Email: ' . htmlspecialchars($email) . '</p>';
        $htmlBody .= '<p>Subscribed on: ' . date('Y-m-d H:i:s') . '</p>';
        $htmlBody .= '</body></html>';
        $headers = "From: $from\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $adminMailSent = mail($to, $subject, $htmlBody, $headers);
        if (!$adminMailSent) {
            error_log("Failed to send newsletter notification to $to");
        }

        // Confirmation to subscriber
        $subscriberMailSent = false;
        $subscriberSubject = 'Welcome to All Saints’ Institute Newsletter';
        $subscriberMessage = '<html><body>';
        $subscriberMessage .= '<p>Dear Subscriber,</p>';
        $subscriberMessage .= '<p>Thank you for subscribing to the <strong>All Saints’ Institute of Nursing & Allied Sciences</strong> newsletter.</p>';
        $subscriberMessage .= '<p>You will receive updates on our programs, admissions, and more.</p>';
        $subscriberMessage .= '<br><p>Regards,<br>All Saints’ Institute</p>';
        $subscriberMessage .= '</body></html>';
        $subscriberHeaders = "From: noreply@asinas.edu.pk\r\n";
        $subscriberHeaders .= "Reply-To: noreply@asinas.edu.pk\r\n";
        $subscriberHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
        $subscriberMailSent = mail($email, $subscriberSubject, $subscriberMessage, $subscriberHeaders);
        if (!$subscriberMailSent) {
            error_log("Failed to send confirmation email to $email for newsletter");
        }

        echo json_encode([
            'success' => $adminMailSent && $subscriberMailSent,
            'message' => $adminMailSent && $subscriberMailSent ? 'Thank you for subscribing to our newsletter!' : 'Subscription recorded, but failed to send confirmation email.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => false,
        'message' => 'Unknown form type.'
    ]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>