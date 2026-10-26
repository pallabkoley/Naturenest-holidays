<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Set page metadata
$page_title = 'Our Services - Newa Yatra Travel & Tours';
$meta_description = 'Discover our comprehensive travel services including tour packages, hotel bookings, transportation, and custom itineraries for Darjeeling, Sikkim, and Northeast India.';
$meta_keywords = 'travel services, tour packages, hotel booking, transportation, travel guide, custom tours, Northeast India tourism';

ob_start();
?>

<?php include '../includes/header.php'; ?>

<!-- Hero Section -->
<section class="services-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="hero-title">Our Travel Services</h1>
                <p class="hero-subtitle">Comprehensive travel solutions for Darjeeling, Sikkim, Kalimpong, and Northeast India</p>
                <p class="hero-text">At Newa Yatra, we offer a wide range of travel services to make your journey memorable, comfortable, and hassle-free. From customized tour packages to transportation and accommodation, we handle every detail.</p>
                <a href="#services" class="btn btn-primary btn-lg mt-3">
                    <i class="fas fa-chevron-down me-2"></i>Explore Our Services
                </a>
            </div>
            <div class="col-lg-6">
                <div class="hero-image">
                    <img src="<?php echo SITE_URL; ?>uploads/slider/service1.jpg" 
                         alt="Travel Services" 
                         class="img-fluid rounded shadow-lg"
                         onerror="this.src='<?php echo SITE_URL; ?>uploads/slider/service2.jpg'">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Overview -->
