// tour-packages.js - Combined functionality for tour packages and gallery slider

document.addEventListener('DOMContentLoaded', function() {
    console.log('Tour Packages Page Loaded');
    
    // Initialize AOS animations
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    }
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize Gallery Slider if it exists on page
    initGallerySlider();
    
    // Initialize FAQ Toggle if it exists on page
    initFAQ();
    
    // Filter functionality
    initFilters();
    
    // Quick View Modal
    initQuickView();
    
    // Back to top button
    initBackToTop();
    
    // Newsletter form
    initNewsletter();
    
    // Pagination smooth scroll
    initPagination();
    
    // Image error handling
    initImageErrorHandling();
});

// Initialize Gallery Slider
function initGallerySlider() {
    const galleryElement = document.querySelector('.swiper-gallery');
    const galleryThumbsElement = document.querySelector('.gallery-thumbs');
    
    if (!galleryElement || !galleryThumbsElement) return;
    
    try {
        // Initialize thumbnail slider
        const galleryThumbs = new Swiper('.gallery-thumbs', {
            spaceBetween: 10,
            slidesPerView: 5,
            freeMode: true,
            watchSlidesProgress: true,
            breakpoints: {
                320: {
                    slidesPerView: 3,
                    spaceBetween: 5
                },
                768: {
                    slidesPerView: 4,
                    spaceBetween: 8
                },
                1024: {
                    slidesPerView: 5,
                    spaceBetween: 10
                }
            }
        });

        // Initialize main gallery slider
        const galleryTop = new Swiper('.swiper-gallery', {
            spaceBetween: 10,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            thumbs: {
                swiper: galleryThumbs
            },
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            effect: 'slide',
            speed: 800,
        });
        
        console.log('Gallery slider initialized successfully');
    } catch (error) {
        console.error('Error initializing gallery slider:', error);
    }
}

// Initialize FAQ Toggle
function initFAQ() {
    const faqItems = document.querySelectorAll('.faq-item');
    if (!faqItems.length) return;
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        if (!question) return;
        
        question.addEventListener('click', () => {
            // Close all other items
            faqItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });
            // Toggle current item
            item.classList.toggle('active');
        });
    });
    
    // Set initial heights for active FAQ items
    document.querySelectorAll('.faq-item.active .faq-answer').forEach(answer => {
        answer.style.maxHeight = answer.scrollHeight + 'px';
    });
}

// Initialize Image Error Handling
function initImageErrorHandling() {
    document.querySelectorAll('img').forEach(img => {
        const originalSrc = img.src;
        img.addEventListener('error', function() {
            if (this.src !== window.location.origin + '/assets/images/default-tour.jpg') {
                this.src = window.location.origin + '/assets/images/default-tour.jpg';
                console.warn('Image failed to load:', originalSrc);
            }
        });
    });
}

function initFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const clearFiltersBtn = document.querySelector('.clear-filters');
    const searchForm = document.querySelector('.search-form');
    const sortSelect = document.querySelector('.sort-select');
    
    // Initialize active states
    function initActiveFilters() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Set active destination
        const destination = urlParams.get('destination');
        if (destination) {
            const btn = document.querySelector(`.filter-btn[data-value="${destination}"]`);
            if (btn) btn.classList.add('active');
        }
        
        // Set active duration
        const duration = urlParams.get('duration');
        if (duration) {
            const btn = document.querySelector(`.filter-btn[data-value="${duration}"]`);
            if (btn) btn.classList.add('active');
        }
        
        // Set active difficulty
        const difficulty = urlParams.get('difficulty');
        if (difficulty) {
            const btn = document.querySelector(`.filter-btn[data-value="${difficulty}"]`);
            if (btn) btn.classList.add('active');
        }
        
        // Set active sort
        const sort = urlParams.get('sort') || 'newest';
        if (sortSelect) {
            sortSelect.value = sort;
        }
    }
    
    initActiveFilters();
    
    // Filter button click
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const filterType = this.dataset.filter;
            const filterValue = this.dataset.value;
            const urlParams = new URLSearchParams(window.location.search);
            
            // Toggle active class
            const groupBtns = document.querySelectorAll(`.filter-btn[data-filter="${filterType}"]`);
            groupBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Update URL params
            if (filterValue === 'all') {
                urlParams.delete(filterType);
            } else {
                urlParams.set(filterType, filterValue);
            }
            
            // Reset to page 1 when filtering
            urlParams.set('page', '1');
            
            // Update URL
            updateUrlWithParams(urlParams);
        });
    });
    
    // Clear all filters
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            window.location.href = window.location.pathname;
        });
    }
    
    // Sort change
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('sort', this.value);
            urlParams.set('page', '1');
            updateUrlWithParams(urlParams);
        });
    }
    
    // Search form submission
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchInput = this.querySelector('.search-input');
            const urlParams = new URLSearchParams(window.location.search);
            
            if (searchInput.value.trim()) {
                urlParams.set('search', searchInput.value.trim());
            } else {
                urlParams.delete('search');
            }
            
            urlParams.set('page', '1');
            updateUrlWithParams(urlParams);
        });
    }
}

