<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Set page metadata
$page_title = 'Offbeat Stays & Unique Accommodations - Newa Yatra';
$meta_description = 'Discover unique offbeat stays in Darjeeling, Sikkim, and North Bengal. Tree houses, homestays, farm stays, and boutique accommodations with Newa Yatra.';
$meta_keywords = 'offbeat stays, unique accommodations, tree houses, homestays, farm stays, boutique hotels, Darjeeling, Sikkim, North Bengal';

ob_start();
?>

<?php include '../includes/header.php'; ?>

<!-- Breadcrumb -->
<section class="breadcrumb">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb-list">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Offbeat Stays</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="hero-title">Offbeat Stays & Unique Accommodations</h1>
                <p class="hero-subtitle">Experience the extraordinary with our curated collection of unique stays in the Eastern Himalayas</p>
                <div class="hero-badges">
                    <span class="badge">
                        <i class="fas fa-tree me-1"></i> Tree Houses
                    </span>
                    <span class="badge">
                        <i class="fas fa-home me-1"></i> Homestays
                    </span>
                    <span class="badge">
                        <i class="fas fa-tractor me-1"></i> Farm Stays
                    </span>
                    <span class="badge">
                        <i class="fas fa-umbrella-beach me-1"></i> Boutique
                    </span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image">
                    <img src="<?php echo SITE_URL; ?>uploads/tours/himalayaoffbeat.jpg" 
                         alt="Offbeat Stays in Himalayas"
                         class="img-fluid rounded shadow-lg"
                         onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="section-title mb-4">Beyond Ordinary Accommodations</h2>
                <p class="lead text-muted mb-5">
                    Escape the mainstream hotels and immerse yourself in authentic local experiences. 
                    Our handpicked offbeat stays offer more than just a place to sleep - they provide 
                    unique experiences, local interactions, and memories that last a lifetime.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="section bg-light">
    <div class="container">
        <h2 class="section-title text-center mb-5">Types of Offbeat Stays</h2>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-tree-house fa-3x"></i>
                    </div>
                    <h4 class="category-title">Tree Houses</h4>
                    <p class="category-text">Sleep among the treetops with panoramic mountain views</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-home-heart fa-3x"></i>
                    </div>
                    <h4 class="category-title">Homestays</h4>
                    <p class="category-text">Live with local families and experience authentic culture</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-tractor fa-3x"></i>
                    </div>
                    <h4 class="category-title">Farm Stays</h4>
                    <p class="category-text">Experience rural life and organic farming practices</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-campground fa-3x"></i>
                    </div>
                    <h4 class="category-title">Eco Camps</h4>
                    <p class="category-text">Sustainable stays in the heart of nature</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Stays -->
