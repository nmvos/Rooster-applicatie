document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');

    searchInput.addEventListener('input', function () {
        const filter = this.value.toLowerCase();
        const userRows = document.querySelectorAll('.profile-item');

        userRows.forEach(row => {
            const username = row.getAttribute('data-medewerker')?.toLowerCase() || '';

            if (username.includes(filter)) {
                row.style.display = 'table-row'; // Correcte weergave voor <tr>
            } else {
                row.style.display = 'none'; // Verberg rijen die niet matchen
            }
        });
    });
});
