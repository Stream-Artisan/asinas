 AOS.init({ duration: 1000, once: true });

      const formContainer = document.getElementById("applyFormContainer");
      const showFormBtn = document.getElementById("showApplyForm");

      showFormBtn.addEventListener("click", () => {
        formContainer.classList.toggle("hidden");
        formContainer.classList.toggle("show");
      });

      document
        .getElementById("applyForm")
        .addEventListener("submit", async (e) => {
          e.preventDefault();
          const formData = new FormData(e.target);
          try {
            const response = await fetch("send_email.php", {
              method: "POST",
              body: formData,
            });
            const result = await response.json();
            alert(result.message);
            if (result.success) {
              e.target.reset();
              formContainer.classList.add("hidden");
              formContainer.classList.remove("show");
            }
          } catch (error) {
            alert("An error occurred. Please try again later.");
          }
        });