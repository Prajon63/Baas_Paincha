document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('auth-modal');
    const loginLink = document.getElementById('login-link');
    const closeBtn = document.querySelector('.close');
    const tabs = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    const switchTabs = document.querySelectorAll('.switch-tab');
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const contactForm = document.getElementById('contact-form');

    // Open modal
    if (loginLink) {
        loginLink.addEventListener('click', (e) => {
            e.preventDefault();
            modal.style.display = 'block';
        });
    }

    // Close modal
    if (closeBtn) {
        closeBtn.addEventListener('click', () => modal.style.display = 'none');
    }

    // Close modal on outside click
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = 'none';
    });

    // Tab switching
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.getAttribute('data-tab');
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(tabName).classList.add('active');
        });
    });

    // Switch tabs via links
    switchTabs.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const tabName = link.getAttribute('data-tab');
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            document.querySelector(`.tab-link[data-tab="${tabName}"]`).classList.add('active');
            document.getElementById(tabName).classList.add('active');
        });
    });

    // Toggle hamburger menu
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // Password visibility toggle
    modal.addEventListener('click', (e) => {
        if (e.target.classList.contains('toggle-password')) {
            const inputId = e.target.getAttribute('data-target');
            const input = document.getElementById(inputId);
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    e.target.classList.remove('fa-eye');
                    e.target.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    e.target.classList.remove('fa-eye-slash');
                    e.target.classList.add('fa-eye');
                }
            }
        }
    });

    // Login form submission
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const role = e.submitter.getAttribute('data-role');
            const email = loginForm.querySelector('input[type="email"]').value;
            const password = loginForm.querySelector('input[name="password"]').value;

            try {
                const response = await fetch('backend/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&role=${encodeURIComponent(role)}`
                });
                const data = await response.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.error);
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        });
    }

    // Register form submission
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const full_name = registerForm.querySelector('input[placeholder="Full Name"]').value;
            const email = registerForm.querySelector('input[placeholder="Email"]').value;
            const password = registerForm.querySelector('input[name="password"]').value;
            const confirm_password = registerForm.querySelector('input[name="confirm_password"]').value;
            const role = registerForm.querySelector('select').value;

            try {
                const response = await fetch('backend/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `full_name=${encodeURIComponent(full_name)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&confirm_password=${encodeURIComponent(confirm_password)}&role=${encodeURIComponent(role)}`
                });
                const data = await response.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.error);
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        });
    }

    // Contact form submission (placeholder)
    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();
            alert('Contact form submission will be implemented soon!');
            contactForm.reset();
        });
    }
});