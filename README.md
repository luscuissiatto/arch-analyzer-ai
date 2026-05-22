# FIAP Secure Systems - Plataforma de Análise Automática de Arquitetura com IA

Plataforma desenvolvida para automatizar a análise de diagramas de arquitetura de software, fornecendo identificação de componentes, riscos e recomendações arquiteturais através do uso avançado de Inteligência Artificial integrada a uma Arquitetura de Microsserviços orientada a eventos.

## 1. O Problema
Empresas que operam sistemas distribuídos lidam com dezenas de diagramas arquiteturais armazenados em formatos estáticos, como imagens ou PDFs. A análise manual desses artefatos durante revisões de design, auditorias de segurança e discussões técnicas consome tempo excessivo, depende da disponibilidade de especialistas seniores e, fundamentalmente, não escala. 

Esta solução resolve esse gargalo implementando um pipeline automatizado que ingere o diagrama e gera um relatório estruturado de forma assíncrona, padronizando a avaliação e acelerando a tomada de decisão técnica.

## 2. Tecnologias Utilizadas (Stack)
O sistema foi construído utilizando tecnologias modernas e padrões de mercado para garantir performance e manutenibilidade:

* **Backend:** PHP 8.4 com Framework Laravel.
* **Inteligência Artificial:** API do Google Gemini (LLM).
* **Mensageria (Assíncrono):** RabbitMQ.
* **Bancos de Dados:** PostgreSQL (Bancos isolados por serviço) e SQLite (Testes CI/CD).
* **Observabilidade:** Stack LGTM (Loki, Promtail, Grafana).
* **Qualidade e Testes:** PHPUnit.
* **DevOps e Infraestrutura:** Docker, Docker Compose e GitHub Actions (CI/CD).

## 3. Arquitetura Proposta

