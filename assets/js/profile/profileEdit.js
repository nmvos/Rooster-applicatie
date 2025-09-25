document.addEventListener('DOMContentLoaded', function () {
    const editButtons = document.querySelectorAll('.edit-btn');
    const editOverlay = document.querySelector('.edit-overlay');
    const editDropdown = document.querySelector('.edit-dropdown');
    const userIdInput = document.getElementById('userId');
    const userNameInput = document.getElementById('userName');
    const profileNameInput = document.getElementById('profileName');
    const departmentSelect = document.getElementById('department');
    const editForm = document.getElementById('editForm');

    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            const profileName = this.getAttribute('data-profile-name');
            const department = this.getAttribute('data-department');

            userIdInput.value = userId;
            userNameInput.value = userName;
            profileNameInput.value = profileName;
            departmentSelect.value = department;

            editOverlay.style.display = 'block';
            editDropdown.style.display = 'flex';

            // Update form actions with the correct user ID
            document.getElementById('resetPasswordForm').action = `/profile/${userId}/reset-password`;
            document.getElementById('deleteForm').action = `/profile/${userId}/delete`;
        });
    });

    
    function closeOverlay() {
        editOverlay.style.display = 'none';
        editDropdown.style.display = 'none';
    }
    document.querySelector('.edit-close-button').addEventListener('click', closeOverlay);

    editOverlay.addEventListener('click', function (event) {
        event.stopPropagation(); // Voorkom dat het klikgedrag opnieuw wordt getriggerd
        closeOverlay();
    }); 
    editForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(editForm);
        const userId = userIdInput.value;

        const oldProfileName = profileNameInput.getAttribute('data-old-value') || profileNameInput.value;
        const profileRow = document.querySelector(`.profile-item[data-medewerker="${oldProfileName}"]`);

        fetch(`/profile/${userId}/update`, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data); // Debugging line
            if (data.success) {
                // Update the profile information in the table
                if (profileRow) {
                    profileRow.querySelector('.profile-info a').textContent = profileNameInput.value;
                    profileRow.querySelector('.profile-info a').href = `/profile/${userId}`;
                    profileRow.querySelector('td:nth-child(2)').textContent = departmentSelect.value;
                    // Update data-medewerker attribuut naar de nieuwe naam
                    profileRow.setAttribute('data-medewerker', profileNameInput.value);
                }

                // Hide the edit form
                editOverlay.style.display = 'none';
                editDropdown.style.display = 'none';
            } else {
                console.error('Error data:', data); // Debugging line
                alert('Er is een fout opgetreden bij het opslaan van de wijzigingen.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Er is een fout opgetreden bij het opslaan van de wijzigingen.');
        });
    });

    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            profileNameInput.setAttribute('data-old-value', profileNameInput.value);
        });
    });
});