<section class="section" id="services">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">What We Offer</h2>
            <p class="section-subtitle">Complete travel solutions tailored to your needs</p>
        </div>

        <div class="row g-4">
            <!-- Service 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-map-marked-alt fa-1x"></i>
                    </div>
                    <h3 class="service-title">Tour Packages</h3>
                    <p class="service-description">
                        Customized and pre-designed tour packages for Darjeeling, Sikkim, Kalimpong, Kurseong, Mirik, Dooars, and Sandakphu. Choose from honeymoon packages, family tours, adventure trips, or create your own itinerary.
                    </p>
                    <ul class="service-features">
                        <li><i class="fas fa-check-circle text-success me-2"></i>Pre-designed packages</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Custom itineraries</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Group & solo tours</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Honeymoon specials</li>
                    </ul>
                    <a href="<?php echo SITE_URL; ?>uploads/slider/service2.jpg" class="btn btn-outline-primary mt-3">
                        <i class="fas fa-eye me-2"></i>View Tours
                    </a>
                </div>
            </div>

            <!-- Service 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-hotel fa-1x"></i>
                    </div>
                    <h3 class="service-title">Hotel Booking</h3>
                    <p class="service-description">
                        Book hotels, resorts, homestays, and guest houses across all destinations. We offer accommodation options ranging from budget to luxury to suit your preferences and budget.
                    </p>
                    <ul class="service-features">
                        <li><i class="fas fa-check-circle text-success me-2"></i>Luxury resorts</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Budget hotels</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Homestays</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Best price guarantee</li>
                    </ul>
                    <a href="<?php echo SITE_URL; ?>/pages/hotels.php" class="btn btn-outline-primary mt-3">
                        <i class="fas fa-bed me-2"></i>Find Hotels
                    </a>
                </div>
            </div>

            <!-- Service 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-car fa-1x"></i>
                    </div>
                    <h3 class="service-title">Transportation</h3>
                    <p class="service-description">
                        Complete transportation services including airport transfers, local sightseeing, and inter-city travel. We offer comfortable vehicles with experienced drivers familiar with hill terrain.
                    </p>
                    <ul class="service-features">
                        <li><i class="fas fa-check-circle text-success me-2"></i>Airport transfers</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Local sightseeing</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Outstation travel</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Experienced drivers</li>
                    </ul>
                    <a href="<?php echo SITE_URL; ?>/pages/contact.php?service=transport" class="btn btn-outline-primary mt-3">
                        <i class="fas fa-car me-2"></i>Book Vehicle
                    </a>
                </div>
            </div>

            <!-- Service 4 -->
            <div class="col-lg-4 col-md-6">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-passport fa-1x"></i>
                    </div>
                    <h3 class="service-title">Permits & Documentation</h3>
                    <p class="service-description">
                        Assistance with travel permits for restricted areas in Sikkim and Northeast India. We handle all documentation requirements for Indian and foreign nationals.
                    </p>
                    <ul class="service-features">
                        <li><i class="fas fa-check-circle text-success me-2"></i>Inner Line Permits</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Protected Area Permits</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Foreign national permits</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Documentation support</li>
                    </ul>
                    <a href="<?php echo SITE_URL; ?>/pages/contact.php?service=permits" class="btn btn-outline-primary mt-3">
                        <i class="fas fa-file-alt me-2"></i>Get Permit Help
                    </a>
                </div>
            </div>

            <!-- Service 5 -->
            <div class="col-lg-4 col-md-6">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-user-tie fa-1x"></i>
                    </div>
                    <h3 class="service-title">Tour Guides</h3>
                    <p class="service-description">
                        Professional, licensed tour guides fluent in multiple languages. Our guides have in-depth knowledge of local history, culture, and attractions.
                    </p>
                    <ul class="service-features">
                        <li><i class="fas fa-check-circle text-success me-2"></i>Licensed guides</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Multiple languages</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Cultural experts</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Photography guides</li>
                    </ul>
                    <a href="<?php echo SITE_URL; ?>/pages/contact.php?service=guide" class="btn btn-outline-primary mt-3">
                        <i class="fas fa-user-friends me-2"></i>Hire Guide
                    </a>
                </div>
            </div>

            <!-- Service 6 -->
            <div class="col-lg-4 col-md-6">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-gift fa-1x"></i>
                    </div>
                    <h3 class="service-title">Special Services</h3>
                    <p class="service-description">
                        Special arrangements for honeymoon couples, anniversary celebrations, photography tours, adventure activities, and corporate retreats.
                    </p>
                    <ul class="service-features">
                        <li><i class="fas fa-check-circle text-success me-2"></i>Honeymoon packages</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Adventure activities</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Photography tours</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Corporate retreats</li>
                    </ul>
                    <a href="<?php echo SITE_URL; ?>/pages/contact.php?service=special" class="btn btn-outline-primary mt-3">
                        <i class="fas fa-star me-2"></i>Special Request
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="section bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="why-choose-image">
                    <img src="<?php echo SITE_URL; ?>uploads/tours/service3.jpg" 
                         alt="Why Choose Newa Yatra" 
                         class="img-fluid rounded shadow-lg"
                         onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="why-choose-content">
                    <h2 class="section-title mb-4">Why Choose Newa Yatra?</h2>
                    
                    <div class="why-item">
                        <div class="why-icon">
                            <i class="fas fa-award fa-2x"></i>
                        </div>
                        <div>
                            <h5>Local Expertise</h5>
                            <p>We are based in Siliguri with extensive local knowledge of Darjeeling, Sikkim, and Northeast India.</p>
                        </div>
                    </div>
                    
                    <div class="why-item">
                        <div class="why-icon">
                            <i class="fas fa-shield-alt fa-2x"></i>
                        </div>
                        <div>
                            <h5>Reliable & Safe</h5>
                            <p>Your safety is our priority. All our vehicles and services meet safety standards.</p>
                        </div>
                    </div>
                    
                    <div class="why-item">
                        <div class="why-icon">
                            <i class="fas fa-rupee-sign fa-2x"></i>
                        </div>
                        <div>
                            <h5>Best Price Guarantee</h5>
                            <p>We offer competitive pricing without compromising on quality and service.</p>
                        </div>
                    </div>
                    
                    <div class="why-item">
                        <div class="why-icon">
                            <i class="fas fa-headset fa-2x"></i>
                        </div>
                        <div>
                            <h5>24/7 Support</h5>
                            <p>Round-the-clock customer support during your entire journey.</p>
                        </div>
                    </div>
                    
                    <div class="why-item">
                        <div class="why-icon">
                            <i class="fas fa-heart fa-2x"></i>
                        </div>
                        <div>
                            <h5>Personalized Service</h5>
                            <p>We customize every tour according to your preferences and requirements.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="section">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">How Our Service Works</h2>
            <p class="section-subtitle">Simple steps to plan your perfect trip</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="step-card">
                    <div class="step-number">01</div>
                    <div class="step-icon">
                        <i class="fas fa-search fa-2x"></i>
                    </div>
                    <h5 class="step-title">Inquiry</h5>
                    <p class="step-text">Contact us with your travel requirements, dates, and preferences.</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="step-card">
                    <div class="step-number">02</div>
                    <div class="step-icon">
                        <i class="fas fa-clipboard-list fa-2x"></i>
                    </div>
                    <h5 class="step-title">Planning</h5>
                    <p class="step-text">We create a customized itinerary and provide a detailed quotation.</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="step-card">
                    <div class="step-number">03</div>
                    <div class="step-icon">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <h5 class="step-title">Confirmation</h5>
                    <p class="step-text">Review and approve the plan. Make payment to confirm your booking.</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="step-card">
                    <div class="step-number">04</div>
                    <div class="step-icon">
                        <i class="fas fa-plane-departure fa-2x"></i>
                    </div>
                    <h5 class="step-title">Travel</h5>
                    <p class="step-text">Enjoy your trip with our comprehensive support throughout your journey.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Service Areas -->
