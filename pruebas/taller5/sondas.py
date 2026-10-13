"""Sondas locales de calidad. Usar despliegue sintético desechable."""
import json,os,secrets,time,math,sys
from pathlib import Path
from http.cookiejar import CookieJar
from urllib.request import Request,build_opener,HTTPCookieProcessor
from urllib.error import HTTPError
from urllib.parse import urlencode,urlparse
base=os.environ['HU02_URL'].rstrip('/')
if urlparse(base).hostname not in ('localhost','127.0.0.1'):raise ValueError('Solo despliegue local sintético')
out=Path('pruebas/taller5/resultados');out.mkdir(parents=True,exist_ok=True)
if '--rendimiento' in sys.argv:
 # Precondición: despliegue aparte con 1000 materias y cero historial.
 perf={}
 for route in ['index.php','movimientos.php']:
  values=[]
  for i in range(105):
   start=time.perf_counter()
   with build_opener().open(base+'/modules/inventario/'+route,timeout=10) as r:
    r.read();assert r.status==200
   if i>=5:values.append(time.perf_counter()-start)
  perf[route]=dict(samples=100,warmups=5,p95_s=sorted(values)[math.ceil(.95*len(values))-1],times_s=values)
 (out/'rendimiento.json').write_text(json.dumps(perf,indent=2));print({k:v['p95_s'] for k,v in perf.items()})
else:
 security=[]
 for ident in ['CP-13','CP-14']:
  cookies=CookieJar();opener=build_opener(HTTPCookieProcessor(cookies))
  if ident=='CP-14':
   with opener.open(base+'/modules/inventario/') as r:r.read()
  label='Sonda_'+ident+'_'+secrets.token_hex(4)
  data=urlencode(dict(nombre=label,unidad_medida='g',cantidad_disponible='1',stock_minimo='0')).encode()
  headers={'Origin':'https://origen-ajeno.example'} if ident=='CP-14' else {}
  try:
   with opener.open(Request(base+'/modules/inventario/guardar.php',data=data,headers=headers),timeout=10) as r:
    status=r.status;created=label in r.read().decode()
  except HTTPError as err:
   status=err.code;created=False
  # Confirmar que no existe escritura incluso si el servidor respondió error.
  with opener.open(base+'/modules/inventario/',timeout=10) as r:created=created or label in r.read().decode()
  security.append(dict(id=ident,http_final=status,created=created,status='Aprobado' if status==403 and not created else 'Fallido'))
 (out/'seguridad.json').write_text(json.dumps(security,indent=2));print(security)
