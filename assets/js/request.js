document.addEventListener("DOMContentLoaded", function () {
    const requestForms = document.querySelectorAll("form");

    requestForms.forEach(form => {
        form.addEventListener("submit", function (e) {
            const requiredInputs = form.querySelectorAll("[required]");
            let valid = true;

            requiredInputs.forEach(input => {
                if (input.value.trim() === "") {
                    valid = false;
                    input.style.border = "1px solid red";
                } else {
                    input.style.border = "";
                }
            });

            if (!valid) {
                e.preventDefault();
                alert("Please fill all required fields.");
            }
        });
    });
});