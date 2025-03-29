// Initialize dark mode based on localStorage
function initializeDarkMode() {
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

// Call this when the page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeDarkMode();
    
    // Add event listener to dark mode toggle if it exists
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            if (document.documentElement.classList.contains('dark')) {
                // Switch to light mode
                document.documentElement.classList.remove('dark');
                localStorage.setItem('darkMode', 'disabled');
            } else {
                // Switch to dark mode
                document.documentElement.classList.add('dark');
                localStorage.setItem('darkMode', 'enabled');
            }
        });
    }
}); 