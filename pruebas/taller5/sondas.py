"""Sondas locales de calidad. Usar despliegue sintético desechable."""
import json,os,secrets,time,math
from pathlib import Path
from http.cookiejar import CookieJar
from urllib.request import Request,build_opener,HTTPCookieProcessor
from urllib.parse import urlencode,urlparse
base=os.environ['HU02_URL'].rstrip('/')
if urlparse(base).hostname not in ('localhost','127.0.0.1'):raise ValueError('Solo despliegue local sintético')
out=Path('pruebas/taller5/resultados');out.mkdir(parents=True,exist_ok=True)
security=[]
for ident in ['CP-13','CP-14']:
 cookies=CookieJar();opener=build_opener(HTTPCookieProcessor(cookies))
 if ident=='CP-14':
  with opener.open(base+'/modules/inventario/') as r:r.read()
 label='Sonda_'+ident+'_'+secrets.token_hex(4)
 data=urlencode(dict(nombre=label,unidad_medida='g',cantidad_disponible='1',stock_minimo='0')).encode()
 headers={'Origin':'https://origen-ajeno.example'} if ident=='CP-14' else {}
 with opener.open(Request(base+'/modules/inventario/guardar.php',data=data,headers=headers)) as r:
  status=r.status;created=label in r.read().decode()
 security.append(dict(id=ident,http_final=status,created=created,status='Fallido' if created else 'Aprobado'))
(out/'seguridad.json').write_text(json.dumps(security,indent=2));print(security)
print('Para rendimiento: repetir las lecturas en un despliegue con 1000 materias y cero historial; estas sondas agregan dos materias.')
if os.environ.get('T5_MEDIR_RENDIMIENTO')=='1':
 perf={}
 for route in ['index.php','movimientos.php']:
  values=[]
  for i in range(105):
   start=time.perf_counter()
   with build_opener().open(base+'/modules/inventario/'+route) as r:r.read()
   if i>=5:values.append(time.perf_counter()-start)
  perf[route]=dict(samples=100,warmups=5,p95_s=sorted(values)[math.ceil(.95*len(values))-1],times_s=values)
 (out/'rendimiento.json').write_text(json.dumps(perf,indent=2));print({k:v['p95_s'] for k,v in perf.items()})
