const base = (process.env.HU02_URL || "http://127.0.0.1:8001").replace(/\/+$/, "");
if (!["localhost", "127.0.0.1"].includes(new URL(base).hostname)) {
  throw new Error("Las pruebas solo pueden ejecutarse en un servidor local.");
}

const cookies = new Map();
let total = 0;

async function solicitar(ruta, datos) {
  let url = new URL(ruta, base).href;
  let metodo = datos ? "POST" : "GET";
  let cuerpo = datos ? new URLSearchParams(datos).toString() : undefined;

  for (let intento = 0; intento < 6; intento++) {
    const headers = {};
    if (cuerpo !== undefined) headers["Content-Type"] = "application/x-www-form-urlencoded";
    if (cookies.size) headers.Cookie = [...cookies].map(([k, v]) => `${k}=${v}`).join("; ");

    const r = await fetch(url, {
      method: metodo, headers, body: cuerpo, redirect: "manual"
    });

    for (const c of (r.headers.getSetCookie?.() || [])) {
      const parte = c.split(";")[0];
      const i = parte.indexOf("=");
      if (i > 0) cookies.set(parte.slice(0, i), parte.slice(i + 1));
    }

    if ([301, 302, 303, 307, 308].includes(r.status) && r.headers.get("location")) {
      url = new URL(r.headers.get("location"), url).href;
      if ([301, 302, 303].includes(r.status)) {
        metodo = "GET";
        cuerpo = undefined;
      }
      continue;
    }
    return { status: r.status, pagina: await r.text() };
  }
  throw new Error("Demasiadas redirecciones HTTP");
}

function comprobar(ok, nombre) {
  if (!ok) throw new Error("FALLO: " + nombre);
  console.log(`OK HTTP ${++total}: ${nombre}`);
}

function escapar(s) {
  return s.replace(/&/g, "&amp;").replace(/</g, "&lt;")
    .replace(/>/g, "&gt;").replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function saldo(pagina, id) {
  const re = new RegExp('<option\\s+value="' + id + '"[^>]*>(.*?)</option>', "s");
  const m = pagina.match(re);
  return m ? m[1].replace(/&amp;/g, "&").replace(/&quot;/g, '"')
    .replace(/&#039;|&#x27;/g, "'").replace(/&lt;/g, "<").replace(/&gt;/g, ">") : "";
}

async function main() {
  const baseRuta = "/modules/inventario/";
  const nombre = "Harina HU02 HTTP" + require("crypto").randomBytes(5).toString("hex");

  let r = await solicitar(baseRuta + "guardar.php", {
    nombre, unidad_medida: "g", cantidad_disponible: "10000", stock_minimo: "1000"
  });
  comprobar(r.status === 200 && r.pagina.includes(nombre) && r.pagina.includes("10000.00"),
    "HU01 registra y consulta 10000 g");

  const fila = r.pagina.match(new RegExp("<tr>\\s*<td>" + nombre + "</td>[\\s\\S]*?</tr>"));
  if (!fila) throw new Error("No se encontró la materia prima creada en la base de pruebas.");
  const id = fila[0].match(/editar\\.php\\?id=(\\d+)/)?.[1];
  if (!id) throw new Error("No se encontró el ID de la materia prima de prueba.");

  r = await solicitar(baseRuta + "movimientos.php");
  const token = r.pagina.match(/name=["']token["'][^>]*value=["']([^"']*)["']/i)?.[1]
    || r.pagina.match(/value=["']([^"']*)["'][^>]*name=["']token["']/i)?.[1];
  if (!token) throw new Error("No se encontró el token CSRF en movimientos.php.");

  const registrar = (tipo, cantidad, observacion = "Caso HTTP reproducible", tokenEnviado = token) =>
    solicitar(baseRuta + "guardar-movimiento.php", {
      materia_prima_id: id, tipo, cantidad, observacion, token: tokenEnviado
    });

  r = await registrar("ENTRADA", "2000");
  comprobar(r.status === 200 && saldo(r.pagina, id).includes("12000.00"), "entrada aumenta a 12000");

  r = await registrar("SALIDA", "1000");
  comprobar(r.status === 200 && saldo(r.pagina, id).includes("11000.00"), "salida disminuye a 11000");

  r = await registrar("PENDIENTE", "5000");
  comprobar(r.status === 200 && saldo(r.pagina, id).includes("11000.00"), "pendiente conserva 11000 disponibles");
  comprobar(r.pagina.includes("5000.00 g"), "pendiente visible por separado");

  r = await registrar("SALIDA", "11000.01");
  comprobar(r.pagina.includes("supera la existencia"), "salida excesiva informa error controlado");
  comprobar(saldo(r.pagina, id).includes("11000.00"), "rechazo conserva existencia");

  r = await registrar("SALIDA", "1", "Caso HTTP reproducible", "invalido");
  comprobar(r.status === 403, "CSRF inválido rechazado");

  r = await solicitar(baseRuta + "guardar-movimiento.php");
  comprobar(r.status === 405, "GET al registro rechazado");

  r = await registrar("ENTRADA", "1.001");
  comprobar(r.status === 200 && r.pagina.includes("dos decimales"),
    "backend rechaza más de dos decimales");

  const observacion = "<script>window.hu02Ataque=true</script>' OR 1=1 --";
  r = await registrar("PENDIENTE", "1", observacion);
  comprobar(r.status === 200 && r.pagina.includes(escapar(observacion)),
    "texto de prueba escapado y conservado");
  comprobar(saldo(r.pagina, id).includes("11000.00"),
    "texto de prueba no modifica existencias");

  r = await solicitar(baseRuta + "editar.php?id=" + id, {
    id, nombre, unidad_medida: "g", cantidad_disponible: "9000", stock_minimo: "1000"
  });
  comprobar(r.pagina.includes("entrada o salida"), "edición directa del saldo bloqueada");

  r = await solicitar(baseRuta + "eliminar.php", { id });
  comprobar(r.status === 200 && r.pagina.includes("historial"),
    "borrado de materia referenciada informa error");
  comprobar(r.pagina.includes(nombre) && r.pagina.includes("11000.00"),
    "materia e inventario conservados");

  console.log(`RESULTADO HTTP: ${total} comprobaciones correctas.`);
  console.log("La materia de prueba queda en la base temporal, que es desechable.");
}

main().catch(e => {
  console.error("ERROR HTTP:", e.message);
  process.exitCode = 1;
});