<section class="section">
    <div class="container">
        <h2 class="section-title text-center mb-5">Featured Offbeat Stays</h2>
        
        <div class="row g-4">
            <!-- Stay 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="stay-card">
                    <div class="stay-image">
                        <img src="<?php echo SITE_URL; ?>uploads/tours/skyveiw tree.jpg" 
                             alt="Skyview Tree House"
                             class="img-fluid"
                             onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                        <span class="stay-badge">Tree House</span>
                    </div>
                    <div class="stay-info">
                        <div class="stay-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span class="rating-text">4.5/5</span>
                        </div>
                        <h3 class="stay-title">Skyview Tree House</h3>
                        <p class="stay-location">
                            <i class="fas fa-map-marker-alt me-2"></i>Kalimpong, West Bengal
                        </p>
                        <p class="stay-desc">
                            Perched 30 feet above ground, this luxury tree house offers 360-degree views 
                            of the Himalayas and Kanchenjunga range.
                        </p>
                        <div class="stay-features">
                            <span><i class="fas fa-wifi"></i> WiFi</span>
                            <span><i class="fas fa-utensils"></i> Meals</span>
                            <span><i class="fas fa-fire"></i> Bonfire</span>
                        </div>
                        <div class="stay-price">
                            <span class="price">From ₹4,500/night</span>
                            <a href="<?php echo SITE_URL; ?>/pages/contact.php?stay=tree-house" class="btn btn-primary">
                                <i class="fas fa-calendar-check me-2"></i>Book Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stay 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="stay-card">
                    <div class="stay-image">
                        <img src="<?php echo SITE_URL; ?>uploads/tours/heritagebunglow.jpg" 
                             alt="Makaibari Tea Estate Bungalow"
                             class="img-fluid"
                             onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                        <span class="stay-badge">Heritage Bungalow</span>
                    </div>
                    <div class="stay-info">
                        <div class="stay-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <span class="rating-text">5/5</span>
                        </div>
                        <h3 class="stay-title">Makaibari Heritage Bungalow</h3>
                        <p class="stay-location">
                            <i class="fas fa-map-marker-alt me-2"></i>Kurseong, Darjeeling
                        </p>
                        <p class="stay-desc">
                            A 150-year-old colonial bungalow in the heart of organic tea gardens with 
                            guided tea tasting sessions.
                        </p>
                        <div class="stay-features">
                            <span><i class="fas fa-leaf"></i> Organic Garden</span>
                            <span><i class="fas fa-mug-hot"></i> Tea Tasting</span>
                            <span><i class="fas fa-hiking"></i> Nature Walks</span>
                        </div>
                        <div class="stay-price">
                            <span class="price">From ₹6,000/night</span>
                            <a href="<?php echo SITE_URL; ?>/pages/contact.php?stay=tea-bungalow" class="btn btn-primary">
                                <i class="fas fa-calendar-check me-2"></i>Book Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stay 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="stay-card">
                    <div class="stay-image">
                        <img src="<?php echo SITE_URL; ?>uploads/tours/rumtek monestry.jpg" 
                             alt="Rumtek Monastery Guest House"
                             class="img-fluid"
                             onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                        <span class="stay-badge">Monastery Stay</span>
                    </div>
                    <div class="stay-info">
                        <div class="stay-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <span class="rating-text">4.8/5</span>
                        </div>
                        <h3 class="stay-title">Rumtek Monastery Guest House</h3>
                        <p class="stay-location">
                            <i class="fas fa-map-marker-alt me-2"></i>Gangtok, Sikkim
                        </p>
                        <p class="stay-desc">
                            Experience spiritual living in a Buddhist monastery with meditation sessions 
                            and traditional vegetarian meals.
                        </p>
                        <div class="stay-features">
                            <span><i class="fas fa-spa"></i> Meditation</span>
                            <span><i class="fas fa-book"></i> Library</span>
                            <span><i class="fas fa-pray"></i> Prayer Sessions</span>
                        </div>
                        <div class="stay-price">
                            <span class="price">From ₹2,500/night</span>
                            <a href="<?php echo SITE_URL; ?>/pages/contact.php?stay=monastery" class="btn btn-primary">
                                <i class="fas fa-calendar-check me-2"></i>Book Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stay 4 -->
            <div class="col-lg-4 col-md-6">
                <div class="stay-card">
                    <div class="stay-image">
                        <img src="<?php echo SITE_URL; ?>uploads/tours/teestariversidecottage.jpg" 
                             alt="Teesta Riverside Cottage"
                             class="img-fluid"
                             onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                        <span class="stay-badge">Riverside Cottage</span>
                    </div>
                    <div class="stay-info">
                        <div class="stay-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span class="rating-text">4.3/5</span>
                        </div>
                        <h3 class="stay-title">Teesta Riverside Cottage</h3>
                        <p class="stay-location">
                            <i class="fas fa-map-marker-alt me-2"></i>Kalijhora, Dooars
                        </p>
                        <p class="stay-desc">
                            Secluded bamboo cottages on the banks of Teesta River with private sit-outs 
                            and river-facing views.
                        </p>
                        <div class="stay-features">
                            <span><i class="fas fa-water"></i> River View</span>
                            <span><i class="fas fa-fish"></i> Fishing</span>
                            <span><i class="fas fa-swimmer"></i> River Bath</span>
                        </div>
                        <div class="stay-price">
                            <span class="price">From ₹3,500/night</span>
                            <a href="<?php echo SITE_URL; ?>/pages/contact.php?stay=riverside" class="btn btn-primary">
                                <i class="fas fa-calendar-check me-2"></i>Book Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stay 5 -->
            <div class="col-lg-4 col-md-6">
                <div class="stay-card">
                    <div class="stay-image">
                        <img src="<?php echo SITE_URL; ?>uploads/tours/singalilaorganicfarmstay.jpg" 
                             alt="Organic Farm Stay"
                             class="img-fluid"
                             onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                        <span class="stay-badge">Farm Stay</span>
                    </div>
                    <div class="stay-info">
                        <div class="stay-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span class="rating-text">4.6/5</span>
                        </div>
                        <h3 class="stay-title">Singalila Organic Farm Stay</h3>
                        <p class="stay-location">
                            <i class="fas fa-map-marker-alt me-2"></i>Mirik, Darjeeling
                        </p>
                        <p class="stay-desc">
                            Experience sustainable farming, milk cows, harvest vegetables, and enjoy 
                            farm-to-table organic meals.
                        </p>
                        <div class="stay-features">
                            <span><i class="fas fa-seedling"></i> Farming</span>
                            <span><i class="fas fa-cow"></i> Animal Care</span>
                            <span><i class="fas fa-utensils"></i> Organic Food</span>
                        </div>
                        <div class="stay-price">
                            <span class="price">From ₹2,800/night</span>
                            <a href="<?php echo SITE_URL; ?>/pages/contact.php?stay=farm" class="btn btn-primary">
                                <i class="fas fa-calendar-check me-2"></i>Book Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stay 6 -->
            <div class="col-lg-4 col-md-6">
                <div class="stay-card">
                    <div class="stay-image">
                        <img src="<?php echo SITE_URL; ?>uploads/tours/ecocamp.jpg" 
                             alt="Dooars Eco Camp"
                             class="img-fluid"
                             onerror="this.src='<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'">
                        <span class="stay-badge">Eco Camp</span>
                    </div>
                    <div class="stay-info">
                        <div class="stay-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <span class="rating-text">4.9/5</span>
                        </div>
                        <h3 class="stay-title">Dooars Jungle Eco Camp</h3>
                        <p class="stay-location">
                            <i class="fas fa-map-marker-alt me-2"></i>Gorumara, Dooars
                        </p>
                        <p class="stay-desc">
                            Luxury tents in the buffer zone of Gorumara National Park with guided 
                            jungle safaris and bird watching.
                        </p>
                        <div class="stay-features">
                            <span><i class="fas fa-binoculars"></i> Safari</span>
                            <span><i class="fas fa-campground"></i> Luxury Tent</span>
                            <span><i class="fas fa-feather-alt"></i> Bird Watching</span>
                        </div>
                        <div class="stay-price">
                            <span class="price">From ₹5,200/night</span>
                            <a href="<?php echo SITE_URL; ?>/pages/contact.php?stay=eco-camp" class="btn btn-primary">
                                <i class="fas fa-calendar-check me-2"></i>Book Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Section -->
