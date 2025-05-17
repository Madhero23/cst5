<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/about.css">
    <title>About us</title>
</head>
<body>

<nav>
    <div class="navigation-container">
        <img src="images/logo.png" alt="LOGO" class="logo-nav"> 
        
        <div class="nav-wrapper">
            <ul class="nav-links">
                <li><a href="homepage.php">Home</a></li>
                <li><a href="about.php">About us</a></li>
                <li><a href="products.php">Menu</a></li>
                <li><a href="location.php">Store Location</a></li>
                <li><a href="account.php">Account</a></li>
            </ul>
        </div>
    </div>

</nav>


        <style>
.navigation-container {
    display: flex;
    align-items: center;
    justify-content: space-between; 
    
    background-color: #1b3c3d;
}

.logo-nav {
    height: 120px;
}

.nav-wrapper {
    flex: 1;
    display: flex;
    justify-content: center;
}

.nav-links {
    display: flex;
    gap: 25px;
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-links li {
    list-style: none;
    font-size: 18px;
    font-family: "Palatino Linotype", "Book Antiqua", Palatino, serif;
    font-weight: 400;
    transition: color 0.3s ease;
}

.nav-links a {
    text-decoration: none;
    color: rgb(190, 196, 106);
    font-size: 18px;
    transition: color 0.3s ease;
}

.nav-links a:hover {
    color: #fff;
}

            
        </style>


<section class="hero-section">
    <div class="hero-container">
        <img src="images/image-row.jpeg" alt="HERO" class="hero">
    </div>
    <section class="title-section">
        <div class="title-container">
            <p class="about">About Us</p>
            <h1>THE HEART <br>BEHIND THE OVEN</h1>
            <p>At Pastry Kitchen, we pour our passion into every freshly baked pastry and comforting bowl of soup. More than just a bakery, we are a warm and inviting space where flavors come to life—where buttery croissants, delicate tarts, and soul-soothing soups are crafted with love and the finest ingredients.</p>
            <br>
            <p>Our mission is to bring warmth to every bite, whether you’re stopping by for a flaky pastry with your morning coffee or a soothing bowl of soup to brighten your day. Welcome to a kitchen where every dish tells a story of comfort, craftsmanship, and care.</p>
            <div class="meet-container">
                <button type="button" id="meetTeamBtn" class="meet-button">MEET OUR TEAM</button>
                <button type="button" id="ourStoryBtn" class="story">OUR STORY</button>
            </div>
        </div>
    </section>
</section>

<section id="ourStorySection" class="story-section">
    <div class="story-container">
        <img src="images/createimg.jpeg" alt="OUR-STORY" class="our-story">
    </div>
    <section class="section-story">
        <div class="container-story">
            <h1>OUR STORY</h1>
            <p>At Pastry Kitchen, our journey began with a simple love for freshly baked pastries and soul-warming soups. What started as a passion for crafting delicate tarts, buttery croissants, and comforting bowls of soup soon grew into a cozy space where people can gather, unwind, and savor every bite.</p>
            <br>
            <p>We believe that great food brings people together, and that’s why every pastry and soup we serve is made with care, the finest ingredients, and a touch of heart.</p>
        </div>
    </section>
</section>

<section id="meetTeamSection" class="the-team-section">
    <div class="team-container">
        <h2>OUR TEAM</h2>
        <h1>WE HAVE AN AMAZING TEAM OF BAKERS</h1>
    </div>
    <div class="team-images">
        <div class="group-image-card"> 
            <div><img src="images/ken.jpg" alt="ken" class="ken"></div>
            <p>Kent Leonel Sevellino</p>
        </div>
        <div class="group-image-card">
            <div><img src="images/bb.jpg" alt="meg" class="meg"></div>
            <p>Liea Maegan Picardal</p>
        </div>
        <div class="group-image-card"> 
            <div><img src="images/gab.jpg" alt="gab" class="gab"></div>
            <p>Gabriel Mantua</p>
        </div>
        
    </div>
</section>

<script>
    // Smooth scroll to "Our Story" section
    document.getElementById('ourStoryBtn').addEventListener('click', function() {
        document.getElementById('ourStorySection').scrollIntoView({ behavior: 'smooth' });
    });

    // Smooth scroll to "Meet Our Team" section
    document.getElementById('meetTeamBtn').addEventListener('click', function() {
        document.getElementById('meetTeamSection').scrollIntoView({ behavior: 'smooth' });
    });
</script>

</body>
</html>