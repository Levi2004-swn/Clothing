<?php
/**
 * About Us Page (restyled for current site)
 */
require_once 'config.php';

// Set page title (used by header.php)
$page_title = 'About Us';

include 'header.php';
?>

<!-- Page-specific styles (scoped and aligned with site theme) -->
<style>
    :root {
        --theme-color: #f53d2d;
        --theme-color-hover: #c23328;
    }

    /* Lightweight grid utilities to mimic Bootstrap-like layout */
    .row { display: flex; flex-wrap: wrap; gap: 24px; }
    .col-md-4 { flex: 1 1 calc(33.333% - 24px); min-width: 260px; }
    .col-md-6 { flex: 1 1 calc(50% - 24px); min-width: 320px; }
    .col-md-8 { flex: 1 1 calc(66.666% - 24px); min-width: 360px; }
    .col-lg-6 { flex: 1 1 calc(50% - 24px); min-width: 360px; }
    .text-center { text-align: center; }
    .bg-dark { background-color: #2b2b2b; }
    .text-white { color: #fff; }
    .py-5 { padding-top: 3rem; padding-bottom: 3rem; }
    .fw-bold { font-weight: 700; }
    .display-4 { font-size: 2.5rem; line-height: 1.2; }
    .img-fluid { max-width: 100%; height: auto; }

    /* Base Styles */
    .about-section {
        padding: 80px 0;
        position: relative;
        overflow: hidden;
    }

    /* Parallax Effect */
    .parallax-section {
        min-height: 500px;
        display: flex;
        align-items: center;
        position: relative;
        background-attachment: fixed;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
    }
    
    .parallax-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(33, 37, 41, 0.7);
    }
    
    .parallax-content {
        position: relative;
        z-index: 2;
        color: white;
    }
    
    /* Image Effects */
    .about-image {
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: transform 0.5s ease, box-shadow 0.5s ease;
        position: relative;
        overflow: hidden;
    }
    
    .about-image:hover {
        transform: scale(1.02);
        box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    }
    
    .about-image::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 50%;
        height: 100%;
        background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 100%);
        transform: skewX(-25deg);
        transition: all 0.75s;
    }
    
    .about-image:hover::after {
        left: 125%;
    }
    
    /* Timeline Styles */
    .story-timeline {
        position: relative;
        padding-left: 30px;
        margin-top: 50px;
    }
    
    .story-timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background-color: var(--theme-color);
        transform: scaleY(0);
        transform-origin: top;
        transition: transform 1.5s ease;
    }
    
    .story-timeline.animate::before {
        transform: scaleY(1);
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 40px;
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s ease, transform 0.5s ease;
    }
    
    .timeline-item.visible {
        opacity: 1;
        transform: translateY(0);
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -34px;
        top: 5px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background-color: var(--theme-color);
        transform: scale(0);
        transition: transform 0.3s ease 0.3s;
    }
    
    .timeline-item.visible::before {
        transform: scale(1);
    }
    
    .timeline-year {
        font-weight: 700;
        color: var(--theme-color);
        margin-bottom: 10px;
        position: relative;
        display: inline-block;
    }
    
    .timeline-year::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 0;
        height: 2px;
        background-color: var(--theme-color);
        transition: width 0.5s ease 0.5s;
    }
    
    .timeline-item.visible .timeline-year::after {
        width: 100%;
    }
    
    /* Value Cards */
    .value-card {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 30px;
        height: 100%;
        transition: all 0.5s ease;
        position: relative;
        z-index: 1;
        overflow: hidden;
    }
    
    .value-card::before {
        content: '';
        position: absolute;
        z-index: -1;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--theme-color);
        transform: scaleY(0);
        transform-origin: 50% 100%;
        transition: transform 0.5s ease;
    }
    
    .value-card:hover::before {
        transform: scaleY(1);
    }
    
    .value-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        color: white;
    }
    
    .value-card:hover .value-icon {
        color: white;
        transform: rotateY(360deg);
    }
    
    .value-icon {
        font-size: 2.5rem;
        color: var(--theme-color);
        margin-bottom: 20px;
        transition: all 0.8s ease;
    }
    
    /* Team Members */
    .team-member {
        text-align: center;
        margin-bottom: 30px;
        position: relative;
        perspective: 1000px;
    }
    
    .team-photo-container {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto 15px;
        perspective: 1000px;
    }
    
    .team-photo {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--theme-color);
        transition: all 0.5s ease;
        backface-visibility: hidden;
    }
    
    .team-member:hover .team-photo {
        transform: rotateY(180deg);
    }
    
    .team-photo-back {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background-color: var(--theme-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        transform: rotateY(-180deg);
        backface-visibility: hidden;
        transition: all 0.5s ease;
    }
    
    .team-member:hover .team-photo-back {
        transform: rotateY(0);
    }
    
    .team-social {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 10px;
    }
    
    .team-social a {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: var(--theme-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .team-social a:hover {
        transform: translateY(-3px);
        background-color: var(--theme-color-hover);
    }
    
    /* Section Titles */
    .section-title {
        position: relative;
        margin-bottom: 50px;
        padding-bottom: 20px;
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100px;
        height: 3px;
        background-color: var(--theme-color);
        transition: width 0.5s ease;
    }
    
    .section-title:hover::after {
        width: 150px;
    }
    
    .section-title.text-center::after {
        left: 50%;
        transform: translateX(-50%);
    }
    
    /* Testimonial Cards */
    .testimonial-card {
        position: relative;
        height: 300px;
        margin-bottom: 30px;
        perspective: 1500px;
    }
    
    .testimonial-inner {
        position: relative;
        width: 100%;
        height: 100%;
        text-align: center;
        transition: transform 0.8s;
        transform-style: preserve-3d;
    }
    
    .testimonial-card:hover .testimonial-inner {
        transform: rotateY(180deg);
    }
    
    .testimonial-front, .testimonial-back {
        position: absolute;
        width: 100%;
        height: 100%;
        backface-visibility: hidden;
        border-radius: 10px;
        padding: 30px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .testimonial-front {
        background-color: #f8f9fa;
        border: 1px solid #e0e0e0;
    }
    
    .testimonial-back {
        background-color: var(--theme-color);
        color: white;
        transform: rotateY(180deg);
    }
    
    .testimonial-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin: 0 auto 15px;
        border: 3px solid var(--theme-color);
    }
    
    .testimonial-back .testimonial-avatar {
        border-color: white;
    }
    
    .testimonial-quote {
        font-size: 3rem;
        color: var(--theme-color);
        opacity: 0.2;
        position: absolute;
        top: 20px;
        left: 20px;
    }
    
    /* Map Section */
    .map-container {
        position: relative;
        height: 400px;
        overflow: hidden;
        border-radius: 10px;
    }
    
    .world-map {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .map-marker {
        position: absolute;
        width: 20px;
        height: 20px;
        background-color: var(--theme-color);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .map-marker:hover {
        transform: translate(-50%, -50%) scale(1.5);
        z-index: 10;
    }
    
    .map-marker::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background-color: var(--theme-color);
        opacity: 0.5;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% {
            transform: scale(1);
            opacity: 0.5;
        }
        70% {
            transform: scale(2);
            opacity: 0;
        }
        100% {
            transform: scale(1);
            opacity: 0;
        }
    }
    
    .map-tooltip {
        position: absolute;
        background-color: white;
        border-radius: 5px;
        padding: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        min-width: 150px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
        z-index: 20;
    }
    
    .map-marker:hover + .map-tooltip {
        opacity: 1;
    }
    
    /* Progress Bars */
    .progress-container {
        margin-bottom: 30px;
    }
    
    .progress-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .progress {
        height: 10px;
        border-radius: 5px;
        overflow: visible;
        width: 100%;
        background-color: #e9ecef; /* light track */
    }
    
    .progress-bar {
        position: relative;
        border-radius: 5px;
        transition: width 1.5s ease;
        width: 0;
        height: 100%;
        background-color: var(--theme-color); /* visible fill when not using utility classes */
    }
    
    .progress-bar.animate {
        width: var(--progress-width);
    }
    
    .progress-bar::after {
        content: '';
        position: absolute;
        right: -5px;
        top: -5px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background-color: var(--theme-color);
        opacity: 0;
        transition: opacity 0.3s ease 1.5s;
    }
    
    .progress-bar.animate::after {
        opacity: 1;
    }

    /* Utility color (Bootstrap-like) fallback */
    .bg-success { background-color: var(--theme-color) !important; }
    .mt-5 { margin-top: 3rem; }
    
    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .fade-in-up {
        opacity: 0;
        animation: fadeInUp 1s ease forwards;
    }
    
    .delay-1 { animation-delay: 0.2s; }
    .delay-2 { animation-delay: 0.4s; }
    .delay-3 { animation-delay: 0.6s; }
    .delay-4 { animation-delay: 0.8s; }
    .delay-5 { animation-delay: 1s; }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .parallax-section {
            background-attachment: scroll;
        }
    }
    /* Minor helpers */
    .mb-5 { margin-bottom: 3rem; }
    .mb-4 { margin-bottom: 1.5rem; }
    .lead { font-size: 1.1rem; color: #555; }
</style>

    <!-- Hero Section -->
    <section class="bg-dark text-white py-5 mb-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Our Story</h1>
                    <p class="lead">From humble beginnings to a global fashion phenomenon, discover the journey behind Multinational Clothing Store.</p>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" 
                         alt="Fashion store" class="img-fluid about-image">
                </div>
            </div>
        </div>
    </section>

    <!-- Our Journey -->
    <section class="about-section">
        <div class="container">
            <h2 class="section-title">Our Journey</h2>
            <p class="lead mb-5">What started as a small boutique has evolved into a global fashion destination, connecting cultures through style.</p>
            
            <div class="story-timeline">
                <div class="timeline-item">
                    <div class="timeline-year">2005</div>
                    <h4>The Beginning</h4>
                    <p>Our founder opened the first small boutique in Milan, focusing on locally sourced materials and traditional craftsmanship.</p>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-year">2010</div>
                    <h4>Expanding Horizons</h4>
                    <p>After gaining recognition for quality and unique designs, we expanded to five European countries, bringing our vision to new markets.</p>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-year">2015</div>
                    <h4>Digital Transformation</h4>
                    <p>Embracing the digital age, we launched our e-commerce platform, making our collections accessible worldwide.</p>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-year">2018</div>
                    <h4>Sustainability Initiative</h4>
                    <p>We committed to sustainable fashion by implementing eco-friendly practices across our supply chain and introducing our first fully sustainable collection.</p>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-year">2023</div>
                    <h4>Global Presence</h4>
                    <p>Today, we operate in over 30 countries, collaborating with local artisans and designers to create fashion that celebrates cultural diversity.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values -->
    <section class="about-section bg-light">
        <div class="container">
            <h2 class="section-title text-center">Our Values</h2>
            <p class="lead text-center mb-5">The principles that guide everything we do</p>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h4>Cultural Diversity</h4>
                        <p>We celebrate the rich tapestry of global fashion traditions, bringing diverse styles together in harmony.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <h4>Sustainability</h4>
                        <p>Our commitment to the planet drives us to continuously improve our environmental footprint.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h4>Quality Craftsmanship</h4>
                        <p>We believe in creating garments that stand the test of time, both in style and durability.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Team -->
    <section class="about-section">
        <div class="container">
            <h2 class="section-title text-center">Meet Our Team</h2>
            <p class="lead text-center mb-5">The passionate individuals behind our brand</p>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="team-member">
                        <img src="uploads/images/sofia.jpg" 
                             alt="Sofia Martinez" class="team-photo">
                        <h4>Sofia Martinez</h4>
                        <p class="text-muted">Founder & Creative Director</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="team-member">
                        <img src="https://svgsilh.com/svg_v2/1300143.svg" 
                             alt="David Chen" class="team-photo">
                        <h4>David Chen</h4>
                        <p class="text-muted">Head of Design</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="team-member">
                        <img src="uploads/images/amara.png" 
                             alt="Amara Okafor" class="team-photo">
                        <h4>Amara Okafor</h4>
                        <p class="text-muted">Sustainability Director</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

        <!-- Mission and Vision with Parallax Effect -->
    <section class="parallax-section" style="background-image: url('https://images.unsplash.com/photo-1558769132-cb1aea458c5e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');">
        <div class="parallax-overlay"></div>
        <div class="container parallax-content">
            <div class="row">
                <div class="col-md-8 offset-md-2 text-center">
                    <h2 class="mb-4 fade-in-up">Our Mission &amp; Vision</h2>
                    <div class="fade-in-up delay-1">
                        <p class="lead mb-4">To revolutionize the fashion industry by creating timeless, sustainable clothing that celebrates cultural diversity and empowers individuals to express their unique identity.</p>
                        <p>We envision a world where fashion transcends boundaries, where every garment tells a story of craftsmanship, heritage, and innovation. Our mission is to blend traditional techniques with modern design, creating pieces that honor our global heritage while embracing the future.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Brand Heritage Timeline with Advanced Animations -->
    <section class="about-section bg-light">
        <div class="container">
            <h2 class="section-title text-center">Our Heritage Journey</h2>
            <p class="lead text-center mb-5">From humble beginnings to global presence</p>
            
            <div class="story-timeline animate" id="heritage-timeline">
                <div class="timeline-item">
                    <div class="timeline-year">2005</div>
                    <h4>The Seed is Planted</h4>
                    <p>Our founder Sofia Martinez created her first collection in a small studio apartment, drawing inspiration from her travels across Southeast Asia. With just three designs and a vision, the foundation of our brand was born.</p>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-year">2008</div>
                    <h4>First Flagship Store</h4>
                    <p>After gaining recognition in local markets, we opened our first flagship store in Barcelona, showcasing our commitment to sustainable materials and ethical production practices.</p>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-year">2012</div>
                    <h4>Global Expansion Begins</h4>
                    <p>Our unique approach to fashion caught international attention, leading to partnerships with artisans across three continents and the opening of stores in London, Tokyo, and New York.</p>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-year">2016</div>
                    <h4>Sustainability Commitment</h4>
                    <p>We launched our "Earth First" initiative, pledging that 75% of our materials would come from sustainable sources by 2020. This bold move transformed our supply chain and inspired industry-wide changes.</p>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-year">2020</div>
                    <h4>Digital Transformation</h4>
                    <p>Embracing the changing retail landscape, we revamped our online presence and introduced virtual fitting rooms, bringing our personalized shopping experience to customers worldwide.</p>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-year">Today</div>
                    <h4>A Global Community</h4>
                    <p>With presence in over 30 countries, we've grown into more than just a fashion brand—we're a global movement celebrating diversity, sustainability, and timeless style.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Customer Testimonials with Card Flip Effects -->
    <section class="about-section">
        <div class="container">
            <h2 class="section-title text-center">Voices of Our Community</h2>
            <p class="lead text-center mb-5">What our customers say about their experience</p>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="testimonial-inner">
                            <div class="testimonial-front">
                                <div class="testimonial-quote"><i class="fas fa-quote-left"></i></div>
                                <img src="https://www.tvpoolonline.com/wp-content/uploads/2022/11/1B0FA6C4-AF54-4A7F-B2E4-D6E9C1F674F7.png" 
                                     alt="Sarah Johnson" class="testimonial-avatar">
                                <h5>Sarah Johnson</h5>
                                <p class="text-muted mb-3">New York, USA</p>
                                <p>"The quality and attention to detail in every piece I've purchased has been exceptional. These aren't just clothes—they're investments."</p>
                            </div>
                            <div class="testimonial-back">
                                <img src="https://www.tvpoolonline.com/wp-content/uploads/2022/11/1B0FA6C4-AF54-4A7F-B2E4-D6E9C1F674F7.png" 
                                     alt="Sarah Johnson" class="testimonial-avatar">
                                <h5>Sarah Johnson</h5>
                                <p class="mb-3">Loyal customer since 2018</p>
                                <p>"I've completely transformed my wardrobe with timeless pieces that make me feel confident and connected to the stories behind each garment."</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="testimonial-inner">
                            <div class="testimonial-front">
                                <div class="testimonial-quote"><i class="fas fa-quote-left"></i></div>
                                <img src="https://static.vecteezy.com/system/resources/previews/046/922/849/non_2x/male-head-silhouette-illustration-white-background-vector.jpg" 
                                     alt="Miguel Fernandez" class="testimonial-avatar">
                                <h5>Miguel Fernandez</h5>
                                <p class="text-muted mb-3">Madrid, Spain</p>
                                <p>"As someone who cares deeply about sustainability, finding a brand that aligns with my values without compromising on style has been refreshing."</p>
                            </div>
                            <div class="testimonial-back">
                                <img src="https://static.vecteezy.com/system/resources/previews/046/922/849/non_2x/male-head-silhouette-illustration-white-background-vector.jpg" 
                                     alt="Miguel Fernandez" class="testimonial-avatar">
                                <h5>Miguel Fernandez</h5>
                                <p class="mb-3">Environmental Activist</p>
                                <p>"I appreciate the transparency about materials and production processes. It's rare to find a company so committed to both people and planet."</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="testimonial-inner">
                            <div class="testimonial-front">
                                <div class="testimonial-quote"><i class="fas fa-quote-left"></i></div>
                                <img src="uploads/images/aisha.png" 
                                     alt="Aisha Patel" class="testimonial-avatar">
                                <h5>Aisha Patel</h5>
                                <p class="text-muted mb-3">Mumbai, India</p>
                                <p>"The fusion of traditional techniques with contemporary designs speaks to my multicultural background. Each piece feels personal."</p>
                            </div>
                            <div class="testimonial-back">
                                <img src="uploads/images/aisha.png" 
                                     alt="Aisha Patel" class="testimonial-avatar">
                                <h5>Aisha Patel</h5>
                                <p class="mb-3">Fashion Blogger</p>
                                <p>"Beyond the beautiful designs, it's the stories behind each collection that keep me coming back. Fashion with meaning is the future."</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Global Presence Map with Interactive Elements -->
    <section class="about-section bg-light">
        <div class="container">
            <h2 class="section-title text-center">Our Global Presence</h2>
            <p class="lead text-center mb-5">Connecting cultures and communities across the world</p>
            
            <div class="map-container">
                <img src="uploads/images/world-map.jpg" alt="World Map" class="world-map">
                
                <!-- Map Markers -->
                <div class="map-marker" style="top: 30%; left: 20%;" data-location="New York"></div>
                <div class="map-tooltip" style="top: 25%; left: 20%;">
                    <h6>New York</h6>
                    <p>Flagship Store & Design Studio</p>
                </div>
                
                <div class="map-marker" style="top: 25%; left: 45%;" data-location="London"></div>
                <div class="map-tooltip" style="top: 20%; left: 45%;">
                    <h6>London</h6>
                    <p>European Headquarters</p>
                </div>
                
                <div class="map-marker" style="top: 35%; left: 50%;" data-location="Milan"></div>
                <div class="map-tooltip" style="top: 30%; left: 50%;">
                    <h6>Milan</h6>
                    <p>Design & Innovation Center</p>
                </div>
                
                <div class="map-marker" style="top: 30%; left: 80%;" data-location="Tokyo"></div>
                <div class="map-tooltip" style="top: 25%; left: 80%;">
                    <h6>Tokyo</h6>
                    <p>Asian Flagship & Tech Hub</p>
                </div>
                
                <div class="map-marker" style="top: 45%; left: 75%;" data-location="Mumbai"></div>
                <div class="map-tooltip" style="top: 40%; left: 75%;">
                    <h6>Mumbai</h6>
                    <p>Artisan Partnership Center</p>
                </div>
                
                <div class="map-marker" style="top: 60%; left: 25%;" data-location="São Paulo"></div>
                <div class="map-tooltip" style="top: 55%; left: 25%;">
                    <h6>São Paulo</h6>
                    <p>South American Headquarters</p>
                </div>
                
                <div class="map-marker" style="top: 65%; left: 85%;" data-location="Sydney"></div>
                <div class="map-tooltip" style="top: 60%; left: 85%;">
                    <h6>Sydney</h6>
                    <p>Oceania Distribution Center</p>
                </div>
            </div>
            
            <div class="row mt-5">
                <div class="col-md-4 text-center fade-in-up">
                    <h3 class="mb-3"><i class="fas fa-store" style="margin-right:8px;"></i> 120+</h3>
                    <p>Retail Locations</p>
                </div>
                <div class="col-md-4 text-center fade-in-up delay-2">
                    <h3 class="mb-3"><i class="fas fa-globe" style="margin-right:8px;"></i> 30+</h3>
                    <p>Countries</p>
                </div>
                <div class="col-md-4 text-center fade-in-up delay-3">
                    <h3 class="mb-3"><i class="fas fa-users" style="margin-right:8px;"></i> 2M+</h3>
                    <p>Global Community</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Sustainability Commitment with Progress Indicators -->
    <section class="about-section">
        <div class="container">
            <h2 class="section-title">Our Sustainability Commitment</h2>
            <p class="lead mb-5">Tracking our progress toward a more sustainable future</p>
            
            <div class="row">
                <div class="col-md-6">
                    <p>At the core of our brand is a deep commitment to environmental stewardship and ethical practices. We believe that fashion can be a force for positive change, and we're dedicated to leading by example.</p>
                    <p>Our sustainability journey is ongoing, with ambitious targets that push us to innovate and improve every aspect of our business—from sourcing and production to packaging and distribution.</p>
                    <p>We're proud of our progress, but we recognize there's always more work to be done. Transparency is key to accountability, which is why we openly share our goals and achievements.</p>
                </div>
                
                <div class="col-md-6">
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Sustainable Materials</span>
                            <span>85%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="--progress-width: 85%;" role="progressbar" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Carbon Neutral Operations</span>
                            <span>70%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="--progress-width: 70%;" role="progressbar" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Zero Waste Packaging</span>
                            <span>90%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="--progress-width: 90%;" role="progressbar" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Fair Trade Certification</span>
                            <span>95%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="--progress-width: 95%;" role="progressbar" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Water Conservation</span>
                            <span>65%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="--progress-width: 65%;" role="progressbar" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Team Section with Flip Cards -->
    <section class="about-section bg-light">
        <div class="container">
            <h2 class="section-title text-center">The Visionaries Behind Our Brand</h2>
            <p class="lead text-center mb-5">Meet the passionate individuals shaping our future</p>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="team-member">
                        <div class="team-photo-container">
                            <img src="uploads/images/sofia-small.jpg" 
                                 alt="Sofia Martinez" class="team-photo">
                            <div class="team-photo-back">
                                <p>"Fashion is storytelling through fabric."</p>
                            </div>
                        </div>
                        <h4>Sofia Martinez</h4>
                        <p class="text-muted">Founder & Creative Director</p>
                        <p>With 20 years of experience in haute couture, Sofia brings her vision of inclusive, sustainable fashion to life through innovative design.</p>
                        <div class="team-social">
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="team-member">
                        <div class="team-photo-container">
                            <img src="uploads/images/david.png" 
                                 alt="David Chen" class="team-photo">
                            <div class="team-photo-back">
                                <p>"Design should solve problems beautifully."</p>
                            </div>
                        </div>
                        <h4>David Chen</h4>
                        <p class="text-muted">Head of Design</p>
                        <p>David's background in architecture informs his structural approach to fashion, creating pieces that are both functional and aesthetically striking.</p>
                        <div class="team-social">
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-behance"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="team-member">
                        <div class="team-photo-container">
                            <img src="uploads/images/amara-small.png" 
                                 alt="Amara Okafor" class="team-photo">
                            <div class="team-photo-back">
                                <p>"Sustainability is not a trend, it's the future."</p>
                            </div>
                        </div>
                        <h4>Amara Okafor</h4>
                        <p class="text-muted">Sustainability Director</p>
                        <p>With a PhD in Environmental Science, Amara leads our sustainability initiatives, ensuring our practices align with our commitment to the planet.</p>
                        <div class="team-social">
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-medium-m"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="parallax-section" style="background-image: url('https://images.unsplash.com/photo-1445205170230-053b83016050?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');">
        <div class="parallax-overlay"></div>
        <div class="container parallax-content text-center">
            <h2 class="mb-4 fade-in-up">Join Our Fashion Journey</h2>
            <p class="lead mb-5 fade-in-up delay-1">Discover our latest collections and be part of our global community dedicated to style, sustainability, and cultural celebration.</p>
            <div class="fade-in-up delay-2">
                <a href="category.php" class="btn btn-light btn-lg me-3">Shop Now</a>
                <a href="#" class="btn btn-outline-light btn-lg">Join Our Newsletter</a>
            </div>
        </div>
    </section>
<script>
// Animation for timeline items
document.addEventListener('DOMContentLoaded', function() {
    // Timeline animations
    const timelineItems = document.querySelectorAll('.timeline-item');
    const storyTimeline = document.querySelector('.story-timeline');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                if (entry.target.classList.contains('story-timeline')) {
                    entry.target.classList.add('animate');
                } else {
                    entry.target.classList.add('visible');
                }
            }
        });
    }, { threshold: 0.2 });
    
    if (storyTimeline) {
        observer.observe(storyTimeline);
    }
    
    timelineItems.forEach(item => {
        observer.observe(item);
    });
    
    // Progress bar animations
    const progressBars = document.querySelectorAll('.progress-bar');
    
    const progressObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, { threshold: 0.2 });
    
    progressBars.forEach(bar => {
        progressObserver.observe(bar);
    });
    
    // Fade-in animations
    const fadeElements = document.querySelectorAll('.fade-in-up');
    
    const fadeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, { threshold: 0.1 });
    
    fadeElements.forEach(element => {
        element.style.animationPlayState = 'paused';
        fadeObserver.observe(element);
    });
});
</script>
<?php include 'footer.php'; ?>
