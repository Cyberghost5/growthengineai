<?php
/**
 * GrowthEngineAI LMS - Landing Page
 * Displays courses dynamically from the database
 */

require_once __DIR__ . '/classes/Course.php';

$courseModel = new Course();

// Get featured/published courses from database (limit to 6 for landing page)
$coursesRaw = $courseModel->getAllCourses(['limit' => 6]);
$courses = [];

// Map category icons
$categoryIcons = [
    'cybersecurity' => 'bi-shield-lock',
    'devops' => 'bi-gear-wide-connected',
    'cloud' => 'bi-cloud',
    'cloud computing' => 'bi-cloud',
    'data science' => 'bi-bar-chart-line',
    'software development' => 'bi-code-slash',
    'web development' => 'bi-code-slash',
    'system administration' => 'bi-hdd-network',
    'default' => 'bi-book'
];

foreach ($coursesRaw as $course) {
    $categorySlug = strtolower($course['category_name'] ?? '');
    $icon = $categoryIcons[$categorySlug] ?? $categoryIcons['default'];
    
    // Get what_you_learn as features (limit to 3)
    $whatYouLearn = json_decode($course['what_you_learn'] ?? '[]', true) ?: [];
    $features = array_slice($whatYouLearn, 0, 3);
    
    $courses[] = [
        'id' => $course['id'],
        'slug' => $course['slug'],
        'title' => $course['title'],
        'description' => $course['description'] ? substr(strip_tags($course['description']), 0, 120) . '...' : '',
        'category' => $course['category_name'],
        'category_slug' => $course['category_slug'] ?? strtolower(str_replace(' ', '-', $course['category_name'] ?? 'general')),
        'icon' => $icon,
        'features' => $features,
        'level' => ucfirst($course['level']),
        'is_featured' => $course['is_featured'] ?? false,
        'is_free' => $course['is_free'],
        'price' => $course['is_free'] ? 0 : ($course['sale_price'] > 0 ? $course['sale_price'] : $course['price']),
        'thumbnail' => $course['thumbnail'] ?: 'images/portfolio-' . (($course['id'] % 9) + 1) . '.webp'
    ];
}

