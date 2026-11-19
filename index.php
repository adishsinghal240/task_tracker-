<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Task Tracker - Welcome</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Header & Navigation Bar -->
    <header>
        <nav>
            <div class="logo">
                <h2>📋 TaskTracker</h2>
            </div>
            <ul>
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
                <li><button id="themeToggleBtn" class="btn-theme-toggle">🌙 Dark Mode</button></li>
            </ul>
        </nav>
    </header>

    <!-- Main Hero / Landing Content -->
    <main>
        <section class="hero-section">
            <h1>Master Your Internship Milestones & Deliverables</h1>
            <p>An enterprise-grade, lightweight web platform to plan your daily workload, attach deliverables, track deadlines, and collaborate with mentors.</p>
            
            <div class="cta-buttons">
                <a href="register.php" class="btn btn-primary">Get Started (Register)</a>
                <a href="login.php" class="btn btn-secondary">Already Registered? Login</a>
            </div>
        </section>

        <!-- Feature Highlights Section -->
        <section class="features-section">
            <h2>Core Platform Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <h3>🎯 Domain Categories</h3>
                    <p>Tag tasks by domain: Development, Design, Testing, Research, or Documentation with color-coded badges.</p>
                </div>
                <div class="feature-card">
                    <h3>📎 File Attachments & Git Links</h3>
                    <p>Directly attach project files (PDF, images, ZIP) or submit GitHub and Google Drive deliverable URLs.</p>
                </div>
                <div class="feature-card">
                    <h3>🔔 Smart Due Reminders</h3>
                    <p>Automated visual badges highlighting Overdue milestones, Due Today items, and tasks due in the next 48 hours.</p>
                </div>
                <div class="feature-card">
                    <h3>👔 Manager & Admin Control</h3>
                    <p>Mentors can track cohort completion rates, review submitted deliverables, and assign tasks directly to interns.</p>
                </div>
                <div class="feature-card">
                    <h3>📊 Interactive Dashboard</h3>
                    <p>Real-time completion percentage metrics, date-range filters, and instant CSV data export.</p>
                </div>
                <div class="feature-card">
                    <h3>🌙 Dark & Light Themes</h3>
                    <p>Full theme support with smooth persistence in browser storage for late-night coding sessions.</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Internship Task Tracker | Built with PHP, MySQL, HTML5, CSS3 & JavaScript</p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>
