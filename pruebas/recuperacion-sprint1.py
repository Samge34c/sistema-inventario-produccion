"""Comprueba acceso, inventario y recetas con copia y base nuevas, sin datos del negocio."""

import argparse
from datetime import datetime
from zoneinfo import ZoneInfo
import hashlib
from http.cookiejar import CookieJar
import json
import os
from pathlib import Path
import re
import secrets
import shutil
import socket
import subprocess
import tempfile
import time
from urllib.error import HTTPError
from urllib.parse import urlencode
from urllib.request import build_opener, HTTPCookieProcessor, Request, urlopen


def ejecutar():
    argumentos = argparse.ArgumentParser(description=__doc__)
    argumentos.add_argument('--salida', type=Path, required=True)
    opciones = argumentos.parse_args()
    if opciones.salida.exists():
        raise ValueError('Utilice una salida nueva para conservar la evidencia anterior.')
    raiz = Path(__file__).resolve().parents[1]
    php = os.environ.get('RECUPERACION_PHP', 'php')
    dsn = os.environ['RECUPERACION_SERVIDOR_DSN']
    if not re.fullmatch(r'mysql:host=(localhost|127\.0\.0\.1)(;port=\d+)?(;charset=utf8mb4)?', dsn):
        raise ValueError('Indique un servidor MySQL local sin dbname ni credenciales en el DSN.')
    nombreBase = 'prueba_recuperacion_' + secrets.token_hex(12)
    entorno = os.environ.copy()
    entorno.update({'INVENTARIO_DSN': dsn + ';dbname=' + nombreBase,
                    'INVENTARIO_USUARIO': os.environ.get('RECUPERACION_USUARIO', 'root'),
                    'INVENTARIO_CLAVE': os.environ.get('RECUPERACION_CLAVE', '')})
    resumen = {'inicio': datetime.now(ZoneInfo('America/Bogota')).isoformat(),
               'ciudad_referencia': 'Villavicencio', 'modo': 'Comprobaciones automatizadas',
               'base_temporal': nombreBase, 'casos': [], 'aprobado': False,
               'fuentes_sha256': {str(p.relative_to(raiz)): hashlib.sha256(p.read_bytes()).hexdigest()
                                 for p in raiz.rglob('*') if p.is_file() and '.git' not in p.parts
                                 and 'node_modules' not in p.parts and p.suffix in ('.php', '.sql', '.js', '.py')},
               'entorno': {'php': subprocess.check_output([php, '-r', 'echo PHP_VERSION;'], text=True),
                           'python': os.sys.version.split()[0]}}
    creada = False
    servidor = None

    def sql(codigo, *datos):
        prefijo = ('$c=new PDO(getenv("INVENTARIO_DSN"),getenv("INVENTARIO_USUARIO"),getenv("INVENTARIO_CLAVE"));'
                   '$c->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);')
        return subprocess.check_output([php, '-r', prefijo + codigo, '--', *datos], env=entorno, text=True, timeout=30)

    def comprobar(condicion, nombre):
        resumen['casos'].append({'caso': nombre, 'aprobado': bool(condicion)})
        if not condicion:
            raise RuntimeError('Falló: ' + nombre)

    try:
        entornoServidor = entorno.copy()
        entornoServidor['INVENTARIO_DSN'] = dsn
        subprocess.run([php, '-r', '$c=new PDO(getenv("INVENTARIO_DSN"),getenv("INVENTARIO_USUARIO"),getenv("INVENTARIO_CLAVE"));$c->exec("CREATE DATABASE `".$argv[1]."`");', nombreBase], env=entornoServidor, check=True)
        creada = True
        esquema = (raiz / 'pruebas/datos/schema-anterior-hu01.sql').read_text()
        esquema = re.sub(r'CREATE DATABASE[^;]*;', '', esquema, count=1)
        esquema = re.sub(r'USE sistema_inventario\s*;', '', esquema, count=1)
        if re.search(r'\bUSE\b|\b(?:CREATE|DROP)\s+DATABASE\b', esquema, re.I):
            raise ValueError('El esquema intenta seleccionar otra base.')
        sql('$c->exec($argv[1]);', esquema)
        migracion = (raiz / 'database/migraciones/002-movimientos-pendientes.sql').read_text()
        migracion = re.sub(r'USE sistema_inventario\s*;', '', migracion, count=1)
        if re.search(r'\bUSE\s+|\b(?:CREATE|DROP)\s+DATABASE\b', migracion, re.I):
            raise ValueError('La migración intenta seleccionar otra base.')
        sql('$c->exec($argv[1]);', migracion)
        sql('$c->exec($argv[1]);', (raiz / 'database/migraciones/003-usuarios.sql').read_text())
        sql('$c->exec($argv[1]);', (raiz / 'database/migraciones/004-proteger-ingredientes.sql').read_text())
        comprobar(sql('echo $c->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()")->fetchColumn();') == '6', 'migraciones 002 a 004 aplicadas sobre esquema anterior')
        comprobar(sql('echo $c->query($argv[1])->fetchColumn();', "SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='receta_ingredientes' AND DELETE_RULE='RESTRICT'") == '1', 'migración protege los ingredientes contra eliminación')
        resumen['entorno']['base_de_datos'] = sql('echo $c->query("SELECT VERSION()")->fetchColumn();')
        correo = 'cuenta-' + secrets.token_hex(8) + '@example.invalid'
        clave = secrets.token_urlsafe(24)
        entorno['INVENTARIO_CLAVE_INICIAL'] = clave
        subprocess.run([php, str(raiz / 'herramientas/crear-usuario.php'), correo, 'Cuenta ficticia'], env=entorno, check=True, capture_output=True)
        entorno.pop('INVENTARIO_CLAVE_INICIAL')
        sql('$c->exec($argv[1]);', "INSERT INTO materias_primas (nombre,unidad_medida,cantidad_disponible) VALUES ('Harina','g',10000),('Azúcar','g',10000)")
        saldoAntes = sql('echo json_encode($c->query("SELECT id,cantidad_disponible FROM materias_primas ORDER BY id")->fetchAll(PDO::FETCH_ASSOC));')
        with tempfile.TemporaryDirectory(prefix='recuperacion-') as temporal:
            copia = Path(temporal) / 'aplicacion'
            shutil.copytree(raiz, copia, ignore=shutil.ignore_patterns('.git', 'node_modules', '__pycache__'))
            rutaPrueba = 'identidad-' + secrets.token_hex(12) + '.php'
            (copia / rutaPrueba).write_text('<?php require __DIR__."/config/database.php"; header("Content-Type: application/json"); echo json_encode(["base"=>$conexion->query("SELECT DATABASE()")->fetchColumn(),"config"=>hash_file("sha256",__DIR__."/config/database.php")]);')
            huella = hashlib.sha256((copia / 'config/database.php').read_bytes()).hexdigest()
            with socket.socket() as reserva:
                reserva.bind(('127.0.0.1', 0))
                puerto = reserva.getsockname()[1]
            url = 'http://127.0.0.1:' + str(puerto)
            sesiones = Path(temporal) / 'sesiones'
            sesiones.mkdir()
            with (Path(temporal) / 'servidor.log').open('w+') as registro:
                servidor = subprocess.Popen([php, '-d', 'session.save_path=' + str(sesiones), '-S', '127.0.0.1:' + str(puerto), '-t', str(copia)], env=entorno, stdout=registro, stderr=registro)
                try:
                    for _ in range(100):
                        try:
                            with socket.create_connection(('127.0.0.1', puerto), timeout=.1):
                                break
                        except OSError:
                            time.sleep(.05)
                    cliente = build_opener(HTTPCookieProcessor(CookieJar()))
                    anonimo = build_opener(HTTPCookieProcessor(CookieJar()))

                    def solicitar(ruta, datos=None, navegador=cliente, origen=None):
                        if datos is not None:
                            with urlopen(url + '/' + rutaPrueba, timeout=10) as respuesta:
                                identidad = json.load(respuesta)
                            if identidad != {'base': nombreBase, 'config': huella}:
                                raise RuntimeError('Aislamiento no confirmado: no se envió POST.')
                        try:
                            peticion = Request(url + ruta, data=urlencode(datos).encode() if datos is not None else None, headers={'Origin': origen} if origen else {})
                            with navegador.open(peticion, timeout=10) as respuesta:
                                return respuesta.status, respuesta.read().decode()
                        except HTTPError as error:
                            return error.code, error.read().decode()

                    def token(ruta, navegador=cliente):
                        estado, pagina = solicitar(ruta, navegador=navegador)
                        if estado != 200:
                            raise RuntimeError('No se obtuvo el formulario: ' + ruta)
                        campo = re.search(r'name="token" value="([a-f0-9]{64})"', pagina)
                        if campo is None:
                            raise RuntimeError('No se encontró el token del formulario: ' + ruta)
                        return campo.group(1)

                    estado, pagina = solicitar('/index.php', navegador=anonimo)
                    comprobar('Iniciar sesión' in pagina, 'lectura privada requiere iniciar sesión')
                    estado, _ = solicitar('/modules/inventario/guardar.php', {'nombre': 'No permitido'}, anonimo)
                    comprobar(estado == 403, 'CP-13: alta sin autorización rechazada')
                    comprobar(sql('echo $c->query("SELECT COUNT(*) FROM materias_primas")->fetchColumn();') == '2', 'CP-13: ninguna escritura')
                    estado, _ = solicitar('/iniciar-sesion.php', {'correo': correo, 'clave': clave})
                    comprobar(estado == 403, 'inicio de sesión sin token rechazado')
                    estado, _ = solicitar('/iniciar-sesion.php', {'correo': correo, 'clave': 'incorrecta', 'token': token('/iniciar-sesion.php')})
                    comprobar(estado == 401, 'contraseña incorrecta rechazada')
                    estado, pagina = solicitar('/iniciar-sesion.php', {'correo': correo, 'clave': clave, 'token': token('/iniciar-sesion.php')})
                    comprobar(estado == 200 and 'Cerrar sesión' in pagina, 'inicio de sesión válido')
                    datosAlta = {'nombre': 'Alta válida', 'unidad_medida': 'g', 'cantidad_disponible': '0', 'stock_minimo': '0'}
                    for nombre, cambio in [('sin token', {}), ('token incorrecto', {'token': 'incorrecto'}), ('token como arreglo', {'token[]': 'incorrecto'})]:
                        estado, _ = solicitar('/modules/inventario/guardar.php', {**datosAlta, **cambio})
                        comprobar(estado == 403, 'CP-14: ' + nombre + ' rechazado')
                    estado, _ = solicitar('/modules/inventario/guardar.php', datosAlta, origen='https://origen-ajeno.example')
                    comprobar(estado == 403, 'CP-14: origen ajeno sin token rechazado')
                    otraSesion = build_opener(HTTPCookieProcessor(CookieJar()))
                    solicitar('/iniciar-sesion.php', {'correo': correo, 'clave': clave, 'token': token('/iniciar-sesion.php', otraSesion)}, otraSesion)
                    datosAlta['token'] = token('/modules/inventario/index.php')
                    estado, _ = solicitar('/modules/inventario/guardar.php', datosAlta, otraSesion)
                    comprobar(estado == 403, 'token de otra sesión autenticada rechazado')
                    estado, pagina = solicitar('/modules/inventario/guardar.php', datosAlta)
                    comprobar(estado == 200 and 'Alta válida' in pagina, 'alta autorizada con token válida')
                    datosAlta['cantidad_disponible'] = '-1'
                    estado, _ = solicitar('/modules/inventario/guardar.php', datosAlta)
                    comprobar(sql('echo $c->query("SELECT COUNT(*) FROM materias_primas")->fetchColumn();') == '3', 'alta negativa no agrega registros')
                    receta = {'nombre': 'Receta de prueba', 'descripcion': 'Una unidad',
                              'ingredientes[0][materia_prima_id]': '1', 'ingredientes[0][cantidad]': '500',
                              'ingredientes[1][materia_prima_id]': '2', 'ingredientes[1][cantidad]': '200',
                              'token': token('/modules/recetas/index.php')}
                    estado, _ = solicitar('/modules/recetas/guardar.php', receta, anonimo)
                    comprobar(estado == 403, 'receta sin autorización rechazada')
                    estado, pagina = solicitar('/modules/recetas/guardar.php', receta)
                    comprobar(estado == 200 and all(x in pagina for x in ['Harina', '500.00', 'Azúcar', '200.00']), 'CP-15: receta guarda y consulta ambos ingredientes y cantidades')
                    comprobar(saldoAntes == sql('echo json_encode($c->query("SELECT id,cantidad_disponible FROM materias_primas WHERE id IN (1,2) ORDER BY id")->fetchAll(PDO::FETCH_ASSOC));'), 'registrar receta no descuenta inventario')
                    for nombre, cambios in [('sin ingredientes', {'ingredientes[0][materia_prima_id]': '', 'ingredientes[0][cantidad]': '', 'ingredientes[1][materia_prima_id]': '', 'ingredientes[1][cantidad]': ''}), ('cantidad cero', {'ingredientes[0][cantidad]': '0'}), ('cantidad negativa', {'ingredientes[0][cantidad]': '-1'}), ('más de dos decimales', {'ingredientes[0][cantidad]': '1.001'}), ('ingrediente repetido', {'ingredientes[1][materia_prima_id]': '1'}), ('ingrediente inexistente', {'ingredientes[1][materia_prima_id]': '999999'})]:
                        solicitar('/modules/recetas/guardar.php', {**receta, **cambios})
                        comprobar(sql('echo $c->query("SELECT COUNT(*) FROM recetas")->fetchColumn();') == '1', 'receta rechazada sin guardar: ' + nombre)
                        comprobar(sql('echo $c->query("SELECT COUNT(*) FROM receta_ingredientes")->fetchColumn();') == '2', 'sin ingredientes parciales: ' + nombre)
                    estado, _ = solicitar('/modules/inventario/eliminar.php', {'id': '1'})
                    comprobar(estado == 403, 'eliminación sin token rechazada')
                    estado, _ = solicitar('/modules/inventario/eliminar.php', {'id': '1', 'token': token('/modules/inventario/editar.php?id=1')})
                    comprobar(sql('echo $c->query("SELECT COUNT(*) FROM materias_primas WHERE id=1")->fetchColumn();') == '1', 'materia usada en receta no se elimina')
                    estado, _ = solicitar('/modules/inventario/editar.php?id=1', {'id': '1'})
                    comprobar(estado == 403, 'edición sin token rechazada')
                    entorno.update({'HU02_URL': url, 'HU02_PRUEBA_CORREO': correo, 'HU02_PRUEBA_CLAVE_USUARIO': clave})
                    resultado = subprocess.run([os.sys.executable, str(raiz / 'pruebas/hu02-http.py')], env=entorno, capture_output=True, text=True, timeout=60)
                    resumen['hu02_http'] = {'codigo_salida': resultado.returncode, 'stdout': resultado.stdout, 'stderr': resultado.stderr}
                    comprobar(resultado.returncode == 0 and '15 comprobaciones correctas' in resultado.stdout, 'HU02: quince comprobaciones HTTP')
                    if os.environ.get('RECUPERACION_NAVEGADOR') == '1':
                        resultado = subprocess.run(['node', str(raiz / 'pruebas/hu02-web.js')], env=entorno, capture_output=True, text=True, timeout=90)
                        resumen['navegador'] = {'codigo_salida': resultado.returncode, 'stdout': resultado.stdout, 'stderr': resultado.stderr}
                        comprobar(resultado.returncode == 0, 'flujo web HU02 y consola JavaScript')
                        resultado = subprocess.run(['node', str(raiz / 'pruebas/recetas-web.js')], env=entorno, capture_output=True, text=True, timeout=90)
                        resumen['recetas_navegador'] = {'codigo_salida': resultado.returncode, 'stdout': resultado.stdout, 'stderr': resultado.stderr}
                        comprobar(resultado.returncode == 0, 'CP-15 desde el navegador y alta dinámica de ingredientes')
                    if os.environ.get('RECUPERACION_PRODUCCION') == '1':
                        resultado = subprocess.run([php, str(raiz / 'pruebas/produccion-integracion.php')], env=entorno, capture_output=True, text=True, timeout=60)
                        resumen['produccion_integracion'] = {'codigo_salida': resultado.returncode, 'stdout': resultado.stdout, 'stderr': resultado.stderr}
                        comprobar(resultado.returncode == 0, 'HU04-HU06: cálculos, viabilidad y proyección')
                        datosWeb = re.search(r'^DATOS_WEB: (.+)$', resultado.stdout, re.M)
                        if datosWeb is None:
                            raise RuntimeError('No se obtuvieron las recetas de prueba de producción.')
                        entorno['PRODUCCION_DATOS_WEB'] = datosWeb.group(1)
                        if os.environ.get('RECUPERACION_NAVEGADOR') == '1':
                            resultado = subprocess.run(['node', str(raiz / 'pruebas/produccion-web.js')], env=entorno, capture_output=True, text=True, timeout=90)
                            resumen['produccion_navegador'] = {'codigo_salida': resultado.returncode, 'stdout': resultado.stdout, 'stderr': resultado.stderr}
                            comprobar(resultado.returncode == 0, 'HU04-HU06: flujo web de escritorio y móvil')
                    sql('$c->exec("UPDATE usuarios SET activo=0");')
                    estado, _ = solicitar('/index.php')
                    comprobar(estado == 403, 'cuenta deshabilitada pierde acceso')
                    sql('$c->exec("UPDATE usuarios SET activo=1");')
                    solicitar('/iniciar-sesion.php', {'correo': correo, 'clave': clave, 'token': token('/iniciar-sesion.php')})
                    estado, pagina = solicitar('/cerrar-sesion.php', {'token': token('/index.php')})
                    comprobar(estado == 200 and 'Iniciar sesión' in pagina, 'cierre de sesión válido')
                    estado, _ = solicitar('/modules/inventario/guardar.php', datosAlta)
                    comprobar(estado == 403, 'sesión cerrada no permite escribir')
                finally:
                    servidor.terminate()
                    servidor.wait(timeout=10)
                    registro.seek(0)
                    resumen['registro_servidor'] = registro.read()
        entorno['HU02_PRUEBA_DSN'] = dsn
        entorno['HU02_PRUEBA_USUARIO'] = entorno['INVENTARIO_USUARIO']
        entorno['HU02_PRUEBA_CLAVE'] = entorno['INVENTARIO_CLAVE']
        entorno['HU02_SCHEMA_ANTERIOR'] = str(raiz / 'pruebas/datos/schema-anterior-hu01.sql')
        resultado = subprocess.run([php, str(raiz / 'pruebas/hu02-integracion.php')], env=entorno, capture_output=True, text=True, timeout=90)
        resumen['hu02_integracion'] = {'codigo_salida': resultado.returncode, 'stdout': resultado.stdout, 'stderr': resultado.stderr}
        comprobar(resultado.returncode == 0, 'HU02: integración, concurrencia y migración')
        resumen['aprobado'] = True
    except Exception as error:
        resumen['error'] = str(error)
    finally:
        if servidor is not None and servidor.poll() is None:
            servidor.terminate()
            servidor.wait(timeout=10)
        if creada:
            try:
                sql('$c->exec("DROP DATABASE `".$argv[1]."`");', nombreBase)
                resumen['base_temporal_eliminada'] = True
            except Exception as error:
                resumen['base_temporal_eliminada'] = False
                resumen['error_limpieza'] = str(error)
                resumen['aprobado'] = False
        resumen['fin'] = datetime.now(ZoneInfo('America/Bogota')).isoformat()
        opciones.salida.parent.mkdir(parents=True, exist_ok=True)
        with opciones.salida.open('x') as archivo:
            json.dump(resumen, archivo, ensure_ascii=False, indent=2)
            archivo.write('\n')
    print(json.dumps({'aprobado': resumen['aprobado'], 'casos': len(resumen['casos']), 'error': resumen.get('error'), 'salida': str(opciones.salida)}, ensure_ascii=False))
    return 0 if resumen['aprobado'] else 1


if __name__ == '__main__':
    raise SystemExit(ejecutar())