<section class="section bg-light">
    <div class="container">
        <h2 class="section-title text-center mb-5">Why Choose Offbeat Stays?</h2>
        
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <h4>Authentic Local Experience</h4>
                    <p>Connect with local communities, learn about their culture, and experience their way of life firsthand.</p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-leaf fa-2x"></i>
                    </div>
                    <h4>Sustainable Tourism</h4>
                    <p>Support eco-friendly accommodations that minimize environmental impact and benefit local communities.</p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mountain fa-2x"></i>
                    </div>
                    <h4>Unique Locations</h4>
                    <p>Stay in locations that regular hotels can't reach - treetops, riversides, farms, and remote villages.</p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-heart fa-2x"></i>
                    </div>
                    <h4>Personalized Service</h4>
                    <p>Enjoy personalized attention from hosts who care about your experience and comfort.</p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-utensils fa-2x"></i>
                    </div>
                    <h4>Local Cuisine</h4>
                    <p>Home-cooked meals using local ingredients and traditional recipes you won't find in restaurants.</p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-camera fa-2x"></i>
                    </div>
                    <h4>Photography Opportunities</h4>
                    <p>Capture stunning photos from unique perspectives that most tourists never get to see.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Booking Process -->
<section class="section">
    <div class="container">
        <h2 class="section-title text-center mb-5">How to Book Offbeat Stays</h2>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <h5>Browse Stays</h5>
                    <p>Explore our curated collection of unique accommodations</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <div class="step-number">2</div>
                    <h5>Check Availability</h5>
                    <p>Contact us to check dates and customize your stay</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <div class="step-number">3</div>
                    <h5>Confirm Booking</h5>
                    <p>Secure your stay with a 30% advance payment</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <div class="step-number">4</div>
                    <h5>Experience & Enjoy</h5>
                    <p>Arrive and immerse yourself in unique experiences</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> Most offbeat stays have limited capacity. We recommend booking at least 2-3 weeks in advance.
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="section bg-light">
    <div class="container">
        <h2 class="section-title text-center mb-5">What Our Guests Say</h2>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">
                        "The tree house stay was magical! Waking up to the view of Kanchenjunga was unforgettable. 
                        The hosts were incredibly warm and the food was delicious."
                    </p>
                    <div class="testimonial-author">
                        <h6>Priya Sharma</h6>
                        <small>Mumbai</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="testimonial-text">
                        "Our farm stay experience was amazing. The kids loved feeding the animals and we enjoyed 
                        the fresh organic vegetables. A perfect family getaway!"
                    </p>
                    <div class="testimonial-author">
                        <h6>Rohit Verma</h6>
                        <small>Delhi</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">
                        "Staying at the monastery was a spiritual experience. The meditation sessions and 
                        simple vegetarian meals were exactly what we needed to disconnect and rejuvenate."
                    </p>
                    <div class="testimonial-author">
                        <h6>Anita Das</h6>
                        <small>Kolkata</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section cta-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="cta-title">Ready for an Unforgettable Stay?</h2>
                <p class="cta-text mb-4">
                    Experience the magic of offbeat accommodations in the Himalayas. 
                    Let us help you find the perfect unique stay for your next adventure.
                </p>
                <div class="cta-buttons">
                    <a href="<?php echo SITE_URL; ?>/pages/contact.php" class="btn btn-primary btn-lg px-5 me-3">
                        <i class="fas fa-calendar-alt me-2"></i>Book Your Stay
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/contact.php" class="btn btn-outline-light btn-lg px-5">
                        <i class="fas fa-question-circle me-2"></i>Get Advice
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

