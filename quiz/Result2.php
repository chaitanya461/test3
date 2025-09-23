<?php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

if ($quiz_id === 0) {
    die("Invalid quiz ID.");
}

// Fetch quiz result for this user
$result_stmt = $pdo->prepare("
    SELECT qr.*, q.title 
    FROM quiz_results qr
    JOIN quizzes q ON qr.quiz_id = q.quiz_id
    WHERE qr.user_id = ? AND qr.quiz_id = ?
    ORDER BY qr.completed_at DESC
    LIMIT 1
");
$result_stmt->execute([$_SESSION['user_id'], $quiz_id]);
$result = $result_stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    die("No results found for this quiz.");
}

// Fetch user responses with question details
$responses_stmt = $pdo->prepare("
    SELECT ur.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_answer
    FROM user_responses ur
    JOIN questions q ON ur.question_id = q.question_id
    WHERE ur.user_id = ? AND q.quiz_id = ?
    ORDER BY ur.question_id
");
$responses_stmt->execute([$_SESSION['user_id'], $quiz_id]);
$responses = $responses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get option text safely - UPDATED FOR LOWERCASE
function getOptionText($response, $answer) {
    if (empty($answer)) return 'Not answered';
    
    // Convert to lowercase to match your schema
    $answer = strtolower($answer);
    $option_key = 'option_' . $answer;
    return isset($response[$option_key]) ? $response[$option_key] : 'Invalid option';
}

// Function to format answer letter for display
function formatAnswerLetter($letter) {
    return strtoupper($letter); // Display as uppercase for better readability
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Results: <?php echo htmlspecialchars($result['title']); ?></title>
    <link rel="stylesheet" href="../css/styles1.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        
        .result-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }
        
        .question-review {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            border-left: 4px solid;
        }
        
        .question-review.correct {
            background: #d4edda;
            border-color: #28a745;
        }
        
        .question-review.incorrect {
            background: #f8d7da;
            border-color: #dc3545;
        }
        
        .question-review p {
            margin: 5px 0;
        }
        
        h1, h2, h3 {
            color: #333;
        }
        
        a {
            color: #007bff;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .answer-comparison {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Quiz Results: <?php echo htmlspecialchars($result['title']); ?></h1>
        
        <div class="result-summary">
            <h2>Your Score: <?php echo round($result['score'], 2); ?>%</h2>
            <p>You answered <?php echo $result['correct_answers']; ?> out of <?php echo $result['total_questions']; ?> questions correctly.</p>
            <p>Completed on: <?php echo date('F j, Y, g:i a', strtotime($result['completed_at'])); ?></p>
        </div>
        
        <h3>Question Review</h3>
        
        <?php if (empty($responses)): ?>
            <p>No responses found for this quiz.</p>
        <?php else: ?>
            <?php foreach ($responses as $index => $response): ?>
                <div class="question-review <?php echo $response['is_correct'] ? 'correct' : 'incorrect'; ?>">
                    <p><strong>Question <?php echo $index + 1; ?>:</strong> <?php echo htmlspecialchars($response['question_text']); ?></p>
                    
                    <p><strong>Your Answer:</strong> 
                        <?php 
                        $userAnswer = getOptionText($response, $response['selected_answer']);
                        echo htmlspecialchars($userAnswer);
                        ?>
                        <span class="answer-status">
                            (<?php echo $response['is_correct'] ? 'Correct' : 'Incorrect'; ?>)
                        </span>
                    </p>
                    
                    <?php if (!$response['is_correct']): ?>
                        <p><strong>Correct Answer:</strong> 
                            <?php 
                            $correctAnswer = getOptionText($response, $response['correct_answer']);
                            echo htmlspecialchars($correctAnswer);
                            ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="answer-comparison">
                        <small>
                            Your choice: <?php echo formatAnswerLetter($response['selected_answer']); ?> | 
                            Correct: <?php echo formatAnswerLetter($response['correct_answer']); ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../index.php">Back to Home</a>
        </div>
    </div>
</body>
</html>
