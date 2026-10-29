<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';
use Sedema\Authorization; use Sedema\Csrf; use Sedema\Database;
use Sedema\Payments\PaymentContext; use Sedema\Payments\PaymentPage;
use Sedema\Sales\SalesRepository;
$c = PaymentContext::boot(); $user = $c['user'];
Authorization::require($user, 'payments.manage');
$r = new SalesRepository(Database::connection());
$id = max(0, (int) ($_GET['id'] ?? 0));
$o = $r->order($id);
if (!$o) { http_response_code(404); exit('Pedido no encontrado.'); }
$reason = $r->correctionBlockedReason($id);
if ($reason) { PaymentPage::flash('error', $reason); redirect('collect.php?id=' . $id); }
PaymentPage::begin('Eliminar pedido #' . $id, 'payments', $user);
?>
<section class="inventory-panel"><h2>Eliminar pedido #<?= $id ?></h2><p>Cliente: <?= e($o['apellido'] . ', ' . $o['nombre']) ?>. Total: $<?= number_format((float) $o['total'], 2, ',', '.') ?>.</p><p>Se eliminará este pedido y sus artículos. Esta acción no se puede deshacer.</p><form method="post" action="action.php"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="action" value="delete-order"><input type="hidden" name="idPedido" value="<?= $id ?>"><input type="hidden" name="orderVersion" value="<?= e($r->correctionVersion($id)) ?>"><div class="form-actions"><a class="button button-secondary" href="collect.php?id=<?= $id ?>">Conservar pedido</a><button class="button button-danger" type="submit">Eliminar definitivamente</button></div></form></section>
<?php PaymentPage::end(); ?>
