<?php
require_once 'includes/config.php';
require_once 'includes/auth_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | Quiz Application</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Welcome to Quiz Application</h1>
            <div class="auth-links">
                <?php if (isLoggedIn()): ?>
                    <span>Hello, <?php 
                    // FIXED: Added null check before htmlspecialchars
                    $username = $_SESSION['username'] ?? '';
                    echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); 
                    ?>!</span>
                    <?php if (isAdmin()): ?>
                        <a href="admin/dashboard.php" class="btn btn-admin">Admin Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-logout">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-login">Login</a>
                    <a href="register.php" class="btn btn-register">Register</a>
                <?php endif; ?>
            </div>
        </header>
        
        <main>
            <?php if (isLoggedIn()): ?>
                <section class="quizzes-section">
                    <h2>Available Quizzes</h2>
                    
                    <?php
                    // Fetch active quizzes with error handling
                    try {
                        $quizzes = $pdo->query("SELECT * FROM quizzes WHERE is_active = TRUE")->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        $quizzes = [];
                        error_log("Database error: " . $e->getMessage());
                    }
                    
                    if (empty($quizzes)): ?>
                        <p>No quizzes available at the moment.</p>
                    <?php else: ?>
                        <div class="quiz-grid">
                            <?php foreach ($quizzes as $quiz): ?>
                                <div class="quiz-card">
                                    <h3><?php echo htmlspecialchars($quiz['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p><?php echo htmlspecialchars($quiz['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                    <a href="quiz/take_quiz.php?quiz_id=<?php echo htmlspecialchars($quiz['quiz_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-start-quiz">Start Quiz</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php else: ?>
                <section class="welcome-section">
                    <h2>Test Your Knowledge</h2>
                    <p>Please login or register to take quizzes and track your progress.</p>
                    <div class="action-buttons">
                        <a href="login.php" class="btn btn-primary">Login</a>
                        <a href="register.php" class="btn btn-secondary">Register</a>
                    </div>
                </section>
            <?php endif; ?>
        </main>
        
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> Quiz Application. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
