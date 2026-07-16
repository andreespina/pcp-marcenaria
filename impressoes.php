<?php
// impressoes.php
require_once 'includes/auth.php';
protegerPagina();
require_once 'config/conexao.php';

$page_title = 'IMPRESSÕES RÁPIDAS';
$page_subtitle = 'Formulários, Checklists e Capas Editáveis';
$main_class = 'flex-1 max-w-7xl mx-auto w-full'; 
$menu_button_text = 'MENU';

// O Segredo da Impressão Perfeita está aqui:
$head_extras = '
<style>
    /* Quando clicar para editar, remove a borda azul padrão do navegador e dá um fundo suave */
    [contenteditable="true"]:focus { outline: 1px dashed #3b82f6; background-color: #eff6ff; }
    .dark [contenteditable="true"]:focus { outline-color: #60a5fa; background-color: #1e3a8a; }

    @media print {
        /* Configura a página para Horizontal (Landscape) com margem reduzida para garantir 1 página */
        @page { size: A4 landscape; margin: 5mm; }
        
        /* Esconde toda a interface do sistema, incluindo o RODAPÉ (footer) */
        body { background: white !important; padding: 0 !important; margin: 0 !important; }
        .no-print, header, nav, footer, details, .tabs-ui { display: none !important; }
        
        /* Oculta as abas que não estão selecionadas */
        .tab-content { display: none !important; padding: 0 !important; margin: 0 !important; box-shadow: none !important; border: none !important;}
        
        /* Mostra apenas a aba ativa e garante que ocupa exatamente 1 página */
        .tab-content.active-print-tab { display: block !important; page-break-after: avoid; }
        .print-area { display: flex !important; width: 100% !important; min-width: 100% !important; height: 98vh !important; overflow: hidden !important; }

        /* Estilização rigorosa das tabelas para impressão perfeita */
        table { width: 100%; border-collapse: collapse !important; margin-bottom: 10px; font-family: Arial, sans-serif; color: #000; }
        th, td { border: 1px solid #000 !important; padding: 4px 6px !important; font-size: 11px !important; }
        th { background-color: #e5e5e5 !important; font-weight: bold; text-align: center; -webkit-print-color-adjust: exact; color: #000 !important; }
        td { color: #000 !important; }
        
        /* Remove o outline de edição na impressão */
        [contenteditable="true"] { outline: none !important; background: transparent !important; }
        
        /* Estilos do cabeçalho nas tabelas */
        .header-print { display: flex; justify-content: space-between; align-items: center; border: 1px solid #000; padding: 10px; margin-bottom: 10px; }
        .header-print img { max-height: 50px; }
        .header-print h2 { margin: 0; font-size: 18px; font-weight: bold; text-transform: uppercase; font-family: Arial, sans-serif; color: #000; }
    }
</style>
';

require_once 'includes/header.php';
?>

<div class="flex flex-col gap-6 no-print">
    <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex justify-between items-center tabs-ui flex-wrap gap-4">
        <div class="flex flex-wrap gap-2">
            <button onclick="mudarModelo('capa')" id="btn_capa" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors">
                Capa Projeto Executivo
            </button>
            <button onclick="mudarModelo('checklist')" id="btn_checklist" class="bg-gray-200 text-gray-700 hover:bg-gray-300 px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors">
                Checklist da Obra
            </button>
            <button onclick="mudarModelo('ferramentas')" id="btn_ferramentas" class="bg-gray-200 text-gray-700 hover:bg-gray-300 px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors">
                Caixa de Ferramentas
            </button>
            <button onclick="mudarModelo('producao')" id="btn_producao" class="bg-gray-200 text-gray-700 hover:bg-gray-300 px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors">
                Ordem de Produção
            </button>
        </div>

        <button onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded text-sm font-bold shadow-md transition-transform hover:scale-105 flex items-center whitespace-nowrap">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            IMPRIMIR MODELO 
        </button>
    </div>
    
    <div class="text-sm text-gray-500 dark:text-gray-400 italic flex items-center">
        <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        Dica: Clique nas linhas sublinhadas ou dentro das tabelas para digitar os dados antes de imprimir.
    </div>
</div>

<div id="mod_capa" class="tab-content active-print-tab mt-6 bg-white rounded-lg shadow-sm overflow-auto">
    <div class="print-area min-w-[900px]" style="padding: 40px 60px; box-sizing: border-box; height: 95vh; display: flex; flex-direction: column; justify-content: center;">
        
        <div style="display: flex; justify-content: space-between; align-items: stretch; margin-bottom: 50px;">
            
            <div style="width: 38%; border: 3px solid #6b7280; padding: 40px 30px; display: flex; align-items: center; justify-content: center;">
                <img src="assets/images/sbg_oficial.png" alt="SBG Móveis & Design" style="max-width: 100%; height: auto;" onerror="this.style.display='none';">
            </div>

            <div style="width: 55%; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5; color: #374151; display: flex; flex-direction: column; justify-content: center;">
                <div style="display: flex; margin-bottom: 25px; align-items: flex-end;">
                    <span style="font-weight: bold; width: 100px; color: #4b5563;">ID:</span>
                    <div contenteditable="true" style="flex: 1; border-bottom: 1px solid #4b5563; font-weight: bold; color: #000; min-height: 25px; outline: none; padding-bottom: 2px;"></div>
                </div>
                <div style="display: flex; margin-bottom: 25px; align-items: flex-end;">
                    <span style="font-weight: bold; width: 100px; color: #4b5563;">CLIENTE:</span>
                    <div contenteditable="true" style="flex: 1; border-bottom: 1px solid #4b5563; font-weight: bold; color: #000; min-height: 25px; outline: none; padding-bottom: 2px;"></div>
                </div>
                <div style="display: flex; margin-bottom: 25px; align-items: flex-end;">
                    <span style="font-weight: bold; width: 100px; color: #4b5563;">ENDEREÇO:</span>
                    <div contenteditable="true" style="flex: 1; border-bottom: 1px solid #4b5563; font-weight: bold; color: #000; min-height: 25px; outline: none; padding-bottom: 2px;"></div>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: flex-end; font-family: Arial, sans-serif; font-size: 16px; color: #4b5563;">
            
            <div style="width: 55%;">
                <strong style="display: block; margin-bottom: 20px; letter-spacing: 1px;">EQUIPE DE MONTAGEM:</strong>
                <div style="display: flex; margin-bottom: 25px; align-items: flex-end;">
                    <span style="margin-right: 15px;">NOME:</span>
                    <div contenteditable="true" style="flex: 1; border-bottom: 1px solid #4b5563; color: #000; min-height: 25px; outline: none; padding-bottom: 2px;"></div>
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <span style="margin-right: 15px;">NOME:</span>
                    <div contenteditable="true" style="flex: 1; border-bottom: 1px solid #4b5563; color: #000; min-height: 25px; outline: none; padding-bottom: 2px;"></div>
                </div>
            </div>
            
            <div style="width: 40%; display: flex; align-items: flex-end; padding-bottom: 5px;">
                <span style="margin-right: 15px; font-weight: bold; letter-spacing: 1px;">DATA DO TÉRMINO:</span>
                <div contenteditable="true" style="flex: 1; border-bottom: 1px solid #4b5563; text-align: center; letter-spacing: 2px; color: #000; outline: none;">&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
            </div>
        </div>
    </div>
</div>

<div id="mod_checklist" class="tab-content hidden mt-6 bg-white rounded-lg shadow-sm overflow-auto">
    <div class="print-area min-w-[900px]" style="padding: 20px 40px; box-sizing: border-box; height: 95vh; display: flex; flex-direction: column; justify-content: center;">
        
        <div style="display: flex; justify-content: space-between; font-family: 'Segoe UI', Arial, sans-serif; align-items: stretch;">
            
            <div style="width: 55%; display: flex; flex-direction: column;">
                <h1 style="font-size: 30px; color: #6b7280; font-weight: 500; margin-bottom: 15px; margin-top: 0;">Checklist da obra</h1>
                
                <?php 
                $itens_checklist = [
                    'Limpeza dos Móveis.',
                    'Limpeza dos Ambientes.',
                    'Etiqueta <strong>SBG</strong>.',
                    'Gelzinho Batedor de Porta.',
                    'Tapa Furos Brancos.',
                    'Tapa Furos Madeirados.',
                    'Capa de L Fixação.',
                    'Puxadores.',
                    'Iluminação.',
                    'Regulagem de Portas.',
                    'Regulagem de Gavetas.',
                    'Calafetação.',
                    'Divisor de Talher.',
                    'Conferir se todas as basculantes estão com 2 (Dois) pistões.'
                ];
                ?>

                <div style="display: flex; flex-direction: column; gap: 14px; color: #4b5563; font-size: 15px; flex: 1;">
                    <?php foreach($itens_checklist as $item): ?>
                        <div style="display: flex; align-items: center;">
                            <div style="width: 13px; height: 13px; border: 1px solid #6b7280; margin-right: 25px; flex-shrink: 0;"></div>
                            <div contenteditable="true" style="outline: none;"><?= $item ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="width: 40%; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
                
                <div style="color: #ef4444; font-weight: bold; font-size: 17px; line-height: 1.4; margin-bottom: 25px; letter-spacing: 0.5px;">
                    ATENÇÃO:<br>
                    NÃO JOGAR AS CANTONEIRAS PLASTICAS NO LIXO!<br>
                    FAVOR RETORNAR PARA A EMPRESA,<br>
                    PARA QUE POSSAMOS REUTILIZAR!
                </div>

                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" style="width: 180px; height: 180px; margin-bottom: 15px;">
                    <defs>
                        <g id="leaf">
                            <path d="M 0 0 C -14 -12, -20 -28, 0 -48 C 20 -28, 14 -12, 0 0 Z" fill="#568c3b"/>
                            <path d="M 0 -3 Q -2 -20 0 -45" stroke="#FFFFFF" stroke-width="1.5" fill="none"/>
                        </g>
                        <path id="hand" d="M 92 180 C 40 185, 18 150, 24 110 C 32 135, 48 152, 68 152 C 78 152, 86 140, 93 126 C 86 142, 75 162, 58 162 C 70 170, 82 176, 92 180 Z" fill="#3b5e32"/>
                    </defs>
                    <rect width="200" height="200" fill="none" />
                    <g id="soil-group">
                        <path id="soil-base" d="M 35 110 Q 100 95 165 110 Q 100 150 35 110 Z" fill="#568c3b"/>
                        <clipPath id="soil-clip">
                            <use href="#soil-base"/>
                        </clipPath>
                        <path d="M 30 125 Q 100 100 170 105" stroke="#FFFFFF" stroke-width="2.2" fill="none" clip-path="url(#soil-clip)"/>
                        <path d="M 60 145 Q 120 115 170 115" stroke="#FFFFFF" stroke-width="2.2" fill="none" clip-path="url(#soil-clip)"/>
                    </g>
                    <g id="stems" fill="#568c3b">
                        <path d="M 97 115 C 94 80, 98 50, 99 40 C 99 38, 101 38, 101 40 C 102 50, 99 80, 103 115 Z"/>
                        <path d="M 97.5 95 Q 80 105 65 82 Q 80 95 97.5 88 Z"/>
                        <path d="M 102.5 95 Q 120 105 135 82 Q 120 95 102.5 88 Z"/>
                        <path d="M 98 70 Q 85 78 72 56 Q 85 70 98 64 Z"/>
                        <path d="M 102 70 Q 115 78 128 56 Q 115 70 102 64 Z"/>
                    </g>
                    <use href="#leaf" transform="translate(100, 40) scale(0.9)"/>
                    <use href="#leaf" transform="translate(65, 82) rotate(-65) scale(0.85)"/>
                    <use href="#leaf" transform="translate(135, 82) rotate(65) scale(0.85)"/>
                    <use href="#leaf" transform="translate(72, 56) rotate(-45) scale(0.70)"/>
                    <use href="#leaf" transform="translate(128, 56) rotate(45) scale(0.70)"/>
                    <use href="#hand"/>
                    <use href="#hand" transform="translate(200, 0) scale(-1, 1)"/>
                </svg>

                <div style="color: #65a30d; font-weight: bold; font-size: 19px; letter-spacing: 1px;">
                    A NATUREZA AGRADECE.
                </div>
            </div>
            
        </div>

        <div style="display: flex; justify-content: space-between; align-items: flex-end; font-family: 'Segoe UI', Arial, sans-serif; color: #4b5563; margin-top: 30px;">
            <div style="display: flex; align-items: flex-end; width: 100%;">
                <span style="font-size: 16px; margin-right: 10px; font-weight: bold;">OBS:</span>
                <div contenteditable="true" style="flex: 1; border-bottom: 1px solid #6b7280; outline: none; min-height: 25px; padding-bottom: 2px;"></div>
                <div contenteditable="true" style="font-family: 'Comic Sans MS', cursive, sans-serif; font-size: 26px; color: #374151; margin-left: 20px; outline: none; min-width: 150px; text-align: center;">Cozinha</div>
            </div>
        </div>

    </div>
</div>

<div id="mod_ferramentas" class="tab-content hidden mt-6 bg-white rounded-lg shadow-sm overflow-auto">
    <div class="print-area min-w-[900px]" style="padding: 20px; box-sizing: border-box; height: 95vh; display: flex; flex-direction: column;">
        
        <div class="header-print">
            <img src="assets/images/sbg_oficial.png" alt="SBG Móveis & Design" onerror="this.style.display='none';">
            <h2 contenteditable="true">CONFERÊNCIA DE CAIXA DE FERRAMENTAS - INSTALAÇÃO</h2>
            <div style="font-family: Arial, sans-serif; font-size: 12px; text-align: right;">
                <div contenteditable="true"><strong>Equipe:</strong> _______________________</div>
                <div contenteditable="true" style="margin-top: 5px;"><strong>Data:</strong> ___/___/20__</div>
            </div>
        </div>

        <div style="display: flex; gap: 10px; width: 100%; flex: 1;">
            <div style="flex: 1;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 70%;">MÁQUINAS E GERAIS</th>
                            <th style="width: 15%;">QTD</th>
                            <th style="width: 15%;">OK</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $ferramentas_col1 = [
                            'Furadeira / Parafusadeira', 'Martelete Rompedor', 'Serra Tico-Tico', 'Plaina / Tupia', 
                            'Nível a Laser / Tripé', 'Nível de Mão (Bolha)', 'Trena 5m / 8m', 'Esquadro', 
                            'Estilete (com lâminas)', 'Martelo', 'Marreta de Borracha', 'Alicate Universal', 
                            'Alicate de Pressão', 'Chave Philips (P/M/G)', 'Chave de Fenda (P/M/G)', 
                            'Lápis / Caneta', 'Baterias Extras', 'Carregador de Bateria', 'Extensão Elétrica', 
                            'Aspirador de Pó / Vassoura', 'Fita Crepe', 'Fita Isolante'
                        ];
                        foreach($ferramentas_col1 as $item): ?>
                        <tr>
                            <td contenteditable="true"><?= $item ?></td>
                            <td contenteditable="true" style="text-align: center;"></td>
                            <td contenteditable="true" style="text-align: center;">[  ]</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php for($i=0; $i<3; $i++): ?>
                        <tr><td contenteditable="true">&nbsp;</td><td contenteditable="true"></td><td contenteditable="true" style="text-align: center;">[  ]</td></tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div style="flex: 1;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 70%;">BROCAS E ACESSÓRIOS</th>
                            <th style="width: 15%;">QTD</th>
                            <th style="width: 15%;">OK</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $ferramentas_col2 = [
                            'Broca Madeira 3mm / 4mm', 'Broca Madeira 5mm / 6mm', 'Broca Madeira 8mm', 
                            'Broca Parede (Widia) 5mm', 'Broca Parede (Widia) 6mm', 'Broca Parede (Widia) 8mm', 
                            'Broca Parede (Widia) 10mm', 'Broca Aço Rápido (Metal)', 'Broca Chata / Dobradiça', 
                            'Serra Copo (Passa Fio)', 'Serra Copo (Luminária)', 'Bits Philips PH2', 
                            'Bits Prolongador / Imã', 'Escareador', 'Gabarito de Furação', 
                            'Silicone Incolor / PU', 'Espuma Expansiva', 'Pistola Aplicadora', 
                            'Gabarito de Puxador', 'Tapa Furo (Cores Diversas)', 'Cera / Giz Correção', 'Thinner / Pano Limpeza'
                        ];
                        foreach($ferramentas_col2 as $item): ?>
                        <tr>
                            <td contenteditable="true"><?= $item ?></td>
                            <td contenteditable="true" style="text-align: center;"></td>
                            <td contenteditable="true" style="text-align: center;">[  ]</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php for($i=0; $i<3; $i++): ?>
                        <tr><td contenteditable="true">&nbsp;</td><td contenteditable="true"></td><td contenteditable="true" style="text-align: center;">[  ]</td></tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div style="flex: 1;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 70%;">PARAFUSOS E BUCHAS</th>
                            <th style="width: 15%;">QTD</th>
                            <th style="width: 15%;">OK</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $ferramentas_col3 = [
                            'Parafuso 4.0 x 16 (Dobradiça)', 'Parafuso 4.0 x 25', 'Parafuso 4.0 x 30', 
                            'Parafuso 4.0 x 40 (Montagem)', 'Parafuso 4.0 x 45', 'Parafuso 4.0 x 50', 
                            'Parafuso 4.5 x 60', 'Parafuso 5.0 x 60', 'Parafuso Puxador', 
                            'Prego 10x10 / 12x12', 'Bucha Parede 6mm', 'Bucha Parede 8mm', 
                            'Bucha Drywall / Gesso 6mm', 'Bucha Drywall / Gesso 8mm', 'Bucha Tijolo Baiano',
                            'Cantoneira L (Montagem)', 'Suporte Aéreo / Camarão', 'Capa Suporte Aéreo'
                        ];
                        foreach($ferramentas_col3 as $item): ?>
                        <tr>
                            <td contenteditable="true"><?= $item ?></td>
                            <td contenteditable="true" style="text-align: center;"></td>
                            <td contenteditable="true" style="text-align: center;">[  ]</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php for($i=0; $i<7; $i++): ?>
                        <tr><td contenteditable="true">&nbsp;</td><td contenteditable="true"></td><td contenteditable="true" style="text-align: center;">[  ]</td></tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <div style="margin-top: 15px; font-family: Arial, sans-serif; font-size: 11px; text-align: center; padding-bottom: 5px;" contenteditable="true">
            Declaro ter conferido e recebido todas as ferramentas e insumos assinalados acima em perfeito estado de conservação.<br><br><br>
            _________________________________________________________<br>
            Assinatura do Instalador Responsável
        </div>

    </div>
</div>

<div id="mod_producao" class="tab-content hidden mt-6 bg-white rounded-lg shadow-sm overflow-auto">
    <div class="print-area min-w-[1000px]" style="padding: 20px; box-sizing: border-box; height: 95vh; display: flex; flex-direction: column;">
        
        <div class="header-print">
            <img src="assets/images/sbg_oficial.png" alt="SBG Móveis & Design" onerror="this.style.display='none';">
            <h2 contenteditable="true">ORDEM DE SEPARAÇÃO E CONFERÊNCIA (ROMANEIO)</h2>
            <div style="font-family: Arial, sans-serif; font-size: 12px;">
                <table style="border: none !important; margin:0;">
                    <tr>
                        <td style="border: none !important; padding: 2px !important;" contenteditable="true"><strong>Cliente:</strong> _______________________</td>
                        <td style="border: none !important; padding: 2px !important;" contenteditable="true"><strong>Ambiente:</strong> _______________________</td>
                    </tr>
                    <tr>
                        <td style="border: none !important; padding: 2px !important;" contenteditable="true"><strong>Responsável:</strong> ____________________</td>
                        <td style="border: none !important; padding: 2px !important;" contenteditable="true"><strong>Data Prev:</strong> ___/___/20__</td>
                    </tr>
                </table>
            </div>
        </div>

        <table style="flex: 1; margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="width: 5%;">ITEM</th>
                    <th style="width: 25%;">DESCRIÇÃO DA PEÇA / MÓVEL</th>
                    <th style="width: 5%;">QTD</th>
                    <th style="width: 10%;">COMPRIMENTO</th>
                    <th style="width: 10%;">LARGURA</th>
                    <th style="width: 15%;">FITA DE BORDA</th>
                    <th style="width: 25%;">OBSERVAÇÃO / FUROS</th>
                    <th style="width: 5%;">CONF.</th>
                </tr>
            </thead>
            <tbody>
                <?php for($i=1; $i<=22; $i++): ?>
                <tr>
                    <td contenteditable="true" style="text-align: center; font-weight: bold;"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></td>
                    <td contenteditable="true">&nbsp;</td>
                    <td contenteditable="true" style="text-align: center;"></td>
                    <td contenteditable="true" style="text-align: center;"></td>
                    <td contenteditable="true" style="text-align: center;"></td>
                    <td contenteditable="true" style="text-align: center; font-size: 9px !important; color: #555;">[ ]L1 &nbsp; [ ]L2 &nbsp; [ ]C1 &nbsp; [ ]C2</td>
                    <td contenteditable="true"></td>
                    <td contenteditable="true" style="text-align: center;">[  ]</td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

    </div>
</div>

<script>
    // Lógica para alternar as abas no ecrã (não afeta a impressão)
    function mudarModelo(modelo) {
        // Lista de todos os modelos disponíveis
        const modelos = ['capa', 'checklist', 'ferramentas', 'producao'];
        
        modelos.forEach(mod => {
            // Esconde os conteúdos
            const el = document.getElementById('mod_' + mod);
            if(el) {
                el.classList.add('hidden');
                el.classList.remove('active-print-tab');
            }
            
            // Reseta a cor dos botões para o padrão inativo
            const btn = document.getElementById('btn_' + mod);
            if(btn) {
                btn.className = 'bg-gray-200 text-gray-700 hover:bg-gray-300 px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors';
            }
        });

        // Ativa o conteúdo selecionado
        const elAtivo = document.getElementById('mod_' + modelo);
        if(elAtivo) {
            elAtivo.classList.remove('hidden');
            elAtivo.classList.add('active-print-tab');
        }

        // Pinta o botão ativo de azul
        const btnAtivo = document.getElementById('btn_' + modelo);
        if(btnAtivo) {
            btnAtivo.className = 'bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors';
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>