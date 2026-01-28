<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EnduraCore - Gym Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.html">
                <i class="fas fa-dumbbell"></i> EnduraCore
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="login-btn btn btn-outline-light ms-2 px-3 transition" href="auth/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6">
                    <h1 class="display-3 fw-bold mb-4">Transform Your Fitness Journey</h1>
                    <p class="lead mb-4">Professional gym management system to track your progress, manage memberships, and achieve your fitness goals.</p>
                    <div class="d-flex gap-3">
                        <a href="auth/register.php" class="btn btn-outline-info btn-lg">Get Started</a>
                        <a href="#features" class="btn btn-outline-light btn-lg">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5 fw-bold">Our Features</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-users fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">Member Management</h5>
                            <p class="card-text">Easily manage member profiles, track attendance, and monitor progress.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-calendar-check fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">Workout Plans</h5>
                            <p class="card-text">Create personalized workout plans tailored to individual goals.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-credit-card fa-3x text-info"></i>
                            </div>
                            <h5 class="card-title">Payment Tracking</h5>
                            <p class="card-text">Manage memberships, payments, and billing with ease.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-user-tie fa-3x text-warning"></i>
                            </div>
                            <h5 class="card-title">Trainer Management</h5>
                            <p class="card-text">Assign trainers, track specializations, and manage schedules.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-chart-line fa-3x text-danger"></i>
                            </div>
                            <h5 class="card-title">Progress Tracking</h5>
                            <p class="card-text">Monitor member progress and achieve fitness milestones.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-tools fa-3x text-secondary"></i>
                            </div>
                            <h5 class="card-title">Equipment Management</h5>
                            <p class="card-text">Track equipment status, maintenance, and availability.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5 fw-bold">Membership Plans</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow">
                        <div class="card-body text-center p-4">
                            <h5 class="card-title fw-bold">Monthly</h5>
                            <h2 class="display-4 my-4">$49</h2>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check text-success"></i> Access to all equipment</li>
                                <li class="mb-2"><i class="fas fa-check text-success"></i> Group classes</li>
                                <li class="mb-2"><i class="fas fa-check text-success"></i> Locker facilities</li>
                            </ul>
                            <a href="auth/register.php" class="btn btn-outline-primary w-100">Choose Plan</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow">
                        <div class="card-body text-center p-4">
                            <h5 class="card-title fw-bold">Quarterly</h5>
                            <h2 class="display-4 my-4">$129</h2>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check text-success"></i> Everything in Monthly</li>
                                <li class="mb-2"><i class="fas fa-check text-success"></i> Personal trainer session</li>
                                <li class="mb-2"><i class="fas fa-check text-success"></i> Diet consultation</li>
                            </ul>
                            <a href="auth/register.php" class="btn btn-outline-primary w-100">Choose Plan</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow border-primary">
                        <div class="card-body text-center p-4">
                            <span class="badge bg-primary mb-2">Popular</span>
                            <h5 class="card-title fw-bold">Half-Yearly</h5>
                            <h2 class="display-4 my-4">$239</h2>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check text-success"></i> Everything in Quarterly</li>
                                <li class="mb-2"><i class="fas fa-check text-success"></i> 4 PT sessions/month</li>
                                <li class="mb-2"><i class="fas fa-check text-success"></i> Nutrition plan</li>
                            </ul>
                            <a href="auth/register.php" class="btn btn-primary w-100">Choose Plan</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow">
                        <div class="card-body text-center p-4">
                            <h5 class="card-title fw-bold">Yearly</h5>
                            <h2 class="display-4 my-4">$449</h2>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check text-success"></i> Everything in Half-Yearly</li>
                                <li class="mb-2"><i class="fas fa-check text-success"></i> Unlimited PT sessions</li>
                                <li class="mb-2"><i class="fas fa-check text-success"></i> Premium support</li>
                            </ul>
                            <a href="auth/register.php" class="btn btn-outline-primary w-100">Choose Plan</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h2 class="mb-4">Get In Touch</h2>
                    <p class="mb-4">Have questions? We'd love to hear from you.</p>
                    <div class="mb-3">
                        <i class="fas fa-map-marker-alt me-2"></i> 123 Fitness Street, Kathmandu, Nepal
                    </div>
                    <div class="mb-3">
                        <i class="fas fa-phone me-2"></i> +977 1234567890
                    </div>
                    <div class="mb-3">
                        <i class="fas fa-envelope me-2"></i> info@enduracore.com
                    </div>
                </div>
                <div class="col-md-6">
                    <form>
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Your Name" required>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Your Email" required>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" rows="4" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 bg-dark text-white text-center">
        <div class="container">
            <p class="mb-0">&copy; 2026 EnduraCore. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>