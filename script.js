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
    const forgotPasswordForm = document.getElementById('forgot-password-form');
    const contactForm = document.getElementById('contact-form');

    console.log('DOM loaded. Checking form elements:');
    console.log('auth-modal:', modal);
    console.log('login-form:', loginForm);
    console.log('register-form:', registerForm);
    console.log('forgot-password-form:', forgotPasswordForm);
    console.log('contact-form:', contactForm);

    // Open modal
    if (loginLink) {
        loginLink.addEventListener('click', (e) => {
            e.preventDefault();
            console.log('Login link clicked, opening modal');
            modal.style.display = 'block';
        });
    }

    // Close modal
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            console.log('Close button clicked, closing modal');
            modal.style.display = 'none';
        });
    }

    // Close modal on outside click
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            console.log('Clicked outside modal, closing modal');
            modal.style.display = 'none';
        }
    });

    // Tab switching
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.getAttribute('data-tab');
            console.log('Switching to tab:', tabName);
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
            console.log('Switching to tab via link:', tabName);
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            document.querySelector(`.tab-link[data-tab="${tabName}"]`).classList.add('active');
            document.getElementById(tabName).classList.add('active');
        });
    });

    // Toggle hamburger menu
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            console.log('Hamburger menu clicked, toggling nav links');
            navLinks.classList.toggle('active');
        });
    }

    // Login form submission
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('Login form submitted');
            const role = e.submitter.getAttribute('data-role');
            const email = loginForm.querySelector('input[type="email"]').value;
            const password = loginForm.querySelector('input[name="password"]').value;

            try {
                const response = await fetch('backend/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&role=${encodeURIComponent(role)}`
                });
                console.log('Login response status:', response.status);
                const data = await response.json();
                console.log('Login response data:', data);
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || data.error);
                }
            } catch (error) {
                console.error('Login form submission error:', error);
                alert('An error occurred. Please try again.');
            }
        });
    } else {
        console.error('Login form not found in DOM');
    }

    // Register form submission
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('Register form submitted');
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
                console.log('Register response status:', response.status);
                const data = await response.json();
                console.log('Register response data:', data);
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || data.error);
                }
            } catch (error) {
                console.error('Register form submission error:', error);
                alert('An error occurred. Please try again.');
            }
        });
    } else {
        console.error('Register form not found in DOM');
    }

    // Forgot password form submission
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('Forgot password form submitted');
            const email = forgotPasswordForm.querySelector('input[name="email"]').value;
            console.log('Submitting forgot password form with email:', email);
            const fetchUrl = 'backend/forgot_password.php';
            console.log('Fetching URL:', fetchUrl);

            try {
                const response = await fetch(fetchUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `email=${encodeURIComponent(email)}`
                });
                console.log('Forgot password response status:', response.status);
                console.log('Forgot password response ok:', response.ok);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                const data = await response.json();
                console.log('Forgot password response data:', data);
                alert(data.message);
                if (data.success) {
                    forgotPasswordForm.reset();
                    console.log('Switching to login tab after successful forgot password submission');
                    document.querySelector('.tab-link[data-tab="login"]').click();
                }
            } catch (error) {
                console.error('Forgot password form submission error:', error.message);
                console.error('Error stack:', error.stack);
                alert('An error occurred while submitting the form: ' + error.message + '. Please check the console for more details.');
            }
        });
    } else {
        console.error('Forgot password form not found in DOM');
    }

    // Contact form submission (placeholder)
    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();
            console.log('Contact form submitted');
            alert('Contact form submission will be implemented soon!');
            contactForm.reset();
        });
    } else {
        console.log('Contact form not found in DOM');
    }
});