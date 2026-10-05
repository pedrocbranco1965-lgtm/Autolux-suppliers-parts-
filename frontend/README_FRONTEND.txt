AutoLux - Projeto Final Frontend com React
Integracao Full Stack: versao 1.3

Aluno: Pedro Castel-Branco
Projeto: AutoLux - Stand de automóveis online

Este frontend corresponde ao projeto React/Vite original AutoLux e foi integrado
no projeto Full Stack através da opção "Peças" na navbar.
No menu PHP, a opção "AutoLux Stand" permite regressar a este frontend.

Tecnologias:
- React
- Vite
- React Router DOM
- Context API
- CSS puro
- Fetch API

Fonte de dados:
- public/data/vehicles.json

Como correr:
1. cp .env.example .env
2. npm install
3. npm run dev
4. Abrir normalmente http://localhost:5173

A variável VITE_BACKOFFICE_URL define o endereço do Back Office PHP.

REVISAO 1.3
Plugin React configurado em vite.config.js; porta 5173 fixa.
Dependencias diretas fixadas a partir do package-lock do repositorio original.
Instalacao/build/novos lockfiles pendentes no Mac.
O contacto continua simulado e a mensagem agora indica que nada foi enviado.
Nao foi acrescentado login/JWT.