function updateUrlWithParams(urlParams) {
    const baseUrl = window.location.pathname;
    const queryString = urlParams.toString();
    window.location.href = queryString ? `${baseUrl}?${queryString}` : baseUrl;
}

function initQuickView() {
    const quickViewButtons = document.querySelectorAll('.btn-quick-view');
    const quickViewModal = document.getElementById('quickViewModal');
    const closeModal = document.querySelector('.close-modal');
    
    if (!quickViewModal) return;
    
    // Close modal
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            quickViewModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
    }
    
    // Close on outside click
    quickViewModal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && quickViewModal.style.display === 'flex') {
            quickViewModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
    
    // Load quick view data
    quickViewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const tourId = this.dataset.tourId;
            loadQuickView(tourId);
        });
    });
}

function loadQuickView(tourId) {
    const quickViewModal = document.getElementById('quickViewModal');
    const modalContent = document.querySelector('.quick-view-content');
    
    if (!quickViewModal || !modalContent) return;
    
    // Show loading state
    modalContent.innerHTML = `
        <button class="close-modal">&times;</button>
        <div class="text-center p-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-3">Loading tour details...</p>
        </div>
    `;
    
    quickViewModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Re-attach close event
    document.querySelector('.close-modal').addEventListener('click', function() {
        quickViewModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    });
    
    // Fetch tour details via AJAX
    fetch(`/includes/get-quick-view.php?tour_id=${tourId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderQuickView(data.tour);
            } else {
                modalContent.innerHTML = `
                    <button class="close-modal">&times;</button>
                    <div class="text-center p-5">
                        <i class="fas fa-exclamation-circle text-danger fa-3x mb-3"></i>
                        <h4>Error Loading Tour</h4>
                        <p>${data.message || 'Could not load tour details.'}</p>
                    </div>
                `;
                attachCloseEvent();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalContent.innerHTML = `
                <button class="close-modal">&times;</button>
                <div class="text-center p-5">
                    <i class="fas fa-exclamation-circle text-danger fa-3x mb-3"></i>
                    <h4>Network Error</h4>
                    <p>Please try again later.</p>
                </div>
            `;
            attachCloseEvent();
        });
    
    function attachCloseEvent() {
        document.querySelector('.close-modal').addEventListener('click', function() {
            quickViewModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
    }
}

function renderQuickView(tour) {
    const modalContent = document.querySelector('.quick-view-content');
    const difficultyClass = tour.difficulty_level ? tour.difficulty_level.toLowerCase().replace(' ', '-') : 'moderate';
    const difficultyText = tour.difficulty_level ? tour.difficulty_level.charAt(0).toUpperCase() + tour.difficulty_level.slice(1) : 'Moderate';
    const priceFormatted = parseFloat(tour.starting_price).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    const imageSrc = tour.featured_image ? tour.featured_image : '/assets/images/default-tour.jpg';
    
    let highlightsHTML = '';
    if (tour.highlights) {
        const highlights = tour.highlights.split(',').slice(0, 6);
        highlights.forEach(function(highlight) {
            if (highlight.trim()) {
                highlightsHTML += `
                    <div class="col-6">
                        <div class="highlight-item">
                            <i class="fas fa-star text-warning me-2"></i>
                            ${highlight.trim()}
                        </div>
                    </div>
                `;
            }
        });
    } else {
        highlightsHTML = '<p class="text-muted">No highlights available.</p>';
    }
    
    modalContent.innerHTML = `
        <button class="close-modal">&times;</button>
        <div class="row g-0">
            <div class="col-lg-6">
                <div class="tour-image" style="height: 400px;">
                    <img src="${imageSrc}" alt="${tour.title.replace(/"/g, '&quot;')}" style="width: 100%; height: 100%; object-fit: cover;">
                    <div class="tour-badges">
                        <span class="tour-badge price">From ₹${priceFormatted}</span>
                        <span class="tour-badge duration">${tour.duration_days} Days / ${tour.duration_nights} Nights</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="p-4">
                    <h3 class="tour-title-enhanced mb-3">${tour.title}</h3>
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${tour.destination || 'Multiple'}</span>
                        </span>
                        <span class="difficulty-badge ${difficultyClass}">${difficultyText}</span>
                    </div>
                    
                    <div class="tour-excerpt mb-4">
                        ${tour.description ? tour.description.substring(0, 200) + '...' : 'No description available.'}
                    </div>
                    
                    <div class="tour-highlights mb-4">
                        <h5 class="mb-3">Tour Highlights</h5>
                        <div class="row g-2">
                            ${highlightsHTML}
                        </div>
                    </div>
                    
                    <div class="tour-meta-grid mb-4">
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span>${tour.duration_days} Days / ${tour.duration_nights} Nights</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-users"></i>
                            <span>Group: ${tour.group_size || '2-12'}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Best Time: ${tour.season || 'All Year'}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-hotel"></i>
                            <span>${tour.accommodation_type || 'Standard Hotels'}</span>
                        </div>
                    </div>
                    
                    <div class="tour-actions">
                        <a href="/pages/tour-detail.php?slug=${tour.slug}" class="btn-details">
                            <i class="fas fa-info-circle me-2"></i>View Full Details
                        </a>
                        <a href="/pages/booking.php?tour=${tour.id}" class="btn-quick-view">
                            <i class="fas fa-calendar-check me-2"></i>Book Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Re-attach close button event
    document.querySelector('.close-modal').addEventListener('click', function() {
        document.getElementById('quickViewModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    });
}

function initBackToTop() {
    const backToTop = document.createElement('button');
    backToTop.className = 'back-to-top';
    backToTop.innerHTML = '<i class="fas fa-chevron-up"></i>';
    backToTop.setAttribute('aria-label', 'Back to top');
    document.body.appendChild(backToTop);
    
    backToTop.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backToTop.style.opacity = '1';
            backToTop.style.visibility = 'visible';
        } else {
            backToTop.style.opacity = '0';
            backToTop.style.visibility = 'hidden';
        }
    });
}

