<?php
namespace App\Controllers;

use App\Core\Session;
use App\Services\MailService;

class ContactController {

    private const SUPPORT_EMAIL = 'johan@jsnsystems.co.za';

    public function show(array $params): void {
        $pageTitle = t('contact.title') . ' — ' . APP_NAME;
        $errors    = Session::getFlash('contact_errors') ?? [];
        $old       = Session::getFlash('contact_old')    ?? [];
        include VIEW_PATH . 'layout/base.php';
        include VIEW_PATH . 'contact/index.php';
        include VIEW_PATH . 'layout/footer.php';
    }

    public function submit(array $params): void {
        $name    = trim($_POST['name']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        $errors = [];
        if (!$name)                          $errors['name']    = t('contact.err_name');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = t('contact.err_email');
        if (!$subject)                       $errors['subject'] = t('contact.err_subject');
        if (strlen($message) < 10)           $errors['message'] = t('contact.err_message');

        if ($errors) {
            Session::flash('contact_errors', $errors);
            Session::flash('contact_old', compact('name', 'email', 'subject', 'message'));
            header('Location: ' . url('contact'), true, 302);
            exit;
        }

        $sent = MailService::sendSupportRequest(self::SUPPORT_EMAIL, $name, $email, $subject, $message);

        if ($sent) {
            Session::flash('success', t('contact.success'));
        } else {
            Session::flash('error', t('contact.fail'));
        }

        header('Location: ' . url('contact'), true, 302);
        exit;
    }
}
