document.addEventListener('DOMContentLoaded', function () {
    const sortNamesHeader = document.getElementById('sortNames');
    let sortDirection = false;

    if (sortNamesHeader) {
        sortNamesHeader.addEventListener('click', function () {
            sortDirection = !sortDirection;
            sortTableByNames(sortDirection);
        });
    } else {
        console.error('Element met ID "sortNames" niet gevonden.');
        return;
    }

    function sortTableByNames(ascending) {
        console.log('Sorteren op naam gestart. Oplopend:', ascending); // Debugging

        // Dynamisch detecteren welke tabel aanwezig is
        const tableBody = document.querySelector('#attendanceDayTableBody') || 
                          document.querySelector('#profileTableBody');
        if (!tableBody) {
            console.error('Geen geldige tabel gevonden.');
            return;
        }

        console.log('TableBody gevonden:', tableBody.id); // Debugging

        // Selecteer alle rijen in de tabel
        const rows = Array.from(tableBody.querySelectorAll('tr'));
        console.log('Aantal rijen gevonden:', rows.length); // Debugging

        // Sorteer de rijen op basis van de naam in de eerste kolom
        rows.sort((a, b) => {
            const nameA = a.querySelector('td:first-child a')?.textContent.trim().toLowerCase() || 
                          a.querySelector('td:first-child')?.textContent.trim().toLowerCase() || '';
            const nameB = b.querySelector('td:first-child a')?.textContent.trim().toLowerCase() || 
                          b.querySelector('td:first-child')?.textContent.trim().toLowerCase() || '';

            return ascending ? nameA.localeCompare(nameB) : nameB.localeCompare(nameA);
        });

        // Voeg de gesorteerde rijen opnieuw toe aan de tabel
        rows.forEach(row => tableBody.appendChild(row));
    }
});
