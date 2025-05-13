<?php
// filepath: c:\Users\User\Desktop\work\nursing\send_email.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';
    $to = 'info@asinas.edu.pk';
    $subject = $formType === 'admission' ? 'Admission Form Submission' : 'Contact Form Submission';
    $headers = [
        'From' => 'noreply@asinas.edu.pk',
        'Reply-To' => $_POST['email'] ?? 'noreply@asinas.edu.pk',
        'Content-Type' => 'text/html; charset=UTF-8',
    ];

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

    if (mail($to, $subject, $message, implode("\r\n", $headers))) {
        echo json_encode(['success' => true, 'message' => 'Form submitted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send the email. Please try again later.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>