// Get categories for portfolio filter
$categoriesRaw = $courseModel->getCategories();
$categories = [];
foreach ($categoriesRaw as $cat) {
    $categories[] = [
        'name' => $cat['name'],
        'slug' => $cat['slug']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>GrowthEngineAI - Your Partner in Intelligent Transformation</title>
  <meta name="description" content="GrowthEngineAI offers premium tech courses in cybersecurity, DevOps, and more. Join our thriving Slack community and accelerate your tech career.">
  <meta name="keywords" content="tech courses, cybersecurity training, DevOps courses, AI learning platform, online tech education">

  <meta name="robots" content="noindex, nofollow">

  <!-- Favicons -->
  <link href="images/favicon.png" rel="icon">
  <link href="images/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin="">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/bootstrap-icons.css" rel="stylesheet">
  <link href="css/aos.css" rel="stylesheet">
  <link href="css/glightbox.min.css" rel="stylesheet">
  <link href="css/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="css/main.css" rel="stylesheet">

</head>

<body class="index-page">

  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="container position-relative d-flex align-items-center justify-content-between">

      <a href="../" class="logo d-flex align-items-center me-auto me-xl-0">
        <!-- Uncomment the line below if you also wish to use an image logo -->
        <img src="images/logo_ge.png" alt="">
        <!-- <h1 class="sitename">GrowthEngineAI</h1> -->
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="#hero" class="active">Home</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#services">Courses</a></li>
          <li><a href="#why-us">Why Us</a></li>
          <li><a href="#testimonials">Testimonials</a></li>
          <li><a href="#contact">Contact</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <a class="btn-getstarted" href="auth/register">Get Started</a>

    </div>
  </header>

  <main class="main">

    <!-- Hero Section -->
    <section id="hero" class="hero section">

      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-7" data-aos="fade-up" data-aos-delay="100">
            <div class="hero-content">
              <div class="hero-badge">
                <span class="badge">🚀 Your Partner in Intelligent Transformation</span>
              </div>
              <h1>AI-Powered Business Automation</h1>
              <p>Accelerate your tech career with premium courses in Cybersecurity, DevOps, Cloud Computing, and more. Join our exclusive Slack community and learn from industry experts.</p>
              <div class="hero-buttons">
                <a href="auth/login" class="btn btn-primary">
                  <span>Get Started</span>
                  <i class="bi bi-arrow-right ms-2"></i>
                </a>
                <a href="#about" class="btn btn-outline">
                  <i class="bi bi-info-circle me-2"></i>
                  <span>Learn More</span>
                </a>
              </div>
            </div>
          </div>
          <div class="col-lg-5" data-aos="fade-up" data-aos-delay="200">
            <div class="hero-visual">
              <div class="product-mockup">
                <div class="mockup-frame">
                  <img src="images/ge-new.png" alt="Product Dashboard" class="img-fluid">
                </div>
                <div class="floating-card card-1" data-aos="fade-up" data-aos-delay="400">
                  <div class="card-icon">
                    <i class="bi bi-book"></i>
                  </div>
                  <div class="card-content">
                    <div class="card-value">10+</div>
                    <div class="card-label">Premium Courses</div>
                  </div>
                </div>
                <div class="floating-card card-2" data-aos="fade-up" data-aos-delay="500">
                  <div class="card-icon">
                    <i class="bi bi-slack"></i>
                  </div>
                  <div class="card-content">
                    <div class="card-value">Active</div>
                    <div class="card-label">Slack Community</div>
                  </div>
                </div>
                <div class="floating-card card-3" data-aos="fade-up" data-aos-delay="600">
                  <div class="card-icon">
                    <i class="bi bi-award"></i>
                  </div>
                  <div class="card-content">
                    <div class="card-value">Expert</div>
                    <div class="card-label">Instructors</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="hero-background">
        <div class="gradient-blob blob-1"></div>
        <div class="gradient-blob blob-2"></div>
        <div class="grid-pattern"></div>
      </div>

    </section><!-- /Hero Section -->

    <!-- About Section -->
    <section id="about" class="about section light-background">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
            <div class="content-wrapper">
              <div class="section-badge">About Us</div>
              <h2>Empowering the Next Generation of Tech Professionals</h2>
              <p class="lead">GrowthEngineAI is a cutting-edge learning platform designed for individuals who want to excel in today's competitive tech industry. We offer premium courses in high-demand fields like Cybersecurity, DevOps, Cloud Computing, and more—backed by our thriving Slack community where learners connect, collaborate, and grow together.</p>

              <div class="stats-grid">
                <div class="stat-item">
                  <div class="stat-number purecounter" data-purecounter-start="0" data-purecounter-end="10" data-purecounter-duration="1">+</div>
                  <div class="stat-label">Premium Courses</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number purecounter" data-purecounter-start="0" data-purecounter-end="5" data-purecounter-duration="1">+</div>
                  <div class="stat-label">Tech Fields</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number purecounter" data-purecounter-start="0" data-purecounter-end="24" data-purecounter-duration="1">/7</div>
                  <div class="stat-label">Community Support</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number purecounter" data-purecounter-start="0" data-purecounter-end="100" data-purecounter-duration="1">%</div>
                  <div class="stat-label">Practical Learning</div>
                </div>
              </div>

              <div class="features-row">
                <div class="feature-item">
                  <div class="feature-icon">
                    <i class="bi bi-slack"></i>
                  </div>
                  <div class="feature-content">
                    <h4>Exclusive Slack Community</h4>
                    <p>Connect with fellow learners, get instant support, and network with industry professionals in our active community.</p>
                  </div>
                </div>
                <div class="feature-item">
                  <div class="feature-icon">
                    <i class="bi bi-laptop"></i>
                  </div>
                  <div class="feature-content">
                    <h4>Hands-On Learning</h4>
                    <p>Our courses focus on practical, real-world skills that employers are actively looking for in today's job market.</p>
                  </div>
                </div>
              </div>

              <div class="cta-group">
                <a href="#services" class="btn btn-primary">Explore Courses <i class="bi bi-arrow-right"></i></a>
                <a href="#contact" class="btn btn-secondary">Join Community</a>
              </div>
            </div>
          </div>

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="300">
            <div class="image-stack">
              <div class="image-card image-primary">
                <img src="images/about-7.webp" alt="Team collaboration" class="img-fluid">
              </div>
              <div class="image-card image-secondary">
                <img src="images/about-square-5.webp" alt="Digital workspace" class="img-fluid">
              </div>
              <div class="floating-badge">
                <div class="badge-icon">
                  <i class="bi bi-lightbulb-fill"></i>
                </div>
                <div class="badge-text">
                  <div class="badge-title">Knowledge for</div>
                  <div class="badge-subtitle">The New Age</div>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>

    </section><!-- /About Section -->

    <!-- Services Section -->
    <section id="services" class="services section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Courses</h2>
        <div><span>Explore Our</span> <span class="description-title">Premium Courses</span></div>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="section-intro">
          <div class="row align-items-center">
            <div class="col-lg-7">
              <h2 class="intro-title">Industry-Leading Tech Courses</h2>
              <p class="intro-text">Master the skills that top employers demand. Our premium courses are designed to take you from beginner to job-ready professional.</p>
            </div>
            <div class="col-lg-5 text-lg-end">
              <a href="student/courses" class="btn-view-all">View All Courses <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div>

        <div class="row gy-4">
          <!-- Static Service Cards -->
          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="150">
            <div class="service-item">
              <div class="service-header">
                <div class="icon-wrapper">
                  <i class="bi bi-shield-lock"></i>
                </div>
                <span class="badge-popular">Coming Soon</span>
              </div>
              <h3>Cybersecurity Training</h3>
              <p>Master ethical hacking, penetration testing, and security operations. Prepare for industry certifications and protect organizations from cyber threats.</p>
              <ul class="feature-list">
                <li><i class="bi bi-check-circle"></i> Ethical Hacking & Pen Testing</li>
                <li><i class="bi bi-check-circle"></i> Network Security</li>
                <li><i class="bi bi-check-circle"></i> SOC Analyst Training</li>
              </ul>
              <a href="student/courses" class="service-cta">
                <span>Explore Courses</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="service-item">
              <div class="service-header">
                <div class="icon-wrapper">
                  <i class="bi bi-gear-wide-connected"></i>
                </div>
                <span class="badge-popular">Coming Soon</span>
              </div>
              <h3>DevOps Engineering</h3>
              <p>Learn containerization, CI/CD pipelines, and infrastructure automation. Deploy applications with confidence using modern DevOps practices.</p>
              <ul class="feature-list">
                <li><i class="bi bi-check-circle"></i> Docker & Kubernetes</li>
                <li><i class="bi bi-check-circle"></i> CI/CD Pipelines</li>
                <li><i class="bi bi-check-circle"></i> Infrastructure as Code</li>
              </ul>
              <a href="student/courses" class="service-cta">
                <span>Explore Courses</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="250">
            <div class="service-item">
              <div class="service-header">
                <div class="icon-wrapper">
                  <i class="bi bi-cloud"></i>
                </div>
                <span class="badge-popular">Coming Soon</span>
              </div>
              <h3>Cloud Computing</h3>
              <p>Build and manage cloud infrastructure on AWS, Azure, and GCP. Prepare for cloud certifications and architect scalable solutions.</p>
              <ul class="feature-list">
                <li><i class="bi bi-check-circle"></i> AWS Solutions Architect</li>
                <li><i class="bi bi-check-circle"></i> Azure Fundamentals</li>
                <li><i class="bi bi-check-circle"></i> Cloud Security</li>
              </ul>
              <a href="student/courses" class="service-cta">
                <span>Explore Courses</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="service-item">
              <div class="service-header">
                <div class="icon-wrapper">
                  <i class="bi bi-code-slash"></i>
                </div>
                <span class="badge-popular">Coming Soon</span>
              </div>
              <h3>Software Development</h3>
              <p>Build complete web and mobile applications. Learn modern frameworks and best practices for professional software development.</p>
              <ul class="feature-list">
                <li><i class="bi bi-check-circle"></i> Full-Stack Web Development</li>
                <li><i class="bi bi-check-circle"></i> Python Programming</li>
                <li><i class="bi bi-check-circle"></i> API Development</li>
              </ul>
              <a href="student/courses" class="service-cta">
                <span>Explore Courses</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="350">
            <div class="service-item">
              <div class="service-header">
                <div class="icon-wrapper">
                  <i class="bi bi-bar-chart-line"></i>
                </div>
                <span class="badge-popular">Coming Soon</span>
              </div>
              <h3>Data Science & AI</h3>
              <p>Unlock the power of data. Learn machine learning, data analysis, and AI implementation for real-world business problems.</p>
              <ul class="feature-list">
                <li><i class="bi bi-check-circle"></i> Machine Learning</li>
                <li><i class="bi bi-check-circle"></i> Data Analysis</li>
                <li><i class="bi bi-check-circle"></i> AI Implementation</li>
              </ul>
              <a href="student/courses" class="service-cta">
                <span>Explore Courses</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="service-item">
              <div class="service-header">
                <div class="icon-wrapper">
                  <i class="bi bi-hdd-network"></i>
                </div>
                <span class="badge-popular">Coming Soon</span>
              </div>
              <h3>System Administration</h3>
              <p>Master Linux and Windows server administration. Learn to manage enterprise infrastructure and maintain system reliability.</p>
              <ul class="feature-list">
                <li><i class="bi bi-check-circle"></i> Linux Administration</li>
                <li><i class="bi bi-check-circle"></i> Windows Server</li>
                <li><i class="bi bi-check-circle"></i> Network Management</li>
              </ul>
              <a href="student/courses" class="service-cta">
                <span>Explore Courses</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

        </div>

        <div class="cta-banner" data-aos="fade-up" data-aos-delay="300">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <div class="cta-content">
                <div class="cta-badge">Ready to Learn?</div>
                <h3>Start Your Tech Career Journey Today</h3>
                <p>Join thousands of learners who are transforming their careers with GrowthEngineAI. Get access to premium courses and our exclusive Slack community.</p>
              </div>
            </div>
            <div class="col-lg-4 text-lg-end">
              <a href="#contact" class="btn-primary">Get Started <i class="bi bi-arrow-right"></i></a>
              <a href="#contact" class="btn-secondary">Join Community</a>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Services Section -->

    <!-- Portfolio Section -->
    <section id="portfolio" class="portfolio section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Learning Paths</h2>
        <div><span>Explore Our</span> <span class="description-title">Learning Paths</span></div>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="isotope-layout" data-default-filter="*" data-layout="masonry" data-sort="original-order">
          <ul class="portfolio-filters isotope-filters" data-aos="fade-up" data-aos-delay="200">
            <li data-filter="*" class="filter-active">All</li>
            <?php foreach ($categories as $cat): ?>
            <li data-filter=".filter-<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></li>
            <?php endforeach; ?>
          </ul>

          <div class="row gy-4 isotope-container" data-aos="fade-up" data-aos-delay="300">
            <?php if (empty($courses)): ?>
            <div class="col-12 text-center">
              <p class="text-muted">No courses available at the moment. Check back soon!</p>
            </div>
            <?php else: ?>
            <?php foreach ($courses as $course): 
              $filterClass = 'filter-' . ($course['category_slug'] ?? 'general');
            ?>
            <div class="col-lg-4 col-md-6 portfolio-item isotope-item <?= htmlspecialchars($filterClass) ?>">
              <div class="portfolio-card">
                <div class="card-image">
                  <img src="<?= htmlspecialchars($course['thumbnail']) ?>" alt="<?= htmlspecialchars($course['title']) ?>" class="img-fluid" loading="lazy">
                  <div class="overlay">
                    <a href="<?= htmlspecialchars($course['thumbnail']) ?>" class="glightbox icon-btn">
                      <i class="bi bi-arrows-fullscreen"></i>
                    </a>
                  </div>
                  <?php if ($course['is_featured']): ?>
                  <div class="tag">Featured</div>
                  <?php elseif ($course['is_free']): ?>
                  <div class="tag">Free</div>
                  <?php endif; ?>
                </div>
                <div class="card-content">
                  <div class="meta">
                    <span class="category"><?= htmlspecialchars($course['category'] ?? 'General') ?></span>
                    <span class="year"><?= htmlspecialchars($course['level']) ?></span>
                  </div>
                  <h3><?= htmlspecialchars($course['title']) ?></h3>
                  <p><?= htmlspecialchars($course['description']) ?></p>
                  <?php if (!empty($course['features'])): ?>
                  <div class="tech-stack">
                    <?php foreach (array_slice($course['features'], 0, 3) as $feature): ?>
                    <span><?= htmlspecialchars(strlen($feature) > 15 ? substr($feature, 0, 15) . '...' : $feature) ?></span>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                  <a href="student/course/<?= htmlspecialchars($course['slug']) ?>" class="view-project">Enroll Now <i class="bi bi-arrow-right"></i></a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </div>

        <div class="cta-box" data-aos="fade-up" data-aos-delay="400">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <h3>Ready to start your learning journey?</h3>
              <p>Join GrowthEngineAI and gain the skills you need to succeed in the tech industry. Our expert-led courses and supportive community are here to help you grow.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
              <a href="#contact" class="btn-primary">Get Started</a>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Portfolio Section -->

    <!-- Why Us Section -->
    <section id="why-us" class="why-us section light-background">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Why Us</h2>
        <div><span>Why</span> <span class="description-title">Choose Us</span></div>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row align-items-center mb-5">
          <div class="col-lg-6" data-aos="fade-right" data-aos-delay="200">
            <div class="intro-content">
              <span class="badge">Why Choose Us</span>
              <h2>Learn From the Best, Become the Best</h2>
              <p class="lead">GrowthEngineAI stands out with our unique combination of premium courses, hands-on projects, and an exclusive Slack community where you connect directly with instructors and fellow learners. We're building the future of tech education.</p>
              <div class="stats-grid">
                <div class="stat-item">
                  <div class="stat-number" data-purecounter-start="0" data-purecounter-end="10" data-purecounter-duration="2">10+</div>
                  <div class="stat-label">Premium Courses</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number" data-purecounter-start="0" data-purecounter-end="24" data-purecounter-duration="2">24/7</div>
                  <div class="stat-label">Community Support</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number" data-purecounter-start="0" data-purecounter-end="100" data-purecounter-duration="2">100%</div>
                  <div class="stat-label">Practical Learning</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6" data-aos="fade-left" data-aos-delay="300">
            <div class="showcase-image">
              <img src="images/illustration-18.webp" alt="Digital Solutions" class="img-fluid">
            </div>
          </div>
        </div>

        <div class="row g-4 mb-5">
          <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="100">
            <div class="value-card">
              <div class="icon-box">
                <i class="bi bi-slack"></i>
              </div>
              <h4>Exclusive Slack Community</h4>
              <p>Connect with instructors and peers in real-time. Get your questions answered, collaborate on projects, and build your professional network.</p>
              <a href="#contact" class="learn-more">Join Community <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="200">
            <div class="value-card featured">
              <div class="featured-badge">Most Popular</div>
              <div class="icon-box">
                <i class="bi bi-laptop"></i>
              </div>
              <h4>Hands-On Projects</h4>
              <p>Learn by doing with real-world projects that prepare you for actual job requirements. Build a portfolio that impresses employers.</p>
              <a href="#services" class="learn-more">View Courses <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="300">
            <div class="value-card">
              <div class="icon-box">
                <i class="bi bi-person-video3"></i>
              </div>
              <h4>Expert Instructors</h4>
              <p>Learn from industry professionals with years of experience. Our instructors bring real-world insights to every lesson.</p>
              <a href="#about" class="learn-more">Learn More <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div>

        <div class="row align-items-center">
          <div class="col-lg-6 order-lg-2" data-aos="fade-left" data-aos-delay="200">
            <div class="capabilities-content">
              <h3>What Makes Us Different</h3>
              <p>At GrowthEngineAI, we've built a learning experience that goes beyond traditional online courses. Here's what sets us apart:</p>

              <div class="capability-list">
                <div class="capability-item">
                  <div class="capability-header">
                    <i class="bi bi-check-circle-fill"></i>
                    <h5>Premium Quality Curriculum</h5>
                  </div>
                  <p>Our courses are meticulously designed by industry experts to cover the most in-demand skills employers are looking for today.</p>
                </div>

                <div class="capability-item">
                  <div class="capability-header">
                    <i class="bi bi-check-circle-fill"></i>
                    <h5>Active Slack Community</h5>
                  </div>
                  <p>Unlike other platforms, we provide a thriving Slack community where you can network, collaborate, and get real-time support.</p>
                </div>

                <div class="capability-item">
                  <div class="capability-header">
                    <i class="bi bi-check-circle-fill"></i>
                    <h5>Career-Focused Learning</h5>
                  </div>
                  <p>Every course is designed to help you build practical skills and a portfolio that will impress potential employers.</p>
                </div>
              </div>

              <div class="cta-buttons">
                <a href="#services" class="btn btn-primary">Explore Courses</a>
                <a href="#contact" class="btn btn-secondary">Join Community</a>
              </div>
            </div>
          </div>

          <div class="col-lg-6 order-lg-1" data-aos="fade-right" data-aos-delay="300">
            <div class="process-visual">
              <div class="process-step" data-aos="fade-up" data-aos-delay="400">
                <div class="step-number">01</div>
                <div class="step-content">
                  <h6>Enroll</h6>
                  <p>Choose your course</p>
                </div>
              </div>
              <div class="process-step" data-aos="fade-up" data-aos-delay="450">
                <div class="step-number">02</div>
                <div class="step-content">
                  <h6>Learn</h6>
                  <p>Study at your pace</p>
                </div>
              </div>
              <div class="process-step" data-aos="fade-up" data-aos-delay="500">
                <div class="step-number">03</div>
                <div class="step-content">
                  <h6>Practice</h6>
                  <p>Hands-on projects</p>
                </div>
              </div>
              <div class="process-step" data-aos="fade-up" data-aos-delay="550">
                <div class="step-number">04</div>
                <div class="step-content">
                  <h6>Succeed</h6>
                  <p>Launch your career</p>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Why Us Section -->

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials section light-background">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Testimonials</h2>
        <div><span>What Our</span> <span class="description-title">Students Say</span></div>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="testimonials-slider swiper init-swiper">
          <script type="application/json" class="swiper-config">
            {
              "slidesPerView": 1,
              "loop": true,
              "speed": 600,
              "autoplay": {
                "delay": 5000
              },
              "navigation": {
                "nextEl": ".swiper-button-next",
                "prevEl": ".swiper-button-prev"
              }
            }
          </script>

          <div class="swiper-wrapper">

            <div class="swiper-slide">
              <div class="testimonial-item">
                <div class="row">
                  <div class="col-lg-8">
                    <h2>The Cybersecurity course changed my career</h2>
                    <p>
                      I was stuck in a dead-end IT support job until I found GrowthEngineAI. The Ethical Hacking course was incredibly comprehensive and hands-on. The Slack community was invaluable - I got help whenever I was stuck.
                    </p>
                    <p>
                      Within 3 months of completing the course, I landed a junior penetration tester role. The practical skills I learned here made all the difference in my interviews. Highly recommended for anyone serious about tech!
                    </p>
                    <div class="profile d-flex align-items-center">
                      <img src="images/person-m-7.webp" class="profile-img" alt="">
                      <div class="profile-info">
                        <h3>Emmanuel Okonkwo</h3>
                        <span>Cybersecurity Student</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4 d-none d-lg-block">
                    <div class="featured-img-wrapper">
                      <img src="images/person-m-7.webp" class="featured-img" alt="">
                    </div>
                  </div>
                </div>
              </div>
            </div><!-- End Testimonial Item -->

            <div class="swiper-slide">
              <div class="testimonial-item">
                <div class="row">
                  <div class="col-lg-8">
                    <h2>Best DevOps training I've ever taken</h2>
                    <p>
                      As someone transitioning from traditional development to DevOps, I needed structured, practical training. GrowthEngineAI's Docker & Kubernetes course was exactly what I needed. The instructors explain complex concepts clearly.
                    </p>
                    <p>
                      What really sets this platform apart is the Slack community. I've made connections with professionals from around the world and even collaborated on open-source projects. This isn't just a course platform, it's a career launchpad.
                    </p>
                    <div class="profile d-flex align-items-center">
                      <img src="images/person-f-8.webp" class="profile-img" alt="">
                      <div class="profile-info">
                        <h3>Fatima Ibrahim</h3>
                        <span>DevOps Student</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4 d-none d-lg-block">
                    <div class="featured-img-wrapper">
                      <img src="images/person-f-8.webp" class="featured-img" alt="">
                    </div>
                  </div>
                </div>
              </div>
            </div><!-- End Testimonial Item -->

            <div class="swiper-slide">
              <div class="testimonial-item">
                <div class="row">
                  <div class="col-lg-8">
                    <h2>
                      Finally mastered cloud computing!
                    </h2>
                    <p>
                      I tried learning AWS on my own for months but kept getting overwhelmed. The AWS Solutions Architect course here broke everything down into manageable pieces. The hands-on labs were game-changers.
                    </p>
                    <p>
                      The Slack community helped me prepare for my certification exam. Other students shared their experiences and tips. I passed on my first attempt! Now working remotely as a cloud engineer. Thank you GrowthEngineAI!
                    </p>
                    <div class="profile d-flex align-items-center">
                      <img src="images/person-m-9.webp" class="profile-img" alt="">
                      <div class="profile-info">
                        <h3>Chidi Amaechi</h3>
                        <span>Cloud Computing Student</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4 d-none d-lg-block">
                    <div class="featured-img-wrapper">
                      <img src="images/person-m-9.webp" class="featured-img" alt="">
                    </div>
                  </div>
                </div>
              </div>
            </div><!-- End Testimonial Item -->

            <div class="swiper-slide">
              <div class="testimonial-item">
                <div class="row">
                  <div class="col-lg-8">
                    <h2>From zero coding to full-stack developer</h2>
                    <p>
                      I had no programming background when I started the Full-Stack Web Development course. The curriculum was well-structured and the instructors were patient. The projects gave me real confidence.
                    </p>
                    <p>
                      The best part? The Slack community is so supportive. Whenever I got stuck on a bug, someone was always there to help. I've now built three complete web applications and I'm freelancing on the side. GrowthEngineAI changed my life!
                    </p>
                    <div class="profile d-flex align-items-center">
                      <img src="images/person-f-10.webp" class="profile-img" alt="">
                      <div class="profile-info">
                        <h3>Aisha Mohammed</h3>
                        <span>Web Development Student</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4 d-none d-lg-block">
                    <div class="featured-img-wrapper">
                      <img src="images/person-f-10.webp" class="featured-img" alt="">
                    </div>
                  </div>
                </div>
              </div>
            </div><!-- End Testimonial Item -->

          </div>

          <div class="swiper-navigation w-100 d-flex align-items-center justify-content-center">
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
          </div>

        </div>

      </div>

    </section><!-- /Testimonials Section -->

    <!-- Contact Section -->
    <section id="contact" class="contact section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Contact</h2>
        <div><span>Get In Touch</span> <span class="description-title">With Us</span></div>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row g-5">

          <div class="col-lg-6">
            <div class="contact-intro">
              <div class="intro-badge">
                <i class="bi bi-rocket-takeoff"></i>
                <span>Start Your Learning Journey</span>
              </div>
              <h2>Ready to Level Up Your Tech Skills?</h2>
              <p>Join GrowthEngineAI today and gain access to premium courses, hands-on projects, and our exclusive Slack community. Whether you're starting from scratch or advancing your career, we're here to help you succeed.</p>
            </div>

            <div class="contact-channels">
              <div class="channel-card">
                <div class="channel-icon">
                  <i class="bi bi-envelope-fill"></i>
                </div>
                <div class="channel-info">
                  <h5>Email</h5>
                  <p>info@growthengineai.org</p>
                  <span class="channel-meta">We reply within 24 hours</span>
                </div>
              </div>

              <div class="channel-card">
                <div class="channel-icon">
                  <i class="bi bi-telephone-fill"></i>
                </div>
                <div class="channel-info">
                  <h5>Phone</h5>
                  <p>+234 802 222 4350</p>
                  <span class="channel-meta">Mon-Fri, 9AM-5PM WAT</span>
                </div>
              </div>

              <div class="channel-card">
                <div class="channel-icon">
                  <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div class="channel-info">
                  <h5>Location</h5>
                  <p>Bauchi, Nigeria</p>
                  <span class="channel-meta">Serving students worldwide</span>
                </div>
              </div>
            </div>

            <div class="trust-indicators">
              <div class="indicator-item">
                <div class="indicator-value">10+</div>
                <div class="indicator-label">Premium Courses</div>
              </div>
              <div class="indicator-item">
                <div class="indicator-value">24/7</div>
                <div class="indicator-label">Community Access</div>
              </div>
              <div class="indicator-item">
                <div class="indicator-value">Free</div>
                <div class="indicator-label">Slack Community</div>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="form-wrapper">
              <div class="form-header">
                <h3>Get Started Today</h3>
                <p>Have questions? Send us a message and we'll get back to you with all the information you need.</p>
              </div>

              <form action="forms/contact" method="post" class="php-email-form">
                <div class="form-group">
                  <label>Full Name</label>
                  <input type="text" name="name" class="form-control" required="">
                </div>

                <div class="form-group">
                  <label>Email Address</label>
                  <input type="email" name="email" class="form-control" required="">
                </div>

                <div class="form-group">
                  <label>Subject</label>
                  <input type="text" name="subject" class="form-control" required="">
                </div>

                <div class="form-group">
                  <label>Message</label>
                  <textarea name="message" class="form-control" rows="5" required=""></textarea>
                </div>
                <div class="loading">Loading</div>
                <div class="error-message"></div>
                <div class="sent-message">Your message has been sent. Thank you!</div>

                <button type="submit" class="submit-btn">
                  <span>Get Started</span>
                  <i class="bi bi-arrow-right"></i>
                </button>

                <div class="form-footer">
                  <i class="bi bi-shield-check"></i>
                  <span>Your information is secure and will never be shared</span>
                </div>
              </form>
            </div>
          </div>

        </div>

      </div>

    </section><!-- /Contact Section -->

  </main>

  <footer id="footer" class="footer">

    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-5 col-md-12 footer-about">
          <a href="../" class="logo d-flex align-items-center">
            <img src="images/logo_ge.png" alt="" style="max-height: 60px;">
            <!-- <span class="sitename">GrowthEngineAI</span> -->
          </a>
          <p>Your Partner in Intelligent Transformation. GrowthEngineAI is your gateway to mastering in-demand tech skills. Join our community of learners and take the first step towards an exciting career in technology.</p>
          <div class="social-links d-flex mt-4">
            <a href="https://twitter.com/growthengineai"><i class="bi bi-twitter-x"></i></a>
            <a href="https://facebook.com/growthengineai"><i class="bi bi-facebook"></i></a>
            <a href="https://instagram.com/growthengineai"><i class="bi bi-instagram"></i></a>
            <a href="https://linkedin.com/company/growthengineai"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>

        <div class="col-lg-2 col-6 footer-links">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="#hero">Home</a></li>
            <li><a href="#about">About Us</a></li>
            <li><a href="#services">Courses</a></li>
            <li><a href="#">Terms of Service</a></li>
            <li><a href="#">Privacy Policy</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-6 footer-links">
          <h4>Our Courses</h4>
          <ul>
            <li><a href="#services">Cybersecurity</a></li>
            <li><a href="#services">DevOps</a></li>
            <li><a href="#services">Cloud Computing</a></li>
            <li><a href="#services">Data Science</a></li>
            <li><a href="#services">Software Development</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-12 footer-contact text-center text-md-start">
          <h4>Contact Us</h4>
          <p>Bauchi</p>
          <p>Nigeria</p>
          <p class="mt-4"><strong>Phone:</strong> <span>+234 802 222 4350</span></p>
          <p><strong>Email:</strong> <span>info@growthengineai.org</span></p>
        </div>

      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p>© <span>Copyright</span> <strong class="px-1 sitename">GrowthEngineAI</strong> <span>All Rights Reserved</span></p>
      <div class="credits">
        Your Partner in Intelligent Transformation
      </div>
    </div>

  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script data-cfasync="false" src="js/email-decode.min.js"></script><script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/validate.js"></script>
  <script src="js/aos.js"></script>
  <script src="js/glightbox.min.js"></script>
  <script src="js/purecounter_vanilla.js"></script>
  <script src="js/imagesloaded.pkgd.min.js"></script>
  <script src="js/isotope.pkgd.min.js"></script>
  <script src="js/swiper-bundle.min.js"></script>

  <!-- Main JS File -->
  <script src="js/main.js"></script>

<script defer="" src="https://static.cloudflareinsights.com/beacon.min.js/vcd15cbe7772f49c399c6a5babf22c1241717689176015" data-cf-beacon="{" version":"2024.11.0","token":"68c5ca450bae485a842ff76066d69420","server_timing":{"name":{"cfcachestatus":true,"cfedge":true,"cfextpri":true,"cfl4":true,"cforigin":true,"cfspeedbrain":true},"location_startswith":null}}"="" crossorigin="anonymous"></script>


</body></html>