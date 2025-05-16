<?php
// filepath: c:\Users\User\Desktop\work\nursing\send_email.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';
    $to = 'info@asinas.edu.pk';
    $studentEmail = $_POST['email'] ?? ''; // Student's email

    $subject = $formType === 'admission' ? 'Admission Form Submission' : 'Contact Form Submission';
    $headers = "From: noreply@asinas.edu.pk\r\n";
    $headers .= "Reply-To: " . ($studentEmail ?: 'noreply@asinas.edu.pk') . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Message to admin
    $message = '<html><body>';
    $message .= '<h2>' . htmlspecialchars($subject) . '</h2>';
    $message .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';

    foreach ($_POST as $key => $value) {
        if ($key !== 'form_type') {
            $message .= '<tr>';
            $message .= '<td style="background-color: #f2f2f2; font-weight: bold;">' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</td>';
            $message .= '<td>' . htmlspecialchars(is_array($value) ? implode(', ', $value) : $value) . '</td>';
            $message .= '</tr>';
        }
    }

    $message .= '</table>';
    $message .= '</body></html>';

    $adminMailSent = mail($to, $subject, $message, $headers);

    // Send confirmation to student
    $studentMailSent = false;
    if (filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
        $studentSubject = 'Thank You for Your Submission - All Saints\' Institute';
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
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