<style>
/* Breadcrumb */
.breadcrumb {
    padding: 15px 0;
    background: var(--light-color);
}

.breadcrumb-list {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
}

.breadcrumb-item {
    display: flex;
    align-items: center;
}

.breadcrumb-item:not(:last-child):after {
    content: '/';
    margin: 0 10px;
    color: var(--gray-color);
}

.breadcrumb-item a {
    color: var(--primary-color);
    text-decoration: none;
}

.breadcrumb-item.active {
    color: var(--secondary-color);
}

/* Hero Section */
.hero-section {
    padding: 80px 0 50px;
    background: linear-gradient(135deg, rgba(142, 20, 2, 0.1) 0%, rgba(198, 107, 5, 0.05) 100%);
}

.hero-title {
    font-size: 3rem;
    font-weight: 700;
    color: var(--secondary-color);
    margin-bottom: 20px;
}

.hero-subtitle {
    font-size: 1.2rem;
    color: var(--dark-color);
    margin-bottom: 25px;
    line-height: 1.6;
}

.hero-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.hero-badges .badge {
    background: rgba(198, 107, 5, 0.1);
    color: var(--primary-color);
    padding: 8px 15px;
    border-radius: 50px;
    font-weight: 500;
    font-size: 0.9rem;
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

/* Category Cards */
.category-card {
    background: white;
    padding: 40px 20px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    text-align: center;
    transition: var(--transition);
    height: 100%;
}

.category-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-lg);
}

.category-icon {
    color: var(--primary-color);
    margin-bottom: 20px;
}

