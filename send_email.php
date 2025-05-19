<?php
// filepath: c:\Users\User\Desktop\work\nursing\send_email.php

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
            $html .= '<td>' . htmlspecialchars($value) . '</td>';
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

    // Attach files
    foreach ($files as $file) {
        if (!empty($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            $filename = basename($file['name']);
            $filedata = file_get_contents($file['tmp_name']);
            $filetype = $file['type'] ?: "application/octet-stream";
            $body .= "--$boundary\r\n";
            $body .= "Content-Type: $filetype; name=\"$filename\"\r\n";
            $body .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($filedata)) . "\r\n";
        }
    }
    $body .= "--$boundary--";

    return mail($to, $subject, $body, $headers);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';
    $to = 'info@asinas.edu.pk';
    $studentEmail = $_POST['email'] ?? '';
    $from = 'noreply@asinas.edu.pk';
    $replyTo = $studentEmail ?: $from;

    if ($formType === 'admission') {
        $subject = 'Admission Form Submission';
        $htmlBody = '<html><body>';
        $htmlBody .= '<h2>Admission Form Submission</h2>';
        $htmlBody .= format_post_data($_POST);
        $htmlBody .= '</body></html>';

        // Collect all uploaded files
        $files = [];
        foreach ($_FILES as $file) {
            // Handle multiple files (e.g., academic_docs[])
            if (is_array($file['name'])) {
                for ($i = 0; $i < count($file['name']); $i++) {
                    if (!empty($file['name'][$i])) {
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
                if (!empty($file['name'])) {
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
            $studentHeaders .= "Reply-To: info@asinas.edu.pk\r\n";
            $studentHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
            $studentMailSent = mail($studentEmail, $studentSubject, $studentMessage, $studentHeaders);
        }

        if ($adminMailSent) {
            echo json_encode([
                'success' => true,
                'message' => 'Form submitted successfully. Confirmation email sent to student: ' . ($studentMailSent ? 'Yes' : 'No')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send admin email. Please try again later.'
            ]);
        }
        exit;
    }

    // Contact form
    if ($formType === 'contact') {
        $subject = 'Contact Form Submission';
        $htmlBody = '<html><body>';
        $htmlBody .= '<h2>Contact Form Submission</h2>';
        $htmlBody .= format_post_data($_POST);
        $htmlBody .= '</body></html>';
        $headers = "From: $from\r\n";
        $headers .= "Reply-To: $replyTo\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $adminMailSent = mail($to, $subject, $htmlBody, $headers);

        // Confirmation to student
        $studentMailSent = false;
        if (filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
            $studentSubject = 'Thank You for Contacting All Saints’ Institute';
            $studentMessage = '<html><body>';
            $studentMessage .= '<p>Dear Student,</p>';
            $studentMessage .= '<p>Thank you for reaching out to <strong>All Saints’ Institute of Nursing & Allied Sciences</strong>.</p>';
            $studentMessage .= '<p>We have received your message and will get back to you soon.</p>';
            $studentMessage .= '<br><p>Regards,<br>Admissions Office<br>All Saints’ Institute</p>';
            $studentMessage .= '</body></html>';
            $studentHeaders = "From: noreply@asinas.edu.pk\r\n";
            $studentHeaders .= "Reply-To: info@asinas.edu.pk\r\n";
            $studentHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
            $studentMailSent = mail($studentEmail, $studentSubject, $studentMessage, $studentHeaders);
        }

        if ($adminMailSent) {
            echo json_encode([
                'success' => true,
                'message' => 'Message sent successfully. Confirmation email sent to student: ' . ($studentMailSent ? 'Yes' : 'No')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send admin email. Please try again later.'
            ]);
        }
        exit;
    }

    // Unknown form type
    echo json_encode([
        'success' => false,
        'message' => 'Unknown form type.'
    ]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