**🔗 [Visualizar Diagrama de Arquitetura Completo (Mermaid)](https://mermaid.ai/view/fc257fb3-f4d3-4a35-b105-e9aa7b42cdd0)**

A solução foi desenhada utilizando uma **Arquitetura de Microsserviços** desacoplada, garantindo *Bounded Contexts* claros, persistência isolada e alta escalabilidade:

* **API Gateway / BFF:** Porta de entrada do sistema. Responsável por receber o upload do diagrama de forma segura, expor o status de processamento aos clientes e orquestrar a comunicação inicial.
* **AI Processing Service:** Worker assíncrono isolado. Recebe o artefato da fila, aplica regras de *prompt engineering* restritas e consome o modelo LLM para extrair os dados técnicos da imagem/PDF.
* **Report Service:** Serviço de domínio exclusivo para consolidar os dados da IA, persistir as informações em um banco de leitura otimizado e expor os relatórios gerados via endpoints REST.
* **Message Broker (RabbitMQ):** Espinha dorsal da comunicação assíncrona da plataforma, orquestrando eventos através das filas `diagram_processing` e `report_queue` para garantir tolerância a falhas e picos de acesso.

## 4. Fluxo de Execução
1. O usuário envia um arquivo (Imagem/PDF) via endpoint `POST` no API Gateway.
2. O Gateway valida o payload, salva o registro inicial no banco de orquestração com status "Recebido" e publica um evento na fila do RabbitMQ.
3. O *AI Processing Service* consome a mensagem instantaneamente, altera o status para "Em processamento" e envia o artefato ao LLM.
4. O resultado processado e validado é enviado para a fila de consolidação, liberando o worker de IA para novos trabalhos.
5. O *Report Service* consome os dados finais, salva no seu banco de dados isolado e disponibiliza o relatório estruturado para leitura.
6. O cliente, de forma assíncrona, consulta o status da operação e consome o relatório completo e estruturado via `GET`.

## 5. Segurança, Governança de IA e Limitações

A plataforma foi projetada seguindo as premissas de *Security by Design*:

### 5.1. Tratamento de Entradas Não Confiáveis
O API Gateway atua como um escudo, validando rigorosamente o tipo (`mime_type`) e o tamanho do arquivo antes de aceitá-lo. Arquivos maliciosos ou fora dos formatos de imagem/PDF estipulados são rejeitados na borda da aplicação, protegendo os *workers* internos de injeções.

### 5.2. Uso Controlado da IA (Guardrails)
Para mitigar alucinações matemáticas ou conceituais e garantir a previsibilidade do LLM, implementou-se um modelo rigoroso de *Prompt Engineering*:
* **Contexto Fechado:** A IA é forçada a atuar estritamente sob a persona de um "Arquiteto de Software de Segurança".
* **Saída Determinística:** Instruções explícitas forçam a IA a devolver o resultado estruturado em formato JSON rigoroso, contendo unicamente as chaves `componentes`, `riscos` e `recomendacoes`, ignorando abstrações desnecessárias ou blocos textuais soltos.

### 5.3. Tratamento de Falhas e Comportamentos Inesperados da IA
A comunicação com modelos generativos é inerentemente instável. O sistema é resiliente a essas variações:
* **Validação de Payload:** Se a IA devolver um JSON malformado que viole a estrutura esperada (alucinação de formato), o sistema captura a exceção imediatamente, rejeita o payload e marca o status como "Erro", evitando corromper o banco de relatórios corporativos.
* **Circuit Breaker / Requeue:** Se a API do Google Gemini retornar *503 Service Unavailable* devido a picos de tráfego, o *worker* intercepta a falha, executa uma pausa (`sleep`) e devolve a mensagem à fila via `nack` para reprocessamento futuro, evitando perda de dados do cliente.

### 5.4. Segurança na Comunicação Interna
Nenhuma comunicação de processamento sensível trafega por HTTP exposto na internet. A troca de mensagens entre orquestração, IA e persistência ocorre via AMQP, isolada de forma segura na rede virtual do Docker Compose.

## 6. Qualidade e Observabilidade
A plataforma entrega os mais altos padrões de engenharia de software corporativa:
* **Cobertura de Código:** 100% de cobertura nos testes unitários, de integração e de ponta a ponta, atestada nos três microsserviços do ecosistema.
* **Observabilidade Centralizada:** Stack LGTM implementada nativamente. Todos os logs dos containers são roteados, estruturados e indexados em tempo real no Grafana.
* **CI/CD Totalmente Automatizado:** Pipeline estruturada no GitHub Actions que garante *linting*, testes automatizados com banco em memória (SQLite) e deploy de artefatos Docker diretamente no GitHub Container Registry (GHCR) a cada iteração na ramificação principal.

---

## 7. Como Executar a Aplicação (Guia de Uso)

O sistema foi empacotado para facilitar o provisionamento de infraestrutura. Todos os serviços, bancos de dados, mensageria e ferramentas de observabilidade sobem com um único comando.

### Pré-requisitos
* **Docker** e **Docker Compose** instalados na máquina host.
* **Git** para clonagem do repositório.
* Uma chave de API válida do **Google Gemini**.

### Passo a Passo

**1. Clone o repositório para a sua máquina local:**
```bash
git clone [https://github.com/seu-usuario/fiap-hackaton.git](https://github.com/seu-usuario/fiap-hackaton.git)
cd fiap-hackaton
```

**2. Configure a Chave da IA:**
Acesse o diretório do serviço de IA e configure as variáveis de ambiente:
```bash
cd ai-processing-service
cp .env.example .env
```

Abra o arquivo `.env` gerado e cole a sua chave da API do Google na variável correspondente:
```bash
GEMINI_API_KEY=sua_chave_real_aqui_sem_aspas
```

(Não é necessário preencher outras variáveis do .env, as portas do banco e fila já estão pré-configuradas para o ambiente Docker).

**3. Suba a Infraestrutura Completa:**
Volte para a raiz do projeto e inicie os containers:
```bash
cd ..
docker-compose up -d --build
```

**4. Acompanhe a subida dos serviços:**
O ambiente provisionará 9 containers: 3 bancos PostgreSQL isolados, RabbitMQ, Gateway, Worker de IA, Worker de Relatório, Loki e Grafana. Aguarde aproximadamente 30 segundos para que todos os serviços inicializem completamente.

### 🔗 Acesso aos Serviços

* **API Gateway (Rotas REST):** Disponível em `http://localhost:8000`
* **Painel de Logs em Tempo Real (Grafana):** Disponível em `http://localhost:3000`
    * **Usuário:** `admin`
    * **Senha:** `admin` *(Pule a criação de nova senha)*
    * **Como visualizar:** No menu lateral, acesse **Explore**, selecione a fonte **Loki** e rode uma query (ex: `{job="laravel"}`) para visualizar os logs estruturados.

### 🧪 Testando o Fluxo

1.  Utilize uma ferramenta como Insomnia, Postman ou cURL para fazer um **POST** para `http://localhost:8000/api/analyze`, anexando um diagrama válido no corpo da requisição.
2.  Anote o `id` retornado na resposta (ex: `12345`).
3.  Faça um **GET** para `http://localhost:8000/api/reports/12345` para visualizar o status mudar de "Em processamento" para "Analisado", revelando o relatório estruturado final gerado pela IA.