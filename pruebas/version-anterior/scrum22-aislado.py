"""Prepara una copia temporal, una base nueva y un servidor propio para SCRUM-22."""

import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import secrets
import shutil
import socket
import subprocess
import sys
import tempfile
import time
from datetime import datetime
from zoneinfo import ZoneInfo


CONFIGURACION = '''<?php

if (getenv('SCRUM22_MODO_AISLADO') !== '1') {
    http_response_code(503);
    exit;
}

$conexion = new PDO(
    getenv('SCRUM22_PRUEBA_DSN'),
    getenv('SCRUM22_USUARIO') ?: 'root',
    getenv('SCRUM22_CLAVE') ?: ''
);
$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
'''

COMPROBACION = '''<?php

require __DIR__ . '/config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode([
    'base' => $conexion->query('SELECT DATABASE()')->fetchColumn(),
    'testigo' => $conexion->query('SELECT testigo FROM prueba_aislamiento')->fetchColumn(),
    'config_sha256' => hash_file('sha256', __DIR__ . '/config/database.php'),
]);
'''


def ejecutar():
    opciones = argparse.ArgumentParser(description=__doc__)
    opciones.add_argument('--salida', type=Path, required=True,
                          help='Archivo JSON nuevo donde conservar la salida real.')
    argumentos = opciones.parse_args()
    if argumentos.salida.exists():
        raise ValueError('La salida ya existe; indicar un archivo nuevo para conservar evidencias anteriores.')
    raiz = Path(__file__).resolve().parents[1]
    php = os.environ.get('SCRUM22_PHP', 'php')
    dsnServidor = os.environ.get('SCRUM22_SERVIDOR_DSN', '')
    if not dsnServidor.startswith('mysql:') or re.search(r'(?:^|;)dbname=', dsnServidor):
        raise ValueError('SCRUM22_SERVIDOR_DSN debe indicar el servidor MySQL sin dbname.')
    hosts = re.findall(r'(?:mysql:|;)host=([^;]+)', dsnServidor)
    if len(hosts) != 1 or hosts[0] not in ('localhost', '127.0.0.1'):
        raise ValueError('Usar un servidor MySQL/MariaDB local, mediante host localhost o 127.0.0.1.')
    esquema = (raiz / 'database/schema.sql').read_text()
    esquema, creadas = re.subn(r'CREATE DATABASE IF NOT EXISTS sistema_inventario\s*;', '', esquema)
    esquema, usadas = re.subn(r'USE sistema_inventario\s*;', '', esquema)
    if creadas != 1 or usadas != 1 or re.search(r'\b(?:CREATE|DROP|ALTER)\s+DATABASE\b|\bUSE\b|sistema_inventario', esquema, re.I):
        raise ValueError('El esquema requiere revisión: no se permite seleccionar o modificar otra base.')
    nombreBase = 'prueba_scrum22_' + secrets.token_hex(12)
    testigo = secrets.token_hex(32)
    nombreComprobacion = 'comprobar-aislamiento-' + secrets.token_hex(12) + '.php'
    entorno = os.environ.copy()
    entorno.update({'SCRUM22_PRUEBA_DSN': dsnServidor.rstrip(';') + ';dbname=' + nombreBase,
                    'SCRUM22_MODO_AISLADO': '1',
                    'SCRUM22_AISLAMIENTO_RUTA': '/' + nombreComprobacion,
                    'SCRUM22_AISLAMIENTO_TESTIGO': testigo})
    inicio = datetime.now(ZoneInfo('America/Bogota')).isoformat()
    configuracionOriginal = raiz / 'config/database.php'
    huellaOriginal = hashlib.sha256(configuracionOriginal.read_bytes()).hexdigest()

    def sql(codigo, *datos):
        conexion = ('$c=new PDO(getenv("SCRUM22_SERVIDOR_DSN"),'
                    'getenv("SCRUM22_USUARIO")?:"root",getenv("SCRUM22_CLAVE")?:"");'
                    '$c->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);')
        return subprocess.check_output([php, '-r', conexion + codigo, *datos], env=entorno,
                                       text=True, stderr=subprocess.PIPE, timeout=30)

    creada = False
    servidor = None
    resumen = None
    try:
        sql('$c->exec("CREATE DATABASE `".$argv[1]."`");', nombreBase)
        creada = True
        sql('$c->exec("USE `".$argv[1]."`");$c->exec($argv[2]);'
            '$c->exec("CREATE TABLE prueba_aislamiento (testigo CHAR(64) NOT NULL)");'
            '$q=$c->prepare("INSERT INTO prueba_aislamiento VALUES (?)");$q->execute([$argv[3]]);',
            nombreBase, esquema, testigo)
        with tempfile.TemporaryDirectory(prefix='scrum22-') as temporal:
            temporal = Path(temporal)
            copia = temporal / 'aplicacion'
            shutil.copytree(raiz, copia, ignore=shutil.ignore_patterns(
                '.git', 'node_modules', 'vendor', '__pycache__'))
            (copia / 'config/database.php').write_text(CONFIGURACION)
            (copia / nombreComprobacion).write_text(COMPROBACION)
            entorno['SCRUM22_AISLAMIENTO_CONFIG'] = hashlib.sha256(
                (copia / 'config/database.php').read_bytes()).hexdigest()
            sesiones = temporal / 'sesiones'
            sesiones.mkdir()
            with socket.socket() as reserva:
                reserva.bind(('127.0.0.1', 0))
                puerto = reserva.getsockname()[1]
            entorno['SCRUM22_URL'] = 'http://127.0.0.1:' + str(puerto)
            with (temporal / 'servidor.log').open('w+') as registro:
                servidor = subprocess.Popen([
                    php, '-d', 'session.save_path=' + str(sesiones), '-S',
                    '127.0.0.1:' + str(puerto), '-t', str(copia)],
                    env=entorno, stdout=registro, stderr=registro)
                try:
                    for _ in range(100):
                        if servidor.poll() is not None:
                            raise RuntimeError('El servidor temporal terminó antes de iniciar.')
                        try:
                            with socket.create_connection(('127.0.0.1', puerto), timeout=.1):
                                break
                        except OSError:
                            time.sleep(.05)
                    else:
                        raise RuntimeError('El servidor temporal no inició.')
                    resultado = subprocess.run([sys.executable, str(raiz / 'pruebas/scrum22-http.py')],
                                               env=entorno, text=True, capture_output=True, timeout=60)
                finally:
                    if servidor.poll() is None:
                        servidor.terminate()
                        try:
                            servidor.wait(timeout=10)
                        except subprocess.TimeoutExpired:
                            servidor.kill()
                            servidor.wait(timeout=10)
                registro.seek(0)
                if resultado.returncode:
                    raise RuntimeError('La comprobación falló; no se declara aprobada. ' + resultado.stderr)
                resumen = json.loads(resultado.stdout)
                resumen.update({'inicio': inicio, 'entorno': {
                    'php': sql('echo PHP_VERSION;'),
                    'base_de_datos': sql('echo $c->query("SELECT VERSION()")->fetchColumn();'),
                    'python': sys.version.split()[0]},
                    'configuracion_original_sin_cambios': huellaOriginal == hashlib.sha256(
                        configuracionOriginal.read_bytes()).hexdigest(),
                    'registro_servidor': registro.read()})
                if not resumen['configuracion_original_sin_cambios']:
                    raise RuntimeError('La configuración original cambió durante la ejecución.')
    finally:
        if servidor is not None and servidor.poll() is None:
            servidor.terminate()
            servidor.wait(timeout=10)
        if creada:
            sql('$c->exec("DROP DATABASE `".$argv[1]."`");', nombreBase)
    resumen['base_temporal_eliminada'] = True
    resumen['fin'] = datetime.now(ZoneInfo('America/Bogota')).isoformat()
    argumentos.salida.parent.mkdir(parents=True, exist_ok=True)
    with argumentos.salida.open('x') as archivo:
        json.dump(resumen, archivo, ensure_ascii=False, indent=2)
        archivo.write('\n')
    print(json.dumps(resumen, ensure_ascii=False, indent=2))


if __name__ == '__main__':
    try:
        ejecutar()
    except (KeyError, ValueError, RuntimeError, OSError, subprocess.SubprocessError) as error:
        print('PRUEBA DETENIDA: ' + str(error), file=sys.stderr)
        sys.exit(1)
