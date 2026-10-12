document.addEventListener('DOMContentLoaded', () => {
  // ==========================================
  // 1. GLOBAL COMPONENTS
  // ==========================================

  // --- Dark/Light Theme Switcher ---
  const themeToggleBtn = document.getElementById('theme-toggle');
  const savedTheme = localStorage.getItem('theme') || 'light';
  if (savedTheme === 'dark') {
    document.body.classList.add('dark-theme');
  }

  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
      document.body.classList.toggle('dark-theme');
      const isDark = document.body.classList.contains('dark-theme');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });
  }

  // --- Hamburger Menu ---
  const hamburgerBtn = document.getElementById('hamburger-btn');
  const navMenu = document.querySelector('header nav');

  if (hamburgerBtn && navMenu) {
    hamburgerBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      navMenu.classList.toggle('nav-active');
    });
  }

  // --- Notification Banner Dismiss ---
  const bannerCloseBtn = document.getElementById('close-banner');
  const promoBanner = document.getElementById('promo-banner');
  if (bannerCloseBtn && promoBanner) {
    bannerCloseBtn.addEventListener('click', () => {
      promoBanner.style.display = 'none';
    });
  }

  // --- Modal Popup Controls ---
  const openModalBtn = document.getElementById('open-modal-btn');
  const closeModalBtn = document.getElementById('close-modal-btn');
  const customModal = document.getElementById('custom-modal');

  if (openModalBtn && customModal) {
    openModalBtn.addEventListener('click', () => {
      customModal.style.display = 'flex';
    });
  }

  if (closeModalBtn && customModal) {
    closeModalBtn.addEventListener('click', () => {
      customModal.style.display = 'none';
    });
  }

  window.addEventListener('click', (e) => {
    if (e.target === customModal) {
      customModal.style.display = 'none';
    }
  });

  // --- Image / Content Slider ---
  const slides = document.querySelectorAll('.slide');
  const prevBtn = document.getElementById('prev-slide');
  const nextBtn = document.getElementById('next-slide');
  let currentSlide = 0;

  function showSlide(index) {
    if (!slides.length) return;
    slides.forEach((slide) => slide.classList.remove('active-slide'));
    currentSlide = (index + slides.length) % slides.length;
    slides[currentSlide].classList.add('active-slide');
  }

  if (nextBtn && prevBtn && slides.length > 0) {
    nextBtn.addEventListener('click', () => showSlide(currentSlide + 1));
    prevBtn.addEventListener('click', () => showSlide(currentSlide - 1));
  }

  // ==========================================
  // 2. FORM VALIDATIONS
  // ==========================================

  const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!\%*?&]).{8,}$/;
  const passwordErrorMessage =
    'Password must contain at least 8 characters, one uppercase, one lowercase, one number, and one special character.';

  // --- Contact Form ---
  const contactForm = document.querySelector('body.contact form');
  if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
      //e.preventDefault();
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

  // --- Login Form ---
  const loginForm = document.querySelector('body.login form');
  if (loginForm) {
    loginForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const email = loginForm.querySelector('input[type="email"]').value.trim();
      const password = loginForm.querySelector('input[type="password"]').value;

      if (!email || !password) {
        alert('Please fill in both email and password.');
        return;
      }
      if (!passwordRegex.test(password)) {
        alert(passwordErrorMessage);
        return;
      }
      alert('Login successful! Redirecting to dashboard...');
      window.location.href = 'dashboard.html';
    });
  }


// --- Student Registration Form ---
const registrationForm = document.getElementById('registrationForm');

