"""Verifica SCRUM-22 en una base de prueba, sin modificar registros existentes."""
import json
import os
import secrets
import subprocess
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime
from html.parser import HTMLParser
from http.cookiejar import CookieJar
from zoneinfo import ZoneInfo

url = os.environ['SCRUM22_URL'].rstrip('/')
dsn = os.environ['SCRUM22_PRUEBA_DSN']
php = os.environ.get('SCRUM22_PHP', 'php')
prefijo = 'Prueba_SCRUM22_' + secrets.token_hex(6)
resultados = []

class Campos(HTMLParser):
    token = None

    def handle_starttag(self, etiqueta, atributos):
        datos = dict(atributos)
        if etiqueta == 'input' and datos.get('name') == 'token':
            self.token = datos.get('value')

def contar():
    codigo = ('$c=new PDO(getenv("SCRUM22_PRUEBA_DSN"),'
              'getenv("SCRUM22_USUARIO")?:"root",getenv("SCRUM22_CLAVE")?:"");'
              '$q=$c->prepare("SELECT COUNT(*) FROM materias_primas WHERE nombre LIKE ?");'
              '$q->execute([$argv[1]."%"]);echo $q->fetchColumn();')
    return int(subprocess.check_output([php, '-r', codigo, prefijo], text=True))

def navegador():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(CookieJar()))

cliente = navegador()
with cliente.open(url + '/modules/inventario/index.php') as respuesta:
    pagina = respuesta.read().decode()
campos = Campos()
campos.feed(pagina)
assert campos.token and len(campos.token) == 64, 'El formulario no incluyó el token'

def probar(nombre, token=None, origen=None, otro_cliente=False, cantidad='1', esperado=403, altas=0):
    antes = contar()
    datos = {'nombre': prefijo + '_' + nombre, 'unidad_medida': 'g',
             'cantidad_disponible': cantidad, 'stock_minimo': '0'}
    if token is not None:
        datos['token[]' if isinstance(token, list) else 'token'] = token
    peticion = urllib.request.Request(
        url + '/modules/inventario/guardar.php',
        data=urllib.parse.urlencode(datos, doseq=True).encode(),
        headers={'Origin': origen} if origen else {})
    try:
        with (navegador() if otro_cliente else cliente).open(peticion) as respuesta:
            estado = respuesta.status
    except urllib.error.HTTPError as error:
        estado = error.code
    despues = contar()
    correcto = estado == esperado and despues - antes == altas
    resultados.append({'caso': nombre, 'http': estado, 'registros_antes': antes,
                       'registros_despues': despues, 'aprobado': correcto})
    assert correcto, resultados[-1]

probar('CP14_origen_ajeno_sin_token', origen='https://origen-ajeno.example')
probar('token_incorrecto', token='incorrecto')
probar('token_tipo_arreglo', token=['incorrecto'])
probar('token_otra_sesion', token=campos.token, otro_cliente=True)
probar('sin_sesion_sin_token', otro_cliente=True)
probar('alta_valida', token=campos.token, esperado=200, altas=1)
probar('cantidad_invalida', token=campos.token, cantidad='-1', esperado=200)
print(json.dumps({'fecha': datetime.now(ZoneInfo('America/Bogota')).isoformat(),
                  'casos': resultados}, ensure_ascii=False, indent=2))