<section class="section bg-light">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">Our Service Areas</h2>
            <p class="section-subtitle">We operate across Northeast India and beyond</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="area-card">
                    <div class="area-icon">
                        <i class="fas fa-mountain fa-2x"></i>
                    </div>
                    <h6 class="area-title">Darjeeling</h6>
                    <p class="area-text">The Queen of Hills</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="area-card">
                    <div class="area-icon">
                        <i class="fas fa-pagoda fa-2x"></i>
                    </div>
                    <h6 class="area-title">Sikkim</h6>
                    <p class="area-text">Himalayan Paradise</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="area-card">
                    <div class="area-icon">
                        <i class="fas fa-flower fa-2x"></i>
                    </div>
                    <h6 class="area-title">Kalimpong</h6>
                    <p class="area-text">Floral Hill Town</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="area-card">
                    <div class="area-icon">
                        <i class="fas fa-train fa-2x"></i>
                    </div>
                    <h6 class="area-title">Kurseong</h6>
                    <p class="area-text">Land of Orchids</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="area-card">
                    <div class="area-icon">
                        <i class="fas fa-water fa-2x"></i>
                    </div>
                    <h6 class="area-title">Mirik</h6>
                    <p class="area-text">Lake Town</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="area-card">
                    <div class="area-icon">
                        <i class="fas fa-tree fa-2x"></i>
                    </div>
                    <h6 class="area-title">Dooars</h6>
                    <p class="area-text">Wildlife Region</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="area-card">
                    <div class="area-icon">
                        <i class="fas fa-hiking fa-2x"></i>
                    </div>
                    <h6 class="area-title">Sandakphu</h6>
                    <p class="area-text">Trekking Destination</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="area-card">
                    <div class="area-icon">
                        <i class="fas fa-globe-asia fa-2x"></i>
                    </div>
                    <h6 class="area-title">Bhutan</h6>
                    <p class="area-text">Dragon Kingdom</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="section">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">What Our Clients Say</h2>
            <p class="section-subtitle">Happy travelers share their experiences</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        <i class="fas fa-quote-left text-primary me-2"></i>
                        Excellent service! Newa Yatra planned our Darjeeling-Sikkim trip perfectly. Accommodation, transportation, and guides were all top-notch.
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="<?php echo SITE_URL; ?>/assets/images/testimonial-1.jpg" 
                                 alt="Rohit Sharma"
                                 onerror="this.src='<?php echo SITE_URL; ?>/assets/images/avatar.jpg'">
                        </div>
                        <div class="author-info">
                            <h6 class="author-name">Rohit Sharma</h6>
                            <p class="author-location">Mumbai</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        <i class="fas fa-quote-left text-primary me-2"></i>
                        Our honeymoon trip to Kalimpong was magical. Special thanks to the team for the romantic arrangements and surprise gifts!
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="<?php echo SITE_URL; ?>/assets/images/testimonial-2.jpg" 
                                 alt="Priya & Raj"
                                 onerror="this.src='<?php echo SITE_URL; ?>/assets/images/avatar.jpg'">
                        </div>
                        <div class="author-info">
                            <h6 class="author-name">Priya & Raj</h6>
                            <p class="author-location">Delhi</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        <i class="fas fa-quote-left text-primary me-2"></i>
                        As a solo female traveler, I felt completely safe and well taken care of. The guide was knowledgeable and the itinerary was perfect.
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="<?php echo SITE_URL; ?>/assets/images/testimonial-3.jpg" 
                                 alt="Anjali Mehta"
                                 onerror="this.src='<?php echo SITE_URL; ?>/assets/images/avatar.jpg'">
                        </div>
                        <div class="author-info">
                            <h6 class="author-name">Anjali Mehta</h6>
                            <p class="author-location">Bangalore</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section cta-services">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="cta-title mb-4">Ready to Plan Your Dream Trip?</h2>
                <p class="cta-text mb-4">Contact us today for a personalized travel plan and quotation. Let's create memories together!</p>
                <div class="cta-buttons">
                    <a href="<?php echo SITE_URL; ?>/pages/contact.php" class="btn btn-primary btn-lg px-5 me-3">
                        <i class="fas fa-phone-alt me-2"></i>Contact Us
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/tours.php" class="btn btn-outline-light btn-lg px-5">
                        <i class="fas fa-map-marked-alt me-2"></i>Browse Tours
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

