// Zoekfunctie voor de tabel in assign_rights.html.twig
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('table tr'); // Selecteer alle rijen in de tabel

    searchInput.addEventListener('input', function () {
        const filter = this.value.toLowerCase();
        let noMatch = true;

        tableRows.forEach(function (row) {
            const nameCell = row.querySelector('td:first-child'); // Selecteer de eerste kolom (Naam)
            if (nameCell) {
                const name = nameCell.textContent.toLowerCase();
                if (name.includes(filter)) {
                    row.style.display = ''; // Toon de rij
                    noMatch = false;
                } else {
                    row.style.display = 'none'; // Verberg de rij
                }
            }
        });

        // Optioneel: Toon een melding als er geen resultaten zijn
        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
            noResultsRow.style.display = noMatch ? '' : 'none';
        }
    });
});