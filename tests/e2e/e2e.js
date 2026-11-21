const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = 'http://127.0.0.1:8000';
const TMP = process.env.TEMP || require('os').tmpdir();
const stamp = Date.now();
const EMAIL = `e2e.${stamp}@mail.com`;
const PASS = 'Password1!';
const PASS2 = 'Password2@';
const CURSO_TITULO = `Curso E2E ${stamp}`;

const resultados = [];
const erroresConsola = [];
const navegaciones = [];
const peticiones = [];

function ok(nombre, cond, extra) {
  resultados.push({ nombre, ok: !!cond, extra: extra || '' });
  console.log(`${cond ? 'PASS' : 'FAIL'} - ${nombre}${extra ? ' :: ' + extra : ''}`);
}

async function registrarPagina(page, etiqueta) {
  page.on('pageerror', (e) => erroresConsola.push(`[${etiqueta}] pageerror: ${e.message}`));
  page.on('console', (m) => {
    if (m.type() === 'error') erroresConsola.push(`[${etiqueta}] console: ${m.text()}`);
  });
  page.on('framenavigated', (f) => {
    if (f === page.mainFrame()) navegaciones.push(`${etiqueta} -> ${f.url()}`);
  });
  page.on('request', (r) => {
    if (r.method() !== 'GET') peticiones.push(`${etiqueta} ${r.method()} ${r.url()}`);
  });
}

async function captura(page, nombre) {
  const archivo = path.join(TMP, `pwci-e2e-${nombre}.png`);
  await page.screenshot({ path: archivo, fullPage: true }).catch(() => {});
  return archivo;
}

