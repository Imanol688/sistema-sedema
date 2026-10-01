<?php
declare(strict_types=1);

namespace Sedema\Logistics;

use Sedema\Authorization;
use Sedema\Csrf;

final class LogisticsPage
{
    /** @param array<string,mixed> $user */
    public static function begin(string $title,string $active,array $user): void
    {
        $name=trim((string)($user['name']??$user['username']));$initial=mb_strtoupper(mb_substr($name,0,1));
        $roles=['ADMINISTRADOR'=>'Administrador','DEPOSITO'=>'Depósito','LOGISTICA'=>'Logística'];$role=$roles[(string)($user['role']??'')]??(string)($user['role']??'Usuario');$flash=self::pullFlash(); ?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Despacho y logística SEDEMA."><title><?=e($title)?> | Logística SEDEMA</title><link rel="stylesheet" href="../assets/css/styles.css"></head>
<body class="dashboard-page inventory-page logistics-page"><div class="dashboard-shell"><aside class="dashboard-sidebar" id="dashboard-sidebar">
<div class="sidebar-brand"><div class="logo-crop dashboard-logo"><img src="../assets/img/sedema-logo.png" alt="SEDEMA S.R.L."></div><div><strong>SEDEMA</strong><span>Despacho y logística</span></div></div>
<nav class="sidebar-nav" aria-label="Navegación de logística"><p class="nav-label">Sistema</p><a class="nav-item" href="../dashboard.php"><?=self::icon('home')?><span>Panel principal</span></a><p class="nav-label">Logística</p>
<?=self::nav('index.php','overview','Despachos',$active)?><?php if(Authorization::can($user,'logistics.manage')): ?><?=self::nav('dispatch.php','dispatch','Programar despacho',$active)?><?=self::nav('vehicles.php','vehicles','Flota',$active)?><?php endif; ?></nav>
<div class="sidebar-footer"><div class="sidebar-user"><span class="user-avatar"><?=e($initial)?></span><div><strong><?=e($name)?></strong><span><?=e($role)?></span></div></div><form method="post" action="../logout.php"><input type="hidden" name="csrf_token" value="<?=e(Csrf::token())?>"><button class="sidebar-logout" type="submit">Cerrar sesión <span>→</span></button></form></div></aside>
<button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Cerrar menú"></button><main class="dashboard-workspace"><header class="dashboard-topbar inventory-topbar"><button class="menu-button" type="button" data-sidebar-open><?=self::icon('menu')?><span class="sr-only">Abrir menú</span></button><div><p class="topbar-kicker">Módulo de logística</p><h1><?=e($title)?></h1></div><div class="topbar-profile"><span class="user-avatar"><?=e($initial)?></span><div><strong><?=e($name)?></strong><span><?=e($role)?></span></div></div></header><div class="dashboard-content inventory-content">
<?php if($flash): ?><div class="alert <?=$flash['type']==='success'?'alert-success':'alert-error'?> inventory-alert" role="alert"><span><?=$flash['type']==='success'?'✓':'!'?></span><p><?=e($flash['message'])?></p></div><?php endif;
    }
    public static function end(): void { ?></div></main></div><script src="../assets/js/dashboard.js" defer></script><script src="../assets/js/logistics.js" defer></script></body></html><?php }
    public static function flash(string $type,string $message): void { $_SESSION['logistics_flash']=['type'=>$type,'message'=>$message]; }
    /** @return array{type:string,message:string}|null */ private static function pullFlash(): ?array {$f=$_SESSION['logistics_flash']??null;unset($_SESSION['logistics_flash']);return is_array($f)&&isset($f['type'],$f['message'])?$f:null;}
    private static function nav(string $href,string $key,string $label,string $active): string {return '<a class="nav-item '.($key===$active?'is-active':'').'" href="'.$href.'">'.self::icon($key).'<span>'.e($label).'</span></a>';}
    public static function icon(string $name): string {$i=['home'=>'<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v10h13V10"/>','overview'=>'<path d="M3 6h11v11H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="19" r="2"/><circle cx="18" cy="19" r="2"/>','dispatch'=>'<path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 19h16"/>','vehicles'=>'<path d="M5 16h14l-1.5-6h-11z"/><circle cx="8" cy="18" r="2"/><circle cx="16" cy="18" r="2"/>','menu'=>'<path d="M4 6h16M4 12h16M4 18h16"/>','plus'=>'<path d="M12 5v14M5 12h14"/>','print'=>'<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6z"/>'];return '<svg viewBox="0 0 24 24" aria-hidden="true">'.($i[$name]??$i['overview']).'</svg>';}
}
