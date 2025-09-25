document.addEventListener('DOMContentLoaded', function () {
    const sortDepartmentHeader = document.getElementById('sortDepartment');
    let sortDirection = false;

    if (sortDepartmentHeader) {
        sortDepartmentHeader.addEventListener('click', function () {
            sortDirection = !sortDirection;
            sortTableByDepartment(sortDirection);
        });
    }

    function sortTableByDepartment(ascending) {
        const tableBody = document.getElementById('attendanceDayTableBody') || document.getElementById('profileTableBody'); // Dynamisch ID detecteren
        if (!tableBody) return; // Als geen van beide aanwezig is, stoppen

        const rows = Array.from(tableBody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            const departmentA = a.querySelector('td:nth-child(2)').textContent.trim().toLowerCase();
            const departmentB = b.querySelector('td:nth-child(2)').textContent.trim().toLowerCase();

            if (departmentA < departmentB) {
                return ascending ? -1 : 1;
            }
            if (departmentA > departmentB) {
                return ascending ? 1 : -1;
            }
            return 0;
        });

        rows.forEach(row => tableBody.appendChild(row));
    }
});
