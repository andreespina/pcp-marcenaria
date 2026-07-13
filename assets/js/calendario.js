// assets/js/calendario.js
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    
    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth', // Mostra o mês completo
            locale: 'pt-br', // Idioma em Português
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            buttonText: {
                today: 'Hoje',
                month: 'Mês',
                week: 'Semana',
                list: 'Lista'
            },
            // Puxa os dados da nossa API
            events: 'api/get_eventos_calendario.php',
            
            // Quando clica num bloco do calendário
            eventClick: function(info) {
                // Preenche os dados no Modal
                document.getElementById('ev_cliente').innerText = info.event.extendedProps.cliente;
                document.getElementById('ev_equipe').innerText = info.event.extendedProps.equipe;
                document.getElementById('ev_periodo').innerText = info.event.extendedProps.data_inicio + ' até ' + info.event.extendedProps.data_fim;
                
                // Formatação simples do status
                const st = info.event.extendedProps.status;
                document.getElementById('ev_status').innerText = (st === 'desenvolvimento') ? 'Desenv. PCP' : st;
                
                abrirModalEvento();
            }
        });
        
        calendar.render();
    }
});

// Funções de controlo do Modal
const modalEvento = document.getElementById('modalEvento'); 
const modalEventoConteudo = document.getElementById('modalEventoConteudo');

function abrirModalEvento() { 
    modalEvento.classList.remove('hidden'); 
    setTimeout(() => { 
        modalEvento.classList.remove('opacity-0'); 
        modalEventoConteudo.classList.remove('scale-95'); 
    }, 10); 
}

function fecharModalEvento() { 
    modalEvento.classList.add('opacity-0'); 
    modalEventoConteudo.classList.add('scale-95'); 
    setTimeout(() => { 
        modalEvento.classList.add('hidden'); 
    }, 300); 
}

// Fechar ao clicar fora
if(modalEvento) {
    modalEvento.addEventListener('click', (e) => { 
        if (e.target === modalEvento) fecharModalEvento(); 
    });
}