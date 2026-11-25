<?php

// Settings → Mail server → "Send a test email". Kept apart from admin.php so this update
// never overwrites your own changes to the main admin texts.
return [
    'test_title' => 'Send a test email',
    'test_text' => 'Check the settings above really work. We send a short email with your logo to any address you choose.',
    'test_to' => 'Send a test email to',
    'test_to_help' => 'Any address. It starts with your own.',
    'send_test' => 'Send test',
    'no_host' => 'Save the mail server details above first.',
    'sent_title' => 'Test email sent to :email',
    'sent_text' => 'The mail server accepted it in :seconds s. Check the inbox (and the spam folder) in a minute.',
    'failed_title' => 'The test email was not sent',
    'how_to_fix' => 'How to fix it',
    'details' => 'Technical details',

    'test' => [
        'subject' => 'Test email from :store',
        'body' => 'Good news: your mail server settings work. Order, account and contact emails from :store will be sent the same way.',
        'sent_at' => 'Sent',
        'sent_by' => 'Sent by',
        'server' => 'Mail server',
        'footnote' => 'You received this because someone pressed "Send test" in your store admin.',
    ],

    'errors' => [
        'host_not_found' => 'Mail server not found: the host name “:host” does not exist. Check the spelling.',
        'refused' => 'Connection refused on port :port: the server’s firewall blocks outgoing mail, or nothing answers on that port.',
        'timeout' => 'No answer from :host on port :port: the server’s firewall most likely blocks outgoing mail.',
        'needs_tls' => 'The mail server wants an encrypted connection: choose TLS.',
        'login' => 'Login failed: the user name or password is wrong.',
        'login_gmail' => 'Login failed: Gmail needs an app password, not your normal password.',
        'login_microsoft' => 'Login failed: Microsoft 365 has SMTP sign-in turned off for this mailbox.',
        'certificate' => 'The mail server’s security certificate is not for “:host”.',
        'use_ssl_465' => 'Wrong encryption: port 465 needs SSL.',
        'use_tls_587' => 'Wrong encryption: port 587 needs TLS, not SSL.',
        'encryption' => 'The encryption does not match the port.',
        'sender' => 'The mail server refused the sender: the “Send from address” is not a mailbox it may send for.',
        'recipient' => 'The mail server refused the address you are sending to.',
        'other' => 'The mail server said: :message',
    ],

    'fixes' => [
        'whm' => 'In WHM → Tweak Settings, turn off “Restrict outgoing SMTP to root, exim, and mailman”.',
        'csf' => 'Or in CSF (ConfigServer Firewall), set SMTP_BLOCK = 0, or add your cPanel user name to SMTP_ALLOWUSER, then restart CSF.',
        'localhost' => 'Or use host localhost, port 25, encryption None, with a mailbox on your own domain (made in cPanel → Email Accounts).',
        'check_host' => 'Copy the host from your email provider, e.g. smtp.gmail.com, smtp.office365.com, or mail.yourdomain.com on cPanel.',
        'use_587_tls' => 'Use port 587 with TLS (or 465 with SSL).',
        'gmail_app_password' => 'Turn on 2-Step Verification for the Google account, then create an app password at myaccount.google.com → Security → App passwords and paste it here.',
        'microsoft_smtp_auth' => 'In the Microsoft 365 admin centre → Users → this mailbox → Mail → Manage email apps, tick “Authenticated SMTP”.',
        'full_email_username' => 'Use the full email address as the user name and type the password again (it is easy to paste a space).',
        'hosting_host' => 'Use the host name your hosting gives for mail (on cPanel often the server name, e.g. server1.yourhost.com), or choose encryption None with localhost.',
        'match_port' => 'Use port 587 with TLS, or port 465 with SSL.',
        'from_mailbox' => 'Set “Send from address” to the same mailbox you log in with, or a mailbox on the same domain.',
        'check_recipient' => 'Check the address for typos, or try another address.',
    ],
];
