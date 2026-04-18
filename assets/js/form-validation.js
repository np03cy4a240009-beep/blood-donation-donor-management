document.addEventListener("DOMContentLoaded", function () {
    const forms = document.querySelectorAll("form");

    forms.forEach(form => {
        form.addEventListener("submit", function (e) {
            let valid = true;
            const requiredFields = form.querySelectorAll("[required]");

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.border = "1px solid red";
                } else {
                    field.style.border = "1px solid #ccc";
                }
            });

            if (!valid) {
                e.preventDefault();
                alert("Please fill all required fields.");
            }
        });
    });
});