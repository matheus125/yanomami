import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const root = process.cwd();
const baseUrl = process.env.SIAY_URL ?? 'http://127.0.0.1:8000';
const chromePath =
    process.env.CHROME_PATH ??
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';

const outputDir = path.join(root, 'docs', 'tutorial-siay');
const assetsDir = path.join(outputDir, 'assets');
const htmlPath = path.join(outputDir, 'tutorial-siay.html');
const pdfPath = path.join(outputDir, 'Tutorial-SIAY.pdf');

await fs.mkdir(assetsDir, { recursive: true });

const browser = await chromium.launch({
    headless: true,
    executablePath: chromePath,
});

const context = await browser.newContext({
    viewport: { width: 1440, height: 1000 },
    deviceScaleFactor: 1,
});

const page = await context.newPage();

async function settle(currentPage = page) {
    await currentPage.waitForLoadState('domcontentloaded');
    await currentPage.waitForLoadState('networkidle').catch(() => {});
    await currentPage.waitForTimeout(500);
}

async function screenshot(name, title, currentPage = page) {
    const filename = `${name}.png`;
    await currentPage.screenshot({
        path: path.join(assetsDir, filename),
        fullPage: true,
    });

    return {
        name,
        title,
        file: `assets/${filename}`,
    };
}

async function visit(url) {
    await page.goto(`${baseUrl}${url}`);
    await settle();
}

const shots = [];

await page.goto(`${baseUrl}/login`);
await settle();
shots.push(await screenshot('01-login', 'Tela de login'));

await page.getByLabel('Email').fill('admin@siay.test');
await page.getByLabel('Senha').fill('password');
await page.getByRole('button', { name: 'Entrar' }).click();
await page.waitForURL('**/dashboard');
await settle();

shots.push(await screenshot('02-dashboard', 'Dashboard principal'));

await visit('/cases');
shots.push(await screenshot('03-casos-listagem', 'Listagem e filtros de casos'));

const firstCaseLink = page.getByRole('link', { name: 'Abrir' }).first();
if (await firstCaseLink.count()) {
    await firstCaseLink.click();
    await settle();
    shots.push(await screenshot('04-caso-detalhe', 'Detalhe do caso'));
}

await visit('/cases/create');
shots.push(await screenshot('05-caso-formulario', 'Formulario de abertura de caso'));

await visit('/modulos/pessoas');
shots.push(await screenshot('06-pessoas', 'Cadastro de pessoas'));

await visit('/modulos/atendimentos');
shots.push(await screenshot('07-atendimentos', 'Registro de atendimentos'));

await visit('/modulos/encaminhamentos');
shots.push(await screenshot('08-encaminhamentos', 'Fluxo de encaminhamentos'));

await visit('/perfis');
shots.push(await screenshot('09-perfis-permissoes', 'Permissoes por perfil'));

await visit('/usuarios');
shots.push(await screenshot('10-usuarios', 'Gestao de usuarios'));

await visit('/auditoria');
shots.push(await screenshot('11-auditoria', 'Auditoria de atividades e erros'));

const storageState = await context.storageState();
const mobileContext = await browser.newContext({
    storageState,
    viewport: { width: 390, height: 844 },
    isMobile: true,
    deviceScaleFactor: 2,
});
const mobilePage = await mobileContext.newPage();
await mobilePage.goto(`${baseUrl}/dashboard`);
await settle(mobilePage);
await mobilePage.getByRole('button', { name: 'Menu' }).click();
await mobilePage.waitForTimeout(300);
shots.push(await screenshot('12-mobile-menu', 'Navegacao em tela mobile', mobilePage));
await mobileContext.close();

const today = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'long',
    timeZone: 'America/Manaus',
}).format(new Date());

