<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Invalid CSRF token');
    }
    
    $lang = $_POST['lang'] ?? 'es';
    
    // Validate language
    if (!in_array($lang, ['en', 'es'])) {
        $lang = 'es'; // Default to Spanish
    }
    
    // Store in session
    $_SESSION['lang'] = $lang;
    
    // Update user's locale in database if logged in
    $user = session_user();
    if ($user && isset($user['id'])) {
        try {
            $pdo = db();
            $statement = $pdo->prepare('UPDATE users SET locale = :locale WHERE id = :id');
            $statement->execute([
                'locale' => $lang === 'en' ? 'en_US' : 'es_ES',
                'id' => $user['id'],
            ]);
        } catch (\Throwable $e) {
            error_log('Error updating user locale: ' . $e->getMessage());
        }
    }
    
    // Redirect back to referrer or home
    $redirect = $_SERVER['HTTP_REFERER'] ?? '/';
    redirect($redirect);
} else {
    // GET request - redirect to home
    redirect('/');
}

