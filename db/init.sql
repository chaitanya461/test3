
-- Create users table
CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create quizzes table
CREATE TABLE quizzes (
    quiz_id SERIAL PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    created_by INT REFERENCES users(user_id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Create questions table
CREATE TABLE questions (
    question_id SERIAL PRIMARY KEY,
    quiz_id INT REFERENCES quizzes(quiz_id),
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_answer CHAR(1) NOT NULL CHECK (correct_answer IN ('a', 'b', 'c', 'd')),
    points INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create user responses table
CREATE TABLE user_responses (
    response_id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(user_id),
    question_id INT REFERENCES questions(question_id),
    selected_answer CHAR(1) CHECK (selected_answer IN ('a', 'b', 'c', 'd')),
    is_correct BOOLEAN,
    responded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create quiz results table

CREATE TABLE quiz_results (
    result_id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(user_id),
    quiz_id INT REFERENCES quizzes(quiz_id),
    total_questions INT NOT NULL,
    correct_answers INT NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    completed_at TIMESTAMP DEFAULT NOW(),
    time_expired BOOLEAN DEFAULT FALSE
);

CREATE TABLE quiz_reattempt_requests (
    request_id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    quiz_id INTEGER NOT NULL,
    request_date TIMESTAMP NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' 
        CHECK (status IN ('pending', 'approved', 'rejected', 'completed')),
    processed_date TIMESTAMP,
    processed_by INT,
    admin_id INTEGER NULL,
    response_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id),
    FOREIGN KEY (admin_id) REFERENCES users(user_id)
);

ALTER TABLE user_responses 
ALTER COLUMN selected_answer TYPE VARCHAR(10),
DROP CONSTRAINT IF EXISTS user_responses_selected_answer_check;

ALTER TABLE questions DROP CONSTRAINT IF EXISTS questions_correct_answer_check;

-- Modify the correct_answer column to support multiple answers
ALTER TABLE questions 
ALTER COLUMN correct_answer TYPE VARCHAR(10);

ALTER TABLE questions 
ADD COLUMN IF NOT EXISTS question_type VARCHAR(10) NOT NULL DEFAULT 'single' CHECK (question_type IN ('single', 'multi'));


CREATE INDEX idx_quiz_results_user_quiz ON quiz_results(user_id, quiz_id);
CREATE INDEX idx_user_responses_user_question ON user_responses(user_id, question_id);

ALTER TABLE quiz_results ADD COLUMN completion_time TIMESTAMP;
COMMENT ON COLUMN quiz_results.completion_time IS 'When the quiz was completed';

ALTER TABLE quizzes ADD COLUMN time_limit INT DEFAULT 30;

ALTER TABLE user_responses DROP CONSTRAINT user_responses_selected_answer_check;

-- First, drop the existing foreign key constraint
ALTER TABLE user_responses 
DROP CONSTRAINT user_responses_question_id_fkey;

-- Recreate it with ON DELETE CASCADE
ALTER TABLE user_responses 
ADD CONSTRAINT user_responses_question_id_fkey 
FOREIGN KEY (question_id) 
REFERENCES questions(question_id) 
ON DELETE CASCADE;