async function main() {
  const browser = await chromium.launch({ channel: 'chrome', headless: true });

  const anon = await browser.newContext();
  const p1 = await anon.newPage();
  await registrarPagina(p1, 'catalogo');
  await p1.goto(BASE + '/', { waitUntil: 'load' });
  await p1.waitForSelector('.js-course-card', { timeout: 15000 });
  const totalInicial = await p1.locator('.js-course-card').count();
  ok('Catalogo: carga cursos via fetch', totalInicial > 0, `${totalInicial} tarjetas`);

  const catBtns = p1.locator('#categorias-list [data-categoria-id]');
  ok('Catalogo: categorias pintadas', (await catBtns.count()) >= 2, `${await catBtns.count()} categorias`);

  const navAntes = navegaciones.length;
  await catBtns.nth(0).click();
  await p1.waitForTimeout(1200);
  const titulo = await p1.locator('#catalogo-titulo').textContent();
  ok('Catalogo: filtrar por categoria sin recargar', navegaciones.length === navAntes && titulo.trim() !== 'Cursos disponibles', `titulo="${titulo.trim()}"`);

  await p1.fill('#searchCurso', 'zzzz-no-existe');
  await p1.waitForTimeout(300);
  const visiblesTrasBusqueda = await p1.locator('.js-course-card:visible').count();
  ok('Catalogo: busqueda local sin resultados', visiblesTrasBusqueda === 0, `${visiblesTrasBusqueda} visibles`);
  await p1.fill('#searchCurso', '');
  await captura(p1, 'catalogo');
  await anon.close();

  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  await registrarPagina(page, 'registro');
  await page.goto(BASE + '/registro', { waitUntil: 'load' });

  await page.fill('#nombre', 'E2E Usuario');
  await page.fill('#correo', EMAIL);
  await page.fill('#contrasena', PASS);
  await page.fill('#contrasena_confirmacion', 'Distinta1!');
  await page.click('#btnCrearCuenta');
  await page.waitForTimeout(800);
  const errReg = await page.locator('#register-alert .alert-danger').count();
  const urlReg = page.url();
  ok('Registro: contrasenas distintas muestra error sin recargar', errReg > 0 && urlReg.endsWith('/registro'), `alertas=${errReg} url=${urlReg}`);

  await page.fill('#contrasena_confirmacion', PASS);
  const pngPath = path.join(TMP, 'pwci-e2e-avatar.png');
  fs.writeFileSync(pngPath, Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', 'base64'));
  await page.setInputFiles('#foto_perfil', pngPath);
  await page.click('#btnCrearCuenta');
  await page.waitForURL(/\/dashboard/, { timeout: 15000 }).catch(() => {});
  ok('Registro: valido con foto redirige a dashboard', page.url().includes('/dashboard'), page.url());
  const formToken = await page.locator('#formCanjearToken').count();
  ok('Verificación: formulario de canje disponible en dashboard', formToken === 1, `formularios=${formToken}`);

  await page.goto(BASE + '/perfil', { waitUntil: 'load' });
  await page.fill('#nombre', 'E2E Editado');
  await page.selectOption('#genero', 'femenino');
  await page.click('#btnGuardarPerfil');
  await page.waitForTimeout(1200);
  const alertaPerfil = await page.locator('#profile-alert .alert-success').count();
  const nombreHeader = await page.locator('#perfil-nombre').textContent();
  ok('Perfil: guardar datos via PATCH sin recargar', alertaPerfil > 0 && nombreHeader.trim() === 'E2E Editado', `nombre="${nombreHeader.trim()}"`);

  const avatarSrc = await page.locator('#perfil-avatar-img').getAttribute('src').catch(() => null);
  ok('Perfil: avatar muestra la foto subida al registro', !!avatarSrc && avatarSrc.includes('uploads/avatars'), avatarSrc || 'sin img');

  await page.fill('#contrasena_actual', 'Incorrecta9!');
  await page.fill('#contrasena_nueva', PASS2);
  await page.fill('#contrasena_confirmacion', PASS2);
  await page.click('#btnCambiarContrasena');
  await page.waitForTimeout(1200);
  const errPass = await page.locator('#password-alert .alert-danger').count();
  ok('Perfil: contrasena actual incorrecta muestra error', errPass > 0, `alertas=${errPass}`);

  await page.fill('#contrasena_actual', PASS);
  await page.click('#btnCambiarContrasena');
  await page.waitForTimeout(1200);
  const okPass = await page.locator('#password-alert .alert-success').count();
  ok('Perfil: cambio de contrasena exitoso', okPass > 0, `alertas=${okPass}`);

  const peticionesAntesLogout = peticiones.length;
  await page.click('form[action$="/logout"] button[type="submit"]');
  await page.waitForURL(/\/login/, { timeout: 10000 });
  const peticionesLogout = peticiones.slice(peticionesAntesLogout);
  const logoutApi = peticionesLogout.some((p) => p.includes('/api/v1/logout'));
  const logoutWeb = peticionesLogout.some((p) => /POST http:\/\/127\.0\.0\.1:8000\/logout(?:\?|$)/.test(p));
  ok('Logout: cierra sesión vía fetch sin envío web', logoutApi && !logoutWeb, peticionesLogout.join(' | '));
  await page.goto(BASE + '/login', { waitUntil: 'load' });
  await page.fill('#correo', EMAIL);
  await page.fill('#contrasena', 'Incorrecta9!');
  await page.click('#btnLogin');
  await page.waitForTimeout(1200);
  const errLogin = await page.locator('#login-alert .alert-danger').count();
  ok('Login: credenciales incorrectas muestra alerta sin recargar', errLogin > 0 && page.url().endsWith('/login'), `alertas=${errLogin}`);

  await page.fill('#contrasena', PASS2);
  await page.click('#btnLogin');
  await page.waitForURL(/\/dashboard/, { timeout: 15000 }).catch(() => {});
  ok('Login: credenciales validas redirige a dashboard', page.url().includes('/dashboard'), page.url());
  await ctx.close();

  const adm = await browser.newContext();
  const pa = await adm.newPage();
  await registrarPagina(pa, 'admin');
  await pa.goto(BASE + '/login', { waitUntil: 'load' });
  await pa.fill('#correo', 'admin@soscur.test');
  await pa.fill('#contrasena', PASS);
  await pa.click('#btnLogin');
  await pa.waitForURL(/\/dashboard/, { timeout: 15000 }).catch(() => {});
  ok('Admin: login exitoso', pa.url().includes('/dashboard'), pa.url());

  await pa.goto(BASE + '/admin/usuarios', { waitUntil: 'load' });
  const fila = pa.locator(`tr[data-usuario-id]`, { hasText: EMAIL });
  ok('Admin: usuario de prueba listado', (await fila.count()) === 1);
  const idUsuario = await fila.getAttribute('data-usuario-id');

  const navAdminFiltros = navegaciones.length;
  await pa.fill('#q', EMAIL);
  await pa.waitForTimeout(900);
  const filasTrasBusqueda = await pa.locator('#usuarios-tbody tr[data-usuario-id]').count();
  ok('Admin: búsqueda filtra sin recargar', filasTrasBusqueda === 1 && navegaciones.length === navAdminFiltros, `filas=${filasTrasBusqueda}`);
  await pa.fill('#q', '');
  await pa.waitForTimeout(900);

  const navAdmin = navegaciones.length;
  await fila.locator('form[data-user-action="bloquear"] button[type="submit"]').click();
  await pa.waitForTimeout(400);
  const motivoRequerido = await fila.locator('form[data-user-action="bloquear"] input[name="motivo"]').evaluate((el) => el.validity.valueMissing);
  ok('Admin: motivo requerido antes de bloquear', motivoRequerido === true);

  await fila.locator('form[data-user-action="bloquear"] input[name="motivo"]').fill('Prueba E2E');
  await fila.locator('form[data-user-action="bloquear"] button[type="submit"]').click();
  await pa.waitForTimeout(1200);
  const badgeBloq = (await fila.locator('.js-estado-badge').textContent()).trim();
  const reactivarVisible = await fila.locator('.js-acciones-bloqueado').isVisible();
  ok('Admin: bloquear actualiza fila sin recargar', badgeBloq === 'Bloqueado' && reactivarVisible && navegaciones.length === navAdmin, `badge=${badgeBloq}`);
  const alertaAdmin = await pa.locator('#admin-alert .alert-success').count();
  ok('Admin: muestra aviso de exito', alertaAdmin > 0);

  await fila.locator('form[data-user-action="reactivar"] button[type="submit"]').click();
  await pa.waitForTimeout(1200);
  const badgeAct = (await fila.locator('.js-estado-badge').textContent()).trim();
  ok('Admin: reactivar restaura fila sin recargar', badgeAct === 'Activo' && navegaciones.length === navAdmin, `badge=${badgeAct}`);
  await captura(pa, 'admin-usuarios');

  await pa.goto(BASE + '/dashboard', { waitUntil: 'load' });
  await pa.click('button[data-bs-target="#modalCurso"]');
  await pa.waitForSelector('#modalCurso.show', { timeout: 5000 });
  await pa.waitForFunction(() => document.querySelectorAll('#curso_categoria_id option').length > 0 && !document.getElementById('curso_categoria_id').disabled, null, { timeout: 10000 }).catch(() => {});
  const opciones = await pa.locator('#curso_categoria_id option').count();
  ok('Curso: categorias cargadas en el modal', opciones >= 2, `${opciones} opciones`);

  await pa.fill('#curso_titulo', CURSO_TITULO);
  await pa.fill('#curso_descripcion', 'Creado por prueba E2E');
  await pa.fill('#curso_precio', '250');
  await pa.click('#btnGuardarCurso');
  await pa.waitForTimeout(1500);
  const itemCreado = await pa.locator('#cursos-creados li', { hasText: CURSO_TITULO }).count();
  const modalCerrado = await pa.locator('#modalCurso.show').count();
  ok('Curso: crear desde modal aparece en lista sin recargar', itemCreado === 1 && modalCerrado === 0, `items=${itemCreado} modalAbierto=${modalCerrado}`);
  await captura(pa, 'dashboard-curso');
  await adm.close();

  await browser.close();

  console.log('\n=== Errores de consola / pagina ===');
  erroresConsola.forEach((e) => console.log(e));
  if (erroresConsola.length === 0) console.log('(ninguno)');

  const fallos = resultados.filter((r) => !r.ok).length;
  console.log(`\nResultado: ${resultados.length - fallos}/${resultados.length} pasaron`);
  fs.writeFileSync(path.join(TMP, 'pwci-e2e-meta.json'), JSON.stringify({ EMAIL, CURSO_TITULO, idUsuario }, null, 2));
  process.exit(fallos > 0 ? 1 : 0);
}

main().catch((e) => {
  console.error('ERROR FATAL:', e);
  process.exit(2);
});
