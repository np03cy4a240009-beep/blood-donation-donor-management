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