if (registrationForm) {
  registrationForm.addEventListener('submit', function (event) {
    const name = document.getElementById('name').value.trim();
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const mobile = document.getElementById('mobile').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const course = document.getElementById('course').value;
    const year = document.getElementById('year').value;
    const gender = document.querySelector('input[name="gender"]:checked');
    const terms = document.getElementById('terms').checked;

    const nameRegex = /^[A-Za-z ]{2,50}$/;
    const usernameRegex = /^[A-Za-z0-9_]{3,50}$/;
    const emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
    const mobileRegex = /^[6-9]\d{9}$/;

    document.querySelectorAll('[id$="Error"]').forEach((span) => {
      span.textContent = '';
    });

    let isValid = true;

    if (!nameRegex.test(name)) {
      document.getElementById('nameError').textContent =
        'Enter a valid name using 2-50 letters and spaces.';
      isValid = false;
    }

    if (!usernameRegex.test(username)) {
      document.getElementById('usernameError').textContent =
        'Username must be 3-50 characters using letters, numbers, or underscores.';
      isValid = false;
    }

    if (!emailRegex.test(email)) {
      document.getElementById('emailError').textContent =
        'Please enter a valid email address.';
      isValid = false;
    }

    if (!mobileRegex.test(mobile)) {
      document.getElementById('mobileError').textContent =
        'Enter a valid 10-digit mobile number starting with 6-9.';
      isValid = false;
    }

    if (!passwordRegex.test(password)) {
      document.getElementById('passwordError').textContent =
        passwordErrorMessage;
      isValid = false;
    }

    if (password !== confirmPassword || confirmPassword === '') {
      document.getElementById('confirmPasswordError').textContent =
        'Passwords do not match.';
      isValid = false;
    }

    if (!course) {
      document.getElementById('courseError').textContent =
        'Please select a course.';
      isValid = false;
    }

    if (!year) {
      document.getElementById('yearError').textContent =
        'Please select your academic year.';
      isValid = false;
    }

    if (!gender) {
      document.getElementById('genderError').textContent =
        'Please select your gender.';
      isValid = false;
    }

    if (!terms) {
      document.getElementById('termsError').textContent =
        'You must accept the terms and conditions.';
      isValid = false;
    }

    if (!isValid) {
      event.preventDefault();
      return;
    }

    // IMPORTANT:
    // Do not prevent the default submission when validation passes.
    // The browser submits the form to register.php using POST.
  });
}



  // ==========================================
  // 3. DYNAMIC DATA HANDLING (JSON + FETCH)
  // ==========================================

  // --- A. EVENTS PAGE (events.html) ---
  const eventsContainer = document.getElementById('eventsContainer');
  if (eventsContainer) {
    const state = {
      allEvents: [],
      searchQuery: '',
      selectedCategory: 'All',
      sortBy: 'date-asc',
      currentPage: 1,
      itemsPerPage: 2,
    };

    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const sortSelect = document.getElementById('sortSelect');
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    const pageInfo = document.getElementById('pageInfo');

    async function fetchEvents() {
      eventsContainer.innerHTML = `<p style="text-align: center;">Loading events...</p>`;
      try {
        const response = await fetch('events.json');
        if (!response.ok) throw new Error(`HTTP ${response.status}: Failed to load`);
        state.allEvents = await response.json();
        renderApp();
      } catch (error) {
        eventsContainer.innerHTML = `<p style="color: red; text-align: center;">Failed to load data: ${error.message}</p>`;
      }
    }

    function getProcessedEvents() {
      let result = state.allEvents.filter((item) => {
        const q = state.searchQuery.toLowerCase().trim();
        const matchesQuery =
          item.title.toLowerCase().includes(q) ||
          (item.description && item.description.toLowerCase().includes(q));
        const matchesCategory =
          state.selectedCategory === 'All' || item.category === state.selectedCategory;
        return matchesQuery && matchesCategory;
      });

      return [...result].sort((a, b) => {
        if (state.sortBy === 'date-asc') return new Date(a.date) - new Date(b.date);
        if (state.sortBy === 'date-desc') return new Date(b.date) - new Date(a.date);
        if (state.sortBy === 'title-asc') return a.title.localeCompare(b.title);
        if (state.sortBy === 'title-desc') return b.title.localeCompare(a.title);
        return 0;
      });
    }

    function renderApp() {
      const processed = getProcessedEvents();
      const totalPages = Math.ceil(processed.length / state.itemsPerPage) || 1;

      if (state.currentPage > totalPages) state.currentPage = totalPages;
      if (state.currentPage < 1) state.currentPage = 1;

      const startIndex = (state.currentPage - 1) * state.itemsPerPage;
      const paginatedItems = processed.slice(startIndex, startIndex + state.itemsPerPage);

      if (paginatedItems.length === 0) {
        eventsContainer.innerHTML = `<p style="text-align: center; margin: 20px 0;">No matching events found.</p>`;
      } else {
        eventsContainer.innerHTML = paginatedItems
          .map(
            (item) => `
          <article style="background: var(--surface); margin: 15px 0; padding: 20px; border-left: 5px solid var(--primary-light); box-shadow: var(--shadow);">
            <h3>${item.title}</h3>
            <p><strong>Category:</strong> ${item.category}</p>
            <p><strong>Date:</strong> ${item.date}</p>
            <p>${item.description || 'No description provided.'}</p>
          </article>
        `
          )
          .join('');
      }

      if (pageInfo) pageInfo.textContent = `Page ${state.currentPage} of ${totalPages}`;
      if (prevPageBtn) prevPageBtn.disabled = state.currentPage === 1;
      if (nextPageBtn) nextPageBtn.disabled = state.currentPage === totalPages;
    }

    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        state.searchQuery = e.target.value;
        state.currentPage = 1;
        renderApp();
      });
    }

    if (categoryFilter) {
      categoryFilter.addEventListener('change', (e) => {
        state.selectedCategory = e.target.value;
        state.currentPage = 1;
        renderApp();
      });
    }

    if (sortSelect) {
      sortSelect.addEventListener('change', (e) => {
        state.sortBy = e.target.value;
        renderApp();
      });
    }

    if (prevPageBtn) {
      prevPageBtn.addEventListener('click', () => {
        if (state.currentPage > 1) {
          state.currentPage--;
          renderApp();
        }
      });
    }

    if (nextPageBtn) {
      nextPageBtn.addEventListener('click', () => {
        const totalPages = Math.ceil(getProcessedEvents().length / state.itemsPerPage);
        if (state.currentPage < totalPages) {
          state.currentPage++;
          renderApp();
        }
      });
    }

    fetchEvents();
  }

