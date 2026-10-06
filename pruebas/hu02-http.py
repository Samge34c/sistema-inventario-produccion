"""Comprobaciones HTTP sobre una base local desechable, sin dependencias externas."""

import html
from html.parser import HTMLParser
from http.cookiejar import CookieJar
import os
import re
import secrets
from urllib.error import HTTPError
from urllib.parse import urlencode, urlparse
from urllib.request import build_opener, HTTPCookieProcessor, Request


class LeerCampos(HTMLParser):
    def __init__(self):
        super().__init__()
        self.campos = {}

    def handle_starttag(self, etiqueta, atributos):
        datos = dict(atributos)
        if etiqueta == "input" and "name" in datos:
            self.campos[datos["name"]] = datos.get("value", "")


url = os.environ.get("HU02_URL", "http://127.0.0.1:8000").rstrip("/")
if urlparse(url).hostname not in ("localhost", "127.0.0.1"):
    raise ValueError("Usar un despliegue local aislado de prueba")
cliente = build_opener(HTTPCookieProcessor(CookieJar()))
numeroPruebas = 0


def solicitar(ruta, datos=None):
    peticion = Request(url + ruta, data=urlencode(datos).encode() if datos is not None else None)
    try:
        with cliente.open(peticion, timeout=10) as respuesta:
            return respuesta.status, respuesta.read().decode()
    except HTTPError as error:
        return error.code, error.read().decode()


def comprobar(esCorrecto, caso):
    global numeroPruebas
    if not esCorrecto:
        raise AssertionError(caso)
    numeroPruebas += 1
    print(f"OK HTTP {numeroPruebas}: {caso}")


base = "/modules/inventario/"
nombre = "Harina HU02 HTTP " + secrets.token_hex(5)
estado, pagina = solicitar(base + "guardar.php", {
    "nombre": nombre, "unidad_medida": "g", "cantidad_disponible": "10000", "stock_minimo": "1000"
})
comprobar(estado == 200 and nombre in pagina and "10000.00" in pagina, "HU01 registra y consulta 10000 g")
fila = re.search(r"<tr>\s*<td>" + nombre + r"</td>.*?</tr>", pagina, re.S)
if fila is None:
    raise AssertionError("No se encontró la materia prima de prueba")
idMateria = re.search(r"editar.php\?id=(\d+)", fila.group()).group(1)
estado, pagina = solicitar(base + "movimientos.php")
campos = LeerCampos()
campos.feed(pagina)
token = campos.campos["token"]


def registrar(tipo, cantidad, observacion="Caso HTTP reproducible", tokenEnviado=token):
    return solicitar(base + "guardar-movimiento.php", {
        "materia_prima_id": idMateria, "tipo": tipo, "cantidad": cantidad,
        "observacion": observacion, "token": tokenEnviado
    })


def obtenerSaldo(pagina):
    opcion = re.search(r'<option value="' + idMateria + r'"[^>]*>(.*?)</option>', pagina, re.S)
    return html.unescape(opcion.group(1)) if opcion else ""


estado, pagina = registrar("ENTRADA", "2000")
comprobar(estado == 200 and "12000.00" in obtenerSaldo(pagina), "entrada aumenta a 12000")
estado, pagina = registrar("SALIDA", "1000")
comprobar(estado == 200 and "11000.00" in obtenerSaldo(pagina), "salida disminuye a 11000")
estado, pagina = registrar("PENDIENTE", "5000")
comprobar(estado == 200 and "11000.00" in obtenerSaldo(pagina), "pendiente conserva 11000 disponibles")
comprobar("5000.00 g" in pagina, "pendiente visible por separado")
estado, pagina = registrar("SALIDA", "11000.01")
comprobar("supera la existencia" in pagina, "salida excesiva informa error controlado")
comprobar("11000.00" in obtenerSaldo(pagina), "rechazo conserva existencia")
estado, pagina = registrar("SALIDA", "1", tokenEnviado="invalido")
comprobar(estado == 403, "CSRF inválido rechazado")
estado, pagina = solicitar(base + "guardar-movimiento.php")
comprobar(estado == 405, "GET al registro rechazado")
estado, pagina = registrar("ENTRADA", "1.001")
comprobar(estado == 200 and "dos decimales" in pagina, "backend rechaza más de dos decimales")
observacion = "<script>window.hu02Ataque=true</script>' OR 1=1 --"
estado, pagina = registrar("PENDIENTE", "1", observacion)
comprobar(estado == 200 and html.escape(observacion, quote=True).replace("&#x27;", "&#039;") in pagina,
          "consulta preparada conserva texto y HTML escapa el script")
comprobar("11000.00" in obtenerSaldo(pagina), "texto de prueba no modifica existencias")
estado, pagina = solicitar(base + "editar.php?id=" + idMateria, {
    "id": idMateria, "nombre": nombre, "unidad_medida": "g", "cantidad_disponible": "9000", "stock_minimo": "1000"
})
comprobar("entrada o salida" in pagina, "edición directa del saldo bloqueada")
estado, pagina = solicitar(base + "eliminar.php", {"id": idMateria})
comprobar(estado == 200 and "historial" in pagina, "borrado de materia referenciada informa error")
comprobar(nombre in pagina and "11000.00" in pagina, "materia e inventario conservados")
print(f"RESULTADO HTTP: {numeroPruebas} comprobaciones correctas. No evalúa consola JavaScript.")
