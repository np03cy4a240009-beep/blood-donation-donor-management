// Email validation
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

if (document.getElementById('emailInput')) {
    const emailInput = document.getElementById('emailInput');
    const form = emailInput.closest('form');
    const errorMessage = document.getElementById('emailErrorMessage');

    // Validate on form submit
    if (form) {
        form.addEventListener('submit', function(e) {
            if (emailInput.value && !validateEmail(emailInput.value)) {
                e.preventDefault();
                errorMessage.style.display = 'block';
                emailInput.classList.add('input-error');
            } else {
                errorMessage.style.display = 'none';
                emailInput.classList.remove('input-error');
            }
        });
    }

    // Hide error when user types valid email
    emailInput.addEventListener('input', function() {
        if (validateEmail(this.value)) {
            errorMessage.style.display = 'none';
            this.classList.remove('input-error');
        }
    });
}

function setRole(role, el) {
    const roleInput = document.getElementById('role');
    const adminFields = document.getElementById('adminFields');
    const userFields = document.getElementById('userFields');

    if (roleInput) roleInput.value = role;

    document.querySelectorAll('.role-box').forEach(box => {
        box.classList.remove('active');
    });

    if (el) el.classList.add('active');

    if (adminFields && userFields) {
        if (role === 'admin') {
            adminFields.style.display = 'grid';
            userFields.style.display = 'none';
        } else {
            adminFields.style.display = 'none';
            userFields.style.display = 'block';
        }
    }
}