<style>
/* Hero Section */
.services-hero {
    padding: 100px 0 80px;
    background: linear-gradient(135deg, rgba(142, 20, 2, 0.05) 0%, rgba(198, 107, 5, 0.02) 100%);
}

.hero-title {
    font-size: 3rem;
    font-weight: 700;
    color: var(--secondary-color);
    margin-bottom: 15px;
}

.hero-subtitle {
    font-size: 1.3rem;
    color: var(--primary-color);
    margin-bottom: 20px;
    font-weight: 500;
}

.hero-text {
    font-size: 1.1rem;
    color: var(--gray-color);
    line-height: 1.7;
    margin-bottom: 20px;
}

.hero-image {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
}

.hero-image img {
    width: 100%;
    height: 400px;
    object-fit: cover;
}

/* Service Cards */
.service-card {
    background: white;
    padding: 30px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    height: 100%;
    transition: var(--transition);
    border-top: 5px solid var(--primary-color);
    position: relative;
    overflow: hidden;
}

.service-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-lg);
    border-top-color: var(--secondary-color);
}

.service-icon {
    width: 80px;
    height: 80px;
    background: rgba(198, 107, 5, 0.1);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin: 0 auto 25px;
    transition: var(--transition);
}

.service-card:hover .service-icon {
    background: var(--primary-color);
    color: white;
    transform: scale(1.1);
}

.service-title {
    font-size: 1.4rem;
    font-weight: 600;
    color: var(--dark-color);
    text-align: center;
    margin-bottom: 15px;
}

.service-description {
    color: var(--gray-color);
    line-height: 1.7;
    margin-bottom: 20px;
    text-align: center;
}

.service-features {
    list-style: none;
    padding: 0;
    margin: 0 0 20px 0;
    text-align: left;
}

.service-features li {
    padding: 5px 0;
    color: var(--gray-color);
    position: relative;
    padding-left: 25px;
    margin-bottom: 5px;
}

.service-features li:before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
}

