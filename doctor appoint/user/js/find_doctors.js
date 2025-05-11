document.addEventListener('DOMContentLoaded', function() {
    // Convert the filters section to a form
    const filtersSection = document.querySelector('.filters-section');
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = 'find_doctors.php';
    form.className = 'filter-form';
    form.innerHTML = filtersSection.querySelector('.filters-container').innerHTML;
    filtersSection.querySelector('.filters-container').replaceWith(form);

    // Add sorting to the form
    const sortingOptions = document.querySelector('.sorting-options');
    const sortSelect = sortingOptions.querySelector('#sort-by');
    sortSelect.name = 'sort';
    form.appendChild(sortSelect.cloneNode(true));
    sortingOptions.querySelector('#sort-by').remove();

    // Apply filters button is now a submit button
    const applyBtn = form.querySelector('#apply-filters');
    applyBtn.type = 'submit';

    // Mobile menu toggle
    if (window.innerWidth < 768) {
        const mobileMenuToggle = document.createElement('div');
        mobileMenuToggle.className = 'mobile-menu-toggle';
        mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
        
        const headerContainer = document.querySelector('.header-container');
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

    // Responsive behavior for window resize
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

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        const dropdowns = document.querySelectorAll('.dropdown-content');
        dropdowns.forEach(dropdown => {
            dropdown.style.display = 'none';
        });
    });

    // Pagination buttons
    const paginationButtons = document.querySelectorAll('.btn-pagination');
    paginationButtons.forEach(button => {
        button.addEventListener('click', function() {
            // In a real implementation, this would update the form with page number
            // and submit it, but we'll keep it simple for this example
            paginationButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });
});