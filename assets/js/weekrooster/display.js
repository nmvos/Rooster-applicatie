//all functions to handle the display of aanwezigheidsregistratie (weekrooster)
document.addEventListener('DOMContentLoaded', function() {
    let weekShown = false;
    let time = new Date();
    let hours = time.getHours();
    let minutes = time.getMinutes();
    let attendedShown = ((hours == 12 && minutes >= 30) || hours > 12);
    const searchInput = document.querySelector('#searchInput'); 
    let userRow = document.querySelectorAll('.userRow');
    let fullRow = document.querySelectorAll('.fullRow');

    if (attendedShown) {
        document.getElementById('attendedShownToggle').innerHTML = 'Alle ingeroosterd'
    } else {
        document.getElementById('attendedShownToggle').innerHTML = 'Niet afgevinkt' 
    }

    function setStandardDisplay(userRow) {
        if (attendedShown){
            userRow.forEach(function(row){
                let morning = row.getAttribute('data-morning');
                let afternoon = row.getAttribute('data-afternoon');
                let different = row.getAttribute('data-different');
                let morning_attended = row.getAttribute('data-morning-attended');
                let afternoon_attended = row.getAttribute('data-afternoon-attended');
                let different_attended = row.getAttribute('data-different-attended');
                if (morning_attended || afternoon_attended || different_attended){
                    row.style.display = 'none';
                } else {
                    if (different || afternoon || morning || weekShown){
                        row.style.display = "table-row";
                    } else {
                        row.style.display = 'none';
                    }
                }
            })
        } else {
            userRow.forEach(function(row){
                let morning = row.getAttribute('data-morning');
                let afternoon = row.getAttribute('data-afternoon');
                let different = row.getAttribute('data-different');
                if (different || afternoon || morning || weekShown){
                    row.style.display = "table-row";
                } else {
                    row.style.display = 'none';
                }
            })
        }
    }

    document.getElementById('attendedShownToggle').addEventListener('click', function(){
        attendedShown = !attendedShown;
        if (attendedShown) {
            document.getElementById('attendedShownToggle').innerHTML = 'Alle ingeroosterd'
        } else {
            document.getElementById('attendedShownToggle').innerHTML = 'Niet afgevinkt' 
        }
        
        
        setStandardDisplay(userRow);
    })

    function setFullDisplay(fullRow) {
        fullRow.forEach(function(row){
            row.style.display = "table-row"; 
        })
    }

    overzichtButton = document.getElementById('overzichtButton');
    if (overzichtButton){
        overzichtButton.addEventListener('click',function(){
            let dayForm = document.getElementById("attendanceDayForm");
            let weekForm = document.getElementById("attendanceWeekForm");
            dayForm.classList.toggle("hidden")
            weekForm.classList.toggle("hidden")
            if (weekShown){
                document.getElementById('overzichtButton').innerHTML = 'Weekoverzicht';
                weekShown = false;
            } else {
                document.getElementById('overzichtButton').innerHTML = 'Dagoverzicht';
                weekShown = true;
            }
        }) 
    }

    setStandardDisplay(userRow);

    searchInput.addEventListener('input', function() {
        let filter = this.value.toLowerCase();
        let noMatch = true

        if (!filter){
            setStandardDisplay(userRow);
            setFullDisplay(fullRow);
        } else {
            fullRow.forEach(function(row){
               let username = row.getAttribute('data-medewerker').toLowerCase();
                if (username.includes(filter)){
                    row.style.display = 'table-row';
                    noMatch = false;
                } else {
                    row.style.display = 'none';
                } 
            });

            userRow.forEach(function(row){
                let username = row.getAttribute('data-medewerker').toLowerCase();
                if (username.includes(filter)){
                    row.style.display = 'table-row';
                    noMatch = false;
                } else {
                    row.style.display = 'none';
                }
            });
        }
    });
})

// Added fucntions
function initializeEventListeners(week, year, employees, settingsColors) {
    document.querySelectorAll('.selector-attendence').forEach(function (input) {
        input.addEventListener('change', function () {
            const userId = this.getAttribute('data-user-id');
            const day = this.getAttribute('data-day');
            const daySlot = this.getAttribute('data-day-slot');
            const value = this.value;

            const payload = {
                userId: userId,
                year: year,
                week: week,
                day: day,
                daySlot: daySlot,
                value: value
            };

            fetch('/update_attendence', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                const newColor = settingsColors[value] || '#FFFFFF';
                const query = `.${day}-${daySlot}-${userId}`;
                document.querySelectorAll(query).forEach(function (element) {
                    element.style.backgroundColor = newColor;
                });
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });

    document.querySelectorAll('.selector-no-schedule').forEach(function (input) {
        input.addEventListener('change', function () {
            const userId = this.getAttribute('data-user-id');
            const day = this.getAttribute('data-day');
            const daySlot = this.value;
            const value = this.getAttribute('data-attendence');

            const payload = {
                userId: userId,
                year: year,
                week: week,
                day: day,
                daySlot: daySlot,
                value: value
            };

            fetch('/update_no_schedule', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });

    const searchInput = document.getElementById('searchInput');
    const autocompleteList = document.getElementById('autocompleteList');

    searchInput.addEventListener('input', function () {
        let inputText = this.value.trim();
        let selectedNames = inputText.length > 0 ? inputText.split(',').map(name => name.trim()) : [];
        let query = selectedNames.length > 0 ? selectedNames[selectedNames.length - 1].toLowerCase() : '';

        autocompleteList.innerHTML = '';

        if (query.length > 0) {
            const filteredEmployees = employees.filter(employee =>
                employee.toLowerCase().startsWith(query)
            );

            if (filteredEmployees.length === 0) return;

            filteredEmployees.forEach(employee => {
                const listItem = document.createElement('li');
                listItem.classList.add('list-group-item');
                listItem.textContent = employee;

                listItem.addEventListener('click', function () {
                    selectedNames[selectedNames.length - 1] = employee;
                    searchInput.value = selectedNames.join(', ') + ', ';
                    autocompleteList.innerHTML = '';
                    updateTable(selectedNames);
                });

                autocompleteList.appendChild(listItem);
            });
        }
    });

    document.addEventListener('click', function (event) {
        if (!searchInput.contains(event.target) && !autocompleteList.contains(event.target)) {
            autocompleteList.innerHTML = '';
        }
    });
}

function updateTable(selectedNames) {
    const userRows = document.querySelectorAll('.userRow, .fullRow'); 
    userRows.forEach(row => {
        const username = row.getAttribute('data-medewerker').trim().toLowerCase();
        const isVisible = selectedNames.some(name => {
            const trimmedName = name.trim().toLowerCase();
            return username === trimmedName; 
        });
        row.style.display = isVisible ? 'table-row' : 'none';
    });
    document.querySelectorAll('.userRow, .fullRow').forEach(row => {
    });
}







