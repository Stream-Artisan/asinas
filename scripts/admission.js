 AOS.init({ duration: 1000, once: true });

    // Back to Top Button
    const backToTop = document.querySelector('.back-to-top');
    window.addEventListener('scroll', () => {
      backToTop.classList.toggle('visible', window.scrollY > 300);
    });
    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Form Section Collapse Toggle
    document.querySelectorAll('.form-section h4').forEach(header => {
      header.addEventListener('click', () => {
        header.parentElement.classList.toggle('collapsed');
      });
    });

    // Form Progress Tracking
    const form = document.getElementById('admissionForm');
    const progressBar = document.querySelector('.progress-bar');
    const inputs = form.querySelectorAll('input, select, textarea');
    const updateProgress = () => {
      const filled = Array.from(inputs).filter(input => input.value || input.files?.length).length;
      const total = inputs.length;
      const percentage = Math.round((filled / total) * 100);
      progressBar.style.width = `${percentage}%`;
      progressBar.textContent = `${percentage}%`;
      progressBar.setAttribute('aria-valuenow', percentage);
    };
    inputs.forEach(input => input.addEventListener('input', updateProgress));
    inputs.forEach(input => input.addEventListener('change', updateProgress));

    // Education Table Row Management
    document.getElementById('addEducationRow').addEventListener('click', () => {
      const tbody = document.querySelector('#educationTable tbody');
      const rowCount = tbody.children.length;
      const newRow = document.createElement('tr');
      newRow.innerHTML = `
        <td><input type="text" class="form-control" name="education[${rowCount}][exam_level]" placeholder="e.g., Graduation"></td>
        <td><input type="text" class="form-control" name="education[${rowCount}][board]" placeholder="e.g., University"></td>
        <td><input type="number" class="form-control" name="education[${rowCount}][year]" placeholder="e.g., 2023"></td>
        <td><input type="text" class="form-control" name="education[${rowCount}][marks]" placeholder="e.g., 3.5/4.0"></td>
        <td><input type="text" class="form-control" name="education[${rowCount}][grade]" placeholder="e.g., A"></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm remove-row">Remove</button></td>
      `;
      tbody.appendChild(newRow);
      updateProgress();
    });

    document.addEventListener('click', (e) => {
      if (e.target.classList.contains('remove-row')) {
        if (document.querySelector('#educationTable tbody').children.length > 1) {
          e.target.closest('tr').remove();
          updateProgress();
        }
      }
    });

    // Medical Training Details Toggle
    document.getElementById('medicalTraining').addEventListener('change', (e) => {
      const details = document.getElementById('medicalTrainingDetails');
      details.style.display = e.target.value === 'Yes' ? 'block' : 'none';
    });

    // Form Submission
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = document.getElementById('submitAdmission');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Submitting...';

      const formData = new FormData(form);
      try {
        const response = await fetch('send_email.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        if (result.success) {
          form.classList.add('animate__animated', 'animate__fadeOut');
          setTimeout(() => {
            alert(result.message);
            form.reset();
            document.querySelector('#educationTable tbody').innerHTML = `
              <tr>
                <td><input type="text" class="form-control" name="education[0][exam_level]" placeholder="e.g., Matric" required></td>
                <td><input type="text" class="form-control" name="education[0][board]" placeholder="e.g., Karachi Board" required></td>
                <td><input type="number" class="form-control" name="education[0][year]" placeholder="e.g., 2020" required></td>
                <td><input type="text" class="form-control" name="education[0][marks]" placeholder="e.g., 850/1000" required></td>
                <td><input type="text" class="form-control" name="education[0][grade]" placeholder="e.g., A+" required></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm remove-row">Remove</button></td>
              </tr>
              <tr>
                <td><input type="text" class="form-control" name="education[1][exam_level]" placeholder="e.g., FSC Pre-Medical"></td>
                <td><input type="text" class="form-control" name="education[1][board]" placeholder="e.g., Karachi Board"></td>
                <td><input type="number" class="form-control" name="education[1][year]" placeholder="e.g., 2022"></td>
                <td><input type="text" class="form-control" name="education[1][marks]" placeholder="e.g., 900/1100"></td>
                <td><input type="text" class="form-control" name="education[1][grade]" placeholder="e.g., A"></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm remove-row">Remove</button></td>
              </tr>
            `;
            document.getElementById('medicalTrainingDetails').style.display = 'none';
            form.classList.remove('animate__fadeOut');
            form.classList.add('animate__fadeIn');
            updateProgress();
          }, 1000);
        } else {
          alert(result.message);
        }
      } catch (error) {
        alert('An error occurred. Please try again.');
        console.error(error);
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Submit Application';
      }
    });

    // Input Validation Feedback
    inputs.forEach(input => {
      input.addEventListener('input', () => {
        if (input.checkValidity()) {
          input.classList.remove('is-invalid');
          input.classList.add('is-valid');
        } else {
          input.classList.remove('is-valid');
          input.classList.add('is-invalid');
        }
      });
    });