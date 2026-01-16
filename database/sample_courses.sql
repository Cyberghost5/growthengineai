-- =============================================
-- GrowthEngineAI LMS - Sample Course Data
-- Created: January 16, 2026
-- Description: Demo courses, modules, lessons, quizzes, assignments
-- =============================================

USE growthengine_lms;

-- =============================================
-- CREATE DEMO INSTRUCTOR
-- First, ensure we have an instructor user
-- =============================================

-- Insert instructor user (if not exists)
INSERT INTO users (first_name, last_name, email, password, role, is_verified, created_at) 
SELECT 'Sarah', 'Johnson', 'sarah.johnson@growthengineai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tutor', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'sarah.johnson@growthengineai.com');

INSERT INTO users (first_name, last_name, email, password, role, is_verified, created_at) 
SELECT 'Michael', 'Chen', 'michael.chen@growthengineai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tutor', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'michael.chen@growthengineai.com');

INSERT INTO users (first_name, last_name, email, password, role, is_verified, created_at) 
SELECT 'Emma', 'Williams', 'emma.williams@growthengineai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tutor', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'emma.williams@growthengineai.com');

-- Create instructor profiles
INSERT INTO instructors (user_id, bio, expertise, is_verified)
SELECT id, 'Dr. Sarah Johnson is a leading AI researcher with over 15 years of experience in artificial intelligence and machine learning. She has published numerous papers in top-tier conferences and has worked with Fortune 500 companies on AI implementations.', 'Artificial Intelligence, Machine Learning, Neural Networks, Deep Learning', 1
FROM users WHERE email = 'sarah.johnson@growthengineai.com'
AND NOT EXISTS (SELECT 1 FROM instructors WHERE user_id = (SELECT id FROM users WHERE email = 'sarah.johnson@growthengineai.com'));

INSERT INTO instructors (user_id, bio, expertise, is_verified)
SELECT id, 'Prof. Michael Chen is a machine learning expert with a Ph.D. from MIT. He has 10+ years of industry experience at Google and Amazon, specializing in recommendation systems and predictive analytics.', 'Machine Learning, Deep Learning, TensorFlow, PyTorch, MLOps', 1
FROM users WHERE email = 'michael.chen@growthengineai.com'
AND NOT EXISTS (SELECT 1 FROM instructors WHERE user_id = (SELECT id FROM users WHERE email = 'michael.chen@growthengineai.com'));

INSERT INTO instructors (user_id, bio, expertise, is_verified)
SELECT id, 'Emma Williams is a data science professional and educator with expertise in Python, data visualization, and business analytics. She has trained thousands of students worldwide in data-driven decision making.', 'Data Science, Python, Pandas, Data Visualization, Business Analytics', 1
FROM users WHERE email = 'emma.williams@growthengineai.com'
AND NOT EXISTS (SELECT 1 FROM instructors WHERE user_id = (SELECT id FROM users WHERE email = 'emma.williams@growthengineai.com'));

-- =============================================
-- COURSE 1: AI Fundamentals for Business
-- =============================================
INSERT INTO courses (
    instructor_id, category_id, title, slug, subtitle, description, 
    requirements, what_you_learn, target_audience, thumbnail,
    level, duration_hours, price, is_free, is_published, status, published_at
) VALUES (
    (SELECT id FROM instructors WHERE user_id = (SELECT id FROM users WHERE email = 'sarah.johnson@growthengineai.com')),
    (SELECT id FROM categories WHERE slug = 'artificial-intelligence'),
    'AI Fundamentals for Business',
    'ai-fundamentals-for-business',
    'Master the core concepts of AI and learn to apply them in real business scenarios',
    'This comprehensive course covers everything you need to know about Artificial Intelligence, from basic concepts to practical business applications. You''ll learn about machine learning, neural networks, natural language processing, and computer vision. By the end of this course, you''ll be able to identify AI opportunities in your organization and communicate effectively with technical teams.',
    '["Basic computer literacy", "No programming experience required", "Curiosity about AI and technology", "Access to a computer with internet"]',
    '["Understand core AI and ML concepts", "Identify AI use cases in business", "Evaluate AI solutions and vendors", "Communicate effectively with AI teams", "Build a business case for AI adoption", "Understand ethical implications of AI"]',
    '["Business professionals", "Managers and executives", "Entrepreneurs", "Anyone curious about AI", "Non-technical professionals"]',
    'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800&h=450&fit=crop',
    'beginner',
    8.5,
    99.99,
    0,
    1,
    'published',
    NOW()
);

