// --- Contact Form Handling ---
const contactForm = document.querySelector('body.contact form');
if (contactForm) {
  contactForm.addEventListener('submit', function (e) {
    e.preventDefault();

    const name = contactForm.querySelector('input[type="text"]').value.trim();
    const email = contactForm.querySelector('input[type="email"]').value.trim();
    const message = contactForm.querySelector('textarea').value.trim();

    if (!name || !email || !message) {
      alert('Please fill in all fields.');
      return;
    }

    alert(`Thank you, ${name}! Your message has been sent.`);
    contactForm.reset();
  });
}

// --- Login Form Handling ---
const loginForm = document.querySelector('body.login form');
if (loginForm) {
  loginForm.addEventListener('submit', function (e) {
    e.preventDefault();

    const email = loginForm.querySelector('input[type="email"]').value.trim();
    const password = loginForm.querySelector('input[type="password"]').value;

    // Minimum 8 characters, at least 1 uppercase, 1 lowercase, and 1 digit
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

    if (!email || !password) {
      alert('Please fill in both email and password.');
      return;
    }

    if (!passwordRegex.test(password)) {
      alert('Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.');
      return;
    }

    alert('Login successful! Redirecting to dashboard...');
    window.location.href = 'dashboard.html';
  });
}

// --- Register Form Handling ---
const registerForm = document.querySelector('body.register form');
if (registerForm) {
  registerForm.addEventListener('submit', function (e) {
    e.preventDefault();

    const name = registerForm.querySelector('input[type="text"]').value.trim();
    const email = registerForm.querySelector('input[type="email"]').value.trim();
    const password = registerForm.querySelector('input[type="password"]').value;

    // Minimum 8 characters, at least 1 uppercase, 1 lowercase, and 1 digit
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

    if (!name || !email || !password) {
      alert('Please fill in all fields.');
      return;
    }

    if (!passwordRegex.test(password)) {
      alert('Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.');
      return;
    }

    // Confirmation dialog with OK (Confirm) and Cancel buttons
    const userConfirmed = confirm('Are you sure you want to create this account?');

    if (userConfirmed) {
      alert(`Account created successfully for ${name}! Redirecting to login...`);
      window.location.href = 'login.html';
    }
  });
}