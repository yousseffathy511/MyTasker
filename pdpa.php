<?php
// 1) Bootstrap (autoload + .env)
require_once __DIR__ . '/bootstrap.php';

// 2) Core app functions (auth, CSRF helpers, etc.)
require_once __DIR__ . '/includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// HTML head and header
$pageTitle = 'PDPA Privacy Policy';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="bi bi-shield-lock"></i> <?php echo $pageTitle; ?></h1>
            <p class="text-muted">Personal Data Protection Act 2010 Compliance</p>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-light">
            <h2 class="h5 mb-0"><i class="bi bi-info-circle"></i> About the PDPA</h2>
        </div>
        <div class="card-body">
            <p>The Personal Data Protection Act 2010 (PDPA) is a Malaysian law that regulates the processing of personal data in commercial transactions.</p>
            
            <p>The Act aims to protect individuals' personal data by requiring organizations to comply with seven principles:</p>
            
            <ol>
                <li><strong>General Principle:</strong> Processing with consent and for lawful purposes</li>
                <li><strong>Notice and Choice Principle:</strong> Informing individuals about their data collection</li>
                <li><strong>Disclosure Principle:</strong> Limiting disclosure of personal data</li>
                <li><strong>Security Principle:</strong> Taking practical steps to protect personal data</li>
                <li><strong>Retention Principle:</strong> Not keeping personal data longer than necessary</li>
                <li><strong>Data Integrity Principle:</strong> Ensuring data is accurate, complete, and up-to-date</li>
                <li><strong>Access Principle:</strong> Allowing individuals to access and correct their data</li>
            </ol>
            
            <p>Non-compliance with the PDPA can result in significant penalties, including fines up to RM500,000 and/or imprisonment for up to 3 years.</p>
            
            <p>For the complete text of the law, please refer to the <a href="https://www.pdp.gov.my/jpdpv2/assets/2019/09/Personal-Data-Protection-Act-2010.pdf" target="_blank">official PDPA 2010 document</a>.</p>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-light">
            <h2 class="h5 mb-0"><i class="bi bi-file-text"></i> Our Privacy Policy</h2>
        </div>
        <div class="card-body">
            <h5>1. Data Collection</h5>
            <p>We collect the following personal information:</p>
            <ul>
                <li>Full name</li>
                <li>Email address</li>
                <li>Encrypted password</li>
            </ul>
            
            <h5>2. Purpose of Collection</h5>
            <p>We collect this information to:</p>
            <ul>
                <li>Create and maintain your account</li>
                <li>Authenticate you when accessing the system</li>
                <li>Associate tasks with your account</li>
                <li>Provide security through audit logging</li>
            </ul>
            
            <h5>3. Data Security Measures</h5>
            <p>We implement the following security measures:</p>
            <ul>
                <li>Password encryption using secure hashing algorithms</li>
                <li>CSRF protection against cross-site request forgery</li>
                <li>Brute force protection through login attempt limits</li>
                <li>Regular database backups</li>
                <li>Audit logs to track all system activities</li>
                <li>Input validation and sanitization</li>
                <li>Principle of least privilege access</li>
            </ul>
            
            <h5>4. Data Retention Period</h5>
            <p>We retain your data for as long as your account remains active. Accounts that are inactive for more than 2 years will be scheduled for deletion, with prior notification.</p>
            
            <h5>5. Your Rights</h5>
            <p>Under the PDPA, you have the right to:</p>
            <ul>
                <li>Access your personal data</li>
                <li>Correct inaccurate information</li>
                <li>Withdraw consent for data processing</li>
                <li>Request deletion of your account</li>
            </ul>
            
            <p>To exercise these rights, you can use the features available in your profile page or contact our Data Protection Officer.</p>
            
            <h5>6. Changes to This Policy</h5>
            <p>We may update this privacy policy from time to time. Any changes will be posted on this page, and if the changes are significant, we will provide a more prominent notice.</p>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-light">
            <h2 class="h5 mb-0"><i class="bi bi-person-badge"></i> Contact Information</h2>
        </div>
        <div class="card-body">
            <p>If you have any questions about this privacy policy or our data practices, please contact our Data Protection Officer:</p>
            
            <address>
                <strong>Data Protection Officer</strong><br>
                MyTasker Application<br>
                Email: dpo@mytasker.example.com<br>
                Phone: +60 12-345 6789
            </address>
            
            <p class="text-muted mt-3">
                <small>Last updated: <?php echo date('F Y'); ?></small>
            </p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 