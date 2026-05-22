# FIAP Secure Systems - Análise Automática de Arquitetura com IA

MVP desenvolvido para automatizar a análise de diagramas de arquitetura de software, fornecendo identificação de componentes, riscos e recomendações arquiteturais através do uso de Inteligência Artificial e Arquitetura de Microsserviços.

## 1. Descrição do Problema
Empresas com sistemas distribuídos lidam com dezenas de diagramas arquiteturais armazenados em formatos estáticos (imagens/PDFs). A análise manual desses arquivos em revisões, auditorias e discussões técnicas consome tempo excessivo, depende de especialistas alocados e não escala adequadamente[cite: 4, 5, 10, 11, 12, 13]. Este projeto resolve esse gargalo implementando um pipeline automatizado que ingere o diagrama e gera um relatório estruturado de forma assíncrona.

## 2. Arquitetura Proposta
A solução foi desenhada utilizando uma **Arquitetura de Microsserviços** desacoplada[cite: 63], garantindo Bounded Contexts claros, persistência isolada e alta escalabilidade:

* **API Gateway / BFF:** Porta de entrada do sistema. Responsável por receber o upload do diagrama, expor o status de processamento e orquestrar a comunicação inicial[cite: 74, 75].
* **AI Processing Service:** Worker assíncrono isolado. Recebe o artefato, aplica regras de prompt engineering e consome a API do Google Gemini (LLM) para extrair os dados técnicos[cite: 76, 82].
* **Report Service:** Serviço de domínio exclusivo para consolidar, persistir em um banco de leitura exclusivo e expor os relatórios gerados via endpoints REST[cite: 77].
* **Message Broker (RabbitMQ):** Espinha dorsal da comunicação assíncrona, orquestrando eventos através das filas `diagram_processing` e `report_queue` para garantir tolerância a falhas[cite: 66].

## 3. Fluxo da Solução
1. O usuário envia um arquivo (Imagem/PDF) via endpoint POST no API Gateway[cite: 24, 51].
2. O Gateway valida o payload, salva o registro inicial com status "Recebido" e publica um evento na fila do RabbitMQ[cite: 53].
3. O *AI Processing Service* consome a mensagem, altera o status para "Em processamento" [cite: 54] e envia o artefato ao LLM.
4. O resultado processado é enviado para uma nova fila, liberando o worker de IA imediatamente[cite: 96, 97].
5. O *Report Service* consome os dados finais, salva no seu banco isolado e disponibiliza o relatório estruturado para leitura[cite: 27].
6. O cliente consulta o status ("Analisado" ou "Erro") e consome o relatório completo via GET[cite: 28, 55, 56].

## 4. Segurança, Governança de IA e Limitações

### 4.1. Tratamento de Entradas Não Confiáveis
O API Gateway atua como um escudo, validando rigorosamente o `mime_type` e o tamanho do arquivo antes de aceitá-lo[cite: 128]. Arquivos maliciosos ou fora do padrão (imagens e PDFs) são rejeitados na borda, protegendo os workers internos.

### 4.2. Uso Controlado da IA (Guardrails)
Para mitigar alucinações e garantir a previsibilidade do LLM [cite: 83, 130], foi implementado um *Prompt Engineering* estrito no serviço de IA[cite: 84]:
* **Contexto Fechado:** A IA recebe a persona estrita de "Arquiteto de Software".
* **Saída Determinística:** Instruções explícitas forçam a IA a devolver o resultado estritamente em formato JSON, com as chaves predefinidas (`componentes`, `riscos`, `recomendacoes`), ignorando blocos Markdown e abstrações desnecessárias.

### 4.3. Tratamento de Falhas e Comportamentos Inesperados da IA
A comunicação com LLMs é inerentemente instável. O sistema implementa o padrão de *Circuit Breaker* adaptado[cite: 94, 131]:
* Se a IA devolver um JSON malformado que viole a estrutura esperada, o sistema captura a exceção via `json_last_error()` e marca o status como "Erro"[cite: 56], evitando corromper o banco de relatórios.
* Se a API do LLM retornar *503 Service Unavailable* (pico de uso), o worker executa um `sleep(10)` e devolve a mensagem à fila via `nack(true)` para reprocessamento futuro (*requeue*), garantindo tolerância a falhas externas.

### 4.4. Segurança na Comunicação entre Serviços
A comunicação sensível de processamento não trafega por HTTP exposto, mas sim através do ambiente fechado e assíncrono do RabbitMQ (AMQP) isolado na rede virtual do Docker.

### 4.5. Riscos e Limitações
A precisão da IA depende da nitidez e dos padrões visuais do diagrama enviado[cite: 133]. Arquiteturas muito obscuras ou manuscritas possuem maior risco de falha na extração de texto (OCR interno do Gemini). Além disso, a análise atual é estática e baseada em melhores práticas globais, não substituindo o contexto de negócio específico da empresa avaliada[cite: 89].

## 5. Qualidade e Observabilidade
O MVP foi construído para nível de produção:
* **Cobertura de Código:** 100% de cobertura nos testes unitários/funcionais atestada via SonarQube nos três microsserviços[cite: 72, 110].
* **Observabilidade Centralizada:** Stack LGTM (Loki, Promtail, Grafana) implementada no Docker Compose, capturando todos os *logs estruturados* (`stderr`) dos containers em tempo real[cite: 107].
* **Métricas de Infraestrutura:** Prometheus acoplado ao cAdvisor monitorando a saúde (CPU, RAM, Rede) de todos os containers[cite: 40].
* **CI/CD:** Pipeline automatizada no GitHub Actions garantindo linting, testes e cobertura a cada Pull Request[cite: 39, 102].

## 6. Instruções de Execução

**Pré-requisitos:** Docker e Docker Compose instalados.
**Chave de IA:** Renomeie o arquivo `.env.example` para `.env` dentro da pasta `ai-processing-service` e preencha a variável `GEMINI_API_KEY` com uma chave válida.

1. Clone o repositório.
2. Na raiz do projeto, suba a infraestrutura completa:
   ```bash
   docker-compose up -d --build
3. O Gateway estará disponível em http://localhost:8000.
4. Os painéis de observabilidade (Grafana) estarão em http://localhost:3000 (user/pass: admin).