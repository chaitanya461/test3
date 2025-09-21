<?php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Get question ID from URL
$question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch question data
$stmt = $pdo->prepare("
    SELECT q.*, qz.title as quiz_title 
    FROM questions q
    JOIN quizzes qz ON q.quiz_id = qz.quiz_id
    WHERE q.question_id = ?
");
$stmt->execute([$question_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    die("Question not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $quiz_id = intval($_POST['quiz_id']);
    $question_text = trim($_POST['question_text']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $question_type = $_POST['question_type'];
    
    // Handle correct answers based on question type
    if ($question_type === 'single') {
        $correct_answer = strtolower(trim($_POST['correct_answer_single']));
        // Validate correct answer is a-d
        if (!in_array($correct_answer, ['a', 'b', 'c', 'd'])) {
            $_SESSION['error'] = "Correct answer must be A, B, C, or D";
            header("Location: edit_question.php?id=$question_id");
            exit;
        }
    } else {
        // For multi-select questions, get array of correct answers
        $correct_answers = isset($_POST['correct_answer_multi']) ? $_POST['correct_answer_multi'] : [];
        if (empty($correct_answers)) {
            $_SESSION['error'] = "Please select at least one correct answer";
            header("Location: edit_question.php?id=$question_id");
            exit;
        }
        // Convert array to comma-separated string (e.g., "a,c")
        $correct_answer = implode(',', $correct_answers);
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE questions 
            SET quiz_id = ?, 
                question_text = ?, 
                option_a = ?, 
                option_b = ?, 
                option_c = ?, 
                option_d = ?, 
                correct_answer = ?,
                question_type = ?
            WHERE question_id = ?
        ");
        $stmt->execute([
            $quiz_id,
            $question_text,
            $option_a,
            $option_b,
            $option_c,
            $option_d,
            $correct_answer,
            $question_type,
            $question_id
        ]);
        
        $_SESSION['message'] = "Question updated successfully.";
        header("Location: manage_questions.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating question: " . $e->getMessage();
        header("Location: edit_question.php?id=$question_id");
        exit;
    }
}

// Fetch all quizzes for dropdown
$quizzes = $pdo->query("SELECT quiz_id, title FROM quizzes ORDER BY title")->fetchAll();

// Parse correct answers for multi-select questions
$correct_answers = [];
if ($question['question_type'] === 'multi') {
    $correct_answers = explode(',', $question['correct_answer']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question | Quiz Application</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        /* Dashboard Styles */
        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .sidebar {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 8px;
            height: fit-content;
        }
        
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        
        .sidebar li {
            margin-bottom: 10px;
        }
        
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 8px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background-color: #3498db;
        }
        
        .main-content {
            display: grid;
            gap: 20px;
        }
        
        /* Admin Header Styles */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .admin-title {
            font-size: 1.8rem;
            color: #2c3e50;
        }
        
        /* Edit Form Styles */
        .edit-form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            min-height: 100px;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .option-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .option-letter {
            font-weight: bold;
            width: 20px;
        }
        
        .correct-answer-section {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .correct-answer-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        
        .error {
            background-color: #f2dede;
            color: #a94442;
        }
        
        /* Button Styles */
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.875rem;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
        }
        
        .btn-logout {
            background-color: #7f8c8d;
            color: white;
        }
        
        .btn-logout:hover {
            background-color: #95a5a6;
        }
        
        .question-type-selector {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .type-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .type-option input[type="radio"] {
            margin: 0;
        }
        
        .type-option.selected {
            border-color: #3498db;
            background-color: #e8f4fd;
        }
        
        .type-option:hover {
            border-color: #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Quiz Application</h1>
            <div class="auth-links">
                <span>Welcome, Admin <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="../logout.php" class="btn btn-logout">Logout</a>
            </div>
        </header>
        
        <div class="dashboard">
            <!-- Sidebar Navigation -->
            <div class="sidebar">
                <h3>Admin Menu</h3>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="add_question.php">Add Questions</a></li>
                    <li><a href="manage_questions.php" class="active">Manage Questions</a></li>
                    <li><a href="manage_quizzes.php">Manage Quizzes</a></li>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <li><a href="view_results.php">View Results</a></li>
                </ul>
            </div>
            
            <!-- Main Content Area -->
            <div class="main-content">
                <div class="admin-header">
                    <h2 class="admin-title">Edit Question</h2>
                    <a href="manage_questions.php" class="btn btn-primary">Back to Questions</a>
                </div>
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="message success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="message error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <div class="edit-form">
                    <form method="post">
                        <div class="form-group">
                            <label for="quiz_id">Quiz</label>
                            <select name="quiz_id" id="quiz_id" required>
                                <?php foreach ($quizzes as $quiz): ?>
                                    <option value="<?php echo $quiz['quiz_id']; ?>"
                                        <?php echo $quiz['quiz_id'] == $question['quiz_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($quiz['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Question Type</label>
                            <div class="question-type-selector">
                                <label class="type-option <?php echo $question['question_type'] === 'single' ? 'selected' : ''; ?>">
                                    <input type="radio" name="question_type" value="single" 
                                        <?php echo $question['question_type'] === 'single' ? 'checked' : ''; ?>>
                                    Single Choice
                                </label>
                                <label class="type-option <?php echo $question['question_type'] === 'multi' ? 'selected' : ''; ?>">
                                    <input type="radio" name="question_type" value="multi" 
                                        <?php echo $question['question_type'] === 'multi' ? 'checked' : ''; ?>>
                                    Multiple Choice
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="question_text">Question Text</label>
                            <textarea name="question_text" id="question_text" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                        </div>
                        
                        <div class="options-grid">
                            <div class="option-item">
                                <span class="option-letter">A:</span>
                                <input type="text" name="option_a" value="<?php echo htmlspecialchars($question['option_a']); ?>" required>
                            </div>
                            
                            <div class="option-item">
                                <span class="option-letter">B:</span>
                                <input type="text" name="option_b" value="<?php echo htmlspecialchars($question['option_b']); ?>" required>
                            </div>
                            
                            <div class="option-item">
                                <span class="option-letter">C:</span>
                                <input type="text" name="option_c" value="<?php echo htmlspecialchars($question['option_c']); ?>" required>
                            </div>
                            
                            <div class="option-item">
                                <span class="option-letter">D:</span>
                                <input type="text" name="option_d" value="<?php echo htmlspecialchars($question['option_d']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="correct-answer-section" id="single-choice-section" 
                            style="<?php echo $question['question_type'] === 'multi' ? 'display: none;' : ''; ?>">
                            <div class="correct-answer-title">Select Correct Answer (Single Choice)</div>
                            <select name="correct_answer_single" id="correct_answer_single">
                                <option value="a" <?php echo $question['correct_answer'] == 'a' ? 'selected' : ''; ?>>A</option>
                                <option value="b" <?php echo $question['correct_answer'] == 'b' ? 'selected' : ''; ?>>B</option>
                                <option value="c" <?php echo $question['correct_answer'] == 'c' ? 'selected' : ''; ?>>C</option>
                                <option value="d" <?php echo $question['correct_answer'] == 'd' ? 'selected' : ''; ?>>D</option>
                            </select>
                        </div>
                        
                        <div class="correct-answer-section" id="multi-choice-section" 
                            style="<?php echo $question['question_type'] === 'single' ? 'display: none;' : ''; ?>">
                            <div class="correct-answer-title">Select Correct Answers (Multiple Choice)</div>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="correct_answer_multi[]" value="a" 
                                        <?php echo in_array('a', $correct_answers) ? 'checked' : ''; ?>>
                                    Option A
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="correct_answer_multi[]" value="b" 
                                        <?php echo in_array('b', $correct_answers) ? 'checked' : ''; ?>>
                                    Option B
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="correct_answer_multi[]" value="c" 
                                        <?php echo in_array('c', $correct_answers) ? 'checked' : ''; ?>>
                                    Option C
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="correct_answer_multi[]" value="d" 
                                        <?php echo in_array('d', $correct_answers) ? 'checked' : ''; ?>>
                                    Option D
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="manage_questions.php" class="btn btn-delete">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Question</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> Quiz Application. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Toggle between single and multiple choice interfaces
        document.querySelectorAll('input[name="question_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const singleSection = document.getElementById('single-choice-section');
                const multiSection = document.getElementById('multi-choice-section');
                
                if (this.value === 'single') {
                    singleSection.style.display = 'block';
                    multiSection.style.display = 'none';
                } else {
                    singleSection.style.display = 'none';
                    multiSection.style.display = 'block';
                }
                
                // Update visual selection
                document.querySelectorAll('.type-option').forEach(option => {
                    option.classList.remove('selected');
                });
                this.parentElement.classList.add('selected');
            });
        });
        
        // Initialize visual selection on page load
        document.querySelectorAll('.type-option').forEach(option => {
            if (option.querySelector('input[type="radio"]').checked) {
                option.classList.add('selected');
            }
        });
    </script>
</body>
</html>
