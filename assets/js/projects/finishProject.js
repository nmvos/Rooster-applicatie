// aanmaken van variabelen voor het laten zien van de afrond popup deze moeten global zijn want ze worden ook gebruikt voor de AJAX-call
let id;
let name;
let sYear;
let sWeek;
let eWeek;
let eYear;

document.addEventListener('DOMContentLoaded', function () {
    const finish_projects = document.querySelectorAll('.finishProject');
    const finishDropdown = document.querySelector('.finish-dropdown');
    const finishOverlay = document.querySelector('.finish-overlay');

    function closeFinish() {
        finishDropdown.style.display = 'none';
        finishOverlay.style.display = 'none';
        document.getElementById('finishName').innerHTML = '';
        document.getElementById('startYear').innerHTML = '';
        document.getElementById('startWeek').innerHTML= '';
        document.getElementById('endWeek').innerHTML= '';
    }

    function showFinishPop(finish_projects){
        finish_projects.forEach(function(finish_project){
            finish_project.addEventListener('click', function () {
                id = this.getAttribute('data-id');
                name = this.getAttribute('data-name');
                sYear = this.getAttribute('data-sYear');
                sWeek = this.getAttribute('data-sWeek');
                finishDropdown.style.display = 'block';
                finishOverlay.style.display = 'block';
                document.getElementById('finishName').innerHTML = name;
                document.getElementById('startYear').innerHTML = sYear;
                document.getElementById('startWeek').innerHTML= sWeek;
                document.getElementById('endWeek').innerHTML= '';
                document.getElementById('message').innerHTML = '';
                //maakt de laatste week input voor de finish pop up
                const input = document.createElement('input');
                input.type='week';
                input.id = 'endWeekInput';
                input.name = 'endWeekInput';
                document.getElementById('endWeek').appendChild(input);
                finishOverlay.style.display = 'block'; 
            })
        })
    }

    showFinishPop(finish_projects);

    finishOverlay.addEventListener('click', function(){
        closeFinish();
    })

    //Ajax to finish project and send to database
    document.querySelector('#finishProjectBtn').addEventListener('click', function(){
        //eWeek komt binnen als year-Wweeknummer bijv. 2024-W36
        eWeek = document.getElementById('endWeekInput').value;
        //controleerd of de week gegevens zijn ingevultd
        if (!eWeek){
            document.getElementById('message').innerHTML = "Vul de ontbrekende gegevens in"
        } else {
            let eWeekParts = eWeek.split('-W');
            eYear = parseInt(eWeekParts[0]);
            eWeek = parseInt(eWeekParts[1]);
            let startYear = parseInt(sYear);
            let startWeek = parseInt(sWeek);
            // controleer of eindatum na begin datum is
            if (startYear > eYear || (startYear === eYear && startWeek > eWeek)){
                document.getElementById('message').innerHTML = "De einddatum moet na de startdatum liggen";
            } else {
                document.getElementById('message').innerHTML = ""   
                const payload = {
                    id: id,
                    eYear: eYear,
                    eWeek: eWeek,
                };
                fetch(path,{
                method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token("finish_project") }}'
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => response.json())
                .then(data =>{
                    //maakt de nieuwe tabelregel aan van het project dat net afgerond is
                    const finishedTable = document.getElementById('finished_table');
                    const newRow = finishedTable.insertRow();
                    newRow.classList.add("border", "border-2", "border-end-0", "border-start-0", "border-bottom-0")
                    const cell0 = newRow.insertCell(0);
                    const cell1 = newRow.insertCell(1);
                    const cell2 = newRow.insertCell(2);
                    const cell3 = newRow.insertCell(3);
                    const cell4 = newRow.insertCell(4);

                    cell0.innerHTML = name;
                    cell1.innerHTML = sYear;
                    cell2.innerHTML = sWeek;
                    cell3.innerHTML = eYear;
                    cell4.innerHTML = eWeek;
                    //verwijderd de tabel van de actieve projecten
                    document.getElementById(id).remove();

                    closeFinish();
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }
    })
})