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
  <title>GrowthEngineAI - Tech Education & Consulting Partner</title>
  <meta name="description" content="GrowthEngineAI offers premium tech courses and expert consulting in cybersecurity, DevOps, cloud, and AI. Transform your career and your business.">
  <meta name="keywords" content="tech courses, cybersecurity training, DevOps courses, AI consulting, cloud consulting, digital transformation, IT consulting, online tech education">

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
          <li><a href="#consulting">Consulting</a></li>
          <li><a href="#services">Courses</a></li>
          <li><a href="#why-us">Why Us</a></li>
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
              <h1>Grow With Expert <span class="hero-highlight">Education</span> &amp; <span class="hero-highlight">Consulting</span></h1>
              <p>GrowthEngineAI powers organizations and individuals with premium tech training and hands-on consulting across Cybersecurity, DevOps, Cloud, and AI. Whether you're upskilling your team or transforming your business—we're the partner you need.</p>
              <div class="hero-dual-pill">
                <div class="pill-item">
                  <i class="bi bi-mortarboard-fill"></i>
                  <span>Tech Education</span>
                </div>
                <div class="pill-divider">+</div>
                <div class="pill-item">
                  <i class="bi bi-briefcase-fill"></i>
                  <span>Expert Consulting</span>
                </div>
              </div>
              <div class="hero-buttons">
                <a href="auth/login" class="btn btn-primary">
                  <span>Explore Courses</span>
                  <i class="bi bi-arrow-right ms-2"></i>
                </a>
                <a href="#consulting" class="btn btn-outline">
                  <i class="bi bi-briefcase me-2"></i>
                  <span>Get Consulting</span>
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
                    <i class="bi bi-briefcase"></i>
                  </div>
                  <div class="card-content">
                    <div class="card-value">Expert</div>
                    <div class="card-label">Consulting Services</div>
                  </div>
                </div>
                <div class="floating-card card-3" data-aos="fade-up" data-aos-delay="600">
                  <div class="card-icon">
                    <i class="bi bi-award"></i>
                  </div>
                  <div class="card-content">
                    <div class="card-value">Industry</div>
                    <div class="card-label">Expert Instructors</div>
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
              <h2>Two Powerful Pillars — Education &amp; Consulting</h2>
              <p class="lead">GrowthEngineAI is a dual-force platform: a cutting-edge learning hub and a results-driven consulting firm. We train individuals to thrive in tech and advise businesses on digital transformation, cloud strategy, AI adoption, and cybersecurity resilience.</p>

              <div class="stats-grid">
                <div class="stat-item">
                  <div class="stat-number purecounter" data-purecounter-start="0" data-purecounter-end="10" data-purecounter-duration="1">+</div>
                  <div class="stat-label">Premium Courses</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number purecounter" data-purecounter-start="0" data-purecounter-end="6" data-purecounter-duration="1">+</div>
                  <div class="stat-label">Consulting Practices</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number purecounter" data-purecounter-start="0" data-purecounter-end="24" data-purecounter-duration="1">/7</div>
                  <div class="stat-label">Community Support</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number purecounter" data-purecounter-start="0" data-purecounter-end="100" data-purecounter-duration="1">%</div>
                  <div class="stat-label">Practical Focus</div>
                </div>
              </div>

              <div class="features-row">
                <div class="feature-item">
                  <div class="feature-icon">
                    <i class="bi bi-mortarboard"></i>
                  </div>
                  <div class="feature-content">
                    <h4>World-Class Tech Education</h4>
                    <p>Premium courses in Cybersecurity, DevOps, Cloud, and more — backed by our active Whatsapp community where learners connect and grow together.</p>
                  </div>
                </div>
                <div class="feature-item">
                  <div class="feature-icon">
                    <i class="bi bi-briefcase"></i>
                  </div>
                  <div class="feature-content">
                    <h4>Strategic Tech Consulting</h4>
                    <p>Expert guidance for businesses navigating digital transformation, cloud adoption, AI integration, and cybersecurity hardening.</p>
                  </div>
                </div>
              </div>

              <div class="cta-group">
                <a href="#services" class="btn btn-primary">Explore Courses <i class="bi bi-arrow-right"></i></a>
                <a href="#consulting" class="btn btn-secondary">Our Consulting</a>
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
                  <div class="badge-title">Knowledge &amp;</div>
                  <div class="badge-subtitle">Strategy Combined</div>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>

    </section><!-- /About Section -->


    <!-- Consulting Section -->
    <section id="consulting" class="consulting section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Consulting</h2>
        <div><span>Strategic</span> <span class="description-title">Consulting Services</span></div>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="consulting-intro">
          <div class="row align-items-center">
            <div class="col-lg-7">
              <h2 class="intro-title">Transform Your Business With Expert Guidance</h2>
              <p class="intro-text">Beyond education, GrowthEngineAI brings deep technical expertise directly to your organization. We work alongside your teams to architect solutions, harden security postures, and accelerate digital transformation initiatives.</p>
            </div>
            <div class="col-lg-5 text-lg-end">
              <a href="#contact" class="btn-view-all">Book a Consultation <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div>

        <div class="row gy-4">

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="150">
            <div class="consulting-card">
              <div class="consulting-card-header">
                <div class="icon-wrapper">
                  <i class="bi bi-shield-lock"></i>
                </div>
              </div>
              <h3>Cybersecurity Consulting</h3>
              <p>Protect your business from evolving threats. We deliver penetration testing, security audits, incident response planning, and security operations centre (SOC) setup.</p>
              <ul class="feature-list">
                <li><i class="bi bi-check-circle"></i> Penetration Testing &amp; Audits</li>
                <li><i class="bi bi-check-circle"></i> SOC Design &amp; Implementation</li>
                <li><i class="bi bi-check-circle"></i> Compliance &amp; Risk Management</li>
              </ul>
              <a href="#contact" class="consulting-cta">
                <span>Get a Quote</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="consulting-card consulting-card-featured">
              <div class="featured-ribbon">Most Popular</div>
              <div class="consulting-card-header">
                <div class="icon-wrapper">
                  <i class="bi bi-cloud-arrow-up"></i>
                </div>
              </div>
              <h3>Cloud Strategy &amp; Migration</h3>
              <p>Move to the cloud with confidence. We design, migrate, and optimize your workloads on AWS, Azure, or GCP — cutting costs and boosting scalability.</p>
              <ul class="feature-list">
                <li><i class="bi bi-check-circle"></i> Cloud Architecture Design</li>
                <li><i class="bi bi-check-circle"></i> Workload Migration</li>
                <li><i class="bi bi-check-circle"></i> Cost Optimization</li>
              </ul>
              <a href="#contact" class="consulting-cta">
                <span>Get a Quote</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="250">
            <div class="consulting-card">
              <div class="consulting-card-header">
                <div class="icon-wrapper">
                  <i class="bi bi-robot"></i>
                </div>
              </div>
              <h3>AI &amp; Automation Consulting</h3>
              <p>Unlock the power of artificial intelligence for your business. We identify automation opportunities and integrate AI workflows that save time and drive revenue.</p>
              <ul class="feature-list">
                <li><i class="bi bi-check-circle"></i> AI Readiness Assessment</li>
                <li><i class="bi bi-check-circle"></i> Process Automation</li>
                <li><i class="bi bi-check-circle"></i> ML Model Integration</li>
              </ul>
              <a href="#contact" class="consulting-cta">
                <span>Get a Quote</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="consulting-card">
              <div class="consulting-card-header">
                <div class="icon-wrapper">
                  <i class="bi bi-gear-wide-connected"></i>
                </div>
              </div>
              <h3>DevOps Transformation</h3>
              <p>Break silos between development and operations. We implement CI/CD pipelines, containerization strategies, and infrastructure-as-code to ship faster and more reliably.</p>
              <ul class="feature-list">
                <li><i class="bi bi-check-circle"></i> CI/CD Pipeline Setup</li>
                <li><i class="bi bi-check-circle"></i> Kubernetes &amp; Docker</li>
                <li><i class="bi bi-check-circle"></i> Infrastructure as Code</li>
              </ul>
              <a href="#contact" class="consulting-cta">
                <span>Get a Quote</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="350">
            <div class="consulting-card">
              <div class="consulting-card-header">
                <div class="icon-wrapper">
                  <i class="bi bi-diagram-3"></i>
                </div>
              </div>
              <h3>Digital Transformation</h3>
              <p>Modernize your entire technology stack. From legacy system migration to building new digital products, we guide your organization through every step of the transformation.</p>
              <ul class="feature-list">
                <li><i class="bi bi-check-circle"></i> IT Strategy &amp; Roadmapping</li>
                <li><i class="bi bi-check-circle"></i> Legacy System Modernization</li>
                <li><i class="bi bi-check-circle"></i> Digital Product Development</li>
              </ul>
              <a href="#contact" class="consulting-cta">
                <span>Get a Quote</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="consulting-card">
              <div class="consulting-card-header">
                <div class="icon-wrapper">
                  <i class="bi bi-people"></i>
                </div>
              </div>
              <h3>Training &amp; Capacity Building</h3>
              <p>Upskill your workforce with custom training programs built around your technology stack and business goals. We deliver on-site or virtual sessions tailored to your team.</p>
              <ul class="feature-list">
                <li><i class="bi bi-check-circle"></i> Custom Corporate Training</li>
                <li><i class="bi bi-check-circle"></i> Hands-On Workshops</li>
                <li><i class="bi bi-check-circle"></i> Ongoing Support &amp; Mentorship</li>
              </ul>
              <a href="#contact" class="consulting-cta">
                <span>Get a Quote</span>
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

        </div>

        <!-- Consulting CTA Banner -->
        <div class="consulting-cta-banner" data-aos="fade-up" data-aos-delay="300">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <div class="cta-content">
                <div class="cta-badge">Ready to Transform?</div>
                <h3>Let's Build Your Growth Strategy Together</h3>
                <p>From a quick advisory call to a full engagement, GrowthEngineAI consulting adapts to your needs. Let's talk about what's holding your business back.</p>
              </div>
            </div>
            <div class="col-lg-4 text-lg-end">
              <a href="#contact" class="btn-primary">Book a Free Call <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Consulting Section -->


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
              <a href="auth/login" class="btn-primary">Get Started</a>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Portfolio Section -->

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
                <p>Join thousands of learners who are transforming their careers with GrowthEngineAI. Get access to premium courses and our exclusive Whatsapp community.</p>
              </div>
            </div>
            <div class="col-lg-4 text-lg-end">
              <a href="auth/login" class="btn-primary">Get Started <i class="bi bi-arrow-right"></i></a>
              <a href="https://chat.whatsapp.com/DwgxaHl0Po6FT1tIK9uV85" target="_blank" class="btn-secondary">Join Community</a>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Services Section -->

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
              <h2>The Complete Growth Partner — Education &amp; Strategy</h2>
              <p class="lead">GrowthEngineAI stands out as the only platform that combines premium tech education with real-world consulting expertise. Whether you want to upskill, get certified, or transform your organization — one partner covers it all.</p>
              <div class="stats-grid">
                <div class="stat-item">
                  <div class="stat-number" data-purecounter-start="0" data-purecounter-end="10" data-purecounter-duration="2">10+</div>
                  <div class="stat-label">Premium Courses</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number" data-purecounter-start="0" data-purecounter-end="6" data-purecounter-duration="2">6+</div>
                  <div class="stat-label">Consulting Practices</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number" data-purecounter-start="0" data-purecounter-end="100" data-purecounter-duration="2">100%</div>
                  <div class="stat-label">Practical Focus</div>
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
                <i class="bi bi-mortarboard"></i>
              </div>
              <h4>World-Class Tech Education</h4>
              <p>Premium courses in Cybersecurity, DevOps, Cloud, and more — backed by our active Whatsapp community for real-time support and networking.</p>
              <a href="#services" class="learn-more">View Courses <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="200">
            <div class="value-card featured">
              <div class="featured-badge">Dual Expertise</div>
              <div class="icon-box">
                <i class="bi bi-briefcase"></i>
              </div>
              <h4>Strategic Tech Consulting</h4>
              <p>Our consultants have real-world experience transforming businesses. We don't just teach — we build and deploy alongside your team.</p>
              <a href="#consulting" class="learn-more">See Services <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="300">
            <div class="value-card">
              <div class="icon-box">
                <i class="bi bi-person-video3"></i>
              </div>
              <h4>Expert Practitioners</h4>
              <p>Our instructors and consultants are active industry practitioners — not just academics. Learn from people who solve real problems every day.</p>
              <a href="#about" class="learn-more">About Us <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div>

        <div class="row align-items-center">
          <div class="col-lg-6 order-lg-2" data-aos="fade-left" data-aos-delay="200">
            <div class="capabilities-content">
              <h3>What Makes Us Different</h3>
              <p>At GrowthEngineAI, we've built a unique dual-service experience. Here's what sets us apart:</p>

              <div class="capability-list">
                <div class="capability-item">
                  <div class="capability-header">
                    <i class="bi bi-check-circle-fill"></i>
                    <h5>Education Meets Real-World Consulting</h5>
                  </div>
                  <p>Our instructors double as consultants, so course content is always aligned with what organizations actually need right now.</p>
                </div>

                <div class="capability-item">
                  <div class="capability-header">
                    <i class="bi bi-check-circle-fill"></i>
                    <h5>Active Whatsapp Community</h5>
                  </div>
                  <p>A thriving community where learners network, collaborate, and get real-time support from peers and instructors.</p>
                </div>

                <div class="capability-item">
                  <div class="capability-header">
                    <i class="bi bi-check-circle-fill"></i>
                    <h5>Tailored Engagement Models</h5>
                  </div>
                  <p>Whether you need a self-paced course, a consulting engagement, or custom corporate training — we flex to fit your goals.</p>
                </div>
              </div>

              <div class="cta-buttons">
                <a href="#services" class="btn btn-primary">Explore Courses</a>
                <a href="#consulting" class="btn btn-secondary">Get Consulting</a>
              </div>
            </div>
          </div>

          <div class="col-lg-6 order-lg-1" data-aos="fade-right" data-aos-delay="300">
            <div class="process-visual">
              <div class="process-step" data-aos="fade-up" data-aos-delay="400">
                <div class="step-number">01</div>
                <div class="step-content">
                  <h6>Discover</h6>
                  <p>Identify your goals</p>
                </div>
              </div>
              <div class="process-step" data-aos="fade-up" data-aos-delay="450">
                <div class="step-number">02</div>
                <div class="step-content">
                  <h6>Learn or Consult</h6>
                  <p>Education or advisory</p>
                </div>
              </div>
              <div class="process-step" data-aos="fade-up" data-aos-delay="500">
                <div class="step-number">03</div>
                <div class="step-content">
                  <h6>Build</h6>
                  <p>Apply &amp; implement</p>
                </div>
              </div>
              <div class="process-step" data-aos="fade-up" data-aos-delay="550">
                <div class="step-number">04</div>
                <div class="step-content">
                  <h6>Grow</h6>
                  <p>Scale your success</p>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Why Us Section -->

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
                <span>Education &amp; Consulting</span>
              </div>
              <h2>Ready to Grow — Learn or Transform?</h2>
              <p>Whether you're an individual looking to launch a tech career or an organization seeking expert guidance, GrowthEngineAI has the expertise to take you further. Reach out and let's explore the possibilities.</p>
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
                  <p>Lokoja, Nigeria</p>
                  <span class="channel-meta">Serving clients worldwide</span>
                </div>
              </div>
            </div>

            <div class="trust-indicators">
              <div class="indicator-item">
                <div class="indicator-value">10+</div>
                <div class="indicator-label">Premium Courses</div>
              </div>
              <div class="indicator-item">
                <div class="indicator-value">6+</div>
                <div class="indicator-label">Consulting Practices</div>
              </div>
              <div class="indicator-item">
                <div class="indicator-value">24/7</div>
                <div class="indicator-label">Community Access</div>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="form-wrapper">
              <div class="form-header">
                <h3>Send Us a Message</h3>
                <p>Tell us about your needs — whether it's a course enquiry or a consulting engagement, we'll get back to you promptly.</p>
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
                  <label>I'm Interested In</label>
                  <select name="subject" class="form-control" required="">
                    <option value="" disabled selected>Select a service...</option>
                    <option value="Tech Education / Course Enquiry">Tech Education / Course Enquiry</option>
                    <option value="Cybersecurity Consulting">Cybersecurity Consulting</option>
                    <option value="Cloud Strategy & Migration">Cloud Strategy &amp; Migration</option>
                    <option value="AI & Automation Consulting">AI &amp; Automation Consulting</option>
                    <option value="DevOps Transformation">DevOps Transformation</option>
                    <option value="Digital Transformation">Digital Transformation</option>
                    <option value="Corporate Training">Corporate Training</option>
                    <option value="Other">Other</option>
                  </select>
                </div>

                <div class="form-group">
                  <label>Message</label>
                  <textarea name="message" class="form-control" rows="5" required=""></textarea>
                </div>
                <div class="loading">Loading</div>
                <div class="error-message"></div>
                <div class="sent-message">Your message has been sent. Thank you!</div>

                <button type="submit" class="submit-btn">
                  <span>Send Message</span>
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
          <p>Your Partner in Intelligent Transformation. GrowthEngineAI combines world-class tech education with hands-on consulting to help individuals launch careers and businesses achieve digital excellence.</p>
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
            <li><a href="#consulting">Consulting</a></li>
            <li><a href="#services">Courses</a></li>
            <li><a href="#">Privacy Policy</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-6 footer-links">
          <h4>Consulting</h4>
          <ul>
            <li><a href="#consulting">Cybersecurity</a></li>
            <li><a href="#consulting">Cloud Strategy</a></li>
            <li><a href="#consulting">AI &amp; Automation</a></li>
            <li><a href="#consulting">DevOps</a></li>
            <li><a href="#consulting">Digital Transformation</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-12 footer-contact text-center text-md-start">
          <h4>Contact Us</h4>
          <p>Lokoja</p>
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