function initNewsletter() {
    const newsletterForm = document.getElementById('newsletterForm');
    if (!newsletterForm) return;
    
    newsletterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const emailInput = this.querySelector('.newsletter-input');
        const submitBtn = this.querySelector('.newsletter-btn');
        
        if (!emailInput.value.trim()) {
            alert('Please enter your email address.');
            return;
        }
        
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        submitBtn.disabled = true;
        
        // Simulate API call
        setTimeout(() => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Subscribed!',
                    text: 'Thank you for subscribing to our newsletter.',
                    confirmButtonText: 'OK'
                });
            } else {
                alert('Thank you for subscribing to our newsletter!');
            }
            
            emailInput.value = '';
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 1500);
    });
}

function initPagination() {
    // Add smooth scroll to pagination links
    document.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.href.includes('#') || this.classList.contains('disabled')) {
                return;
            }
            
            const urlParams = new URLSearchParams(this.href.split('?')[1]);
            const page = urlParams.get('page');
            if (page && page !== '1') {
                e.preventDefault();
                const targetPosition = document.querySelector('.tours-grid')?.offsetTop || 0;
                const navbarHeight = document.querySelector('.navbar')?.offsetHeight || 80;
                
                window.scrollTo({
                    top: targetPosition - navbarHeight - 20,
                    behavior: 'smooth'
                });
                
                // Navigate after scroll
                setTimeout(() => {
                    window.location.href = this.href;
                }, 500);
            }
        });
    });
}