const html = `<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Tutorial SIAY</title>
    <style>
        @page {
            size: A4;
            margin: 15mm 13mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #17202a;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.55;
            background: #ffffff;
        }

        .cover {
            min-height: 255mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-left: 8px solid #047857;
            padding-left: 26px;
            page-break-after: always;
        }

        .brand {
            color: #047857;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
        }

        h1 {
            margin: 12px 0 8px;
            font-size: 32px;
            line-height: 1.1;
        }

        h2 {
            margin: 0 0 8px;
            color: #111827;
            font-size: 20px;
            page-break-after: avoid;
        }

        h3 {
            margin: 18px 0 6px;
            color: #111827;
            font-size: 15px;
        }

        p {
            margin: 0 0 8px;
        }

        ul, ol {
            margin: 6px 0 12px 20px;
            padding: 0;
        }

        li {
            margin: 4px 0;
        }

        code {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 1px 4px;
            font-family: Consolas, monospace;
            font-size: 11px;
        }

        .meta {
            margin-top: 24px;
            max-width: 440px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 14px;
            background: #f9fafb;
        }

        .section {
            page-break-before: always;
        }

        .section:first-of-type {
            page-break-before: auto;
        }

        .step {
            margin: 0 0 14px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            background: #ffffff;
        }

        .note {
            border-left: 4px solid #047857;
            padding: 8px 10px;
            background: #ecfdf5;
            color: #064e3b;
        }

        figure {
            margin: 12px 0 20px;
            page-break-inside: avoid;
        }

        figcaption {
            margin-top: 6px;
            color: #4b5563;
            font-size: 11px;
            font-weight: 700;
        }

        img {
            display: block;
            width: 100%;
            max-height: 190mm;
            object-fit: contain;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #f9fafb;
        }

        .mobile-shot img {
            width: 55%;
            margin: 0 auto;
            max-height: 210mm;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            background: #f9fafb;
        }

        .small {
            color: #4b5563;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <section class="cover">
        <div class="brand">SIAY</div>
        <h1>Tutorial de Uso do Sistema Integrado de Acompanhamento Yanomami</h1>
        <p>Guia operacional inicial para acesso, navegacao, cadastro de casos, gestao de perfis e auditoria.</p>
        <div class="meta">
            <p><strong>Gerado em:</strong> ${today}</p>
            <p><strong>Ambiente local:</strong> <code>${baseUrl}</code></p>
            <p><strong>Usuario inicial:</strong> <code>admin@siay.test</code></p>
            <p><strong>Senha inicial:</strong> <code>password</code></p>
        </div>
    </section>

    <section>
        <h2>1. Acesso ao sistema</h2>
        <div class="step">
            <ol>
                <li>Acesse o endereco local do sistema.</li>
                <li>Informe email e senha.</li>
                <li>Use a opcao de manter conectado somente em computador confiavel.</li>
                <li>Apos entrar, o sistema redireciona para o dashboard conforme as permissoes do perfil.</li>
            </ol>
        </div>
        <p class="note">O cadastro publico foi desativado. Novos usuarios devem ser criados pela tela de Usuarios por um perfil autorizado.</p>
        <figure>
            <img src="${shots.find((shot) => shot.name === '01-login')?.file}" alt="Tela de login">
            <figcaption>Imagem 1 - Tela de login com autenticacao protegida.</figcaption>
        </figure>
    </section>

    <section class="section">
        <h2>2. Dashboard principal</h2>
        <p>O dashboard consolida os indicadores mais importantes para acompanhamento: total de casos, casos ativos, casos criticos, registros sem atualizacao, encaminhamentos pendentes e alertas abertos.</p>
        <div class="grid">
            <div class="card"><strong>Total de casos:</strong> panorama geral dos registros visiveis ao perfil.</div>
            <div class="card"><strong>Casos criticos:</strong> casos classificados como alto risco.</div>
            <div class="card"><strong>Sem atualizacao:</strong> ajuda a identificar acompanhamentos parados.</div>
            <div class="card"><strong>Alertas:</strong> pendencias e riscos que exigem atencao.</div>
        </div>
        <figure>
            <img src="${shots.find((shot) => shot.name === '02-dashboard')?.file}" alt="Dashboard do SIAY">
            <figcaption>Imagem 2 - Dashboard com indicadores e lista de casos recentes.</figcaption>
        </figure>
    </section>

    <section class="section">
        <h2>3. Consulta e abertura de casos</h2>
        <p>Casos sao o nucleo do SIAY. Tudo se conecta ao caso: pessoas, atendimentos, encaminhamentos, alertas, anexos e casa de transito.</p>
        <h3>Consultar casos</h3>
        <ol>
            <li>No menu lateral, clique em <strong>Casos</strong>.</li>
            <li>Use a busca por numero ou resumo.</li>
            <li>Filtre por status ou risco quando necessario.</li>
            <li>Clique em <strong>Abrir</strong> para visualizar o historico completo.</li>
        </ol>
        <figure>
            <img src="${shots.find((shot) => shot.name === '03-casos-listagem')?.file}" alt="Listagem de casos">
            <figcaption>Imagem 3 - Listagem de casos com filtros.</figcaption>
        </figure>

        <h3>Detalhar caso</h3>
        <p>A tela de detalhe mostra resumo, responsavel, pessoas vinculadas, atendimentos, encaminhamentos e alertas.</p>
        <figure>
            <img src="${shots.find((shot) => shot.name === '04-caso-detalhe')?.file}" alt="Detalhe do caso">
            <figcaption>Imagem 4 - Detalhe do caso e historico operacional.</figcaption>
        </figure>

        <h3>Abrir novo caso</h3>
        <ol>
            <li>Clique em <strong>Novo caso</strong>.</li>
            <li>Preencha tipo, status, grau de risco e resumo.</li>
            <li>Informe municipio, comunidade, orgao responsavel e tecnico responsavel.</li>
            <li>Vincule pessoas ja cadastradas quando houver.</li>
            <li>Marque <strong>Caso sigiloso</strong> somente quando o perfil tiver autorizacao para isso.</li>
        </ol>
        <figure>
            <img src="${shots.find((shot) => shot.name === '05-caso-formulario')?.file}" alt="Formulario de caso">
            <figcaption>Imagem 5 - Formulario de abertura ou edicao de caso.</figcaption>
        </figure>
    </section>

    <section class="section">
        <h2>4. Pessoas, familias e cadastros territoriais</h2>
        <p>A tela de Pessoas registra nome tradicional, nome civil, sexo, idade, documentos e vinculo familiar. Familias, comunidades, municipios e orgaos seguem o mesmo padrao: listagem, busca e formulario lateral.</p>
        <ol>
            <li>Acesse o modulo pelo menu lateral.</li>
            <li>Use a busca para localizar registros existentes antes de criar um novo.</li>
            <li>Preencha os campos obrigatorios do formulario lateral.</li>
            <li>Salve e depois vincule o registro ao caso quando necessario.</li>
        </ol>
        <figure>
            <img src="${shots.find((shot) => shot.name === '06-pessoas')?.file}" alt="Cadastro de pessoas">
            <figcaption>Imagem 6 - Modulo de cadastro de pessoas.</figcaption>
        </figure>
    </section>

    <section class="section">
        <h2>5. Atendimentos, encaminhamentos e alertas</h2>
        <h3>Atendimentos</h3>
        <p>Atendimentos registram o que foi feito no caso. Por regra de auditoria, o historico deve ser preservado; correcoes devem ser justificadas em vez de apagar registros.</p>
        <figure>
            <img src="${shots.find((shot) => shot.name === '07-atendimentos')?.file}" alt="Atendimentos">
            <figcaption>Imagem 7 - Registro de atendimentos do caso.</figcaption>
        </figure>

        <h3>Encaminhamentos</h3>
        <p>Encaminhamentos controlam o fluxo entre orgaos. Informe origem, destino, motivo, status, prazo de resposta e retorno recebido.</p>
        <figure>
            <img src="${shots.find((shot) => shot.name === '08-encaminhamentos')?.file}" alt="Encaminhamentos">
            <figcaption>Imagem 8 - Fluxo de encaminhamentos intersetoriais.</figcaption>
        </figure>
    </section>

    <section class="section">
        <h2>6. Perfis, permissoes e usuarios</h2>
        <h3>Permissoes por perfil</h3>
        <p>A tela de perfis define quais modulos e acoes cada perfil pode acessar. Administrador e Supervisor podem receber acesso amplo; perfis operacionais devem receber somente as permissoes necessarias.</p>
        <ol>
            <li>Selecione o perfil na coluna esquerda.</li>
            <li>Marque ou desmarque permissoes por modulo.</li>
            <li>Clique em <strong>Salvar</strong>.</li>
            <li>O efeito passa a valer nos proximos acessos e navegacoes do usuario.</li>
        </ol>
        <figure>
            <img src="${shots.find((shot) => shot.name === '09-perfis-permissoes')?.file}" alt="Permissoes por perfil">
            <figcaption>Imagem 9 - Matriz de permissoes por perfil.</figcaption>
        </figure>

        <h3>Usuarios</h3>
        <p>Usuarios recebem perfil, orgao, municipio e status. Ao desativar alguem, o sistema nao apaga o registro: ele fica inativo e a acao aparece na auditoria.</p>
        <figure>
            <img src="${shots.find((shot) => shot.name === '10-usuarios')?.file}" alt="Gestao de usuarios">
            <figcaption>Imagem 10 - Criacao, edicao e desativacao de usuarios.</figcaption>
        </figure>
    </section>

    <section class="section">
        <h2>7. Auditoria e logs de erro</h2>
        <p>A auditoria registra atividades importantes do sistema: login, visualizacao de caso, criacao, alteracao, atualizacao de permissoes e desativacao de usuario. Cada log guarda usuario, acao, descricao, IP e data.</p>
        <p>Erros de sistema tambem sao armazenados em tabela propria, com classe da excecao, mensagem, arquivo, linha, URL e usuario quando houver.</p>
        <figure>
            <img src="${shots.find((shot) => shot.name === '11-auditoria')?.file}" alt="Auditoria">
            <figcaption>Imagem 11 - Logs de auditoria e ultimos erros.</figcaption>
        </figure>
    </section>

    <section class="section">
        <h2>8. Uso em celular</h2>
        <p>O layout e responsivo. Em telas menores, o menu fica recolhido no topo. Toque no botao de menu para acessar os modulos permitidos ao perfil.</p>
        <figure class="mobile-shot">
            <img src="${shots.find((shot) => shot.name === '12-mobile-menu')?.file}" alt="Menu mobile">
            <figcaption>Imagem 12 - Navegacao mobile com menu responsivo.</figcaption>
        </figure>
    </section>

    <section class="section">
        <h2>9. Boas praticas operacionais</h2>
        <ul>
            <li>Pesquise pessoa, familia ou caso antes de cadastrar um novo registro.</li>
            <li>Classifique o risco com cuidado; alto risco gera prioridade e alerta.</li>
            <li>Registre atendimentos sempre com descricao objetiva e proximo passo.</li>
            <li>Use encaminhamentos para controlar prazos e respostas entre orgaos.</li>
            <li>Nao compartilhe usuario e senha. Cada atividade deve ficar ligada ao usuario real.</li>
            <li>Casos sigilosos devem ser usados somente quando houver necessidade institucional.</li>
            <li>Revise periodicamente a tela de auditoria e os casos sem atualizacao.</li>
        </ul>
        <p class="small">Este tutorial foi gerado automaticamente com capturas do ambiente local do SIAY.</p>
    </section>
</body>
</html>`;

await fs.writeFile(htmlPath, html, 'utf8');

const pdfPage = await browser.newPage();
await pdfPage.goto(pathToFileURL(htmlPath).href, { waitUntil: 'networkidle' });
await pdfPage.pdf({
    path: pdfPath,
    format: 'A4',
    printBackground: true,
    preferCSSPageSize: true,
});

await browser.close();

console.log(pdfPath);
