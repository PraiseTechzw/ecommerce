// Basic JavaScript for interactions
console.log('Script loaded.');

// Get base URL from a meta tag that we'll add to the header
const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '/ecommerce';

// Global Variables
let currentProduct = null;
let quickViewModalInstance = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Quick View Modal
    const quickViewModalElement = document.getElementById('quickViewModal');
    if (quickViewModalElement && typeof bootstrap !== 'undefined') {
        quickViewModalInstance = new bootstrap.Modal(quickViewModalElement);
    } else {
        console.error("Quick View Modal element not found or Bootstrap JS not loaded.");
    }

    // Initialize mobile menu
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            document.querySelector('.nav-links')?.classList.toggle('active');
        });
    }

    // Initialize search functionality
    initializeSearch();
});

// Function to show toast notifications
function showToast(message, type = 'success') {
    if (typeof Toastify === 'undefined') {
        console.error("Toastify is not loaded!");
        alert(message); // Fallback to alert
        return;
    }
    Toastify({
        text: message,
        duration: 3000,
        close: true,
        gravity: "top",
        position: "right",
        stopOnFocus: true,
        style: {
            background: type === 'success' 
                ? "linear-gradient(to right, #00b09b, #96c93d)" 
                : "linear-gradient(to right, #ff5f6d, #ffc371)",
        },
    }).showToast();
}

// Function to update cart count in header
function updateCartCount(count) {
    const cartCountElement = document.getElementById('headerCartCount');
    if (cartCountElement) {
        cartCountElement.textContent = count;
        cartCountElement.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

// Function to format price
function formatPrice(price) {
    const numPrice = parseFloat(price);
    return !isNaN(numPrice) ? '$' + numPrice.toFixed(2) : '$0.00';
}

// Function to generate rating stars HTML
function generateRatingStars(rating) {
    let stars = '';
    const numRating = parseFloat(rating);
    if (isNaN(numRating)) return '<div class="rating-stars">N/A</div>';

    const fullStars = Math.floor(numRating);
    const hasHalfStar = numRating - fullStars >= 0.5;

    for (let i = 0; i < fullStars; i++) {
        stars += '<i class="fas fa-star"></i>';
    }
    if (hasHalfStar && fullStars < 5) {
        stars += '<i class="fas fa-star-half-alt"></i>';
    }
    const emptyStars = 5 - Math.ceil(numRating);
    for (let i = 0; i < emptyStars; i++) {
        stars += '<i class="far fa-star"></i>';
    }
    return `<div class="rating-stars">${stars}</div>`;
}

// Function to show Quick View modal
function quickView(product) {
    if (!quickViewModalInstance) {
        console.error("Quick View modal instance not initialized.");
        showToast("Could not open Quick View.", "error");
        return;
    }
    currentProduct = product;

    // Update modal content
    document.getElementById('quickViewImage').src = product.image_url || product.image || '';
    document.getElementById('quickViewTitle').textContent = product.title || 'No Title';
    document.getElementById('quickViewPrice').textContent = formatPrice(product.price);
    document.getElementById('quickViewDescription').textContent = product.description || 'No Description';

    // Update rating
    const rating = product.rating?.rate;
    const ratingHtml = generateRatingStars(rating !== undefined ? rating : 4.5);
    document.getElementById('quickViewRating').innerHTML = ratingHtml;

    // Reset quantity
    const quantityInput = document.getElementById('quantity');
    if (quantityInput) {
        quantityInput.value = 1;
    }

    quickViewModalInstance.show();
}

// Function to update quantity in Quick View
function updateQuantity(change) {
    const input = document.getElementById('quantity');
    if (input) {
        const newValue = parseInt(input.value) + change;
        if (newValue >= 1) {
            input.value = newValue;
        }
    }
}

// Function to add item to cart from Quick View
function addToCartFromQuickView() {
    if (!currentProduct) {
        showToast("No product selected for Quick View.", "error");
        return;
    }
    const quantityInput = document.getElementById('quantity');
    const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
    addToCart(currentProduct.id, quantity);
}

// Function to add item to cart
function addToCart(productId, quantity = 1) {
    fetch(`${baseUrl}/api/cart/add.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(errData => {
                throw new Error(errData.message || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Product added to cart!', 'success');
            updateCartCount(data.totalQuantity);
            if (quickViewModalInstance) {
                quickViewModalInstance.hide();
            }
        } else {
            showToast(data.message || 'Error adding product to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        showToast(error.message || 'Could not add product to cart', 'error');
    });
}

// Function to initialize search
function initializeSearch() {
    const searchForm = document.querySelector('.search-bar');
    const searchInput = searchForm?.querySelector('input');
    const searchButton = searchForm?.querySelector('button');

    if (searchForm && searchInput && searchButton) {
        searchButton.addEventListener('click', (e) => {
            e.preventDefault();
            const searchQuery = searchInput.value.trim();
            if (searchQuery) {
                window.location.href = `${baseUrl}/pages/products.php?search=${encodeURIComponent(searchQuery)}`;
            }
        });

        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchButton.click();
            }
        });
    }
} 