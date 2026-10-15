// Global function called directly from index.php onclick="addToCart(...)"
function addToCart(productId, btnElement) {
    console.log("Add to cart clicked for product ID:", productId);

    const card = btnElement.closest('.product-card');
    const qtyInput = card ? card.querySelector('.qty-input') : null;
    const qty = qtyInput ? qtyInput.value : 1;

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', qty);

    fetch('add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(res => {
        console.log("Response received from server");
        return res.json();
    })
    .then(data => {
        console.log("Parsed JSON data:", data);
        alert(data.message);
        
        // If user is not logged in, redirect them to login page
        if (data.status === 'error' && data.message.toLowerCase().includes('log in')) {
            window.location.href = 'login.php';
        }
    })
    .catch(err => {
        console.error('Fetch Error:', err);
        alert('An error occurred. Check your browser console (F12) for details.');
    });
}

// 2. DOM Content Loaded Initialization
document.addEventListener("DOMContentLoaded", () => {
    
    // --- Day/Night Mode Logic ---
    const themeToggle = document.getElementById('theme-toggle');
    const currentTheme = localStorage.getItem('theme');

    if (currentTheme) {
        document.documentElement.setAttribute('data-theme', currentTheme);
        if (themeToggle && currentTheme === 'dark') {
            themeToggle.textContent = '☀️';
        }
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            let activeTheme = document.documentElement.getAttribute('data-theme');
            
            if (activeTheme === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                themeToggle.textContent = '🌙';
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeToggle.textContent = '☀️';
            }
        });
    }

    // --- Hero Slider Logic (Changes every 2 seconds) ---
    const slides = document.querySelectorAll('.slide');
    let currentSlide = 0;
    
    if (slides.length > 0) {
        setInterval(() => {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }, 2000);
    }

    // --- Slide Down / Reveal Items Row by Row ---
    const observerOptions = {
        threshold: 0.1,
        rootMargin: "0px 0px -50px 0px"
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.slide-up').forEach(card => {
        observer.observe(card);
    });

    // --- Modal Popup Logic ---
    const modal = document.getElementById("productModal");
    const modalTitle = document.getElementById("modalTitle");
    const modalDescription = document.getElementById("modalDescription");
    const closeBtn = document.querySelector(".close-btn");

    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', function() {
            const title = this.querySelector('h3') ? this.querySelector('h3').innerText : '';
            const desc = this.getAttribute('data-description');
            
            if (modalTitle && modalDescription && modal) {
                modalTitle.innerText = title;
                modalDescription.innerText = desc;
                modal.style.display = "flex";
            }
        });
    });

    if (closeBtn && modal) {
        closeBtn.addEventListener('click', () => {
            modal.style.display = "none";
        });
    }

    window.addEventListener('click', (e) => {
        if (modal && e.target === modal) {
            modal.style.display = "none";
        }
    });

    // --- Quantity Up and Down Logic ---
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); // Stops popup modal from opening when clicking +/-
            const isPlus = this.classList.contains('plus');
            const input = this.parentElement.querySelector('.qty-input');
            let val = parseInt(input.value) || 1;

            if (isPlus) {
                input.value = val + 1;
            } else if (val > 1) {
                input.value = val - 1;
            }
        });
    });

    // Prevent modal opening when clicking directly inside quantity inputs or add-to-cart buttons
    document.querySelectorAll('.qty-input, .add-cart-btn').forEach(el => {
        el.addEventListener('click', (e) => e.stopPropagation());
    });

    // --- Add to Cart Event Listener (Fallback for buttons without inline onclick) ---
    document.querySelectorAll('.add-cart-btn').forEach(button => {
        if (!button.hasAttribute('onclick')) {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const card = this.closest('.product-card');
                const productId = card ? card.getAttribute('data-id') : null;
                if (productId) {
                    addToCart(productId, this);
                }
            });
        }
    });

    // --- Dynamic Cart Selection & Live Total Calculation (cart.php) ---
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const selectAll = document.getElementById('select-all');
    const totalAmountEl = document.getElementById('total-amount');
    const selectedCountEl = document.getElementById('selected-count');

    function calculateCartTotal() {
        if (!totalAmountEl) return;
        let total = 0;
        let count = 0;

        document.querySelectorAll('.cart-item-row').forEach(row => {
            const checkbox = row.querySelector('.item-checkbox');
            if (checkbox && checkbox.checked) {
                const subtotalText = row.querySelector('.subtotal-val').innerText;
                total += parseFloat(subtotalText) || 0;
                count++;
            }
        });

        totalAmountEl.innerText = `$${total.toFixed(2)}`;
        if (selectedCountEl) {
            selectedCountEl.innerText = count;
        }
    }

    if (checkboxes.length > 0) {
        calculateCartTotal(); // Calculate on initial load

        checkboxes.forEach(cb => {
            cb.addEventListener('change', calculateCartTotal);
        });

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                calculateCartTotal();
            });
        }
    }
});



        document.getElementById('checkout-form').addEventListener('submit', function(e) {
    const container = document.getElementById('hidden-inputs-container');
    container.innerHTML = '';
    
    let selectedAny = false;
    document.querySelectorAll('.cart-item-row').forEach(row => {
        const checkbox = row.querySelector('.item-checkbox');
        if (checkbox && checkbox.checked) {
            selectedAny = true;
            const cartId = checkbox.getAttribute('data-cart-id');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_items[]';
            input.value = cartId;
            container.appendChild(input);
        }
    });

    if (!selectedAny) {
        e.preventDefault();
        alert('Please select at least one item to proceed.');
    }
});