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

        // Navbar active link on scroll
        const sections = document.querySelectorAll("section[id]");
        const navLinks = document.querySelectorAll(".navbar-nav .nav-link");
        function updateActiveNavLink() {
          let current = "";
          sections.forEach((section) => {
            if (window.scrollY >= section.offsetTop - 120)
              current = section.getAttribute("id");
          });
          navLinks.forEach((link) => {
            link.classList.remove("active");
            if (link.getAttribute("href").includes(current))
              link.classList.add("active");
          });
        }
        window.addEventListener("scroll", updateActiveNavLink);
        window.addEventListener("load", updateActiveNavLink);

        // Centralized form validation for all forms
        document.querySelectorAll("form").forEach((form) => {
          form.addEventListener("submit", async function (e) {
            if (form.hasAttribute("novalidate")) e.preventDefault();
            let valid = true;
            form.querySelectorAll("input, textarea, select").forEach((input) => {
              if (!input.checkValidity()) {
                input.classList.add("is-invalid");
                valid = false;
              } else {
                input.classList.remove("is-invalid");
              }
            });
            if (!valid) return;
            if (form.action && form.action.includes("send_email.php")) {
              e.preventDefault();
              const formData = new FormData(form);
              try {
                const response = await fetch(form.action, {
                  method: "POST",
                  body: formData,
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) form.reset();
              } catch {
                alert("An error occurred. Please try again.");
              }
            }
          });
          form.querySelectorAll("input, textarea, select").forEach((input) => {
            input.addEventListener("input", () => {
              if (input.checkValidity()) input.classList.remove("is-invalid");
            });
          });
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

        // Remove or comment out the following block to prevent popup on logo click
        // popupTrigger.addEventListener("click", () => {
        //   popup.style.display = "flex";
        // });

        // Auto-close after 10 seconds
        setTimeout(() => {
          popup.style.display = "none";
        }, 10000);
      });
      //side carousel
      const images = document.querySelectorAll('.carousel-image');
  const prevBtn = document.querySelector('.carousel-btn.prev');
  const nextBtn = document.querySelector('.carousel-btn.next');
  const dots = document.querySelectorAll('.carousel-dot');
  let current = 0;
  let intervalId;

  function showImage(idx) {
    images.forEach((img, i) => img.style.display = i === idx ? 'block' : 'none');
    dots.forEach((dot, i) => dot.classList.toggle('active', i === idx));
  }

  function nextImage() {
    current = (current + 1) % images.length;
    showImage(current);
  }

  function prevImage() {
    current = (current - 1 + images.length) % images.length;
    showImage(current);
  }

  function startAutoSlide() {
    intervalId = setInterval(nextImage, 3000); // Change image every 3 seconds
  }

  function stopAutoSlide() {
    clearInterval(intervalId);
  }

  prevBtn.addEventListener('click', () => {
    prevImage();
    stopAutoSlide();
    startAutoSlide();
  });
  nextBtn.addEventListener('click', () => {
    nextImage();
    stopAutoSlide();
    startAutoSlide();
  });
  dots.forEach((dot, idx) => {
    dot.addEventListener('click', () => {
      current = idx;
      showImage(current);
      stopAutoSlide();
      startAutoSlide();
    });
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') {
      prevImage();
      stopAutoSlide();
      startAutoSlide();
    } else if (e.key === 'ArrowRight') {
      nextImage();
      stopAutoSlide();
      startAutoSlide();
    }
  });
  showImage(current);
  startAutoSlide();
;