.btn-outline-primary {
    width: 100%;
    padding: 10px 20px;
    font-weight: 500;
}

/* Why Choose Us */
.why-choose-image {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow);
}

.why-choose-image img {
    width: 100%;
    height: 500px;
    object-fit: cover;
}

.why-choose-content {
    padding-left: 30px;
}

.why-item {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
    align-items: flex-start;
}

.why-icon {
    flex-shrink: 0;
    width: 60px;
    height: 60px;
    background: rgba(198, 107, 5, 0.1);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.why-item h5 {
    color: var(--dark-color);
    margin-bottom: 8px;
    font-size: 1.1rem;
}

.why-item p {
    color: var(--gray-color);
    margin: 0;
    line-height: 1.6;
}

/* How It Works */
.step-card {
    background: white;
    padding: 30px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    text-align: center;
    height: 100%;
    position: relative;
    transition: var(--transition);
}

.step-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow);
}

.step-number {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 4rem;
    font-weight: 800;
    color: rgba(198, 107, 5, 0.1);
    line-height: 1;
}

.step-icon {
    width: 70px;
    height: 70px;
    background: rgba(142, 20, 2, 0.1);
    color: var(--secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin: 0 auto 20px;
}

.step-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 15px;
}

.step-text {
    color: var(--gray-color);
    line-height: 1.6;
    margin: 0;
}

/* Service Areas */
.area-card {
    background: white;
    padding: 25px 20px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    text-align: center;
    transition: var(--transition);
}

.area-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow);
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
}

.area-card:hover .area-icon {
    background: white;
    color: var(--primary-color);
}

.area-card:hover .area-title,
.area-card:hover .area-text {
    color: white;
}

.area-icon {
    width: 60px;
    height: 60px;
    background: rgba(198, 107, 5, 0.1);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin: 0 auto 15px;
    transition: var(--transition);
}

.area-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 5px;
    transition: var(--transition);
}

.area-text {
    color: var(--gray-color);
    font-size: 0.9rem;
    margin: 0;
    transition: var(--transition);
}

/* Testimonials */
.testimonial-card {
    background: white;
    padding: 30px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    height: 100%;
}

.testimonial-text {
    font-style: italic;
    color: var(--gray-color);
    line-height: 1.7;
    margin-bottom: 25px;
    padding-bottom: 25px;
    border-bottom: 1px solid var(--light-color);
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 15px;
}

.author-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
}

.author-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.author-name {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 5px;
}

.author-location {
    color: var(--gray-color);
    font-size: 0.9rem;
    margin: 0;
}

/* CTA Section */
.cta-services {
    padding: 80px 0;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    text-align: center;
}

.cta-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 20px;
}

.cta-text {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto 30px;
}

.cta-buttons .btn {
    font-weight: 600;
    padding: 12px 30px;
}

.btn-outline-light:hover {
    background: white;
    color: var(--primary-color);
}

