<?php
declare(strict_types=1);
// Fusión preparada para v2.2 instalada sin ventas y la base comercial recibida.
// Los dos orígenes se leen únicamente; el destino debe ser una instalación nueva.
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Solo consola.');}
require dirname(__DIR__).'/src/bootstrap.php';
use Sedema\Config;
function qi(string $s):string{if(!preg_match('/^[a-zA-Z0-9_]{1,64}$/D',$s))throw new RuntimeException('Identificador inválido.');return '`'.$s.'`';}
function packed(mixed $v):mixed{if(is_array($v)){foreach($v as &$x)$x=packed($x);unset($x);return $v;}return is_string($v)&&preg_match('//u',$v)!==1?['__binary_base64'=>base64_encode($v)]:$v;}
function js(mixed $v):string{return json_encode(packed($v),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);}
function norm(mixed $v):string{return mb_strtolower(trim((string)$v));}
function keys(array $r,array $pk):string{return json_encode(array_map(fn($c)=>(string)($r[$c]??''),$pk),JSON_THROW_ON_ERROR);}
try{
 $opts=getopt('',['installed:','commercial:','target:','apply','report:']);
 $src=['instalada'=>(string)($opts['installed']??''),'grupo'=>(string)($opts['commercial']??'')];$target=(string)($opts['target']??'sedema_integrada');
 foreach([...array_values($src),$target] as $s)qi($s);
 if(count(array_unique([...array_values($src),$target]))!==3)throw new RuntimeException('Usar tres bases diferentes.');
 $db=new PDO('mysql:host='.Config::get('DB_HOST','127.0.0.1').';port='.Config::get('DB_PORT','3306').';charset=utf8mb4',Config::get('DB_USERNAME','root'),Config::get('DB_PASSWORD',''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
 $db->exec("SET SESSION sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
 $q=$db->prepare("SELECT TABLE_NAME,COLUMN_NAME,IS_NULLABLE,COLUMN_DEFAULT,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? ORDER BY ORDINAL_POSITION");$q->execute([$target]);$cols=[];
 foreach($q as $c)$cols[$c['TABLE_NAME']][$c['COLUMN_NAME']]=$c;
 if(!isset($cols['integration_source_record'],$cols['integration_batch']))throw new RuntimeException('Ejecutar el instalador integrado 3.0 en un destino vacío.');
 $tables=array_values(array_diff(array_keys($cols),['integration_source_record','integration_batch']));
 foreach(array_keys($cols) as $t)if(!in_array($t,['inventory_unit','inventory_category','inventory_warehouse','schema_migration'],true)&&(int)$db->query('SELECT COUNT(*) FROM '.qi($target).'.'.qi($t))->fetchColumn())throw new RuntimeException('Destino con datos: '.$t.'. No se sobrescribirá.');
 $q=$db->prepare("SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND INDEX_NAME='PRIMARY' ORDER BY SEQ_IN_INDEX");$q->execute([$target]);$pks=[];foreach($q as $r)$pks[$r['TABLE_NAME']][]=$r['COLUMN_NAME'];
 $sources=[];$sourceHashes=[];$counters=[];
 foreach($src as $name=>$schema){
  $q=$db->prepare("SELECT TABLE_NAME,AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_TYPE='BASE TABLE'");$q->execute([$schema]);$available=$q->fetchAll();
  if(!$available)throw new RuntimeException('Origen inexistente o vacío: '.$name);
  foreach($tables as $t)$sources[$name][$t]=[];
  foreach($available as $entry){$t=$entry['TABLE_NAME'];$rows=$db->query('SELECT * FROM '.qi($schema).'.'.qi($t))->fetchAll();if(!in_array($t,$tables,true)){if($rows)throw new RuntimeException('Tabla adicional con datos: '.$name.'.'.$t);continue;}$sources[$name][$t]=$rows;if($entry['AUTO_INCREMENT']!==null)$counters[$t]=max((int)($counters[$t]??0),(int)$entry['AUTO_INCREMENT']);}
  $sourceHashes[$name]=hash('sha256',js($sources[$name]));
 }
 foreach(['pedido','pago','cliente','despacho','factura','liquidacionsueldo','current_account_movement'] as $t)if($sources['instalada'][$t])throw new RuntimeException('La base instalada contiene operaciones no contempladas en esta fusión: '.$t.'. Requiere nueva comparación.');
 // Discover declared references plus the logical references used by the application.
 $refs=[];$dependencies=[];$q=$db->prepare('SELECT TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=? AND REFERENCED_TABLE_NAME IS NOT NULL');$q->execute([$target]);
 foreach($q as $fk){$refs[$fk['TABLE_NAME']][$fk['COLUMN_NAME']]=[$fk['REFERENCED_TABLE_NAME'],$fk['REFERENCED_COLUMN_NAME']];$dependencies[$fk['TABLE_NAME']][$fk['REFERENCED_TABLE_NAME']]=true;}
 foreach(['inventory_movement'=>['actorUserId'=>'usuario'],'module_operation'=>['actorUserId'=>'usuario'],'usuario'=>['deletedBy'=>'usuario'],'sales_order_audit'=>['idPedido'=>'pedido']] as $t=>$rs)foreach($rs as $c=>$parent)$refs[$t][$c]=[$parent,$pks[$parent][0]];
 $maps=[];$used=[];$next=[];$decisions=[];$dataset=[];$archives=[];$changes=[];
 foreach($tables as $t){$dataset[$t]=[];foreach($sources['instalada'][$t] as $r){$key=keys($r,$pks[$t]);$maps['instalada'][$t][$key]=$r;$used[$t][$key]=true;$dataset[$t][$key]=$r;$decisions['instalada'][$t][$key]='PRESERVED_INSTALLED';}if(count($pks[$t])===1){$pk=$pks[$t][0];$max=0;foreach($sources as $ss)foreach($ss[$t] as $r)if(ctype_digit((string)$r[$pk]))$max=max($max,(int)$r[$pk]);$next[$t]=max($max+1,$counters[$t]??1);}}
 $allocate=function(string $t,array $row)use(&$used,&$next,$pks):array{$pk=$pks[$t];$key=keys($row,$pk);if(isset($used[$t][$key])){if(count($pk)!==1||!ctype_digit((string)$row[$pk[0]]))throw new RuntimeException('Clave en conflicto: '.$t);$row[$pk[0]]=$next[$t]++;$key=keys($row,$pk);} $used[$t][$key]=true;return $row;};
 // Map canonical catalog keys first. The installed catalog wins by explicit user choice.
 $natural=['inventory_unit'=>'code','inventory_category'=>'name','inventory_warehouse'=>'name','inventory_product'=>'code','proveedor'=>'cuit','vehiculo'=>'matricula','cliente'=>'cuitDNI'];
 foreach($natural as $t=>$field){$index=[];foreach($dataset[$t] as $r)$index[norm($r[$field])]=$r;foreach($sources['grupo'][$t] as $r){$old=keys($r,$pks[$t]);$key=norm($r[$field]);if(isset($index[$key])){$maps['grupo'][$t][$old]=$index[$key];$decisions['grupo'][$t][$old]='REUSED_INSTALLED_BY_'.$field;}else{$n=$allocate($t,$r);$maps['grupo'][$t][$old]=$n;$index[$key]=$n;$decisions['grupo'][$t][$old]='IMPORTED';}}}
 // The matching administrator account identifies the old provisional employee.
 $installedUsers=$sources['instalada']['usuario'];$groupUsers=$sources['grupo']['usuario'];$pinned=[];$sameUsers=[];
 foreach($groupUsers as $g)foreach($installedUsers as $i){$sameEmail=norm($g['email'])===norm($i['email']);$sameUsername=norm($g['username'])===norm($i['username']);if($sameEmail||$sameUsername){if(!$sameEmail||!$sameUsername)throw new RuntimeException('Cuenta con correo y nombre de usuario contradictorios.');$sameUsers[(string)$g['idUsuario']]=$i;$pinned[(string)$g['idEmpleado']]=$i['idEmpleado'];}}
 $employeeIndex=[];foreach($sources['instalada']['empleado'] as $r)$employeeIndex[preg_replace('/\D/','',(string)$r['dni'])]=$r;
 foreach($sources['grupo']['empleado'] as $r){$old=keys($r,$pks['empleado']);$dni=preg_replace('/\D/','',(string)$r['dni']);$matched=null;if(isset($pinned[(string)$r['idEmpleado']])) { foreach($sources['instalada']['empleado'] as $i) { if((string)$i['idEmpleado']===(string)$pinned[(string)$r['idEmpleado']]) $matched=$i; } }
  // The explicit DNI match also covers a second login belonging to the administrator.
  if(!$matched&&$dni!==''&&isset($employeeIndex[$dni]))$matched=$employeeIndex[$dni];
  $maps['grupo']['empleado'][$old]=$matched??$allocate('empleado',$r);$decisions['grupo']['empleado'][$old]=$matched?'MAPPED_TO_INSTALLED_EMPLOYEE':'IMPORTED';
 }
 foreach($groupUsers as $r){$old=keys($r,$pks['usuario']);if(isset($sameUsers[(string)$r['idUsuario']])){$maps['grupo']['usuario'][$old]=$sameUsers[(string)$r['idUsuario']];$decisions['grupo']['usuario'][$old]='PRESERVED_INSTALLED_ADMIN_CREDENTIALS';}else{$maps['grupo']['usuario'][$old]=$allocate('usuario',$r);$decisions['grupo']['usuario'][$old]='IMPORTED';}}
 // Reserve IDs for every remaining table before translating any references.
 foreach($tables as $t){if(in_array($t,[...array_keys($natural),'empleado','usuario','inventory_stock','schema_migration'],true))continue;foreach($sources['grupo'][$t] as $r){$old=keys($r,$pks[$t]);$maps['grupo'][$t][$old]=$allocate($t,$r);$decisions['grupo'][$t][$old]='IMPORTED';}}
 $translate=function(string $parent,mixed $value)use(&$maps,$pks):mixed{if($value===null)return null;$old=json_encode([(string)$value],JSON_THROW_ON_ERROR);if(!isset($maps['grupo'][$parent][$old]))throw new RuntimeException('Referencia sin equivalencia a '.$parent.' #'.$value);return $maps['grupo'][$parent][$old][$pks[$parent][0]];};
 $installedPositions=[];foreach($sources['instalada']['inventory_stock'] as $r)$installedPositions[keys($r,$pks['inventory_stock'])]=true;
 $groupOrders=[];foreach($sources['grupo']['pedido'] as $r)$groupOrders[(int)$r['idPedido']]=$r;
 $assignedEmployees=[];foreach($installedUsers as $u)if($u['idEmpleado']!==null)$assignedEmployees[(int)$u['idEmpleado']]=true;
 foreach($tables as $t)foreach($sources['grupo'][$t] as $original){
  $old=keys($original,$pks[$t]);$disposition=$decisions['grupo'][$t][$old]??'IMPORTED';
  if(isset($natural[$t])&&str_starts_with($disposition,'REUSED_'))continue;
  if($t==='empleado'&&$disposition==='MAPPED_TO_INSTALLED_EMPLOYEE')continue;
  if($t==='usuario'&&$disposition==='PRESERVED_INSTALLED_ADMIN_CREDENTIALS')continue;
  $row=$maps['grupo'][$t][$old]??$original;
  foreach($refs[$t]??[] as $column=>[$parent,$parentCol]){
   if(!array_key_exists($column,$row)||$row[$column]===null)continue;
   // Historical audit rows keep references to already deleted commercial orders.
   if($t==='sales_order_audit'&&$parent==='pedido'&&!isset($maps['grupo']['pedido'][json_encode([(string)$row[$column]],JSON_THROW_ON_ERROR)]))continue;
   $row[$column]=$translate($parent,$row[$column]);
  }
  if($t==='despacho'&&empty($row['idWarehouse'])){$sourceOrder=$groupOrders[(int)$original['idPedido']]??null;if(!$sourceOrder||empty($sourceOrder['idWarehouse']))throw new RuntimeException('Despacho sin depósito determinable.');$row['idWarehouse']=$translate('inventory_warehouse',$sourceOrder['idWarehouse']);$disposition='IMPORTED_WAREHOUSE_FROM_ORDER';}
  if($t==='usuario'&&$row['idEmpleado']!==null&&isset($assignedEmployees[(int)$row['idEmpleado']])){$row['idEmpleado']=null;$row['habilitado']=0;$row['authVersion']=(int)$row['authVersion']+1;$disposition='DUPLICATE_LOGIN_DISABLED_KEEP_HISTORY';$changes[]='Cuenta de origen #'.$original['idUsuario'].' deshabilitada; se conserva el administrador actual.';}
  elseif($t==='usuario'&&$row['idEmpleado']!==null)$assignedEmployees[(int)$row['idEmpleado']]=true;
  if($t==='inventory_stock'){
   $key=keys($row,$pks[$t]);$maps['grupo'][$t][$old]=$row;
   if(isset($installedPositions[$key])){$decisions['grupo'][$t][$old]='KEPT_INSTALLED_STOCK_NO_SUM';continue;}
  }
  if($t==='inventory_movement'){$position=json_encode([(string)$row['idProduct'],(string)$row['idWarehouse']],JSON_THROW_ON_ERROR);if(isset($installedPositions[$position])){$decisions['grupo'][$t][$old]='ARCHIVED_PREVIOUS_STOCK_HISTORY';$maps['grupo'][$t][$old]=null;continue;}}
  if($t==='schema_migration'){
   $maps['grupo'][$t][$old]=$row;if(isset($dataset[$t][$old])){$decisions['grupo'][$t][$old]='PRESERVED_INSTALLED_MIGRATION';continue;}
  }
  $row=array_intersect_key($row,$cols[$t]);$dataset[$t][keys($row,$pks[$t])]=$row;$maps['grupo'][$t][$old]=$row;$decisions['grupo'][$t][$old]=$disposition;
 }
 // Current rows and their operation keys/results retain every original identifier.
 // Keep a lossless copy of all original source rows, including superseded versions.
 foreach($sources as $name=>$ss)foreach($ss as $t=>$rows)foreach($rows as $row){$old=keys($row,$pks[$t]);$mapped=$maps[$name][$t][$old]??null;$archives[]=['sourceName'=>$name,'tableName'=>$t,'sourceKey'=>$old,'mappedKey'=>$mapped?keys($mapped,$pks[$t]):null,'disposition'=>$decisions[$name][$t][$old]??'IMPORTED','originalJson'=>js($row)];}
 $ordered=[];$remaining=array_keys($cols);while($remaining){$advanced=false;foreach($remaining as $i=>$t)if(!array_diff(array_keys($dependencies[$t]??[]),$ordered)){$ordered[]=$t;unset($remaining[$i]);$advanced=true;}if(!$advanced)throw new RuntimeException('Dependencias circulares no soportadas.');}
 $report=['policy'=>['catalog'=>'installed','stock'=>'installed_without_sum','administrator'=>'installed','duplicate_login'=>'disabled_keep_history'],'sourceHashes'=>$sourceHashes,'counts'=>[],'changes'=>$changes,'mapping'=>[]];
 foreach($tables as $t){$report['counts'][$t]=['installed'=>count($sources['instalada'][$t]),'commercial'=>count($sources['grupo'][$t]),'merged'=>count($dataset[$t])];}
 foreach($decisions as $name=>$dd)foreach($dd as $t=>$rows)foreach($rows as $key=>$why){$mapped=$maps[$name][$t][$key]??null;$report['mapping'][]=['source'=>$name,'table'=>$t,'key'=>$key,'mapped'=>$mapped?keys($mapped,$pks[$t]):null,'decision'=>$why];}
 echo 'Plan: '.count($archives).' registros originales conservados; '.count($tables).' tablas operativas y dos de procedencia.'.PHP_EOL;
 foreach($report['counts'] as $t=>$counts)if(array_sum($counts)>0)echo $t.': '.js($counts).PHP_EOL;
 if(isset($opts['report'])){if(file_put_contents((string)$opts['report'],json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))===false)throw new RuntimeException('No se pudo escribir el informe.');}
 if(!isset($opts['apply'])){echo "Prevalidación terminada, sin escrituras en las bases. Las restricciones SQL se comprueban al aplicar.\n";exit(0);}
 // Set counters before the data transaction: DDL must not commit a partial copy.
 foreach($counters as $t=>$n)if(isset($cols[$t])&&isset($next[$t]))$db->exec('ALTER TABLE '.qi($target).'.'.qi($t).' AUTO_INCREMENT='.max($n,$next[$t]));
 $db->beginTransaction();try{
  foreach(array_reverse($ordered) as $t)$db->exec('DELETE FROM '.qi($target).'.'.qi($t));
  foreach($ordered as $t){$rows=$dataset[$t]??[];if($t==='integration_source_record')$rows=$archives;if($t==='integration_batch')$rows=[['mergeId'=>'sedema_3_0_installed_commercial','policyJson'=>js($report['policy']),'sourceHashesJson'=>js($sourceHashes)]];
   foreach($rows as $row){$columns=array_keys($row);$db->prepare('INSERT INTO '.qi($target).'.'.qi($t).' ('.implode(',',array_map('qi',$columns)).') VALUES('.implode(',',array_fill(0,count($row),'?')).')')->execute(array_values($row));}
  }
  $db->prepare('INSERT INTO '.qi($target).".schema_migration(version,description) VALUES('020_integration_20261023','Esquema integrado de Inventario, Logistica, Proveedores, Ventas y Pagos QR'),('021_merge_installed_commercial','Fusion de base instalada v2.2 y comercial; prioridad instalada confirmada')")->execute();
  // Verify the chosen stock, credentials and original source row count before committing.
  foreach($sources['instalada']['inventory_stock'] as $r){$s=$db->prepare('SELECT quantity,minimumStock FROM '.qi($target).'.inventory_stock WHERE idProduct=? AND idWarehouse=?');$s->execute([$r['idProduct'],$r['idWarehouse']]);$n=$s->fetch();if(!$n||(float)$n['quantity']!==(float)$r['quantity']||(float)$n['minimumStock']!==(float)$r['minimumStock'])throw new RuntimeException('No se conservó el stock instalado.');}
  foreach($installedUsers as $u){$s=$db->prepare('SELECT passwordHash,habilitado FROM '.qi($target).'.usuario WHERE idUsuario=?');$s->execute([$u['idUsuario']]);$n=$s->fetch();if(!$n||$n['passwordHash']!==$u['passwordHash']||(int)$n['habilitado']!==(int)$u['habilitado'])throw new RuntimeException('No se conservaron las credenciales instaladas.');}
  if((int)$db->query('SELECT COUNT(*) FROM '.qi($target).'.integration_source_record')->fetchColumn()!==count($archives))throw new RuntimeException('Copia de procedencia incompleta.');
  $db->commit();echo "Fusión confirmada. Ambos orígenes intactos.\n";
 }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}catch(Throwable $e){fwrite(STDERR,'Fusión detenida: '.$e->getMessage().PHP_EOL);exit(1);}
