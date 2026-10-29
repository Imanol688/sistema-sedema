<?php
declare(strict_types=1);

// Copies ONE database into a fresh integrated schema. Never writes to the source.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo consola.'); }
require dirname(__DIR__) . '/src/bootstrap.php';
use Sedema\Config;

$options = getopt('', ['source:', 'target:', 'check', 'receipt-warehouse:']);
$source = (string) ($options['source'] ?? '');
$target = (string) ($options['target'] ?? 'sedema_integrada');
$check = array_key_exists('check', $options);
function identifier(string $name): string {
    if (!preg_match('/^[a-zA-Z0-9_]{1,64}$/D', $name)) throw new RuntimeException('Nombre de base o tabla inválido.');
    return '`' . $name . '`';
}
function tableRows(PDO $db, string $database, string $table): array {
    return $db->query('SELECT * FROM ' . identifier($database) . '.' . identifier($table))->fetchAll(PDO::FETCH_ASSOC);
}
function keyValue(array $row, array $columns): ?string {
    $key=[];
    foreach($columns as $column) {
        if (!isset($row[$column])) return null;
        // Stable representation for signed/unsigned integer values returned by PDO.
        $key[]=(string)$row[$column];
    }
    return json_encode($key, JSON_THROW_ON_ERROR);
}
try {
    identifier($source); identifier($target);
    if ($source === $target) throw new RuntimeException('Origen y destino deben ser diferentes.');
    $db = new PDO('mysql:host='.Config::get('DB_HOST','127.0.0.1').';port='.Config::get('DB_PORT','3306').';charset=utf8mb4',Config::get('DB_USERNAME','root'),Config::get('DB_PASSWORD',''),[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
    $db->exec("SET SESSION sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    $s=$db->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_TYPE=\'BASE TABLE\'');
    $s->execute([$source]);$sourceTables=$s->fetchAll(PDO::FETCH_COLUMN);
    $s->execute([$target]);$targetTables=$s->fetchAll(PDO::FETCH_COLUMN);
    if (!$sourceTables || !$targetTables) throw new RuntimeException('Importá el origen y ejecutá install.sql sobre un destino vacío antes de migrar.');
    $version=$db->query('SELECT COUNT(*) FROM '.identifier($target).".schema_migration WHERE version='020_integration_20261023'")->fetchColumn();
    if (!(int)$version) throw new RuntimeException('El destino no tiene el esquema integrado 020.');
    $seeds=['inventory_category','inventory_unit','inventory_warehouse','schema_migration'];
    foreach($targetTables as $table) {
        if (!in_array($table,$seeds,true) && (int)$db->query('SELECT COUNT(*) FROM '.identifier($target).'.'.identifier($table))->fetchColumn()>0) throw new RuntimeException('El destino contiene operaciones. Usá otra base vacía; no se sobrescribe información.');
    }
    foreach(array_diff($sourceTables,$targetTables) as $table) {
        if((int)$db->query('SELECT COUNT(*) FROM '.identifier($source).'.'.identifier($table))->fetchColumn()>0) throw new RuntimeException('Tabla adicional con datos: '.$table.'. Requiere revisión; no se omite silenciosamente.');
    }
    $s=$db->prepare('SELECT TABLE_NAME,COLUMN_NAME,IS_NULLABLE,COLUMN_DEFAULT,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? ORDER BY ORDINAL_POSITION');
    $s->execute([$target]);$columns=[];
    foreach($s as $column) $columns[$column['TABLE_NAME']][$column['COLUMN_NAME']]=$column;
    $records=[];$sourceCounts=[];$repairs=[];
    $sourceOrders=[];
    if(in_array('pedido',$sourceTables,true)) foreach(tableRows($db,$source,'pedido') as $o) $sourceOrders[(int)$o['idPedido']]=$o;
    foreach($targetTables as $table) {
        $records[$table]=[];
        if(!in_array($table,$sourceTables,true)) continue;
        $rows=tableRows($db,$source,$table);$sourceCounts[$table]=count($rows);
        foreach($rows as $row) {
            if($table==='despacho' && empty($row['idWarehouse'])) {
                $warehouse=(int)($sourceOrders[(int)$row['idPedido']]['idWarehouse']??0);
                if($warehouse<1) throw new RuntimeException('Despacho sin depósito ni depósito de pedido asociado. Requiere revisión.');
                $row['idWarehouse']=$warehouse;
                $repairs[]='Despacho #'.$row['idDespacho'].': depósito '.$warehouse.' tomado de su pedido #'.$row['idPedido'];
            }
            if($table==='detallepedido' && $row['idInventoryProduct']===null && $row['idProducto']!==null) throw new RuntimeException('Detalle de pedido sin equivalencia explícita a inventory_product. Revisar antes de migrar.');
            if($table==='detallepedido' && $row['idInventoryProduct']!==null && $row['idProducto']!==null && (string)$row['idInventoryProduct']!==(string)$row['idProducto']) throw new RuntimeException('Detalle con referencias de producto contradictorias. Requiere equivalencia explícita.');
            if($table==='recepcioncompra' && empty($row['idWarehouse'])) {
                $warehouse=(int)($options['receipt-warehouse']??0);
                if($warehouse<1) throw new RuntimeException('Recepciones sin depósito. Indicá --receipt-warehouse=ID solo tras confirmar el depósito de TODAS esas recepciones.');
                $row['idWarehouse']=$warehouse;
            }
            $record=array_intersect_key($row,$columns[$table]);
            foreach($columns[$table] as $name=>$column) {
                if (!array_key_exists($name,$record) && $column['IS_NULLABLE']==='NO' && $column['COLUMN_DEFAULT']===null && !str_contains($column['EXTRA'],'auto_increment')) throw new RuntimeException('Falta un campo obligatorio en '.$table.'.'.$name.'.');
                if(array_key_exists($name,$record) && $record[$name]===null && $column['IS_NULLABLE']==='NO') throw new RuntimeException('Valor nulo incompatible en '.$table.'.'.$name.'.');
            }
            $records[$table][]=$record;
        }
    }
    $s=$db->prepare('SELECT TABLE_NAME,INDEX_NAME,COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND NON_UNIQUE=0 ORDER BY SEQ_IN_INDEX');
    $s->execute([$target]);$unique=[];
    foreach($s as $index) $unique[$index['TABLE_NAME']][$index['INDEX_NAME']][]=$index['COLUMN_NAME'];
    foreach($unique as $table=>$indexes) foreach($indexes as $name=>$fields) {
        $seen=[];
        foreach($records[$table] as $row) {
            $key=keyValue($row,$fields);if($key===null) continue;
            // MySQL also checks collation-sensitive unique keys during the transaction.
            if(isset($seen[$key]))throw new RuntimeException('Duplicado incompatible con '.$table.'.'.$name.'. No se renumera automáticamente.');
            $seen[$key]=true;
        }
    }
    $s=$db->prepare('SELECT TABLE_NAME,CONSTRAINT_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=? AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY ORDINAL_POSITION');
    $s->execute([$target]);$foreign=[];$dependencies=[];
    foreach($s as $fk) {
        $key=$fk['TABLE_NAME'].'.'.$fk['CONSTRAINT_NAME'];
        $foreign[$key]['table']=$fk['TABLE_NAME'];$foreign[$key]['parent']=$fk['REFERENCED_TABLE_NAME'];
        $foreign[$key]['columns'][]=$fk['COLUMN_NAME'];$foreign[$key]['references'][]=$fk['REFERENCED_COLUMN_NAME'];
        $dependencies[$fk['TABLE_NAME']][$fk['REFERENCED_TABLE_NAME']]=true;
    }
    foreach($foreign as $name=>$fk) {
        $parentKeys=[];
        foreach($records[$fk['parent']] as $row) {$key=keyValue($row,$fk['references']);if($key!==null)$parentKeys[$key]=true;}
        foreach($records[$fk['table']] as $row) {$key=keyValue($row,$fk['columns']);if($key!==null&&!isset($parentKeys[$key]))throw new RuntimeException('Referencia huérfana en '.$name.'. Corregir en una copia del origen.');}
    }
    $dispatchItems=[];foreach($records['itemdespacho']??[] as $item)$dispatchItems[(int)$item['idDespacho']]=true;
    foreach($records['despacho']??[] as $dispatch) {
        if(!isset($dispatchItems[(int)$dispatch['idDespacho']])) echo 'REVISAR: despacho histórico #'.$dispatch['idDespacho']." sin artículos vinculados; no se reconstruye una salida supuesta.\n";
    }
    $ordered=[];$remaining=$targetTables;
    while($remaining) {
        $advanced=false;
        foreach($remaining as $i=>$table) {
            $parents=array_keys($dependencies[$table]??[]);
            if(!array_diff($parents,$ordered)) {$ordered[]=$table;unset($remaining[$i]);$advanced=true;}
        }
        if(!$advanced)throw new RuntimeException('Dependencia circular inesperada en el esquema.');
    }
    echo "Origen: $source; destino: $target\n";
    foreach($sourceCounts as $table=>$count) echo "$table: $count filas\n";
    foreach($repairs as $repair) echo "Compleción de referencia: $repair\n";
    echo "Referencias e identificadores: compatibles.\n";
    if($check) {echo "Prevalidación completada, sin escrituras. Las restricciones CHECK y colaciones se validan al copiar.\n";exit(0);}
    // Preserve source high-water marks even when the last allocated row was deleted.
    // This DDL runs before the data transaction and never writes to the source.
    $s=$db->prepare('SELECT TABLE_NAME,AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND AUTO_INCREMENT IS NOT NULL');
    $s->execute([$source]);$counters=$s->fetchAll();
    foreach($counters as $counter) {
        if(in_array($counter['TABLE_NAME'],$targetTables,true) && ctype_digit((string)$counter['AUTO_INCREMENT'])) {
            $db->exec('ALTER TABLE '.identifier($target).'.'.identifier($counter['TABLE_NAME']).' AUTO_INCREMENT='.(string)$counter['AUTO_INCREMENT']);
        }
    }
    $db->beginTransaction();
    try {
        foreach(array_reverse($ordered) as $table) if($table!=='schema_migration') $db->exec('DELETE FROM '.identifier($target).'.'.identifier($table));
        foreach($ordered as $table) {
            foreach($records[$table] as $row) {
                $fields=array_keys($row);
                $sql='INSERT '.($table==='schema_migration'?'IGNORE ':'').'INTO '.identifier($target).'.'.identifier($table).' ('.implode(',',array_map('identifier',$fields)).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')';
                $db->prepare($sql)->execute(array_values($row));
            }
        }
        foreach($sourceCounts as $table=>$count) {
            if($table==='schema_migration')continue;
            $actual=(int)$db->query('SELECT COUNT(*) FROM '.identifier($target).'.'.identifier($table))->fetchColumn();
            if($actual!==$count)throw new RuntimeException('Cantidad de filas inesperada en '.$table.'.');
        }
        $db->commit();
        echo "Copia terminada. Origen intacto. No se fusionaron registros de una segunda base.\n";
    } catch(Throwable $error) {if($db->inTransaction())$db->rollBack();throw $error;}
} catch(Throwable $error) {
    fwrite(STDERR,'Migración detenida: '.$error->getMessage()."\n");exit(1);
}
