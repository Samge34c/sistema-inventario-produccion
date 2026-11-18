"""Verifica SCRUM-22 en una base de prueba, sin modificar registros existentes."""
import json
import os
import re
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

# La identidad se verifica antes de obtener el formulario y antes de cada POST.
destino = urllib.parse.urlparse(url)
if destino.scheme != 'http' or destino.hostname not in ('127.0.0.1', 'localhost'):
    raise ValueError('Usar únicamente el servidor HTTP local de la copia temporal.')
nombres = re.findall(r'(?:^|;)dbname=([^;]+)', dsn)
if len(nombres) != 1 or not re.fullmatch(r'prueba_scrum22_[0-9a-f]{24}', nombres[0]):
    raise ValueError('La base debe ser nueva: prueba_scrum22_ y 24 caracteres hexadecimales.')
nombreBase = nombres[0]
rutaPrueba = os.environ.get('SCRUM22_AISLAMIENTO_RUTA', '')
testigo = os.environ.get('SCRUM22_AISLAMIENTO_TESTIGO', '')
huella = os.environ.get('SCRUM22_AISLAMIENTO_CONFIG', '')
if not re.fullmatch(r'/comprobar-aislamiento-[0-9a-f]{24}\.php', rutaPrueba):
    raise ValueError('Falta comprobar el servidor: ejecutar scrum22-aislado.py.')
if not re.fullmatch(r'[0-9a-f]{64}', testigo) or not re.fullmatch(r'[0-9a-f]{64}', huella):
    raise ValueError('Falta el testigo o la huella de la configuración temporal.')

def verificarAislamiento():
    codigo = ('$c=new PDO(getenv("SCRUM22_PRUEBA_DSN"),'
              'getenv("SCRUM22_USUARIO")?:"root",getenv("SCRUM22_CLAVE")?:"");'
              '$c->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);'
              'echo json_encode(["base"=>$c->query("SELECT DATABASE()")->fetchColumn(),'
              '"testigo"=>$c->query("SELECT testigo FROM prueba_aislamiento")->fetchColumn()]);')
    identidad = json.loads(subprocess.check_output([php, '-r', codigo], text=True))
    with urllib.request.urlopen(url + rutaPrueba, timeout=10) as respuesta:
        servidor = json.load(respuesta)
    if identidad != {'base': nombreBase, 'testigo': testigo}:
        raise RuntimeError('El verificador no corresponde a la base temporal de esta ejecución.')
    if servidor != {'base': nombreBase, 'testigo': testigo, 'config_sha256': huella}:
        raise RuntimeError('La aplicación y el verificador no comparten la misma base temporal o configuración.')

verificarAislamiento()

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
if not campos.token or len(campos.token) != 64:
    raise RuntimeError('El formulario no incluyó el token esperado.')

def probar(nombre, token=None, origen=None, otro_cliente=False, cantidad='1', esperado=403, altas=0):
    verificarAislamiento()
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
    if not correcto:
        raise RuntimeError('Resultado inesperado: ' + json.dumps(resultados[-1]))

probar('CP14_origen_ajeno_sin_token', origen='https://origen-ajeno.example')
probar('token_incorrecto', token='incorrecto')
probar('token_tipo_arreglo', token=['incorrecto'])
probar('token_otra_sesion', token=campos.token, otro_cliente=True)
probar('sin_sesion_sin_token', otro_cliente=True)
probar('alta_valida', token=campos.token, esperado=200, altas=1)
probar('cantidad_invalida', token=campos.token, cantidad='-1', esperado=200)
print(json.dumps({'fecha': datetime.now(ZoneInfo('America/Bogota')).isoformat(),
                  'aislamiento': {'verificado': True, 'base': nombreBase,
                                  'config_sha256': huella, 'antes_de_cada_post': True},
                  'casos': resultados}, ensure_ascii=False, indent=2))