/* Section Header */
.section-header {
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

.section-title {
    font-size: 2.2rem;
    font-weight: 700;
    color: var(--secondary-color);
    margin-bottom: 15px;
}

.section-subtitle {
    font-size: 1.1rem;
    color: var(--gray-color);
    margin-bottom: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .services-hero {
        padding: 70px 0 50px;
    }
    
    .hero-title {
        font-size: 2.2rem;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
    }
    
    .hero-text {
        font-size: 1rem;
    }
    
    .hero-image img {
        height: 300px;
    }
    
    .section-title {
        font-size: 1.8rem;
    }
    
    .why-choose-content {
        padding-left: 0;
        margin-top: 30px;
    }
    
    .why-item {
        gap: 15px;
    }
    
    .why-icon {
        width: 50px;
        height: 50px;
    }
    
    .why-icon i {
        font-size: 1.5rem;
    }
    
    .cta-buttons {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .cta-buttons .btn {
        width: 100%;
        margin: 0;
    }
}

@media (max-width: 576px) {
    .service-card,
    .step-card,
    .testimonial-card {
        padding: 20px;
    }
    
    .service-icon {
        width: 70px;
        height: 70px;
    }
    
    .service-icon i {
        font-size: 2.5rem;
    }
    
    .step-number {
        font-size: 3rem;
    }
    
    .step-icon {
        width: 60px;
        height: 60px;
    }
    
    .step-icon i {
        font-size: 1.8rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add animation to service cards on scroll
    const serviceCards = document.querySelectorAll('.service-card');
    const stepCards = document.querySelectorAll('.step-card');
    const areaCards = document.querySelectorAll('.area-card');
    const testimonialCards = document.querySelectorAll('.testimonial-card');
    
    // Function to check if element is in viewport
    function isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top <= (window.innerHeight || document.documentElement.clientHeight) * 0.8 &&
            rect.bottom >= 0
        );
    }
    
    // Function to add animation class
    function addAnimationClass(elements, className) {
        elements.forEach((element, index) => {
            if (isInViewport(element)) {
                setTimeout(() => {
                    element.classList.add(className);
                }, index * 100);
            }
        });
    }
    
    // Initial check
    addAnimationClass(serviceCards, 'animated');
    addAnimationClass(stepCards, 'animated');
    addAnimationClass(areaCards, 'animated');
    addAnimationClass(testimonialCards, 'animated');
    
    // Check on scroll
    window.addEventListener('scroll', function() {
        addAnimationClass(serviceCards, 'animated');
        addAnimationClass(stepCards, 'animated');
        addAnimationClass(areaCards, 'animated');
        addAnimationClass(testimonialCards, 'animated');
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // Add CSS for animations
    const animationStyles = document.createElement('style');
    animationStyles.textContent = `
        .service-card, .step-card, .area-card, .testimonial-card {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .service-card.animated, .step-card.animated, .area-card.animated, .testimonial-card.animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        .service-card:nth-child(1) { transition-delay: 0.1s; }
        .service-card:nth-child(2) { transition-delay: 0.2s; }
        .service-card:nth-child(3) { transition-delay: 0.3s; }
        .service-card:nth-child(4) { transition-delay: 0.4s; }
        .service-card:nth-child(5) { transition-delay: 0.5s; }
        .service-card:nth-child(6) { transition-delay: 0.6s; }
        
        .step-card:nth-child(1) { transition-delay: 0.1s; }
        .step-card:nth-child(2) { transition-delay: 0.2s; }
        .step-card:nth-child(3) { transition-delay: 0.3s; }
        .step-card:nth-child(4) { transition-delay: 0.4s; }
        
        .area-card:nth-child(1) { transition-delay: 0.1s; }
        .area-card:nth-child(2) { transition-delay: 0.2s; }
        .area-card:nth-child(3) { transition-delay: 0.3s; }
        .area-card:nth-child(4) { transition-delay: 0.4s; }
        .area-card:nth-child(5) { transition-delay: 0.5s; }
        .area-card:nth-child(6) { transition-delay: 0.6s; }
        .area-card:nth-child(7) { transition-delay: 0.7s; }
        .area-card:nth-child(8) { transition-delay: 0.8s; }
        
        .testimonial-card:nth-child(1) { transition-delay: 0.1s; }
        .testimonial-card:nth-child(2) { transition-delay: 0.2s; }
        .testimonial-card:nth-child(3) { transition-delay: 0.3s; }
    `;
    document.head.appendChild(animationStyles);
    
    // Why choose us items hover effect
    const whyItems = document.querySelectorAll('.why-item');
    whyItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(10px)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
    
    // Service card hover effects
    serviceCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            const icon = this.querySelector('.service-icon');
            icon.style.transform = 'rotateY(180deg)';
        });
        
        card.addEventListener('mouseleave', function() {
            const icon = this.querySelector('.service-icon');
            icon.style.transform = 'rotateY(0)';
        });
    });
});
</script>

<?php
$content = ob_get_clean();
echo $content;
?>