.category-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 15px;
}

.category-text {
    color: var(--gray-color);
    line-height: 1.6;
    margin: 0;
}

/* Stay Cards */
.stay-card {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    height: 100%;
}

.stay-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-lg);
}

.stay-image {
    position: relative;
    height: 250px;
    overflow: hidden;
}

.stay-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.stay-card:hover .stay-image img {
    transform: scale(1.1);
}

.stay-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: var(--primary-color);
    color: white;
    padding: 5px 15px;
    border-radius: 5px;
    font-weight: 600;
    font-size: 0.9rem;
}

.stay-info {
    padding: 25px;
}

.stay-rating {
    color: #FFD700;
    margin-bottom: 10px;
}

.stay-rating .fa-star,
.stay-rating .fa-star-half-alt {
    margin-right: 2px;
}

.rating-text {
    color: var(--gray-color);
    font-size: 0.9rem;
    margin-left: 8px;
}

.stay-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 8px;
}

.stay-location {
    color: var(--primary-color);
    font-weight: 500;
    margin-bottom: 15px;
    font-size: 0.95rem;
}

.stay-desc {
    color: var(--gray-color);
    line-height: 1.6;
    margin-bottom: 20px;
    min-height: 72px;
}

.stay-features {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--light-color);
}

.stay-features span {
    background: rgba(198, 107, 5, 0.1);
    color: var(--primary-color);
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 0.85rem;
}

.stay-features i {
    margin-right: 5px;
}

.stay-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stay-price .price {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--secondary-color);
}

.stay-price .btn {
    padding: 8px 20px;
    font-size: 0.9rem;
}

/* Feature Cards */
.feature-card {
    background: white;
    padding: 30px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    text-align: center;
    height: 100%;
    transition: var(--transition);
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow);
}

.feature-icon {
    width: 70px;
    height: 70px;
    background: rgb(198 107 5 / 86%);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin: 0 auto 20px;
}

.feature-card h4 {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 15px;
}

.feature-card p {
    color: var(--gray-color);
    line-height: 1.6;
    margin: 0;
}

/* Process Steps */
.process-step {
    text-align: center;
    position: relative;
}

.step-number {
    width: 60px;
    height: 60px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 auto 20px;
}

.process-step h5 {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 10px;
}

.process-step p {
    color: var(--gray-color);
    line-height: 1.6;
    margin: 0;
}

/* Testimonial Cards */
.testimonial-card {
    background: white;
    padding: 30px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    height: 100%;
}

.testimonial-rating {
    color: #FFD700;
    margin-bottom: 20px;
}

.testimonial-rating .fa-star,
.testimonial-rating .fa-star-half-alt {
    margin-right: 2px;
}

.testimonial-text {
    color: var(--gray-color);
    line-height: 1.8;
    font-style: italic;
    margin-bottom: 25px;
    position: relative;
}

.testimonial-text:before {
    content: '"';
    font-size: 3rem;
    color: rgba(198, 107, 5, 0.2);
    position: absolute;
    top: -15px;
    left: -10px;
}

.testimonial-author h6 {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 5px;
}

.testimonial-author small {
    color: var(--gray-color);
}

/* CTA Section */
.cta-section {
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

/* Font Awesome Tree House Icon */
.fa-tree-house:before {
    content: "\f6ec";
    font-family: "Font Awesome 5 Free";
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2.2rem;
    }
    
    .hero-section {
        padding: 50px 0 30px;
    }
    
    .stay-price {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .stay-price .btn {
        width: 100%;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize stay card hover effects
    const stayCards = document.querySelectorAll('.stay-card');
    stayCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Initialize category card hover effects
    const categoryCards = document.querySelectorAll('.category-card');
    categoryCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
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
    
    // Add animation to process steps on scroll
    const observerOptions = {
        threshold: 0.3,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe process steps
    document.querySelectorAll('.process-step').forEach(step => {
        step.style.opacity = '0';
        step.style.transform = 'translateY(20px)';
        step.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(step);
    });
});
</script>

<?php
$content = ob_get_clean();
echo $content;
?>