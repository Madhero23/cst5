document.addEventListener('DOMContentLoaded', function() {
    // Animation trigger on scroll
    const animateElements = document.querySelectorAll('.animate');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
            }
        });
    }, { threshold: 0.1 });
    
    animateElements.forEach(element => {
        observer.observe(element);
    });

    // Account dropdown functionality
    const accountDropdown = document.querySelector('.account-dropdown');
    if (accountDropdown) {
        accountDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdownContent = this.querySelector('.dropdown-content');
            dropdownContent.style.display = 
                dropdownContent.style.display === 'block' ? 'none' : 'block';
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        const dropdowns = document.querySelectorAll('.dropdown-content');
        dropdowns.forEach(dropdown => {
            dropdown.style.display = 'none';
        });
    });

    // Mobile menu toggle
    const mobileMenuToggle = document.createElement('div');
    mobileMenuToggle.className = 'mobile-menu-toggle';
    mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    
    const headerContainer = document.querySelector('.header-container');
    if (window.innerWidth < 768) {
        headerContainer.appendChild(mobileMenuToggle);
        const nav = document.querySelector('nav ul');
        nav.style.display = 'none';
        
        mobileMenuToggle.addEventListener('click', function() {
            if (nav.style.display === 'none' || nav.style.display === '') {
                nav.style.display = 'flex';
                mobileMenuToggle.innerHTML = '<i class="fas fa-times"></i>';
            } else {
                nav.style.display = 'none';
                mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });
    }

    // Responsive behavior
    window.addEventListener('resize', function() {
        const nav = document.querySelector('nav ul');
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        
        if (window.innerWidth >= 768) {
            if (nav) nav.style.display = 'flex';
            if (mobileToggle) mobileToggle.style.display = 'none';
        } else {
            if (nav) nav.style.display = 'none';
            if (mobileToggle) mobileToggle.style.display = 'block';
        }
    });
});