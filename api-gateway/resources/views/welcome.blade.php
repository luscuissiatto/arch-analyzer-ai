<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arch-Analyzer AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 min-h-screen text-slate-800 p-4 md:p-8 flex flex-col items-center justify-center">

    <div class="w-full max-w-5xl">
        <header class="text-center mb-10">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4 drop-shadow-md">Arch-Analyzer <span class="text-blue-400">AI</span></h1>
            <p class="text-blue-100 text-lg max-w-2xl mx-auto">Validação inteligente de arquitetura de software. Faça upload do seu diagrama (Imagem ou PDF) e descubra gargalos, riscos e recomendações em segundos.</p>
        </header>

        <main class="glass-panel rounded-2xl shadow-2xl overflow-hidden relative">

            <div id="uploadContainer" class="p-8 md:p-12 transition-all duration-500">
                <form id="uploadForm" class="flex flex-col items-center">
                    <div class="w-full max-w-xl">
                        <label for="fileInput" class="flex flex-col items-center justify-center w-full h-64 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-blue-50 hover:border-blue-400 transition-all group">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6 text-center px-4">
                                <svg class="w-12 h-12 mb-4 text-slate-400 group-hover:text-blue-500 transition-colors" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                </svg>
                                <p class="mb-2 text-lg font-semibold text-slate-700 group-hover:text-blue-600">Clique para enviar ou arraste seu arquivo</p>
                                <p class="text-sm text-slate-500">Arquivos suportados: PDF, PNG ou JPG (Máx. 10MB)</p>
                                <p id="fileNameDisplay" class="mt-4 text-sm font-bold text-blue-600 hidden"></p>
                            </div>
                            <input id="fileInput" type="file" class="hidden" accept=".pdf, image/png, image/jpeg, image/jpg" required />
                        </label>
                    </div>
                    <button type="submit" id="submitBtn" class="mt-8 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-10 rounded-xl transition-all shadow-lg hover:shadow-xl hover:-translate-y-1 w-full max-w-xl text-lg disabled:opacity-50 disabled:cursor-not-allowed">
                        Processar Arquitetura
                    </button>
                </form>
            </div>

            <div id="statusSection" class="hidden absolute inset-0 bg-white/95 flex flex-col items-center justify-center z-10">
                <div class="relative w-24 h-24 mb-6">
                    <div class="absolute inset-0 border-4 border-blue-100 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-blue-600 rounded-full border-t-transparent animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-2xl">🤖</div>
                </div>
                <h3 class="text-2xl font-bold text-slate-800 mb-2">Analisando Diagrama</h3>
                <p id="statusText" class="text-blue-600 font-medium animate-pulse">Enviando para a fila de processamento...</p>
            </div>

            <div id="resultSection" class="hidden p-8 md:p-10 bg-white">
                <div class="flex justify-between items-center mb-8 pb-4 border-b border-slate-200">
                    <h2 class="text-2xl font-bold text-slate-800">📊 Relatório Consolidado</h2>
                    <button onclick="location.reload()" class="text-sm font-semibold text-blue-600 hover:text-blue-800 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Nova Análise
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="bg-slate-50 p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center">
                            <span class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3">📦</span>
                            Componentes Identificados
                        </h3>
                        <ul id="componentsList" class="space-y-3 text-slate-600"></ul>
                    </div>

                    <div class="bg-red-50 p-6 rounded-xl border border-red-100 shadow-sm hover:shadow-md transition-shadow">
                        <h3 class="text-xl font-bold text-red-800 mb-4 flex items-center">
                            <span class="bg-red-100 text-red-600 p-2 rounded-lg mr-3">⚠️</span>
                            Riscos e Gargalos
                        </h3>
                        <ul id="risksList" class="space-y-3 text-red-700"></ul>
                    </div>
                </div>

                <div class="bg-emerald-50 p-6 rounded-xl border border-emerald-100 shadow-sm hover:shadow-md transition-shadow">
                    <h3 class="text-xl font-bold text-emerald-800 mb-4 flex items-center">
                        <span class="bg-emerald-100 text-emerald-600 p-2 rounded-lg mr-3">💡</span>
                        Plano de Ação & Recomendações
                    </h3>
                    <ul id="recommendationsList" class="space-y-3 text-emerald-700"></ul>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mostrar nome do arquivo selecionado
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            const display = document.getElementById('fileNameDisplay');
            if (fileName) {
                display.textContent = `Arquivo selecionado: ${fileName}`;
                display.classList.remove('hidden');
            }
        });

        document.getElementById('uploadForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const fileInput = document.getElementById('fileInput');
            if (!fileInput.files[0]) return;

            const formData = new FormData();
            formData.append('diagram', fileInput.files[0]);

            // Transição UI
            document.getElementById('uploadContainer').classList.add('opacity-0', 'pointer-events-none');
            setTimeout(() => {
                document.getElementById('statusSection').classList.remove('hidden');
            }, 300);

            try {
                const uploadResponse = await fetch('/api/upload', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json' // Força o Laravel a cuspir o erro real
                    },
                    body: formData
                });

                // Se o servidor retornar qualquer erro (422, 500, etc), nós capturamos a mensagem real
                if (!uploadResponse.ok) {
                    const errorData = await uploadResponse.json();
                    throw new Error(errorData.message || 'Erro de validação no servidor');
                }

                const uploadData = await uploadResponse.json();
                if (uploadData.analysis_id) {
                    checkStatus(uploadData.analysis_id);
                }
            } catch (error) {
                document.getElementById('statusText').innerText = error.message;
                document.getElementById('statusText').classList.replace('text-blue-600', 'text-red-600');
                document.querySelector('.animate-spin').classList.add('hidden');
            }
        });

        async function checkStatus(id) {
            const statusInterval = setInterval(async () => {
                try {
                    const response = await fetch(`/api/status/${id}`);
                    const data = await response.json();

                    document.getElementById('statusText').innerText = `Status IA: ${data.status}...`;

                    if (data.status === 'Analisado') {
                        clearInterval(statusInterval);
                        fetchReport(id);
                    } else if (data.status === 'Erro') {
                        clearInterval(statusInterval);
                        document.getElementById('statusText').innerText = 'A IA falhou ao processar o diagrama.';
                        document.getElementById('statusText').classList.replace('text-blue-600', 'text-red-600');
                        document.querySelector('.animate-spin').classList.add('hidden');
                    }
                } catch (error) {
                    console.error('Erro de polling');
                }
            }, 3000);
        }

        async function fetchReport(id) {
            document.getElementById('statusText').innerText = 'Montando relatório final...';

            try {
                const response = await fetch(`http://127.0.0.1:8001/api/reports/${id}`);
                const report = await response.json();

                setTimeout(() => {
                    document.getElementById('statusSection').classList.add('hidden');
                    document.getElementById('uploadContainer').classList.add('hidden'); // 🔥 Adicione esta linha aqui
                    document.getElementById('resultSection').classList.remove('hidden');
                }, 500);

                const populateList = (elementId, items) => {
                    const ul = document.getElementById(elementId);
                    ul.innerHTML = '';
                    items.forEach(item => {
                        const li = document.createElement('li');
                        li.className = "flex items-start";
                        li.innerHTML = `<span class="mr-2 mt-1 text-current opacity-70">•</span> <span>${item}</span>`;
                        ul.appendChild(li);
                    });
                };

                populateList('componentsList', report.components || ['Nenhum componente detectado.']);
                populateList('risksList', report.risks || ['Nenhum risco detectado.']);
                populateList('recommendationsList', report.recommendations || ['Nenhuma recomendação gerada.']);

            } catch (error) {
                document.getElementById('statusText').innerText = 'Erro ao carregar o relatório.';
                document.getElementById('statusText').classList.replace('text-blue-600', 'text-red-600');
            }
        }
    </script>
</body>
</html>
