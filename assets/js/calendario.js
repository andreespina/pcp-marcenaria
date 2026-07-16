// assets/js/calendario.js

// ==========================================
// CONFIGURAÇÃO DO GOOGLE AGENDA
// ==========================================
// Para funcionar, você precisa de uma API KEY gerada no Google Cloud Platform 
// com a "Google Calendar API" ativada, e a sua agenda deve estar "Pública".
const GOOGLE_API_KEY = ''; // Cole a sua API KEY aqui
const GOOGLE_CALENDAR_ID = ''; // Cole o e-mail da sua agenda aqui (ex: sbgmoveis@gmail.com)

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    
    if (calendarEl) {
        
        // Configuração das fontes de dados
        let fontesDeEventos = [
            // Fonte 1: O nosso sistema (PCP)
            {
                url: 'api/get_eventos_calendario.php',
                backgroundColor: '#e0e7ff', // Fundo Azul Pastel Clarinho
                textColor: '#312e81',       // Texto Azul Escuro
            }
        ];

        // Se o utilizador configurou a API do Google, injeta a Fonte 2 no calendário
        if (GOOGLE_API_KEY !== '' && GOOGLE_CALENDAR_ID !== '') {
            fontesDeEventos.push({
                googleCalendarApiKey: GOOGLE_API_KEY,
                googleCalendarId: GOOGLE_CALENDAR_ID,
                backgroundColor: '#dcfce7', // Fundo Verde Pastel
                textColor: '#065f46',       // Texto Verde Escuro
                className: 'gcal-event'
            });
        }

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'pt-br',
            height: 'auto', // Ajusta automaticamente à altura dos eventos, não estica a tela!
            contentHeight: 'auto',
            
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek' // Removi a lista para ficar mais clean
            },
            buttonText: {
                today: 'Hoje',
                month: 'Mês',
                week: 'Semana'
            },
            
            // Injeta as fontes (Sistema + Google)
            eventSources: fontesDeEventos,
            
            // O que acontece ao clicar num evento
            eventClick: function(info) {
                // Impede que eventos do Google redirecionem automaticamente
                info.jsEvent.preventDefault(); 
                
                // Verifica se é um evento do Google Agenda (O Google não envia extendedProps.cliente)
                if (info.event.extendedProps && !info.event.extendedProps.cliente) {
                    // Se for do Google, abre o link do evento numa nova aba
                    if(info.event.url) {
                        window.open(info.event.url, '_blank');
                    }
                    return;
                }

                // Se for um evento do nosso PCP, abre o nosso modal bonito
                if (info.event.extendedProps.cliente) {
                    document.getElementById('ev_cliente').innerText = info.event.extendedProps.cliente || '-';
                    document.getElementById('ev_equipe').innerText = info.event.extendedProps.equipe || 'Não definida';
                    
                    // Formata as datas de exibição
                    let dIni = info.event.extendedProps.data_inicio || info.event.startStr;
                    let dFim = info.event.extendedProps.data_fim || info.event.endStr || dIni;
                    document.getElementById('ev_periodo').innerText = formatarDataBR(dIni) + ' até ' + formatarDataBR(dFim);
                    
                    const st = info.event.extendedProps.status;
                    document.getElementById('ev_status').innerText = (st === 'desenvolvimento') ? 'Desenv. PCP' : st;
                    
                    abrirModalEvento();
                }
            }
        });
        
        calendar.render();
    }
});

// Helper para converter data YYYY-MM-DD para DD/MM/YYYY no modal
function formatarDataBR(dataString) {
    if(!dataString) return '';
    const partes = dataString.split('-');
    if(partes.length === 3) {
        return partes[2] + '/' + partes[1] + '/' + partes[0];
    }
    return dataString;
}

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

// Fechar ao clicar fora do modal
if(modalEvento) {
    modalEvento.addEventListener('click', (e) => { 
        if (e.target === modalEvento) fecharModalEvento(); 
    });
}