SET @course1_id = LAST_INSERT_ID();

-- Course 1 - Module 1: Introduction to AI
INSERT INTO modules (course_id, title, description, sort_order) VALUES
(@course1_id, 'Introduction to Artificial Intelligence', 'Get started with the fundamentals of AI and understand its history and impact on society', 1);
SET @module1_id = LAST_INSERT_ID();

-- Module 1 Lessons
INSERT INTO lessons (module_id, title, slug, description, content_type, video_url, video_provider, duration_minutes, sort_order, is_free_preview) VALUES
(@module1_id, 'What is Artificial Intelligence?', 'what-is-ai', 'Explore the fundamental concepts of AI, its definition, and why it matters in today''s world.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 12, 1, 1),
(@module1_id, 'History of AI: From Turing to Today', 'history-of-ai', 'Journey through the fascinating history of AI, from Alan Turing''s groundbreaking work to modern deep learning breakthroughs.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 18, 2, 0),
(@module1_id, 'Types of AI Systems', 'types-of-ai-systems', 'Learn about Narrow AI, General AI, and Super AI. Understand the differences and real-world examples of each type.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 15, 3, 0),
(@module1_id, 'AI in Everyday Life', 'ai-in-everyday-life', 'Discover how AI is already integrated into your daily life through smartphones, streaming services, and smart devices.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 14, 4, 0);

-- Module 1 Quiz
INSERT INTO quizzes (module_id, title, description, time_limit_minutes, passing_score, max_attempts, sort_order, total_questions) VALUES
(@module1_id, 'AI Basics Assessment', 'Test your understanding of fundamental AI concepts covered in this module.', 15, 70, 3, 5, 10);
SET @quiz1_id = LAST_INSERT_ID();

-- Quiz 1 Questions
INSERT INTO quiz_questions (quiz_id, question_text, question_type, explanation, points, sort_order) VALUES
(@quiz1_id, 'What does AI stand for?', 'multiple_choice', 'AI stands for Artificial Intelligence, which refers to the simulation of human intelligence by machines.', 1, 1),
(@quiz1_id, 'Who is considered the father of Artificial Intelligence?', 'multiple_choice', 'John McCarthy coined the term "Artificial Intelligence" in 1956 and is widely regarded as the father of AI.', 1, 2),
(@quiz1_id, 'Which type of AI is designed to perform specific tasks?', 'multiple_choice', 'Narrow AI (also called Weak AI) is designed and trained for a specific task, like voice recognition or image classification.', 1, 3),
(@quiz1_id, 'Machine Learning is a subset of AI.', 'true_false', 'True! Machine Learning is indeed a subset of AI that enables systems to learn from data.', 1, 4),
(@quiz1_id, 'What year was the term "Artificial Intelligence" first coined?', 'multiple_choice', 'The term was first used at the Dartmouth Conference in 1956.', 1, 5),
(@quiz1_id, 'Which of the following is an example of Narrow AI?', 'multiple_choice', 'Virtual assistants like Siri, Alexa, and Google Assistant are examples of Narrow AI.', 1, 6),
(@quiz1_id, 'General AI can perform any intellectual task that a human can.', 'true_false', 'True! General AI (AGI) would be able to perform any cognitive task that a human can, but it doesn''t exist yet.', 1, 7),
(@quiz1_id, 'Which company developed AlphaGo, the AI that defeated the world Go champion?', 'multiple_choice', 'DeepMind, a subsidiary of Alphabet (Google), developed AlphaGo.', 1, 8),
(@quiz1_id, 'AI systems can learn without being explicitly programmed.', 'true_false', 'True! This is the fundamental principle of Machine Learning.', 1, 9),
(@quiz1_id, 'What is the Turing Test used for?', 'multiple_choice', 'The Turing Test measures a machine''s ability to exhibit intelligent behavior indistinguishable from a human.', 1, 10);

-- Quiz 1 Options
INSERT INTO quiz_options (question_id, option_text, is_correct, sort_order) VALUES
-- Question 1
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 1), 'Automated Intelligence', 0, 1),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 1), 'Artificial Intelligence', 1, 2),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 1), 'Advanced Integration', 0, 3),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 1), 'Automated Integration', 0, 4),
-- Question 2
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 2), 'Alan Turing', 0, 1),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 2), 'John McCarthy', 1, 2),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 2), 'Elon Musk', 0, 3),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 2), 'Bill Gates', 0, 4),
-- Question 3
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 3), 'General AI', 0, 1),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 3), 'Narrow AI', 1, 2),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 3), 'Super AI', 0, 3),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 3), 'Universal AI', 0, 4),
-- Question 4
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 4), 'True', 1, 1),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 4), 'False', 0, 2),
-- Question 5
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 5), '1943', 0, 1),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 5), '1956', 1, 2),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 5), '1969', 0, 3),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 5), '1980', 0, 4),
-- Question 6
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 6), 'A robot that can do everything', 0, 1),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 6), 'Virtual assistants like Siri', 1, 2),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 6), 'Human brain simulation', 0, 3),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 6), 'None of the above', 0, 4),
-- Question 7
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 7), 'True', 1, 1),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 7), 'False', 0, 2),
-- Question 8
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 8), 'OpenAI', 0, 1),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 8), 'DeepMind', 1, 2),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 8), 'Microsoft', 0, 3),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 8), 'IBM', 0, 4),
-- Question 9
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 9), 'True', 1, 1),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 9), 'False', 0, 2),
-- Question 10
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 10), 'To measure computer processing speed', 0, 1),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 10), 'To test machine intelligence', 1, 2),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 10), 'To evaluate programming languages', 0, 3),
((SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND sort_order = 10), 'To benchmark hardware', 0, 4);

