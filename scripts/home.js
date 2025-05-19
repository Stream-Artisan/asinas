  document.addEventListener("DOMContentLoaded", function () {
        // Initialize AOS
        AOS.init({ duration: 800, once: true });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
          anchor.addEventListener("click", function (e) {
            e.preventDefault();
            const href = this.getAttribute("href");
            if (href !== "#")
              document
                .querySelector(href)
                .scrollIntoView({ behavior: "smooth" });
          });
        });

        // Back to top button
        const backToTop = document.querySelector(".back-to-top");
        window.addEventListener("scroll", () =>
          backToTop.classList.toggle("visible", window.scrollY > 300)
        );
        backToTop.addEventListener("click", () =>
          window.scrollTo({ top: 0, behavior: "smooth" })
        );

        // Form input animation
        document.querySelectorAll(".form-control").forEach((input) => {
          input.addEventListener("input", () =>
            input.classList.toggle("filled", input.value !== "")
          );
        });

        // Add education row
        document
          .getElementById("addEducationRow")
          ?.addEventListener("click", () => {
            const tbody = document.querySelector(".table tbody");
            const rowCount = tbody.children.length;
            const newRow = document.createElement("tr");
            newRow.innerHTML = `
          <td><input type="text" class="form-control" name="education[${rowCount}][exam_level]" placeholder="e.g., Graduation" /></td>
          <td><input type="text" class="form-control" name="education[${rowCount}][board]" placeholder="e.g., University" /></td>
          <td><input type="number" class="form-control" name="education[${rowCount}][year]" placeholder="e.g., 2023" /></td>
          <td><input type="text" class="form-control" name="education[${rowCount}][marks]" placeholder="e.g., 3.5/4.0" /></td>
          <td><input type="text" class="form-control" name="education[${rowCount}][grade]" placeholder="e.g., A" /></td>
        `;
            tbody.appendChild(newRow);
          });

        // Show/hide medical training details
        document
          .getElementById("medicalTraining")
          ?.addEventListener("change", (e) => {
            document.getElementById("medicalTrainingDetails").style.display =
              e.target.value === "Yes" ? "block" : "none";
          });

        // Handle form submissions
        const handleFormSubmission = async (form) => {
          const formData = new FormData(form);
          try {
            const response = await fetch("send_email.php", {
              method: "POST",
              body: formData,
            });
            const result = await response.json();
            if (result.success) {
              alert(result.message);
              form.reset();
            } else {
              alert(result.message);
            }
          } catch (error) {
            alert("An error occurred. Please try again.");
            console.error(error);
          }
        };

        document
          .getElementById("contactForm")
          .addEventListener("submit", (e) => {
            e.preventDefault();
            handleFormSubmission(e.target);
          });

        // Highlight active nav link on scroll
        const sections = document.querySelectorAll("section");
        const navLinks = document.querySelectorAll(".navbar-nav .nav-link");
        const updateActiveNavLink = () => {
          let current = "";
          sections.forEach((section) => {
            if (window.scrollY >= section.offsetTop - 100)
              current = section.getAttribute("id");
          });
          navLinks.forEach((link) => {
            link.classList.remove("active");
            if (link.getAttribute("href").includes(current))
              link.classList.add("active");
          });
        };
        window.addEventListener("scroll", updateActiveNavLink);
        window.addEventListener("load", () => {
          navLinks.forEach((link) => link.classList.remove("active"));
          updateActiveNavLink();
        });

        // Image popup functionality
        const popup = document.getElementById("popup");
        const popupClose = document.getElementById("popup-close");
        const popupTrigger = document.getElementById("popupTrigger");

        // Show popup on page load
        popup.style.display = "flex";

        // Close popup on click of X button
        popupClose.addEventListener("click", () => {
          popup.style.display = "none";
        });

        // Close popup on click outside
        popup.addEventListener("click", (e) => {
          if (e.target === popup) popup.style.display = "none";
        });

        // Reopen popup when clicking the logo
        popupTrigger.addEventListener("click", () => {
          popup.style.display = "flex";
        });

        // Auto-close after 10 seconds
        setTimeout(() => {
          popup.style.display = "none";
        }, 10000);
      });