// --- B. PROFILE PAGE (profile.html) ---
  if (document.body.classList.contains('profile')) {
    async function fetchProfile() {
      try {
        const response = await fetch('profile.json');
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();

        const nameEl = document.getElementById('profile-name');
        const idEl = document.getElementById('profile-id');
        const emailEl = document.getElementById('profile-email');
        const courseEl = document.getElementById('profile-course');
        const yearEl = document.getElementById('profile-year');

        if (nameEl) nameEl.textContent = data.name || 'N/A';
        if (idEl) idEl.textContent = data.studentId || 'N/A';
        if (emailEl) emailEl.textContent = data.email || 'N/A';
        if (courseEl) courseEl.textContent = data.course || 'N/A';
        if (yearEl) yearEl.textContent = data.year || 'N/A';
      } catch (err) {
        console.error('Failed to load profile data:', err);
        const container = document.getElementById('profile-container');
        if (container) {
          container.innerHTML = `<p style="color: red;">Failed to load profile: ${err.message}</p>`;
        }
      }
    }
    fetchProfile();
  }

  // --- C. FAQ PAGE (faq.html) ---
  if (document.body.classList.contains('faq')) {
    const faqMain = document.querySelector('body.faq main');

    async function fetchFaqs() {
      try {
        const response = await fetch('faq.json');
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const faqs = await response.json();

        // Render FAQ items dynamically
        faqMain.innerHTML = faqs
          .map(
            (faq) => `
          <div class="faq-item">
            <button class="faq-question">${faq.question}</button>
            <div class="faq-answer" style="display: none;">${faq.answer}</div>
          </div>
        `
          )
          .join('');

        // Rebind click toggles to newly injected FAQ questions
        faqMain.querySelectorAll('.faq-question').forEach((button) => {
          button.addEventListener('click', () => {
            const answer = button.nextElementSibling;
            button.classList.toggle('active');
            if (answer) {
              answer.style.display = answer.style.display === 'block' ? 'none' : 'block';
            }
          });
        });
      } catch (err) {
        console.error('Failed to load FAQs:', err);
      }
    }
    fetchFaqs();
  }

// --- D. HOME PAGE (index.html) ---
  if (document.body.classList.contains('home')) {
    const noticesContainer = document.getElementById('notices-container');

    async function fetchNotices() {
      if (!noticesContainer) {
        console.error('Target #notices-container element not found in DOM.');
        return;
      }

      try {
        console.log('Attempting to fetch notices.json...');
        const response = await fetch('notices.json');

        if (!response.ok) {
          throw new Error(`HTTP Error ${response.status}: notices.json not found.`);
        }

        const items = await response.json();
        console.log('Notices data successfully loaded:', items);

        if (!items || items.length === 0) {
          noticesContainer.innerHTML = '<p>No current notices available.</p>';
          return;
        }

        // Render title and date
        noticesContainer.innerHTML = items
          .map(
            (item) => `
            <div style="background: #f4f4f4; padding: 10px 14px; margin: 8px 0; border-left: 4px solid #0056b3; border-radius: 4px;">
              <strong>${item.title}</strong>
              <span style="color: #666; font-size: 0.85rem; margin-left: 8px;">(${item.date})</span>
            </div>
          `
          )
          .join('');
      } catch (err) {
        console.error('Error fetching notices:', err);
        noticesContainer.innerHTML = `<p style="color: red; font-size: 0.9rem;">Unable to load updates (${err.message})</p>`;
      }
    }

    fetchNotices();
  }
});