-- Course 1 - Module 2: Machine Learning Fundamentals
INSERT INTO modules (course_id, title, description, sort_order) VALUES
(@course1_id, 'Machine Learning Fundamentals', 'Understand the core concepts of machine learning and how machines learn from data', 2);
SET @module2_id = LAST_INSERT_ID();

-- Module 2 Lessons
INSERT INTO lessons (module_id, title, slug, description, content_type, video_url, video_provider, duration_minutes, sort_order) VALUES
(@module2_id, 'Introduction to Machine Learning', 'intro-to-ml', 'Discover what machine learning is and how it enables computers to learn from experience.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 20, 1),
(@module2_id, 'Supervised vs Unsupervised Learning', 'supervised-vs-unsupervised', 'Understand the key differences between supervised and unsupervised learning approaches.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 18, 2),
(@module2_id, 'Common Machine Learning Algorithms', 'common-ml-algorithms', 'Explore the most widely used ML algorithms including linear regression, decision trees, and neural networks.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 25, 3),
(@module2_id, 'The ML Pipeline: From Data to Deployment', 'ml-pipeline', 'Learn about the complete machine learning pipeline from data collection to model deployment.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 22, 4);

-- Module 2 Assignment
INSERT INTO assignments (module_id, title, description, instructions, due_days, max_points, sort_order) VALUES
(@module2_id, 'Identify ML Opportunities in Your Organization', 
'Analyze your organization or a company of your choice and identify potential machine learning applications.',
'## Assignment Instructions

### Objective
Identify and propose three potential machine learning applications that could benefit a business.

### Requirements

1. **Choose a Company**
   - Select your own organization or a well-known company
   - Briefly describe the company and its industry (2-3 sentences)

2. **Identify Three ML Opportunities**
   For each opportunity, provide:
   - **Problem Statement**: What business problem would be solved?
   - **ML Approach**: What type of ML would be used? (Classification, Regression, Clustering, etc.)
   - **Data Required**: What data would be needed?
   - **Expected Benefits**: How would this improve the business?

3. **Prioritization**
   - Rank your three opportunities by potential impact
   - Explain your reasoning

### Submission Format
- PDF or Word document
- 3-5 pages
- Include any diagrams or visuals that help explain your ideas

### Grading Criteria
- Problem identification (25 points)
- ML approach appropriateness (25 points)
- Data consideration (20 points)
- Business impact analysis (20 points)
- Presentation quality (10 points)',
7, 100, 5);

-- Course 1 - Module 3: Neural Networks & Deep Learning
INSERT INTO modules (course_id, title, description, sort_order) VALUES
(@course1_id, 'Neural Networks & Deep Learning', 'Dive into the world of neural networks and understand how deep learning works', 3);
SET @module3_id = LAST_INSERT_ID();

-- Module 3 Lessons
INSERT INTO lessons (module_id, title, slug, description, content_type, video_url, video_provider, duration_minutes, sort_order) VALUES
(@module3_id, 'Understanding Neural Networks', 'understanding-neural-networks', 'Learn how neural networks are inspired by the human brain and how they process information.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 22, 1),
(@module3_id, 'Deep Learning Architecture', 'deep-learning-architecture', 'Explore different deep learning architectures including CNNs, RNNs, and Transformers.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 28, 2),
(@module3_id, 'Training Neural Networks', 'training-neural-networks', 'Understand the process of training neural networks including backpropagation and optimization.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 25, 3),
(@module3_id, 'Real-World Deep Learning Applications', 'real-world-dl-applications', 'See how deep learning is being used in image recognition, NLP, autonomous vehicles, and more.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 20, 4);

-- Module 3 Quiz
INSERT INTO quizzes (module_id, title, description, time_limit_minutes, passing_score, max_attempts, sort_order, total_questions) VALUES
(@module3_id, 'Neural Networks Assessment', 'Test your understanding of neural networks and deep learning concepts.', 20, 75, 2, 5, 8);

-- =============================================
-- COURSE 2: Machine Learning Mastery
-- =============================================
INSERT INTO courses (
    instructor_id, category_id, title, slug, subtitle, description, 
    requirements, what_you_learn, target_audience, thumbnail,
    level, duration_hours, price, is_free, is_published, status, published_at
) VALUES (
    (SELECT id FROM instructors WHERE user_id = (SELECT id FROM users WHERE email = 'michael.chen@growthengineai.com')),
    (SELECT id FROM categories WHERE slug = 'machine-learning'),
    'Machine Learning Mastery',
    'machine-learning-mastery',
    'Deep dive into ML algorithms with hands-on Python implementations',
    'Take your machine learning skills to the next level with this comprehensive, hands-on course. You''ll master essential ML algorithms, learn to build and deploy models using Python and scikit-learn, and gain practical experience through real-world projects. Perfect for developers and data enthusiasts looking to build production-ready ML solutions.',
    '["Basic Python programming", "Understanding of basic statistics", "Familiarity with linear algebra concepts", "Computer with Python 3.8+ installed"]',
    '["Master supervised and unsupervised learning algorithms", "Build and evaluate ML models using scikit-learn", "Perform feature engineering and selection", "Handle imbalanced datasets", "Deploy ML models to production", "Work with real-world datasets"]',
    '["Software developers", "Data analysts", "Aspiring data scientists", "Python programmers", "Anyone with basic coding skills"]',
    'https://images.unsplash.com/photo-1555949963-aa79dcee981c?w=800&h=450&fit=crop',
    'intermediate',
    15.0,
    149.99,
    0,
    1,
    'published',
    NOW()
);

SET @course2_id = LAST_INSERT_ID();

-- Course 2 Modules
INSERT INTO modules (course_id, title, description, sort_order) VALUES
(@course2_id, 'Python for Machine Learning', 'Set up your ML development environment and master essential Python libraries', 1),
(@course2_id, 'Supervised Learning Algorithms', 'Master regression and classification algorithms with hands-on implementations', 2),
(@course2_id, 'Unsupervised Learning', 'Learn clustering, dimensionality reduction, and anomaly detection', 3),
(@course2_id, 'Model Evaluation & Optimization', 'Learn to properly evaluate, tune, and optimize your ML models', 4),
(@course2_id, 'Real-World ML Projects', 'Apply your skills to complete end-to-end machine learning projects', 5);

-- =============================================
-- COURSE 3: Data Analytics with Python
-- =============================================
INSERT INTO courses (
    instructor_id, category_id, title, slug, subtitle, description, 
    requirements, what_you_learn, target_audience, thumbnail,
    level, duration_hours, price, is_free, is_published, status, published_at
) VALUES (
    (SELECT id FROM instructors WHERE user_id = (SELECT id FROM users WHERE email = 'emma.williams@growthengineai.com')),
    (SELECT id FROM categories WHERE slug = 'data-science'),
    'Data Analytics with Python',
    'data-analytics-with-python',
    'Master data analysis using Python, Pandas, and visualization libraries',
    'Learn to analyze and visualize data like a pro using Python. This beginner-friendly course covers everything from data cleaning and manipulation with Pandas to creating stunning visualizations with Matplotlib and Seaborn. You''ll work with real datasets and learn the skills that top companies are looking for in data analysts.',
    '["No prior programming experience needed", "Basic understanding of spreadsheets", "Willingness to learn", "Computer with internet access"]',
    '["Write Python code confidently", "Manipulate data with Pandas", "Create visualizations with Matplotlib and Seaborn", "Perform exploratory data analysis", "Clean and prepare messy datasets", "Generate insights from data"]',
    '["Beginners to data analysis", "Excel users wanting to learn Python", "Business analysts", "Marketing professionals", "Anyone working with data"]',
    'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=800&h=450&fit=crop',
    'beginner',
    10.0,
    79.99,
    0,
    1,
    'published',
    NOW()
);

SET @course3_id = LAST_INSERT_ID();

-- Course 3 Modules
INSERT INTO modules (course_id, title, description, sort_order) VALUES
(@course3_id, 'Getting Started with Python', 'Install Python and learn the basics of programming', 1),
(@course3_id, 'Data Manipulation with Pandas', 'Master the Pandas library for data analysis', 2),
(@course3_id, 'Data Visualization', 'Create stunning charts and graphs with Matplotlib and Seaborn', 3),
(@course3_id, 'Exploratory Data Analysis', 'Learn systematic approaches to understanding your data', 4),
(@course3_id, 'Capstone Project', 'Apply all your skills in a comprehensive data analysis project', 5);

-- Get module IDs for Course 3
SET @c3_module1_id = (SELECT id FROM modules WHERE course_id = @course3_id AND sort_order = 1);
SET @c3_module2_id = (SELECT id FROM modules WHERE course_id = @course3_id AND sort_order = 2);
SET @c3_module3_id = (SELECT id FROM modules WHERE course_id = @course3_id AND sort_order = 3);

-- Course 3 - Module 2 Lessons (Data Manipulation with Pandas)
INSERT INTO lessons (module_id, title, slug, description, content_type, video_url, video_provider, duration_minutes, sort_order) VALUES
(@c3_module2_id, 'Introduction to Pandas', 'intro-to-pandas', 'Learn what Pandas is and why it''s essential for data analysis in Python.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 15, 1),
(@c3_module2_id, 'DataFrames and Series', 'dataframes-and-series', 'Understand the core data structures in Pandas: DataFrames and Series.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 22, 2),
(@c3_module2_id, 'Reading and Writing Data', 'reading-writing-data', 'Learn to import and export data from CSV, Excel, JSON, and databases.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 18, 3),
(@c3_module2_id, 'Data Selection and Filtering', 'data-selection-filtering', 'Master techniques for selecting and filtering data in Pandas.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 25, 4),
(@c3_module2_id, 'Data Cleaning Techniques', 'data-cleaning-techniques', 'Learn to handle missing values, duplicates, and data inconsistencies.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 28, 5),
(@c3_module2_id, 'Grouping and Aggregation', 'grouping-aggregation', 'Master the powerful groupby operations in Pandas.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 22, 6);

-- Course 3 - Module 3 Lessons (Data Visualization)
INSERT INTO lessons (module_id, title, slug, description, content_type, video_url, video_provider, duration_minutes, sort_order) VALUES
(@c3_module3_id, 'Introduction to Matplotlib', 'intro-matplotlib', 'Get started with the foundational plotting library in Python.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 18, 1),
(@c3_module3_id, 'Creating Different Chart Types', 'chart-types', 'Learn to create line charts, bar charts, scatter plots, and more.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 25, 2),
(@c3_module3_id, 'Beautiful Visualizations with Seaborn', 'seaborn-visualizations', 'Create statistical visualizations with the Seaborn library.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 22, 3),
(@c3_module3_id, 'Customizing Your Plots', 'customizing-plots', 'Learn to style and customize your visualizations for maximum impact.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 20, 4),
(@c3_module3_id, 'Data Visualization with Matplotlib', 'data-viz-matplotlib', 'Master advanced visualization techniques including subplots and annotations.', 'video', 'https://www.youtube.com/embed/ad79nYk2keg', 'youtube', 24, 5);

-- Module 3 Assignment
INSERT INTO assignments (module_id, title, description, instructions, due_days, max_points, sort_order) VALUES
(@c3_module3_id, 'Create a Data Visualization Dashboard',
'Create a comprehensive visualization dashboard using a real-world dataset.',
'## Assignment: Data Visualization Dashboard

### Objective
Create a Python script that generates a multi-panel visualization dashboard analyzing a dataset of your choice.

### Requirements

1. **Dataset Selection**
   - Use a dataset with at least 1000 rows and 5 columns
   - Suggested sources: Kaggle, UCI ML Repository, or government data portals

2. **Required Visualizations**
   - At least 5 different chart types
   - One correlation heatmap
   - One time series plot (if applicable)
   - Proper titles, labels, and legends

3. **Technical Requirements**
   - Use both Matplotlib and Seaborn
   - Save the dashboard as a PNG file (minimum 1200x800 pixels)
   - Include your Python code (.py or .ipynb file)

4. **Documentation**
   - Include a brief write-up explaining your insights
   - Describe what each visualization reveals

### Grading Criteria
- Visualization quality (30 points)
- Code quality and organization (25 points)
- Insights and analysis (25 points)
- Creativity and presentation (20 points)',
5, 100, 6);

-- =============================================
-- COURSE 4: Natural Language Processing (Advanced)
-- =============================================
INSERT INTO courses (
    instructor_id, category_id, title, slug, subtitle, description, 
    requirements, what_you_learn, target_audience, thumbnail,
    level, duration_hours, price, is_free, is_published, status, published_at
) VALUES (
    (SELECT id FROM instructors WHERE user_id = (SELECT id FROM users WHERE email = 'sarah.johnson@growthengineai.com')),
    (SELECT id FROM categories WHERE slug = 'nlp'),
    'Natural Language Processing with Python',
    'nlp-with-python',
    'Build intelligent text processing applications using modern NLP techniques',
    'Master the art of processing and understanding human language with Python. This advanced course covers everything from traditional NLP techniques to state-of-the-art transformer models. You''ll build real applications including chatbots, sentiment analyzers, and text summarizers.',
    '["Strong Python programming skills", "Basic machine learning knowledge", "Understanding of neural networks", "Familiarity with deep learning frameworks"]',
    '["Process and clean text data effectively", "Build text classification models", "Implement sentiment analysis", "Work with word embeddings", "Use transformer models like BERT and GPT", "Build conversational AI systems"]',
    '["ML engineers", "Data scientists", "Python developers", "AI researchers", "NLP enthusiasts"]',
    'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800&h=450&fit=crop',
    'advanced',
    12.0,
    199.99,
    0,
    1,
    'published',
    NOW()
);

-- =============================================
-- COURSE 5: Business Intelligence & Reporting (Free)
-- =============================================
INSERT INTO courses (
    instructor_id, category_id, title, slug, subtitle, description, 
    requirements, what_you_learn, target_audience, thumbnail,
    level, duration_hours, price, is_free, is_published, status, published_at
) VALUES (
    (SELECT id FROM instructors WHERE user_id = (SELECT id FROM users WHERE email = 'emma.williams@growthengineai.com')),
    (SELECT id FROM categories WHERE slug = 'business-intelligence'),
    'Business Intelligence & Reporting',
    'business-intelligence-reporting',
    'Create impactful dashboards and reports that drive business decisions',
    'Learn to create powerful business intelligence solutions that transform raw data into actionable insights. This course covers dashboard design principles, key performance indicators, and practical implementation using popular BI tools.',
    '["Basic spreadsheet skills", "Understanding of business metrics", "No coding required"]',
    '["Design effective dashboards", "Choose the right visualizations", "Define and track KPIs", "Tell stories with data", "Present insights to stakeholders"]',
    '["Business analysts", "Managers", "Entrepreneurs", "Anyone who works with business data"]',
    'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&h=450&fit=crop',
    'beginner',
    6.0,
    0.00,
    1,
    1,
    'published',
    NOW()
);

-- =============================================
-- COURSE 6: Deep Learning with TensorFlow
-- =============================================
INSERT INTO courses (
    instructor_id, category_id, title, slug, subtitle, description, 
    requirements, what_you_learn, target_audience, thumbnail,
    level, duration_hours, price, is_free, is_published, status, published_at
) VALUES (
    (SELECT id FROM instructors WHERE user_id = (SELECT id FROM users WHERE email = 'michael.chen@growthengineai.com')),
    (SELECT id FROM categories WHERE slug = 'deep-learning'),
    'Deep Learning with TensorFlow',
    'deep-learning-tensorflow',
    'Build and deploy production-ready deep learning models',
    'Master deep learning with TensorFlow and Keras. This comprehensive course takes you from building your first neural network to deploying complex models in production. You''ll work on image classification, sequence modeling, and generative AI projects.',
    '["Python programming experience", "Understanding of machine learning basics", "Linear algebra and calculus fundamentals", "GPU-enabled computer recommended"]',
    '["Build neural networks with TensorFlow", "Implement CNNs for computer vision", "Create RNNs and LSTMs for sequences", "Use transfer learning effectively", "Deploy models with TensorFlow Serving", "Work with TensorFlow 2.x best practices"]',
    '["ML engineers", "Software developers", "Data scientists", "AI researchers"]',
    'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?w=800&h=450&fit=crop',
    'advanced',
    18.0,
    249.99,
    0,
    1,
    'published',
    NOW()
);

-- =============================================
-- ADD COURSE TAGS
-- =============================================
INSERT INTO course_tags (course_id, tag_id)
SELECT @course1_id, id FROM tags WHERE slug IN ('beginner-friendly', 'certification');

INSERT INTO course_tags (course_id, tag_id)
SELECT @course2_id, id FROM tags WHERE slug IN ('python', 'scikit-learn', 'hands-on-projects');

INSERT INTO course_tags (course_id, tag_id)
SELECT @course3_id, id FROM tags WHERE slug IN ('python', 'pandas', 'beginner-friendly');

-- =============================================
-- ADD LESSON RESOURCES
-- =============================================
INSERT INTO lesson_resources (lesson_id, title, file_url, file_type, file_size, sort_order)
SELECT id, 'Lesson Slides (PDF)', '/resources/lesson-slides.pdf', 'application/pdf', 2457600, 1
FROM lessons WHERE module_id = @module1_id AND sort_order = 1;

INSERT INTO lesson_resources (lesson_id, title, file_url, file_type, file_size, sort_order)
SELECT id, 'AI Terminology Glossary', '/resources/ai-glossary.pdf', 'application/pdf', 156000, 2
FROM lessons WHERE module_id = @module1_id AND sort_order = 1;

INSERT INTO lesson_resources (lesson_id, title, file_url, file_type, file_size, sort_order)
SELECT id, 'AI History Timeline Infographic', '/resources/ai-timeline.pdf', 'application/pdf', 1843200, 1
FROM lessons WHERE module_id = @module1_id AND sort_order = 2;

-- =============================================
-- UPDATE COURSE STATISTICS
-- =============================================
UPDATE courses c
SET 
    total_lessons = (
        SELECT COUNT(*) FROM lessons l 
        JOIN modules m ON l.module_id = m.id 
        WHERE m.course_id = c.id
    ),
    total_quizzes = (
        SELECT COUNT(*) FROM quizzes q 
        JOIN modules m ON q.module_id = m.id 
        WHERE m.course_id = c.id
    ),
    total_assignments = (
        SELECT COUNT(*) FROM assignments a 
        JOIN modules m ON a.module_id = m.id 
        WHERE m.course_id = c.id
    );

-- Update quiz total_points
UPDATE quizzes q
SET total_points = (
    SELECT COALESCE(SUM(points), 0) FROM quiz_questions WHERE quiz_id = q.id
);

-- =============================================
-- SUCCESS MESSAGE
-- =============================================
SELECT 'Sample course data inserted successfully!' AS status;
SELECT CONCAT('Created ', COUNT(*), ' courses') AS courses FROM courses;
SELECT CONCAT('Created ', COUNT(*), ' modules') AS modules FROM modules;
SELECT CONCAT('Created ', COUNT(*), ' lessons') AS lessons FROM lessons;
SELECT CONCAT('Created ', COUNT(*), ' quizzes') AS quizzes FROM quizzes;
SELECT CONCAT('Created ', COUNT(*), ' assignments') AS assignments FROM assignments;
